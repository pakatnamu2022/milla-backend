<?php

namespace App\Http\Resources\gp\gestionhumana\viaticos;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class PerDiemRequestResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @return array<string, mixed>
   */
  public function toArray(Request $request): array
  {
    $daysWithoutSettlement = null;
    if ($this->status === 'in_progress' && $this->paid && $this->payment_date) {
      $daysWithoutSettlement = Carbon::parse($this->payment_date)->diffInDays(now());
    }

    return [
      'id' => $this->id,
      'code' => $this->code,
      'status' => $this->status,
      'start_date' => $this->start_date,
      'end_date' => $this->end_date,
      'days_count' => $this->days_count,
      'purpose' => $this->purpose,
      'final_result' => $this->final_result,
      'destination' => $this->destination,
      'cost_center' => $this->cost_center,
      'total_budget' => (float)$this->total_budget,
      'cash_amount' => (float)$this->cash_amount,
      'transfer_amount' => (float)$this->transfer_amount,
      'paid' => (bool)$this->paid,
      'payment_date' => $this->payment_date,
      'payment_method' => $this->payment_method,
      'settled' => (bool)$this->settled,
      'settlement_date' => $this->settlement_date,
      'total_spent' => (float)$this->total_spent,
      'balance_to_return' => (float)$this->balance_to_return,
      'notes' => $this->notes,
      'days_without_settlement' => $daysWithoutSettlement,

      // Relations
      'employee' => $this->employee?->nombre_completo,
      'company' => $this->company?->name,
      'category' => $this->category ? new PerDiemCategoryResource($this->category) : null,
      'policy' => $this->policy?->name,
      'approvals' => $this->approvals ? PerDiemApprovalResource::collection($this->approvals) : null,
      'hotel_reservation' => $this->hotelReservation ? new HotelReservationResource($this->hotelReservation) : null,
      'expenses_summary' => $this->when(isset($this->expenses_count), [
        'count' => $this->expenses_count ?? 0,
        'total' => $this->expenses_total ?? 0,
      ]),
      'expenses' => $this->expenses ? PerDiemExpenseResource::collection($this->expenses) : null,
      'budgets' => $this->budgets ? RequestBudgetResource::collection($this->budgets) : null,
    ];
  }
}
