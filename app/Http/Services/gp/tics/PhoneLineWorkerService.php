<?php

namespace App\Http\Services\gp\tics;

use App\Http\Resources\gp\tics\PhoneLineWorkerResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\gp\tics\PhoneLineWorker;
use Exception;
use Illuminate\Http\Request;

class PhoneLineWorkerService extends BaseService implements BaseServiceInterface
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      PhoneLineWorker::query(),
      $request,
      PhoneLineWorker::filters,
      PhoneLineWorker::sorts,
      PhoneLineWorkerResource::class,
    );
  }

  public function history($phoneLineId)
  {
    $assignments = PhoneLineWorker::where('phone_line_id', $phoneLineId)
      ->orderBy('assigned_at', 'desc')
      ->get();
    return PhoneLineWorkerResource::collection($assignments);
  }

  public function store($data)
  {
    PhoneLineWorker::where('phone_line_id', $data['phone_line_id'])
      ->where('active', true)
      ->update(['active' => false]);

    $phoneLineWorker = PhoneLineWorker::create($data);
    return new PhoneLineWorkerResource(PhoneLineWorker::find($phoneLineWorker->id));
  }

  public function find($id)
  {
    $phoneLineWorker = PhoneLineWorker::where('id', $id)->first();
    if (!$phoneLineWorker) {
      throw new Exception('Asignación de línea telefónica no encontrada');
    }
    return $phoneLineWorker;
  }

  public function show($id)
  {
    return new PhoneLineWorkerResource($this->find($id));
  }

  public function update($data)
  {
    $phoneLineWorker = $this->find($data['id']);
    $phoneLineWorker->update($data);
    return new PhoneLineWorkerResource($phoneLineWorker);
  }

  public function destroy($id)
  {
    $phoneLineWorker = $this->find($id);
    $phoneLineWorker->delete();
    return response()->json(['message' => 'Asignación eliminada correctamente']);
  }
}
