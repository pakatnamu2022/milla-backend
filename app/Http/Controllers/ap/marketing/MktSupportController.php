<?php

namespace App\Http\Controllers\ap\marketing;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\marketing\IndexMktSupportRequest;
use App\Http\Requests\ap\marketing\StoreMktSupportRequest;
use App\Http\Requests\ap\marketing\UpdateMktSupportRequest;
use App\Http\Services\ap\marketing\MktSupportService;
use App\Http\Services\gp\gestionsistema\DigitalFileService;

class MktSupportController extends Controller
{
  protected MktSupportService $service;
  protected DigitalFileService $digitalFileService;

  public function __construct(MktSupportService $service, DigitalFileService $digitalFileService)
  {
    $this->service = $service;
    $this->digitalFileService = $digitalFileService;
  }

  public function index(IndexMktSupportRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreMktSupportRequest $request)
  {
    try {
      $data = $request->validated();

      if ($request->hasFile('file')) {
        $uploaded = $this->digitalFileService->store($request->file('file'), '/ap/marketing/supports/');
        $data['file_path'] = $uploaded->url;
      }
      unset($data['file']);

      return $this->success($this->service->store($data));
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

  public function update(UpdateMktSupportRequest $request, $id)
  {
    try {
      $data = $request->validated();
      $data['id'] = $id;

      if ($request->hasFile('file')) {
        $uploaded = $this->digitalFileService->store($request->file('file'), '/ap/marketing/supports/');
        $data['file_path'] = $uploaded->url;
      }
      unset($data['file']);

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
}
