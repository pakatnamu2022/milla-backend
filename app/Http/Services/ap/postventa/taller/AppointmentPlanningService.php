<?php

namespace App\Http\Services\ap\postventa\taller;

use App\Http\Resources\ap\postventa\taller\AppointmentPlanningResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\postventa\taller\AppointmentPlanning;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AppointmentPlanningService extends BaseService implements BaseServiceInterface
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      AppointmentPlanning::class,
      $request,
      AppointmentPlanning::filters,
      AppointmentPlanning::sorts,
      AppointmentPlanningResource::class,
    );
  }

  public function find($id)
  {
    $appointmentPlanning = AppointmentPlanning::where('id', $id)->first();
    if (!$appointmentPlanning) {
      throw new Exception('Planificación de cita no encontrada');
    }
    return $appointmentPlanning;
  }

  public function store(mixed $data)
  {
    $appointmentPlanning = AppointmentPlanning::create($data);
    return new AppointmentPlanningResource($appointmentPlanning);
  }

  public function show($id)
  {
    return new AppointmentPlanningResource($this->find($id));
  }

  public function update(mixed $data)
  {
    $appointmentPlanning = $this->find($data['id']);
    $appointmentPlanning->update($data);
    return new AppointmentPlanningResource($appointmentPlanning);
  }

  public function destroy($id)
  {
    $appointmentPlanning = $this->find($id);
    DB::transaction(function () use ($appointmentPlanning) {
      $appointmentPlanning->delete();
    });
    return response()->json(['message' => 'Planificación de cita eliminada correctamente']);
  }
}
