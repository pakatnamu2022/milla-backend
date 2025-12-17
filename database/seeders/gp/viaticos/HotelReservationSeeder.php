<?php

namespace Database\Seeders\gp\viaticos;

use App\Models\gp\gestionhumana\viaticos\HotelAgreement;
use App\Models\gp\gestionhumana\viaticos\HotelReservation;
use App\Models\gp\gestionhumana\viaticos\PerDiemRequest;
use Illuminate\Database\Seeder;

class HotelReservationSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    // Obtener requests que están aprobados, in_progress o pending_settlement
    $requests = PerDiemRequest::whereIn('status', ['approved', 'in_progress', 'pending_settlement'])
      ->get();

    // Tomar solo 10 requests (50% de los aprobados aproximadamente)
    $selectedRequests = $requests->take(10);

    foreach ($selectedRequests as $request) {
      // Buscar un hotel agreement en la ciudad de destino
      $hotelAgreement = HotelAgreement::where('city', $request->destination)
        ->where('active', true)
        ->inRandomOrder()
        ->first();

      // Si no hay convenio, usar datos genéricos
      $hotelName = $hotelAgreement ? $hotelAgreement->name : 'Hotel ' . $request->destination;
      $address = $hotelAgreement ? $hotelAgreement->address : 'Av. Principal 123, ' . $request->destination;
      $phone = $hotelAgreement ? explode(' - ', $hotelAgreement->contact)[1] ?? '(01) 000-0000' : '(01) 000-0000';
      $corporateRate = $hotelAgreement ? $hotelAgreement->corporate_rate : 120.00;

      // Calcular noches (end_date - start_date)
      $nights = $request->start_date->diffInDays($request->end_date);
      $totalCost = $corporateRate * $nights;

      // 70% attended, 30% no attended
      $attended = rand(1, 10) <= 7;

      // 10% de los no attended tiene penalty
      $penalty = 0;
      if (!$attended && rand(1, 10) === 1) {
        $penalty = $corporateRate * 0.5; // 50% del costo por noche como penalidad
      }

      $reservationData = [
        'per_diem_request_id' => $request->id,
        'hotel_agreement_id' => $hotelAgreement?->id,
        'hotel_name' => $hotelName,
        'address' => $address,
        'phone' => $phone,
        'checkin_date' => $request->start_date,
        'checkout_date' => $request->end_date,
        'nights_count' => $nights,
        'total_cost' => $totalCost,
        'receipt_path' => $attended ? 'receipts/hotel_' . $request->code . '.pdf' : null,
        'notes' => $attended ? 'Reserva confirmada y atendida' : 'Reserva no utilizada',
        'attended' => $attended,
        'penalty' => $penalty,
      ];

      HotelReservation::firstOrCreate(
        ['per_diem_request_id' => $request->id],
        $reservationData
      );
    }
  }
}
