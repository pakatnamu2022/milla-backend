<?php

namespace App\Http\Services\tp\comercial;

use App\Http\Resources\tp\comercial\SupplierResource;
use App\Http\Services\BaseService;
use App\Models\tp\comercial\Supplier;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class SupplierService extends BaseService
{
    public function list(Request $request)
    {
        $query = Supplier::query();

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

        if ($request->has('search') && ! empty($request->input('search'))) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('ruc', 'like', "%{$search}%");
            });
        }

        if ($request->has('active_only') && $request->boolean('active_only')) {
            $query->where('is_active', 1);
        }

        $query->orderBy('name', 'asc');
        $perPage = $request->input('per_page', 15);
        $page = $request->input('page', 1);

        $paginated = $query->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => SupplierResource::collection($paginated->items()),
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

    public function store($data)
    {
        DB::beginTransaction();
        try {
            $supplier = Supplier::create([
                'name' => $data['name'],
                'ruc' => $data['ruc'] ?? null,
                'address' => $data['address'] ?? null,
                'phone' => $data['phone'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'created_by' => auth()->id(),
                'status_deleted' => 1,
            ]);

            DB::commit();

            return [
                'success' => true,
                'data' => new SupplierResource($supplier),
                'message' => 'Grifo creado exitosamente',
            ];
        } catch (Throwable $th) {
            DB::rollBack();
            Log::error('Error en SupplierService@store: '.$th->getMessage());
            throw new Exception('Error al crear el grifo: '.$th->getMessage());
        }
    }

    public function show($id)
    {
        $supplier = Supplier::where('status_deleted', 1)->findOrFail($id);

        return new SupplierResource($supplier);
    }

    public function update($data)
    {
        DB::beginTransaction();
        try {
            $supplier = Supplier::where('status_deleted', 1)->findOrFail($data['id']);

            $supplier->update([
                'name' => $data['name'] ?? $supplier->name,
                'ruc' => $data['ruc'] ?? $supplier->ruc,
                'address' => $data['address'] ?? $supplier->address,
                'phone' => $data['phone'] ?? $supplier->phone,
                'is_active' => $data['is_active'] ?? $supplier->is_active,
            ]);

            DB::commit();

            return [
                'success' => true,
                'data' => new SupplierResource($supplier->fresh()),
                'message' => 'Grifo actualizado exitosamente',
            ];
        } catch (Throwable $th) {
            DB::rollBack();
            Log::error('Error en SupplierService@update: '.$th->getMessage());
            throw new Exception('Error al actualizar el grifo: '.$th->getMessage());
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $supplier = Supplier::where('status_deleted', 1)->findOrFail($id);
            $supplier->update(['status_deleted' => 0]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Grifo eliminado exitosamente',
            ];
        } catch (Throwable $th) {
            DB::rollBack();
            Log::error('Error en SupplierService@destroy: '.$th->getMessage());
            throw new Exception('Error al eliminar el grifo: '.$th->getMessage());
        }
    }

    public function getActiveSuppliers()
    {
        return Supplier::where('status_deleted', 1)
            ->where('is_active', 1)
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}
