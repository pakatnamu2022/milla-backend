<?php

namespace Database\Seeders\gp\viaticos;

use App\Models\gp\gestionhumana\viaticos\ExpenseType;
use App\Models\gp\gestionhumana\viaticos\PerDiemCategory;
use App\Models\gp\gestionhumana\viaticos\PerDiemPolicy;
use App\Models\gp\gestionhumana\viaticos\PerDiemRate;
use App\Models\gp\gestionsistema\District;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PerDiemRatesSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run()
  {
    $currentPolicy = PerDiemPolicy::current()->first();
    $managers = PerDiemCategory::where('name', 'Managers')->first();
    $others = PerDiemCategory::where('name', 'Other Employees')->first();

    $meals = ExpenseType::where('code', 'meals')->first();
    $accommodation = ExpenseType::where('code', 'accommodation')->first();
    $localTransport = ExpenseType::where('code', 'local_transport')->first();

    // Helper para encontrar distritos
    $getDistrict = function ($districtName, $provinceName, $departmentName) {
      return District::whereHas('province', function ($q) use ($provinceName, $departmentName) {
        $q->where('name', $provinceName)
          ->whereHas('department', function ($dq) use ($departmentName) {
            $dq->where('name', $departmentName);
          });
      })->where('name', $districtName)->firstOrFail();
    };

    // Obtener todos los distritos
    $sanMiguel = $getDistrict('San Miguel', 'Lima', 'Lima');
    $sanIsidro = $getDistrict('San Isidro', 'Lima', 'Lima');
    $miraflores = $getDistrict('Miraflores', 'Lima', 'Lima');
    $piura = $getDistrict('Piura', 'Piura', 'Piura');
    $chiclayo = $getDistrict('Chiclayo', 'Chiclayo', 'Lambayeque');
    $cajamarca = $getDistrict('Cajamarca', 'Cajamarca', 'Cajamarca');
    $jaen = $getDistrict('Jaen', 'Jaen', 'Cajamarca');
    $trujillo = $getDistrict('Trujillo', 'Trujillo', 'La Libertad');
    $chimbote = $getDistrict('Chimbote', 'Santa', 'Ancash');
    $pacasmayo = $getDistrict('Pacasmayo', 'Pacasmayo', 'La Libertad');

    $rates = [
      // LIMA - SAN MIGUEL
      // Managers
      ['district' => $sanMiguel, 'category' => $managers, 'type' => $meals, 'amount' => 70],
      ['district' => $sanMiguel, 'category' => $managers, 'type' => $accommodation, 'amount' => 150],
      ['district' => $sanMiguel, 'category' => $managers, 'type' => $localTransport, 'amount' => 200],
      // Other Employees
      ['district' => $sanMiguel, 'category' => $others, 'type' => $meals, 'amount' => 70],
      ['district' => $sanMiguel, 'category' => $others, 'type' => $accommodation, 'amount' => 150],
      ['district' => $sanMiguel, 'category' => $others, 'type' => $localTransport, 'amount' => 50],

      // LIMA - SAN ISIDRO
      // Managers
      ['district' => $sanIsidro, 'category' => $managers, 'type' => $meals, 'amount' => 70],
      ['district' => $sanIsidro, 'category' => $managers, 'type' => $accommodation, 'amount' => 180],
      ['district' => $sanIsidro, 'category' => $managers, 'type' => $localTransport, 'amount' => 200],
      // Other Employees
      ['district' => $sanIsidro, 'category' => $others, 'type' => $meals, 'amount' => 70],
      ['district' => $sanIsidro, 'category' => $others, 'type' => $accommodation, 'amount' => 180],
      ['district' => $sanIsidro, 'category' => $others, 'type' => $localTransport, 'amount' => 50],

      // LIMA - MIRAFLORES
      // Managers
      ['district' => $miraflores, 'category' => $managers, 'type' => $meals, 'amount' => 70],
      ['district' => $miraflores, 'category' => $managers, 'type' => $accommodation, 'amount' => 210],
      ['district' => $miraflores, 'category' => $managers, 'type' => $localTransport, 'amount' => 200],
      // Other Employees
      ['district' => $miraflores, 'category' => $others, 'type' => $meals, 'amount' => 70],
      ['district' => $miraflores, 'category' => $others, 'type' => $accommodation, 'amount' => 210],
      ['district' => $miraflores, 'category' => $others, 'type' => $localTransport, 'amount' => 50],

      // PIURA
      // Managers
      ['district' => $piura, 'category' => $managers, 'type' => $meals, 'amount' => 70],
      ['district' => $piura, 'category' => $managers, 'type' => $accommodation, 'amount' => 170],
      ['district' => $piura, 'category' => $managers, 'type' => $localTransport, 'amount' => 40],
      // Other Employees
      ['district' => $piura, 'category' => $others, 'type' => $meals, 'amount' => 70],
      ['district' => $piura, 'category' => $others, 'type' => $accommodation, 'amount' => 95],
      ['district' => $piura, 'category' => $others, 'type' => $localTransport, 'amount' => 40],

      // CHICLAYO
      // Managers
      ['district' => $chiclayo, 'category' => $managers, 'type' => $meals, 'amount' => 70],
      ['district' => $chiclayo, 'category' => $managers, 'type' => $accommodation, 'amount' => 130],
      ['district' => $chiclayo, 'category' => $managers, 'type' => $localTransport, 'amount' => 40],
      // Other Employees
      ['district' => $chiclayo, 'category' => $others, 'type' => $meals, 'amount' => 70],
      ['district' => $chiclayo, 'category' => $others, 'type' => $accommodation, 'amount' => 130],
      ['district' => $chiclayo, 'category' => $others, 'type' => $localTransport, 'amount' => 40],

      // CAJAMARCA
      // Managers
      ['district' => $cajamarca, 'category' => $managers, 'type' => $meals, 'amount' => 70],
      ['district' => $cajamarca, 'category' => $managers, 'type' => $accommodation, 'amount' => 110],
      ['district' => $cajamarca, 'category' => $managers, 'type' => $localTransport, 'amount' => 40],
      // Other Employees
      ['district' => $cajamarca, 'category' => $others, 'type' => $meals, 'amount' => 70],
      ['district' => $cajamarca, 'category' => $others, 'type' => $accommodation, 'amount' => 100],
      ['district' => $cajamarca, 'category' => $others, 'type' => $localTransport, 'amount' => 40],

      // JAEN
      // Managers
      ['district' => $jaen, 'category' => $managers, 'type' => $meals, 'amount' => 70],
      ['district' => $jaen, 'category' => $managers, 'type' => $accommodation, 'amount' => 140],
      ['district' => $jaen, 'category' => $managers, 'type' => $localTransport, 'amount' => 40],
      // Other Employees
      ['district' => $jaen, 'category' => $others, 'type' => $meals, 'amount' => 70],
      ['district' => $jaen, 'category' => $others, 'type' => $accommodation, 'amount' => 90],
      ['district' => $jaen, 'category' => $others, 'type' => $localTransport, 'amount' => 40],

      // TRUJILLO
      // Managers
      ['district' => $trujillo, 'category' => $managers, 'type' => $meals, 'amount' => 70],
      ['district' => $trujillo, 'category' => $managers, 'type' => $accommodation, 'amount' => 150],
      ['district' => $trujillo, 'category' => $managers, 'type' => $localTransport, 'amount' => 40],
      // Other Employees
      ['district' => $trujillo, 'category' => $others, 'type' => $meals, 'amount' => 70],
      ['district' => $trujillo, 'category' => $others, 'type' => $accommodation, 'amount' => 110],
      ['district' => $trujillo, 'category' => $others, 'type' => $localTransport, 'amount' => 40],

      // CHIMBOTE
      // Managers
      ['district' => $chimbote, 'category' => $managers, 'type' => $meals, 'amount' => 70],
      ['district' => $chimbote, 'category' => $managers, 'type' => $accommodation, 'amount' => 130],
      ['district' => $chimbote, 'category' => $managers, 'type' => $localTransport, 'amount' => 40],
      // Other Employees
      ['district' => $chimbote, 'category' => $others, 'type' => $meals, 'amount' => 70],
      ['district' => $chimbote, 'category' => $others, 'type' => $accommodation, 'amount' => 90],
      ['district' => $chimbote, 'category' => $others, 'type' => $localTransport, 'amount' => 40],

      // PACASMAYO
      // Managers
      ['district' => $pacasmayo, 'category' => $managers, 'type' => $meals, 'amount' => 70],
      ['district' => $pacasmayo, 'category' => $managers, 'type' => $accommodation, 'amount' => 140],
      ['district' => $pacasmayo, 'category' => $managers, 'type' => $localTransport, 'amount' => 40],
      // Other Employees
      ['district' => $pacasmayo, 'category' => $others, 'type' => $meals, 'amount' => 70],
      ['district' => $pacasmayo, 'category' => $others, 'type' => $accommodation, 'amount' => 140],
      ['district' => $pacasmayo, 'category' => $others, 'type' => $localTransport, 'amount' => 40],
    ];

    foreach ($rates as $rate) {
      PerDiemRate::create([
        'per_diem_policy_id' => $currentPolicy->id,
        'district_id' => $rate['district']->id,
        'per_diem_category_id' => $rate['category']->id,
        'expense_type_id' => $rate['type']->id,
        'daily_amount' => $rate['amount'],
        'active' => true,
      ]);
    }
  }
}
