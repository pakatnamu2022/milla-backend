<?php

namespace Database\Seeders;

use App\Models\ap\maestroGeneral\HeaderWarehouse;
use App\Models\ap\maestroGeneral\Warehouse;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HeaderWarehouseSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    // Obtener combinaciones únicas de warehouses con status = 1
    $distinctWarehouses = DB::table('warehouse')
      ->select('status', 'is_received', 'sede_id', 'type_operation_id', 'dyn_code')
      ->where('status', 1)
      ->whereNull('deleted_at')
      ->groupBy('status', 'is_received', 'sede_id', 'type_operation_id', 'dyn_code')
      ->get();

    $counter = 1;
    foreach ($distinctWarehouses as $warehouse) {
      // Generar dyn_code automáticamente
      $dynCode = $warehouse->dyn_code;

      // Crear el header_warehouse
      $headerWarehouse = HeaderWarehouse::create([
        'dyn_code' => $dynCode,
        'status' => $warehouse->status,
        'is_received' => $warehouse->is_received,
        'sede_id' => $warehouse->sede_id,
        'type_operation_id' => $warehouse->type_operation_id,
      ]);

      // Actualizar todos los warehouses que coincidan con esta combinación
      Warehouse::where('status', $warehouse->status)
        ->where('is_received', $warehouse->is_received)
        ->where('sede_id', $warehouse->sede_id)
        ->where('type_operation_id', $warehouse->type_operation_id)
        ->update(['header_warehouse_id' => $headerWarehouse->id]);

      $counter++;
    }

    $this->command->info('HeaderWarehouse seeder completado exitosamente.');
    $this->command->info('Total de header_warehouses creados: ' . $distinctWarehouses->count());
  }
}
