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

    .header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 15px;
      padding-bottom: 10px;
      border-bottom: 2px solid #000;
    }

    .company-info {
      flex: 1;
    }

    .company-logo {
      font-size: 24px;
      font-weight: bold;
      margin-bottom: 5px;
    }

    .company-details {
      font-size: 8px;
      line-height: 1.3;
    }

    .document-number {
      text-align: right;
      font-weight: bold;
    }

    .document-number .number {
      font-size: 14px;
      margin-top: 5px;
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

    .value {
      min-height: 18px;
      display: inline-block;
    }

    .signature-section {
      display: flex;
      margin-top: 15px;
    }

    .signature-box {
      flex: 1;
      border: 1px solid #000;
      min-height: 80px;
      text-align: center;
      padding: 5px;
    }

    .signature-box .label {
      background-color: #e0e0e0;
      padding: 5px;
      margin: -5px -5px 10px -5px;
    }

    .notes {
      font-size: 8px;
      margin-top: 15px;
      line-height: 1.4;
    }

    .notes ol {
      margin-left: 15px;
    }

    .footer-brands {
      text-align: center;
      margin-top: 15px;
      padding-top: 10px;
      border-top: 1px solid #000;
      font-size: 9px;
    }
  </style>
</head>
<body>
<div class="watermark">PAKATNAMU</div>

<!-- Encabezado -->
<div class="header">
  <div class="company-info">
    <div class="company-logo">AP AUTOMOTORES<br>PAKATNAMU SAC</div>
    <div class="company-details">
      www.automotoraspakatnamu.com<br>
      CAR. PANAMERICANA NORTE KM 1088 - MALLA - FONE<br>
      CHIMBOTE - ANCASH - SANTA MAEONE<br>
      CHICLAYO - LAMBAYEQUE - CHICLAYO<br>
      CAR. A PIMENTEL KM 8 - PIMENTEL - CHICLAYO<br>
      LAMBAYEQUE
    </div>
  </div>
  <div class="document-number">
    <div>Nº {{ $quote['correlative'] }}</div>
    <div class="number">Nº {{ str_pad($quote['id'], 6, '0', STR_PAD_LEFT) }}</div>
    <img src="data:image/png;base64,..." alt="Logo" style="width: 80px; margin-top: 10px;">
  </div>
</div>

<!-- Título -->
<div class="title">SOLICITUD DE COMPRA</div>

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
    <td colspan="1">{{ $quote['dni_client'] ?? '' }}</td>
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
  <tr>
    <td class="label">Tarjeta de propiedad a nombre de:</td>
    <td colspan="3">Persona natural <span class="checkbox checked"></span></td>
    <td colspan="2">P. Natural con RUC <span class="checkbox"></span></td>
    <td colspan="2">Persona Jurídica <span class="checkbox"></span></td>
  </tr>
  <tr>
    <td class="label">Razón Social</td>
    <td colspan="5"></td>
    <td class="label">RUC</td>
    <td></td>
  </tr>
  <tr>
    <td class="label">Repres. Legal</td>
    <td colspan="5"></td>
    <td class="label">DNI</td>
    <td></td>
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
    <td colspan="2">3 años o 100,000 Km</td>
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

<!-- Precios -->
<table>
  <tr>
    <td rowspan="8" class="section-title" style="writing-mode: vertical-lr; width: 30px;">Valor de la Compra</td>
    <td class="label">Precio de venta</td>
    <td style="width: 25%;">US$ {{ number_format($quote['sale_price'], 2) }}</td>
    <td style="width: 5%;">1</td>
    <td rowspan="8" class="section-title" style="writing-mode: vertical-lr; width: 30px;">Forma de Pago</td>
    <td class="label" style="width: 15%;">A cuenta:</td>
    <td>US$</td>
    <td style="width: 5%;">4</td>
  </tr>
  <tr>
    <td class="label">Equipamiento adicional</td>
    <td>US$</td>
    <td></td>
    <td class="label">Nº de OP.:</td>
    <td colspan="2"></td>
  </tr>
  @for($i = 0; $i < 4; $i++)
    <tr>
      <td></td>
      <td>US$</td>
      <td></td>
      @if($i == 0)
        <td class="label">Banco:</td>
        <td colspan="2"></td>
      @elseif($i == 1)
        <td class="label">Saldo(3-4)</td>
        <td>US$</td>
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
  <tr>
    <td class="label">Total equipamiento</td>
    <td>US$</td>
    <td>2</td>
    <td class="label">Banco:</td>
    <td colspan="2"></td>
  </tr>
  <tr>
    <td class="label">Precio de compra total (1+2)</td>
    <td>US$ {{ number_format($quote['doc_sale_price'], 2) }}</td>
    <td>3</td>
    <td class="label">Sectorista:</td>
    <td colspan="2"></td>
  </tr>
  <tr>
    <td class="label">T.C. referencial S/</td>
    <td>S/</td>
    <td></td>
    <td class="label">Oficina:</td>
    <td colspan="4"></td>
  </tr>
  <tr>
    <td colspan="3" class="section-title">Obsequios/Cortesía</td>
    <td class="label">Telf.:</td>
    <td colspan="4"></td>
  </tr>
</table>

<!-- Obsequios -->
<table>
  <tr>
    <td>
      <ul style="list-style: none; padding-left: 20px;">
        <li>• {{ $quote['comment'] ?? 'TARJETA Y PLACA' }}</li>
      </ul>
    </td>
  </tr>
</table>

<!-- Firmas -->
<div class="signature-section">
  <div class="signature-box">
    <div class="label">APROBADO</div>
  </div>
  <div class="signature-box">
    <div class="label">FIRMA DEL COMPRADOR</div>
    <div style="margin-top: 30px;">Huella digital</div>
  </div>
</div>

<!-- Notas importantes -->
<div class="notes">
  <strong>IMPORTANTE:</strong>
  <ol>
    <li>Esta solicitud está sujeta a la aprobación de Automotores Pakatnamu SAC.</li>
    <li>Cualquier pedido de equipamiento adicional a las características de la presente solicitud será por cuenta y
      costo del cliente.
    </li>
    <li>El trámite completo de la documentación necesaria para la inscripción de esta unidad ante la SUNARP y nuestra
      empresa se encuentra sujeta a los criterios de regulación autónoma de cada regulador...
    </li>
    <li>El solicitante acepta formalmente todas las características del vehículo descrito en el presente documento.</li>
    <li>El cliente deberá conocer que en caso el vehículo no se encuentre en stock, libera a la empresa de cualquier
      responsabilidad relacionada a los plazos de entrega...
    </li>
    <li>Mantiene los datos consignados correctos.</li>
  </ol>
  <div style="margin-top: 10px;">
    <strong>Números de cuenta BCP:</strong> 305-2041120-0-42 MN / 305-2041105-0-39 MN<br>
    <strong>BBVA CONTINENTAL:</strong> Código de Rescudo (Soles): 9600 | Código de Rescudo (Dólares): 9601
  </div>
</div>

<!-- Footer con marcas -->
<div class="footer-brands">
  🚗 SUZUKI ⚡ SUBARU DFSK ⊕MAZDA 🦁CITROËN 🔧RENAULT 🏁HAVAL ⭕Great Wall 🚙CHANGAN ⚫JAC
</div>
</body>
</html>
