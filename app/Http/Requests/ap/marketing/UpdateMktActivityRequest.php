<?php

namespace App\Http\Requests\ap\marketing;

use App\Http\Requests\StoreRequest;

class UpdateMktActivityRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'budget_id'        => 'nullable|integer|exists:ap_mkt_budgets,id',
      'activity_type'    => 'nullable|string|max:100',
      'name'             => 'nullable|string|max:200',
      'description'      => 'nullable|string',
      'objective'        => 'nullable|string',
      'responsible'      => 'nullable|string|max:150',
      'channel'          => 'nullable|string|max:150',
      'start_date'       => 'nullable|date',
      'end_date'         => 'nullable|date|after_or_equal:start_date',
      'currency_id'      => 'nullable|integer|exists:type_currency,id',
      'estimated_amount' => 'nullable|numeric|min:0',
      'supplier_id'      => 'nullable|integer|exists:business_partners,id',
      'status'           => 'nullable|string|in:planned,in_progress,executed,cancelled',
      'notes'            => 'nullable|string',
    ];
  }

  public function attributes(): array
  {
    return [
      'budget_id'        => 'presupuesto',
      'activity_type'    => 'tipo de actividad',
      'name'             => 'nombre',
      'description'      => 'descripción',
      'objective'        => 'objetivo',
      'responsible'      => 'responsable',
      'channel'          => 'canal',
      'start_date'       => 'fecha de inicio',
      'end_date'         => 'fecha de fin',
      'currency_id'      => 'moneda',
      'estimated_amount' => 'monto estimado',
      'supplier_id'      => 'proveedor',
      'status'           => 'estado',
      'notes'            => 'notas',
    ];
  }
}
