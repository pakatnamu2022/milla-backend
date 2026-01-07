@extends('emails.layouts.base')

@section('content')
  <!-- Wrapper -->
  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
    <tr>
      <td align="center">
        <!-- Container -->
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
               style="max-width:640px;background:#ffffff;border:1px solid #e6e8ee;border-radius:16px;overflow:hidden;">
          <!-- Header -->
          <tr>
            <td style="padding:24px 24px 16px 24px;background:#f9fafc;border-bottom:1px solid #eef0f5;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                  <td align="left" style="vertical-align:middle;">
                    @if(isset($logo))
                      <img src="{{ $logo }}" alt="Logo" width="120"
                           style="display:block;height:auto;border:0;outline:none;text-decoration:none;max-width:160px;">
                    @endif
                  </td>
                  <td align="right" style="vertical-align:middle;">
                  <span
                    style="display:inline-block;padding:6px 10px;border:1px solid #e6e8ee;border-radius:999px;font:600 12px/1.2 Inter,Arial,Helvetica,sans-serif;color:#01237E;background:#eef2ff;">
                    {{ $badge ?? 'Notificación' }}
                  </span>
                  </td>
                </tr>
              </table>

              <h1 style="margin:16px 0 4px 0;font:700 20px/1.25 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                {{ $title ?? 'Notificación del Sistema' }}
              </h1>
              @if(isset($subtitle))
                <p style="margin:0;font:400 14px/1.6 Inter,Arial,Helvetica,sans-serif;color:#4b5563;">
                  {{ $subtitle }}
                </p>
              @endif>
            </td>
          </tr>

          <!-- Body -->
          <tr>
            <td style="padding:24px;">
              @if(isset($user_name))
                <p style="margin:0 0 12px 0;font:400 14px/1.7 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                  Hola <strong style="font-weight:600;color:#111827;">{{ $user_name }}</strong>,
                </p>
              @endif

              @if(isset($main_message))
                <div
                  style="margin:0 0 16px 0;padding:16px;border:1px solid #eef0f5;border-radius:12px;background:#fbfbfe;">
                  <p style="margin:0;font:400 14px/1.7 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                    {{ $main_message }}
                  </p>
                </div>
              @endif

              @if(isset($alert_type) && isset($alert_message))
                @php
                  $alertColors = [
                    'success' => ['bg'=>'#ecfdf5','bd'=>'#10b981','fg'=>'#065f46'],
                    'warning' => ['bg'=>'#fffbeb','bd'=>'#f59e0b','fg'=>'#78350f'],
                    'danger'  => ['bg'=>'#fef2f2','bd'=>'#ef4444','fg'=>'#7f1d1d'],
                    'info'    => ['bg'=>'#eff6ff','bd'=>'#3b82f6','fg'=>'#1e3a8a'],
                  ];
                  $c = $alertColors[$alert_type] ?? $alertColors['info'];
                @endphp
                <div
                  style="margin:0 0 16px 0;padding:12px 14px;border-left:4px solid {{ $c['bd'] }};background:{{ $c['bg'] }};border-radius:10px;">
                  <div
                    style="font:600 13px/1.5 Inter,Arial,Helvetica,sans-serif;color:{{ $c['fg'] }};margin-bottom:4px;">
                    {{ ucfirst($alert_type) }}
                  </div>
                  <div style="font:400 14px/1.6 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                    {!! $alert_message !!}
                  </div>
                </div>
              @endif

              @if(isset($details) && is_array($details) && count($details))
                <div style="margin:0 0 16px 0;">
                  <h3 style="margin:0 0 8px 0;font:600 14px/1.4 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                    Detalles</h3>
                  <ul
                    style="padding:0 0 0 18px;margin:0;font:400 14px/1.7 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                    @foreach($details as $detail)
                      <li style="margin:4px 0;">{{ $detail }}</li>
                    @endforeach
                  </ul>
                </div>
              @endif

              @if(isset($action_needed))
                <div
                  style="margin:0 0 16px 0;padding:12px 14px;border:1px dashed #dfe3ec;border-radius:12px;background:#fcfdfd;">
                  <strong
                    style="display:block;margin-bottom:6px;font:600 14px/1.5 Inter,Arial,Helvetica,sans-serif;color:#01237E;">
                    Acción requerida
                  </strong>
                  <div style="font:400 14px/1.7 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                    {!! $action_needed !!}
                  </div>
                </div>
              @endif

              @if(isset($button_text) && isset($button_url))
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center"
                       style="margin:20px auto;">
                  <tr>
                    <td align="center" bgcolor="#01237E" style="border-radius:10px;">
                      <a href="{{ $button_url }}"
                         style="display:inline-block;padding:12px 20px;font:600 14px/1 Inter,Arial,Helvetica,sans-serif;text-decoration:none;color:#ffffff;background:#01237E;border-radius:10px;border:1px solid #011a5b;">
                        {{ $button_text }}
                      </a>
                    </td>
                  </tr>
                </table>
                @if(isset($button_secondary_text) && isset($button_secondary_url))
                  <div style="text-align:center;margin-top:6px;">
                    <a href="{{ $button_secondary_url }}"
                       style="font:600 13px/1 Inter,Arial,Helvetica,sans-serif;color:#F60404;text-decoration:none;">
                      {{ $button_secondary_text }}
                    </a>
                  </div>
                @endif
              @endif

              @if(isset($extra_cards) && is_array($extra_cards) && count($extra_cards))
                @foreach($extra_cards as $card)
                  <div style="margin:16px 0 0 0;padding:16px;border:1px solid #eef0f5;border-radius:12px;">
                    @if(!empty($card['title']))
                      <div style="font:600 14px/1.4 Inter,Arial,Helvetica,sans-serif;color:#111827;margin-bottom:6px;">
                        {{ $card['title'] }}
                      </div>
                    @endif
                    @if(!empty($card['content']))
                      <div style="font:400 14px/1.7 Inter,Arial,Helvetica,sans-serif;color:#111827;">
                        {!! $card['content'] !!}
                      </div>
                    @endif
                  </div>
                @endforeach
              @endif
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="padding:20px 24px;background:#f9fafc;border-top:1px solid #eef0f5;">
              <p style="margin:0 0 4px 0;font:400 12px/1.6 Inter,Arial,Helvetica,sans-serif;color:#4b5563;">
                Fecha: {{ $date ?? now()->format('d/m/Y H:i') }}
              </p>
              <p style="margin:0 0 8px 0;font:400 12px/1.6 Inter,Arial,Helvetica,sans-serif;color:#6b7280;">
                Este es un correo automático, no responder a este mensaje.
              </p>
              @if(isset($company_name))
                <p style="margin:0 0 4px 0;font:400 12px/1.6 Inter,Arial,Helvetica,sans-serif;color:#6b7280;">
                  &copy; {{ date('Y') }} {{ $company_name }}. Todos los derechos reservados.
                </p>
              @endif
              @if(isset($contact_info))
                <p style="margin:0;font:400 12px/1.6 Inter,Arial,Helvetica,sans-serif;color:#6b7280;">
                  Contacto: {{ $contact_info }}
                </p>
              @endif

              <!-- Social (opcional) -->
              @if(!empty($social))
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin-top:10px;">
                  <tr>
                    @foreach($social as $item)
                      <td style="padding-right:8px;">
                        <a href="{{ $item['url'] }}"
                           style="text-decoration:none;font:600 12px/1 Inter,Arial,Helvetica,sans-serif;color:#01237E;">
                          {{ $item['label'] }}
                        </a>
                      </td>
                    @endforeach
                  </tr>
                </table>
              @endif
            </td>
          </tr>
        </table>
        <!-- /Container -->
      </td>
    </tr>
  </table>

  <!-- Minimal dark-mode hint (algunos clientes lo ignoran) -->
  <style>
    @media (prefers-color-scheme: dark) {
      /* Colores base en oscuro */
      table, td {
        background-color: #0b0f1a !important;
      }

      .invert-bg {
        background-color: #0b0f1a !important;
      }
    }
  </style>
@endsection
