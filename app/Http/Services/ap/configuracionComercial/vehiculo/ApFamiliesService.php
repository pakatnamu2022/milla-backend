<?php

namespace App\Http\Services\ap\configuracionComercial\vehiculo;

use App\Http\Resources\ap\configuracionComercial\vehiculo\ApFamiliesResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\ApMasters;
use App\Models\ap\configuracionComercial\vehiculo\ApFamilies;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleBrand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class ApFamiliesService extends BaseService implements BaseServiceInterface
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      ApFamilies::class,
      $request,
      ApFamilies::filters,
      ApFamilies::sorts,
      ApFamiliesResource::class,
    );
  }

  public function find($id)
  {
    $family = ApFamilies::where('id', $id)->first();
    if (!$family) {
      throw new Exception('Familia no encontrado');
    }
    return $family;
  }

  public function store(mixed $data)
  {
    $marca = ApVehicleBrand::findOrFail($data['brand_id']);
    $data['code'] = $marca->dyn_code . $this->nextCorrelativeCount(
        ApFamilies::class,
        2,
        ['brand_id' => $data['brand_id']]
      );

    if ($marca->type_operation_id === ApMasters::TIPO_OPERACION_COMERCIAL) {
      $family = ApFamilies::where('description', $data['description'])->where('brand_id', $data['brand_id'])->first();
      if ($family) {
        throw new Exception('Ya existe una familia con la misma descripción para esta marca');
      }
    }

    $family = ApFamilies::create($data);
    return new ApFamiliesResource($family);
  }

  public function show($id)
  {
    return new ApFamiliesResource($this->find($id));
  }

  public function completeBrandSeries($familyId): string
  {
    $family = $this->find($familyId);
    $familySeries = $family->code;
    $brandSeries = $family->brand->dyn_code;

    if (strlen($familySeries) === 4 && str_contains($familySeries, $brandSeries) && false) {
      return $familySeries;
    } else if (strlen($familySeries) === 2) {
      return $this->completeNumber($brandSeries . $familySeries, 4);
    } else {
      return $this->completeNumber(str($this->nextCorrelativeQuery(
        ApFamilies::where('brand_id', $family->brand_id),
        'code',
        2
      )), 4);
    }
  }

  public function update(mixed $data)
  {
    $family = $this->find($data['id']);
    $data['code'] = $this->completeBrandSeries($family->id);
    $marca = $family->brand;
    if ($marca->type_operation_id === ApMasters::TIPO_OPERACION_COMERCIAL) {
      $family = ApFamilies::where('description', $data['description'])->where('brand_id', $data['brand_id'])->first();
      if ($family) {
        throw new Exception('Ya existe una familia con la misma descripción para esta marca');
      }
    }
    $family->update($data);
    return new ApFamiliesResource($family);
  }

  public function fixWrongCodes(): array
  {
    $families = ApFamilies::with('brand')->get();

    $fixed = [];
    $skipped = [];

    foreach ($families as $family) {
      $brand = $family->brand;
      if (!$brand || !$brand->dyn_code) {
        $skipped[] = ['id' => $family->id, 'code' => $family->code, 'reason' => 'Marca o dyn_code no encontrado'];
        continue;
      }

      $expectedPrefix = strtoupper($brand->dyn_code);
      $currentCode    = $family->code;

      $prefixOk = str_starts_with($currentCode, $expectedPrefix);
      $lengthOk = strlen($currentCode) === 4;
      $isDuplicated = ApFamilies::where('code', $currentCode)
        ->where('id', '!=', $family->id)
        ->exists();

      if ($prefixOk && $lengthOk && !$isDuplicated) {
        continue;
      }

      $reason = !$prefixOk ? 'prefijo de marca incorrecto' : (!$lengthOk ? 'longitud incorrecta' : 'código duplicado');

      DB::table('ap_families')->where('id', $family->id)->update(['code' => '__FIXING__' . $family->id]);

      $lastCode = ApFamilies::where('brand_id', $family->brand_id)
        ->where('code', 'not like', '__FIXING__%')
        ->whereRaw('LENGTH(code) = 4')
        ->orderBy('code', 'desc')
        ->value('code');

      $nextSuffix = $lastCode ? (int) substr($lastCode, 2) + 1 : 1;
      $newCode = strtoupper($brand->dyn_code . str_pad($nextSuffix, 2, '0', STR_PAD_LEFT));

      DB::table('ap_families')->where('id', $family->id)->update(['code' => $newCode]);

      $fixed[] = [
        'id'          => $family->id,
        'description' => $family->description,
        'reason'      => $reason,
        'old_code'    => $currentCode,
        'new_code'    => $newCode,
      ];
    }

    return [
      'fixed'   => count($fixed),
      'skipped' => count($skipped),
      'details' => $fixed,
      'errors'  => $skipped,
    ];
  }

  public function destroy($id)
  {
    $family = $this->find($id);
    DB::transaction(function () use ($family) {
      $family->delete();
    });
    return response()->json(['message' => 'Familia eliminado correctamente']);
  }
}
