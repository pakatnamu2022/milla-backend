<?php

namespace Database\Seeders;

use App\Http\Services\gp\gestionhumana\evaluacion\EvaluationCategoryCompetenceDetailService;
use App\Models\gp\gestionhumana\evaluacion\EvaluationCategoryCompetenceDetail;
use App\Models\gp\gestionhumana\evaluacion\EvaluationCategoryObjectiveDetail;
use App\Models\gp\gestionhumana\evaluacion\EvaluationCompetence;
use App\Models\gp\gestionhumana\evaluacion\EvaluationCycle;
use App\Models\gp\gestionhumana\evaluacion\EvaluationObjective;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonCycleDetail;
use App\Models\gp\gestionhumana\evaluacion\EvaluationsubCompetence;
use App\Models\gp\gestionhumana\evaluacion\HierarchicalCategory;
use App\Models\gp\gestionhumana\evaluacion\HierarchicalCategoryDetail;
use App\Models\gp\gestionhumana\personal\Worker;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompetenceSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    $data = [
      [
        "competence" => "Control del Mantenimiento: Soluciona los problemas de manera efectiva. Evalúa el rendimiento de mantenimiento e identifica oportunidades de reducción de costos.",
        "subCompetence" => "Control De Costos",
        "category" => "Jefe De Operaciones Y Mantenimiento"
      ],
      [
        "competence" => "Control del Mantenimiento: Soluciona los problemas de manera efectiva. Evalúa el rendimiento de mantenimiento e identifica oportunidades de reducción de costos.",
        "subCompetence" => "Solución De Problemas",
        "category" => "Jefe De Operaciones Y Mantenimiento"
      ],
      [
        "competence" => "Control del Mantenimiento: Soluciona los problemas de manera efectiva. Evalúa el rendimiento de mantenimiento e identifica oportunidades de reducción de costos.",
        "subCompetence" => "Evaluación De Indicadores",
        "category" => "Jefe De Operaciones Y Mantenimiento"
      ],
      [
        "competence" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "subCompetence" => "Control",
        "category" => "Jefe De Operaciones Y Mantenimiento"
      ],
      [
        "competence" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "subCompetence" => "Supervisión",
        "category" => "Jefe De Operaciones Y Mantenimiento"
      ],
      [
        "competence" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "subCompetence" => "Seguimiento",
        "category" => "Jefe De Operaciones Y Mantenimiento"
      ],
      [
        "competence" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "subCompetence" => "Retroalimentación",
        "category" => "Jefe De Operaciones Y Mantenimiento"
      ],
      [
        "competence" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "subCompetence" => "Gestión De Consecuencias",
        "category" => "Jefe De Operaciones Y Mantenimiento"
      ],
      [
        "competence" => "Gestión de normas y procedimientos: Se preocupa y promueve respeto de las normas y procedimientos. Vigila el cumplimiento de las normas e interviene para corregir faltas.",
        "subCompetence" => "Claridad En La Orientación",
        "category" => "Jefe De Operaciones Y Mantenimiento"
      ],
      [
        "competence" => "Gestión de normas y procedimientos: Se preocupa y promueve respeto de las normas y procedimientos. Vigila el cumplimiento de las normas e interviene para corregir faltas.",
        "subCompetence" => "Gestión De Consecuencias V1",
        "category" => "Jefe De Operaciones Y Mantenimiento"
      ],
      [
        "competence" => "Gestión de normas y procedimientos: Se preocupa y promueve respeto de las normas y procedimientos. Vigila el cumplimiento de las normas e interviene para corregir faltas.",
        "subCompetence" => "Revisión Y Seguimiento",
        "category" => "Jefe De Operaciones Y Mantenimiento"
      ],
      [
        "competence" => "Supervisión Efectiva: Supervisar el desempeño de los colaboradores brindando retrolimentación a partir del mismo y gestionar los resultados obtenidos.",
        "subCompetence" => "Gestión De Consecuencias Del Desempeño",
        "category" => "Jefe De Operaciones Y Mantenimiento"
      ],
      [
        "competence" => "Supervisión Efectiva: Supervisar el desempeño de los colaboradores brindando retrolimentación a partir del mismo y gestionar los resultados obtenidos.",
        "subCompetence" => "Retroalimentación Sobre El Proceso",
        "category" => "Jefe De Operaciones Y Mantenimiento"
      ],
      [
        "competence" => "Supervisión Efectiva: Supervisar el desempeño de los colaboradores brindando retrolimentación a partir del mismo y gestionar los resultados obtenidos.",
        "subCompetence" => "Seguimiento Efectivo",
        "category" => "Jefe De Operaciones Y Mantenimiento"
      ],
      [
        "competence" => "Gestión de Proveedores - Comercial: Garantizar una adecuada relación con los proveedores. Lograr convencerlos para obtener beneficios preferenciales manejando de manera adecuada los reclamos.",
        "subCompetence" => "Traspase De Información",
        "category" => "Logística TP"
      ],
      [
        "competence" => "Gestión de Proveedores - Comercial: Garantizar una adecuada relación con los proveedores. Lograr convencerlos para obtener beneficios preferenciales manejando de manera adecuada los reclamos.",
        "subCompetence" => "Solución De Problemas V1",
        "category" => "Logística TP"
      ],
      [
        "competence" => "Gestión de Proveedores - Comercial: Garantizar una adecuada relación con los proveedores. Lograr convencerlos para obtener beneficios preferenciales manejando de manera adecuada los reclamos.",
        "subCompetence" => "Seguimiento De Cobranzas",
        "category" => "Logística TP"
      ],
      [
        "competence" => "Gestión de Proveedores - Comercial: Garantizar una adecuada relación con los proveedores. Lograr convencerlos para obtener beneficios preferenciales manejando de manera adecuada los reclamos.",
        "subCompetence" => "Relación Con El Proveedor",
        "category" => "Logística TP"
      ],
      [
        "competence" => "Gestión de Proveedores - Comercial: Garantizar una adecuada relación con los proveedores. Lograr convencerlos para obtener beneficios preferenciales manejando de manera adecuada los reclamos.",
        "subCompetence" => "Persuasión",
        "category" => "Logística TP"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Claridad en el mensaje",
        "category" => "Logística TP"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Respuesta Oportuna",
        "category" => "Logística TP"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Escucha",
        "category" => "Logística TP"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Asertividad",
        "category" => "Logística TP"
      ],
      [
        "competence" => "Mentalidad de Dueño V2: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Anticipación A Requerimientos",
        "category" => "Logística TP"
      ],
      [
        "competence" => "Mentalidad de Dueño V2: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Solución De Problemas Difíciles",
        "category" => "Logística TP"
      ],
      [
        "competence" => "Mentalidad de Dueño V2: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Pertenencia",
        "category" => "Logística TP"
      ],
      [
        "competence" => "Registro y Manejo de Información - Versión 2 AP: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Orientación Al Detalle",
        "category" => "Logística TP"
      ],
      [
        "competence" => "Registro y Manejo de Información - Versión 2 AP: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Organización",
        "category" => "Logística TP"
      ],
      [
        "competence" => "Registro y Manejo de Información - Versión 2 AP: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Cumplimiento De Procesos Operativos",
        "category" => "Logística TP"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Seguimiento A La Atención",
        "category" => "Logística TP"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Atención Presencial Oportuna",
        "category" => "Logística TP"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Atención Efectiva A Consultas",
        "category" => "Logística TP"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Amabilidad Y Cercanía",
        "category" => "Logística TP"
      ],
      [
        "competence" => "Gestión de normas y procedimientos: Se preocupa y promueve respeto de las normas y procedimientos. Vigila el cumplimiento de las normas e interviene para corregir faltas.",
        "subCompetence" => "Claridad En La Orientación",
        "category" => "Supervisión de operaciones y mantenimiento"
      ],
      [
        "competence" => "Gestión de normas y procedimientos: Se preocupa y promueve respeto de las normas y procedimientos. Vigila el cumplimiento de las normas e interviene para corregir faltas.",
        "subCompetence" => "Gestión De Consecuencias V1",
        "category" => "Supervisión de operaciones y mantenimiento"
      ],
      [
        "competence" => "Gestión de normas y procedimientos: Se preocupa y promueve respeto de las normas y procedimientos. Vigila el cumplimiento de las normas e interviene para corregir faltas.",
        "subCompetence" => "Respeto A La Norma",
        "category" => "Supervisión de operaciones y mantenimiento"
      ],
      [
        "competence" => "Gestión de normas y procedimientos: Se preocupa y promueve respeto de las normas y procedimientos. Vigila el cumplimiento de las normas e interviene para corregir faltas.",
        "subCompetence" => "Revisión Y Seguimiento",
        "category" => "Supervisión de operaciones y mantenimiento"
      ],
      [
        "competence" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "subCompetence" => "Alineamiento",
        "category" => "Supervisión de operaciones y mantenimiento"
      ],
      [
        "competence" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "subCompetence" => "Convicción",
        "category" => "Supervisión de operaciones y mantenimiento"
      ],
      [
        "competence" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "subCompetence" => "Dirección De Equipos",
        "category" => "Supervisión de operaciones y mantenimiento"
      ],
      [
        "competence" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "subCompetence" => "Resolución De Conflictos",
        "category" => "Supervisión de operaciones y mantenimiento"
      ],
      [
        "competence" => "Manejo de problemas complejos: Analiza los problemas utilizando diferentes fuentesde información a fin de definir estrategias que le permitan solucionar las dificultades de manera adecuada.",
        "subCompetence" => "Adaptabilidad",
        "category" => "Supervisión de operaciones y mantenimiento"
      ],
      [
        "competence" => "Manejo de problemas complejos: Analiza los problemas utilizando diferentes fuentesde información a fin de definir estrategias que le permitan solucionar las dificultades de manera adecuada.",
        "subCompetence" => "Diagnóstico De Problemas",
        "category" => "Supervisión de operaciones y mantenimiento"
      ],
      [
        "competence" => "Manejo de problemas complejos: Analiza los problemas utilizando diferentes fuentesde información a fin de definir estrategias que le permitan solucionar las dificultades de manera adecuada.",
        "subCompetence" => "Solución De Problemas Internos",
        "category" => "Supervisión de operaciones y mantenimiento"
      ],
      [
        "competence" => "Planificación: Determinar las metas y prioridades de los proyectos, estableciendo las acciones, los plazos y los recursos necesarios. Así como los instrumentos de medición y segumiento.",
        "subCompetence" => "Creación De Planes",
        "category" => "Supervisión de operaciones y mantenimiento"
      ],
      [
        "competence" => "Planificación: Determinar las metas y prioridades de los proyectos, estableciendo las acciones, los plazos y los recursos necesarios. Así como los instrumentos de medición y segumiento.",
        "subCompetence" => "Definir Metas e indicadores",
        "category" => "Supervisión de operaciones y mantenimiento"
      ],
      [
        "competence" => "Planificación: Determinar las metas y prioridades de los proyectos, estableciendo las acciones, los plazos y los recursos necesarios. Así como los instrumentos de medición y segumiento.",
        "subCompetence" => "Planificación De Los Recursos",
        "category" => "Supervisión de operaciones y mantenimiento"
      ],
      [
        "competence" => "Planificación: Determinar las metas y prioridades de los proyectos, estableciendo las acciones, los plazos y los recursos necesarios. Así como los instrumentos de medición y segumiento.",
        "subCompetence" => "Priorización",
        "category" => "Supervisión de operaciones y mantenimiento"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Asertividad",
        "category" => "Gerente De Negocio TP"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Respuesta Oportuna",
        "category" => "Gerente De Negocio TP"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Claridad En El Mensaje",
        "category" => "Gerente De Negocio TP"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Escucha",
        "category" => "Gerente De Negocio TP"
      ],
      [
        "competence" => "Dirección de Negocio: Dirige el Plan de Negocio de manera efectiva controlando y evaluando el despliegue e interviniendo con ajustes oportunos.",
        "subCompetence" => "Ajustes Oportunos",
        "category" => "Gerente De Negocio TP"
      ],
      [
        "competence" => "Dirección de Negocio: Dirige el Plan de Negocio de manera efectiva controlando y evaluando el despliegue e interviniendo con ajustes oportunos.",
        "subCompetence" => "Controla",
        "category" => "Gerente De Negocio TP"
      ],
      [
        "competence" => "Dirección de Negocio: Dirige el Plan de Negocio de manera efectiva controlando y evaluando el despliegue e interviniendo con ajustes oportunos.",
        "subCompetence" => "Evalúa",
        "category" => "Gerente De Negocio TP"
      ],
      [
        "competence" => "Dirección de Negocio: Dirige el Plan de Negocio de manera efectiva controlando y evaluando el despliegue e interviniendo con ajustes oportunos.",
        "subCompetence" => "Dirige",
        "category" => "Gerente De Negocio TP"
      ],
      [
        "competence" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "subCompetence" => "Alineamiento",
        "category" => "Gerente De Negocio TP"
      ],
      [
        "competence" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "subCompetence" => "Resolución De Conflictos",
        "category" => "Gerente De Negocio TP"
      ],
      [
        "competence" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "subCompetence" => "Dirección De Equipos",
        "category" => "Gerente De Negocio TP"
      ],
      [
        "competence" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "subCompetence" => "Convicción",
        "category" => "Gerente De Negocio TP"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Cumplimiento De Acuerdos",
        "category" => "Gerente De Negocio TP"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Proactividad",
        "category" => "Gerente De Negocio TP"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Asumo Las Consecuencias",
        "category" => "Gerente De Negocio TP"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Resultados",
        "category" => "Gerente De Negocio TP"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Solución De Problema",
        "category" => "Gerente De Negocio TP"
      ],
      [
        "competence" => "Socio Estratégico: Establece relaciones de alto valor para la organización, viendo las oportunidades en el mercado y tomando decisiones estratégicas de manera íntegra.",
        "subCompetence" => "Integridad",
        "category" => "Gerente De Negocio TP"
      ],
      [
        "competence" => "Socio Estratégico: Establece relaciones de alto valor para la organización, viendo las oportunidades en el mercado y tomando decisiones estratégicas de manera íntegra.",
        "subCompetence" => "Pensamiento Estratégico Del Negocio",
        "category" => "Gerente De Negocio TP"
      ],
      [
        "competence" => "Socio Estratégico: Establece relaciones de alto valor para la organización, viendo las oportunidades en el mercado y tomando decisiones estratégicas de manera íntegra.",
        "subCompetence" => "Construcción De Relaciones",
        "category" => "Gerente De Negocio TP"
      ],
      [
        "competence" => "Socio Estratégico: Establece relaciones de alto valor para la organización, viendo las oportunidades en el mercado y tomando decisiones estratégicas de manera íntegra.",
        "subCompetence" => "Toma De Decisiones",
        "category" => "Gerente De Negocio TP"
      ],
      [
        "competence" => "Socio Estratégico: Establece relaciones de alto valor para la organización, viendo las oportunidades en el mercado y tomando decisiones estratégicas de manera íntegra.",
        "subCompetence" => "Visión De Negocio",
        "category" => "Gerente De Negocio TP"
      ],
      [
        "competence" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "subCompetence" => "Gestión De Recursos",
        "category" => "Asistente Mecánico TP"
      ],
      [
        "competence" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "subCompetence" => "Metodología",
        "category" => "Asistente Mecánico TP"
      ],
      [
        "competence" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "subCompetence" => "Orientación Al Logro",
        "category" => "Asistente Mecánico TP"
      ],
      [
        "competence" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "subCompetence" => "Gestión De Recursos",
        "category" => "Asistente Mecánico TP"
      ],
      [
        "competence" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "subCompetence" => "Metodología",
        "category" => "Asistente Mecánico TP"
      ],
      [
        "competence" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "subCompetence" => "Orientación Al Logro",
        "category" => "Asistente Mecánico TP"
      ],
      [
        "competence" => "Cooperación Operativa: Coopera de manera activa con las necesidades del equipo y unidades con las que interactúa. Respeta los acuerdos generando un espacio colaborativo y de confianza.",
        "subCompetence" => "Cooperación",
        "category" => "Asistente Mecánico TP"
      ],
      [
        "competence" => "Cooperación Operativa: Coopera de manera activa con las necesidades del equipo y unidades con las que interactúa. Respeta los acuerdos generando un espacio colaborativo y de confianza.",
        "subCompetence" => "Orientación A La Meta Común",
        "category" => "Asistente Mecánico TP"
      ],
      [
        "competence" => "Cooperación Operativa: Coopera de manera activa con las necesidades del equipo y unidades con las que interactúa. Respeta los acuerdos generando un espacio colaborativo y de confianza.",
        "subCompetence" => "Relaciones Interpersonales",
        "category" => "Asistente Mecánico TP"
      ],
      [
        "competence" => "Habilidad para reparar: Soluciona las fallas técnicas con rapidez y efectividad, identifica las causas de manera oportuna y verifica el correcto funcionamiento una vez reparado.",
        "subCompetence" => "Control De Calidad En La Reparación",
        "category" => "Asistente Mecánico TP"
      ],
      [
        "competence" => "Habilidad para reparar: Soluciona las fallas técnicas con rapidez y efectividad, identifica las causas de manera oportuna y verifica el correcto funcionamiento una vez reparado.",
        "subCompetence" => "Diagnóstico",
        "category" => "Asistente Mecánico TP"
      ],
      [
        "competence" => "Habilidad para reparar: Soluciona las fallas técnicas con rapidez y efectividad, identifica las causas de manera oportuna y verifica el correcto funcionamiento una vez reparado.",
        "subCompetence" => "Reparación",
        "category" => "Asistente Mecánico TP"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Claridad En El Mensaje",
        "category" => "Analista De Contabilidad TP"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Respuesta Oportuna",
        "category" => "Analista De Contabilidad TP"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Escucha",
        "category" => "Analista De Contabilidad TP"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Asertividad",
        "category" => "Analista De Contabilidad TP"
      ],
      [
        "competence" => "Gestión de Trámites: Garantiza la recepción y entrega de documentos asegurando que cumpla con los requisitos solicitados.",
        "subCompetence" => "Despacho Documentario",
        "category" => "Analista De Contabilidad TP"
      ],
      [
        "competence" => "Gestión de Trámites: Garantiza la recepción y entrega de documentos asegurando que cumpla con los requisitos solicitados.",
        "subCompetence" => "Control Documentario",
        "category" => "Analista De Contabilidad TP"
      ],
      [
        "competence" => "Gestión de Trámites: Garantiza la recepción y entrega de documentos asegurando que cumpla con los requisitos solicitados.",
        "subCompetence" => "Perseverancia En La Entrega",
        "category" => "Analista De Contabilidad TP"
      ],
      [
        "competence" => "Gestión de Trámites: Garantiza la recepción y entrega de documentos asegurando que cumpla con los requisitos solicitados.",
        "subCompetence" => "Construcción De Red De Contactos",
        "category" => "Analista De Contabilidad TP"
      ],
      [
        "competence" => "Perspicacia Contable: Garantizar el correcto registro de los ingresos de la organización identificando posibles riesgos y auditando. Actuar con transparencia durante la auditoría.",
        "subCompetence" => "Control De Información Sensible",
        "category" => "Analista De Contabilidad TP"
      ],
      [
        "competence" => "Perspicacia Contable: Garantizar el correcto registro de los ingresos de la organización identificando posibles riesgos y auditando. Actuar con transparencia durante la auditoría.",
        "subCompetence" => "Análisis De Información Contable",
        "category" => "Analista De Contabilidad TP"
      ],
      [
        "competence" => "Perspicacia Contable: Garantizar el correcto registro de los ingresos de la organización identificando posibles riesgos y auditando. Actuar con transparencia durante la auditoría.",
        "subCompetence" => "Congruente",
        "category" => "Analista De Contabilidad TP"
      ],
      [
        "competence" => "Perspicacia Contable: Garantizar el correcto registro de los ingresos de la organización identificando posibles riesgos y auditando. Actuar con transparencia durante la auditoría.",
        "subCompetence" => "Fraudes",
        "category" => "Analista De Contabilidad TP"
      ],
      [
        "competence" => "Perspicacia Contable: Garantizar el correcto registro de los ingresos de la organización identificando posibles riesgos y auditando. Actuar con transparencia durante la auditoría.",
        "subCompetence" => "Auditorías Oportunas",
        "category" => "Analista De Contabilidad TP"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Orientación Al Registro Detallado",
        "category" => "Analista De Contabilidad TP"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Organización De Información",
        "category" => "Analista De Contabilidad TP"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Cumplimiento De Procedimientos",
        "category" => "Analista De Contabilidad TP"
      ],
      [
        "competence" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "subCompetence" => "Colaboración",
        "category" => "Analista De Contabilidad TP"
      ],
      [
        "competence" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "subCompetence" => "Responsabilidad",
        "category" => "Analista De Contabilidad TP"
      ],
      [
        "competence" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "subCompetence" => "Resuelve Conflictos",
        "category" => "Analista De Contabilidad TP"
      ],
      [
        "competence" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "subCompetence" => "Pasión",
        "category" => "Analista De Contabilidad TP"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Claridad En El Mensaje",
        "category" => "Asistente De Contabilidad TP"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Respuesta Oportuna",
        "category" => "Asistente De Contabilidad TP"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Escucha",
        "category" => "Asistente De Contabilidad TP"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Asertividad",
        "category" => "Asistente De Contabilidad TP"
      ],
      [
        "competence" => "Gestión de Trámites: Garantiza la recepción y entrega de documentos asegurando que cumpla con los requisitos solicitados.",
        "subCompetence" => "Despacho Documentario",
        "category" => "Asistente De Contabilidad TP"
      ],
      [
        "competence" => "Gestión de Trámites: Garantiza la recepción y entrega de documentos asegurando que cumpla con los requisitos solicitados.",
        "subCompetence" => "Control Documentario",
        "category" => "Asistente De Contabilidad TP"
      ],
      [
        "competence" => "Gestión de Trámites: Garantiza la recepción y entrega de documentos asegurando que cumpla con los requisitos solicitados.",
        "subCompetence" => "Perseverancia En La Entrega",
        "category" => "Asistente De Contabilidad TP"
      ],
      [
        "competence" => "Gestión de Trámites: Garantiza la recepción y entrega de documentos asegurando que cumpla con los requisitos solicitados.",
        "subCompetence" => "Construcción De Red De Contactos",
        "category" => "Asistente De Contabilidad TP"
      ],
      [
        "competence" => "Perspicacia Contable: Garantizar el correcto registro de los ingresos de la organización identificando posibles riesgos y auditando. Actuar con transparencia durante la auditoría.",
        "subCompetence" => "Control De Información Sensible",
        "category" => "Asistente De Contabilidad TP"
      ],
      [
        "competence" => "Perspicacia Contable: Garantizar el correcto registro de los ingresos de la organización identificando posibles riesgos y auditando. Actuar con transparencia durante la auditoría.",
        "subCompetence" => "Análisis De Información Contable",
        "category" => "Asistente De Contabilidad TP"
      ],
      [
        "competence" => "Perspicacia Contable: Garantizar el correcto registro de los ingresos de la organización identificando posibles riesgos y auditando. Actuar con transparencia durante la auditoría.",
        "subCompetence" => "Congruente",
        "category" => "Asistente De Contabilidad TP"
      ],
      [
        "competence" => "Perspicacia Contable: Garantizar el correcto registro de los ingresos de la organización identificando posibles riesgos y auditando. Actuar con transparencia durante la auditoría.",
        "subCompetence" => "Fraudes",
        "category" => "Asistente De Contabilidad TP"
      ],
      [
        "competence" => "Perspicacia Contable: Garantizar el correcto registro de los ingresos de la organización identificando posibles riesgos y auditando. Actuar con transparencia durante la auditoría.",
        "subCompetence" => "Auditorías Oportunas",
        "category" => "Asistente De Contabilidad TP"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Orientación Al Registro Detallado",
        "category" => "Asistente De Contabilidad TP"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Organización De Información",
        "category" => "Asistente De Contabilidad TP"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Cumplimiento De Procedimientos",
        "category" => "Asistente De Contabilidad TP"
      ],
      [
        "competence" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "subCompetence" => "Colaboración",
        "category" => "Asistente De Contabilidad TP"
      ],
      [
        "competence" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "subCompetence" => "Responsabilidad",
        "category" => "Asistente De Contabilidad TP"
      ],
      [
        "competence" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "subCompetence" => "Resuelve Conflictos",
        "category" => "Asistente De Contabilidad TP"
      ],
      [
        "competence" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "subCompetence" => "Pasión",
        "category" => "Asistente De Contabilidad TP"
      ],
      [
        "competence" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "subCompetence" => "Traspase De Información En Equipo",
        "category" => "Asistente De Contabilidad TP"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Asertividad",
        "category" => "Asistente De Facturación Y Cobranza"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Claridad En El Mensaje",
        "category" => "Asistente De Facturación Y Cobranza"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Escucha",
        "category" => "Asistente De Facturación Y Cobranza"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Respuesta Oportuna",
        "category" => "Asistente De Facturación Y Cobranza"
      ],
      [
        "competence" => "Control de las cobranzas: Realiza seguimiento efectivo logrando persuadir y comprometer al cliente con el pago del servicio. Identifica proactivamente los desfases de los clientes.",
        "subCompetence" => "Control De Desfases",
        "category" => "Asistente De Facturación Y Cobranza"
      ],
      [
        "competence" => "Control de las cobranzas: Realiza seguimiento efectivo logrando persuadir y comprometer al cliente con el pago del servicio. Identifica proactivamente los desfases de los clientes.",
        "subCompetence" => "Seguimiento Al Cliente",
        "category" => "Asistente De Facturación Y Cobranza"
      ],
      [
        "competence" => "Control de las cobranzas: Realiza seguimiento efectivo logrando persuadir y comprometer al cliente con el pago del servicio. Identifica proactivamente los desfases de los clientes.",
        "subCompetence" => "Influencia Y Persuasión",
        "category" => "Asistente De Facturación Y Cobranza"
      ],
      [
        "competence" => "Control de las cobranzas: Realiza seguimiento efectivo logrando persuadir y comprometer al cliente con el pago del servicio. Identifica proactivamente los desfases de los clientes.",
        "subCompetence" => "Acciones Para Persuadir",
        "category" => "Asistente De Facturación Y Cobranza"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Asumo Las Consecuencias",
        "category" => "Asistente De Facturación Y Cobranza"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Solución De Problema",
        "category" => "Asistente De Facturación Y Cobranza"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Cumplimiento De Acuerdos",
        "category" => "Asistente De Facturación Y Cobranza"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Resultados",
        "category" => "Asistente De Facturación Y Cobranza"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Proactividad",
        "category" => "Asistente De Facturación Y Cobranza"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Organización De Información",
        "category" => "Asistente De Facturación Y Cobranza"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Orientación Al Registro Detallado",
        "category" => "Asistente De Facturación Y Cobranza"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Cumplimiento De Procedimientos",
        "category" => "Asistente De Facturación Y Cobranza"
      ],
      [
        "competence" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "subCompetence" => "Responsabilidad",
        "category" => "Asistente De Facturación Y Cobranza"
      ],
      [
        "competence" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "subCompetence" => "Pasión",
        "category" => "Asistente De Facturación Y Cobranza"
      ],
      [
        "competence" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "subCompetence" => "Resuelve Conflictos",
        "category" => "Asistente De Facturación Y Cobranza"
      ],
      [
        "competence" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "subCompetence" => "Colaboración",
        "category" => "Asistente De Facturación Y Cobranza"
      ],
      [
        "competence" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "subCompetence" => "Traspase De Información En Equipo",
        "category" => "Asistente De Facturación Y Cobranza"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Asertividad",
        "category" => "Asistente De Operaciones"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Claridad En El Mensaje",
        "category" => "Asistente De Operaciones"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Escucha",
        "category" => "Asistente De Operaciones"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Respuesta Oportuna",
        "category" => "Asistente De Operaciones"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Orientación A La Meta Común",
        "category" => "Asistente De Operaciones"
      ],
      [
        "competence" => "Cooperación Operativa: Coopera de manera activa con las necesidades del equipo y unidades con las que interactúa. Respeta los acuerdos generando un espacio colaborativo y de confianza.",
        "subCompetence" => "Cooperación",
        "category" => "Asistente De Operaciones"
      ],
      [
        "competence" => "Cooperación Operativa: Coopera de manera activa con las necesidades del equipo y unidades con las que interactúa. Respeta los acuerdos generando un espacio colaborativo y de confianza.",
        "subCompetence" => "Relaciones Interpersonales",
        "category" => "Asistente De Operaciones"
      ],
      [
        "competence" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "subCompetence" => "Gestión De Recursos",
        "category" => "Asistente De Operaciones"
      ],
      [
        "competence" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "subCompetence" => "Metodología",
        "category" => "Asistente De Operaciones"
      ],
      [
        "competence" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "subCompetence" => "Orientación Al Logro",
        "category" => "Asistente De Operaciones"
      ],
      [
        "competence" => "Organización y Control: Garantizar el uso de recursos a través de una adecuada organización, clasificación y control.",
        "subCompetence" => "Control De Recursos",
        "category" => "Asistente De Operaciones"
      ],
      [
        "competence" => "Organización y Control: Garantizar el uso de recursos a través de una adecuada organización, clasificación y control.",
        "subCompetence" => "Organización De Recursos Internos",
        "category" => "Asistente De Operaciones"
      ],
      [
        "competence" => "Organización y Control: Garantizar el uso de recursos a través de una adecuada organización, clasificación y control.",
        "subCompetence" => "Protección De Recursos De La Organización",
        "category" => "Asistente De Operaciones"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Claridad En El Mensaje",
        "category" => "Asistente De Seguimiento Y Monitoreo"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Asertividad",
        "category" => "Asistente De Seguimiento Y Monitoreo"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Escucha",
        "category" => "Asistente De Seguimiento Y Monitoreo"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Respuesta Oportuna",
        "category" => "Asistente De Seguimiento Y Monitoreo"
      ],
      [
        "competence" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "subCompetence" => "Gestión De Recursos",
        "category" => "Asistente De Seguimiento Y Monitoreo"
      ],
      [
        "competence" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "subCompetence" => "Metodología",
        "category" => "Asistente De Seguimiento Y Monitoreo"
      ],
      [
        "competence" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "subCompetence" => "Orientación Al Logro",
        "category" => "Asistente De Seguimiento Y Monitoreo"
      ],
      [
        "competence" => "Gestión de normas y procedimientos: Se preocupa y promueve respeto de las normas y procedimientos. Vigila el cumplimiento de las normas e interviene para corregir faltas.",
        "subCompetence" => "Revisión Y Seguimiento",
        "category" => "Asistente De Seguimiento Y Monitoreo"
      ],
      [
        "competence" => "Gestión de normas y procedimientos: Se preocupa y promueve respeto de las normas y procedimientos. Vigila el cumplimiento de las normas e interviene para corregir faltas.",
        "subCompetence" => "Gestión De Consecuencias V1",
        "category" => "Asistente De Seguimiento Y Monitoreo"
      ],
      [
        "competence" => "Gestión de normas y procedimientos: Se preocupa y promueve respeto de las normas y procedimientos. Vigila el cumplimiento de las normas e interviene para corregir faltas.",
        "subCompetence" => "Claridad En La Orientación",
        "category" => "Asistente De Seguimiento Y Monitoreo"
      ],
      [
        "competence" => "Registro y Manejo de Información - Versión 2 AP: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Orientación Al Detalle",
        "category" => "Asistente De Seguimiento Y Monitoreo"
      ],
      [
        "competence" => "Registro y Manejo de Información - Versión 2 AP: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Organización",
        "category" => "Asistente De Seguimiento Y Monitoreo"
      ],
      [
        "competence" => "Registro y Manejo de Información - Versión 2 AP: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Cumplimiento De Procesos Operativos",
        "category" => "Asistente De Seguimiento Y Monitoreo"
      ],
      [
        "competence" => "Administración de Recursos Tecnológicos: Garantiza el adecuado funcionamiento de los recursos tecnológicos , previene cualquier inconveniente y toma acción ante las dificultades.",
        "subCompetence" => "Control De Licencias Y Funcionamiento",
        "category" => "Asistente En Proyecto Tics"
      ],
      [
        "competence" => "Administración de Recursos Tecnológicos: Garantiza el adecuado funcionamiento de los recursos tecnológicos , previene cualquier inconveniente y toma acción ante las dificultades.",
        "subCompetence" => "Control Y Organización De Recursos Ti",
        "category" => "Asistente En Proyecto Tics"
      ],
      [
        "competence" => "Administración de Recursos Tecnológicos: Garantiza el adecuado funcionamiento de los recursos tecnológicos , previene cualquier inconveniente y toma acción ante las dificultades.",
        "subCompetence" => "Intervención De Problemas",
        "category" => "Asistente En Proyecto Tics"
      ],
      [
        "competence" => "Administración de Recursos Tecnológicos: Garantiza el adecuado funcionamiento de los recursos tecnológicos , previene cualquier inconveniente y toma acción ante las dificultades.",
        "subCompetence" => "Prevención De Recursos",
        "category" => "Asistente En Proyecto Tics"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Asertividad",
        "category" => "Asistente En Proyecto Tics"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Claridad En El Mensaje",
        "category" => "Asistente En Proyecto Tics"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Escucha",
        "category" => "Asistente En Proyecto Tics"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Respuesta Oportuna",
        "category" => "Asistente En Proyecto Tics"
      ],
      [
        "competence" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "subCompetence" => "Gestión De Recursos",
        "category" => "Asistente En Proyecto Tics"
      ],
      [
        "competence" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "subCompetence" => "Metodología",
        "category" => "Asistente En Proyecto Tics"
      ],
      [
        "competence" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "subCompetence" => "Orientación Al Logro",
        "category" => "Asistente En Proyecto Tics"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Claridad En El Mensaje",
        "category" => "Auxiliar De Operaciones Lima"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Asertividad",
        "category" => "Auxiliar De Operaciones Lima"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Escucha",
        "category" => "Auxiliar De Operaciones Lima"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Respuesta Oportuna",
        "category" => "Auxiliar De Operaciones Lima"
      ],
      [
        "competence" => "Cooperación Operativa: Coopera de manera activa con las necesidades del equipo y unidades con las que interactúa. Respeta los acuerdos generando un espacio colaborativo y de confianza.",
        "subCompetence" => "Cooperación",
        "category" => "Auxiliar De Operaciones Lima"
      ],
      [
        "competence" => "Cooperación Operativa: Coopera de manera activa con las necesidades del equipo y unidades con las que interactúa. Respeta los acuerdos generando un espacio colaborativo y de confianza.",
        "subCompetence" => "Orientación A La Meta Común",
        "category" => "Auxiliar De Operaciones Lima"
      ],
      [
        "competence" => "Cooperación Operativa: Coopera de manera activa con las necesidades del equipo y unidades con las que interactúa. Respeta los acuerdos generando un espacio colaborativo y de confianza.",
        "subCompetence" => "Relaciones Interpersonales",
        "category" => "Auxiliar De Operaciones Lima"
      ],
      [
        "competence" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "subCompetence" => "Gestión De Recursos",
        "category" => "Auxiliar De Operaciones Lima"
      ],
      [
        "competence" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "subCompetence" => "Metodología",
        "category" => "Auxiliar De Operaciones Lima"
      ],
      [
        "competence" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "subCompetence" => "Orientación Al Logro",
        "category" => "Auxiliar De Operaciones Lima"
      ],
      [
        "competence" => "Organización y Control: Garantizar el uso de recursos a través de una adecuada organización, clasificación y control.",
        "subCompetence" => "Control De Recursos",
        "category" => "Auxiliar De Operaciones Lima"
      ],
      [
        "competence" => "Organización y Control: Garantizar el uso de recursos a través de una adecuada organización, clasificación y control.",
        "subCompetence" => "Organización De Recursos Internos",
        "category" => "Auxiliar De Operaciones Lima"
      ],
      [
        "competence" => "Organización y Control: Garantizar el uso de recursos a través de una adecuada organización, clasificación y control.",
        "subCompetence" => "Protección De Recursos De La Organización",
        "category" => "Auxiliar De Operaciones Lima"
      ],
      [
        "competence" => "Calidad en la entrega del Producto: Velar por la calidad y recursos que utiliza en la entrega del producto al cliente a fin de generar una adecuada satisfacción.",
        "subCompetence" => "Protección Del Vehículo",
        "category" => "Conductor De Tracto Camion"
      ],
      [
        "competence" => "Gestión de normas y procedimientos: Se preocupa y promueve respeto de las normas y procedimientos. Vigila el cumplimiento de las normas e interviene para corregir faltas.",
        "subCompetence" => "Gestión De Consecuencias V1",
        "category" => "Conductor De Tracto Camion"
      ],
      [
        "competence" => "Gestión de normas y procedimientos: Se preocupa y promueve respeto de las normas y procedimientos. Vigila el cumplimiento de las normas e interviene para corregir faltas.",
        "subCompetence" => "Respeto A La Norma",
        "category" => "Conductor De Tracto Camion"
      ],
      [
        "competence" => "Organización y Control de Mercadería: Garantizar la cantidad de recursos que necesita la organización a través de una adecuada supervisión, organización, clasificación y control.",
        "subCompetence" => "Protección De Recursos",
        "category" => "Conductor De Tracto Camion"
      ],
      [
        "competence" => "Organización y Control de Mercadería: Garantizar la cantidad de recursos que necesita la organización a través de una adecuada supervisión, organización, clasificación y control.",
        "subCompetence" => "Reclamos De Ingreso De Recursos",
        "category" => "Conductor De Tracto Camion"
      ],
      [
        "competence" => "Registro y Manejo de Información - Versión 2 AP: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Cumplimiento De Procesos Operativos",
        "category" => "Conductor De Tracto Camion"
      ],
      [
        "competence" => "Registro y Manejo de Información - Versión 2 AP: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Organización",
        "category" => "Conductor De Tracto Camion"
      ],
      [
        "competence" => "Control del Mantenimiento: Soluciona los problemas de manera efectiva. Evalúa el rendimiento de mantenimiento e identifica oportunidades de reducción de costos.",
        "subCompetence" => "Control De Costos",
        "category" => "Coordinador De Mantenimiento"
      ],
      [
        "competence" => "Control del Mantenimiento: Soluciona los problemas de manera efectiva. Evalúa el rendimiento de mantenimiento e identifica oportunidades de reducción de costos.",
        "subCompetence" => "Evaluación De Indicadores",
        "category" => "Coordinador De Mantenimiento"
      ],
      [
        "competence" => "Control del Mantenimiento: Soluciona los problemas de manera efectiva. Evalúa el rendimiento de mantenimiento e identifica oportunidades de reducción de costos.",
        "subCompetence" => "Solución De Problemas",
        "category" => "Coordinador De Mantenimiento"
      ],
      [
        "competence" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "subCompetence" => "Control",
        "category" => "Coordinador De Mantenimiento"
      ],
      [
        "competence" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "subCompetence" => "Gestión De Consecuencias",
        "category" => "Coordinador De Mantenimiento"
      ],
      [
        "competence" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "subCompetence" => "Retroalimentación",
        "category" => "Coordinador De Mantenimiento"
      ],
      [
        "competence" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "subCompetence" => "Seguimiento",
        "category" => "Coordinador De Mantenimiento"
      ],
      [
        "competence" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "subCompetence" => "Supervisión",
        "category" => "Coordinador De Mantenimiento"
      ],
      [
        "competence" => "Gestión de normas y procedimientos: Se preocupa y promueve respeto de las normas y procedimientos. Vigila el cumplimiento de las normas e interviene para corregir faltas.",
        "subCompetence" => "Claridad En La Orientación",
        "category" => "Coordinador De Mantenimiento"
      ],
      [
        "competence" => "Gestión de normas y procedimientos: Se preocupa y promueve respeto de las normas y procedimientos. Vigila el cumplimiento de las normas e interviene para corregir faltas.",
        "subCompetence" => "Gestión De Consecuencias V1",
        "category" => "Coordinador De Mantenimiento"
      ],
      [
        "competence" => "Gestión de normas y procedimientos: Se preocupa y promueve respeto de las normas y procedimientos. Vigila el cumplimiento de las normas e interviene para corregir faltas.",
        "subCompetence" => "Respeto A La Norma",
        "category" => "Coordinador De Mantenimiento"
      ],
      [
        "competence" => "Gestión de normas y procedimientos: Se preocupa y promueve respeto de las normas y procedimientos. Vigila el cumplimiento de las normas e interviene para corregir faltas.",
        "subCompetence" => "Revisión Y Seguimiento",
        "category" => "Coordinador De Mantenimiento"
      ],
      [
        "competence" => "Supervisión Efectiva: Supervisar el desempeño de los colaboradores brindando retrolimentación a partir del mismo y gestionar los resultados obtenidos.",
        "subCompetence" => "Gestión De Consecuencias Del Desempeño",
        "category" => "Coordinador De Mantenimiento"
      ],
      [
        "competence" => "Supervisión Efectiva: Supervisar el desempeño de los colaboradores brindando retrolimentación a partir del mismo y gestionar los resultados obtenidos.",
        "subCompetence" => "Retroalimentación Sobre El Proceso",
        "category" => "Coordinador De Mantenimiento"
      ],
      [
        "competence" => "Supervisión Efectiva: Supervisar el desempeño de los colaboradores brindando retrolimentación a partir del mismo y gestionar los resultados obtenidos.",
        "subCompetence" => "Seguimiento Efectivo",
        "category" => "Coordinador De Mantenimiento"
      ],
      [
        "competence" => "Cooperación Operativa: Coopera de manera activa con las necesidades del equipo y unidades con las que interactúa. Respeta los acuerdos generando un espacio colaborativo y de confianza.",
        "subCompetence" => "Cooperación",
        "category" => "Maestro Mecanico"
      ],
      [
        "competence" => "Cooperación Operativa: Coopera de manera activa con las necesidades del equipo y unidades con las que interactúa. Respeta los acuerdos generando un espacio colaborativo y de confianza.",
        "subCompetence" => "Orientación A La Meta Común",
        "category" => "Maestro Mecanico"
      ],
      [
        "competence" => "Cooperación Operativa: Coopera de manera activa con las necesidades del equipo y unidades con las que interactúa. Respeta los acuerdos generando un espacio colaborativo y de confianza.",
        "subCompetence" => "Relaciones Interpersonales",
        "category" => "Maestro Mecanico"
      ],
      [
        "competence" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "subCompetence" => "Gestión De Recursos",
        "category" => "Maestro Mecanico"
      ],
      [
        "competence" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "subCompetence" => "Metodología",
        "category" => "Maestro Mecanico"
      ],
      [
        "competence" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "subCompetence" => "Orientación Al Logro",
        "category" => "Maestro Mecanico"
      ],
      [
        "competence" => "Habilidad para reparar: Soluciona las fallas técnicas con rapidez y efectividad, identifica las causas de manera oportuna y verifica el correcto funcionamiento una vez reparado.",
        "subCompetence" => "Control De Calidad En La Reparación",
        "category" => "Maestro Mecanico"
      ],
      [
        "competence" => "Habilidad para reparar: Soluciona las fallas técnicas con rapidez y efectividad, identifica las causas de manera oportuna y verifica el correcto funcionamiento una vez reparado.",
        "subCompetence" => "Diagnóstico",
        "category" => "Maestro Mecanico"
      ],
      [
        "competence" => "Habilidad para reparar: Soluciona las fallas técnicas con rapidez y efectividad, identifica las causas de manera oportuna y verifica el correcto funcionamiento una vez reparado.",
        "subCompetence" => "Reparación",
        "category" => "Maestro Mecanico"
      ],
      [
        "competence" => "Organización y Control: Garantizar el uso de recursos a través de una adecuada organización, clasificación y control.",
        "subCompetence" => "Control De Recursos",
        "category" => "Maestro Mecanico"
      ],
      [
        "competence" => "Organización y Control: Garantizar el uso de recursos a través de una adecuada organización, clasificación y control.",
        "subCompetence" => "Organización De Recursos Internos",
        "category" => "Maestro Mecanico"
      ],
      [
        "competence" => "Organización y Control: Garantizar el uso de recursos a través de una adecuada organización, clasificación y control.",
        "subCompetence" => "Protección De Recursos De La Organización",
        "category" => "Maestro Mecanico"
      ],
      [
        "competence" => "Supervisión Efectiva: Supervisar el desempeño de los colaboradores brindando retrolimentación a partir del mismo y gestionar los resultados obtenidos.",
        "subCompetence" => "Gestión De Consecuencias Del Desempeño",
        "category" => "Maestro Mecanico"
      ],
      [
        "competence" => "Supervisión Efectiva: Supervisar el desempeño de los colaboradores brindando retrolimentación a partir del mismo y gestionar los resultados obtenidos.",
        "subCompetence" => "Retroalimentación Sobre El Proceso",
        "category" => "Maestro Mecanico"
      ],
      [
        "competence" => "Supervisión Efectiva: Supervisar el desempeño de los colaboradores brindando retrolimentación a partir del mismo y gestionar los resultados obtenidos.",
        "subCompetence" => "Seguimiento Efectivo",
        "category" => "Maestro Mecanico"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Claridad En El Mensaje",
        "category" => "Supervisor Comercial"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Asertividad",
        "category" => "Supervisor Comercial"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Escucha",
        "category" => "Supervisor Comercial"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Respuesta Oportuna",
        "category" => "Supervisor Comercial"
      ],
      [
        "competence" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "subCompetence" => "Convicción",
        "category" => "Supervisor Comercial"
      ],
      [
        "competence" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "subCompetence" => "Dirección De Equipos",
        "category" => "Supervisor Comercial"
      ],
      [
        "competence" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "subCompetence" => "Resolución De Conflictos",
        "category" => "Supervisor Comercial"
      ],
      [
        "competence" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "subCompetence" => "Alineamiento",
        "category" => "Supervisor Comercial"
      ],
      [
        "competence" => "Negociación: Capacidad para dirigir o controlar una discusión utilizando técnicas ganar-ganar planificando alternativas para negociar los mejores acuerdos.",
        "subCompetence" => "Comunicación Asertiva",
        "category" => "Supervisor Comercial"
      ],
      [
        "competence" => "Negociación: Capacidad para dirigir o controlar una discusión utilizando técnicas ganar-ganar planificando alternativas para negociar los mejores acuerdos.",
        "subCompetence" => "Cierre De Acuerdos",
        "category" => "Supervisor Comercial"
      ],
      [
        "competence" => "Negociación: Capacidad para dirigir o controlar una discusión utilizando técnicas ganar-ganar planificando alternativas para negociar los mejores acuerdos.",
        "subCompetence" => "Planificación De La Estrategia",
        "category" => "Supervisor Comercial"
      ],
      [
        "competence" => "Negociación: Capacidad para dirigir o controlar una discusión utilizando técnicas ganar-ganar planificando alternativas para negociar los mejores acuerdos.",
        "subCompetence" => "Recolección De Información",
        "category" => "Supervisor Comercial"
      ],
      [
        "competence" => "Planificación: Determinar las metas y prioridades de los proyectos, estableciendo las acciones, los plazos y los recursos necesarios. Así como los instrumentos de medición y segumiento.",
        "subCompetence" => "Creación De Planes",
        "category" => "Supervisor Comercial"
      ],
      [
        "competence" => "Planificación: Determinar las metas y prioridades de los proyectos, estableciendo las acciones, los plazos y los recursos necesarios. Así como los instrumentos de medición y segumiento.",
        "subCompetence" => "Definir Metas e indicadores",
        "category" => "Supervisor Comercial"
      ],
      [
        "competence" => "Planificación: Determinar las metas y prioridades de los proyectos, estableciendo las acciones, los plazos y los recursos necesarios. Así como los instrumentos de medición y segumiento.",
        "subCompetence" => "Planificación De Los Recursos",
        "category" => "Supervisor Comercial"
      ],
      [
        "competence" => "Planificación: Determinar las metas y prioridades de los proyectos, estableciendo las acciones, los plazos y los recursos necesarios. Así como los instrumentos de medición y segumiento.",
        "subCompetence" => "Priorización",
        "category" => "Supervisor Comercial"
      ],
      [
        "competence" => "Supervisión Efectiva: Supervisar el desempeño de los colaboradores brindando retrolimentación a partir del mismo y gestionar los resultados obtenidos.",
        "subCompetence" => "Gestión De Consecuencias Del Desempeño",
        "category" => "Supervisor Comercial"
      ],
      [
        "competence" => "Supervisión Efectiva: Supervisar el desempeño de los colaboradores brindando retrolimentación a partir del mismo y gestionar los resultados obtenidos.",
        "subCompetence" => "Retroalimentación Sobre El Proceso",
        "category" => "Supervisor Comercial"
      ],
      [
        "competence" => "Supervisión Efectiva: Supervisar el desempeño de los colaboradores brindando retrolimentación a partir del mismo y gestionar los resultados obtenidos.",
        "subCompetence" => "Seguimiento Efectivo",
        "category" => "Supervisor Comercial"
      ],
      [
        "competence" => "Cooperación Operativa: Coopera de manera activa con las necesidades del equipo y unidades con las que interactúa. Respeta los acuerdos generando un espacio colaborativo y de confianza.",
        "subCompetence" => "Cooperación",
        "category" => "Tecnico Soldador"
      ],
      [
        "competence" => "Cooperación Operativa: Coopera de manera activa con las necesidades del equipo y unidades con las que interactúa. Respeta los acuerdos generando un espacio colaborativo y de confianza.",
        "subCompetence" => "Orientación A La Meta Común",
        "category" => "Tecnico Soldador"
      ],
      [
        "competence" => "Cooperación Operativa: Coopera de manera activa con las necesidades del equipo y unidades con las que interactúa. Respeta los acuerdos generando un espacio colaborativo y de confianza.",
        "subCompetence" => "Relaciones Interpersonales",
        "category" => "Tecnico Soldador"
      ],
      [
        "competence" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "subCompetence" => "Gestión De Recursos",
        "category" => "Tecnico Soldador"
      ],
      [
        "competence" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "subCompetence" => "Metodología",
        "category" => "Tecnico Soldador"
      ],
      [
        "competence" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "subCompetence" => "Orientación Al Logro",
        "category" => "Tecnico Soldador"
      ],
      [
        "competence" => "Habilidad para reparar: Soluciona las fallas técnicas con rapidez y efectividad, identifica las causas de manera oportuna y verifica el correcto funcionamiento una vez reparado.",
        "subCompetence" => "Control De Calidad En La Reparación",
        "category" => "Tecnico Soldador"
      ],
      [
        "competence" => "Habilidad para reparar: Soluciona las fallas técnicas con rapidez y efectividad, identifica las causas de manera oportuna y verifica el correcto funcionamiento una vez reparado.",
        "subCompetence" => "Diagnóstico",
        "category" => "Tecnico Soldador"
      ],
      [
        "competence" => "Habilidad para reparar: Soluciona las fallas técnicas con rapidez y efectividad, identifica las causas de manera oportuna y verifica el correcto funcionamiento una vez reparado.",
        "subCompetence" => "Reparación",
        "category" => "Tecnico Soldador"
      ],
      [
        "competence" => "Organización y Control: Garantizar el uso de recursos a través de una adecuada organización, clasificación y control.",
        "subCompetence" => "Control De Recursos",
        "category" => "Tecnico Soldador"
      ],
      [
        "competence" => "Organización y Control: Garantizar el uso de recursos a través de una adecuada organización, clasificación y control.",
        "subCompetence" => "Organización De Recursos Internos",
        "category" => "Tecnico Soldador"
      ],
      [
        "competence" => "Organización y Control: Garantizar el uso de recursos a través de una adecuada organización, clasificación y control.",
        "subCompetence" => "Protección De Recursos De La Organización",
        "category" => "Tecnico Soldador"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Cumplimiento De Procedimientos",
        "category" => "Tecnico Soldador"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Organización De Información",
        "category" => "Tecnico Soldador"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Orientación Al Registro Detallado",
        "category" => "Tecnico Soldador"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Asertividad",
        "category" => "Tecnico Electricista"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Claridad En El Mensaje",
        "category" => "Tecnico Electricista"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Escucha",
        "category" => "Tecnico Electricista"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Respuesta Oportuna",
        "category" => "Tecnico Electricista"
      ],
      [
        "competence" => "Cooperación Operativa: Coopera de manera activa con las necesidades del equipo y unidades con las que interactúa. Respeta los acuerdos generando un espacio colaborativo y de confianza.",
        "subCompetence" => "Cooperación",
        "category" => "Tecnico Electricista"
      ],
      [
        "competence" => "Cooperación Operativa: Coopera de manera activa con las necesidades del equipo y unidades con las que interactúa. Respeta los acuerdos generando un espacio colaborativo y de confianza.",
        "subCompetence" => "Orientación A La Meta Común",
        "category" => "Tecnico Electricista"
      ],
      [
        "competence" => "Cooperación Operativa: Coopera de manera activa con las necesidades del equipo y unidades con las que interactúa. Respeta los acuerdos generando un espacio colaborativo y de confianza.",
        "subCompetence" => "Relaciones Interpersonales",
        "category" => "Tecnico Electricista"
      ],
      [
        "competence" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "subCompetence" => "Gestión De Recursos",
        "category" => "Tecnico Electricista"
      ],
      [
        "competence" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "subCompetence" => "Metodología",
        "category" => "Tecnico Electricista"
      ],
      [
        "competence" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "subCompetence" => "Orientación Al Logro",
        "category" => "Tecnico Electricista"
      ],
      [
        "competence" => "Habilidad para reparar: Soluciona las fallas técnicas con rapidez y efectividad, identifica las causas de manera oportuna y verifica el correcto funcionamiento una vez reparado.",
        "subCompetence" => "Control De Calidad En La Reparación",
        "category" => "Tecnico Electricista"
      ],
      [
        "competence" => "Habilidad para reparar: Soluciona las fallas técnicas con rapidez y efectividad, identifica las causas de manera oportuna y verifica el correcto funcionamiento una vez reparado.",
        "subCompetence" => "Diagnóstico",
        "category" => "Tecnico Electricista"
      ],
      [
        "competence" => "Habilidad para reparar: Soluciona las fallas técnicas con rapidez y efectividad, identifica las causas de manera oportuna y verifica el correcto funcionamiento una vez reparado.",
        "subCompetence" => "Reparación",
        "category" => "Tecnico Electricista"
      ],
      [
        "competence" => "Organización y Control: Garantizar el uso de recursos a través de una adecuada organización, clasificación y control.",
        "subCompetence" => "Control De Recursos",
        "category" => "Tecnico Electricista"
      ],
      [
        "competence" => "Organización y Control: Garantizar el uso de recursos a través de una adecuada organización, clasificación y control.",
        "subCompetence" => "Organización De Recursos Internos",
        "category" => "Tecnico Electricista"
      ],
      [
        "competence" => "Organización y Control: Garantizar el uso de recursos a través de una adecuada organización, clasificación y control.",
        "subCompetence" => "Protección De Recursos De La Organización",
        "category" => "Tecnico Electricista"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Asertividad",
        "category" => "Partner De Gestión Humana"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Claridad En El Mensaje",
        "category" => "Partner De Gestión Humana"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Escucha",
        "category" => "Partner De Gestión Humana"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Respuesta Oportuna",
        "category" => "Partner De Gestión Humana"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Cumplimiento De Procedimientos",
        "category" => "Partner De Gestión Humana"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Organización De Información",
        "category" => "Partner De Gestión Humana"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Orientación Al Registro Detallado",
        "category" => "Partner De Gestión Humana"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Amabilidad Y Cercanía",
        "category" => "Partner De Gestión Humana"
      ],
      [
        "competence" => "Servicio al colaborador: Resuelve las consultas y solicitudes de los colaboradores con empatía y amabilidad.",
        "subCompetence" => "Amabilidad Y Cercanía Con El Colaborador",
        "category" => "Partner De Gestión Humana"
      ],
      [
        "competence" => "Servicio al colaborador: Resuelve las consultas y solicitudes de los colaboradores con empatía y amabilidad.",
        "subCompetence" => "Atención Efectiva A Consultas De Colaboradores",
        "category" => "Partner De Gestión Humana"
      ],
      [
        "competence" => "Servicio al colaborador: Resuelve las consultas y solicitudes de los colaboradores con empatía y amabilidad.",
        "subCompetence" => "Atención Oportuna",
        "category" => "Partner De Gestión Humana"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Asertividad",
        "category" => "Gestor Comercial"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Claridad En El Mensaje",
        "category" => "Gestor Comercial"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Escucha",
        "category" => "Gestor Comercial"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Respuesta Oportuna",
        "category" => "Gestor Comercial"
      ],
      [
        "competence" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "subCompetence" => "Gestión De Recursos",
        "category" => "Gestor Comercial"
      ],
      [
        "competence" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "subCompetence" => "Metodología",
        "category" => "Gestor Comercial"
      ],
      [
        "competence" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "subCompetence" => "Orientación Al Logro",
        "category" => "Gestor Comercial"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Amabilidad Y Cercanía",
        "category" => "Gestor Comercial"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Atención Efectiva A Consultas",
        "category" => "Gestor Comercial"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Atención Presencial Oportuna",
        "category" => "Gestor Comercial"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Seguimiento A La Atención",
        "category" => "Gestor Comercial"
      ],
      [
        "competence" => "Supervisión Efectiva: Supervisar el desempeño de los colaboradores brindando retrolimentación a partir del mismo y gestionar los resultados obtenidos.",
        "subCompetence" => "Gestión De Consecuencias Del Desempeño",
        "category" => "Gestor Comercial"
      ],
      [
        "competence" => "Supervisión Efectiva: Supervisar el desempeño de los colaboradores brindando retrolimentación a partir del mismo y gestionar los resultados obtenidos.",
        "subCompetence" => "Retroalimentación Sobre El Proceso",
        "category" => "Gestor Comercial"
      ],
      [
        "competence" => "Supervisión Efectiva: Supervisar el desempeño de los colaboradores brindando retrolimentación a partir del mismo y gestionar los resultados obtenidos.",
        "subCompetence" => "Seguimiento Efectivo",
        "category" => "Gestor Comercial"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Asertividad",
        "category" => "Gerente General"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Respuesta Oportuna",
        "category" => "Gerente General"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Claridad En El Mensaje",
        "category" => "Gerente General"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Escucha",
        "category" => "Gerente General"
      ],
      [
        "competence" => "Dirección de Negocio: Dirige el Plan de Negocio de manera efectiva controlando y evaluando el despliegue e interviniendo con ajustes oportunos.",
        "subCompetence" => "Ajustes Oportunos",
        "category" => "Gerente General"
      ],
      [
        "competence" => "Dirección de Negocio: Dirige el Plan de Negocio de manera efectiva controlando y evaluando el despliegue e interviniendo con ajustes oportunos.",
        "subCompetence" => "Controla",
        "category" => "Gerente General"
      ],
      [
        "competence" => "Dirección de Negocio: Dirige el Plan de Negocio de manera efectiva controlando y evaluando el despliegue e interviniendo con ajustes oportunos.",
        "subCompetence" => "Evalúa",
        "category" => "Gerente General"
      ],
      [
        "competence" => "Dirección de Negocio: Dirige el Plan de Negocio de manera efectiva controlando y evaluando el despliegue e interviniendo con ajustes oportunos.",
        "subCompetence" => "Dirige",
        "category" => "Gerente General"
      ],
      [
        "competence" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "subCompetence" => "Alineamiento",
        "category" => "Gerente General"
      ],
      [
        "competence" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "subCompetence" => "Resolución De Conflictos",
        "category" => "Gerente General"
      ],
      [
        "competence" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "subCompetence" => "Dirección De Equipos",
        "category" => "Gerente General"
      ],
      [
        "competence" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "subCompetence" => "Convicción",
        "category" => "Gerente General"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Cumplimiento De Acuerdos",
        "category" => "Gerente General"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Proactividad",
        "category" => "Gerente General"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Asumo Las Consecuencias",
        "category" => "Gerente General"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Resultados",
        "category" => "Gerente General"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Solución De Problema",
        "category" => "Gerente General"
      ],
      [
        "competence" => "Socio Estratégico: Establece relaciones de alto valor para la organización, viendo las oportunidades en el mercado y tomando decisiones estratégicas de manera íntegra.",
        "subCompetence" => "Integridad",
        "category" => "Gerente General"
      ],
      [
        "competence" => "Socio Estratégico: Establece relaciones de alto valor para la organización, viendo las oportunidades en el mercado y tomando decisiones estratégicas de manera íntegra.",
        "subCompetence" => "Pensamiento Estratégico Del Negocio",
        "category" => "Gerente General"
      ],
      [
        "competence" => "Socio Estratégico: Establece relaciones de alto valor para la organización, viendo las oportunidades en el mercado y tomando decisiones estratégicas de manera íntegra.",
        "subCompetence" => "Construcción De Relaciones",
        "category" => "Gerente General"
      ],
      [
        "competence" => "Socio Estratégico: Establece relaciones de alto valor para la organización, viendo las oportunidades en el mercado y tomando decisiones estratégicas de manera íntegra.",
        "subCompetence" => "Toma De Decisiones",
        "category" => "Gerente General"
      ],
      [
        "competence" => "Socio Estratégico: Establece relaciones de alto valor para la organización, viendo las oportunidades en el mercado y tomando decisiones estratégicas de manera íntegra.",
        "subCompetence" => "Visión De Negocio",
        "category" => "Gerente General"
      ],
      [
        "competence" => "Construcción de relaciones de negocio: Comprende el funcionamiento de los negocios, analiza los cambios del entorno para aprovechar las oportunidades del mercado.",
        "subCompetence" => "Anticipación",
        "category" => "Jefe De Legal"
      ],
      [
        "competence" => "Construcción de relaciones de negocio: Comprende el funcionamiento de los negocios, analiza los cambios del entorno para aprovechar las oportunidades del mercado.",
        "subCompetence" => "Análisis De Negocios",
        "category" => "Jefe De Legal"
      ],
      [
        "competence" => "Construcción de relaciones de negocio: Comprende el funcionamiento de los negocios, analiza los cambios del entorno para aprovechar las oportunidades del mercado.",
        "subCompetence" => "Pensamiento Estratégico",
        "category" => "Jefe De Legal"
      ],
      [
        "competence" => "Construcción de relaciones de negocio: Comprende el funcionamiento de los negocios, analiza los cambios del entorno para aprovechar las oportunidades del mercado.",
        "subCompetence" => "Construcción De Relación",
        "category" => "Jefe De Legal"
      ],
      [
        "competence" => "Habilidad analítica: Capacidad de razonamiento y organización cognitivamente del trabajo para realizar un análisis lógico identificando problemas, información y datos significativos.",
        "subCompetence" => "Tratamiento De La Información",
        "category" => "Jefe De Legal"
      ],
      [
        "competence" => "Habilidad analítica: Capacidad de razonamiento y organización cognitivamente del trabajo para realizar un análisis lógico identificando problemas, información y datos significativos.",
        "subCompetence" => "Análisis Lógico",
        "category" => "Jefe De Legal"
      ],
      [
        "competence" => "Habilidad analítica: Capacidad de razonamiento y organización cognitivamente del trabajo para realizar un análisis lógico identificando problemas, información y datos significativos.",
        "subCompetence" => "Identificación De Problemas",
        "category" => "Jefe De Legal"
      ],
      [
        "competence" => "Habilidad analítica: Capacidad de razonamiento y organización cognitivamente del trabajo para realizar un análisis lógico identificando problemas, información y datos significativos.",
        "subCompetence" => "Capacidad De Comprensión",
        "category" => "Jefe De Legal"
      ],
      [
        "competence" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "subCompetence" => "Convicción",
        "category" => "Jefe De Legal"
      ],
      [
        "competence" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "subCompetence" => "Resolución De Conflictos",
        "category" => "Jefe De Legal"
      ],
      [
        "competence" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "subCompetence" => "Dirección De Equipos",
        "category" => "Jefe De Legal"
      ],
      [
        "competence" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "subCompetence" => "Alineamiento",
        "category" => "Jefe De Legal"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Cumplimiento De Acuerdos",
        "category" => "Jefe De Legal"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Asumo Las Consecuencias",
        "category" => "Jefe De Legal"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Proactividad",
        "category" => "Jefe De Legal"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Resultados",
        "category" => "Jefe De Legal"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Solución De Problema",
        "category" => "Jefe De Legal"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Amabilidad Y Cercanía",
        "category" => "Jefe De Legal"
      ],
      [
        "competence" => "Servicio al colaborador: Resuelve las consultas y solicitudes de los colaboradores con empatía y amabilidad.",
        "subCompetence" => "Atención Oportuna",
        "category" => "Jefe De Legal"
      ],
      [
        "competence" => "Servicio al colaborador: Resuelve las consultas y solicitudes de los colaboradores con empatía y amabilidad.",
        "subCompetence" => "Empatía",
        "category" => "Jefe De Legal"
      ],
      [
        "competence" => "Servicio al colaborador: Resuelve las consultas y solicitudes de los colaboradores con empatía y amabilidad.",
        "subCompetence" => "Atención Efectiva A Consultas De Colaboradores",
        "category" => "Jefe De Legal"
      ],
      [
        "competence" => "Servicio al colaborador: Resuelve las consultas y solicitudes de los colaboradores con empatía y amabilidad.",
        "subCompetence" => "Amabilidad Y Cercanía Con El Colaborador",
        "category" => "Jefe De Legal"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Asertividad",
        "category" => "Gerente De Gestión Humana"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Claridad En El Mensaje",
        "category" => "Gerente De Gestión Humana"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Escucha",
        "category" => "Gerente De Gestión Humana"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Respuesta Oportuna",
        "category" => "Gerente De Gestión Humana"
      ],
      [
        "competence" => "Consultoría: Brinda alternativas de solución a partir del conocimiento del negocio; la identificación de las necesidades de los colaboradores y la organización.",
        "subCompetence" => "Asesoría",
        "category" => "Gerente De Gestión Humana"
      ],
      [
        "competence" => "Consultoría: Brinda alternativas de solución a partir del conocimiento del negocio; la identificación de las necesidades de los colaboradores y la organización.",
        "subCompetence" => "Conoce El Negocio",
        "category" => "Gerente De Gestión Humana"
      ],
      [
        "competence" => "Consultoría: Brinda alternativas de solución a partir del conocimiento del negocio; la identificación de las necesidades de los colaboradores y la organización.",
        "subCompetence" => "Evaluación De Colaboradores",
        "category" => "Gerente De Gestión Humana"
      ],
      [
        "competence" => "Consultoría: Brinda alternativas de solución a partir del conocimiento del negocio; la identificación de las necesidades de los colaboradores y la organización.",
        "subCompetence" => "Identificación De Expectativas Y Necesidades",
        "category" => "Gerente De Gestión Humana"
      ],
      [
        "competence" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "subCompetence" => "Alineamiento",
        "category" => "Gerente De Gestión Humana"
      ],
      [
        "competence" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "subCompetence" => "Convicción",
        "category" => "Gerente De Gestión Humana"
      ],
      [
        "competence" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "subCompetence" => "Dirección De Equipos",
        "category" => "Gerente De Gestión Humana"
      ],
      [
        "competence" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "subCompetence" => "Resolución De Conflictos",
        "category" => "Gerente De Gestión Humana"
      ],
      [
        "competence" => "Planificación: Determinar las metas y prioridades de los proyectos, estableciendo las acciones, los plazos y los recursos necesarios. Así como los instrumentos de medición y segumiento.",
        "subCompetence" => "Creación De Planes",
        "category" => "Gerente De Gestión Humana"
      ],
      [
        "competence" => "Planificación: Determinar las metas y prioridades de los proyectos, estableciendo las acciones, los plazos y los recursos necesarios. Así como los instrumentos de medición y segumiento.",
        "subCompetence" => "Definir Metas E Indciadores",
        "category" => "Gerente De Gestión Humana"
      ],
      [
        "competence" => "Planificación: Determinar las metas y prioridades de los proyectos, estableciendo las acciones, los plazos y los recursos necesarios. Así como los instrumentos de medición y segumiento.",
        "subCompetence" => "Planificación De Los Recursos",
        "category" => "Gerente De Gestión Humana"
      ],
      [
        "competence" => "Planificación: Determinar las metas y prioridades de los proyectos, estableciendo las acciones, los plazos y los recursos necesarios. Así como los instrumentos de medición y segumiento.",
        "subCompetence" => "Priorización",
        "category" => "Gerente De Gestión Humana"
      ],
      [
        "competence" => "Administración Estratégica: Fomúla las acciones del negocio definiendo procedimientos claros, evaluando las acciones efectivas y controlando el presupuesto.",
        "subCompetence" => "Control Presupuestal",
        "category" => "Gerente De Administración Y Finanzas"
      ],
      [
        "competence" => "Administración Estratégica: Fomúla las acciones del negocio definiendo procedimientos claros, evaluando las acciones efectivas y controlando el presupuesto.",
        "subCompetence" => "Evaluación Del Negocio",
        "category" => "Gerente De Administración Y Finanzas"
      ],
      [
        "competence" => "Administración Estratégica: Fomúla las acciones del negocio definiendo procedimientos claros, evaluando las acciones efectivas y controlando el presupuesto.",
        "subCompetence" => "Fórmulación De Estrategia",
        "category" => "Gerente De Administración Y Finanzas"
      ],
      [
        "competence" => "Administración Estratégica: Fomúla las acciones del negocio definiendo procedimientos claros, evaluando las acciones efectivas y controlando el presupuesto.",
        "subCompetence" => "Implementación",
        "category" => "Gerente De Administración Y Finanzas"
      ],
      [
        "competence" => "Dirección Financiera: Dirige las operaciones contables, define propuestas y acciones para la optimización de recursos.",
        "subCompetence" => "Propuestas De Inversión",
        "category" => "Gerente De Administración Y Finanzas"
      ],
      [
        "competence" => "Dirección Financiera: Dirige las operaciones contables, define propuestas y acciones para la optimización de recursos.",
        "subCompetence" => "Dirección De Operaciones Financieras",
        "category" => "Gerente De Administración Y Finanzas"
      ],
      [
        "competence" => "Dirección Financiera: Dirige las operaciones contables, define propuestas y acciones para la optimización de recursos.",
        "subCompetence" => "Estrategias De Optimización De Recursos",
        "category" => "Gerente De Administración Y Finanzas"
      ],
      [
        "competence" => "Dirección Financiera: Dirige las operaciones contables, define propuestas y acciones para la optimización de recursos.",
        "subCompetence" => "Gestión Del Riesgo",
        "category" => "Gerente De Administración Y Finanzas"
      ],
      [
        "competence" => "Dirección Financiera: Dirige las operaciones contables, define propuestas y acciones para la optimización de recursos.",
        "subCompetence" => "Negociación",
        "category" => "Gerente De Administración Y Finanzas"
      ],
      [
        "competence" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "subCompetence" => "Alineamiento",
        "category" => "Gerente De Administración Y Finanzas"
      ],
      [
        "competence" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "subCompetence" => "Convicción",
        "category" => "Gerente De Administración Y Finanzas"
      ],
      [
        "competence" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "subCompetence" => "Resolución De Conflictos",
        "category" => "Gerente De Administración Y Finanzas"
      ],
      [
        "competence" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "subCompetence" => "Dirección De Equipos",
        "category" => "Gerente De Administración Y Finanzas"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Solución De Problema",
        "category" => "Gerente De Administración Y Finanzas"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Resultados",
        "category" => "Gerente De Administración Y Finanzas"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Proactividad",
        "category" => "Gerente De Administración Y Finanzas"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Cumplimiento De Acuerdos",
        "category" => "Gerente De Administración Y Finanzas"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Asumo Las Consecuencias",
        "category" => "Gerente De Administración Y Finanzas"
      ],
      [
        "competence" => "Planificación: Determinar las metas y prioridades de los proyectos, estableciendo las acciones, los plazos y los recursos necesarios. Así como los instrumentos de medición y segumiento.",
        "subCompetence" => "Priorización",
        "category" => "Gerente De Administración Y Finanzas"
      ],
      [
        "competence" => "Planificación: Determinar las metas y prioridades de los proyectos, estableciendo las acciones, los plazos y los recursos necesarios. Así como los instrumentos de medición y segumiento.",
        "subCompetence" => "Planificación De Los Recursos",
        "category" => "Gerente De Administración Y Finanzas"
      ],
      [
        "competence" => "Planificación: Determinar las metas y prioridades de los proyectos, estableciendo las acciones, los plazos y los recursos necesarios. Así como los instrumentos de medición y segumiento.",
        "subCompetence" => "Definir Metas E Indciadores",
        "category" => "Gerente De Administración Y Finanzas"
      ],
      [
        "competence" => "Planificación: Determinar las metas y prioridades de los proyectos, estableciendo las acciones, los plazos y los recursos necesarios. Así como los instrumentos de medición y segumiento.",
        "subCompetence" => "Creación De Planes",
        "category" => "Gerente De Administración Y Finanzas"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Asertividad",
        "category" => "Jefe De Tic'S"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Respuesta Oportuna",
        "category" => "Jefe De Tic'S"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Claridad En El Mensaje",
        "category" => "Jefe De Tic'S"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Escucha",
        "category" => "Jefe De Tic'S"
      ],
      [
        "competence" => "Gestión Tecnológica: Garantiza el adecuado funcionamiento de las herramientas tecnológicas.",
        "subCompetence" => "Métodos",
        "category" => "Jefe De Tic'S"
      ],
      [
        "competence" => "Gestión Tecnológica: Garantiza el adecuado funcionamiento de las herramientas tecnológicas.",
        "subCompetence" => "Evaluación De Sistemas",
        "category" => "Jefe De Tic'S"
      ],
      [
        "competence" => "Gestión Tecnológica: Garantiza el adecuado funcionamiento de las herramientas tecnológicas.",
        "subCompetence" => "Definición De Acciones",
        "category" => "Jefe De Tic'S"
      ],
      [
        "competence" => "Gestión Tecnológica: Garantiza el adecuado funcionamiento de las herramientas tecnológicas.",
        "subCompetence" => "Diagnóstico De Problemáticas",
        "category" => "Jefe De Tic'S"
      ],
      [
        "competence" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "subCompetence" => "Dirección De Equipos",
        "category" => "Jefe De Tic'S"
      ],
      [
        "competence" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "subCompetence" => "Resolución De Conflictos",
        "category" => "Jefe De Tic'S"
      ],
      [
        "competence" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "subCompetence" => "Alineamiento",
        "category" => "Jefe De Tic'S"
      ],
      [
        "competence" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "subCompetence" => "Convicción",
        "category" => "Jefe De Tic'S"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Cumplimiento De Acuerdos",
        "category" => "Jefe De Tic'S"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Proactividad",
        "category" => "Jefe De Tic'S"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Asumo Las Consecuencias",
        "category" => "Jefe De Tic'S"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Resultados",
        "category" => "Jefe De Tic'S"
      ],
      [
        "competence" => "Planificación: Determinar las metas y prioridades de los proyectos, estableciendo las acciones, los plazos y los recursos necesarios. Así como los instrumentos de medición y segumiento.",
        "subCompetence" => "Definir Metas E Indciadores",
        "category" => "Jefe De Tic'S"
      ],
      [
        "competence" => "Planificación: Determinar las metas y prioridades de los proyectos, estableciendo las acciones, los plazos y los recursos necesarios. Así como los instrumentos de medición y segumiento.",
        "subCompetence" => "Creación De Planes",
        "category" => "Jefe De Tic'S"
      ],
      [
        "competence" => "Planificación: Determinar las metas y prioridades de los proyectos, estableciendo las acciones, los plazos y los recursos necesarios. Así como los instrumentos de medición y segumiento.",
        "subCompetence" => "Planificación De Los Recursos",
        "category" => "Jefe De Tic'S"
      ],
      [
        "competence" => "Planificación: Determinar las metas y prioridades de los proyectos, estableciendo las acciones, los plazos y los recursos necesarios. Así como los instrumentos de medición y segumiento.",
        "subCompetence" => "Priorización",
        "category" => "Jefe De Tic'S"
      ],
      [
        "competence" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "subCompetence" => "Gestión De Recursos",
        "category" => "Analista De Remuneraciones Y Compensaciones"
      ],
      [
        "competence" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "subCompetence" => "Metodología",
        "category" => "Analista De Remuneraciones Y Compensaciones"
      ],
      [
        "competence" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "subCompetence" => "Orientación Al Logro",
        "category" => "Analista De Remuneraciones Y Compensaciones"
      ],
      [
        "competence" => "Gestión Contable: Analiza de manera adecuada la información contable y define acciones adecuadas para el sistema de contable.",
        "subCompetence" => "Sistemas Correctivos",
        "category" => "Analista De Remuneraciones Y Compensaciones"
      ],
      [
        "competence" => "Gestión Contable: Analiza de manera adecuada la información contable y define acciones adecuadas para el sistema de contable.",
        "subCompetence" => "Plazos De Entrega",
        "category" => "Analista De Remuneraciones Y Compensaciones"
      ],
      [
        "competence" => "Gestión Contable: Analiza de manera adecuada la información contable y define acciones adecuadas para el sistema de contable.",
        "subCompetence" => "Informes Contables",
        "category" => "Analista De Remuneraciones Y Compensaciones"
      ],
      [
        "competence" => "Habilidad analítica: Capacidad de razonamiento y organización cognitivamente del trabajo para realizar un análisis lógico identificando problemas, información y datos significativos.",
        "subCompetence" => "Capacidad De Comprensión",
        "category" => "Analista De Remuneraciones Y Compensaciones"
      ],
      [
        "competence" => "Habilidad analítica: Capacidad de razonamiento y organización cognitivamente del trabajo para realizar un análisis lógico identificando problemas, información y datos significativos.",
        "subCompetence" => "Tratamiento De La Información",
        "category" => "Analista De Remuneraciones Y Compensaciones"
      ],
      [
        "competence" => "Habilidad analítica: Capacidad de razonamiento y organización cognitivamente del trabajo para realizar un análisis lógico identificando problemas, información y datos significativos.",
        "subCompetence" => "Análisis Lógico",
        "category" => "Analista De Remuneraciones Y Compensaciones"
      ],
      [
        "competence" => "Habilidad analítica: Capacidad de razonamiento y organización cognitivamente del trabajo para realizar un análisis lógico identificando problemas, información y datos significativos.",
        "subCompetence" => "Identificación De Problemas",
        "category" => "Analista De Remuneraciones Y Compensaciones"
      ],
      [
        "competence" => "Registro y Manejo de Información - Versión 2 AP: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Orientación Al Detalle",
        "category" => "Analista De Remuneraciones Y Compensaciones"
      ],
      [
        "competence" => "Registro y Manejo de Información - Versión 2 AP: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Organización",
        "category" => "Analista De Remuneraciones Y Compensaciones"
      ],
      [
        "competence" => "Registro y Manejo de Información - Versión 2 AP: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Cumplimiento De Procesos Operativos",
        "category" => "Analista De Remuneraciones Y Compensaciones"
      ],
      [
        "competence" => "Administración Estratégica: Fomúla las acciones del negocio definiendo procedimientos claros, evaluando las acciones efectivas y controlando el presupuesto.",
        "subCompetence" => "Control Presupuestal",
        "category" => "Auditor Interno"
      ],
      [
        "competence" => "Administración Estratégica: Fomúla las acciones del negocio definiendo procedimientos claros, evaluando las acciones efectivas y controlando el presupuesto.",
        "subCompetence" => "Implementación",
        "category" => "Auditor Interno"
      ],
      [
        "competence" => "Administración Estratégica: Fomúla las acciones del negocio definiendo procedimientos claros, evaluando las acciones efectivas y controlando el presupuesto.",
        "subCompetence" => "Fórmulación De Estrategia",
        "category" => "Auditor Interno"
      ],
      [
        "competence" => "Consultoría: Brinda alternativas de solución a partir del conocimiento del negocio; la identificación de las necesidades de los colaboradores y la organización.",
        "subCompetence" => "Asesoría",
        "category" => "Auditor Interno"
      ],
      [
        "competence" => "Consultoría: Brinda alternativas de solución a partir del conocimiento del negocio; la identificación de las necesidades de los colaboradores y la organización.",
        "subCompetence" => "Identificación De Expectativas Y Necesidades",
        "category" => "Auditor Interno"
      ],
      [
        "competence" => "Control del Mantenimiento: Soluciona los problemas de manera efectiva. Evalúa el rendimiento de mantenimiento e identifica oportunidades de reducción de costos.",
        "subCompetence" => "Solución De Problemas",
        "category" => "Auditor Interno"
      ],
      [
        "competence" => "Gestión Comercial - Versión 2 AP: Propone las estrategias comerciales, objetivos y metas de acuerdo al conocimiento del negocio, producto y el funcionamiento de la competencia.",
        "subCompetence" => "Conocimiento Del Negocio",
        "category" => "Auditor Interno"
      ],
      [
        "competence" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "subCompetence" => "Gestión De Consecuencias",
        "category" => "Auditor Interno"
      ],
      [
        "competence" => "Gestión de normas y procedimientos - Versión GP: Gestion la creación de normas y procedimientos transversales para los negocios explicando efectivamente el contenido y los lineamientos.",
        "subCompetence" => "Definición De Políticas Gp",
        "category" => "Auditor Interno"
      ],
      [
        "competence" => "Gestión de normas y procedimientos - Versión GP: Gestion la creación de normas y procedimientos transversales para los negocios explicando efectivamente el contenido y los lineamientos.",
        "subCompetence" => "Definición de Políticas",
        "category" => "Auditor Interno"
      ],
      [
        "competence" => "Gestión de normas y procedimientos: Se preocupa y promueve respeto de las normas y procedimientos. Vigila el cumplimiento de las normas e interviene para corregir faltas.",
        "subCompetence" => "Claridad En La Orientación",
        "category" => "Auditor Interno"
      ],
      [
        "competence" => "Gestión de normas y procedimientos: Se preocupa y promueve respeto de las normas y procedimientos. Vigila el cumplimiento de las normas e interviene para corregir faltas.",
        "subCompetence" => "Revisión Y Seguimiento",
        "category" => "Auditor Interno"
      ],
      [
        "competence" => "Habilidad analítica: Capacidad de razonamiento y organización cognitivamente del trabajo para realizar un análisis lógico identificando problemas, información y datos significativos.",
        "subCompetence" => "Identificación De Problemas",
        "category" => "Auditor Interno"
      ],
      [
        "competence" => "Habilidad analítica: Capacidad de razonamiento y organización cognitivamente del trabajo para realizar un análisis lógico identificando problemas, información y datos significativos.",
        "subCompetence" => "Análisis Lógico",
        "category" => "Auditor Interno"
      ],
      [
        "competence" => "Habilidad analítica: Capacidad de razonamiento y organización cognitivamente del trabajo para realizar un análisis lógico identificando problemas, información y datos significativos.",
        "subCompetence" => "Tratamiento De La Información",
        "category" => "Auditor Interno"
      ],
      [
        "competence" => "Habilidad analítica: Capacidad de razonamiento y organización cognitivamente del trabajo para realizar un análisis lógico identificando problemas, información y datos significativos.",
        "subCompetence" => "Capacidad De Comprensión",
        "category" => "Auditor Interno"
      ],
      [
        "competence" => "Habilidad de Enseñanza: Logra explicar con claridad y paciencia el funcionamiento de la operación, brinda retroalmentación clara para incorporar los procedimientos e instrucciones.",
        "subCompetence" => "Evaluación",
        "category" => "Auditor Interno"
      ],
      [
        "competence" => "Habilidad de Enseñanza: Logra explicar con claridad y paciencia el funcionamiento de la operación, brinda retroalmentación clara para incorporar los procedimientos e instrucciones.",
        "subCompetence" => "Evaluación",
        "category" => "Auditor Interno"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Asumo Las Consecuencias",
        "category" => "Auditor Interno"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Resultados",
        "category" => "Auditor Interno"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Cumplimiento De Acuerdos",
        "category" => "Auditor Interno"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Proactividad",
        "category" => "Auditor Interno"
      ],
      [
        "competence" => "Registro y Manejo de Información - Versión 2 AP: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Orientación Al Detalle",
        "category" => "Auditor Interno"
      ],
      [
        "competence" => "Registro y Manejo de Información - Versión 2 AP: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Organización",
        "category" => "Auditor Interno"
      ],
      [
        "competence" => "Registro y Manejo de Información - Versión 2 AP: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Cumplimiento De Procesos Operativos",
        "category" => "Auditor Interno"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Asertividad",
        "category" => "Operador De Cctv"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Claridad En El Mensaje",
        "category" => "Operador De Cctv"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Escucha",
        "category" => "Operador De Cctv"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Informe Escrito",
        "category" => "Operador De Cctv"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Respuesta Oportuna",
        "category" => "Operador De Cctv"
      ],
      [
        "competence" => "Gestión de normas y procedimientos: Se preocupa y promueve respeto de las normas y procedimientos. Vigila el cumplimiento de las normas e interviene para corregir faltas.",
        "subCompetence" => "Claridad En La Orientación",
        "category" => "Operador De Cctv"
      ],
      [
        "competence" => "Gestión de normas y procedimientos: Se preocupa y promueve respeto de las normas y procedimientos. Vigila el cumplimiento de las normas e interviene para corregir faltas.",
        "subCompetence" => "Respeto A La Norma",
        "category" => "Operador De Cctv"
      ],
      [
        "competence" => "Pensamiento claro y tranquilo: Mantiene la calma necesaria para poder reaccionar de manera correcta y proactiva ante cualquier situacion de riesgo.",
        "subCompetence" => "Calma Ante La Crisis",
        "category" => "Operador De Cctv"
      ],
      [
        "competence" => "Pensamiento claro y tranquilo: Mantiene la calma necesaria para poder reaccionar de manera correcta y proactiva ante cualquier situacion de riesgo.",
        "subCompetence" => "Respuesta Anticipada",
        "category" => "Operador De Cctv"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Cumplimiento De Procedimientos",
        "category" => "Operador De Cctv"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Organización De Información",
        "category" => "Operador De Cctv"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Orientación Al Registro Detallado",
        "category" => "Operador De Cctv"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Amabilidad Y Cercanía (seguridad)",
        "category" => "Operador De Cctv"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Atención Efectiva A Consultas (seguridad)",
        "category" => "Operador De Cctv"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Atención Presencial Oportuna (seguridad)",
        "category" => "Operador De Cctv"
      ],
      [
        "competence" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "subCompetence" => "Gestión De Consecuencias",
        "category" => "Jefe De Contabilidad Y Caja"
      ],
      [
        "competence" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "subCompetence" => "Supervisión",
        "category" => "Jefe De Contabilidad Y Caja"
      ],
      [
        "competence" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "subCompetence" => "Seguimiento",
        "category" => "Jefe De Contabilidad Y Caja"
      ],
      [
        "competence" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "subCompetence" => "Retroalimentación",
        "category" => "Jefe De Contabilidad Y Caja"
      ],
      [
        "competence" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "subCompetence" => "Control",
        "category" => "Jefe De Contabilidad Y Caja"
      ],
      [
        "competence" => "Gestión de Flujo de Caja: Evalúa los recursos financieros, analiza la información del flujo de caja y realiza un adecuado control y reporte de su funcionamiento.",
        "subCompetence" => "Creación De Reportes",
        "category" => "Jefe De Contabilidad Y Caja"
      ],
      [
        "competence" => "Gestión de Flujo de Caja: Evalúa los recursos financieros, analiza la información del flujo de caja y realiza un adecuado control y reporte de su funcionamiento.",
        "subCompetence" => "Evaluación De Recursos Financieros",
        "category" => "Jefe De Contabilidad Y Caja"
      ],
      [
        "competence" => "Gestión de Flujo de Caja: Evalúa los recursos financieros, analiza la información del flujo de caja y realiza un adecuado control y reporte de su funcionamiento.",
        "subCompetence" => "Análisis De Reportes",
        "category" => "Jefe De Contabilidad Y Caja"
      ],
      [
        "competence" => "Gestión de Flujo de Caja: Evalúa los recursos financieros, analiza la información del flujo de caja y realiza un adecuado control y reporte de su funcionamiento.",
        "subCompetence" => "Pago De Servicios",
        "category" => "Jefe De Contabilidad Y Caja"
      ],
      [
        "competence" => "Gestión de Trámites: Garantiza la recepción y entrega de documentos asegurando que cumpla con los requisitos solicitados.",
        "subCompetence" => "Control Documentario",
        "category" => "Jefe De Contabilidad Y Caja"
      ],
      [
        "competence" => "Gestión de Trámites: Garantiza la recepción y entrega de documentos asegurando que cumpla con los requisitos solicitados.",
        "subCompetence" => "Despacho Documentario",
        "category" => "Jefe De Contabilidad Y Caja"
      ],
      [
        "competence" => "Gestión de Trámites: Garantiza la recepción y entrega de documentos asegurando que cumpla con los requisitos solicitados.",
        "subCompetence" => "Perseverancia En La Entrega",
        "category" => "Jefe De Contabilidad Y Caja"
      ],
      [
        "competence" => "Gestión de Trámites: Garantiza la recepción y entrega de documentos asegurando que cumpla con los requisitos solicitados.",
        "subCompetence" => "Construcción De Red De Contactos",
        "category" => "Jefe De Contabilidad Y Caja"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Cumplimiento De Acuerdos",
        "category" => "Jefe De Contabilidad Y Caja"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Proactividad",
        "category" => "Jefe De Contabilidad Y Caja"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Resultados",
        "category" => "Jefe De Contabilidad Y Caja"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Asumo Las Consecuencias",
        "category" => "Jefe De Contabilidad Y Caja"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Cumplimiento De Procedimientos",
        "category" => "Jefe De Contabilidad Y Caja"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Organización De Información",
        "category" => "Jefe De Contabilidad Y Caja"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Orientación Al Registro Detallado",
        "category" => "Jefe De Contabilidad Y Caja"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Asertividad",
        "category" => "Analista En  Proyectos De Gestion Humana"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Respuesta Oportuna",
        "category" => "Analista En  Proyectos De Gestion Humana"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Escucha",
        "category" => "Analista En  Proyectos De Gestion Humana"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Claridad En El Mensaje",
        "category" => "Analista En  Proyectos De Gestion Humana"
      ],
      [
        "competence" => "Consultoría: Brinda alternativas de solución a partir del conocimiento del negocio; la identificación de las necesidades de los colaboradores y la organización.",
        "subCompetence" => "Conoce El Negocio",
        "category" => "Analista En  Proyectos De Gestion Humana"
      ],
      [
        "competence" => "Consultoría: Brinda alternativas de solución a partir del conocimiento del negocio; la identificación de las necesidades de los colaboradores y la organización.",
        "subCompetence" => "Asesoría",
        "category" => "Analista En  Proyectos De Gestion Humana"
      ],
      [
        "competence" => "Consultoría: Brinda alternativas de solución a partir del conocimiento del negocio; la identificación de las necesidades de los colaboradores y la organización.",
        "subCompetence" => "Evaluación De Colaboradores",
        "category" => "Analista En  Proyectos De Gestion Humana"
      ],
      [
        "competence" => "Consultoría: Brinda alternativas de solución a partir del conocimiento del negocio; la identificación de las necesidades de los colaboradores y la organización.",
        "subCompetence" => "Identificación De Expectativas Y Necesidades",
        "category" => "Analista En  Proyectos De Gestion Humana"
      ],
      [
        "competence" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "subCompetence" => "Gestión De Recursos",
        "category" => "Analista En  Proyectos De Gestion Humana"
      ],
      [
        "competence" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "subCompetence" => "Metodología",
        "category" => "Analista En  Proyectos De Gestion Humana"
      ],
      [
        "competence" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "subCompetence" => "Orientación Al Logro",
        "category" => "Analista En  Proyectos De Gestion Humana"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Cumplimiento De Procedimientos",
        "category" => "Analista En  Proyectos De Gestion Humana"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Organización De Información",
        "category" => "Analista En  Proyectos De Gestion Humana"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Orientación Al Registro Detallado",
        "category" => "Analista En  Proyectos De Gestion Humana"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Amabilidad Y Cercanía",
        "category" => "Analista En  Proyectos De Gestion Humana"
      ],
      [
        "competence" => "Servicio al colaborador: Resuelve las consultas y solicitudes de los colaboradores con empatía y amabilidad.",
        "subCompetence" => "Atención Oportuna",
        "category" => "Analista En  Proyectos De Gestion Humana"
      ],
      [
        "competence" => "Servicio al colaborador: Resuelve las consultas y solicitudes de los colaboradores con empatía y amabilidad.",
        "subCompetence" => "Atención Efectiva A Consultas De Colaboradores",
        "category" => "Analista En  Proyectos De Gestion Humana"
      ],
      [
        "competence" => "Servicio al colaborador: Resuelve las consultas y solicitudes de los colaboradores con empatía y amabilidad.",
        "subCompetence" => "Empatía",
        "category" => "Analista En  Proyectos De Gestion Humana"
      ],
      [
        "competence" => "Servicio al colaborador: Resuelve las consultas y solicitudes de los colaboradores con empatía y amabilidad.",
        "subCompetence" => "Amabilidad Y Cercanía Con El Colaborador",
        "category" => "Analista En  Proyectos De Gestion Humana"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Asertividad",
        "category" => "Analista Legal"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Claridad En El Mensaje",
        "category" => "Analista Legal"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Escucha",
        "category" => "Analista Legal"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Respuesta Oportuna",
        "category" => "Analista Legal"
      ],
      [
        "competence" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "subCompetence" => "Gestión De Recursos",
        "category" => "Analista Legal"
      ],
      [
        "competence" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "subCompetence" => "Metodología",
        "category" => "Analista Legal"
      ],
      [
        "competence" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "subCompetence" => "Orientación Al Logro",
        "category" => "Analista Legal"
      ],
      [
        "competence" => "Gestión de Trámites: Garantiza la recepción y entrega de documentos asegurando que cumpla con los requisitos solicitados.",
        "subCompetence" => "Construcción De Red De Contactos",
        "category" => "Analista Legal"
      ],
      [
        "competence" => "Gestión de Trámites: Garantiza la recepción y entrega de documentos asegurando que cumpla con los requisitos solicitados.",
        "subCompetence" => "Control Documentario",
        "category" => "Analista Legal"
      ],
      [
        "competence" => "Gestión de Trámites: Garantiza la recepción y entrega de documentos asegurando que cumpla con los requisitos solicitados.",
        "subCompetence" => "Despacho Documentario",
        "category" => "Analista Legal"
      ],
      [
        "competence" => "Gestión de Trámites: Garantiza la recepción y entrega de documentos asegurando que cumpla con los requisitos solicitados.",
        "subCompetence" => "Perseverancia En La Entrega",
        "category" => "Analista Legal"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Amabilidad Y Cercanía",
        "category" => "Analista Legal"
      ],
      [
        "competence" => "Servicio al colaborador: Resuelve las consultas y solicitudes de los colaboradores con empatía y amabilidad.",
        "subCompetence" => "Amabilidad Y Cercanía Con El Colaborador",
        "category" => "Analista Legal"
      ],
      [
        "competence" => "Servicio al colaborador: Resuelve las consultas y solicitudes de los colaboradores con empatía y amabilidad.",
        "subCompetence" => "Atención Efectiva A Consultas De Colaboradores",
        "category" => "Analista Legal"
      ],
      [
        "competence" => "Servicio al colaborador: Resuelve las consultas y solicitudes de los colaboradores con empatía y amabilidad.",
        "subCompetence" => "Atención Oportuna",
        "category" => "Analista Legal"
      ],
      [
        "competence" => "Servicio al colaborador: Resuelve las consultas y solicitudes de los colaboradores con empatía y amabilidad.",
        "subCompetence" => "Empatía",
        "category" => "Analista Legal"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Asertividad",
        "category" => "Caja Ap"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Respuesta Oportuna",
        "category" => "Caja Ap"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Claridad En El Mensaje",
        "category" => "Caja Ap"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Escucha",
        "category" => "Caja Ap"
      ],
      [
        "competence" => "Registro y Manejo de Caja: Control y registro de información adecuada de la Caja, revisando de manera adecuada los billetes y cumpliendo con los procedimientos para el cierre de caja.",
        "subCompetence" => "Revisión De Ingresos",
        "category" => "Caja Ap"
      ],
      [
        "competence" => "Registro y Manejo de Caja: Control y registro de información adecuada de la Caja, revisando de manera adecuada los billetes y cumpliendo con los procedimientos para el cierre de caja.",
        "subCompetence" => "Cierre De Caja",
        "category" => "Caja Ap"
      ],
      [
        "competence" => "Registro y Manejo de Caja: Control y registro de información adecuada de la Caja, revisando de manera adecuada los billetes y cumpliendo con los procedimientos para el cierre de caja.",
        "subCompetence" => "Reportes",
        "category" => "Caja Ap"
      ],
      [
        "competence" => "Registro y Manejo de Caja: Control y registro de información adecuada de la Caja, revisando de manera adecuada los billetes y cumpliendo con los procedimientos para el cierre de caja.",
        "subCompetence" => "Entrega De Dinero",
        "category" => "Caja Ap"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Organización De Información",
        "category" => "Caja Ap"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Orientación Al Registro Detallado",
        "category" => "Caja Ap"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Cumplimiento De Procedimientos",
        "category" => "Caja Ap"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Amabilidad Y Cercanía",
        "category" => "Caja Ap"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Atención Presencial Oportuna",
        "category" => "Caja Ap"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Atención Efectiva A Consultas",
        "category" => "Caja Ap"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Seguimiento A La Atención",
        "category" => "Caja Ap"
      ],
      [
        "competence" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "subCompetence" => "Pasión",
        "category" => "Caja Ap"
      ],
      [
        "competence" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "subCompetence" => "Colaboración",
        "category" => "Caja Ap"
      ],
      [
        "competence" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "subCompetence" => "Responsabilidad",
        "category" => "Caja Ap"
      ],
      [
        "competence" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "subCompetence" => "Resuelve Conflictos",
        "category" => "Caja Ap"
      ],
      [
        "competence" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "subCompetence" => "Traspase De Información En Equipo",
        "category" => "Caja Ap"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Claridad En El Mensaje",
        "category" => "Jefe De Almacen"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Asertividad",
        "category" => "Jefe De Almacen"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Escucha",
        "category" => "Jefe De Almacen"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Respuesta Oportuna",
        "category" => "Jefe De Almacen"
      ],
      [
        "competence" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "subCompetence" => "Control",
        "category" => "Jefe De Almacen"
      ],
      [
        "competence" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "subCompetence" => "Gestión De Consecuencias",
        "category" => "Jefe De Almacen"
      ],
      [
        "competence" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "subCompetence" => "Supervisión",
        "category" => "Jefe De Almacen"
      ],
      [
        "competence" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "subCompetence" => "Retroalimentación",
        "category" => "Jefe De Almacen"
      ],
      [
        "competence" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "subCompetence" => "Seguimiento",
        "category" => "Jefe De Almacen"
      ],
      [
        "competence" => "Gestión de Proveedores - Admin: Controlar el cumplimiento de los acuerdos del proveedor, interviene ante los problemas y reclamos en la entrega de mercadería.",
        "subCompetence" => "Identificación",
        "category" => "Jefe De Almacen"
      ],
      [
        "competence" => "Gestión de Proveedores - Admin: Controlar el cumplimiento de los acuerdos del proveedor, interviene ante los problemas y reclamos en la entrega de mercadería.",
        "subCompetence" => "Manejo De Reclamos",
        "category" => "Jefe De Almacen"
      ],
      [
        "competence" => "Gestión de Proveedores - Admin: Controlar el cumplimiento de los acuerdos del proveedor, interviene ante los problemas y reclamos en la entrega de mercadería.",
        "subCompetence" => "Solución De Problemas Con Proveedores",
        "category" => "Jefe De Almacen"
      ],
      [
        "competence" => "Gestión de Proveedores - Admin: Controlar el cumplimiento de los acuerdos del proveedor, interviene ante los problemas y reclamos en la entrega de mercadería.",
        "subCompetence" => "Seguimiento A Proveedores",
        "category" => "Jefe De Almacen"
      ],
      [
        "competence" => "Organización y Control de Mercadería: Garantizar la cantidad de recursos que necesita la organización a través de una adecuada supervisión, organización, clasificación y control.",
        "subCompetence" => "Supervisión De Inventario",
        "category" => "Jefe De Almacen"
      ],
      [
        "competence" => "Organización y Control de Mercadería: Garantizar la cantidad de recursos que necesita la organización a través de una adecuada supervisión, organización, clasificación y control.",
        "subCompetence" => "Reclamos De Ingreso De Recursos",
        "category" => "Jefe De Almacen"
      ],
      [
        "competence" => "Organización y Control de Mercadería: Garantizar la cantidad de recursos que necesita la organización a través de una adecuada supervisión, organización, clasificación y control.",
        "subCompetence" => "Protección De Recursos",
        "category" => "Jefe De Almacen"
      ],
      [
        "competence" => "Organización y Control de Mercadería: Garantizar la cantidad de recursos que necesita la organización a través de una adecuada supervisión, organización, clasificación y control.",
        "subCompetence" => "Organización De Recursos",
        "category" => "Jefe De Almacen"
      ],
      [
        "competence" => "Organización y Control de Mercadería: Garantizar la cantidad de recursos que necesita la organización a través de una adecuada supervisión, organización, clasificación y control.",
        "subCompetence" => "Control De Movimientos",
        "category" => "Jefe De Almacen"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Asertividad",
        "category" => "Asistente De Almacén Ap"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Claridad En El Mensaje",
        "category" => "Asistente De Almacén Ap"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Respuesta Oportuna",
        "category" => "Asistente De Almacén Ap"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Escucha",
        "category" => "Asistente De Almacén Ap"
      ],
      [
        "competence" => "Cooperación Operativa: Coopera de manera activa con las necesidades del equipo y unidades con las que interactúa. Respeta los acuerdos generando un espacio colaborativo y de confianza.",
        "subCompetence" => "Orientación A La Meta Común",
        "category" => "Asistente De Almacén Ap"
      ],
      [
        "competence" => "Cooperación Operativa: Coopera de manera activa con las necesidades del equipo y unidades con las que interactúa. Respeta los acuerdos generando un espacio colaborativo y de confianza.",
        "subCompetence" => "Relaciones Interpersonales",
        "category" => "Asistente De Almacén Ap"
      ],
      [
        "competence" => "Cooperación Operativa: Coopera de manera activa con las necesidades del equipo y unidades con las que interactúa. Respeta los acuerdos generando un espacio colaborativo y de confianza.",
        "subCompetence" => "Cooperación",
        "category" => "Asistente De Almacén Ap"
      ],
      [
        "competence" => "Mentalidad de Dueño V2: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Anticipación A Requerimientos",
        "category" => "Asistente De Almacén Ap"
      ],
      [
        "competence" => "Mentalidad de Dueño V2: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Pertenencia",
        "category" => "Asistente De Almacén Ap"
      ],
      [
        "competence" => "Mentalidad de Dueño V2: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Solución De Problemas Difíciles",
        "category" => "Asistente De Almacén Ap"
      ],
      [
        "competence" => "Organización y Control de Mercadería: Garantizar la cantidad de recursos que necesita la organización a través de una adecuada supervisión, organización, clasificación y control.",
        "subCompetence" => "Organización De Recursos",
        "category" => "Asistente De Almacén Ap"
      ],
      [
        "competence" => "Organización y Control de Mercadería: Garantizar la cantidad de recursos que necesita la organización a través de una adecuada supervisión, organización, clasificación y control.",
        "subCompetence" => "Protección De Recursos",
        "category" => "Asistente De Almacén Ap"
      ],
      [
        "competence" => "Organización y Control de Mercadería: Garantizar la cantidad de recursos que necesita la organización a través de una adecuada supervisión, organización, clasificación y control.",
        "subCompetence" => "Reclamos De Ingreso De Recursos",
        "category" => "Asistente De Almacén Ap"
      ],
      [
        "competence" => "Organización y Control de Mercadería: Garantizar la cantidad de recursos que necesita la organización a través de una adecuada supervisión, organización, clasificación y control.",
        "subCompetence" => "Control De Movimientos",
        "category" => "Asistente De Almacén Ap"
      ],
      [
        "competence" => "Organización y Control de Mercadería: Garantizar la cantidad de recursos que necesita la organización a través de una adecuada supervisión, organización, clasificación y control.",
        "subCompetence" => "Supervisión De Inventario",
        "category" => "Asistente De Almacén Ap"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Cumplimiento De Procedimientos",
        "category" => "Asistente De Almacén Ap"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Organización De Información",
        "category" => "Asistente De Almacén Ap"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Orientación Al Registro Detallado",
        "category" => "Asistente De Almacén Ap"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Asertividad",
        "category" => "Coordinador De Ventas"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Claridad En El Mensaje",
        "category" => "Coordinador De Ventas"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Escucha",
        "category" => "Coordinador De Ventas"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Respuesta Oportuna",
        "category" => "Coordinador De Ventas"
      ],
      [
        "competence" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "subCompetence" => "Control",
        "category" => "Coordinador De Ventas"
      ],
      [
        "competence" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "subCompetence" => "Gestión De Consecuencias",
        "category" => "Coordinador De Ventas"
      ],
      [
        "competence" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "subCompetence" => "Retroalimentación",
        "category" => "Coordinador De Ventas"
      ],
      [
        "competence" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "subCompetence" => "Seguimiento",
        "category" => "Coordinador De Ventas"
      ],
      [
        "competence" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "subCompetence" => "Supervisión",
        "category" => "Coordinador De Ventas"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Cumplimiento De Procedimientos",
        "category" => "Coordinador De Ventas"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Organización De Información",
        "category" => "Coordinador De Ventas"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Orientación Al Registro Detallado",
        "category" => "Coordinador De Ventas"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Amabilidad Y Cercanía",
        "category" => "Coordinador De Ventas"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Atención Efectiva A Consultas",
        "category" => "Coordinador De Ventas"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Atención Presencial Oportuna",
        "category" => "Coordinador De Ventas"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Seguimiento A La Atención",
        "category" => "Coordinador De Ventas"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Respuesta Oportuna",
        "category" => "Asesor Comercial"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Asertividad",
        "category" => "Asesor Comercial"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Escucha",
        "category" => "Asesor Comercial"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Claridad En El Mensaje",
        "category" => "Asesor Comercial"
      ],
      [
        "competence" => "Habilidad Comercial: Logra persuadir al cliente a través de argumentos poderosos, realiza seguimiento oportuno manteniéndose cerca del cliente y aprovechando oportunidades.",
        "subCompetence" => "Seguimiento Comercial",
        "category" => "Asesor Comercial"
      ],
      [
        "competence" => "Habilidad Comercial: Logra persuadir al cliente a través de argumentos poderosos, realiza seguimiento oportuno manteniéndose cerca del cliente y aprovechando oportunidades.",
        "subCompetence" => "Negociación Comercial",
        "category" => "Asesor Comercial"
      ],
      [
        "competence" => "Habilidad Comercial: Logra persuadir al cliente a través de argumentos poderosos, realiza seguimiento oportuno manteniéndose cerca del cliente y aprovechando oportunidades.",
        "subCompetence" => "Manejo De Objeciones",
        "category" => "Asesor Comercial"
      ],
      [
        "competence" => "Habilidad Comercial: Logra persuadir al cliente a través de argumentos poderosos, realiza seguimiento oportuno manteniéndose cerca del cliente y aprovechando oportunidades.",
        "subCompetence" => "Disciplina Comercial",
        "category" => "Asesor Comercial"
      ],
      [
        "competence" => "Habilidad Comercial: Logra persuadir al cliente a través de argumentos poderosos, realiza seguimiento oportuno manteniéndose cerca del cliente y aprovechando oportunidades.",
        "subCompetence" => "Búsqueda De Oportunidades",
        "category" => "Asesor Comercial"
      ],
      [
        "competence" => "Negociación: Capacidad para dirigir o controlar una discusión utilizando técnicas ganar-ganar planificando alternativas para negociar los mejores acuerdos.",
        "subCompetence" => "Cierre De Acuerdos",
        "category" => "Asesor Comercial"
      ],
      [
        "competence" => "Negociación: Capacidad para dirigir o controlar una discusión utilizando técnicas ganar-ganar planificando alternativas para negociar los mejores acuerdos.",
        "subCompetence" => "Comunicación Asertiva",
        "category" => "Asesor Comercial"
      ],
      [
        "competence" => "Negociación: Capacidad para dirigir o controlar una discusión utilizando técnicas ganar-ganar planificando alternativas para negociar los mejores acuerdos.",
        "subCompetence" => "Planificación De La Estrategia",
        "category" => "Asesor Comercial"
      ],
      [
        "competence" => "Negociación: Capacidad para dirigir o controlar una discusión utilizando técnicas ganar-ganar planificando alternativas para negociar los mejores acuerdos.",
        "subCompetence" => "Recolección De Información",
        "category" => "Asesor Comercial"
      ],
      [
        "competence" => "Persuasión: Convencer e influir en los procesos de toma de decisiones de los clientes con el fin de lograr que éstos sigan una línea de acción alineada a concretar las ventas.",
        "subCompetence" => "Impacto",
        "category" => "Asesor Comercial"
      ],
      [
        "competence" => "Persuasión: Convencer e influir en los procesos de toma de decisiones de los clientes con el fin de lograr que éstos sigan una línea de acción alineada a concretar las ventas.",
        "subCompetence" => "Influencia",
        "category" => "Asesor Comercial"
      ],
      [
        "competence" => "Persuasión: Convencer e influir en los procesos de toma de decisiones de los clientes con el fin de lograr que éstos sigan una línea de acción alineada a concretar las ventas.",
        "subCompetence" => "Anticipación Asertiva",
        "category" => "Asesor Comercial"
      ],
      [
        "competence" => "Persuasión: Convencer e influir en los procesos de toma de decisiones de los clientes con el fin de lograr que éstos sigan una línea de acción alineada a concretar las ventas.",
        "subCompetence" => "Adaptación",
        "category" => "Asesor Comercial"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Cumplimiento De Procedimientos",
        "category" => "Asesor Comercial"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Organización De Información",
        "category" => "Asesor Comercial"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Orientación Al Registro Detallado",
        "category" => "Asesor Comercial"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Atención Presencial Oportuna",
        "category" => "Asesor Comercial"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Atención Efectiva A Consultas",
        "category" => "Asesor Comercial"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Amabilidad Y Cercanía",
        "category" => "Asesor Comercial"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Seguimiento A La Atención",
        "category" => "Asesor Comercial"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Asertividad",
        "category" => "Jefe De Ventas Ap"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Claridad En El Mensaje",
        "category" => "Jefe De Ventas Ap"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Escucha",
        "category" => "Jefe De Ventas Ap"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Respuesta Oportuna",
        "category" => "Jefe De Ventas Ap"
      ],
      [
        "competence" => "Gestión Comercial - Versión 2 AP: Propone las estrategias comerciales, objetivos y metas de acuerdo al conocimiento del negocio, producto y el funcionamiento de la competencia.",
        "subCompetence" => "Conocimiento De La Competencia",
        "category" => "Jefe De Ventas Ap"
      ],
      [
        "competence" => "Gestión Comercial - Versión 2 AP: Propone las estrategias comerciales, objetivos y metas de acuerdo al conocimiento del negocio, producto y el funcionamiento de la competencia.",
        "subCompetence" => "Conocimiento Del Negocio",
        "category" => "Jefe De Ventas Ap"
      ],
      [
        "competence" => "Gestión Comercial - Versión 2 AP: Propone las estrategias comerciales, objetivos y metas de acuerdo al conocimiento del negocio, producto y el funcionamiento de la competencia.",
        "subCompetence" => "Propone Estrategias, Objetivos Y Metas",
        "category" => "Jefe De Ventas Ap"
      ],
      [
        "competence" => "Gestión Comercial - Versión 2 AP: Propone las estrategias comerciales, objetivos y metas de acuerdo al conocimiento del negocio, producto y el funcionamiento de la competencia.",
        "subCompetence" => "Reajustes Comerciales",
        "category" => "Jefe De Ventas Ap"
      ],
      [
        "competence" => "Gestión de Clientes Relevantes: Fideliza clientes relevantes del negocio y resuelve sus problemas. Cuenta con un profundo conocimiento del cliente.",
        "subCompetence" => "Conocimiento Del Cliente",
        "category" => "Jefe De Ventas Ap"
      ],
      [
        "competence" => "Gestión de Clientes Relevantes: Fideliza clientes relevantes del negocio y resuelve sus problemas. Cuenta con un profundo conocimiento del cliente.",
        "subCompetence" => "Fidelización Con El Cliente",
        "category" => "Jefe De Ventas Ap"
      ],
      [
        "competence" => "Gestión de Clientes Relevantes: Fideliza clientes relevantes del negocio y resuelve sus problemas. Cuenta con un profundo conocimiento del cliente.",
        "subCompetence" => "Manejo De Clientes Díficiles",
        "category" => "Jefe De Ventas Ap"
      ],
      [
        "competence" => "Gestión de Clientes Relevantes: Fideliza clientes relevantes del negocio y resuelve sus problemas. Cuenta con un profundo conocimiento del cliente.",
        "subCompetence" => "Resolución De Problemas",
        "category" => "Jefe De Ventas Ap"
      ],
      [
        "competence" => "Gestión de Clientes Relevantes: Fideliza clientes relevantes del negocio y resuelve sus problemas. Cuenta con un profundo conocimiento del cliente.",
        "subCompetence" => "Relación Con El Cliente",
        "category" => "Jefe De Ventas Ap"
      ],
      [
        "competence" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "subCompetence" => "Supervisión",
        "category" => "Jefe De Ventas Ap"
      ],
      [
        "competence" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "subCompetence" => "Seguimiento",
        "category" => "Jefe De Ventas Ap"
      ],
      [
        "competence" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "subCompetence" => "Retroalimentación",
        "category" => "Jefe De Ventas Ap"
      ],
      [
        "competence" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "subCompetence" => "Gestión De Consecuencias",
        "category" => "Jefe De Ventas Ap"
      ],
      [
        "competence" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "subCompetence" => "Control",
        "category" => "Jefe De Ventas Ap"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Proactividad",
        "category" => "Jefe De Ventas Ap"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Resultados",
        "category" => "Jefe De Ventas Ap"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Cumplimiento De Acuerdos",
        "category" => "Jefe De Ventas Ap"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Solución De Problema",
        "category" => "Jefe De Ventas Ap"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Asumo Las Consecuencias",
        "category" => "Jefe De Ventas Ap"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Asertividad",
        "category" => "Asesor De Repuestos"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Claridad En El Mensaje",
        "category" => "Asesor De Repuestos"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Escucha",
        "category" => "Asesor De Repuestos"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Respuesta Oportuna",
        "category" => "Asesor De Repuestos"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Cumplimiento De Procedimientos",
        "category" => "Asesor De Repuestos"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Organización De Información",
        "category" => "Asesor De Repuestos"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Orientación Al Registro Detallado",
        "category" => "Asesor De Repuestos"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Amabilidad Y Cercanía",
        "category" => "Asesor De Repuestos"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Atención Efectiva A Consultas",
        "category" => "Asesor De Repuestos"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Atención Presencial Oportuna",
        "category" => "Asesor De Repuestos"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Seguimiento A La Atención",
        "category" => "Asesor De Repuestos"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Asertividad",
        "category" => "Asesor De Servicios"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Claridad En El Mensaje",
        "category" => "Asesor De Servicios"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Escucha",
        "category" => "Asesor De Servicios"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Respuesta Oportuna",
        "category" => "Asesor De Servicios"
      ],
      [
        "competence" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "subCompetence" => "Gestión De Recursos",
        "category" => "Asesor De Servicios"
      ],
      [
        "competence" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "subCompetence" => "Metodología",
        "category" => "Asesor De Servicios"
      ],
      [
        "competence" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "subCompetence" => "Orientación Al Logro",
        "category" => "Asesor De Servicios"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Cumplimiento De Procedimientos",
        "category" => "Asesor De Servicios"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Organización De Información",
        "category" => "Asesor De Servicios"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Orientación Al Registro Detallado",
        "category" => "Asesor De Servicios"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Amabilidad Y Cercanía",
        "category" => "Asesor De Servicios"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Atención Efectiva A Consultas",
        "category" => "Asesor De Servicios"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Atención Presencial Oportuna",
        "category" => "Asesor De Servicios"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Seguimiento A La Atención",
        "category" => "Asesor De Servicios"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Asertividad",
        "category" => "Auxiliar De Lavado Y Limpieza"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Claridad En El Mensaje",
        "category" => "Auxiliar De Lavado Y Limpieza"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Escucha",
        "category" => "Auxiliar De Lavado Y Limpieza"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Respuesta Oportuna",
        "category" => "Auxiliar De Lavado Y Limpieza"
      ],
      [
        "competence" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "subCompetence" => "Gestión De Recursos",
        "category" => "Auxiliar De Lavado Y Limpieza"
      ],
      [
        "competence" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "subCompetence" => "Metodología",
        "category" => "Auxiliar De Lavado Y Limpieza"
      ],
      [
        "competence" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "subCompetence" => "Orientación Al Logro",
        "category" => "Auxiliar De Lavado Y Limpieza"
      ],
      [
        "competence" => "Organización y Control: Garantizar el uso de recursos a través de una adecuada organización, clasificación y control.",
        "subCompetence" => "Control De Recursos",
        "category" => "Auxiliar De Lavado Y Limpieza"
      ],
      [
        "competence" => "Organización y Control: Garantizar el uso de recursos a través de una adecuada organización, clasificación y control.",
        "subCompetence" => "Organización De Recursos Internos",
        "category" => "Auxiliar De Lavado Y Limpieza"
      ],
      [
        "competence" => "Organización y Control: Garantizar el uso de recursos a través de una adecuada organización, clasificación y control.",
        "subCompetence" => "Protección De Recursos De La Organización",
        "category" => "Auxiliar De Lavado Y Limpieza"
      ],
      [
        "competence" => "Mentalidad de Dueño V2: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Anticipación A Requerimientos",
        "category" => "Auxiliar De Limpieza Y Conserjeria"
      ],
      [
        "competence" => "Mentalidad de Dueño V2: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Pertenencia",
        "category" => "Auxiliar De Limpieza Y Conserjeria"
      ],
      [
        "competence" => "Mentalidad de Dueño V2: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Solución De Problemas Difíciles",
        "category" => "Auxiliar De Limpieza Y Conserjeria"
      ],
      [
        "competence" => "Organización y Control: Garantizar el uso de recursos a través de una adecuada organización, clasificación y control.",
        "subCompetence" => "Control De Recursos",
        "category" => "Auxiliar De Limpieza Y Conserjeria"
      ],
      [
        "competence" => "Organización y Control: Garantizar el uso de recursos a través de una adecuada organización, clasificación y control.",
        "subCompetence" => "Organización De Recursos Internos",
        "category" => "Auxiliar De Limpieza Y Conserjeria"
      ],
      [
        "competence" => "Organización y Control: Garantizar el uso de recursos a través de una adecuada organización, clasificación y control.",
        "subCompetence" => "Protección De Recursos De La Organización",
        "category" => "Auxiliar De Limpieza Y Conserjeria"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Seguimiento A La Atención",
        "category" => "Auxiliar De Limpieza Y Conserjeria"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Amabilidad Y Cercanía Con El Colaborador",
        "category" => "Auxiliar De Limpieza Y Conserjeria"
      ],
      [
        "competence" => "Calidad de Servicio: Supervisa la calidad de los servicios y la satisfacción de los clientes. Interviene para que la calidad y satisfacción se mantengan dentro de lo esperado.",
        "subCompetence" => "Supervisión Del Servicio",
        "category" => "Jefe De Taller"
      ],
      [
        "competence" => "Calidad de Servicio: Supervisa la calidad de los servicios y la satisfacción de los clientes. Interviene para que la calidad y satisfacción se mantengan dentro de lo esperado.",
        "subCompetence" => "Reporte De Calidad",
        "category" => "Jefe De Taller"
      ],
      [
        "competence" => "Calidad de Servicio: Supervisa la calidad de los servicios y la satisfacción de los clientes. Interviene para que la calidad y satisfacción se mantengan dentro de lo esperado.",
        "subCompetence" => "Monitoreo De Satisfacción",
        "category" => "Jefe De Taller"
      ],
      [
        "competence" => "Calidad de Servicio: Supervisa la calidad de los servicios y la satisfacción de los clientes. Interviene para que la calidad y satisfacción se mantengan dentro de lo esperado.",
        "subCompetence" => "Gestión De Consecuencias Del Servicio",
        "category" => "Jefe De Taller"
      ],
      [
        "competence" => "Calidad de Servicio: Supervisa la calidad de los servicios y la satisfacción de los clientes. Interviene para que la calidad y satisfacción se mantengan dentro de lo esperado.",
        "subCompetence" => "Coordinación Efectiva",
        "category" => "Jefe De Taller"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Asertividad",
        "category" => "Jefe De Taller"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Respuesta Oportuna",
        "category" => "Jefe De Taller"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Escucha",
        "category" => "Jefe De Taller"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Claridad En El Mensaje",
        "category" => "Jefe De Taller"
      ],
      [
        "competence" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "subCompetence" => "Gestión De Consecuencias",
        "category" => "Jefe De Taller"
      ],
      [
        "competence" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "subCompetence" => "Control",
        "category" => "Jefe De Taller"
      ],
      [
        "competence" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "subCompetence" => "Retroalimentación",
        "category" => "Jefe De Taller"
      ],
      [
        "competence" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "subCompetence" => "Seguimiento",
        "category" => "Jefe De Taller"
      ],
      [
        "competence" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "subCompetence" => "Supervisión",
        "category" => "Jefe De Taller"
      ],
      [
        "competence" => "Operación Efectiva: Garantiza una operación efectiva y rentable desde la llegada hasta la entrega del vehículo.",
        "subCompetence" => "Cuidado Del Producto",
        "category" => "Jefe De Taller"
      ],
      [
        "competence" => "Operación Efectiva: Garantiza una operación efectiva y rentable desde la llegada hasta la entrega del vehículo.",
        "subCompetence" => "Control De Calidad En La Operación",
        "category" => "Jefe De Taller"
      ],
      [
        "competence" => "Operación Efectiva: Garantiza una operación efectiva y rentable desde la llegada hasta la entrega del vehículo.",
        "subCompetence" => "Rentabilidad",
        "category" => "Jefe De Taller"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Atención Presencial Oportuna",
        "category" => "Jefe De Taller"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Atención Efectiva A Consultas",
        "category" => "Jefe De Taller"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Seguimiento A La Atención",
        "category" => "Jefe De Taller"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Amabilidad Y Cercanía",
        "category" => "Jefe De Taller"
      ],
      [
        "competence" => "Calidad de Servicio: Supervisa la calidad de los servicios y la satisfacción de los clientes. Interviene para que la calidad y satisfacción se mantengan dentro de lo esperado.",
        "subCompetence" => "Coordinación Efectiva",
        "category" => "Coordinador De Taller"
      ],
      [
        "competence" => "Calidad de Servicio: Supervisa la calidad de los servicios y la satisfacción de los clientes. Interviene para que la calidad y satisfacción se mantengan dentro de lo esperado.",
        "subCompetence" => "Supervisión Del Servicio",
        "category" => "Coordinador De Taller"
      ],
      [
        "competence" => "Calidad de Servicio: Supervisa la calidad de los servicios y la satisfacción de los clientes. Interviene para que la calidad y satisfacción se mantengan dentro de lo esperado.",
        "subCompetence" => "Monitoreo De Satisfacción",
        "category" => "Coordinador De Taller"
      ],
      [
        "competence" => "Calidad de Servicio: Supervisa la calidad de los servicios y la satisfacción de los clientes. Interviene para que la calidad y satisfacción se mantengan dentro de lo esperado.",
        "subCompetence" => "Reporte De Calidad",
        "category" => "Coordinador De Taller"
      ],
      [
        "competence" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "subCompetence" => "Control",
        "category" => "Coordinador De Taller"
      ],
      [
        "competence" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "subCompetence" => "Gestión De Consecuencias",
        "category" => "Coordinador De Taller"
      ],
      [
        "competence" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "subCompetence" => "Supervisión",
        "category" => "Coordinador De Taller"
      ],
      [
        "competence" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "subCompetence" => "Seguimiento",
        "category" => "Coordinador De Taller"
      ],
      [
        "competence" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "subCompetence" => "Retroalimentación",
        "category" => "Coordinador De Taller"
      ],
      [
        "competence" => "Gestión de Mercadería, Equipos y Herramientas: Garantizar la cantidad de recursos que necesita la organización a través de una adecuada organización, clasificación y control.",
        "subCompetence" => "Control De Movimientos De Mercadería",
        "category" => "Coordinador De Taller"
      ],
      [
        "competence" => "Gestión de Mercadería, Equipos y Herramientas: Garantizar la cantidad de recursos que necesita la organización a través de una adecuada organización, clasificación y control.",
        "subCompetence" => "Organización De Mercadería",
        "category" => "Coordinador De Taller"
      ],
      [
        "competence" => "Gestión de Mercadería, Equipos y Herramientas: Garantizar la cantidad de recursos que necesita la organización a través de una adecuada organización, clasificación y control.",
        "subCompetence" => "Protección De Mercadería",
        "category" => "Coordinador De Taller"
      ],
      [
        "competence" => "Gestión de Mercadería, Equipos y Herramientas: Garantizar la cantidad de recursos que necesita la organización a través de una adecuada organización, clasificación y control.",
        "subCompetence" => "Reclamos De Ingreso De Mercadería",
        "category" => "Coordinador De Taller"
      ],
      [
        "competence" => "Habilidad de Enseñanza: Logra explicar con claridad y paciencia el funcionamiento de la operación, brinda retroalmentación clara para incorporar los procedimientos e instrucciones.",
        "subCompetence" => "Tolerancia En La Enseñanza",
        "category" => "Coordinador De Taller"
      ],
      [
        "competence" => "Habilidad de Enseñanza: Logra explicar con claridad y paciencia el funcionamiento de la operación, brinda retroalmentación clara para incorporar los procedimientos e instrucciones.",
        "subCompetence" => "Capacidad Para Explicar",
        "category" => "Coordinador De Taller"
      ],
      [
        "competence" => "Habilidad de Enseñanza: Logra explicar con claridad y paciencia el funcionamiento de la operación, brinda retroalmentación clara para incorporar los procedimientos e instrucciones.",
        "subCompetence" => "Retroalimentación En La Enseñanza",
        "category" => "Coordinador De Taller"
      ],
      [
        "competence" => "Habilidad de Enseñanza: Logra explicar con claridad y paciencia el funcionamiento de la operación, brinda retroalmentación clara para incorporar los procedimientos e instrucciones.",
        "subCompetence" => "Evaluación",
        "category" => "Coordinador De Taller"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Orientación Al Registro Detallado",
        "category" => "Coordinador De Taller"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Organización De Información",
        "category" => "Coordinador De Taller"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Cumplimiento De Procedimientos",
        "category" => "Coordinador De Taller"
      ],
      [
        "competence" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "subCompetence" => "Gestión De Recursos",
        "category" => "Tecnico Mecanico Ap"
      ],
      [
        "competence" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "subCompetence" => "Metodología",
        "category" => "Tecnico Mecanico Ap"
      ],
      [
        "competence" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "subCompetence" => "Orientación Al Logro",
        "category" => "Tecnico Mecanico Ap"
      ],
      [
        "competence" => "Habilidad de Enseñanza: Logra explicar con claridad y paciencia el funcionamiento de la operación, brinda retroalmentación clara para incorporar los procedimientos e instrucciones.",
        "subCompetence" => "Capacidad Para Explicar",
        "category" => "Tecnico Mecanico Ap"
      ],
      [
        "competence" => "Habilidad de Enseñanza: Logra explicar con claridad y paciencia el funcionamiento de la operación, brinda retroalmentación clara para incorporar los procedimientos e instrucciones.",
        "subCompetence" => "Evaluación",
        "category" => "Tecnico Mecanico Ap"
      ],
      [
        "competence" => "Habilidad de Enseñanza: Logra explicar con claridad y paciencia el funcionamiento de la operación, brinda retroalmentación clara para incorporar los procedimientos e instrucciones.",
        "subCompetence" => "Retroalimentación En La Enseñanza",
        "category" => "Tecnico Mecanico Ap"
      ],
      [
        "competence" => "Habilidad de Enseñanza: Logra explicar con claridad y paciencia el funcionamiento de la operación, brinda retroalmentación clara para incorporar los procedimientos e instrucciones.",
        "subCompetence" => "Tolerancia En La Enseñanza",
        "category" => "Tecnico Mecanico Ap"
      ],
      [
        "competence" => "Habilidad para reparar: Soluciona las fallas técnicas con rapidez y efectividad, identifica las causas de manera oportuna y verifica el correcto funcionamiento una vez reparado.",
        "subCompetence" => "Control De Calidad En La Reparación",
        "category" => "Tecnico Mecanico Ap"
      ],
      [
        "competence" => "Habilidad para reparar: Soluciona las fallas técnicas con rapidez y efectividad, identifica las causas de manera oportuna y verifica el correcto funcionamiento una vez reparado.",
        "subCompetence" => "Diagnóstico",
        "category" => "Tecnico Mecanico Ap"
      ],
      [
        "competence" => "Habilidad para reparar: Soluciona las fallas técnicas con rapidez y efectividad, identifica las causas de manera oportuna y verifica el correcto funcionamiento una vez reparado.",
        "subCompetence" => "Reparación",
        "category" => "Tecnico Mecanico Ap"
      ],
      [
        "competence" => "Organización y Control: Garantizar el uso de recursos a través de una adecuada organización, clasificación y control.",
        "subCompetence" => "Control De Recursos",
        "category" => "Tecnico Mecanico Ap"
      ],
      [
        "competence" => "Organización y Control: Garantizar el uso de recursos a través de una adecuada organización, clasificación y control.",
        "subCompetence" => "Organización De Recursos Internos",
        "category" => "Tecnico Mecanico Ap"
      ],
      [
        "competence" => "Organización y Control: Garantizar el uso de recursos a través de una adecuada organización, clasificación y control.",
        "subCompetence" => "Protección De Recursos De La Organización",
        "category" => "Tecnico Mecanico Ap"
      ],
      [
        "competence" => "Protección De Recursos De La Organización",
        "subCompetence" => "Cumplimiento De Procedimientos",
        "category" => "Tecnico Mecanico Ap"
      ],
      [
        "competence" => "Protección De Recursos De La Organización",
        "subCompetence" => "Organización De Información",
        "category" => "Tecnico Mecanico Ap"
      ],
      [
        "competence" => "Protección De Recursos De La Organización",
        "subCompetence" => "Orientación Al Registro Detallado",
        "category" => "Tecnico Mecanico Ap"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Asertividad",
        "category" => "Agente De Seguridad"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Claridad En El Mensaje",
        "category" => "Agente De Seguridad"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Escucha",
        "category" => "Agente De Seguridad"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Informe Escrito",
        "category" => "Agente De Seguridad"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Respuesta Oportuna",
        "category" => "Agente De Seguridad"
      ],
      [
        "competence" => "Gestión de normas y procedimientos: Se preocupa y promueve respeto de las normas y procedimientos. Vigila el cumplimiento de las normas e interviene para corregir faltas.",
        "subCompetence" => "Claridad En La Orientación",
        "category" => "Agente De Seguridad"
      ],
      [
        "competence" => "Gestión de normas y procedimientos: Se preocupa y promueve respeto de las normas y procedimientos. Vigila el cumplimiento de las normas e interviene para corregir faltas.",
        "subCompetence" => "Respeto A La Norma",
        "category" => "Agente De Seguridad"
      ],
      [
        "competence" => "Pensamiento claro y tranquilo: Mantiene la calma necesaria para poder reaccionar de manera correcta y proactiva ante cualquier situacion de riesgo.",
        "subCompetence" => "Calma Ante La Crisis",
        "category" => "Agente De Seguridad"
      ],
      [
        "competence" => "Pensamiento claro y tranquilo: Mantiene la calma necesaria para poder reaccionar de manera correcta y proactiva ante cualquier situacion de riesgo.",
        "subCompetence" => "Respuesta Anticipada",
        "category" => "Agente De Seguridad"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Amabilidad Y Cercanía (seguridad)",
        "category" => "Agente De Seguridad"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Atención Efectiva A Consultas (seguridad)",
        "category" => "Agente De Seguridad"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Atención Presencial Oportuna (seguridad)",
        "category" => "Agente De Seguridad"
      ],
      [
        "competence" => "Cooperación Operativa: Coopera de manera activa con las necesidades del equipo y unidades con las que interactúa. Respeta los acuerdos generando un espacio colaborativo y de confianza.",
        "subCompetence" => "Cooperación",
        "category" => "Codificador De Repuestos"
      ],
      [
        "competence" => "Cooperación Operativa: Coopera de manera activa con las necesidades del equipo y unidades con las que interactúa. Respeta los acuerdos generando un espacio colaborativo y de confianza.",
        "subCompetence" => "Orientación A La Meta Común",
        "category" => "Codificador De Repuestos"
      ],
      [
        "competence" => "Cooperación Operativa: Coopera de manera activa con las necesidades del equipo y unidades con las que interactúa. Respeta los acuerdos generando un espacio colaborativo y de confianza.",
        "subCompetence" => "Relaciones Interpersonales",
        "category" => "Codificador De Repuestos"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Cumplimiento De Procedimientos",
        "category" => "Codificador De Repuestos"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Organización De Información",
        "category" => "Codificador De Repuestos"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Orientación Al Registro Detallado",
        "category" => "Codificador De Repuestos"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Amabilidad Y Cercanía",
        "category" => "Codificador De Repuestos"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Atención Efectiva A Consultas",
        "category" => "Codificador De Repuestos"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Atención Presencial Oportuna",
        "category" => "Codificador De Repuestos"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Seguimiento A La Atención",
        "category" => "Codificador De Repuestos"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Claridad En El Mensaje",
        "category" => "Analista De Contabilidad AP"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Respuesta Oportuna",
        "category" => "Analista De Contabilidad AP"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Escucha",
        "category" => "Analista De Contabilidad AP"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Asertividad",
        "category" => "Analista De Contabilidad AP"
      ],
      [
        "competence" => "Gestión de Trámites: Garantiza la recepción y entrega de documentos asegurando que cumpla con los requisitos solicitados.",
        "subCompetence" => "Despacho Documentario",
        "category" => "Analista De Contabilidad AP"
      ],
      [
        "competence" => "Gestión de Trámites: Garantiza la recepción y entrega de documentos asegurando que cumpla con los requisitos solicitados.",
        "subCompetence" => "Control Documentario",
        "category" => "Analista De Contabilidad AP"
      ],
      [
        "competence" => "Gestión de Trámites: Garantiza la recepción y entrega de documentos asegurando que cumpla con los requisitos solicitados.",
        "subCompetence" => "Perseverancia En La Entrega",
        "category" => "Analista De Contabilidad AP"
      ],
      [
        "competence" => "Gestión de Trámites: Garantiza la recepción y entrega de documentos asegurando que cumpla con los requisitos solicitados.",
        "subCompetence" => "Construcción De Red De Contactos",
        "category" => "Analista De Contabilidad AP"
      ],
      [
        "competence" => "Perspicacia Contable: Garantizar el correcto registro de los ingresos de la organización identificando posibles riesgos y auditando. Actuar con transparencia durante la auditoría.",
        "subCompetence" => "Control De Información Sensible",
        "category" => "Analista De Contabilidad AP"
      ],
      [
        "competence" => "Perspicacia Contable: Garantizar el correcto registro de los ingresos de la organización identificando posibles riesgos y auditando. Actuar con transparencia durante la auditoría.",
        "subCompetence" => "Análisis De Información Contable",
        "category" => "Analista De Contabilidad AP"
      ],
      [
        "competence" => "Perspicacia Contable: Garantizar el correcto registro de los ingresos de la organización identificando posibles riesgos y auditando. Actuar con transparencia durante la auditoría.",
        "subCompetence" => "Congruente",
        "category" => "Analista De Contabilidad AP"
      ],
      [
        "competence" => "Perspicacia Contable: Garantizar el correcto registro de los ingresos de la organización identificando posibles riesgos y auditando. Actuar con transparencia durante la auditoría.",
        "subCompetence" => "Fraudes",
        "category" => "Analista De Contabilidad AP"
      ],
      [
        "competence" => "Perspicacia Contable: Garantizar el correcto registro de los ingresos de la organización identificando posibles riesgos y auditando. Actuar con transparencia durante la auditoría.",
        "subCompetence" => "Auditorías Oportunas",
        "category" => "Analista De Contabilidad AP"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Orientación Al Registro Detallado",
        "category" => "Analista De Contabilidad AP"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Organización De Información",
        "category" => "Analista De Contabilidad AP"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Cumplimiento De Procedimientos",
        "category" => "Analista De Contabilidad AP"
      ],
      [
        "competence" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "subCompetence" => "Colaboración",
        "category" => "Analista De Contabilidad AP"
      ],
      [
        "competence" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "subCompetence" => "Responsabilidad",
        "category" => "Analista De Contabilidad AP"
      ],
      [
        "competence" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "subCompetence" => "Resuelve Conflictos",
        "category" => "Analista De Contabilidad AP"
      ],
      [
        "competence" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "subCompetence" => "Pasión",
        "category" => "Analista De Contabilidad AP"
      ],
      [
        "competence" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "subCompetence" => "Traspase De Información En Equipo",
        "category" => "Analista De Contabilidad AP"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Claridad En El Mensaje",
        "category" => "Asistente De Contabilidad AP"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Respuesta Oportuna",
        "category" => "Asistente De Contabilidad AP"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Escucha",
        "category" => "Asistente De Contabilidad AP"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Asertividad",
        "category" => "Asistente De Contabilidad AP"
      ],
      [
        "competence" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "subCompetence" => "Gestión de Recursos",
        "category" => "Asistente De Contabilidad AP"
      ],
      [
        "competence" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "subCompetence" => "Metodología",
        "category" => "Asistente De Contabilidad AP"
      ],
      [
        "competence" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "subCompetence" => "Orientación al logro",
        "category" => "Asistente De Contabilidad AP"
      ],
      [
        "competence" => "Gestión de Trámites: Garantiza la recepción y entrega de documentos asegurando que cumpla con los requisitos solicitados.",
        "subCompetence" => "Despacho Documentario",
        "category" => "Asistente De Contabilidad AP"
      ],
      [
        "competence" => "Gestión de Trámites: Garantiza la recepción y entrega de documentos asegurando que cumpla con los requisitos solicitados.",
        "subCompetence" => "Control Documentario",
        "category" => "Asistente De Contabilidad AP"
      ],
      [
        "competence" => "Gestión de Trámites: Garantiza la recepción y entrega de documentos asegurando que cumpla con los requisitos solicitados.",
        "subCompetence" => "Perseverancia En La Entrega",
        "category" => "Asistente De Contabilidad AP"
      ],
      [
        "competence" => "Gestión de Trámites: Garantiza la recepción y entrega de documentos asegurando que cumpla con los requisitos solicitados.",
        "subCompetence" => "Construcción De Red De Contactos",
        "category" => "Asistente De Contabilidad AP"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Orientación Al Registro Detallado",
        "category" => "Asistente De Contabilidad AP"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Organización De Información",
        "category" => "Asistente De Contabilidad AP"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Cumplimiento De Procedimientos",
        "category" => "Asistente De Contabilidad AP"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Asertividad",
        "category" => "Asistente Administrativo Ap"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Claridad En El Mensaje",
        "category" => "Asistente Administrativo Ap"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Escucha",
        "category" => "Asistente Administrativo Ap"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Respuesta Oportuna",
        "category" => "Asistente Administrativo Ap"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Cumplimiento De Procedimientos",
        "category" => "Asistente Administrativo Ap"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Organización De Información",
        "category" => "Asistente Administrativo Ap"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Orientación Al Registro Detallado",
        "category" => "Asistente Administrativo Ap"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Amabilidad Y Cercanía",
        "category" => "Asistente Administrativo Ap"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Atención Efectiva A Consultas",
        "category" => "Asistente Administrativo Ap"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Atención Presencial Oportuna",
        "category" => "Asistente Administrativo Ap"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Seguimiento A La Atención",
        "category" => "Asistente Administrativo Ap"
      ],
      [
        "competence" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "subCompetence" => "Colaboración",
        "category" => "Asistente Administrativo Ap"
      ],
      [
        "competence" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "subCompetence" => "Pasión",
        "category" => "Asistente Administrativo Ap"
      ],
      [
        "competence" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "subCompetence" => "Responsabilidad",
        "category" => "Asistente Administrativo Ap"
      ],
      [
        "competence" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "subCompetence" => "Resuelve Conflictos",
        "category" => "Asistente Administrativo Ap"
      ],
      [
        "competence" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "subCompetence" => "Traspase De Información En Equipo",
        "category" => "Asistente Administrativo Ap"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Asertividad",
        "category" => "Asistente De Post Venta"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Claridad En El Mensaje",
        "category" => "Asistente De Post Venta"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Escucha",
        "category" => "Asistente De Post Venta"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Respuesta Oportuna",
        "category" => "Asistente De Post Venta"
      ],
      [
        "competence" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "subCompetence" => "Gestión De Recursos",
        "category" => "Asistente De Post Venta"
      ],
      [
        "competence" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "subCompetence" => "Metodología",
        "category" => "Asistente De Post Venta"
      ],
      [
        "competence" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "subCompetence" => "Orientación Al Logro",
        "category" => "Asistente De Post Venta"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Cumplimiento De Procedimientos",
        "category" => "Asistente De Post Venta"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Organización De Información",
        "category" => "Asistente De Post Venta"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Orientación Al Registro Detallado",
        "category" => "Asistente De Post Venta"
      ],
      [
        "competence" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "subCompetence" => "Colaboración",
        "category" => "Asistente De Post Venta"
      ],
      [
        "competence" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "subCompetence" => "Pasión",
        "category" => "Asistente De Post Venta"
      ],
      [
        "competence" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "subCompetence" => "Responsabilidad",
        "category" => "Asistente De Post Venta"
      ],
      [
        "competence" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "subCompetence" => "Resuelve Conflictos",
        "category" => "Asistente De Post Venta"
      ],
      [
        "competence" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "subCompetence" => "Traspase De Información En Equipo",
        "category" => "Asistente De Post Venta"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Asertividad",
        "category" => "Asistente De Vehiculos (pdi)"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Claridad En El Mensaje",
        "category" => "Asistente De Vehiculos (pdi)"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Escucha",
        "category" => "Asistente De Vehiculos (pdi)"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Respuesta Oportuna",
        "category" => "Asistente De Vehiculos (pdi)"
      ],
      [
        "competence" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "subCompetence" => "Gestión De Recursos",
        "category" => "Asistente De Vehiculos (pdi)"
      ],
      [
        "competence" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "subCompetence" => "Metodología",
        "category" => "Asistente De Vehiculos (pdi)"
      ],
      [
        "competence" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "subCompetence" => "Orientación Al Logro",
        "category" => "Asistente De Vehiculos (pdi)"
      ],
      [
        "competence" => "Mentalidad de Dueño V2: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Anticipación A Requerimientos",
        "category" => "Asistente De Vehiculos (pdi)"
      ],
      [
        "competence" => "Mentalidad de Dueño V2: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Pertenencia",
        "category" => "Asistente De Vehiculos (pdi)"
      ],
      [
        "competence" => "Mentalidad de Dueño V2: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Solución De Problemas Difíciles",
        "category" => "Asistente De Vehiculos (pdi)"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Cumplimiento De Procedimientos",
        "category" => "Asistente De Vehiculos (pdi)"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Organización De Información",
        "category" => "Asistente De Vehiculos (pdi)"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Orientación Al Registro Detallado",
        "category" => "Asistente De Vehiculos (pdi)"
      ],
      [
        "competence" => "Gestión de Campañas y Eventos de Marketing: Define, desarrolla e implementa Campañas y Eventos de acuerdo al plan de marketing e imagen corporativa.",
        "subCompetence" => "Definición De Campañas Y Eventos",
        "category" => "Ejecutivo De Trade Marketing"
      ],
      [
        "competence" => "Gestión de Campañas y Eventos de Marketing: Define, desarrolla e implementa Campañas y Eventos de acuerdo al plan de marketing e imagen corporativa.",
        "subCompetence" => "Creación De Campañas Y Eventos",
        "category" => "Ejecutivo De Trade Marketing"
      ],
      [
        "competence" => "Gestión de Campañas y Eventos de Marketing: Define, desarrolla e implementa Campañas y Eventos de acuerdo al plan de marketing e imagen corporativa.",
        "subCompetence" => "Implementación De Campañas Y Eventos",
        "category" => "Ejecutivo De Trade Marketing"
      ],
      [
        "competence" => "Gestión de Campañas y Eventos de Marketing: Define, desarrolla e implementa Campañas y Eventos de acuerdo al plan de marketing e imagen corporativa.",
        "subCompetence" => "Evaluación De Campañas Y Eventos",
        "category" => "Ejecutivo De Trade Marketing"
      ],
      [
        "competence" => "Gestión de Proveedores - Admin: Controlar el cumplimiento de los acuerdos del proveedor, interviene ante los problemas y reclamos en la entrega de mercadería.",
        "subCompetence" => "Identificación",
        "category" => "Ejecutivo De Trade Marketing"
      ],
      [
        "competence" => "Gestión de Proveedores - Admin: Controlar el cumplimiento de los acuerdos del proveedor, interviene ante los problemas y reclamos en la entrega de mercadería.",
        "subCompetence" => "Seguimiento A Proveedores",
        "category" => "Ejecutivo De Trade Marketing"
      ],
      [
        "competence" => "Gestión de Proveedores - Admin: Controlar el cumplimiento de los acuerdos del proveedor, interviene ante los problemas y reclamos en la entrega de mercadería.",
        "subCompetence" => "Solución De Problemas Con Proveedores",
        "category" => "Ejecutivo De Trade Marketing"
      ],
      [
        "competence" => "Gestión de Proveedores - Admin: Controlar el cumplimiento de los acuerdos del proveedor, interviene ante los problemas y reclamos en la entrega de mercadería.",
        "subCompetence" => "Manejo De Reclamos",
        "category" => "Ejecutivo De Trade Marketing"
      ],
      [
        "competence" => "Supervisión de Piezas Comunicacionales: Define y coordinar el desarrollo de piezas comunicacionales de acuerdo a los planes, campañas y eventos a realizar.",
        "subCompetence" => "Definir Pieza Comunicacional",
        "category" => "Ejecutivo De Trade Marketing"
      ],
      [
        "competence" => "Supervisión de Piezas Comunicacionales: Define y coordinar el desarrollo de piezas comunicacionales de acuerdo a los planes, campañas y eventos a realizar.",
        "subCompetence" => "Claridad En La Pieza",
        "category" => "Ejecutivo De Trade Marketing"
      ],
      [
        "competence" => "Supervisión de Piezas Comunicacionales: Define y coordinar el desarrollo de piezas comunicacionales de acuerdo a los planes, campañas y eventos a realizar.",
        "subCompetence" => "Supervisión Diseño Creativo",
        "category" => "Ejecutivo De Trade Marketing"
      ],
      [
        "competence" => "Supervisión de Piezas Comunicacionales: Define y coordinar el desarrollo de piezas comunicacionales de acuerdo a los planes, campañas y eventos a realizar.",
        "subCompetence" => "Identidad Visual De La Marca",
        "category" => "Ejecutivo De Trade Marketing"
      ],
      [
        "competence" => "Supervisión de Producción de Eventos: Asegura que la ejecución del evento sea óptima y eficiente controlando todas las variables y asgurando la correcta ejecución.",
        "subCompetence" => "Supervisión De Despliegue",
        "category" => "Ejecutivo De Trade Marketing"
      ],
      [
        "competence" => "Supervisión de Producción de Eventos: Asegura que la ejecución del evento sea óptima y eficiente controlando todas las variables y asgurando la correcta ejecución.",
        "subCompetence" => "Control De Presupuesto",
        "category" => "Ejecutivo De Trade Marketing"
      ],
      [
        "competence" => "Supervisión de Producción de Eventos: Asegura que la ejecución del evento sea óptima y eficiente controlando todas las variables y asgurando la correcta ejecución.",
        "subCompetence" => "Calidad De Producción",
        "category" => "Ejecutivo De Trade Marketing"
      ],
      [
        "competence" => "Supervisión de Producción de Eventos: Asegura que la ejecución del evento sea óptima y eficiente controlando todas las variables y asgurando la correcta ejecución.",
        "subCompetence" => "Solución De Problemas En Eventos",
        "category" => "Ejecutivo De Trade Marketing"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Asertividad",
        "category" => "Gerente De Negocio Ap"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Respuesta Oportuna",
        "category" => "Gerente De Negocio Ap"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Claridad En El Mensaje",
        "category" => "Gerente De Negocio Ap"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Escucha",
        "category" => "Gerente De Negocio Ap"
      ],
      [
        "competence" => "Dirección de Negocio: Dirige el Plan de Negocio de manera efectiva controlando y evaluando el despliegue e interviniendo con ajustes oportunos.",
        "subCompetence" => "Ajustes Oportunos",
        "category" => "Gerente De Negocio Ap"
      ],
      [
        "competence" => "Dirección de Negocio: Dirige el Plan de Negocio de manera efectiva controlando y evaluando el despliegue e interviniendo con ajustes oportunos.",
        "subCompetence" => "Controla",
        "category" => "Gerente De Negocio Ap"
      ],
      [
        "competence" => "Dirección de Negocio: Dirige el Plan de Negocio de manera efectiva controlando y evaluando el despliegue e interviniendo con ajustes oportunos.",
        "subCompetence" => "Evalúa",
        "category" => "Gerente De Negocio Ap"
      ],
      [
        "competence" => "Dirección de Negocio: Dirige el Plan de Negocio de manera efectiva controlando y evaluando el despliegue e interviniendo con ajustes oportunos.",
        "subCompetence" => "Dirige",
        "category" => "Gerente De Negocio Ap"
      ],
      [
        "competence" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "subCompetence" => "Alineamiento",
        "category" => "Gerente De Negocio Ap"
      ],
      [
        "competence" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "subCompetence" => "Resolución De Conflictos",
        "category" => "Gerente De Negocio Ap"
      ],
      [
        "competence" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "subCompetence" => "Dirección De Equipos",
        "category" => "Gerente De Negocio Ap"
      ],
      [
        "competence" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "subCompetence" => "Convicción",
        "category" => "Gerente De Negocio Ap"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Cumplimiento De Acuerdos",
        "category" => "Gerente De Negocio Ap"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Proactividad",
        "category" => "Gerente De Negocio Ap"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Asumo Las Consecuencias",
        "category" => "Gerente De Negocio Ap"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Resultados",
        "category" => "Gerente De Negocio Ap"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Solución De Problema",
        "category" => "Gerente De Negocio Ap"
      ],
      [
        "competence" => "Socio Estratégico: Establece relaciones de alto valor para la organización, viendo las oportunidades en el mercado y tomando decisiones estratégicas de manera íntegra.",
        "subCompetence" => "Integridad",
        "category" => "Gerente De Negocio Ap"
      ],
      [
        "competence" => "Socio Estratégico: Establece relaciones de alto valor para la organización, viendo las oportunidades en el mercado y tomando decisiones estratégicas de manera íntegra.",
        "subCompetence" => "Pensamiento Estratégico Del Negocio",
        "category" => "Gerente De Negocio Ap"
      ],
      [
        "competence" => "Socio Estratégico: Establece relaciones de alto valor para la organización, viendo las oportunidades en el mercado y tomando decisiones estratégicas de manera íntegra.",
        "subCompetence" => "Construcción De Relaciones",
        "category" => "Gerente De Negocio Ap"
      ],
      [
        "competence" => "Socio Estratégico: Establece relaciones de alto valor para la organización, viendo las oportunidades en el mercado y tomando decisiones estratégicas de manera íntegra.",
        "subCompetence" => "Toma De Decisiones",
        "category" => "Gerente De Negocio Ap"
      ],
      [
        "competence" => "Socio Estratégico: Establece relaciones de alto valor para la organización, viendo las oportunidades en el mercado y tomando decisiones estratégicas de manera íntegra.",
        "subCompetence" => "Visión De Negocio",
        "category" => "Gerente De Negocio Ap"
      ],
      [
        "competence" => "Gestión de Marketing: Define las estrategias de marketing, analiza el funcionamiento del mercado para definir las acciones adecuadas.",
        "subCompetence" => "Análisis De Mercado",
        "category" => "Jefe De Marketing"
      ],
      [
        "competence" => "Gestión de Marketing: Define las estrategias de marketing, analiza el funcionamiento del mercado para definir las acciones adecuadas.",
        "subCompetence" => "Evaluación De La Demanda",
        "category" => "Jefe De Marketing"
      ],
      [
        "competence" => "Gestión de Marketing: Define las estrategias de marketing, analiza el funcionamiento del mercado para definir las acciones adecuadas.",
        "subCompetence" => "Definición Del Precio De Venta",
        "category" => "Jefe De Marketing"
      ],
      [
        "competence" => "Gestión de Marketing: Define las estrategias de marketing, analiza el funcionamiento del mercado para definir las acciones adecuadas.",
        "subCompetence" => "Definición De Estrategias Y Objetivos",
        "category" => "Jefe De Marketing"
      ],
      [
        "competence" => "Gestión de Presupuesto: Define el presupuesto de inversión adecuado considerando los costos y la rentabilidad del negocio.",
        "subCompetence" => "Ajustes Comerciales",
        "category" => "Jefe De Marketing"
      ],
      [
        "competence" => "Gestión de Presupuesto: Define el presupuesto de inversión adecuado considerando los costos y la rentabilidad del negocio.",
        "subCompetence" => "Control Y Monitoreo Comercial",
        "category" => "Jefe De Marketing"
      ],
      [
        "competence" => "Gestión de Presupuesto: Define el presupuesto de inversión adecuado considerando los costos y la rentabilidad del negocio.",
        "subCompetence" => "Definición Presupuestal",
        "category" => "Jefe De Marketing"
      ],
      [
        "competence" => "Gestión de Presupuesto: Define el presupuesto de inversión adecuado considerando los costos y la rentabilidad del negocio.",
        "subCompetence" => "Evaluación De Estructura De Costos Comerciales",
        "category" => "Jefe De Marketing"
      ],
      [
        "competence" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "subCompetence" => "Dirección De Equipos",
        "category" => "Jefe De Marketing"
      ],
      [
        "competence" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "subCompetence" => "Resolución De Conflictos",
        "category" => "Jefe De Marketing"
      ],
      [
        "competence" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "subCompetence" => "Convicción",
        "category" => "Jefe De Marketing"
      ],
      [
        "competence" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "subCompetence" => "Alineamiento",
        "category" => "Jefe De Marketing"
      ],
      [
        "competence" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "subCompetence" => "Cumplimiento De Acuerdos",
        "category" => "Jefe De Marketing"
      ],
      [
        "competence" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "subCompetence" => "Solución De Problema",
        "category" => "Jefe De Marketing"
      ],
      [
        "competence" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "subCompetence" => "Asumo Las Consecuencias",
        "category" => "Jefe De Marketing"
      ],
      [
        "competence" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "subCompetence" => "Resultados",
        "category" => "Jefe De Marketing"
      ],
      [
        "competence" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "subCompetence" => "Proactividad",
        "category" => "Jefe De Marketing"
      ],
      [
        "competence" => "Planificación: Determinar las metas y prioridades de los proyectos, estableciendo las acciones, los plazos y los recursos necesarios. Así como los instrumentos de medición y segumiento.",
        "subCompetence" => "Creación De Planes",
        "category" => "Jefe De Marketing"
      ],
      [
        "competence" => "Planificación: Determinar las metas y prioridades de los proyectos, estableciendo las acciones, los plazos y los recursos necesarios. Así como los instrumentos de medición y segumiento.",
        "subCompetence" => "Priorización",
        "category" => "Jefe De Marketing"
      ],
      [
        "competence" => "Planificación: Determinar las metas y prioridades de los proyectos, estableciendo las acciones, los plazos y los recursos necesarios. Así como los instrumentos de medición y segumiento.",
        "subCompetence" => "Definir Metas E Indciadores",
        "category" => "Jefe De Marketing"
      ],
      [
        "competence" => "Planificación: Determinar las metas y prioridades de los proyectos, estableciendo las acciones, los plazos y los recursos necesarios. Así como los instrumentos de medición y segumiento.",
        "subCompetence" => "Planificación De Los Recursos",
        "category" => "Jefe De Marketing"
      ],
      [
        "competence" => "Calidad de Servicio: Supervisa la calidad de los servicios y la satisfacción de los clientes. Interviene para que la calidad y satisfacción se mantengan dentro de lo esperado.",
        "subCompetence" => "Supervisión Del Servicio",
        "category" => "Coordinador De Post Venta - Almacen"
      ],
      [
        "competence" => "Calidad de Servicio: Supervisa la calidad de los servicios y la satisfacción de los clientes. Interviene para que la calidad y satisfacción se mantengan dentro de lo esperado.",
        "subCompetence" => "Coordinación Efectiva",
        "category" => "Coordinador De Post Venta - Almacen"
      ],
      [
        "competence" => "Calidad de Servicio: Supervisa la calidad de los servicios y la satisfacción de los clientes. Interviene para que la calidad y satisfacción se mantengan dentro de lo esperado.",
        "subCompetence" => "Reporte De Calidad",
        "category" => "Coordinador De Post Venta - Almacen"
      ],
      [
        "competence" => "Calidad de Servicio: Supervisa la calidad de los servicios y la satisfacción de los clientes. Interviene para que la calidad y satisfacción se mantengan dentro de lo esperado.",
        "subCompetence" => "Monitoreo De Satisfacción",
        "category" => "Coordinador De Post Venta - Almacen"
      ],
      [
        "competence" => "Calidad de Servicio: Supervisa la calidad de los servicios y la satisfacción de los clientes. Interviene para que la calidad y satisfacción se mantengan dentro de lo esperado.",
        "subCompetence" => "Gestión De Consecuencias Del Servicio",
        "category" => "Coordinador De Post Venta - Almacen"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Asertividad",
        "category" => "Coordinador De Post Venta - Almacen"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Respuesta Oportuna",
        "category" => "Coordinador De Post Venta - Almacen"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Escucha",
        "category" => "Coordinador De Post Venta - Almacen"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Claridad En El Mensaje",
        "category" => "Coordinador De Post Venta - Almacen"
      ],
      [
        "competence" => "Gestión de Proveedores - Admin: Controlar el cumplimiento de los acuerdos del proveedor, interviene ante los problemas y reclamos en la entrega de mercadería.",
        "subCompetence" => "Identificación",
        "category" => "Coordinador De Post Venta - Almacen"
      ],
      [
        "competence" => "Gestión de Proveedores - Admin: Controlar el cumplimiento de los acuerdos del proveedor, interviene ante los problemas y reclamos en la entrega de mercadería.",
        "subCompetence" => "Manejo De Reclamos",
        "category" => "Coordinador De Post Venta - Almacen"
      ],
      [
        "competence" => "Gestión de Proveedores - Admin: Controlar el cumplimiento de los acuerdos del proveedor, interviene ante los problemas y reclamos en la entrega de mercadería.",
        "subCompetence" => "Seguimiento A Proveedores",
        "category" => "Coordinador De Post Venta - Almacen"
      ],
      [
        "competence" => "Gestión de Proveedores - Admin: Controlar el cumplimiento de los acuerdos del proveedor, interviene ante los problemas y reclamos en la entrega de mercadería.",
        "subCompetence" => "Solución De Problemas Con Proveedores",
        "category" => "Coordinador De Post Venta - Almacen"
      ],
      [
        "competence" => "Gestión Financiera - Post Venta: Realiza una gestión financiera relacionada a la post-venta para contar con información relevante que ayude a tomar decisiones de manera oportuna.",
        "subCompetence" => "Estructuración De Precios",
        "category" => "Coordinador De Post Venta - Almacen"
      ],
      [
        "competence" => "Gestión Financiera - Post Venta: Realiza una gestión financiera relacionada a la post-venta para contar con información relevante que ayude a tomar decisiones de manera oportuna.",
        "subCompetence" => "Gestión De Garantías",
        "category" => "Coordinador De Post Venta - Almacen"
      ],
      [
        "competence" => "Gestión Financiera - Post Venta: Realiza una gestión financiera relacionada a la post-venta para contar con información relevante que ayude a tomar decisiones de manera oportuna.",
        "subCompetence" => "Reporte De Indicadores Y Recursos",
        "category" => "Coordinador De Post Venta - Almacen"
      ],
      [
        "competence" => "Gestión Financiera - Post Venta: Realiza una gestión financiera relacionada a la post-venta para contar con información relevante que ayude a tomar decisiones de manera oportuna.",
        "subCompetence" => "Segumineto A Cobranzas",
        "category" => "Coordinador De Post Venta - Almacen"
      ],
      [
        "competence" => "Gestión Financiera - Post Venta: Realiza una gestión financiera relacionada a la post-venta para contar con información relevante que ayude a tomar decisiones de manera oportuna.",
        "subCompetence" => "Actualización De Indicadores Y Recursos",
        "category" => "Coordinador De Post Venta - Almacen"
      ],
      [
        "competence" => "Registro y Manejo de Información - Versión 2 AP: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Organización",
        "category" => "Coordinador De Post Venta - Almacen"
      ],
      [
        "competence" => "Registro y Manejo de Información - Versión 2 AP: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Orientación Al Detalle",
        "category" => "Coordinador De Post Venta - Almacen"
      ],
      [
        "competence" => "Registro y Manejo de Información - Versión 2 AP: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Cumplimiento De Procesos Operativos",
        "category" => "Coordinador De Post Venta - Almacen"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Asertividad",
        "category" => "Coordinador De Post Venta"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Respuesta Oportuna",
        "category" => "Coordinador De Post Venta"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Escucha",
        "category" => "Coordinador De Post Venta"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Claridad En El Mensaje",
        "category" => "Coordinador De Post Venta"
      ],
      [
        "competence" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "subCompetence" => "Control",
        "category" => "Coordinador De Post Venta"
      ],
      [
        "competence" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "subCompetence" => "Gestión De Consecuencias",
        "category" => "Coordinador De Post Venta"
      ],
      [
        "competence" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "subCompetence" => "Retroalimentación",
        "category" => "Coordinador De Post Venta"
      ],
      [
        "competence" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "subCompetence" => "Seguimiento",
        "category" => "Coordinador De Post Venta"
      ],
      [
        "competence" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "subCompetence" => "Supervisión",
        "category" => "Coordinador De Post Venta"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Cumplimiento De Procedimientos",
        "category" => "Coordinador De Post Venta"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Organización De Información",
        "category" => "Coordinador De Post Venta"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Orientación Al Registro Detallado",
        "category" => "Coordinador De Post Venta"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Amabilidad Y Cercanía",
        "category" => "Coordinador De Post Venta"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Atención Efectiva A Consultas",
        "category" => "Coordinador De Post Venta"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Atención Presencial Oportuna",
        "category" => "Coordinador De Post Venta"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Seguimiento A La Atención",
        "category" => "Coordinador De Post Venta"
      ],
      [
        "competence" => "Gestión Financiera - Post Venta: Realiza una gestión financiera relacionada a la post-venta para contar con información relevante que ayude a tomar decisiones de manera oportuna.",
        "subCompetence" => "Actualización De Indicadores Y Recursos",
        "category" => "Gerente De Post Venta"
      ],
      [
        "competence" => "Gestión Financiera - Post Venta: Realiza una gestión financiera relacionada a la post-venta para contar con información relevante que ayude a tomar decisiones de manera oportuna.",
        "subCompetence" => "Segumineto A Cobranzas",
        "category" => "Gerente De Post Venta"
      ],
      [
        "competence" => "Gestión Financiera - Post Venta: Realiza una gestión financiera relacionada a la post-venta para contar con información relevante que ayude a tomar decisiones de manera oportuna.",
        "subCompetence" => "Reporte De Indicadores Y Recursos",
        "category" => "Gerente De Post Venta"
      ],
      [
        "competence" => "Gestión Financiera - Post Venta: Realiza una gestión financiera relacionada a la post-venta para contar con información relevante que ayude a tomar decisiones de manera oportuna.",
        "subCompetence" => "Gestión De Garantías",
        "category" => "Gerente De Post Venta"
      ],
      [
        "competence" => "Gestión Financiera - Post Venta: Realiza una gestión financiera relacionada a la post-venta para contar con información relevante que ayude a tomar decisiones de manera oportuna.",
        "subCompetence" => "Estructuración De Precios",
        "category" => "Gerente De Post Venta"
      ],
      [
        "competence" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "subCompetence" => "Alineamiento",
        "category" => "Gerente De Post Venta"
      ],
      [
        "competence" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "subCompetence" => "Resolución De Conflictos",
        "category" => "Gerente De Post Venta"
      ],
      [
        "competence" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "subCompetence" => "Dirección De Equipos",
        "category" => "Gerente De Post Venta"
      ],
      [
        "competence" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "subCompetence" => "Convicción",
        "category" => "Gerente De Post Venta"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Cumplimiento De Acuerdos",
        "category" => "Gerente De Post Venta"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Proactividad",
        "category" => "Gerente De Post Venta"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Resultados",
        "category" => "Gerente De Post Venta"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Asumo Las Consecuencias",
        "category" => "Gerente De Post Venta"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Solución De Problema",
        "category" => "Gerente De Post Venta"
      ],
      [
        "competence" => "Planificación: Determinar las metas y prioridades de los proyectos, estableciendo las acciones, los plazos y los recursos necesarios. Así como los instrumentos de medición y segumiento.",
        "subCompetence" => "Definir Metas E Indciadores",
        "category" => "Gerente De Post Venta"
      ],
      [
        "competence" => "Planificación: Determinar las metas y prioridades de los proyectos, estableciendo las acciones, los plazos y los recursos necesarios. Así como los instrumentos de medición y segumiento.",
        "subCompetence" => "Planificación De Los Recursos",
        "category" => "Gerente De Post Venta"
      ],
      [
        "competence" => "Planificación: Determinar las metas y prioridades de los proyectos, estableciendo las acciones, los plazos y los recursos necesarios. Así como los instrumentos de medición y segumiento.",
        "subCompetence" => "Priorización",
        "category" => "Gerente De Post Venta"
      ],
      [
        "competence" => "Planificación: Determinar las metas y prioridades de los proyectos, estableciendo las acciones, los plazos y los recursos necesarios. Así como los instrumentos de medición y segumiento.",
        "subCompetence" => "Creación De Planes",
        "category" => "Gerente De Post Venta"
      ],
      [
        "competence" => "Calidad de Servicio: Supervisa la calidad de los servicios y la satisfacción de los clientes. Interviene para que la calidad y satisfacción se mantengan dentro de lo esperado.",
        "subCompetence" => "Supervisión Del Servicio",
        "category" => "Diseñador Grafico"
      ],
      [
        "competence" => "Calidad de Servicio: Supervisa la calidad de los servicios y la satisfacción de los clientes. Interviene para que la calidad y satisfacción se mantengan dentro de lo esperado.",
        "subCompetence" => "Coordinación Efectiva",
        "category" => "Diseñador Grafico"
      ],
      [
        "competence" => "Calidad de Servicio: Supervisa la calidad de los servicios y la satisfacción de los clientes. Interviene para que la calidad y satisfacción se mantengan dentro de lo esperado.",
        "subCompetence" => "Monitoreo De Satisfacción",
        "category" => "Diseñador Grafico"
      ],
      [
        "competence" => "Calidad de Servicio: Supervisa la calidad de los servicios y la satisfacción de los clientes. Interviene para que la calidad y satisfacción se mantengan dentro de lo esperado.",
        "subCompetence" => "Reporte De Calidad",
        "category" => "Diseñador Grafico"
      ],
      [
        "competence" => "Ejecucion de Piezas Comunicacionales: Define, coordina y ejecuta el desarrollo de piezas comunicacionales de acuerdo a los planes, campañas y proyectos a realizar.",
        "subCompetence" => "Ejecucion Diseño Creativo",
        "category" => "Diseñador Grafico"
      ],
      [
        "competence" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "subCompetence" => "Gestión De Consecuencias",
        "category" => "Diseñador Grafico"
      ],
      [
        "competence" => "Supervisión de Piezas Comunicacionales: Define y coordinar el desarrollo de piezas comunicacionales de acuerdo a los planes, campañas y eventos a realizar.",
        "subCompetence" => "Definir Pieza Comunicacional",
        "category" => "Diseñador Grafico"
      ],
      [
        "competence" => "Supervisión de Piezas Comunicacionales: Define y coordinar el desarrollo de piezas comunicacionales de acuerdo a los planes, campañas y eventos a realizar.",
        "subCompetence" => "Claridad En La Pieza",
        "category" => "Diseñador Grafico"
      ],
      [
        "competence" => "Supervisión de Piezas Comunicacionales: Define y coordinar el desarrollo de piezas comunicacionales de acuerdo a los planes, campañas y eventos a realizar.",
        "subCompetence" => "Identidad Visual De La Marca",
        "category" => "Diseñador Grafico"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Claridad En El Mensaje",
        "category" => "Analista de Negocio"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Respuesta Oportuna",
        "category" => "Analista de Negocio"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Escucha",
        "category" => "Analista de Negocio"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Asertividad",
        "category" => "Analista de Negocio"
      ],
      [
        "competence" => "Gestión de Trámites: Garantiza la recepción y entrega de documentos asegurando que cumpla con los requisitos solicitados.",
        "subCompetence" => "Despacho Documentario",
        "category" => "Analista de Negocio"
      ],
      [
        "competence" => "Gestión de Trámites: Garantiza la recepción y entrega de documentos asegurando que cumpla con los requisitos solicitados.",
        "subCompetence" => "Control Documentario",
        "category" => "Analista de Negocio"
      ],
      [
        "competence" => "Gestión de Trámites: Garantiza la recepción y entrega de documentos asegurando que cumpla con los requisitos solicitados.",
        "subCompetence" => "Perseverancia En La Entrega",
        "category" => "Analista de Negocio"
      ],
      [
        "competence" => "Gestión de Trámites: Garantiza la recepción y entrega de documentos asegurando que cumpla con los requisitos solicitados.",
        "subCompetence" => "Construcción De Red De Contactos",
        "category" => "Analista de Negocio"
      ],
      [
        "competence" => "Perspicacia Contable: Garantizar el correcto registro de los ingresos de la organización identificando posibles riesgos y auditando. Actuar con transparencia durante la auditoría.",
        "subCompetence" => "Control De Información Sensible",
        "category" => "Analista de Negocio"
      ],
      [
        "competence" => "Perspicacia Contable: Garantizar el correcto registro de los ingresos de la organización identificando posibles riesgos y auditando. Actuar con transparencia durante la auditoría.",
        "subCompetence" => "Análisis De Información Contable",
        "category" => "Analista de Negocio"
      ],
      [
        "competence" => "Perspicacia Contable: Garantizar el correcto registro de los ingresos de la organización identificando posibles riesgos y auditando. Actuar con transparencia durante la auditoría.",
        "subCompetence" => "Congruente",
        "category" => "Analista de Negocio"
      ],
      [
        "competence" => "Perspicacia Contable: Garantizar el correcto registro de los ingresos de la organización identificando posibles riesgos y auditando. Actuar con transparencia durante la auditoría.",
        "subCompetence" => "Fraudes",
        "category" => "Analista de Negocio"
      ],
      [
        "competence" => "Perspicacia Contable: Garantizar el correcto registro de los ingresos de la organización identificando posibles riesgos y auditando. Actuar con transparencia durante la auditoría.",
        "subCompetence" => "Auditorías Oportunas",
        "category" => "Analista de Negocio"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Orientación Al Registro Detallado",
        "category" => "Analista de Negocio"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Organización De Información",
        "category" => "Analista de Negocio"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Cumplimiento De Procedimientos",
        "category" => "Analista de Negocio"
      ],
      [
        "competence" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "subCompetence" => "Colaboración",
        "category" => "Analista de Negocio"
      ],
      [
        "competence" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "subCompetence" => "Responsabilidad",
        "category" => "Analista de Negocio"
      ],
      [
        "competence" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "subCompetence" => "Resuelve Conflictos",
        "category" => "Analista de Negocio"
      ],
      [
        "competence" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "subCompetence" => "Pasión",
        "category" => "Analista de Negocio"
      ],
      [
        "competence" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "subCompetence" => "Traspase De Información En Equipo",
        "category" => "Analista de Negocio"
      ],
      [
        "competence" => "Administración de Recursos Tecnológicos: Garantiza el adecuado funcionamiento de los recursos tecnológicos , previene cualquier inconveniente y toma acción ante las dificultades.",
        "subCompetence" => "Control De Licencias Y Funcionamiento",
        "category" => "Analista En Proyecto Tics"
      ],
      [
        "competence" => "Administración de Recursos Tecnológicos: Garantiza el adecuado funcionamiento de los recursos tecnológicos , previene cualquier inconveniente y toma acción ante las dificultades.",
        "subCompetence" => "Control Y Organización De Recursos Ti",
        "category" => "Analista En Proyecto Tics"
      ],
      [
        "competence" => "Administración de Recursos Tecnológicos: Garantiza el adecuado funcionamiento de los recursos tecnológicos , previene cualquier inconveniente y toma acción ante las dificultades.",
        "subCompetence" => "Intervención De Problemas",
        "category" => "Analista En Proyecto Tics"
      ],
      [
        "competence" => "Administración de Recursos Tecnológicos: Garantiza el adecuado funcionamiento de los recursos tecnológicos , previene cualquier inconveniente y toma acción ante las dificultades.",
        "subCompetence" => "Prevención De Recursos",
        "category" => "Analista En Proyecto Tics"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Asertividad",
        "category" => "Analista En Proyecto Tics"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Claridad En El Mensaje",
        "category" => "Analista En Proyecto Tics"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Escucha",
        "category" => "Analista En Proyecto Tics"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Respuesta Oportuna",
        "category" => "Analista En Proyecto Tics"
      ],
      [
        "competence" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "subCompetence" => "Gestión De Recursos",
        "category" => "Analista En Proyecto Tics"
      ],
      [
        "competence" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "subCompetence" => "Metodología",
        "category" => "Analista En Proyecto Tics"
      ],
      [
        "competence" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "subCompetence" => "Orientación Al Logro",
        "category" => "Analista En Proyecto Tics"
      ],
      [
        "competence" => "Gestión Contable: Analiza de manera adecuada la información contable y define acciones adecuadas para el sistema de contable.",
        "subCompetence" => "Análisis De Estados Financieros",
        "category" => "Jefe De Contabilidad"
      ],
      [
        "competence" => "Gestión Contable: Analiza de manera adecuada la información contable y define acciones adecuadas para el sistema de contable.",
        "subCompetence" => "Sistemas Correctivos",
        "category" => "Jefe De Contabilidad"
      ],
      [
        "competence" => "Gestión Contable: Analiza de manera adecuada la información contable y define acciones adecuadas para el sistema de contable.",
        "subCompetence" => "Plazos De Entrega",
        "category" => "Jefe De Contabilidad"
      ],
      [
        "competence" => "Gestión Contable: Analiza de manera adecuada la información contable y define acciones adecuadas para el sistema de contable.",
        "subCompetence" => "Informes Contables",
        "category" => "Jefe De Contabilidad"
      ],
      [
        "competence" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "subCompetence" => "Supervisión",
        "category" => "Jefe De Contabilidad"
      ],
      [
        "competence" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "subCompetence" => "Seguimiento",
        "category" => "Jefe De Contabilidad"
      ],
      [
        "competence" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "subCompetence" => "Retroalimentación",
        "category" => "Jefe De Contabilidad"
      ],
      [
        "competence" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "subCompetence" => "Gestión De Consecuencias",
        "category" => "Jefe De Contabilidad"
      ],
      [
        "competence" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "subCompetence" => "Control",
        "category" => "Jefe De Contabilidad"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Cumplimiento De Acuerdos",
        "category" => "Jefe De Contabilidad"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Proactividad",
        "category" => "Jefe De Contabilidad"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Resultados",
        "category" => "Jefe De Contabilidad"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Asumo Las Consecuencias",
        "category" => "Jefe De Contabilidad"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Solución De Problema",
        "category" => "Jefe De Contabilidad"
      ],
      [
        "competence" => "Perspicacia Contable: Garantizar el correcto registro de los ingresos de la organización identificando posibles riesgos y auditando. Actuar con transparencia durante la auditoría.",
        "subCompetence" => "Fraudes",
        "category" => "Jefe De Contabilidad"
      ],
      [
        "competence" => "Perspicacia Contable: Garantizar el correcto registro de los ingresos de la organización identificando posibles riesgos y auditando. Actuar con transparencia durante la auditoría.",
        "subCompetence" => "Control De Información Sensible",
        "category" => "Jefe De Contabilidad"
      ],
      [
        "competence" => "Perspicacia Contable: Garantizar el correcto registro de los ingresos de la organización identificando posibles riesgos y auditando. Actuar con transparencia durante la auditoría.",
        "subCompetence" => "Congruente",
        "category" => "Jefe De Contabilidad"
      ],
      [
        "competence" => "Perspicacia Contable: Garantizar el correcto registro de los ingresos de la organización identificando posibles riesgos y auditando. Actuar con transparencia durante la auditoría.",
        "subCompetence" => "Auditorías Oportunas",
        "category" => "Jefe De Contabilidad"
      ],
      [
        "competence" => "Perspicacia Contable: Garantizar el correcto registro de los ingresos de la organización identificando posibles riesgos y auditando. Actuar con transparencia durante la auditoría.",
        "subCompetence" => "Análisis De Información Contable",
        "category" => "Jefe De Contabilidad"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Cumplimiento De Procedimientos",
        "category" => "Jefe De Contabilidad"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Organización De Información",
        "category" => "Jefe De Contabilidad"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Orientación Al Registro Detallado",
        "category" => "Jefe De Contabilidad"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Asertividad",
        "category" => "Partner De Gestión Humana"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Claridad En El Mensaje",
        "category" => "Partner De Gestión Humana"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Escucha",
        "category" => "Partner De Gestión Humana"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Respuesta Oportuna",
        "category" => "Partner De Gestión Humana"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Cumplimiento De Procedimientos",
        "category" => "Partner De Gestión Humana"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Organización De Información",
        "category" => "Partner De Gestión Humana"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Orientación Al Registro Detallado",
        "category" => "Partner De Gestión Humana"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Amabilidad Y Cercanía",
        "category" => "Partner De Gestión Humana"
      ],
      [
        "competence" => "Servicio al colaborador: Resuelve las consultas y solicitudes de los colaboradores con empatía y amabilidad.",
        "subCompetence" => "Amabilidad Y Cercanía Con El Colaborador",
        "category" => "Partner De Gestión Humana"
      ],
      [
        "competence" => "Servicio al colaborador: Resuelve las consultas y solicitudes de los colaboradores con empatía y amabilidad.",
        "subCompetence" => "Atención Efectiva A Consultas De Colaboradores",
        "category" => "Partner De Gestión Humana"
      ],
      [
        "competence" => "Servicio al colaborador: Resuelve las consultas y solicitudes de los colaboradores con empatía y amabilidad.",
        "subCompetence" => "Atención Oportuna",
        "category" => "Partner De Gestión Humana"
      ],
      [
        "competence" => "Servicio al colaborador: Resuelve las consultas y solicitudes de los colaboradores con empatía y amabilidad.",
        "subCompetence" => "Empatía",
        "category" => "Partner De Gestión Humana"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Asertividad",
        "category" => "Analista De Contabilidad DP"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Respuesta Oportuna",
        "category" => "Analista De Contabilidad DP"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Claridad En El Mensaje",
        "category" => "Analista De Contabilidad DP"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Escucha",
        "category" => "Analista De Contabilidad DP"
      ],
      [
        "competence" => "Gestión Contable: Analiza de manera adecuada la información contable y define acciones adecuadas para el sistema de contable.",
        "subCompetence" => "Análisis De Estados Financieros",
        "category" => "Analista De Contabilidad DP"
      ],
      [
        "competence" => "Gestión Contable: Analiza de manera adecuada la información contable y define acciones adecuadas para el sistema de contable.",
        "subCompetence" => "Sistemas Correctivos",
        "category" => "Analista De Contabilidad DP"
      ],
      [
        "competence" => "Gestión Contable: Analiza de manera adecuada la información contable y define acciones adecuadas para el sistema de contable.",
        "subCompetence" => "Plazos De Entrega",
        "category" => "Analista De Contabilidad DP"
      ],
      [
        "competence" => "Gestión Contable: Analiza de manera adecuada la información contable y define acciones adecuadas para el sistema de contable.",
        "subCompetence" => "Informes Contables",
        "category" => "Analista De Contabilidad DP"
      ],
      [
        "competence" => "Habilidad analítica: Capacidad de razonamiento y organización cognitivamente del trabajo para realizar un análisis lógico identificando problemas, información y datos significativos.",
        "subCompetence" => "Tratamiento De La Información",
        "category" => "Analista De Contabilidad DP"
      ],
      [
        "competence" => "Habilidad analítica: Capacidad de razonamiento y organización cognitivamente del trabajo para realizar un análisis lógico identificando problemas, información y datos significativos.",
        "subCompetence" => "Identificación De Problemas",
        "category" => "Analista De Contabilidad DP"
      ],
      [
        "competence" => "Habilidad analítica: Capacidad de razonamiento y organización cognitivamente del trabajo para realizar un análisis lógico identificando problemas, información y datos significativos.",
        "subCompetence" => "Capacidad De Comprensión",
        "category" => "Analista De Contabilidad DP"
      ],
      [
        "competence" => "Habilidad analítica: Capacidad de razonamiento y organización cognitivamente del trabajo para realizar un análisis lógico identificando problemas, información y datos significativos.",
        "subCompetence" => "Análisis Lógico",
        "category" => "Analista De Contabilidad DP"
      ],
      [
        "competence" => "Perspicacia Contable: Garantizar el correcto registro de los ingresos de la organización identificando posibles riesgos y auditando. Actuar con transparencia durante la auditoría.",
        "subCompetence" => "Fraudes",
        "category" => "Analista De Contabilidad DP"
      ],
      [
        "competence" => "Perspicacia Contable: Garantizar el correcto registro de los ingresos de la organización identificando posibles riesgos y auditando. Actuar con transparencia durante la auditoría.",
        "subCompetence" => "Control De Información Sensible",
        "category" => "Analista De Contabilidad DP"
      ],
      [
        "competence" => "Perspicacia Contable: Garantizar el correcto registro de los ingresos de la organización identificando posibles riesgos y auditando. Actuar con transparencia durante la auditoría.",
        "subCompetence" => "Congruente",
        "category" => "Analista De Contabilidad DP"
      ],
      [
        "competence" => "Perspicacia Contable: Garantizar el correcto registro de los ingresos de la organización identificando posibles riesgos y auditando. Actuar con transparencia durante la auditoría.",
        "subCompetence" => "Auditorías Oportunas",
        "category" => "Analista De Contabilidad DP"
      ],
      [
        "competence" => "Perspicacia Contable: Garantizar el correcto registro de los ingresos de la organización identificando posibles riesgos y auditando. Actuar con transparencia durante la auditoría.",
        "subCompetence" => "Análisis De Información Contable",
        "category" => "Analista De Contabilidad DP"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Cumplimiento De Procedimientos",
        "category" => "Analista De Contabilidad DP"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Organización De Información",
        "category" => "Analista De Contabilidad DP"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Orientación Al Registro Detallado",
        "category" => "Analista De Contabilidad DP"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Claridad En El Mensaje",
        "category" => "Asistente De Contabilidad Dp"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Respuesta Oportuna",
        "category" => "Asistente De Contabilidad Dp"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Escucha",
        "category" => "Asistente De Contabilidad Dp"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Asertividad",
        "category" => "Asistente De Contabilidad Dp"
      ],
      [
        "competence" => "Gestión de Trámites: Garantiza la recepción y entrega de documentos asegurando que cumpla con los requisitos solicitados.",
        "subCompetence" => "Despacho Documentario",
        "category" => "Asistente De Contabilidad Dp"
      ],
      [
        "competence" => "Gestión de Trámites: Garantiza la recepción y entrega de documentos asegurando que cumpla con los requisitos solicitados.",
        "subCompetence" => "Control Documentario",
        "category" => "Asistente De Contabilidad Dp"
      ],
      [
        "competence" => "Gestión de Trámites: Garantiza la recepción y entrega de documentos asegurando que cumpla con los requisitos solicitados.",
        "subCompetence" => "Perseverancia En La Entrega",
        "category" => "Asistente De Contabilidad Dp"
      ],
      [
        "competence" => "Gestión de Trámites: Garantiza la recepción y entrega de documentos asegurando que cumpla con los requisitos solicitados.",
        "subCompetence" => "Construcción De Red De Contactos",
        "category" => "Asistente De Contabilidad Dp"
      ],
      [
        "competence" => "Perspicacia Contable: Garantizar el correcto registro de los ingresos de la organización identificando posibles riesgos y auditando. Actuar con transparencia durante la auditoría.",
        "subCompetence" => "Control De Información Sensible",
        "category" => "Asistente De Contabilidad Dp"
      ],
      [
        "competence" => "Perspicacia Contable: Garantizar el correcto registro de los ingresos de la organización identificando posibles riesgos y auditando. Actuar con transparencia durante la auditoría.",
        "subCompetence" => "Análisis De Información Contable",
        "category" => "Asistente De Contabilidad Dp"
      ],
      [
        "competence" => "Perspicacia Contable: Garantizar el correcto registro de los ingresos de la organización identificando posibles riesgos y auditando. Actuar con transparencia durante la auditoría.",
        "subCompetence" => "Congruente",
        "category" => "Asistente De Contabilidad Dp"
      ],
      [
        "competence" => "Perspicacia Contable: Garantizar el correcto registro de los ingresos de la organización identificando posibles riesgos y auditando. Actuar con transparencia durante la auditoría.",
        "subCompetence" => "Fraudes",
        "category" => "Asistente De Contabilidad Dp"
      ],
      [
        "competence" => "Perspicacia Contable: Garantizar el correcto registro de los ingresos de la organización identificando posibles riesgos y auditando. Actuar con transparencia durante la auditoría.",
        "subCompetence" => "Auditorías Oportunas",
        "category" => "Asistente De Contabilidad Dp"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Orientación Al Registro Detallado",
        "category" => "Asistente De Contabilidad Dp"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Organización De Información",
        "category" => "Asistente De Contabilidad Dp"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Cumplimiento De Procedimientos",
        "category" => "Asistente De Contabilidad Dp"
      ],
      [
        "competence" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "subCompetence" => "Colaboración",
        "category" => "Asistente De Contabilidad Dp"
      ],
      [
        "competence" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "subCompetence" => "Responsabilidad",
        "category" => "Asistente De Contabilidad Dp"
      ],
      [
        "competence" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "subCompetence" => "Resuelve Conflictos",
        "category" => "Asistente De Contabilidad Dp"
      ],
      [
        "competence" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "subCompetence" => "Pasión",
        "category" => "Asistente De Contabilidad Dp"
      ],
      [
        "competence" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "subCompetence" => "Traspase De Información En Equipo",
        "category" => "Asistente De Contabilidad Dp"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Asertividad",
        "category" => "Asistente Administrativo Dp"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Claridad En El Mensaje",
        "category" => "Asistente Administrativo Dp"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Escucha",
        "category" => "Asistente Administrativo Dp"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Respuesta Oportuna",
        "category" => "Asistente Administrativo Dp"
      ],
      [
        "competence" => "Registro y Manejo de Información - Versión 2 AP: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Cumplimiento De Procesos Operativos",
        "category" => "Asistente Administrativo Dp"
      ],
      [
        "competence" => "Registro y Manejo de Información - Versión 2 AP: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Organización",
        "category" => "Asistente Administrativo Dp"
      ],
      [
        "competence" => "Registro y Manejo de Información - Versión 2 AP: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Orientación Al Detalle",
        "category" => "Asistente Administrativo Dp"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Amabilidad Y Cercanía",
        "category" => "Asistente Administrativo Dp"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Atención Efectiva A Consultas",
        "category" => "Asistente Administrativo Dp"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Atención Presencial Oportuna",
        "category" => "Asistente Administrativo Dp"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Seguimiento A La Atención",
        "category" => "Asistente Administrativo Dp"
      ],
      [
        "competence" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "subCompetence" => "Colaboración",
        "category" => "Asistente Administrativo Dp"
      ],
      [
        "competence" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "subCompetence" => "Pasión",
        "category" => "Asistente Administrativo Dp"
      ],
      [
        "competence" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "subCompetence" => "Responsabilidad",
        "category" => "Asistente Administrativo Dp"
      ],
      [
        "competence" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "subCompetence" => "Resuelve Conflictos",
        "category" => "Asistente Administrativo Dp"
      ],
      [
        "competence" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "subCompetence" => "Traspase De Información En Equipo",
        "category" => "Asistente Administrativo Dp"
      ],
      [
        "competence" => "Calidad en la entrega del Producto: Velar por la calidad y recursos que utiliza en la entrega del producto al cliente a fin de generar una adecuada satisfacción.",
        "subCompetence" => "Protección Del Vehículo",
        "category" => "Asistente De Reparto"
      ],
      [
        "competence" => "Calidad en la entrega del Producto: Velar por la calidad y recursos que utiliza en la entrega del producto al cliente a fin de generar una adecuada satisfacción.",
        "subCompetence" => "Protección Del Producto",
        "category" => "Asistente De Reparto"
      ],
      [
        "competence" => "Calidad en la entrega del Producto: Velar por la calidad y recursos que utiliza en la entrega del producto al cliente a fin de generar una adecuada satisfacción.",
        "subCompetence" => "Conocimiento De Procesos",
        "category" => "Asistente De Reparto"
      ],
      [
        "competence" => "Calidad en la entrega del Producto: Velar por la calidad y recursos que utiliza en la entrega del producto al cliente a fin de generar una adecuada satisfacción.",
        "subCompetence" => "Control De Calidad",
        "category" => "Asistente De Reparto"
      ],
      [
        "competence" => "Calidad en la entrega del Producto: Velar por la calidad y recursos que utiliza en la entrega del producto al cliente a fin de generar una adecuada satisfacción.",
        "subCompetence" => "Entrega Del Producto",
        "category" => "Asistente De Reparto"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Asertividad",
        "category" => "Asistente De Reparto"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Respuesta Oportuna",
        "category" => "Asistente De Reparto"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Escucha",
        "category" => "Asistente De Reparto"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Claridad En El Mensaje",
        "category" => "Asistente De Reparto"
      ],
      [
        "competence" => "Cooperación Operativa: Coopera de manera activa con las necesidades del equipo y unidades con las que interactúa. Respeta los acuerdos generando un espacio colaborativo y de confianza.",
        "subCompetence" => "Cooperación",
        "category" => "Asistente De Reparto"
      ],
      [
        "competence" => "Cooperación Operativa: Coopera de manera activa con las necesidades del equipo y unidades con las que interactúa. Respeta los acuerdos generando un espacio colaborativo y de confianza.",
        "subCompetence" => "Orientación A La Meta Común",
        "category" => "Asistente De Reparto"
      ],
      [
        "competence" => "Cooperación Operativa: Coopera de manera activa con las necesidades del equipo y unidades con las que interactúa. Respeta los acuerdos generando un espacio colaborativo y de confianza.",
        "subCompetence" => "Relaciones Interpersonales",
        "category" => "Asistente De Reparto"
      ],
      [
        "competence" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "subCompetence" => "Gestión De Recursos",
        "category" => "Asistente De Reparto"
      ],
      [
        "competence" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "subCompetence" => "Metodología",
        "category" => "Asistente De Reparto"
      ],
      [
        "competence" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "subCompetence" => "Orientación Al Logro",
        "category" => "Asistente De Reparto"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Atención Presencial Oportuna",
        "category" => "Asistente De Reparto"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Atención Efectiva A Consultas",
        "category" => "Asistente De Reparto"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Amabilidad Y Cercanía",
        "category" => "Asistente De Reparto"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Seguimiento A La Atención",
        "category" => "Asistente De Reparto"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Asertividad",
        "category" => "Caja General"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Claridad En El Mensaje",
        "category" => "Caja General"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Escucha",
        "category" => "Caja General"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Respuesta Oportuna",
        "category" => "Caja General"
      ],
      [
        "competence" => "Mentalidad de Dueño V2: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Anticipación A Requerimientos",
        "category" => "Caja General"
      ],
      [
        "competence" => "Mentalidad de Dueño V2: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Solución De Problemas Difíciles",
        "category" => "Caja General"
      ],
      [
        "competence" => "Mentalidad de Dueño V2: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Pertenencia",
        "category" => "Caja General"
      ],
      [
        "competence" => "Registro y Manejo de Caja - Caja General",
        "subCompetence" => "Cierre De Caja Cg",
        "category" => "Caja General"
      ],
      [
        "competence" => "Registro y Manejo de Caja - Caja General",
        "subCompetence" => "Entrega De Dinero Cg",
        "category" => "Caja General"
      ],
      [
        "competence" => "Registro y Manejo de Caja - Caja General",
        "subCompetence" => "Reportes Cg",
        "category" => "Caja General"
      ],
      [
        "competence" => "Registro y Manejo de Caja - Caja General",
        "subCompetence" => "Seguimiento De Cobranzas Cg",
        "category" => "Caja General"
      ],
      [
        "competence" => "Registro y Manejo de Caja - Caja General",
        "subCompetence" => "Revisión De Ingresos Cg",
        "category" => "Caja General"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Cumplimiento De Procedimientos",
        "category" => "Caja General"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Organización De Información",
        "category" => "Caja General"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Orientación Al Registro Detallado",
        "category" => "Caja General"
      ],
      [
        "competence" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "subCompetence" => "Traspase De Información En Equipo",
        "category" => "Caja General"
      ],
      [
        "competence" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "subCompetence" => "Resuelve Conflictos",
        "category" => "Caja General"
      ],
      [
        "competence" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "subCompetence" => "Responsabilidad",
        "category" => "Caja General"
      ],
      [
        "competence" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "subCompetence" => "Pasión",
        "category" => "Caja General"
      ],
      [
        "competence" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "subCompetence" => "Colaboración",
        "category" => "Caja General"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Asertividad",
        "category" => "Caja Dp"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Respuesta Oportuna",
        "category" => "Caja Dp"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Claridad En El Mensaje",
        "category" => "Caja Dp"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Escucha",
        "category" => "Caja Dp"
      ],
      [
        "competence" => "Registro y Manejo de Caja: Control y registro de información adecuada de la Caja, revisando de manera adecuada los billetes y cumpliendo con los procedimientos para el cierre de caja.",
        "subCompetence" => "Revisión De Ingresos",
        "category" => "Caja Dp"
      ],
      [
        "competence" => "Registro y Manejo de Caja: Control y registro de información adecuada de la Caja, revisando de manera adecuada los billetes y cumpliendo con los procedimientos para el cierre de caja.",
        "subCompetence" => "Cierre De Caja",
        "category" => "Caja Dp"
      ],
      [
        "competence" => "Registro y Manejo de Caja: Control y registro de información adecuada de la Caja, revisando de manera adecuada los billetes y cumpliendo con los procedimientos para el cierre de caja.",
        "subCompetence" => "Reportes",
        "category" => "Caja Dp"
      ],
      [
        "competence" => "Registro y Manejo de Caja: Control y registro de información adecuada de la Caja, revisando de manera adecuada los billetes y cumpliendo con los procedimientos para el cierre de caja.",
        "subCompetence" => "Entrega De Dinero",
        "category" => "Caja Dp"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Organización De Información",
        "category" => "Caja Dp"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Orientación Al Registro Detallado",
        "category" => "Caja Dp"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Cumplimiento De Procedimientos",
        "category" => "Caja Dp"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Amabilidad Y Cercanía",
        "category" => "Caja Dp"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Atención Presencial Oportuna",
        "category" => "Caja Dp"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Atención Efectiva A Consultas",
        "category" => "Caja Dp"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Seguimiento A La Atención",
        "category" => "Caja Dp"
      ],
      [
        "competence" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "subCompetence" => "Pasión",
        "category" => "Caja Dp"
      ],
      [
        "competence" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "subCompetence" => "Colaboración",
        "category" => "Caja Dp"
      ],
      [
        "competence" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "subCompetence" => "Responsabilidad",
        "category" => "Caja Dp"
      ],
      [
        "competence" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "subCompetence" => "Resuelve Conflictos",
        "category" => "Caja Dp"
      ],
      [
        "competence" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "subCompetence" => "Traspase De Información En Equipo",
        "category" => "Caja Dp"
      ],
      [
        "competence" => "Calidad en la entrega del Producto: Velar por la calidad y recursos que utiliza en la entrega del producto al cliente a fin de generar una adecuada satisfacción.",
        "subCompetence" => "Conocimiento De Procesos",
        "category" => "Conductor I"
      ],
      [
        "competence" => "Calidad en la entrega del Producto: Velar por la calidad y recursos que utiliza en la entrega del producto al cliente a fin de generar una adecuada satisfacción.",
        "subCompetence" => "Control De Calidad",
        "category" => "Conductor I"
      ],
      [
        "competence" => "Calidad en la entrega del Producto: Velar por la calidad y recursos que utiliza en la entrega del producto al cliente a fin de generar una adecuada satisfacción.",
        "subCompetence" => "Entrega Del Producto",
        "category" => "Conductor I"
      ],
      [
        "competence" => "Calidad en la entrega del Producto: Velar por la calidad y recursos que utiliza en la entrega del producto al cliente a fin de generar una adecuada satisfacción.",
        "subCompetence" => "Protección Del Producto",
        "category" => "Conductor I"
      ],
      [
        "competence" => "Calidad en la entrega del Producto: Velar por la calidad y recursos que utiliza en la entrega del producto al cliente a fin de generar una adecuada satisfacción.",
        "subCompetence" => "Protección Del Vehículo",
        "category" => "Conductor I"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Asertividad",
        "category" => "Conductor I"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Claridad En El Mensaje",
        "category" => "Conductor I"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Escucha",
        "category" => "Conductor I"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Respuesta Oportuna",
        "category" => "Conductor I"
      ],
      [
        "competence" => "Cooperación Operativa: Coopera de manera activa con las necesidades del equipo y unidades con las que interactúa. Respeta los acuerdos generando un espacio colaborativo y de confianza.",
        "subCompetence" => "Cooperación",
        "category" => "Conductor I"
      ],
      [
        "competence" => "Cooperación Operativa: Coopera de manera activa con las necesidades del equipo y unidades con las que interactúa. Respeta los acuerdos generando un espacio colaborativo y de confianza.",
        "subCompetence" => "Orientación A La Meta Común",
        "category" => "Conductor I"
      ],
      [
        "competence" => "Cooperación Operativa: Coopera de manera activa con las necesidades del equipo y unidades con las que interactúa. Respeta los acuerdos generando un espacio colaborativo y de confianza.",
        "subCompetence" => "Relaciones Interpersonales",
        "category" => "Conductor I"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Amabilidad Y Cercanía",
        "category" => "Conductor I"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Atención Efectiva A Consultas",
        "category" => "Conductor I"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Atención Presencial Oportuna",
        "category" => "Conductor I"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Seguimiento A La Atención",
        "category" => "Conductor I"
      ],
      [
        "competence" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "subCompetence" => "Gestión De Recursos",
        "category" => "Conductor I"
      ],
      [
        "competence" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "subCompetence" => "Metodología",
        "category" => "Conductor I"
      ],
      [
        "competence" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "subCompetence" => "Orientación Al Logro",
        "category" => "Conductor I"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Asertividad",
        "category" => "Conductor II"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Claridad En El Mensaje",
        "category" => "Conductor II"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Escucha",
        "category" => "Conductor II"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Respuesta Oportuna",
        "category" => "Conductor II"
      ],
      [
        "competence" => "Cooperación Operativa: Coopera de manera activa con las necesidades del equipo y unidades con las que interactúa. Respeta los acuerdos generando un espacio colaborativo y de confianza.",
        "subCompetence" => "Cooperación",
        "category" => "Conductor II"
      ],
      [
        "competence" => "Cooperación Operativa: Coopera de manera activa con las necesidades del equipo y unidades con las que interactúa. Respeta los acuerdos generando un espacio colaborativo y de confianza.",
        "subCompetence" => "Orientación A La Meta Común",
        "category" => "Conductor II"
      ],
      [
        "competence" => "Cooperación Operativa: Coopera de manera activa con las necesidades del equipo y unidades con las que interactúa. Respeta los acuerdos generando un espacio colaborativo y de confianza.",
        "subCompetence" => "Relaciones Interpersonales",
        "category" => "Conductor II"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Amabilidad Y Cercanía",
        "category" => "Conductor II"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Atención Efectiva A Consultas",
        "category" => "Conductor II"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Atención Presencial Oportuna",
        "category" => "Conductor II"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Seguimiento A La Atención",
        "category" => "Conductor II"
      ],
      [
        "competence" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "subCompetence" => "Gestión De Recursos",
        "category" => "Conductor II"
      ],
      [
        "competence" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "subCompetence" => "Metodología",
        "category" => "Conductor II"
      ],
      [
        "competence" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "subCompetence" => "Orientación Al Logro",
        "category" => "Conductor II"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Asertividad",
        "category" => "Ejecutivo Comercial"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Respuesta Oportuna",
        "category" => "Ejecutivo Comercial"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Claridad En El Mensaje",
        "category" => "Ejecutivo Comercial"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Escucha",
        "category" => "Ejecutivo Comercial"
      ],
      [
        "competence" => "Gestión de Clientes Relevantes: Fideliza clientes relevantes del negocio y resuelve sus problemas. Cuenta con un profundo conocimiento del cliente.",
        "subCompetence" => "Manejo De Clientes Díficiles",
        "category" => "Ejecutivo Comercial"
      ],
      [
        "competence" => "Gestión de Clientes Relevantes: Fideliza clientes relevantes del negocio y resuelve sus problemas. Cuenta con un profundo conocimiento del cliente.",
        "subCompetence" => "Resolución De Problemas",
        "category" => "Ejecutivo Comercial"
      ],
      [
        "competence" => "Gestión de Clientes Relevantes: Fideliza clientes relevantes del negocio y resuelve sus problemas. Cuenta con un profundo conocimiento del cliente.",
        "subCompetence" => "Relación Con El Cliente",
        "category" => "Ejecutivo Comercial"
      ],
      [
        "competence" => "Gestión de Clientes Relevantes: Fideliza clientes relevantes del negocio y resuelve sus problemas. Cuenta con un profundo conocimiento del cliente.",
        "subCompetence" => "Fidelización Con El Cliente",
        "category" => "Ejecutivo Comercial"
      ],
      [
        "competence" => "Gestión de Clientes Relevantes: Fideliza clientes relevantes del negocio y resuelve sus problemas. Cuenta con un profundo conocimiento del cliente.",
        "subCompetence" => "Conocimiento Del Cliente",
        "category" => "Ejecutivo Comercial"
      ],
      [
        "competence" => "Habilidad Comercial: Logra persuadir al cliente a través de argumentos poderosos, realiza seguimiento oportuno manteniéndose cerca del cliente y aprovechando oportunidades.",
        "subCompetence" => "Disciplina Comercial",
        "category" => "Ejecutivo Comercial"
      ],
      [
        "competence" => "Habilidad Comercial: Logra persuadir al cliente a través de argumentos poderosos, realiza seguimiento oportuno manteniéndose cerca del cliente y aprovechando oportunidades.",
        "subCompetence" => "Manejo De Objeciones",
        "category" => "Ejecutivo Comercial"
      ],
      [
        "competence" => "Habilidad Comercial: Logra persuadir al cliente a través de argumentos poderosos, realiza seguimiento oportuno manteniéndose cerca del cliente y aprovechando oportunidades.",
        "subCompetence" => "Negociación Comercial",
        "category" => "Ejecutivo Comercial"
      ],
      [
        "competence" => "Habilidad Comercial: Logra persuadir al cliente a través de argumentos poderosos, realiza seguimiento oportuno manteniéndose cerca del cliente y aprovechando oportunidades.",
        "subCompetence" => "Búsqueda De Oportunidades",
        "category" => "Ejecutivo Comercial"
      ],
      [
        "competence" => "Habilidad Comercial: Logra persuadir al cliente a través de argumentos poderosos, realiza seguimiento oportuno manteniéndose cerca del cliente y aprovechando oportunidades.",
        "subCompetence" => "Seguimiento Comercial",
        "category" => "Ejecutivo Comercial"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Amabilidad Y Cercanía",
        "category" => "Ejecutivo Comercial"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Atención Presencial Oportuna",
        "category" => "Ejecutivo Comercial"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Atención Efectiva A Consultas",
        "category" => "Ejecutivo Comercial"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Seguimiento A La Atención",
        "category" => "Ejecutivo Comercial"
      ],
      [
        "competence" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "subCompetence" => "Colaboración",
        "category" => "Ejecutivo Comercial"
      ],
      [
        "competence" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "subCompetence" => "Pasión",
        "category" => "Ejecutivo Comercial"
      ],
      [
        "competence" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "subCompetence" => "Responsabilidad",
        "category" => "Ejecutivo Comercial"
      ],
      [
        "competence" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "subCompetence" => "Resuelve Conflictos",
        "category" => "Ejecutivo Comercial"
      ],
      [
        "competence" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "subCompetence" => "Traspase De Información En Equipo",
        "category" => "Ejecutivo Comercial"
      ],
      [
        "competence" => "Gestión Comercial: Define las estrategias comerciales, objetivos y metas de acuerdo al conocimiento del negocio, producto y el funcionamiento de la competencia.",
        "subCompetence" => "Conocimiento De La Competencia Comercial",
        "category" => "Gerente Comercial Dp"
      ],
      [
        "competence" => "Gestión Comercial: Define las estrategias comerciales, objetivos y metas de acuerdo al conocimiento del negocio, producto y el funcionamiento de la competencia.",
        "subCompetence" => "Reajustes Comerciales V1",
        "category" => "Gerente Comercial Dp"
      ],
      [
        "competence" => "Gestión Comercial: Define las estrategias comerciales, objetivos y metas de acuerdo al conocimiento del negocio, producto y el funcionamiento de la competencia.",
        "subCompetence" => "Definición Estrategias, Objetivos Y Metas",
        "category" => "Gerente Comercial Dp"
      ],
      [
        "competence" => "Gestión Comercial: Define las estrategias comerciales, objetivos y metas de acuerdo al conocimiento del negocio, producto y el funcionamiento de la competencia.",
        "subCompetence" => "Conocimiento Del Negocio Comercial",
        "category" => "Gerente Comercial Dp"
      ],
      [
        "competence" => "Gestión de Marketing: Define las estrategias de marketing, analiza el funcionamiento del mercado para definir las acciones adecuadas.",
        "subCompetence" => "Análisis De Mercado",
        "category" => "Gerente Comercial Dp"
      ],
      [
        "competence" => "Gestión de Marketing: Define las estrategias de marketing, analiza el funcionamiento del mercado para definir las acciones adecuadas.",
        "subCompetence" => "Evaluación De La Demanda",
        "category" => "Gerente Comercial Dp"
      ],
      [
        "competence" => "Gestión de Marketing: Define las estrategias de marketing, analiza el funcionamiento del mercado para definir las acciones adecuadas.",
        "subCompetence" => "Definición Del Precio De Venta",
        "category" => "Gerente Comercial Dp"
      ],
      [
        "competence" => "Gestión de Marketing: Define las estrategias de marketing, analiza el funcionamiento del mercado para definir las acciones adecuadas.",
        "subCompetence" => "Definición De Estrategias Y Objetivos",
        "category" => "Gerente Comercial Dp"
      ],
      [
        "competence" => "Gestión de Presupuesto: Define el presupuesto de inversión adecuado considerando los costos y la rentabilidad del negocio.",
        "subCompetence" => "Control Y Monitoreo Comercial",
        "category" => "Gerente Comercial Dp"
      ],
      [
        "competence" => "Gestión de Presupuesto: Define el presupuesto de inversión adecuado considerando los costos y la rentabilidad del negocio.",
        "subCompetence" => "Ajustes Comerciales",
        "category" => "Gerente Comercial Dp"
      ],
      [
        "competence" => "Gestión de Presupuesto: Define el presupuesto de inversión adecuado considerando los costos y la rentabilidad del negocio.",
        "subCompetence" => "Definición Presupuestal",
        "category" => "Gerente Comercial Dp"
      ],
      [
        "competence" => "Gestión de Presupuesto: Define el presupuesto de inversión adecuado considerando los costos y la rentabilidad del negocio.",
        "subCompetence" => "Evaluación De Estructura De Costos Comerciales",
        "category" => "Gerente Comercial Dp"
      ],
      [
        "competence" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "subCompetence" => "Dirección De Equipos",
        "category" => "Gerente Comercial Dp"
      ],
      [
        "competence" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "subCompetence" => "Resolución De Conflictos",
        "category" => "Gerente Comercial Dp"
      ],
      [
        "competence" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "subCompetence" => "Convicción",
        "category" => "Gerente Comercial Dp"
      ],
      [
        "competence" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "subCompetence" => "Alineamiento",
        "category" => "Gerente Comercial Dp"
      ],
      [
        "competence" => "Planificación: Determinar las metas y prioridades de los proyectos, estableciendo las acciones, los plazos y los recursos necesarios. Así como los instrumentos de medición y segumiento.",
        "subCompetence" => "Creación De Planes",
        "category" => "Gerente Comercial Dp"
      ],
      [
        "competence" => "Planificación: Determinar las metas y prioridades de los proyectos, estableciendo las acciones, los plazos y los recursos necesarios. Así como los instrumentos de medición y segumiento.",
        "subCompetence" => "Priorización",
        "category" => "Gerente Comercial Dp"
      ],
      [
        "competence" => "Planificación: Determinar las metas y prioridades de los proyectos, estableciendo las acciones, los plazos y los recursos necesarios. Así como los instrumentos de medición y segumiento.",
        "subCompetence" => "Definir Metas E Indciadores",
        "category" => "Gerente Comercial Dp"
      ],
      [
        "competence" => "Planificación: Determinar las metas y prioridades de los proyectos, estableciendo las acciones, los plazos y los recursos necesarios. Así como los instrumentos de medición y segumiento.",
        "subCompetence" => "Planificación De Los Recursos",
        "category" => "Gerente Comercial Dp"
      ],
      [
        "competence" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "subCompetence" => "Control",
        "category" => "Jefe De Administracion"
      ],
      [
        "competence" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "subCompetence" => "Supervisión",
        "category" => "Jefe De Administracion"
      ],
      [
        "competence" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "subCompetence" => "Seguimiento",
        "category" => "Jefe De Administracion"
      ],
      [
        "competence" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "subCompetence" => "Retroalimentación",
        "category" => "Jefe De Administracion"
      ],
      [
        "competence" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "subCompetence" => "Gestión De Consecuencias",
        "category" => "Jefe De Administracion"
      ],
      [
        "competence" => "Gestión de Proveedores - Admin: Controlar el cumplimiento de los acuerdos del proveedor, interviene ante los problemas y reclamos en la entrega de mercadería.",
        "subCompetence" => "Identificación",
        "category" => "Jefe De Administracion"
      ],
      [
        "competence" => "Gestión de Proveedores - Admin: Controlar el cumplimiento de los acuerdos del proveedor, interviene ante los problemas y reclamos en la entrega de mercadería.",
        "subCompetence" => "Manejo De Reclamos",
        "category" => "Jefe De Administracion"
      ],
      [
        "competence" => "Gestión de Proveedores - Admin: Controlar el cumplimiento de los acuerdos del proveedor, interviene ante los problemas y reclamos en la entrega de mercadería.",
        "subCompetence" => "Seguimiento A Proveedores",
        "category" => "Jefe De Administracion"
      ],
      [
        "competence" => "Gestión de Proveedores - Admin: Controlar el cumplimiento de los acuerdos del proveedor, interviene ante los problemas y reclamos en la entrega de mercadería.",
        "subCompetence" => "Solución De Problemas Con Proveedores",
        "category" => "Jefe De Administracion"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Proactividad",
        "category" => "Jefe De Administracion"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Resultados",
        "category" => "Jefe De Administracion"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Cumplimiento De Acuerdos",
        "category" => "Jefe De Administracion"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Solución De Problema",
        "category" => "Jefe De Administracion"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Asumo Las Consecuencias",
        "category" => "Jefe De Administracion"
      ],
      [
        "competence" => "Organización y Control de Recursos JA",
        "subCompetence" => "Manejo De Procedimientos",
        "category" => "Jefe De Administracion"
      ],
      [
        "competence" => "Organización y Control de Recursos JA",
        "subCompetence" => "Organización Y Clasificación",
        "category" => "Jefe De Administracion"
      ],
      [
        "competence" => "Organización y Control de Recursos JA",
        "subCompetence" => "Control De Recursos Ja",
        "category" => "Jefe De Administracion"
      ],
      [
        "competence" => "Organización y Control de Recursos JA",
        "subCompetence" => "Solicitud Oportuna",
        "category" => "Jefe De Administracion"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Cumplimiento De Procedimientos",
        "category" => "Jefe De Administracion"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Organización De Información",
        "category" => "Jefe De Administracion"
      ],
      [
        "competence" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "subCompetence" => "Orientación Al Registro Detallado",
        "category" => "Jefe De Administracion"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Asertividad",
        "category" => "Jefe De Ventas Dp"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Respuesta Oportuna",
        "category" => "Jefe De Ventas Dp"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Claridad En El Mensaje",
        "category" => "Jefe De Ventas Dp"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Escucha",
        "category" => "Jefe De Ventas Dp"
      ],
      [
        "competence" => "Gestión Comercial: Define las estrategias comerciales, objetivos y metas de acuerdo al conocimiento del negocio, producto y el funcionamiento de la competencia.",
        "subCompetence" => "Conocimiento De La Competencia Comercial",
        "category" => "Jefe De Ventas Dp"
      ],
      [
        "competence" => "Gestión Comercial: Define las estrategias comerciales, objetivos y metas de acuerdo al conocimiento del negocio, producto y el funcionamiento de la competencia.",
        "subCompetence" => "Reajustes Comerciales V1",
        "category" => "Jefe De Ventas Dp"
      ],
      [
        "competence" => "Gestión Comercial: Define las estrategias comerciales, objetivos y metas de acuerdo al conocimiento del negocio, producto y el funcionamiento de la competencia.",
        "subCompetence" => "Definición Estrategias, Objetivos Y Metas",
        "category" => "Jefe De Ventas Dp"
      ],
      [
        "competence" => "Gestión Comercial: Define las estrategias comerciales, objetivos y metas de acuerdo al conocimiento del negocio, producto y el funcionamiento de la competencia.",
        "subCompetence" => "Conocimiento Del Negocio Comercial",
        "category" => "Jefe De Ventas Dp"
      ],
      [
        "competence" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "subCompetence" => "Control",
        "category" => "Jefe De Ventas Dp"
      ],
      [
        "competence" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "subCompetence" => "Supervisión",
        "category" => "Jefe De Ventas Dp"
      ],
      [
        "competence" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "subCompetence" => "Seguimiento",
        "category" => "Jefe De Ventas Dp"
      ],
      [
        "competence" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "subCompetence" => "Retroalimentación",
        "category" => "Jefe De Ventas Dp"
      ],
      [
        "competence" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "subCompetence" => "Gestión De Consecuencias",
        "category" => "Jefe De Ventas Dp"
      ],
      [
        "competence" => "Gestión de Proveedores - Comercial: Garantizar una adecuada relación con los proveedores. Lograr convencerlos para obtener beneficios preferenciales manejando de manera adecuada los reclamos.",
        "subCompetence" => "Seguimiento De Cobranzas",
        "category" => "Jefe De Ventas Dp"
      ],
      [
        "competence" => "Gestión de Proveedores - Comercial: Garantizar una adecuada relación con los proveedores. Lograr convencerlos para obtener beneficios preferenciales manejando de manera adecuada los reclamos.",
        "subCompetence" => "Solución De Problemas V1",
        "category" => "Jefe De Ventas Dp"
      ],
      [
        "competence" => "Gestión de Proveedores - Comercial: Garantizar una adecuada relación con los proveedores. Lograr convencerlos para obtener beneficios preferenciales manejando de manera adecuada los reclamos.",
        "subCompetence" => "Relación Con El Proveedor",
        "category" => "Jefe De Ventas Dp"
      ],
      [
        "competence" => "Gestión de Proveedores - Comercial: Garantizar una adecuada relación con los proveedores. Lograr convencerlos para obtener beneficios preferenciales manejando de manera adecuada los reclamos.",
        "subCompetence" => "Persuasión",
        "category" => "Jefe De Ventas Dp"
      ],
      [
        "competence" => "Gestión de Proveedores - Comercial: Garantizar una adecuada relación con los proveedores. Lograr convencerlos para obtener beneficios preferenciales manejando de manera adecuada los reclamos.",
        "subCompetence" => "Traspase De Información",
        "category" => "Jefe De Ventas Dp"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Proactividad",
        "category" => "Jefe De Ventas Dp"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Solución De Problema",
        "category" => "Jefe De Ventas Dp"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Resultados",
        "category" => "Jefe De Ventas Dp"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Cumplimiento De Acuerdos",
        "category" => "Jefe De Ventas Dp"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Asumo Las Consecuencias",
        "category" => "Jefe De Ventas Dp"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Asertividad",
        "category" => "Supervisor De Almacen"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Respuesta Oportuna",
        "category" => "Supervisor De Almacen"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Claridad En El Mensaje",
        "category" => "Supervisor De Almacen"
      ],
      [
        "competence" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "subCompetence" => "Escucha",
        "category" => "Supervisor De Almacen"
      ],
      [
        "competence" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "subCompetence" => "Supervisión",
        "category" => "Supervisor De Almacen"
      ],
      [
        "competence" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "subCompetence" => "Seguimiento",
        "category" => "Supervisor De Almacen"
      ],
      [
        "competence" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "subCompetence" => "Retroalimentación",
        "category" => "Supervisor De Almacen"
      ],
      [
        "competence" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "subCompetence" => "Gestión De Consecuencias",
        "category" => "Supervisor De Almacen"
      ],
      [
        "competence" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "subCompetence" => "Control",
        "category" => "Supervisor De Almacen"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Cumplimiento De Acuerdos",
        "category" => "Supervisor De Almacen"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Proactividad",
        "category" => "Supervisor De Almacen"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Resultados",
        "category" => "Supervisor De Almacen"
      ],
      [
        "competence" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "subCompetence" => "Asumo Las Consecuencias",
        "category" => "Supervisor De Almacen"
      ],
      [
        "competence" => "Organización y Control: Garantizar el uso de recursos a través de una adecuada organización, clasificación y control.",
        "subCompetence" => "Control De Recursos",
        "category" => "Supervisor De Almacen"
      ],
      [
        "competence" => "Organización y Control: Garantizar el uso de recursos a través de una adecuada organización, clasificación y control.",
        "subCompetence" => "Organización De Recursos Internos",
        "category" => "Supervisor De Almacen"
      ],
      [
        "competence" => "Organización y Control: Garantizar el uso de recursos a través de una adecuada organización, clasificación y control.",
        "subCompetence" => "Protección De Recursos De La Organización",
        "category" => "Supervisor De Almacen"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Atención Presencial Oportuna",
        "category" => "Supervisor De Almacen"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Atención Efectiva A Consultas",
        "category" => "Supervisor De Almacen"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Seguimiento A La Atención",
        "category" => "Supervisor De Almacen"
      ],
      [
        "competence" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "subCompetence" => "Amabilidad Y Cercanía",
        "category" => "Supervisor De Almacen"
      ]
    ];

    DB::statement('SET FOREIGN_KEY_CHECKS=0;');

    EvaluationPersonCycleDetail::query()->truncate();
    EvaluationCategoryCompetenceDetail::query()->truncate();
    EvaluationCycle::query()->truncate();
    EvaluationSubCompetence::query()->truncate();
    EvaluationCompetence::query()->truncate();

    DB::statement('SET FOREIGN_KEY_CHECKS=1;');

    /**
     * "competence"
     * "subCompetence"
     * "category"
     */


//    2. Definir servicios
    $categoryCompetenceService = new EvaluationCategoryCompetenceDetailService();

//    3. Recorrer el array e insertar los datos en las tablas correspondientes
    foreach ($data as $item) {
      $category = HierarchicalCategory::where('name', $item['category'])->first();
      if ($category) {
        $competence = EvaluationCompetence::firstOrCreate([
          'nombre' => $item['competence'],
          'grupo_cargos_id' => 1, // Eliminar cargo
          'status_delete' => 0
        ]);


        EvaluationSubCompetence::firstOrCreate([
          'nombre' => $item['subCompetence'],
          'competencia_id' => $competence->id,
        ]);

        $categoryCompetenceService->store([
          'category_id' => $category->id,
          'competence_id' => $competence->id,
        ]);
      }
    }

//    $categoryCompetenceService->assignCompetencesToWorkers();

  }
}
