<?php

namespace App\Http\Controllers\gp\gestionhumana\viaticos;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\viaticos\IndexHotelReservationRequest;
use App\Http\Requests\gp\gestionhumana\viaticos\StoreHotelReservationRequest;
use App\Http\Requests\gp\gestionhumana\viaticos\UpdateHotelReservationRequest;
use App\Http\Requests\gp\gestionhumana\viaticos\MarkAttendedHotelReservationRequest;
use App\Http\Resources\gp\gestionhumana\viaticos\HotelReservationResource;
use App\Http\Services\gp\gestionhumana\viaticos\HotelReservationService;
use Illuminate\Support\Facades\Log;
use Throwable;

class HotelReservationController extends Controller
{
  protected HotelReservationService $service;

  public function __construct(HotelReservationService $service)
  {
    $this->service = $service;
  }

  /**
   * Display a listing of hotel reservations
   */
  public function index(IndexHotelReservationRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  /**
   * Store a newly created hotel reservation
   */
  public function store(int $requestId, StoreHotelReservationRequest $request)
  {
    try {
      $data = $request->all();
      $data['per_diem_request_id'] = $requestId;
      return $this->success($this->service->store($data));
    } catch (Throwable $th) {
      Log::error($th);
      return $this->error($th->getMessage());
    }
  }

  /**
   * Update the specified hotel reservation
   */
  public function update(int $reservationId, UpdateHotelReservationRequest $request)
  {
    try {
      $data = $request->all();
      $file = $request->file('receipt_file');
      if ($file) {
        $data['receipt_file'] = $file;
      }
      $data['id'] = $reservationId;
      return $this->success($this->service->update($data));
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
      return $this->service->destroy($reservationId);
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
      $data = $request->all();
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
