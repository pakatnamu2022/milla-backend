<?php

namespace App\Http\Controllers\ap\comercial;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\comercial\IndexApReceivingChecklistRequest;
use App\Http\Requests\ap\comercial\StoreApReceivingChecklistRequest;
use App\Http\Requests\ap\comercial\UpdateApReceivingChecklistRequest;
use App\Http\Services\ap\comercial\ApReceivingChecklistService;
use Exception;
use Illuminate\Http\JsonResponse;

class ApReceivingChecklistController extends Controller
{
    protected ApReceivingChecklistService $service;

    public function __construct(ApReceivingChecklistService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the receiving checklists
     */
    public function index(IndexApReceivingChecklistRequest $request): JsonResponse
    {
        try {
            return $this->service->list($request);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Store newly created receiving checklists in storage
     */
    public function store(StoreApReceivingChecklistRequest $request): JsonResponse
    {
        try {
            return $this->service->store($request->validated());
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified receiving checklist
     */
    public function show($id): JsonResponse
    {
        try {
            return response()->json($this->service->show($id));
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }

    /**
     * Update receiving checklists for a shipping guide
     */
    public function update(UpdateApReceivingChecklistRequest $request): JsonResponse
    {
        try {
            return $this->service->update($request->validated());
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified receiving checklist from storage
     */
    public function destroy($id): JsonResponse
    {
        try {
            return $this->service->destroy($id);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get all receiving checklists for a specific shipping guide
     */
    public function getByShippingGuide($shippingGuideId): JsonResponse
    {
        try {
            return $this->service->getByShippingGuide($shippingGuideId);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove all receiving checklists for a shipping guide
     */
    public function destroyByShippingGuide($shippingGuideId): JsonResponse
    {
        try {
            return $this->service->destroyByShippingGuide($shippingGuideId);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
