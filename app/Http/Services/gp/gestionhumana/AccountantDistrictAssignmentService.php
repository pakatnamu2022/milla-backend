<?php

namespace App\Http\Services\gp\gestionhumana;

use App\Http\Resources\gp\gestionhumana\AccountantDistrictAssignmentResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionhumana\AccountantDistrictAssignment;
use App\Models\gp\gestionhumana\personal\Worker;
use App\Models\gp\gestionsistema\District;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountantDistrictAssignmentService extends BaseService
{
    /**
     * List assignments with filters and pagination
     */
    public function index(Request $request)
    {
        $query = AccountantDistrictAssignment::with(['worker', 'worker.position', 'worker.sede', 'district', 'district.province', 'district.province.department']);

        return $this->getFilteredResults(
            $query,
            $request,
            AccountantDistrictAssignment::filters,
            AccountantDistrictAssignment::sorts,
            AccountantDistrictAssignmentResource::class
        );
    }

    /**
     * Find assignment by ID
     */
    public function find($id): AccountantDistrictAssignment
    {
        $assignment = AccountantDistrictAssignment::with(['worker', 'worker.position', 'worker.sede', 'district', 'district.province', 'district.province.department'])
            ->where('id', $id)
            ->first();

        if (!$assignment) {
            throw new Exception('Asignación no encontrada');
        }

        return $assignment;
    }

    /**
     * Create new assignment
     */
    public function store(mixed $data)
    {
        DB::beginTransaction();
        try {
            // Validate that worker exists
            $worker = Worker::find($data['worker_id']);
            if (!$worker) {
                throw new Exception('Trabajador no encontrado');
            }

            // Validate that district exists
            $district = District::find($data['district_id']);
            if (!$district) {
                throw new Exception('Distrito no encontrado');
            }

            // Validate that there's no active duplicate assignment
            $existingAssignment = AccountantDistrictAssignment::where('worker_id', $data['worker_id'])
                ->where('district_id', $data['district_id'])
                ->whereNull('deleted_at')
                ->first();

            if ($existingAssignment) {
                throw new Exception('Ya existe una asignación activa de este trabajador a este distrito');
            }

            // Create assignment
            $assignment = AccountantDistrictAssignment::create($data);

            DB::commit();

            return AccountantDistrictAssignmentResource::make(
                $assignment->load(['worker', 'worker.position', 'worker.sede', 'district', 'district.province', 'district.province.department'])
            );
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Show assignment
     */
    public function show($id)
    {
        return AccountantDistrictAssignmentResource::make($this->find($id));
    }

    /**
     * Update assignment
     */
    public function update(mixed $data)
    {
        DB::beginTransaction();
        try {
            $assignment = $this->find($data['id']);

            // If updating worker_id or district_id, validate duplicates
            $workerIdToCheck = $data['worker_id'] ?? $assignment->worker_id;
            $districtIdToCheck = $data['district_id'] ?? $assignment->district_id;

            // Only validate if one of them changed
            if (isset($data['worker_id']) || isset($data['district_id'])) {
                $existingAssignment = AccountantDistrictAssignment::where('worker_id', $workerIdToCheck)
                    ->where('district_id', $districtIdToCheck)
                    ->where('id', '!=', $assignment->id)
                    ->whereNull('deleted_at')
                    ->first();

                if ($existingAssignment) {
                    throw new Exception('Ya existe una asignación activa de este trabajador a este distrito');
                }
            }

            // Validate that worker exists if updating
            if (isset($data['worker_id'])) {
                $worker = Worker::find($data['worker_id']);
                if (!$worker) {
                    throw new Exception('Trabajador no encontrado');
                }
            }

            // Validate that district exists if updating
            if (isset($data['district_id'])) {
                $district = District::find($data['district_id']);
                if (!$district) {
                    throw new Exception('Distrito no encontrado');
                }
            }

            $assignment->update($data);
            DB::commit();

            return AccountantDistrictAssignmentResource::make(
                $assignment->load(['worker', 'worker.position', 'worker.sede', 'district', 'district.province', 'district.province.department'])
            );
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete assignment (soft delete)
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $assignment = $this->find($id);
            $assignment->delete();
            DB::commit();

            return response()->json([
                'message' => 'Asignación eliminada correctamente'
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
