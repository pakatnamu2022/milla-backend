<?php

namespace App\Http\Services\gp\gestionhumana\payroll;

use App\Http\Resources\gp\gestionhumana\payroll\PayrollLoanExtraDiscountResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\gp\gestionhumana\payroll\PayrollLoanExtraDiscount;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayrollLoanExtraDiscountService extends BaseService implements BaseServiceInterface
{
    public function __construct(
        protected PayrollLoanService $loanService
    ) {}
    public function list(Request $request)
    {
        return $this->getFilteredResults(
            PayrollLoanExtraDiscount::class,
            $request,
            PayrollLoanExtraDiscount::filters,
            PayrollLoanExtraDiscount::sorts,
            PayrollLoanExtraDiscountResource::class,
        );
    }

    public function find($id)
    {
        $record = PayrollLoanExtraDiscount::find($id);
        if (!$record) {
            throw new Exception('Descuento extra no encontrado');
        }
        return $record;
    }

    public function show($id)
    {
        return new PayrollLoanExtraDiscountResource($this->find($id));
    }

    public function store(mixed $data)
    {
        try {
            DB::beginTransaction();
            $record = PayrollLoanExtraDiscount::create($data);
            DB::commit();
            return new PayrollLoanExtraDiscountResource($record);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function update(mixed $data)
    {
        try {
            DB::beginTransaction();
            $record = $this->find($data['id']);

            // Si solo se envía el amount, recalcular cuotas futuras
            if (isset($data['amount']) && $record->concept_type === PayrollLoanExtraDiscount::CONCEPT_TYPE_REGULAR && !$record->applied) {
                $newAmount = (float) $data['amount'];

                // Actualizar el monto de esta cuota
                $record->update(['amount' => $newAmount]);

                // Obtener el préstamo
                $loan = $record->loan;

                // Calcular cuánto falta por pagar DESPUÉS de esta cuota (asumiendo que se pagará)
                // El remaining_balance actual incluye todas las cuotas pendientes
                // Necesitamos saber cuánto quedará después de pagar esta cuota con el nuevo monto

                // Sumar todas las cuotas pendientes ANTES de esta (ya están en remaining_balance)
                $previousPending = PayrollLoanExtraDiscount::where('loan_id', $loan->id)
                    ->where('concept_type', PayrollLoanExtraDiscount::CONCEPT_TYPE_REGULAR)
                    ->where('applied', false)
                    ->where('scheduled_date', '<', $record->scheduled_date)
                    ->whereNull('deleted_at')
                    ->sum('amount');

                // El saldo que quedará después de pagar las anteriores y esta cuota
                $balanceAfterThis = round((float) $loan->remaining_balance - (float) $previousPending - $newAmount, 2);
                $balanceAfterThis = max(0, $balanceAfterThis);

                // Regenerar las cuotas futuras (elimina las existentes y crea nuevas según el saldo)
                $this->loanService->regenerateFutureInstallments($loan, $balanceAfterThis, $record->scheduled_date);
            } else {
                // Actualización normal de otros campos
                $record->update($data);
            }

            DB::commit();
            return new PayrollLoanExtraDiscountResource($record->fresh());
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            $record = $this->find($id);
            $record->delete();
            DB::commit();
            return response()->json(['message' => 'Descuento extra eliminado correctamente']);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}