<?php

namespace App\Http\Services\ap\comercial;

use App\Http\Resources\ap\comercial\PurchaseRequestQuoteResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\comercial\DetailsApprovedAccessoriesQuote;
use App\Models\ap\comercial\DiscountCoupons;
use App\Models\ap\comercial\PurchaseRequestQuote;
use App\Models\ap\maestroGeneral\TypeCurrency;
use App\Models\ap\postventa\ApprovedAccessories;
use App\Models\gp\maestroGeneral\ExchangeRate;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseRequestQuoteService extends BaseService implements BaseServiceInterface
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      PurchaseRequestQuote::class,
      $request,
      PurchaseRequestQuote::filters,
      PurchaseRequestQuote::sorts,
      PurchaseRequestQuoteResource::class,
    );
  }

  public function find($id)
  {
    $PurchaseRequestQuote = PurchaseRequestQuote::where('id', $id)->first();
    if (!$PurchaseRequestQuote) {
      throw new Exception('Registro no encontrado');
    }
    return $PurchaseRequestQuote;
  }

  public function store(mixed $data)
  {
    return DB::transaction(function () use ($data) {
      // Obtener el exchange_rate_id según la moneda del documento
      $exchangeRateId = $this->getExchangeRateId($data['doc_type_currency_id']);

      // Preparar datos para crear el PurchaseRequestQuote
      $quoteData = [
        'type_document' => $data['type_document'],
        'type_vehicle' => $data['type_vehicle'],
        'opportunity_id' => $data['opportunity_id'],
        'comment' => $data['comment'] ?? null,
        'holder_id' => $data['holder_id'],
        'vehicle_color_id' => $data['vehicle_color_id'],
        'ap_models_vn_id' => $data['ap_models_vn_id'],
        'ap_vehicle_purchase_order_id' => $data['ap_vehicle_purchase_order_id'] ?? null,
        'doc_type_currency_id' => $data['doc_type_currency_id'],
        'exchange_rate_id' => $exchangeRateId,
        'subtotal' => $data['subtotal'],
        'total' => $data['total'],
      ];

      // Crear el registro principal
      $purchaseRequestQuote = PurchaseRequestQuote::create($quoteData);

      // Guardar bonus_discounts en DiscountCoupons
      if (isset($data['bonus_discounts']) && is_array($data['bonus_discounts'])) {
        $this->saveBonusDiscounts($purchaseRequestQuote->id, $data['bonus_discounts'], $data['sale_price']);
      }

      // Guardar accessories en DetailsApprovedAccessoriesQuote
      if (isset($data['accessories']) && is_array($data['accessories'])) {
        $this->saveAccessories($purchaseRequestQuote->id, $data['accessories']);
      }

      return new PurchaseRequestQuoteResource($purchaseRequestQuote);
    });
  }

  public function show($id)
  {
    return new PurchaseRequestQuoteResource($this->find($id));
  }

  public function update(mixed $data)
  {
    $PurchaseRequestQuote = $this->find($data['id']);
    $PurchaseRequestQuote->update($data);
    return new PurchaseRequestQuoteResource($PurchaseRequestQuote);
  }

  public function destroy($id)
  {
    $PurchaseRequestQuote = $this->find($id);
    DB::transaction(function () use ($PurchaseRequestQuote) {
      $PurchaseRequestQuote->delete();
    });
    return response()->json(['message' => 'Registro eliminado correctamente']);
  }

  /**
   * Obtiene el exchange_rate_id según la moneda del documento
   */
  private function getExchangeRateId($docTypeCurrencyId)
  {
    // Si la moneda es PEN (Sol Peruano), no necesita exchange_rate
    if ($docTypeCurrencyId == TypeCurrency::PEN_ID) {
      return null;
    }

    // Para USD, buscar el exchange_rate de hoy de PEN a USD
    if ($docTypeCurrencyId == TypeCurrency::USD_ID) {
      $exchangeRate = ExchangeRate::todayUSD();
      if (!$exchangeRate) {
        throw new Exception('No se ha registrado la tasa de cambio USD para la fecha de hoy.');
      }
      return $exchangeRate->id;
    }

    // Para EUR u otras monedas, buscar el exchange_rate de hoy
    $exchangeRate = ExchangeRate::where('from_currency_id', TypeCurrency::PEN_ID)
      ->where('to_currency_id', $docTypeCurrencyId)
      ->where('date', date('Y-m-d'))
      ->where('type', ExchangeRate::TYPE_VENTA)
      ->orderBy('created_at', 'desc')
      ->first();

    if (!$exchangeRate) {
      throw new Exception('No se ha registrado la tasa de cambio para la moneda seleccionada en la fecha de hoy.');
    }

    return $exchangeRate->id;
  }

  /**
   * Guarda los bonus_discounts en la tabla DiscountCoupons
   */
  private function saveBonusDiscounts($purchaseRequestQuoteId, $bonusDiscounts, $salePrice)
  {
    foreach ($bonusDiscounts as $discount) {
      $percentage = 0;
      $amount = 0;

      if ($discount['type'] === 'MONTO_FIJO') {
        // Si es monto fijo, guardar en amount y calcular el porcentaje
        $amount = $discount['value'];
        $percentage = ($salePrice > 0) ? ($amount / $salePrice) * 100 : 0;
      } elseif ($discount['type'] === 'PORCENTAJE') {
        // Si es porcentaje, guardar en percentage y calcular el monto
        $percentage = $discount['value'];
        $amount = ($salePrice * $percentage) / 100;
      }

      DiscountCoupons::create([
        'description' => $discount['description'],
        'percentage' => $percentage,
        'amount' => $amount,
        'concept_code_id' => $discount['concept_id'],
        'purchase_request_quote_id' => $purchaseRequestQuoteId,
      ]);
    }
  }

  /**
   * Guarda los accesorios en la tabla DetailsApprovedAccessoriesQuote
   */
  private function saveAccessories($purchaseRequestQuoteId, $accessories)
  {
    foreach ($accessories as $accessory) {
      // Obtener el accesorio aprobado para obtener su precio y moneda
      $approvedAccessory = ApprovedAccessories::find($accessory['accessory_id']);

      if (!$approvedAccessory) {
        throw new Exception('Accesorio con ID ' . $accessory['accessory_id'] . ' no encontrado.');
      }

      $quantity = $accessory['quantity'];
      $price = $approvedAccessory->price;
      $total = $quantity * $price;

      DetailsApprovedAccessoriesQuote::create([
        'approved_accessory_id' => $accessory['accessory_id'],
        'quantity' => $quantity,
        'price' => $price,
        'total' => $total,
        'purchase_request_quote_id' => $purchaseRequestQuoteId,
      ]);
    }
  }
}
