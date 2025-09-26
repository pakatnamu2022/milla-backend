<?php

namespace App\Http\Services\ap\configuracionComercial\vehiculo;

use App\Http\Resources\ap\configuracionComercial\vehiculo\ApDeliveryReceivingChecklistResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\configuracionComercial\vehiculo\ApDeliveryReceivingChecklist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class ApDeliveryReceivingChecklistService extends BaseService implements BaseServiceInterface
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      ApDeliveryReceivingChecklist::class,
      $request,
      ApDeliveryReceivingChecklist::filters,
      ApDeliveryReceivingChecklist::sorts,
      ApDeliveryReceivingChecklistResource::class,
    );
  }

  public function find($id)
  {
    $DeliveryReceivingChecklist = ApDeliveryReceivingChecklist::where('id', $id)->first();
    if (!$DeliveryReceivingChecklist) {
      throw new Exception('Item de entrega / recepción no encontrado');
    }
    return $DeliveryReceivingChecklist;
  }

  public function store(mixed $data)
  {
    $DeliveryReceivingChecklist = ApDeliveryReceivingChecklist::create($data);
    return new ApDeliveryReceivingChecklistResource($DeliveryReceivingChecklist);
  }

  public function show($id)
  {
    return new ApDeliveryReceivingChecklistResource($this->find($id));
  }

  public function update(mixed $data)
  {
    $DeliveryReceivingChecklist = $this->find($data['id']);
    $DeliveryReceivingChecklist->update($data);
    return new ApDeliveryReceivingChecklistResource($DeliveryReceivingChecklist);
  }

  public function destroy($id)
  {
    $DeliveryReceivingChecklist = $this->find($id);
    DB::transaction(function () use ($DeliveryReceivingChecklist) {
      $DeliveryReceivingChecklist->delete();
    });
    return response()->json(['message' => 'Item de entrega / recepción eliminado correctamente']);
  }
}
