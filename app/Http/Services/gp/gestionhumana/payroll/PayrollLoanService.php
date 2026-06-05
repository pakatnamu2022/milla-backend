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
            $record->update($data);
            DB::commit();
            return new PayrollLoanResource($record->fresh());
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

    private function recalculatePendingInstallments(PayrollLoan $loan, float $newBalance): void
    {
        $pending = PayrollLoanExtraDiscount::where('loan_id', $loan->id)
            ->where('concept_type', PayrollLoanExtraDiscount::CONCEPT_TYPE_REGULAR)
            ->where('applied', false)
            ->whereNull('deleted_at')
            ->orderBy('scheduled_date')
            ->get();

        $remaining = $newBalance;

        foreach ($pending as $installment) {
            if ($remaining <= 0) {
                $installment->delete();
            } elseif ($remaining < (float) $installment->amount) {
                $installment->update(['amount' => $remaining]);
                $remaining = 0;
            } else {
                $remaining = round($remaining - (float) $installment->amount, 2);
            }
        }
    }
}