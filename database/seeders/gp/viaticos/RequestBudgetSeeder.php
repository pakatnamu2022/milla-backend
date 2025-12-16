<?php

namespace Database\Seeders\gp\viaticos;

use App\Models\gp\gestionhumana\viaticos\ExpenseType;
use App\Models\gp\gestionhumana\viaticos\PerDiemRequest;
use App\Models\gp\gestionhumana\viaticos\RequestBudget;
use Illuminate\Database\Seeder;

class RequestBudgetSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    $accommodation = ExpenseType::where('code', 'accommodation')->first();
    $meals = ExpenseType::where('code', 'meals')->first();
    $localTransport = ExpenseType::where('code', 'local_transport')->first();
    $transportation = ExpenseType::where('code', 'transportation')->first();

    if (!$accommodation || !$meals || !$localTransport || !$transportation) {
      return;
    }

    // Obtener todas las solicitudes excepto draft (que no tienen presupuesto asignado)
    $requests = PerDiemRequest::whereNotIn('status', ['draft'])->get();

    foreach ($requests as $request) {
      $days = $request->days_count;
      $budgets = [];

      // Todos los requests tienen alojamiento, alimentación y transporte local mínimo
      switch ($request->destination) {
        case 'Lima':
          $budgets[] = [
            'expense_type_id' => $accommodation->id,
            'daily_amount' => $request->category->name === 'Gerentes' ? 180.00 : 150.00,
            'days' => $days,
          ];
          $budgets[] = [
            'expense_type_id' => $meals->id,
            'daily_amount' => 70.00,
            'days' => $days,
          ];
          $budgets[] = [
            'expense_type_id' => $localTransport->id,
            'daily_amount' => $request->category->name === 'Gerentes' ? 200.00 : 50.00,
            'days' => $days,
          ];
          break;

        case 'Arequipa':
          $budgets[] = [
            'expense_type_id' => $accommodation->id,
            'daily_amount' => $request->category->name === 'Gerentes' ? 160.00 : 110.00,
            'days' => $days,
          ];
          $budgets[] = [
            'expense_type_id' => $meals->id,
            'daily_amount' => 70.00,
            'days' => $days,
          ];
          $budgets[] = [
            'expense_type_id' => $localTransport->id,
            'daily_amount' => 40.00,
            'days' => $days,
          ];
          break;

        case 'Cusco':
          $budgets[] = [
            'expense_type_id' => $accommodation->id,
            'daily_amount' => $request->category->name === 'Gerentes' ? 180.00 : 130.00,
            'days' => $days,
          ];
          $budgets[] = [
            'expense_type_id' => $meals->id,
            'daily_amount' => 70.00,
            'days' => $days,
          ];
          $budgets[] = [
            'expense_type_id' => $localTransport->id,
            'daily_amount' => 40.00,
            'days' => $days,
          ];
          break;

        case 'Trujillo':
          $budgets[] = [
            'expense_type_id' => $accommodation->id,
            'daily_amount' => $request->category->name === 'Gerentes' ? 150.00 : 110.00,
            'days' => $days,
          ];
          $budgets[] = [
            'expense_type_id' => $meals->id,
            'daily_amount' => 70.00,
            'days' => $days,
          ];
          $budgets[] = [
            'expense_type_id' => $localTransport->id,
            'daily_amount' => 40.00,
            'days' => $days,
          ];
          break;

        case 'Piura':
          $budgets[] = [
            'expense_type_id' => $accommodation->id,
            'daily_amount' => $request->category->name === 'Gerentes' ? 170.00 : 95.00,
            'days' => $days,
          ];
          $budgets[] = [
            'expense_type_id' => $meals->id,
            'daily_amount' => 70.00,
            'days' => $days,
          ];
          $budgets[] = [
            'expense_type_id' => $localTransport->id,
            'daily_amount' => 40.00,
            'days' => $days,
          ];
          break;

        case 'Chiclayo':
          $budgets[] = [
            'expense_type_id' => $accommodation->id,
            'daily_amount' => 130.00,
            'days' => $days,
          ];
          $budgets[] = [
            'expense_type_id' => $meals->id,
            'daily_amount' => 70.00,
            'days' => $days,
          ];
          $budgets[] = [
            'expense_type_id' => $localTransport->id,
            'daily_amount' => 40.00,
            'days' => $days,
          ];
          break;

        case 'Cajamarca':
          $budgets[] = [
            'expense_type_id' => $accommodation->id,
            'daily_amount' => $request->category->name === 'Gerentes' ? 110.00 : 100.00,
            'days' => $days,
          ];
          $budgets[] = [
            'expense_type_id' => $meals->id,
            'daily_amount' => 70.00,
            'days' => $days,
          ];
          $budgets[] = [
            'expense_type_id' => $localTransport->id,
            'daily_amount' => 40.00,
            'days' => $days,
          ];
          break;

        default:
          // Default genérico
          $budgets[] = [
            'expense_type_id' => $accommodation->id,
            'daily_amount' => 120.00,
            'days' => $days,
          ];
          $budgets[] = [
            'expense_type_id' => $meals->id,
            'daily_amount' => 70.00,
            'days' => $days,
          ];
          $budgets[] = [
            'expense_type_id' => $localTransport->id,
            'daily_amount' => 40.00,
            'days' => $days,
          ];
          break;
      }

      // Agregar pasajes para viajes largos (más de 3 días) o gerentes
      if ($days > 3 || $request->category->name === 'Gerentes') {
        $budgets[] = [
          'expense_type_id' => $transportation->id,
          'daily_amount' => in_array($request->destination, ['Lima', 'Arequipa', 'Cusco']) ? 250.00 : 180.00,
          'days' => 1, // Los pasajes son por viaje, no por día
        ];
      }

      // Crear los presupuestos
      foreach ($budgets as $budgetData) {
        RequestBudget::firstOrCreate(
          [
            'per_diem_request_id' => $request->id,
            'expense_type_id' => $budgetData['expense_type_id'],
          ],
          [
            'daily_amount' => $budgetData['daily_amount'],
            'days' => $budgetData['days'],
            'total' => $budgetData['daily_amount'] * $budgetData['days'],
          ]
        );
      }

      // Actualizar el total_budget del request
      $totalBudget = RequestBudget::where('per_diem_request_id', $request->id)->sum('total');
      $request->update(['total_budget' => $totalBudget]);
    }
  }
}
