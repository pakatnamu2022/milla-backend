<?php

namespace App\Reports\ap\postventa;

use App\Http\Utils\Helpers;
use App\Models\ap\postventa\taller\ApOrderQuotations;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;

class OrderQuotationRepuestoPdf
{
  /**
   * Descarga el PDF de cotización de repuestos
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
      'advancesOrderQuotation'
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
      'type_currency' => $quotation->typeCurrency,
      'status_id' => $quotation->status_id,
    ];

    // Datos del cliente
    $data['customer_name'] = $quotation->client->full_name ?? 'N/A';
    $data['customer_document'] = $quotation->client->num_doc ?? 'N/A';
    $data['customer_address'] = $quotation->client->direction ?? 'N/A';
    $data['customer_district'] = $quotation->client->district ? $quotation->client->district->name : 'N/A';
    $data['customer_email'] = $quotation->client->email ?? 'N/A';
    $data['customer_phone'] = $quotation->client->phone ?? 'N/A';
    $data['customer_activity'] = $quotation->client->activityEconomic->description ?? 'N/A';

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

    // Datos del vehículo (sin kilometraje)
    if ($quotation->vehicle) {
      $vehicle = $quotation->vehicle;
      $data['vehicle_plate'] = $vehicle->plate ?? 'N/A';
      $data['vehicle_vin'] = $vehicle->vin ?? 'N/A';
      $data['vehicle_engine'] = $vehicle->engine_number ?? 'N/A';

      if ($vehicle->model) {
        $data['vehicle_model'] = $vehicle->model->version ?? 'N/A';

        if ($vehicle->model->family) {
          $data['vehicle_brand'] = $vehicle->model->family->brand
            ? $vehicle->model->family->brand->name
            : 'N/A';
        } else {
          $data['vehicle_brand'] = 'N/A';
        }
      } else {
        $data['vehicle_model'] = 'N/A';
        $data['vehicle_brand'] = 'N/A';
      }

      $data['vehicle_color'] = $vehicle->color ? $vehicle->color->description : 'N/A';
    } else {
      $data['vehicle_plate'] = 'N/A';
      $data['vehicle_vin'] = 'N/A';
      $data['vehicle_engine'] = 'N/A';
      $data['vehicle_model'] = 'N/A';
      $data['vehicle_brand'] = 'N/A';
      $data['vehicle_color'] = 'N/A';
    }

    // Filtrar solo repuestos (excluir mano de obra) y ordenar por campo 'order'
    $repuestosDetails = $quotation->details
      ->where('item_type', '!=', 'labor')
      ->sortBy('order')
      ->values();

    // Detalles de la cotización (solo repuestos)
    $data['details'] = $repuestosDetails->map(function ($detail) use ($showCodes) {
      return [
        'code' => $showCodes && $detail->product ? $detail->product->code : '',
        'description' => $detail->description,
        'unit_measure' => $detail->unit_measure,
        'observations' => $detail->observations ?? '',
        'quantity' => $detail->quantity,
        'unit_price' => $detail->unit_price,
        'discount' => $detail->discount_percentage,
        'total_amount' => $detail->net_amount,
        'total_amount_with_tax' => $detail->net_amount + $detail->tax_amount,
        'item_type' => $detail->item_type,
        'supply_type' => $detail->supply_type,
      ];
    });

    $data['op_gravada'] = $quotation->subtotal - $quotation->discount_amount;
    $data['total_discounts'] = $quotation->discount_amount;
    $data['subtotal'] = $quotation->subtotal;
    $data['tax_amount'] = $quotation->tax_amount;
    $data['total_amount'] = $quotation->total_amount;
    $data['area'] = $quotation->area ? $quotation->area->description : 'N/A';

    // Calcular pagos realizados (anticipos no anulados)
    $totalPagado = $quotation->advancesOrderQuotation
      ->where('anulado', false)
      ->where('aceptada_por_sunat', 1)
      ->sum('total');

    $data['total_pagado'] = $totalPagado;
    $data['saldo_pendiente'] = $quotation->total_amount - $totalPagado;

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
    $pdf = Pdf::loadView('reports.ap.postventa.taller.order-quotation-repuesto', [
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
    return 'Cotizacion_Repuestos_' . $quotationNumber . '.pdf';
  }
}
