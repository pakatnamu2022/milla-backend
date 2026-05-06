{{-- resources/views/emails/evaluation-reminder.blade.php --}}
@extends('emails.layouts.evaluation')

@push('ev_styles')
<style>
@media only screen and (max-width:600px) {
  .ev-ring-stat { display:block !important; width:100% !important;
                  text-align:center !important; padding:10px 0 !important;
                  border-right:none !important; }
  .ev-ring-divider { display:none !important; }
}
</style>
@endpush

@section('email_subject') Recordatorio: Evaluaciones Pendientes @endsection
@section('title') Evaluaciones pendientes @endsection
@section('subtitle') Tienes evaluaciones de desempeño que requieren tu atención. @endsection

@section('content')
@php
  $pending     = (int)($pending_count ?? 0);
  $total       = max(1, (int)($total_count ?? 1));
  $progress    = round((($total - $pending) / $total) * 100);
  $shown       = array_slice($pending_evaluations ?? [], 0, 4);
  $extra       = max(0, count($pending_evaluations ?? []) - 4);

  $ringR       = 52;
  $ringC       = round(2 * M_PI * $ringR, 2);
  $ringOffset  = round($ringC * (1 - $progress / 100), 2);
@endphp

{{-- Greeting --}}
<p style="margin:0 0 22px 0;
          font-family:system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;
          font-size:15px;line-height:1.7;color:#3a3a3c;">
  Hola, <strong style="color:#1d1d1f;">{{ $leader_name }}</strong>.
  Completa las evaluaciones antes de la fecha límite para mantener el proceso al día.
</p>

{{-- Summary card --}}
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
       style="background:#f9f9fb;border-radius:16px;margin-bottom:26px;">
  <tr>
    <td style="padding:28px 28px 24px;">

      {{-- Evaluation label --}}
      <p style="margin:0 0 4px 0;
                font-family:system-ui,-apple-system,sans-serif;
                font-size:10px;font-weight:600;color:#aeaeb2;
                text-transform:uppercase;letter-spacing:0.9px;text-align:center;">
        Evaluación
      </p>
      <p style="margin:0 0 24px 0;
                font-family:system-ui,-apple-system,sans-serif;
                font-size:15px;font-weight:600;color:#1d1d1f;text-align:center;">
        {{ $evaluation_name }}
      </p>

      {{-- Progress ring — Gmail, Apple Mail, modern clients --}}
      <!--[if !mso]><!-->
      <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
        <tr>
          <td align="center" style="padding-bottom:22px;">
            <svg width="120" height="120" viewBox="0 0 120 120"
                 xmlns="http://www.w3.org/2000/svg"
                 style="display:inline-block;vertical-align:middle;">
              <circle cx="60" cy="60" r="{{ $ringR }}"
                      fill="none" stroke="#e8e8ed" stroke-width="8"/>
              <circle cx="60" cy="60" r="{{ $ringR }}"
                      fill="none" stroke="#01237e" stroke-width="8"
                      stroke-linecap="round"
                      stroke-dasharray="{{ $ringC }}"
                      stroke-dashoffset="{{ $ringOffset }}"
                      transform="rotate(-90 60 60)"/>
              <text x="60" y="54" text-anchor="middle"
                    font-family="system-ui,-apple-system,Helvetica,sans-serif"
                    font-size="25" font-weight="700" fill="#1d1d1f">{{ $progress }}%</text>
              <text x="60" y="73" text-anchor="middle"
                    font-family="system-ui,-apple-system,Helvetica,sans-serif"
                    font-size="10" fill="#aeaeb2" letter-spacing="0.6">COMPLETADO</text>
            </svg>
          </td>
        </tr>
      </table>
      <!--<![endif]-->

      {{-- Progress ring — Outlook fallback --}}
      <!--[if mso]>
      <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
        <tr>
          <td align="center" style="padding-bottom:20px;">
            <p style="margin:0 0 4px 0;font-family:Arial,sans-serif;font-size:42px;font-weight:bold;color:#01237e;line-height:1;">{{ $progress }}%</p>
            <p style="margin:0;font-family:Arial,sans-serif;font-size:10px;color:#aeaeb2;letter-spacing:1px;">COMPLETADO</p>
          </td>
        </tr>
      </table>
      <![endif]-->

      {{-- Stats row: pending + deadline --}}
      <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
        <tr>
          <td class="ev-ring-stat" width="50%" align="center" valign="middle"
              style="border-right:1px solid #e8e8ed;padding:4px 16px 4px 0;">
            <p style="margin:0 0 3px 0;
                      font-family:system-ui,-apple-system,sans-serif;
                      font-size:26px;font-weight:700;line-height:1;color:#1d1d1f;">
              {{ $pending_count }}
            </p>
            <p style="margin:0;
                      font-family:system-ui,-apple-system,sans-serif;
                      font-size:10px;font-weight:500;color:#aeaeb2;
                      text-transform:uppercase;letter-spacing:0.6px;">
              Pendientes
            </p>
          </td>
          <td class="ev-ring-divider" width="1">&nbsp;</td>
          <td class="ev-ring-stat" width="50%" align="center" valign="middle"
              style="padding:4px 0 4px 16px;">
            <p style="margin:0 0 3px 0;
                      font-family:system-ui,-apple-system,sans-serif;
                      font-size:16px;font-weight:600;line-height:1.2;color:#1d1d1f;">
              {{ $end_date }}
            </p>
            <p style="margin:0;
                      font-family:system-ui,-apple-system,sans-serif;
                      font-size:10px;font-weight:500;color:#aeaeb2;
                      text-transform:uppercase;letter-spacing:0.6px;">
              Fecha límite
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
