<?php

namespace App\Http\Services\ap\postventa\taller;

use App\Http\Resources\ap\postventa\taller\ApOrderQuotationDetailsResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\postventa\taller\ApOrderQuotationDetails;
use App\Models\ap\postventa\taller\ApOrderQuotations;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApOrderQuotationDetailsService extends BaseService implements BaseServiceInterface
{

  public function list(Request $request)
  {
    return $this->getFilteredResults(
      ApOrderQuotationDetails::class,
      $request,
      ApOrderQuotationDetails::filters,
      ApOrderQuotationDetails::sorts,
      ApOrderQuotationDetailsResource::class
    );
  }

  public function find($id)
  {
    $apOrderQuotationDetails = ApOrderQuotationDetails::with([
      'orderQuotation',
      'product',
    ])->where('id', $id)->first();

    if (!$apOrderQuotationDetails) {
      throw new Exception('Detalle de cotización no encontrado');
    }

    return $apOrderQuotationDetails;
  }

  public function store(mixed $data)
  {
    return DB::transaction(function () use ($data) {
      // Set created_at
      if (auth()->check()) {
        $data['created_at'] = auth()->user()->id;
      }

      // Create quotation detail
      $apOrderQuotationDetails = ApOrderQuotationDetails::create($data);

      // Recalculate quotation totals
      $this->updateQuotationTotals($apOrderQuotationDetails->order_quotation_id);

      return new ApOrderQuotationDetailsResource($apOrderQuotationDetails->load([
        'orderQuotation',
        'product',
      ]));
    });
  }

  public function show($id)
  {
    return new ApOrderQuotationDetailsResource($this->find($id));
  }

  public function update(mixed $data)
  {
    return DB::transaction(function () use ($data) {
      $apOrderQuotationDetails = $this->find($data['id']);

      // Update quotation detail
      $apOrderQuotationDetails->update($data);

      // Recalculate quotation totals
      $this->updateQuotationTotals($apOrderQuotationDetails->order_quotation_id);

      // Reload relations
      $apOrderQuotationDetails->load([
        'orderQuotation',
        'product',
      ]);

      return new ApOrderQuotationDetailsResource($apOrderQuotationDetails);
    });
  }

  public function destroy($id)
  {
    $apOrderQuotationDetails = $this->find($id);
    $quotationId = $apOrderQuotationDetails->order_quotation_id;

    DB::transaction(function () use ($apOrderQuotationDetails, $quotationId) {
      $apOrderQuotationDetails->delete();

      // Recalculate quotation totals
      $this->updateQuotationTotals($quotationId);
    });

    return response()->json(['message' => 'Detalle de cotización eliminado correctamente.']);
  }

  /**
   * Recalculate and update quotation totals based on all details
   *
   * @param int $quotationId
   * @return void
   */
  private function updateQuotationTotals(int $quotationId): void
  {
    // Get all details for this quotation
    $details = ApOrderQuotationDetails::where('order_quotation_id', $quotationId)->get();

    // Calculate totals
    $subtotal = 0;
    $totalDiscountAmount = 0;

    foreach ($details as $detail) {
      // Subtotal = sum of (quantity * unit_price) for all items
      $subtotal += ($detail->quantity * $detail->unit_price);

      // Total discount = sum of all discount amounts
      $totalDiscountAmount += $detail->discount;
    }

    // Calculate discount percentage
    $discountPercentage = $subtotal > 0 ? ($totalDiscountAmount / $subtotal) * 100 : 0;

    // Calculate total (subtotal - discounts, without taxes)
    $totalAmount = $subtotal - $totalDiscountAmount;

    // Update quotation
    ApOrderQuotations::where('id', $quotationId)->update([
      'subtotal' => round($subtotal, 2),
      'discount_amount' => round($totalDiscountAmount, 2),
      'discount_percentage' => round($discountPercentage, 2),
      'total_amount' => round($totalAmount, 2),
    ]);
  }
}
