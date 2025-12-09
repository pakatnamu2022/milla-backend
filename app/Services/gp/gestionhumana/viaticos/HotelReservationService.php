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

        // Business validation: check if request already has a reservation
        if ($request->hotelReservation()->exists()) {
            throw new Exception('La solicitud ya tiene una reserva de hotel.');
        }

        // Add request id to data
        $data['per_diem_request_id'] = $requestId;

        // Create reservation
        $reservation = HotelReservation::create($data);

        return $reservation->fresh(['hotelAgreement', 'request']);
    }

    /**
     * Update hotel reservation
     */
    public function update(int $reservationId, array $data): HotelReservation
    {
        $reservation = HotelReservation::findOrFail($reservationId);

        // Update reservation
        $reservation->update($data);

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
     * Mark reservation as attended
     */
    public function markAsAttended(int $reservationId, array $data): HotelReservation
    {
        $reservation = HotelReservation::findOrFail($reservationId);

        // Update reservation
        $reservation->update($data);

        return $reservation->fresh(['hotelAgreement', 'request']);
    }
}
