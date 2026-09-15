{{--
  Plantilla generica para correos de Reclutamiento (carta oferta, bienvenida).
  NO usar la variable "message" aqui: Illuminate\Mail\Mailer::send() siempre la
  sobreescribe con el objeto Illuminate\Mail\Message antes de renderizar la vista,
  por eso este template usa "body_html" en su lugar (ver emails/default.blade.php,
  que sufre este mismo choque de nombres si se le pasa contenido en "message").
--}}
@extends('emails.layouts.base')

@section('content')
<div class="header">
    <h1>{{ $title ?? 'Notificación' }}</h1>
</div>

<div class="content">
    {!! $body_html !!}
</div>

<div class="footer">
    <p>Este es un correo electrónico automático, por favor no responder.</p>
</div>
@endsection
