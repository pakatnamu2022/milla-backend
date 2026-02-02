<?php

namespace App\Http\Controllers\gp\tics;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\tics\IndexPhoneLineRequest;
use App\Http\Requests\gp\tics\StorePhoneLineRequest;
use App\Http\Requests\gp\tics\UpdatePhoneLineRequest;
use App\Http\Services\gp\tics\PhoneLineService;
use Illuminate\Http\Request;
use Throwable;

class PhoneLineController extends Controller
{
  protected PhoneLineService $service;

  public function __construct(PhoneLineService $service)
  {
    $this->service = $service;
  }

  public function index(IndexPhoneLineRequest $request)
  {
    return $this->service->list($request);
  }

  public function store(StorePhoneLineRequest $request)
  {
    $data = $request->validated();
    return response()->json($this->service->store($data));
  }

  public function show($id)
  {
    try {
      return response()->json($this->service->show($id));
    } catch (\Exception $e) {
      return response()->json(['message' => $e->getMessage()], 404);
    }
  }

  public function update(UpdatePhoneLineRequest $request, $id)
  {
    try {
      $data = $request->validated();
      $data['id'] = $id;
      return response()->json($this->service->update($data));
    } catch (\Exception $e) {
      return response()->json(['message' => $e->getMessage()], 404);
    }
  }

  public function destroy($id)
  {
    try {
      return $this->service->destroy($id);
    } catch (\Exception $e) {
      return response()->json(['message' => $e->getMessage()], 404);
    }
  }

  /**
   * Importar líneas telefónicas desde un archivo Excel
   */
  public function import(Request $request)
  {
    $request->validate([
      'file' => 'required|file|mimes:xlsx,xls,csv|max:10240'
    ]);

    if (!$request->hasFile('file') || !$request->file('file')->isValid()) {
      return $this->error('Archivo no encontrado o no válido. Asegúrate de enviar un archivo con el campo "file".');
    }

    try {
      $result = $this->service->importFromExcel($request->file('file'));

      if ($result['success']) {
        return $this->success($result);
      } else {
        return $this->error($result['message']);
      }
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
