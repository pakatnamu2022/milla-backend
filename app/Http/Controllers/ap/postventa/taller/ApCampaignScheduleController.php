<?php

namespace App\Http\Controllers\ap\postventa\taller;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\postventa\taller\IndexApCampaignScheduleRequest;
use App\Http\Requests\ap\postventa\taller\StoreApCampaignScheduleRequest;
use App\Http\Services\ap\postventa\taller\ApCampaignScheduleService;
use Illuminate\Http\Request;

class ApCampaignScheduleController extends Controller
{
  protected ApCampaignScheduleService $service;

  public function __construct(ApCampaignScheduleService $service)
  {
    $this->service = $service;
  }

  /**
   * Lista los registros de cronograma de campaña con filtros
   */
  public function index(IndexApCampaignScheduleRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Crea o actualiza de manera masiva los registros de cronograma de campaña
   * El frontend enviará: sede_id, worker_id, dates[]
   * Esto reemplazará todos los registros del mes(es) enviado(s) para ese worker
   */
  public function store(StoreApCampaignScheduleRequest $request)
  {
    try {
      $result = $this->service->storeOrUpdate($request->validated());
      return $this->success([
        'message' => 'Cronograma de campaña actualizado correctamente.',
        'data' => $result,
      ]);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Muestra un registro específico
   */
  public function show($id)
  {
    try {
      return $this->success($this->service->show($id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Elimina un registro específico
   * Solo se pueden eliminar registros del mes actual o futuro
   */
  public function destroy($id)
  {
    try {
      $this->service->destroy($id);
      return response()->json(['message' => 'Registro de campaña eliminado correctamente.']);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Obtiene las fechas registradas de un técnico en un rango de fechas
   * Útil para que el calendario del frontend muestre las fechas ya registradas
   *
   * Query params: worker_id, start_date, end_date
   */
  public function getWorkerSchedule(Request $request)
  {
    try {
      $request->validate([
        'worker_id' => 'required|integer|exists:rrhh_persona,id',
        'start_date' => 'required|date',
        'end_date' => 'required|date|after_or_equal:start_date',
      ]);

      $dates = $this->service->getWorkerScheduleDates(
        $request->worker_id,
        $request->start_date,
        $request->end_date
      );

      return $this->success([
        'worker_id' => $request->worker_id,
        'dates' => $dates,
      ]);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
