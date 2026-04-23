@extends('emails.layouts.base')

@section('content')
  @php $font = "Inter,Arial,Helvetica,sans-serif"; @endphp

  {{-- Vigencia (si aplica) --}}
  @if($quote_deadline)
    <p style="margin:0 0 20px 0;font:400 13px/1.5 {{ $font }};color:#6b7280;">
      Vigencia del documento: <span style="color:#111827;font-weight:500;">{{ $quote_deadline }}</span>
    </p>
  @endif

  {{-- Titular y Asesor --}}
  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
         style="margin-bottom:20px;">
    <tr>
      <td width="50%" style="vertical-align:top;padding-right:10px;">
        <p style="margin:0 0 6px 0;font:600 11px/1 {{ $font }};color:#9ca3af;letter-spacing:1px;text-transform:uppercase;">
          Titular
        </p>
        <p style="margin:0;font:400 13px/1.7 {{ $font }};color:#111827;">
          <strong style="font-weight:600;">{{ $holder_name }}</strong><br>
          @if($holder_doc)
            <span style="color:#6b7280;">Doc:</span> {{ $holder_doc }}<br>
          @endif
          @if($holder_phone)
            <span style="color:#6b7280;">Tel:</span> {{ $holder_phone }}<br>
          @endif
          @if($holder_email)
            <span style="color:#6b7280;">Email:</span> {{ $holder_email }}
          @endif
        </p>
      </td>
      <td width="50%" style="vertical-align:top;padding-left:10px;border-left:1px solid #f3f4f6;">
        <p style="margin:0 0 6px 0;font:600 11px/1 {{ $font }};color:#9ca3af;letter-spacing:1px;text-transform:uppercase;">
          Asesor Comercial
        </p>
        <p style="margin:0;font:400 13px/1.7 {{ $font }};color:#111827;">
          <strong style="font-weight:600;">{{ $advisor_name }}</strong><br>
          @if($sede)
            <span style="color:#6b7280;">Sede:</span> {{ $sede }}
          @endif
        </p>
      </td>
    </tr>
  </table>

  {{-- Vehículo --}}
  <p style="margin:0 0 8px 0;font:600 11px/1 {{ $font }};color:#9ca3af;letter-spacing:1px;text-transform:uppercase;">
    Vehículo
  </p>
  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
         style="margin-bottom:24px;">
    <tr>
      <td style="font:400 13px/1.8 {{ $font }};color:#6b7280;width:130px;vertical-align:top;">Marca / Modelo</td>
      <td style="font:500 13px/1.8 {{ $font }};color:#111827;vertical-align:top;">{{ $brand }} {{ $model }}</td>
    </tr>
    @if($color)
      <tr>
        <td style="font:400 13px/1.8 {{ $font }};color:#6b7280;">Color</td>
        <td style="font:500 13px/1.8 {{ $font }};color:#111827;">{{ $color }}</td>
      </tr>
    @endif
    @if($model_year)
      <tr>
        <td style="font:400 13px/1.8 {{ $font }};color:#6b7280;">Año modelo</td>
        <td style="font:500 13px/1.8 {{ $font }};color:#111827;">{{ $model_year }}</td>
      </tr>
    @endif
    <tr>
      <td style="font:400 13px/1.8 {{ $font }};color:#6b7280;">Garantía</td>
      <td style="font:500 13px/1.8 {{ $font }};color:#111827;">
        {{ $warranty_years }} año(s) / {{ number_format($warranty_km, 0, '.', ',') }} km
      </td>
    </tr>
  </table>

  {{-- Sección de precios --}}
  <p style="margin:0 0 8px 0;font:600 11px/1 {{ $font }};color:#9ca3af;letter-spacing:1px;text-transform:uppercase;">
    Detalle de precio
  </p>

  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
         style="border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;margin-bottom:24px;">

    {{-- Precio base --}}
    <tr>
      <td style="padding:11px 16px;font:400 13px/1.5 {{ $font }};color:#4b5563;border-bottom:1px dotted #e5e7eb;">
        Precio base
      </td>
      <td align="right" style="padding:11px 16px;font:500 13px/1.5 'Courier New',Courier,monospace;color:#111827;border-bottom:1px dotted #e5e7eb;white-space:nowrap;">
        {{ $currency }} {{ number_format($base_selling_price, 2) }}
      </td>
    </tr>

    {{-- Descuentos --}}
    @if(count($discounts) > 0)
      @foreach($discounts as $discount)
        <tr>
          <td style="padding:8px 16px 8px 28px;font:400 12px/1.5 {{ $font }};color:#6b7280;border-bottom:1px dotted #f3f4f6;">
            {{ $discount['description'] }}
            <span style="font-size:11px;color:#9ca3af;margin-left:4px;">{{ $discount['type'] }}</span>
          </td>
          <td align="right"
              style="padding:8px 16px;font:400 12px/1.5 'Courier New',Courier,monospace;border-bottom:1px dotted #f3f4f6;white-space:nowrap;
              {{ $discount['is_negative'] ? 'color:#dc2626;' : 'color:#16a34a;' }}">
            {{ $discount['is_negative'] ? '−' : '+' }}&nbsp;{{ $currency }} {{ number_format($discount['precio_unitario'], 2) }}
          </td>
        </tr>
      @endforeach
    @endif

    {{-- Accesorios --}}
    @if(count($accessories) > 0)
      @foreach($accessories as $acc)
        <tr>
          <td style="padding:8px 16px 8px 28px;font:400 12px/1.5 {{ $font }};color:#6b7280;border-bottom:1px dotted #f3f4f6;">
            {{ $acc['description'] }}
            <span style="font-size:11px;color:#9ca3af;margin-left:4px;">× {{ $acc['quantity'] }}</span>
          </td>
          <td align="right" style="padding:8px 16px;font:400 12px/1.5 'Courier New',Courier,monospace;color:#16a34a;border-bottom:1px dotted #f3f4f6;white-space:nowrap;">
            +&nbsp;{{ $acc['type_currency_code'] }} {{ number_format($acc['total'], 2) }}
          </td>
        </tr>
      @endforeach
    @endif

    {{-- Precio de venta --}}
    <tr>
      <td style="padding:11px 16px;font:400 13px/1.5 {{ $font }};color:#4b5563;border-top:1px solid #e5e7eb;border-bottom:1px dotted #e5e7eb;">
        Precio de venta
      </td>
      <td align="right" style="padding:11px 16px;font:600 13px/1.5 'Courier New',Courier,monospace;color:#111827;border-top:1px solid #e5e7eb;border-bottom:1px dotted #e5e7eb;white-space:nowrap;">
        {{ $currency }} {{ number_format($sale_price, 2) }}
      </td>
    </tr>

    {{-- Cuota inicial --}}
    @if($down_payment)
      <tr>
        <td style="padding:11px 16px;font:400 13px/1.5 {{ $font }};color:#4b5563;border-bottom:1px dotted #e5e7eb;">
          Cuota inicial
        </td>
        <td align="right" style="padding:11px 16px;font:500 13px/1.5 'Courier New',Courier,monospace;color:#111827;border-bottom:1px dotted #e5e7eb;white-space:nowrap;">
          {{ $currency }} {{ number_format($down_payment, 2) }}
        </td>
      </tr>
    @endif

    {{-- Equivalente moneda doc --}}
    @if($doc_currency && $doc_currency !== $currency && $doc_sale_price)
      <tr>
        <td style="padding:8px 16px;font:400 12px/1.5 {{ $font }};color:#9ca3af;border-bottom:1px dotted #f3f4f6;">
          Equivalente en {{ $doc_currency }}
        </td>
        <td align="right" style="padding:8px 16px;font:400 12px/1.5 'Courier New',Courier,monospace;color:#9ca3af;border-bottom:1px dotted #f3f4f6;white-space:nowrap;">
          {{ $doc_currency }} {{ number_format($doc_sale_price, 2) }}
        </td>
      </tr>
    @endif

    {{-- TOTAL --}}
    <tr>
      <td style="padding:14px 16px;font:700 14px/1.5 {{ $font }};color:#01237E;background:#f0f4ff;border-top:1px solid #d1d5db;">
        TOTAL
      </td>
      <td align="right" style="padding:14px 16px;font:700 16px/1.5 'Courier New',Courier,monospace;color:#01237E;background:#f0f4ff;border-top:1px solid #d1d5db;white-space:nowrap;">
        {{ $currency }} {{ number_format($sale_price, 2) }}
      </td>
    </tr>

  </table>

  {{-- Comentario --}}
  @if($comment)
    <p style="margin:0 0 6px 0;font:600 11px/1 {{ $font }};color:#9ca3af;letter-spacing:1px;text-transform:uppercase;">
      Observaciones
    </p>
    <p style="margin:0 0 24px 0;font:400 13px/1.7 {{ $font }};color:#4b5563;border-left:3px solid #e5e7eb;padding-left:12px;">
      {{ $comment }}
    </p>
  @endif

  {{-- Nota final --}}
  <p style="margin:0;font:400 11px/1.7 {{ $font }};color:#9ca3af;">
    Este documento fue generado automáticamente el {{ $quote_date }}.<br>
    Los precios son referenciales y están sujetos a disponibilidad.
  </p>
@endsection
