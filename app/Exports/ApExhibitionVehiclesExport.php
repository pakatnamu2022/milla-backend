<?php

namespace App\Exports;

use App\Models\ap\comercial\ApExhibitionVehicles;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ApExhibitionVehiclesExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = ApExhibitionVehicles::with([
            'vehicle.vehicleStatus',
            'vehicle.model.family.brand',
            'vehicle.color',
            'advisor',
            'propietario',
            'ubicacion',
        ]);

        // Apply filters if needed
        // You can add filter logic here based on $this->filters

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'STATUS',
            'MARCA',
            'MODELO',
            'COLOR',
            'CHASIS',
            'MOTOR',
            'AÑO MOD',
            'PLACA',
            'PEDIDO DE SUCURSAL',
            'EQUIPO DERCO',
            'LLEGADA',
            'N° GUIA',
            'FECHA GUIA',
            'ASESOR',
            'PROPIETARIO',
            'UBICACION',
            'OBSERVACIONES',
            'N° DUA',
            'DETALLE',
        ];
    }

    public function map($exhibitionVehicle): array
    {
        return [
            $exhibitionVehicle->vehicle?->vehicleStatus?->description ?? '',
            $exhibitionVehicle->vehicle?->model?->family?->brand?->name ?? '',
            $exhibitionVehicle->vehicle?->model?->version ?? '',
            $exhibitionVehicle->vehicle?->color?->description ?? '',
            $exhibitionVehicle->vehicle?->vin ?? '',
            $exhibitionVehicle->vehicle?->engine_number ?? '',
            $exhibitionVehicle->vehicle?->year ?? '',
            $exhibitionVehicle->vehicle?->plate ?? '',
            $exhibitionVehicle->pedido_sucursal ?? '',
            $exhibitionVehicle->equipo_derco ?? '',
            $exhibitionVehicle->llegada?->format('d/m/Y') ?? '',
            $exhibitionVehicle->guia_number ?? '',
            $exhibitionVehicle->guia_date?->format('d/m/Y') ?? '',
            $exhibitionVehicle->advisor?->nombre_completo ?? '',
            $exhibitionVehicle->propietario?->full_name ?? '',
            $exhibitionVehicle->ubicacion?->description ?? '',
            $exhibitionVehicle->observaciones ?? '',
            $exhibitionVehicle->dua_number ?? '',
            $exhibitionVehicle->detalle ?? '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
