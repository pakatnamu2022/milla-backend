<?php

namespace App\Http\Controllers\Api\gp\gestionhumana\viaticos;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\viaticos\StoreHotelReservationRequest;
use App\Http\Resources\gp\gestionhumana\viaticos\HotelReservationResource;
use App\Services\gp\gestionhumana\viaticos\HotelReservationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class HotelReservationController extends Controller
{
    protected HotelReservationService $service;

    public function __construct(HotelReservationService $service)
    {
        $this->service = $service;
    }

    /**
     * Create hotel reservation for a per diem request
     */
    public function store(StoreHotelReservationRequest $request, string $requestId): JsonResponse
    {
        try {
            $reservation = $this->service->create((int) $requestId, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Reserva de hotel creada exitosamente',
                'data' => new HotelReservationResource($reservation),
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear reserva de hotel',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update hotel reservation
     */
    public function update(StoreHotelReservationRequest $request, string $reservationId): JsonResponse
    {
        try {
            $reservation = $this->service->update((int) $reservationId, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Reserva de hotel actualizada exitosamente',
                'data' => new HotelReservationResource($reservation),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar reserva de hotel',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete hotel reservation
     */
    public function destroy(string $reservationId): JsonResponse
    {
        try {
            $this->service->delete((int) $reservationId);

            return response()->json([
                'success' => true,
                'message' => 'Reserva de hotel eliminada exitosamente',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar reserva de hotel',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark reservation as attended/not attended
     */
    public function markAttended(Request $request, string $reservationId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'attended' => 'required|boolean',
                'penalty_amount' => 'nullable|numeric|min:0',
            ]);

            $reservation = $this->service->markAsAttended(
                (int) $reservationId,
                $validated['attended'],
                $validated['penalty_amount'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Estado de asistencia actualizado exitosamente',
                'data' => new HotelReservationResource($reservation),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar estado de asistencia',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
