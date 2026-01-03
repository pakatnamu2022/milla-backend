<?php

namespace Database\Seeders\gp\evaluation;

use App\Http\Services\gp\gestionhumana\evaluacion\EvaluationCategoryObjectiveDetailService;
use App\Models\gp\gestionhumana\evaluacion\Evaluation;
use App\Models\gp\gestionhumana\evaluacion\EvaluationCategoryCompetenceDetail;
use App\Models\gp\gestionhumana\evaluacion\EvaluationCategoryObjectiveDetail;
use App\Models\gp\gestionhumana\evaluacion\EvaluationCycle;
use App\Models\gp\gestionhumana\evaluacion\EvaluationCycleCategoryDetail;
use App\Models\gp\gestionhumana\evaluacion\EvaluationDashboard;
use App\Models\gp\gestionhumana\evaluacion\EvaluationMetric;
use App\Models\gp\gestionhumana\evaluacion\EvaluationObjective;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPerson;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonCycleDetail;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonDashboard;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonDetail;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonResult;
use App\Models\gp\gestionhumana\evaluacion\HierarchicalCategory;
use App\Models\gp\gestionhumana\evaluacion\HierarchicalCategoryDetail;
use App\Models\gp\gestionhumana\personal\Worker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ObjectiveSeeder2 extends Seeder
{
  public function run(): void
  {
    $data = [
      [
        "dni" => "45727231",
        "objective" => "Reingresos de vehículos",
        "metric" => "Unidad",
        "goal" => 2,
        "isAscending" => 0,
        "weight" => "50"
      ],
      [
        "dni" => "45727231",
        "objective" => "Eficiencia de OT > 70%",
        "metric" => "Porcentaje",
        "goal" => 70,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "73748430",
        "objective" => "Cumplimiento con la actualización de información hoja de trabajo de Fact y cobranza",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "73748430",
        "objective" => "Control de parihuelas Gloria",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "73748430",
        "objective" => "Aplicación de detracciones",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "73748430",
        "objective" => "Cumplimiento con la recuperación de documentos para facturación",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "41086411",
        "objective" => "Cumplimiento mensual de los límites de velocidad en ruta",
        "metric" => "Porcentaje",
        "goal" => 99,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "41086411",
        "objective" => "Cero multas por infringir las normas de tránsito en el desarrollo de sus funciones",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "41086411",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "41086411",
        "objective" => "Cero incidencias en la ruta asignada (robos, choques,etc)",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "71319927",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidad",
        "goal" => 13,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "71319927",
        "objective" => "Colocación de créditos",
        "metric" => "Unidad",
        "goal" => 3,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "71319927",
        "objective" => "Colocación de seguros",
        "metric" => "Unidad",
        "goal" => 1,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "47620737",
        "objective" => "Incrementar el % de cumplimiento de la programaciòn (Servicios entregados a tiempo\/total de servicios
programados)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => "33"
      ],
      [
        "dni" => "47620737",
        "objective" => "No tener robos por falta de seguimiento y monitoreo",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "33"
      ],
      [
        "dni" => "47620737",
        "objective" => "Accidente y\/o Siniestros por responsabilidad el equipo de operaciones y mantenimiento",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "34"
      ],
      [
        "dni" => "42330495",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Porcentaje",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "42330495",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "42330495",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Soles",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "42330495",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "32932405",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "10"
      ],
      [
        "dni" => "47085242",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Porcentaje",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "47085242",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "47085242",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Soles",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "47085242",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "75421824",
        "objective" => "Arqueo de caja, sin diferencias ",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "75421824",
        "objective" => "Cuadres de caja oportunos",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "75421824",
        "objective" => "Envío de reportes oportunos.",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "75421824",
        "objective" => "Informe de estado de las CXC.",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "70059908",
        "objective" => "Cuadres de caja oportunos",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "70059908",
        "objective" => "Arqueo de caja, sin diferencias ",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "40502833",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidad",
        "goal" => 6,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "40502833",
        "objective" => "Colocación de créditos",
        "metric" => "Unidad",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "40502833",
        "objective" => "Colocación de seguros",
        "metric" => "Unidad",
        "goal" => 1,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "16664102",
        "objective" => "Notas de crèdito a proveedores ",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "16664102",
        "objective" => "Pago a proveedores",
        "metric" => "Porcentaje",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "16664102",
        "objective" => "Gestión de compras ",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "16664102",
        "objective" => "Efectividad de liquidaciones: % de reconocimeinto de la liquidacion \/ total de liquidaciones
registradas",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "76752460",
        "objective" => "Eficiencia de OT > 70%",
        "metric" => "Porcentaje",
        "goal" => 70,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "76752460",
        "objective" => "Reingresos de vehículos",
        "metric" => "Unidad",
        "goal" => 2,
        "isAscending" => 0,
        "weight" => "50"
      ],
      [
        "dni" => "18186852",
        "objective" => "Cumplimiento mensual de los límites de velocidad en ruta",
        "metric" => "Porcentaje",
        "goal" => 99,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "18186852",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "18186852",
        "objective" => "Cero multas por infringir las normas de tránsito en el desarrollo de sus funciones",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "18186852",
        "objective" => "Cero incidencias en la ruta asignada (robos, choques,etc)",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "16802538",
        "objective" => "Seguimiento de pedidos de compra de vehículos, conciliando con el sistema de emisión de facturas",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "33"
      ],
      [
        "dni" => "16802538",
        "objective" => "Obtener un resultado mayor o igual a 80% en NPS.",
        "metric" => "Porcentaje",
        "goal" => 80,
        "isAscending" => 1,
        "weight" => "33"
      ],
      [
        "dni" => "16802538",
        "objective" => "Cumplimiento del 100% del envío de las solicitudes de nota de crédito (7 días hábiles)",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "34"
      ],
      [
        "dni" => "16753465",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Porcentaje",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "16753465",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "16753465",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Soles",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "16753465",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "47386012",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Porcentaje",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "47386012",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "47386012",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "47386012",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "70086807",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidad",
        "goal" => 6,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "70086807",
        "objective" => "Colocación de créditos",
        "metric" => "Unidad",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "70086807",
        "objective" => "Colocación de seguros",
        "metric" => "Unidad",
        "goal" => 1,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "44063728",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "10"
      ],
      [
        "dni" => "77668182",
        "objective" => "Eficiencia de OT > 70%",
        "metric" => "Porcentaje",
        "goal" => 70,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "77668182",
        "objective" => "Reingresos de vehículos",
        "metric" => "Unidad",
        "goal" => 2,
        "isAscending" => 0,
        "weight" => "50"
      ],
      [
        "dni" => "76252984",
        "objective" => "Eficiencia de OT > 70%",
        "metric" => "Porcentaje",
        "goal" => 70,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "76252984",
        "objective" => "Reingresos de vehículos",
        "metric" => "Unidad",
        "goal" => 2,
        "isAscending" => 0,
        "weight" => "50"
      ],
      [
        "dni" => "73747580",
        "objective" => "Calidad de servicio - Mesa de ayuda (Milla \/ Dynamics)",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "73747580",
        "objective" => "Promedio de efectividad en tiempo de atención - Soporte TIC's",
        "metric" => "Minutos",
        "goal" => 45,
        "isAscending" => 0,
        "weight" => "50"
      ],
      [
        "dni" => "72790935",
        "objective" => "Eficiencia de OT > 70%",
        "metric" => "Porcentaje",
        "goal" => 70,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "72790935",
        "objective" => "Reingresos de vehículos",
        "metric" => "Unidad",
        "goal" => 2,
        "isAscending" => 0,
        "weight" => "50"
      ],
      [
        "dni" => "19255538",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Porcentaje",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "19255538",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "19255538",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Soles",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "19255538",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "72116004",
        "objective" => "Eficiencia de OT > 70%",
        "metric" => "Porcentaje",
        "goal" => 70,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "72116004",
        "objective" => "Reingresos de vehículos",
        "metric" => "Unidad",
        "goal" => 2,
        "isAscending" => 0,
        "weight" => "50"
      ],
      [
        "dni" => "46468460",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "10"
      ],
      [
        "dni" => "47241820",
        "objective" => "Cumplimiento mensual de los límites de velocidad en ruta",
        "metric" => "Porcentaje",
        "goal" => 99,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "47241820",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "47241820",
        "objective" => "Cero multas por infringir las normas de tránsito en el desarrollo de sus funciones",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "47241820",
        "objective" => "Cero incidencias en la ruta asignada (robos, choques,etc)",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "80541083",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidad",
        "goal" => 94,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "80541083",
        "objective" => "Rentabilidad bruta por grupo de marcas",
        "metric" => "Porcentaje",
        "goal" => 5,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "80541083",
        "objective" => "Cumplimiento de colaterales: Financiamiento",
        "metric" => "Unidad",
        "goal" => 24,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "80541083",
        "objective" => "Cumplimiento de colaterales: Seguros",
        "metric" => "Unidad",
        "goal" => 18,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "80541083",
        "objective" => "Porcentaje de MS",
        "metric" => "Porcentaje",
        "goal" => 11.5,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "70860579",
        "objective" => "Eficiencia de OT > 70%",
        "metric" => "Porcentaje",
        "goal" => 70,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "70860579",
        "objective" => "Reingresos de vehículos",
        "metric" => "Unidad",
        "goal" => 2,
        "isAscending" => 0,
        "weight" => "50"
      ],
      [
        "dni" => "42119750",
        "objective" => "Cobranza ",
        "metric" => "dias",
        "goal" => 20,
        "isAscending" => 0,
        "weight" => "20"
      ],
      [
        "dni" => "42119750",
        "objective" => "Posicionamiento digital (SEO) AP",
        "metric" => "Porcentaje",
        "goal" => 3,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "42119750",
        "objective" => "Inversión ",
        "metric" => "Porcentaje",
        "goal" => 25,
        "isAscending" => 0,
        "weight" => "20"
      ],
      [
        "dni" => "42119750",
        "objective" => "Win rate de Lead AP mayor al 3%",
        "metric" => "Porcentaje",
        "goal" => 3,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "42119750",
        "objective" => "Reputación Chiclayo ",
        "metric" => "Puntaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "5%"
      ],
      [
        "dni" => "42119750",
        "objective" => "Reputacion Cajamarca ",
        "metric" => "Puntaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "5%"
      ],
      [
        "dni" => "42119750",
        "objective" => "Reputación Jaén ",
        "metric" => "Puntaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "5%"
      ],
      [
        "dni" => "42119750",
        "objective" => "Reputación Piura ",
        "metric" => "Puntaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "5%"
      ],
      [
        "dni" => "16804371",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Porcentaje",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "16804371",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "16804371",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Soles",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "16804371",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "45017690",
        "objective" => "Gestión de compras ",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "45017690",
        "objective" => "Notas de crèdito a proveedores ",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "45017690",
        "objective" => "Efectividad de liquidaciones: % de reconocimeinto de la liquidacion \/ total de liquidaciones
registradas",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "45017690",
        "objective" => "Cumplimiento de ventas en sede ",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "45017690",
        "objective" => "Cumplimiento de cobranza de sede",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "42374851",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Porcentaje",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "42374851",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "42374851",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Soles",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "42374851",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "43920495",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Porcentaje",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "43920495",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "43920495",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Soles",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "43920495",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "45695736",
        "objective" => "Utilidad Operativa",
        "metric" => "Soles",
        "goal" => 135000,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "45695736",
        "objective" => "Número de viajes",
        "metric" => "Numero",
        "goal" => 350,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "71325400",
        "objective" => "Cero soles de multa por errores contables en la empresa a cargo\n",
        "metric" => "Soles",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "20"
      ],
      [
        "dni" => "71325400",
        "objective" => "Exactitud en registros contables",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 0,
        "weight" => "50"
      ],
      [
        "dni" => "71325400",
        "objective" => "Entrega de cierres contables en tiempo de empresa a cargo (en los primeros 5 dias habiles)\n",
        "metric" => "Porcentaje",
        "goal" => 95,
        "isAscending" => 1,
        "weight" => "30"
      ],
      [
        "dni" => "42704083",
        "objective" => "Cumplimiento de presupuesto comercial",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "42704083",
        "objective" => "% de Efectividad de Cobranza",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "46466488",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidad",
        "goal" => 7,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "46466488",
        "objective" => "Colocación de créditos",
        "metric" => "Unidad",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "46466488",
        "objective" => "Colocación de seguros",
        "metric" => "Unidad",
        "goal" => 1,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "45577414",
        "objective" => "Cumplimiento mensual de los límites de velocidad en ruta",
        "metric" => "Porcentaje",
        "goal" => 99,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "45577414",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "45577414",
        "objective" => "Cero multas por infringir las normas de tránsito en el desarrollo de sus funciones",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "45577414",
        "objective" => "Cero incidencias en la ruta asignada (robos, choques,etc)",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "75756058",
        "objective" => "Cumplir la meta de OT mensual",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "33"
      ],
      [
        "dni" => "75756058",
        "objective" => "Cumplimiento de NPS",
        "metric" => "Porcentaje",
        "goal" => 80,
        "isAscending" => 1,
        "weight" => "33"
      ],
      [
        "dni" => "75756058",
        "objective" => "Cumplimiento de meta mensual de venta de accesorios",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "34"
      ],
      [
        "dni" => "44652712",
        "objective" => "Cero soles de multa por errores contables en la empresa a cargo\n",
        "metric" => "Soles",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "33"
      ],
      [
        "dni" => "44652712",
        "objective" => "Cero errores en el registro de información contable de manera mensual",
        "metric" => "Errores",
        "goal" => 2,
        "isAscending" => 0,
        "weight" => "33"
      ],
      [
        "dni" => "44652712",
        "objective" => "Entrega de cierre contable mensual integral (en los primeros 12 días calendario)",
        "metric" => "Dias",
        "goal" => 12,
        "isAscending" => 0,
        "weight" => "34"
      ],
      [
        "dni" => "19256673",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "10"
      ],
      [
        "dni" => "27167946",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Porcentaje",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "27167946",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "27167946",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Soles",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "27167946",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "41893192",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "10"
      ],
      [
        "dni" => "45092596",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Porcentaje",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "45092596",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "45092596",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Soles",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "45092596",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "41399449",
        "objective" => "Cumplimiento de presupuesto comercial",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "41399449",
        "objective" => "% de Efectividad de Cobranza",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "41399449",
        "objective" => "Mix de productos",
        "metric" => "Unidad",
        "goal" => 5,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "41399449",
        "objective" => "Cliente Nuevo al mes",
        "metric" => "Unidad",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "72904816",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "10"
      ],
      [
        "dni" => "05644606",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidad",
        "goal" => 12,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "05644606",
        "objective" => "Colocación de créditos",
        "metric" => "Unidad",
        "goal" => 3,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "05644606",
        "objective" => "Colocación de seguros",
        "metric" => "Unidad",
        "goal" => 1,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "70408777",
        "objective" => "Reingresos de vehículos\n",
        "metric" => "Unidad",
        "goal" => 2,
        "isAscending" => 0,
        "weight" => "50"
      ],
      [
        "dni" => "70408777",
        "objective" => "Cumplimiento de OT>100%",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "45523776",
        "objective" => "Vales pendientes rendidos al 100%",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "45523776",
        "objective" => "Arqueo de caja, sin diferencias ",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "45523776",
        "objective" => "Cuadres de caja oportunos",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "45523776",
        "objective" => "Envio de reportes oportunos",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "45523776",
        "objective" => "Informe de estado de las cuentas por cobrar",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "74413833",
        "objective" => "Eficiencia de OT > 70%",
        "metric" => "Porcentaje",
        "goal" => 70,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "74413833",
        "objective" => "Reingresos de vehículos",
        "metric" => "Unidad",
        "goal" => 2,
        "isAscending" => 0,
        "weight" => "50"
      ],
      [
        "dni" => "47080684",
        "objective" => "Eficiencia de OT > 70%",
        "metric" => "Porcentaje",
        "goal" => 70,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "47080684",
        "objective" => "Reingresos de vehículos",
        "metric" => "Unidad",
        "goal" => 2,
        "isAscending" => 0,
        "weight" => "50"
      ],
      [
        "dni" => "46280064",
        "objective" => "Cuadres de caja oportunos",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "46280064",
        "objective" => "Arqueo de caja, sin diferencias ",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "70897298",
        "objective" => "Promedio de vacaciones DP y TP",
        "metric" => "Unidad",
        "goal" => 24,
        "isAscending" => 0,
        "weight" => "20"
      ],
      [
        "dni" => "70897298",
        "objective" => "Procesos dentro del plazo",
        "metric" => "Porcentaje",
        "goal" => 80,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "70897298",
        "objective" => "Porcentaje de legajos digitalizados",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "70897298",
        "objective" => "Plazo de envio de boletas DP",
        "metric" => "Dias",
        "goal" => 5,
        "isAscending" => 0,
        "weight" => "20"
      ],
      [
        "dni" => "70897298",
        "objective" => "Plazo de envio de boletas TP",
        "metric" => "Dias",
        "goal" => 7,
        "isAscending" => 0,
        "weight" => "20"
      ],
      [
        "dni" => "42352466",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "10"
      ],
      [
        "dni" => "02767057",
        "objective" => "Cumplimiento mensual de los límites de velocidad en ruta",
        "metric" => "Porcentaje",
        "goal" => 99,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "02767057",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "02767057",
        "objective" => "Cero multas por infringir las normas de tránsito en el desarrollo de sus funciones",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "02767057",
        "objective" => "Cero incidencias en la ruta asignada (robos, choques,etc)",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "48139848",
        "objective" => "Cumplimiento mensual de los límites de velocidad en ruta",
        "metric" => "Porcentaje",
        "goal" => 99,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "48139848",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "48139848",
        "objective" => "Cero multas por infringir las normas de tránsito en el desarrollo de sus funciones\n",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "48139848",
        "objective" => "Cero incidencias en la ruta asignada (robos, choques,etc)\n",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "16668399",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidad",
        "goal" => 58,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "16668399",
        "objective" => "Meta en seguros",
        "metric" => "Número",
        "goal" => 5,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "16668399",
        "objective" => "Meta en créditos",
        "metric" => "Número",
        "goal" => 14,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "16668399",
        "objective" => "Alcance de Mix de Marca",
        "metric" => "Unidad",
        "goal" => 4,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "40291710",
        "objective" => "Cumplimiento al 100% de la meta de venta establecida",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "40291710",
        "objective" => "% de Efectividad de Cobranza",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "40291710",
        "objective" => "Rentabilidad bruta mayor igual a 5.6%",
        "metric" => "Porcentaje",
        "goal" => 5.6,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "40291710",
        "objective" => "Incremento de cartera",
        "metric" => "Unidad",
        "goal" => 4,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "40291710",
        "objective" => "Mix de productos",
        "metric" => "Unidad",
        "goal" => 5,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "74868424",
        "objective" => "Cero soles de multa por errores contables en la empresa a cargo\n",
        "metric" => "Soles",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "33"
      ],
      [
        "dni" => "74868424",
        "objective" => "Entrega de cierres contables en tiempo de empresa a cargo (12 dias)\n",
        "metric" => "Dias",
        "goal" => 12,
        "isAscending" => 0,
        "weight" => "33"
      ],
      [
        "dni" => "74868424",
        "objective" => "Presentación de DDJJ mensuales a SUNAT ",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "34"
      ],
      [
        "dni" => "16710404",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "10"
      ],
      [
        "dni" => "27436038",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "10"
      ],
      [
        "dni" => "44184967",
        "objective" => "Eficiencia de OT > 70%",
        "metric" => "Porcentaje",
        "goal" => 70,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "44184967",
        "objective" => "Reingresos de vehículos",
        "metric" => "Unidad",
        "goal" => 2,
        "isAscending" => 0,
        "weight" => "50"
      ],
      [
        "dni" => "41255232",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Porcentaje",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "41255232",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "41255232",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Soles",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "41255232",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "45421958",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Porcentaje",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "45421958",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "45421958",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Soles",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "45421958",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "32960097",
        "objective" => "Promedio de cumplimiento de presupuesto mensual de AP, DP y TP",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "10"
      ],
      [
        "dni" => "26619707",
        "objective" => "Stock saludable de un mes por sede",
        "metric" => "Unidad",
        "goal" => 1,
        "isAscending" => 0,
        "weight" => "10"
      ],
      [
        "dni" => "47037915",
        "objective" => "Cumplimiento mensual de los límites de velocidad en ruta",
        "metric" => "Porcentaje",
        "goal" => 99,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "47037915",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "47037915",
        "objective" => "Cero multas por infringir las normas de tránsito en el desarrollo de sus funciones",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "47037915",
        "objective" => "Cero incidencias en la ruta asignada (robos, choques,etc)",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "47704283",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "10"
      ],
      [
        "dni" => "44764424",
        "objective" => "Cumplimiento al 100% de la meta de venta establecida",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "44764424",
        "objective" => "% de Efectividad de Cobranza",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "44764424",
        "objective" => "Rentabilidad bruta mayor igual a 5.6%",
        "metric" => "Porcentaje",
        "goal" => 5.6,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "44764424",
        "objective" => "Incremento de cartera",
        "metric" => "Unidad",
        "goal" => 4,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "44764424",
        "objective" => "Mix de productos",
        "metric" => "Unidad",
        "goal" => 5,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "42315795",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Porcentaje",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "42315795",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "42315795",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Soles",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "42315795",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "76009888",
        "objective" => "Cero soles de multa por errores contables en la empresa a cargo",
        "metric" => "Soles",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "50"
      ],
      [
        "dni" => "76009888",
        "objective" => "Entrega de cierres contables en tiempo de empresa a cargo (en los primeros 7 dias habiles)\n",
        "metric" => "dias",
        "goal" => 7,
        "isAscending" => 0,
        "weight" => "50"
      ],
      [
        "dni" => "76449504",
        "objective" => "Cumplir la meta de OT mensual",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "33"
      ],
      [
        "dni" => "76449504",
        "objective" => "Cumplimiento de NPS",
        "metric" => "Porcentaje",
        "goal" => 80,
        "isAscending" => 1,
        "weight" => "33"
      ],
      [
        "dni" => "76449504",
        "objective" => "Cumplimiento de meta mensual de venta de accesorios",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "34"
      ],
      [
        "dni" => "16787219",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidad",
        "goal" => 136,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "16787219",
        "objective" => "Rentabilidad bruta por grupo de marcas",
        "metric" => "Porcentaje",
        "goal" => 5,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "16787219",
        "objective" => "Cumplimiento de colaterales: Financiamiento",
        "metric" => "Unidad",
        "goal" => 32,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "16787219",
        "objective" => "Cumplimiento de colaterales: Seguros",
        "metric" => "Unidad",
        "goal" => 12,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "16787219",
        "objective" => "Porcentaje de MS",
        "metric" => "Porcentaje",
        "goal" => 13,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "80547267",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "10"
      ],
      [
        "dni" => "47618600",
        "objective" => "Stock saludable de un mes por sede",
        "metric" => "Unidad",
        "goal" => 1,
        "isAscending" => 0,
        "weight" => "10"
      ],
      [
        "dni" => "76574187",
        "objective" => "Vales pendientes rendidos al 100%",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "76574187",
        "objective" => "Arqueo de caja, sin diferencias ",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "76574187",
        "objective" => "Cuadres de caja oportunos",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "76574187",
        "objective" => "Envio de reportes oportunos",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "76574187",
        "objective" => "Informe de estado de las cuentas por cobrar",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "45243028",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidad",
        "goal" => 5,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "45243028",
        "objective" => "Colocación de créditos",
        "metric" => "Unidad",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "45243028",
        "objective" => "Colocación de seguros",
        "metric" => "Unidad",
        "goal" => 1,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "73033157",
        "objective" => "Cumplimiento del ICD al 90% por sede\n",
        "metric" => "Porcentaje",
        "goal" => 90,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "73033157",
        "objective" => "Cumplimiento de los estándares de Post Venta por parte de Derco al 80%\n",
        "metric" => "Porcentaje",
        "goal" => 80,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "80350561",
        "objective" => "Número de unidades entregadas",
        "metric" => "Numero",
        "goal" => 6,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "80350561",
        "objective" => "Colocación de créditos",
        "metric" => "Unidad",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "80350561",
        "objective" => "Colocación de seguros",
        "metric" => "Unidad",
        "goal" => 1,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "72342187",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidad",
        "goal" => 8,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "72342187",
        "objective" => "Colocación de créditos",
        "metric" => "Unidad",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "72342187",
        "objective" => "Colocación de seguros",
        "metric" => "Unidad",
        "goal" => 1,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "42137357",
        "objective" => "Meta de facturación mensual mayor o igual a 100%\n",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "10"
      ],
      [
        "dni" => "73468572",
        "objective" => "Crecimiento de comunidad (SEO)",
        "metric" => "Porcentaje",
        "goal" => 3,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "73468572",
        "objective" => "Generación de Leads (Promedio mensual)",
        "metric" => "Numero",
        "goal" => 500,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "73468572",
        "objective" => "Nota de Reputación SCORE total AP",
        "metric" => "Numero",
        "goal" => 850,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "73468572",
        "objective" => "Winrate",
        "metric" => "Porcentaje",
        "goal" => 2.5,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "73468572",
        "objective" => "Cumplimiento del calendario de campañas",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "76627801",
        "objective" => "Vales pendientes rendidos al 100%",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "76627801",
        "objective" => "Arqueo de caja, sin diferencias ",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "76627801",
        "objective" => "Cuadres de caja oportunos",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "76627801",
        "objective" => "Envio de reportes oportunos",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "76627801",
        "objective" => "Informe de estado de las cuentas por cobrar",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "48021041",
        "objective" => "Cuadres de caja oportunos\n",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "48021041",
        "objective" => "Arqueo de caja, sin diferencias \n",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "75581810",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "10"
      ],
      [
        "dni" => "74325638",
        "objective" => "Eficiencia de OT > 70%",
        "metric" => "Porcentaje",
        "goal" => 70,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "74325638",
        "objective" => "Reingresos de vehículos",
        "metric" => "Unidad",
        "goal" => 2,
        "isAscending" => 0,
        "weight" => "50"
      ],
      [
        "dni" => "41694134",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Porcentaje",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "41694134",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "41694134",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Soles",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "41694134",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "42606028",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidad",
        "goal" => 13,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "42606028",
        "objective" => "Colocación de créditos",
        "metric" => "Unidad",
        "goal" => 3,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "42606028",
        "objective" => "Colocación de seguros",
        "metric" => "Unidad",
        "goal" => 1,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "02683938",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "10"
      ],
      [
        "dni" => "26729421",
        "objective" => "Seguimiento de pedidos de compra de vehículos, conciliando con el sistema de emisión de facturas",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "33"
      ],
      [
        "dni" => "26729421",
        "objective" => "Obtener un resultado mayor o igual a 80% en NPS.",
        "metric" => "Porcentaje",
        "goal" => 80,
        "isAscending" => 1,
        "weight" => "33"
      ],
      [
        "dni" => "26729421",
        "objective" => "Cumplimiento del 100% del envío de las solicitudes de nota de crédito (7 días hábiles)",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "34"
      ],
      [
        "dni" => "004565680",
        "objective" => "Seguimiento de pedidos de compra de vehículos, conciliando con el sistema de emisión de facturas",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "33"
      ],
      [
        "dni" => "004565680",
        "objective" => "Obtener un resultado mayor o igual a 80% en NPS.",
        "metric" => "Porcentaje",
        "goal" => 80,
        "isAscending" => 1,
        "weight" => "34"
      ],
      [
        "dni" => "004565680",
        "objective" => "Cumplimiento del 100% del envío de las solicitudes de nota de crédito (7 días hábiles)",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "33"
      ],
      [
        "dni" => "74443725",
        "objective" => "Entrega de cierres contables en tiempo de empresa a cargo (en los primeros 7 dias habiles)",
        "metric" => "Dias",
        "goal" => 7,
        "isAscending" => 0,
        "weight" => "50"
      ],
      [
        "dni" => "74443725",
        "objective" => "Cero soles de multa por errores contables en la empresa a cargo",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "50"
      ],
      [
        "dni" => "02786294",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidad",
        "goal" => 7,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "02786294",
        "objective" => "Colocación de créditos",
        "metric" => "Unidad",
        "goal" => 1,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "41340413",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Porcentaje",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "41340413",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "41340413",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Soles",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "41340413",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "74881133",
        "objective" => "Cumplir la meta de OT mensual",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "33"
      ],
      [
        "dni" => "74881133",
        "objective" => "Cumplimiento de NPS",
        "metric" => "Porcentaje",
        "goal" => 80,
        "isAscending" => 1,
        "weight" => "33"
      ],
      [
        "dni" => "74881133",
        "objective" => "Cumplimiento de meta mensual de venta de accesorios",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "34"
      ],
      [
        "dni" => "72703707",
        "objective" => "Promedio de vacaciones AP y GP",
        "metric" => "Unidad",
        "goal" => 24,
        "isAscending" => 0,
        "weight" => "20"
      ],
      [
        "dni" => "72703707",
        "objective" => "Proceso dentro del plazo ",
        "metric" => "Porcentaje",
        "goal" => 80,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "72703707",
        "objective" => "Porcentaje de legajos digitalizados",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "72703707",
        "objective" => "Plazo de envio de boletas GP",
        "metric" => "Dias",
        "goal" => 5,
        "isAscending" => 0,
        "weight" => "20"
      ],
      [
        "dni" => "72703707",
        "objective" => "Plazo de envio de boletas AP",
        "metric" => "Dias",
        "goal" => 7,
        "isAscending" => 0,
        "weight" => "20"
      ],
      [
        "dni" => "40158039",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "10"
      ],
      [
        "dni" => "42819192",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "10"
      ],
      [
        "dni" => "42140416",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "10"
      ],
      [
        "dni" => "40896485",
        "objective" => "Cumplimiento al 100% de la meta de venta establecida",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "40896485",
        "objective" => "% de Efectividad de Cobranza",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "40896485",
        "objective" => "Rentabilidad bruta mayor igual a 5.6%",
        "metric" => "Porcentaje",
        "goal" => 5.6,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "40896485",
        "objective" => "Incremento de cartera",
        "metric" => "Unidad",
        "goal" => 4,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "40896485",
        "objective" => "Mix de productos",
        "metric" => "Unidad",
        "goal" => 5,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "42251736",
        "objective" => "Pagos ejecutados oportunamente",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "40"
      ],
      [
        "dni" => "42251736",
        "objective" => "Presentación contable de planilla",
        "metric" => "Dias",
        "goal" => 12,
        "isAscending" => 0,
        "weight" => "20"
      ],
      [
        "dni" => "42251736",
        "objective" => "Pagos ejecutados correctamente",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "40"
      ],
      [
        "dni" => "47678322",
        "objective" => "Meta de facturación mensual mayor o igual a 100%\n",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "10"
      ],
      [
        "dni" => "73092833",
        "objective" => "Tasa de reclamo menor o igual a 5% ",
        "metric" => "Porcentaje",
        "goal" => 5,
        "isAscending" => 0,
        "weight" => "10"
      ],
      [
        "dni" => "40521977",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Porcentaje",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "40521977",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "40521977",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Soles",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "40521977",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "44205462",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "10"
      ],
      [
        "dni" => "71900918",
        "objective" => "Eficiencia de OT > 70%",
        "metric" => "Porcentaje",
        "goal" => 70,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "71900918",
        "objective" => "Reingresos de vehículos",
        "metric" => "Unidad",
        "goal" => 2,
        "isAscending" => 0,
        "weight" => "50"
      ],
      [
        "dni" => "46635205",
        "objective" => "Eficiencia de OT > 70%",
        "metric" => "Porcentaje",
        "goal" => 70,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "46635205",
        "objective" => "Reingresos de vehículos",
        "metric" => "Unidad",
        "goal" => 2,
        "isAscending" => 0,
        "weight" => "50"
      ],
      [
        "dni" => "40524259",
        "objective" => "Notas de crèdito a proveedores ",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "40524259",
        "objective" => "Pago a proveedores",
        "metric" => "Porcentaje",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "40524259",
        "objective" => "Gestión de compras ",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "40524259",
        "objective" => "Efectividad de liquidaciones: % de reconocimeinto de la liquidacion \/ total de liquidaciones
registradas",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "42401576",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "10"
      ],
      [
        "dni" => "46815084",
        "objective" => "Cumplimiento mensual de los límites de velocidad en ruta",
        "metric" => "Porcentaje",
        "goal" => 99,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "46815084",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "46815084",
        "objective" => "Cero multas por infringir las normas de tránsito en el desarrollo de sus funciones",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "46815084",
        "objective" => "Cero incidencias en la ruta asignada (robos, choques,etc)",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "71908990",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "10"
      ],
      [
        "dni" => "75848561",
        "objective" => "Optimizar los tiempos de reparacion en un 55%",
        "metric" => "Porcentaje",
        "goal" => 55,
        "isAscending" => 0,
        "weight" => "50"
      ],
      [
        "dni" => "75848561",
        "objective" => "Mantenimentos correctivos de emergencia(Auxilios Mecánicos), no supere el 5% mensual de la flota",
        "metric" => "Porcentaje",
        "goal" => 5,
        "isAscending" => 0,
        "weight" => "50"
      ],
      [
        "dni" => "45704839",
        "objective" => "Nivel de servicio",
        "metric" => "Porcentaje",
        "goal" => 95,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "45704839",
        "objective" => "Cumplimiento del volumen de ventas de post venta",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "45704839",
        "objective" => "Cumplimiento de la venta de accesorios",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "45704839",
        "objective" => "Cumplimiento de rotacion de stock",
        "metric" => "Dias",
        "goal" => 30,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "72211890",
        "objective" => "Stock saludable de un mes por sede",
        "metric" => "Unidad",
        "goal" => 1,
        "isAscending" => 0,
        "weight" => "10"
      ],
      [
        "dni" => "09994093",
        "objective" => "Notas de crèdito a proveedores ",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "09994093",
        "objective" => "Pago a proveedores",
        "metric" => "Porcentaje",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "09994093",
        "objective" => "Gestión de compras ",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "09994093",
        "objective" => "Efectividad de liquidaciones: % de reconocimeinto de la liquidacion \/ total de liquidaciones
registradas",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "46906293",
        "objective" => "Seguimiento de pedidos de compra de vehículos, conciliando con el sistema de emisión de facturas",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "33"
      ],
      [
        "dni" => "46906293",
        "objective" => "Obtener un resultado mayor o igual a 80% en NPS.",
        "metric" => "Porcentaje",
        "goal" => 80,
        "isAscending" => 1,
        "weight" => "33"
      ],
      [
        "dni" => "46906293",
        "objective" => "Cumplimiento del 100% del envío de las solicitudes de nota de crédito (7 días hábiles)",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "34"
      ],
      [
        "dni" => "16662152",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Porcentaje",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "16662152",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "16662152",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Soles",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "16662152",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "72811912",
        "objective" => "Promedio de stock saludable de un mes en los cuatro almacenes",
        "metric" => "Unidad",
        "goal" => 1,
        "isAscending" => 0,
        "weight" => "10"
      ],
      [
        "dni" => "40654637",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidad",
        "goal" => 12,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "40654637",
        "objective" => "Colocación de créditos",
        "metric" => "Unidad",
        "goal" => 3,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "40654637",
        "objective" => "Colocación de seguros",
        "metric" => "Unidad",
        "goal" => 1,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "40803935",
        "objective" => "Cumplimiento de presupuesto comercial",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "40803935",
        "objective" => "% de Efectividad de Cobranza",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "40803935",
        "objective" => "Mix de productos",
        "metric" => "Unidad",
        "goal" => 5,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "40803935",
        "objective" => "Cliente Nuevo al mes",
        "metric" => "Unidad",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "45838857",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Porcentaje",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "45838857",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "45838857",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Soles",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "45838857",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "71600393",
        "objective" => "Stock saludable de un mes por sede",
        "metric" => "Unidad",
        "goal" => 1,
        "isAscending" => 0,
        "weight" => "10"
      ],
      [
        "dni" => "47189021",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidad",
        "goal" => 8,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "47189021",
        "objective" => "Colocación de créditos",
        "metric" => "Unidad",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "47189021",
        "objective" => "Colocación de seguros",
        "metric" => "Unidad",
        "goal" => 1,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "44868919",
        "objective" => "Inventarios cíclicos",
        "metric" => "Porcentaje",
        "goal" => 93,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "44868919",
        "objective" => "Control del costo de Suministros (ingresos y salidas)",
        "metric" => "Porcentaje",
        "goal" => 95,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "43945322",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidad",
        "goal" => 5,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "43945322",
        "objective" => "Colocación de créditos",
        "metric" => "Unidad",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "43945322",
        "objective" => "Colocación de seguros",
        "metric" => "Unidad",
        "goal" => 1,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "43579923",
        "objective" => "Nº de unidades vendidas en el mes en las 4 sedes",
        "metric" => "Unidades",
        "goal" => 215,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "43579923",
        "objective" => "% de market share AP",
        "metric" => "Porcentaje",
        "goal" => 23.2,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "43579923",
        "objective" => "Cumplimiento del volumen de ventas de post venta",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "43579923",
        "objective" => "Meses de stock de repuestos",
        "metric" => "Dias",
        "goal" => 30,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "45488727",
        "objective" => "Promedio de cumplimiento de presupuesto mensual de AP, DP y TP",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "33"
      ],
      [
        "dni" => "45488727",
        "objective" => "Tiempo de reclutamiento",
        "metric" => "Porcentaje",
        "goal" => 80,
        "isAscending" => 1,
        "weight" => "33"
      ],
      [
        "dni" => "45488727",
        "objective" => "Asegurar pagos correctos",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "34"
      ],
      [
        "dni" => "02855107",
        "objective" => "Cero soles de multa por errores contables en la empresa a cargo\n",
        "metric" => "Soles",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "33"
      ],
      [
        "dni" => "02855107",
        "objective" => "Cero errores en el registro de información contable de manera mensual",
        "metric" => "Errores",
        "goal" => 2,
        "isAscending" => 0,
        "weight" => "33"
      ],
      [
        "dni" => "02855107",
        "objective" => "Entrega de cierre contable mensual integral (en los primeros 12 días calendario)",
        "metric" => "Dias",
        "goal" => 12,
        "isAscending" => 0,
        "weight" => "34"
      ],
      [
        "dni" => "42082941",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidad",
        "goal" => 18,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "42082941",
        "objective" => "Meta en seguros",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "42082941",
        "objective" => "Meta en créditos",
        "metric" => "Número",
        "goal" => 6,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "42082941",
        "objective" => "Alcance de Mix de Marca",
        "metric" => "Unidad",
        "goal" => 6,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "72210478",
        "objective" => "Stock saludable de un mes por sede",
        "metric" => "Unidad",
        "goal" => 1,
        "isAscending" => 0,
        "weight" => "10"
      ],
      [
        "dni" => "42298543",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidad",
        "goal" => 12,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "42298543",
        "objective" => "Colocación de créditos",
        "metric" => "Unidad",
        "goal" => 3,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "42298543",
        "objective" => "Colocación de seguros",
        "metric" => "Unidad",
        "goal" => 1,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "17861917",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidad",
        "goal" => 9,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "17861917",
        "objective" => "Colocación de créditos",
        "metric" => "Unidad",
        "goal" => 3,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "17861917",
        "objective" => "Colocación de seguros",
        "metric" => "Unidad",
        "goal" => 1,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "44579660",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidad",
        "goal" => 6,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "44579660",
        "objective" => "Colocación de créditos",
        "metric" => "Unidad",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "44579660",
        "objective" => "Colocación de seguros",
        "metric" => "Unidad",
        "goal" => 1,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "71622182",
        "objective" => "Cumplimiento de la facturación de taller mas un incremento adicional del 2%",
        "metric" => "Porcentaje",
        "goal" => 102,
        "isAscending" => 1,
        "weight" => "33"
      ],
      [
        "dni" => "71622182",
        "objective" => "Cumplimiento de OT>100%",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "33"
      ],
      [
        "dni" => "71622182",
        "objective" => "Tasa de reclamos relacionados al servicio que brinda el jefe de taller",
        "metric" => "Porcentaje",
        "goal" => 1,
        "isAscending" => 0,
        "weight" => "34"
      ],
      [
        "dni" => "48493374",
        "objective" => "Programación de flota",
        "metric" => "Porcentaje",
        "goal" => 95,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "48493374",
        "objective" => "Incremento en ventas",
        "metric" => "Porcentaje",
        "goal" => 5,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "48493374",
        "objective" => "Rentabilidad",
        "metric" => "Porcentaje",
        "goal" => 15,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "48493374",
        "objective" => "Captación y propuesta de nuevos clientes",
        "metric" => "Número",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "46720670",
        "objective" => "Seguimiento, solicitud y cumplimiento de citas cerámicos",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "46720670",
        "objective" => "Recuperación de documentos cerámicos ",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "46720670",
        "objective" => "Recuperación y control de parihuelas cerámicos",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "46720670",
        "objective" => "Cumplimiento de facturación cerámicos",
        "metric" => "Porcentaje",
        "goal" => 95,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => 45801393,
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidad",
        "goal" => 49,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "45801393",
        "objective" => "Meta en seguros",
        "metric" => "Número",
        "goal" => 7,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "45801393",
        "objective" => "Meta en créditos",
        "metric" => "Número",
        "goal" => 14,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "45801393",
        "objective" => "Alcance de Mix de Marca",
        "metric" => "Unidad",
        "goal" => 5,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "45746188",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "10"
      ],
      [
        "dni" => "72318704",
        "objective" => "Cero soles de multa por errores contables en la empresa a cargo\n",
        "metric" => "Soles",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "33"
      ],
      [
        "dni" => "72318704",
        "objective" => "Presentación de DDJJ mensuales a SUNAT ",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "33"
      ],
      [
        "dni" => "72318704",
        "objective" => "Entrega de cierres contables en tiempo de empresa a cargo (en los primeros 10 dias habiles)\n",
        "metric" => "Dias",
        "goal" => 10,
        "isAscending" => 0,
        "weight" => "34"
      ],
      [
        "dni" => "48509128",
        "objective" => "Meta de facturación mensual mayor o igual a 100%\n",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "10"
      ],
      [
        "dni" => "16475507",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Porcentaje",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "16475507",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "16475507",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Soles",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "16475507",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "76311394",
        "objective" => "cumplimiento de cronograma de proyectos",
        "metric" => "Porcentaje",
        "goal" => 95,
        "isAscending" => 1,
        "weight" => "33"
      ],
      [
        "dni" => "76311394",
        "objective" => "CUMPLIMIENTO DE LOS PROCESOS DE INDUCCIÓN Y ONBOARDING",
        "metric" => "Porcentaje",
        "goal" => 95,
        "isAscending" => 1,
        "weight" => "33"
      ],
      [
        "dni" => "76311394",
        "objective" => "Satisfacción de los procesos de onboarding",
        "metric" => "Numero",
        "goal" => 3.5,
        "isAscending" => 1,
        "weight" => "34"
      ],
      [
        "dni" => "46891277",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidad",
        "goal" => 6,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "46891277",
        "objective" => "Colocación de créditos",
        "metric" => "Unidad",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "46891277",
        "objective" => "Colocación de seguros",
        "metric" => "Unidad",
        "goal" => 1,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "41998623",
        "objective" => "Cumplimiento al 100% de la meta de venta establecida",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "41998623",
        "objective" => "% de Efectividad de Cobranza",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "41998623",
        "objective" => "Rentabilidad bruta mayor igual a 5.6%",
        "metric" => "Porcentaje",
        "goal" => 5.6,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "41998623",
        "objective" => "Incremento de cartera",
        "metric" => "Unidad",
        "goal" => 4,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "41998623",
        "objective" => "Mix de productos",
        "metric" => "Unidad",
        "goal" => 5,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "02851958",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "10"
      ],
      [
        "dni" => "44249939",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "10"
      ],
      [
        "dni" => "47057666",
        "objective" => "Los costos de Kilómetro de combustible no deben superar los 1.65\/km recorrido",
        "metric" => "Soles",
        "goal" => 1.65,
        "isAscending" => 0,
        "weight" => "33"
      ],
      [
        "dni" => "47057666",
        "objective" => "Rendimiento promedio 8.6 km\/gln",
        "metric" => "Soles",
        "goal" => 8.6,
        "isAscending" => 1,
        "weight" => "33"
      ],
      [
        "dni" => "47057666",
        "objective" => "Accidente y\/o Siniestros por responsabilidad el equipo de operaciones y mantenimiento",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "34"
      ],
      [
        "dni" => "43284061",
        "objective" => "Promedio de cumplimiento de presupuesto mensual de AP, DP y TP",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "10"
      ],
      [
        "dni" => "46343351",
        "objective" => "Incrementar el % de cumplimiento de la programaciòn (Servicios entregados a tiempo\/total de servicios
programados)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => "34"
      ],
      [
        "dni" => "46343351",
        "objective" => "No tener robos por falta de seguimiento y monitoreo",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "33"
      ],
      [
        "dni" => "46343351",
        "objective" => "Accidente y\/o Siniestros por responsabilidad el equipo de operaciones y mantenimiento",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "33"
      ],
      [
        "dni" => "73809915",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "10"
      ],
      [
        "dni" => "19324383",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Porcentaje",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "19324383",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "19324383",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Soles",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "19324383",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "48691128",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "10"
      ],
      [
        "dni" => "48299990",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidad",
        "goal" => 9,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "48299990",
        "objective" => "Colocación de créditos",
        "metric" => "Unidad",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "48299990",
        "objective" => "Colocación de seguros",
        "metric" => "Unidad",
        "goal" => 1,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "44498939",
        "objective" => "Cumplimiento mensual de los límites de velocidad en ruta",
        "metric" => "Porcentaje",
        "goal" => 99,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "44498939",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "44498939",
        "objective" => "Cero multas por infringir las normas de tránsito en el desarrollo de sus funciones",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "44498939",
        "objective" => "Cero incidencias en la ruta asignada (robos, choques,etc)",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "32739469",
        "objective" => "Cumplimiento de la programacion de capacitaciones a los agentes ",
        "metric" => "Porcentaje",
        "goal" => 99,
        "isAscending" => 1,
        "weight" => "33"
      ],
      [
        "dni" => "32739469",
        "objective" => "Disminución de número de agentes que abandonen su labor antes de cumplimiento de contrato",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "33"
      ],
      [
        "dni" => "32739469",
        "objective" => "Disminución de la tasa % de robos o pérdidas de activos de propiedad de la empresa",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "34"
      ],
      [
        "dni" => "45620643",
        "objective" => "Cumplimiento de cronogramas de desarrollo y mantenimiento de software (Milla, Quiter, Dynamic )",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "30"
      ],
      [
        "dni" => "45620643",
        "objective" => "Efectividad en el tiempo de atención en el servicio",
        "metric" => "Minutos",
        "goal" => 30,
        "isAscending" => 0,
        "weight" => "30"
      ],
      [
        "dni" => "45620643",
        "objective" => "Calidad de servicio del área",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "30"
      ],
      [
        "dni" => "45620643",
        "objective" => "Promedio de cumplimiento de presupuesto mensual de AP, DP y TP",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "10"
      ],
      [
        "dni" => "42116951",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidad",
        "goal" => 45,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "42116951",
        "objective" => "Meta en seguros",
        "metric" => "Número",
        "goal" => 4,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "42116951",
        "objective" => "Meta en créditos",
        "metric" => "Número",
        "goal" => 11,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "42116951",
        "objective" => "Alcance de Mix de Marca",
        "metric" => "Unidad",
        "goal" => 3,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "48493314",
        "objective" => "Cero soles de multa por errores contables en la empresa a cargo\n",
        "metric" => "Soles",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "20"
      ],
      [
        "dni" => "48493314",
        "objective" => "Exactitud en registros contables",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 0,
        "weight" => "50"
      ],
      [
        "dni" => "48493314",
        "objective" => "Entrega de cierres contables en tiempo de empresa a cargo (en los primeros 5 dias habiles)\n",
        "metric" => "Porcentaje",
        "goal" => 95,
        "isAscending" => 1,
        "weight" => "30"
      ],
      [
        "dni" => "16589869",
        "objective" => "Cumplimiento de presupuesto comercial",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "16589869",
        "objective" => "% de Efectividad de Cobranza",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "16589869",
        "objective" => "Mix de productos",
        "metric" => "Unidad",
        "goal" => 5,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "16589869",
        "objective" => "Cliente Nuevo al mes",
        "metric" => "Unidad",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "77499375",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "50"
      ],
      [
        "dni" => "77499375",
        "objective" => "Cumplimiento de inventario de sede al 100%",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "76010852",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "10"
      ],
      [
        "dni" => "16734135",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Porcentaje",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "16734135",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "16734135",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Soles",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "16734135",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "40831827",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Porcentaje",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "40831827",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "40831827",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Soles",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "40831827",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "42150787",
        "objective" => "Alcanzar numero mínimo de fletes al mes ",
        "metric" => "Viajes",
        "goal" => 353,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "42150787",
        "objective" => "Rentabilizar el negocio: margen bruto mensual ",
        "metric" => "Porcentaje",
        "goal" => 19,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "72843284",
        "objective" => "Cuadres de caja oportunos",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "72843284",
        "objective" => "Arqueo de caja, sin diferencias ",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "46837187",
        "objective" => "Cumplimiento mensual de los límites de velocidad en ruta",
        "metric" => "Porcentaje",
        "goal" => 99,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "46837187",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "46837187",
        "objective" => "Cero multas por infringir las normas de tránsito en el desarrollo de sus funciones",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "46837187",
        "objective" => "Cero incidencias en la ruta asignada (robos, choques,etc)",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "71202755",
        "objective" => "Eficiencia de OT > 70%",
        "metric" => "Porcentaje",
        "goal" => 70,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "71202755",
        "objective" => "Reingresos de vehículos",
        "metric" => "Unidad",
        "goal" => 2,
        "isAscending" => 0,
        "weight" => "50"
      ],
      [
        "dni" => "45106359",
        "objective" => "Reportes semanales de CXC",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "45106359",
        "objective" => "Cumplimiento de programacion de visitas a sedes con la finalidad de supervisar, procedimientos y control
de caja",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "45106359",
        "objective" => "Reduccion de gastos financieros",
        "metric" => "Porcentaje",
        "goal" => 10,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "45106359",
        "objective" => "Reduccion de indice de deudas a proveedores",
        "metric" => "Porcentaje",
        "goal" => 10,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "00850163",
        "objective" => "Apoyo en otras actividades de operativas con las demas àreas",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "00850163",
        "objective" => "Incidentes con responsalidad del auxiliar de operaciones",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "50"
      ],
      [
        "dni" => "70724393",
        "objective" => "Cumplir la meta de OT mensual",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "33"
      ],
      [
        "dni" => "70724393",
        "objective" => "Cumplimiento mensual de venta de accesorios",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "33"
      ],
      [
        "dni" => "70724393",
        "objective" => "Cumplimiento de NPS",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "34"
      ],
      [
        "dni" => "41072838",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Porcentaje",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "41072838",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "41072838",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Soles",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "41072838",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "48299503",
        "objective" => "Eficiencia de OT > 70%",
        "metric" => "Porcentaje",
        "goal" => 70,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "48299503",
        "objective" => "Reingresos de vehículos",
        "metric" => "Unidad",
        "goal" => 2,
        "isAscending" => 0,
        "weight" => "50"
      ],
      [
        "dni" => "16426491",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "10"
      ],
      [
        "dni" => "17632430",
        "objective" => "Incrementar el % de cumplimiento de la programaciòn (Servicios entregados a tiempo\/total de servicios
programados)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => "33"
      ],
      [
        "dni" => "17632430",
        "objective" => "No tener robos por falta de seguimiento y monitoreo",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "33"
      ],
      [
        "dni" => "17632430",
        "objective" => "Accidente y\/o Siniestros por responsabilidad el equipo de operaciones y mantenimiento",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "34"
      ],
      [
        "dni" => "75106508",
        "objective" => "Eficiencia de OT > 70%",
        "metric" => "Porcentaje",
        "goal" => 70,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "75106508",
        "objective" => "Reingresos de vehículos",
        "metric" => "Unidad",
        "goal" => 2,
        "isAscending" => 0,
        "weight" => "50"
      ],
      [
        "dni" => "41544958",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidad",
        "goal" => 12,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "41544958",
        "objective" => "Colocación de créditos",
        "metric" => "Unidad",
        "goal" => 3,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "41544958",
        "objective" => "Colocación de seguros",
        "metric" => "Unidad",
        "goal" => 1,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "74202439",
        "objective" => "Calidad de servicio - Mesa de ayuda (Milla \/ Dynamics)\n",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "74202439",
        "objective" => "Promedio de efectividad en tiempo de atención - Soporte TIC's\n",
        "metric" => "Minutos",
        "goal" => 45,
        "isAscending" => 0,
        "weight" => "50"
      ],
      [
        "dni" => "44577179",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidad",
        "goal" => 17,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "44577179",
        "objective" => "Meta en seguros",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "44577179",
        "objective" => "Meta en créditos",
        "metric" => "Número",
        "goal" => 6,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "44577179",
        "objective" => "Alcance de Mix de Marca",
        "metric" => "Unidad",
        "goal" => 4,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "44846745",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "50"
      ],
      [
        "dni" => "44846745",
        "objective" => "Cumplimiento de inventario de sede al 100%",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "44517042",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidad",
        "goal" => 6,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "44517042",
        "objective" => "Colocación de créditos",
        "metric" => "Unidad",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "44517042",
        "objective" => "Colocación de seguros",
        "metric" => "Unidad",
        "goal" => 1,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "72722099",
        "objective" => "Cero soles de multa por errores contables en la empresa a cargo\n",
        "metric" => "Soles",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "33"
      ],
      [
        "dni" => "72722099",
        "objective" => "Cero errores en el registro de información contable de manera mensual",
        "metric" => "Errores",
        "goal" => 2,
        "isAscending" => 0,
        "weight" => "33"
      ],
      [
        "dni" => "72722099",
        "objective" => "Entrega de cierre contable mensual integral (en los primeros 12 días calendario)",
        "metric" => "Dias",
        "goal" => 12,
        "isAscending" => 0,
        "weight" => "34"
      ],
      [
        "dni" => "46815196",
        "objective" => "Cumplir la meta de OT mensual",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "34"
      ],
      [
        "dni" => "46815196",
        "objective" => "Cumplimiento de NPS",
        "metric" => "Porcentaje",
        "goal" => 80,
        "isAscending" => 1,
        "weight" => "33"
      ],
      [
        "dni" => "46815196",
        "objective" => "Cumplimiento de meta mensual de venta de accesorios",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "33"
      ],
      [
        "dni" => "43122872",
        "objective" => "Cumplimiento mensual de los límites de velocidad en ruta",
        "metric" => "Porcentaje",
        "goal" => 99,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "43122872",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "43122872",
        "objective" => "Cero multas por infringir las normas de tránsito en el desarrollo de sus funciones",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "43122872",
        "objective" => "Cero incidencias en la ruta asignada (robos, choques,etc)",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "45676375",
        "objective" => "Cumplimiento mensual de los límites de velocidad en ruta",
        "metric" => "Porcentaje",
        "goal" => 99,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "45676375",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "45676375",
        "objective" => "Cero multas por infringir las normas de tránsito en el desarrollo de sus funciones",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "45676375",
        "objective" => "Cero incidencias en la ruta asignada (robos, choques,etc)",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "74624087",
        "objective" => "Cero soles de multa por errores contables en la empresa a cargo\n",
        "metric" => "Soles",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "33"
      ],
      [
        "dni" => "74624087",
        "objective" => "Cero errores en el registro de información contable de manera mensual",
        "metric" => "Errores",
        "goal" => 2,
        "isAscending" => 0,
        "weight" => "33"
      ],
      [
        "dni" => "74624087",
        "objective" => "Entrega de cierre contable mensual integral (en los primeros 12 días calendario)",
        "metric" => "Dias",
        "goal" => 12,
        "isAscending" => 0,
        "weight" => "34"
      ],
      [
        "dni" => "75662130",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "10"
      ],
      [
        "dni" => "17619908",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Porcentaje",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "17619908",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "17619908",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Soles",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "17619908",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "46226629",
        "objective" => "Cero soles de multa por errores contables en AP",
        "metric" => "Soles",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "33"
      ],
      [
        "dni" => "46226629",
        "objective" => "Presentación de DDJJ mensuales a SUNAT ",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "33"
      ],
      [
        "dni" => "46226629",
        "objective" => "Entrega de cierres contables (EE.RR) en tiempo de empresa a cargo (15 dias)",
        "metric" => "Dias",
        "goal" => 15,
        "isAscending" => 0,
        "weight" => "34"
      ],
      [
        "dni" => "75618646",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidad",
        "goal" => 6,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "75618646",
        "objective" => "Colocación de créditos",
        "metric" => "Unidad",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "75618646",
        "objective" => "Colocación de seguros",
        "metric" => "Unidad",
        "goal" => 1,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "72650523",
        "objective" => "Cumplimiento de presupuesto comercial",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "72650523",
        "objective" => "% de Efectividad de Cobranza",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "72650523",
        "objective" => "Mix de productos",
        "metric" => "Unidad",
        "goal" => 5,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "72650523",
        "objective" => "Cliente Nuevo al mes",
        "metric" => "Unidad",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "73855265",
        "objective" => "Notas de crédito",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "33"
      ],
      [
        "dni" => "73855265",
        "objective" => "Entrega de informe de compras",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "33"
      ],
      [
        "dni" => "73855265",
        "objective" => "Aprobación correcta de descuentos",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "34"
      ],
      [
        "dni" => "46012169",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "10"
      ],
      [
        "dni" => "41037767",
        "objective" => "Seguimiento de pedidos de compra de vehículos, conciliando con el sistema de emisión de facturas",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "33"
      ],
      [
        "dni" => "41037767",
        "objective" => "Obtener un resultado mayor o igual a 80% en NPS.",
        "metric" => "Porcentaje",
        "goal" => 80,
        "isAscending" => 1,
        "weight" => "33"
      ],
      [
        "dni" => "41037767",
        "objective" => "Cumplimiento del 100% del envío de las solicitudes de nota de crédito (7 días hábiles)",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "34"
      ],
      [
        "dni" => "78005001",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "10"
      ],
      [
        "dni" => "41430542",
        "objective" => "Seguimiento de pedidos de compra de vehículos, conciliando con el sistema de emisión de facturas",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "33"
      ],
      [
        "dni" => "41430542",
        "objective" => "Obtener un resultado mayor o igual a 80% en NPS.",
        "metric" => "Porcentaje",
        "goal" => 80,
        "isAscending" => 1,
        "weight" => "33"
      ],
      [
        "dni" => "41430542",
        "objective" => "Cumplimiento del 100% del envío de las solicitudes de nota de crédito (7 días hábiles)",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "34"
      ],
      [
        "dni" => "16530913",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Porcentaje",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "16530913",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "16530913",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Soles",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "16530913",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "73235748",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidad",
        "goal" => 13,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "73235748",
        "objective" => "Colocación de créditos",
        "metric" => "Unidad",
        "goal" => 3,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "73235748",
        "objective" => "Colocación de seguros",
        "metric" => "Unidad",
        "goal" => 1,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "70478021",
        "objective" => "Eficiencia de OT > 70%",
        "metric" => "Porcentaje",
        "goal" => 70,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "70478021",
        "objective" => "Reingresos de vehículos",
        "metric" => "Unidad",
        "goal" => 2,
        "isAscending" => 0,
        "weight" => "50"
      ],
      [
        "dni" => "41337449",
        "objective" => "Cumplimiento de presupuesto comercial",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "41337449",
        "objective" => "% de Efectividad de Cobranza",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "41337449",
        "objective" => "Mix de productos",
        "metric" => "Unidad",
        "goal" => 5,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "41337449",
        "objective" => "Cliente Nuevo al mes",
        "metric" => "Unidad",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "72700680",
        "objective" => "Cumplir la meta de OT mensual",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "33"
      ],
      [
        "dni" => "72700680",
        "objective" => "Cumplimiento de NPS",
        "metric" => "Porcentaje",
        "goal" => 80,
        "isAscending" => 1,
        "weight" => "33"
      ],
      [
        "dni" => "72700680",
        "objective" => "Cumplimiento de meta mensual de venta de accesorios",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "34"
      ],
      [
        "dni" => "05641617",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidad",
        "goal" => 7,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "05641617",
        "objective" => "Colocación de créditos",
        "metric" => "Unidad",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "05641617",
        "objective" => "Colocación de seguros",
        "metric" => "Unidad",
        "goal" => 1,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "46613975",
        "objective" => "Eficiencia de OT > 70%",
        "metric" => "Porcentaje",
        "goal" => 70,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "46613975",
        "objective" => "Reingresos de vehículos",
        "metric" => "Unidad",
        "goal" => 2,
        "isAscending" => 0,
        "weight" => "50"
      ],
      [
        "dni" => "74916002",
        "objective" => "Optimizar los tiempos de reparacion en un 55%",
        "metric" => "Porcentaje",
        "goal" => 55,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "74916002",
        "objective" => "Mantenimentos correctivos de emergencia(Auxilios Mecánicos), no supere el 5% mensual de la flota",
        "metric" => "Porcentaje",
        "goal" => 5,
        "isAscending" => 0,
        "weight" => "50"
      ],
      [
        "dni" => "77071508",
        "objective" => "Eficiencia de OT > 70%",
        "metric" => "Porcentaje",
        "goal" => 70,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "77071508",
        "objective" => "Reingresos de vehículos",
        "metric" => "Unidad",
        "goal" => 2,
        "isAscending" => 0,
        "weight" => "50"
      ],
      [
        "dni" => "73736078",
        "objective" => "Controlar el costo operacional (Costo venta\/kilometraje) que no supere 4.5 sol\/km",
        "metric" => "Soles",
        "goal" => 4.5,
        "isAscending" => 0,
        "weight" => "20"
      ],
      [
        "dni" => "73736078",
        "objective" => "Controlar el costo kilómetro de combustible no supere los 2.0 sol\/km",
        "metric" => "Soles",
        "goal" => 2,
        "isAscending" => 0,
        "weight" => "20"
      ],
      [
        "dni" => "73736078",
        "objective" => "Controlar los gastos reparables que no superen los 50,000 soles",
        "metric" => "Soles",
        "goal" => 50000,
        "isAscending" => 0,
        "weight" => "20"
      ],
      [
        "dni" => "73736078",
        "objective" => "Mantener la disponibilidad al 96% unidades completas(conductor y vehículo)",
        "metric" => "Porcentaje",
        "goal" => 96,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "73736078",
        "objective" => "Accidente y\/o Siniestros por responsabilidad el equipo de operaciones y mantenimiento",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "20"
      ],
      [
        "dni" => "45206383",
        "objective" => "Notas de crédito",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "33"
      ],
      [
        "dni" => "45206383",
        "objective" => "Entrega de informe de compras",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "33"
      ],
      [
        "dni" => "45206383",
        "objective" => "Aprobación correcta de descuentos",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "34"
      ],
      [
        "dni" => "40988232",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Porcentaje",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "40988232",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "40988232",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Soles",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "40988232",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "44639590",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Porcentaje",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "44639590",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "44639590",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Soles",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "44639590",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "43088093",
        "objective" => "Eficiencia de OT > 70%",
        "metric" => "Porcentaje",
        "goal" => 70,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "43088093",
        "objective" => "Reingresos de vehículos",
        "metric" => "Unidad",
        "goal" => 2,
        "isAscending" => 0,
        "weight" => "50"
      ],
      [
        "dni" => "48359987",
        "objective" => "Vales pendientes rendidos al 100%",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "48359987",
        "objective" => "Arqueo de caja, sin diferencias ",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "48359987",
        "objective" => "Cuadres de caja oportunos",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "48359987",
        "objective" => "Envio de reportes oportunos",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "48359987",
        "objective" => "Informe de estado de las cuentas por cobrar",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "19332661",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Porcentaje",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "19332661",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "19332661",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Soles",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "19332661",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "45801388",
        "objective" => "Cero soles de multa por errores contables en la empresa a cargo\n",
        "metric" => "Soles",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "34"
      ],
      [
        "dni" => "45801388",
        "objective" => "Entrega de cierres contables en tiempo de empresa a cargo\n",
        "metric" => "Dias",
        "goal" => 12,
        "isAscending" => 0,
        "weight" => "33"
      ],
      [
        "dni" => "45801388",
        "objective" => "Presentación de DDJJ mensuales a SUNAT ",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "33"
      ],
      [
        "dni" => "16545918",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Porcentaje",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "16545918",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "16545918",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Soles",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "16545918",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "43589428",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Porcentaje",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "43589428",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "43589428",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Soles",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "43589428",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "16795422",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Porcentaje",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "16795422",
        "objective" => "No tener incidencias, con responsalidad del conductor\n",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "16795422",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Soles",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "16795422",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "17444802",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Porcentaje",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "17444802",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "17444802",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Soles",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "17444802",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "40989878",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "10"
      ],
      [
        "dni" => "46479361",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Porcentaje",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "46479361",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "46479361",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Soles",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "46479361",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "46132573",
        "objective" => "Cumplir la meta de OT mensual",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "33"
      ],
      [
        "dni" => "46132573",
        "objective" => "Cumplimiento de NPS",
        "metric" => "Porcentaje",
        "goal" => 80,
        "isAscending" => 1,
        "weight" => "33"
      ],
      [
        "dni" => "46132573",
        "objective" => "Cumplimiento de meta mensual de venta de accesorios",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "34"
      ],
      [
        "dni" => "76571917",
        "objective" => "Eficiencia de OT > 70%",
        "metric" => "Porcentaje",
        "goal" => 70,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "76571917",
        "objective" => "Reingresos de vehículos",
        "metric" => "Unidad",
        "goal" => 2,
        "isAscending" => 0,
        "weight" => "50"
      ],
      [
        "dni" => "71895009",
        "objective" => "Reingresos de vehículos",
        "metric" => "Unidad",
        "goal" => 2,
        "isAscending" => 0,
        "weight" => "50"
      ],
      [
        "dni" => "71895009",
        "objective" => "Eficiencia de OT > 70%",
        "metric" => "Porcentaje",
        "goal" => 70,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "73777585",
        "objective" => "Cumplimiento de presupuesto comercial",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "73777585",
        "objective" => "% de Efectividad de Cobranza",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "73777585",
        "objective" => "Mix de productos",
        "metric" => "Unidad",
        "goal" => 5,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "73777585",
        "objective" => "Cliente Nuevo al mes",
        "metric" => "Unidad",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "42772411",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidad",
        "goal" => 20,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "42772411",
        "objective" => "Meta en seguros",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "42772411",
        "objective" => "Meta en créditos",
        "metric" => "Número",
        "goal" => 7,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "42772411",
        "objective" => "Alcance de Mix de Marca",
        "metric" => "Unidad",
        "goal" => 4,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "43324348",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "10"
      ],
      [
        "dni" => "75235770",
        "objective" => "Promedio de cumplimiento del ICD al 90% a nivel de las 4 sedes",
        "metric" => "Porcentaje",
        "goal" => 90,
        "isAscending" => 1,
        "weight" => "10"
      ],
      [
        "dni" => "71628227",
        "objective" => "Mantener la disponibilidad al 96% unidades completas(conductor y vehículo)",
        "metric" => "Porcentaje",
        "goal" => 96,
        "isAscending" => 1,
        "weight" => "18"
      ],
      [
        "dni" => "71628227",
        "objective" => "Mantener un 85 % de cumplimiento de la programaciòn (Servicios entregados a tiempo\/total de servicios
programados)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => "18"
      ],
      [
        "dni" => "71628227",
        "objective" => "Controlar los descansos no superar los 450 días pendientes al mes. ",
        "metric" => "Dias",
        "goal" => 450,
        "isAscending" => 0,
        "weight" => "18"
      ],
      [
        "dni" => "71628227",
        "objective" => "Los costos de mantenimiento no deben superar los 0.30 soles\/km recorrido",
        "metric" => "Soles",
        "goal" => 0.3,
        "isAscending" => 0,
        "weight" => "18"
      ],
      [
        "dni" => "71628227",
        "objective" => "No tener multas por documentacion (guías, revisión técnica, bonificaciòn, soat,certificado MTC) ",
        "metric" => "Soles",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "14"
      ],
      [
        "dni" => "71628227",
        "objective" => "Accidente y\/o Siniestros por responsabilidad el equipo de operaciones y mantenimiento",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "14"
      ],
      [
        "dni" => "47160168",
        "objective" => "Efectividad de proyección de facturación",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "33"
      ],
      [
        "dni" => "47160168",
        "objective" => "Recuperación de documentos",
        "metric" => "Porcentaje",
        "goal" => 90,
        "isAscending" => 1,
        "weight" => "34"
      ],
      [
        "dni" => "47160168",
        "objective" => "Efectividad proyección de cobranza",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "33"
      ],
      [
        "dni" => "70046881",
        "objective" => "Cumplimiento de la facturación de taller mas un incremento adicional del 2%",
        "metric" => "Porcentaje",
        "goal" => 102,
        "isAscending" => 1,
        "weight" => "33"
      ],
      [
        "dni" => "70046881",
        "objective" => "Cumplimiento de OT>100%",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "33"
      ],
      [
        "dni" => "70046881",
        "objective" => "Tasa de reclamos relacionados al servicio que brinda el jefe de taller",
        "metric" => "Porcentaje",
        "goal" => 1,
        "isAscending" => 0,
        "weight" => "34"
      ],
      [
        "dni" => "46688489",
        "objective" => "Arqueo de caja, sin diferencias ",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "46688489",
        "objective" => "Cuadres de caja oportunos",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "46688489",
        "objective" => "Envío de reportes oportunos.",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "46688489",
        "objective" => "Informe de estado de las CXC.",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "43315473",
        "objective" => "Cumplimiento de presupuesto comercial",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "43315473",
        "objective" => "% de Efectividad de Cobranza",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "43315473",
        "objective" => "Mix de productos",
        "metric" => "Unidad",
        "goal" => 5,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "43315473",
        "objective" => "Cliente Nuevo al mes",
        "metric" => "Unidad",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "72865261",
        "objective" => "Cumplir la meta de OT mensual\n",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "33"
      ],
      [
        "dni" => "72865261",
        "objective" => "Cumplimiento de NPS\n",
        "metric" => "Porcentaje",
        "goal" => 80,
        "isAscending" => 1,
        "weight" => "34"
      ],
      [
        "dni" => "72865261",
        "objective" => "Cumplimiento de meta mensual de ventas de accesorios\n",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "33"
      ],
      [
        "dni" => "46304286",
        "objective" => "Notas de crèdito a proveedores ",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "46304286",
        "objective" => "Pago a proveedores",
        "metric" => "Porcentaje",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "46304286",
        "objective" => "Gestión de compras ",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "46304286",
        "objective" => "Efectividad de liquidaciones: % de reconocimeinto de la liquidacion \/ total de liquidaciones
registradas",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "19037052",
        "objective" => "Cumplimiento de presupuesto comercial",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "19037052",
        "objective" => "% de Efectividad de Cobranza",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "19037052",
        "objective" => "Mix de productos",
        "metric" => "Unidad",
        "goal" => 5,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "19037052",
        "objective" => "Cliente Nuevo al mes",
        "metric" => "Unidad",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "71960403",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "10"
      ],
      [
        "dni" => "42310255",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Porcentaje",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "42310255",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Numero",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "42310255",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Numero",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "42310255",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "46749588",
        "objective" => "Visitas de campo",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "34"
      ],
      [
        "dni" => "46749588",
        "objective" => "Campañas de Mkt",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "33"
      ],
      [
        "dni" => "46749588",
        "objective" => "Penetración de mercado",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "33"
      ],
      [
        "dni" => "40601600",
        "objective" => "Notas de crédito",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "33"
      ],
      [
        "dni" => "40601600",
        "objective" => "Entrega de informe de compras",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "33"
      ],
      [
        "dni" => "40601600",
        "objective" => "Aprobación correcta de descuentos",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "34"
      ],
      [
        "dni" => "75459353",
        "objective" => "Optimizar los tiempos de reparacion en un 55%",
        "metric" => "Porcentaje",
        "goal" => 55,
        "isAscending" => 0,
        "weight" => "50"
      ],
      [
        "dni" => "75459353",
        "objective" => "Mantenimentos correctivos de emergencia(Auxilios Mecánicos), no supere el 5% mensual de la flota",
        "metric" => "Porcentaje",
        "goal" => 5,
        "isAscending" => 0,
        "weight" => "50"
      ],
      [
        "dni" => "76390749",
        "objective" => "Cero soles de multa por errores contables en la empresa a cargo\n",
        "metric" => "soles",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "33"
      ],
      [
        "dni" => "76390749",
        "objective" => "Cero errores en el registro de información contable de manera mensual\n",
        "metric" => "Errores",
        "goal" => 2,
        "isAscending" => 0,
        "weight" => "33"
      ],
      [
        "dni" => "76390749",
        "objective" => "Entrega de cierre contable mensual integral (en los primeros 12 días calendario)",
        "metric" => "Dias",
        "goal" => 13,
        "isAscending" => 0,
        "weight" => "34"
      ],
      [
        "dni" => "16754597",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "10"
      ],
      [
        "dni" => "44723898",
        "objective" => "Cumplimiento mensual de los límites de velocidad en ruta",
        "metric" => "Porcentaje",
        "goal" => 99,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "44723898",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "44723898",
        "objective" => "Cero multas por infringir las normas de tránsito en el desarrollo de sus funciones",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "44723898",
        "objective" => "Cero incidencias en la ruta asignada (robos, choques,etc)",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "40971761",
        "objective" => "Cumplir con la implementaciòn de las unidades y su debido registro al 100%, según condicion del cliente
(indicador rìgido)",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "40971761",
        "objective" => "Cumplir con el registro al 100% de robos de implementos, mercaderìa e incidencias.",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "40971761",
        "objective" => "Realizar inventario mensuales del 80% de toda la flota (como mìnimo) con la finalidad de verificar las
condiciones de los implementos",
        "metric" => "Porcentaje",
        "goal" => 80,
        "isAscending" => 1,
        "weight" => "20"
      ],
      [
        "dni" => "40971761",
        "objective" => "Cero multas por documentacion vencida, mantener actualizada de todas las unidades.(revisiòn tècnica,
bonificaciòn, soat,certificado MTC)",
        "metric" => "Soles",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "20"
      ],
      [
        "dni" => "40971761",
        "objective" => "Accidente y\/o Siniestros por responsabilidad el equipo de operaciones y mantenimiento",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "20"
      ],
      [
        "dni" => "45938688",
        "objective" => "Arqueo de caja, sin diferencias ",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "45938688",
        "objective" => "Cuadres de caja oportunos",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "45938688",
        "objective" => "Envío de reportes oportunos.",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "45938688",
        "objective" => "Informe de estado de las CXC.",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "16454626",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Porcentaje",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "16454626",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "16454626",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Soles",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "16454626",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "73271353",
        "objective" => "Tasa de reclamo menor o igual a 5% ",
        "metric" => "Porcentaje",
        "goal" => 5,
        "isAscending" => 0,
        "weight" => "10"
      ],
      [
        "dni" => "70309494",
        "objective" => "Cumplimiento mensual de los límites de velocidad en ruta",
        "metric" => "Porcentaje",
        "goal" => 99,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "70309494",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "70309494",
        "objective" => "Cero multas por infringir las normas de tránsito en el desarrollo de sus funciones",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "70309494",
        "objective" => "Cero incidencias en la ruta asignada (robos, choques,etc)",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "16526312",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Porcentaje",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "16526312",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "16526312",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Soles",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "16526312",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "73226257",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "10"
      ],
      [
        "dni" => "76692661",
        "objective" => "Cumplimiento mensual de los límites de velocidad en ruta",
        "metric" => "Porcentaje",
        "goal" => 99,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "76692661",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "76692661",
        "objective" => "Cero multas por infringir las normas de tránsito en el desarrollo de sus funciones",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "76692661",
        "objective" => "Cero incidencias en la ruta asignada (robos, choques,etc)",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "75560129",
        "objective" => "Incrementar el % de cumplimiento de la programaciòn (Servicios entregados a tiempo\/total de servicios
programados)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => "34"
      ],
      [
        "dni" => "75560129",
        "objective" => "No tener robos por falta de seguimiento y monitoreo",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "33"
      ],
      [
        "dni" => "75560129",
        "objective" => "Accidente y\/o Siniestros por responsabilidad el equipo de operaciones y mantenimiento",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "33"
      ],
      [
        "dni" => "42430798",
        "objective" => "Cumplimiento de la facturación de taller mas un incremento adicional del 2%",
        "metric" => "Porcentaje",
        "goal" => 102,
        "isAscending" => 1,
        "weight" => "33"
      ],
      [
        "dni" => "42430798",
        "objective" => "Cumplimiento de OT>100%",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "33"
      ],
      [
        "dni" => "42430798",
        "objective" => "Tasa de reclamos relacionados al servicio que brinda el jefe de taller",
        "metric" => "Porcentaje",
        "goal" => 1,
        "isAscending" => 0,
        "weight" => "34"
      ],
      [
        "dni" => "43210143",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Porcentaje",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "43210143",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "43210143",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Soles",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "43210143",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "19324054",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Porcentaje",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "19324054",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "19324054",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Soles",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "19324054",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "75497507",
        "objective" => "Propuestas de conceptos visuales campañas de marketing (4 unidades de negocio)",
        "metric" => "Numero",
        "goal" => 4,
        "isAscending" => 1,
        "weight" => "33"
      ],
      [
        "dni" => "75497507",
        "objective" => "Desarrollo de gráficas multiformato",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "33"
      ],
      [
        "dni" => "75497507",
        "objective" => "Entrega de artes para campañas de marketing",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "34"
      ],
      [
        "dni" => "41093744",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Porcentaje",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "41093744",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "41093744",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Soles",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "41093744",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "72080239",
        "objective" => "Stock saludable de un mes por sede",
        "metric" => "Unidad",
        "goal" => 1,
        "isAscending" => 0,
        "weight" => "10"
      ],
      [
        "dni" => "44588021",
        "objective" => "Optimizar los tiempos de reparacion en un 55%",
        "metric" => "Porcentaje",
        "goal" => 55,
        "isAscending" => 0,
        "weight" => "50"
      ],
      [
        "dni" => "44588021",
        "objective" => "Mantenimentos correctivos de emergencia(Auxilios Mecánicos), no supere el 5% mensual de la flota",
        "metric" => "Porcentaje",
        "goal" => 5,
        "isAscending" => 0,
        "weight" => "50"
      ],
      [
        "dni" => "48080535",
        "objective" => "Notas de crèdito a proveedores ",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "48080535",
        "objective" => "Pago a proveedores",
        "metric" => "Porcentaje",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "48080535",
        "objective" => "Gestión de compras ",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "48080535",
        "objective" => "Efectividad de liquidaciones: % de reconocimeinto de la liquidacion \/ total de liquidaciones
registradas",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "42838742",
        "objective" => "Cumplimiento de presupuesto comercial",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "42838742",
        "objective" => "% de Efectividad de Cobranza",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "42838742",
        "objective" => "Mix de productos",
        "metric" => "Unidad",
        "goal" => 5,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "42838742",
        "objective" => "Cliente Nuevo al mes",
        "metric" => "Unidad",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "16657698",
        "objective" => "Cumplimiento mensual de los límites de velocidad en ruta",
        "metric" => "Porcentaje",
        "goal" => 99,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "16657698",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "16657698",
        "objective" => "Cero multas por infringir las normas de tránsito en el desarrollo de sus funciones",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "16657698",
        "objective" => "Cero incidencias en la ruta asignada (robos, choques,etc)",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "45509583",
        "objective" => "Cumplimiento de presupuesto comercial",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "45509583",
        "objective" => "% de Efectividad de Cobranza",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "45509583",
        "objective" => "Mix de productos",
        "metric" => "Unidad",
        "goal" => 5,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "45509583",
        "objective" => "Cliente Nuevo al mes",
        "metric" => "Unidad",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "46792789",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "10"
      ],
      [
        "dni" => "73340961",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "10"
      ],
      [
        "dni" => "46002145",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "10"
      ],
      [
        "dni" => "18217261",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidad",
        "goal" => 7,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "18217261",
        "objective" => "Colocación de créditos",
        "metric" => "Unidad",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "18217261",
        "objective" => "Colocación de seguros",
        "metric" => "Unidad",
        "goal" => 1,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "48502011",
        "objective" => "Eficiencia de OT > 70%",
        "metric" => "Porcentaje",
        "goal" => 70,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "48502011",
        "objective" => "Reingresos de vehículos",
        "metric" => "Unidad",
        "goal" => 2,
        "isAscending" => 0,
        "weight" => "50"
      ],
      [
        "dni" => "44198340",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Porcentaje",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "44198340",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "44198340",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Soles",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "44198340",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "73365479",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidad",
        "goal" => 3,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "73365479",
        "objective" => "Colocación de créditos",
        "metric" => "Unidad",
        "goal" => 1,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "73657400",
        "objective" => "Arqueo de caja, sin diferencias ",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "73657400",
        "objective" => "Cuadres de caja oportunos",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "73657400",
        "objective" => "Envío de reportes oportunos.",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "73657400",
        "objective" => "Informe de estado de las CXC.",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "48178171",
        "objective" => "Reingresos de vehículos",
        "metric" => "Unidad",
        "goal" => 2,
        "isAscending" => 0,
        "weight" => "50"
      ],
      [
        "dni" => "48178171",
        "objective" => "Eficiencia de OT > 70%",
        "metric" => "Porcentaje",
        "goal" => 70,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "42658440",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "10"
      ],
      [
        "dni" => "70984021",
        "objective" => "Cuadres de caja oportunos",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "70984021",
        "objective" => "Arqueo de caja, sin diferencias ",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "45354782",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Porcentaje",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "45354782",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "45354782",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Soles",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "45354782",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "75943283",
        "objective" => "Procesos dentro del plazo",
        "metric" => "Porcentaje",
        "goal" => 80,
        "isAscending" => 1,
        "weight" => "34"
      ],
      [
        "dni" => "75943283",
        "objective" => "Calidad de reclutamiento",
        "metric" => "Porcentaje",
        "goal" => 95,
        "isAscending" => 1,
        "weight" => "33"
      ],
      [
        "dni" => "75943283",
        "objective" => "CV recibidos marca empleadora",
        "metric" => "Unidades",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "33"
      ],
      [
        "dni" => 72757329,
        "objective" => "Errores en cálculo de planilla GP",
        "metric" => "Numero",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "34"
      ],
      [
        "dni" => 72757329,
        "objective" => "Errores en cálculo de planilla TP",
        "metric" => "Numero",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "34"
      ],
      [
        "dni" => 72757329,
        "objective" => "Entrega de anexos de cuentas para planilla",
        "metric" => "dias",
        "goal" => 10,
        "isAscending" => 0,
        "weight" => "32"
      ],
      [
        "dni" => 71875278,
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Porcentaje",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => 71875278,
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => 71875278,
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Soles",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => 71875278,
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => 75886284,
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "10"
      ],
      [
        "dni" => 75089704,
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "10"
      ],
      [
        "dni" => 73078735,
        "objective" => "Calidad de contenido (crecimiento de 3% respecto al mes anterior)",
        "metric" => "Porcentaje",
        "goal" => 3,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => 73078735,
        "objective" => "Promedio de retención de visualización de videos y reels",
        "metric" => "Porcentaje",
        "goal" => 80,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => 73078735,
        "objective" => "Cumplimiento de entrega de piezas",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => 73078735,
        "objective" => "Visitas mensuales a sedes",
        "metric" => "Unidad",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => 46337540,
        "objective" => "Cumplimiento mensual de los límites de velocidad en ruta",
        "metric" => "Porcentaje",
        "goal" => 99,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => 46337540,
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => 46337540,
        "objective" => "Cero multas por infringir las normas de tránsito en el desarrollo de sus funciones",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => 46337540,
        "objective" => "Cero incidencias en la ruta asignada (robos, choques,etc)",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => 72636980,
        "objective" => "Cuadres de caja oportunos\n",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => 72636980,
        "objective" => "Arqueo de caja, sin diferencias \n",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => 46555930,
        "objective" => "Apoyo en otras actividades de operativas con las demas àreas",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => 46555930,
        "objective" => "Incidentes con responsalidad del auxiliar de operaciones",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "50"
      ],
      [
        "dni" => 62046276,
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "10"
      ],
      [
        "dni" => 75480931,
        "objective" => "Liquidaciones pendientes rendidos al 100% ",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => 75480931,
        "objective" => "Arqueo de caja, sin diferencias ",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => 75480931,
        "objective" => "Cuadres de caja oportunos",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => 75480931,
        "objective" => "Informe de estado de las CXC.",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => 75770012,
        "objective" => "Liquidaciones pendientes rendidos al 100% ",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => 75770012,
        "objective" => "Arqueo de caja, sin diferencias ",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => 75770012,
        "objective" => "Cuadres de caja oportunos",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => 75770012,
        "objective" => "Informe de estado de las CXC.",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => 45853764,
        "objective" => "Cuadres de caja oportunos\n",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => 45853764,
        "objective" => "Arqueo de caja, sin diferencias \n",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "71606304",
        "objective" => "Reingresos de vehículos",
        "metric" => "Unidad",
        "goal" => 2,
        "isAscending" => 0,
        "weight" => "50"
      ],
      [
        "dni" => "71606304",
        "objective" => "Eficiencia de OT > 70%",
        "metric" => "Porcentaje",
        "goal" => 70,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "45795895",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidad",
        "goal" => 7,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "45795895",
        "objective" => "Colocación de créditos",
        "metric" => "Unidad",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "45795895",
        "objective" => "Colocación de seguros",
        "metric" => "Unidad",
        "goal" => 1,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "71702184",
        "objective" => "Los costos de Kilómetro de Neumáticos no deben superar los 0.16 sol\/km recorrido",
        "metric" => "Soles",
        "goal" => 0.16,
        "isAscending" => 0,
        "weight" => "16"
      ],
      [
        "dni" => "71702184",
        "objective" => "Rendimiento de Neumatico debe ser como mínimo 11,000 Km\/mm",
        "metric" => "Kilometros",
        "goal" => 11000,
        "isAscending" => 1,
        "weight" => "16"
      ],
      [
        "dni" => "71702184",
        "objective" => "Mantenimentos correctivos de emergencia(Auxilios Mecánicos), no supere el 5% mensual de la flota",
        "metric" => "Porcentaje",
        "goal" => 5,
        "isAscending" => 0,
        "weight" => "16"
      ],
      [
        "dni" => "71702184",
        "objective" => "Cumplir al menos con el 96% de la programación de mantenimiento preventivo",
        "metric" => "Porcentaje",
        "goal" => 96,
        "isAscending" => 1,
        "weight" => "16"
      ],
      [
        "dni" => "71702184",
        "objective" => "Números de escaneos mensuales mínimo 105",
        "metric" => "Unidad",
        "goal" => 105,
        "isAscending" => 1,
        "weight" => "18"
      ],
      [
        "dni" => "71702184",
        "objective" => "Accidente y\/o Siniestros por responsabilidad el equipo de operaciones y mantenimiento",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "18"
      ],
      [
        "dni" => "43488881",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidad",
        "goal" => 3,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "43488881",
        "objective" => "Colocación de créditos",
        "metric" => "Unidad",
        "goal" => 1,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "41941867",
        "objective" => "Cumplimiento de presupuesto comercial",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "41941867",
        "objective" => "% de Efectividad de Cobranza",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "41941867",
        "objective" => "Mix de productos",
        "metric" => "Unidad",
        "goal" => 5,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "41941867",
        "objective" => "Cliente Nuevo al mes",
        "metric" => "Unidad",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "42795204",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Porcentaje",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "42795204",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "42795204",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Soles",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "42795204",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "007633555",
        "objective" => "Cumplimiento mensual de los límites de velocidad en ruta",
        "metric" => "Porcentaje",
        "goal" => 99,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "007633555",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "007633555",
        "objective" => "Cero multas por infringir las normas de tránsito en el desarrollo de sus funciones",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "007633555",
        "objective" => "Cero incidencias en la ruta asignada (robos, choques,etc)",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "77918582",
        "objective" => "Entrega de cierres contables en tiempo de empresa a cargo (en los primeros 7 dias habiles)",
        "metric" => "dias",
        "goal" => 7,
        "isAscending" => 0,
        "weight" => "50"
      ],
      [
        "dni" => "77918582",
        "objective" => "Cero soles de multa por errores contables en la empresa a cargo",
        "metric" => "soles",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "50"
      ],
      [
        "dni" => "43084165",
        "objective" => "Cumplimiento de presupuesto comercial",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "43084165",
        "objective" => "% de Efectividad de Cobranza",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "43084165",
        "objective" => "Mix de productos",
        "metric" => "Unidad",
        "goal" => 5,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "43084165",
        "objective" => "Cliente Nuevo al mes",
        "metric" => "Unidad",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "41903472",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidad",
        "goal" => 6,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "41903472",
        "objective" => "Colocación de créditos",
        "metric" => "Unidad",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "41903472",
        "objective" => "Colocación de seguros",
        "metric" => "Unidad",
        "goal" => 1,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "42085586",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Porcentaje",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "42085586",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "42085586",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Soles",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "42085586",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "16736017",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidad",
        "goal" => 9,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "16736017",
        "objective" => "Colocación de créditos",
        "metric" => "Unidad",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "16736017",
        "objective" => "Colocación de seguros",
        "metric" => "Unidad",
        "goal" => 1,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "40734495",
        "objective" => "Número de unidades ligeras entregadas",
        "metric" => "Unidad",
        "goal" => 10,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "40734495",
        "objective" => "Número de unidades pesadas entregadas",
        "metric" => "Unidad",
        "goal" => 4,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "46783418",
        "objective" => "Meta de facturación mensual mayor o igual a 100%\n",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "10"
      ],
      [
        "dni" => "46966858",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Porcentaje",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "46966858",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "46966858",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Soles",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "46966858",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "46670405",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "10"
      ],
      [
        "dni" => "42662829",
        "objective" => "Efectividad de proyección de facturación",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "33"
      ],
      [
        "dni" => "42662829",
        "objective" => "Recuperación de documentos",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "34"
      ],
      [
        "dni" => "42662829",
        "objective" => "Efectividad proyección de cobranza",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "33"
      ],
      [
        "dni" => "45957690",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "10"
      ],
      [
        "dni" => "47552281",
        "objective" => "Cumplimiento de ICD al 90% por sede",
        "metric" => "Porcentaje",
        "goal" => 90,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "47552281",
        "objective" => "Cumplimiento de los standares del Postventa por parte de Derco al 80%",
        "metric" => "Porcentaje",
        "goal" => 80,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "16523120",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Porcentaje",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "16523120",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "16523120",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Soles",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "16523120",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "71232635",
        "objective" => "Memorándums elaborados",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "33"
      ],
      [
        "dni" => "71232635",
        "objective" => "Reclamos atendidos",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "33"
      ],
      [
        "dni" => "71232635",
        "objective" => "Solicitudes y requerimientos de entidades",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "34"
      ],
      [
        "dni" => "19247378",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Porcentaje",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "19247378",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "19247378",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Soles",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "19247378",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "43141821",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Porcentaje",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "43141821",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "43141821",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Soles",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "43141821",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "80590892",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Porcentaje",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "80590892",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "80590892",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Soles",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "80590892",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "40708289",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "10"
      ],
      [
        "dni" => "75262550",
        "objective" => "Tasa de reclamo menor o igual a 5% ",
        "metric" => "Porcentaje",
        "goal" => 5,
        "isAscending" => 0,
        "weight" => "10"
      ],
      [
        "dni" => "74537001",
        "objective" => "Tasa de reclamo menor o igual a 5% ",
        "metric" => "Porcentaje",
        "goal" => 5,
        "isAscending" => 0,
        "weight" => "10"
      ],
      [
        "dni" => "76332500",
        "objective" => "Reingresos de vehículos",
        "metric" => "Unidad",
        "goal" => 2,
        "isAscending" => 0,
        "weight" => "50"
      ],
      [
        "dni" => "76332500",
        "objective" => "Eficiencia de OT > 70%",
        "metric" => "Porcentaje",
        "goal" => 70,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "72936727",
        "objective" => "Reingresos de vehículos",
        "metric" => "Unidad",
        "goal" => 2,
        "isAscending" => 0,
        "weight" => "50"
      ],
      [
        "dni" => "72936727",
        "objective" => "Eficiencia de OT > 70%",
        "metric" => "Porcentaje",
        "goal" => 70,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "16729345",
        "objective" => "Cero soles de multa por errores contables en la empresa a cargo\n",
        "metric" => "Soles",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "33"
      ],
      [
        "dni" => "16729345",
        "objective" => "Cero errores en el registro de información contable de manera mensual",
        "metric" => "Errores",
        "goal" => 2,
        "isAscending" => 0,
        "weight" => "33"
      ],
      [
        "dni" => "16729345",
        "objective" => "Entrega de cierre contable mensual integral (en los primeros 12 días calendario)",
        "metric" => "Dias",
        "goal" => 12,
        "isAscending" => 0,
        "weight" => "34"
      ],
      [
        "dni" => "73144212",
        "objective" => "Cumplimiento del cronograma en el desarrollo del sistema Milla AP",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "73144212",
        "objective" => "Calidad del software",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "73144212",
        "objective" => "Incidencias resueltas en el equipo (número)",
        "metric" => "Número",
        "goal" => 5,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "73144212",
        "objective" => "Nivel de satisfacción de los stakeholders respecto a la calidad de las entregas del sistema.(%)",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "75085359",
        "objective" => "Cumplimiento del cronograma en el desarrollo del sistema Milla AP",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "75085359",
        "objective" => "Calidad del software",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "75085359",
        "objective" => "Incidencias resueltas en el equipo (número)",
        "metric" => "Número",
        "goal" => 5,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "75085359",
        "objective" => "Nivel de satisfacción de los stakeholders respecto a la calidad de las entregas del sistema.(%)",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "47844976",
        "objective" => "Cumplir la meta de OT mensual",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "33"
      ],
      [
        "dni" => "47844976",
        "objective" => "Cumplimiento mensual de venta de accesorios",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "33"
      ],
      [
        "dni" => "47844976",
        "objective" => "Cumplimiento de NPS",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "34"
      ],
      [
        "dni" => "44265001",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Porcentaje",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "44265001",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "44265001",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Soles",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "44265001",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "45806884",
        "objective" => "Cumplimiento mensual de los límites de velocidad en ruta",
        "metric" => "Porcentaje",
        "goal" => 99,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "45806884",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "45806884",
        "objective" => "Cero multas por infringir las normas de tránsito en el desarrollo de sus funciones",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "45806884",
        "objective" => "Cero incidencias en la ruta asignada (robos, choques,etc)",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "02899619",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidad",
        "goal" => 25,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "02899619",
        "objective" => "Meta en seguros",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "02899619",
        "objective" => "Meta en créditos",
        "metric" => "Número",
        "goal" => 6,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "02899619",
        "objective" => "Alcance de Mix de Marca",
        "metric" => "Unidad",
        "goal" => 4,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "70475815",
        "objective" => "Ejecución del Plan de Clima Laboral y Bienestar",
        "metric" => "Porcentaje",
        "goal" => 95,
        "isAscending" => 1,
        "weight" => "33"
      ],
      [
        "dni" => "70475815",
        "objective" => "Adherencia a Pakatnamu Star",
        "metric" => "Porcentaje",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => "33"
      ],
      [
        "dni" => "70475815",
        "objective" => "Subsidios recuperados",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "34"
      ],
      [
        "dni" => "10427037",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Porcentaje",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "10427037",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "10427037",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Soles",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "10427037",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "47221021",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "10"
      ],
      [
        "dni" => "42091625",
        "objective" => "Cuadres de caja oportunos",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "42091625",
        "objective" => "Arqueo de caja, sin diferencias ",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "41037736",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidad",
        "goal" => 5,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "41037736",
        "objective" => "Colocación de créditos",
        "metric" => "Unidad",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "41037736",
        "objective" => "Colocación de seguros",
        "metric" => "Unidad",
        "goal" => 1,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "75215691",
        "objective" => "Calidad de servicio - Mesa de ayuda (Milla \/ Dynamics)",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "75215691",
        "objective" => "Promedio de efectividad en tiempo de atención - Soporte TIC's",
        "metric" => "Minutos",
        "goal" => 45,
        "isAscending" => 0,
        "weight" => "50"
      ],
      [
        "dni" => "60899000",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "10"
      ],
      [
        "dni" => "75078996",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "10"
      ],
      [
        "dni" => "74068114",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "10"
      ],
      [
        "dni" => "74135027",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "10"
      ],
      [
        "dni" => "78378602",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "10"
      ],
      [
        "dni" => "72657695",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 45,
        "isAscending" => 0,
        "weight" => "10"
      ],
      [
        "dni" => "76422823",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 45,
        "isAscending" => 0,
        "weight" => "10"
      ],
      [
        "dni" => "76846424",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 45,
        "isAscending" => 0,
        "weight" => "10"
      ],
      [
        "dni" => "73786239",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 45,
        "isAscending" => 0,
        "weight" => "10"
      ],
      [
        "dni" => "41583758",
        "objective" => "Cumplimiento mensual de los límites de velocidad en ruta",
        "metric" => "Porcentaje",
        "goal" => 99,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "41583758",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Minutos",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "41583758",
        "objective" => "Cero multas por infringir las normas de tránsito en el desarrollo de sus funciones",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "41583758",
        "objective" => "Cero incidencias en la ruta asignada (robos, choques,etc)",
        "metric" => "Unidad",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => "25"
      ],
      [
        "dni" => "73090979",
        "objective" => "Cumplir la meta de OT mensual",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "33"
      ],
      [
        "dni" => "73090979",
        "objective" => "Cumplimiento mensual de venta de accesorios",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "33"
      ],
      [
        "dni" => "73090979",
        "objective" => "Cumplimiento de NPS",
        "metric" => "Porcentaje",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => "34"
      ],
      [
        "dni" => "72218186",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidad",
        "goal" => 6,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "72218186",
        "objective" => "Colocación de créditos",
        "metric" => "Unidad",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "72218186",
        "objective" => "Colocación de seguros",
        "metric" => "Unidad",
        "goal" => 1,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "46976471",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidad",
        "goal" => 5,
        "isAscending" => 1,
        "weight" => "50"
      ],
      [
        "dni" => "46976471",
        "objective" => "Colocación de créditos",
        "metric" => "Unidad",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => "25"
      ],
      [
        "dni" => "46976471",
        "objective" => "Colocación de seguros",
        "metric" => "Unidad",
        "goal" => 1,
        "isAscending" => 1,
        "weight" => "25"
      ]
    ];
    /**
     * [
     * "dni"=> "45727231",
     * "objective"=> "Reingresos de vehículos",
     * "metric"=> "Número",
     * "goal"=> 2,
     * "isAscending"=> 0,
     * "weight"=> 50,
     * ]
     */

    //      1. Eliminar todos los registros de las tablas
    DB::statement('SET FOREIGN_KEY_CHECKS=0;');

    EvaluationPersonDashboard::query()->truncate();
    EvaluationDashboard::query()->truncate();
//    EvaluationPersonDetail::query()->truncate();
    EvaluationPerson::query()->truncate();
    EvaluationPersonResult::query()->truncate();
    EvaluationPersonCycleDetail::query()->truncate();
    EvaluationCategoryObjectiveDetail::query()->truncate();
//    EvaluationCategoryCompetenceDetail::query()->truncate();
    EvaluationCycle::query()->truncate();
    Evaluation::query()->truncate();
    EvaluationObjective::query()->truncate();
    EvaluationCycleCategoryDetail::query()->truncate();
//    HierarchicalCategoryDetail::query()->truncate();
//    HierarchicalCategory::query()->truncate();

    DB::statement('SET FOREIGN_KEY_CHECKS=1;');


    //    2. Definir servicios
    $categoryObjectiveService = new EvaluationCategoryObjectiveDetailService();

    //    Mapear todos los datos
    foreach ($data as $item) {
      $person = Worker::where('vat', $item['dni'])->where('status_id', 22)->first();

      $category = $person->position->hierarchicalCategory;

      $objective = EvaluationObjective::where('name', $item['objective'])->first();
      $metric = EvaluationMetric::where('name', $item['metric'])->first();

      if (!$person) {
        throw new \Exception("No se encontró la persona con DNI: {$item['dni']}");
      }

      if (!$person->cargo_id) {
        throw new \Exception("La persona con DNI: {$item['dni']} no tiene un cargo asignado.");
      }

      if (!$category) {
        throw new \Exception("La persona con DNI: {$item['dni']} no tiene una categoría jerárquica asignada.");
      }

      $categoryDetail = HierarchicalCategoryDetail::where('position_id', $person->cargo_id)
        ->first();

      if (!$categoryDetail) {
        HierarchicalCategoryDetail::create([
          'hierarchical_category_id' => $category->id,
          'position_id' => $person->cargo_id,
        ]);
      }


      if (!$metric) {
        $newMetric = EvaluationMetric::create([
          'name' => $item['metric'],
          'description' => 'Description for the metric ' . $item['metric'],
        ]);
        $metric = EvaluationMetric::find($newMetric->id);
      }

      if (!$objective) {
        $newObjective = EvaluationObjective::create([
          'name' => $item['objective'],
          'description' => 'Description for the objective ' . $item['objective'],
          'goalReference' => $item['goal'],
          'fixedWeight' => false,
          'isAscending' => $item['isAscending'],
          'metric_id' => $metric->id
        ]);
        $objective = EvaluationObjective::find($newObjective->id);
      }

      if (!$objective) {
        throw new \Exception("No se encontró el objetivo: {$item['objective']}");
      }


      $data = [
        'objective_id' => $objective->id,
        'category_id' => $category->id,
        'person_id' => $person->id,
        'goal' => $objective->goalReference,
        'fixedWeight' => true,
        'weight' => $item['weight'],
      ];
      EvaluationCategoryObjectiveDetail::create($data);
    }

    //    4. Asignar el gerente general category 'Gerente General' position id 23
    $categoryChiefExecutive = HierarchicalCategory::create([
      'name' => 'Gerente General',
      'description' => 'Description for the category Gerente General',
      'hasObjectives' => false,
    ]);

    HierarchicalCategoryDetail::create([
      'hierarchical_category_id' => $categoryChiefExecutive->id,
      'position_id' => 23,
    ]);

    //    Excluir del cálculo al gerente general
    $excludedPositions = [215, 307, 255, 214, 276, 23, 294, 326, 295, 352];
    $excludedPeople = Worker::whereIn('cargo_id', $excludedPositions)->where('status_id', 22)->get();
    if ($excludedPeople) {
      foreach ($excludedPeople as $excludedId) {
        EvaluationPersonDetail::create([
          'person_id' => $excludedId->id,
        ]);
      }
    }
    //    5. Asignar objetivos faltantes a todas las categorías
    $categoryObjectiveService->assignMissingObjectives();
  }
}
