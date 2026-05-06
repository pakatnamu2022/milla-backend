{{-- resources/views/emails/evaluation-reminder.blade.php --}}
@extends('emails.layouts.evaluation')

@push('ev_styles')
  <style>
    @media only screen and (max-width: 600px) {
      .ev-ring-col {
        display: block !important;
        width: 100% !important;
        padding: 0 0 20px 0 !important;
        border-right: none !important;
      }

      .ev-ring-sep {
        display: none !important;
      }

      .ev-count-col {
        display: block !important;
        width: 100% !important;
        padding: 20px 0 0 0 !important;
        border-top: 1px solid #e8e8ed;
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
          font-size:15px;line-height:1.7;color:#3a3a3c;">
    Hola, <strong style="color:#1d1d1f;">{{ $leader_name }}</strong>.
    Completa las evaluaciones antes de la fecha límite para mantener el proceso al día.
  </p>

  {{-- Evaluation name + period (above card) --}}
  <p style="margin:0 0 2px 0;
          font-family:system-ui,-apple-system,sans-serif;
          font-size:15px;font-weight:600;color:#1d1d1f;">
    {{ $evaluation_name }}
  </p>
  <p style="margin:0 0 20px 0;
          font-family:system-ui,-apple-system,sans-serif;
          font-size:13px;color:#aeaeb2;">
    {{ $start_date }} — {{ $end_date }}
  </p>

  {{-- Summary card --}}
  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
         style="background:#fbfafc;border-radius:16px;border:1px solid #efefef;margin-bottom:26px;">
    <tr>
      <td style="padding:28px 24px;">

        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
          <tr>

            {{-- Left: progress ring --}}
            <td class="ev-ring-col" width="46%" align="center" valign="middle"
                style="border-right:1px solid #e8e8ed;padding-right:20px;">

              {{-- conic-gradient ring — Gmail web, Apple Mail, modern clients --}}
              <!--[if !mso]><!-->
              <div style="width:110px;height:110px;border-radius:55px;
                        background:conic-gradient(from -90deg, #01237e {{ $progress }}%, #e8e8ed 0%);
                        margin:0 auto;font-size:0;line-height:0;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                       width="86" height="86"
                       style="border-radius:43px;background:#fbfafc;margin:12px auto;">
                  <tr>
                    <td align="center" valign="middle">
                      <p style="margin:0;
                              font-family:system-ui,-apple-system,Helvetica,Arial,sans-serif;
                              font-size:22px;font-weight:700;color:#1d1d1f;line-height:1;">
                        {{ $progress }}%
                      </p>
                      <p style="margin:5px 0 0;
                              font-family:system-ui,-apple-system,Helvetica,Arial,sans-serif;
                              font-size:9px;font-weight:600;color:#aeaeb2;
                              letter-spacing:0.6px;">
                        Completado
                      </p>
                    </td>
                  </tr>
                </table>
              </div>
              <!--<![endif]-->

              {{-- Outlook fallback --}}
              <!--[if mso]>
            <p style="margin:0 0 2px;font-family:Arial,sans-serif;font-size:36px;font-weight:bold;color:#01237e;line-height:1;text-align:center;">{{ $progress }}%</p>
            <p style="margin:0;font-family:Arial,sans-serif;font-size:9px;color:#aeaeb2;text-align:center;letter-spacing:1px;text-transform:uppercase;">Completado</p>
            <![endif]-->

            </td>

            {{-- Separator --}}
            <td class="ev-ring-sep" width="1" bgcolor="#e8e8ed"
                style="background:#e8e8ed;font-size:0;line-height:0;">&nbsp;
            </td>

            {{-- Right: pending count --}}
            <td class="ev-count-col" width="46%" align="center" valign="middle"
                style="padding-left:20px;padding-right:20px;">
              <p style="margin:0 0 6px 0;
                      font-family:system-ui,-apple-system,Helvetica,Arial,sans-serif;
                      font-size:52px;font-weight:700;line-height:1;color:#1d1d1f;
                      letter-spacing:-2px;">
                {{ $pending_count }}
              </p>
              <p style="margin:0;
                      font-family:system-ui,-apple-system,Helvetica,Arial,sans-serif;
                      font-size:13px;line-height:1.5;color:#3a3a3c;">
                evaluaciones<br>pendientes
              </p>
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
          $badgeBg    = '#fef3c7';
          $badgeColor = '#92400e';
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
                                   font-size:14px;font-weight:500;color:#1d1d1f;">
            {{ $ev['employee_name'] }}
          </td>
          <td align="right" valign="middle" style="padding:14px 0;white-space:nowrap;">
          <span style="display:inline-block;padding:5px 12px;
                       background:{{ $badgeBg }};border-radius:999px;
                       font-family:system-ui,-apple-system,sans-serif;
                       font-size:11px;font-weight:600;color:{{ $badgeColor }};">
            {{ $badgeLabel }}
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
                  text-decoration:none;border-radius:14px;
                  box-shadow:0 4px 16px rgba(1,35,126,0.28);">
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
