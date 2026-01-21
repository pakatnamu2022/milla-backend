<?php

namespace App\Http\Controllers\gp\gestionhumana;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\IndexAccountantDistrictAssignmentRequest;
use App\Http\Requests\gp\gestionhumana\StoreAccountantDistrictAssignmentRequest;
use App\Http\Requests\gp\gestionhumana\UpdateAccountantDistrictAssignmentRequest;
use App\Http\Services\gp\gestionhumana\AccountantDistrictAssignmentService;
use Throwable;

class AccountantDistrictAssignmentController extends Controller
{
    protected AccountantDistrictAssignmentService $service;

    public function __construct(AccountantDistrictAssignmentService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of accountant district assignments
     */
    public function index(IndexAccountantDistrictAssignmentRequest $request)
    {
        try {
            return $this->service->index($request);
        } catch (Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    /**
     * Store a newly created accountant district assignment
     */
    public function store(StoreAccountantDistrictAssignmentRequest $request)
    {
        try {
            return $this->success($this->service->store($request->validated()));
        } catch (Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    /**
     * Display the specified accountant district assignment
     */
    public function show(int $id)
    {
        try {
            return $this->success($this->service->show($id));
        } catch (Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    /**
     * Update the specified accountant district assignment
     */
    public function update(UpdateAccountantDistrictAssignmentRequest $request, int $id)
    {
        try {
            $data = $request->validated();
            $data['id'] = $id;
            return $this->success($this->service->update($data));
        } catch (Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    /**
     * Remove the specified accountant district assignment
     */
    public function destroy(int $id)
    {
        try {
            return $this->service->destroy($id);
        } catch (Throwable $th) {
            return $this->error($th->getMessage());
        }
    }
}
