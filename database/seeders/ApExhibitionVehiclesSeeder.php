<?php

namespace Database\Seeders;

use App\Models\ap\comercial\ApExhibitionVehicles;
use App\Models\ap\comercial\ApExhibitionVehicleItems;
use App\Models\ap\comercial\Vehicles;
use App\Models\ap\configuracionComercial\vehiculo\ApModelsVn;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleStatus;
use App\Models\ap\maestroGeneral\Warehouse;
use App\Models\ap\ApCommercialMasters;
use App\Models\gp\gestionhumana\personal\Worker;
use App\Models\ap\comercial\BusinessPartners;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class ApExhibitionVehiclesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get sample IDs from existing records
        $supplier = BusinessPartners::first();
        $warehouse = Warehouse::first();
        $advisor = Worker::first();
        $vehicleStatus = ApVehicleStatus::first();
        $model = ApModelsVn::first();
        $color = ApCommercialMasters::first();
        $engineType = ApCommercialMasters::skip(1)->first();
        $typeOperation = ApCommercialMasters::skip(2)->first();

        if (!$supplier || !$warehouse || !$advisor || !$vehicleStatus || !$model || !$color || !$engineType || !$typeOperation) {
            $this->command->error('Faltan datos maestros. Asegúrate de tener registros en: business_partners, warehouse, workers, ap_vehicle_status, ap_models_vn, ap_commercial_masters');
            return;
        }

        $exhibitionVehicles = [
            [
                'header' => [
                    'supplier_id' => $supplier->id,
                    'guia_number' => 'T603-00069609',
                    'guia_date' => '2025-04-09',
                    'llegada' => '2025-04-12',
                    'ubicacion_id' => $warehouse->id,
                    'advisor_id' => $advisor->id,
                    'ap_vehicle_status_id' => $vehicleStatus->id,
                    'pedido_sucursal' => 'PS-001',
                    'observaciones' => 'Vehículos de exhibición - TEST DRIVE',
                    'status' => true,
                ],
                'items' => [
                    [
                        'item_type' => 'vehicle',
                        'vin' => 'LSCBBZ2G1SG800145',
                        'plate' => 'CAM-933',
                        'year' => 2024,
                        'engine_number' => 'D20TCIEA5D23001177',
                        'description' => 'CHANGAN NEW F70 ELITE 1.9 MT 4X4',
                    ],
                    [
                        'item_type' => 'equipment',
                        'description' => 'Kit de accesorios DERCO Premium',
                        'quantity' => 2,
                    ],
                ],
            ],
            [
                'header' => [
                    'supplier_id' => $supplier->id,
                    'guia_number' => 'T603-00070863',
                    'guia_date' => '2025-05-02',
                    'llegada' => '2025-05-05',
                    'ubicacion_id' => $warehouse->id,
                    'advisor_id' => $advisor->id,
                    'ap_vehicle_status_id' => $vehicleStatus->id,
                    'pedido_sucursal' => 'PS-002',
                    'observaciones' => 'SE TRASLADO A CIX 13/08/2025//RETORNO 10/10/2025',
                    'status' => true,
                ],
                'items' => [
                    [
                        'item_type' => 'vehicle',
                        'vin' => 'LJ11PABE1TC000764',
                        'plate' => 'CDI-767',
                        'year' => 2025,
                        'engine_number' => 'HFC4DB22D1S4101570',
                        'description' => 'JAC T9 2.0 4X4 ADVANCE',
                    ],
                ],
            ],
            [
                'header' => [
                    'supplier_id' => $supplier->id,
                    'guia_number' => 'T603-00071331',
                    'guia_date' => '2025-05-14',
                    'llegada' => '2025-05-16',
                    'ubicacion_id' => $warehouse->id,
                    'advisor_id' => $advisor->id,
                    'ap_vehicle_status_id' => $vehicleStatus->id,
                    'pedido_sucursal' => 'PS-003',
                    'status' => true,
                ],
                'items' => [
                    [
                        'item_type' => 'vehicle',
                        'vin' => 'LS5A3DSE9SD910248',
                        'plate' => 'CND-417',
                        'year' => 2025,
                        'engine_number' => 'JL473QFR3CV501574',
                        'description' => 'CHANGAN CS15 LUXURY 1.5L DCT 4X2',
                    ],
                    [
                        'item_type' => 'equipment',
                        'description' => 'Tapetes personalizados',
                        'quantity' => 1,
                    ],
                ],
            ],
            [
                'header' => [
                    'supplier_id' => $supplier->id,
                    'guia_number' => 'T603-00072414',
                    'guia_date' => '2025-05-30',
                    'llegada' => '2025-06-02',
                    'ubicacion_id' => $warehouse->id,
                    'advisor_id' => $advisor->id,
                    'ap_vehicle_status_id' => $vehicleStatus->id,
                    'observaciones' => 'SE ENTREGO 14/08/2025',
                    'status' => true,
                ],
                'items' => [
                    [
                        'item_type' => 'vehicle',
                        'vin' => 'TSMYD21S6RMC45656',
                        'plate' => 'CRX-039',
                        'year' => 2023,
                        'engine_number' => 'M16A2413574',
                        'description' => 'SUZUKI NEW VITARA GL LUX MT 2WD',
                    ],
                ],
            ],
            [
                'header' => [
                    'supplier_id' => $supplier->id,
                    'guia_number' => 'T603-00071073',
                    'guia_date' => '2025-05-08',
                    'llegada' => '2025-05-12',
                    'ubicacion_id' => $warehouse->id,
                    'advisor_id' => $advisor->id,
                    'ap_vehicle_status_id' => $vehicleStatus->id,
                    'pedido_sucursal' => 'PS-004',
                    'observaciones' => 'SE TRASLADO A JAEN 06/09/2025 / SE ENCUENTRA EN JAEN',
                    'status' => true,
                ],
                'items' => [
                    [
                        'item_type' => 'vehicle',
                        'vin' => 'LJ11PABD7TC000436',
                        'plate' => 'CDI-748',
                        'year' => 2024,
                        'engine_number' => 'HFC4DB21D1R4138172',
                        'description' => 'JAC T8 PRO 2.0 4X4 ADVANCE',
                    ],
                    [
                        'item_type' => 'equipment',
                        'description' => 'Kit de seguridad completo',
                        'quantity' => 1,
                    ],
                ],
            ],
            [
                'header' => [
                    'supplier_id' => $supplier->id,
                    'guia_number' => 'T603-00070000',
                    'guia_date' => '2025-04-15',
                    'llegada' => '2025-04-18',
                    'ubicacion_id' => $warehouse->id,
                    'advisor_id' => $advisor->id,
                    'ap_vehicle_status_id' => $vehicleStatus->id,
                    'observaciones' => 'CEDIDA A CIX 15/08/2025',
                    'status' => true,
                ],
                'items' => [
                    [
                        'item_type' => 'vehicle',
                        'vin' => 'JM7DM2W7AT0313538',
                        'plate' => 'CSA169',
                        'year' => 2024,
                        'engine_number' => 'PE22058062',
                        'description' => 'MAZDA CX-30 CORE 2.0 AT 2WD',
                    ],
                ],
            ],
            [
                'header' => [
                    'supplier_id' => $supplier->id,
                    'guia_number' => 'T603-00070598',
                    'guia_date' => '2025-04-25',
                    'llegada' => '2025-04-30',
                    'ubicacion_id' => $warehouse->id,
                    'advisor_id' => $advisor->id,
                    'ap_vehicle_status_id' => $vehicleStatus->id,
                    'observaciones' => 'CEDIDA A CLIENTE 17/07/2025',
                    'status' => true,
                ],
                'items' => [
                    [
                        'item_type' => 'vehicle',
                        'vin' => 'LGWFF6A59RH914808',
                        'plate' => 'CPJ584',
                        'year' => 2023,
                        'engine_number' => 'GW4N2023418012589',
                        'description' => 'HAVAL DARGO 2.0 4X4 AT SUPREME',
                    ],
                    [
                        'item_type' => 'equipment',
                        'description' => 'Barras de techo cromadas',
                        'quantity' => 1,
                    ],
                    [
                        'item_type' => 'equipment',
                        'description' => 'Estribos laterales',
                        'quantity' => 2,
                    ],
                ],
            ],
            [
                'header' => [
                    'supplier_id' => $supplier->id,
                    'guia_number' => 'T603-00070599',
                    'guia_date' => '2025-04-25',
                    'llegada' => '2025-04-30',
                    'ubicacion_id' => $warehouse->id,
                    'advisor_id' => $advisor->id,
                    'ap_vehicle_status_id' => $vehicleStatus->id,
                    'observaciones' => 'SE ENTREGO 23/08/2025',
                    'status' => true,
                ],
                'items' => [
                    [
                        'item_type' => 'vehicle',
                        'vin' => 'LGWFF6A52PH916722',
                        'plate' => 'CPK-043',
                        'year' => 2022,
                        'engine_number' => 'GW4N2022401050034',
                        'description' => 'HAVAL NG H6 2.0 4X4 AT SUPREME',
                    ],
                ],
            ],
            [
                'header' => [
                    'supplier_id' => $supplier->id,
                    'guia_number' => 'EG07-00011914',
                    'guia_date' => '2025-04-09',
                    'llegada' => '2025-04-17',
                    'ubicacion_id' => $warehouse->id,
                    'advisor_id' => $advisor->id,
                    'ap_vehicle_status_id' => $vehicleStatus->id,
                    'observaciones' => 'SE ENTREGO 25/10/2025',
                    'status' => true,
                ],
                'items' => [
                    [
                        'item_type' => 'vehicle',
                        'vin' => '9FBHJD203RM534316',
                        'plate' => 'CPI-155',
                        'year' => 2024,
                        'engine_number' => 'REN123456789',
                        'description' => 'RENAULT NEW DUSTER ICONIC 1.3CVT 4X2 TURBO',
                    ],
                ],
            ],
            [
                'header' => [
                    'supplier_id' => $supplier->id,
                    'guia_number' => 'T603-00077561',
                    'guia_date' => '2025-08-01',
                    'llegada' => '2025-08-05',
                    'ubicacion_id' => $warehouse->id,
                    'advisor_id' => $advisor->id,
                    'ap_vehicle_status_id' => $vehicleStatus->id,
                    'dua_number' => 'DUA-2025-001',
                    'observaciones' => 'SE TRASLADO A LIMA EL 15/08/2025',
                    'status' => true,
                ],
                'items' => [
                    [
                        'item_type' => 'vehicle',
                        'vin' => 'LGWFF7A54TJ603096',
                        'plate' => 'CRP546',
                        'year' => 2025,
                        'engine_number' => 'E20CB24587471151',
                        'description' => 'GREAT WALL TANK',
                    ],
                    [
                        'item_type' => 'equipment',
                        'description' => 'Kit multimedia Premium',
                        'quantity' => 1,
                    ],
                    [
                        'item_type' => 'equipment',
                        'description' => 'Llantas de aleación 18"',
                        'quantity' => 4,
                    ],
                ],
            ],
        ];

        foreach ($exhibitionVehicles as $data) {
            // Create header
            $header = ApExhibitionVehicles::create($data['header']);

            // Create items
            foreach ($data['items'] as $item) {
                if ($item['item_type'] === 'vehicle') {
                    // Create vehicle first
                    $vehicle = Vehicles::create([
                        'vin' => $item['vin'],
                        'plate' => $item['plate'],
                        'year' => $item['year'],
                        'engine_number' => $item['engine_number'],
                        'ap_models_vn_id' => $model->id,
                        'vehicle_color_id' => $color->id,
                        'engine_type_id' => $engineType->id,
                        'ap_vehicle_status_id' => $vehicleStatus->id,
                        'warehouse_id' => $warehouse->id,
                        'type_operation_id' => $typeOperation->id,
                        'status' => true,
                    ]);

                    // Create exhibition vehicle item
                    ApExhibitionVehicleItems::create([
                        'exhibition_vehicle_id' => $header->id,
                        'item_type' => 'vehicle',
                        'vehicle_id' => $vehicle->id,
                        'description' => $item['description'],
                        'quantity' => 1,
                        'status' => true,
                    ]);
                } else {
                    // Create equipment item
                    ApExhibitionVehicleItems::create([
                        'exhibition_vehicle_id' => $header->id,
                        'item_type' => 'equipment',
                        'vehicle_id' => null,
                        'description' => $item['description'],
                        'quantity' => $item['quantity'],
                        'status' => true,
                    ]);
                }
            }
        }

        $this->command->info('✅ Se crearon 10 órdenes de exhibición con sus items (vehículos y equipos)');
    }
}
