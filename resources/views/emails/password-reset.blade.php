@extends('emails.layouts.per-diem')

@section('email_subject', 'Restablecer contraseña — Sian')

@section('title', 'Restablecer contraseña')

@section('subtitle', 'Recibimos una solicitud para restablecer la contraseña de tu cuenta.')

@section('content')

  {{-- Nombre --}}
  <tr>
    <td style="padding-bottom:20px;">
      <p style="margin:0;font-family:system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;font-size:15px;line-height:1.6;color:#374151;">
        Hola, <strong>{{ $name }}</strong>.
      </p>
    </td>
  </tr>

  {{-- Mensaje --}}
  <tr>
    <td style="padding-bottom:28px;">
      <p style="margin:0;font-family:system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;font-size:15px;line-height:1.6;color:#374151;">
        Haz clic en el botón de abajo para crear una nueva contraseña. Este enlace expirará en
        <strong>60 minutos</strong>.
      </p>
    </td>
  </tr>

  {{-- Botón --}}
  <tr>
    <td style="padding-bottom:28px;" align="left">
      <a href="{{ $resetUrl }}"
         style="display:inline-block;padding:12px 28px;background:#111111;color:#ffffff;font-family:system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;font-size:14px;font-weight:500;text-decoration:none;border-radius:6px;letter-spacing:0.01em;">
        Restablecer contraseña
      </a>
    </td>
  </tr>

  {{-- Aviso token --}}
  <tr>
    <td style="padding-bottom:8px;border-top:1px solid #f0f0f0;padding-top:20px;">
      <p style="margin:0;font-family:system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;font-size:13px;line-height:1.6;color:#6b7280;">
        Si no solicitaste este cambio, ignora este correo. Tu contraseña no será modificada.
      </p>
    </td>
  </tr>

  {{-- URL de respaldo --}}
  <tr>
    <td>
      <p style="margin:0;font-family:system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;font-size:12px;line-height:1.6;color:#9ca3af;">
        Si el botón no funciona, copia y pega el siguiente enlace en tu navegador:<br>
        <span style="color:#6b7280;word-break:break-all;">{{ $resetUrl }}</span>
      </p>
    </td>
  </tr>

@endsection
