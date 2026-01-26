<?php

namespace App\Http\Services\gp\gestionhumana\payroll;

use App\Http\Resources\gp\gestionhumana\payroll\PayrollConceptResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\gp\gestionhumana\payroll\PayrollConcept;
use App\Models\gp\gestionhumana\payroll\PayrollFormulaVariable;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayrollConceptService extends BaseService implements BaseServiceInterface
{
  protected FormulaParserService $formulaParser;

  public function __construct(FormulaParserService $formulaParser)
  {
    $this->formulaParser = $formulaParser;
  }

  /**
   * Get all concepts with filters and pagination
   */
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      PayrollConcept::class,
      $request,
      PayrollConcept::filters,
      PayrollConcept::sorts,
      PayrollConceptResource::class,
    );
  }

  /**
   * Find a concept by ID
   */
  public function find($id)
  {
    $concept = PayrollConcept::find($id);
    if (!$concept) {
      throw new Exception('Concept not found');
    }
    return $concept;
  }

  /**
   * Show a concept by ID
   */
  public function show($id)
  {
    return new PayrollConceptResource($this->find($id));
  }

  /**
   * Create a new concept
   */
  public function store(mixed $data)
  {
    try {
      DB::beginTransaction();

      $concept = PayrollConcept::create([
        'code' => strtoupper($data['code']),
        'name' => $data['name'],
        'description' => $data['description'] ?? null,
        'type' => $data['type'],
        'category' => $data['category'],
        'formula' => $data['formula'] ?? null,
        'formula_description' => $data['formula_description'] ?? null,
        'is_taxable' => $data['is_taxable'] ?? true,
        'calculation_order' => $data['calculation_order'] ?? 0,
        'active' => $data['active'] ?? true,
      ]);

      DB::commit();
      return new PayrollConceptResource($concept);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Update a concept
   */
  public function update(mixed $data)
  {
    try {
      DB::beginTransaction();

      $concept = $this->find($data['id']);

      $concept->update([
        'code' => strtoupper($data['code'] ?? $concept->code),
        'name' => $data['name'] ?? $concept->name,
        'description' => $data['description'] ?? $concept->description,
        'type' => $data['type'] ?? $concept->type,
        'category' => $data['category'] ?? $concept->category,
        'formula' => $data['formula'] ?? $concept->formula,
        'formula_description' => $data['formula_description'] ?? $concept->formula_description,
        'is_taxable' => $data['is_taxable'] ?? $concept->is_taxable,
        'calculation_order' => $data['calculation_order'] ?? $concept->calculation_order,
        'active' => $data['active'] ?? $concept->active,
      ]);

      DB::commit();
      return new PayrollConceptResource($concept->fresh());
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Delete a concept
   */
  public function destroy($id)
  {
    try {
      DB::beginTransaction();

      $concept = $this->find($id);

      // Check if concept is in use
      if ($concept->calculationDetails()->exists()) {
        throw new Exception('Cannot delete concept: it is being used in calculations');
      }

      $concept->delete();

      DB::commit();
      return response()->json(['message' => 'Concept deleted successfully']);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Test a formula with sample values
   */
  public function testFormula(int $id, array $testVariables = [])
  {
    $concept = $this->find($id);

    if (empty($concept->formula)) {
      throw new Exception('This concept has no formula defined');
    }

    // Get default test variables from formula variables
    $defaultVariables = PayrollFormulaVariable::active()->fixed()->pluck('value', 'code')->toArray();

    // Merge with test variables (test variables take precedence)
    $variables = array_merge($defaultVariables, $testVariables);

    // Extract variables from formula
    $requiredVariables = $this->formulaParser->extractVariables($concept->formula);

    // Check if all required variables are present
    $missingVariables = array_diff($requiredVariables, array_keys($variables));
    if (!empty($missingVariables)) {
      return [
        'valid' => false,
        'formula' => $concept->formula,
        'error' => 'Missing variables: ' . implode(', ', $missingVariables),
        'required_variables' => $requiredVariables,
        'available_variables' => array_keys($variables),
      ];
    }

    // Test the formula
    $result = $this->formulaParser->testFormula($concept->formula, $variables);

    return array_merge($result, [
      'formula' => $concept->formula,
      'variables_used' => array_intersect_key($variables, array_flip($requiredVariables)),
    ]);
  }
}
