<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Services\ap\configuracionComercial\vehiculo\ApModelsVnService;
use Illuminate\Support\Facades\Validator;

class ImportVehicles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:vehicles {file?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importar modelos de vehículos desde un archivo CSV';

    protected ApModelsVnService $service;

    public function __construct(ApModelsVnService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = $this->argument('file') ?? storage_path('app/vehicles.csv');

        if (!file_exists($filePath)) {
            $this->error("El archivo {$filePath} no existe.");
            $this->info("Uso: php artisan import:vehicles [ruta/al/archivo.csv]");
            return 1;
        }

        $this->info("Leyendo datos desde: {$filePath}");

        $vehicles = $this->getVehiclesFromCSV($filePath);

        if (empty($vehicles)) {
            $this->error("No se encontraron datos para importar.");
            return 1;
        }

        $this->info("Total de registros a importar: " . count($vehicles));

        if (!$this->confirm('¿Deseas continuar con la importación?')) {
            $this->info('Importación cancelada.');
            return 0;
        }

        $bar = $this->output->createProgressBar(count($vehicles));
        $bar->start();

        $success = 0;
        $errors = [];

        foreach ($vehicles as $index => $vehicleData) {
            try {
                // Validar datos antes de importar
                $validator = Validator::make($vehicleData, [
                    'code' => 'required|string|max:50',
                    'version' => 'required|string|max:255',
                    'power' => 'required|string|max:50',
                    'model_year' => 'required|integer|min:1900|max:' . (date('Y') + 5),
                    'wheelbase' => 'required|string|max:50',
                    'axles_number' => 'required|string|max:50',
                    'width' => 'required|string|max:50',
                    'length' => 'required|string|max:50',
                    'height' => 'required|string|max:50',
                    'seats_number' => 'required|string|max:50',
                    'doors_number' => 'required|string|max:50',
                    'net_weight' => 'required|string|max:50',
                    'gross_weight' => 'required|string|max:50',
                    'payload' => 'required|string|max:50',
                    'displacement' => 'required|string|max:50',
                    'cylinders_number' => 'required|string|max:50',
                    'passengers_number' => 'required|string|max:50',
                    'wheels_number' => 'required|string|max:50',
                    'distributor_price' => 'required|numeric|min:0',
                    'transport_cost' => 'required|numeric|min:0',
                    'other_amounts' => 'required|numeric|min:0',
                    'purchase_discount' => 'required|numeric|min:0',
                    'igv_amount' => 'required|numeric|min:0',
                    'total_purchase_excl_igv' => 'required|numeric|min:0',
                    'total_purchase_incl_igv' => 'required|numeric|min:0',
                    'sale_price' => 'required|numeric|min:0',
                    'margin' => 'required|numeric|min:0',
                    'family_id' => 'required|integer|exists:ap_families,id',
                    'class_id' => 'required|integer|exists:ap_class_article,id',
                    'fuel_id' => 'required|integer|exists:ap_fuel_type,id',
                    'vehicle_type_id' => 'required|integer|exists:ap_commercial_masters,id',
                    'body_type_id' => 'required|integer|exists:ap_commercial_masters,id',
                    'traction_type_id' => 'required|integer|exists:ap_commercial_masters,id',
                    'transmission_id' => 'required|integer|exists:ap_commercial_masters,id',
                    'currency_type_id' => 'required|integer|exists:type_currency,id',
                ]);

                if ($validator->fails()) {
                    $errors[] = [
                        'row' => $index + 1,
                        'code' => $vehicleData['code'] ?? 'N/A',
                        'error' => $validator->errors()->first()
                    ];
                    $bar->advance();
                    continue;
                }

                // Llamar al servicio para guardar el vehículo
                $this->service->store($vehicleData);
                $success++;

            } catch (\Exception $e) {
                $errors[] = [
                    'row' => $index + 1,
                    'code' => $vehicleData['code'] ?? 'N/A',
                    'error' => $e->getMessage()
                ];
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Mostrar resumen
        $this->info("Importación completada!");
        $this->info("Registros exitosos: {$success}");
        $this->error("Registros con errores: " . count($errors));

        if (!empty($errors)) {
            $this->newLine();
            $this->error("Detalle de errores:");
            foreach ($errors as $error) {
                $this->line("  - Fila {$error['row']} (Código: {$error['code']}): {$error['error']}");
            }
        }

        return 0;
    }

    /**
     * Leer datos desde archivo CSV
     */
    private function getVehiclesFromCSV(string $filePath): array
    {
        $vehicles = [];

        if (($handle = fopen($filePath, 'r')) !== false) {
            // Leer encabezados
            $headers = fgetcsv($handle, 0, ',');

            // Leer cada fila
            while (($data = fgetcsv($handle, 0, ',')) !== false) {
                if (count($data) === count($headers)) {
                    $vehicles[] = array_combine($headers, $data);
                }
            }

            fclose($handle);
        }

        return $vehicles;
    }
}
