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

/**
 * sudo docker compose exec app php artisan db:seed --class=Database\\Seeders\\gp\\evaluation\\ObjectiveSeeder3
 * php artisan db:seed --class=Database\Seeders\gp\evaluation\ObjectiveSeeder3
 */
class ObjectiveSeeder3 extends Seeder
{
  public function run(): void
  {
    /**
     * [
     * "dni" => "45727231",
     * "objective" => "Reingresos de vehículos",
     * "metric" => "Número",
     * "goal" => 2,
     * "isAscending" => 0,
     * "weight" => 50,
     * "categoria" => "Tecnico Mecanico Ap"
     * ],
     */

    $data = [
      [
        "dni" => "72811912",
        "objective" => "Cumplimiento de facturación de las 4 sedes",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 100,
        "isAscending" => true,
        "categoria" => "Jefe de Repuestos"
      ],
      [
        "dni" => "45727231",
        "objective" => "Reingresos de vehículos",
        "metric" => "Unidad",
        "goal" => 1,
        "weight" => 50,
        "isAscending" => false,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "45727231",
        "objective" => "Eficiencia de OT",
        "description" => "Promedio de productividad de OT atendidas al mes",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "73748430",
        "objective" => "Efectividad de proyección de facturación",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 30,
        "isAscending" => true,
        "categoria" => "Asistente De Facturacion Y Cobranza"
      ],
      [
        "dni" => "73748430",
        "objective" => "Recuperación de documentos en tiempo establecido",
        "metric" => "Porcentaje",
        "goal" => 95,
        "weight" => 15,
        "isAscending" => true,
        "categoria" => "Asistente De Facturacion Y Cobranza"
      ],
      [
        "dni" => "73748430",
        "objective" => "Efectividad proyección de cobranza",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 30,
        "isAscending" => true,
        "categoria" => "Asistente De Facturacion Y Cobranza"
      ],
      [
        "dni" => "73748430",
        "objective" => "Control de parihuelas Gloria",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 15,
        "isAscending" => true,
        "categoria" => "Asistente De Facturacion Y Cobranza"
      ],
      [
        "dni" => "73748430",
        "objective" => "Aplicación de detracciones",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 10,
        "isAscending" => true,
        "categoria" => "Asistente De Facturacion Y Cobranza"
      ],
      [
        "dni" => "41086411",
        "objective" => "Cumplimiento mensual de los límites de velocidad en ruta",
        "description" => "Total de viajes por despachos del mes no excedan los 80km\/hr. (Control GPS)",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "41086411",
        "objective" => "Cero multas de tránsito",
        "description" => "Por infringir las normas de tránsito en el desarrollo de sus funciones",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "41086411",
        "objective" => "Promedio mensual de ingreso",
        "description" => "Promedio mensual de marcaciones de ingreso del mes según biométrico.",
        "metric" => "Minutos",
        "goal" => 3,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "41086411",
        "objective" => "Cumplimiento de inventario de sede al 100%",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 0,
        "isAscending" => true,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "41086411",
        "objective" => "Cero incidencias en la ruta asignada (robos, choques,etc)",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "46976471",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidades",
        "goal" => 5,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "46976471",
        "objective" => "Reputation",
        "description" => "Meta regional score superior a 820 puntos",
        "metric" => "Número",
        "goal" => 820,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "46976471",
        "objective" => "Colocación de créditos",
        "metric" => "Unidad",
        "goal" => 2,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "46976471",
        "objective" => "Colocación de seguros",
        "metric" => "Unidad",
        "goal" => 2,
        "weight" => 10,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "71319927",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidades",
        "goal" => 13,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "71319927",
        "objective" => "Reputation",
        "description" => "Meta regional score superior a 820 puntos",
        "metric" => "Número",
        "goal" => 820,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "71319927",
        "objective" => "Colocación de créditos",
        "metric" => "Unidad",
        "goal" => 5,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "71319927",
        "objective" => "Colocación de seguros",
        "metric" => "Unidad",
        "goal" => 5,
        "weight" => 10,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "47620737",
        "objective" => "Cumplimiento de la programaciòn",
        "description" => " (Servicios entregados a tiempo\/total de servicios programados)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Asistente De Seguimiento Y Monitoreo"
      ],
      [
        "dni" => "47620737",
        "objective" => "Errores en llenado de guías",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Asistente De Seguimiento Y Monitoreo"
      ],
      [
        "dni" => "47620737",
        "objective" => "Robos en tracto camiones",
        "description" => "Por falta de seguimiento y monitoreo",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Asistente De Seguimiento Y Monitoreo"
      ],
      [
        "dni" => "47620737",
        "objective" => "Accidente y\/o Siniestros",
        "description" => "Por responsabilidad de seguimiento y monitoreo según nivel",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Asistente De Seguimiento Y Monitoreo"
      ],
      [
        "dni" => "42330495",
        "objective" => "Rendimiento de combustible.",
        "description" => 0.92,
        "metric" => "Porcentaje",
        "goal" => 92,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "42330495",
        "objective" => "Fletes no cubiertos por negativa de conductor",
        "description" => "Flete no asumido por el conductor, más no perdido.",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "42330495",
        "objective" => "Número de incidencias por responsalidad del conductor",
        "description" => "0: No hubo incidencia \/ 1: Incidencia leve \/ 2:Incidencia moderada \/ 3: Incidencia grave",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 30,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "42330495",
        "objective" => "Promedio de conducción",
        "description" => "(Se da un un rango de cumplimiento entre 85 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "32932405",
        "objective" => "Total de incoformidades en despachos",
        "description" => " % Total de facturas (despachos externos) vs % de incorfomidades",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 60,
        "isAscending" => true,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "32932405",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza",
        "metric" => "Minutos",
        "goal" => 3,
        "weight" => 40,
        "isAscending" => false,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "47085242",
        "objective" => "Rendimiento de combustible.",
        "description" => 0.92,
        "metric" => "Porcentaje",
        "goal" => 92,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "47085242",
        "objective" => "Fletes no cubiertos por negativa de conductor",
        "description" => "Flete no asumido por el conductor, más no perdido.",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "47085242",
        "objective" => "Número de incidencias por responsalidad del conductor",
        "description" => "0: No hubo incidencia \/ 1: Incidencia leve \/ 2:Incidencia moderada \/ 3: Incidencia grave",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 30,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "47085242",
        "objective" => "Promedio de conducción",
        "description" => "(Se da un un rango de cumplimiento entre 85 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "75421824",
        "objective" => "Arqueo de caja, sin diferencias",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Caja Ap"
      ],
      [
        "dni" => "75421824",
        "objective" => "Número de arqueos con efectivo mayor a S\/5000",
        "description" => "El efectivo en caja no debe exceder los S\/5000 diario según arqueos.",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 40,
        "isAscending" => false,
        "categoria" => "Caja Ap"
      ],
      [
        "dni" => "75421824",
        "objective" => "Aplicaciones de CXC ejecutadas al cierre de mes",
        "description" => "Número de ingresos en bancos aplicados despues de plazo",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 40,
        "isAscending" => false,
        "categoria" => "Caja Ap"
      ],
      [
        "dni" => "70059908",
        "objective" => "Cuadres de caja oportunos",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Caja Dp"
      ],
      [
        "dni" => "70059908",
        "objective" => "Arqueo de caja, sin diferencias",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Caja Dp"
      ],
      [
        "dni" => "40502833",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidades",
        "goal" => 7,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "40502833",
        "objective" => "Reputation",
        "description" => "Meta regional score superior a 820 puntos",
        "metric" => "Número",
        "goal" => 820,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "40502833",
        "objective" => "Colocación de créditos",
        "metric" => "Unidad",
        "goal" => 2,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "40502833",
        "objective" => "Colocación de seguros",
        "metric" => "Unidad",
        "goal" => 1,
        "weight" => 10,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "70475815",
        "objective" => "Ejecución del Plan de Clima Laboral y Bienestar",
        "metric" => "Porcentaje",
        "goal" => 95,
        "weight" => 40,
        "isAscending" => true,
        "categoria" => "Asistente De Bienestar Y Clima Laboral"
      ],
      [
        "dni" => "70475815",
        "objective" => "Nota de Clima Laboral",
        "description" => "Notal general de Clima Laboral",
        "metric" => "Porcentaje",
        "goal" => 80,
        "weight" => 0,
        "isAscending" => true,
        "categoria" => "Asistente De Bienestar Y Clima Laboral"
      ],
      [
        "dni" => "70475815",
        "objective" => "Adherencia a Encuesta de Clima 2026",
        "description" => "Cumplimiento total en 15 días ",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 0,
        "isAscending" => true,
        "categoria" => "Asistente De Bienestar Y Clima Laboral"
      ],
      [
        "dni" => "70475815",
        "objective" => "Adherencia a Pakatnamu Star",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 30,
        "isAscending" => true,
        "categoria" => "Asistente De Bienestar Y Clima Laboral"
      ],
      [
        "dni" => "70475815",
        "objective" => "Subsidios recuperados",
        "description" => "Cantidad de subsidios en dinero recuperado dentro del plazo(1 mes)\/ cantidad de subsidios en dinero
declarados",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 30,
        "isAscending" => true,
        "categoria" => "Asistente De Bienestar Y Clima Laboral"
      ],
      [
        "dni" => "16664102",
        "objective" => "Notas de crèdito a proveedores",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Jefe De Administracion"
      ],
      [
        "dni" => "16664102",
        "objective" => "Pago a proveedores",
        "metric" => "Porcentaje",
        "goal" => 0,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Jefe De Administracion"
      ],
      [
        "dni" => "16664102",
        "objective" => "Gestión de compras",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Jefe De Administracion"
      ],
      [
        "dni" => "16664102",
        "objective" => "Efectividad de liquidaciones",
        "description" => "% de reconocimeinto de la liquidacion / total de liquidaciones registradas",
        "metric" => "Número",
        "goal" => 100,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Jefe De Administracion"
      ],
      [
        "dni" => "76752460",
        "objective" => "Reingresos de vehículos",
        "metric" => "Unidad",
        "goal" => 1,
        "weight" => 50,
        "isAscending" => false,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "76752460",
        "objective" => "Eficiencia de OT",
        "description" => "Promedio de productividad de OT atendidas al mes",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "18186852",
        "objective" => "Cumplimiento mensual de los límites de velocidad en ruta",
        "description" => "Total de viajes por despachos del mes no excedan los 80km\/hr. (Control GPS)",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "18186852",
        "objective" => "Cero multas de tránsito",
        "description" => "Por infringir las normas de tránsito en el desarrollo de sus funciones",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "18186852",
        "objective" => "Promedio mensual de ingreso",
        "description" => "Promedio mensual de marcaciones de ingreso del mes según biométrico.",
        "metric" => "Minutos",
        "goal" => 3,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "18186852",
        "objective" => "Cumplimiento de inventario de sede al 100%",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 0,
        "isAscending" => true,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "18186852",
        "objective" => "Cero incidencias en la ruta asignada (robos, choques,etc)",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "16802538",
        "objective" => "Cumplimiento promedio de registro de compras en el sistema (diario)",
        "description" => "Compras vs registro en sistema propio",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 30,
        "isAscending" => true,
        "categoria" => "Coordinador De Ventas"
      ],
      [
        "dni" => "16802538",
        "objective" => "Liquidaciones dentro del plazo estipulado",
        "description" => "Número de semanas con aplicaciones dentro del plazo",
        "metric" => "Unidad",
        "goal" => 4,
        "weight" => 30,
        "isAscending" => true,
        "categoria" => "Coordinador De Ventas"
      ],
      [
        "dni" => "16802538",
        "objective" => "Cumplimiento promedio de reportería en Dealer Portal (diario)",
        "description" => "Facturación vs reporte TICs ",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 30,
        "isAscending" => true,
        "categoria" => "Coordinador De Ventas"
      ],
      [
        "dni" => "16802538",
        "objective" => "Cumplimiento de envío de las solicitudes de nota de crédito en tiempo establecido",
        "metric" => "Días",
        "goal" => 3,
        "weight" => 10,
        "isAscending" => false,
        "categoria" => "Coordinador De Ventas"
      ],
      [
        "dni" => "16753465",
        "objective" => "Rendimiento de combustible.",
        "description" => 0.92,
        "metric" => "Porcentaje",
        "goal" => 92,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16753465",
        "objective" => "Fletes no cubiertos por negativa de conductor",
        "description" => "Flete no asumido por el conductor, más no perdido.",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16753465",
        "objective" => "Número de incidencias por responsalidad del conductor",
        "description" => "0: No hubo incidencia \/ 1: Incidencia leve \/ 2:Incidencia moderada \/ 3: Incidencia grave",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 30,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16753465",
        "objective" => "Promedio de conducción",
        "description" => "(Se da un un rango de cumplimiento entre 85 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "47386012",
        "objective" => "Rendimiento de combustible.",
        "description" => 0.92,
        "metric" => "Porcentaje",
        "goal" => 92,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "47386012",
        "objective" => "Fletes no cubiertos por negativa de conductor",
        "description" => "Flete no asumido por el conductor, más no perdido.",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "47386012",
        "objective" => "Número de incidencias por responsalidad del conductor",
        "description" => "0: No hubo incidencia \/ 1: Incidencia leve \/ 2:Incidencia moderada \/ 3: Incidencia grave",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 30,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "47386012",
        "objective" => "Promedio de conducción",
        "description" => "(Se da un un rango de cumplimiento entre 85 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "70086807",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidades",
        "goal" => 7,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "70086807",
        "objective" => "Reputation",
        "description" => "Meta regional score superior a 820 puntos",
        "metric" => "Número",
        "goal" => 820,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "70086807",
        "objective" => "Colocación de créditos",
        "metric" => "Unidad",
        "goal" => 2,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "70086807",
        "objective" => "Colocación de seguros",
        "metric" => "Unidad",
        "goal" => 1,
        "weight" => 10,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "44063728",
        "objective" => "Total de incoformidades en despachos",
        "description" => " % Total de facturas (despachos externos) vs % de incorfomidades",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 60,
        "isAscending" => true,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "44063728",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza",
        "metric" => "Minutos",
        "goal" => 3,
        "weight" => 40,
        "isAscending" => false,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "77668182",
        "objective" => "Reingresos de vehículos",
        "metric" => "Unidad",
        "goal" => 1,
        "weight" => 50,
        "isAscending" => false,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "77668182",
        "objective" => "Eficiencia de OT",
        "description" => "Promedio de productividad de OT atendidas al mes",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "76252984",
        "objective" => "Reingresos de vehículos",
        "metric" => "Unidad",
        "goal" => 1,
        "weight" => 50,
        "isAscending" => false,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "76252984",
        "objective" => "Eficiencia de OT",
        "description" => "Promedio de productividad de OT atendidas al mes",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "73747580",
        "objective" => "Calidad de servicio - Mesa de ayuda (Milla \/ Dynamics)",
        "description" => "Promedio de calificaciones de encuesta de satisfacción en base a los servicios atendidos (Escalada 1-5)",
        "metric" => "Unidad",
        "goal" => 4,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Analista En Proyecto TICs"
      ],
      [
        "dni" => "73747580",
        "objective" => "Promedio de efectividad en tiempo de atención - Soporte TIC's",
        "metric" => "Minutos",
        "goal" => 20,
        "weight" => 50,
        "isAscending" => false,
        "categoria" => "Analista En Proyecto TICs"
      ],
      [
        "dni" => "72790935",
        "objective" => "Reingresos de vehículos",
        "metric" => "Unidad",
        "goal" => 1,
        "weight" => 50,
        "isAscending" => false,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "72790935",
        "objective" => "Eficiencia de OT",
        "description" => "Promedio de productividad de OT atendidas al mes",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "19255538",
        "objective" => "Rendimiento de combustible.",
        "description" => 0.92,
        "metric" => "Porcentaje",
        "goal" => 92,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "19255538",
        "objective" => "Fletes no cubiertos por negativa de conductor",
        "description" => "Flete no asumido por el conductor, más no perdido.",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "19255538",
        "objective" => "Número de incidencias por responsalidad del conductor",
        "description" => "0: No hubo incidencia \/ 1: Incidencia leve \/ 2:Incidencia moderada \/ 3: Incidencia grave",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 30,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "19255538",
        "objective" => "Promedio de conducción",
        "description" => "(Se da un un rango de cumplimiento entre 85 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "72116004",
        "objective" => "Reingresos de vehículos",
        "metric" => "Unidad",
        "goal" => 1,
        "weight" => 50,
        "isAscending" => false,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "72116004",
        "objective" => "Eficiencia de OT",
        "description" => "Promedio de productividad de OT atendidas al mes",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "46468460",
        "objective" => "Total de incoformidades en despachos",
        "description" => " % Total de facturas (despachos externos) vs % de incorfomidades",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 60,
        "isAscending" => true,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "46468460",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza",
        "metric" => "Minutos",
        "goal" => 3,
        "weight" => 40,
        "isAscending" => false,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "47241820",
        "objective" => "Cumplimiento mensual de los límites de velocidad en ruta",
        "description" => "Total de viajes por despachos del mes no excedan los 80km\/hr. (Control GPS)",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor II"
      ],
      [
        "dni" => "47241820",
        "objective" => "Cero multas de tránsito",
        "description" => "Por infringir las normas de tránsito en el desarrollo de sus funciones",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Conductor II"
      ],
      [
        "dni" => "47241820",
        "objective" => "Promedio mensual de ingreso",
        "description" => "Promedio mensual de marcaciones de ingreso del mes según biométrico.",
        "metric" => "Minutos",
        "goal" => 3,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Conductor II"
      ],
      [
        "dni" => "47241820",
        "objective" => "Cumplimiento de inventario de sede al 100%",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 0,
        "isAscending" => true,
        "categoria" => "Conductor II"
      ],
      [
        "dni" => "47241820",
        "objective" => "Cero incidencias en la ruta asignada (robos, choques,etc)",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Conductor II"
      ],
      [
        "dni" => "80541083",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidades",
        "goal" => 102,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Gerente Comercial Ap"
      ],
      [
        "dni" => "80541083",
        "objective" => "Rentabilidad bruta por grupo de marcas",
        "metric" => "Porcentaje",
        "goal" => 5.5,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Gerente Comercial Ap"
      ],
      [
        "dni" => "80541083",
        "objective" => "Cumplimiento de colaterales=> Financiamiento",
        "metric" => "Número",
        "goal" => 49,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Gerente Comercial Ap"
      ],
      [
        "dni" => "80541083",
        "objective" => "Cumplimiento de colaterales=> Seguros",
        "metric" => "Número",
        "goal" => 18,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Gerente Comercial Ap"
      ],
      [
        "dni" => "80541083",
        "objective" => "Cumplimiento de MS",
        "description" => "Cumplimiento por sede según región",
        "metric" => "Unidad",
        "goal" => 3,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Gerente Comercial Ap"
      ],
      [
        "dni" => "70860579",
        "objective" => "Reingresos de vehículos",
        "metric" => "Unidad",
        "goal" => 1,
        "weight" => 50,
        "isAscending" => false,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "70860579",
        "objective" => "Eficiencia de OT",
        "description" => "Promedio de productividad de OT atendidas al mes",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "42119750",
        "objective" => "Inversión",
        "metric" => "Porcentaje",
        "goal" => 25,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Jefe De Marketing"
      ],
      [
        "dni" => "42119750",
        "objective" => "Win rate de Lead Inchcape mayor al 3%",
        "metric" => "Porcentaje",
        "goal" => 3,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Jefe De Marketing"
      ],
      [
        "dni" => "42119750",
        "objective" => "Cumplimiento mensual de entregas",
        "description" => "Meta AP por el global de sedes",
        "metric" => "Unidad",
        "goal" => 4,
        "weight" => 30,
        "isAscending" => true,
        "categoria" => "Jefe De Marketing"
      ],
      [
        "dni" => "42119750",
        "objective" => "Cumplimiento mensual de PV",
        "description" => "Cumplimiento global de las 5 sedes",
        "metric" => "Unidad",
        "goal" => 5,
        "weight" => 30,
        "isAscending" => true,
        "categoria" => "Jefe De Marketing"
      ],
      [
        "dni" => "16804371",
        "objective" => "Rendimiento de combustible.",
        "description" => 0.92,
        "metric" => "Porcentaje",
        "goal" => 92,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16804371",
        "objective" => "Fletes no cubiertos por negativa de conductor",
        "description" => "Flete no asumido por el conductor, más no perdido.",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16804371",
        "objective" => "Número de incidencias por responsalidad del conductor",
        "description" => "0: No hubo incidencia \/ 1: Incidencia leve \/ 2:Incidencia moderada \/ 3: Incidencia grave",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 30,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16804371",
        "objective" => "Promedio de conducción",
        "description" => "(Se da un un rango de cumplimiento entre 85 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "45017690",
        "objective" => "Gestión de compras",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Jefe De Administracion"
      ],
      [
        "dni" => "45017690",
        "objective" => "Notas de crèdito a proveedores",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Jefe De Administracion"
      ],
      [
        "dni" => "45017690",
        "objective" => "Efectividad de liquidaciones",
        "description" => "% de reconocimeinto de la liquidacion / total de liquidaciones registradas",
        "metric" => "Número",
        "goal" => 100,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Jefe De Administracion"
      ],
      [
        "dni" => "45017690",
        "objective" => "Cumplimiento de ventas en sede",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Jefe De Administracion"
      ],
      [
        "dni" => "45017690",
        "objective" => "Cumplimiento de cobranza de sede",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Jefe De Administracion"
      ],
      [
        "dni" => "42374851",
        "objective" => "Rendimiento de combustible.",
        "description" => 0.92,
        "metric" => "Porcentaje",
        "goal" => 92,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "42374851",
        "objective" => "Fletes no cubiertos por negativa de conductor",
        "description" => "Flete no asumido por el conductor, más no perdido.",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "42374851",
        "objective" => "Número de incidencias por responsalidad del conductor",
        "description" => "0: No hubo incidencia \/ 1: Incidencia leve \/ 2:Incidencia moderada \/ 3: Incidencia grave",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 30,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "42374851",
        "objective" => "Promedio de conducción",
        "description" => "(Se da un un rango de cumplimiento entre 85 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "43920495",
        "objective" => "Rendimiento de combustible.",
        "description" => 0.92,
        "metric" => "Porcentaje",
        "goal" => 92,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "43920495",
        "objective" => "Fletes no cubiertos por negativa de conductor",
        "description" => "Flete no asumido por el conductor, más no perdido.",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "43920495",
        "objective" => "Número de incidencias por responsalidad del conductor",
        "description" => "0: No hubo incidencia \/ 1: Incidencia leve \/ 2:Incidencia moderada \/ 3: Incidencia grave",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 30,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "43920495",
        "objective" => "Promedio de conducción",
        "description" => "(Se da un un rango de cumplimiento entre 85 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "45695736",
        "objective" => "Utilidad Operativa",
        "metric" => "Porcentaje",
        "goal" => 9.5,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Supervisor Comercial"
      ],
      [
        "dni" => "45695736",
        "objective" => "Cliente Nuevo al mes",
        "description" => "Cliente nuevo ingresado en producción en el mes",
        "metric" => "Unidad",
        "goal" => 2,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Supervisor Comercial"
      ],
      [
        "dni" => "45695736",
        "objective" => "Número de viajes",
        "metric" => "Número",
        "goal" => 380,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Supervisor Comercial"
      ],
      [
        "dni" => "71325400",
        "objective" => "Cero soles de multa por errores contables en la empresa a cargo",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Asistente De Contabilidad"
      ],
      [
        "dni" => "71325400",
        "objective" => "Exactitud en registros contables",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 50,
        "isAscending" => false,
        "categoria" => "Asistente De Contabilidad"
      ],
      [
        "dni" => "71325400",
        "objective" => "Entrega de cierres contables en tiempo de empresa a cargo",
        "metric" => "Días",
        "goal" => 5,
        "weight" => 30,
        "isAscending" => true,
        "categoria" => "Asistente De Contabilidad"
      ],
      [
        "dni" => "42704083",
        "objective" => "Cumplimiento de presupuesto comercial (Industria)",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 35,
        "isAscending" => true,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "42704083",
        "objective" => "% de Efectividad de Cobranza",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 35,
        "isAscending" => true,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "46466488",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidades",
        "goal" => 7,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "46466488",
        "objective" => "Reputation",
        "description" => "Meta regional score superior a 820 puntos",
        "metric" => "Número",
        "goal" => 820,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "46466488",
        "objective" => "Colocación de créditos",
        "metric" => "Unidad",
        "goal" => 3,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "46466488",
        "objective" => "Colocación de seguros",
        "metric" => "Unidad",
        "goal" => 3,
        "weight" => 10,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "45577414",
        "objective" => "Cumplimiento mensual de los límites de velocidad en ruta",
        "description" => "Total de viajes por despachos del mes no excedan los 80km\/hr. (Control GPS)",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "45577414",
        "objective" => "Cero multas de tránsito",
        "description" => "Por infringir las normas de tránsito en el desarrollo de sus funciones",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "45577414",
        "objective" => "Promedio mensual de ingreso",
        "description" => "Promedio mensual de marcaciones de ingreso del mes según biométrico.",
        "metric" => "Minutos",
        "goal" => 3,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "45577414",
        "objective" => "Cumplimiento de inventario de sede al 100%",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 0,
        "isAscending" => true,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "45577414",
        "objective" => "Cero incidencias en la ruta asignada (robos, choques,etc)",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "75756058",
        "objective" => "Cumplir de paso vehicular",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 30,
        "isAscending" => true,
        "categoria" => "Asesor De Servicios"
      ],
      [
        "dni" => "75756058",
        "objective" => "Cumplimiento de meta general (facturación)",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Asesor De Servicios"
      ],
      [
        "dni" => "75756058",
        "objective" => "Reputation",
        "description" => "Meta regional score superior a 820 puntos",
        "metric" => "Número",
        "goal" => 820,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asesor De Servicios"
      ],
      [
        "dni" => "19256673",
        "objective" => "Total de incoformidades en despachos",
        "description" => " % Total de facturas vs % de incorfomidades (plataforma)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 60,
        "isAscending" => true,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "19256673",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza",
        "metric" => "Minutos",
        "goal" => 3,
        "weight" => 40,
        "isAscending" => false,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "27167946",
        "objective" => "Rendimiento de combustible.",
        "description" => 0.92,
        "metric" => "Porcentaje",
        "goal" => 92,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "27167946",
        "objective" => "Fletes no cubiertos por negativa de conductor",
        "description" => "Flete no asumido por el conductor, más no perdido.",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "27167946",
        "objective" => "Número de incidencias por responsalidad del conductor",
        "description" => "0: No hubo incidencia \/ 1: Incidencia leve \/ 2:Incidencia moderada \/ 3: Incidencia grave",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 30,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "27167946",
        "objective" => "Promedio de conducción",
        "description" => "(Se da un un rango de cumplimiento entre 85 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "41893192",
        "objective" => "Total de incoformidades en despachos",
        "description" => " % Total de facturas (despachos externos) vs % de incorfomidades",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 60,
        "isAscending" => true,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "41893192",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza",
        "metric" => "Minutos",
        "goal" => 3,
        "weight" => 40,
        "isAscending" => false,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "45092596",
        "objective" => "Rendimiento de combustible.",
        "description" => 0.92,
        "metric" => "Porcentaje",
        "goal" => 92,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "45092596",
        "objective" => "Fletes no cubiertos por negativa de conductor",
        "description" => "Flete no asumido por el conductor, más no perdido.",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "45092596",
        "objective" => "Número de incidencias por responsalidad del conductor",
        "description" => "0: No hubo incidencia \/ 1: Incidencia leve \/ 2:Incidencia moderada \/ 3: Incidencia grave",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 30,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "45092596",
        "objective" => "Promedio de conducción",
        "description" => "(Se da un un rango de cumplimiento entre 85 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "41399449",
        "objective" => "Cumplimiento de presupuesto comercial",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "41399449",
        "objective" => "% de Efectividad de Cobranza",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "41399449",
        "objective" => "Mix de productos",
        "metric" => "Unidad",
        "goal" => 5,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "41399449",
        "objective" => "Cliente Nuevo al mes",
        "metric" => "Unidad",
        "goal" => 2,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "72904816",
        "objective" => "Total de incoformidades en despachos",
        "description" => " % Total de facturas (despachos externos) vs % de incorfomidades",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 60,
        "isAscending" => true,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "72904816",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza",
        "metric" => "Minutos",
        "goal" => 3,
        "weight" => 40,
        "isAscending" => false,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "05644606",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidades",
        "goal" => 13,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "05644606",
        "objective" => "Reputation",
        "description" => "Meta regional score superior a 820 puntos",
        "metric" => "Número",
        "goal" => 820,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "05644606",
        "objective" => "Colocación de créditos",
        "metric" => "Unidad",
        "goal" => 5,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "05644606",
        "objective" => "Colocación de seguros",
        "metric" => "Unidad",
        "goal" => 5,
        "weight" => 10,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "70408777",
        "objective" => "Reingresos de vehículos",
        "metric" => "Unidad",
        "goal" => 2,
        "weight" => 70,
        "isAscending" => false,
        "categoria" => "Coordinador De Taller"
      ],
      [
        "dni" => "74413833",
        "objective" => "Reingresos de vehículos",
        "metric" => "Unidad",
        "goal" => 1,
        "weight" => 50,
        "isAscending" => false,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "74413833",
        "objective" => "Eficiencia de OT",
        "description" => "Promedio de productividad de OT atendidas al mes",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "47080684",
        "objective" => "Reingresos de vehículos",
        "metric" => "Unidad",
        "goal" => 1,
        "weight" => 50,
        "isAscending" => false,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "47080684",
        "objective" => "Eficiencia de OT",
        "description" => "Promedio de productividad de OT atendidas al mes",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "46280064",
        "objective" => "Cuadres de caja oportunos",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Caja Dp"
      ],
      [
        "dni" => "46280064",
        "objective" => "Arqueo de caja, sin diferencias",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Caja Dp"
      ],
      [
        "dni" => "70897298",
        "objective" => "Promedio de vacaciones DP y TP",
        "metric" => "Unidad",
        "goal" => 24,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Partner De Gestión Humana"
      ],
      [
        "dni" => "70897298",
        "objective" => "Procesos dentro del plazo DP",
        "metric" => "Porcentaje",
        "goal" => 80,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Partner De Gestión Humana"
      ],
      [
        "dni" => "70897298",
        "objective" => "Unidades inactivas por falta de conductor TP",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Partner De Gestión Humana"
      ],
      [
        "dni" => "70897298",
        "objective" => "Nota Clima Laboral TP",
        "metric" => "Porcentaje",
        "goal" => 80,
        "weight" => 0,
        "isAscending" => true,
        "categoria" => "Partner De Gestión Humana"
      ],
      [
        "dni" => "70897298",
        "objective" => "Nota Clima Laboral DP",
        "metric" => "Porcentaje",
        "goal" => 80,
        "weight" => 0,
        "isAscending" => true,
        "categoria" => "Partner De Gestión Humana"
      ],
      [
        "dni" => "70897298",
        "objective" => "Plazo de envio de boletas DP",
        "metric" => "Días",
        "goal" => 5,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Partner De Gestión Humana"
      ],
      [
        "dni" => "70897298",
        "objective" => "Plazo de envio de boletas TP",
        "metric" => "Días",
        "goal" => 7,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Partner De Gestión Humana"
      ],
      [
        "dni" => "42352466",
        "objective" => "Total de incoformidades en despachos",
        "description" => " % Total de facturas (despachos externos e internos) vs % de incorfomidades",
        "metric" => "Porcentaje",
        "goal" => 90,
        "weight" => 60,
        "isAscending" => true,
        "categoria" => "Supervisor De Almacen"
      ],
      [
        "dni" => "42352466",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza",
        "metric" => "Minutos",
        "goal" => 3,
        "weight" => 40,
        "isAscending" => false,
        "categoria" => "Supervisor De Almacen"
      ],
      [
        "dni" => "02767057",
        "objective" => "Cumplimiento mensual de los límites de velocidad en ruta",
        "description" => "Total de viajes por despachos del mes no excedan los 80km\/hr. (Control GPS)",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "02767057",
        "objective" => "Cero multas de tránsito",
        "description" => "Por infringir las normas de tránsito en el desarrollo de sus funciones",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "02767057",
        "objective" => "Promedio mensual de ingreso",
        "description" => "Promedio mensual de marcaciones de ingreso del mes según biométrico.",
        "metric" => "Minutos",
        "goal" => 3,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "02767057",
        "objective" => "Cumplimiento de inventario de sede al 100%",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 0,
        "isAscending" => true,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "02767057",
        "objective" => "Cero incidencias en la ruta asignada (robos, choques,etc)",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "48139848",
        "objective" => "Cumplimiento mensual de los límites de velocidad en ruta",
        "description" => "Total de viajes por despachos del mes no excedan los 80km\/hr. (Control GPS)",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "48139848",
        "objective" => "Cero multas de tránsito",
        "description" => "Por infringir las normas de tránsito en el desarrollo de sus funciones",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "48139848",
        "objective" => "Promedio mensual de ingreso",
        "description" => "Promedio mensual de marcaciones de ingreso del mes según biométrico.",
        "metric" => "Minutos",
        "goal" => 3,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "48139848",
        "objective" => "Cumplimiento de inventario de sede al 100%",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 0,
        "isAscending" => true,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "48139848",
        "objective" => "Cero incidencias en la ruta asignada (robos, choques,etc)",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "16668399",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidades",
        "goal" => 62,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Jefe De Ventas Ap"
      ],
      [
        "dni" => "16668399",
        "objective" => "Reputation",
        "description" => "Meta regional score superior a 820 puntos",
        "metric" => "Número",
        "goal" => 820,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Jefe De Ventas Ap"
      ],
      [
        "dni" => "16668399",
        "objective" => "Meta en seguros",
        "metric" => "Número",
        "goal" => 24,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Jefe De Ventas Ap"
      ],
      [
        "dni" => "16668399",
        "objective" => "Meta en créditos",
        "metric" => "Número",
        "goal" => 24,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Jefe De Ventas Ap"
      ],
      [
        "dni" => "16668399",
        "objective" => "Alcance de Mix de Marca",
        "metric" => "Unidad",
        "goal" => 3,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Jefe De Ventas Ap"
      ],
      [
        "dni" => "40291710",
        "objective" => "Cumplimiento al 100% de la meta de venta establecida",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Jefe De Ventas Dp"
      ],
      [
        "dni" => "40291710",
        "objective" => "% de Efectividad de Cobranza",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Jefe De Ventas Dp"
      ],
      [
        "dni" => "40291710",
        "objective" => "Rentabilidad bruta mayor igual a 5.6%",
        "metric" => "Porcentaje",
        "goal" => 5.6,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Jefe De Ventas Dp"
      ],
      [
        "dni" => "40291710",
        "objective" => "Utilidad Operativa",
        "metric" => "Porcentaje",
        "goal" => 1.2,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Jefe De Ventas Dp"
      ],
      [
        "dni" => "40291710",
        "objective" => "Mix de productos",
        "metric" => "Unidad",
        "goal" => 5,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Jefe De Ventas Dp"
      ],
      [
        "dni" => "74868424",
        "objective" => "Cero soles de multa por errores contables en la empresa a cargo",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 30,
        "isAscending" => false,
        "categoria" => "Analista De Contabilidad AP"
      ],
      [
        "dni" => "74868424",
        "objective" => "Entrega de cierres contables en tiempo de empresa a cargo",
        "metric" => "Días",
        "goal" => 5,
        "weight" => 40,
        "isAscending" => true,
        "categoria" => "Analista De Contabilidad AP"
      ],
      [
        "dni" => "74868424",
        "objective" => "Presentación de DDJJ mensuales a SUNAT",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 30,
        "isAscending" => true,
        "categoria" => "Analista De Contabilidad AP"
      ],
      [
        "dni" => "16710404",
        "objective" => "Total de incoformidades en despachos",
        "description" => " % Total de facturas (despachos externos e internos) vs % de incorfomidades",
        "metric" => "Porcentaje",
        "goal" => 90,
        "weight" => 60,
        "isAscending" => true,
        "categoria" => "Supervisor De Almacen"
      ],
      [
        "dni" => "16710404",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza",
        "metric" => "Minutos",
        "goal" => 3,
        "weight" => 40,
        "isAscending" => false,
        "categoria" => "Supervisor De Almacen"
      ],
      [
        "dni" => "27436038",
        "objective" => "Total de incoformidades en despachos",
        "description" => " % Total de facturas (despachos externos) vs % de incorfomidades",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 60,
        "isAscending" => true,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "27436038",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza",
        "metric" => "Minutos",
        "goal" => 3,
        "weight" => 40,
        "isAscending" => false,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "44184967",
        "objective" => "Reingresos de vehículos",
        "metric" => "Unidad",
        "goal" => 1,
        "weight" => 50,
        "isAscending" => false,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "44184967",
        "objective" => "Eficiencia de OT",
        "description" => "Promedio de productividad de OT atendidas al mes",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "41255232",
        "objective" => "Rendimiento de combustible.",
        "description" => 0.92,
        "metric" => "Porcentaje",
        "goal" => 92,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "41255232",
        "objective" => "Fletes no cubiertos por negativa de conductor",
        "description" => "Flete no asumido por el conductor, más no perdido.",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "41255232",
        "objective" => "Número de incidencias por responsalidad del conductor",
        "description" => "0: No hubo incidencia \/ 1: Incidencia leve \/ 2:Incidencia moderada \/ 3: Incidencia grave",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 30,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "41255232",
        "objective" => "Promedio de conducción",
        "description" => "(Se da un un rango de cumplimiento entre 85 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "45421958",
        "objective" => "Rendimiento de combustible.",
        "description" => 0.92,
        "metric" => "Porcentaje",
        "goal" => 92,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "45421958",
        "objective" => "Fletes no cubiertos por negativa de conductor",
        "description" => "Flete no asumido por el conductor, más no perdido.",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "45421958",
        "objective" => "Número de incidencias por responsalidad del conductor",
        "description" => "0: No hubo incidencia \/ 1: Incidencia leve \/ 2:Incidencia moderada \/ 3: Incidencia grave",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 30,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "45421958",
        "objective" => "Promedio de conducción",
        "description" => "(Se da un un rango de cumplimiento entre 85 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "32960097",
        "objective" => "Entrega de EERR promedio AP-DP-TP",
        "metric" => "Días",
        "goal" => 15,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Gerente De Administración Y Finanzas"
      ],
      [
        "dni" => "32960097",
        "objective" => "Sanciones SUNAT - SUNAFIL",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Gerente De Administración Y Finanzas"
      ],
      [
        "dni" => "32960097",
        "objective" => "Disminuir procentaje de interés con bancos",
        "metric" => "Porcentaje",
        "goal" => 7,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Gerente De Administración Y Finanzas"
      ],
      [
        "dni" => "32960097",
        "objective" => "Cierre de mes sin deuda a proveedores",
        "metric" => "Soles",
        "goal" => 0,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Gerente De Administración Y Finanzas"
      ],
      [
        "dni" => "32960097",
        "objective" => "Nota de Clima Laboral",
        "metric" => "Porcentaje",
        "goal" => 80,
        "weight" => 0,
        "isAscending" => true,
        "categoria" => "Gerente De Administración Y Finanzas"
      ],
      [
        "dni" => "32960097",
        "objective" => "Cumplimiento de margen neto de AP-TP-DP",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Gerente De Administración Y Finanzas"
      ],
      [
        "dni" => "47037915",
        "objective" => "Cumplimiento mensual de los límites de velocidad en ruta",
        "description" => "Total de viajes por despachos del mes no excedan los 80km\/hr. (Control GPS)",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "47037915",
        "objective" => "Cero multas de tránsito",
        "description" => "Por infringir las normas de tránsito en el desarrollo de sus funciones",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "47037915",
        "objective" => "Promedio mensual de ingreso",
        "description" => "Promedio mensual de marcaciones de ingreso del mes según biométrico.",
        "metric" => "Minutos",
        "goal" => 3,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "47037915",
        "objective" => "Cumplimiento de inventario de sede al 100%",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 0,
        "isAscending" => true,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "47037915",
        "objective" => "Cero incidencias en la ruta asignada (robos, choques,etc)",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "47704283",
        "objective" => "Total de incoformidades en despachos",
        "description" => " % Total de facturas (despachos externos) vs % de incorfomidades",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 60,
        "isAscending" => true,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "47704283",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza",
        "metric" => "Minutos",
        "goal" => 3,
        "weight" => 40,
        "isAscending" => false,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "44764424",
        "objective" => "Cumplimiento al 100% de la meta de venta establecida",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Jefe De Ventas Dp"
      ],
      [
        "dni" => "44764424",
        "objective" => "% de Efectividad de Cobranza",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Jefe De Ventas Dp"
      ],
      [
        "dni" => "44764424",
        "objective" => "Rentabilidad bruta mayor igual a 5.6%",
        "metric" => "Porcentaje",
        "goal" => 5.6,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Jefe De Ventas Dp"
      ],
      [
        "dni" => "44764424",
        "objective" => "Incremento de cartera",
        "metric" => "Unidad",
        "goal" => 4,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Jefe De Ventas Dp"
      ],
      [
        "dni" => "44764424",
        "objective" => "Mix de productos",
        "metric" => "Unidad",
        "goal" => 5,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Jefe De Ventas Dp"
      ],
      [
        "dni" => "42315795",
        "objective" => "Rendimiento de combustible.",
        "description" => 0.92,
        "metric" => "Porcentaje",
        "goal" => 92,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "42315795",
        "objective" => "Fletes no cubiertos por negativa de conductor",
        "description" => "Flete no asumido por el conductor, más no perdido.",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "42315795",
        "objective" => "Número de incidencias por responsalidad del conductor",
        "description" => "0: No hubo incidencia \/ 1: Incidencia leve \/ 2:Incidencia moderada \/ 3: Incidencia grave",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 30,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "42315795",
        "objective" => "Promedio de conducción",
        "description" => "(Se da un un rango de cumplimiento entre 85 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "76009888",
        "objective" => "Errores en cálculo de planilla GP",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 34,
        "isAscending" => false,
        "categoria" => "Asistente de Remuneraciones y Compensaciones"
      ],
      [
        "dni" => "76009888",
        "objective" => "Errores en cálculo de planilla TP",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 34,
        "isAscending" => false,
        "categoria" => "Asistente de Remuneraciones y Compensaciones"
      ],
      [
        "dni" => "76449504",
        "objective" => "Cumplir de paso vehicular",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 30,
        "isAscending" => true,
        "categoria" => "Asesor De Servicios"
      ],
      [
        "dni" => "76449504",
        "objective" => "Cumplimiento de meta general (facturación)",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Asesor De Servicios"
      ],
      [
        "dni" => "76449504",
        "objective" => "Reputation",
        "description" => "Meta regional score superior a 820 puntos",
        "metric" => "Número",
        "goal" => 820,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asesor De Servicios"
      ],
      [
        "dni" => "16787219",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidades",
        "goal" => 168,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Gerente Comercial Ap"
      ],
      [
        "dni" => "16787219",
        "objective" => "Rentabilidad bruta por grupo de marcas",
        "metric" => "Porcentaje",
        "goal" => 5.5,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Gerente Comercial Ap"
      ],
      [
        "dni" => "16787219",
        "objective" => "Cumplimiento de colaterales=> Financiamiento",
        "metric" => "Número",
        "goal" => 24,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Gerente Comercial Ap"
      ],
      [
        "dni" => "16787219",
        "objective" => "Cumplimiento de colaterales=> Seguros",
        "metric" => "Número",
        "goal" => 18,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Gerente Comercial Ap"
      ],
      [
        "dni" => "16787219",
        "objective" => "Cumplimiento de MS",
        "description" => "Cumplimiento por sede según marca",
        "metric" => "Porcentaje",
        "goal" => 3,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Gerente Comercial Ap"
      ],
      [
        "dni" => "80547267",
        "objective" => "Total de incoformidades en despachos",
        "description" => " % Total de facturas (despachos externos) vs % de incorfomidades",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 60,
        "isAscending" => true,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "80547267",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza",
        "metric" => "Minutos",
        "goal" => 3,
        "weight" => 40,
        "isAscending" => false,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "47618600",
        "objective" => "Nivel de servicio",
        "description" => "Cantidad de repuestos para atención de clientes vs demanda",
        "metric" => "Porcentaje",
        "goal" => 90,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Jefe De Almacen"
      ],
      [
        "dni" => "47618600",
        "objective" => "Control de Inventario",
        "description" => "Resultado de inventario físico por sede vs sistema",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Jefe De Almacen"
      ],
      [
        "dni" => "47618600",
        "objective" => "Días de stock de material",
        "metric" => "Días",
        "goal" => 30,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Jefe De Almacen"
      ],
      [
        "dni" => "76574187",
        "objective" => "Cumplimiento promedio de liquidación de vales por arqueo",
        "description" => "Vales pendientes por emitir vs vales levantados según arqueo programado o sorpresivo",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 15,
        "isAscending" => true,
        "categoria" => "Caja General"
      ],
      [
        "dni" => "76574187",
        "objective" => "Número de arqueos con efectivo mayor a S\/50000",
        "description" => "El efectivo en caja no debe exceder los S\/50000 diario según arqueos.",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Caja General"
      ],
      [
        "dni" => "76574187",
        "objective" => "Número de solicitudes de apertura de cajas",
        "description" => "Por error directo de caja general",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 15,
        "isAscending" => false,
        "categoria" => "Caja General"
      ],
      [
        "dni" => "76574187",
        "objective" => "Envio de reportes oportunos",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 10,
        "isAscending" => true,
        "categoria" => "Caja General"
      ],
      [
        "dni" => "76574187",
        "objective" => "Aplicaciones de CXC ejecutadas al cierre de mes",
        "description" => "Número de ingresos en bancos aplicados despues de plazo",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 40,
        "isAscending" => false,
        "categoria" => "Caja General"
      ],
      [
        "dni" => "45243028",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidades",
        "goal" => 5,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "45243028",
        "objective" => "Reputation",
        "description" => "Meta regional score superior a 820 puntos",
        "metric" => "Número",
        "goal" => 820,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "45243028",
        "objective" => "Colocación de créditos",
        "metric" => "Unidad",
        "goal" => 2,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "45243028",
        "objective" => "Colocación de seguros",
        "metric" => "Unidad",
        "goal" => 2,
        "weight" => 10,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "73033157",
        "objective" => "Cumplimiento del ICD al 90% por sede",
        "metric" => "Porcentaje",
        "goal" => 90,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Asistente De Post Venta"
      ],
      [
        "dni" => "73033157",
        "objective" => "Cumplimiento de los estándares de Post Venta por parte de Derco al 80%",
        "metric" => "Porcentaje",
        "goal" => 80,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Asistente De Post Venta"
      ],
      [
        "dni" => "80350561",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidades",
        "goal" => 10,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "80350561",
        "objective" => "Reputation",
        "description" => "Meta regional score superior a 820 puntos",
        "metric" => "Número",
        "goal" => 820,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "80350561",
        "objective" => "Colocación de créditos",
        "metric" => "Unidad",
        "goal" => 4,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "80350561",
        "objective" => "Colocación de seguros",
        "metric" => "Unidad",
        "goal" => 4,
        "weight" => 10,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "72342187",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidades",
        "goal" => 8,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "72342187",
        "objective" => "Reputation",
        "description" => "Meta regional score superior a 820 puntos",
        "metric" => "Número",
        "goal" => 820,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "72342187",
        "objective" => "Colocación de créditos",
        "metric" => "Unidad",
        "goal" => 3,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "72342187",
        "objective" => "Colocación de seguros",
        "metric" => "Unidad",
        "goal" => 3,
        "weight" => 10,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "42137357",
        "objective" => "Meta de facturación mensual mayor o igual a 100%",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 100,
        "isAscending" => true,
        "categoria" => "Asesor De Repuestos"
      ],
      [
        "dni" => "73468572",
        "objective" => "Crecimiento de comunidad (SEO)",
        "metric" => "Porcentaje",
        "goal" => 3,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Community Manager"
      ],
      [
        "dni" => "73468572",
        "objective" => "Generación de Leads (Promedio mensual)",
        "description" => "Leads AP",
        "metric" => "Número",
        "goal" => 1000,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Community Manager"
      ],
      [
        "dni" => "73468572",
        "objective" => "Reputation AP",
        "description" => "Número total de comentarios (5 sedes)",
        "metric" => "Número",
        "goal" => 70,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Community Manager"
      ],
      [
        "dni" => "73468572",
        "objective" => "Winrate Inchcape",
        "metric" => "Porcentaje",
        "goal" => 2.5,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Community Manager"
      ],
      [
        "dni" => "73468572",
        "objective" => "Cumplimiento del calendario de campañas",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Community Manager"
      ],
      [
        "dni" => "76627801",
        "objective" => "Cumplimiento promedio de liquidación de vales por arqueo",
        "description" => "Vales pendientes por emitir vs vales levantados según arqueo programado o sorpresivo",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 15,
        "isAscending" => true,
        "categoria" => "Caja General"
      ],
      [
        "dni" => "76627801",
        "objective" => "Número de arqueos con efectivo mayor a S\/50000",
        "description" => "El efectivo en caja no debe exceder los S\/50000 diario según arqueos.",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Caja General"
      ],
      [
        "dni" => "76627801",
        "objective" => "Número de solicitudes de apertura de cajas",
        "description" => "Por error directo de caja general",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 15,
        "isAscending" => false,
        "categoria" => "Caja General"
      ],
      [
        "dni" => "76627801",
        "objective" => "Envio de reportes oportunos",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 10,
        "isAscending" => true,
        "categoria" => "Caja General"
      ],
      [
        "dni" => "76627801",
        "objective" => "Aplicaciones de CXC ejecutadas al cierre de mes",
        "description" => "Número de ingresos en bancos aplicados despues de plazo",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 40,
        "isAscending" => false,
        "categoria" => "Caja General"
      ],
      [
        "dni" => "48021041",
        "objective" => "Cuadres de caja oportunos",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Caja Dp"
      ],
      [
        "dni" => "48021041",
        "objective" => "Arqueo de caja, sin diferencias",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Caja Dp"
      ],
      [
        "dni" => "75581810",
        "objective" => "Total de incoformidades en despachos",
        "description" => " % Total de facturas (despachos externos) vs % de incorfomidades",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 60,
        "isAscending" => true,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "75581810",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza",
        "metric" => "Minutos",
        "goal" => 3,
        "weight" => 40,
        "isAscending" => false,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "74325638",
        "objective" => "Reingresos de vehículos",
        "metric" => "Unidad",
        "goal" => 1,
        "weight" => 50,
        "isAscending" => false,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "74325638",
        "objective" => "Eficiencia de OT",
        "description" => "Promedio de productividad de OT atendidas al mes",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "41694134",
        "objective" => "Rendimiento de combustible.",
        "description" => 0.92,
        "metric" => "Porcentaje",
        "goal" => 92,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "41694134",
        "objective" => "Fletes no cubiertos por negativa de conductor",
        "description" => "Flete no asumido por el conductor, más no perdido.",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "41694134",
        "objective" => "Número de incidencias por responsalidad del conductor",
        "description" => "0: No hubo incidencia \/ 1: Incidencia leve \/ 2:Incidencia moderada \/ 3: Incidencia grave",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 30,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "41694134",
        "objective" => "Promedio de conducción",
        "description" => "(Se da un un rango de cumplimiento entre 85 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "42606028",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidades",
        "goal" => 13,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "42606028",
        "objective" => "Reputation",
        "description" => "Meta regional score superior a 820 puntos",
        "metric" => "Número",
        "goal" => 820,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "42606028",
        "objective" => "Colocación de créditos",
        "metric" => "Unidad",
        "goal" => 5,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "42606028",
        "objective" => "Colocación de seguros",
        "metric" => "Unidad",
        "goal" => 5,
        "weight" => 10,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "02683938",
        "objective" => "Total de incoformidades en despachos",
        "description" => " % Total de facturas (despachos externos) vs % de incorfomidades",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 60,
        "isAscending" => true,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "02683938",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza",
        "metric" => "Minutos",
        "goal" => 3,
        "weight" => 40,
        "isAscending" => false,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "26729421",
        "objective" => "Cumplimiento promedio de registro de compras en el sistema (diario)",
        "description" => "Compras vs registro en sistema propio",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 30,
        "isAscending" => true,
        "categoria" => "Coordinador De Ventas"
      ],
      [
        "dni" => "26729421",
        "objective" => "Liquidaciones dentro del plazo estipulado",
        "description" => "Número de semanas con aplicaciones dentro del plazo",
        "metric" => "Unidad",
        "goal" => 4,
        "weight" => 30,
        "isAscending" => true,
        "categoria" => "Coordinador De Ventas"
      ],
      [
        "dni" => "26729421",
        "objective" => "Cumplimiento promedio de reportería en Dealer Portal (diario)",
        "description" => "Facturación vs reporte TICs ",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 30,
        "isAscending" => true,
        "categoria" => "Coordinador De Ventas"
      ],
      [
        "dni" => "26729421",
        "objective" => "Cumplimiento de envío de las solicitudes de nota de crédito en tiempo establecido",
        "metric" => "Días",
        "goal" => 3,
        "weight" => 10,
        "isAscending" => false,
        "categoria" => "Coordinador De Ventas"
      ],
      [
        "dni" => "004565680",
        "objective" => "Cumplimiento promedio de registro de compras en el sistema (diario)",
        "description" => "Compras vs registro en sistema propio",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 30,
        "isAscending" => true,
        "categoria" => "Coordinador De Ventas"
      ],
      [
        "dni" => "004565680",
        "objective" => "Liquidaciones dentro del plazo estipulado",
        "description" => "Número de semanas con aplicaciones dentro del plazo",
        "metric" => "Unidad",
        "goal" => 4,
        "weight" => 30,
        "isAscending" => true,
        "categoria" => "Coordinador De Ventas"
      ],
      [
        "dni" => "004565680",
        "objective" => "Cumplimiento promedio de reportería en Dealer Portal (diario)",
        "description" => "Facturación vs reporte TICs ",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 30,
        "isAscending" => true,
        "categoria" => "Coordinador De Ventas"
      ],
      [
        "dni" => "004565680",
        "objective" => "Cumplimiento de envío de las solicitudes de nota de crédito en tiempo establecido",
        "metric" => "Días",
        "goal" => 3,
        "weight" => 10,
        "isAscending" => false,
        "categoria" => "Coordinador De Ventas"
      ],
      [
        "dni" => "74443725",
        "objective" => "Entrega de cierres contables en tiempo de empresa a cargo",
        "metric" => "Días",
        "goal" => 5,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Asistente De Contabilidad"
      ],
      [
        "dni" => "74443725",
        "objective" => "Cero soles de multa por errores contables en la empresa a cargo",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 50,
        "isAscending" => false,
        "categoria" => "Asistente De Contabilidad"
      ],
      [
        "dni" => "02786294",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidades",
        "goal" => 7,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "02786294",
        "objective" => "Colocación de créditos",
        "metric" => "Unidad",
        "goal" => 1,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "41340413",
        "objective" => "Rendimiento de combustible.",
        "description" => 0.92,
        "metric" => "Porcentaje",
        "goal" => 92,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "41340413",
        "objective" => "Fletes no cubiertos por negativa de conductor",
        "description" => "Flete no asumido por el conductor, más no perdido.",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "41340413",
        "objective" => "Número de incidencias por responsalidad del conductor",
        "description" => "0: No hubo incidencia \/ 1: Incidencia leve \/ 2:Incidencia moderada \/ 3: Incidencia grave",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 30,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "41340413",
        "objective" => "Promedio de conducción",
        "description" => "(Se da un un rango de cumplimiento entre 85 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "74881133",
        "objective" => "Cumplir de paso vehicular",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 30,
        "isAscending" => true,
        "categoria" => "Asesor De Servicios"
      ],
      [
        "dni" => "74881133",
        "objective" => "Cumplimiento de meta general (facturación)",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Asesor De Servicios"
      ],
      [
        "dni" => "74881133",
        "objective" => "Reputation",
        "description" => "Meta regional score superior a 820 puntos",
        "metric" => "Número",
        "goal" => 820,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asesor De Servicios"
      ],
      [
        "dni" => "72703707",
        "objective" => "Promedio de vacaciones AP y GP",
        "metric" => "Unidad",
        "goal" => 24,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Partner De Gestión Humana"
      ],
      [
        "dni" => "72703707",
        "objective" => "Proceso dentro del plazo",
        "metric" => "Porcentaje",
        "goal" => 80,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Partner De Gestión Humana"
      ],
      [
        "dni" => "72703707",
        "objective" => "Nota Clima Laboral AP",
        "metric" => "Porcentaje",
        "goal" => 83,
        "weight" => 0,
        "isAscending" => true,
        "categoria" => "Partner De Gestión Humana"
      ],
      [
        "dni" => "72703707",
        "objective" => "Nota Clima Laboral GP",
        "metric" => "Porcentaje",
        "goal" => 80,
        "weight" => 0,
        "isAscending" => true,
        "categoria" => "Partner De Gestión Humana"
      ],
      [
        "dni" => "72703707",
        "objective" => "Nota de capacitaciones (PDG)",
        "metric" => "Número",
        "goal" => 100,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Partner De Gestión Humana"
      ],
      [
        "dni" => "72703707",
        "objective" => "Plazo de envio de boletas GP",
        "metric" => "Días",
        "goal" => 5,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Partner De Gestión Humana"
      ],
      [
        "dni" => "72703707",
        "objective" => "Plazo de envio de boletas AP",
        "metric" => "Días",
        "goal" => 7,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Partner De Gestión Humana"
      ],
      [
        "dni" => "40158039",
        "objective" => "Total de incoformidades en despachos en plataforma",
        "description" => " % Total de facturas vs % de incorfomidades (plataforma)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 60,
        "isAscending" => true,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "40158039",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza",
        "metric" => "Minutos",
        "goal" => 3,
        "weight" => 40,
        "isAscending" => false,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "42819192",
        "objective" => "Total de incoformidades en despachos",
        "description" => " % Total de facturas (despachos externos) vs % de incorfomidades",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 60,
        "isAscending" => true,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "42819192",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza",
        "metric" => "Minutos",
        "goal" => 3,
        "weight" => 40,
        "isAscending" => false,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "42140416",
        "objective" => "Total de incoformidades en despachos",
        "description" => " % Total de facturas vs % de incorfomidades (plataforma)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 60,
        "isAscending" => true,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "42140416",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza",
        "metric" => "Minutos",
        "goal" => 3,
        "weight" => 40,
        "isAscending" => false,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "40896485",
        "objective" => "Cumplimiento al 100% de la meta de venta establecida",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Jefe De Ventas Dp"
      ],
      [
        "dni" => "40896485",
        "objective" => "% de Efectividad de Cobranza",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Jefe De Ventas Dp"
      ],
      [
        "dni" => "40896485",
        "objective" => "Rentabilidad bruta mayor igual a 5.6%",
        "metric" => "Porcentaje",
        "goal" => 5.6,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Jefe De Ventas Dp"
      ],
      [
        "dni" => "40896485",
        "objective" => "Incremento de cartera",
        "metric" => "Unidad",
        "goal" => 4,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Jefe De Ventas Dp"
      ],
      [
        "dni" => "40896485",
        "objective" => "Mix de productos",
        "metric" => "Unidad",
        "goal" => 5,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Jefe De Ventas Dp"
      ],
      [
        "dni" => "42251736",
        "objective" => "Pagos ejecutados oportunamente",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Analista De Remuneraciones Y Compensaciones"
      ],
      [
        "dni" => "42251736",
        "objective" => "Ahorro financiero por pago de planilla",
        "description" => "Disminución anual de %",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 15,
        "isAscending" => false,
        "categoria" => "Analista De Remuneraciones Y Compensaciones"
      ],
      [
        "dni" => "42251736",
        "objective" => "Cumplimiento de cronograma Proyecto Planilla",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 15,
        "isAscending" => true,
        "categoria" => "Analista De Remuneraciones Y Compensaciones"
      ],
      [
        "dni" => "42251736",
        "objective" => "Presentación contable de planilla",
        "metric" => "Días",
        "goal" => 12,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Analista De Remuneraciones Y Compensaciones"
      ],
      [
        "dni" => "42251736",
        "objective" => "Pagos ejecutados correctamente",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Analista De Remuneraciones Y Compensaciones"
      ],
      [
        "dni" => "47678322",
        "objective" => "Meta de facturación mensual mayor o igual a 100%",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 100,
        "isAscending" => true,
        "categoria" => "Asesor De Repuestos"
      ],
      [
        "dni" => "73092833",
        "objective" => "Cumplimiento de inmatriculación",
        "description" => "Número de inmatriculaciones al mes vs facturación",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 100,
        "isAscending" => true,
        "categoria" => "Gestor De Inmatriculacion"
      ],
      [
        "dni" => "40521977",
        "objective" => "Rendimiento de combustible.",
        "description" => 0.92,
        "metric" => "Porcentaje",
        "goal" => 92,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "40521977",
        "objective" => "Fletes no cubiertos por negativa de conductor",
        "description" => "Flete no asumido por el conductor, más no perdido.",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "40521977",
        "objective" => "Número de incidencias por responsalidad del conductor",
        "description" => "0: No hubo incidencia \/ 1: Incidencia leve \/ 2:Incidencia moderada \/ 3: Incidencia grave",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 30,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "40521977",
        "objective" => "Promedio de conducción",
        "description" => "(Se da un un rango de cumplimiento entre 85 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "44205462",
        "objective" => "Total de incoformidades en despachos en plataforma",
        "description" => " % Total de facturas vs % de incorfomidades (plataforma)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 60,
        "isAscending" => true,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "44205462",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza",
        "metric" => "Minutos",
        "goal" => 3,
        "weight" => 40,
        "isAscending" => false,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "71900918",
        "objective" => "Reingresos de vehículos",
        "metric" => "Unidad",
        "goal" => 1,
        "weight" => 50,
        "isAscending" => false,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "71900918",
        "objective" => "Eficiencia de OT",
        "description" => "Promedio de productividad de OT atendidas al mes",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "70408777",
        "objective" => "Eficiencia de OT",
        "description" => "Promedio de productividad de OT por equipo en sede",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 30,
        "isAscending" => true,
        "categoria" => "Coordinador De Taller"
      ],
      [
        "dni" => "46635205",
        "objective" => "Eficiencia de OT > 70%",
        "metric" => "Unidad",
        "goal" => 2,
        "weight" => 70,
        "isAscending" => false,
        "categoria" => "Coordinador De Taller"
      ],
      [
        "dni" => "46635205",
        "objective" => "Eficiencia de OT",
        "description" => "Promedio de productividad de OT por equipo en sede",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 30,
        "isAscending" => true,
        "categoria" => "Coordinador De Taller"
      ],
      [
        "dni" => "40524259",
        "objective" => "Notas de crèdito a proveedores",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Jefe De Administracion"
      ],
      [
        "dni" => "40524259",
        "objective" => "Pago a proveedores",
        "metric" => "Porcentaje",
        "goal" => 0,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Jefe De Administracion"
      ],
      [
        "dni" => "40524259",
        "objective" => "Gestión de compras",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Jefe De Administracion"
      ],
      [
        "dni" => "40524259",
        "objective" => "Efectividad de liquidaciones",
        "description" => "% de reconocimeinto de la liquidacion / total de liquidaciones registradas",
        "metric" => "Número",
        "goal" => 100,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Jefe De Administracion"
      ],
      [
        "dni" => "42401576",
        "objective" => "Total de incoformidades en despachos",
        "description" => " % Total de facturas (despachos externos) vs % de incorfomidades",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 60,
        "isAscending" => true,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "42401576",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza",
        "metric" => "Minutos",
        "goal" => 3,
        "weight" => 40,
        "isAscending" => false,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "46815084",
        "objective" => "Cumplimiento mensual de los límites de velocidad en ruta",
        "description" => "Total de viajes por despachos del mes no excedan los 80km\/hr. (Control GPS)",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "46815084",
        "objective" => "Cero multas de tránsito",
        "description" => "Por infringir las normas de tránsito en el desarrollo de sus funciones",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "46815084",
        "objective" => "Promedio mensual de ingreso",
        "description" => "Promedio mensual de marcaciones de ingreso del mes según biométrico.",
        "metric" => "Minutos",
        "goal" => 3,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "46815084",
        "objective" => "Cumplimiento de inventario de sede al 100%",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 0,
        "isAscending" => true,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "46815084",
        "objective" => "Cero incidencias en la ruta asignada (robos, choques,etc)",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "71908990",
        "objective" => "Total de incoformidades en despachos",
        "description" => " % Total de facturas vs % de incorfomidades (plataforma)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 60,
        "isAscending" => true,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "71908990",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza",
        "metric" => "Minutos",
        "goal" => 3,
        "weight" => 40,
        "isAscending" => false,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "75848561",
        "objective" => "Eficiencia de OT",
        "description" => "Promedio de productividad de OT atendidas al mes",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Tecnico Electricista"
      ],
      [
        "dni" => "75848561",
        "objective" => "Mantenimentos correctivos de emergencia",
        "description" => "(Auxilios Mecánicos), no supere el 4% mensual de la flota",
        "metric" => "Porcentaje",
        "goal" => 4,
        "weight" => 50,
        "isAscending" => false,
        "categoria" => "Tecnico Electricista"
      ],
      [
        "dni" => "45704839",
        "objective" => "Reputation",
        "description" => "Meta regional score superior a 820 puntos",
        "metric" => "Número",
        "goal" => 820,
        "weight" => 15,
        "isAscending" => true,
        "categoria" => "Gerente De Post Venta"
      ],
      [
        "dni" => "45704839",
        "objective" => "Margen bruto mensual",
        "description" => "Promedio de las 5 sedes",
        "metric" => "Porcentaje",
        "goal" => 55,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Gerente De Post Venta"
      ],
      [
        "dni" => "45704839",
        "objective" => "Cumplimiento del volumen de ventas de post venta",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Gerente De Post Venta"
      ],
      [
        "dni" => "45704839",
        "objective" => "Tasa de reclamos relacionados al servicio según libro de reclamaciones",
        "metric" => "Unidad",
        "goal" => 3,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Gerente De Post Venta"
      ],
      [
        "dni" => "45704839",
        "objective" => "Cumplimiento de rotacion de stock",
        "metric" => "Días",
        "goal" => 30,
        "weight" => 15,
        "isAscending" => false,
        "categoria" => "Gerente De Post Venta"
      ],
      [
        "dni" => "72211890",
        "objective" => "Nivel de servicio",
        "description" => "Cantidad de repuestos para atención de clientes vs demanda",
        "metric" => "Porcentaje",
        "goal" => 90,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Jefe De Almacen"
      ],
      [
        "dni" => "72211890",
        "objective" => "Control de Inventario",
        "description" => "Resultado de inventario físico por sede vs sistema",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Jefe De Almacen"
      ],
      [
        "dni" => "72211890",
        "objective" => "Días de stock de material",
        "metric" => "Días",
        "goal" => 30,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Jefe De Almacen"
      ],
      [
        "dni" => "09994093",
        "objective" => "Notas de crèdito a proveedores",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Jefe De Administracion"
      ],
      [
        "dni" => "09994093",
        "objective" => "Pago a proveedores",
        "metric" => "Porcentaje",
        "goal" => 0,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Jefe De Administracion"
      ],
      [
        "dni" => "09994093",
        "objective" => "Gestión de compras",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Jefe De Administracion"
      ],
      [
        "dni" => "09994093",
        "objective" => "Efectividad de liquidaciones",
        "description" => "% de reconocimeinto de la liquidacion / total de liquidaciones registradas",
        "metric" => "Número",
        "goal" => 100,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Jefe De Administracion"
      ],
      [
        "dni" => "46906293",
        "objective" => "Cumplimiento promedio de registro de compras en el sistema (diario)",
        "description" => "Compras vs registro en sistema propio",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 30,
        "isAscending" => true,
        "categoria" => "Coordinador De Ventas"
      ],
      [
        "dni" => "46906293",
        "objective" => "Liquidaciones dentro del plazo estipulado",
        "description" => "Número de semanas con aplicaciones dentro del plazo",
        "metric" => "Unidad",
        "goal" => 4,
        "weight" => 30,
        "isAscending" => true,
        "categoria" => "Coordinador De Ventas"
      ],
      [
        "dni" => "46906293",
        "objective" => "Cumplimiento promedio de reportería en Dealer Portal (diario)",
        "description" => "Facturación vs reporte TICs ",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 30,
        "isAscending" => true,
        "categoria" => "Coordinador De Ventas"
      ],
      [
        "dni" => "46906293",
        "objective" => "Cumplimiento de envío de las solicitudes de nota de crédito en tiempo establecido",
        "metric" => "Días",
        "goal" => 3,
        "weight" => 10,
        "isAscending" => false,
        "categoria" => "Coordinador De Ventas"
      ],
      [
        "dni" => "16662152",
        "objective" => "Rendimiento de combustible.",
        "description" => 0.92,
        "metric" => "Porcentaje",
        "goal" => 92,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16662152",
        "objective" => "Fletes no cubiertos por negativa de conductor",
        "description" => "Flete no asumido por el conductor, más no perdido.",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16662152",
        "objective" => "Número de incidencias por responsalidad del conductor",
        "description" => "0: No hubo incidencia \/ 1: Incidencia leve \/ 2:Incidencia moderada \/ 3: Incidencia grave",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 30,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16662152",
        "objective" => "Promedio de conducción",
        "description" => "(Se da un un rango de cumplimiento entre 85 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "72811912",
        "objective" => "Cumplimiento de facturación de las 4 sedes",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 100,
        "isAscending" => true,
        "categoria" => "Jefe de Repuestos"
      ],
      [
        "dni" => "40654637",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidades",
        "goal" => 13,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "40654637",
        "objective" => "Reputation",
        "description" => "Meta regional score superior a 820 puntos",
        "metric" => "Número",
        "goal" => 820,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "40654637",
        "objective" => "Colocación de créditos",
        "metric" => "Unidad",
        "goal" => 5,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "40654637",
        "objective" => "Colocación de seguros",
        "metric" => "Unidad",
        "goal" => 5,
        "weight" => 10,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "40803935",
        "objective" => "Cumplimiento de presupuesto comercial",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "40803935",
        "objective" => "% de Efectividad de Cobranza",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "40803935",
        "objective" => "Mix de productos",
        "metric" => "Unidad",
        "goal" => 5,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "40803935",
        "objective" => "Cliente Nuevo al mes",
        "metric" => "Unidad",
        "goal" => 2,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "45838857",
        "objective" => "Rendimiento de combustible.",
        "description" => 0.92,
        "metric" => "Porcentaje",
        "goal" => 92,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "45838857",
        "objective" => "Fletes no cubiertos por negativa de conductor",
        "description" => "Flete no asumido por el conductor, más no perdido.",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "45838857",
        "objective" => "Número de incidencias por responsalidad del conductor",
        "description" => "0: No hubo incidencia \/ 1: Incidencia leve \/ 2:Incidencia moderada \/ 3: Incidencia grave",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 30,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "45838857",
        "objective" => "Promedio de conducción",
        "description" => "(Se da un un rango de cumplimiento entre 85 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "71600393",
        "objective" => "Stock saludable de un mes por sede",
        "metric" => "Unidad",
        "goal" => 1,
        "weight" => 100,
        "isAscending" => false,
        "categoria" => "Asistente De Almacén Ap"
      ],
      [
        "dni" => "47189021",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidades",
        "goal" => 10,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "47189021",
        "objective" => "Reputation",
        "description" => "Meta regional score superior a 820 puntos",
        "metric" => "Número",
        "goal" => 820,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "47189021",
        "objective" => "Colocación de créditos",
        "metric" => "Unidad",
        "goal" => 4,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "47189021",
        "objective" => "Colocación de seguros",
        "metric" => "Unidad",
        "goal" => 4,
        "weight" => 10,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "44868919",
        "objective" => "Inventarios mensual sin diferencias",
        "description" => "Inventario físico vs sistema",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Asistente De Logistica"
      ],
      [
        "dni" => "44868919",
        "objective" => "Control del costo de Suministros (ingresos y salidas)",
        "metric" => "Soles",
        "goal" => 115000,
        "weight" => 50,
        "isAscending" => false,
        "categoria" => "Asistente De Logistica"
      ],
      [
        "dni" => "43945322",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidades",
        "goal" => 8,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "43945322",
        "objective" => "Reputation",
        "description" => "Meta regional score superior a 820 puntos",
        "metric" => "Número",
        "goal" => 820,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "43945322",
        "objective" => "Colocación de créditos",
        "metric" => "Unidad",
        "goal" => 3,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "43945322",
        "objective" => "Colocación de seguros",
        "metric" => "Unidad",
        "goal" => 3,
        "weight" => 10,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "43579923",
        "objective" => "Cumplimiento de margen neto acumulado",
        "metric" => "Número",
        "goal" => 100,
        "weight" => 16.7,
        "isAscending" => true,
        "categoria" => "Gerente De Negocio Ap"
      ],
      [
        "dni" => "43579923",
        "objective" => "% de market share AP",
        "metric" => "Porcentaje",
        "goal" => 23.2,
        "weight" => 16.7,
        "isAscending" => true,
        "categoria" => "Gerente De Negocio Ap"
      ],
      [
        "dni" => "43579923",
        "objective" => "Nota de Clima Laboral",
        "metric" => "Porcentaje",
        "goal" => 83,
        "weight" => 0,
        "isAscending" => true,
        "categoria" => "Gerente De Negocio Ap"
      ],
      [
        "dni" => "43579923",
        "objective" => "Cumplimiento de margen neto de PV",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 16.7,
        "isAscending" => true,
        "categoria" => "Gerente De Negocio Ap"
      ],
      [
        "dni" => "43579923",
        "objective" => "Días de stock de vehículos",
        "metric" => "Días",
        "goal" => 150,
        "weight" => 16.7,
        "isAscending" => false,
        "categoria" => "Gerente De Negocio Ap"
      ],
      [
        "dni" => "43579923",
        "objective" => "Disminuir procesos Indecopi por semestre VS 2025",
        "metric" => "Porcentaje",
        "goal" => 15,
        "weight" => 16.7,
        "isAscending" => true,
        "categoria" => "Gerente De Negocio Ap"
      ],
      [
        "dni" => "43579923",
        "objective" => "Días de stock de repuestos",
        "metric" => "Días",
        "goal" => 30,
        "weight" => 16.5,
        "isAscending" => false,
        "categoria" => "Gerente De Negocio Ap"
      ],
      [
        "dni" => "45488727",
        "objective" => "Promedio de cumplimiento de presupuesto mensual de AP, DP y TP",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 30,
        "isAscending" => true,
        "categoria" => "Gerente De Gestión Humana"
      ],
      [
        "dni" => "45488727",
        "objective" => "Tiempo de reclutamiento",
        "metric" => "Porcentaje",
        "goal" => 80,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Gerente De Gestión Humana"
      ],
      [
        "dni" => "45488727",
        "objective" => "Nota de Clima Laboral",
        "description" => "Nota global de clima de las 4 empresas",
        "metric" => "Porcentaje",
        "goal" => 80,
        "weight" => 30,
        "isAscending" => true,
        "categoria" => "Gerente De Gestión Humana"
      ],
      [
        "dni" => "45488727",
        "objective" => "Asegurar pagos correctos",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Gerente De Gestión Humana"
      ],
      [
        "dni" => "02855107",
        "objective" => "Cero soles de multa por errores contables en la empresa a cargo",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 33,
        "isAscending" => false,
        "categoria" => "Asistente De Contabilidad"
      ],
      [
        "dni" => "02855107",
        "objective" => "Cero errores en el registro de información contable de manera mensual",
        "metric" => "Errores",
        "goal" => 2,
        "weight" => 33,
        "isAscending" => false,
        "categoria" => "Asistente De Contabilidad"
      ],
      [
        "dni" => "02855107",
        "objective" => "Entrega de cierre contable mensual integral (en los primeros 12 días calendario)",
        "metric" => "Días",
        "goal" => 12,
        "weight" => 34,
        "isAscending" => false,
        "categoria" => "Asistente De Contabilidad"
      ],
      [
        "dni" => "42082941",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidades",
        "goal" => 21,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Jefe De Ventas Ap"
      ],
      [
        "dni" => "42082941",
        "objective" => "Reputation",
        "description" => "Meta regional score superior a 820 puntos",
        "metric" => "Número",
        "goal" => 820,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Jefe De Ventas Ap"
      ],
      [
        "dni" => "42082941",
        "objective" => "Meta en seguros",
        "metric" => "Número",
        "goal" => 6,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Jefe De Ventas Ap"
      ],
      [
        "dni" => "42082941",
        "objective" => "Meta en créditos",
        "metric" => "Número",
        "goal" => 6,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Jefe De Ventas Ap"
      ],
      [
        "dni" => "42082941",
        "objective" => "Alcance de Mix de Marca",
        "metric" => "Unidad",
        "goal" => 4,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Jefe De Ventas Ap"
      ],
      [
        "dni" => "72210478",
        "objective" => "Stock saludable de un mes por sede",
        "metric" => "Unidad",
        "goal" => 1,
        "weight" => 100,
        "isAscending" => false,
        "categoria" => "Asistente De Almacén Ap"
      ],
      [
        "dni" => "42298543",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidades",
        "goal" => 13,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "42298543",
        "objective" => "Colocación de créditos",
        "metric" => "Unidad",
        "goal" => 5,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "42298543",
        "objective" => "Reputation",
        "description" => "Meta regional score superior a 820 puntos",
        "metric" => "Número",
        "goal" => 820,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "42298543",
        "objective" => "Colocación de seguros",
        "metric" => "Unidad",
        "goal" => 5,
        "weight" => 10,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "17861917",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidades",
        "goal" => 13,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "17861917",
        "objective" => "Reputation",
        "description" => "Meta regional score superior a 820 puntos",
        "metric" => "Número",
        "goal" => 820,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "17861917",
        "objective" => "Colocación de créditos",
        "metric" => "Unidad",
        "goal" => 5,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "17861917",
        "objective" => "Colocación de seguros",
        "metric" => "Unidad",
        "goal" => 5,
        "weight" => 10,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "44579660",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidades",
        "goal" => 6,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "44579660",
        "objective" => "Reputation",
        "description" => "Meta regional score superior a 820 puntos",
        "metric" => "Número",
        "goal" => 820,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "44579660",
        "objective" => "Colocación de créditos",
        "metric" => "Unidad",
        "goal" => 2,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "44579660",
        "objective" => "Colocación de seguros",
        "metric" => "Unidad",
        "goal" => 2,
        "weight" => 10,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "71622182",
        "objective" => "Cumplimiento de la facturación de taller mas un incremento adicional del 2%",
        "metric" => "Porcentaje",
        "goal" => 102,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Jefe De Taller"
      ],
      [
        "dni" => "71622182",
        "objective" => "Cumplimiento de OT>100%",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 30,
        "isAscending" => true,
        "categoria" => "Jefe De Taller"
      ],
      [
        "dni" => "71622182",
        "objective" => "Tasa de reclamos relacionados al servicio según libro de reclamaciones",
        "metric" => "Porcentaje",
        "goal" => 0,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Jefe De Taller"
      ],
      [
        "dni" => "48493374",
        "objective" => "Tiempo promedio de respuesta a cotizaciones",
        "metric" => "Días",
        "goal" => 1,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Gestor Comercial"
      ],
      [
        "dni" => "48493374",
        "objective" => "Nuevos clientes captados o propuestos",
        "metric" => "Número",
        "goal" => 2,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Gestor Comercial"
      ],
      [
        "dni" => "48493374",
        "objective" => "Actualización de tarifario",
        "description" => "Respetar el % mínimo 13%",
        "metric" => "Porcentaje",
        "goal" => 13,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Gestor Comercial"
      ],
      [
        "dni" => "48493374",
        "objective" => "Actualización de aplicativos de sistema de clientes",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Gestor Comercial"
      ],
      [
        "dni" => "48493374",
        "objective" => "Soporte a programación",
        "description" => "Servicios sin errores",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Gestor Comercial"
      ],
      [
        "dni" => "46720670",
        "objective" => "Programación de flota",
        "metric" => "Porcentaje",
        "goal" => 95,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Gestor Comercial"
      ],
      [
        "dni" => "46720670",
        "objective" => "Incremento en ventas",
        "metric" => "Porcentaje",
        "goal" => 5,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Gestor Comercial"
      ],
      [
        "dni" => "46720670",
        "objective" => "Rentabilidad",
        "metric" => "Porcentaje",
        "goal" => 15,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Gestor Comercial"
      ],
      [
        "dni" => "46720670",
        "objective" => "Propuesta de nuevos clientes",
        "metric" => "Número",
        "goal" => 2,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Gestor Comercial"
      ],
      [
        "dni" => "45801393",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidades",
        "goal" => 47,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Jefe De Ventas Ap"
      ],
      [
        "dni" => "45801393",
        "objective" => "Reputation",
        "description" => "Meta regional score superior a 820 puntos",
        "metric" => "Número",
        "goal" => 820,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Jefe De Ventas Ap"
      ],
      [
        "dni" => "45801393",
        "objective" => "Meta en seguros",
        "metric" => "Número",
        "goal" => 18,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Jefe De Ventas Ap"
      ],
      [
        "dni" => "45801393",
        "objective" => "Meta en créditos",
        "metric" => "Número",
        "goal" => 18,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Jefe De Ventas Ap"
      ],
      [
        "dni" => "45801393",
        "objective" => "Alcance de Mix de Marca",
        "metric" => "Unidad",
        "goal" => 5,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Jefe De Ventas Ap"
      ],
      [
        "dni" => "71908990",
        "objective" => "Total de incoformidades en despachos",
        "description" => " % Total de facturas (despachos externos) vs % de incorfomidades",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 60,
        "isAscending" => true,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "45746188",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza",
        "metric" => "Minutos",
        "goal" => 3,
        "weight" => 40,
        "isAscending" => false,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "76009888",
        "objective" => "Entrega de anexos de cuentas para planilla",
        "metric" => "Días",
        "goal" => 10,
        "weight" => 32,
        "isAscending" => false,
        "categoria" => "Asistente de Remuneraciones y Compensaciones"
      ],
      [
        "dni" => "72318704",
        "objective" => "Cero soles de multa por errores contables en la empresa a cargo",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 30,
        "isAscending" => false,
        "categoria" => "Analista De Contabilidad TP"
      ],
      [
        "dni" => "72318704",
        "objective" => "Presentación de DDJJ mensuales a SUNAT",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 30,
        "isAscending" => true,
        "categoria" => "Analista De Contabilidad TP"
      ],
      [
        "dni" => "72318704",
        "objective" => "Entrega de cierres contables en tiempo de empresa a cargo",
        "metric" => "Días",
        "goal" => 5,
        "weight" => 40,
        "isAscending" => false,
        "categoria" => "Analista De Contabilidad TP"
      ],
      [
        "dni" => "48509128",
        "objective" => "Meta de facturación mensual mayor o igual a 100%",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 100,
        "isAscending" => true,
        "categoria" => "Asesor De Repuestos"
      ],
      [
        "dni" => "16475507",
        "objective" => "Rendimiento de combustible.",
        "description" => 0.92,
        "metric" => "Porcentaje",
        "goal" => 92,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16475507",
        "objective" => "Fletes no cubiertos por negativa de conductor",
        "description" => "Flete no asumido por el conductor, más no perdido.",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16475507",
        "objective" => "Número de incidencias por responsalidad del conductor",
        "description" => "0: No hubo incidencia \/ 1: Incidencia leve \/ 2:Incidencia moderada \/ 3: Incidencia grave",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 30,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16475507",
        "objective" => "Promedio de conducción",
        "description" => "(Se da un un rango de cumplimiento entre 85 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "76311394",
        "objective" => "Cumplimiento de cronograma de Planillas",
        "metric" => "Porcentaje",
        "goal" => 95,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Analista En Proyectos De Gestion Humana"
      ],
      [
        "dni" => "76311394",
        "objective" => "Cumplimiento de procesos de Onboarding e inducción",
        "metric" => "Porcentaje",
        "goal" => 98,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Analista En Proyectos De Gestion Humana"
      ],
      [
        "dni" => "76311394",
        "objective" => "Cumplimiento de Proyecto Clima Laboral 2026",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Analista En Proyectos De Gestion Humana"
      ],
      [
        "dni" => "76311394",
        "objective" => "Adherencia de desempeño",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Analista En Proyectos De Gestion Humana"
      ],
      [
        "dni" => "76311394",
        "objective" => "Satisfacción de los procesos de onboarding",
        "metric" => "Número",
        "goal" => 3.5,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Analista En Proyectos De Gestion Humana"
      ],
      [
        "dni" => "46891277",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidades",
        "goal" => 6,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "46891277",
        "objective" => "Reputation",
        "description" => "Meta regional score superior a 820 puntos",
        "metric" => "Número",
        "goal" => 820,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "46891277",
        "objective" => "Colocación de créditos",
        "metric" => "Unidad",
        "goal" => 2,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "46891277",
        "objective" => "Colocación de seguros",
        "metric" => "Unidad",
        "goal" => 2,
        "weight" => 10,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "41998623",
        "objective" => "Cumplimiento al 100% de la meta de venta establecida",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Jefe De Ventas Dp"
      ],
      [
        "dni" => "41998623",
        "objective" => "% de Efectividad de Cobranza",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Jefe De Ventas Dp"
      ],
      [
        "dni" => "41998623",
        "objective" => "Rentabilidad bruta mayor igual a 5.6%",
        "metric" => "Porcentaje",
        "goal" => 5.6,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Jefe De Ventas Dp"
      ],
      [
        "dni" => "41998623",
        "objective" => "Incremento de cartera",
        "metric" => "Unidad",
        "goal" => 4,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Jefe De Ventas Dp"
      ],
      [
        "dni" => "41998623",
        "objective" => "Mix de productos",
        "metric" => "Unidad",
        "goal" => 5,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Jefe De Ventas Dp"
      ],
      [
        "dni" => "02851958",
        "objective" => "Total de incoformidades en despachos",
        "description" => " % Total de facturas (despachos externos) vs % de incorfomidades",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 60,
        "isAscending" => true,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "02851958",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza",
        "metric" => "Minutos",
        "goal" => 3,
        "weight" => 40,
        "isAscending" => false,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "44249939",
        "objective" => "Total de incoformidades en despachos",
        "description" => " % Total de facturas (despachos externos) vs % de incorfomidades",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 60,
        "isAscending" => true,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "44249939",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza",
        "metric" => "Minutos",
        "goal" => 3,
        "weight" => 40,
        "isAscending" => false,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "47057666",
        "objective" => "Control del costo combustible",
        "metric" => "Soles",
        "goal" => 1.2,
        "weight" => 30,
        "isAscending" => false,
        "categoria" => "Asistente De Operaciones"
      ],
      [
        "dni" => "47057666",
        "objective" => "Rendimiento promedio de combustible",
        "description" => "No debe superar 8.6 km\/gln",
        "metric" => "Soles",
        "goal" => 8.6,
        "weight" => 30,
        "isAscending" => false,
        "categoria" => "Asistente De Operaciones"
      ],
      [
        "dni" => "47057666",
        "objective" => "Control de Tickets de consumo",
        "description" => "Número de tickets físicos o digitales vs registro del ISC",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Asistente De Operaciones"
      ],
      [
        "dni" => "47057666",
        "objective" => "Accidente y\/o Siniestros",
        "description" => "Por responsabilidad el equipo de operaciones y mantenimiento",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 15,
        "isAscending" => false,
        "categoria" => "Asistente De Operaciones"
      ],
      [
        "dni" => "43284061",
        "objective" => "Promedio de cumplimiento de presupuesto mensual de AP, DP y TP",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 100,
        "isAscending" => true,
        "categoria" => "Jefe De Legal"
      ],
      [
        "dni" => "46343351",
        "objective" => "Cumplimiento de la programaciòn",
        "description" => " (Servicios entregados a tiempo\/total de servicios programados)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Asistente De Seguimiento Y Monitoreo"
      ],
      [
        "dni" => "46343351",
        "objective" => "Errores en llenado de guías",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Asistente De Seguimiento Y Monitoreo"
      ],
      [
        "dni" => "46343351",
        "objective" => "Robos en tracto camiones",
        "description" => "Por falta de seguimiento y monitoreo",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Asistente De Seguimiento Y Monitoreo"
      ],
      [
        "dni" => "46343351",
        "objective" => "Accidente y\/o Siniestros",
        "description" => "Por responsabilidad de seguimiento y monitoreo según nivel",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Asistente De Seguimiento Y Monitoreo"
      ],
      [
        "dni" => "73809915",
        "objective" => "Total de incoformidades en despachos",
        "description" => " % Total de facturas (despachos externos e internos) vs % de incorfomidades",
        "metric" => "Porcentaje",
        "goal" => 90,
        "weight" => 60,
        "isAscending" => true,
        "categoria" => "Supervisor De Almacen"
      ],
      [
        "dni" => "73809915",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza",
        "metric" => "Minutos",
        "goal" => 3,
        "weight" => 40,
        "isAscending" => false,
        "categoria" => "Supervisor De Almacen"
      ],
      [
        "dni" => "19324383",
        "objective" => "Rendimiento de combustible.",
        "description" => 0.92,
        "metric" => "Porcentaje",
        "goal" => 92,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "19324383",
        "objective" => "Fletes no cubiertos por negativa de conductor",
        "description" => "Flete no asumido por el conductor, más no perdido.",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "19324383",
        "objective" => "Número de incidencias por responsalidad del conductor",
        "description" => "0: No hubo incidencia \/ 1: Incidencia leve \/ 2:Incidencia moderada \/ 3: Incidencia grave",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 30,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "19324383",
        "objective" => "Promedio de conducción",
        "description" => "(Se da un un rango de cumplimiento entre 85 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "48691128",
        "objective" => "Total de incoformidades en despachos",
        "description" => " % Total de facturas (despachos externos e internos) vs % de incorfomidades",
        "metric" => "Porcentaje",
        "goal" => 90,
        "weight" => 60,
        "isAscending" => true,
        "categoria" => "Supervisor De Almacen"
      ],
      [
        "dni" => "48691128",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza",
        "metric" => "Minutos",
        "goal" => 3,
        "weight" => 40,
        "isAscending" => false,
        "categoria" => "Supervisor De Almacen"
      ],
      [
        "dni" => "48299990",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidades",
        "goal" => 9,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "48299990",
        "objective" => "Reputation",
        "description" => "Meta regional score superior a 820 puntos",
        "metric" => "Número",
        "goal" => 820,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "48299990",
        "objective" => "Colocación de créditos",
        "metric" => "Unidad",
        "goal" => 3,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "48299990",
        "objective" => "Colocación de seguros",
        "metric" => "Unidad",
        "goal" => 3,
        "weight" => 10,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "44498939",
        "objective" => "Cumplimiento mensual de los límites de velocidad en ruta",
        "description" => "Total de viajes por despachos del mes no excedan los 80km\/hr. (Control GPS)",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "44498939",
        "objective" => "Cero multas de tránsito",
        "description" => "Por infringir las normas de tránsito en el desarrollo de sus funciones",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "44498939",
        "objective" => "Promedio mensual de ingreso",
        "description" => "Promedio mensual de marcaciones de ingreso del mes según biométrico.",
        "metric" => "Minutos",
        "goal" => 3,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "44498939",
        "objective" => "Cumplimiento de inventario de sede al 100%",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 0,
        "isAscending" => true,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "44498939",
        "objective" => "Cero incidencias en la ruta asignada (robos, choques,etc)",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "32739469",
        "objective" => "Satisfacción de capacitaciones según cronograma",
        "description" => "Según encuesta con escala del 1-4",
        "metric" => "Número",
        "goal" => 3.5,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Supervisor De Seguridad Patrimonial"
      ],
      [
        "dni" => "32739469",
        "objective" => "Resultado de encuesta del personal cesado",
        "description" => "Según encuesta con escala del 1-4",
        "metric" => "Número",
        "goal" => 4,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Supervisor De Seguridad Patrimonial"
      ],
      [
        "dni" => "32739469",
        "objective" => "Nota de Clima Laboral",
        "description" => "Nota de Clima Laboral de todo el equipo de seguridad.",
        "metric" => "Porcentaje",
        "goal" => 80,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Supervisor De Seguridad Patrimonial"
      ],
      [
        "dni" => "32739469",
        "objective" => "Impacto en siniestros",
        "description" => "Disminución del daño causado en el siniestro",
        "metric" => "Porcentaje",
        "goal" => 0,
        "weight" => 40,
        "isAscending" => false,
        "categoria" => "Supervisor De Seguridad Patrimonial"
      ],
      [
        "dni" => "45620643",
        "objective" => "Cumplimiento de cronogramas de desarrollo y mantenimiento de software (Milla, Quiter, Dynamic )",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 30,
        "isAscending" => true,
        "categoria" => "Jefe De TICs"
      ],
      [
        "dni" => "45620643",
        "objective" => "Efectividad en el tiempo de atención en el servicio",
        "metric" => "Minutos",
        "goal" => 30,
        "weight" => 30,
        "isAscending" => false,
        "categoria" => "Jefe De TICs"
      ],
      [
        "dni" => "45620643",
        "objective" => "Calidad de servicio del área",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 30,
        "isAscending" => true,
        "categoria" => "Jefe De TICs"
      ],
      [
        "dni" => "45620643",
        "objective" => "Nota de Clima Laboral",
        "description" => "Nota de Clima Laboral del equipo TICs",
        "metric" => "Porcentaje",
        "goal" => 80,
        "weight" => 0,
        "isAscending" => true,
        "categoria" => "Jefe De TICs"
      ],
      [
        "dni" => "45620643",
        "objective" => "Promedio de cumplimiento de presupuesto mensual de AP, DP y TP",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 10,
        "isAscending" => true,
        "categoria" => "Jefe De TICs"
      ],
      [
        "dni" => "42116951",
        "objective" => "Reputation",
        "description" => "Meta regional score superior a 820 puntos",
        "metric" => "Número",
        "goal" => 820,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Jefe De Ventas Ap"
      ],
      [
        "dni" => "42116951",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidades",
        "goal" => 60,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Jefe De Ventas Ap"
      ],
      [
        "dni" => "42116951",
        "objective" => "Meta en seguros",
        "metric" => "Número",
        "goal" => 23,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Jefe De Ventas Ap"
      ],
      [
        "dni" => "42116951",
        "objective" => "Meta en créditos",
        "metric" => "Número",
        "goal" => 23,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Jefe De Ventas Ap"
      ],
      [
        "dni" => "42116951",
        "objective" => "Alcance de Mix de Marca",
        "metric" => "Unidad",
        "goal" => 3,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Jefe De Ventas Ap"
      ],
      [
        "dni" => "48493314",
        "objective" => "Cero soles de multa por errores contables en la empresa a cargo",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Asistente De Contabilidad"
      ],
      [
        "dni" => "48493314",
        "objective" => "Exactitud en registros contables",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 50,
        "isAscending" => false,
        "categoria" => "Asistente De Contabilidad"
      ],
      [
        "dni" => "48493314",
        "objective" => "Entrega de cierres contables en tiempo de empresa a cargo",
        "metric" => "Días",
        "goal" => 5,
        "weight" => 30,
        "isAscending" => true,
        "categoria" => "Asistente De Contabilidad"
      ],
      [
        "dni" => "16589869",
        "objective" => "Cumplimiento de presupuesto comercial",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "16589869",
        "objective" => "% de Efectividad de Cobranza",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "16589869",
        "objective" => "Mix de productos",
        "metric" => "Unidad",
        "goal" => 5,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "16589869",
        "objective" => "Cliente Nuevo al mes",
        "metric" => "Unidad",
        "goal" => 2,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "77499375",
        "objective" => "Total de incoformidades en despachos",
        "description" => " % Total de facturas (despachos externos) vs % de incorfomidades",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 60,
        "isAscending" => true,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "77499375",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza",
        "metric" => "Minutos",
        "goal" => 3,
        "weight" => 40,
        "isAscending" => false,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "76010852",
        "objective" => "Total de incoformidades en despachos",
        "description" => " % Total de facturas (despachos externos) vs % de incorfomidades",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 60,
        "isAscending" => true,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "76010852",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza",
        "metric" => "Minutos",
        "goal" => 3,
        "weight" => 40,
        "isAscending" => false,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "16734135",
        "objective" => "Rendimiento de combustible.",
        "description" => 0.92,
        "metric" => "Porcentaje",
        "goal" => 92,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16734135",
        "objective" => "Fletes no cubiertos por negativa de conductor",
        "description" => "Flete no asumido por el conductor, más no perdido.",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16734135",
        "objective" => "Número de incidencias por responsalidad del conductor",
        "description" => "0: No hubo incidencia \/ 1: Incidencia leve \/ 2:Incidencia moderada \/ 3: Incidencia grave",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 30,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16734135",
        "objective" => "Promedio de conducción",
        "description" => "(Se da un un rango de cumplimiento entre 85 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "40831827",
        "objective" => "Rendimiento de combustible.",
        "description" => 0.92,
        "metric" => "Porcentaje",
        "goal" => 92,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "40831827",
        "objective" => "Fletes no cubiertos por negativa de conductor",
        "description" => "Flete no asumido por el conductor, más no perdido.",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "40831827",
        "objective" => "Número de incidencias por responsalidad del conductor",
        "description" => "0: No hubo incidencia \/ 1: Incidencia leve \/ 2:Incidencia moderada \/ 3: Incidencia grave",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 30,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "40831827",
        "objective" => "Promedio de conducción",
        "description" => "(Se da un un rango de cumplimiento entre 85 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "42150787",
        "objective" => "Alcanzar numero mínimo de fletes al mes",
        "metric" => "Viajes",
        "goal" => 380,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Gerente De Negocio Tp"
      ],
      [
        "dni" => "42150787",
        "objective" => "Nota de Clima Laboral",
        "metric" => "Porcentaje",
        "goal" => 80,
        "weight" => 0,
        "isAscending" => true,
        "categoria" => "Gerente De Negocio Tp"
      ],
      [
        "dni" => "42150787",
        "objective" => "Unidades inactivas por falta de conductor",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Gerente De Negocio Tp"
      ],
      [
        "dni" => "42150787",
        "objective" => "Disminuir gasto por siniestros",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Gerente De Negocio Tp"
      ],
      [
        "dni" => "42150787",
        "objective" => "Cumplimiento de margen neto acumulado del canal de cisternas",
        "metric" => "Soles",
        "goal" => 100,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Gerente De Negocio Tp"
      ],
      [
        "dni" => "42150787",
        "objective" => "Cumplimiento de margen neto acumulado total (sin ventas de activos)",
        "metric" => "Soles",
        "goal" => 100,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Gerente De Negocio Tp"
      ],
      [
        "dni" => "08870187",
        "objective" => "Cumplimiento de margen neto acumulado",
        "metric" => "Porcentaje",
        "goal" => 1.2,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Gerente General"
      ],
      [
        "dni" => "08870187",
        "objective" => "Nota de Clima Laboral",
        "metric" => "Porcentaje",
        "goal" => 80,
        "weight" => 0,
        "isAscending" => true,
        "categoria" => "Gerente General"
      ],
      [
        "dni" => "08870187",
        "objective" => "Días de stock Koplast",
        "metric" => "Días",
        "goal" => 60,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Gerente General"
      ],
      [
        "dni" => "08870187",
        "objective" => "Días de stock Eternit",
        "metric" => "Días",
        "goal" => 20,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Gerente General"
      ],
      [
        "dni" => "08870187",
        "objective" => "Días de stock fierro",
        "metric" => "Días",
        "goal" => 7,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Gerente General"
      ],
      [
        "dni" => "72843284",
        "objective" => "Cuadres de caja oportunos",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Caja Dp"
      ],
      [
        "dni" => "72843284",
        "objective" => "Arqueo de caja, sin diferencias",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Caja Dp"
      ],
      [
        "dni" => "46837187",
        "objective" => "Cumplimiento mensual de los límites de velocidad en ruta",
        "description" => "Total de viajes por despachos del mes no excedan los 80km\/hr. (Control GPS)",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "46837187",
        "objective" => "Cero multas de tránsito",
        "description" => "Por infringir las normas de tránsito en el desarrollo de sus funciones",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "46837187",
        "objective" => "Promedio mensual de ingreso",
        "description" => "Promedio mensual de marcaciones de ingreso del mes según biométrico.",
        "metric" => "Minutos",
        "goal" => 3,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "46837187",
        "objective" => "Cumplimiento de inventario de sede al 100%",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 0,
        "isAscending" => true,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "46837187",
        "objective" => "Cero incidencias en la ruta asignada (robos, choques,etc)",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "71202755",
        "objective" => "Reingresos de vehículos",
        "metric" => "Unidad",
        "goal" => 1,
        "weight" => 50,
        "isAscending" => false,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "71202755",
        "objective" => "Eficiencia de OT",
        "description" => "Promedio de productividad de OT atendidas al mes",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "45106359",
        "objective" => "Reportes semanales de CXC",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Jefe De Contabilidad Y Caja"
      ],
      [
        "dni" => "45106359",
        "objective" => "Cumplimiento de programacion de visitas a sedes con la finalidad de supervisar, procedimientos y control
de caja",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Jefe De Contabilidad Y Caja"
      ],
      [
        "dni" => "45106359",
        "objective" => "Reduccion de gastos financieros",
        "metric" => "Porcentaje",
        "goal" => 10,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Jefe De Contabilidad Y Caja"
      ],
      [
        "dni" => "45106359",
        "objective" => "Reduccion de indice de deudas a proveedores",
        "metric" => "Porcentaje",
        "goal" => 10,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Jefe De Contabilidad Y Caja"
      ],
      [
        "dni" => "70724393",
        "objective" => "Cumplir de paso vehicular",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 30,
        "isAscending" => true,
        "categoria" => "Asesor De Servicios"
      ],
      [
        "dni" => "70724393",
        "objective" => "Cumplimiento de meta general (facturación)",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Asesor De Servicios"
      ],
      [
        "dni" => "70724393",
        "objective" => "Reputation",
        "description" => "Meta regional score superior a 820 puntos",
        "metric" => "Número",
        "goal" => 820,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asesor De Servicios"
      ],
      [
        "dni" => "41072838",
        "objective" => "Rendimiento de combustible.",
        "description" => 0.92,
        "metric" => "Porcentaje",
        "goal" => 92,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "41072838",
        "objective" => "Fletes no cubiertos por negativa de conductor",
        "description" => "Flete no asumido por el conductor, más no perdido.",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "41072838",
        "objective" => "Número de incidencias por responsalidad del conductor",
        "description" => "0: No hubo incidencia \/ 1: Incidencia leve \/ 2:Incidencia moderada \/ 3: Incidencia grave",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 30,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "41072838",
        "objective" => "Promedio de conducción",
        "description" => "(Se da un un rango de cumplimiento entre 85 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "48299503",
        "objective" => "Reingresos de vehículos",
        "metric" => "Unidad",
        "goal" => 1,
        "weight" => 50,
        "isAscending" => false,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "48299503",
        "objective" => "Eficiencia de OT",
        "description" => "Promedio de productividad de OT atendidas al mes",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "16426491",
        "objective" => "Total de incoformidades en despachos",
        "description" => " % Total de facturas (despachos externos) vs % de incorfomidades",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 60,
        "isAscending" => true,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "16426491",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza",
        "metric" => "Minutos",
        "goal" => 3,
        "weight" => 40,
        "isAscending" => false,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "17632430",
        "objective" => "Cumplimiento de la programaciòn",
        "description" => " (Servicios entregados a tiempo\/total de servicios programados)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Asistente De Seguimiento Y Monitoreo"
      ],
      [
        "dni" => "17632430",
        "objective" => "Errores en llenado de guías",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Asistente De Seguimiento Y Monitoreo"
      ],
      [
        "dni" => "17632430",
        "objective" => "Robos en tracto camiones",
        "description" => "Por falta de seguimiento y monitoreo",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Asistente De Seguimiento Y Monitoreo"
      ],
      [
        "dni" => "17632430",
        "objective" => "Accidente y\/o Siniestros",
        "description" => "Por responsabilidad de seguimiento y monitoreo según nivel",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Asistente De Seguimiento Y Monitoreo"
      ],
      [
        "dni" => "75106508",
        "objective" => "Reingresos de vehículos",
        "metric" => "Unidad",
        "goal" => 1,
        "weight" => 50,
        "isAscending" => false,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "75106508",
        "objective" => "Eficiencia de OT",
        "description" => "Promedio de productividad de OT atendidas al mes",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "41544958",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidades",
        "goal" => 13,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "41544958",
        "objective" => "Reputation",
        "description" => "Meta regional score superior a 820 puntos",
        "metric" => "Número",
        "goal" => 820,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "41544958",
        "objective" => "Colocación de créditos",
        "metric" => "Unidad",
        "goal" => 5,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "41544958",
        "objective" => "Colocación de seguros",
        "metric" => "Unidad",
        "goal" => 5,
        "weight" => 10,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "74202439",
        "objective" => "Calidad de servicio - Mesa de ayuda (Milla \/ Dynamics)",
        "description" => "Promedio de calificaciones de encuesta de satisfacción en base a los servicios atendidos (Escalada
1-5)",
        "metric" => "Unidad",
        "goal" => 4,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Analista En Proyecto TICs"
      ],
      [
        "dni" => "74202439",
        "objective" => "Promedio de efectividad en tiempo de atención - Soporte TIC's",
        "metric" => "Minutos",
        "goal" => 20,
        "weight" => 50,
        "isAscending" => false,
        "categoria" => "Analista En Proyecto TICs"
      ],
      [
        "dni" => "44577179",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidades",
        "goal" => 24,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Jefe De Ventas Ap"
      ],
      [
        "dni" => "44577179",
        "objective" => "Reputation",
        "description" => "Meta regional score superior a 820 puntos",
        "metric" => "Número",
        "goal" => 820,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Jefe De Ventas Ap"
      ],
      [
        "dni" => "44577179",
        "objective" => "Meta en seguros",
        "metric" => "Número",
        "goal" => 9,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Jefe De Ventas Ap"
      ],
      [
        "dni" => "44577179",
        "objective" => "Meta en créditos",
        "metric" => "Número",
        "goal" => 9,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Jefe De Ventas Ap"
      ],
      [
        "dni" => "44577179",
        "objective" => "Alcance de Mix de Marca",
        "metric" => "Unidad",
        "goal" => 4,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Jefe De Ventas Ap"
      ],
      [
        "dni" => "44846745",
        "objective" => "Total de incoformidades en despachos",
        "description" => " % Total de facturas (despachos externos) vs % de incorfomidades",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 60,
        "isAscending" => true,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "44846745",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza",
        "metric" => "Minutos",
        "goal" => 3,
        "weight" => 40,
        "isAscending" => false,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "44517042",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidades",
        "goal" => 7,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "44517042",
        "objective" => "Reputation",
        "description" => "Meta regional score superior a 820 puntos",
        "metric" => "Número",
        "goal" => 820,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "44517042",
        "objective" => "Colocación de créditos",
        "metric" => "Unidad",
        "goal" => 3,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "44517042",
        "objective" => "Colocación de seguros",
        "metric" => "Unidad",
        "goal" => 3,
        "weight" => 10,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "72722099",
        "objective" => "Cero soles de multa por errores contables en la empresa a cargo",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 33,
        "isAscending" => false,
        "categoria" => "Asistente De Contabilidad"
      ],
      [
        "dni" => "72722099",
        "objective" => "Cero errores en el registro de información contable de manera mensual",
        "metric" => "Errores",
        "goal" => 2,
        "weight" => 33,
        "isAscending" => false,
        "categoria" => "Asistente De Contabilidad"
      ],
      [
        "dni" => "72722099",
        "objective" => "Entrega de cierre contable mensual integral (en los primeros 12 días calendario)",
        "metric" => "Días",
        "goal" => 12,
        "weight" => 34,
        "isAscending" => false,
        "categoria" => "Asistente De Contabilidad"
      ],
      [
        "dni" => "43122872",
        "objective" => "Cumplimiento mensual de los límites de velocidad en ruta",
        "description" => "Total de viajes por despachos del mes no excedan los 80km\/hr. (Control GPS)",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor II"
      ],
      [
        "dni" => "43122872",
        "objective" => "Cero multas de tránsito",
        "description" => "Por infringir las normas de tránsito en el desarrollo de sus funciones",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Conductor II"
      ],
      [
        "dni" => "43122872",
        "objective" => "Promedio mensual de ingreso",
        "description" => "Promedio mensual de marcaciones de ingreso del mes según biométrico.",
        "metric" => "Minutos",
        "goal" => 3,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Conductor II"
      ],
      [
        "dni" => "43122872",
        "objective" => "Cumplimiento de inventario de sede al 100%",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 0,
        "isAscending" => true,
        "categoria" => "Conductor II"
      ],
      [
        "dni" => "43122872",
        "objective" => "Cero incidencias en la ruta asignada (robos, choques,etc)",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Conductor II"
      ],
      [
        "dni" => "45676375",
        "objective" => "Cumplimiento mensual de los límites de velocidad en ruta",
        "description" => "Total de viajes por despachos del mes no excedan los 80km\/hr. (Control GPS)",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "45676375",
        "objective" => "Cero multas de tránsito",
        "description" => "Por infringir las normas de tránsito en el desarrollo de sus funciones",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "45676375",
        "objective" => "Promedio mensual de ingreso",
        "description" => "Promedio mensual de marcaciones de ingreso del mes según biométrico.",
        "metric" => "Minutos",
        "goal" => 3,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "45676375",
        "objective" => "Cumplimiento de inventario de sede al 100%",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 0,
        "isAscending" => true,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "45676375",
        "objective" => "Cero incidencias en la ruta asignada (robos, choques,etc)",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "74624087",
        "objective" => "Cero soles de multa por errores contables en la empresa a cargo",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 33,
        "isAscending" => false,
        "categoria" => "Asistente De Contabilidad"
      ],
      [
        "dni" => "74624087",
        "objective" => "Cero errores en el registro de información contable de manera mensual",
        "metric" => "Errores",
        "goal" => 2,
        "weight" => 33,
        "isAscending" => false,
        "categoria" => "Asistente De Contabilidad"
      ],
      [
        "dni" => "74624087",
        "objective" => "Entrega de cierre contable mensual integral (en los primeros 12 días calendario)",
        "metric" => "Días",
        "goal" => 12,
        "weight" => 34,
        "isAscending" => false,
        "categoria" => "Asistente De Contabilidad"
      ],
      [
        "dni" => "75662130",
        "objective" => "Total de incoformidades en despachos",
        "description" => " % Total de facturas (despachos externos) vs % de incorfomidades",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 60,
        "isAscending" => true,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "75662130",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza",
        "metric" => "Minutos",
        "goal" => 3,
        "weight" => 40,
        "isAscending" => false,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "17619908",
        "objective" => "Rendimiento de combustible.",
        "description" => 0.92,
        "metric" => "Porcentaje",
        "goal" => 92,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "17619908",
        "objective" => "Fletes no cubiertos por negativa de conductor",
        "description" => "Flete no asumido por el conductor, más no perdido.",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "17619908",
        "objective" => "Número de incidencias por responsalidad del conductor",
        "description" => "0: No hubo incidencia \/ 1: Incidencia leve \/ 2:Incidencia moderada \/ 3: Incidencia grave",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 30,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "17619908",
        "objective" => "Promedio de conducción",
        "description" => "(Se da un un rango de cumplimiento entre 85 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "46226629",
        "objective" => "Cero soles de multa por errores contables en AP",
        "metric" => "Soles",
        "goal" => 0,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Jefe De Contabilidad"
      ],
      [
        "dni" => "46226629",
        "objective" => "Presentación de DDJJ mensuales a SUNAT",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Jefe De Contabilidad"
      ],
      [
        "dni" => "46226629",
        "objective" => "Entrega de reporte de vencimiento de deuda a proveedores",
        "metric" => "Días",
        "goal" => 25,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Jefe De Contabilidad"
      ],
      [
        "dni" => "46226629",
        "objective" => "Entrega de cierres contables (EE.RR)",
        "description" => "Máximo hasta el día 15 de cada mes",
        "metric" => "Días",
        "goal" => 15,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Jefe De Contabilidad"
      ],
      [
        "dni" => "72650523",
        "objective" => "Cumplimiento de presupuesto comercial",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "72650523",
        "objective" => "% de Efectividad de Cobranza",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "72650523",
        "objective" => "Mix de productos",
        "metric" => "Unidad",
        "goal" => 5,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "72650523",
        "objective" => "Cliente Nuevo al mes",
        "metric" => "Unidad",
        "goal" => 2,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "73855265",
        "objective" => "Plazo de entrega de documentos contables",
        "description" => "Plazo de entrega durante los primeros 4 días calendario del mes (notas de crédicto, débito y otros
relacionados a la contabilidad)",
        "metric" => "Días",
        "goal" => 4,
        "weight" => 33,
        "isAscending" => false,
        "categoria" => "Asistente Administrativo DP"
      ],
      [
        "dni" => "73855265",
        "objective" => "Cierre de liquidaciones RIQRA, acuerdos comerciales y otros proveedores",
        "description" => "Plazo de entrega durante los primeros 10 días calendario del mes",
        "metric" => "Días",
        "goal" => 10,
        "weight" => 34,
        "isAscending" => false,
        "categoria" => "Asistente Administrativo DP"
      ],
      [
        "dni" => "73855265",
        "objective" => "Aprobación correcta de descuentos",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 33,
        "isAscending" => true,
        "categoria" => "Asistente Administrativo DP"
      ],
      [
        "dni" => "46012169",
        "objective" => "Total de incoformidades en despachos",
        "description" => " % Total de facturas vs % de incorfomidades (plataforma)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 60,
        "isAscending" => true,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "46012169",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza",
        "metric" => "Minutos",
        "goal" => 3,
        "weight" => 40,
        "isAscending" => false,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "41037767",
        "objective" => "Cumplimiento promedio de registro de compras en el sistema (diario)",
        "description" => "Compras vs registro en sistema propio",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 30,
        "isAscending" => true,
        "categoria" => "Coordinador De Ventas"
      ],
      [
        "dni" => "41037767",
        "objective" => "Liquidaciones dentro del plazo estipulado",
        "description" => "Número de semanas con aplicaciones dentro del plazo",
        "metric" => "Unidad",
        "goal" => 4,
        "weight" => 30,
        "isAscending" => true,
        "categoria" => "Coordinador De Ventas"
      ],
      [
        "dni" => "41037767",
        "objective" => "Cumplimiento promedio de reportería en Dealer Portal (diario)",
        "description" => "Facturación vs reporte TICs ",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 30,
        "isAscending" => true,
        "categoria" => "Coordinador De Ventas"
      ],
      [
        "dni" => "41037767",
        "objective" => "Cumplimiento de envío de las solicitudes de nota de crédito en tiempo establecido",
        "metric" => "Días",
        "goal" => 3,
        "weight" => 10,
        "isAscending" => false,
        "categoria" => "Coordinador De Ventas"
      ],
      [
        "dni" => "78005001",
        "objective" => "Total de incoformidades en despachos",
        "description" => " % Total de facturas (despachos externos) vs % de incorfomidades",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 60,
        "isAscending" => true,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "78005001",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza",
        "metric" => "Minutos",
        "goal" => 3,
        "weight" => 40,
        "isAscending" => false,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "41430542",
        "objective" => "Cumplimiento promedio de registro de compras en el sistema (diario)",
        "description" => "Compras vs registro en sistema propio",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 30,
        "isAscending" => true,
        "categoria" => "Coordinador De Ventas"
      ],
      [
        "dni" => "41430542",
        "objective" => "Liquidaciones dentro del plazo estipulado",
        "description" => "Número de semanas con aplicaciones dentro del plazo",
        "metric" => "Unidad",
        "goal" => 4,
        "weight" => 30,
        "isAscending" => true,
        "categoria" => "Coordinador De Ventas"
      ],
      [
        "dni" => "41430542",
        "objective" => "Cumplimiento promedio de reportería en Dealer Portal (diario)",
        "description" => "Facturación vs reporte TICs ",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 30,
        "isAscending" => true,
        "categoria" => "Coordinador De Ventas"
      ],
      [
        "dni" => "41430542",
        "objective" => "Cumplimiento de envío de las solicitudes de nota de crédito en tiempo establecido",
        "metric" => "Días",
        "goal" => 3,
        "weight" => 10,
        "isAscending" => false,
        "categoria" => "Coordinador De Ventas"
      ],
      [
        "dni" => "16530913",
        "objective" => "Rendimiento de combustible.",
        "description" => 0.92,
        "metric" => "Porcentaje",
        "goal" => 92,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16530913",
        "objective" => "Fletes no cubiertos por negativa de conductor",
        "description" => "Flete no asumido por el conductor, más no perdido.",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16530913",
        "objective" => "Número de incidencias por responsalidad del conductor",
        "description" => "0: No hubo incidencia \/ 1: Incidencia leve \/ 2:Incidencia moderada \/ 3: Incidencia grave",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 30,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16530913",
        "objective" => "Promedio de conducción",
        "description" => "(Se da un un rango de cumplimiento entre 85 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "73235748",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidades",
        "goal" => 13,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "73235748",
        "objective" => "Reputation",
        "description" => "Meta regional score superior a 820 puntos",
        "metric" => "Número",
        "goal" => 820,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "73235748",
        "objective" => "Colocación de créditos",
        "metric" => "Unidad",
        "goal" => 5,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "73235748",
        "objective" => "Colocación de seguros",
        "metric" => "Unidad",
        "goal" => 5,
        "weight" => 10,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "70478021",
        "objective" => "Reingresos de vehículos",
        "metric" => "Unidad",
        "goal" => 1,
        "weight" => 50,
        "isAscending" => false,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "70478021",
        "objective" => "Eficiencia de OT",
        "description" => "Promedio de productividad de OT atendidas al mes",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "41337449",
        "objective" => "Cumplimiento de presupuesto comercial",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "41337449",
        "objective" => "% de Efectividad de Cobranza",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "41337449",
        "objective" => "Mix de productos",
        "metric" => "Unidad",
        "goal" => 5,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "41337449",
        "objective" => "Cliente Nuevo al mes",
        "metric" => "Unidad",
        "goal" => 2,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "72700680",
        "objective" => "Cumplir de paso vehicular",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 30,
        "isAscending" => true,
        "categoria" => "Asesor De Servicios"
      ],
      [
        "dni" => "72700680",
        "objective" => "Cumplimiento de meta general (facturación)",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Asesor De Servicios"
      ],
      [
        "dni" => "72700680",
        "objective" => "Reputation",
        "description" => "Meta regional score superior a 820 puntos",
        "metric" => "Número",
        "goal" => 820,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asesor De Servicios"
      ],
      [
        "dni" => "05641617",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidades",
        "goal" => 7,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "05641617",
        "objective" => "Reputation",
        "description" => "Meta regional score superior a 820 puntos",
        "metric" => "Número",
        "goal" => 820,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "05641617",
        "objective" => "Colocación de créditos",
        "metric" => "Unidad",
        "goal" => 3,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "05641617",
        "objective" => "Colocación de seguros",
        "metric" => "Unidad",
        "goal" => 3,
        "weight" => 10,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "46613975",
        "objective" => "Reingresos de vehículos",
        "metric" => "Unidad",
        "goal" => 1,
        "weight" => 50,
        "isAscending" => false,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "46613975",
        "objective" => "Eficiencia de OT",
        "description" => "Promedio de productividad de OT atendidas al mes",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "74916002",
        "objective" => "Eficiencia de OT",
        "description" => "Promedio de productividad de OT atendidas al mes",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Asistente Mecánico Tp"
      ],
      [
        "dni" => "74916002",
        "objective" => "Mantenimentos correctivos de emergencia",
        "description" => "(Auxilios Mecánicos), no supere el 4% mensual de la flota",
        "metric" => "Porcentaje",
        "goal" => 4,
        "weight" => 50,
        "isAscending" => false,
        "categoria" => "Asistente Mecánico Tp"
      ],
      [
        "dni" => "77071508",
        "objective" => "Reingresos de vehículos",
        "metric" => "Unidad",
        "goal" => 1,
        "weight" => 50,
        "isAscending" => false,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "77071508",
        "objective" => "Eficiencia de OT",
        "description" => "Promedio de productividad de OT atendidas al mes",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "73736078",
        "objective" => "Costo operacional",
        "description" => "(Costo venta\/kilometraje) que no supere 4.0 sol\/km",
        "metric" => "Soles",
        "goal" => 4,
        "weight" => 30,
        "isAscending" => false,
        "categoria" => "Jefe De Operaciones Y Mantenimiento"
      ],
      [
        "dni" => "73736078",
        "objective" => "Nota de Clima Laboral Conductores",
        "metric" => "Porcentaje",
        "goal" => 75,
        "weight" => 0,
        "isAscending" => true,
        "categoria" => "Jefe De Operaciones Y Mantenimiento"
      ],
      [
        "dni" => "73736078",
        "objective" => "Costo máximo de kilómetro de combustible",
        "description" => "No debe superar los 1.0 sol\/km",
        "metric" => "Soles",
        "goal" => 1.2,
        "weight" => 30,
        "isAscending" => false,
        "categoria" => "Jefe De Operaciones Y Mantenimiento"
      ],
      [
        "dni" => "73736078",
        "objective" => "Gastos reparables",
        "description" => "No deben superar los 45,000 soles",
        "metric" => "Soles",
        "goal" => 45000,
        "weight" => 15,
        "isAscending" => false,
        "categoria" => "Jefe De Operaciones Y Mantenimiento"
      ],
      [
        "dni" => "73736078",
        "objective" => "Unidades activas",
        "description" => "Unidades activas vs número de conductores activos",
        "metric" => "Unidad",
        "goal" => 44,
        "weight" => 15,
        "isAscending" => false,
        "categoria" => "Jefe De Operaciones Y Mantenimiento"
      ],
      [
        "dni" => "73736078",
        "objective" => "Accidente y\/o Siniestros por responsabilidad el equipo de operaciones y mantenimiento",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 10,
        "isAscending" => false,
        "categoria" => "Jefe De Operaciones Y Mantenimiento"
      ],
      [
        "dni" => "45206383",
        "objective" => "Plazo de entrega de documentos contables",
        "description" => "Plazo de entrega durante los primeros 4 días calendario del mes (notas de crédicto, débito y otros
relacionados a la contabilidad)",
        "metric" => "Días",
        "goal" => 4,
        "weight" => 33,
        "isAscending" => false,
        "categoria" => "Asistente Administrativo DP"
      ],
      [
        "dni" => "45206383",
        "objective" => "Cierre de liquidaciones RIQRA, acuerdos comerciales y otros proveedores",
        "description" => "Plazo de entrega durante los primeros 10 días calendario del mes",
        "metric" => "Días",
        "goal" => 10,
        "weight" => 34,
        "isAscending" => false,
        "categoria" => "Asistente Administrativo DP"
      ],
      [
        "dni" => "45206383",
        "objective" => "Aprobación correcta de descuentos",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 33,
        "isAscending" => true,
        "categoria" => "Asistente Administrativo DP"
      ],
      [
        "dni" => "40988232",
        "objective" => "Rendimiento de combustible.",
        "description" => 0.92,
        "metric" => "Porcentaje",
        "goal" => 92,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "40988232",
        "objective" => "Fletes no cubiertos por negativa de conductor",
        "description" => "Flete no asumido por el conductor, más no perdido.",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "40988232",
        "objective" => "Número de incidencias por responsalidad del conductor",
        "description" => "0: No hubo incidencia \/ 1: Incidencia leve \/ 2:Incidencia moderada \/ 3: Incidencia grave",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 30,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "40988232",
        "objective" => "Promedio de conducción",
        "description" => "(Se da un un rango de cumplimiento entre 85 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "44639590",
        "objective" => "Rendimiento de combustible.",
        "description" => 0.92,
        "metric" => "Porcentaje",
        "goal" => 92,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "44639590",
        "objective" => "Fletes no cubiertos por negativa de conductor",
        "description" => "Flete no asumido por el conductor, más no perdido.",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "44639590",
        "objective" => "Número de incidencias por responsalidad del conductor",
        "description" => "0: No hubo incidencia \/ 1: Incidencia leve \/ 2:Incidencia moderada \/ 3: Incidencia grave",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 30,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "44639590",
        "objective" => "Promedio de conducción",
        "description" => "(Se da un un rango de cumplimiento entre 85 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "43088093",
        "objective" => "Reingresos de vehículos",
        "metric" => "Unidad",
        "goal" => 1,
        "weight" => 50,
        "isAscending" => false,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "43088093",
        "objective" => "Eficiencia de OT",
        "description" => "Promedio de productividad de OT atendidas al mes",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "19332661",
        "objective" => "Rendimiento de combustible.",
        "description" => 0.92,
        "metric" => "Porcentaje",
        "goal" => 92,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "19332661",
        "objective" => "Fletes no cubiertos por negativa de conductor",
        "description" => "Flete no asumido por el conductor, más no perdido.",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "19332661",
        "objective" => "Número de incidencias por responsalidad del conductor",
        "description" => "0: No hubo incidencia \/ 1: Incidencia leve \/ 2:Incidencia moderada \/ 3: Incidencia grave",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 30,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "19332661",
        "objective" => "Promedio de conducción",
        "description" => "(Se da un un rango de cumplimiento entre 85 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "45801388",
        "objective" => "Cero soles de multa por errores contables en la empresa a cargo",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 30,
        "isAscending" => false,
        "categoria" => "Analista De Contabilidad AP"
      ],
      [
        "dni" => "45801388",
        "objective" => "Entrega de cierres contables en tiempo de empresa a cargo",
        "metric" => "Días",
        "goal" => 5,
        "weight" => 40,
        "isAscending" => true,
        "categoria" => "Analista De Contabilidad AP"
      ],
      [
        "dni" => "45801388",
        "objective" => "Presentación de DDJJ mensuales a SUNAT",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 30,
        "isAscending" => true,
        "categoria" => "Analista De Contabilidad AP"
      ],
      [
        "dni" => "16545918",
        "objective" => "Rendimiento de combustible.",
        "description" => 0.92,
        "metric" => "Porcentaje",
        "goal" => 92,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16545918",
        "objective" => "Fletes no cubiertos por negativa de conductor",
        "description" => "Flete no asumido por el conductor, más no perdido.",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16545918",
        "objective" => "Número de incidencias por responsalidad del conductor",
        "description" => "0: No hubo incidencia \/ 1: Incidencia leve \/ 2:Incidencia moderada \/ 3: Incidencia grave",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 30,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16545918",
        "objective" => "Promedio de conducción",
        "description" => "(Se da un un rango de cumplimiento entre 85 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "43589428",
        "objective" => "Rendimiento de combustible.",
        "description" => 0.92,
        "metric" => "Porcentaje",
        "goal" => 92,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "43589428",
        "objective" => "Fletes no cubiertos por negativa de conductor",
        "description" => "Flete no asumido por el conductor, más no perdido.",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "43589428",
        "objective" => "Número de incidencias por responsalidad del conductor",
        "description" => "0: No hubo incidencia \/ 1: Incidencia leve \/ 2:Incidencia moderada \/ 3: Incidencia grave",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 30,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "43589428",
        "objective" => "Promedio de conducción",
        "description" => "(Se da un un rango de cumplimiento entre 85 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16795422",
        "objective" => "Rendimiento de combustible.",
        "description" => 0.92,
        "metric" => "Porcentaje",
        "goal" => 92,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16795422",
        "objective" => "Fletes no cubiertos por negativa de conductor",
        "description" => "Flete no asumido por el conductor, más no perdido.",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16795422",
        "objective" => "Número de incidencias por responsalidad del conductor",
        "description" => "0: No hubo incidencia \/ 1: Incidencia leve \/ 2:Incidencia moderada \/ 3: Incidencia grave",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 30,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16795422",
        "objective" => "Promedio de conducción",
        "description" => "(Se da un un rango de cumplimiento entre 85 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "17444802",
        "objective" => "Rendimiento de combustible.",
        "description" => 0.92,
        "metric" => "Porcentaje",
        "goal" => 92,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "17444802",
        "objective" => "Fletes no cubiertos por negativa de conductor",
        "description" => "Flete no asumido por el conductor, más no perdido.",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "17444802",
        "objective" => "Número de incidencias por responsalidad del conductor",
        "description" => "0: No hubo incidencia \/ 1: Incidencia leve \/ 2:Incidencia moderada \/ 3: Incidencia grave",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 30,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "17444802",
        "objective" => "Promedio de conducción",
        "description" => "(Se da un un rango de cumplimiento entre 85 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "40989878",
        "objective" => "Total de incoformidades en despachos",
        "description" => " % Total de facturas (despachos externos) vs % de incorfomidades",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 60,
        "isAscending" => true,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "40989878",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza",
        "metric" => "Minutos",
        "goal" => 3,
        "weight" => 40,
        "isAscending" => false,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "46479361",
        "objective" => "Rendimiento de combustible.",
        "description" => 0.92,
        "metric" => "Porcentaje",
        "goal" => 92,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "46479361",
        "objective" => "Fletes no cubiertos por negativa de conductor",
        "description" => "Flete no asumido por el conductor, más no perdido.",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "46479361",
        "objective" => "Número de incidencias por responsalidad del conductor",
        "description" => "0: No hubo incidencia \/ 1: Incidencia leve \/ 2:Incidencia moderada \/ 3: Incidencia grave",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 30,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "46479361",
        "objective" => "Promedio de conducción",
        "description" => "(Se da un un rango de cumplimiento entre 85 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "46132573",
        "objective" => "Cumplir de paso vehicular",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 30,
        "isAscending" => true,
        "categoria" => "Asesor De Servicios"
      ],
      [
        "dni" => "46132573",
        "objective" => "Cumplimiento de meta general (facturación)",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Asesor De Servicios"
      ],
      [
        "dni" => "46132573",
        "objective" => "Reputation",
        "description" => "Meta regional score superior a 820 puntos",
        "metric" => "Número",
        "goal" => 820,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asesor De Servicios"
      ],
      [
        "dni" => "76571917",
        "objective" => "Reingresos de vehículos",
        "metric" => "Unidad",
        "goal" => 1,
        "weight" => 50,
        "isAscending" => false,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "76571917",
        "objective" => "Eficiencia de OT",
        "description" => "Promedio de productividad de OT atendidas al mes",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "71895009",
        "objective" => "Reingresos de vehículos",
        "metric" => "Unidad",
        "goal" => 1,
        "weight" => 50,
        "isAscending" => false,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "71895009",
        "objective" => "Eficiencia de OT",
        "description" => "Promedio de productividad de OT atendidas al mes",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "72218186",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidades",
        "goal" => 8,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "72218186",
        "objective" => "Reputation",
        "description" => "Meta regional score superior a 820 puntos",
        "metric" => "Número",
        "goal" => 820,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "72218186",
        "objective" => "Colocación de créditos",
        "metric" => "Unidad",
        "goal" => 3,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "72218186",
        "objective" => "Colocación de seguros",
        "metric" => "Unidad",
        "goal" => 3,
        "weight" => 10,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "73777585",
        "objective" => "Cumplimiento de presupuesto comercial",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "73777585",
        "objective" => "% de Efectividad de Cobranza",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "73777585",
        "objective" => "Mix de productos",
        "metric" => "Unidad",
        "goal" => 5,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "73777585",
        "objective" => "Cliente Nuevo al mes",
        "metric" => "Unidad",
        "goal" => 2,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "42772411",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidades",
        "goal" => 31,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Jefe De Ventas Ap"
      ],
      [
        "dni" => "42772411",
        "objective" => "Reputation",
        "description" => "Meta regional score superior a 820 puntos",
        "metric" => "Número",
        "goal" => 820,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Jefe De Ventas Ap"
      ],
      [
        "dni" => "42772411",
        "objective" => "Meta en seguros",
        "metric" => "Número",
        "goal" => 12,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Jefe De Ventas Ap"
      ],
      [
        "dni" => "42772411",
        "objective" => "Meta en créditos",
        "metric" => "Número",
        "goal" => 12,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Jefe De Ventas Ap"
      ],
      [
        "dni" => "42772411",
        "objective" => "Alcance de Mix de Marca",
        "metric" => "Unidad",
        "goal" => 3,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Jefe De Ventas Ap"
      ],
      [
        "dni" => "43324348",
        "objective" => "Total de incoformidades en despachos",
        "description" => " % Total de facturas (despachos externos e internos) vs % de incorfomidades",
        "metric" => "Porcentaje",
        "goal" => 90,
        "weight" => 60,
        "isAscending" => true,
        "categoria" => "Supervisor De Almacen"
      ],
      [
        "dni" => "43324348",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza",
        "metric" => "Minutos",
        "goal" => 3,
        "weight" => 40,
        "isAscending" => false,
        "categoria" => "Supervisor De Almacen"
      ],
      [
        "dni" => "75235770",
        "objective" => "Cumplimiento de Reputation AP",
        "description" => "Número de sedes en las que se cumplió en nivel de Reputation.",
        "metric" => "Número",
        "goal" => 4,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Coordinador De Post Venta"
      ],
      [
        "dni" => "75235770",
        "objective" => "Promedio de cumplimiento del ICD al 90% a nivel de las 4 sedes",
        "metric" => "Porcentaje",
        "goal" => 90,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Coordinador De Post Venta"
      ],
      [
        "dni" => "71628227",
        "objective" => "Unidades inactivas por falta de conductor",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Supervisor De Operaciones Y Mantenimiento"
      ],
      [
        "dni" => "71628227",
        "objective" => "Nota de Clima Laboral Conductores",
        "metric" => "Porcentaje",
        "goal" => 75,
        "weight" => 0,
        "isAscending" => true,
        "categoria" => "Supervisor De Operaciones Y Mantenimiento"
      ],
      [
        "dni" => "71628227",
        "objective" => "Cumplimiento de la programaciòn de mantenimiento.",
        "description" => " (Servicios entregados a tiempo\/total de servicios programados)",
        "metric" => "Porcentaje",
        "goal" => 88,
        "weight" => 30,
        "isAscending" => true,
        "categoria" => "Supervisor De Operaciones Y Mantenimiento"
      ],
      [
        "dni" => "71628227",
        "objective" => "Control de descansos laborales de conductores",
        "description" => "No superar los 450 días pendientes al mes.",
        "metric" => "Días",
        "goal" => 450,
        "weight" => 15,
        "isAscending" => false,
        "categoria" => "Supervisor De Operaciones Y Mantenimiento"
      ],
      [
        "dni" => "71628227",
        "objective" => "Costo de mantenimiento por recorrido",
        "description" => "Los costos de mantenimiento no deben superar los 0.28 soles\/km recorrido",
        "metric" => "Soles",
        "goal" => 0.28,
        "weight" => 15,
        "isAscending" => false,
        "categoria" => "Supervisor De Operaciones Y Mantenimiento"
      ],
      [
        "dni" => "71628227",
        "objective" => "Accidente y\/o Siniestros",
        "description" => "Por responsabilidad el equipo de operaciones y mantenimiento",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 15,
        "isAscending" => false,
        "categoria" => "Supervisor De Operaciones Y Mantenimiento"
      ],
      [
        "dni" => "47160168",
        "objective" => "Efectividad de proyección de facturación",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 30,
        "isAscending" => true,
        "categoria" => "Asistente De Facturacion Y Cobranza"
      ],
      [
        "dni" => "47160168",
        "objective" => "Recuperación de documentos en tiempo establecido",
        "metric" => "Porcentaje",
        "goal" => 95,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asistente De Facturacion Y Cobranza"
      ],
      [
        "dni" => "47160168",
        "objective" => "Aplicación de detracciones",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asistente De Facturacion Y Cobranza"
      ],
      [
        "dni" => "47160168",
        "objective" => "Efectividad proyección de cobranza",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 30,
        "isAscending" => true,
        "categoria" => "Asistente De Facturacion Y Cobranza"
      ],
      [
        "dni" => "70046881",
        "objective" => "Cumplimiento de la facturación de taller mas un incremento adicional del 2%",
        "metric" => "Porcentaje",
        "goal" => 102,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Jefe De Taller"
      ],
      [
        "dni" => "70046881",
        "objective" => "Cumplimiento de OT>100%",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 30,
        "isAscending" => true,
        "categoria" => "Jefe De Taller"
      ],
      [
        "dni" => "70046881",
        "objective" => "Tasa de reclamos relacionados al servicio que brinda el jefe de taller",
        "metric" => "Unidad",
        "goal" => 1,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Jefe De Taller"
      ],
      [
        "dni" => "46688489",
        "objective" => "Arqueo de caja, sin diferencias",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Caja Ap"
      ],
      [
        "dni" => "46688489",
        "objective" => "Número de arqueos con efectivo mayor a S\/5000",
        "description" => "El efectivo en caja no debe exceder los S\/5000 diario según arqueos.",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 40,
        "isAscending" => false,
        "categoria" => "Caja Ap"
      ],
      [
        "dni" => "46688489",
        "objective" => "Aplicaciones de CXC ejecutadas al cierre de mes",
        "description" => "Número de ingresos en bancos aplicados despues de plazo",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 40,
        "isAscending" => false,
        "categoria" => "Caja Ap"
      ],
      [
        "dni" => "43315473",
        "objective" => "Cumplimiento de presupuesto comercial",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "43315473",
        "objective" => "% de Efectividad de Cobranza",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "43315473",
        "objective" => "Mix de productos",
        "metric" => "Unidad",
        "goal" => 5,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "43315473",
        "objective" => "Cliente Nuevo al mes",
        "metric" => "Unidad",
        "goal" => 2,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "72865261",
        "objective" => "Cumplir de paso vehicular",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 30,
        "isAscending" => true,
        "categoria" => "Asesor De Servicios"
      ],
      [
        "dni" => "72865261",
        "objective" => "Cumplimiento de meta general (facturación)",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Asesor De Servicios"
      ],
      [
        "dni" => "72865261",
        "objective" => "Reputation",
        "description" => "Meta regional score superior a 820 puntos",
        "metric" => "Número",
        "goal" => 820,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asesor De Servicios"
      ],
      [
        "dni" => "46304286",
        "objective" => "Notas de crèdito a proveedores",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Jefe De Administracion"
      ],
      [
        "dni" => "46304286",
        "objective" => "Pago a proveedores",
        "metric" => "Porcentaje",
        "goal" => 0,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Jefe De Administracion"
      ],
      [
        "dni" => "46304286",
        "objective" => "Gestión de compras",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Jefe De Administracion"
      ],
      [
        "dni" => "46304286",
        "objective" => "Efectividad de liquidaciones",
        "description" => "% de reconocimeinto de la liquidacion / total de liquidaciones registradas",
        "metric" => "Número",
        "goal" => 100,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Jefe De Administracion"
      ],
      [
        "dni" => "19037052",
        "objective" => "Cumplimiento de presupuesto comercial",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "19037052",
        "objective" => "% de Efectividad de Cobranza",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "19037052",
        "objective" => "Mix de productos",
        "metric" => "Unidad",
        "goal" => 5,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "19037052",
        "objective" => "Cliente Nuevo al mes",
        "metric" => "Unidad",
        "goal" => 2,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "71960403",
        "objective" => "Total de incoformidades en despachos",
        "description" => " % Total de facturas (despachos externos) vs % de incorfomidades",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 60,
        "isAscending" => true,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "71960403",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza",
        "metric" => "Minutos",
        "goal" => 3,
        "weight" => 40,
        "isAscending" => false,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "42310255",
        "objective" => "Rendimiento de combustible.",
        "description" => 0.92,
        "metric" => "Porcentaje",
        "goal" => 92,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "42310255",
        "objective" => "Fletes no cubiertos por negativa de conductor",
        "description" => "Flete no asumido por el conductor, más no perdido.",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "42310255",
        "objective" => "Número de incidencias por responsalidad del conductor",
        "description" => "0: No hubo incidencia \/ 1: Incidencia leve \/ 2:Incidencia moderada \/ 3: Incidencia grave",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 30,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "42310255",
        "objective" => "Promedio de conducción",
        "description" => "(Se da un un rango de cumplimiento entre 85 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "46749588",
        "objective" => "Promedio de ratio de cierre de venta de visita en piso",
        "description" => "Cierre de ventas vs número de visitas en piso por marca ",
        "metric" => "Porcentaje",
        "goal" => 25,
        "weight" => 30,
        "isAscending" => true,
        "categoria" => "Ejecutivo De Trade Marketing"
      ],
      [
        "dni" => "46749588",
        "objective" => "Cumplimiento de entregas AP",
        "description" => "Total 4 sedes",
        "metric" => "Unidad",
        "goal" => 4,
        "weight" => 30,
        "isAscending" => true,
        "categoria" => "Ejecutivo De Trade Marketing"
      ],
      [
        "dni" => "46749588",
        "objective" => "Cumplimiento de cronograma de campañas de Mkt",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Ejecutivo De Trade Marketing"
      ],
      [
        "dni" => "46749588",
        "objective" => "Tiempo promedio de facturación de OC",
        "metric" => "Días",
        "goal" => 3,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Ejecutivo De Trade Marketing"
      ],
      [
        "dni" => "40601600",
        "objective" => "Plazo de entrega de documentos contables",
        "description" => "Plazo de entrega durante los primeros 4 días calendario del mes (notas de crédicto, débito y otros
relacionados a la contabilidad)",
        "metric" => "Días",
        "goal" => 4,
        "weight" => 33,
        "isAscending" => false,
        "categoria" => "Asistente Administrativo DP"
      ],
      [
        "dni" => "40601600",
        "objective" => "Cierre de liquidaciones RIQRA, acuerdos comerciales y otros proveedores",
        "description" => "Plazo de entrega durante los primeros 10 días calendario del mes",
        "metric" => "Días",
        "goal" => 10,
        "weight" => 34,
        "isAscending" => false,
        "categoria" => "Asistente Administrativo DP"
      ],
      [
        "dni" => "40601600",
        "objective" => "Aprobación correcta de descuentos",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 33,
        "isAscending" => true,
        "categoria" => "Asistente Administrativo DP"
      ],
      [
        "dni" => "75459353",
        "objective" => "Eficiencia de OT",
        "description" => "Promedio de productividad de OT atendidas al mes",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 30,
        "isAscending" => true,
        "categoria" => "Maestro Mecanico"
      ],
      [
        "dni" => "75459353",
        "objective" => "Unidades Operativas",
        "description" => "Número de unidades operativas por responsabilidad de taller al 98%",
        "metric" => "Porcentaje",
        "goal" => 98,
        "weight" => 40,
        "isAscending" => false,
        "categoria" => "Maestro Mecanico"
      ],
      [
        "dni" => "75459353",
        "objective" => "Reingresos de vehículos",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 10,
        "isAscending" => false,
        "categoria" => "Maestro Mecanico"
      ],
      [
        "dni" => "75459353",
        "objective" => "Mantenimentos correctivos de emergencia",
        "description" => "(Auxilios Mecánicos), no supere el 4% mensual de la flota",
        "metric" => "Porcentaje",
        "goal" => 4,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Maestro Mecanico"
      ],
      [
        "dni" => "76390749",
        "objective" => "Cumplimiento de pagos autorizados",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 33,
        "isAscending" => true,
        "categoria" => "Asistente De Contabilidad Dp"
      ],
      [
        "dni" => "76390749",
        "objective" => "Errores en el registro de información contable de manera mensual",
        "metric" => "Errores",
        "goal" => 0,
        "weight" => 33,
        "isAscending" => false,
        "categoria" => "Asistente De Contabilidad Dp"
      ],
      [
        "dni" => "76390749",
        "objective" => "Cartas fianza renovadas oportunamente",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 34,
        "isAscending" => false,
        "categoria" => "Asistente De Contabilidad Dp"
      ],
      [
        "dni" => "16754597",
        "objective" => "Total de incoformidades en despachos",
        "description" => " % Total de facturas vs % de incorfomidades (plataforma)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 60,
        "isAscending" => true,
        "categoria" => "Asistente De Reparto",
      ],
      [
        "dni" => "16754597",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza",
        "metric" => "Minutos",
        "goal" => 3,
        "weight" => 40,
        "isAscending" => false,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "44723898",
        "objective" => "Cumplimiento mensual de los límites de velocidad en ruta",
        "description" => "Total de viajes por despachos del mes no excedan los 80km\/hr. (Control GPS)",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "44723898",
        "objective" => "Cero multas de tránsito",
        "description" => "Por infringir las normas de tránsito en el desarrollo de sus funciones",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "44723898",
        "objective" => "Promedio mensual de ingreso",
        "description" => "Promedio mensual de marcaciones de ingreso del mes según biométrico.",
        "metric" => "Minutos",
        "goal" => 3,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "44723898",
        "objective" => "Cumplimiento de inventario de sede al 100%",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 0,
        "isAscending" => true,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "44723898",
        "objective" => "Cero incidencias en la ruta asignada (robos, choques,etc)",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "40971761",
        "objective" => "Errores en implementaciòn de unidades",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Asistente De Operaciones"
      ],
      [
        "dni" => "40971761",
        "objective" => "Registro de incidencias en Drive",
        "description" => "Robos de implementos, mercaderìa e incidencias.",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asistente De Operaciones"
      ],
      [
        "dni" => "40971761",
        "objective" => "Implementación de flota mensual",
        "description" => "Validación de flota",
        "metric" => "Porcentaje",
        "goal" => 80,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asistente De Operaciones"
      ],
      [
        "dni" => "40971761",
        "objective" => "Cero multas por documentacion vencida",
        "description" => " Mantener actualizada de todas las unidades.(revisiòn tècnica, bonificaciòn, soat,certificado MTC)",
        "metric" => "Soles",
        "goal" => 0,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Asistente De Operaciones"
      ],
      [
        "dni" => "40971761",
        "objective" => "Accidente y\/o Siniestros",
        "description" => "Por responsabilidad el equipo de operaciones y mantenimiento",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 15,
        "isAscending" => false,
        "categoria" => "Asistente De Operaciones"
      ],
      [
        "dni" => "45938688",
        "objective" => "Arqueo de caja, sin diferencias",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Caja Ap"
      ],
      [
        "dni" => "45938688",
        "objective" => "Número de arqueos con efectivo mayor a S\/5000",
        "description" => "El efectivo en caja no debe exceder los S\/5000 diario según arqueos.",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 40,
        "isAscending" => false,
        "categoria" => "Caja Ap"
      ],
      [
        "dni" => "45938688",
        "objective" => "Aplicaciones de CXC ejecutadas al cierre de mes",
        "description" => "Número de ingresos en bancos aplicados despues de plazo",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 40,
        "isAscending" => false,
        "categoria" => "Caja Ap"
      ],
      [
        "dni" => "16454626",
        "objective" => "Rendimiento de combustible.",
        "description" => 0.92,
        "metric" => "Porcentaje",
        "goal" => 92,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16454626",
        "objective" => "Fletes no cubiertos por negativa de conductor",
        "description" => "Flete no asumido por el conductor, más no perdido.",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16454626",
        "objective" => "Número de incidencias por responsalidad del conductor",
        "description" => "0: No hubo incidencia \/ 1: Incidencia leve \/ 2:Incidencia moderada \/ 3: Incidencia grave",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 30,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16454626",
        "objective" => "Promedio de conducción",
        "description" => "(Se da un un rango de cumplimiento entre 85 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "73271353",
        "objective" => "Cumplimiento de inmatriculación",
        "description" => "Número de inmatriculaciones al mes vs facturación",
        "metric" => "Porcentaje",
        "goal" => 95,
        "weight" => 100,
        "isAscending" => true,
        "categoria" => "Gestor De Inmatriculacion"
      ],
      [
        "dni" => "70309494",
        "objective" => "Cumplimiento mensual de los límites de velocidad en ruta",
        "description" => "Total de viajes por despachos del mes no excedan los 80km\/hr. (Control GPS)",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "70309494",
        "objective" => "Cero multas de tránsito",
        "description" => "Por infringir las normas de tránsito en el desarrollo de sus funciones",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "70309494",
        "objective" => "Promedio mensual de ingreso",
        "description" => "Promedio mensual de marcaciones de ingreso del mes según biométrico.",
        "metric" => "Minutos",
        "goal" => 3,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "70309494",
        "objective" => "Cumplimiento de inventario de sede al 100%",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 0,
        "isAscending" => true,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "70309494",
        "objective" => "Cero incidencias en la ruta asignada (robos, choques,etc)",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "16526312",
        "objective" => "Rendimiento de combustible.",
        "description" => 0.92,
        "metric" => "Porcentaje",
        "goal" => 92,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16526312",
        "objective" => "Fletes no cubiertos por negativa de conductor",
        "description" => "Flete no asumido por el conductor, más no perdido.",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16526312",
        "objective" => "Número de incidencias por responsalidad del conductor",
        "description" => "0: No hubo incidencia \/ 1: Incidencia leve \/ 2:Incidencia moderada \/ 3: Incidencia grave",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 30,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16526312",
        "objective" => "Promedio de conducción",
        "description" => "(Se da un un rango de cumplimiento entre 85 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "73226257",
        "objective" => "Total de incoformidades en despachos",
        "description" => " % Total de facturas vs % de incorfomidades (plataforma)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 60,
        "isAscending" => true,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "73226257",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza",
        "metric" => "Minutos",
        "goal" => 3,
        "weight" => 40,
        "isAscending" => false,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "75560129",
        "objective" => "Cumplimiento de la programaciòn",
        "description" => " (Servicios entregados a tiempo\/total de servicios programados)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Asistente De Seguimiento Y Monitoreo"
      ],
      [
        "dni" => "75560129",
        "objective" => "Errores en llenado de guías",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Asistente De Seguimiento Y Monitoreo"
      ],
      [
        "dni" => "75560129",
        "objective" => "Robos en tracto camiones",
        "description" => "Por falta de seguimiento y monitoreo",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Asistente De Seguimiento Y Monitoreo"
      ],
      [
        "dni" => "75560129",
        "objective" => "Accidente y\/o Siniestros",
        "description" => "Por responsabilidad de seguimiento y monitoreo según nivel",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Asistente De Seguimiento Y Monitoreo"
      ],
      [
        "dni" => "42430798",
        "objective" => "Cumplimiento de la facturación de taller mas un incremento adicional del 2%",
        "metric" => "Porcentaje",
        "goal" => 102,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Jefe De Taller"
      ],
      [
        "dni" => "42430798",
        "objective" => "Cumplimiento de OT>100%",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 30,
        "isAscending" => true,
        "categoria" => "Jefe De Taller"
      ],
      [
        "dni" => "42430798",
        "objective" => "Tasa de reclamos relacionados al servicio que brinda el jefe de taller",
        "metric" => "Unidad",
        "goal" => 1,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Jefe De Taller"
      ],
      [
        "dni" => "43210143",
        "objective" => "Rendimiento de combustible.",
        "description" => 0.92,
        "metric" => "Porcentaje",
        "goal" => 92,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "43210143",
        "objective" => "Fletes no cubiertos por negativa de conductor",
        "description" => "Flete no asumido por el conductor, más no perdido.",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "43210143",
        "objective" => "Número de incidencias por responsalidad del conductor",
        "description" => "0: No hubo incidencia \/ 1: Incidencia leve \/ 2:Incidencia moderada \/ 3: Incidencia grave",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 30,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "43210143",
        "objective" => "Promedio de conducción",
        "description" => "(Se da un un rango de cumplimiento entre 85 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "19324054",
        "objective" => "Rendimiento de combustible.",
        "description" => 0.92,
        "metric" => "Porcentaje",
        "goal" => 92,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "19324054",
        "objective" => "Fletes no cubiertos por negativa de conductor",
        "description" => "Flete no asumido por el conductor, más no perdido.",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "19324054",
        "objective" => "Número de incidencias por responsalidad del conductor",
        "description" => "0: No hubo incidencia \/ 1: Incidencia leve \/ 2:Incidencia moderada \/ 3: Incidencia grave",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 30,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "19324054",
        "objective" => "Promedio de conducción",
        "description" => "(Se da un un rango de cumplimiento entre 85 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "75497507",
        "objective" => "Cumplimiento de diseño de conceptos visuales campañas de marketing",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 33,
        "isAscending" => true,
        "categoria" => "Diseñador Grafico"
      ],
      [
        "dni" => "75497507",
        "objective" => "Promedio de entrega de solicitudes de diseño por marca",
        "metric" => "Días",
        "goal" => 3,
        "weight" => 33,
        "isAscending" => true,
        "categoria" => "Diseñador Grafico"
      ],
      [
        "dni" => "41093744",
        "objective" => "Rendimiento de combustible.",
        "description" => 0.92,
        "metric" => "Porcentaje",
        "goal" => 92,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "41093744",
        "objective" => "Fletes no cubiertos por negativa de conductor",
        "description" => "Flete no asumido por el conductor, más no perdido.",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "41093744",
        "objective" => "Número de incidencias por responsalidad del conductor",
        "description" => "0: No hubo incidencia \/ 1: Incidencia leve \/ 2:Incidencia moderada \/ 3: Incidencia grave",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 30,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "41093744",
        "objective" => "Promedio de conducción",
        "description" => "(Se da un un rango de cumplimiento entre 85 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "72080239",
        "objective" => "Stock saludable de un mes por sede",
        "metric" => "Unidad",
        "goal" => 1,
        "weight" => 100,
        "isAscending" => false,
        "categoria" => "Asistente De Almacén Ap"
      ],
      [
        "dni" => "44588021",
        "objective" => "Eficiencia de OT",
        "description" => "Promedio de productividad de OT atendidas al mes",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Tecnico Soldador"
      ],
      [
        "dni" => "44588021",
        "objective" => "Mantenimentos correctivos de emergencia",
        "description" => "(Auxilios Mecánicos), no supere el 4% mensual de la flota",
        "metric" => "Porcentaje",
        "goal" => 4,
        "weight" => 50,
        "isAscending" => false,
        "categoria" => "Tecnico Soldador"
      ],
      [
        "dni" => "48080535",
        "objective" => "Plazo de entrega de documentos contables",
        "description" => "Plazo de entrega durante los primeros 4 días calendario del mes (notas de crédicto, débito y otros relacionados a la contabilidad)",
        "metric" => "Días",
        "goal" => 4,
        "weight" => 33,
        "isAscending" => false,
        "categoria" => "Jefe De Administracion"
      ],
      [
        "dni" => "48080535",
        "objective" => "Cierre de liquidaciones RIQRA, acuerdos comerciales y otros proveedores",
        "description" => "Plazo de entrega durante los primeros 10 días calendario del mes",
        "metric" => "Días",
        "goal" => 10,
        "weight" => 34,
        "isAscending" => false,
        "categoria" => "Jefe De Administracion"
      ],
      [
        "dni" => "48080535",
        "objective" => "Aprobación correcta de descuentos",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 33,
        "isAscending" => true,
        "categoria" => "Jefe De Administracion"
      ],
      [
        "dni" => "42838742",
        "objective" => "Cumplimiento de presupuesto comercial",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "42838742",
        "objective" => "% de Efectividad de Cobranza",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "42838742",
        "objective" => "Mix de productos",
        "metric" => "Unidad",
        "goal" => 5,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "42838742",
        "objective" => "Cliente Nuevo al mes",
        "metric" => "Unidad",
        "goal" => 2,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "16657698",
        "objective" => "Cumplimiento mensual de los límites de velocidad en ruta",
        "description" => "Total de viajes por despachos del mes no excedan los 80km\/hr. (Control GPS)",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "16657698",
        "objective" => "Cero multas de tránsito",
        "description" => "Por infringir las normas de tránsito en el desarrollo de sus funciones",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "16657698",
        "objective" => "Promedio mensual de ingreso",
        "description" => "Promedio mensual de marcaciones de ingreso del mes según biométrico.",
        "metric" => "Minutos",
        "goal" => 3,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "16657698",
        "objective" => "Cumplimiento de inventario de sede al 100%",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 0,
        "isAscending" => true,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "16657698",
        "objective" => "Cero incidencias en la ruta asignada (robos, choques,etc)",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Conductor I"
      ],
      [
        "dni" => "45509583",
        "objective" => "Cumplimiento de presupuesto comercial",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "45509583",
        "objective" => "% de Efectividad de Cobranza",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "45509583",
        "objective" => "Mix de productos",
        "metric" => "Unidad",
        "goal" => 5,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "45509583",
        "objective" => "Cliente Nuevo al mes",
        "metric" => "Unidad",
        "goal" => 2,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "46792789",
        "objective" => "Total de incoformidades en despachos",
        "description" => " % Total de facturas (despachos externos) vs % de incorfomidades",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 60,
        "isAscending" => true,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "46792789",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza",
        "metric" => "Minutos",
        "goal" => 3,
        "weight" => 40,
        "isAscending" => false,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "73340961",
        "objective" => "Total de incoformidades en despachos",
        "description" => " % Total de facturas (despachos externos) vs % de incorfomidades",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 60,
        "isAscending" => true,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "73340961",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza",
        "metric" => "Minutos",
        "goal" => 3,
        "weight" => 40,
        "isAscending" => false,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "46002145",
        "objective" => "Total de incoformidades en despachos",
        "description" => " % Total de facturas (despachos externos) vs % de incorfomidades",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 60,
        "isAscending" => true,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "46002145",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza",
        "metric" => "Minutos",
        "goal" => 3,
        "weight" => 40,
        "isAscending" => false,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "18217261",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidades",
        "goal" => 7,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "18217261",
        "objective" => "Reputation",
        "description" => "Meta regional score superior a 820 puntos",
        "metric" => "Número",
        "goal" => 820,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "18217261",
        "objective" => "Colocación de créditos",
        "metric" => "Unidad",
        "goal" => 2,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "18217261",
        "objective" => "Colocación de seguros",
        "metric" => "Unidad",
        "goal" => 2,
        "weight" => 10,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "48502011",
        "objective" => "Reingresos de vehículos",
        "metric" => "Unidad",
        "goal" => 1,
        "weight" => 50,
        "isAscending" => false,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "48502011",
        "objective" => "Eficiencia de OT",
        "description" => "Promedio de productividad de OT atendidas al mes",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "44198340",
        "objective" => "Rendimiento de combustible.",
        "description" => 0.92,
        "metric" => "Porcentaje",
        "goal" => 92,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "44198340",
        "objective" => "Fletes no cubiertos por negativa de conductor",
        "description" => "Flete no asumido por el conductor, más no perdido.",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "44198340",
        "objective" => "Número de incidencias por responsalidad del conductor",
        "description" => "0: No hubo incidencia \/ 1: Incidencia leve \/ 2:Incidencia moderada \/ 3: Incidencia grave",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 30,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "44198340",
        "objective" => "Promedio de conducción",
        "description" => "(Se da un un rango de cumplimiento entre 85 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "73657400",
        "objective" => "Arqueo de caja, sin diferencias",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Caja Ap"
      ],
      [
        "dni" => "73657400",
        "objective" => "Número de arqueos con efectivo mayor a S\/5000",
        "description" => "El efectivo en caja no debe exceder los S\/5000 diario según arqueos.",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 40,
        "isAscending" => false,
        "categoria" => "Caja Ap"
      ],
      [
        "dni" => "73657400",
        "objective" => "Aplicaciones de CXC ejecutadas al cierre de mes",
        "description" => "Número de ingresos en bancos aplicados despues de plazo",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 40,
        "isAscending" => false,
        "categoria" => "Caja Ap"
      ],
      [
        "dni" => "48178171",
        "objective" => "Reingresos de vehículos",
        "metric" => "Unidad",
        "goal" => 1,
        "weight" => 50,
        "isAscending" => false,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "48178171",
        "objective" => "Eficiencia de OT",
        "description" => "Promedio de productividad de OT atendidas al mes",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "42658440",
        "objective" => "Total de incoformidades en despachos",
        "description" => " % Total de facturas (despachos externos) vs % de incorfomidades",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 60,
        "isAscending" => true,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "42658440",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza",
        "metric" => "Minutos",
        "goal" => 3,
        "weight" => 40,
        "isAscending" => false,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "70984021",
        "objective" => "Cuadres de caja oportunos",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Caja Dp"
      ],
      [
        "dni" => "70984021",
        "objective" => "Arqueo de caja, sin diferencias",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Caja Dp"
      ],
      [
        "dni" => "45354782",
        "objective" => "Rendimiento de combustible.",
        "description" => 0.92,
        "metric" => "Porcentaje",
        "goal" => 92,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "45354782",
        "objective" => "Fletes no cubiertos por negativa de conductor",
        "description" => "Flete no asumido por el conductor, más no perdido.",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "45354782",
        "objective" => "Número de incidencias por responsalidad del conductor",
        "description" => "0: No hubo incidencia \/ 1: Incidencia leve \/ 2:Incidencia moderada \/ 3: Incidencia grave",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 30,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "45354782",
        "objective" => "Promedio de conducción",
        "description" => "(Se da un un rango de cumplimiento entre 85 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "75943283",
        "objective" => "Procesos dentro del plazo",
        "metric" => "Porcentaje",
        "goal" => 80,
        "weight" => 15,
        "isAscending" => true,
        "categoria" => "Asistente de Atracción De Talento"
      ],
      [
        "dni" => "75943283",
        "objective" => "Unidades inactivas por falta de conductor TP",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Asistente de Atracción De Talento"
      ],
      [
        "dni" => "75943283",
        "objective" => "Nota de capacitaciones (PDG)",
        "metric" => "Número",
        "goal" => 100,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Asistente de Atracción De Talento"
      ],
      [
        "dni" => "75943283",
        "objective" => "Cumplimiento del PAC",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 10,
        "isAscending" => true,
        "categoria" => "Asistente de Atracción De Talento"
      ],
      [
        "dni" => "75943283",
        "objective" => "Calidad de reclutamiento",
        "metric" => "Porcentaje",
        "goal" => 95,
        "weight" => 15,
        "isAscending" => true,
        "categoria" => "Asistente de Atracción De Talento"
      ],
      [
        "dni" => "75943283",
        "objective" => "Cumplimiento de Plan de Marca Empleadora",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 10,
        "isAscending" => true,
        "categoria" => "Asistente de Atracción De Talento"
      ],
      [
        "dni" => "71875278",
        "objective" => "Rendimiento de combustible.",
        "description" => 0.92,
        "metric" => "Porcentaje",
        "goal" => 92,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "71875278",
        "objective" => "Fletes no cubiertos por negativa de conductor",
        "description" => "Flete no asumido por el conductor, más no perdido.",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "71875278",
        "objective" => "Número de incidencias por responsalidad del conductor",
        "description" => "0: No hubo incidencia \/ 1: Incidencia leve \/ 2:Incidencia moderada \/ 3: Incidencia grave",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 30,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "71875278",
        "objective" => "Promedio de conducción",
        "description" => "(Se da un un rango de cumplimiento entre 85 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "75886284",
        "objective" => "Total de incoformidades en despachos",
        "description" => " % Total de facturas (despachos externos) vs % de incorfomidades",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 60,
        "isAscending" => true,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "75886284",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza",
        "metric" => "Minutos",
        "goal" => 3,
        "weight" => 40,
        "isAscending" => false,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "75089704",
        "objective" => "Total de incoformidades en despachos",
        "description" => " % Total de facturas (despachos externos) vs % de incorfomidades",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 60,
        "isAscending" => true,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "75089704",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza",
        "metric" => "Minutos",
        "goal" => 3,
        "weight" => 40,
        "isAscending" => false,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "46337540",
        "objective" => "Cumplimiento mensual de los límites de velocidad en ruta",
        "description" => "Total de viajes por despachos del mes no excedan los 80km\/hr. (Control GPS)",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor II"
      ],
      [
        "dni" => "46337540",
        "objective" => "Cero multas de tránsito",
        "description" => "Por infringir las normas de tránsito en el desarrollo de sus funciones",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Conductor II"
      ],
      [
        "dni" => "46337540",
        "objective" => "Promedio mensual de ingreso",
        "description" => "Promedio mensual de marcaciones de ingreso del mes según biométrico.",
        "metric" => "Minutos",
        "goal" => 3,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Conductor II"
      ],
      [
        "dni" => "46337540",
        "objective" => "Cumplimiento de inventario de sede al 100%",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 0,
        "isAscending" => true,
        "categoria" => "Conductor II"
      ],
      [
        "dni" => "46337540",
        "objective" => "Cero incidencias en la ruta asignada (robos, choques,etc)",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Conductor II"
      ],
      [
        "dni" => "72636980",
        "objective" => "Cuadres de caja oportunos",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Caja Dp"
      ],
      [
        "dni" => "72636980",
        "objective" => "Arqueo de caja, sin diferencias",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Caja Dp"
      ],
      [
        "dni" => "62046276",
        "objective" => "Total de incoformidades en despachos",
        "description" => " % Total de facturas (despachos externos) vs % de incorfomidades",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 60,
        "isAscending" => true,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "62046276",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza",
        "metric" => "Minutos",
        "goal" => 3,
        "weight" => 40,
        "isAscending" => false,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "75480931",
        "objective" => "Liquidaciones pendientes durante el mes",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Caja Tp"
      ],
      [
        "dni" => "75480931",
        "objective" => "Arqueo de caja, sin diferencias",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Caja Tp"
      ],
      [
        "dni" => "75480931",
        "objective" => "Cumplimiento promedio de liquidación de vales por arqueo",
        "description" => "Vales pendientes por emitir vs vales levantados según arqueo programado o sorpresivo",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 10,
        "isAscending" => true,
        "categoria" => "Caja Tp"
      ],
      [
        "dni" => "75480931",
        "objective" => "Aplicaciones de CXC ejecutadas al cierre de mes",
        "description" => "Número de ingresos en bancos aplicados despues de plazo",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Caja Tp"
      ],
      [
        "dni" => "75770012",
        "objective" => "Liquidaciones pendientes durante el mes",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Caja Tp"
      ],
      [
        "dni" => "75770012",
        "objective" => "Arqueo de caja, sin diferencias",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Caja Tp"
      ],
      [
        "dni" => "75770012",
        "objective" => "Cumplimiento promedio de liquidación de vales por arqueo",
        "description" => "Vales pendientes por emitir vs vales levantados según arqueo programado o sorpresivo",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 10,
        "isAscending" => true,
        "categoria" => "Caja Tp"
      ],
      [
        "dni" => "75770012",
        "objective" => "Aplicaciones de CXC ejecutadas al cierre de mes",
        "description" => "Número de ingresos en bancos aplicados despues de plazo",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Caja Tp"
      ],
      [
        "dni" => "45853764",
        "objective" => "Cuadres de caja oportunos",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Caja Dp"
      ],
      [
        "dni" => "45853764",
        "objective" => "Arqueo de caja, sin diferencias",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Caja Dp"
      ],
      [
        "dni" => "71606304",
        "objective" => "Reingresos de vehículos",
        "metric" => "Unidad",
        "goal" => 1,
        "weight" => 50,
        "isAscending" => false,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "71606304",
        "objective" => "Eficiencia de OT",
        "description" => "Promedio de productividad de OT atendidas al mes",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "45795895",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidades",
        "goal" => 7,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "45795895",
        "objective" => "Reputation",
        "description" => "Meta regional score superior a 820 puntos",
        "metric" => "Número",
        "goal" => 820,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "45795895",
        "objective" => "Colocación de créditos",
        "metric" => "Unidad",
        "goal" => 3,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "45795895",
        "objective" => "Colocación de seguros",
        "metric" => "Unidad",
        "goal" => 3,
        "weight" => 10,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "71702184",
        "objective" => "Costos de Kilómetro de Neumáticos",
        "description" => " No deben superar los 0.15 sol\/km recorrido",
        "metric" => "Soles",
        "goal" => 0.15,
        "weight" => 15,
        "isAscending" => true,
        "categoria" => "Coordinador De Mantenimiento"
      ],
      [
        "dni" => "71702184",
        "objective" => "Rendimiento de Neumático",
        "description" => "Debe ser como mínimo 11,000 Km\/mm",
        "metric" => "Kilometros",
        "goal" => 11000,
        "weight" => 15,
        "isAscending" => true,
        "categoria" => "Coordinador De Mantenimiento"
      ],
      [
        "dni" => "71702184",
        "objective" => "Mantenimentos correctivos de emergencia",
        "description" => "(Auxilios Mecánicos), no supere el 4% mensual de la flota",
        "metric" => "Porcentaje",
        "goal" => 4,
        "weight" => 15,
        "isAscending" => true,
        "categoria" => "Coordinador De Mantenimiento"
      ],
      [
        "dni" => "71702184",
        "objective" => "Cumplimiento de la programación de mantenimiento preventivo",
        "description" => 0.96,
        "metric" => "Porcentaje",
        "goal" => 96,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Coordinador De Mantenimiento"
      ],
      [
        "dni" => "71702184",
        "objective" => "Números de escaneos mensuales",
        "metric" => "Unidad",
        "goal" => 88,
        "weight" => 15,
        "isAscending" => false,
        "categoria" => "Coordinador De Mantenimiento"
      ],
      [
        "dni" => "71702184",
        "objective" => "Accidente y\/o Siniestros",
        "description" => "Por responsabilidad el equipo de operaciones y mantenimiento",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Coordinador De Mantenimiento"
      ],
      [
        "dni" => "43488881",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidades",
        "goal" => 5,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "43488881",
        "objective" => "Colocación de créditos",
        "metric" => "Unidad",
        "goal" => 1,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "41941867",
        "objective" => "Cumplimiento de presupuesto comercial",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "41941867",
        "objective" => "% de Efectividad de Cobranza",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "41941867",
        "objective" => "Mix de productos",
        "metric" => "Unidad",
        "goal" => 5,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "41941867",
        "objective" => "Cliente Nuevo al mes",
        "metric" => "Unidad",
        "goal" => 2,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "42795204",
        "objective" => "Rendimiento de combustible.",
        "description" => 0.92,
        "metric" => "Porcentaje",
        "goal" => 92,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "42795204",
        "objective" => "Fletes no cubiertos por negativa de conductor",
        "description" => "Flete no asumido por el conductor, más no perdido.",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "42795204",
        "objective" => "Número de incidencias por responsalidad del conductor",
        "description" => "0: No hubo incidencia \/ 1: Incidencia leve \/ 2:Incidencia moderada \/ 3: Incidencia grave",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 30,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "42795204",
        "objective" => "Promedio de conducción",
        "description" => "(Se da un un rango de cumplimiento entre 85 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "77918582",
        "objective" => "Entrega de cierres contables en tiempo de empresa a cargo",
        "metric" => "Días",
        "goal" => 5,
        "weight" => 40,
        "isAscending" => false,
        "categoria" => "Asistente de Contabilidad Volante Ap"
      ],
      [
        "dni" => "77918582",
        "objective" => "Presentación de DDJJ mensuales a SUNAT",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 30,
        "isAscending" => true,
        "categoria" => "Asistente de Contabilidad Volante Ap"
      ],
      [
        "dni" => "77918582",
        "objective" => "Cero soles de multa por errores contables en la empresa a cargo",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 30,
        "isAscending" => false,
        "categoria" => "Asistente de Contabilidad Volante Ap"
      ],
      [
        "dni" => "43084165",
        "objective" => "Cumplimiento de presupuesto comercial",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "43084165",
        "objective" => "% de Efectividad de Cobranza",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "43084165",
        "objective" => "Mix de productos",
        "metric" => "Unidad",
        "goal" => 5,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "43084165",
        "objective" => "Cliente Nuevo al mes",
        "metric" => "Unidad",
        "goal" => 2,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "41903472",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidades",
        "goal" => 7,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "41903472",
        "objective" => "Reputation",
        "description" => "Meta regional score superior a 820 puntos",
        "metric" => "Número",
        "goal" => 820,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "41903472",
        "objective" => "Colocación de créditos",
        "metric" => "Unidad",
        "goal" => 2,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "41903472",
        "objective" => "Colocación de seguros",
        "metric" => "Unidad",
        "goal" => 1,
        "weight" => 10,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "42085586",
        "objective" => "Rendimiento de combustible.",
        "description" => 0.92,
        "metric" => "Porcentaje",
        "goal" => 92,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "42085586",
        "objective" => "Fletes no cubiertos por negativa de conductor",
        "description" => "Flete no asumido por el conductor, más no perdido.",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "42085586",
        "objective" => "Número de incidencias por responsalidad del conductor",
        "description" => "0: No hubo incidencia \/ 1: Incidencia leve \/ 2:Incidencia moderada \/ 3: Incidencia grave",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 30,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "42085586",
        "objective" => "Promedio de conducción",
        "description" => "(Se da un un rango de cumplimiento entre 85 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16736017",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidades",
        "goal" => 6,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "16736017",
        "objective" => "Reputation",
        "description" => "Meta regional score superior a 820 puntos",
        "metric" => "Número",
        "goal" => 820,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "16736017",
        "objective" => "Colocación de créditos",
        "metric" => "Unidad",
        "goal" => 3,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "16736017",
        "objective" => "Colocación de seguros",
        "metric" => "Unidad",
        "goal" => 3,
        "weight" => 10,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "74531701",
        "objective" => "Total de incoformidades en despachos",
        "description" => " % Total de facturas (despachos externos) vs % de incorfomidades",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 60,
        "isAscending" => true,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "74531701",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza",
        "metric" => "Minutos",
        "goal" => 3,
        "weight" => 40,
        "isAscending" => false,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "40734495",
        "objective" => "Cumplimiento de entrega de unidades ligeras",
        "metric" => "Unidad",
        "goal" => 10,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Jefe de Ventas - Camiones"
      ],
      [
        "dni" => "40734495",
        "objective" => "Cumplimiento de entrega de unidades pesadas",
        "metric" => "Unidad",
        "goal" => 4,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Jefe de Ventas - Camiones"
      ],
      [
        "dni" => "46966858",
        "objective" => "Rendimiento de combustible.",
        "description" => 0.92,
        "metric" => "Porcentaje",
        "goal" => 92,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "46966858",
        "objective" => "Fletes no cubiertos por negativa de conductor",
        "description" => "Flete no asumido por el conductor, más no perdido.",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "46966858",
        "objective" => "Número de incidencias por responsalidad del conductor",
        "description" => "0: No hubo incidencia \/ 1: Incidencia leve \/ 2:Incidencia moderada \/ 3: Incidencia grave",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 30,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "46966858",
        "objective" => "Promedio de conducción",
        "description" => "(Se da un un rango de cumplimiento entre 85 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "46670405",
        "objective" => "Total de incoformidades en despachos",
        "description" => " % Total de facturas (despachos externos) vs % de incorfomidades",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 60,
        "isAscending" => true,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "46670405",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza",
        "metric" => "Minutos",
        "goal" => 3,
        "weight" => 40,
        "isAscending" => false,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "42662829",
        "objective" => "Efectividad de proyección de facturación",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 30,
        "isAscending" => true,
        "categoria" => "Asistente De Facturacion Y Cobranza"
      ],
      [
        "dni" => "42662829",
        "objective" => "Recuperación de documentos en tiempo establecido",
        "metric" => "Porcentaje",
        "goal" => 95,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asistente De Facturacion Y Cobranza"
      ],
      [
        "dni" => "42662829",
        "objective" => "Aplicación de detracciones",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asistente De Facturacion Y Cobranza"
      ],
      [
        "dni" => "42662829",
        "objective" => "Efectividad proyección de cobranza",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 30,
        "isAscending" => true,
        "categoria" => "Asistente De Facturacion Y Cobranza"
      ],
      [
        "dni" => "45957690",
        "objective" => "Total de incoformidades en despachos",
        "description" => " % Total de facturas (despachos externos) vs % de incorfomidades",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 60,
        "isAscending" => true,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "45957690",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza",
        "metric" => "Minutos",
        "goal" => 3,
        "weight" => 40,
        "isAscending" => false,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "47552281",
        "objective" => "Cumplimiento de ICD al 90% por sede",
        "metric" => "Porcentaje",
        "goal" => 90,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Asistente De Post Venta"
      ],
      [
        "dni" => "47552281",
        "objective" => "Cumplimiento de los standares del Postventa por parte de Derco al 80%",
        "metric" => "Porcentaje",
        "goal" => 80,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Asistente De Post Venta"
      ],
      [
        "dni" => "16523120",
        "objective" => "Rendimiento de combustible.",
        "description" => 0.92,
        "metric" => "Porcentaje",
        "goal" => 92,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16523120",
        "objective" => "Fletes no cubiertos por negativa de conductor",
        "description" => "Flete no asumido por el conductor, más no perdido.",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16523120",
        "objective" => "Número de incidencias por responsalidad del conductor",
        "description" => "0: No hubo incidencia \/ 1: Incidencia leve \/ 2:Incidencia moderada \/ 3: Incidencia grave",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 30,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "16523120",
        "objective" => "Promedio de conducción",
        "description" => "(Se da un un rango de cumplimiento entre 85 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "71232635",
        "objective" => "Tiempo de entrega de Memorándums",
        "description" => "Promedio de tiempo de entrega no mayor a 10 días hábiles",
        "metric" => "Días",
        "goal" => 10,
        "weight" => 40,
        "isAscending" => false,
        "categoria" => "Asistente Legal"
      ],
      [
        "dni" => "71232635",
        "objective" => "Reclamos atendidos AP en tiempo",
        "description" => "Reclamos atendidos a tiempo durante el mes no mayor a 12 días hábiles",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 60,
        "isAscending" => true,
        "categoria" => "Asistente Legal"
      ],
      [
        "dni" => "19247378",
        "objective" => "Rendimiento de combustible.",
        "description" => 0.92,
        "metric" => "Porcentaje",
        "goal" => 92,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "19247378",
        "objective" => "Fletes no cubiertos por negativa de conductor",
        "description" => "Flete no asumido por el conductor, más no perdido.",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "19247378",
        "objective" => "Número de incidencias por responsalidad del conductor",
        "description" => "0: No hubo incidencia \/ 1: Incidencia leve \/ 2:Incidencia moderada \/ 3: Incidencia grave",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 30,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "19247378",
        "objective" => "Promedio de conducción",
        "description" => "(Se da un un rango de cumplimiento entre 85 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "76332500",
        "objective" => "Reingresos de vehículos",
        "metric" => "Unidad",
        "goal" => 1,
        "weight" => 50,
        "isAscending" => false,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "76332500",
        "objective" => "Eficiencia de OT",
        "description" => "Promedio de productividad de OT atendidas al mes",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "72936727",
        "objective" => "Reingresos de vehículos",
        "metric" => "Unidad",
        "goal" => 1,
        "weight" => 50,
        "isAscending" => false,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "72936727",
        "objective" => "Eficiencia de OT",
        "description" => "Promedio de productividad de OT atendidas al mes",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Tecnico Mecanico Ap"
      ],
      [
        "dni" => "73365479",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidades",
        "goal" => 3,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "73365479",
        "objective" => "Colocación de créditos",
        "metric" => "Unidad",
        "goal" => 1,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "41037736",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidades",
        "goal" => 5,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "41037736",
        "objective" => "Reputation",
        "description" => "Meta regional score superior a 820 puntos",
        "metric" => "Número",
        "goal" => 820,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "41037736",
        "objective" => "Colocación de créditos",
        "metric" => "Unidad",
        "goal" => 2,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "41037736",
        "objective" => "Colocación de seguros",
        "metric" => "Unidad",
        "goal" => 2,
        "weight" => 10,
        "isAscending" => true,
        "categoria" => "Asesor Comercial"
      ],
      [
        "dni" => "80590892",
        "objective" => "Rendimiento de combustible.",
        "description" => 0.92,
        "metric" => "Porcentaje",
        "goal" => 92,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "80590892",
        "objective" => "Fletes no cubiertos por negativa de conductor",
        "description" => "Flete no asumido por el conductor, más no perdido.",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "80590892",
        "objective" => "Número de incidencias por responsalidad del conductor",
        "description" => "0: No hubo incidencia \/ 1: Incidencia leve \/ 2:Incidencia moderada \/ 3: Incidencia grave",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 30,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "80590892",
        "objective" => "Promedio de conducción",
        "description" => "(Se da un un rango de cumplimiento entre 85 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "44265001",
        "objective" => "Rendimiento de combustible.",
        "description" => 0.92,
        "metric" => "Porcentaje",
        "goal" => 92,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "44265001",
        "objective" => "Fletes no cubiertos por negativa de conductor",
        "description" => "Flete no asumido por el conductor, más no perdido.",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "44265001",
        "objective" => "Número de incidencias por responsalidad del conductor",
        "description" => "0: No hubo incidencia \/ 1: Incidencia leve \/ 2:Incidencia moderada \/ 3: Incidencia grave",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 30,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "44265001",
        "objective" => "Promedio de conducción",
        "description" => "(Se da un un rango de cumplimiento entre 85 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "10427037",
        "objective" => "Rendimiento de combustible.",
        "description" => 0.92,
        "metric" => "Porcentaje",
        "goal" => 92,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "10427037",
        "objective" => "Fletes no cubiertos por negativa de conductor",
        "description" => "Flete no asumido por el conductor, más no perdido.",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "10427037",
        "objective" => "Número de incidencias por responsalidad del conductor",
        "description" => "0: No hubo incidencia \/ 1: Incidencia leve \/ 2:Incidencia moderada \/ 3: Incidencia grave",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 30,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "10427037",
        "objective" => "Promedio de conducción",
        "description" => "(Se da un un rango de cumplimiento entre 85 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "43141821",
        "objective" => "Rendimiento de combustible.",
        "description" => 0.92,
        "metric" => "Porcentaje",
        "goal" => 92,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "43141821",
        "objective" => "Fletes no cubiertos por negativa de conductor",
        "description" => "Flete no asumido por el conductor, más no perdido.",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "43141821",
        "objective" => "Número de incidencias por responsalidad del conductor",
        "description" => "0: No hubo incidencia \/ 1: Incidencia leve \/ 2:Incidencia moderada \/ 3: Incidencia grave",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 30,
        "isAscending" => false,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "43141821",
        "objective" => "Promedio de conducción",
        "description" => "(Se da un un rango de cumplimiento entre 85 y 90)",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor De Tracto Camion"
      ],
      [
        "dni" => "47221021",
        "objective" => "Total de incoformidades en despachos",
        "description" => " % Total de facturas (despachos externos) vs % de incorfomidades",
        "metric" => "Porcentaje",
        "goal" => 85,
        "weight" => 60,
        "isAscending" => true,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "47221021",
        "objective" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza",
        "metric" => "Minutos",
        "goal" => 3,
        "weight" => 40,
        "isAscending" => false,
        "categoria" => "Asistente De Reparto"
      ],
      [
        "dni" => "42091625",
        "objective" => "Cuadres de caja oportunos",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Caja Dp"
      ],
      [
        "dni" => "42091625",
        "objective" => "Arqueo de caja, sin diferencias",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Caja Dp"
      ],
      [
        "dni" => "16664102",
        "objective" => "Cumplimiento de ventas en sede",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 0,
        "isAscending" => true,
        "categoria" => "Jefe De Administracion"
      ],
      [
        "dni" => "16664102",
        "objective" => "Cumplimiento de cobranza de sede",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 0,
        "isAscending" => true,
        "categoria" => "Jefe De Administracion"
      ],
      [
        "dni" => "45017690",
        "objective" => "Pago a proveedores",
        "metric" => "Porcentaje",
        "goal" => 0,
        "weight" => 0,
        "isAscending" => false,
        "categoria" => "Jefe De Administracion"
      ],
      [
        "dni" => "40524259",
        "objective" => "Cumplimiento de ventas en sede",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 0,
        "isAscending" => true,
        "categoria" => "Jefe De Administracion"
      ],
      [
        "dni" => "40524259",
        "objective" => "Cumplimiento de cobranza de sede",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 0,
        "isAscending" => true,
        "categoria" => "Jefe De Administracion"
      ],
      [
        "dni" => "09994093",
        "objective" => "Cumplimiento de ventas en sede",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 0,
        "isAscending" => true,
        "categoria" => "Jefe De Administracion"
      ],
      [
        "dni" => "09994093",
        "objective" => "Cumplimiento de cobranza de sede",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 0,
        "isAscending" => true,
        "categoria" => "Jefe De Administracion"
      ],
      [
        "dni" => "46304286",
        "objective" => "Cumplimiento de ventas en sede",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 0,
        "isAscending" => true,
        "categoria" => "Jefe De Administracion"
      ],
      [
        "dni" => "46304286",
        "objective" => "Cumplimiento de cobranza de sede",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 0,
        "isAscending" => true,
        "categoria" => "Jefe De Administracion"
      ],
      [
        "dni" => "73271353",
        "objective" => "Cumplimiento promedio de registro de compras en el sistema (diario)",
        "description" => "Compras vs registro en sistema propio",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 30,
        "isAscending" => true,
        "categoria" => "Coordinador De Ventas"
      ],
      [
        "dni" => "73271353",
        "objective" => "Liquidaciones dentro del plazo estipulado",
        "description" => "Número de semanas con aplicaciones dentro del plazo",
        "metric" => "Unidad",
        "goal" => 4,
        "weight" => 30,
        "isAscending" => true,
        "categoria" => "Coordinador De Ventas"
      ],
      [
        "dni" => "73271353",
        "objective" => "Cumplimiento promedio de reportería en Dealer Portal (diario)",
        "description" => "Facturación vs reporte TICs ",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 30,
        "isAscending" => true,
        "categoria" => "Coordinador De Ventas"
      ],
      [
        "dni" => "73271353",
        "objective" => "Cumplimiento de envío de las solicitudes de nota de crédito en tiempo establecido",
        "metric" => "Días",
        "goal" => 3,
        "weight" => 10,
        "isAscending" => false,
        "categoria" => "Coordinador De Ventas"
      ],
      [
        "dni" => "45806884",
        "objective" => "Cumplimiento mensual de los límites de velocidad en ruta",
        "description" => "Total de viajes por despachos del mes no excedan los 80km\/hr. (Control GPS)",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Conductor II"
      ],
      [
        "dni" => "45806884",
        "objective" => "Cero multas de tránsito",
        "description" => "Por infringir las normas de tránsito en el desarrollo de sus funciones",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Conductor II"
      ],
      [
        "dni" => "45806884",
        "objective" => "Promedio mensual de ingreso",
        "description" => "Promedio mensual de marcaciones de ingreso del mes según biométrico.",
        "metric" => "Minutos",
        "goal" => 3,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Conductor II"
      ],
      [
        "dni" => "45806884",
        "objective" => "Cumplimiento de inventario de sede al 100%",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 0,
        "isAscending" => true,
        "categoria" => "Conductor II"
      ],
      [
        "dni" => "45806884",
        "objective" => "Cero incidencias en la ruta asignada (robos, choques,etc)",
        "metric" => "Unidad",
        "goal" => 0,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Conductor II"
      ],
      [
        "dni" => "71325400",
        "objective" => "Cero errores en el registro de información contable de manera mensual",
        "metric" => "Errores",
        "goal" => 2,
        "weight" => 0,
        "isAscending" => false,
        "categoria" => "Asistente De Contabilidad"
      ],
      [
        "dni" => "71325400",
        "objective" => "Entrega de cierre contable mensual integral (en los primeros 12 días calendario)",
        "metric" => "Días",
        "goal" => 12,
        "weight" => 0,
        "isAscending" => false,
        "categoria" => "Asistente De Contabilidad"
      ],
      [
        "dni" => "48493314",
        "objective" => "Cero errores en el registro de información contable de manera mensual",
        "metric" => "Errores",
        "goal" => 2,
        "weight" => 0,
        "isAscending" => false,
        "categoria" => "Asistente De Contabilidad"
      ],
      [
        "dni" => "48493314",
        "objective" => "Entrega de cierre contable mensual integral (en los primeros 12 días calendario)",
        "metric" => "Días",
        "goal" => 12,
        "weight" => 0,
        "isAscending" => false,
        "categoria" => "Asistente De Contabilidad"
      ],
      [
        "dni" => "74443725",
        "objective" => "Exactitud en registros contables",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 0,
        "isAscending" => false,
        "categoria" => "Asistente De Contabilidad"
      ],
      [
        "dni" => "74443725",
        "objective" => "Cero errores en el registro de información contable de manera mensual",
        "metric" => "Errores",
        "goal" => 2,
        "weight" => 0,
        "isAscending" => false,
        "categoria" => "Asistente De Contabilidad"
      ],
      [
        "dni" => "74443725",
        "objective" => "Entrega de cierre contable mensual integral (en los primeros 12 días calendario)",
        "metric" => "Días",
        "goal" => 12,
        "weight" => 0,
        "isAscending" => false,
        "categoria" => "Asistente De Contabilidad"
      ],
      [
        "dni" => "02855107",
        "objective" => "Exactitud en registros contables",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 0,
        "isAscending" => false,
        "categoria" => "Asistente De Contabilidad"
      ],
      [
        "dni" => "02855107",
        "objective" => "Entrega de cierres contables en tiempo de empresa a cargo",
        "metric" => "Días",
        "goal" => 5,
        "weight" => 0,
        "isAscending" => true,
        "categoria" => "Asistente De Contabilidad"
      ],
      [
        "dni" => "72722099",
        "objective" => "Exactitud en registros contables",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 0,
        "isAscending" => false,
        "categoria" => "Asistente De Contabilidad"
      ],
      [
        "dni" => "72722099",
        "objective" => "Entrega de cierres contables en tiempo de empresa a cargo",
        "metric" => "Días",
        "goal" => 5,
        "weight" => 0,
        "isAscending" => true,
        "categoria" => "Asistente De Contabilidad"
      ],
      [
        "dni" => "74624087",
        "objective" => "Exactitud en registros contables",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 0,
        "isAscending" => false,
        "categoria" => "Asistente De Contabilidad"
      ],
      [
        "dni" => "74624087",
        "objective" => "Entrega de cierres contables en tiempo de empresa a cargo",
        "metric" => "Días",
        "goal" => 5,
        "weight" => 0,
        "isAscending" => true,
        "categoria" => "Asistente De Contabilidad"
      ],
      [
        "dni" => "42704083",
        "objective" => "Rentabilidad neta acumulada del canal de Industria",
        "description" => "Rentabilidad acumulada se las 4 sedes a cargo",
        "metric" => "Porcentaje",
        "goal" => 8,
        "weight" => 30,
        "isAscending" => true,
        "categoria" => "Ejecutivo Comercial"
      ],
      [
        "dni" => "76449504",
        "objective" => "Cumplir de paso vehicular",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 0,
        "isAscending" => true,
        "categoria" => "Asesor De Servicios"
      ],
      [
        "dni" => "76449504",
        "objective" => "Cumplimiento de meta general (facturación)",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 0,
        "isAscending" => true,
        "categoria" => "Asesor De Servicios"
      ],
      [
        "dni" => "76449504",
        "objective" => "Reputation",
        "description" => "Meta regional score superior a 820 puntos",
        "metric" => "Número",
        "goal" => 820,
        "weight" => 0,
        "isAscending" => true,
        "categoria" => "Asesor De Servicios"
      ],
      [
        "dni" => "47844976",
        "objective" => "Cumplir de paso vehicular",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asesor De Servicios"
      ],
      [
        "dni" => "47844976",
        "objective" => "Cumplimiento de meta general (facturación)",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asesor De Servicios"
      ],
      [
        "dni" => "47844976",
        "objective" => "Reputation",
        "description" => "Meta regional score superior a 820 puntos",
        "metric" => "Número",
        "goal" => 820,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asesor De Servicios"
      ],
      [
        "dni" => "02899619",
        "objective" => "Número de unidades entregadas",
        "metric" => "Unidades",
        "goal" => 25,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Jefe De Ventas Ap"
      ],
      [
        "dni" => "02899619",
        "objective" => "Reputation",
        "description" => "Meta regional score superior a 820 puntos",
        "metric" => "Número",
        "goal" => 820,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Jefe De Ventas Ap"
      ],
      [
        "dni" => "02899619",
        "objective" => "Meta en seguros",
        "metric" => "Número",
        "goal" => 10,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Jefe De Ventas Ap"
      ],
      [
        "dni" => "02899619",
        "objective" => "Meta en créditos",
        "metric" => "Número",
        "goal" => 10,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Jefe De Ventas Ap"
      ],
      [
        "dni" => "02899619",
        "objective" => "Alcance de Mix de Marca",
        "metric" => "Unidad",
        "goal" => 3,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Jefe De Ventas Ap"
      ],
      [
        "dni" => "45206383",
        "objective" => "Plazo de entrega de documentos contables",
        "description" => "Plazo de entrega durante los primeros 4 días calendario del mes (notas de crédicto, débito y otros
relacionados a la contabilidad)",
        "metric" => "Días",
        "goal" => 4,
        "weight" => 33,
        "isAscending" => false,
        "categoria" => "Asistente Administrativo DP"
      ],
      [
        "dni" => "45206383",
        "objective" => "Cierre de liquidaciones RIQRA, acuerdos comerciales y otros proveedores",
        "description" => "Plazo de entrega durante los primeros 10 días calendario del mes",
        "metric" => "Días",
        "goal" => 10,
        "weight" => 34,
        "isAscending" => false,
        "categoria" => "Asistente Administrativo DP"
      ],
      [
        "dni" => "45206383",
        "objective" => "Aprobación correcta de descuentos",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 33,
        "isAscending" => true,
        "categoria" => "Asistente Administrativo DP"
      ],
      [
        "dni" => "16729345",
        "objective" => "Cero soles de multa por errores contables en la empresa a cargo",
        "metric" => "Número",
        "goal" => 0,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Asistente De Contabilidad"
      ],
      [
        "dni" => "16729345",
        "objective" => "Exactitud en registros contables",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Asistente De Contabilidad"
      ],
      [
        "dni" => "16729345",
        "objective" => "Entrega de cierres contables en tiempo de empresa a cargo",
        "metric" => "Días",
        "goal" => 5,
        "weight" => 20,
        "isAscending" => true,
        "categoria" => "Asistente De Contabilidad"
      ],
      [
        "dni" => "16729345",
        "objective" => "Cero errores en el registro de información contable de manera mensual",
        "metric" => "Errores",
        "goal" => 2,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Asistente De Contabilidad"
      ],
      [
        "dni" => "16729345",
        "objective" => "Entrega de cierre contable mensual integral (en los primeros 12 días calendario)",
        "metric" => "Días",
        "goal" => 12,
        "weight" => 20,
        "isAscending" => false,
        "categoria" => "Asistente De Contabilidad"
      ],
      [
        "dni" => "75262550",
        "objective" => "Cumplimiento de inmatriculación",
        "description" => "Número de inmatriculaciones al mes vs facturación",
        "metric" => "Porcentaje",
        "goal" => 95,
        "weight" => 100,
        "isAscending" => true,
        "categoria" => "Gestor De Inmatriculacion"
      ],
      [
        "dni" => "71071635",
        "objective" => "Nivel de servicio",
        "description" => "Cantidad de repuestos para atención de clientes vs demanda",
        "metric" => "Porcentaje",
        "goal" => 90,
        "weight" => 50,
        "isAscending" => true,
        "categoria" => "Asistente De Almacén Ap"
      ],
      [
        "dni" => "71071635",
        "objective" => "Control de Inventario",
        "description" => "Resultado de inventario físico por sede vs sistema",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Asistente De Almacén Ap"
      ],
      [
        "dni" => "71071635",
        "objective" => "Días de stock de material",
        "metric" => "Días",
        "goal" => 30,
        "weight" => 25,
        "isAscending" => false,
        "categoria" => "Asistente De Almacén Ap"
      ],
      [
        "dni" => "75085359",
        "objective" => "Cumplimiento del cronograma en el desarrollo del sistema Milla AP",
        "description" => "Actividades programadas vs ejecutadas en tiempo",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Desarrollador de Software"
      ],
      [
        "dni" => "75085359",
        "objective" => "Calidad del software elaborados en el mes",
        "description" => "1 al 4 (metdologia SCRUN)",
        "metric" => "Unidad",
        "goal" => 4,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Desarrollador de Software"
      ],
      [
        "dni" => "75085359",
        "objective" => "Incidencias resueltas en el equipo",
        "metric" => "Número",
        "goal" => 6,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Desarrollador de Software"
      ],
      [
        "dni" => "75085359",
        "objective" => "Nivel de satisfacción de los stakeholders respecto a la calidad de las entregas del sistema",
        "description" => "Calificación bajo encuesta de satisfacción del 1 al 5",
        "metric" => "Unidad",
        "goal" => 5,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Desarrollador de Software"
      ],
      [
        "dni" => "73144212",
        "objective" => "Cumplimiento del cronograma en el desarrollo del sistema Milla AP",
        "description" => "Actividades programadas vs ejecutadas en tiempo",
        "metric" => "Porcentaje",
        "goal" => 100,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Desarrollador de Software"
      ],
      [
        "dni" => "73144212",
        "objective" => "Calidad del software elaborados en el mes",
        "description" => "1 al 4 (metdologia SCRUN)",
        "metric" => "Unidad",
        "goal" => 4,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Desarrollador de Software"
      ],
      [
        "dni" => "73144212",
        "objective" => "Incidencias resueltas en el equipo",
        "metric" => "Número",
        "goal" => 6,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Desarrollador de Software"
      ],
      [
        "dni" => "73144212",
        "objective" => "Nivel de satisfacción de los stakeholders respecto a la calidad de las entregas del sistema",
        "description" => "Calificación bajo encuesta de satisfacción del 1 al 5",
        "metric" => "Unidad",
        "goal" => 5,
        "weight" => 25,
        "isAscending" => true,
        "categoria" => "Desarrollador de Software"
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

    //    2. Definir servicios
    $categoryObjectiveService = new EvaluationCategoryObjectiveDetailService();

    DB::beginTransaction();

    try {
//    Mapear todos los datos
      foreach ($data as $item) {
        $person = Worker::where('vat', $item['dni'])->where('status_id', 22)->first();

        $category = HierarchicalCategory::where('name', $item['categoria'])->first();

        $objective = EvaluationObjective::where('name', $item['objective'])->where('active', 1)->first();
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
      DB::commit();
    } catch (\Exception $e) {
      DB::rollBack();
      echo "Error: " . $e->getMessage() . "\n";
    }
  }
}
