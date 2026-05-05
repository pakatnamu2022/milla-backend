@extends('emails.layouts.base')

@section('content')
  @php $font = "Inter,Arial,Helvetica,sans-serif"; @endphp

  {{-- Vigencia (si aplica) --}}
  @if($quote_deadline)
    <p style="margin:0 0 20px 0;font:400 13px/1.5 {{ $font }};color:#374151;">
      Vigencia del documento: <strong style="color:#111827;">{{ $quote_deadline }}</strong>
    </p>
  @endif

  {{-- Titular y Asesor --}}
  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
         style="margin-bottom:24px;">
    <tr>
      <td width="50%" style="vertical-align:top;padding-right:12px;">
        <p style="margin:0 0 5px 0;font:700 11px/1 {{ $font }};color:#374151;letter-spacing:1px;text-transform:uppercase;">
          Titular
        </p>
        <p style="margin:0;font:400 13px/1.8 {{ $font }};color:#111827;">
          <strong style="font-weight:700;">{{ $holder_name }}</strong><br>
          @if($holder_doc)
            <span style="color:#374151;">Doc:</span> {{ $holder_doc }}<br>
          @endif
          @if($holder_phone)
            <span style="color:#374151;">Tel:</span> {{ $holder_phone }}<br>
          @endif
          @if($holder_email)
            <span style="color:#374151;">Email:</span> {{ $holder_email }}
          @endif
        </p>
      </td>
      <td width="50%" style="vertical-align:top;padding-left:12px;border-left:2px solid #e5e7eb;">
        <p style="margin:0 0 5px 0;font:700 11px/1 {{ $font }};color:#374151;letter-spacing:1px;text-transform:uppercase;">
          Asesor Comercial
        </p>
        <p style="margin:0;font:400 13px/1.8 {{ $font }};color:#111827;">
          <strong style="font-weight:700;">{{ $advisor_name }}</strong><br>
          @if($sede)
            <span style="color:#374151;">Sede:</span> {{ $sede }}
          @endif
        </p>
      </td>
    </tr>
  </table>

  {{-- Vehículo --}}
  <p style="margin:0 0 8px 0;font:700 11px/1 {{ $font }};color:#374151;letter-spacing:1px;text-transform:uppercase;">
    Vehículo
  </p>
  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
         style="margin-bottom:28px;">
    <tr>
      <td style="font:400 13px/1.9 {{ $font }};color:#374151;width:130px;vertical-align:top;">Marca / Modelo</td>
      <td style="font:600 13px/1.9 {{ $font }};color:#111827;vertical-align:top;">{{ $brand }} {{ $model }}</td>
    </tr>
    @if($color)
      <tr>
        <td style="font:400 13px/1.9 {{ $font }};color:#374151;">Color</td>
        <td style="font:600 13px/1.9 {{ $font }};color:#111827;">{{ $color }}</td>
      </tr>
    @endif
    @if($model_year)
      <tr>
        <td style="font:400 13px/1.9 {{ $font }};color:#374151;">Año modelo</td>
        <td style="font:600 13px/1.9 {{ $font }};color:#111827;">{{ $model_year }}</td>
      </tr>
    @endif
    <tr>
      <td style="font:400 13px/1.9 {{ $font }};color:#374151;">Garantía</td>
      <td style="font:600 13px/1.9 {{ $font }};color:#111827;">
        {{ $warranty_years }} año(s) / {{ number_format($warranty_km, 0, '.', ',') }} km
      </td>
    </tr>
  </table>

  {{-- Sección de precios --}}
  <p style="margin:0 0 8px 0;font:700 11px/1 {{ $font }};color:#374151;letter-spacing:1px;text-transform:uppercase;">
    Detalle de precio
  </p>

  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
         style="border:1px solid #d1d5db;border-radius:10px;overflow:hidden;margin-bottom:24px;">

    {{-- Precio base --}}
    <tr style="background:#f9fafb;">
      <td style="padding:12px 16px;font:400 13px/1.5 {{ $font }};color:#374151;border-bottom:1px dotted #d1d5db;">
        Precio base
      </td>
      <td align="right" style="padding:12px 16px;font:500 13px/1.5 'Courier New',Courier,monospace;color:#111827;border-bottom:1px dotted #d1d5db;white-space:nowrap;">
        {{ $currency }} {{ number_format($base_selling_price, 2) }}
      </td>
    </tr>

    {{-- Descuentos --}}
    @if(count($discounts) > 0)
      @foreach($discounts as $discount)
        <tr>
          <td style="padding:9px 16px 9px 30px;font:400 12px/1.5 {{ $font }};color:#374151;border-bottom:1px dotted #e5e7eb;">
            {{ $discount['description'] }}
            <span style="font-size:11px;color:#6b7280;margin-left:4px;">{{ $discount['type'] }}</span>
          </td>
          <td align="right"
              style="padding:9px 16px;font:500 12px/1.5 'Courier New',Courier,monospace;border-bottom:1px dotted #e5e7eb;white-space:nowrap;
              {{ $discount['is_negative'] ? 'color:#b91c1c;' : 'color:#15803d;' }}">
            {{ $discount['is_negative'] ? '−' : '+' }}&nbsp;{{ $currency }} {{ number_format($discount['precio_unitario'], 2) }}
          </td>
        </tr>
      @endforeach
    @endif

    {{-- Accesorios --}}
    @if(count($accessories) > 0)
      @foreach($accessories as $acc)
        <tr>
          <td style="padding:9px 16px 9px 30px;font:400 12px/1.5 {{ $font }};color:#374151;border-bottom:1px dotted #e5e7eb;">
            {{ $acc['description'] }}
            <span style="font-size:11px;color:#6b7280;margin-left:4px;">× {{ $acc['quantity'] }}</span>
          </td>
          <td align="right" style="padding:9px 16px;font:500 12px/1.5 'Courier New',Courier,monospace;color:#15803d;border-bottom:1px dotted #e5e7eb;white-space:nowrap;">
            +&nbsp;{{ $acc['type_currency_code'] }} {{ number_format($acc['total'], 2) }}
          </td>
        </tr>
      @endforeach
    @endif

    {{-- Precio de venta --}}
    <tr style="background:#f9fafb;">
      <td style="padding:12px 16px;font:500 13px/1.5 {{ $font }};color:#111827;border-top:1px solid #d1d5db;border-bottom:1px dotted #d1d5db;">
        Precio de venta
      </td>
      <td align="right" style="padding:12px 16px;font:600 13px/1.5 'Courier New',Courier,monospace;color:#111827;border-top:1px solid #d1d5db;border-bottom:1px dotted #d1d5db;white-space:nowrap;">
        {{ $currency }} {{ number_format($sale_price, 2) }}
      </td>
    </tr>

    {{-- Cuota inicial --}}
    @if($down_payment)
      <tr>
        <td style="padding:12px 16px;font:400 13px/1.5 {{ $font }};color:#374151;border-bottom:1px dotted #d1d5db;">
          Cuota inicial
        </td>
        <td align="right" style="padding:12px 16px;font:500 13px/1.5 'Courier New',Courier,monospace;color:#111827;border-bottom:1px dotted #d1d5db;white-space:nowrap;">
          {{ $currency }} {{ number_format($down_payment, 2) }}
        </td>
      </tr>
    @endif

    {{-- Equivalente moneda doc --}}
    @if($doc_currency && $doc_currency !== $currency && $doc_sale_price)
      <tr>
        <td style="padding:9px 16px;font:400 12px/1.5 {{ $font }};color:#6b7280;border-bottom:1px dotted #e5e7eb;">
          Equivalente en {{ $doc_currency }}
        </td>
        <td align="right" style="padding:9px 16px;font:400 12px/1.5 'Courier New',Courier,monospace;color:#6b7280;border-bottom:1px dotted #e5e7eb;white-space:nowrap;">
          {{ $doc_currency }} {{ number_format($doc_sale_price, 2) }}
        </td>
      </tr>
    @endif

    {{-- TOTAL --}}
    <tr>
      <td style="padding:15px 16px;font:700 14px/1.5 {{ $font }};color:#01237E;background:#e8eeff;border-top:2px solid #c7d2fe;">
        TOTAL
      </td>
      <td align="right" style="padding:15px 16px;font:700 16px/1.5 'Courier New',Courier,monospace;color:#01237E;background:#e8eeff;border-top:2px solid #c7d2fe;white-space:nowrap;">
        {{ $currency }} {{ number_format($sale_price, 2) }}
      </td>
    </tr>

  </table>

  {{-- Comentario --}}
  @if($comment)
    <p style="margin:0 0 6px 0;font:700 11px/1 {{ $font }};color:#374151;letter-spacing:1px;text-transform:uppercase;">
      Observaciones
    </p>
    <p style="margin:0 0 24px 0;font:400 13px/1.7 {{ $font }};color:#374151;border-left:3px solid #d1d5db;padding-left:12px;">
      {{ $comment }}
    </p>
  @endif

  {{-- Nota final --}}
  <p style="margin:0;font:400 11px/1.7 {{ $font }};color:#6b7280;">
    Este documento fue generado automáticamente el {{ $quote_date }}.<br>
    Los precios son referenciales y están sujetos a disponibilidad.
  </p>
@endsection
