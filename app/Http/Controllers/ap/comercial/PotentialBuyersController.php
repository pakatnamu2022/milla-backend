<?php

namespace App\Http\Controllers\ap\comercial;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\comercial\IndexPotentialBuyersRequest;
use App\Http\Requests\ap\comercial\StorePotentialBuyersRequest;
use App\Http\Services\ap\comercial\PotentialBuyersService;
use Illuminate\Http\Request;

class PotentialBuyersController extends Controller
{
  protected PotentialBuyersService $service;

  public function __construct(PotentialBuyersService $service)
  {
    $this->service = $service;
  }

  public function index(IndexPotentialBuyersRequest $request)
  {
    return $this->service->list($request);
  }

  public function store(StorePotentialBuyersRequest $request)
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

  public function destroy($id)
  {
    try {
      return $this->service->destroy($id);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function import(Request $request)
  {
    $request->validate([
      'file' => 'required|file|mimes:xlsx,xls,csv|max:10240'
    ]);

    // Verificar que el archivo fue recibido correctamente
    if (!$request->hasFile('file') || !$request->file('file')->isValid()) {
      return $this->error('Archivo no encontrado o no válido. Asegúrate de enviar un archivo con el campo "file".');
    }

    try {
      $result = $this->service->importFromExcel($request->file('file'));

      if ($result['success']) {
        return $this->success($result, $result['message']);
      } else {
        return $this->error($result['message'], $result);
      }
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
