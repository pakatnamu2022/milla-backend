<?php

namespace App\Services\gp\gestionhumana\viaticos;

use App\Models\gp\gestionhumana\viaticos\PerDiemPolicy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class PerDiemPolicyService
{
    /**
     * Get all policies
     */
    public function getAll(): Collection
    {
        return PerDiemPolicy::withCount('rates')
            ->orderBy('start_date', 'desc')
            ->get();
    }

    /**
     * Get current active policy
     */
    public function getCurrent(): ?PerDiemPolicy
    {
        return PerDiemPolicy::where('is_current', true)
            ->with(['rates.expenseType', 'rates.category', 'rates.district'])
            ->first();
    }

    /**
     * Create new policy
     */
    public function create(array $data): PerDiemPolicy
    {
        $policy = PerDiemPolicy::create([
            'version' => $data['version'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'] ?? null,
            'is_current' => $data['is_current'] ?? false,
        ]);

        // If this policy is set as current, deactivate others
        if ($policy->is_current) {
            $this->activate($policy->id);
        }

        return $policy->fresh();
    }

    /**
     * Update policy
     */
    public function update(int $id, array $data): PerDiemPolicy
    {
        $policy = PerDiemPolicy::findOrFail($id);

        $policy->update([
            'version' => $data['version'] ?? $policy->version,
            'name' => $data['name'] ?? $policy->name,
            'description' => $data['description'] ?? $policy->description,
            'start_date' => $data['start_date'] ?? $policy->start_date,
            'end_date' => $data['end_date'] ?? $policy->end_date,
        ]);

        // If is_current is being updated to true, activate this policy
        if (isset($data['is_current']) && $data['is_current'] === true) {
            $this->activate($policy->id);
        }

        return $policy->fresh();
    }

    /**
     * Activate policy and deactivate others
     */
    public function activate(int $id): PerDiemPolicy
    {
        try {
            DB::beginTransaction();

            // Deactivate all other policies
            PerDiemPolicy::where('id', '!=', $id)
                ->update(['is_current' => false]);

            // Activate this policy
            $policy = PerDiemPolicy::findOrFail($id);
            $policy->update(['is_current' => true]);

            DB::commit();

            return $policy->fresh();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Close policy with end date
     */
    public function close(int $id, ?string $endDate = null): PerDiemPolicy
    {
        $policy = PerDiemPolicy::findOrFail($id);

        $policy->update([
            'end_date' => $endDate ?? now()->toDateString(),
            'is_current' => false,
        ]);

        return $policy->fresh();
    }
}
