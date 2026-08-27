<?php

namespace App\Http\Services\ap\postventa\taller;

use App\Http\Resources\ap\postventa\taller\ApCampaignScheduleResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\postventa\taller\ApCampaignSchedule;
use App\Models\gp\gestionhumana\asistencias\AttendanceSync;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApCampaignScheduleService extends BaseService
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      ApCampaignSchedule::class,
      $request,
      ApCampaignSchedule::filters,
      ApCampaignSchedule::sorts,
      ApCampaignScheduleResource::class,
    );
  }

  public function find($id): ApCampaignSchedule
  {
    $record = ApCampaignSchedule::find($id);
    if (!$record) {
      throw new Exception('Registro de campaña no encontrado.');
    }
    return $record;
  }

  /**
   * Crea o actualiza registros de cronograma de campaña de manera masiva
   *
   * @param array $data - Debe contener: sede_id, worker_id, dates[]
   * @return array - Array de recursos creados/actualizados
   */
  public function storeOrUpdate(array $data): array
  {
    return DB::transaction(function () use ($data) {
      $sedeId = $data['sede_id'];
      $workerId = $data['worker_id'];
      $dates = $data['dates'];
      $createdBy = auth()->id();

      // Validar que todas las fechas sean del mes actual o futuras
      $currentMonth = Carbon::now()->startOfMonth();
      foreach ($dates as $date) {
        $dateCarbon = Carbon::parse($date);
        if ($dateCarbon->startOfMonth()->lt($currentMonth)) {
          throw new Exception('No se pueden registrar fechas de meses pasados. Solo puede modificar el mes actual o futuros.');
        }
      }

      // Validar que el técnico no tenga asistencias registradas en las fechas seleccionadas
      foreach ($dates as $date) {
        $existingAttendance = AttendanceSync::where('person_id', $workerId)
          ->whereDate('date', $date)
          ->first();

        if ($existingAttendance) {
          $formattedDate = Carbon::parse($date)->format('d/m/Y');
          throw new Exception("El técnico ya tiene registrada una asistencia el {$formattedDate}, no se puede asignar a la campaña.");
        }
      }

      // Obtener el rango de fechas enviadas para determinar qué mes(es) se están actualizando
      $datesParsed = array_map(fn($date) => Carbon::parse($date), $dates);
      $minDate = min($datesParsed);
      $maxDate = max($datesParsed);

      // Solo eliminar registros del mismo mes que se está actualizando
      // No tocar meses anteriores ni futuros que no estén en el rango
      $startOfMinMonth = $minDate->copy()->startOfMonth();
      $endOfMaxMonth = $maxDate->copy()->endOfMonth();

      // Eliminar (hard delete) solo los registros del worker en el rango de meses que se están actualizando
      ApCampaignSchedule::where('worker_id', $workerId)
        ->whereBetween('date', [$startOfMinMonth, $endOfMaxMonth])
        ->delete();

      // Crear los nuevos registros
      $createdRecords = [];
      foreach ($dates as $date) {
        $record = ApCampaignSchedule::create([
          'sede_id' => $sedeId,
          'worker_id' => $workerId,
          'date' => $date,
          'created_by' => $createdBy,
        ]);
        $createdRecords[] = new ApCampaignScheduleResource($record);
      }

      return $createdRecords;
    });
  }

  public function show($id): ApCampaignScheduleResource
  {
    return new ApCampaignScheduleResource($this->find($id));
  }

  public function destroy($id): void
  {
    $record = $this->find($id);

    // Validar que la fecha no sea de un mes pasado
    $recordDate = Carbon::parse($record->date);
    $currentMonth = Carbon::now()->startOfMonth();

    if ($recordDate->startOfMonth()->lt($currentMonth)) {
      throw new Exception('No se pueden eliminar registros de meses pasados.');
    }

    DB::transaction(function () use ($record) {
      $record->delete();
    });
  }

  /**
   * Obtiene las fechas registradas de un worker en un rango de fechas
   * Útil para que el frontend muestre qué fechas ya están registradas
   *
   * @param int $workerId
   * @param string $startDate - Fecha inicio (formato Y-m-d)
   * @param string $endDate - Fecha fin (formato Y-m-d)
   * @return array - Array de fechas en formato Y-m-d
   */
  public function getWorkerScheduleDates(int $workerId, string $startDate, string $endDate): array
  {
    $schedules = ApCampaignSchedule::where('worker_id', $workerId)
      ->whereBetween('date', [$startDate, $endDate])
      ->orderBy('date', 'asc')
      ->get();

    return $schedules->map(function ($schedule) {
      return [
        'id' => $schedule->id,
        'date' => $schedule->date->format('Y-m-d'),
        'sede_id' => $schedule->sede_id,
        'sede_name' => $schedule->sede?->abreviatura,
      ];
    })->toArray();
  }
}
