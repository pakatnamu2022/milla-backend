<?php

namespace App\Http\Controllers\ap\comercial;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\comercial\IndexBusinessPartnersEstablishmentRequest;
use App\Http\Requests\ap\comercial\UpdateBusinessPartnersEstablishmentRequest;
use App\Http\Services\ap\comercial\BusinessPartnersEstablishmentService;

class BusinessPartnersEstablishmentController extends Controller
{
  protected BusinessPartnersEstablishmentService $service;

  public function __construct(BusinessPartnersEstablishmentService $service)
  {
    $this->service = $service;
  }

  public function index(IndexBusinessPartnersEstablishmentRequest $request)
  {
    return $this->service->list($request);
  }

  public function update(UpdateBusinessPartnersEstablishmentRequest $request, $id)
  {
    try {
      $data = $request->validated();
      $data['id'] = $id;
      return $this->success($this->service->update($data));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
