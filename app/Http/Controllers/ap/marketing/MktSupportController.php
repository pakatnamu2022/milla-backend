<?php

namespace App\Http\Controllers\ap\marketing;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\marketing\IndexMktSupportRequest;
use App\Http\Requests\ap\marketing\StoreMktSupportRequest;
use App\Http\Requests\ap\marketing\UpdateMktSupportRequest;
use App\Http\Services\ap\marketing\MktSupportService;

class MktSupportController extends Controller
{
  protected MktSupportService $service;

  public function __construct(MktSupportService $service)
  {
    $this->service = $service;
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
      $files = $request->file('files', []);
      unset($data['files'], $data['file'], $data['file_path']);

      return $this->success($this->service->store($data, $files));
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
      $files = $request->file('files', []);
      unset($data['files'], $data['file'], $data['file_path']);

      return $this->success($this->service->update($data, $files));
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

  /** Elimina una imagen/archivo puntual de un sustento. */
  public function destroyFile($id, $fileId)
  {
    try {
      return $this->success($this->service->deleteFile((int) $id, (int) $fileId));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
