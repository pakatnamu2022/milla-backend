<?php

namespace App\Http\Controllers\ap\comercial;

use App\Http\Controllers\Controller;
use App\Http\Services\ap\comercial\ApDeliveryChecklistService;
use Illuminate\Http\Request;

class ApDeliveryChecklistController extends Controller
{
  protected ApDeliveryChecklistService $service;

  public function __construct(ApDeliveryChecklistService $service)
  {
    $this->service = $service;
  }

  /**
   * Obtiene el checklist existente o retorna los ítems sugeridos para inicializarlo.
   */
  public function getOrInitialize(int $vehicleDeliveryId)
  {
    try {
      return $this->success($this->service->getOrInitialize($vehicleDeliveryId));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Crea el checklist con sus ítems.
   * Body: { vehicle_delivery_id, observations?, items?: [{description, quantity?, unit?, source?, source_id?, observations?}] }
   */
  public function store(Request $request)
  {
    try {
      $data = $request->validate([
        'vehicle_delivery_id'     => 'required|integer|exists:ap_vehicle_delivery,id',
        'observations'            => 'nullable|string|max:1000',
        'items'                   => 'nullable|array',
        'items.*.description'     => 'required_with:items|string|max:255',
        'items.*.quantity'        => 'nullable|numeric|min:0.01',
        'items.*.unit'            => 'nullable|string|max:50',
        'items.*.source'          => 'nullable|in:reception,purchase_order,manual',
        'items.*.source_id'       => 'nullable|integer',
        'items.*.is_confirmed'    => 'nullable|boolean',
        'items.*.observations'    => 'nullable|string|max:500',
      ]);

      return $this->success($this->service->store($data));
    } catch (\Illuminate\Validation\ValidationException $e) {
      return $this->errorValidation($e->errors());
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Actualiza el header del checklist (observaciones).
   */
  public function update(Request $request, int $id)
  {
    try {
      $data = $request->validate([
        'observations' => 'nullable|string|max:1000',
      ]);

      return $this->success($this->service->update($id, $data));
    } catch (\Illuminate\Validation\ValidationException $e) {
      return $this->errorValidation($e->errors());
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Confirma el checklist (prerequisito para generar la guía de remisión).
   */
  public function confirm(int $id)
  {
    try {
      return $this->success($this->service->confirm($id));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Agrega un ítem manual al checklist.
   */
  public function addItem(Request $request, int $id)
  {
    try {
      $data = $request->validate([
        'description'  => 'required|string|max:255',
        'quantity'     => 'nullable|numeric|min:0.01',
        'unit'         => 'nullable|string|max:50',
        'observations' => 'nullable|string|max:500',
      ]);

      return $this->success($this->service->addItem($id, $data));
    } catch (\Illuminate\Validation\ValidationException $e) {
      return $this->errorValidation($e->errors());
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Actualiza un ítem del checklist (marcar como confirmado, editar observaciones, etc).
   */
  public function updateItem(Request $request, int $id, int $itemId)
  {
    try {
      $data = $request->validate([
        'is_confirmed' => 'nullable|boolean',
        'observations' => 'nullable|string|max:500',
        'quantity'     => 'nullable|numeric|min:0.01',
        'description'  => 'nullable|string|max:255',
        'unit'         => 'nullable|string|max:50',
      ]);

      return $this->success($this->service->updateItem($id, $itemId, $data));
    } catch (\Illuminate\Validation\ValidationException $e) {
      return $this->errorValidation($e->errors());
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Elimina un ítem del checklist.
   */
  public function removeItem(int $id, int $itemId)
  {
    try {
      return $this->success($this->service->removeItem($id, $itemId));
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Genera y descarga el PDF profesional del checklist de entrega.
   */
  public function generatePdf(int $id)
  {
    try {
      return $this->service->generatePdf($id);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
