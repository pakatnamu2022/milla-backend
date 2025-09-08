<?php

namespace App\Http\Services\gp\gestionsistema;

use App\Http\Resources\gp\gestionsistema\DepartmentResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionsistema\Department;
use Illuminate\Http\Request;

class DepartmentService extends BaseService
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      Department::class,
      $request,
      Department::filters,
      Department::sorts,
      DepartmentResource::class,
    );
  }
}
