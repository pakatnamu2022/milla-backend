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
        // Create policy
        $policy = PerDiemPolicy::create($data);

        return $policy->fresh();
    }

    /**
     * Update policy
     */
    public function update(int $id, array $data): PerDiemPolicy
    {
        $policy = PerDiemPolicy::findOrFail($id);

        // Update policy
        $policy->update($data);

        return $policy->fresh();
    }

    /**
     * Activate policy (uses model method)
     */
    public function activate(int $id): PerDiemPolicy
    {
        $policy = PerDiemPolicy::findOrFail($id);

        // Use model method for activation
        $policy->activate();

        return $policy->fresh();
    }

    /**
     * Close policy (uses model method)
     */
    public function close(int $id, ?string $endDate = null): PerDiemPolicy
    {
        $policy = PerDiemPolicy::findOrFail($id);

        // Use model method for closing
        $policy->close($endDate ?? now()->toDateString());

        return $policy->fresh();
    }
}
