<?php

namespace App\Http\Services\gp\gestionhumana\viaticos;

use App\Http\Services\BaseService;
use App\Http\Resources\gp\gestionhumana\viaticos\PerDiemPolicyResource;
use App\Models\gp\gestionhumana\viaticos\PerDiemPolicy;
use Illuminate\Http\Request;

class PerDiemPolicyService extends BaseService
{
    public function index(Request $request)
    {
        return $this->getFilteredResults(
            PerDiemPolicy::query(),
            $request,
            PerDiemPolicy::filters ?? ['search' => ['name'], 'is_current' => '='],
            PerDiemPolicy::sorts ?? ['effective_from', 'name'],
            PerDiemPolicyResource::class,
        );
    }

    public function show(int $id): ?PerDiemPolicy
    {
        return PerDiemPolicy::find($id);
    }

    public function store(array $data): PerDiemPolicy
    {
        return PerDiemPolicy::create($data);
    }

    public function update(int $id, array $data): PerDiemPolicy
    {
        $policy = PerDiemPolicy::findOrFail($id);
        $policy->update($data);
        return $policy->fresh();
    }

    public function destroy(int $id): bool
    {
        $policy = PerDiemPolicy::findOrFail($id);

        if ($policy->is_current) {
            throw new \Exception('No se puede eliminar la política activa.');
        }

        if ($policy->perDiemRequests()->exists()) {
            throw new \Exception('No se puede eliminar la política porque tiene solicitudes asociadas.');
        }

        return $policy->delete();
    }

    public function current(): ?PerDiemPolicy
    {
        return PerDiemPolicy::where('is_current', true)->first();
    }

    public function activate(int $id): PerDiemPolicy
    {
        // Desactivar todas las políticas actuales
        PerDiemPolicy::where('is_current', true)->update(['is_current' => false]);

        // Activar la política seleccionada
        $policy = PerDiemPolicy::findOrFail($id);
        $policy->update(['is_current' => true]);

        return $policy->fresh();
    }

    public function close(int $id, array $data): PerDiemPolicy
    {
        $policy = PerDiemPolicy::findOrFail($id);

        if (!$policy->is_current) {
            throw new \Exception('Solo se puede cerrar la política activa.');
        }

        $policy->update([
            'effective_to' => $data['end_date'],
            'is_current' => false,
        ]);

        return $policy->fresh();
    }
}
