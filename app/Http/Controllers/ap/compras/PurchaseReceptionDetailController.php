<?php

namespace App\Http\Controllers\ap\compras;

use App\Http\Controllers\Controller;
use App\Http\Services\ap\postventa\taller\ApSupplierOrderService;
use App\Models\ap\compras\PurchaseReceptionDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use function PHPUnit\Framework\isNull;

class PurchaseReceptionDetailController extends Controller
{
  /**
   * Update is_credit_note field
   */
  public function updateCreditNote(Request $request, $id)
  {
    try {
      $request->validate([
        'is_credit_note' => 'required|boolean',
      ]);

      $detail = PurchaseReceptionDetail::with('reception.supplierOrder')->find($id);

      if (!$detail) {
        return $this->error('Detalle de recepción no encontrado.');
      }

      if ($detail->reception->purchase_order_id !== null) {
        return $this->error('No se puede actualizar porque la recepción ya tiene asociado a una factura.');
      }

      DB::transaction(function () use ($detail, $request) {
        $detail->update([
          'is_credit_note' => $request->is_credit_note,
        ]);

        // Recalcular el reception_type del ApSupplierOrder si existe
        if ($detail->reception && $detail->reception->supplierOrder) {
          $supplierOrderService = new ApSupplierOrderService();
          $supplierOrderService->updateReceptionType($detail->reception->supplierOrder);
        }
      });

      return $this->success([
        'message' => 'Campo is_credit_note actualizado correctamente.',
        'data' => $detail->fresh(),
      ]);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
