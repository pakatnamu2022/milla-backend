<?php

namespace Database\Seeders\gp\viaticos;

use App\Models\gp\gestionhumana\viaticos\ExpenseType;
use App\Models\gp\gestionhumana\viaticos\PerDiemExpense;
use App\Models\gp\gestionhumana\viaticos\PerDiemRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PerDiemExpenseSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    $accommodation = ExpenseType::where('code', 'accommodation')->first();
    $meals = ExpenseType::where('code', 'meals')->first();
    $breakfast = ExpenseType::where('code', 'breakfast')->first();
    $lunch = ExpenseType::where('code', 'lunch')->first();
    $dinner = ExpenseType::where('code', 'dinner')->first();
    $localTransport = ExpenseType::where('code', 'local_transport')->first();
    $transportation = ExpenseType::where('code', 'transportation')->first();

    $validator = User::first();

    // Obtener requests que están in_progress o pending_settlement (pueden registrar gastos)
    $requests = PerDiemRequest::whereIn('status', ['in_progress', 'pending_settlement'])->get();

    foreach ($requests as $request) {
      // Generar entre 5 y 15 gastos por request
      $numExpenses = rand(5, 15);
      $dayRange = $request->start_date->diffInDays($request->end_date);

      for ($i = 0; $i < $numExpenses; $i++) {
        // Fecha aleatoria dentro del rango del viaje
        $dayOffset = $dayRange > 0 ? rand(0, $dayRange) : 0;
        $expenseDate = $request->start_date->copy()->addDays($dayOffset);

        // Tipo de gasto aleatorio
        $expenseTypes = [
          $accommodation,
          $meals,
          $breakfast,
          $lunch,
          $dinner,
          $localTransport,
          $transportation,
        ];
        $expenseType = $expenseTypes[array_rand($expenseTypes)];

        // Generar montos según tipo
        $receiptAmount = 0;
        $concept = '';
        $receiptType = 'invoice';

        switch ($expenseType->code) {
          case 'accommodation':
            $receiptAmount = rand(100, 200);
            $concept = 'Hospedaje - Hotel ' . $request->destination;
            $receiptType = 'invoice';
            break;

          case 'meals':
            $receiptAmount = rand(50, 100);
            $concept = 'Alimentación completa';
            $receiptType = 'invoice';
            break;

          case 'breakfast':
            $receiptAmount = rand(15, 30);
            $concept = 'Desayuno';
            $receiptType = 'receipt';
            break;

          case 'lunch':
            $receiptAmount = rand(25, 50);
            $concept = 'Almuerzo';
            $receiptType = 'receipt';
            break;

          case 'dinner':
            $receiptAmount = rand(30, 60);
            $concept = 'Cena';
            $receiptType = 'receipt';
            break;

          case 'local_transport':
            $receiptAmount = rand(10, 40);
            $concept = 'Taxi/Uber dentro de ' . $request->destination;
            $receiptType = rand(1, 10) > 3 ? 'receipt' : 'no_receipt'; // 70% con recibo
            break;

          case 'transportation':
            $receiptAmount = rand(150, 300);
            $concept = 'Pasaje a/desde ' . $request->destination;
            $receiptType = 'invoice';
            break;
        }

        // Determinar si el gasto es de la empresa o del empleado
        // 80% gastos de empresa, 20% del empleado
        $isCompanyExpense = rand(1, 10) <= 8;
        $companyAmount = $isCompanyExpense ? $receiptAmount : 0;
        $employeeAmount = $isCompanyExpense ? 0 : $receiptAmount;

        // 60% de gastos validados
        $validated = rand(1, 10) <= 6;
        $validatedBy = $validated ? $validator->id : null;
        $validatedAt = $validated ? Carbon::now()->subDays(rand(1, 5)) : null;

        // Generar número de comprobante
        $receiptNumber = $receiptType === 'no_receipt'
          ? null
          : ($receiptType === 'invoice' ? 'F001-' . str_pad($i + 1, 8, '0', STR_PAD_LEFT) : 'B001-' . str_pad($i + 1, 8, '0', STR_PAD_LEFT));

        $expenseData = [
          'per_diem_request_id' => $request->id,
          'expense_type_id' => $expenseType->id,
          'expense_date' => $expenseDate,
          'receipt_amount' => $receiptAmount,
          'company_amount' => $companyAmount,
          'employee_amount' => $employeeAmount,
          'receipt_type' => $receiptType,
          'receipt_number' => $receiptNumber,
          'receipt_path' => $receiptType !== 'no_receipt' ? 'receipts/' . $request->code . '_expense_' . ($i + 1) . '.pdf' : null,
          'notes' => $validated ? null : 'Pendiente de validación',
          'validated' => $validated,
          'validated_by' => $validatedBy,
          'validated_at' => $validatedAt,
        ];

        PerDiemExpense::create($expenseData);
      }

      // Actualizar el total_spent del request (solo gastos validados)
      $totalSpent = PerDiemExpense::where('per_diem_request_id', $request->id)
        ->where('validated', true)
        ->sum('receipt_amount');

      $balanceToReturn = $request->total_budget - $totalSpent;

      $request->update([
        'total_spent' => $totalSpent,
        'balance_to_return' => max(0, $balanceToReturn), // No puede ser negativo
      ]);
    }
  }
}
