<?php

namespace App\Http\Services\ap\marketing;

use App\Http\Resources\ap\marketing\MktProposalResource;
use App\Http\Services\BaseService;
use App\Models\ap\marketing\MktProposal;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MktProposalService extends BaseService
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      MktProposal::class,
      $request,
      MktProposal::filters,
      MktProposal::sorts,
      MktProposalResource::class
    );
  }

  private function find(int $id): MktProposal
  {
    $proposal = MktProposal::with(['activity', 'supplier', 'currency', 'reviewedBy'])->find($id);
    if (!$proposal) {
      throw new Exception('Propuesta no encontrada');
    }
    return $proposal;
  }

  public function store(mixed $data): MktProposalResource
  {
    DB::beginTransaction();
    try {
      $proposal = MktProposal::create($data);
      $proposal->load(['activity', 'supplier', 'currency']);
      DB::commit();
      return new MktProposalResource($proposal);
    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function show(int $id): MktProposalResource
  {
    return new MktProposalResource($this->find($id));
  }

  public function update(mixed $data): MktProposalResource
  {
    $proposal = $this->find($data['id']);
    DB::beginTransaction();
    try {
      $proposal->update($data);
      $proposal->load(['activity', 'supplier', 'currency']);
      DB::commit();
      return new MktProposalResource($proposal);
    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function destroy(int $id)
  {
    $proposal = $this->find($id);
    $proposal->delete();
    return response()->json(['message' => 'Propuesta eliminada correctamente']);
  }

  public function approve(int $id): MktProposalResource
  {
    $proposal = $this->find($id);
    if ($proposal->status !== MktProposal::STATUS_PENDING) {
      throw new Exception('Solo se pueden aprobar propuestas en estado pendiente');
    }
    DB::beginTransaction();
    try {
      $proposal->update([
        'status'      => MktProposal::STATUS_APPROVED,
        'reviewed_by' => Auth::id(),
        'reviewed_at' => now(),
      ]);
      DB::commit();
      return new MktProposalResource($proposal);
    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function reject(int $id, ?string $notes = null): MktProposalResource
  {
    $proposal = $this->find($id);
    if ($proposal->status !== MktProposal::STATUS_PENDING) {
      throw new Exception('Solo se pueden rechazar propuestas en estado pendiente');
    }
    DB::beginTransaction();
    try {
      $proposal->update([
        'status'      => MktProposal::STATUS_REJECTED,
        'reviewed_by' => Auth::id(),
        'reviewed_at' => now(),
        'notes'       => $notes ?? $proposal->notes,
      ]);
      DB::commit();
      return new MktProposalResource($proposal);
    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }
}
