<?php

namespace App\Observers;

use App\Models\ap\maestroGeneral\TechnicianHourlyCost;
use App\Models\GeneralMaster;
use Carbon\Carbon;

class GeneralMasterObserver
{
    /**
     * Handle the GeneralMaster "updated" event.
     * Cuando se actualiza el costo por hora del técnico (ID=61),
     * automáticamente crea un registro en la tabla de histórico
     */
    public function updated(GeneralMaster $generalMaster): void
    {
        // Solo actuar si es el registro de costo por hora del técnico (ID=61)
        if ($generalMaster->id !== 61) {
            return;
        }

        // Solo actuar si el valor cambió
        if (!$generalMaster->wasChanged('value')) {
            return;
        }

        // Obtener año y mes actual
        $now = Carbon::now();
        $year = $now->year;
        $month = $now->month;

        // Verificar si ya existe un registro para este periodo
        $existingRecord = TechnicianHourlyCost::where('year', $year)
            ->where('month', $month)
            ->first();

        if ($existingRecord) {
            // Si ya existe, actualizarlo
            $existingRecord->update([
                'cost_per_hour' => $generalMaster->value,
                'notes' => 'Actualizado automáticamente desde general_masters',
                'created_by' => auth()->id(),
            ]);
        } else {
            // Si no existe, crearlo
            TechnicianHourlyCost::create([
                'year' => $year,
                'month' => $month,
                'cost_per_hour' => $generalMaster->value,
                'notes' => 'Creado automáticamente desde general_masters',
                'created_by' => auth()->id(),
            ]);
        }
    }
}
