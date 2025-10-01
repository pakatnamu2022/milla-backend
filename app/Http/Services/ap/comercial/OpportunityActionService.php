<?php

namespace App\Http\Services\ap\comercial;

use App\Http\Resources\ap\comercial\OpportunityActionResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\comercial\OpportunityAction;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OpportunityActionService extends BaseService implements BaseServiceInterface
{
    public function list(Request $request)
    {
        return $this->getFilteredResults(
            OpportunityAction::class,
            $request,
            OpportunityAction::filters,
            OpportunityAction::sorts,
            OpportunityActionResource::class,
        );
    }

    public function find($id)
    {
        $action = OpportunityAction::where('id', $id)->first();
        if (!$action) {
            throw new Exception('Acción no encontrada');
        }
        return $action;
    }

    public function store(mixed $data)
    {
        DB::beginTransaction();
        try {
            $action = OpportunityAction::create($data);
            DB::commit();
            return new OpportunityActionResource($action);
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage());
        }
    }

    public function show($id)
    {
        return new OpportunityActionResource($this->find($id));
    }

    public function update(mixed $data)
    {
        DB::beginTransaction();
        try {
            $action = $this->find($data['id']);
            $action->update($data);
            DB::commit();
            return new OpportunityActionResource($action);
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage());
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $action = $this->find($id);
            $action->delete();
            DB::commit();
            return response()->json(['message' => 'Acción eliminada correctamente']);
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage());
        }
    }
}