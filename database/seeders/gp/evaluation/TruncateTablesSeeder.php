<?php

namespace Database\Seeders\gp\evaluation;

use App\Models\gp\gestionhumana\evaluacion\DetailedDevelopmentPlan;
use App\Models\gp\gestionhumana\evaluacion\DevelopmentPlanTask;
use App\Models\gp\gestionhumana\evaluacion\Evaluation;
use App\Models\gp\gestionhumana\evaluacion\EvaluationCycle;
use App\Models\gp\gestionhumana\evaluacion\EvaluationCycleCategoryDetail;
use App\Models\gp\gestionhumana\evaluacion\EvaluationDashboard;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPerson;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonCompetenceDetail;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonCycleDetail;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonDashboard;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonResult;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TruncateTablesSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    DB::statement('SET FOREIGN_KEY_CHECKS=0;');

    DevelopmentPlanTask::query()->truncate();
    DetailedDevelopmentPlan::query()->truncate();
    EvaluationPersonCycleDetail::query()->truncate();
    EvaluationPersonCompetenceDetail::query()->truncate();
    EvaluationWorker::query()->truncate();
    EvaluationPersonResult::query()->truncate();
    EvaluationPersonDashboard::query()->truncate();
    EvaluationDashboard::query()->truncate();
    EvaluationCycleCategoryDetail::query()->truncate();
    Evaluation::query()->truncate();
    EvaluationCycle::query()->truncate();

    DB::statement('SET FOREIGN_KEY_CHECKS=1;');
  }
}
