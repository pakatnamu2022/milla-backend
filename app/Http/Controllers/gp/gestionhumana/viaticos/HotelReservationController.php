<?php

namespace App\Http\Controllers\gp\gestionhumana\viaticos;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\viaticos\StoreHotelReservationRequest;
use App\Http\Requests\gp\gestionhumana\viaticos\UpdateHotelReservationRequest;
use App\Http\Requests\gp\gestionhumana\viaticos\MarkAttendedHotelReservationRequest;
use App\Http\Resources\gp\gestionhumana\viaticos\HotelReservationResource;
use App\Http\Services\gp\gestionhumana\viaticos\HotelReservationService;
use Throwable;

class HotelReservationController extends Controller
{
  protected HotelReservationService $service;

  public function __construct(HotelReservationService $service)
  {
    $this->service = $service;
  }

  /**
   * Store a newly created hotel reservation
   */
  public function store(int $requestId, StoreHotelReservationRequest $request)
  {
    try {
      $data = $request->validated();
      $reservation = $this->service->create($requestId, $data);

      return $this->success([
        'data' => new HotelReservationResource($reservation),
        'message' => 'Reserva de hotel creada exitosamente'
      ]);
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Update the specified hotel reservation
   */
  public function update(int $reservationId, UpdateHotelReservationRequest $request)
  {
    try {
      $data = $request->validated();
      $reservation = $this->service->update($reservationId, $data);

      return $this->success([
        'data' => new HotelReservationResource($reservation),
        'message' => 'Reserva de hotel actualizada exitosamente'
      ]);
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Remove the specified hotel reservation
   */
  public function destroy(int $reservationId)
  {
    try {
      $this->service->delete($reservationId);

      return $this->success([
        'message' => 'Reserva de hotel eliminada exitosamente'
      ]);
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Mark reservation as attended
   */
  public function markAttended(int $reservationId, MarkAttendedHotelReservationRequest $request)
  {
    try {
      $data = $request->validated();
      $reservation = $this->service->markAsAttended($reservationId, $data);

      return $this->success([
        'data' => new HotelReservationResource($reservation),
        'message' => 'Reserva actualizada exitosamente'
      ]);
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
