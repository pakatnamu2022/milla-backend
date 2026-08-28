<?php

namespace App\Http\Services\ap\postventa\repuestos;

use App\Http\Resources\ap\postventa\repuestos\ApprovedAccessoriesResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Http\Utils\AccessoryCodeGenerator;
use App\Models\ap\postventa\repuestos\ApprovedAccessories;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApprovedAccessoriesService extends BaseService implements BaseServiceInterface
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      ApprovedAccessories::with(['prices.bodyType', 'typeCurrency', 'typeOperation']),
      $request,
      ApprovedAccessories::filters,
      ApprovedAccessories::sorts,
      ApprovedAccessoriesResource::class,
    );
  }

  public function find($id)
  {
    $approvedAccessory = ApprovedAccessories::with(['prices.bodyType', 'typeCurrency', 'typeOperation'])
      ->where('id', $id)
      ->first();

    if (!$approvedAccessory) {
      throw new Exception('Accesorio Homologado no encontrado');
    }

    return $approvedAccessory;
  }

  public function store(mixed $data)
  {
    return DB::transaction(function () use ($data) {
      $bodyTypeIds = collect($data['prices'])->pluck('body_type_id')->map(fn ($id) => (int) $id)->all();

      $approvedAccessory = ApprovedAccessories::create([
        'code'              => AccessoryCodeGenerator::generate($data['description'], $bodyTypeIds),
        'description'       => $data['description'],
        'type_operation_id' => $data['type_operation_id'],
        'type_currency_id'  => $data['type_currency_id'],
        'status'            => $data['status'] ?? true,
      ]);

      $this->syncPrices($approvedAccessory, $data['prices']);

      return new ApprovedAccessoriesResource($this->find($approvedAccessory->id));
    });
  }

  public function show($id)
  {
    return new ApprovedAccessoriesResource($this->find($id));
  }

  public function update(mixed $data)
  {
    return DB::transaction(function () use ($data) {
      $approvedAccessory = $this->find($data['id']);

      $description = $data['description'] ?? $approvedAccessory->description;
      $prices = $data['prices'] ?? $approvedAccessory->prices
        ->map(fn ($p) => ['body_type_id' => $p->body_type_id, 'price' => $p->price])
        ->all();
      $bodyTypeIds = collect($prices)->pluck('body_type_id')->map(fn ($id) => (int) $id)->all();

      $approvedAccessory->update([
        'code'              => AccessoryCodeGenerator::generate($description, $bodyTypeIds, $approvedAccessory->id),
        'description'       => $description,
        'type_operation_id' => $data['type_operation_id'] ?? $approvedAccessory->type_operation_id,
        'type_currency_id'  => $data['type_currency_id'] ?? $approvedAccessory->type_currency_id,
        'status'            => $data['status'] ?? $approvedAccessory->status,
      ]);

      $this->syncPrices($approvedAccessory, $prices);

      return new ApprovedAccessoriesResource($this->find($approvedAccessory->id));
    });
  }

  public function destroy($id)
  {
    $approvedAccessory = $this->find($id);
    DB::transaction(function () use ($approvedAccessory) {
      $approvedAccessory->delete();
    });
    return response()->json(['message' => 'Accesorio Homologado eliminado correctamente']);
  }

  /**
   * Reemplaza las filas de precio del accesorio con las del payload:
   * crea las nuevas, actualiza las existentes y elimina las que ya no vienen.
   *
   * @param  array<int, array{body_type_id: int|string, price: int|float|string}>  $prices
   */
  private function syncPrices(ApprovedAccessories $approvedAccessory, array $prices): void
  {
    $keptIds = [];

    foreach ($prices as $price) {
      $row = $approvedAccessory->prices()->updateOrCreate(
        ['body_type_id' => (int) $price['body_type_id']],
        ['price' => $price['price']],
      );
      $keptIds[] = $row->id;
    }

    $approvedAccessory->prices()->whereNotIn('id', $keptIds)->delete();
  }
}
