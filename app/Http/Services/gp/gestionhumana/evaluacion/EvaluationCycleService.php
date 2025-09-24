<?php

namespace App\Http\Services\gp\gestionhumana\evaluacion;

use App\Http\Resources\gp\gestionhumana\evaluacion\EvaluationCycleResource;
use App\Http\Resources\gp\gestionhumana\evaluacion\HierarchicalCategoryResource;
use App\Http\Resources\gp\gestionhumana\personal\WorkerResource;
use App\Http\Resources\gp\gestionsistema\PositionResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Http\Services\ExportService;
use App\Models\gp\gestionhumana\evaluacion\EvaluationCycle;
use App\Models\gp\gestionhumana\evaluacion\EvaluationCycleCategoryDetail;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonCycleDetail;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonResult;
use App\Models\gp\gestionhumana\evaluacion\HierarchicalCategory;
use App\Models\gp\gestionsistema\Person;
use App\Models\gp\gestionsistema\Position;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EvaluationCycleService extends BaseService implements BaseServiceInterface
{
  protected $exportService;


  public function __construct(
    ExportService $exportService
  )
  {
    $this->exportService = $exportService;
  }

  public function export(Request $request)
  {
    return $this->exportService->exportFromRequest($request, EvaluationCycle::class);
  }

  public function enrichData(array $data)
  {
    $data['start_date_objectives'] = $data['start_date'];
    $data['end_date_objectives'] = $data['end_date'];
    return $data;
  }

  public function list(Request $request)
  {
    return $this->getFilteredResults(
      EvaluationCycle::class,
      $request,
      EvaluationCycle::filters,
      EvaluationCycle::sorts,
      EvaluationCycleResource::class,
    );
  }

  public function participants(int $id)
  {
    $cycle = $this->find($id);
    $personsInCycle = EvaluationPersonCycleDetail::where('cycle_id', $cycle->id)
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
    $cycle = $this->find($id);
    $positionsInCycle = EvaluationPersonCycleDetail::where('cycle_id', $cycle->id)
      ->select('position_id')
      ->distinct()
      ->get()
      ->pluck('position_id')
      ->toArray();
    $positions = Position::whereIn('id', $positionsInCycle)->get();
    return PositionResource::collection($positions);
  }

  public function categories(int $id)
  {
    $cycle = $this->find($id);
    $categoriesInCycle = EvaluationPersonCycleDetail::where('cycle_id', $cycle->id)
      ->select('category_id')
      ->distinct()
      ->get()
      ->pluck('category_id')
      ->toArray();
    $categories = HierarchicalCategory::whereIn('id', $categoriesInCycle)->get();
    return HierarchicalCategoryResource::collection($categories);
  }

  public function store($data)
  {
    $data = $this->enrichData($data);
    $evaluationCycle = EvaluationCycle::create($data);
    return new EvaluationCycleResource(EvaluationCycle::find($evaluationCycle->id));
  }

  public function find($id)
  {
    $evaluationCycle = EvaluationCycle::find($id);
    if (!$evaluationCycle) {
      throw new Exception('Ciclo no encontrado');
    }
    return $evaluationCycle;
  }

  public function show($id)
  {
    return new EvaluationCycleResource($this->find($id));
  }

  public function update($data)
  {
    $evaluationCycle = $this->find($data['id']);
    $evaluationCycle->update($data);
    return new EvaluationCycleResource($evaluationCycle);
  }

  public function destroy($id)
  {
    $evaluationCycle = $this->find($id);
    DB::transaction(function () use ($evaluationCycle) {
      // 1) Borra (soft-delete) los detalles de categorías del ciclo
      $categoryDetails = EvaluationCycleCategoryDetail::where('cycle_id', $evaluationCycle->id)->get();
      foreach ($categoryDetails as $detail) {
        $detail->delete();
      }
      // 2) Borra (soft-delete) los detalles de persona del ciclo
      $personCycleDetails = EvaluationPersonCycleDetail::where('cycle_id', $evaluationCycle->id)->get();
      foreach ($personCycleDetails as $detail) {
        $detail->delete();
      }
      // 3) Borra (soft-delete) el ciclo
      $evaluationCycle->delete();
    });

    return response()->json(['message' => 'Ciclo eliminado correctamente']);
  }
}
