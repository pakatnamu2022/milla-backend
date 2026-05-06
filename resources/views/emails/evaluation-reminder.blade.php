{{-- resources/views/emails/evaluation-reminder.blade.php --}}
@extends('emails.layouts.evaluation')

@section('email_subject') Recordatorio: Evaluaciones Pendientes @endsection
@section('title') Evaluaciones pendientes @endsection
@section('subtitle') Tienes evaluaciones de desempeño que requieren tu atención. @endsection

@section('content')
@php
  $pending  = (int)($pending_count ?? 0);
  $total    = max(1, (int)($total_count ?? 1));
  $progress = round((($total - $pending) / $total) * 100);
  $shown    = array_slice($pending_evaluations ?? [], 0, 4);
  $extra    = max(0, count($pending_evaluations ?? []) - 4);
@endphp

{{-- Greeting --}}
<p style="margin:0 0 20px 0;
          font-family:system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;
          font-size:15px;line-height:1.7;color:#3a3a3c;">
  Hola, <strong style="color:#1d1d1f;">{{ $leader_name }}</strong>.
  Completa las evaluaciones antes de la fecha límite para mantener el proceso al día.
</p>

{{-- Summary card --}}
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
       style="background:#f9f9fb;border-radius:14px;margin-bottom:24px;">
  <tr>
    <td style="padding:24px;">

      {{-- Evaluation name --}}
      <p style="margin:0 0 3px 0;
                font-family:system-ui,-apple-system,sans-serif;
                font-size:11px;font-weight:600;color:#aeaeb2;
                text-transform:uppercase;letter-spacing:0.8px;">
        Evaluación
      </p>
      <p style="margin:0 0 22px 0;
                font-family:system-ui,-apple-system,sans-serif;
                font-size:15px;font-weight:600;color:#1d1d1f;">
        {{ $evaluation_name }}
      </p>

      {{-- Progress percentage --}}
      <p style="margin:0 0 2px 0;
                font-family:system-ui,-apple-system,sans-serif;
                font-size:38px;font-weight:700;line-height:1;
                color:#01237e;letter-spacing:-1px;">
        {{ $progress }}%
      </p>
      <p style="margin:0 0 10px 0;
                font-family:system-ui,-apple-system,sans-serif;
                font-size:11px;color:#aeaeb2;
                text-transform:uppercase;letter-spacing:0.5px;">
        Completado
      </p>

      {{-- Progress bar --}}
      <div style="background:#e8e8ed;border-radius:3px;height:6px;margin-bottom:22px;
                  line-height:6px;font-size:1px;">
        <div style="background:#01237e;border-radius:3px;height:6px;
                    width:{{ $progress }}%;font-size:1px;"></div>
      </div>

      {{-- Stats: pending count + deadline --}}
      <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
             class="ev-stats">
        <tr>
          <td width="50%" valign="top">
            <p style="margin:0;
                      font-family:system-ui,-apple-system,sans-serif;
                      font-size:26px;font-weight:700;line-height:1;color:#1d1d1f;">
              {{ $pending_count }}
            </p>
            <p style="margin:4px 0 0 0;
                      font-family:system-ui,-apple-system,sans-serif;
                      font-size:11px;color:#aeaeb2;
                      text-transform:uppercase;letter-spacing:0.5px;">
              Pendientes de evaluar
            </p>
          </td>
          <td width="50%" align="right" valign="top">
            <p style="margin:0;
                      font-family:system-ui,-apple-system,sans-serif;
                      font-size:15px;font-weight:600;color:#1d1d1f;">
              {{ $end_date }}
            </p>
            <p style="margin:4px 0 0 0;
                      font-family:system-ui,-apple-system,sans-serif;
                      font-size:11px;color:#aeaeb2;
                      text-transform:uppercase;letter-spacing:0.5px;">
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
  <p style="margin:0 0 12px 0;
            font-family:system-ui,-apple-system,sans-serif;
            font-size:11px;font-weight:600;color:#aeaeb2;
            text-transform:uppercase;letter-spacing:0.8px;">
    Personal a evaluar
  </p>

  @foreach($shown as $index => $ev)
    @php
      $p        = (int)($ev['progress_percentage'] ?? 0);
      $words    = preg_split('/\s+/', trim($ev['employee_name']));
      $initials = strtoupper(substr($words[0] ?? '', 0, 1) . substr($words[1] ?? '', 0, 1));
      $isLast   = ($index === count($shown) - 1) && $extra === 0;

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
        <td width="44" valign="middle" style="padding:11px 0;">
          <span style="display:inline-block;width:36px;height:36px;line-height:36px;
                       border-radius:50%;background:#01237e;color:#ffffff;
                       font-family:system-ui,-apple-system,sans-serif;
                       font-size:12px;font-weight:600;text-align:center;">
            {{ $initials }}
          </span>
        </td>
        <td valign="middle" style="padding:11px 0;
                                   font-family:system-ui,-apple-system,sans-serif;
                                   font-size:14px;font-weight:500;color:#1d1d1f;">
          {{ $ev['employee_name'] }}
        </td>
        <td align="right" valign="middle" style="padding:11px 0;white-space:nowrap;">
          <span style="display:inline-block;padding:4px 10px;
                       background:{{ $badgeBg }};border-radius:999px;
                       font-family:system-ui,-apple-system,sans-serif;
                       font-size:11px;font-weight:600;color:{{ $badgeColor }};">
            {{ $badgeLabel }}
          </span>
        </td>
      </tr>
      @if(!$isLast)
      <tr>
        <td colspan="3" style="padding:0;font-size:0;line-height:0;">
          <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
            <tr><td height="1" bgcolor="#f5f5f7" style="font-size:0;line-height:0;">&nbsp;</td></tr>
          </table>
        </td>
      </tr>
      @endif
    </table>
  @endforeach

  @if($extra > 0)
    <p style="margin:12px 0 0 0;
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
           style="display:inline-block;padding:15px 40px;
                  background:#01237e;color:#ffffff;
                  font-family:system-ui,-apple-system,sans-serif;
                  font-size:15px;font-weight:600;line-height:1;
                  text-decoration:none;border-radius:12px;
                  border:1px solid #0131b1;">
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
