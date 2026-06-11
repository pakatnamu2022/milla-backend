<?php

namespace Database\Seeders\ap\vehiculo;

use App\Models\ap\ApMasters;
use App\Models\ap\configuracionComercial\vehiculo\ApClassArticle;
use App\Models\ap\configuracionComercial\vehiculo\ApFamilies;
use App\Models\ap\configuracionComercial\vehiculo\ApFuelType;
use App\Models\ap\configuracionComercial\vehiculo\ApModelsVn;
use App\Models\ap\maestroGeneral\TypeCurrency;
use Illuminate\Database\Seeder;

class ApModelsVnSeeder extends Seeder
{
  public function run(): void
  {
    // Prerrequisitos: estos registros deben existir previamente en sus tablas.
    $helpers = $this->buildHelpers();

    if (!$helpers) {
      $this->command->warn('⚠️  No se encontraron los datos de referencia necesarios. Ejecuta primero los seeders de familias, clases, combustibles y maestros AP.');
      return;
    }

    $models = [
      // ── HILUX ──────────────────────────────────────────────────────────────
      [
        'family'           => 'HILUX',
        'class'            => 'VEHICULOS COMERCIALES',
        'version'          => 'HILUX 4X2 STD',
        'fuel'             => 'GASOLINA',
        'vehicle_type'     => 'PICK UP',
        'body_type'        => 'CABINA DOBLE',
        'traction_type'    => '4X2',
        'transmission'     => 'MECANICA',
        'currency'         => 'USD',
        'type_operation'   => 'COMERCIAL',
        'model_year'       => 2025,
        'power'            => '163 HP',
        'wheelbase'        => '3085',
        'axles_number'     => '2',
        'width'            => '1855',
        'length'           => '5335',
        'height'           => '1815',
        'seats_number'     => '5',
        'doors_number'     => '4',
        'net_weight'       => '1810',
        'gross_weight'     => '2800',
        'payload'          => '990',
        'displacement'     => '2755',
        'cylinders_number' => '4',
        'passengers_number'=> '5',
        'wheels_number'    => '4',
        'distributor_price'  => 32500.0000,
        'transport_cost'     => 850.0000,
        'other_amounts'      => 0.0000,
        'purchase_discount'  => 0.0000,
      ],
      [
        'family'           => 'HILUX',
        'class'            => 'VEHICULOS COMERCIALES',
        'version'          => 'HILUX 4X4 SRV',
        'fuel'             => 'DIESEL',
        'vehicle_type'     => 'PICK UP',
        'body_type'        => 'CABINA DOBLE',
        'traction_type'    => '4X4',
        'transmission'     => 'AUTOMATICA',
        'currency'         => 'USD',
        'type_operation'   => 'COMERCIAL',
        'model_year'       => 2025,
        'power'            => '204 HP',
        'wheelbase'        => '3085',
        'axles_number'     => '2',
        'width'            => '1855',
        'length'           => '5335',
        'height'           => '1815',
        'seats_number'     => '5',
        'doors_number'     => '4',
        'net_weight'       => '2080',
        'gross_weight'     => '3010',
        'payload'          => '930',
        'displacement'     => '2755',
        'cylinders_number' => '4',
        'passengers_number'=> '5',
        'wheels_number'    => '4',
        'distributor_price'  => 44800.0000,
        'transport_cost'     => 850.0000,
        'other_amounts'      => 0.0000,
        'purchase_discount'  => 0.0000,
      ],
      // ── LAND CRUISER ───────────────────────────────────────────────────────
      [
        'family'           => 'LAND CRUISER',
        'class'            => 'VEHICULOS COMERCIALES',
        'version'          => 'LAND CRUISER PRADO TX',
        'fuel'             => 'DIESEL',
        'vehicle_type'     => 'SUV',
        'body_type'        => 'STATION WAGON',
        'traction_type'    => '4X4',
        'transmission'     => 'AUTOMATICA',
        'currency'         => 'USD',
        'type_operation'   => 'COMERCIAL',
        'model_year'       => 2025,
        'power'            => '175 HP',
        'wheelbase'        => '2790',
        'axles_number'     => '2',
        'width'            => '1885',
        'length'           => '4825',
        'height'           => '1835',
        'seats_number'     => '7',
        'doors_number'     => '5',
        'net_weight'       => '2150',
        'gross_weight'     => '2890',
        'payload'          => '740',
        'displacement'     => '2755',
        'cylinders_number' => '4',
        'passengers_number'=> '7',
        'wheels_number'    => '4',
        'distributor_price'  => 62000.0000,
        'transport_cost'     => 950.0000,
        'other_amounts'      => 0.0000,
        'purchase_discount'  => 0.0000,
      ],
      // ── YARIS ──────────────────────────────────────────────────────────────
      [
        'family'           => 'YARIS',
        'class'            => 'VEHICULOS COMERCIALES',
        'version'          => 'YARIS SEDAN XLS',
        'fuel'             => 'GASOLINA',
        'vehicle_type'     => 'SEDAN',
        'body_type'        => 'SEDAN',
        'traction_type'    => '4X2',
        'transmission'     => 'AUTOMATICA',
        'currency'         => 'USD',
        'type_operation'   => 'COMERCIAL',
        'model_year'       => 2025,
        'power'            => '107 HP',
        'wheelbase'        => '2550',
        'axles_number'     => '2',
        'width'            => '1700',
        'length'           => '4410',
        'height'           => '1475',
        'seats_number'     => '5',
        'doors_number'     => '4',
        'net_weight'       => '1085',
        'gross_weight'     => '1535',
        'payload'          => '450',
        'displacement'     => '1496',
        'cylinders_number' => '4',
        'passengers_number'=> '5',
        'wheels_number'    => '4',
        'distributor_price'  => 18900.0000,
        'transport_cost'     => 650.0000,
        'other_amounts'      => 0.0000,
        'purchase_discount'  => 0.0000,
      ],
      // ── POSTVENTA (repuesto/servicio) ──────────────────────────────────────
      [
        'family'           => 'HILUX',
        'class'            => 'REPUESTOS',
        'version'          => 'HILUX 2.8 DIESEL GD6',
        'fuel'             => 'DIESEL',
        'vehicle_type'     => 'PICK UP',
        'body_type'        => 'CABINA DOBLE',
        'traction_type'    => '4X4',
        'transmission'     => 'MECANICA',
        'currency'         => 'USD',
        'type_operation'   => 'POSTVENTA',
        'model_year'       => null,
        'power'            => null,
        'wheelbase'        => null,
        'axles_number'     => null,
        'width'            => null,
        'length'           => null,
        'height'           => null,
        'seats_number'     => null,
        'doors_number'     => null,
        'net_weight'       => null,
        'gross_weight'     => null,
        'payload'          => null,
        'displacement'     => '2755',
        'cylinders_number' => '4',
        'passengers_number'=> null,
        'wheels_number'    => null,
        'distributor_price'  => 0.0000,
        'transport_cost'     => 0.0000,
        'other_amounts'      => 0.0000,
        'purchase_discount'  => 0.0000,
      ],
    ];

    foreach ($models as $data) {
      $family = ApFamilies::where('description', $data['family'])->first();
      $class  = ApClassArticle::where('description', $data['class'])->first();
      $fuel   = ApFuelType::where('description', $data['fuel'])->first();

      $vehicleType  = ApMasters::where('type', 'TIPO_VEHICULO')->where('description', $data['vehicle_type'])->first();
      $bodyType     = ApMasters::where('type', 'TIPO_CARROCERIA')->where('description', $data['body_type'])->first();
      $tractionType = ApMasters::where('type', 'TIPO_TRACCION')->where('description', $data['traction_type'])->first();
      $transmission = ApMasters::where('type', 'TRANSMISION_VEHICULO')->where('description', $data['transmission'])->first();
      $typeOp       = ApMasters::where('type', 'TIPO_OPERACION')->where('description', $data['type_operation'])->first();
      $currency     = TypeCurrency::where('code', $data['currency'])->first();

      $missing = array_filter([
        'familia'         => $family,
        'clase'           => $class,
        'combustible'     => $fuel,
        'tipo_vehiculo'   => $vehicleType,
        'tipo_carroceria' => $bodyType,
        'tipo_traccion'   => $tractionType,
        'transmision'     => $transmission,
        'tipo_operacion'  => $typeOp,
        'moneda'          => $currency,
      ], fn($v) => $v === null);

      if (!empty($missing)) {
        $this->command->warn("⚠️  Modelo '{$data['version']}' omitido — faltan: " . implode(', ', array_keys($missing)));
        continue;
      }

      ApModelsVn::firstOrCreate(
        [
          'family_id'        => $family->id,
          'version'          => strtoupper($data['version']),
          'type_operation_id'=> $typeOp->id,
          'model_year'       => $data['model_year'],
        ],
        [
          'class_id'          => $class->id,
          'fuel_id'           => $fuel->id,
          'vehicle_type_id'   => $vehicleType->id,
          'body_type_id'      => $bodyType->id,
          'traction_type_id'  => $tractionType->id,
          'transmission_id'   => $transmission->id,
          'currency_type_id'  => $currency->id,
          'power'             => $data['power'],
          'model_year'        => $data['model_year'],
          'wheelbase'         => $data['wheelbase'],
          'axles_number'      => $data['axles_number'],
          'width'             => $data['width'],
          'length'            => $data['length'],
          'height'            => $data['height'],
          'seats_number'      => $data['seats_number'],
          'doors_number'      => $data['doors_number'],
          'net_weight'        => $data['net_weight'],
          'gross_weight'      => $data['gross_weight'],
          'payload'           => $data['payload'],
          'displacement'      => $data['displacement'],
          'cylinders_number'  => $data['cylinders_number'],
          'passengers_number' => $data['passengers_number'],
          'wheels_number'     => $data['wheels_number'],
          'distributor_price' => $data['distributor_price'],
          'transport_cost'    => $data['transport_cost'],
          'other_amounts'     => $data['other_amounts'],
          'purchase_discount' => $data['purchase_discount'],
          'status'            => true,
        ]
      );

      $this->command->info("✅ Modelo '{$data['version']}' ({$data['type_operation']}) insertado.");
    }

    $this->command->info('🚀 ApModelsVnSeeder finalizado.');
  }

  private function buildHelpers(): bool
  {
    return ApFamilies::exists()
      && ApClassArticle::exists()
      && ApFuelType::exists()
      && ApMasters::where('type', 'TIPO_VEHICULO')->exists()
      && ApMasters::where('type', 'TIPO_OPERACION')->exists()
      && TypeCurrency::exists();
  }
}
