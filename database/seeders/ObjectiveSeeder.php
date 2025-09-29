<?php

namespace Database\Seeders;

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

class ObjectiveSeeder extends Seeder
{
  public function run(): void
  {
    $data = [
      [
        "dni" => "45727231",
        "objective" => "Reingresos de vehículos",
        "metric" => "Número",
        "goal" => 2,
        "isAscending" => 0,
        "weight" => 50,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "45727231",
        "objective" => "Eficiencia de OT > 70%",
        "metric" => "Número",
        "goal" => 70,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "73748430",
        "objective" => "Cumplimiento con la actualización de información hoja de trabajo de Fact y cobranza",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Asistente De Facturacion Y Cobranza"
      ],
      [
        "dni" => "73748430",
        "objective" => "Recuperación de documentos",
        "metric" => "Número",
        "goal" => 90,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Asistente De Facturacion Y Cobranza"
      ],
      [
        "dni" => "73748430",
        "objective" => "Control de parihuelas Gloria",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Asistente De Facturacion Y Cobranza"
      ],
      [
        "dni" => "73748430",
        "objective" => "Aplicación de detracciones",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Asistente De Facturacion Y Cobranza"
      ],
      [
        "dni" => "73748430",
        "objective" => "Cumplimiento con la recuperación de documentos para facturación",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Asistente De Facturacion Y Cobranza"
      ],
      [
        "dni" => "41086411",
        "objective" => "Cumplimiento mensual de los límites de velocidad en ruta",
        "metric" => "Número",
        "goal" => 99,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "41086411",
        "objective" => "Cero multas por infringir las normas de tránsito en el desarrollo de sus funciones",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "41086411",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "41086411",
        "objective" => "Cero incidencias en la ruta asignada (robos, choques,etc)",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "71447834",
        "objective" => "Número de unidades entregadas",
        "metric" => "Número",
        "goal" => 5,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "71447834",
        "objective" => "Colocación de créditos",
        "metric" => "Número",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "71447834",
        "objective" => "Colocación de seguros",
        "metric" => "Número",
        "goal" => 1,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "71319927",
        "objective" => "Número de unidades entregadas",
        "metric" => "Número",
        "goal" => 13,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "71319927",
        "objective" => "Colocación de créditos",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "71319927",
        "objective" => "Colocación de seguros",
        "metric" => "Número",
        "goal" => 1,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "47620737",
        "objective" => "Incrementar el % de cumplimiento de la programaciòn (Servicios entregados a tiempo/total de servicios programados)",
        "metric" => "Número",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Asistente De Seguimiento Y Monitoreo"
      ],
      [
        "dni" => "47620737",
        "objective" => "No tener robos por falta de seguimiento y monitoreo",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 33,
        "categoria" => "Asistente De Seguimiento Y Monitoreo"
      ],
      [
        "dni" => "47620737",
        "objective" => "Accidente y/o Siniestros por responsabilidad el equipo de operaciones y mantenimiento",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 34,
        "categoria" => "Asistente De Seguimiento Y Monitoreo"
      ],
      [
        "dni" => "42330495",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Número",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "42330495",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "42330495",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "42330495",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Número",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "32932405",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => 100,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "47085242",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Número",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "47085242",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "47085242",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "47085242",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Número",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "75421824",
        "objective" => "Arqueo de caja, sin diferencias ",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Caja Ap"
      ],
      [
        "dni" => "75421824",
        "objective" => "Cuadres de caja oportunos",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Caja Ap"
      ],
      [
        "dni" => "75421824",
        "objective" => "Envío de reportes oportunos.",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Caja Ap"
      ],
      [
        "dni" => "75421824",
        "objective" => "Informe de estado de las CXC.",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Caja Ap"
      ],
      [
        "dni" => "70059908",
        "objective" => "Cuadres de caja oportunos",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Caja Dp"
      ],
      [
        "dni" => "70059908",
        "objective" => "Arqueo de caja, sin diferencias ",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Caja Dp"
      ],
      [
        "dni" => "40502833",
        "objective" => "Número de unidades entregadas",
        "metric" => "Número",
        "goal" => 6,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "40502833",
        "objective" => "Colocación de créditos",
        "metric" => "Número",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "40502833",
        "objective" => "Colocación de seguros",
        "metric" => "Número",
        "goal" => 1,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "40619365",
        "objective" => "Vales pendientes rendidos al 100%",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Caja General"
      ],
      [
        "dni" => "40619365",
        "objective" => "Arqueo de caja, sin diferencias ",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Caja General"
      ],
      [
        "dni" => "40619365",
        "objective" => "Cuadres de caja oportunos",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Caja General"
      ],
      [
        "dni" => "40619365",
        "objective" => "Envio de reportes oportunos",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Caja General"
      ],
      [
        "dni" => "40619365",
        "objective" => "Informe de estado de las cuentas por cobrar",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Caja General"
      ],
      [
        "dni" => "71436051",
        "objective" => "Ejecución del Plan de Clima Laboral y Bienestar",
        "metric" => "Número",
        "goal" => 95,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Trabajadora Social"
      ],
      [
        "dni" => "71436051",
        "objective" => "Adherencia a Pakatnamu Star",
        "metric" => "Número",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Trabajadora Social"
      ],
      [
        "dni" => "71436051",
        "objective" => "Subsidios recuperados",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 34,
        "categoria" => "Trabajadora Social"
      ],
      [
        "dni" => "70475815",
        "objective" => "Ejecución del Plan de Clima Laboral y Bienestar",
        "metric" => "Número",
        "goal" => 95,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Asistente De Bienestar Y Clima Laboral"
      ],
      [
        "dni" => "70475815",
        "objective" => "Adherencia a Pakatnamu Star",
        "metric" => "Número",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Asistente De Bienestar Y Clima Laboral"
      ],
      [
        "dni" => "70475815",
        "objective" => "Subsidios recuperados",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 34,
        "categoria" => "Asistente De Bienestar Y Clima Laboral"
      ],
      [
        "dni" => "16664102",
        "objective" => "Notas de crèdito a proveedores ",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Jefe De Administracion"
      ],
      [
        "dni" => "16664102",
        "objective" => "Pago a proveedores",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Jefe De Administracion"
      ],
      [
        "dni" => "16664102",
        "objective" => "Gestión de compras ",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Jefe De Administracion"
      ],
      [
        "dni" => "16664102",
        "objective" => "Efectividad de liquidaciones=> % de reconocimeinto de la liquidacion / total de liquidaciones registradas",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Jefe De Administracion"
      ],
      [
        "dni" => "76752460",
        "objective" => "Eficiencia de OT > 70%",
        "metric" => "Número",
        "goal" => 70,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "76752460",
        "objective" => "Reingresos de vehículos",
        "metric" => "Número",
        "goal" => 2,
        "isAscending" => 0,
        "weight" => 50,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "18186852",
        "objective" => "Cumplimiento mensual de los límites de velocidad en ruta",
        "metric" => "Número",
        "goal" => 99,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "18186852",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "18186852",
        "objective" => "Cero multas por infringir las normas de tránsito en el desarrollo de sus funciones",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "18186852",
        "objective" => "Cero incidencias en la ruta asignada (robos, choques,etc)",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "16802538",
        "objective" => "Seguimiento de pedidos de compra de vehículos, conciliando con el sistema de emisión de facturas",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Coordinador De Ventas"
      ],
      [
        "dni" => "16802538",
        "objective" => "Obtener un resultado mayor o igual a 80% en NPS.",
        "metric" => "Número",
        "goal" => 80,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Coordinador De Ventas"
      ],
      [
        "dni" => "16802538",
        "objective" => "Cumplimiento del 100% del envío de las solicitudes de nota de crédito (7 días hábiles)",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 34,
        "categoria" => "Coordinador De Ventas"
      ],
      [
        "dni" => "16753465",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Número",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16753465",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16753465",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16753465",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Número",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "47386012",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Número",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "47386012",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "47386012",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "47386012",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Número",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "70086807",
        "objective" => "Número de unidades entregadas",
        "metric" => "Número",
        "goal" => 6,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "70086807",
        "objective" => "Colocación de créditos",
        "metric" => "Número",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "70086807",
        "objective" => "Colocación de seguros",
        "metric" => "Número",
        "goal" => 1,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "44063728",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => 100,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "77668182",
        "objective" => "Eficiencia de OT > 70%",
        "metric" => "Número",
        "goal" => 70,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "77668182",
        "objective" => "Reingresos de vehículos",
        "metric" => "Número",
        "goal" => 2,
        "isAscending" => 0,
        "weight" => 50,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "76252984",
        "objective" => "Eficiencia de OT > 70%",
        "metric" => "Número",
        "goal" => 70,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "76252984",
        "objective" => "Reingresos de vehículos",
        "metric" => "Número",
        "goal" => 2,
        "isAscending" => 0,
        "weight" => 50,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "73747580",
        "objective" => "Calidad de servicio - Mesa de ayuda (Milla / Dynamics)",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Analista En Proyecto TICs"
      ],
      [
        "dni" => "73747580",
        "objective" => "Promedio de efectividad en tiempo de atención - Soporte TIC's",
        "metric" => "Número",
        "goal" => 45,
        "isAscending" => 0,
        "weight" => 50,
        "categoria" => "Analista En Proyecto TICs"
      ],
      [
        "dni" => "72790935",
        "objective" => "Eficiencia de OT > 70%",
        "metric" => "Número",
        "goal" => 70,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "72790935",
        "objective" => "Reingresos de vehículos",
        "metric" => "Número",
        "goal" => 2,
        "isAscending" => 0,
        "weight" => 50,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "19255538",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Número",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "19255538",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "19255538",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "19255538",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Número",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "72116004",
        "objective" => "Eficiencia de OT > 70%",
        "metric" => "Número",
        "goal" => 70,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "72116004",
        "objective" => "Reingresos de vehículos",
        "metric" => "Número",
        "goal" => 2,
        "isAscending" => 0,
        "weight" => 50,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "46468460",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => 100,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "47241820",
        "objective" => "Cumplimiento mensual de los límites de velocidad en ruta",
        "metric" => "Número",
        "goal" => 99,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor Ii"
      ],
      [
        "dni" => "47241820",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor Ii"
      ],
      [
        "dni" => "47241820",
        "objective" => "Cero multas por infringir las normas de tránsito en el desarrollo de sus funciones",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor Ii"
      ],
      [
        "dni" => "47241820",
        "objective" => "Cero incidencias en la ruta asignada (robos, choques,etc)",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor Ii"
      ],
      [
        "dni" => "80541083",
        "objective" => "Número de unidades entregadas",
        "metric" => "Número",
        "goal" => 94,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Gerente Comercial Ap"
      ],
      [
        "dni" => "80541083",
        "objective" => "Rentabilidad bruta por grupo de marcas",
        "metric" => "Número",
        "goal" => "5.1",
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Gerente Comercial Ap"
      ],
      [
        "dni" => "80541083",
        "objective" => "Cumplimiento de colaterales=> Financiamiento",
        "metric" => "Número",
        "goal" => 24,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Gerente Comercial Ap"
      ],
      [
        "dni" => "80541083",
        "objective" => "Cumplimiento de colaterales=> Seguros",
        "metric" => "Número",
        "goal" => 18,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Gerente Comercial Ap"
      ],
      [
        "dni" => "80541083",
        "objective" => "Porcentaje de MS",
        "metric" => "Número",
        "goal" => 11.5,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Gerente Comercial Ap"
      ],
      [
        "dni" => "70860579",
        "objective" => "Eficiencia de OT > 70%",
        "metric" => "Número",
        "goal" => 70,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "70860579",
        "objective" => "Reingresos de vehículos",
        "metric" => "Número",
        "goal" => 2,
        "isAscending" => 0,
        "weight" => 50,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "42119750",
        "objective" => "Cobranza ",
        "metric" => "Número",
        "goal" => 20,
        "isAscending" => 0,
        "weight" => 20,
        "categoria" => "Jefe De Marketing"
      ],
      [
        "dni" => "42119750",
        "objective" => "Posicionamiento digital (SEO)  AP",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Jefe De Marketing"
      ],
      [
        "dni" => "42119750",
        "objective" => "Inversión ",
        "metric" => "Número",
        "goal" => 25,
        "isAscending" => 0,
        "weight" => 20,
        "categoria" => "Jefe De Marketing"
      ],
      [
        "dni" => "42119750",
        "objective" => "Win rate de Lead AP mayor al 3%",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Jefe De Marketing"
      ],
      [
        "dni" => "42119750",
        "objective" => "Reputación Chiclayo ",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 5,
        "categoria" => "Jefe De Marketing"
      ],
      [
        "dni" => "42119750",
        "objective" => "Reputacion Cajamarca ",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 5,
        "categoria" => "Jefe De Marketing"
      ],
      [
        "dni" => "42119750",
        "objective" => "Reputación Jaén ",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 5,
        "categoria" => "Jefe De Marketing"
      ],
      [
        "dni" => "42119750",
        "objective" => "Reputación Piura ",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 5,
        "categoria" => "Jefe De Marketing"
      ],
      [
        "dni" => "16804371",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Número",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16804371",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16804371",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16804371",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Número",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "45017690",
        "objective" => "Gestión de compras ",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Jefe De Administracion"
      ],
      [
        "dni" => "45017690",
        "objective" => "Notas de crèdito a proveedores ",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Jefe De Administracion"
      ],
      [
        "dni" => "45017690",
        "objective" => "Efectividad de liquidaciones=> % de reconocimeinto de la liquidacion / total de liquidaciones registradas",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Jefe De Administracion"
      ],
      [
        "dni" => "45017690",
        "objective" => "Cumplimiento de ventas en sede ",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Jefe De Administracion"
      ],
      [
        "dni" => "45017690",
        "objective" => "Cumplimiento de cobranza de sede",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Jefe De Administracion"
      ],
      [
        "dni" => "42374851",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Número",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "42374851",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "42374851",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "42374851",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Número",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "43920495",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Número",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "43920495",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "43920495",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "43920495",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Número",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "45695736",
        "objective" => "Utilidad Operativa",
        "metric" => "Número",
        "goal" => 135000,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Supervisor Comercial"
      ],
      [
        "dni" => "45695736",
        "objective" => "Número de viajes",
        "metric" => "Número",
        "goal" => 350,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Supervisor Comercial"
      ],
      [
        "dni" => "71325400",
        "objective" => "Cero soles de multa por errores contables en la empresa a cargo",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 20,
        "categoria" => "Asistente De Contabilidad"
      ],
      [
        "dni" => "71325400",
        "objective" => "Exactitud en registros contables",
        "metric" => "Numero",
        "goal" => 100,
        "isAscending" => 0,
        "weight" => 50,
        "categoria" => "Asistente De Contabilidad"
      ],
      [
        "dni" => "71325400",
        "objective" => "Entrega de cierres contables en tiempo de empresa a cargo",
        "metric" => "Número",
        "goal" => 5,
        "isAscending" => 1,
        "weight" => 30,
        "categoria" => "Asistente De Contabilidad"
      ],
      [
        "dni" => "42704083",
        "objective" => "Cumplimiento de presupuesto comercial",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "42704083",
        "objective" => "% de Efectividad de Cobranza",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "46466488",
        "objective" => "Número de unidades entregadas",
        "metric" => "Número",
        "goal" => 7,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "46466488",
        "objective" => "Colocación de créditos",
        "metric" => "Número",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "46466488",
        "objective" => "Colocación de seguros",
        "metric" => "Número",
        "goal" => 1,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "45577414",
        "objective" => "Cumplimiento mensual de los límites de velocidad en ruta",
        "metric" => "Número",
        "goal" => 99,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "45577414",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "45577414",
        "objective" => "Cero multas por infringir las normas de tránsito en el desarrollo de sus funciones",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "45577414",
        "objective" => "Cero incidencias en la ruta asignada (robos, choques,etc)",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "75756058",
        "objective" => "Cumplir la meta de OT mensual",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Asesor De Servicios"
      ],
      [
        "dni" => "75756058",
        "objective" => "Cumplimiento de NPS",
        "metric" => "Número",
        "goal" => 80,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Asesor De Servicios"
      ],
      [
        "dni" => "75756058",
        "objective" => "Cumplimiento de meta mensual de venta de accesorios",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 34,
        "categoria" => "Asesor De Servicios"
      ],
      [
        "dni" => "44652712",
        "objective" => "Cero soles de multa por errores contables en la empresa a cargo",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 33,
        "categoria" => "Asistente De Contabilidad"
      ],
      [
        "dni" => "44652712",
        "objective" => "Cero errores en el registro de información contable de manera mensual",
        "metric" => "Número",
        "goal" => 2,
        "isAscending" => 0,
        "weight" => 33,
        "categoria" => "Asistente De Contabilidad"
      ],
      [
        "dni" => "44652712",
        "objective" => "Entrega de cierre contable mensual integral (en los primeros 12 días calendario)",
        "metric" => "Número",
        "goal" => 12,
        "isAscending" => 0,
        "weight" => 34,
        "categoria" => "Asistente De Contabilidad"
      ],
      [
        "dni" => "19256673",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => 100,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "27167946",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Número",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "27167946",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "27167946",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "27167946",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Número",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "41893192",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => 100,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "45092596",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Número",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "45092596",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "45092596",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "45092596",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Número",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "41399449",
        "objective" => "Cumplimiento de presupuesto comercial",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "41399449",
        "objective" => "% de Efectividad de Cobranza",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "41399449",
        "objective" => "Mix de productos",
        "metric" => "Número",
        "goal" => 5,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "41399449",
        "objective" => "Cliente Nuevo al mes",
        "metric" => "Número",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "72904816",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => 100,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "05644606",
        "objective" => "Número de unidades entregadas",
        "metric" => "Número",
        "goal" => 12,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "05644606",
        "objective" => "Colocación de créditos",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "05644606",
        "objective" => "Colocación de seguros",
        "metric" => "Número",
        "goal" => 1,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "70408777",
        "objective" => "Reingresos de vehículos",
        "metric" => "Número",
        "goal" => 2,
        "isAscending" => 0,
        "weight" => 50,
        "categoria" => "Coordinador De Taller"
      ],
      [
        "dni" => "70408777",
        "objective" => "Cumplimiento de OT>100%",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Coordinador De Taller"
      ],
      [
        "dni" => "45523776",
        "objective" => "Vales pendientes rendidos al 100%",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Caja General"
      ],
      [
        "dni" => "45523776",
        "objective" => "Arqueo de caja, sin diferencias ",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Caja General"
      ],
      [
        "dni" => "45523776",
        "objective" => "Cuadres de caja oportunos",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Caja General"
      ],
      [
        "dni" => "45523776",
        "objective" => "Envio de reportes oportunos",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Caja General"
      ],
      [
        "dni" => "45523776",
        "objective" => "Informe de estado de las cuentas por cobrar",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Caja General"
      ],
      [
        "dni" => "74413833",
        "objective" => "Eficiencia de OT > 70%",
        "metric" => "Número",
        "goal" => 70,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "74413833",
        "objective" => "Reingresos de vehículos",
        "metric" => "Número",
        "goal" => 2,
        "isAscending" => 0,
        "weight" => 50,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "47080684",
        "objective" => "Eficiencia de OT > 70%",
        "metric" => "Número",
        "goal" => 70,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "47080684",
        "objective" => "Reingresos de vehículos",
        "metric" => "Número",
        "goal" => 2,
        "isAscending" => 0,
        "weight" => 50,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "46280064",
        "objective" => "Cuadres de caja oportunos",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Caja Dp"
      ],
      [
        "dni" => "46280064",
        "objective" => "Arqueo de caja, sin diferencias ",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Caja Dp"
      ],
      [
        "dni" => "70897298",
        "objective" => "Promedio de vacaciones DP y TP",
        "metric" => "Número",
        "goal" => 24,
        "isAscending" => 0,
        "weight" => 20,
        "categoria" => "Partner De Gestión Humana"
      ],
      [
        "dni" => "70897298",
        "objective" => "Procesos dentro del plazo",
        "metric" => "Número",
        "goal" => 80,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Partner De Gestión Humana"
      ],
      [
        "dni" => "70897298",
        "objective" => "Porcentaje de legajos digitalizados",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Partner De Gestión Humana"
      ],
      [
        "dni" => "70897298",
        "objective" => "Plazo de envio de boletas DP",
        "metric" => "Número",
        "goal" => 5,
        "isAscending" => 0,
        "weight" => 20,
        "categoria" => "Partner De Gestión Humana"
      ],
      [
        "dni" => "70897298",
        "objective" => "Plazo de envio de boletas TP",
        "metric" => "Número",
        "goal" => 7,
        "isAscending" => 0,
        "weight" => 20,
        "categoria" => "Partner De Gestión Humana"
      ],
      [
        "dni" => "42352466",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => 100,
        "categoria" => "Supervisor De Almacen"
      ],
      [
        "dni" => "02767057",
        "objective" => "Cumplimiento mensual de los límites de velocidad en ruta",
        "metric" => "Número",
        "goal" => 99,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "02767057",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "02767057",
        "objective" => "Cero multas por infringir las normas de tránsito en el desarrollo de sus funciones",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "02767057",
        "objective" => "Cero incidencias en la ruta asignada (robos, choques,etc)",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "48139848",
        "objective" => "Cumplimiento mensual de los límites de velocidad en ruta",
        "metric" => "Número",
        "goal" => 99,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "48139848",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "48139848",
        "objective" => "Cero multas por infringir las normas de tránsito en el desarrollo de sus funciones",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "48139848",
        "objective" => "Cero incidencias en la ruta asignada (robos, choques,etc)",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "16668399",
        "objective" => "Número de unidades entregadas",
        "metric" => "Número",
        "goal" => 58,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Jefe De Ventas Ap"
      ],
      [
        "dni" => "16668399",
        "objective" => "Meta en seguros",
        "metric" => "Número",
        "goal" => 5,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Jefe De Ventas Ap"
      ],
      [
        "dni" => "16668399",
        "objective" => "Meta en créditos",
        "metric" => "Número",
        "goal" => 14,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Jefe De Ventas Ap"
      ],
      [
        "dni" => "16668399",
        "objective" => "Alcance de Mix de Marca",
        "metric" => "Número",
        "goal" => 4,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Jefe De Ventas Ap"
      ],
      [
        "dni" => "61024946",
        "objective" => "Cumplir la meta de OT mensual",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Asesor De Servicios"
      ],
      [
        "dni" => "61024946",
        "objective" => "Cumplimiento mensual de venta de accesorios",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Asesor De Servicios"
      ],
      [
        "dni" => "61024946",
        "objective" => "Cumplimiento de NPS",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 34,
        "categoria" => "Asesor De Servicios"
      ],
      [
        "dni" => "40291710",
        "objective" => "Cumplimiento al 100% de la meta de venta establecida",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Jefe De Ventas Dp"
      ],
      [
        "dni" => "40291710",
        "objective" => "% de Efectividad de Cobranza",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Jefe De Ventas Dp"
      ],
      [
        "dni" => "40291710",
        "objective" => "Rentabilidad bruta mayor igual a 5.6%",
        "metric" => "Número",
        "goal" => 5.6,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Jefe De Ventas Dp"
      ],
      [
        "dni" => "40291710",
        "objective" => "Incremento de cartera",
        "metric" => "Número",
        "goal" => 4,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Jefe De Ventas Dp"
      ],
      [
        "dni" => "40291710",
        "objective" => "Mix de productos",
        "metric" => "Número",
        "goal" => 5,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Jefe De Ventas Dp"
      ],
      [
        "dni" => "74868424",
        "objective" => "Cero soles de multa por errores contables en la empresa a cargo",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 33,
        "categoria" => "Analista De Contabilidad AP"
      ],
      [
        "dni" => "74868424",
        "objective" => "Entrega de cierres contables en tiempo de empresa a cargo",
        "metric" => "Número",
        "goal" => 12,
        "isAscending" => 0,
        "weight" => 33,
        "categoria" => "Analista De Contabilidad AP"
      ],
      [
        "dni" => "74868424",
        "objective" => "Presentación de DDJJ mensuales a SUNAT ",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 34,
        "categoria" => "Analista De Contabilidad AP"
      ],
      [
        "dni" => "16710404",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => 100,
        "categoria" => "Supervisor De Almacen"
      ],
      [
        "dni" => "27436038",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => 100,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "44184967",
        "objective" => "Eficiencia de OT > 70%",
        "metric" => "Número",
        "goal" => 70,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "44184967",
        "objective" => "Reingresos de vehículos",
        "metric" => "Número",
        "goal" => 2,
        "isAscending" => 0,
        "weight" => 50,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "41255232",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Número",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "41255232",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "41255232",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "41255232",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Número",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "45421958",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Número",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "45421958",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "45421958",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "45421958",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Número",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "32960097",
        "objective" => "Promedio de cumplimiento de presupuesto mensual de AP, DP y TP",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 100,
        "categoria" => "Gerente De Administración Y Finanzas"
      ],
      [
        "dni" => "26619707",
        "objective" => "Stock saludable de un mes por sede",
        "metric" => "Número",
        "goal" => 1,
        "isAscending" => 0,
        "weight" => 100,
        "categoria" => "Jefe De Almacen"
      ],
      [
        "dni" => "47037915",
        "objective" => "Cumplimiento mensual de los límites de velocidad en ruta",
        "metric" => "Número",
        "goal" => 99,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "47037915",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "47037915",
        "objective" => "Cero multas por infringir las normas de tránsito en el desarrollo de sus funciones",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "47037915",
        "objective" => "Cero incidencias en la ruta asignada (robos, choques,etc)",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "47704283",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => 100,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "44764424",
        "objective" => "Cumplimiento al 100% de la meta de venta establecida",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Jefe De Ventas Dp"
      ],
      [
        "dni" => "44764424",
        "objective" => "% de Efectividad de Cobranza",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Jefe De Ventas Dp"
      ],
      [
        "dni" => "44764424",
        "objective" => "Rentabilidad bruta mayor igual a 5.6%",
        "metric" => "Número",
        "goal" => 5.6,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Jefe De Ventas Dp"
      ],
      [
        "dni" => "44764424",
        "objective" => "Incremento de cartera",
        "metric" => "Número",
        "goal" => 6,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Jefe De Ventas Dp"
      ],
      [
        "dni" => "44764424",
        "objective" => "Mix de productos",
        "metric" => "Número",
        "goal" => 5,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Jefe De Ventas Dp"
      ],
      [
        "dni" => "42315795",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Número",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "42315795",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "42315795",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "42315795",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Número",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "76009888",
        "objective" => "Cero soles de multa por errores contables en la empresa a cargo",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 50,
        "categoria" => "Asistente De Contabilidad"
      ],
      [
        "dni" => "76009888",
        "objective" => "Entrega de cierres contables en tiempo de empresa a cargo",
        "metric" => "Número",
        "goal" => 7,
        "isAscending" => 0,
        "weight" => 50,
        "categoria" => "Asistente De Contabilidad"
      ],
      [
        "dni" => "76449504",
        "objective" => "Cumplir la meta de OT mensual",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Asesor De Servicios"
      ],
      [
        "dni" => "76449504",
        "objective" => "Cumplimiento de NPS",
        "metric" => "Número",
        "goal" => 80,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Asesor De Servicios"
      ],
      [
        "dni" => "76449504",
        "objective" => "Cumplimiento de meta mensual de venta de accesorios",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 34,
        "categoria" => "Asesor De Servicios"
      ],
      [
        "dni" => "48295271",
        "objective" => "Cuadres de caja oportunos",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Caja Dp"
      ],
      [
        "dni" => "48295271",
        "objective" => "Arqueo de caja, sin diferencias ",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Caja Dp"
      ],
      [
        "dni" => "16787219",
        "objective" => "Número de unidades entregadas",
        "metric" => "Número",
        "goal" => 136,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Gerente Comercial Ap"
      ],
      [
        "dni" => "16787219",
        "objective" => "Rentabilidad bruta por grupo de marcas",
        "metric" => "Número",
        "goal" => "5.1",
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Gerente Comercial Ap"
      ],
      [
        "dni" => "16787219",
        "objective" => "Cumplimiento de colaterales=> Financiamiento",
        "metric" => "Número",
        "goal" => 32,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Gerente Comercial Ap"
      ],
      [
        "dni" => "16787219",
        "objective" => "Cumplimiento de colaterales=> Seguros",
        "metric" => "Número",
        "goal" => 12,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Gerente Comercial Ap"
      ],
      [
        "dni" => "16787219",
        "objective" => "Porcentaje de MS",
        "metric" => "Número",
        "goal" => 13,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Gerente Comercial Ap"
      ],
      [
        "dni" => "80547267",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => 100,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "47618600",
        "objective" => "Stock saludable de un mes por sede",
        "metric" => "Número",
        "goal" => 1,
        "isAscending" => 0,
        "weight" => 100,
        "categoria" => "Jefe De Almacen"
      ],
      [
        "dni" => "76574187",
        "objective" => "Vales pendientes rendidos al 100%",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Caja General"
      ],
      [
        "dni" => "76574187",
        "objective" => "Arqueo de caja, sin diferencias ",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Caja General"
      ],
      [
        "dni" => "76574187",
        "objective" => "Cuadres de caja oportunos",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Caja General"
      ],
      [
        "dni" => "76574187",
        "objective" => "Envio de reportes oportunos",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Caja General"
      ],
      [
        "dni" => "76574187",
        "objective" => "Informe de estado de las cuentas por cobrar",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Caja General"
      ],
      [
        "dni" => "45243028",
        "objective" => "Número de unidades entregadas",
        "metric" => "Número",
        "goal" => 5,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "45243028",
        "objective" => "Colocación de créditos",
        "metric" => "Número",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "45243028",
        "objective" => "Colocación de seguros",
        "metric" => "Número",
        "goal" => 1,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "73033157",
        "objective" => "Cumplimiento del  ICD al 90% por sede",
        "metric" => "Número",
        "goal" => 90,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Asistente De Post Venta"
      ],
      [
        "dni" => "73033157",
        "objective" => "Cumplimiento de los estándares de Post Venta por parte de Derco al 80%",
        "metric" => "Número",
        "goal" => 80,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Asistente De Post Venta"
      ],
      [
        "dni" => "80350561",
        "objective" => "Número de unidades entregadas",
        "metric" => "Número",
        "goal" => 6,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "80350561",
        "objective" => "Colocación de créditos",
        "metric" => "Número",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "80350561",
        "objective" => "Colocación de seguros",
        "metric" => "Número",
        "goal" => 1,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "72342187",
        "objective" => "Número de unidades entregadas",
        "metric" => "Número",
        "goal" => 8,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "72342187",
        "objective" => "Colocación de créditos",
        "metric" => "Número",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "72342187",
        "objective" => "Colocación de seguros",
        "metric" => "Número",
        "goal" => 1,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Asesor Comercial"
      ],
//      [
//        "dni" => "43112264",
//        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
//        "metric" => "Número",
//        "goal" => 92,
//        "isAscending" => 1,
//        "weight" => 25,
//        "categoria" => "Conductor De Tracto Camion"
//      ],
//      [
//        "dni" => "43112264",
//        "objective" => "No tener incidencias, con responsalidad del conductor",
//        "metric" => "Número",
//        "goal" => 0,
//        "isAscending" => 0,
//        "weight" => 25,
//        "categoria" => "Conductor De Tracto Camion"
//      ],
//      [
//        "dni" => "43112264",
//        "objective" => "No tener multas por llenado por mal llenado de guías",
//        "metric" => "Número",
//        "goal" => 0,
//        "isAscending" => 0,
//        "weight" => 25,
//        "categoria" => "Conductor De Tracto Camion"
//      ],
//      [
//        "dni" => "43112264",
//        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
//        "metric" => "Número",
//        "goal" => 85,
//        "isAscending" => 1,
//        "weight" => 25,
//        "categoria" => "Conductor De Tracto Camion"
//      ],
      [
        "dni" => "42137357",
        "objective" => "Meta de facturación mensual mayor o igual a 100%",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 100,
        "categoria" => "Asesor De Repuestos"
      ],
      [
        "dni" => "73468572",
        "objective" => "Crecimiento de comunidad (SEO)",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Community Manager"
      ],
      [
        "dni" => "73468572",
        "objective" => "Generación de Leads (Promedio mensual)",
        "metric" => "Número",
        "goal" => 500,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Community Manager"
      ],
      [
        "dni" => "73468572",
        "objective" => "Nota de Reputación SCORE total AP",
        "metric" => "Número",
        "goal" => 850,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Community Manager"
      ],
      [
        "dni" => "73468572",
        "objective" => "Winrate",
        "metric" => "Número",
        "goal" => 2.5,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Community Manager"
      ],
      [
        "dni" => "73468572",
        "objective" => "Cumplimiento del calendario de campañas",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Community Manager"
      ],
      [
        "dni" => "76627801",
        "objective" => "Vales pendientes rendidos al 100%",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Caja General"
      ],
      [
        "dni" => "76627801",
        "objective" => "Arqueo de caja, sin diferencias ",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Caja General"
      ],
      [
        "dni" => "76627801",
        "objective" => "Cuadres de caja oportunos",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Caja General"
      ],
      [
        "dni" => "76627801",
        "objective" => "Envio de reportes oportunos",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Caja General"
      ],
      [
        "dni" => "76627801",
        "objective" => "Informe de estado de las cuentas por cobrar",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Caja General"
      ],
      [
        "dni" => "48021041",
        "objective" => "Cuadres de caja oportunos",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Caja Dp"
      ],
      [
        "dni" => "48021041",
        "objective" => "Arqueo de caja, sin diferencias ",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Caja Dp"
      ],
      [
        "dni" => "75581810",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => 100,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "74325638",
        "objective" => "Eficiencia de OT > 70%",
        "metric" => "Número",
        "goal" => 70,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "74325638",
        "objective" => "Reingresos de vehículos",
        "metric" => "Número",
        "goal" => 2,
        "isAscending" => 0,
        "weight" => 50,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "41694134",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Número",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "41694134",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "41694134",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "41694134",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Número",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "42606028",
        "objective" => "Número de unidades entregadas",
        "metric" => "Número",
        "goal" => 13,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "42606028",
        "objective" => "Colocación de créditos",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "42606028",
        "objective" => "Colocación de seguros",
        "metric" => "Número",
        "goal" => 1,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "02683938",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => 100,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "26729421",
        "objective" => "Seguimiento de pedidos de compra de vehículos, conciliando con el sistema de emisión de facturas",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Coordinador De Ventas"
      ],
      [
        "dni" => "26729421",
        "objective" => "Obtener un resultado mayor o igual a 80% en NPS.",
        "metric" => "Número",
        "goal" => 80,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Coordinador De Ventas"
      ],
      [
        "dni" => "26729421",
        "objective" => "Cumplimiento del 100% del envío de las solicitudes de nota de crédito (7 días hábiles)",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 34,
        "categoria" => "Coordinador De Ventas"
      ],
      [
        "dni" => "40441721",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Número",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "40441721",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "40441721",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "40441721",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Número",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "004565680",
        "objective" => "Seguimiento de pedidos de compra de vehículos, conciliando con el sistema de emisión de facturas",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Coordinador De Ventas"
      ],
      [
        "dni" => "004565680",
        "objective" => "Obtener un resultado mayor o igual a 80% en NPS.",
        "metric" => "Número",
        "goal" => 80,
        "isAscending" => 1,
        "weight" => 34,
        "categoria" => "Coordinador De Ventas"
      ],
      [
        "dni" => "004565680",
        "objective" => "Cumplimiento del 100% del envío de las solicitudes de nota de crédito (7 días hábiles)",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Coordinador De Ventas"
      ],
      [
        "dni" => "74443725",
        "objective" => "Entrega de cierres contables en tiempo de empresa a cargo",
        "metric" => "Número",
        "goal" => 7,
        "isAscending" => 0,
        "weight" => 50,
        "categoria" => "Asistente De Contabilidad"
      ],
      [
        "dni" => "74443725",
        "objective" => "Cero soles de multa por errores contables en la empresa a cargo",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 50,
        "categoria" => "Asistente De Contabilidad"
      ],
      [
        "dni" => "02786294",
        "objective" => "Número de unidades entregadas",
        "metric" => "Número",
        "goal" => 7,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "02786294",
        "objective" => "Colocación de créditos",
        "metric" => "Número",
        "goal" => 1,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "41340413",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Número",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "41340413",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "41340413",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "41340413",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Número",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "74881133",
        "objective" => "Cumplir la meta de OT mensual",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Asesor De Servicios"
      ],
      [
        "dni" => "74881133",
        "objective" => "Cumplimiento de NPS",
        "metric" => "Número",
        "goal" => 80,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Asesor De Servicios"
      ],
      [
        "dni" => "74881133",
        "objective" => "Cumplimiento de meta mensual de venta de accesorios",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 34,
        "categoria" => "Asesor De Servicios"
      ],
      [
        "dni" => "72703707",
        "objective" => "Promedio de vacaciones AP y GP",
        "metric" => "Número",
        "goal" => 24,
        "isAscending" => 0,
        "weight" => 20,
        "categoria" => "Partner De Gestión Humana"
      ],
      [
        "dni" => "72703707",
        "objective" => "Proceso dentro del plazo ",
        "metric" => "Número",
        "goal" => 80,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Partner De Gestión Humana"
      ],
      [
        "dni" => "72703707",
        "objective" => "Porcentaje de legajos digitalizados",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Partner De Gestión Humana"
      ],
      [
        "dni" => "72703707",
        "objective" => "Plazo de envio de boletas GP",
        "metric" => "Número",
        "goal" => 5,
        "isAscending" => 0,
        "weight" => 20,
        "categoria" => "Partner De Gestión Humana"
      ],
      [
        "dni" => "72703707",
        "objective" => "Plazo de envio de boletas AP",
        "metric" => "Número",
        "goal" => 7,
        "isAscending" => 0,
        "weight" => 20,
        "categoria" => "Partner De Gestión Humana"
      ],
//      [
//        "dni" => "42562016",
//        "objective" => "Cumplimiento mensual de los límites de velocidad en ruta",
//        "metric" => "Número",
//        "goal" => 99,
//        "isAscending" => 1,
//        "weight" => 25,
//        "categoria" => "Conductor I"
//      ],
//      [
//        "dni" => "42562016",
//        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
//        "metric" => "Número",
//        "goal" => 3,
//        "isAscending" => 0,
//        "weight" => 25,
//        "categoria" => "Conductor I"
//      ],
//      [
//        "dni" => "42562016",
//        "objective" => "Cero multas por infringir las normas de tránsito en el desarrollo de sus funciones",
//        "metric" => "Número",
//        "goal" => 0,
//        "isAscending" => 0,
//        "weight" => 25,
//        "categoria" => "Conductor I"
//      ],
//      [
//        "dni" => "42562016",
//        "objective" => "Cero incidencias en la ruta asignada (robos, choques,etc)",
//        "metric" => "Número",
//        "goal" => 0,
//        "isAscending" => 0,
//        "weight" => 25,
//        "categoria" => "Conductor I"
//      ],
      [
        "dni" => "40158039",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => 100,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "42819192",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => 100,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "42602275",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Número",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "42602275",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "42602275",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "42602275",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Número",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "42140416",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => 100,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "40896485",
        "objective" => "Cumplimiento al 100% de la meta de venta establecida",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Jefe De Ventas Dp"
      ],
      [
        "dni" => "40896485",
        "objective" => "% de Efectividad de Cobranza",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Jefe De Ventas Dp"
      ],
      [
        "dni" => "40896485",
        "objective" => "Rentabilidad bruta mayor igual a 5.6%",
        "metric" => "Número",
        "goal" => 5.6,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Jefe De Ventas Dp"
      ],
      [
        "dni" => "40896485",
        "objective" => "Incremento de cartera",
        "metric" => "Número",
        "goal" => 4,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Jefe De Ventas Dp"
      ],
      [
        "dni" => "40896485",
        "objective" => "Mix de productos",
        "metric" => "Número",
        "goal" => 5,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Jefe De Ventas Dp"
      ],
      [
        "dni" => "42251736",
        "objective" => "Pagos ejecutados oportunamente",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 40,
        "categoria" => "Analista De Remuneraciones Y Compensaciones"
      ],
      [
        "dni" => "42251736",
        "objective" => "Presentación contable de planilla",
        "metric" => "Número",
        "goal" => 12,
        "isAscending" => 0,
        "weight" => 20,
        "categoria" => "Analista De Remuneraciones Y Compensaciones"
      ],
      [
        "dni" => "42251736",
        "objective" => "Pagos ejecutados correctamente",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 40,
        "categoria" => "Analista De Remuneraciones Y Compensaciones"
      ],
      [
        "dni" => "47678322",
        "objective" => "Meta de facturación mensual mayor o igual a 100%",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 100,
        "categoria" => "Asesor De Repuestos"
      ],
      [
        "dni" => "73092833",
        "objective" => "Tasa de reclamo menor o igual a 5% ",
        "metric" => "Número",
        "goal" => 5,
        "isAscending" => 0,
        "weight" => 100,
        "categoria" => "Gestor De Inmatriculacion"
      ],
      [
        "dni" => "40521977",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Número",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "40521977",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "40521977",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "40521977",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Número",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "44205462",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => 100,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "71900918",
        "objective" => "Eficiencia de OT > 70%",
        "metric" => "Número",
        "goal" => 70,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "71900918",
        "objective" => "Reingresos de vehículos",
        "metric" => "Número",
        "goal" => 2,
        "isAscending" => 0,
        "weight" => 50,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "46635205",
        "objective" => "Eficiencia de OT > 70%",
        "metric" => "Número",
        "goal" => 70,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Coordinador De Taller"
      ],
      [
        "dni" => "46635205",
        "objective" => "Reingresos de vehículos",
        "metric" => "Número",
        "goal" => 2,
        "isAscending" => 0,
        "weight" => 50,
        "categoria" => "Coordinador De Taller"
      ],
      [
        "dni" => "40524259",
        "objective" => "Notas de crèdito a proveedores ",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Jefe De Administracion"
      ],
      [
        "dni" => "40524259",
        "objective" => "Pago a proveedores",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Jefe De Administracion"
      ],
      [
        "dni" => "40524259",
        "objective" => "Gestión de compras ",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Jefe De Administracion"
      ],
      [
        "dni" => "40524259",
        "objective" => "Efectividad de liquidaciones=> % de reconocimeinto de la liquidacion / total de liquidaciones registradas",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Jefe De Administracion"
      ],
      [
        "dni" => "42401576",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => 100,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "46815084",
        "objective" => "Cumplimiento mensual de los límites de velocidad en ruta",
        "metric" => "Número",
        "goal" => 99,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "46815084",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "46815084",
        "objective" => "Cero multas por infringir las normas de tránsito en el desarrollo de sus funciones",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "46815084",
        "objective" => "Cero incidencias en la ruta asignada (robos, choques,etc)",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "71908990",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => 100,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "75848561",
        "objective" => "Optimizar los tiempos de reparacion en un 55%",
        "metric" => "Número",
        "goal" => 55,
        "isAscending" => 0,
        "weight" => 50,
        "categoria" => "Tecnico Electricista"
      ],
      [
        "dni" => "75848561",
        "objective" => "Mantenimentos correctivos de emergencia(Auxilios Mecánicos), no supere el 5% mensual de la flota",
        "metric" => "Número",
        "goal" => 5,
        "isAscending" => 0,
        "weight" => 50,
        "categoria" => "Tecnico Electricista"
      ],
      [
        "dni" => "45704839",
        "objective" => "Nivel de servicio",
        "metric" => "Número",
        "goal" => 95,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Gerente De Post Venta"
      ],
      [
        "dni" => "45704839",
        "objective" => "Cumplimiento del volumen de ventas de post venta",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Gerente De Post Venta"
      ],
      [
        "dni" => "45704839",
        "objective" => "Cumplimiento de la venta de accesorios",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Gerente De Post Venta"
      ],
      [
        "dni" => "45704839",
        "objective" => "Cumplimiento de rotacion de stock",
        "metric" => "Número",
        "goal" => 30,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Gerente De Post Venta"
      ],
      [
        "dni" => "72211890",
        "objective" => "Stock saludable de un mes por sede",
        "metric" => "Número",
        "goal" => 1,
        "isAscending" => 0,
        "weight" => 100,
        "categoria" => "Jefe De Almacen"
      ],
      [
        "dni" => "09994093",
        "objective" => "Notas de crèdito a proveedores ",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Jefe De Administracion"
      ],
      [
        "dni" => "09994093",
        "objective" => "Pago a proveedores",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Jefe De Administracion"
      ],
      [
        "dni" => "09994093",
        "objective" => "Gestión de compras ",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Jefe De Administracion"
      ],
      [
        "dni" => "09994093",
        "objective" => "Efectividad de liquidaciones=> % de reconocimeinto de la liquidacion / total de liquidaciones registradas",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Jefe De Administracion"
      ],
      [
        "dni" => "46906293",
        "objective" => "Seguimiento de pedidos de compra de vehículos, conciliando con el sistema de emisión de facturas",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Coordinador De Ventas"
      ],
      [
        "dni" => "46906293",
        "objective" => "Obtener un resultado mayor o igual a 80% en NPS.",
        "metric" => "Número",
        "goal" => 80,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Coordinador De Ventas"
      ],
      [
        "dni" => "46906293",
        "objective" => "Cumplimiento del 100% del envío de las solicitudes de nota de crédito (7 días hábiles)",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 34,
        "categoria" => "Coordinador De Ventas"
      ],
      [
        "dni" => "16662152",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Número",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16662152",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16662152",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16662152",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Número",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "72811912",
        "objective" => "Promedio de stock saludable de un mes en los cuatro almacenes",
        "metric" => "Número",
        "goal" => 1,
        "isAscending" => 0,
        "weight" => 100,
        "categoria" => "Coordinador De Post Venta - Almacen"
      ],
      [
        "dni" => "40654637",
        "objective" => "Número de unidades entregadas",
        "metric" => "Número",
        "goal" => 12,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "40654637",
        "objective" => "Colocación de créditos",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "40654637",
        "objective" => "Colocación de seguros",
        "metric" => "Número",
        "goal" => 1,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "40803935",
        "objective" => "Cumplimiento de presupuesto comercial",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "40803935",
        "objective" => "% de Efectividad de Cobranza",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "40803935",
        "objective" => "Mix de productos",
        "metric" => "Número",
        "goal" => 5,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "40803935",
        "objective" => "Cliente Nuevo al mes",
        "metric" => "Número",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "45838857",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Número",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "45838857",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "45838857",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "45838857",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Número",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "71600393",
        "objective" => "Stock saludable de un mes por sede",
        "metric" => "Número",
        "goal" => 1,
        "isAscending" => 0,
        "weight" => 100,
        "categoria" => "Asistente De Almacén Ap"
      ],
      [
        "dni" => "47189021",
        "objective" => "Número de unidades entregadas",
        "metric" => "Número",
        "goal" => 8,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "47189021",
        "objective" => "Colocación de créditos",
        "metric" => "Número",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "47189021",
        "objective" => "Colocación de seguros",
        "metric" => "Número",
        "goal" => 1,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "44868919",
        "objective" => "Inventarios cíclicos",
        "metric" => "Número",
        "goal" => 93,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Asistente De Logistica"
      ],
      [
        "dni" => "44868919",
        "objective" => "Control del costo de Suministros (ingresos y salidas)",
        "metric" => "Número",
        "goal" => 95,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Asistente De Logistica"
      ],
      [
        "dni" => "43945322",
        "objective" => "Número de unidades entregadas",
        "metric" => "Número",
        "goal" => 5,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "43945322",
        "objective" => "Colocación de créditos",
        "metric" => "Número",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "43945322",
        "objective" => "Colocación de seguros",
        "metric" => "Número",
        "goal" => 1,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "43579923",
        "objective" => "Nº de unidades vendidas en el mes en las 4 sedes",
        "metric" => "Número",
        "goal" => 215,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Gerente De Negocio Ap"
      ],
      [
        "dni" => "43579923",
        "objective" => "% de market share AP",
        "metric" => "Número",
        "goal" => 23.2,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Gerente De Negocio Ap"
      ],
      [
        "dni" => "43579923",
        "objective" => "Cumplimiento del volumen de ventas de post venta",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Gerente De Negocio Ap"
      ],
      [
        "dni" => "43579923",
        "objective" => "Meses de stock de repuestos",
        "metric" => "Número",
        "goal" => 30,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Gerente De Negocio Ap"
      ],
      [
        "dni" => "45488727",
        "objective" => "Promedio de cumplimiento de presupuesto mensual de AP, DP y TP",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Gerente De Gestión Humana"
      ],
      [
        "dni" => "45488727",
        "objective" => "Tiempo de reclutamiento",
        "metric" => "Número",
        "goal" => 80,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Gerente De Gestión Humana"
      ],
      [
        "dni" => "45488727",
        "objective" => "Asegurar pagos correctos",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 34,
        "categoria" => "Gerente De Gestión Humana"
      ],
      [
        "dni" => "02855107",
        "objective" => "Cero soles de multa por errores contables en la empresa a cargo",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 33,
        "categoria" => "Asistente De Contabilidad"
      ],
      [
        "dni" => "02855107",
        "objective" => "Cero errores en el registro de información contable de manera mensual",
        "metric" => "Número",
        "goal" => 2,
        "isAscending" => 0,
        "weight" => 33,
        "categoria" => "Asistente De Contabilidad"
      ],
      [
        "dni" => "02855107",
        "objective" => "Entrega de cierre contable mensual integral (en los primeros 12 días calendario)",
        "metric" => "Número",
        "goal" => 12,
        "isAscending" => 0,
        "weight" => 34,
        "categoria" => "Asistente De Contabilidad"
      ],
      [
        "dni" => "42082941",
        "objective" => "Número de unidades entregadas",
        "metric" => "Número",
        "goal" => 18,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Jefe De Ventas Ap"
      ],
      [
        "dni" => "42082941",
        "objective" => "Meta en seguros",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Jefe De Ventas Ap"
      ],
      [
        "dni" => "42082941",
        "objective" => "Meta en créditos",
        "metric" => "Número",
        "goal" => 6,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Jefe De Ventas Ap"
      ],
      [
        "dni" => "42082941",
        "objective" => "Alcance de Mix de Marca",
        "metric" => "Número",
        "goal" => 6,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Jefe De Ventas Ap"
      ],
      [
        "dni" => "72210478",
        "objective" => "Stock saludable de un mes por sede",
        "metric" => "Número",
        "goal" => 1,
        "isAscending" => 0,
        "weight" => 100,
        "categoria" => "Asistente De Almacén Ap"
      ],
      [
        "dni" => "42298543",
        "objective" => "Número de unidades entregadas",
        "metric" => "Número",
        "goal" => 12,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "42298543",
        "objective" => "Colocación de créditos",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "42298543",
        "objective" => "Colocación de seguros",
        "metric" => "Número",
        "goal" => 1,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "17861917",
        "objective" => "Número de unidades entregadas",
        "metric" => "Número",
        "goal" => 9,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "17861917",
        "objective" => "Colocación de créditos",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "17861917",
        "objective" => "Colocación de seguros",
        "metric" => "Número",
        "goal" => 1,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "44579660",
        "objective" => "Número de unidades entregadas",
        "metric" => "Número",
        "goal" => 6,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "44579660",
        "objective" => "Colocación de créditos",
        "metric" => "Número",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "44579660",
        "objective" => "Colocación de seguros",
        "metric" => "Número",
        "goal" => 1,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "71622182",
        "objective" => "Cumplimiento de la facturación de taller mas un incremento adicional del 2%",
        "metric" => "Número",
        "goal" => 102,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Jefe De Taller"
      ],
      [
        "dni" => "71622182",
        "objective" => "Cumplimiento de OT>100%",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Jefe De Taller"
      ],
      [
        "dni" => "71622182",
        "objective" => "Tasa de reclamos relacionados al servicio que brinda el jefe de taller",
        "metric" => "Número",
        "goal" => 1,
        "isAscending" => 0,
        "weight" => 34,
        "categoria" => "Jefe De Taller"
      ],
      [
        "dni" => "48493374",
        "objective" => "Programación de flota",
        "metric" => "Número",
        "goal" => 95,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Gestor Comercial"
      ],
      [
        "dni" => "48493374",
        "objective" => "Incremento en ventas",
        "metric" => "Número",
        "goal" => 5,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Gestor Comercial"
      ],
      [
        "dni" => "48493374",
        "objective" => "Rentabilidad",
        "metric" => "Número",
        "goal" => 15,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Gestor Comercial"
      ],
      [
        "dni" => "48493374",
        "objective" => "Captación y propuesta de nuevos clientes",
        "metric" => "Número",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Gestor Comercial"
      ],
      [
        "dni" => "46720670",
        "objective" => "Seguimiento, solicitud y cumplimiento de citas cerámicos",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Gestor Comercial"
      ],
      [
        "dni" => "46720670",
        "objective" => "Recuperación de documentos cerámicos ",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Gestor Comercial"
      ],
      [
        "dni" => "46720670",
        "objective" => "Recuperación y control de parihuelas cerámicos",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Gestor Comercial"
      ],
      [
        "dni" => "46720670",
        "objective" => "Control de parihuelas Celima",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Gestor Comercial"
      ],
      [
        "dni" => "46720670",
        "objective" => "Cumplimiento de facturación cerámicos",
        "metric" => "Número",
        "goal" => 95,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Gestor Comercial"
      ],
      [
        "dni" => "45801393",
        "objective" => "Número de unidades entregadas",
        "metric" => "Número",
        "goal" => 49,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Jefe De Ventas Ap"
      ],
      [
        "dni" => "45801393",
        "objective" => "Meta en seguros",
        "metric" => "Número",
        "goal" => 7,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Jefe De Ventas Ap"
      ],
      [
        "dni" => "45801393",
        "objective" => "Meta en créditos",
        "metric" => "Número",
        "goal" => 14,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Jefe De Ventas Ap"
      ],
      [
        "dni" => "45801393",
        "objective" => "Alcance de Mix de Marca",
        "metric" => "Número",
        "goal" => 5,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Jefe De Ventas Ap"
      ],
      [
        "dni" => "45746188",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => 100,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "72318704",
        "objective" => "Cero soles de multa por errores contables en la empresa a cargo",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 33,
        "categoria" => "Analista De Contabilidad TP"
      ],
      [
        "dni" => "72318704",
        "objective" => "Presentación de DDJJ mensuales a SUNAT ",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Analista De Contabilidad TP"
      ],
      [
        "dni" => "72318704",
        "objective" => "Entrega de cierres contables en tiempo de empresa a cargo",
        "metric" => "Número",
        "goal" => 10,
        "isAscending" => 0,
        "weight" => 34,
        "categoria" => "Analista De Contabilidad TP"
      ],
      [
        "dni" => "48509128",
        "objective" => "Meta de facturación mensual mayor o igual a 100%",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 100,
        "categoria" => "Asesor De Repuestos"
      ],
      [
        "dni" => "16475507",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Número",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16475507",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16475507",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16475507",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Número",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "76311394",
        "objective" => "cumplimiento de cronograma de proyectos",
        "metric" => "Número",
        "goal" => 95,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Analista En Proyectos De Gestion Humana"
      ],
      [
        "dni" => "76311394",
        "objective" => "CUMPLIMIENTO DE LOS PROCESOS DE INDUCCIÓN Y ONBOARDING",
        "metric" => "Número",
        "goal" => 95,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Analista En Proyectos De Gestion Humana"
      ],
      [
        "dni" => "76311394",
        "objective" => "Satisfacción de los procesos de onboarding",
        "metric" => "Número",
        "goal" => 3.5,
        "isAscending" => 1,
        "weight" => 34,
        "categoria" => "Analista En Proyectos De Gestion Humana"
      ],
      [
        "dni" => "46891277",
        "objective" => "Número de unidades entregadas",
        "metric" => "Número",
        "goal" => 6,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "46891277",
        "objective" => "Colocación de créditos",
        "metric" => "Número",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "46891277",
        "objective" => "Colocación de seguros",
        "metric" => "Número",
        "goal" => 1,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "41998623",
        "objective" => "Cumplimiento al 100% de la meta de venta establecida",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Jefe De Ventas Dp"
      ],
      [
        "dni" => "41998623",
        "objective" => "% de Efectividad de Cobranza",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Jefe De Ventas Dp"
      ],
      [
        "dni" => "41998623",
        "objective" => "Rentabilidad bruta mayor igual a 5.6%",
        "metric" => "Número",
        "goal" => 5.6,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Jefe De Ventas Dp"
      ],
      [
        "dni" => "41998623",
        "objective" => "Incremento de cartera",
        "metric" => "Número",
        "goal" => 6,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Jefe De Ventas Dp"
      ],
      [
        "dni" => "41998623",
        "objective" => "Mix de productos",
        "metric" => "Número",
        "goal" => 5,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Jefe De Ventas Dp"
      ],
      [
        "dni" => "44236861",
        "objective" => "Cumplimiento de presupuesto comercial",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "44236861",
        "objective" => "% de Efectividad de Cobranza",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "44236861",
        "objective" => "Mix de productos",
        "metric" => "Número",
        "goal" => 5,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "44236861",
        "objective" => "Cliente Nuevo al mes",
        "metric" => "Número",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "02851958",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => 100,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "44249939",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => 100,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "47057666",
        "objective" => "Los costos de Kilómetro de combustible no deben superar los 1.65/km recorrido",
        "metric" => "Número",
        "goal" => 1.65,
        "isAscending" => 0,
        "weight" => 33,
        "categoria" => "Asistente De Operaciones"
      ],
      [
        "dni" => "47057666",
        "objective" => "Rendimiento promedio no debe superar 8.6 km/gln",
        "metric" => "Número",
        "goal" => 8.6,
        "isAscending" => 0,
        "weight" => 33,
        "categoria" => "Asistente De Operaciones"
      ],
      [
        "dni" => "47057666",
        "objective" => "Accidente y/o Siniestros por responsabilidad el equipo de operaciones y mantenimiento",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 34,
        "categoria" => "Asistente De Operaciones"
      ],
      [
        "dni" => "43284061",
        "objective" => "Promedio de cumplimiento de presupuesto mensual de AP, DP y TP",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 100,
        "categoria" => "Jefe De Legal"
      ],
      [
        "dni" => "45801424",
        "objective" => "Cero soles de multa por errores contables en la empresa a cargo",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 33,
        "categoria" => "Analista De Contabilidad DP"
      ],
      [
        "dni" => "45801424",
        "objective" => "Presentación de DDJJ mensuales a SUNAT ",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Analista De Contabilidad DP"
      ],
      [
        "dni" => "45801424",
        "objective" => "Entrega de cierres contables (EE.RR) en tiempo de empresa a cargo (15 dias)",
        "metric" => "Número",
        "goal" => 15,
        "isAscending" => 0,
        "weight" => 34,
        "categoria" => "Analista De Contabilidad DP"
      ],
      [
        "dni" => "46343351",
        "objective" => "Incrementar el % de cumplimiento de la programaciòn (Servicios entregados a tiempo/total de servicios programados)",
        "metric" => "Número",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => 34,
        "categoria" => "Asistente De Seguimiento Y Monitoreo"
      ],
      [
        "dni" => "46343351",
        "objective" => "No tener robos por falta de seguimiento y monitoreo",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 33,
        "categoria" => "Asistente De Seguimiento Y Monitoreo"
      ],
      [
        "dni" => "46343351",
        "objective" => "Accidente y/o Siniestros por responsabilidad el equipo de operaciones y mantenimiento",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 33,
        "categoria" => "Asistente De Seguimiento Y Monitoreo"
      ],
      [
        "dni" => "73809915",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => 100,
        "categoria" => "Supervisor De Almacen"
      ],
      [
        "dni" => "19324383",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Número",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "19324383",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "19324383",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "19324383",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Número",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "48691128",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => 100,
        "categoria" => "Supervisor De Almacen"
      ],
      [
        "dni" => "48299990",
        "objective" => "Número de unidades entregadas",
        "metric" => "Número",
        "goal" => 9,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "48299990",
        "objective" => "Colocación de créditos",
        "metric" => "Número",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "48299990",
        "objective" => "Colocación de seguros",
        "metric" => "Número",
        "goal" => 1,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "44498939",
        "objective" => "Cumplimiento mensual de los límites de velocidad en ruta",
        "metric" => "Número",
        "goal" => 99,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "44498939",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "44498939",
        "objective" => "Cero multas por infringir las normas de tránsito en el desarrollo de sus funciones",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "44498939",
        "objective" => "Cero incidencias en la ruta asignada (robos, choques,etc)",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "32739469",
        "objective" => "Cumplimiento de la programacion de  capacitaciones a los agentes ",
        "metric" => "Número",
        "goal" => 99,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Supervisor De Seguridad Patrimonial"
      ],
      [
        "dni" => "32739469",
        "objective" => "Disminución de número de agentes que abandonen su labor antes de cumplimiento de contrato",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 33,
        "categoria" => "Supervisor De Seguridad Patrimonial"
      ],
      [
        "dni" => "32739469",
        "objective" => "Disminución de la tasa % de robos o pérdidas de activos de propiedad de la empresa",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 34,
        "categoria" => "Supervisor De Seguridad Patrimonial"
      ],
      [
        "dni" => "45620643",
        "objective" => "Cumplimiento de cronogramas de desarrollo y mantenimiento de software (Milla, Quiter, Dynamic )",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 30,
        "categoria" => "Jefe De TICs"
      ],
      [
        "dni" => "45620643",
        "objective" => "Efectividad en el tiempo de atención en el servicio",
        "metric" => "Número",
        "goal" => 30,
        "isAscending" => 0,
        "weight" => 30,
        "categoria" => "Jefe De TICs"
      ],
      [
        "dni" => "45620643",
        "objective" => "Calidad de servicio del área",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 30,
        "categoria" => "Jefe De TICs"
      ],
      [
        "dni" => "45620643",
        "objective" => "Promedio de cumplimiento de presupuesto mensual de AP, DP y TP",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 10,
        "categoria" => "Jefe De TICs"
      ],
      [
        "dni" => "42116951",
        "objective" => "Número de unidades entregadas",
        "metric" => "Número",
        "goal" => 45,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Jefe De Ventas Ap"
      ],
      [
        "dni" => "42116951",
        "objective" => "Meta en seguros",
        "metric" => "Número",
        "goal" => 4,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Jefe De Ventas Ap"
      ],
      [
        "dni" => "42116951",
        "objective" => "Meta en créditos",
        "metric" => "Número",
        "goal" => 11,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Jefe De Ventas Ap"
      ],
      [
        "dni" => "42116951",
        "objective" => "Alcance de Mix de Marca",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Jefe De Ventas Ap"
      ],
      [
        "dni" => "48493314",
        "objective" => "Cero soles de multa por errores contables en la empresa a cargo",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 20,
        "categoria" => "Asistente De Contabilidad"
      ],
      [
        "dni" => "48493314",
        "objective" => "Exactitud en registros contables",
        "metric" => "Numero",
        "goal" => 100,
        "isAscending" => 0,
        "weight" => 50,
        "categoria" => "Asistente De Contabilidad"
      ],
      [
        "dni" => "48493314",
        "objective" => "Entrega de cierres contables en tiempo de empresa a cargo",
        "metric" => "Número",
        "goal" => 95,
        "isAscending" => 1,
        "weight" => 30,
        "categoria" => "Asistente De Contabilidad"
      ],
      [
        "dni" => "16589869",
        "objective" => "Cumplimiento de presupuesto comercial",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "16589869",
        "objective" => "% de Efectividad de Cobranza",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "16589869",
        "objective" => "Mix de productos",
        "metric" => "Número",
        "goal" => 5,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "16589869",
        "objective" => "Cliente Nuevo al mes",
        "metric" => "Número",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "77499375",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => 100,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "76010852",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => 100,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "16734135",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Número",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16734135",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16734135",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16734135",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Número",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "40831827",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Número",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "40831827",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "40831827",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "40831827",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Número",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "42150787",
        "objective" => "Alcanzar numero mínimo de fletes al mes ",
        "metric" => "Número",
        "goal" => 353,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Gerente De Negocio Tp"
      ],
      [
        "dni" => "42150787",
        "objective" => "Rentabilizar el negocio=> margen bruto mensual ",
        "metric" => "Número",
        "goal" => 19,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Gerente De Negocio Tp"
      ],
      [
        "dni" => "72843284",
        "objective" => "Cuadres de caja oportunos",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Caja Dp"
      ],
      [
        "dni" => "72843284",
        "objective" => "Arqueo de caja, sin diferencias ",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Caja Dp"
      ],
      [
        "dni" => "46837187",
        "objective" => "Cumplimiento mensual de los límites de velocidad en ruta",
        "metric" => "Número",
        "goal" => 99,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "46837187",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "46837187",
        "objective" => "Cero multas por infringir las normas de tránsito en el desarrollo de sus funciones",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "46837187",
        "objective" => "Cero incidencias en la ruta asignada (robos, choques,etc)",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "71202755",
        "objective" => "Eficiencia de OT > 70%",
        "metric" => "Número",
        "goal" => 70,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "71202755",
        "objective" => "Reingresos de vehículos",
        "metric" => "Número",
        "goal" => 2,
        "isAscending" => 0,
        "weight" => 50,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "45106359",
        "objective" => "Reportes semanales de CXC",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Jefe De Contabilidad Y Caja"
      ],
      [
        "dni" => "45106359",
        "objective" => "Cumplimiento de programacion de visitas a sedes con la finalidad de supervisar, procedimientos y control de caja",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Jefe De Contabilidad Y Caja"
      ],
      [
        "dni" => "45106359",
        "objective" => "Reduccion de gastos financieros",
        "metric" => "Número",
        "goal" => 10,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Jefe De Contabilidad Y Caja"
      ],
      [
        "dni" => "45106359",
        "objective" => "Reduccion de indice de deudas a proveedores",
        "metric" => "Número",
        "goal" => 10,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Jefe De Contabilidad Y Caja"
      ],
      [
        "dni" => "00850163",
        "objective" => "Apoyo en otras actividades de operativas con las demas àreas",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Auxiliar De Operaciones Lima"
      ],
      [
        "dni" => "00850163",
        "objective" => "Incidentes con responsalidad del auxiliar de operaciones",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 50,
        "categoria" => "Auxiliar De Operaciones Lima"
      ],
      [
        "dni" => "70724393",
        "objective" => "Cumplir la meta de OT mensual",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Asesor De Servicios"
      ],
      [
        "dni" => "70724393",
        "objective" => "Cumplimiento mensual de venta de accesorios",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Asesor De Servicios"
      ],
      [
        "dni" => "70724393",
        "objective" => "Cumplimiento de NPS",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 34,
        "categoria" => "Asesor De Servicios"
      ],
      [
        "dni" => "41072838",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Número",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "41072838",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "41072838",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "41072838",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Número",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "48299503",
        "objective" => "Eficiencia de OT > 70%",
        "metric" => "Número",
        "goal" => 70,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "48299503",
        "objective" => "Reingresos de vehículos",
        "metric" => "Número",
        "goal" => 2,
        "isAscending" => 0,
        "weight" => 50,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "16426491",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => 100,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "17632430",
        "objective" => "Incrementar el % de cumplimiento de la programaciòn (Servicios entregados a tiempo/total de servicios programados)",
        "metric" => "Número",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Asistente De Seguimiento Y Monitoreo"
      ],
      [
        "dni" => "17632430",
        "objective" => "No tener robos por falta de seguimiento y monitoreo",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 33,
        "categoria" => "Asistente De Seguimiento Y Monitoreo"
      ],
      [
        "dni" => "17632430",
        "objective" => "Accidente y/o Siniestros por responsabilidad el equipo de operaciones y mantenimiento",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 34,
        "categoria" => "Asistente De Seguimiento Y Monitoreo"
      ],
      [
        "dni" => "75106508",
        "objective" => "Eficiencia de OT > 70%",
        "metric" => "Número",
        "goal" => 70,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "75106508",
        "objective" => "Reingresos de vehículos",
        "metric" => "Número",
        "goal" => 2,
        "isAscending" => 0,
        "weight" => 50,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "41544958",
        "objective" => "Número de unidades entregadas",
        "metric" => "Número",
        "goal" => 12,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "41544958",
        "objective" => "Colocación de créditos",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "41544958",
        "objective" => "Colocación de seguros",
        "metric" => "Número",
        "goal" => 1,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "74202439",
        "objective" => "Calidad de servicio - Mesa de ayuda (Milla / Dynamics)",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Analista En Proyecto TICs"
      ],
      [
        "dni" => "74202439",
        "objective" => "Promedio de efectividad en tiempo de atención - Soporte TIC's",
        "metric" => "Número",
        "goal" => 45,
        "isAscending" => 0,
        "weight" => 50,
        "categoria" => "Analista En Proyecto TICs"
      ],
      [
        "dni" => "72483421",
        "objective" => "Cumplir la meta de OT mensual",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 34,
        "categoria" => "Asesor De Servicios"
      ],
      [
        "dni" => "72483421",
        "objective" => "Cumplimiento de NPS",
        "metric" => "Número",
        "goal" => 80,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Asesor De Servicios"
      ],
      [
        "dni" => "72483421",
        "objective" => "Cumplimiento de meta mensual de ventas de accesorios",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Asesor De Servicios"
      ],
      [
        "dni" => "44577179",
        "objective" => "Número de unidades entregadas",
        "metric" => "Número",
        "goal" => 17,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Jefe De Ventas Ap"
      ],
      [
        "dni" => "44577179",
        "objective" => "Meta en seguros",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Jefe De Ventas Ap"
      ],
      [
        "dni" => "44577179",
        "objective" => "Meta en créditos",
        "metric" => "Número",
        "goal" => 6,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Jefe De Ventas Ap"
      ],
      [
        "dni" => "44577179",
        "objective" => "Alcance de Mix de Marca",
        "metric" => "Número",
        "goal" => 4,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Jefe De Ventas Ap"
      ],
      [
        "dni" => "44846745",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => 100,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "44517042",
        "objective" => "Número de unidades entregadas",
        "metric" => "Número",
        "goal" => 6,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "44517042",
        "objective" => "Colocación de créditos",
        "metric" => "Número",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "44517042",
        "objective" => "Colocación de seguros",
        "metric" => "Número",
        "goal" => 1,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "72722099",
        "objective" => "Cero soles de multa por errores contables en la empresa a cargo",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 33,
        "categoria" => "Asistente De Contabilidad"
      ],
      [
        "dni" => "72722099",
        "objective" => "Cero errores en el registro de información contable de manera mensual",
        "metric" => "Número",
        "goal" => 2,
        "isAscending" => 0,
        "weight" => 33,
        "categoria" => "Asistente De Contabilidad"
      ],
      [
        "dni" => "72722099",
        "objective" => "Entrega de cierre contable mensual integral (en los primeros 12 días calendario)",
        "metric" => "Número",
        "goal" => 12,
        "isAscending" => 0,
        "weight" => 34,
        "categoria" => "Asistente De Contabilidad"
      ],
      [
        "dni" => "46815196",
        "objective" => "Cumplir la meta de OT mensual",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 34,
        "categoria" => "Asesor De Servicios"
      ],
      [
        "dni" => "46815196",
        "objective" => "Cumplimiento de NPS",
        "metric" => "Número",
        "goal" => 80,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Asesor De Servicios"
      ],
      [
        "dni" => "46815196",
        "objective" => "Cumplimiento de meta mensual de venta de accesorios",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Asesor De Servicios"
      ],
      [
        "dni" => "43122872",
        "objective" => "Cumplimiento mensual de los límites de velocidad en ruta",
        "metric" => "Número",
        "goal" => 99,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor Ii"
      ],
      [
        "dni" => "43122872",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor Ii"
      ],
      [
        "dni" => "43122872",
        "objective" => "Cero multas por infringir las normas de tránsito en el desarrollo de sus funciones",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor Ii"
      ],
      [
        "dni" => "43122872",
        "objective" => "Cero incidencias en la ruta asignada (robos, choques,etc)",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor Ii"
      ],
      [
        "dni" => "45676375",
        "objective" => "Cumplimiento mensual de los límites de velocidad en ruta",
        "metric" => "Número",
        "goal" => 99,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "45676375",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "45676375",
        "objective" => "Cero multas por infringir las normas de tránsito en el desarrollo de sus funciones",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "45676375",
        "objective" => "Cero incidencias en la ruta asignada (robos, choques,etc)",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "74624087",
        "objective" => "Cero soles de multa por errores contables en la empresa a cargo",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 33,
        "categoria" => "Asistente De Contabilidad"
      ],
      [
        "dni" => "74624087",
        "objective" => "Cero errores en el registro de información contable de manera mensual",
        "metric" => "Número",
        "goal" => 2,
        "isAscending" => 0,
        "weight" => 33,
        "categoria" => "Asistente De Contabilidad"
      ],
      [
        "dni" => "74624087",
        "objective" => "Entrega de cierre contable mensual integral (en los primeros 12 días calendario)",
        "metric" => "Número",
        "goal" => 12,
        "isAscending" => 0,
        "weight" => 34,
        "categoria" => "Asistente De Contabilidad"
      ],
      [
        "dni" => "75662130",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => 100,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "17619908",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Número",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "17619908",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "17619908",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "17619908",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Número",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "46226629",
        "objective" => "Cero soles de multa por errores contables en AP",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 33,
        "categoria" => "Jefe De Contabilidad"
      ],
      [
        "dni" => "46226629",
        "objective" => "Presentación de DDJJ mensuales a SUNAT ",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Jefe De Contabilidad"
      ],
      [
        "dni" => "46226629",
        "objective" => "Entrega de cierres contables (EE.RR) en tiempo de empresa a cargo (15 dias)",
        "metric" => "Número",
        "goal" => 15,
        "isAscending" => 0,
        "weight" => 34,
        "categoria" => "Jefe De Contabilidad"
      ],
      [
        "dni" => "75618646",
        "objective" => "Número de unidades entregadas",
        "metric" => "Número",
        "goal" => 6,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "75618646",
        "objective" => "Colocación de créditos",
        "metric" => "Número",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "75618646",
        "objective" => "Colocación de seguros",
        "metric" => "Número",
        "goal" => 1,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "72650523",
        "objective" => "Cumplimiento de presupuesto comercial",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "72650523",
        "objective" => "% de Efectividad de Cobranza",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "72650523",
        "objective" => "Mix de productos",
        "metric" => "Número",
        "goal" => 5,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "72650523",
        "objective" => "Cliente Nuevo al mes",
        "metric" => "Número",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "73855265",
        "objective" => "Notas de crédito",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Asistente Administrativo DP"
      ],
      [
        "dni" => "73855265",
        "objective" => "Entrega de informe de compras",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Asistente Administrativo DP"
      ],
      [
        "dni" => "73855265",
        "objective" => "Aprobación correcta de descuentos",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 34,
        "categoria" => "Asistente Administrativo DP"
      ],
      [
        "dni" => "46012169",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => 100,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "41037767",
        "objective" => "Seguimiento de pedidos de compra de vehículos, conciliando con el sistema de emisión de facturas",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Coordinador De Ventas"
      ],
      [
        "dni" => "41037767",
        "objective" => "Obtener un resultado mayor o igual a 80% en NPS.",
        "metric" => "Número",
        "goal" => 80,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Coordinador De Ventas"
      ],
      [
        "dni" => "41037767",
        "objective" => "Cumplimiento del 100% del envío de las solicitudes de nota de crédito (7 días hábiles)",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 34,
        "categoria" => "Coordinador De Ventas"
      ],
      [
        "dni" => "78005001",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => 100,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "41430542",
        "objective" => "Seguimiento de pedidos de compra de vehículos, conciliando con el sistema de emisión de facturas",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Coordinador De Ventas"
      ],
      [
        "dni" => "41430542",
        "objective" => "Obtener un resultado mayor o igual a 80% en NPS.",
        "metric" => "Número",
        "goal" => 80,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Coordinador De Ventas"
      ],
      [
        "dni" => "41430542",
        "objective" => "Cumplimiento del 100% del envío de las solicitudes de nota de crédito (7 días hábiles)",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 34,
        "categoria" => "Coordinador De Ventas"
      ],
      [
        "dni" => "16530913",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Número",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16530913",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16530913",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16530913",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Número",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "73235748",
        "objective" => "Número de unidades entregadas",
        "metric" => "Número",
        "goal" => 13,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "73235748",
        "objective" => "Colocación de créditos",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "73235748",
        "objective" => "Colocación de seguros",
        "metric" => "Número",
        "goal" => 1,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "70478021",
        "objective" => "Eficiencia de OT > 70%",
        "metric" => "Número",
        "goal" => 70,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "70478021",
        "objective" => "Reingresos de vehículos",
        "metric" => "Número",
        "goal" => 2,
        "isAscending" => 0,
        "weight" => 50,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "41337449",
        "objective" => "Cumplimiento de presupuesto comercial",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "41337449",
        "objective" => "% de Efectividad de Cobranza",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "41337449",
        "objective" => "Mix de productos",
        "metric" => "Número",
        "goal" => 5,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "41337449",
        "objective" => "Cliente Nuevo al mes",
        "metric" => "Número",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "72700680",
        "objective" => "Cumplir la meta de OT mensual",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Asesor De Servicios"
      ],
      [
        "dni" => "72700680",
        "objective" => "Cumplimiento de NPS",
        "metric" => "Número",
        "goal" => 80,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Asesor De Servicios"
      ],
      [
        "dni" => "72700680",
        "objective" => "Cumplimiento de meta mensual de venta de accesorios",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 34,
        "categoria" => "Asesor De Servicios"
      ],
      [
        "dni" => "46292825",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => 100,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "05641617",
        "objective" => "Número de unidades entregadas",
        "metric" => "Número",
        "goal" => 7,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "05641617",
        "objective" => "Colocación de créditos",
        "metric" => "Número",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "05641617",
        "objective" => "Colocación de seguros",
        "metric" => "Número",
        "goal" => 1,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "46613975",
        "objective" => "Eficiencia de OT > 70%",
        "metric" => "Número",
        "goal" => 70,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "46613975",
        "objective" => "Reingresos de vehículos",
        "metric" => "Número",
        "goal" => 2,
        "isAscending" => 0,
        "weight" => 50,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "74916002",
        "objective" => "Optimizar los tiempos de reparacion en un 55%",
        "metric" => "Número",
        "goal" => 55,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Asistente Mecánico Tp"
      ],
      [
        "dni" => "74916002",
        "objective" => "Mantenimentos correctivos de emergencia(Auxilios Mecánicos), no supere el 5% mensual de la flota",
        "metric" => "Número",
        "goal" => 5,
        "isAscending" => 0,
        "weight" => 50,
        "categoria" => "Asistente Mecánico Tp"
      ],
      [
        "dni" => "77071508",
        "objective" => "Eficiencia de OT > 70%",
        "metric" => "Número",
        "goal" => 70,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "77071508",
        "objective" => "Reingresos de vehículos",
        "metric" => "Número",
        "goal" => 2,
        "isAscending" => 0,
        "weight" => 50,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "73736078",
        "objective" => "Controlar el costo operacional (Costo venta/kilometraje) que no supere 4.5 sol/km",
        "metric" => "Número",
        "goal" => 4.5,
        "isAscending" => 0,
        "weight" => 20,
        "categoria" => "Jefe De Operaciones Y Mantenimiento"
      ],
      [
        "dni" => "73736078",
        "objective" => "Controlar el costo kilómetro de combustible no supere los 2.0 sol/km",
        "metric" => "Número",
        "goal" => 2,
        "isAscending" => 0,
        "weight" => 20,
        "categoria" => "Jefe De Operaciones Y Mantenimiento"
      ],
      [
        "dni" => "73736078",
        "objective" => "Controlar los gastos reparables que no superen los 50,000 soles",
        "metric" => "Número",
        "goal" => 50000,
        "isAscending" => 0,
        "weight" => 20,
        "categoria" => "Jefe De Operaciones Y Mantenimiento"
      ],
      [
        "dni" => "73736078",
        "objective" => "Mantener la disponibilidad al 96% unidades completas(conductor y vehículo)",
        "metric" => "Número",
        "goal" => 96,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Jefe De Operaciones Y Mantenimiento"
      ],
      [
        "dni" => "73736078",
        "objective" => "Accidente y/o Siniestros por responsabilidad el equipo de operaciones y mantenimiento",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 20,
        "categoria" => "Jefe De Operaciones Y Mantenimiento"
      ],
      [
        "dni" => "45206383",
        "objective" => "Notas de crédito",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Asistente Administrativo Dp"
      ],
      [
        "dni" => "45206383",
        "objective" => "Entrega de informe de compras",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Asistente Administrativo Dp"
      ],
      [
        "dni" => "45206383",
        "objective" => "Aprobación correcta de descuentos",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 34,
        "categoria" => "Asistente Administrativo Dp"
      ],
      [
        "dni" => "40988232",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Número",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "40988232",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "40988232",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "40988232",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Número",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "44639590",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Número",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "44639590",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "44639590",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "44639590",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Número",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "43088093",
        "objective" => "Eficiencia de OT > 70%",
        "metric" => "Número",
        "goal" => 70,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "43088093",
        "objective" => "Reingresos de vehículos",
        "metric" => "Número",
        "goal" => 2,
        "isAscending" => 0,
        "weight" => 50,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "48359987",
        "objective" => "Vales pendientes rendidos al 100%",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Caja General"
      ],
      [
        "dni" => "48359987",
        "objective" => "Arqueo de caja, sin diferencias ",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Caja General"
      ],
      [
        "dni" => "48359987",
        "objective" => "Cuadres de caja oportunos",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Caja General"
      ],
      [
        "dni" => "48359987",
        "objective" => "Envio de reportes oportunos",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Caja General"
      ],
      [
        "dni" => "48359987",
        "objective" => "Informe de estado de las cuentas por cobrar",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Caja General"
      ],
      [
        "dni" => "19332661",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Número",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "19332661",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "19332661",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "19332661",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Número",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "45801388",
        "objective" => "Cero soles de multa por errores contables en la empresa a cargo",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 34,
        "categoria" => "Analista De Contabilidad AP"
      ],
      [
        "dni" => "45801388",
        "objective" => "Entrega de cierres contables en tiempo de empresa a cargo",
        "metric" => "Número",
        "goal" => 12,
        "isAscending" => 0,
        "weight" => 33,
        "categoria" => "Analista De Contabilidad AP"
      ],
      [
        "dni" => "45801388",
        "objective" => "Presentación de DDJJ mensuales a SUNAT ",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Analista De Contabilidad AP"
      ],
      [
        "dni" => "16545918",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Número",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16545918",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16545918",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16545918",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Número",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "43589428",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Número",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "43589428",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "43589428",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "43589428",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Número",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16795422",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Número",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16795422",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16795422",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16795422",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Número",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "17444802",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Número",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "17444802",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "17444802",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "17444802",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Número",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "40989878",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => 100,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "46479361",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Número",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "46479361",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "46479361",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "46479361",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Número",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "46132573",
        "objective" => "Cumplir la meta de OT mensual",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Asesor De Servicios"
      ],
      [
        "dni" => "46132573",
        "objective" => "Cumplimiento de NPS",
        "metric" => "Número",
        "goal" => 80,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Asesor De Servicios"
      ],
      [
        "dni" => "46132573",
        "objective" => "Cumplimiento de meta mensual de venta de accesorios",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 34,
        "categoria" => "Asesor De Servicios"
      ],
      [
        "dni" => "76571917",
        "objective" => "Eficiencia de OT > 70%",
        "metric" => "Número",
        "goal" => 70,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "76571917",
        "objective" => "Reingresos de vehículos",
        "metric" => "Número",
        "goal" => 2,
        "isAscending" => 0,
        "weight" => 50,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "71895009",
        "objective" => "Reingresos de vehículos",
        "metric" => "Número",
        "goal" => 2,
        "isAscending" => 0,
        "weight" => 50,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "71895009",
        "objective" => "Eficiencia de OT > 70%",
        "metric" => "Número",
        "goal" => 70,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "77127044",
        "objective" => "Número de unidades entregadas",
        "metric" => "Número",
        "goal" => 6,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "77127044",
        "objective" => "Colocación de créditos",
        "metric" => "Número",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "77127044",
        "objective" => "Colocación de seguros",
        "metric" => "Número",
        "goal" => 1,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "73777585",
        "objective" => "Cumplimiento de presupuesto comercial",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "73777585",
        "objective" => "% de Efectividad de Cobranza",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "73777585",
        "objective" => "Mix de productos",
        "metric" => "Número",
        "goal" => 5,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "73777585",
        "objective" => "Cliente Nuevo al mes",
        "metric" => "Número",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "42772411",
        "objective" => "Número de unidades entregadas",
        "metric" => "Número",
        "goal" => 20,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Jefe De Ventas Ap"
      ],
      [
        "dni" => "42772411",
        "objective" => "Meta en seguros",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Jefe De Ventas Ap"
      ],
      [
        "dni" => "42772411",
        "objective" => "Meta en créditos",
        "metric" => "Número",
        "goal" => 7,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Jefe De Ventas Ap"
      ],
      [
        "dni" => "42772411",
        "objective" => "Alcance de Mix de Marca",
        "metric" => "Número",
        "goal" => 4,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Jefe De Ventas Ap"
      ],
      [
        "dni" => "43324348",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => 100,
        "categoria" => "Supervisor De Almacen"
      ],
      [
        "dni" => "75235770",
        "objective" => "Promedio de cumplimiento del ICD al 90% a nivel de las 4 sedes",
        "metric" => "Número",
        "goal" => 90,
        "isAscending" => 1,
        "weight" => 100,
        "categoria" => "Coordinador De Post Venta"
      ],
      [
        "dni" => "71628227",
        "objective" => "Mantener la disponibilidad al 96% unidades completas(conductor y vehículo)",
        "metric" => "Número",
        "goal" => 96,
        "isAscending" => 1,
        "weight" => 18,
        "categoria" => "Supervisor De Operaciones Y Mantenimiento"
      ],
      [
        "dni" => "71628227",
        "objective" => "Mantener un 85 % de cumplimiento de la programaciòn (Servicios entregados a tiempo/total de servicios programados)",
        "metric" => "Número",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => 18,
        "categoria" => "Supervisor De Operaciones Y Mantenimiento"
      ],
      [
        "dni" => "71628227",
        "objective" => "Controlar los descansos no superar los 450 días pendientes al mes. ",
        "metric" => "Número",
        "goal" => 450,
        "isAscending" => 0,
        "weight" => 18,
        "categoria" => "Supervisor De Operaciones Y Mantenimiento"
      ],
      [
        "dni" => "71628227",
        "objective" => "Los costos de mantenimiento no deben superar los 0.30 soles/km recorrido",
        "metric" => "Número",
        "goal" => 0.3,
        "isAscending" => 0,
        "weight" => 18,
        "categoria" => "Supervisor De Operaciones Y Mantenimiento"
      ],
      [
        "dni" => "71628227",
        "objective" => "No tener multas por documentacion (guías, revisión técnica, bonificaciòn, soat,certificado MTC) ",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 14.000000000000002,
        "categoria" => "Supervisor De Operaciones Y Mantenimiento"
      ],
      [
        "dni" => "71628227",
        "objective" => "Accidente y/o Siniestros por responsabilidad el equipo de operaciones y mantenimiento",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 14.000000000000002,
        "categoria" => "Supervisor De Operaciones Y Mantenimiento"
      ],
      [
        "dni" => "47160168",
        "objective" => "Efectividad de proyección de facturación",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Asistente De Facturacion Y Cobranza"
      ],
      [
        "dni" => "47160168",
        "objective" => "Recuperación de documentos",
        "metric" => "Número",
        "goal" => 90,
        "isAscending" => 1,
        "weight" => 34,
        "categoria" => "Asistente De Facturacion Y Cobranza"
      ],
      [
        "dni" => "47160168",
        "objective" => "Efectividad proyección de cobranza",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Asistente De Facturacion Y Cobranza"
      ],
      [
        "dni" => "70046881",
        "objective" => "Cumplimiento de la facturación de taller mas un incremento adicional del 2%",
        "metric" => "Número",
        "goal" => 102,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Jefe De Taller"
      ],
      [
        "dni" => "70046881",
        "objective" => "Cumplimiento de OT>100%",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Jefe De Taller"
      ],
      [
        "dni" => "70046881",
        "objective" => "Tasa de reclamos relacionados al servicio que brinda el jefe de taller",
        "metric" => "Número",
        "goal" => 1,
        "isAscending" => 0,
        "weight" => 34,
        "categoria" => "Jefe De Taller"
      ],
      [
        "dni" => "46688489",
        "objective" => "Arqueo de caja, sin diferencias ",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Caja Ap"
      ],
      [
        "dni" => "46688489",
        "objective" => "Cuadres de caja oportunos",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Caja Ap"
      ],
      [
        "dni" => "46688489",
        "objective" => "Envío de reportes oportunos.",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Caja Ap"
      ],
      [
        "dni" => "46688489",
        "objective" => "Informe de estado de las CXC.",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Caja Ap"
      ],
      [
        "dni" => "43315473",
        "objective" => "Cumplimiento de presupuesto comercial",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "43315473",
        "objective" => "% de Efectividad de Cobranza",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "43315473",
        "objective" => "Mix de productos",
        "metric" => "Número",
        "goal" => 5,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "43315473",
        "objective" => "Cliente Nuevo al mes",
        "metric" => "Número",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "72865261",
        "objective" => "Cumplir la meta de OT mensual",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Asesor De Servicios"
      ],
      [
        "dni" => "72865261",
        "objective" => "Cumplimiento de NPS",
        "metric" => "Número",
        "goal" => 80,
        "isAscending" => 1,
        "weight" => 34,
        "categoria" => "Asesor De Servicios"
      ],
      [
        "dni" => "72865261",
        "objective" => "Cumplimiento de meta mensual de ventas de accesorios",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Asesor De Servicios"
      ],
      [
        "dni" => "46304286",
        "objective" => "Notas de crèdito a proveedores ",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Jefe De Administracion"
      ],
      [
        "dni" => "46304286",
        "objective" => "Pago a proveedores",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Jefe De Administracion"
      ],
      [
        "dni" => "46304286",
        "objective" => "Gestión de compras ",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Jefe De Administracion"
      ],
      [
        "dni" => "46304286",
        "objective" => "Efectividad de liquidaciones=> % de reconocimeinto de la liquidacion / total de liquidaciones registradas",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Jefe De Administracion"
      ],
      [
        "dni" => "19037052",
        "objective" => "Cumplimiento de presupuesto comercial",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "19037052",
        "objective" => "% de Efectividad de Cobranza",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "19037052",
        "objective" => "Mix de productos",
        "metric" => "Número",
        "goal" => 5,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "19037052",
        "objective" => "Cliente Nuevo al mes",
        "metric" => "Número",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "71960403",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => 100,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "42310255",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Número",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "42310255",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "42310255",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "42310255",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Número",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "46749588",
        "objective" => "Visitas de campo",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 34,
        "categoria" => "Ejecutivo De Trade Marketing"
      ],
      [
        "dni" => "46749588",
        "objective" => "Campañas de Mkt",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Ejecutivo De Trade Marketing"
      ],
      [
        "dni" => "46749588",
        "objective" => "Penetración de mercado",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Ejecutivo De Trade Marketing"
      ],
      [
        "dni" => "40601600",
        "objective" => "Notas de crédito",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Asistente Administrativo Dp"
      ],
      [
        "dni" => "40601600",
        "objective" => "Entrega de informe de compras",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Asistente Administrativo Dp"
      ],
      [
        "dni" => "40601600",
        "objective" => "Aprobación correcta de descuentos",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 34,
        "categoria" => "Asistente Administrativo Dp"
      ],
      [
        "dni" => "75459353",
        "objective" => "Optimizar los tiempos de reparacion en un 55%",
        "metric" => "Número",
        "goal" => 55,
        "isAscending" => 0,
        "weight" => 50,
        "categoria" => "Maestro Mecanico"
      ],
      [
        "dni" => "75459353",
        "objective" => "Mantenimentos correctivos de emergencia(Auxilios Mecánicos), no supere el 5% mensual de la flota",
        "metric" => "Número",
        "goal" => 5,
        "isAscending" => 0,
        "weight" => 50,
        "categoria" => "Maestro Mecanico"
      ],
      [
        "dni" => "76390749",
        "objective" => "Cero soles de multa por errores contables en la empresa a cargo",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 33,
        "categoria" => "Asistente De Contabilidad Dp"
      ],
      [
        "dni" => "76390749",
        "objective" => "Cero errores en el registro de información contable de manera mensual",
        "metric" => "Número",
        "goal" => 2,
        "isAscending" => 0,
        "weight" => 33,
        "categoria" => "Asistente De Contabilidad Dp"
      ],
      [
        "dni" => "76390749",
        "objective" => "Entrega de cierre contable mensual integral (en los primeros 12 días calendario)",
        "metric" => "Número",
        "goal" => 13,
        "isAscending" => 0,
        "weight" => 34,
        "categoria" => "Asistente De Contabilidad Dp"
      ],
      [
        "dni" => "16754597",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => 100,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "44723898",
        "objective" => "Cumplimiento mensual de los límites de velocidad en ruta",
        "metric" => "Número",
        "goal" => 99,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "44723898",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "44723898",
        "objective" => "Cero multas por infringir las normas de tránsito en el desarrollo de sus funciones",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "44723898",
        "objective" => "Cero incidencias en la ruta asignada (robos, choques,etc)",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "40971761",
        "objective" => "Cumplir con la implementaciòn de las unidades y su debido registro al 100%, según condicion del cliente (indicador rìgido)",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Asistente De Operaciones"
      ],
      [
        "dni" => "40971761",
        "objective" => "Cumplir con el registro al 100% de robos de implementos, mercaderìa e incidencias.",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Asistente De Operaciones"
      ],
      [
        "dni" => "40971761",
        "objective" => "Realizar inventario mensuales del 80% de toda la flota (como mìnimo) con la finalidad de verificar las condiciones de los implementos",
        "metric" => "Número",
        "goal" => 80,
        "isAscending" => 1,
        "weight" => 20,
        "categoria" => "Asistente De Operaciones"
      ],
      [
        "dni" => "40971761",
        "objective" => "Cero multas por documentacion vencida, mantener actualizada de todas las unidades.(revisiòn tècnica, bonificaciòn, soat,certificado MTC)",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 20,
        "categoria" => "Asistente De Operaciones"
      ],
      [
        "dni" => "40971761",
        "objective" => "Accidente y/o Siniestros por responsabilidad el equipo de operaciones y mantenimiento",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 20,
        "categoria" => "Asistente De Operaciones"
      ],
      [
        "dni" => "45938688",
        "objective" => "Arqueo de caja, sin diferencias ",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Caja Ap"
      ],
      [
        "dni" => "45938688",
        "objective" => "Cuadres de caja oportunos",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Caja Ap"
      ],
      [
        "dni" => "45938688",
        "objective" => "Envío de reportes oportunos.",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Caja Ap"
      ],
      [
        "dni" => "45938688",
        "objective" => "Informe de estado de las CXC.",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Caja Ap"
      ],
      [
        "dni" => "16454626",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Número",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16454626",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16454626",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16454626",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Número",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "73271353",
        "objective" => "Tasa de reclamo menor o igual a 5% ",
        "metric" => "Número",
        "goal" => 5,
        "isAscending" => 0,
        "weight" => 100,
        "categoria" => "Gestor De Inmatriculacion"
      ],
      [
        "dni" => "70309494",
        "objective" => "Cumplimiento mensual de los límites de velocidad en ruta",
        "metric" => "Número",
        "goal" => 99,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "70309494",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "70309494",
        "objective" => "Cero multas por infringir las normas de tránsito en el desarrollo de sus funciones",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "70309494",
        "objective" => "Cero incidencias en la ruta asignada (robos, choques,etc)",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "16526312",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Número",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16526312",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16526312",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16526312",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Número",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "73226257",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => 100,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "76692661",
        "objective" => "Cumplimiento mensual de los límites de velocidad en ruta",
        "metric" => "Número",
        "goal" => 99,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor Ii"
      ],
      [
        "dni" => "76692661",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor Ii"
      ],
      [
        "dni" => "76692661",
        "objective" => "Cero multas por infringir las normas de tránsito en el desarrollo de sus funciones",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor Ii"
      ],
      [
        "dni" => "76692661",
        "objective" => "Cero incidencias en la ruta asignada (robos, choques,etc)",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor Ii"
      ],
      [
        "dni" => "75560129",
        "objective" => "Generación de guías",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 34,
        "categoria" => "Gestor Comercial"
      ],
      [
        "dni" => "75560129",
        "objective" => "Actualización del sistema",
        "metric" => "Número",
        "goal" => 95,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Gestor Comercial"
      ],
      [
        "dni" => "75560129",
        "objective" => "Actualización de pesos en sistema",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Gestor Comercial"
      ],
      [
        "dni" => "73629364",
        "objective" => "Cuadres de caja oportunos",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Caja Dp"
      ],
      [
        "dni" => "73629364",
        "objective" => "Arqueo de caja, sin diferencias ",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Caja Dp"
      ],
      [
        "dni" => "42430798",
        "objective" => "Cumplimiento de la facturación de taller mas un incremento adicional del 2%",
        "metric" => "Número",
        "goal" => 102,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Jefe De Taller"
      ],
      [
        "dni" => "42430798",
        "objective" => "Cumplimiento de OT>100%",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Jefe De Taller"
      ],
      [
        "dni" => "42430798",
        "objective" => "Tasa de reclamos relacionados al servicio que brinda el jefe de taller",
        "metric" => "Número",
        "goal" => 1,
        "isAscending" => 0,
        "weight" => 34,
        "categoria" => "Jefe De Taller"
      ],
      [
        "dni" => "43210143",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Número",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "43210143",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "43210143",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "43210143",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Número",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "19324054",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Número",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "19324054",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "19324054",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "19324054",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Número",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "75497507",
        "objective" => "Propuestas de conceptos visuales campañas de marketing (4 unidades de negocio)",
        "metric" => "Número",
        "goal" => 4,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Diseñador Grafico"
      ],
      [
        "dni" => "75497507",
        "objective" => "Desarrollo de gráficas multiformato",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Diseñador Grafico"
      ],
      [
        "dni" => "75497507",
        "objective" => "Entrega de artes para campañas de marketing",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 34,
        "categoria" => "Diseñador Grafico"
      ],
      [
        "dni" => "41093744",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Número",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "41093744",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "41093744",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "41093744",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Número",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "43753580",
        "objective" => "Cumplimiento de la facturación de taller mas un incremento adicional del 2%",
        "metric" => "Número",
        "goal" => 102,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Jefe De Taller"
      ],
      [
        "dni" => "43753580",
        "objective" => "Cumplimiento de OT>100%",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Jefe De Taller"
      ],
      [
        "dni" => "43753580",
        "objective" => "Tasa de reclamos relacionados al servicio que brinda el jefe de taller",
        "metric" => "Número",
        "goal" => 1,
        "isAscending" => 0,
        "weight" => 34,
        "categoria" => "Jefe De Taller"
      ],
      [
        "dni" => "72080239",
        "objective" => "Stock saludable de un mes por sede",
        "metric" => "Número",
        "goal" => 1,
        "isAscending" => 0,
        "weight" => 100,
        "categoria" => "Asistente De Almacén Ap"
      ],
      [
        "dni" => "44588021",
        "objective" => "Optimizar los tiempos de reparacion en un 55%",
        "metric" => "Número",
        "goal" => 55,
        "isAscending" => 0,
        "weight" => 50,
        "categoria" => "Tecnico Soldador"
      ],
      [
        "dni" => "44588021",
        "objective" => "Mantenimentos correctivos de emergencia(Auxilios Mecánicos), no supere el 5% mensual de la flota",
        "metric" => "Número",
        "goal" => 5,
        "isAscending" => 0,
        "weight" => 50,
        "categoria" => "Tecnico Soldador"
      ],
      [
        "dni" => "48080535",
        "objective" => "Notas de crèdito a proveedores ",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Asistente Administrativo Dp"
      ],
      [
        "dni" => "48080535",
        "objective" => "Pago a proveedores",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Asistente Administrativo Dp"
      ],
      [
        "dni" => "48080535",
        "objective" => "Gestión de compras ",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Asistente Administrativo Dp"
      ],
      [
        "dni" => "48080535",
        "objective" => "Efectividad de liquidaciones=> % de reconocimeinto de la liquidacion / total de liquidaciones registradas",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Asistente Administrativo Dp"
      ],
      [
        "dni" => "42838742",
        "objective" => "Cumplimiento de presupuesto comercial",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "42838742",
        "objective" => "% de Efectividad de Cobranza",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "42838742",
        "objective" => "Mix de productos",
        "metric" => "Número",
        "goal" => 5,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "42838742",
        "objective" => "Cliente Nuevo al mes",
        "metric" => "Número",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "16657698",
        "objective" => "Cumplimiento mensual de los límites de velocidad en ruta",
        "metric" => "Número",
        "goal" => 99,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "16657698",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "16657698",
        "objective" => "Cero multas por infringir las normas de tránsito en el desarrollo de sus funciones",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "16657698",
        "objective" => "Cero incidencias en la ruta asignada (robos, choques,etc)",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "45509583",
        "objective" => "Cumplimiento de presupuesto comercial",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "45509583",
        "objective" => "% de Efectividad de Cobranza",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "45509583",
        "objective" => "Mix de productos",
        "metric" => "Número",
        "goal" => 5,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "45509583",
        "objective" => "Cliente Nuevo al mes",
        "metric" => "Número",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "46792789",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => 100,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "73340961",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => 100,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "46002145",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => 100,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "18217261",
        "objective" => "Número de unidades entregadas",
        "metric" => "Número",
        "goal" => 7,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "18217261",
        "objective" => "Colocación de créditos",
        "metric" => "Número",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "18217261",
        "objective" => "Colocación de seguros",
        "metric" => "Número",
        "goal" => 1,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "48502011",
        "objective" => "Eficiencia de OT > 70%",
        "metric" => "Número",
        "goal" => 70,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "48502011",
        "objective" => "Reingresos de vehículos",
        "metric" => "Número",
        "goal" => 2,
        "isAscending" => 0,
        "weight" => 50,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "44198340",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Número",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "44198340",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "44198340",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "44198340",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Número",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "73365479",
        "objective" => "Número de unidades entregadas",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "73365479",
        "objective" => "Colocación de créditos",
        "metric" => "Número",
        "goal" => 1,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "73657400",
        "objective" => "Arqueo de caja, sin diferencias ",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Caja Ap"
      ],
      [
        "dni" => "73657400",
        "objective" => "Cuadres de caja oportunos",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Caja Ap"
      ],
      [
        "dni" => "73657400",
        "objective" => "Envío de reportes oportunos.",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Caja Ap"
      ],
      [
        "dni" => "73657400",
        "objective" => "Informe de estado de las CXC.",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Caja Ap"
      ],
      [
        "dni" => "48178171",
        "objective" => "Reingresos de vehículos",
        "metric" => "Número",
        "goal" => 2,
        "isAscending" => 0,
        "weight" => 50,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "48178171",
        "objective" => "Eficiencia de OT > 70%",
        "metric" => "Número",
        "goal" => 70,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "42658440",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => 100,
        "categoria" => "Asistente De Reparto"
      ],
//      [
//        "dni" => "70614230",
//        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
//        "metric" => "Número",
//        "goal" => 3,
//        "isAscending" => 0,
//        "weight" => 100,
//        "categoria" => "Asistente De Reparto"
//      ],
      [
        "dni" => "70984021",
        "objective" => "Cuadres de caja oportunos",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Caja Dp"
      ],
      [
        "dni" => "70984021",
        "objective" => "Arqueo de caja, sin diferencias ",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Caja Dp"
      ],
      [
        "dni" => "45354782",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Número",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "45354782",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "45354782",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "45354782",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Número",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "75943283",
        "objective" => "Procesos dentro del plazo",
        "metric" => "Número",
        "goal" => 80,
        "isAscending" => 1,
        "weight" => 34,
        "categoria" => "Asistente de Atracción De Talento"
      ],
      [
        "dni" => "75943283",
        "objective" => "Calidad de reclutamiento",
        "metric" => "Número",
        "goal" => 95,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Asistente de Atracción De Talento"
      ],
      [
        "dni" => "75943283",
        "objective" => "CV recibidos marca empleadora",
        "metric" => "Número",
        "goal" => 50,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Asistente de Atracción De Talento"
      ],
      [
        "dni" => "45464734",
        "objective" => "Número de unidades entregadas",
        "metric" => "Número",
        "goal" => 5,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "45464734",
        "objective" => "Colocación de créditos",
        "metric" => "Número",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "45464734",
        "objective" => "Colocación de seguros",
        "metric" => "Número",
        "goal" => 1,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "72757329",
        "objective" => "Errores en cálculo de planilla GP",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 34,
        "categoria" => "Asistente de Remuneraciones y Compensaciones"
      ],
      [
        "dni" => "72757329",
        "objective" => "Errores en cálculo de planilla TP",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 34,
        "categoria" => "Asistente de Remuneraciones y Compensaciones"
      ],
      [
        "dni" => "72757329",
        "objective" => "Entrega de anexos de cuentas para planilla",
        "metric" => "Número",
        "goal" => 10,
        "isAscending" => 0,
        "weight" => 32,
        "categoria" => "Asistente de Remuneraciones y Compensaciones"
      ],
      [
        "dni" => "71875278",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Número",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "71875278",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "71875278",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "71875278",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Número",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "75886284",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => 100,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "75089704",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => 100,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "73078735",
        "objective" => "Calidad de contenido (crecimiento de 3% respecto al mes anterior)",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Asistente De Marketing"
      ],
      [
        "dni" => "73078735",
        "objective" => "Promedio de retención de visualización de videos y reels",
        "metric" => "Número",
        "goal" => 80,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Asistente De Marketing"
      ],
      [
        "dni" => "73078735",
        "objective" => "Cumplimiento de entrega de piezas",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Asistente De Marketing"
      ],
      [
        "dni" => "73078735",
        "objective" => "Visitas mensuales a sedes",
        "metric" => "Número",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Asistente De Marketing"
      ],
      [
        "dni" => "46337540",
        "objective" => "Cumplimiento mensual de los límites de velocidad en ruta",
        "metric" => "Número",
        "goal" => 99,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor II"
      ],
      [
        "dni" => "46337540",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor II"
      ],
      [
        "dni" => "46337540",
        "objective" => "Cero multas por infringir las normas de tránsito en el desarrollo de sus funciones",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor II"
      ],
      [
        "dni" => "46337540",
        "objective" => "Cero incidencias en la ruta asignada (robos, choques,etc)",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor II"
      ],
      [
        "dni" => "72636980",
        "objective" => "Cuadres de caja oportunos",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Caja DP"
      ],
      [
        "dni" => "72636980",
        "objective" => "Arqueo de caja, sin diferencias ",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Caja DP"
      ],
      [
        "dni" => "46555930",
        "objective" => "Apoyo en otras actividades de operativas con las demas àreas",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Auxiliar De Operaciones - Chiclayo"
      ],
      [
        "dni" => "46555930",
        "objective" => "Incidentes con responsalidad del auxiliar de operaciones",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 50,
        "categoria" => "Auxiliar De Operaciones - Chiclayo"
      ],
      [
        "dni" => "62046276",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => 100,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "40709560",
        "objective" => "Cumplimiento mensual de los límites de velocidad en ruta",
        "metric" => "Número",
        "goal" => 99,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "40709560",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "40709560",
        "objective" => "Cero multas por infringir las normas de tránsito en el desarrollo de sus funciones",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "40709560",
        "objective" => "Cero incidencias en la ruta asignada (robos, choques,etc)",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "75480931",
        "objective" => "Liquidaciones pendientes rendidos al 100% ",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Caja Tp"
      ],
      [
        "dni" => "75480931",
        "objective" => "Arqueo de caja, sin diferencias ",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Caja Tp"
      ],
      [
        "dni" => "75480931",
        "objective" => "Cuadres de caja oportunos",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Caja Tp"
      ],
      [
        "dni" => "75480931",
        "objective" => "Informe de estado de las CXC.",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Caja Tp"
      ],
      [
        "dni" => "75770012",
        "objective" => "Liquidaciones pendientes rendidos al 100% ",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Caja Tp"
      ],
      [
        "dni" => "75770012",
        "objective" => "Arqueo de caja, sin diferencias ",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Caja Tp"
      ],
      [
        "dni" => "75770012",
        "objective" => "Cuadres de caja oportunos",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Caja Tp"
      ],
      [
        "dni" => "75770012",
        "objective" => "Informe de estado de las CXC.",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Caja Tp"
      ],
      [
        "dni" => "45853764",
        "objective" => "Cuadres de caja oportunos",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Caja DP"
      ],
      [
        "dni" => "45853764",
        "objective" => "Arqueo de caja, sin diferencias ",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Caja DP"
      ],
      [
        "dni" => "71606304",
        "objective" => "Reingresos de vehículos",
        "metric" => "Número",
        "goal" => 2,
        "isAscending" => 0,
        "weight" => 50,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "71606304",
        "objective" => "Eficiencia de OT > 70%",
        "metric" => "Número",
        "goal" => 70,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "75626493",
        "objective" => "Optimizar los tiempos de reparacion en un 55%",
        "metric" => "Número",
        "goal" => 55,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Asistente Mecánico Tp"
      ],
      [
        "dni" => "75626493",
        "objective" => "Mantenimentos correctivos de emergencia(Auxilios Mecánicos), no supere el 5% mensual de la flota",
        "metric" => "Número",
        "goal" => 5,
        "isAscending" => 0,
        "weight" => 50,
        "categoria" => "Asistente Mecánico Tp"
      ],
      [
        "dni" => "45795895",
        "objective" => "Número de unidades entregadas",
        "metric" => "Número",
        "goal" => 7,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "45795895",
        "objective" => "Colocación de créditos",
        "metric" => "Número",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "45795895",
        "objective" => "Colocación de seguros",
        "metric" => "Número",
        "goal" => 1,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "71702184",
        "objective" => "Los costos de  Kilómetro de Neumáticos no deben superar los 0.16 sol/km recorrido",
        "metric" => "Número",
        "goal" => 0.16,
        "isAscending" => 0,
        "weight" => 16,
        "categoria" => "Coordinador De Mantenimiento"
      ],
      [
        "dni" => "71702184",
        "objective" => "Rendimiento de Neumatico debe ser como mínimo 11,000 Km/mm",
        "metric" => "Número",
        "goal" => 11000,
        "isAscending" => 1,
        "weight" => 16,
        "categoria" => "Coordinador De Mantenimiento"
      ],
      [
        "dni" => "71702184",
        "objective" => "Mantenimentos correctivos de emergencia(Auxilios Mecánicos), no supere el 5% mensual de la flota",
        "metric" => "Número",
        "goal" => 5,
        "isAscending" => 0,
        "weight" => 16,
        "categoria" => "Coordinador De Mantenimiento"
      ],
      [
        "dni" => "71702184",
        "objective" => "Cumplir al menos con el 96% de la programación de mantenimiento preventivo",
        "metric" => "Número",
        "goal" => 96,
        "isAscending" => 1,
        "weight" => 16,
        "categoria" => "Coordinador De Mantenimiento"
      ],
      [
        "dni" => "71702184",
        "objective" => "Números de escaneos mensuales mínimo 105",
        "metric" => "Número",
        "goal" => 105,
        "isAscending" => 1,
        "weight" => 18,
        "categoria" => "Coordinador De Mantenimiento"
      ],
      [
        "dni" => "71702184",
        "objective" => "Accidente y/o Siniestros por responsabilidad el equipo de operaciones y mantenimiento",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 18,
        "categoria" => "Coordinador De Mantenimiento"
      ],
      [
        "dni" => "43488881",
        "objective" => "Número de unidades entregadas",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "43488881",
        "objective" => "Colocación de créditos",
        "metric" => "Número",
        "goal" => 1,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "41941867",
        "objective" => "Cumplimiento de presupuesto comercial",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "41941867",
        "objective" => "% de Efectividad de Cobranza",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "41941867",
        "objective" => "Mix de productos",
        "metric" => "Número",
        "goal" => 5,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "41941867",
        "objective" => "Cliente Nuevo al mes",
        "metric" => "Número",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "42795204",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Número",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "42795204",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "42795204",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "42795204",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Número",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "007633555",
        "objective" => "Cumplimiento mensual de los límites de velocidad en ruta",
        "metric" => "Número",
        "goal" => 99,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "007633555",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "007633555",
        "objective" => "Cero multas por infringir las normas de tránsito en el desarrollo de sus funciones",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "007633555",
        "objective" => "Cero incidencias en la ruta asignada (robos, choques,etc)",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "77918582",
        "objective" => "Entrega de cierres contables en tiempo de empresa a cargo",
        "metric" => "Número",
        "goal" => 7,
        "isAscending" => 0,
        "weight" => 50,
        "categoria" => "Asistente de Contabilidad Volante Ap"
      ],
      [
        "dni" => "77918582",
        "objective" => "Cero soles de multa por errores contables en la empresa a cargo",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 50,
        "categoria" => "Asistente de Contabilidad Volante Ap"
      ],
      [
        "dni" => "74873133",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => 100,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "43084165",
        "objective" => "Cumplimiento de presupuesto comercial",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "43084165",
        "objective" => "% de Efectividad de Cobranza",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "43084165",
        "objective" => "Mix de productos",
        "metric" => "Número",
        "goal" => 5,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "43084165",
        "objective" => "Cliente Nuevo al mes",
        "metric" => "Número",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "41903472",
        "objective" => "Número de unidades entregadas",
        "metric" => "Número",
        "goal" => 6,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "41903472",
        "objective" => "Colocación de créditos",
        "metric" => "Número",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "41903472",
        "objective" => "Colocación de seguros",
        "metric" => "Número",
        "goal" => 1,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "71248855",
        "objective" => "Meta de facturación mensual mayor o igual a 100%",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 100,
        "categoria" => "Asesor De Repuestos"
      ],
      [
        "dni" => "42085586",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Número",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "42085586",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "42085586",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "42085586",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Número",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16736017",
        "objective" => "Número de unidades entregadas",
        "metric" => "Número",
        "goal" => 9,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "16736017",
        "objective" => "Colocación de créditos",
        "metric" => "Número",
        "goal" => 2,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "16736017",
        "objective" => "Colocación de seguros",
        "metric" => "Número",
        "goal" => 1,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "42040791",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => 100,
        "categoria" => "Supervisor De Almacen"
      ],
      [
        "dni" => "74531701",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => 100,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "40734495",
        "objective" => "Número de unidades ligeras entregadas",
        "metric" => "Número",
        "goal" => 10,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Jefe de Ventas - Camiones"
      ],
      [
        "dni" => "40734495",
        "objective" => "Número de unidades pesadas entregadas",
        "metric" => "Número",
        "goal" => 4,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Jefe de Ventas - Camiones"
      ],
      [
        "dni" => "46783418",
        "objective" => "Meta de facturación mensual mayor o igual a 100%",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 100,
        "categoria" => "Asesor De Repuestos"
      ],
      [
        "dni" => "46966858",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Número",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "46966858",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "46966858",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "46966858",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Número",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "46670405",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => 100,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "42662829",
        "objective" => "Efectividad de proyección de facturación",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Asistente De Facturacion Y Cobranza"
      ],
      [
        "dni" => "42662829",
        "objective" => "Recuperación de documentos",
        "metric" => "Número",
        "goal" => 90,
        "isAscending" => 1,
        "weight" => 34,
        "categoria" => "Asistente De Facturacion Y Cobranza"
      ],
      [
        "dni" => "42662829",
        "objective" => "Efectividad proyección de cobranza",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Asistente De Facturacion Y Cobranza"
      ],
      [
        "dni" => "45957690",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => 100,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "47552281",
        "objective" => "Cumplimiento de ICD al 90% por sede",
        "metric" => "Número",
        "goal" => 90,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Asistente De Post Venta"
      ],
      [
        "dni" => "47552281",
        "objective" => "Cumplimiento de los standares del Postventa por parte de Derco al 80%",
        "metric" => "Número",
        "goal" => 80,
        "isAscending" => 1,
        "weight" => 50,
        "categoria" => "Asistente De Post Venta"
      ],
      [
        "dni" => "16523120",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Número",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16523120",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16523120",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16523120",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Número",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "71232635",
        "objective" => "Memorándums elaborados",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Asistente Legal"
      ],
      [
        "dni" => "71232635",
        "objective" => "Reclamos atendidos",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 33,
        "categoria" => "Asistente Legal"
      ],
      [
        "dni" => "71232635",
        "objective" => "Solicitudes y requerimientos de entidades",
        "metric" => "Número",
        "goal" => 100,
        "isAscending" => 1,
        "weight" => 34,
        "categoria" => "Asistente Legal"
      ],
      [
        "dni" => "71215084",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza ",
        "metric" => "Número",
        "goal" => 3,
        "isAscending" => 0,
        "weight" => 100,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "19247378",
        "objective" => "Tener mínimo un rendimiento de combustible del 92%",
        "metric" => "Número",
        "goal" => 92,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "19247378",
        "objective" => "No tener incidencias, con responsalidad del conductor",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "19247378",
        "objective" => "No tener multas por llenado por mal llenado de guías",
        "metric" => "Número",
        "goal" => 0,
        "isAscending" => 0,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "19247378",
        "objective" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)",
        "metric" => "Número",
        "goal" => 85,
        "isAscending" => 1,
        "weight" => 25,
        "categoria" => "Conductor De Tracto Camion"
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
     * "categoria"=> "Tecnico Mecanico Ap"
     * ]
     */

//      1. Eliminar todos los registros de las tablas
    DB::statement('SET FOREIGN_KEY_CHECKS=0;');

    EvaluationPersonDashboard::query()->truncate();
    EvaluationDashboard::query()->truncate();
    EvaluationPersonDetail::query()->truncate();
    EvaluationPerson::query()->truncate();
    EvaluationPersonResult::query()->truncate();
    EvaluationPersonCycleDetail::query()->truncate();
    EvaluationCategoryObjectiveDetail::query()->truncate();
    EvaluationCategoryCompetenceDetail::query()->truncate();
    EvaluationCycle::query()->truncate();
    Evaluation::query()->truncate();
    EvaluationObjective::query()->truncate();
    EvaluationCycleCategoryDetail::query()->truncate();
    HierarchicalCategoryDetail::query()->truncate();
    HierarchicalCategory::query()->truncate();

    DB::statement('SET FOREIGN_KEY_CHECKS=1;');


//    2. Definir servicios
    $categoryObjectiveService = new EvaluationCategoryObjectiveDetailService();

//    Mapear todos los datos
    foreach ($data as $item) {
      $person = Worker::where('vat', $item['dni'])->where('status_id', 22)->first();

      $category = HierarchicalCategory::where('name', $item['categoria'])->first();

      $objective = EvaluationObjective::where('name', $item['objective'])->first();
      $metric = EvaluationMetric::where('name', $item['metric'])->first();

      if (!$person) {
        throw new \Exception("No se encontró la persona con DNI: {$item['dni']}");
      }

      if (!$person->cargo_id) {
        throw new \Exception("La persona con DNI: {$item['dni']} no tiene un cargo asignado.");
      }

      if (!$category) {
        $newCategory = HierarchicalCategory::create([
          'name' => $item['categoria'],
          'description' => 'Description for the category ' . $item['categoria'],
          'hasObjectives' => true,
        ]);
        $category = HierarchicalCategory::find($newCategory->id);
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
