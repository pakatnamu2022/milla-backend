<?php

namespace App\Reports\ap\postventa;

use App\Http\Utils\Constants;
use App\Models\ap\ApMasters;
use App\Models\ap\comercial\BusinessPartners;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\ap\postventa\taller\ApOrderPurchaseRequests;
use App\Models\ap\postventa\taller\ApWorkOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;

class OrderPurchaseRequestPdf
{
  /**
   * Generar y descargar PDF de la solicitud de compra
   *
   * @param int $id
   * @return \Illuminate\Http\Response
   * @throws Exception
   */
  public function download(int $id)
  {
    $data = $this->prepareData($id);
    $pdf = $this->generatePdf($data);

    return $pdf->download($this->getFileName($data['request_number']));
  }

  /**
   * Preparar los datos para el PDF
   *
   * @param int $id
   * @return array
   * @throws Exception
   */
  private function prepareData(int $id): array
  {
    $purchaseRequest = ApOrderPurchaseRequests::with([
      'apOrderQuotation.client.district',
      'apOrderQuotation.details.product',
      'apOrderQuotation.typeCurrency',
      'apOrderQuotation.createdBy.person',
      'apOrderQuotation.vehicle.model.family.brand',
      'warehouse',
      'warehouse.sede',
      'requestedBy.person',
      'details.product',
      'typeCurrency'
    ])->find($id);

    if (!$purchaseRequest) {
      throw new Exception('solicitud de compra no encontrada');
    }

    $quotation = $purchaseRequest->apOrderQuotation;
    $hasQuotation = $quotation !== null;

    // Datos base de la solicitud
    $data = [
      'request_number' => $purchaseRequest->request_number,
      'requested_date' => $purchaseRequest->requested_date ?? $purchaseRequest->created_at,
      'delivery_date' => '-',
      'work_order_number' => '-',
      'has_quotation' => $hasQuotation,
      'quotation_number' => $hasQuotation ? $quotation->quotation_number : null,
      'sede' => $purchaseRequest->warehouse->sede,
    ];

    // Datos del proveedor/cliente
    if ($hasQuotation && $quotation->client) {
      $client = $quotation->client;
    } else {
      $client = BusinessPartners::find(BusinessPartners::AUTOMOTORES_PAKATNAMU_ID);
    }

    $data['supplier_name'] = $client->full_name ?? '-';
    $data['supplier_ruc'] = $client->num_doc ?? '-';
    $data['supplier_address'] = $client->direction ?? '-';
    $data['supplier_ubigeo'] = $client->ubigeo ?? '-';
    $data['supplier_city'] = $client->district ? $client->district->name . ' - ' . ($client->district->province->name ?? '') : '-';
    $data['supplier_phone'] = $client->phone ?? '-';
    $data['supplier_email'] = $client->email ?? '-';

    // Datos del vendedor/asesor
    if ($purchaseRequest->requestedBy && $purchaseRequest->requestedBy->person) {
      $data['advisor_name'] = ($purchaseRequest->requestedBy->person->nombre_completo ?? '-');
    } else {
      $data['advisor_name'] = '-';
    }

    // Datos del almacén
    $data['warehouse_name'] = $purchaseRequest->warehouse
      ? $purchaseRequest->warehouse->id . ' - ' . $purchaseRequest->warehouse->description
      : '-';

    // Datos del vehículo
    if ($hasQuotation && $quotation->vehicle) {
      $vehicle = $quotation->vehicle;
      $data['vehicle_plate'] = $vehicle->plate ?? '-';
      $data['vehicle_vin'] = $vehicle->vin ?? '-';
      $data['vehicle_model'] = $vehicle->model
        ? ($vehicle->model->family->brand->name ?? '') . ' ' . ($vehicle->model->version ?? '')
        : '-';
    } else {
      $data['vehicle_plate'] = '-';
      $data['vehicle_vin'] = '-';
      $data['vehicle_model'] = '-';
    }

    // Forma de pago (si hay cotización)
    $data['payment_method'] = '-';

    // Preparar detalles con precios de la cotización
    $details = [];
    $total = 0;

    foreach ($purchaseRequest->details as $detail) {
      $product = $detail->product;
      $code = $product ? $product->code : '-';
      $description = $product ? $product->name : '-';
      $quantity = $detail->quantity;
      $supply_type = $detail->supply_type;
      $notes = $detail->notes ?? '-';

      // Obtener precios directamente del detalle de la solicitud de compra
      $price = $detail->unit_price ?? '-';
      $discount = ($detail->discount_percentage ?? 0) > 0 ? $detail->discount_percentage : '0';
      $lineTotal = $detail->total_amount ?? '-';

      if (is_numeric($lineTotal)) {
        $total += $lineTotal;
      }

      $details[] = [
        'code' => $code,
        'description' => $description,
        'supply_type' => $supply_type,
        'notes' => $notes,
        'quantity' => number_format($quantity, 2),
        'price' => is_numeric($price) ? number_format($price, 2) : $price,
        'discount' => is_numeric($discount) ? number_format($discount, 2) : $discount,
        'total' => is_numeric($lineTotal) ? number_format($lineTotal, 2) : $lineTotal,
      ];
    }

    $data['details'] = $details;
    $data['observations'] = $purchaseRequest->observations ?? '';

    // Obtener símbolo de moneda directamente de la solicitud de compra
    $currencySymbol = '';
    if ($purchaseRequest->typeCurrency) {
      $currencySymbol = $purchaseRequest->typeCurrency->symbol ?? '';
    }
    $data['currency_symbol'] = $currencySymbol;

    // Calcular subtotal, IGV y total
    if ($total > 0) {
      $igvRate = Constants::VAT_TAX / 100;
      $igv = $total * $igvRate;
      $totalWithIgv = $total + $igv;

      $data['subtotal'] = number_format($total, 2);
      $data['igv'] = number_format($igv, 2);
      $data['total'] = number_format($totalWithIgv, 2);
    } else {
      $data['subtotal'] = '-';
      $data['igv'] = '-';
      $data['total'] = '-';
    }

    // Obtener anticipos y facturas si existe cotización
    $electronicDocuments = [];
    if ($hasQuotation) {
      $documents = collect();

      // Diferenciar búsqueda según area_id
      if ($purchaseRequest->area_id == ApMasters::AREA_MESON) {
        // Para MESON: buscar por order_quotation_id (lógica actual)
        $documents = ElectronicDocument::where('order_quotation_id', $quotation->id)
          ->where('anulado', false)
          ->whereIn('status', [
            ElectronicDocument::STATUS_SENT,
            ElectronicDocument::STATUS_ACCEPTED
          ])
          ->orderBy('fecha_de_emision', 'asc')
          ->get();
      } elseif ($purchaseRequest->area_id == ApMasters::AREA_TALLER) {
        // Para TALLER: buscar work_order_id usando order_quotation_id
        $workOrder = ApWorkOrder::where('order_quotation_id', $quotation->id)->first();

        if ($workOrder) {
          $documents = ElectronicDocument::where('work_order_id', $workOrder->id)
            ->where('anulado', false)
            ->whereIn('status', [
              ElectronicDocument::STATUS_SENT,
              ElectronicDocument::STATUS_ACCEPTED
            ])
            ->orderBy('fecha_de_emision', 'asc')
            ->get();
        }
      }

      foreach ($documents as $doc) {
        $electronicDocuments[] = [
          'number' => $doc->full_number,
          'date' => $doc->fecha_de_emision ? $doc->fecha_de_emision->format('d/m/Y') : '-',
          'amount' => number_format($doc->total, 2),
          'status' => $this->getStatusLabel($doc->status),
          'is_advance' => $doc->is_advance_payment ? 'Sí' : 'No',
          'type' => $doc->is_advance_payment ? 'Anticipo' : 'Factura'
        ];
      }
    }
    $data['electronic_documents'] = $electronicDocuments;

    return $data;
  }

  /**
   * Generar el PDF con los datos preparados
   *
   * @param array $data
   * @return \Barryvdh\DomPDF\PDF
   */
  private function generatePdf(array $data)
  {
    $pdf = Pdf::loadView('reports.ap.postventa.taller.order-purchase-request', [
      'purchaseRequest' => $data
    ]);

    $pdf->setPaper('a4', 'portrait');

    return $pdf;
  }

  /**
   * Obtener el nombre del archivo PDF
   *
   * @param string $requestNumber
   * @return string
   */
  private function getFileName(string $requestNumber): string
  {
    return 'Solicitud_Compra_' . $requestNumber . '.pdf';
  }

  /**
   * Obtener etiqueta del estado del documento electrónico
   *
   * @param string $status
   * @return string
   */
  private function getStatusLabel(string $status): string
  {
    return match ($status) {
      ElectronicDocument::STATUS_DRAFT => 'Borrador',
      ElectronicDocument::STATUS_SENT => 'Enviado',
      ElectronicDocument::STATUS_ACCEPTED => 'Aceptado',
      ElectronicDocument::STATUS_REJECTED => 'Rechazado',
      ElectronicDocument::STATUS_CANCELLED => 'Anulado',
      default => $status,
    };
  }
}
