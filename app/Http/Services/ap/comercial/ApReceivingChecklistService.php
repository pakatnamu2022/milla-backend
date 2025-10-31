<?php

namespace App\Http\Services\ap\comercial;

use App\Http\Resources\ap\comercial\ApReceivingChecklistResource;
use App\Http\Services\BaseService;
use App\Jobs\SyncShippingGuideJob;
use App\Models\ap\comercial\ApReceivingChecklist;
use App\Models\ap\comercial\ShippingGuides;
use App\Models\ap\configuracionComercial\vehiculo\ApDeliveryReceivingChecklist;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApReceivingChecklistService extends BaseService
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

      // Validate items_receiving is provided and is an array/object
      if (!isset($data['items_receiving']) || !is_array($data['items_receiving'])) {
        throw new Exception('items_receiving debe ser un objeto');
      }

      // Get existing records for this shipping guide
      $existingRecords = ApReceivingChecklist::where('shipping_guide_id', $data['shipping_guide_id'])->get();
      $existingReceivingIds = $existingRecords->pluck('receiving_id')->toArray();
      $newReceivingIds = array_keys($data['items_receiving']);

      // Determine which to delete (in existing but not in new)
      $toDelete = array_diff($existingReceivingIds, $newReceivingIds);

      // Determine which to add (in new but not in existing)
      $toAdd = array_diff($newReceivingIds, $existingReceivingIds);

      // Determine which to update (in both existing and new)
      $toUpdate = array_intersect($existingReceivingIds, $newReceivingIds);

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
          'quantity' => $data['items_receiving'][$receivingId],
        ]);
      }

      // Update existing records with new quantities
      foreach ($toUpdate as $receivingId) {
        ApReceivingChecklist::where('shipping_guide_id', $data['shipping_guide_id'])
          ->where('receiving_id', $receivingId)
          ->update(['quantity' => $data['items_receiving'][$receivingId]]);
      }

      // Despachar el Job síncronamente para debugging
      SyncShippingGuideJob::dispatchSync($shippingGuide->id);

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
