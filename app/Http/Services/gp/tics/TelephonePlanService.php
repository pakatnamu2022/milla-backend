<?php

namespace App\Http\Services\gp\tics;

use App\Http\Resources\gp\tics\TelephonePlanResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\gp\tics\TelephonePlan;
use Exception;
use Illuminate\Http\Request;

class TelephonePlanService extends BaseService implements BaseServiceInterface
{
  /**
   * Lista los planes telefónicos con filtros, ordenamientos y paginación.
   * @param Request $request
   * @return \Illuminate\Http\JsonResponse
   */
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      TelephonePlan::query(),
      $request,
      TelephonePlan::filters,
      TelephonePlan::sorts,
      TelephonePlanResource::class,
    );
  }

  /**
   * Crea un nuevo plan telefónico con los datos proporcionados.
   * @param $data
   * @return TelephonePlanResource
   */
  public function store($data)
  {
    $telephonePlan = TelephonePlan::create($data);
    return new TelephonePlanResource(TelephonePlan::find($telephonePlan->id));
  }

  /**
   * Busca un plan telefónico por su ID. Si no se encuentra, lanza una excepción.
   * @param $id
   * @return mixed
   * @throws Exception
   */
  public function find($id)
  {
    $telephonePlan = TelephonePlan::where('id', $id)->first();
    if (!$telephonePlan) {
      throw new Exception('Plan telefónico no encontrado');
    }
    return $telephonePlan;
  }

  /**
   * Muestra los detalles de un plan telefónico específico por su ID. Si no se encuentra, lanza una excepción.
   * @param $id
   * @return TelephonePlanResource
   * @throws Exception
   */
  public function show($id)
  {
    return new TelephonePlanResource($this->find($id));
  }

  /**
   * Actualiza un plan telefónico existente con los datos proporcionados. Si el plan no se encuentra, lanza una excepción.
   * @param $data
   * @return TelephonePlanResource
   * @throws Exception
   */
  public function update($data)
  {
    $telephonePlan = $this->find($data['id']);
    $telephonePlan->update($data);
    return new TelephonePlanResource($telephonePlan);
  }

  /**
   * Elimina un plan telefónico por su ID. Si el plan no se encuentra, lanza una excepción.
   * @param $id
   * @return \Illuminate\Http\JsonResponse
   * @throws Exception
   */
  public function destroy($id)
  {
    $telephonePlan = $this->find($id);
    $telephonePlan->delete();
    return response()->json(['message' => 'Plan telefónico eliminado correctamente']);
  }
}
