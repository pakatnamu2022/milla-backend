<?php

namespace App\Http\Services\gp\gestionhumana\contratos;

use App\Http\Resources\gp\gestionhumana\contratos\ContractTypeResource;
use App\Http\Services\BaseService;
use App\Http\Services\common\ExportService;
use App\Models\gp\gestionhumana\contratos\ContractType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContractTypeService extends BaseService
{
  public function __construct(private ExportService $exportService) {}

  public function list(Request $request): JsonResponse
  {
    return $this->getFilteredResults(
      ContractType::query(),
      $request,
      ContractType::filters,
      ContractType::sorts,
      ContractTypeResource::class,
    );
  }

  public function show(int $id): ContractTypeResource
  {
    return new ContractTypeResource(ContractType::findOrFail($id));
  }

  public function store(array $data): ContractTypeResource
  {
    return DB::transaction(function () use ($data) {
      $contractType = ContractType::create([
        ...$data,
        'status_id'      => 1,
        'write_id'       => auth()->id(),
        'status_deleted' => 1,
      ]);

      return $this->show($contractType->id);
    });
  }

  public function update(int $id, array $data): ContractTypeResource
  {
    $contractType = ContractType::findOrFail($id);
    $contractType->update($data);

    return $this->show($contractType->id);
  }

  public function destroy(int $id): void
  {
    $contractType = ContractType::findOrFail($id);
    $contractType->update(['status_deleted' => 0]);
  }

  public function export(Request $request)
  {
    return $this->exportService->exportFromRequest($request, ContractType::class);
  }
}
