@php
  function getBase64Image($path) {
    $fullPath = public_path($path);
    if (!file_exists($fullPath)) {
      return '';
    }
    $imageData = base64_encode(file_get_contents($fullPath));
    $mimeType = mime_content_type($fullPath);
    return "data:{$mimeType};base64,{$imageData}";
  }
@endphp
  <!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Solicitud de Compra</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: Arial, sans-serif;
      font-size: 11px;
      padding: 20px;
      position: relative;
    }

    .watermark {
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%) rotate(-45deg);
      font-size: 120px;
      color: rgba(200, 200, 200, 0.15);
      font-weight: bold;
      z-index: -1;
      white-space: nowrap;
    }

    .title {
      background-color: #e0e0e0;
      padding: 8px;
      font-size: 16px;
      font-weight: bold;
      text-align: center;
      margin-bottom: 10px;
      border: 1px solid #000;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 10px;
    }

    table, th, td {
      border: 1px solid #000;
    }

    td {
      padding: 4px 6px;
      vertical-align: top;
    }

    .label {
      font-weight: bold;
      font-size: 10px;
    }

    .section-title {
      background-color: #e0e0e0;
      font-weight: bold;
      padding: 5px;
      text-align: center;
    }

    .checkbox {
      display: inline-block;
      width: 14px;
      height: 14px;
      border: 1px solid #000;
      margin-left: 5px;
      vertical-align: middle;
    }

    .checkbox.checked::after {
      content: 'X';
      display: block;
      text-align: center;
      line-height: 14px;
      font-weight: bold;
    }

    .notes {
      font-size: 8px;
      margin-top: 15px;
      line-height: 1.4;
    }

    .notes ol {
      margin-left: 15px;
    }
  </style>
</head>
<body>
<div class="watermark">PAKATNAMU</div>

<!-- Encabezado -->
<table style="margin-bottom: 15px;">
  <tr>
    <td style="width: 15%; text-align: center; vertical-align: middle; border: none;">
      <img src="{{ getBase64Image('images/ap/logo-ap.png') }}" alt="AP Logo" style="max-width: 80px; height: auto;">
    </td>
    <td style="width: 50%; vertical-align: middle; padding: 5px; border: none;">
      <div style="font-size: 14px; font-weight: bold; margin-bottom: 5px;">AP AUTOMOTORES PAKATNAMU SAC</div>
      <div style="font-size: 8px; line-height: 1.3;">
        www.automotorespakatnamu.com<br>
        CAR. A PIMENTEL KM 5 - PIMENTEL - CHICLAYO - LAMBAYEQUE<br>
        Tel: (044) 123-4567 | Email: ventas@automotoraspakatnamu.com
      </div>
    </td>
    <td style="width: 20%; text-align: center; vertical-align: middle; padding: 0 10px; border: none;">
      <div style="font-size: 10px; font-weight: bold;">Núm Documento</div>
      <div style="font-size: 14px; font-weight: bold; margin-top: 5px;">
        Nº {{ $quote['correlative'] }}</div>
    </td>
    <td style="width: 15%; text-align: center; vertical-align: middle; border: none;">
      <img src="{{ getBase64Image('images/ap/derco.jpg') }}" alt="Derco Logo" style="max-width: 100px; height: auto;">
    </td>
  </tr>
</table>

<!-- Título -->
<div class="title">{{ $quote['document_title'] ?? 'SOLICITUD DE COMPRA' }}</div>

<!-- Información del comprador -->
<table>
  <tr>
    <td class="label" style="width: 15%;">Comprador</td>
    <td colspan="3">{{ $quote['client_name'] ?? '' }}</td>
    <td class="label" style="width: 10%;">Fecha:</td>
    <td colspan="1">{{ \Carbon\Carbon::parse($quote['created_at'])->format('d/m/Y') }}</td>
    <td class="label" colspan="1">Vendedor:</td>
    <td>{{ $quote['advisor_name'] ?? '' }}</td>
  </tr>
  <tr>
    <td class="label">Fecha de Nac.</td>
    <td colspan="3">{{ $quote['birth_date'] ?? '' }}</td>
    <td class="label">Estado Civil</td>
    <td colspan="1">{{ $quote['marital_status'] ?? '' }}</td>
    <td class="label">DNI</td>
    <td colspan="1">{{ $quote['num_doc_client'] ?? '' }}</td>
  </tr>
  <tr>
    <td class="label">Cónyuge</td>
    <td colspan="5">{{ $quote['spouse_full_name'] ?? '' }}</td>
    <td class="label">DNI</td>
    <td>{{ $quote['spouse_num_doc'] ?? '' }}</td>
  </tr>
  <tr>
    <td class="label">Dirección</td>
    <td colspan="7">{{ $quote['address'] ?? '' }}</td>
  </tr>
  @php
    $numDoc = $quote['num_doc_client'] ?? '';
    $docLength = strlen($numDoc);
    $docType = 'natural'; // Por defecto Persona natural

    if ($docLength == 11) {
      $firstTwo = substr($numDoc, 0, 2);
      if ($firstTwo == '10') {
        $docType = 'natural_ruc';
      } elseif ($firstTwo == '20') {
        $docType = 'juridica';
      }
    }
  @endphp
  <tr>
    <td class="label">Tarjeta de propiedad a nombre de:</td>
    <td colspan="3">Persona natural <span class="checkbox {{ $docType == 'natural' ? 'checked' : '' }}"></span></td>
    <td colspan="2">P. Natural con RUC <span class="checkbox {{ $docType == 'natural_ruc' ? 'checked' : '' }}"></span>
    </td>
    <td colspan="2">Persona Jurídica <span class="checkbox {{ $docType == 'juridica' ? 'checked' : '' }}"></span></td>
  </tr>
  <tr>
    <td class="label">Razón Social</td>
    <td colspan="5">{{ $quote['holder'] ?? '' }}</td>
    <td class="label">RUC</td>
    <td>{{ $quote['num_doc_client'] ?? '' }}</td>
  </tr>
  <tr>
    <td class="label">Repres. Legal</td>
    <td colspan="5">{{ $quote['legal_representative'] ?? '' }}</td>
    <td class="label">DNI</td>
    <td>{{ $quote['dni_legal_representative'] ?? '' }}</td>
  </tr>
  <tr>
    <td class="label">Dirección</td>
    <td colspan="7"></td>
  </tr>
  <tr>
    <td class="label">Referencia</td>
    <td colspan="7"></td>
  </tr>
  <tr>
    <td class="label">email</td>
    <td colspan="5">{{ $quote['email'] ?? '' }}</td>
    <td class="label">Teléf.</td>
    <td colspan="1">{{ $quote['phone'] ?? '' }}</td>
  </tr>
</table>

<!-- Información del vehículo -->
<table>
  <tr>
    <td rowspan="4" class="section-title" style="writing-mode: vertical-lr; text-align: center; width: 30px;">Vehículo
    </td>
    <td class="label">Clase</td>
    <td class="label">Marca</td>
    <td class="label">Modelo</td>
    <td colspan="2" class="label">Garantía</td>
  </tr>
  <tr>
    <td>{{ $quote['class'] ?? '' }}</td>
    <td>{{ $quote['brand'] ?? '' }}</td>
    <td>{{ $quote['ap_model_vn'] ?? '' }}</td>
    <td colspan="2">{{ $quote['warranty'] ?? '' }}</td>
  </tr>
  <tr>
    <td class="label">Motor</td>
    <td class="label">Chasis</td>
    <td class="label" colspan="2">Color</td>
    <td class="label">Fab./Modelo</td>
  </tr>
  <tr>
    <td>{{ $quote['engine_number'] ?? '' }}</td>
    <td>{{ $quote['vin'] ?? '' }}</td>
    <td colspan="2">{{ $quote['vehicle_color'] ?? 'BLANCO' }}</td>
    <td>{{ $quote['model_year'] ?? '' }}</td>
  </tr>
  <tr>
    <td colspan="6" class="label">Tipo de uso del vehículo (Particular/Servicio Público)</td>
  </tr>
</table>

<!-- Precios el rowspan="9" indica que va usar 9 filas y colspan="2" utiliza 2 columnas -->
<table>
  @php
    $addedAccessories = collect($quote['accessories'] ?? [])->filter(function($item) {
      return $item['type'] === 'ACCESORIO_ADICIONAL';
    });
    $gifts = collect($quote['accessories'] ?? [])->filter(function($item) {
      return $item['type'] === 'OBSEQUIO';
    });
  @endphp
  <tr>
    <td rowspan="8" class="section-title" style="writing-mode: vertical-lr; width: 30px;">Valor de la Compra</td>
    <td class="label">Precio de venta</td>
    <td
      style="width: 25%;">{{ $quote['type_currency_symbol'] }} {{ number_format($quote['base_selling_price'], 2) }}</td>
    <td style="width: 5%;">1</td>
    <td rowspan="8" class="section-title" style="writing-mode: vertical-lr; width: 30px;">Forma de Pago</td>
    <td class="label" style="width: 15%;">A cuenta:</td>
    <td>{{ $quote['type_currency_symbol'] }}</td>
    <td style="width: 5%;">4</td>
  </tr>
  @if($addedAccessories->count() > 0)
    @foreach($addedAccessories as $index => $accessory)
      <tr>
        @if($index == 0)
          <td class="label">Equipamiento adicional</td>
        @else
          <td></td>
        @endif
        <td>{{ $accessory['description'] ?? '' }} (Cant: {{ $accessory['quantity'] ?? 1 }})
          {{ $quote['type_currency_symbol'] }} {{ number_format(($accessory['total'] ?? 0) / ($quote['exchange_rate'] ?? 1), 2) }}</td>
        <td></td>
        @if($index == 0)
          <td class="label">Nº de OP.:</td>
          <td colspan="2"></td>
        @elseif($index == 1)
          <td class="label">Banco:</td>
          <td colspan="2"></td>
        @elseif($index == 2)
          <td class="label">Saldo(3-4)</td>
          <td>{{ $quote['type_currency_symbol'] }}</td>
          <td>5</td>
        @elseif($index == 3)
          <td class="label">Forma de pago:</td>
          <td colspan="2"></td>
        @else
          <td></td>
          <td colspan="2"></td>
        @endif
      </tr>
    @endforeach
    @php
      $remainingRows = max(0, 4 - $addedAccessories->count());
    @endphp
    @for($i = 0; $i < $remainingRows; $i++)
      @php
        $rowIndex = $addedAccessories->count() + $i;
      @endphp
      <tr>
        <td></td>
        <td>{{ $quote['type_currency_symbol'] }}</td>
        <td></td>
        @if($rowIndex == 1)
          <td class="label">Banco:</td>
          <td colspan="2"></td>
        @elseif($rowIndex == 2)
          <td class="label">Saldo(3-4)</td>
          <td>{{ $quote['type_currency_symbol'] }}</td>
          <td>5</td>
        @elseif($rowIndex == 3)
          <td class="label">Forma de pago:</td>
          <td colspan="2"></td>
        @else
          <td></td>
          <td colspan="2"></td>
        @endif
      </tr>
    @endfor
  @else
    <tr>
      <td class="label">Equipamiento adicional</td>
      <td>{{ $quote['type_currency_symbol'] }}</td>
      <td></td>
      <td class="label">Nº de OP.:</td>
      <td colspan="2"></td>
    </tr>
    @for($i = 0; $i < 4; $i++)
      <tr>
        <td></td>
        <td>{{ $quote['type_currency_symbol'] }}</td>
        <td></td>
        @if($i == 0)
          <td class="label">Banco:</td>
          <td colspan="2"></td>
        @elseif($i == 1)
          <td class="label">Saldo(3-4)</td>
          <td>{{ $quote['type_currency_symbol'] }}</td>
          <td>5</td>
        @elseif($i == 2)
          <td class="label">Forma de pago:</td>
          <td colspan="2"></td>
        @else
          <td></td>
          <td colspan="2"></td>
        @endif
      </tr>
    @endfor
  @endif
  <tr>
    <td class="label">Total equipamiento</td>
    <td>{{ $quote['type_currency_symbol'] }}</td>
    <td>2</td>
    <td class="label">Banco:</td>
    <td colspan="2"></td>
  </tr>
  <tr>
    <td class="label">Precio de compra total (1+2)</td>
    <td>{{ $quote['type_currency_symbol'] }} {{ number_format($quote['doc_sale_price'], 2) }}</td>
    <td>3</td>
    <td class="label">Sectorista:</td>
    <td colspan="2"></td>
  </tr>
  <tr>
    <td class="label">T.C. referencial S/</td>
    <td>S/ {{ number_format($quote['selling_price_soles'], 2) }}</td>
    <td></td>
    <td class="label">Oficina:</td>
    <td colspan="2"></td>
  </tr>
  <tr>
    <td colspan="8" class="section-title">Obsequios / Cortesía</td>
  </tr>
  @if($gifts->count() > 0)
    @foreach($gifts as $gift)
      <tr>
        <td colspan="8">• {{ $gift['description'] ?? '' }} (Cant: {{ $gift['quantity'] ?? 1 }})</td>
      </tr>
    @endforeach
  @endif
  @if($gifts->count() === 0)
    <tr>
      <td colspan="8">• {{ $quote['comment'] ?? 'TARJETA Y PLACA' }}</td>
    </tr>
  @endif
</table>

<!-- Firmas -->
<table>
  <tr>
    <td class="signature-box"
        style="width: 50%; min-height: 80px; text-align: center; padding: 5px; vertical-align: top;">
      <div class="label"
           style="background-color: #e0e0e0; padding: 5px; margin: -5px -5px 10px -5px; font-weight: bold; font-size: 10px;">
        APROBADO
      </div>
    </td>
    <td class="signature-box"
        style="width: 50%; min-height: 80px; text-align: center; padding: 5px; vertical-align: top;">
      <div class="label"
           style="background-color: #e0e0e0; padding: 5px; margin: -5px -5px 10px -5px; font-weight: bold; font-size: 10px;">
        FIRMA DEL COMPRADOR
      </div>
      <div style="margin-top: 30px;">Huella digital</div>
    </td>
  </tr>
</table>

<!-- Notas importantes -->
<div class="notes">
  <strong>IMPORTANTE:</strong>
  <ol>
    <li>Esta solicitud está sujeta a la aprobación de Automotores Pakatnamu SAC.</li>
    <li>Cualquier pedido de equipamiento adicional a las características de la presente solicitud será por cuenta y
      costo del cliente.
    </li>
    <li>El trámite de placas de rodaje y tarjeta de propiedad es una cortesía que otorgamos a nuestros clientes. Dicho
      trámite se encuentra sujeta a los criterios de calificación
      autónomos de cada registrador, por lo que nuestra empresa no se hace responsable por la demora ocasionada como
      consecuencia de la aplicación de los criterios registrales empleados por SUNARP.
    </li>
    <li>El solicitante acepta formalmente todas las características del vehículo descrito en el presente documento.</li>
    <li>El cliente declara conocer que en caso el vehículo no se encuentre en stock, libera a la empresa de cualquier
      responsabilidad relacionada a los plazos de entrega. Las fechas de entrega son variables y están sujetas a cambio,
      con previa comunicación al cliente.
    </li>
    <li>Manifesto que los datos consignados son exactos y se ajustan fielmente a la realidad.</li>
  </ol>
  <div style="margin-top: 10px;">
    <strong>Números de cuenta BCP:</strong> (305-2041120-0-42 MN / 305-2041105-0-39 MN) (305-2035096-1-39 MN /
    305-2035097-1-49 ME)<br>
    <strong>BBVA CONTINENTAL:</strong> Código de Recaudo (Soles): 9600 | Código de Recaudo (Dólares): 9601
  </div>
</div>

<!-- Footer con marcas -->
<table style="border: none; border-top: 1px solid #000; margin-top: 15px; padding-top: 10px;">
  <tr>
    <td style="text-align: center; border: none; padding: 10px 0;">
      <img src="{{ getBase64Image('images/ap/brands/suzuki.png') }}" alt="Suzuki"
           style="height: 13px; width: auto; margin: 0 8px;">
      <img src="{{ getBase64Image('images/ap/brands/subaru.png') }}" alt="Subaru"
           style="height: 13px; width: auto; margin: 0 8px;">
      <img src="{{ getBase64Image('images/ap/brands/dfsk.png') }}" alt="DFSK"
           style="height: 13px; width: auto; margin: 0 8px;">
      <img src="{{ getBase64Image('images/ap/brands/mazda.png') }}" alt="Mazda"
           style="height: 13px; width: auto; margin: 0 8px;">
      <img src="{{ getBase64Image('images/ap/brands/citroen.jpg') }}" alt="Citroën"
           style="height: 13px; width: auto; margin: 0 8px;">
      <img src="{{ getBase64Image('images/ap/brands/renault.png') }}" alt="Renault"
           style="height: 13px; width: auto; margin: 0 8px;">
      <img src="{{ getBase64Image('images/ap/brands/haval.png') }}" alt="Haval"
           style="height: 13px; width: auto; margin: 0 8px;">
      <img src="{{ getBase64Image('images/ap/brands/great-wall.png') }}" alt="Great Wall"
           style="height: 13px; width: auto; margin: 0 8px;">
      <img src="{{ getBase64Image('images/ap/brands/changan.png') }}" alt="Changan"
           style="height: 13px; width: auto; margin: 0 8px;">
      <img src="{{ getBase64Image('images/ap/brands/jac.png') }}" alt="JAC"
           style="height: 13px; width: auto; margin: 0 8px;">
    </td>
  </tr>
</table>
</body>
</html>
