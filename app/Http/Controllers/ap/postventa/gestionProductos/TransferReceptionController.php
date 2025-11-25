<?php

namespace App\Http\Controllers\ap\postventa\gestionProductos;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\postventa\gestionProductos\StoreTransferReceptionRequest;
use App\Http\Services\ap\postventa\gestionProductos\TransferReceptionService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransferReceptionController extends Controller
{
  protected TransferReceptionService $service;

  public function __construct(TransferReceptionService $service)
  {
    $this->service = $service;
  }

  /**
   * Display a listing of transfer receptions
   *
   * @param Request $request
   * @return JsonResponse
   */
  public function index(Request $request)
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Display the specified transfer reception
   *
   * @param int $id
   * @return JsonResponse
   */
  public function show(int $id)
  {
    try {
      return $this->success($this->service->show($id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Create a new transfer reception
   * This receives products from a TRANSFER_OUT movement and creates TRANSFER_IN
   *
   * @param StoreTransferReceptionRequest $request
   * @return JsonResponse
   */
  public function store(StoreTransferReceptionRequest $request): JsonResponse
  {
    $request->validated();
    try {
      $reception = $this->service->createReception($request->all());

      return $this->success([
        'message' => 'Recepción de transferencia creada exitosamente',
        'reception' => $reception,
        'transfer_in_created' => true,
        'stock_updated' => true,
      ]);
    } catch (Exception $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Delete transfer reception and reverse stock movements
   *
   * @param int $id
   * @return JsonResponse
   */
  public function destroy(int $id): JsonResponse
  {
    try {
      $result = $this->service->destroy($id);
      return $this->success($result);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }
}