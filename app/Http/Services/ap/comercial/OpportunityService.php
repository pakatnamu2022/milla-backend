<?php

namespace App\Http\Services\ap\comercial;

use App\Http\Resources\ap\comercial\OpportunityResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\comercial\Opportunity;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OpportunityService extends BaseService implements BaseServiceInterface
{
    public function list(Request $request)
    {
        return $this->getFilteredResults(
            Opportunity::class,
            $request,
            Opportunity::filters,
            Opportunity::sorts,
            OpportunityResource::class,
        );
    }

    public function find($id)
    {
        $opportunity = Opportunity::where('id', $id)->first();
        if (!$opportunity) {
            throw new Exception('Oportunidad no encontrada');
        }
        return $opportunity;
    }

    public function store(mixed $data)
    {
        DB::beginTransaction();
        try {
            $opportunity = Opportunity::create($data);
            DB::commit();
            return new OpportunityResource($opportunity);
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage());
        }
    }

    public function show($id)
    {
        return new OpportunityResource($this->find($id));
    }

    public function update(mixed $data)
    {
        DB::beginTransaction();
        try {
            $opportunity = $this->find($data['id']);
            $opportunity->update($data);
            DB::commit();
            return new OpportunityResource($opportunity);
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage());
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $opportunity = $this->find($id);
            $opportunity->delete();
            DB::commit();
            return response()->json(['message' => 'Oportunidad eliminada correctamente']);
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage());
        }
    }
}