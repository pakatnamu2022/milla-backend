<?php

namespace App\Http\Services\ap\comercial;

use App\Http\Resources\ap\comercial\ApDeliveryChecklistResource;
use App\Models\ap\comercial\ApDeliveryChecklist;
use App\Models\ap\comercial\ApDeliveryChecklistItem;
use App\Models\ap\comercial\ApVehicleDelivery;
use App\Models\ap\comercial\Vehicles;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ApDeliveryChecklistService
{
  /**
   * Obtiene el checklist existente o lo inicializa con ítems de la recepción y la OC.
   */
  public function getOrInitialize(int $vehicleDeliveryId): ApDeliveryChecklistResource
  {
    $delivery = $this->findDelivery($vehicleDeliveryId);

    $checklist = ApDeliveryChecklist::with('items')
      ->where('vehicle_delivery_id', $vehicleDeliveryId)
      ->first();

    if ($checklist) {
      return new ApDeliveryChecklistResource($checklist);
    }

    return DB::transaction(function () use ($delivery, $vehicleDeliveryId) {
      $checklist = ApDeliveryChecklist::create([
        'vehicle_delivery_id' => $vehicleDeliveryId,
        'status' => ApDeliveryChecklist::STATUS_DRAFT,
        'created_by' => auth()->id(),
      ]);

      foreach ($this->buildSuggestedItems($delivery) as $item) {
        ApDeliveryChecklistItem::create([
          'delivery_checklist_id' => $checklist->id,
          'source' => $item['source'],
          'source_id' => $item['source_id'] ?? null,
          'description' => $item['description'],
          'quantity' => $item['quantity'] ?? 1,
          'unit' => $item['unit'] ?? null,
          'is_confirmed' => false,
          'observations' => null,
          'sort_order' => $item['sort_order'],
        ]);
      }

      return new ApDeliveryChecklistResource($checklist->load('items'));
    });
  }

  /**
   * Crea el checklist con sus ítems.
   */
  public function store(array $data): ApDeliveryChecklistResource
  {
    return DB::transaction(function () use ($data) {
      $vehicleDeliveryId = $data['vehicle_delivery_id'];
      $this->findDelivery($vehicleDeliveryId);

      $existing = ApDeliveryChecklist::where('vehicle_delivery_id', $vehicleDeliveryId)->first();
      if ($existing) {
        throw new Exception('Ya existe un checklist para esta programación de entrega');
      }

      $checklist = ApDeliveryChecklist::create([
        'vehicle_delivery_id' => $vehicleDeliveryId,
        'observations' => $data['observations'] ?? null,
        'status' => ApDeliveryChecklist::STATUS_DRAFT,
        'created_by' => auth()->id(),
      ]);

      $items = $data['items'] ?? [];

      foreach ($items as $index => $item) {
        ApDeliveryChecklistItem::create([
          'delivery_checklist_id' => $checklist->id,
          'source' => $item['source'] ?? ApDeliveryChecklistItem::SOURCE_MANUAL,
          'source_id' => $item['source_id'] ?? null,
          'description' => $item['description'],
          'quantity' => $item['quantity'] ?? 1,
          'unit' => $item['unit'] ?? null,
          'is_confirmed' => $item['is_confirmed'] ?? false,
          'observations' => $item['observations'] ?? null,
          'sort_order' => $item['sort_order'] ?? $index,
        ]);
      }

      return new ApDeliveryChecklistResource($checklist->load('items'));
    });
  }

  /**
   * Actualiza el header del checklist.
   */
  public function update(int $id, array $data): ApDeliveryChecklistResource
  {
    $checklist = $this->findChecklist($id);

    if ($checklist->isConfirmed()) {
      throw new Exception('No se puede editar un checklist ya confirmado');
    }

    $checklist->update([
      'observations' => $data['observations'] ?? $checklist->observations,
    ]);

    return new ApDeliveryChecklistResource($checklist->load('items'));
  }

  /**
   * Confirma el checklist para habilitar la generación de la guía de remisión.
   */
  public function confirm(int $id): ApDeliveryChecklistResource
  {
    $checklist = $this->findChecklist($id);

    if ($checklist->isConfirmed()) {
      throw new Exception('El checklist ya está confirmado');
    }

    if ($checklist->items()->count() === 0) {
      throw new Exception('El checklist debe tener al menos un ítem antes de confirmarlo');
    }

    $checklist->update([
      'status' => ApDeliveryChecklist::STATUS_CONFIRMED,
      'confirmed_at' => now(),
      'confirmed_by' => auth()->id(),
    ]);

    return new ApDeliveryChecklistResource($checklist->load('items'));
  }

  /**
   * Agrega un ítem al checklist.
   */
  public function addItem(int $checklistId, array $data): ApDeliveryChecklistResource
  {
    $checklist = $this->findChecklist($checklistId);

    if ($checklist->isConfirmed()) {
      throw new Exception('No se pueden agregar ítems a un checklist confirmado');
    }

    $lastOrder = $checklist->items()->max('sort_order') ?? -1;

    ApDeliveryChecklistItem::create([
      'delivery_checklist_id' => $checklist->id,
      'source' => ApDeliveryChecklistItem::SOURCE_MANUAL,
      'source_id' => null,
      'description' => $data['description'],
      'quantity' => $data['quantity'] ?? 1,
      'unit' => $data['unit'] ?? null,
      'is_confirmed' => false,
      'observations' => $data['observations'] ?? null,
      'sort_order' => $lastOrder + 1,
    ]);

    return new ApDeliveryChecklistResource($checklist->load('items'));
  }

  /**
   * Actualiza un ítem del checklist (marcar como confirmado, editar obs, etc).
   */
  public function updateItem(int $checklistId, int $itemId, array $data): ApDeliveryChecklistResource
  {
    $checklist = $this->findChecklist($checklistId);
    $item = ApDeliveryChecklistItem::where('id', $itemId)
      ->where('delivery_checklist_id', $checklistId)
      ->first();

    if (!$item) {
      throw new Exception('Ítem no encontrado en este checklist');
    }

    if ($checklist->isConfirmed() && isset($data['description'])) {
      throw new Exception('No se puede modificar la descripción de un checklist confirmado');
    }

    $fillable = [];
    if (isset($data['is_confirmed'])) $fillable['is_confirmed'] = $data['is_confirmed'];
    if (isset($data['observations'])) $fillable['observations'] = $data['observations'];
    if (isset($data['quantity']) && !$checklist->isConfirmed()) $fillable['quantity'] = $data['quantity'];
    if (isset($data['description']) && !$checklist->isConfirmed()) $fillable['description'] = $data['description'];
    if (isset($data['unit']) && !$checklist->isConfirmed()) $fillable['unit'] = $data['unit'];

    $item->update($fillable);

    return new ApDeliveryChecklistResource($checklist->load('items'));
  }

  /**
   * Elimina un ítem del checklist.
   */
  public function removeItem(int $checklistId, int $itemId): ApDeliveryChecklistResource
  {
    $checklist = $this->findChecklist($checklistId);

    if ($checklist->isConfirmed()) {
      throw new Exception('No se pueden eliminar ítems de un checklist confirmado');
    }

    $item = ApDeliveryChecklistItem::where('id', $itemId)
      ->where('delivery_checklist_id', $checklistId)
      ->first();

    if (!$item) {
      throw new Exception('Ítem no encontrado en este checklist');
    }

    $item->delete();

    return new ApDeliveryChecklistResource($checklist->load('items'));
  }

  /**
   * Genera el PDF profesional del checklist de entrega.
   */
  public function generatePdf(int $id)
  {
    $checklist = $this->findChecklist($id);
    $checklist->load(['items', 'vehicleDelivery.vehicle.model', 'vehicleDelivery.advisor', 'vehicleDelivery.client', 'vehicleDelivery.sede', 'confirmedBy']);

    $delivery = $checklist->vehicleDelivery;
    $vehicle = $delivery->vehicle;

    $pdf = Pdf::loadView('reports.ap.comercial.delivery-checklist', [
      'checklist' => $checklist,
      'delivery' => $delivery,
      'vehicle' => $vehicle,
    ]);

    $pdf->setPaper('a4', 'portrait');

    $fileName = 'Checklist_Entrega_' . str_pad($checklist->id, 6, '0', STR_PAD_LEFT) . '.pdf';

    return $pdf->download($fileName);
  }

  /**
   * Construye la lista de ítems sugeridos desde la recepción del vehículo y los accesorios de la OC.
   */
  private function buildSuggestedItems(ApVehicleDelivery $delivery): array
  {
    $items = [];
    $order = 0;

    // 1. Ítems del checklist de recepción (lo que fue recepcionado en su momento)
    $vehicle = Vehicles::with([
      'shippingGuideReceiving.receivingChecklists.receiving',
    ])->find($delivery->vehicle_id);

    $receivingGuide = $vehicle?->shippingGuideReceiving;

    Log::info($receivingGuide);

    if ($receivingGuide) {
      foreach ($receivingGuide->receivingChecklists as $rcItem) {
        $description = $rcItem->receiving?->description;
        if (!$description) continue;

        $items[] = [
          'source' => ApDeliveryChecklistItem::SOURCE_RECEPTION,
          'source_id' => $rcItem->id,
          'description' => $description,
          'quantity' => $rcItem->quantity ?? 1,
          'unit' => null,
          'is_confirmed' => false,
          'observations' => null,
          'sort_order' => $order++,
        ];
      }
    }

    // 2. Accesorios de la orden de compra
    $purchaseOrder = $vehicle?->purchaseOrder;

    if ($purchaseOrder) {
      $purchaseOrder->load('accessories.accessory');

      foreach ($purchaseOrder->accessories as $accessory) {
        $description = $accessory->accessory?->description;
        if (!$description) continue;

        $items[] = [
          'source' => ApDeliveryChecklistItem::SOURCE_PURCHASE_ORDER,
          'source_id' => $accessory->id,
          'description' => $description,
          'quantity' => $accessory->quantity ?? 1,
          'unit' => null,
          'is_confirmed' => false,
          'observations' => null,
          'sort_order' => $order++,
        ];
      }
    }

    return $items;
  }

  private function findDelivery(int $id): ApVehicleDelivery
  {
    $delivery = ApVehicleDelivery::find($id);
    if (!$delivery) {
      throw new Exception('Programación de entrega no encontrada');
    }
    return $delivery;
  }

  private function findChecklist(int $id): ApDeliveryChecklist
  {
    $checklist = ApDeliveryChecklist::find($id);
    if (!$checklist) {
      throw new Exception('Checklist de entrega no encontrado');
    }
    return $checklist;
  }
}
