<?php

namespace App\Http\Services\ap\configuracionComercial\venta;

use App\Http\Resources\ap\configuracionComercial\venta\ApAssignCompanyBranchResource;
use App\Http\Services\BaseService;
use App\Http\Utils\Constants;
use App\Models\ap\configuracionComercial\venta\ApAssignCompanyBranchPeriod;
use App\Models\gp\gestionsistema\CompanyBranch;
use Illuminate\Http\Request;
use Exception;

class ApAssignCompanyBranchService extends BaseService
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      CompanyBranch::with('workers')
        ->whereNotNull('company_id')
        ->where('company_id', Constants::COMPANY_AP),
      $request,
      CompanyBranch::filters,
      CompanyBranch::sorts,
      ApAssignCompanyBranchResource::class
    );
  }

  public function listRecord(Request $request)
  {
    $year = $request->query('year');
    $month = $request->query('month');
    $company_branch = $request->query('company_branch');

    $query = ApAssignCompanyBranchPeriod::with(['companyBranch', 'worker'])
      ->when($year, fn($q) => $q->where('year', $year))
      ->when($month, fn($q) => $q->where('month', $month))
      ->when($company_branch, function ($q) use ($company_branch) {
        $q->whereHas('companyBranch', function ($sub) use ($company_branch) {
          $sub->where('abbreviation', 'like', "%{$company_branch}%");
        });
      });

    $periodos = $query->get();

    $grouped = $periodos->groupBy('company_branch_id')->map(function ($items) {
      $first = $items->first();

      return [
        'company_branch_id' => $first->company_branch_id,
        'company_branch' => $first->companyBranch->abbreviation,
        'year' => $first->year,
        'month' => $first->month,
        'workers' => $items->map(function ($item) {
          return [
            'id' => $item->worker->id,
            'name' => $item->worker->nombre_completo,
          ];
        })->values(),
      ];
    })->values();

    return response()->json(['data' => $grouped]);
  }

  public function show($id)
  {
    $sede = CompanyBranch::with('workers')->find($id);
    if (!$sede) {
      throw new Exception('Sede no encontrada');
    }
    return new ApAssignCompanyBranchResource($sede);
  }

  public function store(array $data)
  {
    $sede = CompanyBranch::findOrFail($data['company_branch_id']);
    $sede->workers()->sync($data['workers']);
    return new ApAssignCompanyBranchResource($sede->load('workers'));
  }

  public function update(mixed $data)
  {
    $sede = CompanyBranch::findOrFail($data['company_branch_id']);
    $sede->workers()->sync($data['workers']);

    if (isset($data['year']) && isset($data['month'])) {
      $existing = ApAssignCompanyBranchPeriod::withTrashed()
        ->where('company_branch_id', $data['company_branch_id'])
        ->where('year', $data['year'])
        ->where('month', $data['month'])
        ->get()
        ->keyBy('worker_id');

      $newAsesores = collect($data['workers'])->mapWithKeys(fn($id) => [$id => $id]);

      foreach ($newAsesores as $asesorId) {
        if ($existing->has($asesorId)) {
          $record = $existing[$asesorId];
          if ($record->trashed()) {
            $record->restore();
          }
        } else {
          ApAssignCompanyBranchPeriod::create([
            'company_branch_id' => $data['company_branch_id'],
            'worker_id' => $asesorId,
            'year' => $data['year'],
            'month' => $data['month'],
          ]);
        }
      }

      $toDelete = $existing->keys()->diff($newAsesores->keys());
      if ($toDelete->isNotEmpty()) {
        ApAssignCompanyBranchPeriod::where('company_branch_id', $data['company_branch_id'])
          ->where('year', $data['year'])
          ->where('month', $data['month'])
          ->whereIn('worker_id', $toDelete)
          ->delete();
      }
    }

    return new ApAssignCompanyBranchResource($sede->load('workers'));
  }
}
