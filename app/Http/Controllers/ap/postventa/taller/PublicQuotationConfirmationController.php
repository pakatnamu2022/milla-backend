<?php

namespace App\Http\Controllers\ap\postventa\taller;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\postventa\taller\ConfirmQuotationVirtuallyRequest;
use App\Http\Resources\ap\postventa\taller\ApOrderQuotationsResource;
use App\Models\ap\postventa\taller\ApOrderQuotations;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PublicQuotationConfirmationController extends Controller
{
  /**
   * Muestra la cotización por token (público - sin autenticación)
   */
  public function show(string $token): JsonResponse
  {
    try {
      $quotation = ApOrderQuotations::with([
        'vehicle.model.family.brand',
        'vehicle.color',
        'client.district',
        'createdBy.person',
        'details.product',
        'sede',
        'typeCurrency',
        'area'
      ])
        ->where('confirmation_token', $token)
        ->firstOrFail();

      // Verificar si el token ha expirado
      if ($quotation->isConfirmationTokenExpired()) {
        return $this->error('El enlace de confirmación ha expirado.');
      }

      // Verificar si ya fue confirmada
      $alreadyConfirmed = $quotation->isConfirmed();

      return $this->success([
        'success' => true,
        'message' => $alreadyConfirmed
          ? 'Esta cotización ya fue confirmada anteriormente.'
          : 'Cotización por confirmar',
        'data' => [
          'already_confirmed' => $alreadyConfirmed,
          'confirmed_at' => $quotation->confirmed_at,
          'confirmation_channel' => $quotation->confirmation_channel,
          'quotation' => !$alreadyConfirmed
            ? new ApOrderQuotationsResource($quotation) : null
        ]
      ]);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Confirma la cotización virtualmente (público - sin autenticación)
   */
  public function confirm(ConfirmQuotationVirtuallyRequest $request, string $token): JsonResponse
  {
    try {
      return DB::transaction(function () use ($request, $token) {
        $quotation = ApOrderQuotations::where('confirmation_token', $token)
          ->lockForUpdate()
          ->firstOrFail();

        // Verificar si el token ha expirado
        if ($quotation->isConfirmationTokenExpired()) {
          throw new Exception('El enlace de confirmación ha expirado.');
        }

        // Verificar si ya fue confirmada
        if ($quotation->isConfirmed()) {
          throw new Exception('Esta cotización ya fue confirmada anteriormente.');
        }

        // Verificar si la cotización está en estado válido para confirmar
        if ($quotation->status === ApOrderQuotations::STATUS_DESCARTADO) {
          throw new Exception('No se puede confirmar una cotización que ha sido descartada.');
        }

        if ($quotation->has_invoice_generated) {
          throw new Exception('Esta cotización ya tiene una factura generada.');
        }

        // Obtener datos de la solicitud
        $data = $request->validated();

        // Preparar metadata de confirmación
        $metadata = [
          'user_agent' => $request->userAgent(),
          'platform' => $request->header('sec-ch-ua-platform'),
          'mobile' => $request->header('sec-ch-ua-mobile'),
          'confirmed_by_name' => $data['confirmed_by_name'] ?? null,
          'notes' => $data['notes'] ?? null,
        ];

        // Actualizar cotización con datos de confirmación virtual
        $quotation->update([
          'confirmed_at' => Carbon::now(),
          'confirmation_channel' => ApOrderQuotations::CONFIRMATION_CHANNEL_VIRTUAL,
          'confirmation_ip' => $request->ip(),
          'confirmation_metadata' => $metadata,
          'status' => ApOrderQuotations::STATUS_POR_FACTURAR,
        ]);

        $quotation->load([
          'vehicle',
          'client',
          'createdBy',
          'details'
        ]);

        return response()->json([
          'success' => true,
          'message' => 'Cotización confirmada exitosamente. Gracias por su preferencia.',
          'data' => [
            'id' => $quotation->id,
            'quotation_number' => $quotation->quotation_number,
            'confirmed_at' => $quotation->confirmed_at,
            'confirmation_channel' => $quotation->confirmation_channel,
            'status' => $quotation->status,
          ]
        ]);
      });
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
