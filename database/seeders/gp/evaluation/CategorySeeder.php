<?php

namespace Database\Seeders\gp\evaluation;

use App\Http\Services\gp\gestionhumana\evaluacion\EvaluationCategoryObjectiveDetailService;
use App\Models\gp\gestionhumana\evaluacion\EvaluationCategoryObjectiveDetail;
use App\Models\gp\gestionhumana\evaluacion\HierarchicalCategory;
use App\Models\gp\gestionhumana\evaluacion\HierarchicalCategoryDetail;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    $lavadoLimpieza = [65, 75, 92, 135];
    $limpiezaConsejeria = [19, 66, 76, 93, 109, 113, 116, 136, 150, 160, 171, 195, 204, 214, 253, 330, 334];
    $agentesSeguridad = [257, 258, 259, 260, 261, 262, 263, 264, 266, 267, 268, 269, 270, 271, 329, 333, 350];
    $asistenteAdministrativoAp = [85, 117];
    $codificadorRepuetos = [77, 97, 139];

    $categoryLavadoLimpieza = HierarchicalCategory::create([
      'name' => 'Auxiliar De Lavado Y Limpieza',
      'description' => 'Description for the category Auxiliar De Lavado Y Limpieza',
      'hasObjectives' => false,
      'excluded_from_evaluation' => false,
    ]);

    foreach ($lavadoLimpieza as $id) {
      HierarchicalCategoryDetail::create([
        'hierarchical_category_id' => $categoryLavadoLimpieza->id,
        'position_id' => $id,
      ]);
    }

    $categoryLimpiezaConsejeria = HierarchicalCategory::create([
      'name' => 'Auxiliar De Limpieza Y Conserjeria',
      'description' => 'Description for the category Auxiliar De Limpieza Y Conserjeria',
      'hasObjectives' => false,
      'excluded_from_evaluation' => false,
    ]);

    foreach ($limpiezaConsejeria as $id) {
      HierarchicalCategoryDetail::create([
        'hierarchical_category_id' => $categoryLimpiezaConsejeria->id,
        'position_id' => $id,
      ]);
    }

    $categoryAgenteSeguridad = HierarchicalCategory::create([
      'name' => 'Agente De Seguridad',
      'description' => 'Description for the category Agente De Seguridad',
      'hasObjectives' => false,
      'excluded_from_evaluation' => false,
    ]);

    foreach ($agentesSeguridad as $id) {
      HierarchicalCategoryDetail::create([
        'hierarchical_category_id' => $categoryAgenteSeguridad->id,
        'position_id' => $id,
      ]);
    }

    $categoryAsistenteAdministrativoAp = HierarchicalCategory::create([
      'name' => 'Asistente Administrativo Ap',
      'description' => 'Description for the category Asistente Administrativo Ap',
      'hasObjectives' => false,
      'excluded_from_evaluation' => false,
    ]);

    foreach ($asistenteAdministrativoAp as $id) {
      HierarchicalCategoryDetail::create([
        'hierarchical_category_id' => $categoryAsistenteAdministrativoAp->id,
        'position_id' => $id,
      ]);
    }

    $asistenteContabilidad = HierarchicalCategory::where('name', 'Asistente De Contabilidad')->first();

    HierarchicalCategoryDetail::create([
      'hierarchical_category_id' => $asistenteContabilidad->id,
      'position_id' => 149,
    ]);

    $asistenteDeAlmacen = HierarchicalCategory::where('name', 'Asistente De Almacén Ap')->first();

    HierarchicalCategoryDetail::create([
      'hierarchical_category_id' => $asistenteDeAlmacen->id,
      'position_id' => 251,
    ]);

    $analistaNegocio = HierarchicalCategory::create([
      'name' => 'Analista de Negocio',
      'description' => 'Description for the category Analista de Negocio',
      'hasObjectives' => false,
      'excluded_from_evaluation' => false,
    ]);

    HierarchicalCategoryDetail::create([
      'hierarchical_category_id' => $analistaNegocio->id,
      'position_id' => 319,
    ]);

    $categoryJefeAdministracionComercial = HierarchicalCategory::create([
      'name' => 'Jefe de Administración comercial',
      'description' => 'Description for the category Jefe de Administración comercial',
      'hasObjectives' => false,
      'excluded_from_evaluation' => false,
    ]);

    HierarchicalCategoryDetail::create([
      'hierarchical_category_id' => $categoryJefeAdministracionComercial->id,
      'position_id' => 355,
    ]);

    $categoryAuditorInterno = HierarchicalCategory::create([
      'name' => 'Auditor Interno',
      'description' => 'Description for the category Auditor Interno',
      'hasObjectives' => false,
      'excluded_from_evaluation' => false,
    ]);

    HierarchicalCategoryDetail::create([
      'hierarchical_category_id' => $categoryAuditorInterno->id,
      'position_id' => 220,
    ]);

    $gestorDeInmatriculacion = HierarchicalCategory::where('name', 'Gestor De Inmatriculacion')->first();

    HierarchicalCategoryDetail::create([
      'hierarchical_category_id' => $gestorDeInmatriculacion->id,
      'position_id' => 123,
    ]);

    $categoryOperadorDeCctv = HierarchicalCategory::create([
      'name' => 'Operador De Cctv',
      'description' => 'Description for the category Operador De Cctv',
      'hasObjectives' => false,
      'excluded_from_evaluation' => false,
    ]);

    HierarchicalCategoryDetail::create([
      'hierarchical_category_id' => $categoryOperadorDeCctv->id,
      'position_id' => 299,
    ]);

    $categoryCodificadorDeRepuestos = HierarchicalCategory::create([
      'name' => 'Codificador De Repuestos',
      'description' => 'Description for the category Codificador De Repuestos',
      'hasObjectives' => false,
      'excluded_from_evaluation' => false,
    ]);

    foreach ($codificadorRepuetos as $id) {
      HierarchicalCategoryDetail::create([
        'hierarchical_category_id' => $categoryCodificadorDeRepuestos->id,
        'position_id' => $id,
      ]);
    }

    $asistenteDeReparto = HierarchicalCategory::where('name', 'Asistente De Reparto')->first();

    HierarchicalCategoryDetail::create([
      'hierarchical_category_id' => $asistenteDeReparto->id,
      'position_id' => 243,
    ]);


    $categoryObjectiveService = new EvaluationCategoryObjectiveDetailService();
    $categoryObjectiveService->assignMissingObjectives();

//    6. Actualizar a false fixedWeight en todos los objetivos
    EvaluationCategoryObjectiveDetail::query()->update(['fixedWeight' => false]);
  }
}
