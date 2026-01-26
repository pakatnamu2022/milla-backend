<?php

namespace App\Http\Services\gp\gestionhumana\payroll;

use App\Http\Resources\gp\gestionhumana\payroll\PayrollFormulaVariableResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\gp\gestionhumana\payroll\PayrollFormulaVariable;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayrollFormulaVariableService extends BaseService implements BaseServiceInterface
{
  /**
   * Get all formula variables with filters and pagination
   */
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      PayrollFormulaVariable::class,
      $request,
      PayrollFormulaVariable::filters,
      PayrollFormulaVariable::sorts,
      PayrollFormulaVariableResource::class,
    );
  }

  /**
   * Find a formula variable by ID
   */
  public function find($id)
  {
    $variable = PayrollFormulaVariable::find($id);
    if (!$variable) {
      throw new Exception('Formula variable not found');
    }
    return $variable;
  }

  /**
   * Show a formula variable by ID
   */
  public function show($id)
  {
    return new PayrollFormulaVariableResource($this->find($id));
  }

  /**
   * Create a new formula variable
   */
  public function store(mixed $data)
  {
    try {
      DB::beginTransaction();

      $variable = PayrollFormulaVariable::create([
        'code' => strtoupper($data['code']),
        'name' => $data['name'],
        'description' => $data['description'] ?? null,
        'type' => $data['type'] ?? PayrollFormulaVariable::TYPE_FIXED,
        'value' => $data['value'] ?? null,
        'source_field' => $data['source_field'] ?? null,
        'formula' => $data['formula'] ?? null,
        'active' => $data['active'] ?? true,
      ]);

      DB::commit();
      return new PayrollFormulaVariableResource($variable);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Update a formula variable
   */
  public function update(mixed $data)
  {
    try {
      DB::beginTransaction();

      $variable = $this->find($data['id']);

      $variable->update([
        'code' => strtoupper($data['code'] ?? $variable->code),
        'name' => $data['name'] ?? $variable->name,
        'description' => $data['description'] ?? $variable->description,
        'type' => $data['type'] ?? $variable->type,
        'value' => $data['value'] ?? $variable->value,
        'source_field' => $data['source_field'] ?? $variable->source_field,
        'formula' => $data['formula'] ?? $variable->formula,
        'active' => $data['active'] ?? $variable->active,
      ]);

      DB::commit();
      return new PayrollFormulaVariableResource($variable->fresh());
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Delete a formula variable
   */
  public function destroy($id)
  {
    try {
      DB::beginTransaction();

      $variable = $this->find($id);
      $variable->delete();

      DB::commit();
      return response()->json(['message' => 'Formula variable deleted successfully']);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Get all active variables as key-value array
   */
  public function getActiveVariablesArray(): array
  {
    return PayrollFormulaVariable::getActiveVariablesArray();
  }
}
