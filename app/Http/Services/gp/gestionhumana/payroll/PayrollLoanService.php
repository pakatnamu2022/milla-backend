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

    /**
     * Sincroniza los préstamos de web_millagp_2 (rrhh_prestamos / rrhh_detalle_prestamo,
     * misma BD) hacia gh_payroll_loans / gh_payroll_loan_extra_discounts.
     *
     * - Trae todos los préstamos activos del legacy (status_deleted = 1), incluido el historial
     *   ya pagado (préstamos, adelantos, colaboraciones, teléfono...). El concepto del legacy
     *   (rrhh_concepto) se guarda en `concept` y el motivo en `reason`.
     * - Cada detalle del legacy (cuota descontada por el sistema o pago manual) se importa
     *   como cuota ya aplicada. Las cuotas descontadas por el sistema son 'PAGO DE CUOTA';
     *   los pagos manuales conservan su comentario (p. ej. 'PAGADO EN LBS').
     * - Las cuotas pendientes se regeneran cada vez desde el saldo y la fecha del próximo
     *   pago del legacy: mensuales, el mismo día, de a monto_cuota (la última absorbe el resto).
     * - Es repetible: usa legacy_id / legacy_detail_id. Un préstamo con pagos confirmados
     *   en este sistema no se toca, y los creados aquí (legacy_id NULL) tampoco.
     */
    public function syncFromLegacy(): array
    {
        set_time_limit(0);

        $localByLegacy = PayrollLoan::withTrashed()->whereNotNull('legacy_id')->get()->keyBy('legacy_id');
        $concepts = DB::table('rrhh_concepto')->pluck('nombre', 'id');

        $legacyLoans = DB::table('rrhh_prestamos')
            ->where('status_deleted', 1)
            ->orderBy('id')
            ->get();

        $existingWorkers = DB::table('rrhh_persona')
            ->whereIn('id', $legacyLoans->pluck('empleado_id')->unique())
            ->pluck('id')
            ->flip();

        $legacyDetails = DB::table('rrhh_detalle_prestamo')
            ->where('status_deleted', 1)
            ->whereIn('prestamo_id', $legacyLoans->pluck('id'))
            ->orderBy('fecha')
            ->orderBy('id')
            ->get()
            ->groupBy('prestamo_id');

        $result = [
            'total_legacy' => $legacyLoans->count(),
            'created' => 0,
            'updated' => 0,
            'deleted' => 0,
            'details_imported' => 0,
            'skipped' => [],
        ];

        foreach ($legacyLoans as $legacy) {
            if (!isset($existingWorkers[$legacy->empleado_id])) {
                $result['skipped'][] = "Préstamo legacy #{$legacy->id}: el empleado {$legacy->empleado_id} no existe";
                continue;
            }

            try {
                $outcome = DB::transaction(fn() => $this->syncLegacyLoan(
                    $legacy,
                    $localByLegacy->get($legacy->id),
                    $legacyDetails->get($legacy->id, collect()),
                    $concepts->get($legacy->concepto_id)
                ));
                $result[$outcome['action']]++;
                $result['details_imported'] += $outcome['details'];
                array_push($result['skipped'], ...$outcome['warnings']);
            } catch (Exception $e) {
                $result['skipped'][] = "Préstamo legacy #{$legacy->id}: {$e->getMessage()}";
            }
        }

        // Préstamos que el legacy ya eliminó (status_deleted = 0): se eliminan aquí también.
        $activeLegacyIds = $legacyLoans->pluck('id')->flip();
        foreach ($localByLegacy as $legacyId => $local) {
            if ($local->trashed() || isset($activeLegacyIds[$legacyId])) {
                continue;
            }
            if ($this->hasLocalPayments($local)) {
                $result['skipped'][] = "Préstamo legacy #{$legacyId}: eliminado en el legacy pero tiene pagos confirmados aquí";
                continue;
            }
            PayrollLoanExtraDiscount::where('loan_id', $local->id)->forceDelete();
            $local->delete();
            $result['deleted']++;
        }

        // Con el historial completo puede haber cientos de observaciones: se devuelven las primeras.
        $result['skipped_total'] = count($result['skipped']);
        $result['skipped'] = array_slice($result['skipped'], 0, 20);

        return $result;
    }

    private function syncLegacyLoan(object $legacy, ?PayrollLoan $local, $details, ?string $concept): array
    {
        if ($local && $this->hasLocalPayments($local)) {
            throw new Exception('tiene pagos confirmados en este sistema, no se sobrescribe');
        }

        $nextPayment = $legacy->fecha_proximo_pago ? Carbon::parse($legacy->fecha_proximo_pago) : null;
        $remaining = round((float) $legacy->monto_restante, 2);
        $installment = round((float) $legacy->monto_cuota, 2);

        $attributes = [
            'concept' => $concept ? mb_substr(strtoupper($concept), 0, 50) : null,
            'worker_id' => $legacy->empleado_id,
            'delivery_date' => $legacy->fecha_entrega,
            'reason' => mb_substr((string) $legacy->motivo, 0, 255),
            'payment_start' => $legacy->fecha_pago,
            'payment_days' => $nextPayment ? [(int) $nextPayment->day] : null,
            'loan_amount' => round((float) $legacy->monto_prestamo, 2),
            'installments_count' => (int) $legacy->numero_cuotas,
            'installment_amount' => $installment,
            'remaining_balance' => $remaining,
            'status' => 1,
        ];

        $action = $local ? 'updated' : 'created';
        if ($local) {
            if ($local->trashed()) {
                $local->restore();
            }
            $local->update($attributes);
            $loan = $local;
        } else {
            $loan = PayrollLoan::create($attributes + ['legacy_id' => $legacy->id]);
        }

        $warnings = [];
        $imported = 0;

        $knownDetails = PayrollLoanExtraDiscount::withTrashed()
            ->where('loan_id', $loan->id)
            ->whereNotNull('legacy_detail_id')
            ->get()
            ->keyBy('legacy_detail_id');

        foreach ($details as $detail) {
            $paidOn = Carbon::parse($detail->fecha);
            if ($paidOn->year > 2100) {
                $warnings[] = "Préstamo legacy #{$legacy->id}: detalle #{$detail->id} con fecha inválida ({$detail->fecha}), se omite";
                continue;
            }

            $known = $knownDetails->get($detail->id);
            if ($known) {
                if ($known->trashed()) {
                    $known->restore();
                }
                continue;
            }

            $manualConcept = mb_substr(strtoupper(trim((string) $detail->comentario)), 0, 100) ?: 'PAGO MANUAL';
            PayrollLoanExtraDiscount::create([
                'loan_id' => $loan->id,
                'legacy_detail_id' => $detail->id,
                'scheduled_date' => $paidOn->toDateString(),
                'concept_type' => $detail->realizado === 'SISTEMA'
                    ? PayrollLoanExtraDiscount::CONCEPT_TYPE_REGULAR
                    : $manualConcept,
                'amount' => round((float) $detail->monto_pagado, 2),
                'applied' => true,
                'confirmed_at' => $detail->created_at,
                'status' => 1,
            ]);
            $imported++;
        }

        // Detalles ya importados que el legacy eliminó después.
        PayrollLoanExtraDiscount::where('loan_id', $loan->id)
            ->whereNotNull('legacy_detail_id')
            ->whereNotIn('legacy_detail_id', $details->pluck('id'))
            ->forceDelete();

        // Cuotas pendientes: se descartan y se vuelven a proyectar desde el saldo actual.
        PayrollLoanExtraDiscount::where('loan_id', $loan->id)
            ->where('concept_type', PayrollLoanExtraDiscount::CONCEPT_TYPE_REGULAR)
            ->where('applied', false)
            ->forceDelete();

        if ($remaining > 0) {
            if ($installment <= 0 || !$nextPayment) {
                $warnings[] = "Préstamo legacy #{$legacy->id}: sin monto de cuota o fecha de próximo pago, no se proyectaron cuotas pendientes";
            } else {
                $this->projectLegacyInstallments($loan, $nextPayment, $remaining, $installment);
            }
        }

        return ['action' => $action, 'details' => $imported, 'warnings' => $warnings];
    }

    /**
     * Cuotas mensuales el mismo día, de a $installment. El cociente se redondea a 2
     * decimales antes del ceil porque el legacy guarda la cuota con 4 decimales
     * (623.71 / 3 = 207.9033) y sin redondear saldría una cuota extra de S/ 0.01.
     */
    private function projectLegacyInstallments(PayrollLoan $loan, Carbon $firstDate, float $remaining, float $installment): void
    {
        $count = max(1, (int) ceil(round($remaining / $installment, 2)));
        $left = $remaining;

        for ($i = 0; $i < $count; $i++) {
            $amount = $i === $count - 1 ? $left : min($installment, $left);
            $left = round($left - $amount, 2);

            PayrollLoanExtraDiscount::create([
                'loan_id' => $loan->id,
                'scheduled_date' => $firstDate->copy()->addMonthsNoOverflow($i)->toDateString(),
                'concept_type' => PayrollLoanExtraDiscount::CONCEPT_TYPE_REGULAR,
                'amount' => $amount,
                'applied' => false,
                'status' => 1,
            ]);
        }
    }

    /** Pagos confirmados aquí (no importados del legacy). */
    private function hasLocalPayments(PayrollLoan $loan): bool
    {
        return PayrollLoanExtraDiscount::where('loan_id', $loan->id)
            ->whereNull('legacy_detail_id')
            ->where('applied', true)
            ->exists();
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