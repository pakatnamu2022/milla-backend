<?php

namespace Database\Seeders\gp\evaluation;

use App\Models\gp\gestionhumana\evaluacion\EvaluationCategoryObjectiveDetail;
use App\Models\gp\gestionhumana\evaluacion\EvaluationObjective;
use App\Models\gp\gestionhumana\evaluacion\HierarchicalCategory;
use App\Models\gp\gestionhumana\evaluacion\HierarchicalCategoryDetail;
use App\Models\gp\gestionsistema\Position;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HierarchicalCategorySeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    $data = [
      [
        "name" => "Jefe De Operaciones Y Mantenimiento",
        "positions" => ["289"],
        "objectives" => [["name" => "Controlar el costo operacional (Costo venta/kilometraje) que no supere 4.5 sol/km", "metric" => 2], ["name" => "Controlar el costo kilometro de combustible no supere los 2.0 sol/km", "metric" => 2], ["name" => "Controlar los gastos reparables que no superen los 50,000 8", "metric" => 2], ["name" => "Mantener la disponibilidad al 96% unidades completas(conductor y vehículo)", "metric" => 2], ["name" => "Accidente y/o Siniestros por responsabilidad el equipo de operaciones y mantenimiento", "metric" => 2]]
      ],
      [
        "name" => "Logística TP",
        "positions" => ["21"],
        "objectives" => [["name" => "Inventarios cíclicos", "metric" => 2], ["name" => "Control del costo de Suministros (ingresos y salidas)", "metric" => 2]]
      ],
      [
        "name" => "Supervisión de operaciones y mantenimiento",
        "positions" => ["290"],
        "objectives" => [["name" => "Mantener la disponibilidad al 96% unidades completas(conductor y vehículo)", "metric" => 2], ["name" => "Mantener un 85 % de cumplimiento de la programaciòn (Servicios entregados a tiempo/total de servicios programados)", "metric" => 2], ["name" => "Controlar los descansos no superar los 450 dias pendientes al mes.", "metric" => 2], ["name" => "Los costos de mantenimiento no deben superar los 0.30 8/km recorrido", "metric" => 2], ["name" => "No tener multas por documentacion (guías, revisión técnica, bonificaciòn, soat,certificado MTC)", "metric" => 2], ["name" => "Accidente y/o Siniestros por responsabilidad el equipo de operaciones y mantenimiento", "metric" => 2]]
      ],
      [
        "name" => "Gerente De Negocio TP",
        "positions" => ["22"],
        "objectives" => [["name" => "Alcanzar 2 mínimo de fletes al mes", "metric" => 2], ["name" => "Rentabilizar el negocio: margen bruto mensual", "metric" => 2]]
      ],
      [
        "name" => "Asistente Mecánico TP",
        "positions" => ["13"],
        "objectives" => [["name" => "Optimizar los tiempos de reparacion en un 55%", "metric" => 2], ["name" => "Mantenimentos correctivos de emergencia(Auxilios Mecánicos), no supere el 5% mensual de la flota", "metric" => 2]]
      ],
      [
        "name" => "Analista De Contabilidad TP",
        "positions" => ["282"],
        "objectives" => [["name" => "Cero 8 de multa por errores contables en la empresa a cargo", "metric" => 2], ["name" => "Entrega de cierres contables en tiempo de empresa a cargo (en los primeros 10 dias habiles)", "metric" => 2]]
      ],
      [
        "name" => "Asistente De Contabilidad TP",
        "positions" => ["283"],
        "objectives" => [["name" => "Cero 8 de multa por errores contables en la empresa a cargo", "metric" => 2], ["name" => "Entrega de cierres contables en tiempo de empresa a cargo (en los primeros 5 dias habiles)", "metric" => 2], ["name" => "Cero 8 de multa por errores contables en la empresa a cargo", "metric" => 2], ["name" => "Entrega de cierres contables en tiempo de empresa a cargo (en los primeros 5 dias habiles)", "metric" => 2]]
      ],
      [
        "name" => "Asistente De Facturación Y Cobranza",
        "positions" => ["2"],
        "objectives" => [["name" => "Cumplimiento con la actualizacion de informacion hoja de trabajo de Fact y cobranza", "metric" => 2], ["name" => "Recuperación de documentos", "metric" => 2], ["name" => "Control de parihuelas Gloria", "metric" => 2], ["name" => "Aplicación de detracciones", "metric" => 2], ["name" => "Cumplimiento con la recuperación de documentos para facturación", "metric" => 2], ["name" => "Efectividad de proyección de facturación", "metric" => 2], ["name" => "Recuperación de documentos", "metric" => 2], ["name" => "Efectividad proyección de cobranza", "metric" => 2], ["name" => "22", "metric" => 2], ["name" => "Recuperación de documentos", "metric" => 2], ["name" => "Recuperación de documentos", "metric" => 2], ["name" => "Recuperación de documentos", "metric" => 2]]
      ],
      [
        "name" => "Asistente De Operaciones",
        "positions" => ["6"],
        "objectives" => [["name" => "Los costos de Kilómetro de combustible no deben superar los 1.65/km recorrido", "metric" => 2], ["name" => "Rendimiento promedio no debe superar 8.6 km/gln", "metric" => 2], ["name" => "Accidente y/o Siniestros por responsabilidad el equipo de operaciones y mantenimiento", "metric" => 2], ["name" => "Cumplir con la implementaciòn de las unidades y su debido registro al 100%, según condicion del cliente (indicador rìgido)", "metric" => 2], ["name" => "Cumplir con el registro al 100% de robos de implementos, mercaderìa e incidencias.", "metric" => 2], ["name" => "Realizar inventario mensuales del 80% de toda la flota (como mìnimo) con la finalidad de verificar las condiciones de los implementos", "metric" => 2], ["name" => "Cero multas por documentacion vencida, mantener actualizada de todas las unidades.(revisiòn tècnica, bonificaciòn, soat,certificado MTC)", "metric" => 2], ["name" => "Accidente y/o Siniestros por responsabilidad el equipo de operaciones y mantenimiento", "metric" => 2]]
      ],
      [
        "name" => "Asistente De Seguimiento Y Monitoreo",
        "positions" => ["5", "304"],
        "objectives" => [["name" => "Incrementar el % de cumplimiento de la programaciòn (Servicios entregados a tiempo/total de servicios programados)", "metric" => 2], ["name" => "No tener robos por falta de seguimiento y monitoreo", "metric" => 2], ["name" => "Accidente y/o Siniestros por responsabilidad el equipo de operaciones y mantenimiento", "metric" => 2], ["name" => "Incrementar el % de cumplimiento de la programaciòn (Servicios entregados a tiempo/total de servicios programados)", "metric" => 2], ["name" => "No tener robos por falta de seguimiento y monitoreo", "metric" => 2], ["name" => "Accidente y/o Siniestros por responsabilidad el equipo de operaciones y mantenimiento", "metric" => 2], ["name" => "Incrementar el % de cumplimiento de la programaciòn (Servicios entregados a tiempo/total de servicios programados)", "metric" => 2], ["name" => "No tener robos por falta de seguimiento y monitoreo", "metric" => 2], ["name" => "Accidente y/o Siniestros por responsabilidad el equipo de operaciones y mantenimiento", "metric" => 2]]
      ],
      [
        "name" => "Asistente En Proyecto Tics",
        "positions" => ["317"],
        "objectives" => [["name" => "Calidad de servicio - Mesa de ayuda (Milla / Dynamics)", "metric" => 2], ["name" => "Promedio de efectividad en tiempo de atención - Soporte TIC's", "metric" => 2]]
      ],
      [
        "name" => "Auxiliar De Operaciones Lima",
        "positions" => ["8"],
        "objectives" => [["name" => "Apoyo en otras actividades de operativas con las demas àreas", "metric" => 2], ["name" => "Incidentes con responsalidad del auxiliar de operaciones", "metric" => 2]]
      ],
      [
        "name" => "Conductor De Tracto Camion",
        "positions" => ["11"],
        "objectives" => [["name" => "Tener mínimo un rendimiento de combustible del 92%", "metric" => 2], ["name" => "No tener incidencias, con responsalidad del conductor", "metric" => 2], ["name" => "No tener multas por llenado por mal llenado de guías", "metric" => 2], ["name" => "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)", "metric" => 2]]
      ],
      [
        "name" => "Coordinador De Mantenimiento",
        "positions" => ["291"],
        "objectives" => [["name" => "Los costos de  Kilómetro de Neumáticos no deben superar los 0.16 sol/km recorrido", "metric" => 2], ["name" => "Rendimiento de Neumatico debe ser como mínimo 11,000 Km/mm", "metric" => 2], ["name" => "Mantenimentos correctivos de emergencia(Auxilios Mecánicos), no supere el 5% mensual de la flota", "metric" => 2], ["name" => "Cumplir al menos con el 96% de la programación de mantenimiento preventivo", "metric" => 2], ["name" => "Números de escaneos mensuales mínimo 105", "metric" => 2], ["name" => "Accidente y/o Siniestros por responsabilidad el equipo de operaciones y mantenimiento", "metric" => 2]]
      ],
      [
        "name" => "Maestro Mecanico",
        "positions" => ["17"],
        "objectives" => [["name" => "Optimizar los tiempos de reparacion en un 55%", "metric" => 2], ["name" => "Mantenimentos correctivos de emergencia(Auxilios Mecánicos), no supere el 5% mensual de la flota", "metric" => 2]]
      ],
      [
        "name" => "Supervisor Comercial",
        "positions" => ["324"],
        "objectives" => [["name" => "Utilidad Operativa", "metric" => 2], ["name" => "Número de viajes", "metric" => 2], ["name" => "Utilidad Operativa", "metric" => 8], ["name" => "Número de viajes", "metric" => 2]]
      ],
      [
        "name" => "Tecnico Soldador",
        "positions" => ["15"],
        "objectives" => [["name" => "Optimizar los tiempos de reparacion en un 55%", "metric" => 6], ["name" => "Mantenimentos correctivos de emergencia(Auxilios Mecánicos), no supere el 5% mensual de la flota", "metric" => 6]]
      ],
      [
        "name" => "Tecnico Electricista",
        "positions" => ["16", "19"],
        "objectives" => [["name" => "Optimizar los tiempos de reparacion en un 55%", "metric" => 2], ["name" => "Mantenimentos correctivos de emergencia(Auxilios Mecánicos), no supere el 5% mensual de la flota", "metric" => 2], ["name" => "Optimizar los tiempos de reparacion en un 55%", "metric" => 2], ["name" => "Mantenimentos correctivos de emergencia(Auxilios Mecánicos), no supere el 5% mensual de la flota", "metric" => 2], ["name" => "Optimizar los tiempos de reparacion en un 55%", "metric" => 2], ["name" => "Mantenimentos correctivos de emergencia(Auxilios Mecánicos), no supere el 5% mensual de la flota", "metric" => 2], ["name" => "Optimizar los tiempos de reparacion en un 55%", "metric" => 6], ["name" => "Mantenimentos correctivos de emergencia(Auxilios Mecánicos), no supere el 5% mensual de la flota", "metric" => 6]]
      ],
      [
        "name" => "Partner De Gestión Humana",
        "positions" => ["337"],
        "objectives" => [["name" => "Promedio de vacaciones DP y TP", "metric" => 2], ["name" => "Procesos dentro del plazo", "metric" => 2], ["name" => "6 de legajos digitalizados", "metric" => 2], ["name" => "Plazo de envio de boletas DP", "metric" => 2], ["name" => "Plazo de envio de boletas TP", "metric" => 2], ["name" => "Promedio de vacaciones DP y TP", "metric" => 9], ["name" => "Procesos dentro del plazo", "metric" => 6], ["name" => "6 de legajos digitalizados", "metric" => 6], ["name" => "Plazo de envio de boletas DP", "metric" => 2], ["name" => "Plazo de envio de boletas TP", "metric" => 2]]
      ],
      [
        "name" => "Gestor Comercial",
        "positions" => ["3"],
        "objectives" => [["name" => "Programación de flota", "metric" => 2], ["name" => "Incremento en ventas", "metric" => 2], ["name" => "Rentabilidad", "metric" => 2], ["name" => "Generación de guías", "metric" => 2], ["name" => "Actualización del sistema", "metric" => 2], ["name" => "Actualización de pesos en sistema", "metric" => 2], ["name" => "Recuperación y control de parihuelas cerámicos", "metric" => 2], ["name" => "Control de parihuelas Celima", "metric" => 2], ["name" => "Cumplimiento de facturación cerámicos", "metric" => 2], ["name" => "Generación de guías", "metric" => 6], ["name" => "Actualización del sistema", "metric" => 6], ["name" => "Actualización de pesos en sistema", "metric" => 6], ["name" => "Programación de flota", "metric" => 6], ["name" => "Incremento en ventas", "metric" => 6], ["name" => "Rentabilidad", "metric" => 6], ["name" => "Captación y propuesta de nuevos clientes", "metric" => 2], ["name" => "Seguimiento, solicitud y cumplimiento de citas cerámicos", "metric" => 6], ["name" => "Recuperación de documentos cerámicos", "metric" => 6], ["name" => "Recuperación y control de parihuelas cerámicos", "metric" => 6], ["name" => "Control de parihuelas Celima", "metric" => 6], ["name" => "Cumplimiento de facturación cerámicos", "metric" => 6]]
      ],
      [
        "name" => "Gerente General",
        "positions" => ["23"],
        "objectives" => [["name" => [], "metric" => []]]
      ],
      [
        "name" => "Jefe De Legal",
        "positions" => ["24"],
        "objectives" => [["name" => "Promedio de cumplimiento de presupuesto mensual de AP, DP y TP", "metric" => 6]]
      ],
      [
        "name" => "Gerente De Gestión Humana",
        "positions" => ["26"],
        "objectives" => [["name" => "Promedio de cumplimiento de presupuesto mensual de AP, DP y TP", "metric" => 6], ["name" => "Tiempo de reclutamiento", "metric" => 6], ["name" => "Asegurar pagos correctos", "metric" => 6]]
      ],
      [
        "name" => "Asistente de Atracción De Talento",
        "positions" => ["31"],
        "objectives" => [["name" => "Procesos dentro del plazo", "metric" => 6], ["name" => "Calidad de reclutamiento", "metric" => 6], ["name" => "CV recibidos marca empleadora", "metric" => 9]]
      ],
      [
        "name" => "Gerente De Administración Y Finanzas",
        "positions" => ["37"],
        "objectives" => [["name" => "Promedio de cumplimiento de presupuesto mensual de AP, DP y TP", "metric" => 6]]
      ],
      [
        "name" => "Jefe De Finanzas Y Tesorería",
        "positions" => ["41"],
        "objectives" => [["name" => "Reportes semanales de CxC", "metric" => 6], ["name" => "Cumplimiento de programacion de visitas a sedes con la finalidad de supervisar procedimientos y control de caja", "metric" => 6], ["name" => "Reducción de gastos financieros", "metric" => 6], ["name" => "Reducción de indice de deuda a proveedores", "metric" => 6]]
      ],
      [
        "name" => "Asistente De Contabilidad",
        "positions" => ["47"],
        "objectives" => [["name" => "Cero 8 de multa por errores contables en la empresa a cargo", "metric" => 8], ["name" => "Entrega de cierres contables en tiempo de empresa a cargo (en los primeros 7 dias habiles)", "metric" => 2]]
      ],
      [
        "name" => "Jefe De Tic'S",
        "positions" => ["345"],
        "objectives" => [["name" => "Cumplimiento de cronogramas de desarrollo y mantenimiento de software (Milla, Quiter, Dynamic )", "metric" => 6], ["name" => "Efectividad en el tiempo de atencion en el servicio", "metric" => 7], ["name" => "Calidad de servicio del área", "metric" => 6], ["name" => "Promedio de cumplimiento de presupuesto mensual de AP, DP y TP", "metric" => 6]]
      ],
      [
        "name" => "Analista De Remuneraciones Y Compensaciones",
        "positions" => ["53"],
        "objectives" => [["name" => "Pagos ejecutados oportunamente", "metric" => 6], ["name" => "Presentación contable de planilla", "metric" => 2], ["name" => "Pagos ejecutados correctamente", "metric" => 6]]
      ],
      [
        "name" => "Auditor Interno",
        "positions" => ["220"],
        "objectives" => [["name" => [], "metric" => []]]
      ],
      [
        "name" => "Supervisor De Seguridad Patrimonial",
        "positions" => ["254"],
        "objectives" => [["name" => "Cumplimiento de la programacion de  capacitaciones a los agentes", "metric" => 6], ["name" => "Disminución de 2 de agentes que abandonen su labor antes de cumplimiento de contrato", "metric" => 9], ["name" => "Disminución de la tasa % de robos o perdidas de activos de propiedad de la empresa", "metric" => 9]]
      ],
      [
        "name" => "Operador De Cctv",
        "positions" => ["299"],
        "objectives" => [["name" => [], "metric" => []]]
      ],
      [
        "name" => "Jefe De Contabilidad Y Caja",
        "positions" => ["335"],
        "objectives" => [["name" => "Presentación de DDJJ mensuales a SUNAT", "metric" => 2], ["name" => "Entrega de Cierres contable s(EERR) a tiempo", "metric" => 2], ["name" => "Reducción de gastos financieros", "metric" => 6], ["name" => "Reducción de Índice de deuda a proveedores", "metric" => 6]]
      ],
      [
        "name" => "Asistente De Remuneraciones Y Compensaciones",
        "positions" => ["336"],
        "objectives" => [["name" => "Pagos ejecutados correctamente GP", "metric" => 6], ["name" => "Pagos ejecutados correctamente TP", "metric" => 6], ["name" => "Entrega de información para planilla", "metric" => 2]]
      ],
      [
        "name" => "Analista En  Proyectos De Gestion Humana",
        "positions" => ["342"],
        "objectives" => [["name" => "Cumplimiento de cronograma de proyectos", "metric" => 6], ["name" => "Cumplimiento de los procesos de Inducción y Onboarding", "metric" => 6], ["name" => "Satisfacción de los procesos de Onboarding", "metric" => 2]]
      ],
      [
        "name" => "Analista Legal",
        "positions" => ["351"],
        "objectives" => [["name" => "Memorándums elaborados", "metric" => 6], ["name" => "Reclamos atendidos", "metric" => 6], ["name" => "Solicitudes y requerimientos de entidades", "metric" => 6]]
      ],
      [
        "name" => "Caja Ap",
        "positions" => ["54", "102", "118", "306"],
        "objectives" => [["name" => "Arqueo de caja sin diferencias.", "metric" => 6], ["name" => "Cuadres de caja oportunos.", "metric" => 6], ["name" => "Envío de reportes oportunos.", "metric" => 6], ["name" => "Informe de estado de las CXC.", "metric" => 6]]
      ],
      [
        "name" => "Gerente General",
        "positions" => ["23"],
        "objectives" => [["name" => [], "metric" => []]]
      ],
      [
        "name" => "Jefe De Legal",
        "positions" => ["24"],
        "objectives" => [["name" => [], "metric" => []]]
      ],
      [
        "name" => "Gerente De Gestión Humana",
        "positions" => ["26"],
        "objectives" => [["name" => [], "metric" => []]]
      ],
      [
        "name" => "Asistente de Atracción De Talento",
        "positions" => ["31"],
        "objectives" => [["name" => [], "metric" => []]]
      ],
      [
        "name" => "Gerente De Administración Y Finanzas",
        "positions" => ["37"],
        "objectives" => [["name" => [], "metric" => []]]
      ],
      [
        "name" => "Jefe De Finanzas Y Tesorería",
        "positions" => ["41"],
        "objectives" => [["name" => [], "metric" => []]]
      ],
      [
        "name" => "Asistente De Contabilidad",
        "positions" => ["47"],
        "objectives" => [["name" => [], "metric" => []]]
      ],
      [
        "name" => "Jefe De Tic'S",
        "positions" => ["345"],
        "objectives" => [["name" => [], "metric" => []]]
      ],
      [
        "name" => "Analista De Remuneraciones Y Compensaciones",
        "positions" => ["53"],
        "objectives" => [["name" => [], "metric" => []]]
      ],
      [
        "name" => "Auditor Interno",
        "positions" => ["220"],
        "objectives" => [["name" => [], "metric" => []]]
      ],
      [
        "name" => "Supervisor De Seguridad Patrimonial",
        "positions" => ["254"],
        "objectives" => [["name" => [], "metric" => []]]
      ],
      [
        "name" => "Operador De Cctv",
        "positions" => ["299"],
        "objectives" => [["name" => [], "metric" => []]]
      ],
      [
        "name" => "Jefe De Contabilidad Y Caja",
        "positions" => ["335"],
        "objectives" => [["name" => [], "metric" => []]]
      ],
      [
        "name" => "Asistente De Remuneraciones Y Compensaciones",
        "positions" => ["336"],
        "objectives" => [["name" => [], "metric" => []]]
      ],
      [
        "name" => "Analista En  Proyectos De Gestion Humana",
        "positions" => ["342"],
        "objectives" => [["name" => [], "metric" => []]]
      ],
      [
        "name" => "Analista Legal",
        "positions" => ["351"],
        "objectives" => [["name" => [], "metric" => []]]
      ],
      [
        "name" => "Caja Ap",
        "positions" => ["54", "102", "118", "306"],
        "objectives" => [["name" => [], "metric" => []]]
      ],
      [
        "name" => "Jefe De Almacen",
        "positions" => ["56", "86", "248"],
        "objectives" => [["name" => "Stock saludable de un mes por sede", "metric" => 9]]
      ],
      [
        "name" => "Asistente De Almacén Ap",
        "positions" => ["57", "87", "251", "129"],
        "objectives" => [["name" => "Stock saludable de un mes por sede", "metric" => 9]]
      ],
      [
        "name" => "Coordinador De Ventas",
        "positions" => ["59", "81", "104", "110", "121"],
        "objectives" => [["name" => "Seguimiento de pedidos de compra de vehículos, conciliando con el sistema de emisión de facturas", "metric" => 6], ["name" => "Obtener un resultado mayor o igual a 80% en NPS.", "metric" => 6], ["name" => "Cumplimiento del 100% del envío de las solicitudes de nota de crédito (7 días hábiles)", "metric" => 6]]
      ],
      [
        "name" => "Asesor Comercial",
        "positions" => ["60", "80", "103", "108", "111", "119", "332", "328"],
        "objectives" => [["name" => "Sell Out: Cumplimiento de ventas establecido mensual", "metric" => 9], ["name" => "Colocación de créditos", "metric" => 9], ["name" => "Colocación de seguros", "metric" => 9]]
      ],
      [
        "name" => "Jefe De Ventas Ap",
        "positions" => ["61", "82", "107", "114", "124", "331", "327"],
        "objectives" => [["name" => "Sell Out: Cumplimiento de ventas", "metric" => 9], ["name" => "Cumplimiento de penetración de seguros", "metric" => 6], ["name" => "Cumplimiento de penetración de créditos", "metric" => 6], ["name" => "Alcance de Mix de Marca", "metric" => 9]]
      ],
      [
        "name" => "Asesor De Repuestos",
        "positions" => ["62", "72", "88", "130", "349"],
        "objectives" => [["name" => "Meta de facturación mensual mayor o igual a 100%", "metric" => 6]]
      ],
      [
        "name" => "Asesor De Servicios",
        "positions" => ["63", "73", "89", "131"],
        "objectives" => [["name" => "Cumplir la meta de OT mensual", "metric" => 6], ["name" => "Cumplimiento de NPS", "metric" => 6], ["name" => "Cumplimiento de meta mensual de venta de accesorios", "metric" => 6]]
      ],
      [
        "name" => "Asistente Mecánico Ap",
        "positions" => ["64", "74", "91", "134"],
        "objectives" => [["name" => [], "metric" => []]]
      ],
      [
        "name" => "Auxiliar De Lavado Y Limpieza",
        "positions" => ["65", "75", "92", "135"],
        "objectives" => [["name" => [], "metric" => []]]
      ],
      [
        "name" => "Auxiliar De Limpieza Y Conserjeria",
        "positions" => ["66", "76", "93", "113", "116", "334", "330", "150", "160", "171", "195", "204", "214", "253"],
        "objectives" => [["name" => [], "metric" => []]]
      ],
      [
        "name" => "Coordinador De Taller",
        "positions" => ["68", "78", "98", "140"],
        "objectives" => [["name" => "Reingresos de vehículos", "metric" => 9], ["name" => "Cumplimiento de OT>100%", "metric" => 6]]
      ],
      [
        "name" => "Jefe De Taller",
        "positions" => ["69", "99", "143", "246"],
        "objectives" => [["name" => "Cumplimiento de la facturación de taller mas un incremento adicional del 2%", "metric" => 6], ["name" => "Cumplimiento de OT>100%", "metric" => 6], ["name" => "Tasa de reclamos relacionados al servicio que brinda el jefe de taller", "metric" => 6]]
      ],
      [
        "name" => "Tecnico Mecanico Ap",
        "positions" => ["70", "79", "100", "144"],
        "objectives" => [["name" => "Reingresos de vehículos", "metric" => 9], ["name" => "Eficiencia de OT > 70%", "metric" => 6]]
      ],
      [
        "name" => "Agente De Seguridad",
        "positions" => ["258", "259", "260", "268", "269", "270", "350", "333", "329", "261", "262", "264", "266", "267", "271", "257"],
        "objectives" => [["name" => [], "metric" => []]]
      ],
      [
        "name" => "Codificador De Repuestos",
        "positions" => ["77", "97", "139"],
        "objectives" => [["name" => [], "metric" => []]]
      ],
      [
        "name" => "Gestor De Inmatriculacion",
        "positions" => ["106", "123", "298"],
        "objectives" => [["name" => "Tasa de reclamo menor o igual a 5%", "metric" => 6]]
      ],
      [
        "name" => "Analista De Contabilidad AP",
        "positions" => ["301", "302"],
        "objectives" => [["name" => "Cero 8 de multa por errores contables en la empresa a cargo", "metric" => 8], ["name" => "Entrega de cierres contables en tiempo de empresa a cargo (12 dias)", "metric" => 2], ["name" => "Presentación de DDJJ mensuales a SUNAT", "metric" => 6]]
      ],
      [
        "name" => "Asistente De Contabilidad AP",
        "positions" => ["275", "300"],
        "objectives" => [["name" => "Entrega de cierres contables en tiempo de empresa a cargo (en los primeros 7 dias habiles)", "metric" => 2], ["name" => "Cero 8 de multa por errores contables en la empresa a cargo", "metric" => 2]]
      ],
      [
        "name" => "Asistente Administrativo Ap",
        "positions" => ["85", "117"],
        "objectives" => [["name" => [], "metric" => []]]
      ],
      [
        "name" => "Asistente De Post Venta",
        "positions" => ["90", "133"],
        "objectives" => [["name" => "Cumplimiento del  ICD al 90% por sede", "metric" => 6], ["name" => "Cumplimiento de los estándares de Post Venta por parte de Derco al 80%", "metric" => 6]]
      ],
      [
        "name" => "Asistente De Vehiculos (pdi)",
        "positions" => ["132", "249"],
        "objectives" => [["name" => [], "metric" => []]]
      ],
      [
        "name" => "Asistente De Marketing",
        "positions" => ["120"],
        "objectives" => [["name" => "Calidad de contenido (crecimiento de 3% respecto al mes anterior)", "metric" => 6], ["name" => "Promedio de retención de visualización de videos y reels", "metric" => 6], ["name" => "Cumplimiento de entrega de piezas", "metric" => 6], ["name" => "Visitas mensuales a sedes", "metric" => 9], ["name" => "Cumplimiento del calendario de campañas", "metric" => 6]]
      ],
      [
        "name" => "Ejecutivo De Trade Marketing",
        "positions" => ["125"],
        "objectives" => [["name" => "Visitas de campo", "metric" => 6], ["name" => "Campañas de Mkt", "metric" => 6], ["name" => "Penetración de mercado", "metric" => 6]]
      ],
      [
        "name" => "Gerente De Negocio Ap",
        "positions" => ["126"],
        "objectives" => [["name" => "Nº de unidades vendidas en el mes en las 4 sedes", "metric" => 10], ["name" => "% de market share AP", "metric" => 6], ["name" => "Cumplimiento del volumen de ventas de post venta", "metric" => 6], ["name" => "Meses de stock de repuestos", "metric" => 2]]
      ],
      [
        "name" => "Jefe De Marketing",
        "positions" => ["127"],
        "objectives" => [["name" => "Cobranza", "metric" => 2], ["name" => "Posicionamiento digital (SEO)  AP", "metric" => 6], ["name" => "Inversión", "metric" => 6], ["name" => "Win rate de Lead AP mayor al 3%", "metric" => 6], ["name" => "Reputación Chiclayo", "metric" => 7], ["name" => "Reputacion Cajamarca", "metric" => 7], ["name" => "Reputación Jaén", "metric" => 7], ["name" => "Reputación Piura", "metric" => 7]]
      ],
      [
        "name" => "Coordinador De Post Venta - Almacen",
        "positions" => ["128"],
        "objectives" => [["name" => "Promedio de stock saludable de un mes en los cuatro almacenes", "metric" => 9]]
      ],
      [
        "name" => "Coordinador De Post Venta",
        "positions" => ["141"],
        "objectives" => [["name" => "Promedio de cumplimiento del ICD al 90% a nivel de las 4 sedes", "metric" => 6]]
      ],
      [
        "name" => "Gerente De Post Venta",
        "positions" => ["142"],
        "objectives" => [["name" => "Nivel de servicio", "metric" => 6], ["name" => "Cumplimiento del volumen de ventas de post venta", "metric" => 6], ["name" => "Cumplimiento de la venta de accesorios", "metric" => 6], ["name" => "Cumplimiento de rotacion de stock", "metric" => 2]]
      ],
      [
        "name" => "Community Manager",
        "positions" => ["218"],
        "objectives" => [["name" => "Crecimiento de comunidad (SEO)", "metric" => 6], ["name" => "Generación de Leads (Promedio mensual)", "metric" => 2], ["name" => "Nota de Reputación SCORE total AP", "metric" => 2], ["name" => "Winrate", "metric" => 6], ["name" => "Cumplimiento del calendario de campañas", "metric" => 6]]
      ],
      [
        "name" => "Diseñador Grafico",
        "positions" => ["219"],
        "objectives" => [["name" => "Propuestas de conceptos visuales campañas de marketing (4 unidades de negocio)", "metric" => 2], ["name" => "Desarrollo de gráficas multiformato", "metric" => 6], ["name" => "Entrega de artes para campañas de marketing", "metric" => 6]]
      ],
      [
        "name" => "Analista de Negocio",
        "positions" => ["256"],
        "objectives" => [["name" => [], "metric" => []]]
      ],
      [
        "name" => "Analista En Proyecto Tics",
        "positions" => ["273", "280"],
        "objectives" => [["name" => "Calidad de servicio - Mesa de ayuda (Milla / Dynamics)", "metric" => 6], ["name" => "Promedio de efectividad en tiempo de atención - Soporte TIC's", "metric" => 5]]
      ],
      [
        "name" => "Trabajadora Social",
        "positions" => ["286", "287"],
        "objectives" => [["name" => "Ejecución del Plan de Clima Laboral y Bienestar", "metric" => 6], ["name" => "Adherencia a Pakatnamu Star", "metric" => 6], ["name" => "Subsidios recuperados", "metric" => 6]]
      ],
      [
        "name" => "Jefe De Contabilidad",
        "positions" => ["288"],
        "objectives" => [["name" => "Entrega de cierres contables en tiempo de AP a la GAF", "metric" => 6], ["name" => "Presentación de DDJJ mensuales a SUNAT", "metric" => 6], ["name" => "Cero 8 de multa por errores contables en AP", "metric" => 8]]
      ],
      [
        "name" => "Partner De Gestión Humana",
        "positions" => ["341"],
        "objectives" => [["name" => "Promedio de vacaciones AP y GP", "metric" => 9], ["name" => "Proceso dentro del plazo", "metric" => 6], ["name" => "6 de legajos digitalizados", "metric" => 6], ["name" => "Plazo de envio de boletas GP", "metric" => 2], ["name" => "Plazo de envio de boletas AP", "metric" => 2]]
      ],
      [
        "name" => "Jefe De Repuestos",
        "positions" => ["344"],
        "objectives" => [["name" => "Cumplimiento de facturación de las 4 sedes", "metric" => 6]]
      ],
      [
        "name" => "Analista Comercial Dp",
        "positions" => ["303"],
        "objectives" => [["name" => [], "metric" => []]]
      ],
      [
        "name" => "Analista De Contabilidad DP",
        "positions" => ["277"],
        "objectives" => [["name" => "Cero 8 de multa por errores contables en la empresa a cargo", "metric" => 8], ["name" => "Presentación de DDJJ mensuales a SUNAT", "metric" => 6], ["name" => "Entrega de cierres contables (EE.RR) en tiempo de empresa a cargo (15 dias)", "metric" => 2]]
      ],
      [
        "name" => "Asistente De Contabilidad Dp",
        "positions" => ["149", "157", "172", "181", "194", "206", "278"],
        "objectives" => [["name" => "Cero 8 de multa por errores contables en la empresa a cargo", "metric" => 8], ["name" => "Cero errores en el registro de información contable de manera mensual", "metric" => 4], ["name" => "Entrega de cierre contable mensual integral (en los primeros 12 dias calendario)", "metric" => 2]]
      ],
      [
        "name" => "Asistente Administrativo Dp",
        "positions" => ["145", "184", "207", "234", "305"],
        "objectives" => [["name" => "Notas de crédito", "metric" => 6], ["name" => "Entrega de informe de compras", "metric" => 6], ["name" => "Aprobación correcta de descuentos", "metric" => 6]]
      ],
      [
        "name" => "Asistente de Almacén DP",
        "positions" => ["233", "309"],
        "objectives" => [["name" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza", "metric" => 5]]
      ],
      [
        "name" => "Asistente De Reparto",
        "positions" => ["151", "162", "174", "185", "196", "209", "215"],
        "objectives" => [["name" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza", "metric" => 5]]
      ],
      [
        "name" => "Asistente De Reparto - Montacarguista",
        "positions" => ["238", "239", "240", "241", "242", "243"],
        "objectives" => [["name" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza", "metric" => 5]]
      ],
      [
        "name" => "Caja Dp",
        "positions" => ["148", "158", "170", "183", "192", "205"],
        "objectives" => [["name" => "Cuadres de caja oportunos", "metric" => 6], ["name" => "Arqueo de caja, sin diferencias", "metric" => 6]]
      ],
      [
        "name" => "Caja General",
        "positions" => ["146", "161", "173", "182", "193", "208"],
        "objectives" => [["name" => "Vales pendientes rendidos al 100%", "metric" => 6], ["name" => "Arqueo de caja, sin diferencias", "metric" => 6], ["name" => "Cuadres de caja oportunos", "metric" => 6], ["name" => "Envio de reportes oportunos", "metric" => 6], ["name" => "Informe de estado de las cuentas por cobrar", "metric" => 6]]
      ],
      [
        "name" => "Conductor I",
        "positions" => ["153", "165", "176", "187", "198", "210"],
        "objectives" => [["name" => "Cumplimiento mensual de los limites de velocidad en ruta", "metric" => 6], ["name" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza", "metric" => 5], ["name" => "Cero multas por infringir las normas de tránsito en el desarrollo de sus funciones", "metric" => 9], ["name" => "Cero incidencias en la ruta asignada (robos, choques,etc)", "metric" => 9]]
      ],
      [
        "name" => "Conductor II",
        "positions" => ["164", "188", "199", "211", "346"],
        "objectives" => [["name" => "Cumplimiento mensual de los limites de velocidad en ruta", "metric" => 6], ["name" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza", "metric" => 5], ["name" => "Cero multas por infringir las normas de tránsito en el desarrollo de sus funciones", "metric" => 9], ["name" => "Cero incidencias en la ruta asignada (robos, choques,etc)", "metric" => 9]]
      ],
      [
        "name" => "Gerente Comercial Dp",
        "positions" => ["217"],
        "objectives" => [["name" => "Rentabilidad bruta mayor igual a 5.6%", "metric" => 6], ["name" => "% de Efectividad de Cobranza", "metric" => 6], ["name" => "Meses de stock/ % Días de antigüedad -  fierro", "metric" => 2], ["name" => "Meses de stock/ % Días de antigüedad -  Koplast", "metric" => 2], ["name" => "Meses de stock/ % Días de antigüedad - Eternit", "metric" => 2]]
      ],
      [
        "name" => "Jefe De Administracion",
        "positions" => ["147", "159", "169", "179", "180", "203", "236", "191"],
        "objectives" => [["name" => "Efectividad de liquidaciones: % de reconocimeinto de la liquidacion / total de liquidaciones registradas", "metric" => 6], ["name" => "Notas de crèdito a proveedores", "metric" => 6], ["name" => "Pago a proveedores", "metric" => 6], ["name" => "Gestión de compras", "metric" => 6]]
      ],
      [
        "name" => "Jefe De Ventas Dp",
        "positions" => ["155", "168", "353"],
        "objectives" => [["name" => "Cumplimiento al 100% de la meta de venta establecida", "metric" => 6], ["name" => "% de Efectividad de Cobranza", "metric" => 6], ["name" => "Rentabilidad bruta mayor igual a 5.6%", "metric" => 6], ["name" => "Incremento de cartera", "metric" => 9], ["name" => "Mix de productos", "metric" => 9]]
      ],
      [
        "name" => "Supervisor De Almacen",
        "positions" => ["152", "163", "175", "186", "197", "231"],
        "objectives" => [["name" => "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza", "metric" => 5]]
      ],
      [
        "name" => "Ejecutivo Comercial",
        "positions" => [],
        "objectives" => [["name" => [], "metric" => []]]
      ],
      [
        "name" => "Ejecutivo Comercial Industria",
        "positions" => [],
        "objectives" => [["name" => "Cumplimiento de presupuesto comercial", "metric" => 6], ["name" => "% de Efectividad de Cobranza", "metric" => 6]]
      ]
    ];

    DB::statement('SET FOREIGN_KEY_CHECKS=0;');

    HierarchicalCategoryDetail::truncate();
    EvaluationCategoryObjectiveDetail::truncate();
    HierarchicalCategory::truncate();
    EvaluationObjective::truncate();

    DB::statement('SET FOREIGN_KEY_CHECKS=1;');


    foreach ($data as $item) {
      // 1. Buscar o crear la categoría
      $category = HierarchicalCategory::firstOrCreate(
        ['name' => $item['name']],
        ['description' => 'Category for ' . $item['name']]
      );

      // 2. Asignar posiciones
      if (!empty($item['positions']) && is_array($item['positions'])) {
        $validIds = Position::whereIn('id', $item['positions'])->pluck('id')->toArray();

        foreach ($validIds as $positionId) {
          $category->children()->create([
            'position_id' => $positionId,
          ]);
        }
      }

      // 3. Crear o asociar objetivos
      if (!empty($item['objectives']) && is_array($item['objectives'])) {
        foreach ($item['objectives'] as $objectiveData) {
          // Validar que tenga nombre y métrica no vacíos
          if (
            !empty($objectiveData['name']) &&
            !empty($objectiveData['metric'])
          ) {
            // Buscar o crear el objetivo por nombre y métrica
            $objective = EvaluationObjective::firstOrCreate([
              'name' => $objectiveData['name'],
              'metric_id' => $objectiveData['metric'],
            ]);

            // Asociar a la categoría (evita duplicados)
            EvaluationCategoryObjectiveDetail::firstOrCreate([
              'category_id' => $category->id,
              'objective_id' => $objective->id,
            ]);
          }
        }
      }
    }
  }
}
