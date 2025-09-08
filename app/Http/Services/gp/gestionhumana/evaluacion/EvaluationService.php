<?php

namespace App\Http\Services\gp\gestionhumana\evaluacion;

use App\Http\Resources\gp\gestionhumana\evaluacion\EvaluationResource;
use App\Http\Resources\gp\gestionhumana\personal\WorkerResource;
use App\Http\Resources\gp\gestionsistema\PositionResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionhumana\evaluacion\Evaluation;
use App\Models\gp\gestionhumana\evaluacion\EvaluationCycle;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonResult;
use App\Models\gp\gestionsistema\Person;
use App\Models\gp\gestionsistema\Position;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EvaluationService extends BaseService
{
  protected EvaluationPersonService $evaluationPersonService;
  protected EvaluationPersonResultService $evaluationPersonResultService;

  public function __construct(
    EvaluationPersonService       $evaluationPersonService,
    EvaluationPersonResultService $evaluationPersonResultService
  )
  {
    $this->evaluationPersonService = $evaluationPersonService;
    $this->evaluationPersonResultService = $evaluationPersonResultService;
  }


  public function list(Request $request)
  {
    return $this->getFilteredResults(
      Evaluation::class,
      $request,
      Evaluation::filters,
      Evaluation::sorts,
      EvaluationResource::class,
    );
  }

  public function participants(int $id)
  {
    $evaluation = $this->find($id);
    $personsInCycle = EvaluationPersonResult::where('evaluation_id', $evaluation->id)
      ->select('person_id')
      ->distinct()
      ->get()
      ->pluck('person_id')
      ->toArray();
    $persons = Person::whereIn('id', $personsInCycle)->get();
    return WorkerResource::collection($persons);
  }

  public function positions(int $id)
  {
    $evaluation = $this->find($id);
    $personsInCycle = EvaluationPersonResult::where('evaluation_id', $evaluation->id)
      ->select('person_id')
      ->distinct()
      ->get()
      ->pluck('person_id')
      ->toArray();
    $positionsIds = Person::whereIn('id', $personsInCycle)->select('cargo_id')->distinct()->get()->pluck('cargo_id')->toArray();
    $positions = Position::whereIn('id', $positionsIds)->get();
    return PositionResource::collection($positions);
  }
//
//  public function categories(int $id)
//  {
//    $evaluation = $this->find($id);
//    $personsInCycle = EvaluationPersonResult::where('evaluation_id', $evaluation->id)
//      ->select('person_id')
//      ->distinct()
//      ->get()
//      ->pluck('person_id')
//      ->toArray();
//    $positionsIds = Person::whereIn('id', $personsInCycle)->select('cargo_id')->distinct()->get()->pluck('cargo_id')->toArray();
//    $positions = Position::whereIn('id', $positionsIds)->get();
//    return PositionResource::collection($positions);
//  }

  public function enrichData($data)
  {
    $cycle = EvaluationCycle::find($data['cycle_id']);
    $data['objective_parameter_id'] = $cycle->parameter_id;
    $data['period_id'] = $cycle->period_id;
    return $data;
  }

  public function find($id)
  {
    $evaluationCompetence = Evaluation::where('id', $id)->first();
    if (!$evaluationCompetence) {
      throw new Exception('Evaluación no encontrada');
    }
    return $evaluationCompetence;
  }

  public function store($data)
  {
    $data = $this->enrichData($data);
    $evaluation = Evaluation::create($data);
    $this->evaluationPersonResultService->storeMany($evaluation->id);
    $this->evaluationPersonService->storeMany($evaluation->id);
    return new EvaluationResource($evaluation);
  }

  public function show($id)
  {
    return new EvaluationResource($this->find($id));
  }

  public function update($data)
  {
    $evaluationCompetence = $this->find($data['id']);
    $data = $this->enrichData($data);
    $evaluationCompetence->update($data);
    return new EvaluationResource($evaluationCompetence);
  }

  public function destroy($id)
  {
    $evaluationCompetence = $this->find($id);
    DB::transaction(function () use ($evaluationCompetence) {
      $evaluationCompetence->delete();
    });
    return response()->json(['message' => 'Evaluación eliminada correctamente']);
  }
}
