<?php

namespace App\Http\Services\ap\configuracionComercial\venta;

use App\Http\Resources\ap\configuracionComercial\venta\ApCampaignResource;
use App\Http\Services\BaseService;
use App\Models\ap\configuracionComercial\venta\ApCampaign;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApCampaignService extends BaseService
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      ApCampaign::class,
      $request,
      ApCampaign::filters,
      ApCampaign::sorts,
      ApCampaignResource::class
    );
  }

  private function find(int $id): ApCampaign
  {
    $campaign = ApCampaign::with('area')->find($id);
    if (!$campaign) {
      throw new Exception('Campaña no encontrada');
    }
    return $campaign;
  }

  public function store(mixed $data): ApCampaignResource
  {
    DB::beginTransaction();
    try {
      $campaign = ApCampaign::create($data);
      $campaign->load('area');
      DB::commit();
      return new ApCampaignResource($campaign);
    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function show(int $id): ApCampaignResource
  {
    return new ApCampaignResource($this->find($id));
  }

  public function update(mixed $data): ApCampaignResource
  {
    $campaign = $this->find($data['id']);
    DB::beginTransaction();
    try {
      $campaign->update($data);
      $campaign->load('area');
      DB::commit();
      return new ApCampaignResource($campaign);
    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function destroy(int $id)
  {
    $campaign = $this->find($id);
    $campaign->delete();
    return response()->json(['message' => 'Campaña eliminada correctamente']);
  }

  public function active(): ApCampaignResource
  {
    $campaign = ApCampaign::with('area')->active()->first();
    if (!$campaign) {
      throw new Exception('No hay ninguna campaña activa en este momento');
    }
    return new ApCampaignResource($campaign);
  }
}
