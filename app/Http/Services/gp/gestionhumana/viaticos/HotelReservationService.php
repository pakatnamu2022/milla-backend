<?php

namespace App\Http\Services\gp\gestionhumana\viaticos;

use App\Http\Resources\gp\gestionhumana\viaticos\HotelReservationResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Http\Services\common\EmailService;
use App\Http\Services\gp\gestionsistema\DigitalFileService;
use App\Models\gp\gestionhumana\viaticos\ExpenseType;
use App\Models\gp\gestionhumana\viaticos\HotelReservation;
use App\Models\gp\gestionhumana\viaticos\PerDiemExpense;
use App\Models\gp\gestionhumana\viaticos\PerDiemRate;
use App\Models\gp\gestionhumana\viaticos\PerDiemRequest;
use App\Models\gp\gestionsistema\DigitalFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Carbon\Carbon;
use Exception;

class HotelReservationService extends BaseService implements BaseServiceInterface
{
  protected DigitalFileService $digitalFileService;
  protected EmailService $emailService;

  // Configuración de rutas para archivos
  private const array FILE_PATHS = [
    'receipt_file' => '/gh/viaticos/reservaciones/',
  ];

  public function __construct(DigitalFileService $digitalFileService, EmailService $emailService)
  {
    $this->digitalFileService = $digitalFileService;
    $this->emailService = $emailService;
  }

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
   * @throws Exception
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

      // Extraer archivo del array de datos
      $files = $this->extractFiles($data);

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

      // Subir archivo y actualizar URL
      if (!empty($files)) {
        $this->uploadAndAttachFiles($reservation, $files);
      }

      // Confirm request: change status to in_progress and regenerate budgets if status is approved
      if ($perDiemRequest->status === 'approved') {
        // Change status to 'in_progress'
        $perDiemRequest->update(['status' => 'in_progress']);

        // Delete existing budgets
        $perDiemRequest->budgets()->delete();

        // Get rates again
        $rates = PerDiemRate::getCurrentRatesByDistrict(
          $perDiemRequest->district_id,
          $perDiemRequest->per_diem_category_id
        );

        // Regenerate budgets with hotel consideration using PerDiemRequestService
        $perDiemRequestService = app(PerDiemRequestService::class);
        $totalBudget = $perDiemRequestService->regenerateBudgets($perDiemRequest, $rates);

        // Update total budget
        $perDiemRequest->update(['total_budget' => $totalBudget]);

        // Send hotel reservation email
        $this->sendHotelReservationEmail($reservation->fresh(['request.employee', 'request.district']));
      }

      // Create company expense for accommodation
      $this->createCompanyExpenseForReservation($reservation, $data['document_number'] ?? "");

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

      // Extraer archivo del array de datos
      $files = $this->extractFiles($data);

      // Calculate nights count if dates are updated
      if (isset($data['checkin_date']) || isset($data['checkout_date'])) {
        $checkinDate = Carbon::parse($data['checkin_date'] ?? $reservation->checkin_date);
        $checkoutDate = Carbon::parse($data['checkout_date'] ?? $reservation->checkout_date);
        $data['nights_count'] = $checkinDate->diffInDays($checkoutDate);
      }

      // Update the reservation
      $reservation->update($data);

      // Si hay nuevo archivo, subirlo y actualizar URL
      if (!empty($files)) {
        // Eliminar archivo anterior si existe
        $this->deleteAttachedFiles($reservation);

        // Subir nuevo archivo
        $this->uploadAndAttachFiles($reservation, $files);
      }

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
      // Eliminar archivos asociados si existen
      $this->deleteAttachedFiles($reservation);

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

      // Extraer archivo del array de datos
      $files = $this->extractFiles($data);

      // Update attendance information
      $updateData = [
        'attended' => true,
        'total_cost' => $data['total_cost'] ?? $reservation->total_cost,
        'penalty' => $data['penalty'] ?? 0,
        'notes' => $data['notes'] ?? $reservation->notes,
      ];

      $reservation->update($updateData);

      // Si hay archivo, subirlo y actualizar URL
      if (!empty($files)) {
        // Eliminar archivo anterior si existe
        $this->deleteAttachedFiles($reservation);

        // Subir nuevo archivo
        $this->uploadAndAttachFiles($reservation, $files);
      }

      // Create company expense for accommodation
      $this->createCompanyExpenseForReservation($reservation);

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

  /**
   * Extrae los archivos del array de datos
   */
  private function extractFiles(array &$data): array
  {
    $files = [];

    foreach (array_keys(self::FILE_PATHS) as $field) {
      if (isset($data[$field]) && $data[$field] instanceof UploadedFile) {
        $files[$field] = $data[$field];
        unset($data[$field]); // Remover del array para no guardarlo en la BD
      }
    }

    return $files;
  }

  /**
   * Sube archivos y actualiza el modelo con las URLs
   */
  private function uploadAndAttachFiles(HotelReservation $reservation, $files): void
  {
    foreach ($files as $field => $file) {
      $path = self::FILE_PATHS[$field];
      $model = $reservation->getTable();

      // Subir archivo usando DigitalFileService
      $digitalFile = $this->digitalFileService->store($file, $path, 'public', $model);

      // Actualizar el campo del reservation con la URL
      $reservation->receipt_path = $digitalFile->url;
    }

    $reservation->save();
  }

  /**
   * Elimina archivos asociados al modelo
   */
  private function deleteAttachedFiles(HotelReservation $reservation): void
  {
    if ($reservation->receipt_path) {
      // Buscar el archivo digital asociado y eliminarlo
      $digitalFile = DigitalFile::where('url', $reservation->receipt_path)->first();

      if ($digitalFile) {
        $this->digitalFileService->destroy($digitalFile->id);
      }
    }
  }

  /**
   * Create a company expense for the hotel reservation
   */
  private function createCompanyExpenseForReservation(HotelReservation $reservation, string $document_number = ""): void
  {
    // Check if expense already exists for this reservation
    $existingExpense = PerDiemExpense::where('hotel_reservation_id', $reservation->id)->first();

    if ($existingExpense) {
      // Update existing expense
      $existingExpense->update([
        'receipt_amount' => $reservation->total_cost,
        'company_amount' => $reservation->total_cost,
        'employee_amount' => 0,
        'expense_date' => $reservation->checkin_date,
        'receipt_path' => $reservation->receipt_path,
        'notes' => "Reserva de hotel: {$reservation->hotel_name} ({$reservation->nights_count} noches)",
      ]);
    } else {
      // Create new company expense
      PerDiemExpense::create([
        'per_diem_request_id' => $reservation->per_diem_request_id,
        'hotel_reservation_id' => $reservation->id,
        'expense_type_id' => ExpenseType::ACCOMMODATION_ID,
        'expense_date' => $reservation->checkin_date,
        'receipt_amount' => $reservation->total_cost,
        'company_amount' => $reservation->total_cost,
        'employee_amount' => 0,
        'receipt_type' => 'invoice',
        'receipt_number' => strtoupper($document_number),
        'business_name' => strtoupper($reservation->hotel_name),
        'receipt_path' => $reservation->receipt_path,
        'notes' => "Reserva de hotel: {$reservation->hotel_name} ({$reservation->nights_count} noches)",
        'is_company_expense' => true,
        'validated' => true,
        'validated_by' => auth()->user()->partner_id,
        'validated_at' => now(),
      ]);
    }
  }

  /**
   * Send email notification when a hotel reservation is created
   */
  private function sendHotelReservationEmail(HotelReservation $reservation): void
  {
    try {
      $request = $reservation->request;

      $emailData = [
        'employee_name' => $request->employee->nombre_completo,
        'request_code' => $request->code,
        'hotel_name' => $reservation->hotel_name,
        'address' => $reservation->address,
        'checkin_date' => $reservation->checkin_date->format('d/m/Y'),
        'checkout_date' => $reservation->checkout_date->format('d/m/Y'),
        'nights_count' => $reservation->nights_count,
        'total_cost' => $reservation->total_cost,
      ];

      $this->emailService->send([
        'to' => [$request->employee->email2],
        'subject' => 'Reserva de Hotel Confirmada - ' . $request->code,
        'template' => 'emails.per-diem-hotel-reserved',
        'data' => $emailData,
      ]);
    } catch (Exception $e) {
      \Log::error('Error sending hotel reservation email: ' . $e->getMessage());
    }
  }
}
