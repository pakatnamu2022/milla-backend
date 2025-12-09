<?php

namespace App\Http\Controllers\gp\gestionhumana\viaticos;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\viaticos\IndexPerDiemPolicyRequest;
use App\Http\Requests\gp\gestionhumana\viaticos\StorePerDiemPolicyRequest;
use App\Http\Requests\gp\gestionhumana\viaticos\UpdatePerDiemPolicyRequest;
use App\Http\Resources\gp\gestionhumana\viaticos\PerDiemPolicyResource;
use App\Models\gp\gestionhumana\viaticos\PerDiemPolicy;
use App\Services\gp\gestionhumana\viaticos\PerDiemPolicyService;
use Illuminate\Http\Request;

class PerDiemPolicyController extends Controller
{
    protected $service;

    public function __construct(PerDiemPolicyService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of policies
     */
    public function index(IndexPerDiemPolicyRequest $request)
    {
        try {
            $policies = $this->service->getAll();

            return response()->json([
                'success' => true,
                'data' => PerDiemPolicyResource::collection($policies)
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Store a newly created policy
     */
    public function store(StorePerDiemPolicyRequest $request)
    {
        try {
            $data = $request->validated();
            $policy = $this->service->create($data);

            return response()->json([
                'success' => true,
                'data' => new PerDiemPolicyResource($policy),
                'message' => 'Política creada exitosamente'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Display the specified policy
     */
    public function show(int $id)
    {
        try {
            $policy = PerDiemPolicy::with(['perDiemRates.expenseType', 'perDiemRates.category', 'perDiemRates.district'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => new PerDiemPolicyResource($policy)
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Update the specified policy
     */
    public function update(UpdatePerDiemPolicyRequest $request, int $id)
    {
        try {
            $data = $request->validated();
            $policy = $this->service->update($id, $data);

            return response()->json([
                'success' => true,
                'data' => new PerDiemPolicyResource($policy),
                'message' => 'Política actualizada exitosamente'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Remove the specified policy
     */
    public function destroy(int $id)
    {
        try {
            PerDiemPolicy::findOrFail($id)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Política eliminada exitosamente'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get current active policy
     */
    public function current()
    {
        try {
            $policy = $this->service->getCurrent();

            if (!$policy) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay una política activa'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => new PerDiemPolicyResource($policy)
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Activate a policy
     */
    public function activate(int $id)
    {
        try {
            $policy = $this->service->activate($id);

            return response()->json([
                'success' => true,
                'data' => new PerDiemPolicyResource($policy),
                'message' => 'Política activada exitosamente'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Close a policy
     */
    public function close(int $id, Request $request)
    {
        try {
            $endDate = $request->input('end_date');
            $policy = $this->service->close($id, $endDate);

            return response()->json([
                'success' => true,
                'data' => new PerDiemPolicyResource($policy),
                'message' => 'Política cerrada exitosamente'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
