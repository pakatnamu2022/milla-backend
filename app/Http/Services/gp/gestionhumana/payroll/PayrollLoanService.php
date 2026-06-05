<?php

namespace App\Http\Services\gp\gestionhumana\payroll;

use App\Http\Resources\gp\gestionhumana\payroll\PayrollLoanExtraDiscountResource;
use App\Http\Resources\gp\gestionhumana\payroll\PayrollLoanResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\gp\gestionhumana\payroll\PayrollLoan;
use App\Models\gp\gestionhumana\payroll\PayrollLoanExtraDiscount;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PayrollLoanService extends BaseService implements BaseServiceInterface
{
    public function list(Request $request)
    {
        return $this->getFilteredResults(
            PayrollLoan::class,
            $request,
            PayrollLoan::filters,
            PayrollLoan::sorts,
            PayrollLoanResource::class,
        );
    }

    public function find($id)
    {
        $record = PayrollLoan::find($id);
        if (!$record) {
            throw new Exception('Préstamo no encontrado');
        }
        return $record;
    }

    public function show($id)
    {
        $record = $this->find($id);
        $record->load('extraDiscounts');
        return new PayrollLoanResource($record);
    }

    public function store(mixed $data)
    {
        DB::beginTransaction();
        try {
            $hasDays = !empty($data['payment_days']);

            if ($hasDays) {
                $data['installments_count'] = (int) ceil(
                    (float) $data['loan_amount'] / (float) $data['installment_amount']
                );
            }

            $data['remaining_balance'] = $data['loan_amount'];

            $record = PayrollLoan::create($data);

            if ($hasDays) {
                $this->generateInstallments($record);
            }

            DB::commit();
            return new PayrollLoanResource($record->fresh()->load('extraDiscounts'));
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function update(mixed $data)
    {
        DB::beginTransaction();
        try {
            $record = $this->find($data['id']);

            // Validar que no existan pagos confirmados
            $hasConfirmedPayments = PayrollLoanExtraDiscount::where('loan_id', $record->id)
                ->where('applied', true)
                ->whereNull('deleted_at')
                ->exists();

            if ($hasConfirmedPayments) {
                throw new Exception('No se puede editar el préstamo porque ya tiene pagos confirmados');
            }

            $hasDays = !empty($data['payment_days']);

            // Si cambió el monto de la cuota o los días de pago, recalcular el número de cuotas
            if ($hasDays && (
                (isset($data['loan_amount']) && $data['loan_amount'] != $record->loan_amount) ||
                (isset($data['installment_amount']) && $data['installment_amount'] != $record->installment_amount)
            )) {
                $loanAmount = $data['loan_amount'] ?? $record->loan_amount;
                $installmentAmount = $data['installment_amount'] ?? $record->installment_amount;
                $data['installments_count'] = (int) ceil(
                    (float) $loanAmount / (float) $installmentAmount
                );
            }

            // Si cambió el monto del préstamo, actualizar el saldo restante
            if (isset($data['loan_amount']) && $data['loan_amount'] != $record->loan_amount) {
                $data['remaining_balance'] = $data['loan_amount'];
            }

            $record->update($data);

            // Si tiene días de pago configurados, regenerar las cuotas
            if (!empty($record->payment_days)) {
                // Eliminar todas las cuotas pendientes (no confirmadas)
                PayrollLoanExtraDiscount::where('loan_id', $record->id)
                    ->where('concept_type', PayrollLoanExtraDiscount::CONCEPT_TYPE_REGULAR)
                    ->where('applied', false)
                    ->whereNull('deleted_at')
                    ->delete();

                // Regenerar las cuotas
                $this->generateInstallments($record);
            }

            DB::commit();
            return new PayrollLoanResource($record->fresh()->load('extraDiscounts'));
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $record = $this->find($id);
            $record->delete();
            DB::commit();
            return response()->json(['message' => 'Préstamo eliminado correctamente']);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Crea un pago directo (ya confirmado) y recalcula las cuotas pendientes.
     * Usado para pagos inmediatos donde no se necesita confirmación posterior.
     */
    public function applyPayment(int $loanId, array $data): PayrollLoanResource
    {
        DB::beginTransaction();
        try {
            $loan        = $this->find($loanId);
            $amount      = (float) $data['amount'];
            $conceptType = $data['concept_type'] ?? PayrollLoanExtraDiscount::CONCEPT_TYPE_REGULAR;

            PayrollLoanExtraDiscount::create([
                'loan_id'        => $loanId,
                'concept_type'   => $conceptType,
                'amount'         => $amount,
                'scheduled_date' => $data['scheduled_date'],
                'applied'        => true,
                'confirmed_by'   => Auth::id(),
                'confirmed_at'   => now(),
                'status'         => 1,
            ]);

            $newBalance = round(max(0, (float) $loan->remaining_balance - $amount), 2);
            $this->recalculatePendingInstallments($loan, $newBalance);
            $loan->update(['remaining_balance' => $newBalance]);

            DB::commit();
            return new PayrollLoanResource($loan->fresh()->load('extraDiscounts'));
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Confirma una cuota/descuento pendiente.
     * Si el monto varió respecto al programado, lo actualiza y recalcula las cuotas restantes.
     * Valida que las cuotas se confirmen en orden cronológico.
     */
    public function confirmPayment(int $discountId, array $data): PayrollLoanExtraDiscountResource
    {
        DB::beginTransaction();
        try {
            $record = PayrollLoanExtraDiscount::find($discountId);
            if (!$record) {
                throw new Exception('Cuota no encontrada');
            }

            if ($record->applied) {
                throw new Exception('Esta cuota ya fue confirmada');
            }

            // Validar que no existan cuotas anteriores sin confirmar (solo para cuotas REGULAR)
            if ($record->concept_type === PayrollLoanExtraDiscount::CONCEPT_TYPE_REGULAR) {
                $previousUnconfirmed = PayrollLoanExtraDiscount::where('loan_id', $record->loan_id)
                    ->where('concept_type', PayrollLoanExtraDiscount::CONCEPT_TYPE_REGULAR)
                    ->where('applied', false)
                    ->where('scheduled_date', '<', $record->scheduled_date)
                    ->whereNull('deleted_at')
                    ->exists();

                if ($previousUnconfirmed) {
                    throw new Exception('No se puede confirmar esta cuota. Existen cuotas anteriores pendientes que deben confirmarse primero en orden cronológico.');
                }
            }

            $newAmount     = isset($data['amount']) ? (float) $data['amount'] : (float) $record->amount;
            $amountChanged = abs($newAmount - (float) $record->amount) > 0.001;

            $record->update([
                'amount'       => $newAmount,
                'applied'      => true,
                'confirmed_by' => Auth::id(),
                'confirmed_at' => now(),
            ]);

            $loan       = $this->find($record->loan_id);
            $newBalance = round(max(0, (float) $loan->remaining_balance - $newAmount), 2);

            // Siempre recalcula: el monto puede haber variado o simplemente hay que
            // descontar esta cuota del saldo pendiente y ajustar las restantes.
            $this->recalculatePendingInstallments($loan, $newBalance);
            $loan->update(['remaining_balance' => $newBalance]);

            DB::commit();
            return new PayrollLoanExtraDiscountResource($record->fresh()->load('confirmedBy'));
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Elimina las cuotas REGULAR pendientes y las regenera desde hoy
     * según el saldo actual y los días de pago configurados.
     */
    public function regenerateInstallments(int $loanId): PayrollLoanResource
    {
        DB::beginTransaction();
        try {
            $loan = $this->find($loanId);

            if (empty($loan->payment_days)) {
                throw new Exception('El préstamo no tiene días de pago configurados');
            }

            PayrollLoanExtraDiscount::where('loan_id', $loanId)
                ->where('concept_type', PayrollLoanExtraDiscount::CONCEPT_TYPE_REGULAR)
                ->where('applied', false)
                ->whereNull('deleted_at')
                ->delete();

            $this->generateInstallments($loan, today()->toDateString());

            DB::commit();
            return new PayrollLoanResource($loan->fresh()->load('extraDiscounts'));
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function generateInstallments(PayrollLoan $loan, ?string $startFrom = null): void
    {
        $paymentDays = $loan->payment_days;
        sort($paymentDays);

        $remaining         = (float) $loan->remaining_balance;
        $installmentAmount = (float) $loan->installment_amount;
        $totalInstallments = (int) ceil($remaining / $installmentAmount);

        $startDate = $startFrom
            ? Carbon::parse($startFrom)
            : ($loan->payment_start
                ? Carbon::instance($loan->payment_start)
                : Carbon::instance($loan->delivery_date ?? today()));

        $year       = (int) $startDate->format('Y');
        $month      = (int) $startDate->format('n');
        $startDay   = (int) $startDate->format('j');
        $count      = 0;
        $records    = [];
        $firstMonth = true;

        while ($count < $totalInstallments && $remaining > 0) {
            foreach ($paymentDays as $day) {
                if ($count >= $totalInstallments || $remaining <= 0) {
                    break;
                }

                if ($firstMonth && (int) $day < $startDay) {
                    continue;
                }

                $daysInMonth   = Carbon::createFromDate($year, $month, 1)->daysInMonth;
                $actualDay     = min((int) $day, $daysInMonth);
                $scheduledDate = Carbon::createFromDate($year, $month, $actualDay)->toDateString();

                $amount    = round(min($installmentAmount, $remaining), 2);
                $remaining = round($remaining - $amount, 2);

                $records[] = [
                    'loan_id'        => $loan->id,
                    'concept_type'   => PayrollLoanExtraDiscount::CONCEPT_TYPE_REGULAR,
                    'amount'         => $amount,
                    'scheduled_date' => $scheduledDate,
                    'applied'        => false,
                    'status'         => 1,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ];

                $count++;
            }

            $firstMonth = false;
            $month++;
            if ($month > 12) {
                $month = 1;
                $year++;
            }
        }

        if (!empty($records)) {
            PayrollLoanExtraDiscount::insert($records);
        }

        $loan->update(['installments_count' => $count]);
    }

    /**
     * Recalcula las cuotas pendientes de un préstamo basándose en un nuevo saldo.
     * Opcionalmente puede filtrar solo las cuotas posteriores a una fecha.
     *
     * @param PayrollLoan $loan El préstamo a recalcular
     * @param float $newBalance El nuevo saldo pendiente
     * @param string|null $afterDate Si se proporciona, solo recalcula cuotas después de esta fecha
     * @return void
     */
    public function recalculatePendingInstallments(PayrollLoan $loan, float $newBalance, ?string $afterDate = null): void
    {
        $query = PayrollLoanExtraDiscount::where('loan_id', $loan->id)
            ->where('concept_type', PayrollLoanExtraDiscount::CONCEPT_TYPE_REGULAR)
            ->where('applied', false)
            ->whereNull('deleted_at');

        if ($afterDate) {
            $query->where('scheduled_date', '>', $afterDate);
        }

        $pending = $query->orderBy('scheduled_date')->get();
        $totalInstallments = $pending->count();

        if ($totalInstallments === 0) {
            return;
        }

        $remaining = $newBalance;
        $installmentAmount = (float) $loan->installment_amount;

        foreach ($pending as $index => $installment) {
            $isLast = ($index === $totalInstallments - 1);

            if ($remaining <= 0) {
                // Si no queda saldo, eliminar esta cuota
                $installment->delete();
            } elseif ($isLast) {
                // En la última cuota, asignar TODO el saldo restante (sin límite de installment_amount)
                $installment->update(['amount' => $remaining]);
                $remaining = 0;
            } else {
                // En cuotas intermedias, usar el menor entre installment_amount y el saldo restante
                $newInstallmentAmount = round(min($installmentAmount, $remaining), 2);
                $installment->update(['amount' => $newInstallmentAmount]);
                $remaining = round($remaining - $newInstallmentAmount, 2);
            }
        }
    }

    /**
     * Regenera completamente las cuotas futuras después de una fecha específica.
     * Elimina todas las cuotas REGULAR pendientes posteriores a esa fecha y genera nuevas
     * basadas en el saldo restante y los días de pago configurados.
     *
     * @param PayrollLoan $loan El préstamo
     * @param float $newBalance El saldo que queda por pagar
     * @param string $afterDate Fecha a partir de la cual regenerar (exclusive)
     * @return void
     */
    public function regenerateFutureInstallments(PayrollLoan $loan, float $newBalance, string $afterDate): void
    {
        if (empty($loan->payment_days)) {
            throw new Exception('El préstamo no tiene días de pago configurados');
        }

        // Eliminar todas las cuotas REGULAR pendientes DESPUÉS de esta fecha
        PayrollLoanExtraDiscount::where('loan_id', $loan->id)
            ->where('concept_type', PayrollLoanExtraDiscount::CONCEPT_TYPE_REGULAR)
            ->where('applied', false)
            ->where('scheduled_date', '>', $afterDate)
            ->whereNull('deleted_at')
            ->delete();

        // Si no hay saldo restante, no generar cuotas
        if ($newBalance <= 0) {
            return;
        }

        // Generar nuevas cuotas desde la fecha siguiente
        $paymentDays = $loan->payment_days;
        sort($paymentDays);

        $remaining         = $newBalance;
        $installmentAmount = (float) $loan->installment_amount;
        $totalInstallments = (int) ceil($remaining / $installmentAmount);

        // Partir desde el día siguiente a la fecha de referencia
        $startDate = Carbon::parse($afterDate)->addDay();

        $year       = (int) $startDate->format('Y');
        $month      = (int) $startDate->format('n');
        $startDay   = (int) $startDate->format('j');
        $count      = 0;
        $records    = [];
        $firstMonth = true;

        while ($count < $totalInstallments && $remaining > 0) {
            foreach ($paymentDays as $day) {
                if ($count >= $totalInstallments || $remaining <= 0) {
                    break;
                }

                if ($firstMonth && (int) $day < $startDay) {
                    continue;
                }

                $daysInMonth   = Carbon::createFromDate($year, $month, 1)->daysInMonth;
                $actualDay     = min((int) $day, $daysInMonth);
                $scheduledDate = Carbon::createFromDate($year, $month, $actualDay)->toDateString();

                // Asegurarse que la fecha sea posterior a la fecha de referencia
                if ($scheduledDate <= $afterDate) {
                    continue;
                }

                $amount    = round(min($installmentAmount, $remaining), 2);
                $remaining = round($remaining - $amount, 2);

                $records[] = [
                    'loan_id'        => $loan->id,
                    'concept_type'   => PayrollLoanExtraDiscount::CONCEPT_TYPE_REGULAR,
                    'amount'         => $amount,
                    'scheduled_date' => $scheduledDate,
                    'applied'        => false,
                    'status'         => 1,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ];

                $count++;
            }

            $firstMonth = false;
            $month++;
            if ($month > 12) {
                $month = 1;
                $year++;
            }
        }

        if (!empty($records)) {
            PayrollLoanExtraDiscount::insert($records);
        }
    }
}