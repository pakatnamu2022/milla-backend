<?php

namespace App\Http\Controllers\gp\gestionhumana\viaticos;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\viaticos\IndexExpenseTypeRequest;
use App\Http\Requests\gp\gestionhumana\viaticos\StoreExpenseTypeRequest;
use App\Http\Requests\gp\gestionhumana\viaticos\UpdateExpenseTypeRequest;
use App\Http\Resources\gp\gestionhumana\viaticos\ExpenseTypeResource;
use App\Services\gp\gestionhumana\viaticos\ExpenseTypeService;
use Illuminate\Http\Request;
use Throwable;

class ExpenseTypeController extends Controller
{
    protected ExpenseTypeService $service;

    public function __construct(ExpenseTypeService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of all expense types
     */
    public function index(IndexExpenseTypeRequest $request)
    {
        try {
            return $this->service->index($request);
        } catch (Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    /**
     * Display active expense types only
     */
    public function active(Request $request)
    {
        try {
            return $this->service->active($request);
        } catch (Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    /**
     * Display parent expense types only
     */
    public function parents(Request $request)
    {
        try {
            return $this->service->parents($request);
        } catch (Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    /**
     * Store a newly created expense type
     */
    public function store(StoreExpenseTypeRequest $request)
    {
        try {
            $expenseType = $this->service->store($request->validated());
            return $this->success([
                'data' => new ExpenseTypeResource($expenseType),
                'message' => 'Tipo de gasto creado exitosamente'
            ]);
        } catch (Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    /**
     * Display the specified expense type
     */
    public function show(int $id)
    {
        try {
            $expenseType = $this->service->show($id);
            if (!$expenseType) {
                return $this->error('Tipo de gasto no encontrado');
            }
            return $this->success(new ExpenseTypeResource($expenseType));
        } catch (Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    /**
     * Update the specified expense type
     */
    public function update(UpdateExpenseTypeRequest $request, int $id)
    {
        try {
            $expenseType = $this->service->update($id, $request->validated());
            return $this->success([
                'data' => new ExpenseTypeResource($expenseType),
                'message' => 'Tipo de gasto actualizado exitosamente'
            ]);
        } catch (Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    /**
     * Remove the specified expense type
     */
    public function destroy(int $id)
    {
        try {
            $this->service->destroy($id);
            return $this->success(['message' => 'Tipo de gasto eliminado exitosamente']);
        } catch (Throwable $th) {
            return $this->error($th->getMessage());
        }
    }
}
