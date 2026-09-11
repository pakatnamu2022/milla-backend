<?php

namespace App\Exports\ap\postventa\Reports;

use App\Models\ap\postventa\taller\ApOrderQuotations;
use Exception;
use Maatwebsite\Excel\Facades\Excel;

class OrderQuotationRepuestoExcel
{
  /**
   * Descarga el Excel de cotización de repuestos
   *
   * @param int $id
   * @param bool $showCodes
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
   * @throws Exception
   */
  public function download(int $id, bool $showCodes = true)
  {
    // Limpiar cualquier output previo
    if (ob_get_length()) ob_end_clean();

    $data = $this->prepareData($id, $showCodes);
    $fileName = $this->getFileName($data['quotation_number']);

    return Excel::download(
      new OrderQuotationExport($data),
      $fileName
    );
  }

  /**
   * Prepara los datos para el Excel
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
      'advancesOrderQuotation',
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
        'observations' => $detail->observations ?? '',
        'quantity' => $detail->quantity,
        'unit_price' => $detail->unit_price,
        'discount' => $detail->discount_percentage,
        'total_amount' => $detail->net_amount,
        'item_type' => $detail->item_type,
        'supply_type' => $detail->supply_type,
      ];
    })->toArray();

    $data['op_gravada'] = $quotation->subtotal - $quotation->discount_amount;
    $data['total_discounts'] = $quotation->discount_amount;
    $data['subtotal'] = $quotation->subtotal;
    $data['tax_amount'] = $quotation->tax_amount;
    $data['total_amount'] = $quotation->total_amount;
    $data['area'] = $quotation->area ? $quotation->area->description : 'N/A';
    $data['currency_symbol'] = $quotation->typeCurrency ? $quotation->typeCurrency->symbol : 'S/';
    $data['currency_name'] = $quotation->typeCurrency ? $quotation->typeCurrency->name : 'SOLES';

    // Calcular pagos realizados (anticipos no anulados)
    $totalPagado = $quotation->advancesOrderQuotation
      ->where('anulado', false)
      ->where('aceptada_por_sunat', 1)
      ->sum('total');

    $data['total_pagado'] = $totalPagado;
    $data['saldo_pendiente'] = $quotation->total_amount - $totalPagado;

    return $data;
  }

  /**
   * Obtiene el nombre del archivo
   *
   * @param string $quotationNumber
   * @return string
   */
  private function getFileName(string $quotationNumber): string
  {
    return 'Cotizacion_Repuestos_' . $quotationNumber . '.xlsx';
  }
}