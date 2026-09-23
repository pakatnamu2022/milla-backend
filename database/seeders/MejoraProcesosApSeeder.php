<?php

namespace Database\Seeders;

use App\Models\gp\tics\pm\ScrumItem;
use App\Models\gp\tics\pm\ScrumProject;
use App\Models\gp\tics\pm\ScrumSprint;
use App\Models\gp\tics\pm\ScrumTag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

// php artisan db:seed --class=MejoraProcesosApSeeder
//
// Reemplaza TODO el contenido del módulo Scrum/PM (trunca proyectos, sprints,
// items, tags, comentarios, watchers e historial) y deja un único proyecto:
// "Mejora de Procesos AP". Cada mejora del cronograma de gerencia queda como
// una Historia, desglosada en un EDT de 2 tareas: "Análisis y Desarrollo"
// (análisis, backend y frontend van juntos en una sola tarea, ya que se
// trabajan de forma continua) y "Pruebas". Ya NO existe un sprint de Pruebas
// separado: ambas tareas viven dentro del mismo sprint de Desarrollo de la
// historia, como dos tareas más de esa historia.
//
// Por cada mes con fecha límite se genera un único sprint de Desarrollo, que
// cubre todo el mes (y se extiende un poco más si las pruebas de la última
// historia terminan después de fin de mes).
//
// Dentro del mes, las historias no comparten fecha: se reparten en escalera
// de desarrollo (cada una empieza su desarrollo justo donde termina la
// anterior, sin traslape) y, apenas termina el desarrollo de una historia,
// arranca su propia fase de Pruebas (en paralelo con el desarrollo de la
// siguiente historia, aunque ambas caigan en el mismo sprint).
//
// Esa dependencia queda registrada explícitamente con `predecessor_id`:
// - La tarea "Pruebas" de una historia tiene como predecesora la tarea
//   "Análisis y Desarrollo" de la MISMA historia.
// - La tarea "Análisis y Desarrollo" de una historia tiene como predecesora
//   la de la historia anterior en el mes (cadena de desarrollo).
// Con eso, si se edita la fecha fin de una tarea, ScrumItemService::update()
// desplaza en cascada a sus sucesoras la misma cantidad de días (ver
// cascadeDueDateShift). El Gantt además calcula el inicio visual de cada
// tarea como el fin de la anterior en su cadena (ver ScrumProjectService::
// gantt y GanttView.tsx).
class MejoraProcesosApSeeder extends Seeder
{
  private const CREATED_BY = 2641; // VALDIVIEZO SANDOVAL HECTOR DAVID

  private const PROJECT = [
    'name' => 'Mejora de Procesos AP',
    'description' => 'Proyecto único de mejoras de postventa (Recepción, Taller, Entrega, Reportes, Back Office) solicitadas por gerencia. Cada mejora es una Historia con su EDT (Análisis y Desarrollo, Pruebas) distribuido en escalera dentro del sprint de Desarrollo del mes.',
    'color' => '#6366f1',
    'status' => 'activo',
  ];

  private const MONTHS = [
    '2026-08', '2026-09', '2026-10', '2026-11', '2026-12',
    '2027-02', '2027-03', '2027-09', '2027-10', '2027-11',
  ];

  // Clasificación por área de proceso (se nota en la Lista como badge de color)
  private const CATEGORIES = [
    'recepcion'   => ['label' => 'Recepción', 'color' => 'blue'],
    'taller'      => ['label' => 'Taller', 'color' => 'orange'],
    'entrega'     => ['label' => 'Entrega', 'color' => 'emerald'],
    'reportes'    => ['label' => 'Reportes', 'color' => 'purple'],
    'back_office' => ['label' => 'Back Office', 'color' => 'gray'],
  ];

  // EDT estándar aplicado a cada historia: [título, fase, horas estimadas]
  // Ambas fases ("dev" y "test") viven en el mismo sprint de Desarrollo del mes.
  private const EDT = [
    ['Análisis y Desarrollo', 'dev', 38],
    ['Pruebas', 'test', 8],
  ];

  // [mes|null, título, fecha límite original|null, prioridad, responsable original, etiqueta corta, categoría]
  // La etiqueta corta identifica la historia de un vistazo en listas planas
  // (Kanban/Lista) donde el EDT repite "Análisis y Desarrollo"/"Pruebas" 47
  // veces: cada tarea queda como "[Etiqueta] Fase". La categoría es el área
  // de proceso (clave de self::CATEGORIES) y se aplica como tag tanto a la
  // historia como a sus tareas, para que se note en la Lista.
  private const HISTORIAS = [
    ['2026-08', 'Implementar APP para Asesor y poder usar su teléfono', '2026-08-31', 'alta', 'TICS', 'APP Asesor', 'recepcion'],
    ['2026-09', 'Implementar usuario para Agendamiento de Citas a Vigilante', '2026-09-30', 'alta', 'TICS', 'Agendamiento Vigilante', 'recepcion'],
    ['2026-09', 'Implementar reporte de facturación que incluya Anticipos y Rebates de Derco', '2026-09-30', 'alta', 'TICS + COMERCIAL', 'Reporte Facturación Derco', 'reportes'],
    ['2026-10', 'Implementar IA para comprobación de teléfono e e-mail', '2026-10-31', 'alta', 'TICS', 'IA Verificación Contacto', 'recepcion'],
    ['2026-10', 'Dashboard de Performance por Técnico mecánico: Cerradas / En curso / En pausa', '2026-10-31', 'alta', 'TICS', 'Dashboard Técnico', 'taller'],
    ['2026-10', 'Dashboard de Performance del VAT', '2026-10-31', 'alta', 'TICS', 'Dashboard VAT', 'taller'],
    ['2026-10', 'Incorporar en el dashboard los vehículos que regresan por el mismo error < 30 días', '2026-10-31', 'alta', 'TICS', 'Dashboard Reincidencias', 'taller'],
    ['2026-10', 'Implementar Reporte de Errores en email y teléfonos', '2026-10-31', 'alta', 'TICS', 'Reporte Errores Contacto', 'reportes'],
    ['2026-10', 'Implementar historial del cliente + vehículo', '2026-10-15', 'alta', 'TICS', 'Historial Cliente-Vehículo', 'recepcion'],
    ['2026-11', 'Implementar Protocolo de SMS y/o WA al cliente (apertura de OT)', '2026-11-30', 'alta', 'TICS', 'SMS/WA Apertura OT', 'recepcion'],
    ['2026-11', 'Implementar Protocolo de SMS y/o WA al cliente (trabajo de OT)', '2026-11-30', 'alta', 'TICS', 'SMS/WA Trabajo OT', 'taller'],
    ['2026-11', 'Implementar Protocolo de SMS y/o WA al cliente (control de calidad)', '2026-11-30', 'alta', 'TICS', 'SMS/WA Control Calidad', 'taller'],
    ['2026-11', 'Implementar Protocolo de SMS y/o WA al asesor a fin de preparar la salida del vehículo', '2026-11-30', 'alta', 'TICS', 'SMS/WA Salida Vehículo', 'entrega'],
    ['2026-11', 'Implementar modificación de campos errados según especificación de Inchcape', '2026-11-30', 'alta', 'TICS', 'Campos Inchcape', 'back_office'],
    ['2026-11', 'Implementar Reportes por Sede en archivos independientes', '2026-11-30', 'alta', 'TICS', 'Reportes por Sede', 'reportes'],
    ['2026-11', 'Implementar reporte sell out con el número de clientes', '2026-11-30', 'alta', 'TICS', 'Reporte Sell Out', 'reportes'],
    ['2026-11', 'Implementar Reporte de Anticipos abiertos con fechas de OT y fecha del anticipo', '2026-11-30', 'alta', 'TICS', 'Reporte Anticipos Abiertos', 'reportes'],
    ['2026-11', 'Implementar reporte OT abiertas que no pagadas', '2026-11-30', 'alta', 'TICS', 'Reporte OT No Pagadas', 'reportes'],
    ['2026-12', 'Implementar DASHBOARD de capacidad del Taller (todos los procesos)', '2026-12-31', 'media', 'TICS', 'Dashboard Capacidad Taller', 'taller'],
    ['2026-12', 'Implementar DASHBOARD de performance por Posición', '2026-12-31', 'media', 'TICS', 'Dashboard por Posición', 'taller'],
    ['2027-02', 'Implementar integración de facturas por pagar con IA', '2027-02-28', 'media', 'TICS', 'IA Facturas por Pagar', 'back_office'],
    ['2027-03', 'Implementar Reportes de cartera de clientes por captar', '2027-03-31', 'media', 'TICS', 'Reporte Cartera Clientes', 'reportes'],
    ['2027-09', 'Implementar Protocolo de Asignación automática de Técnicos mecánicos', '2027-09-30', 'media', 'TICS', 'Asignación Automática Técnicos', 'taller'],
    ['2027-09', 'Dashboard de Performance por Bahía de trabajo', '2027-09-30', 'media', 'TICS', 'Dashboard Bahías', 'taller'],
    ['2027-09', 'Implementar QR/Barcode en la bahía de trabajo', '2027-09-30', 'media', 'TICS', 'QR Bahía de Trabajo', 'taller'],
    ['2027-09', 'Implementar Reporte de Capacidad de las Bahías de trabajo', '2027-09-30', 'media', 'TICS', 'Reporte Capacidad Bahías', 'taller'],
    ['2027-10', 'Incorporar en el dashboard los errores por Técnico mecánico', '2027-10-31', 'media', 'TICS', 'Dashboard Errores Técnico', 'taller'],
    ['2027-11', 'Implementar código QR/Barcode para las OT', '2027-11-30', 'media', 'TICS', 'QR/Barcode OT', 'taller'],
    ['2027-11', 'Implementar Sistema de Cola para atención', '2027-11-30', 'media', 'TICS', 'Sistema de Cola', 'recepcion'],
    ['2027-11', 'Implementar Protocolo de Pago con Asesor de Servicio (dejando en tesorería solo pagos en efectivo)', '2027-11-30', 'media', 'TICS + FINANZAS', 'Pago con Asesor', 'entrega'],
    ['2027-11', 'Implementar Factura y/o boleta desde POS excepto para pagos en efectivo', '2027-11-30', 'media', 'TICS + FINANZAS', 'Factura desde POS', 'back_office'],
    ['2027-11', 'Implementar Encuesta de satisfacción por WA', '2027-11-30', 'media', 'TICS + MARKETING', 'Encuesta WA', 'entrega'],
    ['2027-11', 'Implementar medición automatizada de C.SAT / NPS', '2027-11-30', 'media', 'TICS + MARKETING', 'Medición C.SAT/NPS', 'entrega'],
    // sin fecha -> quedan en el backlog del proyecto (sin sprint, sin escalera)
    [null, 'Implementar agendamiento con Visión Taller (agendar desde cualquier punto de contacto)', null, 'baja', 'TICS', 'Agendamiento Visión Taller', 'recepcion'],
    [null, 'Implementar DASHBOARD Comercial', null, 'baja', 'TICS + COMERCIAL', 'Dashboard Comercial', 'reportes'],
    [null, 'Implementar Segmentación dentro de SIAN', null, 'baja', 'COMERCIAL + MARKETING + TICS', 'Segmentación SIAN', 'back_office'],
    [null, 'Implementar archivo CSV para integrar asiento de planillas', null, 'baja', 'TICS + RRHH', 'CSV Asiento Planillas', 'back_office'],
    [null, 'Implementar nueva Central de Contacto (Call to action)', null, 'baja', 'TICS', 'Central de Contacto', 'recepcion'],
    [null, 'Implementar nuevos Canales automatizados 24/7 con IA (incluye QR para Cita)', null, 'baja', 'TICS', 'Canales IA 24/7', 'recepcion'],
    [null, 'Implementar registro electrónico + lector de QR (reemplazo de cuaderno físico)', null, 'baja', 'TICS', 'Registro Electrónico QR', 'recepcion'],
    [null, 'Incorporar inteligencia comercial (Campañas/Accesorios/Lavados premium, etc.) — apertura de OT', null, 'baja', 'COMERCIAL + TICS', 'Inteligencia Comercial Apertura', 'recepcion'],
    [null, 'Incorporar inteligencia comercial (Campañas/Accesorios/Lavados premium, etc.) — trabajo de OT', null, 'baja', 'COMERCIAL + TICS', 'Inteligencia Comercial Trabajo', 'taller'],
    [null, 'Implementar Protocolo de Asignación automática de Lavaderos', null, 'baja', 'TICS', 'Asignación Automática Lavaderos', 'taller'],
    [null, 'Implementar Inteligencia comercial personalizada (inspección del vehículo)', null, 'baja', 'COMERCIAL + TICS', 'Inteligencia Comercial Inspección', 'recepcion'],
    [null, 'Implementar Ticket de Salida', null, 'baja', 'TICS', 'Ticket de Salida', 'entrega'],
    [null, 'Reemplazar cuaderno por registro automático (QR/Barcode) en salida de vehículo', null, 'baja', 'TICS', 'Registro Automático Salida', 'entrega'],
    [null, 'Implementar herramientas para digitalización de flujos al taller', null, 'baja', 'TICS', 'Digitalización Flujos Taller', 'taller'],
  ];

  // Detalle de negocio por historia, indexado por la etiqueta corta (self::HISTORIAS[*][5]).
  // objetivo: qué problema resuelve / qué logra el negocio con esta historia.
  // alcance: qué incluye la solución (pantallas, módulos, integraciones).
  // consideraciones: dudas a validar con negocio, dependencias con otras historias, riesgos.
  // pruebas: casos funcionales concretos a verificar antes de cerrar el sprint de pruebas.
  // Items marcados "Confirmar con negocio" tienen un dato real que no está definido en el
  // cronograma original (p.ej. la especificación de Inchcape) y debe levantarse antes de
  // poder dimensionar el desarrollo con precisión.
  private const DETALLE = [
    'APP Asesor' => [
      'objetivo' => 'Que el asesor de servicio atienda al cliente y gestione la OT desde su propio celular, sin depender de un puesto fijo con PC, agilizando la recepción del vehículo.',
      'alcance' => 'App móvil (o versión responsive de SIAN) para el asesor: login, agenda de citas del día, abrir/consultar OT, checklist de recepción con fotos, captura de firma del cliente.',
      'consideraciones' => [
        'Definir si es app nativa o web responsive.',
        'Validar conectividad de red en el patio/recepción.',
        'Permisos y roles del asesor en el celular.',
        '¿Reemplaza la tablet actual o convive con ella?',
      ],
      'pruebas' => [
        'Login desde un celular real (no solo emulador).',
        'Abrir una OT y guardar el checklist con fotos desde la cámara.',
        'Verificar que la firma capturada se guarda correctamente en la OT.',
        'Probar con conexión de datos móvil inestable.',
      ],
    ],
    'Agendamiento Vigilante' => [
      'objetivo' => 'Que el vigilante de entrada vea/registre la cita agendada del cliente al ingreso, para agilizar el control de acceso y anticipar al asesor que el cliente llegó.',
      'alcance' => 'Nuevo rol "Vigilante" en SIAN con acceso limitado a: buscar cita por placa/DNI/nombre y marcar "cliente llegó" indicando el asesor asignado.',
      'consideraciones' => [
        'Los permisos deben ser mínimos: no debe ver datos comerciales ni de facturación.',
        'Validar si vigilancia usará el mismo dispositivo que hoy usa para el cuaderno físico.',
      ],
      'pruebas' => [
        'Crear un usuario vigilante y verificar que solo ve lo permitido.',
        'Buscar una cita agendada y marcarla como "llegó".',
        'Verificar que el asesor asignado recibe la alerta correspondiente.',
      ],
    ],
    'Reporte Facturación Derco' => [
      'objetivo' => 'Tener un reporte de facturación que, además del monto facturado, muestre los anticipos aplicados y los rebates que reconoce Derco, para que Comercial y Finanzas concilien correctamente.',
      'alcance' => 'Reporte (pantalla + exportable) por rango de fechas y sede, con columnas: N° OT, monto facturado, anticipos aplicados, rebate Derco, neto.',
      'consideraciones' => [
        'Confirmar con negocio con Comercial/Finanzas la fórmula exacta del rebate de Derco y su fuente de datos (archivo de Derco, cálculo en SIAN, u otro).',
        'Validar el periodo de corte contable a usar.',
      ],
      'pruebas' => [
        'Generar el reporte de un mes cerrado y cuadrarlo contra el reporte manual actual.',
        'Validar un caso con anticipo parcial.',
        'Validar un caso con rebate aplicado y uno sin rebate.',
      ],
    ],
    'IA Verificación Contacto' => [
      'objetivo' => 'Reducir citas/OT con datos de contacto inválidos, verificando automáticamente que el teléfono y correo del cliente sean reales antes de continuar el flujo.',
      'alcance' => 'Validación en el formulario de datos del cliente: formato + validación real (envío de SMS/OTP o servicio de verificación de email).',
      'consideraciones' => [
        'Elegir proveedor de verificación (SMS OTP, servicio de validación de email) y su costo por verificación.',
        '¿Qué pasa si el cliente no puede verificar en el momento? ¿bloquea el flujo o solo advierte?',
      ],
      'pruebas' => [
        'Ingresar un teléfono inválido y verificar que el sistema lo detecta.',
        'Completar una verificación real con OTP.',
        'Ingresar un correo con typo común (p.ej. gmial.com) y verificar que se advierte.',
      ],
    ],
    'Dashboard Técnico' => [
      'objetivo' => 'Que el jefe de taller vea en un solo dashboard cuántas OT tiene cada técnico cerradas, en curso y en pausa, para balancear la carga de trabajo.',
      'alcance' => 'Dashboard filtrable por sede/fecha, con las 3 métricas por técnico y detalle de OT al hacer clic sobre cada una.',
      'consideraciones' => [
        'Definir qué estados de OT cuentan como "en pausa" (¿espera de repuesto?, ¿espera de aprobación del cliente?).',
        'Validar que el dato de "técnico asignado" ya existe de forma confiable en SIAN.',
      ],
      'pruebas' => [
        'Verificar que los conteos cuadran con las OT reales de un técnico.',
        'Probar el filtro de fecha/sede.',
        'Confirmar que un cambio de estado de OT se refleja correctamente en el dashboard.',
      ],
    ],
    'Dashboard VAT' => [
      'objetivo' => 'Medir el desempeño del VAT con un dashboard, en la misma línea que el de técnico mecánico.',
      'alcance' => 'Dashboard filtrable por sede/fecha con las métricas de desempeño que se definan para el VAT.',
      'consideraciones' => [
        'Confirmar con negocio qué es exactamente "VAT" en este contexto (rol/puesto dentro del taller) y qué métricas de desempeño se necesitan, antes de diseñar el dashboard.',
      ],
      'pruebas' => [
        'A definir una vez confirmado el rol y las métricas con negocio.',
      ],
    ],
    'Dashboard Reincidencias' => [
      'objetivo' => 'Detectar vehículos que vuelven al taller por la misma falla en menos de 30 días, para medir calidad del servicio y actuar sobre reincidencias.',
      'alcance' => 'Cruce de OT por VIN/placa comparando el motivo de ingreso actual contra OT cerradas de los últimos 30 días del mismo vehículo, mostrado en el dashboard con detalle de ambas OT.',
      'consideraciones' => [
        'Definir qué se considera "mismo error" (mismo código de falla, misma categoría de servicio, o coincidencia textual del motivo).',
        '¿Aplica solo a taller mecánico o también a otros procesos?',
      ],
      'pruebas' => [
        'Simular un vehículo con 2 OT por el mismo motivo dentro de 30 días y verificar que aparece marcado.',
        'Simular uno con 35 días de diferencia y verificar que NO aparece.',
        'Probar con motivos similares pero no idénticos.',
      ],
    ],
    'Reporte Errores Contacto' => [
      'objetivo' => 'Identificar y reportar clientes con correo o teléfono inválido/no contactable, para limpiar la base y evitar campañas o notificaciones fallidas.',
      'alcance' => 'Reporte de clientes con datos de contacto marcados como inválidos (bounce de correo, teléfono no verificado), filtrable por sede y fecha.',
      'consideraciones' => [
        'Depende de [[IA Verificación Contacto]] como fuente de qué se considera "inválido".',
        'Definir si se reintenta automáticamente o solo se reporta.',
      ],
      'pruebas' => [
        'Verificar que un cliente con correo que rebota aparece en el reporte.',
        'Verificar que un cliente con datos válidos no aparece.',
        'Exportar el reporte y validar el formato.',
      ],
    ],
    'Historial Cliente-Vehículo' => [
      'objetivo' => 'Que el asesor vea de un vistazo el historial completo de un cliente y de su vehículo (visitas, servicios, reclamos) al momento de atenderlo.',
      'alcance' => 'Panel en la ficha de OT/cliente con línea de tiempo: OT anteriores, servicios realizados, reclamos y notas relevantes.',
      'consideraciones' => [
        'Definir el volumen de histórico a mostrar (todo o últimos N años).',
        'Cuidar la performance de la consulta para clientes con muchas visitas.',
      ],
      'pruebas' => [
        'Abrir el historial de un cliente con varias OT previas y verificar el orden cronológico.',
        'Verificar que solo trae datos del cliente/vehículo correcto (no cruzar por nombre similar).',
        'Probar con un cliente sin historial (debe mostrarse vacío, sin error).',
      ],
    ],
    'SMS/WA Apertura OT' => [
      'objetivo' => 'Confirmar automáticamente al cliente por SMS/WhatsApp que su OT fue abierta, con el detalle básico (N° OT, fecha estimada).',
      'alcance' => 'Envío automático al crear la OT, usando el/los canal(es) disponibles (WhatsApp Business API y/o SMS), con plantilla de mensaje.',
      'consideraciones' => [
        'Confirmar el proveedor/API de WhatsApp Business ya contratado o a contratar.',
        'Definir la plantilla y si requiere aprobación de Meta.',
        '¿Qué pasa si el número no es válido? Definir fallback a SMS.',
      ],
      'pruebas' => [
        'Abrir una OT de prueba y verificar que el mensaje llega al número registrado.',
        'Probar con número inválido y verificar el fallback o el registro del fallo.',
        'Validar que el contenido del mensaje sea correcto (N° OT, sede, fecha).',
      ],
    ],
    'SMS/WA Trabajo OT' => [
      'objetivo' => 'Mantener informado al cliente durante el proceso de reparación (p.ej. cuando inicia el trabajo o hay un cambio relevante como repuesto pendiente).',
      'alcance' => 'Mismo mecanismo que apertura de OT, disparado al cambiar la OT a "en progreso" (y opcionalmente en hitos intermedios a definir).',
      'consideraciones' => [
        'Definir con Taller en qué momentos exactos se dispara (solo al iniciar, o también en pausas/repuesto pendiente).',
        'Evitar saturar al cliente con demasiados mensajes.',
      ],
      'pruebas' => [
        'Cambiar el estado de una OT a "en progreso" y verificar el envío.',
        'Verificar que no se duplica el mensaje si el estado se actualiza varias veces por error.',
      ],
    ],
    'SMS/WA Control Calidad' => [
      'objetivo' => 'Notificar al cliente cuando su vehículo pasa a control de calidad, como paso previo a la entrega.',
      'alcance' => 'Mismo mecanismo de mensajería, disparado al cambiar la OT al estado de control de calidad.',
      'consideraciones' => [
        'Definir si este mensaje reemplaza o complementa al de "trabajo de OT".',
        'Validar con Taller el estado exacto que dispara el envío.',
      ],
      'pruebas' => [
        'Cambiar la OT a control de calidad y verificar el mensaje.',
        'Validar que no se envía si la OT se salta ese estado (flujo alterno).',
      ],
    ],
    'SMS/WA Salida Vehículo' => [
      'objetivo' => 'Avisar al asesor (no al cliente) cuando el vehículo está listo, para que prepare la entrega antes de que el cliente llegue.',
      'alcance' => 'Notificación interna (push/SMS/WA) al asesor asignado cuando la OT pasa a "listo para entrega".',
      'consideraciones' => [
        'Definir el canal interno preferido: WhatsApp, notificación dentro de la [[APP Asesor]], o ambos.',
      ],
      'pruebas' => [
        'Marcar una OT como lista y verificar que el asesor asignado recibe la notificación.',
        'Verificar que si se reasigna el asesor, la notificación llega al correcto.',
      ],
    ],
    'Campos Inchcape' => [
      'objetivo' => 'Corregir campos del sistema que no cumplen con el formato/estándar exigido por Inchcape (marca/franquicia), evitando observaciones en auditorías o reportes hacia la marca.',
      'alcance' => 'Por definir exactamente según el documento de especificación de Inchcape.',
      'consideraciones' => [
        'Confirmar con negocio: obtener el documento/checklist oficial de Inchcape con el detalle de campos y formato exigido antes de poder dimensionar el desarrollo.',
      ],
      'pruebas' => [
        'Validar cada campo corregido contra el checklist oficial de Inchcape.',
        'Confirmar con el responsable de marca que ya no hay observaciones pendientes.',
      ],
    ],
    'Reportes por Sede' => [
      'objetivo' => 'Que los reportes gerenciales se puedan generar/exportar por sede de forma independiente (un archivo por sede), en vez de un único archivo consolidado.',
      'alcance' => 'Ajuste en el módulo de reportes existente para agregar filtro/exportación por sede, generando un archivo separado por cada una.',
      'consideraciones' => [
        'Definir cuáles reportes exactamente requieren este comportamiento (todos o solo algunos).',
        'Formato de salida (Excel/PDF) por sede.',
      ],
      'pruebas' => [
        'Generar el reporte para 2+ sedes y verificar que salen archivos separados con datos correctos de cada una.',
        'Validar que no se mezclan datos entre sedes.',
      ],
    ],
    'Reporte Sell Out' => [
      'objetivo' => 'Medir no solo el volumen de venta/servicio ("sell out") sino cuántos clientes distintos lo generaron, para análisis comercial.',
      'alcance' => 'Reporte con total de sell out por periodo/sede y columna adicional de número de clientes únicos atendidos.',
      'consideraciones' => [
        'Definir con Comercial la fuente exacta de "sell out" (ventas de repuestos, servicios, o ambos).',
      ],
      'pruebas' => [
        'Validar el conteo de clientes únicos contra una consulta manual de un periodo pequeño.',
        'Probar con un cliente que tuvo varias OT en el periodo (debe contar una sola vez).',
      ],
    ],
    'Reporte Anticipos Abiertos' => [
      'objetivo' => 'Que Finanzas visualice todos los anticipos recibidos que aún no han sido aplicados/cerrados, junto a la fecha de la OT y la fecha del anticipo.',
      'alcance' => 'Reporte con N° OT, cliente, fecha de OT, fecha del anticipo, monto y estado (abierto/aplicado).',
      'consideraciones' => [
        'Definir el criterio de "abierto": anticipo sin OT facturada aún, o con saldo pendiente de aplicar.',
      ],
      'pruebas' => [
        'Verificar que un anticipo recién aplicado desaparece del reporte (o cambia de estado).',
        'Validar las fechas contra el registro contable original.',
      ],
    ],
    'Reporte OT No Pagadas' => [
      'objetivo' => 'Ver todas las OT que ya fueron facturadas/cerradas pero que aún no tienen el pago registrado, para gestión de cobranza.',
      'alcance' => 'Reporte con N° OT, cliente, fecha de cierre, monto facturado, monto pagado y saldo pendiente.',
      'consideraciones' => [
        'Diferenciar de "Anticipos abiertos": este reporte es post-facturación, no pre-pago.',
        'Definir la antigüedad de deuda a resaltar.',
      ],
      'pruebas' => [
        'Validar que una OT pagada completamente no aparece.',
        'Validar que una OT con pago parcial muestra el saldo correcto.',
      ],
    ],
    'Dashboard Capacidad Taller' => [
      'objetivo' => 'Ver en tiempo real cuánta capacidad de taller está siendo usada vs disponible, considerando todos los procesos (recepción, mecánica, lavado, entrega), para planificar mejor las citas.',
      'alcance' => 'Dashboard con % de ocupación por proceso/etapa, filtrable por sede y fecha, alimentado por las OT activas en cada etapa.',
      'consideraciones' => [
        'Definir la capacidad máxima teórica por proceso (n° de bahías, n° de técnicos disponibles) como base del cálculo del %.',
      ],
      'pruebas' => [
        'Verificar que el % de ocupación cuadra con el conteo real de OT activas en cada etapa.',
        'Probar en hora pico y en hora valle.',
      ],
    ],
    'Dashboard por Posición' => [
      'objetivo' => 'Medir el desempeño del taller por posición/puesto de trabajo (no solo por técnico individual), para identificar cuellos de botella por estación.',
      'alcance' => 'Dashboard con tiempo promedio y OT atendidas por cada posición/bahía de trabajo.',
      'consideraciones' => [
        'Relación con [[Dashboard Bahías]]: definir si "Posición" y "Bahía" son el mismo concepto o distintos, para no duplicar desarrollo.',
      ],
      'pruebas' => [
        'Verificar que las métricas por posición cuadran con las OT reales que pasaron por esa posición.',
      ],
    ],
    'IA Facturas por Pagar' => [
      'objetivo' => 'Automatizar el registro/lectura de facturas de proveedores (por pagar) usando IA (OCR/extracción de datos), reduciendo la digitación manual.',
      'alcance' => 'Módulo que reciba la factura (PDF/imagen/XML), extraiga automáticamente proveedor, monto, fecha y N° de factura, y la registre como cuenta por pagar para validación.',
      'consideraciones' => [
        'Definir el % de precisión mínimo aceptable de la extracción y el flujo de corrección manual cuando falle.',
        'Elegir proveedor de OCR/IA a usar.',
      ],
      'pruebas' => [
        'Probar con una factura física escaneada y una digital (PDF nativo).',
        'Probar con una factura de mala calidad de imagen y verificar que pide corrección manual en vez de guardar datos errados.',
      ],
    ],
    'Reporte Cartera Clientes' => [
      'objetivo' => 'Identificar clientes potenciales/inactivos que Comercial puede recontactar para captar más servicio.',
      'alcance' => 'Reporte con clientes segmentados por antigüedad de última visita, sede y tipo de vehículo, para campañas de recaptación.',
      'consideraciones' => [
        'Definir con Comercial el criterio de "por captar" (nunca atendidos, o inactivos hace X meses).',
        'Relación con [[Segmentación SIAN]].',
      ],
      'pruebas' => [
        'Validar que un cliente inactivo hace 6 meses aparece según el criterio definido.',
        'Validar que un cliente recién atendido no aparece.',
      ],
    ],
    'Asignación Automática Técnicos' => [
      'objetivo' => 'Asignar automáticamente el técnico disponible más adecuado a cada OT, en vez de asignación manual, para balancear carga y reducir tiempos muertos.',
      'alcance' => 'Motor de asignación que considere disponibilidad del técnico, especialidad/tipo de servicio y carga actual, con opción de reasignación manual.',
      'consideraciones' => [
        'Definir reglas de prioridad (especialidad vs disponibilidad vs antigüedad de la OT en cola).',
        'Permitir override manual del jefe de taller.',
      ],
      'pruebas' => [
        'Simular 2 técnicos libres con distinta especialidad y una OT que requiere una específica; verificar asignación correcta.',
        'Probar la reasignación manual y confirmar que el sistema la respeta.',
      ],
    ],
    'Dashboard Bahías' => [
      'objetivo' => 'Medir el desempeño (ocupación, tiempo promedio, OT atendidas) de cada bahía física de trabajo del taller.',
      'alcance' => 'Dashboard con métricas por bahía, filtrable por sede y fecha.',
      'consideraciones' => [
        'Requiere que cada OT tenga registrada la bahía asignada (hoy puede no existir ese dato).',
        'Depende de [[QR Bahía de Trabajo]] para la captura automática.',
      ],
      'pruebas' => [
        'Verificar que las métricas de una bahía cuadran con las OT que realmente pasaron por ella.',
      ],
    ],
    'QR Bahía de Trabajo' => [
      'objetivo' => 'Que el técnico escanee un QR/código de barras físico en la bahía para registrar automáticamente inicio/fin de trabajo en esa posición, sin digitar nada.',
      'alcance' => 'Generación e impresión de códigos QR por bahía; app/lector para que el técnico escanee al iniciar y terminar; registro automático en la OT.',
      'consideraciones' => [
        'Definir el dispositivo de escaneo (celular del técnico o lector fijo).',
        '¿Qué pasa si el técnico olvida escanear? Definir fallback manual.',
      ],
      'pruebas' => [
        'Escanear el QR de una bahía y verificar que se registra el inicio.',
        'Escanear el de fin y verificar el tiempo calculado.',
        'Probar con un QR de otra sede/bahía por error.',
      ],
    ],
    'Reporte Capacidad Bahías' => [
      'objetivo' => 'Ver cuántas bahías están disponibles/ocupadas en cada momento, para planificar el agendamiento sin sobrecargar el taller.',
      'alcance' => 'Reporte/vista en tiempo real del estado de cada bahía (libre/ocupada/en mantenimiento) por sede.',
      'consideraciones' => [
        'Depende de [[QR Bahía de Trabajo]] (u otro mecanismo confiable) para conocer el estado real de cada bahía.',
      ],
      'pruebas' => [
        'Verificar que al iniciar un trabajo en una bahía (vía QR) el reporte la marca como ocupada.',
        'Verificar que al finalizar, se libera.',
      ],
    ],
    'Dashboard Errores Técnico' => [
      'objetivo' => 'Medir cuántos errores/reprocesos comete cada técnico (relacionado con [[Dashboard Reincidencias]]), para gestión de calidad y capacitación.',
      'alcance' => 'Cruce entre reincidencias (vehículo que regresa por el mismo error) y el técnico que atendió la OT original, mostrado por técnico en el dashboard.',
      'consideraciones' => [
        'Depende de que [[Dashboard Reincidencias]] esté implementado primero, ya que reutiliza esa lógica de detección.',
      ],
      'pruebas' => [
        'Con un caso de reincidencia simulado, verificar que se atribuye correctamente al técnico de la OT original.',
      ],
    ],
    'QR/Barcode OT' => [
      'objetivo' => 'Que cada OT tenga un código QR/barcode físico para identificarla rápidamente en cualquier punto del proceso (taller, entrega, archivo), sin buscar por número manualmente.',
      'alcance' => 'Generación automática del código al crear la OT, impreso en la orden física y disponible para escaneo en los distintos puntos (bahía, control de calidad, entrega).',
      'consideraciones' => [
        'Definir el estándar del código (QR vs barcode 1D) y qué información codifica.',
      ],
      'pruebas' => [
        'Generar una OT y verificar que el código escaneado abre la OT correcta.',
        'Probar impresión y escaneo desde un dispositivo real, no solo en pantalla.',
      ],
    ],
    'Sistema de Cola' => [
      'objetivo' => 'Ordenar la atención de clientes en recepción con un sistema de turnos/cola (ticket), evitando desorden y reclamos por "quién sigue".',
      'alcance' => 'Módulo de generación de turno (físico o digital vía QR), pantalla/tótem con el turno actual llamado, integrado a la asignación de asesor.',
      'consideraciones' => [
        'Definir si el turno se saca físicamente (impresora de tickets) o digital (QR desde el celular del cliente).',
        'Validar la capacidad del hardware existente en recepción.',
      ],
      'pruebas' => [
        'Generar un turno y verificar que aparece en la pantalla de llamado.',
        'Probar con varios turnos simultáneos y verificar el orden correcto (FIFO o por prioridad si aplica).',
      ],
    ],
    'Pago con Asesor' => [
      'objetivo' => 'Que el cliente pueda pagar con tarjeta/otros medios directamente con el asesor (POS móvil), dejando tesorería solo para pagos en efectivo, agilizando la entrega.',
      'alcance' => 'Asignación de POS móvil (u similar) al asesor, registro del pago en la OT desde el punto de atención del asesor, y conciliación con tesorería.',
      'consideraciones' => [
        'Definir los medios de pago habilitados para el asesor (tarjeta, Yape/Plin, etc.).',
        'Definir cómo se concilia el efectivo que sigue yendo a tesorería con lo cobrado por el asesor.',
      ],
      'pruebas' => [
        'Realizar un pago con tarjeta desde el POS del asesor y verificar que se refleja en la OT.',
        'Verificar que un pago en efectivo sigue derivando correctamente a tesorería.',
      ],
    ],
    'Factura desde POS' => [
      'objetivo' => 'Emitir automáticamente la factura/boleta electrónica desde el mismo punto de pago (POS) cuando el pago no es en efectivo, evitando un paso adicional en caja.',
      'alcance' => 'Integración del POS con el módulo de facturación electrónica de SIAN, excluyendo el flujo de efectivo (que mantiene su proceso actual).',
      'consideraciones' => [
        'Validar con Finanzas el cumplimiento SUNAT del comprobante emitido desde el POS.',
        '¿Qué pasa si falla la emisión electrónica en el momento? Definir reintento/contingencia.',
      ],
      'pruebas' => [
        'Realizar un pago con tarjeta y verificar que se emite la factura/boleta automáticamente.',
        'Verificar que un pago en efectivo NO dispara este flujo y sigue el proceso normal.',
      ],
    ],
    'Encuesta WA' => [
      'objetivo' => 'Medir la satisfacción del cliente enviando automáticamente una encuesta corta por WhatsApp después de la entrega del vehículo.',
      'alcance' => 'Envío automático de encuesta (link o preguntas directas por WA) al cerrar la OT/entregar el vehículo, con almacenamiento de respuestas.',
      'consideraciones' => [
        'Definir si usa la misma integración de WhatsApp de [[SMS/WA Apertura OT]].',
        'Definir cantidad y tipo de preguntas (relacionado con [[Medición C.SAT/NPS]]).',
      ],
      'pruebas' => [
        'Cerrar una OT de prueba y verificar que la encuesta llega por WA.',
        'Responder la encuesta y verificar que la respuesta se guarda asociada a la OT/cliente correcto.',
      ],
    ],
    'Medición C.SAT/NPS' => [
      'objetivo' => 'Calcular automáticamente los indicadores C.SAT (satisfacción) y NPS (recomendación) a partir de las respuestas de encuesta, sin tabular manualmente.',
      'alcance' => 'Cálculo automático de ambos indicadores por sede/periodo/asesor a partir de las respuestas de [[Encuesta WA]], con dashboard de tendencia.',
      'consideraciones' => [
        'Depende de que [[Encuesta WA]] esté implementada.',
        'Confirmar con Marketing la fórmula exacta de C.SAT y NPS que usan hoy, para no romper la comparabilidad histórica.',
      ],
      'pruebas' => [
        'Con un set de respuestas de prueba, verificar que el NPS y C.SAT calculados coinciden con el cálculo manual esperado.',
      ],
    ],
    'Agendamiento Visión Taller' => [
      'objetivo' => 'Permitir que el cliente agende su cita de taller desde cualquier canal de contacto (web, WA, call center, app), no solo desde un canal fijo.',
      'alcance' => 'Unificar el agendamiento en un servicio central consumido por todos los canales (web, WhatsApp, central de contacto), evitando duplicados/choques de horario.',
      'consideraciones' => [
        'Confirmar con negocio qué es exactamente "Visión Taller" (¿sistema/proveedor externo, o nombre del proyecto interno de agendamiento?) antes de diseñar la integración.',
        'Relación con [[Central de Contacto]] y [[Canales IA 24/7]].',
      ],
      'pruebas' => [
        'Agendar la misma franja horaria desde 2 canales distintos casi al mismo tiempo y verificar que no se duplica/choca.',
        'Verificar que la cita agendada por cualquier canal aparece igual en SIAN.',
      ],
    ],
    'Dashboard Comercial' => [
      'objetivo' => 'Dar a Comercial una vista consolidada de sus indicadores clave (ventas, sell out, captación, etc.) en un solo dashboard.',
      'alcance' => 'Dashboard que reutiliza/consolida los reportes comerciales ya definidos (Sell Out, Cartera de clientes, Inteligencia comercial) en un panel único.',
      'consideraciones' => [
        'Definir con Comercial qué KPIs exactos van en el dashboard principal, para no duplicar todos los reportes existentes sin criterio.',
      ],
      'pruebas' => [
        'Validar que cada KPI del dashboard cuadra con su reporte fuente individual.',
      ],
    ],
    'Segmentación SIAN' => [
      'objetivo' => 'Poder segmentar la base de clientes dentro de SIAN (por comportamiento, vehículo, frecuencia, etc.) para campañas dirigidas de Marketing/Comercial.',
      'alcance' => 'Módulo de segmentación con filtros combinables (antigüedad, tipo de vehículo, frecuencia de visita, sede) y exportación de la lista resultante.',
      'consideraciones' => [
        'Confirmar con negocio/Marketing qué criterios de segmentación son prioritarios primero.',
        'Relación con [[Reporte Cartera Clientes]], para no duplicar lógica.',
      ],
      'pruebas' => [
        'Crear un segmento con 2+ filtros combinados y verificar que la lista resultante es correcta.',
        'Exportar y validar el archivo.',
      ],
    ],
    'CSV Asiento Planillas' => [
      'objetivo' => 'Generar automáticamente el archivo CSV con el formato que RRHH/contabilidad necesita para el asiento contable de planillas, sin armarlo manualmente.',
      'alcance' => 'Exportación CSV desde el módulo correspondiente con las columnas/formato exigido por el sistema contable de RRHH.',
      'consideraciones' => [
        'Confirmar con RRHH el layout exacto del CSV (columnas, orden, separador, codificación) antes de desarrollar.',
      ],
      'pruebas' => [
        'Generar el CSV de un periodo y validar el formato con RRHH (o cargarlo en su sistema contable) para confirmar que es aceptado sin errores.',
      ],
    ],
    'Central de Contacto' => [
      'objetivo' => 'Tener un canal centralizado de contacto (call center/central) desde donde se puedan originar acciones (agendar cita, resolver consulta) directamente en SIAN.',
      'alcance' => 'Interfaz para el operador de central con búsqueda de cliente/vehículo y acciones rápidas (agendar, derivar, registrar consulta).',
      'consideraciones' => [
        'Definir si reemplaza un proceso/herramienta actual o es completamente nueva.',
        'Relación con [[Agendamiento Visión Taller]] y [[Canales IA 24/7]].',
      ],
      'pruebas' => [
        'Desde la central, agendar una cita para un cliente y verificar que se refleja igual que si se agendara por otro canal.',
      ],
    ],
    'Canales IA 24/7' => [
      'objetivo' => 'Que el cliente pueda agendar o resolver consultas básicas fuera de horario de atención mediante un canal automatizado con IA (chatbot), incluyendo agendar cita por QR.',
      'alcance' => 'Chatbot (WA u otro canal) disponible 24/7 con flujo de agendamiento de cita y respuestas a preguntas frecuentes; QR de acceso rápido a agendar cita.',
      'consideraciones' => [
        'Definir el alcance real de la IA (solo agendar + FAQ, o también consultas de estado de OT).',
        'Elegir proveedor de IA/chatbot a usar.',
      ],
      'pruebas' => [
        'Interactuar con el bot fuera de horario y agendar una cita completa sin intervención humana.',
        'Escanear el QR de cita y verificar que lleva al flujo correcto.',
      ],
    ],
    'Registro Electrónico QR' => [
      'objetivo' => 'Reemplazar el cuaderno físico de registro (de ingreso/control) por un registro electrónico con lectura de QR, eliminando el papel y el riesgo de pérdida de datos.',
      'alcance' => 'Pantalla/dispositivo de registro con lector QR en el punto donde hoy se usa el cuaderno, con historial consultable digitalmente.',
      'consideraciones' => [
        'Identificar exactamente cuál cuaderno físico se reemplaza (recepción, vigilancia, u otro), ya que hay otra historia relacionada a registro físico: [[Registro Automático Salida]].',
      ],
      'pruebas' => [
        'Escanear un QR y verificar que el registro queda guardado con fecha/hora correcta.',
        'Consultar el historial de un día y compararlo contra lo registrado.',
      ],
    ],
    'Inteligencia Comercial Apertura' => [
      'objetivo' => 'Que al abrir la OT, el sistema sugiera automáticamente al asesor ofertas relevantes (campañas activas, accesorios, lavado premium) según el perfil del cliente/vehículo.',
      'alcance' => 'Motor de sugerencias en la pantalla de apertura de OT, basado en reglas (tipo de vehículo, campañas vigentes, historial de compra).',
      'consideraciones' => [
        'Definir el motor de reglas inicial (basado en reglas de negocio) vs uno con IA/ML más adelante.',
        'Definir quién mantiene las campañas activas en el sistema.',
      ],
      'pruebas' => [
        'Abrir una OT de un vehículo que cumple una campaña activa y verificar que la sugerencia aparece.',
        'Verificar que no aparece si la campaña ya venció.',
      ],
    ],
    'Inteligencia Comercial Trabajo' => [
      'objetivo' => 'Igual que la sugerencia de apertura, pero durante el trabajo de la OT (p.ej. al detectar un hallazgo en la inspección, sugerir un servicio/accesorio adicional relacionado).',
      'alcance' => 'Sugerencias comerciales activadas por eventos durante el proceso de taller (hallazgos de inspección, cambio de estado), no solo al abrir la OT.',
      'consideraciones' => [
        'Depende de que [[Inteligencia Comercial Apertura]] tenga el motor base ya construido, para reutilizarlo.',
      ],
      'pruebas' => [
        'Registrar un hallazgo de inspección que dispare una sugerencia y verificar que el asesor la recibe a tiempo para ofrecerla al cliente.',
      ],
    ],
    'Asignación Automática Lavaderos' => [
      'objetivo' => 'Asignar automáticamente el lavado del vehículo al personal/lavadero disponible, similar a la asignación automática de técnicos pero para el proceso de lavado.',
      'alcance' => 'Motor de asignación por disponibilidad de personal de lavado, integrado al flujo de la OT cuando llega a esa etapa.',
      'consideraciones' => [
        'Definir si el lavado es un proceso interno o tercerizado, ya que afecta el diseño de la asignación.',
      ],
      'pruebas' => [
        'Simular 2 lavadores disponibles y verificar la asignación correcta.',
        'Probar cuando no hay ninguno disponible (debe encolar, no fallar).',
      ],
    ],
    'Inteligencia Comercial Inspección' => [
      'objetivo' => 'Generar sugerencias comerciales personalizadas a partir de los hallazgos específicos de la inspección del vehículo (p.ej. llanta desgastada → sugerir cambio).',
      'alcance' => 'Reglas que mapeen hallazgos de inspección a sugerencias comerciales específicas, mostradas al asesor para ofrecer al cliente.',
      'consideraciones' => [
        'Relación directa con [[Inteligencia Comercial Trabajo]]: definir si es la misma historia dividida o una capa adicional más específica, para no duplicar desarrollo.',
      ],
      'pruebas' => [
        'Registrar un hallazgo específico de inspección y verificar que genera la sugerencia comercial correspondiente y no una genérica.',
      ],
    ],
    'Ticket de Salida' => [
      'objetivo' => 'Generar un ticket/comprobante de salida del vehículo del taller, como control físico/digital de que el vehículo fue entregado correctamente.',
      'alcance' => 'Documento (impreso o digital) generado al momento de la entrega, con datos de la OT, checklist de entrega y firma.',
      'consideraciones' => [
        'Definir si reemplaza el proceso actual del cuaderno de salida (ver también [[Registro Automático Salida]]) para no duplicar esfuerzos.',
      ],
      'pruebas' => [
        'Generar el ticket al entregar un vehículo y verificar que los datos coinciden con la OT.',
        'Verificar que no se puede generar el ticket si la OT no está realmente lista.',
      ],
    ],
    'Registro Automático Salida' => [
      'objetivo' => 'Reemplazar el cuaderno físico donde hoy se anota la salida del vehículo del taller/patio por un registro automático vía QR/barcode.',
      'alcance' => 'Escaneo del QR/barcode de la OT (ver [[QR/Barcode OT]]) al momento de la salida física, registrando automáticamente fecha/hora y responsable.',
      'consideraciones' => [
        'Reutilizar el código QR generado en [[QR/Barcode OT]] en vez de crear uno nuevo, para mantener un solo código por OT en todo el flujo.',
      ],
      'pruebas' => [
        'Escanear el QR de una OT al sacarla del patio y verificar que se registra la salida con fecha/hora correcta.',
        'Probar el caso de escaneo duplicado (no debe generar 2 registros de salida).',
      ],
    ],
    'Digitalización Flujos Taller' => [
      'objetivo' => 'Historia paraguas para llevar a digital los flujos de taller que hoy siguen siendo manuales/en papel, más allá de los ya cubiertos por historias específicas de este proyecto.',
      'alcance' => 'Por definir con negocio: levantar junto a Taller qué flujos concretos siguen en papel y no están cubiertos por las demás historias (QR bahía, registro de salida, ticket de salida, etc.), y priorizarlos.',
      'consideraciones' => [
        'Hacer primero un levantamiento con Taller de los flujos pendientes.',
        'Evitar duplicar alcance con historias ya específicas de este proyecto.',
      ],
      'pruebas' => [
        'A definir según los flujos concretos que resulten del levantamiento.',
      ],
    ],
  ];

  public function run(): void
  {
    DB::statement('SET FOREIGN_KEY_CHECKS=0');
    DB::table('scrum_item_watcher')->truncate();
    DB::table('scrum_item_tag')->truncate();
    DB::table('scrum_item_history')->truncate();
    DB::table('scrum_comments')->truncate();
    DB::table('scrum_items')->truncate();
    DB::table('scrum_tags')->truncate();
    DB::table('scrum_sprints')->truncate();
    DB::table('scrum_projects')->truncate();
    DB::statement('SET FOREIGN_KEY_CHECKS=1');

    $project = ScrumProject::create([...self::PROJECT, 'created_by' => self::CREATED_BY]);

    $tagIds = [];
    foreach (self::CATEGORIES as $key => ['label' => $label, 'color' => $color]) {
      $tagIds[$key] = ScrumTag::create([
        'project_id' => $project->id,
        'name' => $label,
        'color' => $color,
      ])->id;
    }

    $today = Carbon::today();
    $testDurationDays = 2; // duración de la fase de Pruebas de cada historia

    // Agrupar historias por mes para poder escalonar sus fechas dentro del mes
    $byMonth = [];
    foreach (self::HISTORIAS as $historia) {
      $byMonth[$historia[0] ?? '_backlog'][] = $historia;
    }

    $order = 0;

    foreach (self::MONTHS as $monthKey) {
      $historiasDelMes = $byMonth[$monthKey] ?? [];
      $n = count($historiasDelMes);
      if ($n === 0) {
        continue;
      }

      $monthStart = Carbon::parse($monthKey . '-01');
      $monthEnd = $monthStart->copy()->endOfMonth();
      $devSpanDays = max(1, $monthStart->diffInDays($monthEnd));
      $label = ucfirst($monthStart->translatedFormat('F Y'));

      // Escalera de desarrollo: la historia i empieza su desarrollo justo donde
      // termina la i-1 (sin traslape), repartidas en todo el mes. La fase de
      // Pruebas de cada historia arranca apenas termina SU desarrollo (no al
      // final del mes), así que se solapa con el desarrollo de la(s)
      // siguiente(s) historia(s): desarrollo y pruebas avanzan en paralelo.
      $slices = [];
      foreach ($historiasDelMes as $i => $h) {
        $sliceStart = (int) round(($i / $n) * $devSpanDays);
        $sliceEnd = (int) round((($i + 1) / $n) * $devSpanDays);

        $devStart = $monthStart->copy()->addDays($sliceStart);
        $devDue = $monthStart->copy()->addDays($sliceEnd);
        $testStart = $devDue->copy();
        $testDue = $testStart->copy()->addDays($testDurationDays);

        $slices[] = compact('devStart', 'devDue', 'testStart', 'testDue');
      }

      // El sprint de Desarrollo cubre todo el mes, extendiéndose hasta que
      // terminan las pruebas de la última historia si eso cae después de fin
      // de mes (ya no hay un sprint de Pruebas aparte que las contenga).
      $sprintEnd = collect($slices)->max('testDue');
      if ($sprintEnd->lt($monthEnd)) {
        $sprintEnd = $monthEnd->copy();
      }

      $devSprint = ScrumSprint::create([
        'project_id' => $project->id,
        'name' => "Desarrollo - {$label}",
        'start_date' => $monthStart->format('Y-m-d'),
        'end_date' => $sprintEnd->format('Y-m-d'),
        'status' => $this->sprintStatus($monthStart, $sprintEnd, $today),
      ]);

      $historiaStatus = match ($devSprint->status) {
        'cerrado' => 'hecho',
        'activo' => 'por_hacer',
        default => 'backlog',
      };
      $devTaskStatus = $historiaStatus;
      $testTaskStatus = $historiaStatus;

      $previousDevTaskId = null;

      foreach ($historiasDelMes as $i => [, $title, , $priority, $resp, $shortLabel, $category]) {
        $order++;
        ['devStart' => $developmentStart, 'devDue' => $developmentDue, 'testStart' => $testStart, 'testDue' => $testDue] = $slices[$i];

        $historia = ScrumItem::create([
          'project_id' => $project->id,
          'sprint_id' => $devSprint->id,
          'type' => 'historia',
          'title' => $title,
          'description' => $this->historiaDescription($shortLabel, $resp),
          'status' => $historiaStatus,
          'priority' => $priority,
          'start_date' => $developmentStart->format('Y-m-d'),
          'due_date' => $developmentDue->format('Y-m-d'),
          'created_by' => self::CREATED_BY,
          'order' => $order,
          'closed_at' => $historiaStatus === 'hecho' ? now() : null,
        ]);
        $historia->tags()->attach($tagIds[$category]);

        [, , $devHours] = self::EDT[0];
        [, , $testHours] = self::EDT[1];

        $devTask = ScrumItem::create([
          'project_id' => $project->id,
          'sprint_id' => $devSprint->id,
          'parent_id' => $historia->id,
          'predecessor_id' => $previousDevTaskId, // encadenada al desarrollo de la historia anterior del mes
          'type' => 'tarea',
          'title' => "[{$shortLabel}] Análisis y Desarrollo",
          'description' => $this->analisisDescription($shortLabel, $title),
          'status' => $devTaskStatus,
          'priority' => $priority,
          'estimated_hours' => $devHours,
          'start_date' => $developmentStart->format('Y-m-d'),
          'due_date' => $developmentDue->format('Y-m-d'),
          'created_by' => self::CREATED_BY,
          'order' => 1,
          'closed_at' => $devTaskStatus === 'hecho' ? now() : null,
        ]);
        $devTask->tags()->attach($tagIds[$category]);

        $testTask = ScrumItem::create([
          'project_id' => $project->id,
          'sprint_id' => $devSprint->id,
          'parent_id' => $historia->id,
          'predecessor_id' => $devTask->id, // las pruebas de la historia dependen de su propio desarrollo
          'type' => 'tarea',
          'title' => "Pruebas de {$shortLabel}",
          'description' => $this->pruebasDescription($shortLabel, $title),
          'status' => $testTaskStatus,
          'priority' => $priority,
          'estimated_hours' => $testHours,
          'start_date' => $testStart->format('Y-m-d'),
          'due_date' => $testDue->format('Y-m-d'),
          'created_by' => self::CREATED_BY,
          'order' => 2,
          'closed_at' => $testTaskStatus === 'hecho' ? now() : null,
        ]);
        $testTask->tags()->attach($tagIds[$category]);

        $previousDevTaskId = $devTask->id;
      }
    }

    // Historias sin mes asignado: quedan en el backlog del proyecto, sin
    // sprint, sin fechas y sin dependencias.
    foreach ($byMonth['_backlog'] ?? [] as [, $title, , $priority, $resp, $shortLabel, $category]) {
      $order++;

      $historia = ScrumItem::create([
        'project_id' => $project->id,
        'sprint_id' => null,
        'type' => 'historia',
        'title' => $title,
        'description' => $this->historiaDescription($shortLabel, $resp),
        'status' => 'backlog',
        'priority' => $priority,
        'created_by' => self::CREATED_BY,
        'order' => $order,
      ]);
      $historia->tags()->attach($tagIds[$category]);

      [, , $devHours] = self::EDT[0];
      [, , $testHours] = self::EDT[1];

      $devTask = ScrumItem::create([
        'project_id' => $project->id,
        'parent_id' => $historia->id,
        'type' => 'tarea',
        'title' => "[{$shortLabel}] Análisis y Desarrollo",
        'description' => $this->analisisDescription($shortLabel, $title),
        'status' => 'backlog',
        'priority' => $priority,
        'estimated_hours' => $devHours,
        'created_by' => self::CREATED_BY,
        'order' => 1,
      ]);
      $devTask->tags()->attach($tagIds[$category]);

      $testTask = ScrumItem::create([
        'project_id' => $project->id,
        'parent_id' => $historia->id,
        'predecessor_id' => $devTask->id,
        'type' => 'tarea',
        'title' => "Pruebas de {$shortLabel}",
        'description' => $this->pruebasDescription($shortLabel, $title),
        'status' => 'backlog',
        'priority' => $priority,
        'estimated_hours' => $testHours,
        'created_by' => self::CREATED_BY,
        'order' => 2,
      ]);
      $testTask->tags()->attach($tagIds[$category]);
    }
  }

  private function bullets(array $items): string
  {
    return collect($items)->map(fn ($i) => "- {$i}")->implode("\n");
  }

  private function historiaDescription(string $shortLabel, string $resp): string
  {
    $d = self::DETALLE[$shortLabel] ?? null;
    if (!$d) {
      return "Responsable original: {$resp}. Cargado desde el cronograma de mejoras de gerencia (set. 2026).";
    }

    return "Responsable: {$resp}\n\n"
      . "Objetivo\n{$d['objetivo']}\n\n"
      . "Alcance\n{$d['alcance']}\n\n"
      . "A considerar / investigar\n" . $this->bullets($d['consideraciones']);
  }

  private function analisisDescription(string $shortLabel, string $title): string
  {
    $d = self::DETALLE[$shortLabel] ?? null;
    if (!$d) {
      return "Analizar el requerimiento, diseñar la solución e implementar backend y frontend en paralelo de: {$title}. Incluye API/lógica de negocio (backend) e interfaz/interacción (frontend).";
    }

    return "Construir lo definido en la historia \"{$title}\".\n\n"
      . "Objetivo\n{$d['objetivo']}\n\n"
      . "Alcance (backend + frontend)\n{$d['alcance']}\n\n"
      . "A considerar / investigar\n" . $this->bullets($d['consideraciones']);
  }

  private function pruebasDescription(string $shortLabel, string $title): string
  {
    $d = self::DETALLE[$shortLabel] ?? null;
    if (!$d) {
      return "Probar el flujo completo (backend + frontend) de: {$title} antes de cerrar el sprint.";
    }

    return "Validar antes de cerrar el sprint el flujo completo (backend + frontend) de \"{$title}\".\n\n"
      . "Qué probar\n" . $this->bullets($d['pruebas']);
  }

  private function sprintStatus(Carbon $start, Carbon $end, Carbon $today): string
  {
    if ($today->between($start, $end)) return 'activo';
    if ($end->lt($today)) return 'cerrado';
    return 'planeado';
  }
}
