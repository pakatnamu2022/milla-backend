<?php

namespace App\Reports\ap\postventa;

use App\Http\Utils\Constants;
use App\Http\Utils\Helpers;
use App\Models\ap\postventa\taller\ApOrderQuotationDetails;
use App\Models\ap\postventa\taller\ApOrderQuotations;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;

class OrderQuotationTallerPdf
{
  /**
   * Descarga el PDF de cotización de taller
   *
   * @param int $id
   * @param bool $showCodes
   * @return \Illuminate\Http\Response
   * @throws Exception
   */
  public function download(int $id, bool $showCodes = true)
  {
    $data = $this->prepareData($id, $showCodes);
    $pdf = $this->generatePdf($data);
    return $pdf->download($this->getFileName($data['quotation_number']));
  }

  /**
   * Prepara los datos para el PDF
   *
   * @param int $id
   * @param bool $showCodes
   * @return array
   * @throws Exception
   */
  private function prepareData(int $id, bool $showCodes): array
  {
    $quotation = ApOrderQuotations::with([
      'vehicle.model.family.brand',
      'vehicle.color',
      'vehicle.customer.district',
      'createdBy',
      'details.product',
      'typeCurrency'
    ])->find($id);

    if (!$quotation) {
      throw new Exception('Cotización no encontrada');
    }

    // Datos básicos
    $data = [
      'quotation_number' => $quotation->quotation_number,
      'quotation_date' => $quotation->quotation_date,
      'expiration_date' => $quotation->expiration_date,
      'observations' => $quotation->observations ?? '',
      'validity_days' => $quotation->validity_days,
      'show_codes' => $showCodes,
      'sede' => $quotation->sede,
    ];

    // Datos del cliente
    if ($quotation->client) {
      $customer = $quotation->client;
      $data['customer_name'] = $customer->full_name;
      $data['customer_document'] = $customer->num_doc ?? 'N/A';
      $data['customer_address'] = $customer->direction ?? 'N/A';
      $data['customer_district'] = $customer->district ? $customer->district->name : 'N/A';
      $data['customer_email'] = $customer->email ?? 'N/A';
      $data['customer_phone'] = $customer->phone ?? 'N/A';
    } else {
      $data['customer_name'] = 'N/A';
      $data['customer_document'] = 'N/A';
      $data['customer_address'] = 'N/A';
      $data['customer_district'] = 'N/A';
      $data['customer_email'] = 'N/A';
      $data['customer_phone'] = 'N/A';
    }

    // Datos del asesor
    if ($quotation->createdBy) {
      $data['advisor_name'] = $quotation->createdBy->person->nombre_completo ?? 'N/A';
      $data['advisor_phone'] = $quotation->createdBy->person->tel_referencia_3 ?? 'N/A';
      $data['advisor_email'] = $quotation->createdBy->person->email2 ?? 'N/A';
    } else {
      $data['advisor_name'] = 'N/A';
      $data['advisor_phone'] = 'N/A';
      $data['advisor_email'] = 'N/A';
    }

    // Datos del vehículo
    if ($quotation->vehicle) {
      $vehicle = $quotation->vehicle;
      $data['vehicle_plate'] = $vehicle->plate ?? 'N/A';
      $data['vehicle_vin'] = $vehicle->vin ?? 'N/A';
      $data['vehicle_engine'] = $vehicle->engine_number ?? 'N/A';
      $data['vehicle_model'] = $vehicle->model ? $vehicle->model->version : 'N/A';
      $data['vehicle_brand'] = $vehicle->model && $vehicle->model->family && $vehicle->model->family->brand
        ? $vehicle->model->family->brand->name
        : 'N/A';
      $data['vehicle_color'] = $vehicle->color ? $vehicle->color->description : 'N/A';
      $data['vehicle_km'] = $quotation->mileage;
    } else {
      $data['vehicle_plate'] = 'N/A';
      $data['vehicle_vin'] = 'N/A';
      $data['vehicle_engine'] = 'N/A';
      $data['vehicle_model'] = 'N/A';
      $data['vehicle_brand'] = 'N/A';
      $data['vehicle_color'] = 'N/A';
      $data['vehicle_km'] = 'N/A';
    }

    // Ordenar detalles por campo 'order' de forma independiente por bloque
    // Bloque 1: LABOR + MATERIAL (mano de obra)
    $laborMaterialDetails = $quotation->details
      ->whereIn('item_type', [ApOrderQuotationDetails::ITEM_TYPE_LABOR, ApOrderQuotationDetails::ITEM_TYPE_MATERIAL])
      ->sortBy('order')
      ->values();

    // Bloque 2: PRODUCT (repuestos/recambios)
    $productDetails = $quotation->details
      ->where('item_type', ApOrderQuotationDetails::ITEM_TYPE_PRODUCT)
      ->sortBy('order')
      ->values();

    // Combinar los bloques ordenados: primero LABOR+MATERIAL, luego PRODUCT
    $details = $laborMaterialDetails->merge($productDetails);

    // Detalles de la cotización
    $data['details'] = $details->map(function ($detail) use ($showCodes) {
      return [
        'code' => $showCodes && $detail->product ? $detail->product->code : '',
        'description' => $detail->description,
        'observations' => $detail->observations ?? '',
        'quantity' => $detail->quantity,
        'unit_price' => $detail->unit_price,
        'discount' => $detail->discount_percentage,
        'total_amount' => $detail->net_amount,
        'item_type' => $detail->item_type,
        'supply_type' => $detail->supply_type,
      ];
    });

    // Calcular totales correctamente
    $totalLabor = 0;
    $totalParts = 0;
    $totalDiscounts = 0;

    foreach ($quotation->details as $detail) {
      $itemSubtotal = $detail->total_cost;
      $itemDiscount = $detail->total_cost - $detail->net_amount;

      // Total mano de obra (LABOR + MATERIAL) - sin descuento
      if ($detail->item_type === ApOrderQuotationDetails::ITEM_TYPE_LABOR ||
        $detail->item_type === ApOrderQuotationDetails::ITEM_TYPE_MATERIAL) {
        $totalLabor += $itemSubtotal;
      }

      // Total recambios/repuestos (PRODUCT) - sin descuento
      if ($detail->item_type === ApOrderQuotationDetails::ITEM_TYPE_PRODUCT) {
        $totalParts += $itemSubtotal;
      }

      // Total descuentos en monto (dinero)
      $totalDiscounts += $itemDiscount;
    }

    // Calcular base imponible (base propuesta): mano de obra + recambios - descuento
    $baseImponible = $totalLabor + $totalParts - $totalDiscounts;

    // Calcular IGV: 18% sobre la base imponible
    $igv_amount = $baseImponible * (Constants::VAT_TAX / 100);

    // Calcular total: base imponible + IGV
    $total_amount = $baseImponible + $igv_amount;

    $data['total_labor'] = $totalLabor;
    $data['total_parts'] = $totalParts;
    $data['total_discounts'] = $totalDiscounts;
    $data['base_imponible'] = $baseImponible;
    $data['tax_amount'] = $igv_amount;
    $data['total_amount'] = $total_amount;
    $data['area'] = $quotation->area ? $quotation->area->description : 'N/A';
    $data['currency_symbol'] = $quotation->typeCurrency ? $quotation->typeCurrency->symbol : 'S/';
    $data['currency_name'] = $quotation->typeCurrency ? $quotation->typeCurrency->name : 'SOLES';

    // Convertir firma del cliente a base64 si existe
    $customerSignature = null;
    if ($quotation->customer_signature_url) {
      $customerSignature = Helpers::convertUrlToBase64($quotation->customer_signature_url);
    }
    $data['customer_signature'] = $customerSignature;

    return $data;
  }

  /**
   * Genera el PDF
   *
   * @param array $data
   * @return \Barryvdh\DomPDF\PDF
   */
  private function generatePdf(array $data)
  {
    $pdf = Pdf::loadView('reports.ap.postventa.taller.order-quotation', [
      'quotation' => $data
    ]);

    $pdf->setPaper('a4', 'portrait');

    return $pdf;
  }

  /**
   * Obtiene el nombre del archivo
   *
   * @param string $quotationNumber
   * @return string
   */
  private function getFileName(string $quotationNumber): string
  {
    return 'Cotizacion_' . $quotationNumber . '.pdf';
  }
}
