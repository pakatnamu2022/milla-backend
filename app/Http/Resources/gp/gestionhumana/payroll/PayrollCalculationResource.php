<?php

namespace App\Http\Resources\gp\gestionhumana\payroll;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollCalculationResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @return array<string, mixed>
   */
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'total_normal_hours' => (float) $this->total_normal_hours,
      'total_extra_hours_25' => (float) $this->total_extra_hours_25,
      'total_extra_hours_35' => (float) $this->total_extra_hours_35,
      'total_night_hours' => (float) $this->total_night_hours,
      'total_holiday_hours' => (float) $this->total_holiday_hours,
      'days_worked' => (int) $this->days_worked,
      'days_absent' => (int) $this->days_absent,
      'gross_salary' => (float) $this->gross_salary,
      'total_earnings' => (float) $this->total_earnings,
      'total_deductions' => (float) $this->total_deductions,
      'net_salary' => (float) $this->net_salary,
      'employer_cost' => (float) $this->employer_cost,
      'status' => $this->status,
      'can_modify' => $this->canModify(),
      'can_approve' => $this->canApprove(),
      'calculated_at' => $this->calculated_at,
      'approved_at' => $this->approved_at,

      // Relations
      'worker' => $this->worker ? [
        'id' => $this->worker->id,
        'full_name' => $this->worker->nombre_completo,
        'vat' => $this->worker->vat,
        'sueldo' => (float) ($this->worker->sueldo ?? 0),
      ] : null,

      'period' => $this->period ? [
        'id' => $this->period->id,
        'code' => $this->period->code,
        'name' => $this->period->name,
      ] : null,

      'company' => $this->company ? [
        'id' => $this->company->id,
        'name' => $this->company->name,
      ] : null,

      'sede' => $this->sede ? [
        'id' => $this->sede->id,
        'name' => $this->sede->abreviatura,
      ] : null,

      'calculated_by' => $this->calculatedByUser ? [
        'id' => $this->calculatedByUser->id,
        'name' => $this->calculatedByUser->name,
      ] : null,

      'approved_by' => $this->approvedByUser ? [
        'id' => $this->approvedByUser->id,
        'name' => $this->approvedByUser->name,
      ] : null,

      'details' => $this->whenLoaded('details', function () {
        return PayrollCalculationDetailResource::collection($this->details);
      }),

      'earnings' => $this->whenLoaded('earnings', function () {
        return PayrollCalculationDetailResource::collection($this->earnings);
      }),

      'deductions' => $this->whenLoaded('deductions', function () {
        return PayrollCalculationDetailResource::collection($this->deductions);
      }),

      'employer_contributions' => $this->whenLoaded('employerContributions', function () {
        return PayrollCalculationDetailResource::collection($this->employerContributions);
      }),

      'created_at' => $this->created_at,
      'updated_at' => $this->updated_at,
    ];
  }
}
