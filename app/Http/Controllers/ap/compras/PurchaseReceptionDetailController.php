<?php

namespace App\Http\Controllers\ap\compras;

use App\Http\Controllers\Controller;
use App\Models\ap\compras\PurchaseReceptionDetail;
use Illuminate\Http\Request;

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

      $detail = PurchaseReceptionDetail::find($id);

      if (!$detail) {
        return $this->error('Detalle de recepción no encontrado.');
      }

      $detail->update([
        'is_credit_note' => $request->is_credit_note,
      ]);

      return $this->success([
        'message' => 'Campo is_credit_note actualizado correctamente.',
        'data' => $detail->fresh(),
      ]);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}