<?php

namespace App\Http\Controllers\ap\marketing;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\marketing\IndexMktProposalRequest;
use App\Http\Requests\ap\marketing\StoreMktProposalRequest;
use App\Http\Requests\ap\marketing\UpdateMktProposalRequest;
use App\Http\Services\ap\marketing\MktProposalService;
use Illuminate\Http\Request;

class MktProposalController extends Controller
{
  protected MktProposalService $service;

  public function __construct(MktProposalService $service)
  {
    $this->service = $service;
  }

  public function index(IndexMktProposalRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreMktProposalRequest $request)
  {
    try {
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

  public function update(UpdateMktProposalRequest $request, $id)
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

  public function approve(Request $request, $id)
  {
    try {
      return $this->success($this->service->approve($id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function reject(Request $request, $id)
  {
    try {
      return $this->success($this->service->reject($id, $request->input('notes')));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
