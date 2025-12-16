<?php

namespace Database\Seeders\gp\viaticos;

use App\Models\gp\gestionhumana\viaticos\PerDiemApproval;
use App\Models\gp\gestionhumana\viaticos\PerDiemRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PerDiemApprovalSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    $users = User::limit(5)->get();
    if ($users->count() < 3) {
      return;
    }

    $manager = $users[0];
    $hr = $users[1];
    $general = $users[2];

    // Obtener requests que necesitan aprobaciones (todos excepto draft)
    $requests = PerDiemRequest::whereNotIn('status', ['draft'])->get();

    foreach ($requests as $request) {
      $approvals = [];

      switch ($request->status) {
        case 'pending_manager':
          // Solo aprobación de manager pendiente
          $approvals[] = [
            'approver_id' => $manager->id,
            'approver_type' => 'manager',
            'status' => 'pending',
            'comments' => null,
            'approved_at' => null,
          ];
          break;

        case 'pending_hr':
          // Manager aprobado, HR pendiente
          $approvals[] = [
            'approver_id' => $manager->id,
            'approver_type' => 'manager',
            'status' => 'approved',
            'comments' => 'Aprobado. Procede con autorización de RRHH.',
            'approved_at' => Carbon::now()->subDays(2),
          ];
          $approvals[] = [
            'approver_id' => $hr->id,
            'approver_type' => 'hr',
            'status' => 'pending',
            'comments' => null,
            'approved_at' => null,
          ];
          break;

        case 'pending_general':
          // Manager y HR aprobados, General pendiente
          $approvals[] = [
            'approver_id' => $manager->id,
            'approver_type' => 'manager',
            'status' => 'approved',
            'comments' => 'Aprobado por área.',
            'approved_at' => Carbon::now()->subDays(4),
          ];
          $approvals[] = [
            'approver_id' => $hr->id,
            'approver_type' => 'hr',
            'status' => 'approved',
            'comments' => 'Verificado. Presupuesto disponible.',
            'approved_at' => Carbon::now()->subDays(3),
          ];
          $approvals[] = [
            'approver_id' => $general->id,
            'approver_type' => 'general_manager',
            'status' => 'pending',
            'comments' => null,
            'approved_at' => null,
          ];
          break;

        case 'approved':
        case 'in_progress':
        case 'pending_settlement':
          // Todos los niveles aprobados
          $approvals[] = [
            'approver_id' => $manager->id,
            'approver_type' => 'manager',
            'status' => 'approved',
            'comments' => 'Aprobado. Viaje justificado.',
            'approved_at' => Carbon::now()->subDays(7),
          ];
          $approvals[] = [
            'approver_id' => $hr->id,
            'approver_type' => 'hr',
            'status' => 'approved',
            'comments' => 'Conforme. Presupuesto disponible.',
            'approved_at' => Carbon::now()->subDays(6),
          ];
          $approvals[] = [
            'approver_id' => $general->id,
            'approver_type' => 'general_manager',
            'status' => 'approved',
            'comments' => 'Autorizado.',
            'approved_at' => Carbon::now()->subDays(5),
          ];
          break;

        case 'rejected':
          // Al menos una rechazada (rechazamos en el nivel de manager)
          $approvals[] = [
            'approver_id' => $manager->id,
            'approver_type' => 'manager',
            'status' => 'rejected',
            'comments' => 'Rechazado. Viaje no justificado o duplicado.',
            'approved_at' => Carbon::now()->subDays(2),
          ];
          break;
      }

      // Crear las aprobaciones
      foreach ($approvals as $approvalData) {
        PerDiemApproval::firstOrCreate(
          [
            'per_diem_request_id' => $request->id,
            'approver_type' => $approvalData['approver_type'],
          ],
          $approvalData
        );
      }
    }
  }
}
