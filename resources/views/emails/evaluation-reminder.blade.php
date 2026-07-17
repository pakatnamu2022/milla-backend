{{-- resources/views/emails/evaluation-reminder.blade.php --}}
@extends('emails.layouts.main')

@push('styles')
  <style>
    @media only screen and (max-width: 600px) {

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
  Tienes evaluaciones de guerreros que requieren tu atención.
@endsection

@section('content')
<tr>
<td>
  @php
    $pending  = (int)($pending_count ?? 0);
    $total    = max(1, (int)($total_count ?? 1));
    $progress = round((($total - $pending) / $total) * 100);
    $shown    = array_slice($pending_evaluations ?? [], 0, 4);
    $extra    = max(0, count($pending_evaluations ?? []) - 4);
  @endphp

  {{-- Greeting --}}
  <p style="margin:0 0 28px 0;
          font-family:system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;
          font-size:15px;line-height:1.7;color:#3a3a3c;">
    Hola, <strong style="color:#1d1d1f;font-weight:500;">{{ Str::title($leader_name) }}</strong>. Completa las
    evaluaciones de tus guerreros antes del <span
      style="font-weight:600;color:#1d1d1f;">{{ \Carbon\Carbon::parse($end_date)->locale('es')->translatedFormat('d \d\e F \d\e Y') }}</span>
    para asegurar un proceso completo y justo.
  </p>

  {{-- Summary card - Apple Style Horizontal Progress --}}
  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
         style="background:#f7f7f9;border-radius:18px;margin-bottom:28px;border:1px solid #f0f0f2;overflow:hidden;">
    <tr>
      <td style="padding:28px 32px;">

        {{-- Main content table: icon + metrics --}}
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
          <tr>
            {{-- Left: Icon --}}
            <td width="54" valign="middle" style="padding-right:20px;">
              <span style="display:inline-block;width:72px;height:72px;line-height:72px;
                           border-radius:50%;background:#e8e8ed;text-align:center;">
                <img src="https://api.iconify.design/lucide/clipboard-check.svg?color=%2301237e&width=32&height=32"
                     width="32" height="32"
                     style="display:inline-block;vertical-align:middle;width:32px;height:32px;border:0;outline:none;text-decoration:none;"
                     alt="">
              </span>
            </td>

            {{-- Right: Score & Progress --}}
            <td valign="middle" width="*">
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                <tr>
                  <td valign="middle">

                    <p style="margin:0 0 6px 0;
                              font-family:system-ui,-apple-system,sans-serif;
                              font-size:20px;line-height:1;
                              letter-spacing:-0.5px;">
                      <span style="font-size:28px;color:#01237e;font-weight:600;">{{ $pending }}</span> evaluaciones
                      pendientes
                    </p>
                  </td>
                </tr>
              </table>

              {{-- Progress bar - Simple and elegant --}}
              <div style="background:#e5e5ea;border-radius:6px;height:6px;margin:12px 0 8px 0;
                          line-height:6px;font-size:1px;overflow:hidden;">
                <div style="background:#01237e;border-radius:6px;height:6px;
                            width:{{ $progress }}%;font-size:1px;display:block;"></div>
              </div>

              {{-- Completion text --}}
              <p style="margin:0;font-family:system-ui,-apple-system,sans-serif;
                        font-size:12px;color:#6b7280;letter-spacing:0.3px;">
                {{ $progress }}% completado
              </p>
            </td>

          </tr>
        </table>

      </td>
    </tr>
  </table>

  {{-- Collaborator list --}}
  @if(!empty($shown))
    <p style="margin:0 0 16px 0;
            font-family:system-ui,-apple-system,sans-serif;
            font-size:11px;font-weight:600;color:#aeaeb2;
            text-transform:uppercase;letter-spacing:0.9px;">
      Personal a evaluar
    </p>

    @foreach($shown as $index => $ev)
      @php
        $p        = (int)($ev['progress_percentage'] ?? 0);
        $words    = preg_split('/\s+/', trim($ev['employee_name']));
        $initials = strtoupper(substr($words[0] ?? '', 0, 1) . substr($words[1] ?? '', 0, 1));

        if ($p === 0) {
          $badgeLabel = 'Pendiente';
          $badgeBg    = '#fffbf0';
          $badgeColor = '#c67c1b';
          $badgeBorder = '#fde8cc';
        } elseif ($p < 100) {
          $badgeLabel = 'En progreso';
          $badgeBg    = '#f0f9ff';
          $badgeColor = '#0369a1';
          $badgeBorder = '#e0f2fe';
        } else {
          $badgeLabel = 'Completado';
          $badgeBg    = '#f0fdf4';
          $badgeColor = '#15803d';
          $badgeBorder = '#dcfce7';
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
          <td valign="middle" class="ev-collab-name" style="padding:14px 0;
                                   font-family:system-ui,-apple-system,sans-serif;
                                   font-size:15px;font-weight:500;color:#1d1d1f;">
            {{ Str::title($ev['employee_name']) }}
          </td>
          <td align="right" valign="middle" style="padding:14px 0;white-space:nowrap;">
          <span style="display:inline-block;padding:7px 14px;
                       background:{{ $badgeBg }};border-radius:999px;
                       font-family:system-ui,-apple-system,sans-serif;
                       font-size:11px;font-weight:600;color:{{ $badgeColor }};
                       border:1px solid {{ $badgeBorder }};">
           {{ $badgeLabel }}
          </span>
          </td>
        </tr>
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
           style="margin-top:32px;">
      <tr>
        <td align="center">
          <a href="{{ $evaluation_url }}"
             style="display:inline-block;padding:14px 56px;
                  background:#01237e;color:#ffffff;
                  font-family:system-ui,-apple-system,sans-serif;
                  font-size:15px;font-weight:600;line-height:1;
                  text-decoration:none;border-radius:12px;
                  box-shadow:0 2px 8px rgba(1,35,126,0.2);">
            Completar evaluaciones
          </a>
        </td>
      </tr>
    </table>
  @endisset

  {{-- Additional notes --}}
  @isset($additional_notes)
    <p style="margin:24px 0 0 0;
            font-family:system-ui,-apple-system,sans-serif;
            font-size:13px;line-height:1.6;color:#aeaeb2;text-align:center;">
      {{ $additional_notes }}
    </p>
  @endisset

  <div style="height:8px;font-size:0;"></div>
</td>
</tr>
@endsection
