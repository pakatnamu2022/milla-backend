<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\StoreRequest;
use App\Models\ap\comercial\Opportunity;
use App\Models\ap\comercial\OpportunityAction;
use Carbon\Carbon;

class UpdateOpportunityRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'worker_id' => [
        'sometimes',
        'integer',
        'exists:rrhh_persona,id',
        function ($attribute, $value, $fail) {
          // Solo validar si se está intentando cambiar el asesor
          $opportunityId = $this->route('id');
          $opportunity = Opportunity::find($opportunityId);

          if (!$opportunity) {
            return;
          }

          // Si el worker_id no está cambiando, no validar
          if ($opportunity->worker_id == $value) {
            return;
          }

          // Obtener la última acción registrada en esta oportunidad
          $lastAction = OpportunityAction::where('opportunity_id', $opportunityId)
            ->orderBy('datetime', 'desc')
            ->first();

          if ($lastAction) {
            $daysSinceLastAction = Carbon::parse($lastAction->datetime)->diffInDays(now());

            if ($daysSinceLastAction < 15) {
              $fail('No se puede cambiar el asesor hasta que hayan pasado al menos 15 días desde la última acción registrada en la oportunidad.');
            }
          }
        },
      ],
      'client_id' => 'sometimes|integer|exists:business_partners,id',
      'family_id' => 'sometimes|integer|exists:ap_families,id',
      'opportunity_type_id' => 'sometimes|integer|exists:ap_commercial_masters,id',
      'client_status_id' => 'sometimes|integer|exists:ap_commercial_masters,id',
      'opportunity_status_id' => 'sometimes|integer|exists:ap_commercial_masters,id',
      'lead_id' => 'sometimes|integer|exists:potential_buyers,id',
    ];
  }

  public function messages(): array
  {
    return [
      'worker_id.integer' => 'El trabajador debe ser un número entero.',
      'worker_id.exists' => 'El trabajador seleccionado no existe.',

      'client_id.integer' => 'El cliente debe ser un número entero.',
      'client_id.exists' => 'El cliente seleccionado no existe.',

      'family_id.integer' => 'La familia debe ser un número entero.',
      'family_id.exists' => 'La familia seleccionada no existe.',

      'opportunity_type_id.integer' => 'El tipo de oportunidad debe ser un número entero.',
      'opportunity_type_id.exists' => 'El tipo de oportunidad seleccionado no existe.',

      'client_status_id.integer' => 'El estado del cliente debe ser un número entero.',
      'client_status_id.exists' => 'El estado del cliente seleccionado no existe.',

      'opportunity_status_id.integer' => 'El estado de la oportunidad debe ser un número entero.',
      'opportunity_status_id.exists' => 'El estado de la oportunidad seleccionado no existe.',
    ];
  }
}
