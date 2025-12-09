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
            'total_budget' => (float) $this->total_budget,
            'cash_amount' => (float) $this->cash_amount,
            'transfer_amount' => (float) $this->transfer_amount,
            'paid' => (bool) $this->paid,
            'payment_date' => $this->payment_date,
            'payment_method' => $this->payment_method,
            'settled' => (bool) $this->settled,
            'settlement_date' => $this->settlement_date,
            'total_spent' => (float) $this->total_spent,
            'balance_to_return' => (float) $this->balance_to_return,
            'notes' => $this->notes,
            'days_without_settlement' => $daysWithoutSettlement,

            // Relations
            'employee' => $this->whenLoaded('employee', function () {
                return [
                    'id' => $this->employee->id,
                    'name' => $this->employee->name ?? $this->employee->fullname ?? null,
                ];
            }),

            'company' => $this->whenLoaded('company', function () {
                return [
                    'id' => $this->company->id,
                    'name' => $this->company->name,
                ];
            }),

            'category' => $this->whenLoaded('category', function () {
                return new PerDiemCategoryResource($this->category);
            }),

            'policy' => $this->whenLoaded('policy', function () {
                return [
                    'id' => $this->policy->id,
                    'version' => $this->policy->version,
                    'name' => $this->policy->name,
                ];
            }),

            'budgets' => $this->whenLoaded('budgets', function () {
                return $this->budgets->map(function ($budget) {
                    return [
                        'id' => $budget->id,
                        'daily_amount' => (float) $budget->daily_amount,
                        'days' => $budget->days,
                        'total' => (float) $budget->total,
                        'expense_type' => $budget->expenseType ? [
                            'id' => $budget->expenseType->id,
                            'code' => $budget->expenseType->code,
                            'name' => $budget->expenseType->name,
                        ] : null,
                    ];
                });
            }),

            'approvals' => $this->whenLoaded('approvals', function () {
                return $this->approvals->map(function ($approval) {
                    return [
                        'id' => $approval->id,
                        'approver_type' => $approval->approver_type,
                        'status' => $approval->status,
                        'comments' => $approval->comments,
                        'approved_at' => $approval->approved_at,
                        'approver' => $approval->approver ? [
                            'id' => $approval->approver->id,
                            'name' => $approval->approver->name ?? $approval->approver->fullname ?? null,
                        ] : null,
                    ];
                });
            }),

            'hotel_reservation' => $this->whenLoaded('hotelReservation', function () {
                return $this->hotelReservation ? new HotelReservationResource($this->hotelReservation) : null;
            }),

            'expenses_summary' => $this->when(isset($this->expenses_count), [
                'count' => $this->expenses_count ?? 0,
                'total' => $this->expenses_total ?? 0,
            ]),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
