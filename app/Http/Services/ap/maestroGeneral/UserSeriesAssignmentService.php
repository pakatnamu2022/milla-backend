<?php

namespace App\Http\Services\ap\maestroGeneral;

use App\Http\Resources\ap\maestroGeneral\AssignSalesSeriesResource;
use App\Http\Resources\ap\maestroGeneral\UserSeriesAssignmentResource;
use App\Http\Services\BaseService;
use App\Models\ap\maestroGeneral\UserSeriesAssignment;
use App\Models\User;
use Illuminate\Http\Request;

class UserSeriesAssignmentService extends BaseService
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      UserSeriesAssignment::query()
        ->with(['user.person', 'vouchers.sede', 'vouchers.typeReceipt', 'vouchers.typeOperation'])
        ->whereIn('id', function ($q) {
          $q->selectRaw('MIN(id)')
            ->from('user_series_assignment')
            ->whereNull('deleted_at')
            ->groupBy('worker_id');
        }),
      $request,
      UserSeriesAssignment::filters,
      UserSeriesAssignment::sorts,
      UserSeriesAssignmentResource::class
    );
  }

  public function store(Mixed $data)
  {
    $worker = User::findOrFail($data['worker_id']);
    $worker->vouchers()->sync($data['vouchers']);
    $assignment = UserSeriesAssignment::with(['user.person', 'vouchers.sede', 'vouchers.typeReceipt', 'vouchers.typeOperation'])
      ->where('worker_id', $data['worker_id'])
      ->firstOrFail();
    return new UserSeriesAssignmentResource($assignment);
  }

  public function show($id)
  {
    $assignment = UserSeriesAssignment::with(['user.person', 'vouchers.sede', 'vouchers.typeReceipt', 'vouchers.typeOperation'])
      ->where('worker_id', $id)
      ->firstOrFail();
    return new UserSeriesAssignmentResource($assignment);
  }

  public function update(Mixed $data)
  {
    $worker = User::findOrFail($data['worker_id']);
    $worker->vouchers()->sync($data['vouchers']);
    $assignment = UserSeriesAssignment::with(['user.person', 'vouchers.sede', 'vouchers.typeReceipt', 'vouchers.typeOperation'])
      ->where('worker_id', $data['worker_id'])
      ->firstOrFail();
    return new UserSeriesAssignmentResource($assignment);
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
