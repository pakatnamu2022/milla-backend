<?php

namespace App\Http\Controllers\ap\comercial;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\comercial\IndexBusinessPartnersRequest;
use App\Http\Requests\ap\comercial\StoreBusinessPartnersRequest;
use App\Http\Requests\ap\comercial\UpdateBusinessPartnersRequest;
use App\Http\Services\ap\comercial\BusinessPartnersService;
use Illuminate\Http\Request;

class BusinessPartnersController extends Controller
{
  protected BusinessPartnersService $service;

  public function __construct(BusinessPartnersService $service)
  {
    $this->service = $service;
  }

  public function index(IndexBusinessPartnersRequest $request)
  {
    return $this->service->list($request);
  }

  public function store(StoreBusinessPartnersRequest $request)
  {
    try {
      if ($request->input('company_id') == 1) {
        $request->merge(['status_gp' => true]);
      } elseif ($request->input('company_id') == 2) {
        $request->merge(['status_ap' => true]);
      } elseif ($request->input('company_id') == 3) {
        $request->merge(['status_tp' => true]);
      } elseif ($request->input('company_id') == 4) {
        $request->merge(['status_dp' => true]);
      }
      return $this->success($this->service->store($request->validated()));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function show($id)
  {
    try {
      return $this->success($this->service->show($id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function update(UpdateBusinessPartnersRequest $request, $id)
  {
    try {
      $data = $request->validated();
      $data['id'] = $id;
      return $this->success($this->service->update($data));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function destroy($id)
  {
    try {
      return $this->service->destroy($id);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function removeType(Request $request, $id)
  {
    $request->validate([
      'type' => 'required|in:CLIENTE,PROVEEDOR'
    ]);

    return $this->service->destroy($id, $request->type);
  }
}
