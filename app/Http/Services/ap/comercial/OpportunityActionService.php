<?php

namespace App\Http\Services\ap\comercial;

use App\Http\Resources\ap\comercial\OpportunityActionResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\comercial\OpportunityAction;
use App\Models\ap\comercial\Opportunity;
use App\Models\ap\ApCommercialMasters;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OpportunityActionService extends BaseService implements BaseServiceInterface
{
  /**
   * Verificar si una oportunidad está cerrada (GANADA o PERDIDA)
   */
  protected function isOpportunityClosed($opportunityId)
  {
    $opportunity = Opportunity::find($opportunityId);
    if (!$opportunity) {
      throw new Exception('Oportunidad no encontrada');
    }

    $status = ApCommercialMasters::find($opportunity->opportunity_status_id);
    return $status && in_array($status->code, ['WON', 'LOST']);
  }

  public function list(Request $request)
  {
    return $this->getFilteredResults(
      OpportunityAction::class,
      $request,
      OpportunityAction::filters,
      OpportunityAction::sorts,
      OpportunityActionResource::class,
    );
  }

  public function find($id)
  {
    $action = OpportunityAction::where('id', $id)->first();
    if (!$action) {
      throw new Exception('Acción no encontrada');
    }
    return $action;
  }

  public function store(mixed $data)
  {
    DB::beginTransaction();
    try {
      // Validar que la oportunidad no esté cerrada
      if ($this->isOpportunityClosed($data['opportunity_id'])) {
        throw new Exception('No se pueden agregar acciones a una oportunidad cerrada (ganada o perdida)');
      }

      $data['datetime'] = now();
      $action = OpportunityAction::create($data);
      DB::commit();
      return new OpportunityActionResource($action);
    } catch (Exception $e) {
      DB::rollBack();
      throw new Exception($e->getMessage());
    }
  }

  public function show($id)
  {
    return new OpportunityActionResource($this->find($id));
  }

  public function update(mixed $data)
  {
    DB::beginTransaction();
    try {
      $action = $this->find($data['id']);

      // Validar que la oportunidad no esté cerrada
      if ($this->isOpportunityClosed($action->opportunity_id)) {
        throw new Exception('No se pueden editar acciones de una oportunidad cerrada (ganada o perdida)');
      }

      $action->update($data);
      DB::commit();
      return new OpportunityActionResource($action);
    } catch (Exception $e) {
      DB::rollBack();
      throw new Exception($e->getMessage());
    }
  }

  public function destroy($id)
  {
    DB::beginTransaction();
    try {
      $action = $this->find($id);

      // Validar que la oportunidad no esté cerrada
      if ($this->isOpportunityClosed($action->opportunity_id)) {
        throw new Exception('No se pueden eliminar acciones de una oportunidad cerrada (ganada o perdida)');
      }

      $action->delete();
      DB::commit();
      return response()->json(['message' => 'Acción eliminada correctamente']);
    } catch (Exception $e) {
      DB::rollBack();
      throw new Exception($e->getMessage());
    }
  }
}
