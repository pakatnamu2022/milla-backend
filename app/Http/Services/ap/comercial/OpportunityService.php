<?php

namespace App\Http\Services\ap\comercial;

use App\Http\Resources\ap\comercial\OpportunityActionResource;
use App\Http\Resources\ap\comercial\OpportunityResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Http\Utils\Constants;
use App\Models\ap\comercial\Opportunity;
use App\Models\ap\comercial\OpportunityAction;
use App\Models\ap\ApMasters;
use App\Models\ap\comercial\PotentialBuyers;
use App\Models\ap\configuracionComercial\venta\ApAssignmentLeadership;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OpportunityService extends BaseService implements BaseServiceInterface
{
  /**
   * Obtener los IDs de trabajadores que el usuario puede ver
   * (él mismo + su equipo si es jefe en el periodo actual)
   */
  protected function getAccessibleWorkerIds($workerId)
  {
    $workerIds = [$workerId];

    $currentYear = Carbon::now()->year;
    $currentMonth = Carbon::now()->month;

    // Obtener trabajadores a cargo del jefe en el periodo actual
    $teamMembers = ApAssignmentLeadership::where('boss_id', $workerId)
      ->where('year', $currentYear)
      ->where('month', $currentMonth)
      ->where('status', true)
      ->pluck('worker_id')
      ->toArray();

    return array_merge($workerIds, $teamMembers);
  }

  public function list(Request $request)
  {
    return $this->getFilteredResults(
      Opportunity::class,
      $request,
      Opportunity::filters,
      Opportunity::sorts,
      OpportunityResource::class,
    );
  }

  public function find($id)
  {
    $opportunity = Opportunity::where('id', $id)->first();
    if (!$opportunity) {
      throw new Exception('Oportunidad no encontrada');
    }
    return $opportunity;
  }

  public function store(mixed $data)
  {
    $data['worker_id'] = auth()->user()->partner_id;
    DB::beginTransaction();
    try {
      $opportunity = Opportunity::create($data);
      $lead = $opportunity->lead();
      $lead->update(['use' => PotentialBuyers::USED]);
      DB::commit();
      return new OpportunityResource($opportunity);
    } catch (Exception $e) {
      DB::rollBack();
      throw new Exception($e->getMessage());
    }
  }

  public function show($id)
  {
    return new OpportunityResource($this->find($id));
  }

  public function update(mixed $data)
  {
    DB::beginTransaction();
    try {
      $opportunity = $this->find($data['id']);
      $opportunity->update($data);
      DB::commit();
      return new OpportunityResource($opportunity);
    } catch (Exception $e) {
      DB::rollBack();
      throw new Exception($e->getMessage());
    }
  }

  public function destroy($id)
  {
    DB::beginTransaction();
    try {
      $opportunity = $this->find($id);
      $opportunity->delete();
      DB::commit();
      return response()->json(['message' => 'Oportunidad eliminada correctamente']);
    } catch (Exception $e) {
      DB::rollBack();
      throw new Exception($e->getMessage());
    }
  }

  /**
   * Crear oportunidad desde un cliente específico
   */
  public function storeFromClient($clientId, mixed $data)
  {
    $data['client_id'] = $clientId;
    return $this->store($data);
  }

  /**
   * Obtener acciones de una oportunidad
   */
  public function getOpportunityActions($opportunityId)
  {
    $opportunity = $this->find($opportunityId);
    $actions = OpportunityAction::where('opportunity_id', $opportunityId)
      ->orderBy('datetime', 'desc')
      ->get();

    return OpportunityActionResource::collection($actions);
  }

  public function close($id, $message)
  {
    DB::beginTransaction();
    try {
      $opportunity = $this->find($id);
      if ($opportunity->is_closed) throw new Exception('La oportunidad ya está cerrada');
      $status = ApMasters::where('code', Opportunity::CLOSED)->whereNull('deleted_at')->first();
      $opportunity->update(['opportunity_status_id' => $status->id, 'comment' => $message]);
      DB::commit();
      return new OpportunityResource($opportunity);
    } catch (Exception $e) {
      DB::rollBack();
      throw new Exception($e->getMessage());
    }
  }

  /**
   * Obtener oportunidades del asesor (con permisos de jefe)
   */
  public function getMyOpportunities(Request $request, $workerId, $requestWorkerId, $canViewAllUsers)
  {
    $workerIdToUse = $workerId;
    if ($canViewAllUsers && $requestWorkerId) {
      $workerIdToUse = $requestWorkerId;
    }

    $accessibleWorkerIds = $this->getAccessibleWorkerIds($workerIdToUse);

    $query = Opportunity::whereIn('worker_id', $accessibleWorkerIds)
      ->with(['worker', 'client', 'family', 'opportunityType', 'clientStatus', 'opportunityStatus']);

    return $this->getFilteredResults(
      $query,
      $request,
      Opportunity::filters,
      Opportunity::sorts,
      OpportunityResource::class,
    );
  }

  /**
   * Obtener agenda del asesor (acciones agrupadas por fecha)
   */
  public function getMyAgenda(Request $request, $workerId, $requestWorkerId, $canViewAllUsers)
  {
    $workerIdToUse = $workerId;
    if ($canViewAllUsers && $requestWorkerId) {
      $workerIdToUse = $requestWorkerId;
    }

    $accessibleWorkerIds = $this->getAccessibleWorkerIds($workerIdToUse);

    $query = OpportunityAction::whereHas('opportunity', function ($q) use ($accessibleWorkerIds) {
      $q->whereIn('worker_id', $accessibleWorkerIds);
    })->with(['opportunity.client', 'actionType', 'actionContactType']);

    // Filtros de fecha
    if ($request->has('date_from')) {
      $query->whereDate('datetime', '>=', $request->date_from);
    }

    if ($request->has('date_to')) {
      $query->whereDate('datetime', '<=', $request->date_to);
    }

    $actions = $query->orderBy('datetime', 'asc')->get();

    // Agrupar por fecha
    $groupedActions = $actions->groupBy(function ($action) {
      return Carbon::parse($action->datetime)->format('Y-m-d');
    });

    return $groupedActions->map(function ($actions, $date) {
      return [
        'date' => $date,
        'count' => $actions->count(),
        'count_positive_result' => $actions->where('result', 1)->count(),
        'count_negative_result' => $actions->where('result', 0)->count(),
        'actions' => OpportunityActionResource::collection($actions),
      ];
    })->values();
  }

  /**
   * Obtener datos necesarios para generar una solicitud de cotización desde una oportunidad
   * Sin lógica adicional, solo exposición de datos
   */
  public function getRequestData($id)
  {
    $opportunity = $this->find($id);

    // Cargar relaciones necesarias
    $opportunity->load(['client', 'worker', 'family']);

    return response()->json([
      'opportunity_id' => $opportunity->id,
      'holder_id' => $opportunity->client_id,
      'client' => [
        'id' => $opportunity->client->id,
        'name' => $opportunity->client->card_name,
        'num_doc' => $opportunity->client->num_doc,
        'phone' => $opportunity->client->phone1,
        'email' => $opportunity->client->e_mail,
      ],
      'worker_id' => $opportunity->worker_id,
      'worker' => [
        'id' => $opportunity->worker->id,
        'full_name' => $opportunity->worker->FullName,
        'email' => $opportunity->worker->email,
      ],
      'family_id' => $opportunity->family_id,
      'family' => $opportunity->family ? [
        'id' => $opportunity->family->id,
        'description' => $opportunity->family->description,
      ] : null,
      'opportunity_status' => [
        'id' => $opportunity->opportunityStatus->id,
        'code' => $opportunity->opportunityStatus->code,
        'description' => $opportunity->opportunityStatus->description,
      ],
      'client_status' => $opportunity->clientStatus ? [
        'id' => $opportunity->clientStatus->id,
        'code' => $opportunity->clientStatus->code,
        'description' => $opportunity->clientStatus->description,
      ] : null,
      'comment' => $opportunity->comment,
      'created_at' => $opportunity->created_at,
    ]);
  }
}
