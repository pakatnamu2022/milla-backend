<?php

namespace App\Http\Controllers\gp\gestionhumana\viaticos;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\viaticos\StoreHotelReservationRequest;
use App\Http\Requests\gp\gestionhumana\viaticos\UpdateHotelReservationRequest;
use App\Http\Resources\gp\gestionhumana\viaticos\HotelReservationResource;
use App\Services\gp\gestionhumana\viaticos\HotelReservationService;
use Illuminate\Http\Request;

class HotelReservationController extends Controller
{
  protected $service;

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

      return response()->json([
        'success' => true,
        'data' => new HotelReservationResource($reservation),
        'message' => 'Reserva de hotel creada exitosamente'
      ], 201);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => $e->getMessage()
      ], 400);
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

      return response()->json([
        'success' => true,
        'data' => new HotelReservationResource($reservation),
        'message' => 'Reserva de hotel actualizada exitosamente'
      ], 200);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => $e->getMessage()
      ], 400);
    }
  }

  /**
   * Remove the specified hotel reservation
   */
  public function destroy(int $reservationId)
  {
    try {
      $this->service->delete($reservationId);

      return response()->json([
        'success' => true,
        'message' => 'Reserva de hotel eliminada exitosamente'
      ], 200);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => $e->getMessage()
      ], 400);
    }
  }

  /**
   * Mark reservation as attended
   */
  public function markAttended(int $reservationId, Request $request)
  {
    try {
      $data = [
        'attended' => $request->input('attended', true),
        'penalty' => $request->input('penalty')
      ];

      $reservation = $this->service->markAsAttended($reservationId, $data);

      return response()->json([
        'success' => true,
        'data' => new HotelReservationResource($reservation),
        'message' => 'Reserva actualizada exitosamente'
      ], 200);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => $e->getMessage()
      ], 400);
    }
  }
}
