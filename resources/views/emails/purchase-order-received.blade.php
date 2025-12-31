{{-- resources/views/emails/purchase-order-received.blade.php --}}
@extends('emails.layouts.base')

@section('content')
  <div style="font:400 14px/1.7 Inter,Arial,Helvetica,sans-serif;color:#111827;">
    <p style="margin:0 0 12px 0;">Hola <strong style="font-weight:600;">{{ $user_name }}</strong>,</p>

    <div class="card card-muted">
      Te informamos que la orden de compra <strong style="font-weight:600;color:#01237E;">{{ $purchase_order_number }}</strong>
      ha sido recibida en almacén. Los productos de tu solicitud ya están disponibles.
    </div>

    @if(isset($request_numbers) && count($request_numbers) > 0)
      <div class="callout">
        <div class="callout-title">Solicitudes relacionadas</div>
        <ul style="margin:8px 0 0 0;padding-left:20px;">
          @foreach($request_numbers as $request_number)
            <li>{{ $request_number }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <div class="callout" style="background:#e6edff;border-left-color:#01237e;">
      <div class="callout-title">Próximos pasos</div>
      <div>
        Ingresa al sistema y busca la orden de compra <strong>{{ $purchase_order_number }}</strong>
        para revisar los detalles completos de tu pedido.
      </div>
    </div>

    <p style="margin:20px 0 0 0;font-size:13px;color:#6b7280;">
      Si tienes dudas sobre tu pedido, por favor contacta al área de almacén.
    </p>
  </div>
@endsection