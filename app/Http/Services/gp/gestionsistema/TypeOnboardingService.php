<?php

namespace App\Http\Services\gp\gestionsistema;

use App\Http\Resources\gp\gestionsistema\TypeOnboardingResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\gp\gestionsistema\TypeOnboarding;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TypeOnboardingService extends BaseService implements BaseServiceInterface
{
  protected $model;
  protected $resource;

  public function __construct()
  {
    $this->model = TypeOnboarding::class;
    $this->resource = TypeOnboardingResource::class;
  }

  public function list(Request $request)
  {
    return $this->getFilteredResults(
      $this->model,
      $request,
      $this->model::filters,
      $this->model::sorts,
      $this->resource,
    );
  }

  public function find($id)
  {
    $typeOnboarding = TypeOnboarding::where('id', $id)->first();
    if (!$typeOnboarding) {
      throw new Exception('Tipo de onboarding no encontrado');
    }
    return $typeOnboarding;
  }

  public function store(mixed $data)
  {
    DB::beginTransaction();
    try {
      $typeOnboarding = TypeOnboarding::create($data);
      DB::commit();
      return new TypeOnboardingResource($typeOnboarding);
    } catch (Exception $e) {
      DB::rollBack();
      throw new Exception('Error al crear el tipo de onboarding: ' . $e->getMessage());
    }
  }

  public function show($id)
  {
    $typeOnboarding = TypeOnboarding::find($id);
    if (!$typeOnboarding) {
      throw new Exception('Tipo de onboarding no encontrado');
    }
    return new TypeOnboardingResource($typeOnboarding);
  }

  public function update(mixed $data)
  {
    DB::beginTransaction();
    try {
      $typeOnboarding = TypeOnboarding::find($data['id']);
      if (!$typeOnboarding) {
        throw new Exception('Tipo de onboarding no encontrado');
      }
      $typeOnboarding->update($data);
      DB::commit();
      return new TypeOnboardingResource($typeOnboarding);
    } catch (Exception $e) {
      DB::rollBack();
      throw new Exception('Error al actualizar el tipo de onboarding: ' . $e->getMessage());
    }
  }

  public function destroy($id)
  {
    DB::beginTransaction();
    try {
      $typeOnboarding = TypeOnboarding::find($id);
      if (!$typeOnboarding) {
        throw new Exception('Tipo de onboarding no encontrado');
      }
      $typeOnboarding->update(['status_deleted' => 0]);
      DB::commit();
      return response()->json(['message' => 'Tipo de onboarding eliminado correctamente']);
    } catch (Exception $e) {
      DB::rollBack();
      throw new Exception('Error al eliminar el tipo de onboarding: ' . $e->getMessage());
    }
  }
}
