{{-- resources/views/emails/evaluation-reminder.blade.php --}}
@extends('emails.layouts.evaluation')

@push('ev_styles')
  <style>
    @media only screen and (max-width: 600px) {
      .ev-ring-col {
        display: block !important;
        width: 100% !important;
        border-right: none !important;
        padding-bottom: 16px !important;
      }

      .ev-ring-sep {
        display: block !important;
        width: 100% !important;
        height: 1px !important;
        margin: 16px 0 !important;
        padding: 0 !important;
      }

      .ev-ring-sep img {
        width: 100% !important;
        height: 1px !important;
      }

      .ev-count-col {
        display: block !important;
        width: 100% !important;
        padding: 0 !important;
        border-top: none !important;
      }

      .ev-collab-name {
        padding-right: 12px !important;
      }
    }
  </style>
@endpush

@section('email_subject')
  Recordatorio: Evaluaciones Pendientes
@endsection
@section('title')
  Evaluaciones pendientes
@endsection
@section('subtitle')
  Tienes evaluaciones de desempeño que requieren tu atención.
@endsection

@section('content')
  @php
    $pending  = (int)($pending_count ?? 0);
    $total    = max(1, (int)($total_count ?? 1));
    $progress = round((($total - $pending) / $total) * 100);
    $shown    = array_slice($pending_evaluations ?? [], 0, 4);
    $extra    = max(0, count($pending_evaluations ?? []) - 4);
  @endphp

  {{-- Greeting --}}
  <p style="margin:0 0 6px 0;
          font-family:system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;
          font-size:15px;line-height:1.7;color:#3a3a3c; padding-bottom: 20px;">
    Hola, <strong style="color:#01237e;">{{ Str::title($leader_name) }}</strong>. Completa las evaluaciones antes
    del <span
      style="font-weight: 700;color:#01237e;">{{ \Carbon\Carbon::parse($end_date)->locale('es')->translatedFormat('d \d\e F \d\e Y') }}</span>
    para mantener el proceso al día.
  </p>

  {{-- Summary card --}}
  {{-- Usamos solo la tabla para las esquinas redondeadas (evita doble rounded causado por un <div> externo) --}}
  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
         style="background:#fbfafc;border-radius:16px;border:1px solid #efefef;margin-bottom:26px;border-collapse:separate;border-spacing:0;">
    <tr>
      <td style="padding:28px 24px;border-radius:16px;overflow:hidden;background:transparent;">

        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
               style="border-collapse:collapse;border-spacing:0;background:transparent;">
          <tr>

            {{-- Left: progress ring --}}
            <td class="ev-ring-col" width="46%" align="center" valign="middle">

              {{-- conic-gradient ring — Gmail web, Apple Mail, modern clients --}}
              <!--[if !mso]><!-->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                     width="110" height="110" align="center"
                     style="width:110px;height:110px;border-collapse:collapse;
                            border-radius:55px;background:conic-gradient(from -90deg, #01237e {{ $progress }}%, #e8e8ed 0%);
                            margin:0 auto;font-size:0;line-height:0;">
                <tr>
                  <td align="center" valign="middle" style="padding:0;font-size:0;line-height:0;">
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                           width="95" height="95" align="center"
                           style="width:95px;height:95px;border-collapse:collapse;
                                  border-radius:1000px;background:#fbfafc;">
                      <tr>
                        <td align="center" valign="middle" style="padding:0;">
                          <p style="margin:0;
                                  font-family:system-ui,-apple-system,Helvetica,Arial,sans-serif;
                                  font-size:20px;font-weight:600;color:#1d1d1f;line-height:1;">
                              <span style="margin:0;
                                  font-family:system-ui,-apple-system,Helvetica,Arial,sans-serif;
                                  font-size:34px;font-weight:600;color:#1d1d1f;line-height:1;">{{ $progress }}</span>%
                          </p>
                          <p style=" margin:5px 0 0;
                                    font-family:system-ui,-apple-system,Helvetica,Arial,sans-serif;
                                    font-size:9px;font-weight:600;color:#aeaeb2;
                                    letter-spacing:0.6px;padding-bottom: 10px;">
                            Completado
                          </p>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>
              <!--<![endif]-->

              {{-- Outlook fallback --}}
              <!--[if mso]>
            <p style="margin:0 0 2px;font-family:Arial,sans-serif;font-size:36px;font-weight:bold;color:#01237e;line-height:1;text-align:center;">{{ $progress }}%</p>
            <p style="margin:0;font-family:Arial,sans-serif;font-size:9px;color:#aeaeb2;text-align:center;letter-spacing:1px;text-transform:uppercase;">Completado</p>
            <![endif]-->

            </td>

            {{-- Separator: 1px centered al alto del ring --}}
            <td width="1" align="center" valign="middle" style="padding:0;margin:0;">
              <img
                src="data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='1' height='110'><rect width='1' height='110' fill='%23e8e8ed'/></svg>"
                width="1" height="110"
                style="display:block;border:0;line-height:0;width:1px;height:110px;max-width:1px;" alt=""
                aria-hidden="true">
            </td>

            {{-- Right: pending count with icon (rounded) --}}
            <td class="ev-count-col" width="46%" align="center" valign="middle">
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
                     style="border-collapse:collapse;">
                <tr>
                  <td align="left" valign="middle" style="width:52px;padding-right:12px;">
                    <span
                      style="display:inline-block;width:40px;height:40px;border-radius:50%;background:#eef6ff;box-shadow:0 4px 10px rgba(1,35,126,0.07);text-align:center;">
                      <img src="https://api.iconify.design/lucide/users.svg?color=%2301237e&width=18&height=18"
                           width="18" height="18"
                           style="display:block;margin:11px auto;width:18px;height:18px;border:0;outline:none;text-decoration:none;"
                           alt="">
                    </span>
                  </td>
                  <td align="left" valign="middle" style="padding:0;">
                    <p
                      style="margin:0;font-size:14px;font-family:system-ui,-apple-system,Helvetica,Arial,sans-serif;color:#1d1d1f;line-height:1;">
                      <strong style="font-size:16px;font-weight:700">{{ $pending_count }}</strong> evaluaciones
                      pendientes
                    </p>

                  </td>
                </tr>
              </table>

            </td>

          </tr>
        </table>

      </td>
    </tr>
  </table>

  {{-- Collaborator list --}}
  @if(!empty($shown))
    <p style="margin:0 0 14px 0;
            font-family:system-ui,-apple-system,sans-serif;
            font-size:10px;font-weight:600;color:#aeaeb2;
            text-transform:uppercase;letter-spacing:0.8px;">
      Personal a evaluar
    </p>

    @foreach($shown as $index => $ev)
      @php
        $p        = (int)($ev['progress_percentage'] ?? 0);
        $words    = preg_split('/\s+/', trim($ev['employee_name']));
        $initials = strtoupper(substr($words[0] ?? '', 0, 1) . substr($words[1] ?? '', 0, 1));

        if ($p === 0) {
          $badgeLabel = 'Pendiente';
          $badgeBg    = '#ffedd4';
          $badgeColor = '#e17100';
        } elseif ($p < 100) {
          $badgeLabel = 'En progreso';
          $badgeBg    = '#eff6ff';
          $badgeColor = '#1e40af';
        } else {
          $badgeLabel = 'Completado';
          $badgeBg    = '#f0fdf4';
          $badgeColor = '#166534';
        }
      @endphp

      <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
        <tr>
          <td width="48" valign="middle" style="padding:14px 0;">
          <span style="display:inline-block;width:38px;height:38px;line-height:38px;
                       border-radius:50%;background:#01237e;color:#ffffff;
                       font-family:system-ui,-apple-system,sans-serif;
                       font-size:12px;font-weight:600;text-align:center;">
            {{ $initials }}
          </span>
          </td>
          <td valign="middle" style="padding:14px 0;
                                   font-family:system-ui,-apple-system,sans-serif;
                                   font-size:16px;font-weight:500;color:#1d1d1f;">
            {{ Str::title($ev['employee_name']) }}
          </td>
          <td align="right" valign="middle" style="padding:14px 0;white-space:nowrap;">
          <span style="display:inline-block;padding:5px 12px;
                       background:{{ $badgeBg }};border-radius:999px;
                       font-family:system-ui,-apple-system,sans-serif;
                       font-size:12px;font-weight:600;color:{{ $badgeColor }};">
           • &nbsp; {{ $badgeLabel }}
          </span>
          </td>
        </tr>
      </table>
    @endforeach

    @if($extra > 0)
      <p style="margin:8px 0 0 0;
              font-family:system-ui,-apple-system,sans-serif;
              font-size:13px;color:#aeaeb2;text-align:center;">
        +{{ $extra }} {{ $extra === 1 ? 'colaborador más' : 'colaboradores más' }}
      </p>
    @endif
  @endif

  {{-- CTA --}}
  @isset($evaluation_url)
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
           style="margin-top:28px;">
      <tr>
        <td align="center">
          <a href="{{ $evaluation_url }}"
             style="display:inline-block;padding:16px 48px;
                  background:#01237e;color:#ffffff;
                  font-family:system-ui,-apple-system,sans-serif;
                  font-size:15px;font-weight:600;line-height:1;
                  text-decoration:none;border-radius:14px;">
            Completar evaluaciones
          </a>
        </td>
      </tr>
    </table>
  @endisset

  {{-- Additional notes --}}
  @isset($additional_notes)
    <p style="margin:20px 0 0 0;
            font-family:system-ui,-apple-system,sans-serif;
            font-size:13px;line-height:1.6;color:#aeaeb2;text-align:center;">
      {{ $additional_notes }}
    </p>
  @endisset

  <div style="height:8px;font-size:0;"></div>
@endsection
