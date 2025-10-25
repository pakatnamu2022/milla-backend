<?php

namespace App\Http\Services\ap\comercial;

use App\Http\Resources\ap\comercial\ApReceivingChecklistResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\comercial\ApReceivingChecklist;
use App\Models\ap\comercial\ShippingGuides;
use App\Models\ap\configuracionComercial\vehiculo\ApDeliveryReceivingChecklist;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class ApReceivingChecklistService extends BaseService implements BaseServiceInterface
{
  public function list(Request $request): JsonResponse
  {
    return $this->getFilteredResults(
      ApReceivingChecklist::class,
      $request,
      ApReceivingChecklist::filters,
      ApReceivingChecklist::sorts,
      ApReceivingChecklistResource::class
    );
  }

  public function find($id): ApReceivingChecklist
  {
    $receivingChecklist = ApReceivingChecklist::where('id', $id)->first();
    if (!$receivingChecklist) {
      throw new Exception('Checklist de recepción no encontrado');
    }
    return $receivingChecklist;
  }

  public function store(mixed $data): JsonResponse
  {
    DB::beginTransaction();
    try {
      // Validate shipping guide exists
      $shippingGuide = ShippingGuides::find($data['shipping_guide_id']);
      if (!$shippingGuide) {
        throw new Exception('Guía de envío no encontrada');
      }

      // Validate receiving_ids is an array
      if (!isset($data['receiving_ids']) || !is_array($data['receiving_ids'])) {
        throw new Exception('receiving_ids debe ser un array');
      }

      $createdRecords = [];

      // Create one record for each receiving_id
      foreach ($data['receiving_ids'] as $receivingId) {
        // Validate each receiving exists
        $receiving = ApDeliveryReceivingChecklist::find($receivingId);
        if (!$receiving) {
          throw new Exception("Checklist de recepción con ID {$receivingId} no encontrado");
        }

        // Check if combination already exists
        $exists = ApReceivingChecklist::where('receiving_id', $receivingId)
          ->where('shipping_guide_id', $data['shipping_guide_id'])
          ->first();

        if ($exists) {
          throw new Exception("Ya existe un registro para receiving_id {$receivingId} y shipping_guide_id {$data['shipping_guide_id']}");
        }

        $receivingChecklist = ApReceivingChecklist::create([
          'receiving_id' => $receivingId,
          'shipping_guide_id' => $data['shipping_guide_id'],
        ]);

        $createdRecords[] = new ApReceivingChecklistResource($receivingChecklist);
      }

      // Update shipping guide with note, is_received, received_by and received_date
      $shippingGuide->update([
        'is_received' => true,
        'note_received' => $data['note'] ?? null,
        'received_by' => auth()->id(),
        'received_date' => now(),
      ]);

      DB::commit();
      return response()->json([
        'message' => 'Registros creados correctamente',
        'data' => $createdRecords,
        'shipping_guide' => [
          'id' => $shippingGuide->id,
          'is_received' => $shippingGuide->is_received,
          'note_received' => $shippingGuide->note_received,
          'received_by' => $shippingGuide->received_by,
          'received_date' => $shippingGuide->received_date,
        ],
      ]);
    } catch (Exception $e) {
      DB::rollBack();
      throw new Exception($e->getMessage());
    }
  }

  public function show($id): ApReceivingChecklistResource
  {
    return new ApReceivingChecklistResource($this->find($id));
  }

  public function getByShippingGuide($shippingGuideId): JsonResponse
  {
    try {
      // Validate shipping guide exists
      $shippingGuide = ShippingGuides::find($shippingGuideId);
      if (!$shippingGuide) {
        throw new Exception('Guía de envío no encontrada');
      }

      // Get all checklists for this shipping guide with relationships
      $checklists = ApReceivingChecklist::where('shipping_guide_id', $shippingGuideId)
        ->with(['receiving', 'shipping_guide'])
        ->get();

      return response()->json([
        'data' => ApReceivingChecklistResource::collection($checklists),
        'note_received' => $shippingGuide->note_received,
      ]);
    } catch (Exception $e) {
      throw new Exception($e->getMessage());
    }
  }

  public function update(mixed $data): JsonResponse
  {
    DB::beginTransaction();
    try {
      // Validate shipping guide exists
      $shippingGuide = ShippingGuides::find($data['shipping_guide_id']);
      if (!$shippingGuide) {
        throw new Exception('Guía de envío no encontrada');
      }

      // Validate receiving_ids is an array
      if (!isset($data['receiving_ids']) || !is_array($data['receiving_ids'])) {
        throw new Exception('receiving_ids debe ser un array');
      }

      // Get existing records for this shipping guide
      $existingRecords = ApReceivingChecklist::where('shipping_guide_id', $data['shipping_guide_id'])->get();
      $existingReceivingIds = $existingRecords->pluck('receiving_id')->toArray();
      $newReceivingIds = $data['receiving_ids'];

      // Determine which to delete (in existing but not in new)
      $toDelete = array_diff($existingReceivingIds, $newReceivingIds);

      // Determine which to add (in new but not in existing)
      $toAdd = array_diff($newReceivingIds, $existingReceivingIds);

      // Delete removed records
      if (!empty($toDelete)) {
        ApReceivingChecklist::where('shipping_guide_id', $data['shipping_guide_id'])
          ->whereIn('receiving_id', $toDelete)
          ->delete();
      }

      // Add new records
      foreach ($toAdd as $receivingId) {
        // Validate receiving exists
        $receiving = ApDeliveryReceivingChecklist::find($receivingId);
        if (!$receiving) {
          throw new Exception("Checklist de recepción con ID {$receivingId} no encontrado");
        }

        ApReceivingChecklist::create([
          'receiving_id' => $receivingId,
          'shipping_guide_id' => $data['shipping_guide_id'],
        ]);
      }

      // Update shipping guide with note, is_received, received_by and received_date
      $shippingGuide->update([
        'is_received' => true,
        'note_received' => $data['note'] ?? null,
        'received_by' => auth()->id(),
        'received_date' => now(),
      ]);

      // Get updated records
      $updatedRecords = ApReceivingChecklist::where('shipping_guide_id', $data['shipping_guide_id'])
        ->get()
        ->map(fn($record) => new ApReceivingChecklistResource($record));

      DB::commit();
      return response()->json([
        'data' => $updatedRecords,
        'note_received' => $shippingGuide->note_received,
      ]);
    } catch (Exception $e) {
      DB::rollBack();
      throw new Exception($e->getMessage());
    }
  }

  public function destroy($id): JsonResponse
  {
    DB::beginTransaction();
    try {
      $receivingChecklist = $this->find($id);
      $receivingChecklist->delete();
      DB::commit();
      return response()->json(['message' => 'Checklist de recepción eliminado correctamente']);
    } catch (Exception $e) {
      DB::rollBack();
      throw new Exception($e->getMessage());
    }
  }

  public function destroyByShippingGuide($shippingGuideId): JsonResponse
  {
    DB::beginTransaction();
    try {
      $deleted = ApReceivingChecklist::where('shipping_guide_id', $shippingGuideId)->delete();
      DB::commit();
      return response()->json([
        'message' => 'Registros eliminados correctamente',
        'deleted' => $deleted,
      ]);
    } catch (Exception $e) {
      DB::rollBack();
      throw new Exception($e->getMessage());
    }
  }
}
