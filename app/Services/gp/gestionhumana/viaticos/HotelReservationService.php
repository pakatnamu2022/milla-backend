<?php

namespace App\Services\gp\gestionhumana\viaticos;

use App\Models\gp\gestionhumana\viaticos\HotelReservation;
use App\Models\gp\gestionhumana\viaticos\PerDiemRequest;
use Carbon\Carbon;
use Exception;

class HotelReservationService
{
    /**
     * Create hotel reservation for a per diem request
     */
    public function create(int $requestId, array $data): HotelReservation
    {
        $request = PerDiemRequest::findOrFail($requestId);

        // Check if request already has a reservation
        if ($request->hotelReservation()->exists()) {
            throw new Exception('Request already has a hotel reservation.');
        }

        // Calculate nights count
        $checkinDate = Carbon::parse($data['checkin_date']);
        $checkoutDate = Carbon::parse($data['checkout_date']);
        $nightsCount = $checkinDate->diffInDays($checkoutDate);

        $reservation = HotelReservation::create([
            'per_diem_request_id' => $requestId,
            'hotel_agreement_id' => $data['hotel_agreement_id'] ?? null,
            'hotel_name' => $data['hotel_name'],
            'address' => $data['address'],
            'phone' => $data['phone'] ?? null,
            'checkin_date' => $data['checkin_date'],
            'checkout_date' => $data['checkout_date'],
            'nights_count' => $nightsCount,
            'total_cost' => $data['total_cost'],
            'receipt_path' => $data['receipt_path'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        return $reservation->fresh(['hotelAgreement', 'request']);
    }

    /**
     * Update hotel reservation
     */
    public function update(int $reservationId, array $data): HotelReservation
    {
        $reservation = HotelReservation::findOrFail($reservationId);

        // Calculate nights count if dates are updated
        $nightsCount = $reservation->nights_count;
        if (isset($data['checkin_date']) || isset($data['checkout_date'])) {
            $checkinDate = Carbon::parse($data['checkin_date'] ?? $reservation->checkin_date);
            $checkoutDate = Carbon::parse($data['checkout_date'] ?? $reservation->checkout_date);
            $nightsCount = $checkinDate->diffInDays($checkoutDate);
        }

        $reservation->update([
            'hotel_agreement_id' => $data['hotel_agreement_id'] ?? $reservation->hotel_agreement_id,
            'hotel_name' => $data['hotel_name'] ?? $reservation->hotel_name,
            'address' => $data['address'] ?? $reservation->address,
            'phone' => $data['phone'] ?? $reservation->phone,
            'checkin_date' => $data['checkin_date'] ?? $reservation->checkin_date,
            'checkout_date' => $data['checkout_date'] ?? $reservation->checkout_date,
            'nights_count' => $nightsCount,
            'total_cost' => $data['total_cost'] ?? $reservation->total_cost,
            'receipt_path' => $data['receipt_path'] ?? $reservation->receipt_path,
            'notes' => $data['notes'] ?? $reservation->notes,
        ]);

        return $reservation->fresh(['hotelAgreement', 'request']);
    }

    /**
     * Delete hotel reservation
     */
    public function delete(int $reservationId): bool
    {
        $reservation = HotelReservation::findOrFail($reservationId);
        return $reservation->delete();
    }

    /**
     * Mark reservation as attended or not attended
     */
    public function markAsAttended(int $reservationId, bool $attended, ?float $penaltyAmount = null): HotelReservation
    {
        $reservation = HotelReservation::findOrFail($reservationId);

        $updateData = [
            'attended' => $attended,
        ];

        // If not attended and penalty amount provided
        if (!$attended && $penaltyAmount !== null) {
            $updateData['penalty'] = $penaltyAmount;
        }

        $reservation->update($updateData);

        return $reservation->fresh(['hotelAgreement', 'request']);
    }

    /**
     * Apply penalty to reservation
     */
    public function applyPenalty(int $reservationId, float $penaltyAmount): HotelReservation
    {
        $reservation = HotelReservation::findOrFail($reservationId);

        $reservation->update([
            'penalty' => $penaltyAmount,
        ]);

        return $reservation->fresh(['hotelAgreement', 'request']);
    }
}
