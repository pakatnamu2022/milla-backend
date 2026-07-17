@extends('emails.layouts.main')

@section('email_subject'){{ $title ?? 'Cotización de Vehículo' }}@endsection
@section('title', 'Nueva cotización generada.')
@section('subtitle')N° {{ $quote_number }}@endsection

@section('content')
  @php $font = "system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif"; @endphp

  {{-- Intro neutral --}}
  <tr>
    <td style="padding:0 0 24px 0;">
      <p style="margin:0;font-family:{{ $font }};font-size:14px;line-height:1.7;color:#6b7280;">
        Se ha generado la siguiente cotización. Revisa los datos a continuación.
      </p>
    </td>
  </tr>

  {{-- ── COTIZACIÓN ── --}}
  <tr>
    <td>
      <p style="margin:0 0 4px 0;font-family:{{ $font }};font-size:11px;font-weight:600;
                letter-spacing:0.8px;text-transform:uppercase;color:#aeaeb2;">Cotización</p>
      <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">

        {{-- N° Cotización --}}
        <tr>
          <td style="padding:14px 0;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
              <tr>
                <td style="width:44px;vertical-align:middle;padding-right:12px;">
                  <img src="https://api.iconify.design/lucide/hash.svg?color=%23111111&width=28&height=28" alt=""
                       width="28" height="28"
                       style="display:block;width:28px;height:28px;border:0;outline:none;text-decoration:none;">
                </td>
                <td style="vertical-align:top;">
                  <p style="margin:0 0 3px 0;font-family:{{ $font }};font-size:16px;font-weight:600;color:#111111;line-height:1.2;">{{ $quote_number }}</p>
                  <p style="margin:0;font-family:{{ $font }};font-size:12px;color:#6b7280;line-height:1.4;">Número de cotización</p>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        {{-- Vigencia --}}
        @if($quote_deadline)
          <tr>
            <td style="padding:14px 0;">
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                <tr>
                  <td style="width:44px;vertical-align:middle;padding-right:12px;">
                    <img src="https://api.iconify.design/lucide/calendar.svg?color=%23111111&width=28&height=28" alt=""
                         width="28" height="28"
                         style="display:block;width:28px;height:28px;border:0;outline:none;text-decoration:none;">
                  </td>
                  <td style="vertical-align:top;">
                    <p style="margin:0 0 3px 0;font-family:{{ $font }};font-size:16px;font-weight:600;color:#111111;line-height:1.2;">{{ $quote_deadline }}</p>
                    <p style="margin:0;font-family:{{ $font }};font-size:12px;color:#6b7280;line-height:1.4;">Vigencia</p>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
        @endif

        {{-- Asesor --}}
        <tr>
          <td style="padding:14px 0;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
              <tr>
                <td style="width:44px;vertical-align:middle;padding-right:12px;">
                  <img src="https://api.iconify.design/lucide/briefcase.svg?color=%23111111&width=28&height=28" alt=""
                       width="28" height="28"
                       style="display:block;width:28px;height:28px;border:0;outline:none;text-decoration:none;">
                </td>
                <td style="vertical-align:top;">
                  <p style="margin:0 0 3px 0;font-family:{{ $font }};font-size:16px;font-weight:600;color:#111111;line-height:1.2;">{{ $advisor_name }}{{ $sede ? ' · ' . $sede : '' }}</p>
                  <p style="margin:0;font-family:{{ $font }};font-size:12px;color:#6b7280;line-height:1.4;">Asesor comercial</p>
                </td>
              </tr>
            </table>
          </td>
        </tr>

      </table>
    </td>
  </tr>

  {{-- Separador --}}
  <tr>
    <td style="padding:12px 0;">
      <div style="height:1px;background:#f0f0f2;font-size:0;line-height:0;">&nbsp;</div>
    </td>
  </tr>

  {{-- ── TITULAR ── --}}
  <tr>
    <td>
      <p style="margin:0 0 4px 0;font-family:{{ $font }};font-size:11px;font-weight:600;
                letter-spacing:0.8px;text-transform:uppercase;color:#aeaeb2;">Titular</p>
      <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">

        {{-- Nombre --}}
        <tr>
          <td style="padding:14px 0;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
              <tr>
                <td style="width:44px;vertical-align:middle;padding-right:12px;">
                  <img src="https://api.iconify.design/lucide/user.svg?color=%23111111&width=28&height=28" alt=""
                       width="28" height="28"
                       style="display:block;width:28px;height:28px;border:0;outline:none;text-decoration:none;">
                </td>
                <td style="vertical-align:top;">
                  <p style="margin:0 0 3px 0;font-family:{{ $font }};font-size:16px;font-weight:600;color:#111111;line-height:1.2;">{{ $holder_name }}</p>
                  <p style="margin:0;font-family:{{ $font }};font-size:12px;color:#6b7280;line-height:1.4;">Nombre completo</p>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        {{-- Documento --}}
        @if(!empty($holder_doc))
          <tr>
            <td style="padding:14px 0;">
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                <tr>
                  <td style="width:44px;vertical-align:middle;padding-right:12px;">
                    <img src="https://api.iconify.design/lucide/id-card.svg?color=%23111111&width=28&height=28" alt=""
                         width="28" height="28"
                         style="display:block;width:28px;height:28px;border:0;outline:none;text-decoration:none;">
                  </td>
                  <td style="vertical-align:top;">
                    <p style="margin:0 0 3px 0;font-family:{{ $font }};font-size:16px;font-weight:600;color:#111111;line-height:1.2;">{{ $holder_doc }}</p>
                    <p style="margin:0;font-family:{{ $font }};font-size:12px;color:#6b7280;line-height:1.4;">Documento</p>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
        @endif

        {{-- Teléfono --}}
        @if(!empty($holder_phone))
          <tr>
            <td style="padding:14px 0;">
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                <tr>
                  <td style="width:44px;vertical-align:middle;padding-right:12px;">
                    <img src="https://api.iconify.design/lucide/phone.svg?color=%23111111&width=28&height=28" alt=""
                         width="28" height="28"
                         style="display:block;width:28px;height:28px;border:0;outline:none;text-decoration:none;">
                  </td>
                  <td style="vertical-align:top;">
                    <p style="margin:0 0 3px 0;font-family:{{ $font }};font-size:16px;font-weight:600;color:#111111;line-height:1.2;">{{ $holder_phone }}</p>
                    <p style="margin:0;font-family:{{ $font }};font-size:12px;color:#6b7280;line-height:1.4;">Teléfono</p>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
        @endif

        {{-- Email --}}
        @if(!empty($holder_email))
          <tr>
            <td style="padding:14px 0;">
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                <tr>
                  <td style="width:44px;vertical-align:middle;padding-right:12px;">
                    <img src="https://api.iconify.design/lucide/mail.svg?color=%23111111&width=28&height=28" alt=""
                         width="28" height="28"
                         style="display:block;width:28px;height:28px;border:0;outline:none;text-decoration:none;">
                  </td>
                  <td style="vertical-align:top;">
                    <p style="margin:0 0 3px 0;font-family:{{ $font }};font-size:16px;font-weight:600;color:#111111;line-height:1.2;">{{ $holder_email }}</p>
                    <p style="margin:0;font-family:{{ $font }};font-size:12px;color:#6b7280;line-height:1.4;">Email</p>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
        @endif

      </table>
    </td>
  </tr>

  {{-- Separador --}}
  <tr>
    <td style="padding:12px 0;">
      <div style="height:1px;background:#f0f0f2;font-size:0;line-height:0;">&nbsp;</div>
    </td>
  </tr>

  {{-- ── VEHÍCULO ── --}}
  <tr>
    <td>
      <p style="margin:0 0 4px 0;font-family:{{ $font }};font-size:11px;font-weight:600;
                letter-spacing:0.8px;text-transform:uppercase;color:#aeaeb2;">Vehículo</p>
      <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">

        {{-- Marca --}}
        <tr>
          <td style="padding:14px 0;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
              <tr>
                <td style="width:44px;vertical-align:middle;padding-right:12px;">
                  <img src="https://api.iconify.design/lucide/car.svg?color=%23111111&width=28&height=28" alt=""
                       width="28" height="28"
                       style="display:block;width:28px;height:28px;border:0;outline:none;text-decoration:none;">
                </td>
                <td style="vertical-align:top;">
                  <p style="margin:0 0 3px 0;font-family:{{ $font }};font-size:16px;font-weight:600;color:#111111;line-height:1.2;">{{ $brand }}</p>
                  <p style="margin:0;font-family:{{ $font }};font-size:12px;color:#6b7280;line-height:1.4;">Marca</p>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        {{-- Modelo --}}
        <tr>
          <td style="padding:14px 0;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
              <tr>
                <td style="width:44px;vertical-align:middle;padding-right:12px;">
                  <img src="https://api.iconify.design/lucide/tag.svg?color=%23111111&width=28&height=28" alt=""
                       width="28" height="28"
                       style="display:block;width:28px;height:28px;border:0;outline:none;text-decoration:none;">
                </td>
                <td style="vertical-align:top;">
                  <p style="margin:0 0 3px 0;font-family:{{ $font }};font-size:16px;font-weight:600;color:#111111;line-height:1.2;">{{ $model }}</p>
                  <p style="margin:0;font-family:{{ $font }};font-size:12px;color:#6b7280;line-height:1.4;">Modelo</p>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        {{-- Año de modelo --}}
        @if($model_year)
          <tr>
            <td style="padding:14px 0;">
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                <tr>
                  <td style="width:44px;vertical-align:middle;padding-right:12px;">
                    <img src="https://api.iconify.design/lucide/calendar-days.svg?color=%23111111&width=28&height=28" alt=""
                         width="28" height="28"
                         style="display:block;width:28px;height:28px;border:0;outline:none;text-decoration:none;">
                  </td>
                  <td style="vertical-align:top;">
                    <p style="margin:0 0 3px 0;font-family:{{ $font }};font-size:16px;font-weight:600;color:#111111;line-height:1.2;">{{ $model_year }}</p>
                    <p style="margin:0;font-family:{{ $font }};font-size:12px;color:#6b7280;line-height:1.4;">Año de modelo</p>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
        @endif

        {{-- Color --}}
        @if($color)
          <tr>
            <td style="padding:14px 0;">
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                <tr>
                  <td style="width:44px;vertical-align:middle;padding-right:12px;">
                    <img src="https://api.iconify.design/lucide/palette.svg?color=%23111111&width=28&height=28" alt=""
                         width="28" height="28"
                         style="display:block;width:28px;height:28px;border:0;outline:none;text-decoration:none;">
                  </td>
                  <td style="vertical-align:top;">
                    <p style="margin:0 0 3px 0;font-family:{{ $font }};font-size:16px;font-weight:600;color:#111111;line-height:1.2;">{{ $color }}</p>
                    <p style="margin:0;font-family:{{ $font }};font-size:12px;color:#6b7280;line-height:1.4;">Color</p>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
        @endif

        {{-- Garantía --}}
        <tr>
          <td style="padding:14px 0;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
              <tr>
                <td style="width:44px;vertical-align:middle;padding-right:12px;">
                  <img src="https://api.iconify.design/lucide/shield-check.svg?color=%23111111&width=28&height=28" alt=""
                       width="28" height="28"
                       style="display:block;width:28px;height:28px;border:0;outline:none;text-decoration:none;">
                </td>
                <td style="vertical-align:top;">
                  <p style="margin:0 0 3px 0;font-family:{{ $font }};font-size:16px;font-weight:600;color:#111111;line-height:1.2;">{{ $warranty_years }} año(s) / {{ number_format($warranty_km, 0, '.', ',') }} km</p>
                  <p style="margin:0;font-family:{{ $font }};font-size:12px;color:#6b7280;line-height:1.4;">Garantía</p>
                </td>
              </tr>
            </table>
          </td>
        </tr>

      </table>
    </td>
  </tr>

  {{-- Separador --}}
  <tr>
    <td style="padding:12px 0;">
      <div style="height:1px;background:#f0f0f2;font-size:0;line-height:0;">&nbsp;</div>
    </td>
  </tr>

  {{-- ── DETALLE DE PRECIO ── --}}
  <tr>
    <td>
      <p style="margin:0 0 16px 0;font-family:{{ $font }};font-size:11px;font-weight:600;
                letter-spacing:0.8px;text-transform:uppercase;color:#aeaeb2;">
        Detalle de precio
      </p>

      <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">

        {{-- Precio base --}}
        <tr>
          <td style="padding:9px 0;font-family:{{ $font }};font-size:13px;color:#6b7280;">
            Precio base
          </td>
          <td align="right" style="padding:9px 0;font-family:{{ $font }};
                     font-size:13px;font-weight:500;color:#111111;white-space:nowrap;">
            {{ $currency }} {{ number_format($base_selling_price, 2) }}
          </td>
        </tr>

        {{-- Descuentos --}}
        @foreach($discounts as $discount)
          <tr>
            <td style="padding:7px 0 7px 16px;font-family:{{ $font }};font-size:12px;color:#6b7280;">
              {{ $discount['description'] }}
              <span style="font-size:11px;color:#aeaeb2;margin-left:4px;">{{ $discount['type'] }}</span>
            </td>
            <td align="right" style="padding:7px 0;font-family:{{ $font }};
                       font-size:12px;font-weight:500;white-space:nowrap;
                       color:{{ $discount['is_negative'] ? '#d93025' : '#1a7f37' }};">
              {{ $discount['is_negative'] ? '−' : '+' }}&nbsp;{{ $currency }} {{ number_format($discount['precio_unitario'], 2) }}
            </td>
          </tr>
        @endforeach

        {{-- Accesorios --}}
        @foreach($accessories as $acc)
          <tr>
            <td style="padding:7px 0 7px 16px;font-family:{{ $font }};font-size:12px;color:#6b7280;">
              {{ $acc['description'] }}
              <span style="font-size:11px;color:#aeaeb2;margin-left:4px;">× {{ $acc['quantity'] }}</span>
            </td>
            <td align="right" style="padding:7px 0;font-family:{{ $font }};
                       font-size:12px;font-weight:500;color:#1a7f37;white-space:nowrap;">
              +&nbsp;{{ $acc['type_currency_code'] }} {{ number_format($acc['total'], 2) }}
            </td>
          </tr>
        @endforeach

      </table>
    </td>
  </tr>

  {{-- ── TOTAL destacado ── --}}
  <tr>
    <td style="padding:16px 0 0 0;">
      <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
             style="background:#eef1fb;border-radius:14px;">
        <tr>
          <td style="padding:22px 24px;">

            {{-- Label --}}
            <p style="margin:0 0 6px 0;font-family:{{ $font }};font-size:11px;font-weight:600;
                      text-transform:uppercase;letter-spacing:0.8px;color:#6b87cb;">
              Precio de venta
            </p>

            {{-- Monto principal --}}
            <p style="margin:0;font-family:{{ $font }};font-size:30px;font-weight:700;
                      color:#01237e;line-height:1.1;letter-spacing:-0.5px;">
              {{ $currency }} {{ number_format($sale_price, 2) }}
            </p>

            {{-- Cuota inicial --}}
            @if($down_payment)
              <p style="margin:10px 0 0 0;font-family:{{ $font }};font-size:13px;
                        color:#4a5c99;font-weight:500;">
                Cuota inicial:
                <span style="font-weight:600;color:#01237e;">{{ $currency }} {{ number_format($down_payment, 2) }}</span>
              </p>
            @endif

            {{-- Equivalente en otra moneda --}}
            @if($doc_currency && $doc_currency !== $currency && $doc_sale_price)
              <p style="margin:4px 0 0 0;font-family:{{ $font }};font-size:12px;color:#8a9cc7;">
                Equivalente: {{ $doc_currency }} {{ number_format($doc_sale_price, 2) }}
              </p>
            @endif

          </td>
        </tr>
      </table>
    </td>
  </tr>

  {{-- Observaciones --}}
  @if(!empty($comment))
    <tr>
      <td style="padding:20px 0 0 0;">
        <p style="margin:0 0 6px 0;font-family:{{ $font }};font-size:11px;font-weight:600;
                  letter-spacing:0.8px;text-transform:uppercase;color:#aeaeb2;">
          Observaciones
        </p>
        <p style="margin:0;font-family:{{ $font }};font-size:13px;line-height:1.7;color:#3a3a3c;">
          {{ $comment }}
        </p>
      </td>
    </tr>
  @endif

  {{-- Nota final --}}
  <tr>
    <td style="padding:28px 0 0 0;">
      <p style="margin:0;font-family:{{ $font }};font-size:12px;line-height:1.6;color:#aeaeb2;text-align:center;">
        Generado el {{ $quote_date }}. Los precios son referenciales y están sujetos a disponibilidad.
      </p>
    </td>
  </tr>

  <tr><td style="height:8px;font-size:0;"></td></tr>
@endsection
