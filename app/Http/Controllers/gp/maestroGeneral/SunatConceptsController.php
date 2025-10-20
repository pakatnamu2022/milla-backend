<?php

namespace App\Http\Controllers\gp\maestroGeneral;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\maestroGeneral\IndexSunatConceptsRequest;
use App\Http\Services\gp\maestroGeneral\SunatConceptsService;
use Illuminate\Http\Request;

class SunatConceptsController extends Controller
{
  protected SunatConceptsService $service;

  public function __construct(SunatConceptsService $service)
  {
    $this->service = $service;
  }

  public function index(IndexSunatConceptsRequest $request)
  {
    return $this->service->list($request);
  }
}
