<?php

namespace App\Http\Services\tp\comercial;

use App\Http\Resources\tp\comercial\SupplyControlResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionhumana\personal\Worker;
use App\Models\tp\comercial\Supplier;
use App\Models\tp\comercial\SupplyControl;
use App\Models\tp\comercial\SupplyPhoto;
use App\Models\tp\comercial\Vehicle;
use App\Models\tp\Driver;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class SupplyControlService extends BaseService
{
    protected SupplyPhotoService $photoService;

    public function __construct(SupplyPhotoService $photoService)
    {
        $this->photoService = $photoService;
    }

    public function list(Request $request)
    {
        $query = SupplyControl::with(['vehicle', 'driver', 'supplier', 'photo']);

        // Filtro de estado
        if ($request->has('status_id')) {
            $statusId = $request->input('status_id');
            switch ($statusId) {
                case '0':
                    $query->where('status_deleted', 0);
                    break;
                case '1':
                    $query->where('status_deleted', 1);
                    break;
                case 'all':
                    break;
                default:
                    $query->where('status_deleted', 1);
            }
        } else {
            $query->where('status_deleted', 1);
        }

        // Filtro de búsqueda
        if ($request->has('search') && ! empty($request->input('search'))) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->whereHas('vehicle', function ($v) use ($search) {
                    $v->where('placa', 'like', "%{$search}%");
                })->orWhereHas('driver', function ($d) use ($search) {
                    $d->where('nombre_completo', 'like', "%{$search}%")
                        ->orWhere('vat', 'like', "%{$search}%");
                })->orWhereHas('supplier', function ($s) use ($search) {
                    $s->where('name', 'like', "%{$search}%");
                });
            });
        }

        // Filtros específicos
        if ($request->has('vehicle_id') && $request->input('vehicle_id')) {
            $query->where('vehicle_id', $request->input('vehicle_id'));
        }

        if ($request->has('driver_id') && $request->input('driver_id')) {
            $query->where('driver_id', $request->input('driver_id'));
        }

        if ($request->has('supplier_id') && $request->input('supplier_id')) {
            $query->where('supplier_id', $request->input('supplier_id'));
        }

        if ($request->has('is_base') && $request->input('is_base') !== '') {
            $query->where('is_base', $request->boolean('is_base'));
        }

        if ($request->has('date_from') && $request->input('date_from')) {
            $query->whereDate('recorded_at', '>=', $request->input('date_from'));
        }

        if ($request->has('date_to') && $request->input('date_to')) {
            $query->whereDate('recorded_at', '<=', $request->input('date_to'));
        }

        $user = auth()->user();
        if ($user) {
            $worker = Worker::find($user->partner_id);
            if ($worker && in_array($worker->cargo_id, [11, 12])) {
                $query->where('driver_id', $worker->id);
            }
        }

        $query->orderBy('recorded_at', 'desc');
        $perPage = $request->input('per_page', 15);
        $page = $request->input('page', 1);

        $paginated = $query->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => SupplyControlResource::collection($paginated->items()),
            'links' => [
                'first' => $paginated->url(1),
                'last' => $paginated->url($paginated->lastPage()),
                'prev' => $paginated->previousPageUrl(),
                'next' => $paginated->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'from' => $paginated->firstItem(),
                'to' => $paginated->lastItem(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'last_page' => $paginated->lastPage(),
            ],
        ];
    }

    public function getFormData(): array
    {
        $vehicles = Vehicle::where('status_deleted', 1)
            ->where('tipo_vehiculo_id', 1)
            ->where('vehiculo_status', 1)
            ->orderBy('placa')
            ->get(['id', 'placa', 'modelo', 'marca']);

        $drivers = Driver::where('status_deleted', 1)
            ->where('b_empleado', 1)
            ->where('status_id', 22)
            ->whereIn('cargo_id', [11, 12])
            ->orderBy('nombre_completo')
            ->get(['id', 'nombre_completo', 'vat']);

        $suppliers = Supplier::where('status_deleted', 1)
            ->where('is_active', 1)
            ->orderBy('name')
            ->get(['id', 'name']);

        return [
            'vehicles' => $vehicles,
            'drivers' => $drivers,
            'suppliers' => $suppliers,
        ];
    }

    public function store($data)
    {
        DB::beginTransaction();
        try {
            $supply = SupplyControl::create([
                'vehicle_id' => $data['vehicle_id'],
                'driver_id' => $data['driver_id'],
                'supplier_id' => $data['supplier_id'],
                'mileage' => $data['mileage'],
                'gallons' => $data['gallons'],
                'is_base' => $data['is_base'] ?? true,
                'recorded_at' => $data['recorded_at'] ?? now(),
                'created_by' => auth()->id(),
                'status_deleted' => 1,
            ]);

            $this->processTankPhotos($data, $supply->id);

            if (isset($data['is_base']) && $data['is_base'] == false) {
                if (! empty($data['ticket_photo'])) {
                    $this->processTicketPhoto($data['ticket_photo'], $supply->id);
                } else {
                    throw new Exception('El ticket es obligatorio para registros fuera de base');
                }
            }

            DB::commit();

            $supply->load(['vehicle', 'driver', 'supplier', 'photo']);
            $shouldPrint = isset($data['is_base']) && $data['is_base'] == true;

            return [
                'success' => true,
                'data' => new SupplyControlResource($supply),
                'should_print' => $shouldPrint,
                'message' => 'Abastecimiento registrado exitosamente',
            ];
        } catch (Throwable $th) {
            DB::rollBack();
            Log::error('Error en SupplyControlService@store: '.$th->getMessage());
            throw new Exception('Error al registrar el abastecimiento: '.$th->getMessage());
        }
    }

    private function processTankPhotos(array $data, int $supplyControlId): void
    {
        $tankPhotos = [
            'tank_left_photo' => SupplyPhoto::TYPE_TANK_LEFT,
            'tank_right_photo' => SupplyPhoto::TYPE_TANK_RIGHT,
        ];

        foreach ($tankPhotos as $field => $type) {
            if (! empty($data[$field])) {
                try {
                    $this->photoService->storePhotoFromBase64(
                        $data[$field],
                        $supplyControlId,
                        null,
                        $type
                    );
                } catch (\Throwable $e) {
                    Log::error("Error guardando foto {$type}: ".$e->getMessage());
                }
            }
        }
    }

    private function processTicketPhoto(string $base64Image, int $supplyControlId): void
    {
        try {
            $this->photoService->storePhotoFromBase64(
                $base64Image,
                $supplyControlId,
                null,
                SupplyPhoto::TYPE_TICKET
            );
        } catch (\Throwable $e) {
            Log::error('Error guardando ticket: '.$e->getMessage());
        }
    }

    public function show($id)
    {
        $supply = SupplyControl::with(['vehicle', 'driver', 'supplier', 'photo'])
            ->where('status_deleted', 1)
            ->findOrFail($id);

        $user = auth()->user();
        if ($user) {
            $worker = Worker::find($user->partner_id);
            if ($worker && in_array($worker->cargo_id, [11, 12]) && $supply->driver_id != $worker->id) {
                throw new Exception('No tiene permiso para ver este registro');
            }
        }

        return new SupplyControlResource($supply);
    }

    public function update($data)
    {
        DB::beginTransaction();
        try {
            $supply = SupplyControl::where('status_deleted', 1)->findOrFail($data['id']);

            $user = auth()->user();
            if ($user) {
                $worker = Worker::find($user->partner_id);
                if ($worker && in_array($worker->cargo_id, [11, 12]) && $supply->driver_id != $worker->id) {
                    throw new Exception('No tiene permiso para editar este registro');
                }
            }

            $updateData = [];
            $fields = ['vehicle_id', 'driver_id', 'supplier_id', 'mileage', 'gallons', 'is_base', 'recorded_at'];
            foreach ($fields as $field) {
                if (array_key_exists($field, $data)) {
                    $updateData[$field] = $data[$field];
                }
            }

            $supply->update($updateData);

            if (! empty($data['photo'])) {
                if ($supply->photo_id) {
                    $this->photoService->destroy($supply->photo_id);
                }

                $photo = $this->photoService->storePhotoFromBase64(
                    $data['photo'],
                    $supply->id,
                    $data['photo_name'] ?? null
                );
                $supply->photo_id = $photo->id;
                $supply->save();
            }

            DB::commit();

            $supply->load(['vehicle', 'driver', 'supplier', 'photo']);

            return [
                'success' => true,
                'data' => new SupplyControlResource($supply),
                'message' => 'Abastecimiento actualizado exitosamente',
            ];
        } catch (Throwable $th) {
            DB::rollBack();
            Log::error('Error en SupplyControlService@update: '.$th->getMessage());
            throw new Exception('Error al actualizar el abastecimiento: '.$th->getMessage());
        }
    }

    public function uploadTicketPhoto(int $supplyControlId, string $base64Image): array
    {
        DB::beginTransaction();
        try {
            $supply = SupplyControl::where('status_deleted', 1)
                ->findOrFail($supplyControlId);

            if (! $supply->is_base) {
                throw new Exception('Este registro no está en base, no se puede subir ticket');
            }

            $existingTicket = SupplyPhoto::where('supply_control_id', $supplyControlId)
                ->where('photo_type', SupplyPhoto::TYPE_TICKET)
                ->first();

            if ($existingTicket) {
                $this->photoService->destroy($existingTicket->id);
            }

            // Guardar nueva foto
            $photo = $this->photoService->storePhotoFromBase64(
                $base64Image,
                $supplyControlId,
                null,
                SupplyPhoto::TYPE_TICKET
            );

            $supply->update(['photo_id' => $photo->id]);

            DB::commit();

            $supply->load(['vehicle', 'driver', 'supplier', 'photo']);

            return [
                'success' => true,
                'data' => new SupplyControlResource($supply),
                'message' => 'Ticket subido exitosamente',
            ];
        } catch (Throwable $th) {
            DB::rollBack();
            Log::error('Error en SupplyControlService@uploadTicketPhoto: '.$th->getMessage());
            throw new Exception('Error al subir el ticket: '.$th->getMessage());
        }
    }

    public function getPhotosByType(int $supplyControlId, ?string $type = null): array
    {
        $query = SupplyPhoto::where('supply_control_id', $supplyControlId)
            ->where('status_deleted', 1);

        if ($type) {
            $query->where('photo_type', $type);
        }

        $photos = $query->get();

        return [
            'photos' => $photos,
            'tank_left' => $photos->where('photo_type', SupplyPhoto::TYPE_TANK_LEFT)->first(),
            'tank_right' => $photos->where('photo_type', SupplyPhoto::TYPE_TANK_RIGHT)->first(),
            'ticket' => $photos->where('photo_type', SupplyPhoto::TYPE_TICKET)->first(),
            'has_all_photos' => $this->hasAllPhotos($photos),
        ];
    }

    private function hasAllPhotos($photos): bool
    {
        $types = [SupplyPhoto::TYPE_TANK_LEFT, SupplyPhoto::TYPE_TANK_RIGHT, SupplyPhoto::TYPE_TICKET];
        $existingTypes = $photos->pluck('photo_type')->toArray();

        foreach ($types as $type) {
            if (! in_array($type, $existingTypes)) {
                return false;
            }
        }

        return true;
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $supply = SupplyControl::where('status_deleted', 1)->findOrFail($id);

            // Verificar permisos: solo asistente de operaciones puede eliminar
            $user = auth()->user();
            if ($user) {
                $worker = Worker::find($user->partner_id);
                if ($worker && in_array($worker->cargo_id, [11, 12])) {
                    throw new Exception('No tiene permiso para anular este registro');
                }
            }

            if ($supply->photo_id) {
                $this->photoService->destroy($supply->photo_id);
            }

            $supply->update(['status_deleted' => 0]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Abastecimiento anulado exitosamente',
            ];
        } catch (Throwable $th) {
            DB::rollBack();
            Log::error('Error en SupplyControlService@destroy: '.$th->getMessage());
            throw new Exception('Error al anular el abastecimiento: '.$th->getMessage());
        }
    }

    public function getStats(): array
    {
        $query = SupplyControl::where('status_deleted', 1);

        $total = $query->count();
        $inBase = (clone $query)->where('is_base', 1)->count();
        $outOfBase = (clone $query)->where('is_base', 0)->count();

        // Total de galones
        $totalGallons = (clone $query)->sum('gallons');

        // Últimos 7 días
        $last7Days = (clone $query)
            ->where('recorded_at', '>=', now()->subDays(7))
            ->count();

        return [
            'total' => $total,
            'in_base' => $inBase,
            'out_of_base' => $outOfBase,
            'total_gallons' => round($totalGallons, 3),
            'last_7_days' => $last7Days,
        ];
    }
}
