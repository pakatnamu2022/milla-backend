@extends('emails.layouts.base')

@section('content')
<div class="header">
    @if(isset($logo))
        <img src="{{ $logo }}" alt="Logo" class="logo">
    @endif
    <h1>{{ $title ?? 'Notificación' }}</h1>
    @if(isset($subtitle))
        <div class="subtitle">{{ $subtitle }}</div>
    @endif
</div>

<div class="content">
    @if(isset($greeting))
        <div class="greeting">{{ $greeting }}</div>
    @endif

    @if(isset($message))
        <div class="message">{!! $message !!}</div>
    @endif

    @if(isset($button_text) && isset($button_url))
        <div style="text-align: center; margin: 20px 0;">
            <a href="{{ $button_url }}" class="btn {{ $button_style ?? '' }}">{{ $button_text }}</a>
        </div>
    @endif

    @if(isset($additional_info))
        <div class="details">
            <h3>Información adicional</h3>
            {!! $additional_info !!}
        </div>
    @endif

    @if(isset($alert_type) && isset($alert_message))
        <div class="alert alert-{{ $alert_type }}">
            {!! $alert_message !!}
        </div>
    @endif
</div>

<div class="footer">
    <p>{{ $footer_text ?? 'Este es un correo electrónico automático, por favor no responder.' }}</p>
    @if(isset($company_name))
        <div class="company-info">
            <p>&copy; {{ date('Y') }} {{ $company_name }}. Todos los derechos reservados.</p>
        </div>
    @endif
    @if(isset($contact_info))
        <p>Contacto: {{ $contact_info }}</p>
    @endif
</div>
@endsection