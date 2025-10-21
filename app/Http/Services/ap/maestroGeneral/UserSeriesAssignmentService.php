<?php

namespace App\Http\Services\ap\maestroGeneral;

use App\Http\Resources\ap\maestroGeneral\AssignSalesSeriesResource;
use App\Http\Resources\ap\maestroGeneral\UserSeriesAssignmentResource;
use App\Http\Services\BaseService;
use App\Models\User;
use Illuminate\Http\Request;

class UserSeriesAssignmentService extends BaseService
{
  public function list(Request $request)
  {
    $query = User::with('vouchers.sede')->whereHas('vouchers');
    $this->applyWorkerFilters($query, $request);

    $all = $request->query('all', false) === 'true';
    $perPage = $request->query('per_page', 10);

    $results = $all ? $query->get() : $query->paginate($perPage);

    return $all
      ? response()->json(UserSeriesAssignmentResource::collection($results))
      : UserSeriesAssignmentResource::collection($results);
  }

  private function applyWorkerFilters($query, $request)
  {
    if ($workerName = $request->query('nombre_completo')) {
      $query->where('nombre_completo', 'like', '%' . $workerName . '%');
    }

    if ($series = $request->query('series')) {
      $query->whereHas('vouchers', function ($q) use ($series) {
        $q->where('series', 'like', '%' . $series . '%');
      });
    }

    if ($sede = $request->query('sede')) {
      $query->whereHas('vouchers.sede', function ($q) use ($sede) {
        $q->where('abreviatura', 'like', '%' . $sede . '%');
      });
    }

    if ($search = $request->query('search')) {
      $query->where(function ($q) use ($search) {
        $q->where('nombre_completo', 'like', '%' . $search . '%')
          ->orWhereHas('vouchers', function ($vq) use ($search) {
            $vq->where('series', 'like', '%' . $search . '%');
          })
          ->orWhereHas('vouchers.sede', function ($sq) use ($search) {
            $sq->where('abreviatura', 'like', '%' . $search . '%');
          });
      });
    }
  }

  public function store(Mixed $data)
  {
    $worker = User::findOrFail($data['worker_id']);
    $worker->vouchers()->sync($data['vouchers']);
    $worker->load('vouchers');
    return new UserSeriesAssignmentResource($worker);
  }

  public function show($id)
  {
    $worker = User::with('vouchers')->findOrFail($id);
    return new UserSeriesAssignmentResource($worker);
  }

  public function update(Mixed $data)
  {
    $workerId = $data['worker_id'];
    $worker = User::with('vouchers')->findOrFail($workerId);
    $worker->vouchers()->sync($data['vouchers']);
    $worker->load('vouchers');

    return new UserSeriesAssignmentResource($worker);
  }

  public function getAuthorizedSeries(Request $request)
  {
    $user = auth()->user();

    if (!$user) {
      return response()->json(['message' => 'Usuario no autenticado'], 401);
    }

    $query = $user->vouchers()
      ->with(['typeReceipt', 'typeOperation', 'sede']);

    // Filtrar por tipo de comprobante
    if ($typeReceiptId = $request->query('type_receipt_id')) {
      $query->where('assign_sales_series.type_receipt_id', $typeReceiptId);
    }

    // Filtrar por tipo de operación
    if ($typeOperationId = $request->query('type_operation_id')) {
      $query->where('assign_sales_series.type_operation_id', $typeOperationId);
    }

    // Filtrar por sede
    if ($sedeId = $request->query('sede_id')) {
      $query->where('assign_sales_series.sede_id', $sedeId);
    }

    $series = $query->get();

    return response()->json(AssignSalesSeriesResource::collection($series));
  }
}
