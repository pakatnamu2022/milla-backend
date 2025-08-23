<?php

namespace App\Http\Services\gp\gestionhumana\evaluacion;

use App\Http\Resources\gp\gestionhumana\evaluacion\EvaluationPersonDetailResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonDetail;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EvaluationPersonDetailService extends BaseService implements BaseServiceInterface
{

  public function list(Request $request)
  {
    return $this->getFilteredResults(
      EvaluationPersonDetail::class,
      $request,
      [],
      [],
      EvaluationPersonDetailResource::class
    );
  }

  public function store(mixed $data)
  {
    $evaluationPerson = EvaluationPersonDetail::create($data);
    return new EvaluationPersonDetailResource($evaluationPerson);
  }

  public function find(int $id)
  {
    $evaluationPerson = EvaluationPersonDetail::where('id', $id)->first();
    if (!$evaluationPerson) {
      throw new Exception('Detalle de Evaluacion Persona no encontrado');
    }
    return $evaluationPerson;
  }

  public function update(mixed $data)
  {
    // TODO: Implement update() method.
  }

  public function destroy(int $id)
  {
    $evaluationPerson = $this->find($id);
    DB::transaction(function () use ($evaluationPerson) {
      $evaluationPerson->delete();
    });
    return response()->json(['message' => 'Detalle de Evaluacion Persona eliminado correctamente']);
  }
}
