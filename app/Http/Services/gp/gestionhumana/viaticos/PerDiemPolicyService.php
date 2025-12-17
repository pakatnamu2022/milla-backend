<?php

namespace App\Http\Services\gp\gestionhumana\viaticos;

use App\Http\Resources\gp\gestionhumana\viaticos\PerDiemPolicyResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\gp\gestionhumana\viaticos\PerDiemPolicy;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Exception;

class PerDiemPolicyService extends BaseService implements BaseServiceInterface
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      PerDiemPolicy::class,
      $request,
      PerDiemPolicy::filters,
      PerDiemPolicy::sorts,
      PerDiemPolicyResource::class,
    );
  }

  public function find($id)
  {
    $perDiemPolicy = PerDiemPolicy::where('id', $id)->first();
    if (!$perDiemPolicy) {
      throw new Exception('Política de viáticos no encontrada');
    }
    return $perDiemPolicy;
  }

  public function store(mixed $data)
  {
    $perDiemPolicy = PerDiemPolicy::create($data);
    return new PerDiemPolicyResource($perDiemPolicy);
  }

  public function show($id)
  {
    return new PerDiemPolicyResource($this->find($id));
  }

  public function update(mixed $data)
  {
    $perDiemPolicy = $this->find($data['id']);
    $perDiemPolicy->update($data);
    return new PerDiemPolicyResource($perDiemPolicy);
  }

  public function destroy($id)
  {
    $perDiemPolicy = $this->find($id);
    DB::transaction(function () use ($perDiemPolicy) {
      $perDiemPolicy->delete();
    });
    return response()->json(['message' => 'Política de viáticos eliminada exitosamente.'], 200);
  }
}
