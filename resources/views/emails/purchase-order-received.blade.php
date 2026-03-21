{{-- resources/views/emails/purchase-order-received.blade.php --}}
@extends('emails.layouts.base')

@section('content')
  <div style="font:400 14px/1.7 Inter,Arial,Helvetica,sans-serif;color:#111827;">
    <p style="margin:0 0 12px 0;">Hola <strong style="font-weight:600;">{{ $user_name }}</strong>,</p>

    <div class="card card-muted">
      Te informamos que la orden de compra <strong
        style="font-weight:600;color:#01237E;">{{ $purchase_order_number }}</strong>
      ha sido recibida en almacén. Los productos de tu solicitud ya están disponibles.
    </div>

    @if(isset($received_items) && count($received_items) > 0)
      <div style="margin: 20px 0;">
        <h3 style="margin: 0 0 12px 0; font-size: 15px; font-weight: 600; color: #111827;">
          Detalle de productos recepcionados
        </h3>
        <table
          style="width: 100%; border-collapse: collapse; background: #fff; border: 1px solid #e5e7eb; border-radius: 6px; overflow: hidden;">
          <thead>
          <tr style="background: #f9fafb;">
            <th
              style="padding: 12px; text-align: left; font-weight: 600; font-size: 13px; color: #374151; border-bottom: 1px solid #e5e7eb;">
              Código
            </th>
            <th
              style="padding: 12px; text-align: left; font-weight: 600; font-size: 13px; color: #374151; border-bottom: 1px solid #e5e7eb;">
              Producto
            </th>
            <th
              style="padding: 12px; text-align: center; font-weight: 600; font-size: 13px; color: #374151; border-bottom: 1px solid #e5e7eb;">
              Cantidad
            </th>
          </tr>
          </thead>
          <tbody>
          @foreach($received_items as $item)
            <tr>
              <td style="padding: 12px; font-size: 13px; color: #6b7280; border-bottom: 1px solid #f3f4f6;">
                <code
                  style="background: #f3f4f6; padding: 2px 6px; border-radius: 3px; font-size: 12px;">{{ $item['code'] }}</code>
              </td>
              <td
                style="padding: 12px; font-size: 13px; color: #111827; border-bottom: 1px solid #f3f4f6;">{{ $item['name'] }}</td>
              <td
                style="padding: 12px; font-size: 13px; color: #111827; text-align: center; border-bottom: 1px solid #f3f4f6;">
                <strong style="font-weight: 600;">{{ $item['quantity'] }}</strong> {{ $item['unit'] ?? '' }}
              </td>
            </tr>
          @endforeach
          </tbody>
        </table>
      </div>
    @endif

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
