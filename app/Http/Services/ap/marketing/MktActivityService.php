<?php

namespace App\Http\Services\ap\marketing;

use App\Http\Resources\ap\marketing\MktActivityLocationResource;
use App\Http\Resources\ap\marketing\MktActivityResource;
use App\Http\Resources\ap\marketing\MktSupportResource;
use App\Http\Services\ap\marketing\Concerns\NormalizesUppercaseText;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\marketing\MktActivity;
use App\Models\ap\marketing\MktActivityLocation;
use App\Models\ap\marketing\MktSupport;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MktActivityService extends BaseService implements BaseServiceInterface
{
  use NormalizesUppercaseText;

  const UPPERCASE_FIELDS = [
    'name', 'activity_type', 'channel', 'responsible', 'objective', 'description', 'notes',
  ];

  public function list(Request $request)
  {
    return $this->getFilteredResults(
      MktActivity::query()->with(['budget:id,type,plan_id', 'currency:id,name,code,symbol', 'supplier:id,full_name']),
      $request,
      MktActivity::filters,
      MktActivity::sorts,
      MktActivityResource::class
    );
  }

  public function find(int $id): MktActivity
  {
    $activity = MktActivity::with(['budget', 'currency', 'supplier', 'locations', 'proposals', 'kpis'])->find($id);
    if (!$activity) {
      throw new Exception('Actividad no encontrada');
    }
    return $activity;
  }

  public function store(mixed $data): MktActivityResource
  {
    $data = $this->normalizeUpperFields($data, self::UPPERCASE_FIELDS);
    DB::beginTransaction();
    try {
      $activity = MktActivity::create($data);
      $activity->load(['budget', 'currency', 'supplier', 'locations', 'proposals', 'kpis']);
      DB::commit();
      return new MktActivityResource($activity);
    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function show(int $id): MktActivityResource
  {
    return new MktActivityResource($this->find($id));
  }

  public function update(mixed $data): MktActivityResource
  {
    $activity = $this->find($data['id']);
    $data = $this->normalizeUpperFields($data, self::UPPERCASE_FIELDS);
    DB::beginTransaction();
    try {
      $activity->update($data);
      $activity->load(['budget', 'currency', 'supplier', 'locations', 'proposals', 'kpis']);
      DB::commit();
      return new MktActivityResource($activity);
    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function destroy(int $id): array
  {
    DB::beginTransaction();
    try {
      $activity = $this->find($id);
      $activity->delete();
      DB::commit();
      return ['message' => 'Actividad eliminada correctamente'];
    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function addLocation(int $activityId, mixed $data): MktActivityLocationResource
  {
    $activity = $this->find($activityId);
    DB::beginTransaction();
    try {
      $data['activity_id'] = $activity->id;
      $location = MktActivityLocation::create($data);
      $location->load(['sede', 'currency']);
      DB::commit();
      return new MktActivityLocationResource($location);
    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function addSupport(int $activityId, mixed $data): MktSupportResource
  {
    $activity = $this->find($activityId);
    DB::beginTransaction();
    try {
      $data['activity_id'] = $activity->id;
      $support = MktSupport::create($data);
      $support->load(['supplier', 'currency', 'purchaseOrder']);
      DB::commit();
      return new MktSupportResource($support);
    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function getActivityTypes()
  {
    $types = MktActivity::select('activity_type')
      ->distinct()
      ->whereNotNull('activity_type')
      ->orderBy('activity_type')
      ->pluck('activity_type');

    return response()->json(['data' => $types]);
  }

  public function getChannels()
  {
    $channels = MktActivity::select('channel')
      ->distinct()
      ->whereNotNull('channel')
      ->orderBy('channel')
      ->pluck('channel');

    return response()->json(['data' => $channels]);
  }

  /**
   * Genera el PDF con el detalle de los sustentos registrados para la actividad.
   */
  public function generateSupportsPdf(int $id)
  {
    $activity = MktActivity::with(['budget.plan', 'currency', 'supplier'])->find($id);
    if (!$activity) {
      throw new Exception('Actividad no encontrada');
    }

    $supports = MktSupport::where('activity_id', $id)
      ->with(['supplier', 'currency'])
      ->orderBy('issue_date')
      ->get();

    $totalsByCurrency = $supports
      ->groupBy(fn($s) => $s->currency->symbol ?? $s->currency->code ?? 'S/')
      ->map(fn($group) => $group->sum('amount'));

    $pdf = Pdf::loadView('reports.ap.marketing.activity-supports', [
      'activity' => $activity,
      'supports' => $supports,
      'totalsByCurrency' => $totalsByCurrency,
    ]);

    $pdf->setPaper('a4', 'portrait');

    $fileName = 'Sustentos_Actividad_' . str_pad($activity->id, 6, '0', STR_PAD_LEFT) . '.pdf';

    return $pdf->download($fileName);
  }

  public function changeStatus(int $id, string $status): MktActivityResource
  {
    $validStatuses = [
      MktActivity::STATUS_PLANNED,
      MktActivity::STATUS_IN_PROGRESS,
      MktActivity::STATUS_EXECUTED,
      MktActivity::STATUS_CANCELLED,
    ];

    if (!in_array($status, $validStatuses)) {
      throw new Exception('Estado no válido');
    }

    $activity = $this->find($id);
    DB::beginTransaction();
    try {
      $activity->update(['status' => $status]);
      DB::commit();
      return new MktActivityResource($activity);
    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }
}
