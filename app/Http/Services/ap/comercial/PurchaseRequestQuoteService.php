<?php

namespace App\Http\Services\ap\comercial;

use App\Http\Resources\ap\comercial\PurchaseRequestQuoteResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\comercial\PurchaseRequestQuote;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseRequestQuoteService extends BaseService implements BaseServiceInterface
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      PurchaseRequestQuote::class,
      $request,
      PurchaseRequestQuote::filters,
      PurchaseRequestQuote::sorts,
      PurchaseRequestQuoteResource::class,
    );
  }

  public function find($id)
  {
    $PurchaseRequestQuote = PurchaseRequestQuote::where('id', $id)->first();
    if (!$PurchaseRequestQuote) {
      throw new Exception('Registro no encontrado');
    }
    return $PurchaseRequestQuote;
  }

  public function store(mixed $data)
  {
    $data['ap_vehicle_status_id'] = 28;
    $PurchaseRequestQuote = PurchaseRequestQuote::create($data);
    return new PurchaseRequestQuoteResource($PurchaseRequestQuote);
  }

  public function show($id)
  {
    return new PurchaseRequestQuoteResource($this->find($id));
  }

  public function update(mixed $data)
  {
    $PurchaseRequestQuote = $this->find($data['id']);
    $PurchaseRequestQuote->update($data);
    return new PurchaseRequestQuoteResource($PurchaseRequestQuote);
  }

  public function destroy($id)
  {
    $PurchaseRequestQuote = $this->find($id);
    DB::transaction(function () use ($PurchaseRequestQuote) {
      $PurchaseRequestQuote->delete();
    });
    return response()->json(['message' => 'Registro eliminado correctamente']);
  }
}
