<?php

namespace App\Http\Services\gp\gestionsistema;

use App\Http\Resources\gp\gestionsistema\CompanyBranchResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionsistema\CompanyBranch;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\DB;

class CompanyBranchService extends BaseService
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      CompanyBranch::whereNotNull('company_id')->orderBy('company_id', 'asc'),
      $request,
      CompanyBranch::filters,
      CompanyBranch::sorts,
      CompanyBranchResource::class,
    );
  }

  public function store($data)
  {
    $sede = CompanyBranch::create($data);
    return new CompanyBranchResource(CompanyBranch::find($sede->id));
  }

  public function show($id)
  {
    $sede = CompanyBranch::find($id);
    if (!$sede) {
      throw new Exception('Sede no encontrada');
    }
    return new CompanyBranchResource($sede);
  }

  public function update($data)
  {
    $sede = CompanyBranch::find($data['id']);
    if (!$sede) {
      throw new Exception('Sede no encontrada');
    }
    $sede->update($data);
    return new CompanyBranchResource($sede);
  }

  public function destroy($id)
  {
    $sede = $this->find($id);
    DB::transaction(function () use ($sede) {
      $sede->delete();
    });
    return response()->json(['message' => 'Sede eliminado correctamente']);
  }
}
