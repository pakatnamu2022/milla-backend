<?php

namespace App\Http\Controllers\gp\gestionsistema;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionsistema\IndexDepartmentRequest;
use App\Http\Services\gp\gestionsistema\DepartmentService;

class DepartmentController extends Controller
{
  protected DepartmentService $service;

  public function __construct(DepartmentService $service)
  {
    $this->service = $service;
  }

  public function index(IndexDepartmentRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
