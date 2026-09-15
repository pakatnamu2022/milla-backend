<?php

namespace App\Http\Services\gp\gestionhumana\contratos;

use App\Http\Resources\gp\gestionhumana\contratos\ContractTemplateResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionhumana\contratos\ContractTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContractTemplateService extends BaseService
{
  public function list(Request $request): JsonResponse
  {
    return $this->getFilteredResults(
      ContractTemplate::query(),
      $request,
      ContractTemplate::filters,
      ContractTemplate::sorts,
      ContractTemplateResource::class,
    );
  }

  public function show(int $id): ContractTemplateResource
  {
    return new ContractTemplateResource(ContractTemplate::findOrFail($id));
  }

  public function store(array $data): ContractTemplateResource
  {
    return DB::transaction(function () use ($data) {
      $template = ContractTemplate::create([
        ...$data,
        'write_id'       => auth()->id(),
        'status_deleted' => 1,
      ]);

      return $this->show($template->id);
    });
  }

  public function update(int $id, array $data): ContractTemplateResource
  {
    $template = ContractTemplate::findOrFail($id);
    $template->update($data);

    return $this->show($template->id);
  }

  public function destroy(int $id): void
  {
    $template = ContractTemplate::findOrFail($id);
    $template->update(['status_deleted' => 0]);
  }
}
