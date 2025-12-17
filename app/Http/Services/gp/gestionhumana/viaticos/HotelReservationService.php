<?php

namespace App\Http\Services\gp\gestionhumana\viaticos;

use App\Http\Resources\gp\gestionhumana\viaticos\HotelReservationResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\gp\gestionhumana\viaticos\HotelReservation;
use App\Models\gp\gestionhumana\viaticos\PerDiemRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Exception;

class HotelReservationService extends BaseService implements BaseServiceInterface
{
  /**
   * Get all hotel reservations with filters and pagination
   */
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      HotelReservation::with(['request', 'hotelAgreement']),
      $request,
      HotelReservation::filters,
      HotelReservation::sorts,
      HotelReservationResource::class,
    );
  }

  /**
   * Find a hotel reservation by ID (internal method)
   */
  public function find($id)
  {
    $reservation = HotelReservation::where('id', $id)->first();
    if (!$reservation) {
      throw new Exception('Reserva de hotel no encontrada');
    }
    return $reservation;
  }

  /**
   * Show a hotel reservation by ID
   */
  public function show($id)
  {
    return new HotelReservationResource($this->find($id)->load(['request', 'hotelAgreement']));
  }

  /**
   * Create a new hotel reservation
   */
  public function store(mixed $data)
  {
    try {
      DB::beginTransaction();

      // Validate that the request exists
      $perDiemRequest = PerDiemRequest::findOrFail($data['per_diem_request_id']);

      // Check if request already has a reservation
      if ($perDiemRequest->hotelReservation()->exists()) {
        throw new Exception('La solicitud ya tiene una reserva de hotel asociada');
      }

      // Calculate nights count
      $checkinDate = Carbon::parse($data['checkin_date']);
      $checkoutDate = Carbon::parse($data['checkout_date']);
      $nightsCount = $checkinDate->diffInDays($checkoutDate);

      // Prepare reservation data
      $reservationData = [
        'per_diem_request_id' => $data['per_diem_request_id'],
        'hotel_agreement_id' => $data['hotel_agreement_id'] ?? null,
        'hotel_name' => $data['hotel_name'],
        'address' => $data['address'] ?? null,
        'phone' => $data['phone'] ?? null,
        'checkin_date' => $data['checkin_date'],
        'checkout_date' => $data['checkout_date'],
        'nights_count' => $nightsCount,
        'total_cost' => $data['total_cost'] ?? 0,
        'notes' => $data['notes'] ?? null,
        'attended' => false,
      ];

      // Create the reservation
      $reservation = HotelReservation::create($reservationData);

      DB::commit();
      return new HotelReservationResource($reservation->fresh(['request', 'hotelAgreement']));
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Update a hotel reservation
   */
  public function update(mixed $data)
  {
    try {
      DB::beginTransaction();

      $reservation = $this->find($data['id']);

      // Only allow updates if not attended or if explicitly allowed
      if ($reservation->attended && !isset($data['force_update'])) {
        throw new Exception('No se puede actualizar una reserva que ya fue atendida');
      }

      // Calculate nights count if dates are updated
      if (isset($data['checkin_date']) || isset($data['checkout_date'])) {
        $checkinDate = Carbon::parse($data['checkin_date'] ?? $reservation->checkin_date);
        $checkoutDate = Carbon::parse($data['checkout_date'] ?? $reservation->checkout_date);
        $data['nights_count'] = $checkinDate->diffInDays($checkoutDate);
      }

      // Update the reservation
      $reservation->update($data);

      DB::commit();
      return new HotelReservationResource($reservation->fresh(['request', 'hotelAgreement']));
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Delete a hotel reservation
   */
  public function destroy($id)
  {
    $reservation = $this->find($id);

    // Only allow deletion if not attended
    if ($reservation->attended) {
      throw new Exception('No se puede eliminar una reserva que ya fue atendida');
    }

    DB::transaction(function () use ($reservation) {
      $reservation->delete();
    });

    return response()->json(['message' => 'Reserva de hotel eliminada correctamente']);
  }

  /**
   * Mark a reservation as attended
   */
  public function markAsAttended(int $reservationId, array $data): HotelReservation
  {
    try {
      DB::beginTransaction();

      $reservation = $this->find($reservationId);

      // Validate that reservation is not already attended
      if ($reservation->attended) {
        throw new Exception('Esta reserva ya fue marcada como atendida');
      }

      // Update attendance information
      $updateData = [
        'attended' => true,
        'total_cost' => $data['total_cost'] ?? $reservation->total_cost,
        'penalty' => $data['penalty'] ?? 0,
        'receipt_path' => $data['receipt_path'] ?? null,
        'notes' => $data['notes'] ?? $reservation->notes,
      ];

      $reservation->update($updateData);

      DB::commit();
      return $reservation->fresh(['request', 'hotelAgreement']);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Get reservations by per diem request
   */
  public function getByRequest(int $requestId)
  {
    return HotelReservation::with(['hotelAgreement'])
      ->where('per_diem_request_id', $requestId)
      ->first();
  }

  /**
   * Get reservations with penalties
   */
  public function getWithPenalties()
  {
    return HotelReservation::with(['request.employee', 'hotelAgreement'])
      ->withPenalty()
      ->orderBy('checkout_date', 'desc')
      ->get();
  }

  /**
   * Get reservations by date range
   */
  public function getByDateRange(string $startDate, string $endDate)
  {
    return HotelReservation::with(['request.employee', 'hotelAgreement'])
      ->whereBetween('checkin_date', [$startDate, $endDate])
      ->orderBy('checkin_date', 'asc')
      ->get();
  }
}
