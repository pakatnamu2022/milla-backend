<?php

namespace App\Http\Controllers\ap\configuracionComercial\vehiculo;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\configuracionComercial\vehiculo\IndexApModelsVnRequest;
use App\Http\Requests\ap\configuracionComercial\vehiculo\StoreApModelsVnRequest;
use App\Http\Requests\ap\configuracionComercial\vehiculo\StoreAutomaticApModelsVnRequest;
use App\Http\Requests\ap\configuracionComercial\vehiculo\UpdateApModelsVnRequest;
use App\Http\Services\ap\configuracionComercial\vehiculo\ApModelsVnService;
use Illuminate\Http\Request;
use Throwable;

class ApModelsVnController extends Controller
{
  protected ApModelsVnService $service;

  public function __construct(ApModelsVnService $service)
  {
    $this->service = $service;
  }

  public function index(IndexApModelsVnRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreApModelsVnRequest $request)
  {
    try {
      return $this->success($this->service->store($request->all()));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function storeAutomatic(StoreAutomaticApModelsVnRequest $request)
  {
    try {
      return $this->success($this->service->storeAutomatic($request->validated()));
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

  public function update(UpdateApModelsVnRequest $request, $id)
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

  public function export(Request $request)
  {
    try {
      return $this->service->export($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function downloadTemplate()
  {
    try {
      return $this->service->downloadTemplate();
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function import(Request $request)
  {
    $request->validate([
      'file' => 'required|file|mimes:xlsx,xls|max:10240',
    ]);

    try {
      return $this->success($this->service->importFromExcel($request->file('file')));
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function sync($id)
  {
    try {
      return $this->success($this->service->syncModel((int)$id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function syncAll()
  {
    try {
      return $this->success($this->service->syncAll());
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function syncLogs(Request $request)
  {
    try {
      return $this->service->syncLogs($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function dynamicsPreview($id)
  {
    try {
      return $this->success($this->service->dynamicsPreview($id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function downloadVerifyTemplate()
  {
    try {
      return $this->service->downloadVerifyTemplate();
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function fixWrongCodes()
  {
    try {
      return $this->success($this->service->fixWrongCodes());
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function verify(Request $request)
  {
    $request->validate([
      'file' => 'required|file|mimes:xlsx,xls|max:10240',
    ]);

    try {
      return $this->success($this->service->verifyFromExcel($request->file('file')));
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function downloadInitialStockTemplate()
  {
    try {
      return $this->service->downloadInitialStockTemplate();
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function matchExcelTemplate()
  {
    try {
      return $this->service->matchExcelTemplate();
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function matchExcel(Request $request)
  {
    $request->validate([
      'file' => 'required|file|mimes:xlsx,xls|max:10240',
    ]);

    try {
      return $this->service->matchFromExcel($request->file('file'));
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function importInitialStock(Request $request)
  {
    $request->validate([
      'file' => 'required|file|mimes:xlsx,xls|max:10240',
    ]);

    try {
      return $this->success($this->service->importInitialStockFromExcel($request->file('file')));
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
