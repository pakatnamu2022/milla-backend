<?php

namespace Database\Seeders\gp\viaticos;

use App\Models\gp\gestionhumana\viaticos\ExpenseType;
use App\Models\gp\gestionhumana\viaticos\PerDiemCategory;
use App\Models\gp\gestionhumana\viaticos\PerDiemPolicy;
use App\Models\gp\gestionhumana\viaticos\PerDiemRate;
use App\Models\gp\gestionsistema\District;
use Illuminate\Database\Seeder;

class PerDiemRatesSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run()
  {
    $currentPolicy = PerDiemPolicy::current()->first();
    $managers = PerDiemCategory::where('name', 'Gerentes')->first();
    $others = PerDiemCategory::where('name', 'Colaboradores')->first();

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
    $tumbes = $getDistrict('Tumbes', 'Tumbes', 'Tumbes');
    $jaen = $getDistrict('Jaen', 'Jaen', 'Cajamarca');

    $rates = [
      // LIMA - SAN MIGUEL
      // Managers
      ['district' => $sanMiguel, 'category' => $managers, 'type' => $meals, 'amount' => 50],
      ['district' => $sanMiguel, 'category' => $managers, 'type' => $accommodation, 'amount' => 150],
      ['district' => $sanMiguel, 'category' => $managers, 'type' => $localTransport, 'amount' => 200],
      // Other Employees
      ['district' => $sanMiguel, 'category' => $others, 'type' => $meals, 'amount' => 45],
      ['district' => $sanMiguel, 'category' => $others, 'type' => $accommodation, 'amount' => 150],
      ['district' => $sanMiguel, 'category' => $others, 'type' => $localTransport, 'amount' => 50],

      // LIMA - SAN ISIDRO
      // Managers
      ['district' => $sanIsidro, 'category' => $managers, 'type' => $meals, 'amount' => 50],
      ['district' => $sanIsidro, 'category' => $managers, 'type' => $accommodation, 'amount' => 180],
      ['district' => $sanIsidro, 'category' => $managers, 'type' => $localTransport, 'amount' => 200],
      // Other Employees
      ['district' => $sanIsidro, 'category' => $others, 'type' => $meals, 'amount' => 45],
      ['district' => $sanIsidro, 'category' => $others, 'type' => $accommodation, 'amount' => 180],
      ['district' => $sanIsidro, 'category' => $others, 'type' => $localTransport, 'amount' => 50],

      // LIMA - MIRAFLORES
      // Managers
      ['district' => $miraflores, 'category' => $managers, 'type' => $meals, 'amount' => 50],
      ['district' => $miraflores, 'category' => $managers, 'type' => $accommodation, 'amount' => 210],
      ['district' => $miraflores, 'category' => $managers, 'type' => $localTransport, 'amount' => 200],
      // Other Employees
      ['district' => $miraflores, 'category' => $others, 'type' => $meals, 'amount' => 45],
      ['district' => $miraflores, 'category' => $others, 'type' => $accommodation, 'amount' => 210],
      ['district' => $miraflores, 'category' => $others, 'type' => $localTransport, 'amount' => 50],

      // PIURA
      // Managers
      ['district' => $piura, 'category' => $managers, 'type' => $meals, 'amount' => 50],
      ['district' => $piura, 'category' => $managers, 'type' => $accommodation, 'amount' => 170],
      ['district' => $piura, 'category' => $managers, 'type' => $localTransport, 'amount' => 40],
      // Other Employees
      ['district' => $piura, 'category' => $others, 'type' => $meals, 'amount' => 45],
      ['district' => $piura, 'category' => $others, 'type' => $accommodation, 'amount' => 95],
      ['district' => $piura, 'category' => $others, 'type' => $localTransport, 'amount' => 40],

      // CHICLAYO
      // Managers
      ['district' => $chiclayo, 'category' => $managers, 'type' => $meals, 'amount' => 50],
      ['district' => $chiclayo, 'category' => $managers, 'type' => $accommodation, 'amount' => 130],
      ['district' => $chiclayo, 'category' => $managers, 'type' => $localTransport, 'amount' => 40],
      // Other Employees
      ['district' => $chiclayo, 'category' => $others, 'type' => $meals, 'amount' => 45],
      ['district' => $chiclayo, 'category' => $others, 'type' => $accommodation, 'amount' => 130],
      ['district' => $chiclayo, 'category' => $others, 'type' => $localTransport, 'amount' => 40],

      // CAJAMARCA
      // Managers
      ['district' => $cajamarca, 'category' => $managers, 'type' => $meals, 'amount' => 50],
      ['district' => $cajamarca, 'category' => $managers, 'type' => $accommodation, 'amount' => 110],
      ['district' => $cajamarca, 'category' => $managers, 'type' => $localTransport, 'amount' => 40],
      // Other Employees
      ['district' => $cajamarca, 'category' => $others, 'type' => $meals, 'amount' => 45],
      ['district' => $cajamarca, 'category' => $others, 'type' => $accommodation, 'amount' => 110],
      ['district' => $cajamarca, 'category' => $others, 'type' => $localTransport, 'amount' => 40],

      // TUMBES
      // Managers
      ['district' => $tumbes, 'category' => $managers, 'type' => $meals, 'amount' => 50],
      ['district' => $tumbes, 'category' => $managers, 'type' => $accommodation, 'amount' => 130],
      ['district' => $tumbes, 'category' => $managers, 'type' => $localTransport, 'amount' => 40],
      // Other Employees
      ['district' => $tumbes, 'category' => $others, 'type' => $meals, 'amount' => 45],
      ['district' => $tumbes, 'category' => $others, 'type' => $accommodation, 'amount' => 90],
      ['district' => $tumbes, 'category' => $others, 'type' => $localTransport, 'amount' => 40],

      // JAEN
      // Managers
      ['district' => $jaen, 'category' => $managers, 'type' => $meals, 'amount' => 50],
      ['district' => $jaen, 'category' => $managers, 'type' => $accommodation, 'amount' => 140],
      ['district' => $jaen, 'category' => $managers, 'type' => $localTransport, 'amount' => 40],
      // Other Employees
      ['district' => $jaen, 'category' => $others, 'type' => $meals, 'amount' => 45],
      ['district' => $jaen, 'category' => $others, 'type' => $accommodation, 'amount' => 90],
      ['district' => $jaen, 'category' => $others, 'type' => $localTransport, 'amount' => 40],
    ];

    foreach ($rates as $rate) {
      PerDiemRate::firstOrCreate([
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
