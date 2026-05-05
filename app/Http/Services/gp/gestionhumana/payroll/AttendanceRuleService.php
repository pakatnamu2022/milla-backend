<?php

namespace App\Http\Services\gp\gestionhumana\payroll;

use App\Http\Resources\gp\gestionhumana\payroll\AttendanceRuleResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\gp\gestionhumana\payroll\AttendanceRule;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceRuleService extends BaseService implements BaseServiceInterface
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      AttendanceRule::class,
      $request,
      AttendanceRule::filters,
      AttendanceRule::sorts,
      AttendanceRuleResource::class,
    );
  }

  public function find($id)
  {
    $rule = AttendanceRule::find($id);
    if (!$rule) {
      throw new Exception('Attendance rule not found');
    }
    return $rule;
  }

  public function show($id)
  {
    return new AttendanceRuleResource($this->find($id));
  }

  public function store(mixed $data)
  {
    try {
      DB::beginTransaction();
      $rule = AttendanceRule::create($data);
      DB::commit();
      return new AttendanceRuleResource($rule);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function update(mixed $data)
  {
    try {
      DB::beginTransaction();
      $rule = $this->find($data['id']);
      $rule->update($data);
      DB::commit();
      return new AttendanceRuleResource($rule->fresh());
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function codes()
  {
    return AttendanceRule::select('code', 'description')
      ->distinct()
      ->orderBy('code')
      ->get()
      ->map(function ($rule) {
        return [
          'code' => $rule->code,
          'description' => $rule->description,
        ];
      });
  }

  public function destroy($id)
  {
    try {
      DB::beginTransaction();
      $rule = $this->find($id);
      $rule->delete();
      DB::commit();
      return response()->json(['message' => 'Attendance rule deleted successfully']);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }
}
