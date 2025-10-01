<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class ShapshotAssignSede implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $year = now()->year;
        $month = now()->month;
        $assignments = DB::table('ap_assign_company_branch')->get();

        foreach ($assignments as $a) {
            DB::table('ap_assign_company_branch')->updateOrInsert(
                [
                    'company_branch_id' => $a->company_branch_id,
                    'asesor_id' => $a->asesor_id,
                    'anio' => $year,
                    'mes' => $month,
                ],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
