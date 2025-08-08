<?php

namespace Database\Seeders;

use App\Models\gp\gestionhumana\evaluacion\HierarchicalCategoryDetail;
use App\Models\gp\gestionsistema\Position;
use Illuminate\Database\Seeder;
use App\Models\gp\gestionhumana\evaluacion\HierarchicalCategory;
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
                "objectives" => ["Controlar el costo operacional (Costo venta/kilometraje) que no supere 4.5 sol/km", "Controlar el costo kilometro de combustible no supere los 2.0 sol/km", "Controlar los gastos reparables que no superen los 50,000 8", "Mantener la disponibilidad al 96% unidades completas(conductor y vehículo)", "Accidente y/o Siniestros por responsabilidad el equipo de operaciones y mantenimiento"]
            ],
            [
                "name" => "Logística TP",
                "positions" => ["21"],
                "objectives" => ["Inventarios cíclicos", "Control del costo de Suministros (ingresos y salidas)"]
            ],
            [
                "name" => "Supervisión de operaciones y mantenimiento",
                "positions" => ["290"],
                "objectives" => ["Mantener la disponibilidad al 96% unidades completas(conductor y vehículo)", "Mantener un 85 % de cumplimiento de la programaciòn (Servicios entregados a tiempo/total de servicios programados)", "Controlar los descansos no superar los 450 dias pendientes al mes.", "Los costos de mantenimiento no deben superar los 0.30 8/km recorrido", "No tener multas por documentacion (guías, revisión técnica, bonificaciòn, soat,certificado MTC)", "Accidente y/o Siniestros por responsabilidad el equipo de operaciones y mantenimiento"]
            ],
            [
                "name" => "Gerente De Negocio TP",
                "positions" => ["22"],
                "objectives" => ["Alcanzar 2 mínimo de fletes al mes", "Rentabilizar el negocio: margen bruto mensual"]
            ],
            [
                "name" => "Asistente Mecánico TP",
                "positions" => ["13"],
                "objectives" => ["Optimizar los tiempos de reparacion en un 55%", "Mantenimentos correctivos de emergencia(Auxilios Mecánicos), no supere el 5% mensual de la flota"]
            ],
            [
                "name" => "Analista De Contabilidad TP",
                "positions" => ["282"],
                "objectives" => ["Cero 8 de multa por errores contables en la empresa a cargo", "Entrega de cierres contables en tiempo de empresa a cargo (en los primeros 10 dias habiles)"]
            ],
            [
                "name" => "Asistente De Contabilidad TP",
                "positions" => ["283"],
                "objectives" => ["Cero 8 de multa por errores contables en la empresa a cargo", "Entrega de cierres contables en tiempo de empresa a cargo (en los primeros 5 dias habiles)", "Cero 8 de multa por errores contables en la empresa a cargo", "Entrega de cierres contables en tiempo de empresa a cargo (en los primeros 5 dias habiles)"]
            ],
            [
                "name" => "Asistente De Facturación Y Cobranza",
                "positions" => ["2"],
                "objectives" => ["Cumplimiento con la actualizacion de informacion hoja de trabajo de Fact y cobranza", "Recuperación de documentos", "Control de parihuelas Gloria", "Aplicación de detracciones", "Cumplimiento con la recuperación de documentos para facturación", "Efectividad de proyección de facturación", "Recuperación de documentos", "Efectividad proyección de cobranza", "22", "Recuperación de documentos", "Recuperación de documentos", "Recuperación de documentos"]
            ],
            [
                "name" => "Asistente De Operaciones",
                "positions" => ["6"],
                "objectives" => ["Los costos de Kilómetro de combustible no deben superar los 1.65/km recorrido", "Rendimiento promedio no debe superar 8.6 km/gln", "Accidente y/o Siniestros por responsabilidad el equipo de operaciones y mantenimiento", "Cumplir con la implementaciòn de las unidades y su debido registro al 100%, según condicion del cliente (indicador rìgido)", "Cumplir con el registro al 100% de robos de implementos, mercaderìa e incidencias.", "Realizar inventario mensuales del 80% de toda la flota (como mìnimo) con la finalidad de verificar las condiciones de los implementos", "Cero multas por documentacion vencida, mantener actualizada de todas las unidades.(revisiòn tècnica, bonificaciòn, soat,certificado MTC)", "Accidente y/o Siniestros por responsabilidad el equipo de operaciones y mantenimiento"]
            ],
            [
                "name" => "Asistente De Seguimiento Y Monitoreo",
                "positions" => ["5", "304"],
                "objectives" => ["Incrementar el % de cumplimiento de la programaciòn (Servicios entregados a tiempo/total de servicios programados)", "No tener robos por falta de seguimiento y monitoreo", "Accidente y/o Siniestros por responsabilidad el equipo de operaciones y mantenimiento", "Incrementar el % de cumplimiento de la programaciòn (Servicios entregados a tiempo/total de servicios programados)", "No tener robos por falta de seguimiento y monitoreo", "Accidente y/o Siniestros por responsabilidad el equipo de operaciones y mantenimiento", "Incrementar el % de cumplimiento de la programaciòn (Servicios entregados a tiempo/total de servicios programados)", "No tener robos por falta de seguimiento y monitoreo", "Accidente y/o Siniestros por responsabilidad el equipo de operaciones y mantenimiento"]
            ],
            [
                "name" => "Asistente En Proyecto Tics",
                "positions" => ["317"],
                "objectives" => ["Calidad de servicio - Mesa de ayuda (Milla / Dynamics)", "Promedio de efectividad en tiempo de atención - Soporte TIC's"]
            ],
            [
                "name" => "Auxiliar De Operaciones Lima",
                "positions" => ["8"],
                "objectives" => ["Apoyo en otras actividades de operativas con las demas àreas", "Incidentes con responsalidad del auxiliar de operaciones"]
            ],
            [
                "name" => "Conductor De Tracto Camion",
                "positions" => ["11"],
                "objectives" => ["Tener mínimo un rendimiento de combustible del 92%", "No tener incidencias, con responsalidad del conductor", "No tener multas por llenado por mal llenado de guías", "Tener mínimo 85 promedio de conducción (Se da un un rango de cumplimiento entre 80 y 90)"]
            ],
            [
                "name" => "Coordinador De Mantenimiento",
                "positions" => ["291"],
                "objectives" => ["Los costos de  Kilómetro de Neumáticos no deben superar los 0.16 sol/km recorrido", "Rendimiento de Neumatico debe ser como mínimo 11,000 Km/mm", "Mantenimentos correctivos de emergencia(Auxilios Mecánicos), no supere el 5% mensual de la flota", "Cumplir al menos con el 96% de la programación de mantenimiento preventivo", "Números de escaneos mensuales mínimo 105", "Accidente y/o Siniestros por responsabilidad el equipo de operaciones y mantenimiento"]
            ],
            [
                "name" => "Maestro Mecanico",
                "positions" => ["17"],
                "objectives" => ["Optimizar los tiempos de reparacion en un 55%", "Mantenimentos correctivos de emergencia(Auxilios Mecánicos), no supere el 5% mensual de la flota"]
            ],
            [
                "name" => "Supervisor Comercial",
                "positions" => ["324"],
                "objectives" => ["Utilidad Operativa", "Número de viajes", "Utilidad Operativa", "Número de viajes"]
            ],
            [
                "name" => "Tecnico Soldador",
                "positions" => ["15"],
                "objectives" => ["Optimizar los tiempos de reparacion en un 55%", "Mantenimentos correctivos de emergencia(Auxilios Mecánicos), no supere el 5% mensual de la flota"]
            ],
            [
                "name" => "Tecnico Electricista",
                "positions" => ["16", "19"],
                "objectives" => ["Optimizar los tiempos de reparacion en un 55%", "Mantenimentos correctivos de emergencia(Auxilios Mecánicos), no supere el 5% mensual de la flota", "Optimizar los tiempos de reparacion en un 55%", "Mantenimentos correctivos de emergencia(Auxilios Mecánicos), no supere el 5% mensual de la flota", "Optimizar los tiempos de reparacion en un 55%", "Mantenimentos correctivos de emergencia(Auxilios Mecánicos), no supere el 5% mensual de la flota", "Optimizar los tiempos de reparacion en un 55%", "Mantenimentos correctivos de emergencia(Auxilios Mecánicos), no supere el 5% mensual de la flota"]
            ],
            [
                "name" => "Partner De Gestión Humana",
                "positions" => ["337"],
                "objectives" => ["Promedio de vacaciones DP y TP", "Procesos dentro del plazo", "6 de legajos digitalizados", "Plazo de envio de boletas DP", "Plazo de envio de boletas TP", "Promedio de vacaciones DP y TP", "Procesos dentro del plazo", "6 de legajos digitalizados", "Plazo de envio de boletas DP", "Plazo de envio de boletas TP"]
            ],
            [
                "name" => "Gestor Comercial",
                "positions" => ["3"],
                "objectives" => ["Programación de flota", "Incremento en ventas", "Rentabilidad", "Generación de guías", "Actualización del sistema", "Actualización de pesos en sistema", "Recuperación y control de parihuelas cerámicos", "Control de parihuelas Celima", "Cumplimiento de facturación cerámicos", "Generación de guías", "Actualización del sistema", "Actualización de pesos en sistema", "Programación de flota", "Incremento en ventas", "Rentabilidad", "Captación y propuesta de nuevos clientes", "Seguimiento, solicitud y cumplimiento de citas cerámicos", "Recuperación de documentos cerámicos", "Recuperación y control de parihuelas cerámicos", "Control de parihuelas Celima", "Cumplimiento de facturación cerámicos"]
            ],
            [
                "name" => "Gerente General",
                "positions" => ["23"],
                "objectives" => []
            ],
            [
                "name" => "Jefe De Legal",
                "positions" => ["24"],
                "objectives" => ["Promedio de cumplimiento de presupuesto mensual de AP, DP y TP"]
            ],
            [
                "name" => "Gerente De Gestión Humana",
                "positions" => ["26"],
                "objectives" => ["Promedio de cumplimiento de presupuesto mensual de AP, DP y TP", "Tiempo de reclutamiento", "Asegurar pagos correctos"]
            ],
            [
                "name" => "Asistente de Atracción De Talento",
                "positions" => ["31"],
                "objectives" => ["Procesos dentro del plazo", "Calidad de reclutamiento", "CV recibidos marca empleadora"]
            ],
            [
                "name" => "Gerente De Administración Y Finanzas",
                "positions" => ["37"],
                "objectives" => ["Promedio de cumplimiento de presupuesto mensual de AP, DP y TP"]
            ],
            [
                "name" => "Jefe De Finanzas Y Tesorería",
                "positions" => ["41"],
                "objectives" => ["Reportes semanales de CxC", "Cumplimiento de programacion de visitas a sedes con la finalidad de supervisar procedimientos y control de caja", "Reducción de gastos financieros", "Reducción de indice de deuda a proveedores"]
            ],
            [
                "name" => "Asistente De Contabilidad",
                "positions" => ["47"],
                "objectives" => ["Cero 8 de multa por errores contables en la empresa a cargo", "Entrega de cierres contables en tiempo de empresa a cargo (en los primeros 7 dias habiles)"]
            ],
            [
                "name" => "Jefe De Tic'S",
                "positions" => ["345"],
                "objectives" => ["Cumplimiento de cronogramas de desarrollo y mantenimiento de software (Milla, Quiter, Dynamic )", "Efectividad en el tiempo de atencion en el servicio", "Calidad de servicio del área", "Promedio de cumplimiento de presupuesto mensual de AP, DP y TP"]
            ],
            [
                "name" => "Analista De Remuneraciones Y Compensaciones",
                "positions" => ["53"],
                "objectives" => ["Pagos ejecutados oportunamente", "Presentación contable de planilla", "Pagos ejecutados correctamente"]
            ],
            [
                "name" => "Auditor Interno",
                "positions" => ["220"],
                "objectives" => []
            ],
            [
                "name" => "Supervisor De Seguridad Patrimonial",
                "positions" => ["254"],
                "objectives" => ["Cumplimiento de la programacion de  capacitaciones a los agentes", "Disminución de 2 de agentes que abandonen su labor antes de cumplimiento de contrato", "Disminución de la tasa % de robos o perdidas de activos de propiedad de la empresa"]
            ],
            [
                "name" => "Operador De Cctv",
                "positions" => ["299"],
                "objectives" => []
            ],
            [
                "name" => "Jefe De Contabilidad Y Caja",
                "positions" => ["335"],
                "objectives" => ["Presentación de DDJJ mensuales a SUNAT", "Entrega de Cierres contable s(EERR) a tiempo", "Reducción de gastos financieros", "Reducción de Índice de deuda a proveedores"]
            ],
            [
                "name" => "Asistente De Remuneraciones Y Compensaciones",
                "positions" => ["336"],
                "objectives" => ["Pagos ejecutados correctamente GP", "Pagos ejecutados correctamente TP", "Entrega de información para planilla"]
            ],
            [
                "name" => "Analista En  Proyectos De Gestion Humana",
                "positions" => ["342"],
                "objectives" => ["Cumplimiento de cronograma de proyectos", "Cumplimiento de los procesos de Inducción y Onboarding", "Satisfacción de los procesos de Onboarding"]
            ],
            [
                "name" => "Analista Legal",
                "positions" => ["351"],
                "objectives" => ["Memorándums elaborados", "Reclamos atendidos", "Solicitudes y requerimientos de entidades"]
            ],
            [
                "name" => "Caja Ap",
                "positions" => ["54", "102", "118", "306"],
                "objectives" => ["Arqueo de caja sin diferencias.", "Cuadres de caja oportunos.", "Envío de reportes oportunos.", "Informe de estado de las CXC."]
            ],
            [
                "name" => "Gerente General",
                "positions" => ["23"],
                "objectives" => []
            ],
            [
                "name" => "Jefe De Legal",
                "positions" => ["24"],
                "objectives" => []
            ],
            [
                "name" => "Gerente De Gestión Humana",
                "positions" => ["26"],
                "objectives" => []
            ],
            [
                "name" => "Asistente de Atracción De Talento",
                "positions" => ["31"],
                "objectives" => []
            ],
            [
                "name" => "Gerente De Administración Y Finanzas",
                "positions" => ["37"],
                "objectives" => []
            ],
            [
                "name" => "Jefe De Finanzas Y Tesorería",
                "positions" => ["41"],
                "objectives" => []
            ],
            [
                "name" => "Asistente De Contabilidad",
                "positions" => ["47"],
                "objectives" => []
            ],
            [
                "name" => "Jefe De Tic'S",
                "positions" => ["345"],
                "objectives" => []
            ],
            [
                "name" => "Analista De Remuneraciones Y Compensaciones",
                "positions" => ["53"],
                "objectives" => []
            ],
            [
                "name" => "Auditor Interno",
                "positions" => ["220"],
                "objectives" => []
            ],
            [
                "name" => "Supervisor De Seguridad Patrimonial",
                "positions" => ["254"],
                "objectives" => []
            ],
            [
                "name" => "Operador De Cctv",
                "positions" => ["299"],
                "objectives" => []
            ],
            [
                "name" => "Jefe De Contabilidad Y Caja",
                "positions" => ["335"],
                "objectives" => []
            ],
            [
                "name" => "Asistente De Remuneraciones Y Compensaciones",
                "positions" => ["336"],
                "objectives" => []
            ],
            [
                "name" => "Analista En  Proyectos De Gestion Humana",
                "positions" => ["342"],
                "objectives" => []
            ],
            [
                "name" => "Analista Legal",
                "positions" => ["351"],
                "objectives" => []
            ],
            [
                "name" => "Caja Ap",
                "positions" => ["54", "102", "118", "306"],
                "objectives" => []
            ],
            [
                "name" => "Jefe De Almacen",
                "positions" => ["56", "86", "248"],
                "objectives" => ["Stock saludable de un mes por sede"]
            ],
            [
                "name" => "Asistente De Almacén Ap",
                "positions" => ["57", "87", "251", "129"],
                "objectives" => ["Stock saludable de un mes por sede"]
            ],
            [
                "name" => "Coordinador De Ventas",
                "positions" => ["59", "81", "104", "110", "121"],
                "objectives" => ["Seguimiento de pedidos de compra de vehículos, conciliando con el sistema de emisión de facturas", "Obtener un resultado mayor o igual a 80% en NPS.", "Cumplimiento del 100% del envío de las solicitudes de nota de crédito (7 días hábiles)"]
            ],
            [
                "name" => "Asesor Comercial",
                "positions" => ["60", "80", "103", "108", "111", "119", "332", "328"],
                "objectives" => ["Sell Out: Cumplimiento de ventas establecido mensual", "Colocación de créditos", "Colocación de seguros"]
            ],
            [
                "name" => "Jefe De Ventas Ap",
                "positions" => ["61", "82", "107", "114", "124", "331", "327"],
                "objectives" => ["Sell Out: Cumplimiento de ventas", "Cumplimiento de penetración de seguros", "Cumplimiento de penetración de créditos", "Alcance de Mix de Marca"]
            ],
            [
                "name" => "Asesor De Repuestos",
                "positions" => ["62", "72", "88", "130", "349"],
                "objectives" => ["Meta de facturación mensual mayor o igual a 100%"]
            ],
            [
                "name" => "Asesor De Servicios",
                "positions" => ["63", "73", "89", "131"],
                "objectives" => ["Cumplir la meta de OT mensual", "Cumplimiento de NPS", "Cumplimiento de meta mensual de venta de accesorios"]
            ],
            [
                "name" => "Asistente Mecánico Ap",
                "positions" => ["64", "74", "91", "134"],
                "objectives" => []
            ],
            [
                "name" => "Auxiliar De Lavado Y Limpieza",
                "positions" => ["65", "75", "92", "135"],
                "objectives" => []
            ],
            [
                "name" => "Auxiliar De Limpieza Y Conserjeria",
                "positions" => ["66", "76", "93", "113", "116", "334", "330", "150", "160", "171", "195", "204", "214", "253"],
                "objectives" => []
            ],
            [
                "name" => "Coordinador De Taller",
                "positions" => ["68", "78", "98", "140"],
                "objectives" => ["Reingresos de vehículos", "Cumplimiento de OT>100%"]
            ],
            [
                "name" => "Jefe De Taller",
                "positions" => ["69", "99", "143", "246"],
                "objectives" => ["Cumplimiento de la facturación de taller mas un incremento adicional del 2%", "Cumplimiento de OT>100%", "Tasa de reclamos relacionados al servicio que brinda el jefe de taller"]
            ],
            [
                "name" => "Tecnico Mecanico Ap",
                "positions" => ["70", "79", "100", "144"],
                "objectives" => ["Reingresos de vehículos", "Eficiencia de OT > 70%"]
            ],
            [
                "name" => "Agente De Seguridad",
                "positions" => ["258", "259", "260", "268", "269", "270", "350", "333", "329", "261", "262", "264", "266", "267", "271", "257"],
                "objectives" => []
            ],
            [
                "name" => "Codificador De Repuestos",
                "positions" => ["77", "97", "139"],
                "objectives" => []
            ],
            [
                "name" => "Gestor De Inmatriculacion",
                "positions" => ["106", "123", "298"],
                "objectives" => ["Tasa de reclamo menor o igual a 5%"]
            ],
            [
                "name" => "Analista De Contabilidad AP",
                "positions" => ["301", "302"],
                "objectives" => ["Cero 8 de multa por errores contables en la empresa a cargo", "Entrega de cierres contables en tiempo de empresa a cargo (12 dias)", "Presentación de DDJJ mensuales a SUNAT"]
            ],
            [
                "name" => "Asistente De Contabilidad AP",
                "positions" => ["275", "300"],
                "objectives" => ["Entrega de cierres contables en tiempo de empresa a cargo (en los primeros 7 dias habiles)", "Cero 8 de multa por errores contables en la empresa a cargo"]
            ],
            [
                "name" => "Asistente Administrativo Ap",
                "positions" => ["85", "117"],
                "objectives" => []
            ],
            [
                "name" => "Asistente De Post Venta",
                "positions" => ["90", "133"],
                "objectives" => ["Cumplimiento del  ICD al 90% por sede", "Cumplimiento de los estándares de Post Venta por parte de Derco al 80%"]
            ],
            [
                "name" => "Asistente De Vehiculos (pdi)",
                "positions" => ["132", "249"],
                "objectives" => []
            ],
            [
                "name" => "Asistente De Marketing",
                "positions" => ["120"],
                "objectives" => ["Calidad de contenido (crecimiento de 3% respecto al mes anterior)", "Promedio de retención de visualización de videos y reels", "Cumplimiento de entrega de piezas", "Visitas mensuales a sedes", "Cumplimiento del calendario de campañas"]
            ],
            [
                "name" => "Ejecutivo De Trade Marketing",
                "positions" => ["125"],
                "objectives" => ["Visitas de campo", "Campañas de Mkt", "Penetración de mercado"]
            ],
            [
                "name" => "Gerente De Negocio Ap",
                "positions" => ["126"],
                "objectives" => ["Nº de unidades vendidas en el mes en las 4 sedes", "% de market share AP", "Cumplimiento del volumen de ventas de post venta", "Meses de stock de repuestos"]
            ],
            [
                "name" => "Jefe De Marketing",
                "positions" => ["127"],
                "objectives" => ["Cobranza", "Posicionamiento digital (SEO)  AP", "Inversión", "Win rate de Lead AP mayor al 3%", "Reputación Chiclayo", "Reputacion Cajamarca", "Reputación Jaén", "Reputación Piura"]
            ],
            [
                "name" => "Coordinador De Post Venta - Almacen",
                "positions" => ["128"],
                "objectives" => ["Promedio de stock saludable de un mes en los cuatro almacenes"]
            ],
            [
                "name" => "Coordinador De Post Venta",
                "positions" => ["141"],
                "objectives" => ["Promedio de cumplimiento del ICD al 90% a nivel de las 4 sedes"]
            ],
            [
                "name" => "Gerente De Post Venta",
                "positions" => ["142"],
                "objectives" => ["Nivel de servicio", "Cumplimiento del volumen de ventas de post venta", "Cumplimiento de la venta de accesorios", "Cumplimiento de rotacion de stock"]
            ],
            [
                "name" => "Community Manager",
                "positions" => ["218"],
                "objectives" => ["Crecimiento de comunidad (SEO)", "Generación de Leads (Promedio mensual)", "Nota de Reputación SCORE total AP", "Winrate", "Cumplimiento del calendario de campañas"]
            ],
            [
                "name" => "Diseñador Grafico",
                "positions" => ["219"],
                "objectives" => ["Propuestas de conceptos visuales campañas de marketing (4 unidades de negocio)", "Desarrollo de gráficas multiformato", "Entrega de artes para campañas de marketing"]
            ],
            [
                "name" => "Analista de Negocio",
                "positions" => ["256"],
                "objectives" => []
            ],
            [
                "name" => "Analista En Proyecto Tics",
                "positions" => ["273", "280"],
                "objectives" => ["Calidad de servicio - Mesa de ayuda (Milla / Dynamics)", "Promedio de efectividad en tiempo de atención - Soporte TIC's"]
            ],
            [
                "name" => "Trabajadora Social",
                "positions" => ["286", "287"],
                "objectives" => ["Ejecución del Plan de Clima Laboral y Bienestar", "Adherencia a Pakatnamu Star", "Subsidios recuperados"]
            ],
            [
                "name" => "Jefe De Contabilidad",
                "positions" => ["288"],
                "objectives" => ["Entrega de cierres contables en tiempo de AP a la GAF", "Presentación de DDJJ mensuales a SUNAT", "Cero 8 de multa por errores contables en AP"]
            ],
            [
                "name" => "Partner De Gestión Humana",
                "positions" => ["341"],
                "objectives" => ["Promedio de vacaciones AP y GP", "Proceso dentro del plazo", "6 de legajos digitalizados", "Plazo de envio de boletas GP", "Plazo de envio de boletas AP"]
            ],
            [
                "name" => "Jefe De Repuestos",
                "positions" => ["344"],
                "objectives" => ["Cumplimiento de facturación de las 4 sedes"]
            ],
            [
                "name" => "Analista Comercial Dp",
                "positions" => ["303"],
                "objectives" => []
            ],
            [
                "name" => "Analista De Contabilidad DP",
                "positions" => ["277"],
                "objectives" => ["Cero 8 de multa por errores contables en la empresa a cargo", "Presentación de DDJJ mensuales a SUNAT", "Entrega de cierres contables (EE.RR) en tiempo de empresa a cargo (15 dias)"]
            ],
            [
                "name" => "Asistente De Contabilidad Dp",
                "positions" => ["149", "157", "172", "181", "194", "206", "278"],
                "objectives" => ["Cero 8 de multa por errores contables en la empresa a cargo", "Cero errores en el registro de información contable de manera mensual", "Entrega de cierre contable mensual integral (en los primeros 12 dias calendario)"]
            ],
            [
                "name" => "Asistente Administrativo Dp",
                "positions" => ["145", "184", "207", "234", "305"],
                "objectives" => ["Notas de crédito", "Entrega de informe de compras", "Aprobación correcta de descuentos"]
            ],
            [
                "name" => "Asistente de Almacén DP",
                "positions" => ["233", "309"],
                "objectives" => ["Promedio mensual de ingreso no mayor a 3 Minutos de tardanza"]
            ],
            [
                "name" => "Asistente De Reparto",
                "positions" => ["151", "162", "174", "185", "196", "209", "215"],
                "objectives" => ["Promedio mensual de ingreso no mayor a 3 Minutos de tardanza"]
            ],
            [
                "name" => "Asistente De Reparto - Montacarguista",
                "positions" => ["238", "239", "240", "241", "242", "243"],
                "objectives" => ["Promedio mensual de ingreso no mayor a 3 Minutos de tardanza"]
            ],
            [
                "name" => "Caja Dp",
                "positions" => ["148", "158", "170", "183", "192", "205"],
                "objectives" => ["Cuadres de caja oportunos", "Arqueo de caja, sin diferencias"]
            ],
            [
                "name" => "Caja General",
                "positions" => ["146", "161", "173", "182", "193", "208"],
                "objectives" => ["Vales pendientes rendidos al 100%", "Arqueo de caja, sin diferencias", "Cuadres de caja oportunos", "Envio de reportes oportunos", "Informe de estado de las cuentas por cobrar"]
            ],
            [
                "name" => "Conductor I",
                "positions" => ["153", "165", "176", "187", "198", "210"],
                "objectives" => ["Cumplimiento mensual de los limites de velocidad en ruta", "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza", "Cero multas por infringir las normas de tránsito en el desarrollo de sus funciones", "Cero incidencias en la ruta asignada (robos, choques,etc)"]
            ],
            [
                "name" => "Conductor II",
                "positions" => ["164", "188", "199", "211", "346"],
                "objectives" => ["Cumplimiento mensual de los limites de velocidad en ruta", "Promedio mensual de ingreso no mayor a 3 Minutos de tardanza", "Cero multas por infringir las normas de tránsito en el desarrollo de sus funciones", "Cero incidencias en la ruta asignada (robos, choques,etc)"]
            ],
            [
                "name" => "Gerente Comercial Dp",
                "positions" => ["217"],
                "objectives" => ["Rentabilidad bruta mayor igual a 5.6%", "% de Efectividad de Cobranza", "Meses de stock/ % Días de antigüedad -  fierro", "Meses de stock/ % Días de antigüedad -  Koplast", "Meses de stock/ % Días de antigüedad - Eternit"]
            ],
            [
                "name" => "Jefe De Administracion",
                "positions" => ["147", "159", "169", "179", "180", "203", "236", "191"],
                "objectives" => ["Efectividad de liquidaciones: % de reconocimeinto de la liquidacion / total de liquidaciones registradas", "Notas de crèdito a proveedores", "Pago a proveedores", "Gestión de compras"]
            ],
            [
                "name" => "Jefe De Ventas Dp",
                "positions" => ["155", "168", "353"],
                "objectives" => ["Cumplimiento al 100% de la meta de venta establecida", "% de Efectividad de Cobranza", "Rentabilidad bruta mayor igual a 5.6%", "Incremento de cartera", "Mix de productos"]
            ],
            [
                "name" => "Supervisor De Almacen",
                "positions" => ["152", "163", "175", "186", "197", "231"],
                "objectives" => ["Promedio mensual de ingreso no mayor a 3 Minutos de tardanza"]
            ],
            [
                "name" => "Ejecutivo Comercial",
                "positions" => [],
                "objectives" => []
            ],
            [
                "name" => "Ejecutivo Comercial Industria",
                "positions" => [],
                "objectives" => ["Cumplimiento de presupuesto comercial", "% de Efectividad de Cobranza"]
            ]
        ];

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        HierarchicalCategoryDetail::truncate();
        HierarchicalCategory::truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');


        foreach ($data as $item) {

            $categoryFounded = HierarchicalCategory::where('name', $item['name'])->first();

            if (!$categoryFounded) {
                $category = HierarchicalCategory::create([
                    'name' => $item['name'],
                    'description' => 'Category for ' . $item['name']
                ]);
                if (!empty($item['positions']) && is_array($item['positions'])) {
                    $validIds = Position::whereIn('id', $item['positions'])->pluck('id')->toArray();
                    foreach ($validIds as $positionId) {
                        $category->children()->create([
                            'position_id' => $positionId,
                        ]);
                    }

                }
            }
        }
    }
}
