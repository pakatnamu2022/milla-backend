<?php

namespace Database\Seeders\gp\evaluation;

use App\Http\Services\gp\gestionhumana\evaluacion\EvaluationCategoryCompetenceDetailService;
use App\Models\gp\gestionhumana\evaluacion\EvaluationCategoryCompetenceDetail;
use App\Models\gp\gestionhumana\evaluacion\EvaluationCompetence;
use App\Models\gp\gestionhumana\evaluacion\EvaluationCycle;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonCompetenceDetail;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPersonCycleDetail;
use App\Models\gp\gestionhumana\evaluacion\EvaluationSubCompetence;
use App\Models\gp\gestionhumana\evaluacion\HierarchicalCategory;
use App\Models\gp\gestionhumana\evaluacion\HierarchicalCategoryDetail;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class Competence2Seeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    /**
     * [
     * "CATEGORIA" => "Agente De Seguridad",
     * "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
     * "SUBCOMPETENCIA" => "Asertividad",
     * "DESCRIPCION" => ""
     * ],
     */
    $data = [
      [
        "CATEGORIA" => "Agente De Seguridad",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Asertividad",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Agente De Seguridad",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Claridad en el mensaje",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Agente De Seguridad",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Escucha",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Agente De Seguridad",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Informe Escrito",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Agente De Seguridad",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Orientación A La Meta Común",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Agente De Seguridad",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Respuesta Oportuna",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Agente De Seguridad",
        "COMPETENCIA" => "Gestión de normas y procedimientos: Se preocupa y promueve respeto de las normas y procedimientos. Vigila el cumplimiento de las normas e interviene para corregir faltas.",
        "SUBCOMPETENCIA" => "Claridad En La Orientación",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Agente De Seguridad",
        "COMPETENCIA" => "Gestión de normas y procedimientos: Se preocupa y promueve respeto de las normas y procedimientos. Vigila el cumplimiento de las normas e interviene para corregir faltas.",
        "SUBCOMPETENCIA" => "Gestión De Consecuencias V1",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Agente De Seguridad",
        "COMPETENCIA" => "Gestión de normas y procedimientos: Se preocupa y promueve respeto de las normas y procedimientos. Vigila el cumplimiento de las normas e interviene para corregir faltas.",
        "SUBCOMPETENCIA" => "Respeto A La Norma",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Agente De Seguridad",
        "COMPETENCIA" => "Gestión de normas y procedimientos: Se preocupa y promueve respeto de las normas y procedimientos. Vigila el cumplimiento de las normas e interviene para corregir faltas.",
        "SUBCOMPETENCIA" => "Revisión Y Seguimiento",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Agente De Seguridad",
        "COMPETENCIA" => "Pensamiento claro y tranquilo: Mantiene la calma necesaria para poder reaccionar de manera correcta y proactiva ante cualquier situacion de riesgo.",
        "SUBCOMPETENCIA" => "Calma Ante La Crisis",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Agente De Seguridad",
        "COMPETENCIA" => "Pensamiento claro y tranquilo: Mantiene la calma necesaria para poder reaccionar de manera correcta y proactiva ante cualquier situacion de riesgo.",
        "SUBCOMPETENCIA" => "Respuesta Anticipada",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Agente De Seguridad",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Amabilidad Y Cercanía",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Agente De Seguridad",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Amabilidad Y Cercanía (seguridad)",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Agente De Seguridad",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Amabilidad Y Cercanía Con El Colaborador",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Agente De Seguridad",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Atención Efectiva A Consultas",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Agente De Seguridad",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Atención Efectiva A Consultas (seguridad)",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Agente De Seguridad",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Atención Presencial Oportuna",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Agente De Seguridad",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Atención Presencial Oportuna (seguridad)",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Agente De Seguridad",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Seguimiento A La Atención",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Analista De Contabilidad AP",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Claridad en el mensaje",
        "DESCRIPCION" => "Comunica sus ideas de forma clara y precisa."
      ],
      [
        "CATEGORIA" => "Analista De Contabilidad AP",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Respuesta Oportuna",
        "DESCRIPCION" => "Comparte información e ideas en el momento adecuado."
      ],
      [
        "CATEGORIA" => "Analista De Contabilidad AP",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Escucha",
        "DESCRIPCION" => "Escucha atentamente las opiniones y sugerencias."
      ],
      [
        "CATEGORIA" => "Analista De Contabilidad AP",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Asertividad",
        "DESCRIPCION" => "Expresa sus opiniones sin herir los sentimientos de los demás."
      ],
      [
        "CATEGORIA" => "Analista De Contabilidad AP",
        "COMPETENCIA" => "Habilidad analítica: Capacidad de razonamiento y organización cognitivamente del trabajo para realizar un análisis lógico identificando problemas, información, datos significativos y soluciones.",
        "SUBCOMPETENCIA" => "Capacidad de comprensión",
        "DESCRIPCION" => "Comprende los procesos relacionados con su trabajo y áreas con las que se relaciona y el porque de su aplicación."
      ],
      [
        "CATEGORIA" => "Analista De Contabilidad AP",
        "COMPETENCIA" => "Habilidad analítica: Capacidad de razonamiento y organización cognitivamente del trabajo para realizar un análisis lógico identificando problemas, información, datos significativos y soluciones.",
        "SUBCOMPETENCIA" => "Tratamiento de la información",
        "DESCRIPCION" => "Identifica, analiza, organiza y presenta información para establecer conexiones relevantes entre datos."
      ],
      [
        "CATEGORIA" => "Analista De Contabilidad AP",
        "COMPETENCIA" => "Habilidad analítica: Capacidad de razonamiento y organización cognitivamente del trabajo para realizar un análisis lógico identificando problemas, información, datos significativos y soluciones.",
        "SUBCOMPETENCIA" => "Análisis lógico",
        "DESCRIPCION" => "Logra identificar, entender y presentar las causas e impacto de la información y hechos que analiza."
      ],
      [
        "CATEGORIA" => "Analista De Contabilidad AP",
        "COMPETENCIA" => "Habilidad analítica: Capacidad de razonamiento y organización cognitivamente del trabajo para realizar un análisis lógico identificando problemas, información, datos significativos y soluciones.",
        "SUBCOMPETENCIA" => "Identificación de problemas",
        "DESCRIPCION" => "Identifica problemas analizando, organizando y conectando datos relevantes."
      ],
      [
        "CATEGORIA" => "Analista De Contabilidad AP",
        "COMPETENCIA" => "Perspicacia Contable: Garantizar el correcto registro de los ingresos de la organización identificando posibles riesgos y auditando. Actuar con transparencia durante la auditoría.",
        "SUBCOMPETENCIA" => "Auditorías Oportunas",
        "DESCRIPCION" => "Realiza revisiones sorpresa para la verificación del cumplimiento de la normativa."
      ],
      [
        "CATEGORIA" => "Analista De Contabilidad AP",
        "COMPETENCIA" => "Perspicacia Contable: Garantizar el correcto registro de los ingresos de la organización identificando posibles riesgos y auditando. Actuar con transparencia durante la auditoría.",
        "SUBCOMPETENCIA" => "Control de información Sensible",
        "DESCRIPCION" => "Controla de manera adecuada la información contable y financiera de la empresa"
      ],
      [
        "CATEGORIA" => "Analista De Contabilidad AP",
        "COMPETENCIA" => "Perspicacia Contable: Garantizar el correcto registro de los ingresos de la organización identificando posibles riesgos y auditando. Actuar con transparencia durante la auditoría.",
        "SUBCOMPETENCIA" => "Fraudes",
        "DESCRIPCION" => "Identifica posibles actos fraudulentos que impacten en la rentabilidad."
      ],
      [
        "CATEGORIA" => "Analista De Contabilidad AP",
        "COMPETENCIA" => "Perspicacia Contable: Garantizar el correcto registro de los ingresos de la organización identificando posibles riesgos y auditando. Actuar con transparencia durante la auditoría.",
        "SUBCOMPETENCIA" => "Congruente",
        "DESCRIPCION" => "Actúa con honestidad, sus acciones son confiables para la empresa."
      ],
      [
        "CATEGORIA" => "Analista De Contabilidad AP",
        "COMPETENCIA" => "Perspicacia Contable: Garantizar el correcto registro de los ingresos de la organización identificando posibles riesgos y auditando. Actuar con transparencia durante la auditoría.",
        "SUBCOMPETENCIA" => "Análisis de información Contable",
        "DESCRIPCION" => "Interpreta de manera adecuada la información contable y la información financiera para la toma de decisiones gerenciales."
      ],
      [
        "CATEGORIA" => "Analista De Contabilidad AP",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Orientación al Detalle",
        "DESCRIPCION" => "Es minucioso en el manejo de datos."
      ],
      [
        "CATEGORIA" => "Analista De Contabilidad AP",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Organización",
        "DESCRIPCION" => "Mantiene un orden en los documentos que maneja."
      ],
      [
        "CATEGORIA" => "Analista De Contabilidad AP",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Cumplimiento de Procesos Operativos",
        "DESCRIPCION" => "Cumple con los procedimientos establecidos por la organización de manera correcta."
      ],
      [
        "CATEGORIA" => "Analista De Contabilidad AP",
        "COMPETENCIA" => "Gestión Contable: Analiza de manera adecuada la información contable y define acciones adecuadas para el sistema de contable.",
        "SUBCOMPETENCIA" => "Análisis de estados financieros",
        "DESCRIPCION" => "Analiza de manera efectiva los estados financieros."
      ],
      [
        "CATEGORIA" => "Analista De Contabilidad AP",
        "COMPETENCIA" => "Gestión Contable: Analiza de manera adecuada la información contable y define acciones adecuadas para el sistema de contable.",
        "SUBCOMPETENCIA" => "Sistemas correctivos",
        "DESCRIPCION" => "Realiza acciones adecuadas para mejorar los sistemas correctivos."
      ],
      [
        "CATEGORIA" => "Analista De Contabilidad AP",
        "COMPETENCIA" => "Gestión Contable: Analiza de manera adecuada la información contable y define acciones adecuadas para el sistema de contable.",
        "SUBCOMPETENCIA" => "Plazos de Entrega",
        "DESCRIPCION" => "Cumple con los plazos establecidos para las declaraciones en las instituciones."
      ],
      [
        "CATEGORIA" => "Analista De Contabilidad AP",
        "COMPETENCIA" => "Gestión Contable: Analiza de manera adecuada la información contable y define acciones adecuadas para el sistema de contable.",
        "SUBCOMPETENCIA" => "Informes contables",
        "DESCRIPCION" => "Ejecuta informes financieros con información adecuada."
      ],
      [
        "CATEGORIA" => "Analista De Contabilidad DP",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Claridad en el mensaje",
        "DESCRIPCION" => "Comunica sus ideas de forma clara y precisa."
      ],
      [
        "CATEGORIA" => "Analista De Contabilidad DP",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Respuesta Oportuna",
        "DESCRIPCION" => "Comparte información e ideas en el momento adecuado."
      ],
      [
        "CATEGORIA" => "Analista De Contabilidad DP",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Escucha",
        "DESCRIPCION" => "Escucha atentamente las opiniones y sugerencias."
      ],
      [
        "CATEGORIA" => "Analista De Contabilidad DP",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Asertividad",
        "DESCRIPCION" => "Expresa sus opiniones sin herir los sentimientos de los demás."
      ],
      [
        "CATEGORIA" => "Analista De Contabilidad DP",
        "COMPETENCIA" => "Habilidad analítica: Capacidad de razonamiento y organización cognitivamente del trabajo para realizar un análisis lógico identificando problemas, información, datos significativos y soluciones.",
        "SUBCOMPETENCIA" => "Capacidad de comprensión",
        "DESCRIPCION" => "Comprende los procesos relacionados con su trabajo y áreas con las que se relaciona y el porque de su aplicación."
      ],
      [
        "CATEGORIA" => "Analista De Contabilidad DP",
        "COMPETENCIA" => "Habilidad analítica: Capacidad de razonamiento y organización cognitivamente del trabajo para realizar un análisis lógico identificando problemas, información, datos significativos y soluciones.",
        "SUBCOMPETENCIA" => "Tratamiento de la información",
        "DESCRIPCION" => "Identifica, analiza, organiza y presenta información para establecer conexiones relevantes entre datos."
      ],
      [
        "CATEGORIA" => "Analista De Contabilidad DP",
        "COMPETENCIA" => "Habilidad analítica: Capacidad de razonamiento y organización cognitivamente del trabajo para realizar un análisis lógico identificando problemas, información, datos significativos y soluciones.",
        "SUBCOMPETENCIA" => "Análisis lógico",
        "DESCRIPCION" => "Logra identificar, entender y presentar las causas e impacto de la información y hechos que analiza."
      ],
      [
        "CATEGORIA" => "Analista De Contabilidad DP",
        "COMPETENCIA" => "Habilidad analítica: Capacidad de razonamiento y organización cognitivamente del trabajo para realizar un análisis lógico identificando problemas, información, datos significativos y soluciones.",
        "SUBCOMPETENCIA" => "Identificación de problemas",
        "DESCRIPCION" => "Identifica problemas analizando, organizando y conectando datos relevantes."
      ],
      [
        "CATEGORIA" => "Analista De Contabilidad DP",
        "COMPETENCIA" => "Perspicacia Contable: Garantizar el correcto registro de los ingresos de la organización identificando posibles riesgos y auditando. Actuar con transparencia durante la auditoría.",
        "SUBCOMPETENCIA" => "Auditorías Oportunas",
        "DESCRIPCION" => "Realiza revisiones sorpresa para la verificación del cumplimiento de la normativa."
      ],
      [
        "CATEGORIA" => "Analista De Contabilidad DP",
        "COMPETENCIA" => "Perspicacia Contable: Garantizar el correcto registro de los ingresos de la organización identificando posibles riesgos y auditando. Actuar con transparencia durante la auditoría.",
        "SUBCOMPETENCIA" => "Control de información Sensible",
        "DESCRIPCION" => "Controla de manera adecuada la información contable y financiera de la empresa"
      ],
      [
        "CATEGORIA" => "Analista De Contabilidad DP",
        "COMPETENCIA" => "Perspicacia Contable: Garantizar el correcto registro de los ingresos de la organización identificando posibles riesgos y auditando. Actuar con transparencia durante la auditoría.",
        "SUBCOMPETENCIA" => "Fraudes",
        "DESCRIPCION" => "Identifica posibles actos fraudulentos que impacten en la rentabilidad."
      ],
      [
        "CATEGORIA" => "Analista De Contabilidad DP",
        "COMPETENCIA" => "Perspicacia Contable: Garantizar el correcto registro de los ingresos de la organización identificando posibles riesgos y auditando. Actuar con transparencia durante la auditoría.",
        "SUBCOMPETENCIA" => "Congruente",
        "DESCRIPCION" => "Actúa con honestidad, sus acciones son confiables para la empresa."
      ],
      [
        "CATEGORIA" => "Analista De Contabilidad DP",
        "COMPETENCIA" => "Perspicacia Contable: Garantizar el correcto registro de los ingresos de la organización identificando posibles riesgos y auditando. Actuar con transparencia durante la auditoría.",
        "SUBCOMPETENCIA" => "Análisis de información Contable",
        "DESCRIPCION" => "Interpreta de manera adecuada la información contable y la información financiera para la toma de decisiones gerenciales."
      ],
      [
        "CATEGORIA" => "Analista De Contabilidad DP",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Orientación al Detalle",
        "DESCRIPCION" => "Es minucioso en el manejo de datos."
      ],
      [
        "CATEGORIA" => "Analista De Contabilidad DP",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Organización",
        "DESCRIPCION" => "Mantiene un orden en los documentos que maneja."
      ],
      [
        "CATEGORIA" => "Analista De Contabilidad DP",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Cumplimiento de Procesos Operativos",
        "DESCRIPCION" => "Cumple con los procedimientos establecidos por la organización de manera correcta."
      ],
      [
        "CATEGORIA" => "Analista De Contabilidad DP",
        "COMPETENCIA" => "Gestión Contable: Analiza de manera adecuada la información contable y define acciones adecuadas para el sistema de contable.",
        "SUBCOMPETENCIA" => "Análisis de estados financieros",
        "DESCRIPCION" => "Analiza de manera efectiva los estados financieros."
      ],
      [
        "CATEGORIA" => "Analista De Contabilidad DP",
        "COMPETENCIA" => "Gestión Contable: Analiza de manera adecuada la información contable y define acciones adecuadas para el sistema de contable.",
        "SUBCOMPETENCIA" => "Sistemas correctivos",
        "DESCRIPCION" => "Realiza acciones adecuadas para mejorar los sistemas correctivos."
      ],
      [
        "CATEGORIA" => "Analista De Contabilidad DP",
        "COMPETENCIA" => "Gestión Contable: Analiza de manera adecuada la información contable y define acciones adecuadas para el sistema de contable.",
        "SUBCOMPETENCIA" => "Plazos de Entrega",
        "DESCRIPCION" => "Cumple con los plazos establecidos para las declaraciones en las instituciones."
      ],
      [
        "CATEGORIA" => "Analista De Contabilidad DP",
        "COMPETENCIA" => "Gestión Contable: Analiza de manera adecuada la información contable y define acciones adecuadas para el sistema de contable.",
        "SUBCOMPETENCIA" => "Informes contables",
        "DESCRIPCION" => "Ejecuta informes financieros con información adecuada."
      ],
      [
        "CATEGORIA" => "Analista De Contabilidad TP",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Claridad en el mensaje",
        "DESCRIPCION" => "Comunica sus ideas de forma clara y precisa."
      ],
      [
        "CATEGORIA" => "Analista De Contabilidad TP",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Respuesta Oportuna",
        "DESCRIPCION" => "Comparte información e ideas en el momento adecuado."
      ],
      [
        "CATEGORIA" => "Analista De Contabilidad TP",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Escucha",
        "DESCRIPCION" => "Escucha atentamente las opiniones y sugerencias."
      ],
      [
        "CATEGORIA" => "Analista De Contabilidad TP",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Asertividad",
        "DESCRIPCION" => "Expresa sus opiniones sin herir los sentimientos de los demás."
      ],
      [
        "CATEGORIA" => "Analista De Contabilidad TP",
        "COMPETENCIA" => "Habilidad analítica: Capacidad de razonamiento y organización cognitivamente del trabajo para realizar un análisis lógico identificando problemas, información, datos significativos y soluciones.",
        "SUBCOMPETENCIA" => "Capacidad de comprensión",
        "DESCRIPCION" => "Comprende los procesos relacionados con su trabajo y áreas con las que se relaciona y el porque de su aplicación."
      ],
      [
        "CATEGORIA" => "Analista De Contabilidad TP",
        "COMPETENCIA" => "Habilidad analítica: Capacidad de razonamiento y organización cognitivamente del trabajo para realizar un análisis lógico identificando problemas, información, datos significativos y soluciones.",
        "SUBCOMPETENCIA" => "Tratamiento de la información",
        "DESCRIPCION" => "Identifica, analiza, organiza y presenta información para establecer conexiones relevantes entre datos."
      ],
      [
        "CATEGORIA" => "Analista De Contabilidad TP",
        "COMPETENCIA" => "Habilidad analítica: Capacidad de razonamiento y organización cognitivamente del trabajo para realizar un análisis lógico identificando problemas, información, datos significativos y soluciones.",
        "SUBCOMPETENCIA" => "Análisis lógico",
        "DESCRIPCION" => "Logra identificar, entender y presentar las causas e impacto de la información y hechos que analiza."
      ],
      [
        "CATEGORIA" => "Analista De Contabilidad TP",
        "COMPETENCIA" => "Habilidad analítica: Capacidad de razonamiento y organización cognitivamente del trabajo para realizar un análisis lógico identificando problemas, información, datos significativos y soluciones.",
        "SUBCOMPETENCIA" => "Identificación de problemas",
        "DESCRIPCION" => "Identifica problemas analizando, organizando y conectando datos relevantes."
      ],
      [
        "CATEGORIA" => "Analista De Contabilidad TP",
        "COMPETENCIA" => "Perspicacia Contable: Garantizar el correcto registro de los ingresos de la organización identificando posibles riesgos y auditando. Actuar con transparencia durante la auditoría.",
        "SUBCOMPETENCIA" => "Auditorías Oportunas",
        "DESCRIPCION" => "Realiza revisiones sorpresa para la verificación del cumplimiento de la normativa."
      ],
      [
        "CATEGORIA" => "Analista De Contabilidad TP",
        "COMPETENCIA" => "Perspicacia Contable: Garantizar el correcto registro de los ingresos de la organización identificando posibles riesgos y auditando. Actuar con transparencia durante la auditoría.",
        "SUBCOMPETENCIA" => "Control de información Sensible",
        "DESCRIPCION" => "Controla de manera adecuada la información contable y financiera de la empresa"
      ],
      [
        "CATEGORIA" => "Analista De Contabilidad TP",
        "COMPETENCIA" => "Perspicacia Contable: Garantizar el correcto registro de los ingresos de la organización identificando posibles riesgos y auditando. Actuar con transparencia durante la auditoría.",
        "SUBCOMPETENCIA" => "Fraudes",
        "DESCRIPCION" => "Identifica posibles actos fraudulentos que impacten en la rentabilidad."
      ],
      [
        "CATEGORIA" => "Analista De Contabilidad TP",
        "COMPETENCIA" => "Perspicacia Contable: Garantizar el correcto registro de los ingresos de la organización identificando posibles riesgos y auditando. Actuar con transparencia durante la auditoría.",
        "SUBCOMPETENCIA" => "Congruente",
        "DESCRIPCION" => "Actúa con honestidad, sus acciones son confiables para la empresa."
      ],
      [
        "CATEGORIA" => "Analista De Contabilidad TP",
        "COMPETENCIA" => "Perspicacia Contable: Garantizar el correcto registro de los ingresos de la organización identificando posibles riesgos y auditando. Actuar con transparencia durante la auditoría.",
        "SUBCOMPETENCIA" => "Análisis de información Contable",
        "DESCRIPCION" => "Interpreta de manera adecuada la información contable y la información financiera para la toma de decisiones gerenciales."
      ],
      [
        "CATEGORIA" => "Analista De Contabilidad TP",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Orientación al Detalle",
        "DESCRIPCION" => "Es minucioso en el manejo de datos."
      ],
      [
        "CATEGORIA" => "Analista De Contabilidad TP",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Organización",
        "DESCRIPCION" => "Mantiene un orden en los documentos que maneja."
      ],
      [
        "CATEGORIA" => "Analista De Contabilidad TP",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Cumplimiento de Procesos Operativos",
        "DESCRIPCION" => "Cumple con los procedimientos establecidos por la organización de manera correcta."
      ],
      [
        "CATEGORIA" => "Analista De Contabilidad TP",
        "COMPETENCIA" => "Gestión Contable: Analiza de manera adecuada la información contable y define acciones adecuadas para el sistema de contable.",
        "SUBCOMPETENCIA" => "Análisis de estados financieros",
        "DESCRIPCION" => "Analiza de manera efectiva los estados financieros."
      ],
      [
        "CATEGORIA" => "Analista De Contabilidad TP",
        "COMPETENCIA" => "Gestión Contable: Analiza de manera adecuada la información contable y define acciones adecuadas para el sistema de contable.",
        "SUBCOMPETENCIA" => "Sistemas correctivos",
        "DESCRIPCION" => "Realiza acciones adecuadas para mejorar los sistemas correctivos."
      ],
      [
        "CATEGORIA" => "Analista De Contabilidad TP",
        "COMPETENCIA" => "Gestión Contable: Analiza de manera adecuada la información contable y define acciones adecuadas para el sistema de contable.",
        "SUBCOMPETENCIA" => "Plazos de Entrega",
        "DESCRIPCION" => "Cumple con los plazos establecidos para las declaraciones en las instituciones."
      ],
      [
        "CATEGORIA" => "Analista De Contabilidad TP",
        "COMPETENCIA" => "Gestión Contable: Analiza de manera adecuada la información contable y define acciones adecuadas para el sistema de contable.",
        "SUBCOMPETENCIA" => "Informes contables",
        "DESCRIPCION" => "Ejecuta informes financieros con información adecuada."
      ],
      [
        "CATEGORIA" => "Analista de Negocio",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Asertividad",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Analista de Negocio",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Claridad en el mensaje",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Analista de Negocio",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Escucha",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Analista de Negocio",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Informe Escrito",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Analista de Negocio",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Orientación A La Meta Común",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Analista de Negocio",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Respuesta Oportuna",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Analista de Negocio",
        "COMPETENCIA" => "Gestión de Trámites: Garantiza la recepción y entrega de documentos asegurando que cumpla con los requisitos solicitados.",
        "SUBCOMPETENCIA" => "Construcción De Red De Contactos",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Analista de Negocio",
        "COMPETENCIA" => "Gestión de Trámites: Garantiza la recepción y entrega de documentos asegurando que cumpla con los requisitos solicitados.",
        "SUBCOMPETENCIA" => "Control Documentario",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Analista de Negocio",
        "COMPETENCIA" => "Gestión de Trámites: Garantiza la recepción y entrega de documentos asegurando que cumpla con los requisitos solicitados.",
        "SUBCOMPETENCIA" => "Despacho Documentario",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Analista de Negocio",
        "COMPETENCIA" => "Gestión de Trámites: Garantiza la recepción y entrega de documentos asegurando que cumpla con los requisitos solicitados.",
        "SUBCOMPETENCIA" => "Perseverancia En La Entrega",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Analista de Negocio",
        "COMPETENCIA" => "Perspicacia Contable: Garantizar el correcto registro de los ingresos de la organización identificando posibles riesgos y auditando. Actuar con transparencia durante la auditoría.",
        "SUBCOMPETENCIA" => "Análisis De Información Contable",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Analista de Negocio",
        "COMPETENCIA" => "Perspicacia Contable: Garantizar el correcto registro de los ingresos de la organización identificando posibles riesgos y auditando. Actuar con transparencia durante la auditoría.",
        "SUBCOMPETENCIA" => "Auditorías Oportunas",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Analista de Negocio",
        "COMPETENCIA" => "Perspicacia Contable: Garantizar el correcto registro de los ingresos de la organización identificando posibles riesgos y auditando. Actuar con transparencia durante la auditoría.",
        "SUBCOMPETENCIA" => "Congruente",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Analista de Negocio",
        "COMPETENCIA" => "Perspicacia Contable: Garantizar el correcto registro de los ingresos de la organización identificando posibles riesgos y auditando. Actuar con transparencia durante la auditoría.",
        "SUBCOMPETENCIA" => "Control De Información Sensible",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Analista de Negocio",
        "COMPETENCIA" => "Perspicacia Contable: Garantizar el correcto registro de los ingresos de la organización identificando posibles riesgos y auditando. Actuar con transparencia durante la auditoría.",
        "SUBCOMPETENCIA" => "Fraudes",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Analista de Negocio",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Cumplimiento De Procedimientos",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Analista de Negocio",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Organización De Información",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Analista de Negocio",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Orientación Al Registro Detallado",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Analista de Negocio",
        "COMPETENCIA" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "SUBCOMPETENCIA" => "Colaboración",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Analista de Negocio",
        "COMPETENCIA" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "SUBCOMPETENCIA" => "Pasión",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Analista de Negocio",
        "COMPETENCIA" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "SUBCOMPETENCIA" => "Responsabilidad",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Analista de Negocio",
        "COMPETENCIA" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "SUBCOMPETENCIA" => "Resuelve Conflictos",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Analista de Negocio",
        "COMPETENCIA" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "SUBCOMPETENCIA" => "Traspase De Información En Equipo",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Analista De Remuneraciones Y Compensaciones",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Gestión De Recursos",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Analista De Remuneraciones Y Compensaciones",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Metodología",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Analista De Remuneraciones Y Compensaciones",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Orientación Al Logro",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Analista De Remuneraciones Y Compensaciones",
        "COMPETENCIA" => "Gestión Contable: Analiza de manera adecuada la información contable y define acciones adecuadas para el sistema de contable.",
        "SUBCOMPETENCIA" => "Análisis De Estados Financieros",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Analista De Remuneraciones Y Compensaciones",
        "COMPETENCIA" => "Gestión Contable: Analiza de manera adecuada la información contable y define acciones adecuadas para el sistema de contable.",
        "SUBCOMPETENCIA" => "Informes Contables",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Analista De Remuneraciones Y Compensaciones",
        "COMPETENCIA" => "Gestión Contable: Analiza de manera adecuada la información contable y define acciones adecuadas para el sistema de contable.",
        "SUBCOMPETENCIA" => "Plazos De Entrega",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Analista De Remuneraciones Y Compensaciones",
        "COMPETENCIA" => "Gestión Contable: Analiza de manera adecuada la información contable y define acciones adecuadas para el sistema de contable.",
        "SUBCOMPETENCIA" => "Sistemas Correctivos",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Analista De Remuneraciones Y Compensaciones",
        "COMPETENCIA" => "Habilidad analítica: Capacidad de razonamiento y organización cognitivamente del trabajo para realizar un análisis lógico identificando problemas, información y datos significativos.",
        "SUBCOMPETENCIA" => "Análisis Lógico",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Analista De Remuneraciones Y Compensaciones",
        "COMPETENCIA" => "Habilidad analítica: Capacidad de razonamiento y organización cognitivamente del trabajo para realizar un análisis lógico identificando problemas, información y datos significativos.",
        "SUBCOMPETENCIA" => "Capacidad De Comprensión",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Analista De Remuneraciones Y Compensaciones",
        "COMPETENCIA" => "Habilidad analítica: Capacidad de razonamiento y organización cognitivamente del trabajo para realizar un análisis lógico identificando problemas, información y datos significativos.",
        "SUBCOMPETENCIA" => "Identificación De Problemas",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Analista De Remuneraciones Y Compensaciones",
        "COMPETENCIA" => "Habilidad analítica: Capacidad de razonamiento y organización cognitivamente del trabajo para realizar un análisis lógico identificando problemas, información y datos significativos.",
        "SUBCOMPETENCIA" => "Tratamiento De La Información",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Analista De Remuneraciones Y Compensaciones",
        "COMPETENCIA" => "Registro y Manejo de Información - Versión 2 AP: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Cumplimiento De Procesos Operativos",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Analista De Remuneraciones Y Compensaciones",
        "COMPETENCIA" => "Registro y Manejo de Información - Versión 2 AP: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Organización",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Analista De Remuneraciones Y Compensaciones",
        "COMPETENCIA" => "Registro y Manejo de Información - Versión 2 AP: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Orientación Al Detalle",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Analista En Proyecto TICs",
        "COMPETENCIA" => "Administración de Recursos Tecnológicos: Garantiza el adecuado funcionamiento de los recursos tecnológicos , previene cualquier inconveniente y toma acción ante las dificultades.",
        "SUBCOMPETENCIA" => "Intervención de problemas",
        "DESCRIPCION" => "Resuelve de manera oportuna las interrupciones, alteraciones o falla derivada del uso de TI"
      ],
      [
        "CATEGORIA" => "Analista En Proyecto TICs",
        "COMPETENCIA" => "Administración de Recursos Tecnológicos: Garantiza el adecuado funcionamiento de los recursos tecnológicos , previene cualquier inconveniente y toma acción ante las dificultades.",
        "SUBCOMPETENCIA" => "Control y Organización de Recursos TI",
        "DESCRIPCION" => "Tiene un orden y control en los recursos tecnológicos (PCs, mouse, licencias, etc)"
      ],
      [
        "CATEGORIA" => "Analista En Proyecto TICs",
        "COMPETENCIA" => "Administración de Recursos Tecnológicos: Garantiza el adecuado funcionamiento de los recursos tecnológicos , previene cualquier inconveniente y toma acción ante las dificultades.",
        "SUBCOMPETENCIA" => "Control de Licencias y Funcionamiento",
        "DESCRIPCION" => "Controla las licencias y funcionamiento óptimo de TI sin esperar su vencimiento o falta de funciomamiento."
      ],
      [
        "CATEGORIA" => "Analista En Proyecto TICs",
        "COMPETENCIA" => "Administración de Recursos Tecnológicos: Garantiza el adecuado funcionamiento de los recursos tecnológicos , previene cualquier inconveniente y toma acción ante las dificultades.",
        "SUBCOMPETENCIA" => "Prevención de recursos",
        "DESCRIPCION" => "Mantiene el cuidado adecuado a las fibras ópticas (cables) y redes."
      ],
      [
        "CATEGORIA" => "Analista En Proyecto TICs",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Claridad en el mensaje",
        "DESCRIPCION" => "Comunica sus ideas de forma clara y precisa."
      ],
      [
        "CATEGORIA" => "Analista En Proyecto TICs",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Respuesta Oportuna",
        "DESCRIPCION" => "Comparte información e ideas en el momento adecuado."
      ],
      [
        "CATEGORIA" => "Analista En Proyecto TICs",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Escucha",
        "DESCRIPCION" => "Escucha atentamente las opiniones y sugerencias."
      ],
      [
        "CATEGORIA" => "Analista En Proyecto TICs",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Asertividad",
        "DESCRIPCION" => "Expresa sus opiniones sin herir los sentimientos de los demás."
      ],
      [
        "CATEGORIA" => "Analista En Proyecto TICs",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Gestión de Recursos",
        "DESCRIPCION" => "Identifica y asigna los recursos de manera responsable."
      ],
      [
        "CATEGORIA" => "Analista En Proyecto TICs",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Metodología",
        "DESCRIPCION" => "Utiliza metodologías o procesos estandarizados que garantizan los resultados esperados."
      ],
      [
        "CATEGORIA" => "Analista En Proyecto TICs",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Orientación al logro",
        "DESCRIPCION" => "Logra los resultados esperados en los tiempos establecidos."
      ],
      [
        "CATEGORIA" => "Analista En Proyecto TICs",
        "COMPETENCIA" => "Servicio al Colaborador: Resuelve las consultas y solicitudes de los colaboradores con empatía y amabilidad. ",
        "SUBCOMPETENCIA" => "Atención Efectiva a consultas",
        "DESCRIPCION" => "Responde oportunamente a las solicitudes y consultas de los colaboradores."
      ],
      [
        "CATEGORIA" => "Analista En Proyecto TICs",
        "COMPETENCIA" => "Servicio al Colaborador: Resuelve las consultas y solicitudes de los colaboradores con empatía y amabilidad. ",
        "SUBCOMPETENCIA" => "Empatía",
        "DESCRIPCION" => "Muestra preocupación por los problemas que tienen los colaboradores."
      ],
      [
        "CATEGORIA" => "Analista En Proyecto TICs",
        "COMPETENCIA" => "Servicio al Colaborador: Resuelve las consultas y solicitudes de los colaboradores con empatía y amabilidad. ",
        "SUBCOMPETENCIA" => "Atención oportuna",
        "DESCRIPCION" => "Responde de manera oportuna ante las problemáticas de los colaboradores."
      ],
      [
        "CATEGORIA" => "Analista En Proyecto TICs",
        "COMPETENCIA" => "Servicio al Colaborador: Resuelve las consultas y solicitudes de los colaboradores con empatía y amabilidad. ",
        "SUBCOMPETENCIA" => "Amabilidad y cercanía",
        "DESCRIPCION" => "Trata al colaborador con amabilidad generando cercanía y confianza."
      ],
      [
        "CATEGORIA" => "Analista En Proyecto TICs",
        "COMPETENCIA" => "Habilidad analítica: Capacidad de razonamiento y organización cognitivamente del trabajo para realizar un análisis lógico identificando problemas, información, datos significativos y soluciones.",
        "SUBCOMPETENCIA" => "Capacidad de comprensión",
        "DESCRIPCION" => "Comprende los procesos relacionados con su trabajo y áreas con las que se relaciona y el porque de su aplicación."
      ],
      [
        "CATEGORIA" => "Analista En Proyecto TICs",
        "COMPETENCIA" => "Habilidad analítica: Capacidad de razonamiento y organización cognitivamente del trabajo para realizar un análisis lógico identificando problemas, información, datos significativos y soluciones.",
        "SUBCOMPETENCIA" => "Tratamiento de la información",
        "DESCRIPCION" => "Identifica, analiza, organiza y presenta información para establecer conexiones relevantes entre datos."
      ],
      [
        "CATEGORIA" => "Analista En Proyecto TICs",
        "COMPETENCIA" => "Habilidad analítica: Capacidad de razonamiento y organización cognitivamente del trabajo para realizar un análisis lógico identificando problemas, información, datos significativos y soluciones.",
        "SUBCOMPETENCIA" => "Análisis lógico",
        "DESCRIPCION" => "Logra identificar, entender y presentar las causas e impacto de la información y hechos que analiza."
      ],
      [
        "CATEGORIA" => "Analista En Proyectos De Gestion Humana",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Asertividad",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Analista En Proyectos De Gestion Humana",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Claridad en el mensaje",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Analista En Proyectos De Gestion Humana",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Escucha",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Analista En Proyectos De Gestion Humana",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Informe Escrito",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Analista En Proyectos De Gestion Humana",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Orientación A La Meta Común",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Analista En Proyectos De Gestion Humana",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Respuesta Oportuna",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Analista En Proyectos De Gestion Humana",
        "COMPETENCIA" => "Consultoría: Brinda alternativas de solución a partir del conocimiento del negocio; la identificación de las necesidades de los colaboradores y la organización.",
        "SUBCOMPETENCIA" => "Asesoría",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Analista En Proyectos De Gestion Humana",
        "COMPETENCIA" => "Consultoría: Brinda alternativas de solución a partir del conocimiento del negocio; la identificación de las necesidades de los colaboradores y la organización.",
        "SUBCOMPETENCIA" => "Conoce El Negocio",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Analista En Proyectos De Gestion Humana",
        "COMPETENCIA" => "Consultoría: Brinda alternativas de solución a partir del conocimiento del negocio; la identificación de las necesidades de los colaboradores y la organización.",
        "SUBCOMPETENCIA" => "Evaluación De Colaboradores",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Analista En Proyectos De Gestion Humana",
        "COMPETENCIA" => "Consultoría: Brinda alternativas de solución a partir del conocimiento del negocio; la identificación de las necesidades de los colaboradores y la organización.",
        "SUBCOMPETENCIA" => "Identificación De Expectativas Y Necesidades",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Analista En Proyectos De Gestion Humana",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Gestión De Recursos",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Analista En Proyectos De Gestion Humana",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Metodología",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Analista En Proyectos De Gestion Humana",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Orientación Al Logro",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Analista En Proyectos De Gestion Humana",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Cumplimiento De Procedimientos",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Analista En Proyectos De Gestion Humana",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Organización De Información",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Analista En Proyectos De Gestion Humana",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Orientación Al Registro Detallado",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Analista En Proyectos De Gestion Humana",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Amabilidad Y Cercanía",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Analista En Proyectos De Gestion Humana",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Amabilidad Y Cercanía (seguridad)",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Analista En Proyectos De Gestion Humana",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Amabilidad Y Cercanía Con El Colaborador",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Analista En Proyectos De Gestion Humana",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Atención Efectiva A Consultas",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Analista En Proyectos De Gestion Humana",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Atención Efectiva A Consultas (seguridad)",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Analista En Proyectos De Gestion Humana",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Atención Presencial Oportuna",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Analista En Proyectos De Gestion Humana",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Atención Presencial Oportuna (seguridad)",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Analista En Proyectos De Gestion Humana",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Seguimiento A La Atención",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Analista En Proyectos De Gestion Humana",
        "COMPETENCIA" => "Servicio al colaborador: Resuelve las consultas y solicitudes de los colaboradores con empatía y amabilidad.",
        "SUBCOMPETENCIA" => "Amabilidad Y Cercanía Con El Colaborador",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Analista En Proyectos De Gestion Humana",
        "COMPETENCIA" => "Servicio al colaborador: Resuelve las consultas y solicitudes de los colaboradores con empatía y amabilidad.",
        "SUBCOMPETENCIA" => "Atención Efectiva A Consultas De Colaboradores",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Analista En Proyectos De Gestion Humana",
        "COMPETENCIA" => "Servicio al colaborador: Resuelve las consultas y solicitudes de los colaboradores con empatía y amabilidad.",
        "SUBCOMPETENCIA" => "Atención Oportuna",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Analista En Proyectos De Gestion Humana",
        "COMPETENCIA" => "Servicio al colaborador: Resuelve las consultas y solicitudes de los colaboradores con empatía y amabilidad.",
        "SUBCOMPETENCIA" => "Empatía",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Asesor Comercial",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Claridad en el mensaje",
        "DESCRIPCION" => "Comunica sus ideas de forma clara y precisa."
      ],
      [
        "CATEGORIA" => "Asesor Comercial",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Respuesta Oportuna",
        "DESCRIPCION" => "Comparte información e ideas en el momento adecuado."
      ],
      [
        "CATEGORIA" => "Asesor Comercial",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Escucha",
        "DESCRIPCION" => "Escucha atentamente las opiniones y sugerencias."
      ],
      [
        "CATEGORIA" => "Asesor Comercial",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Asertividad",
        "DESCRIPCION" => "Expresa sus opiniones sin herir los sentimientos de los demás."
      ],
      [
        "CATEGORIA" => "Asesor Comercial",
        "COMPETENCIA" => "Habilidad Comercial: Logra persuadir al cliente a través de argumentos poderosos, realiza seguimiento oportuno manteniéndose cerca del cliente y aprovechando oportunidades.",
        "SUBCOMPETENCIA" => "Disciplina Comercial",
        "DESCRIPCION" => "Cumple de manera rigurosa con la metodología comercial sin bajar la guardia."
      ],
      [
        "CATEGORIA" => "Asesor Comercial",
        "COMPETENCIA" => "Habilidad Comercial: Logra persuadir al cliente a través de argumentos poderosos, realiza seguimiento oportuno manteniéndose cerca del cliente y aprovechando oportunidades.",
        "SUBCOMPETENCIA" => "Negociación",
        "DESCRIPCION" => "Negocia considerando los beneficios del cliente y los beneficios de la empresa."
      ],
      [
        "CATEGORIA" => "Asesor Comercial",
        "COMPETENCIA" => "Habilidad Comercial: Logra persuadir al cliente a través de argumentos poderosos, realiza seguimiento oportuno manteniéndose cerca del cliente y aprovechando oportunidades.",
        "SUBCOMPETENCIA" => "Seguimiento",
        "DESCRIPCION" => "Realiza seguimiento al cliente sobre su decisión, se mantiene cerca y realiza un seguimiento de cobranza efectiva."
      ],
      [
        "CATEGORIA" => "Asesor Comercial",
        "COMPETENCIA" => "Habilidad Comercial: Logra persuadir al cliente a través de argumentos poderosos, realiza seguimiento oportuno manteniéndose cerca del cliente y aprovechando oportunidades.",
        "SUBCOMPETENCIA" => "Manejo de Objeciones",
        "DESCRIPCION" => "Cuando el cliente se opone o tiene una idea negativa, le respondo con seguridad utilizando las fortalezas, ventajas y beneficios de la organización."
      ],
      [
        "CATEGORIA" => "Asesor Comercial",
        "COMPETENCIA" => "Negociación: Capacidad para dirigir o controlar una discusión utilizando técnicas ganar-ganar planificando alternativas para negociar los mejores acuerdos.",
        "SUBCOMPETENCIA" => "Planificación de la estrategia",
        "DESCRIPCION" => "Planifica la estrategia de negociación a utilizar en el momento de la verdad."
      ],
      [
        "CATEGORIA" => "Asesor Comercial",
        "COMPETENCIA" => "Negociación: Capacidad para dirigir o controlar una discusión utilizando técnicas ganar-ganar planificando alternativas para negociar los mejores acuerdos.",
        "SUBCOMPETENCIA" => "Recolección de información",
        "DESCRIPCION" => "Reune toda la información necesaria para tener opiniones y argumentos sólidos."
      ],
      [
        "CATEGORIA" => "Asesor Comercial",
        "COMPETENCIA" => "Negociación: Capacidad para dirigir o controlar una discusión utilizando técnicas ganar-ganar planificando alternativas para negociar los mejores acuerdos.",
        "SUBCOMPETENCIA" => "Comunicación asertiva",
        "DESCRIPCION" => "Durante el proceso de comunicación en la negociación sus mensajes son emitidos de manera asertiva."
      ],
      [
        "CATEGORIA" => "Asesor Comercial",
        "COMPETENCIA" => "Negociación: Capacidad para dirigir o controlar una discusión utilizando técnicas ganar-ganar planificando alternativas para negociar los mejores acuerdos.",
        "SUBCOMPETENCIA" => "Despacho documentario",
        "DESCRIPCION" => "Concreta acuerdos exitosos y satisfactorios para la organización."
      ],
      [
        "CATEGORIA" => "Asesor Comercial",
        "COMPETENCIA" => "Persuasión: Convencer e influir en los procesos de toma de decisiones de los clientes con el fin de lograr que éstos sigan una línea de acción alineada a concretar las ventas.",
        "SUBCOMPETENCIA" => "Adaptación",
        "DESCRIPCION" => "Se adapta con facilidad a las pautas culturales, entorno, lenguaje y código del cliente con la finalidad generar vínculos de confianza con el cliente."
      ],
      [
        "CATEGORIA" => "Asesor Comercial",
        "COMPETENCIA" => "Persuasión: Convencer e influir en los procesos de toma de decisiones de los clientes con el fin de lograr que éstos sigan una línea de acción alineada a concretar las ventas.",
        "SUBCOMPETENCIA" => "Impacto",
        "DESCRIPCION" => "Lleva a cabo todas las acciones necesarias para impactar al cliente y generar una sensación de asombro."
      ],
      [
        "CATEGORIA" => "Asesor Comercial",
        "COMPETENCIA" => "Persuasión: Convencer e influir en los procesos de toma de decisiones de los clientes con el fin de lograr que éstos sigan una línea de acción alineada a concretar las ventas.",
        "SUBCOMPETENCIA" => "Influencia",
        "DESCRIPCION" => "Utiliza métodos para generar influencia en sus clientes comunicando datos cuidadosamente."
      ],
      [
        "CATEGORIA" => "Asesor Comercial",
        "COMPETENCIA" => "Persuasión: Convencer e influir en los procesos de toma de decisiones de los clientes con el fin de lograr que éstos sigan una línea de acción alineada a concretar las ventas.",
        "SUBCOMPETENCIA" => "Anticipación",
        "DESCRIPCION" => "Anticipa dudas, consultas y reacciones con la finalidad de brindar respuesas asertivas al cliente."
      ],
      [
        "CATEGORIA" => "Asesor Comercial",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Orientación al Detalle",
        "DESCRIPCION" => "Es minucioso en el manejo de datos."
      ],
      [
        "CATEGORIA" => "Asesor Comercial",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Organización",
        "DESCRIPCION" => "Mantiene un orden en los documentos que maneja."
      ],
      [
        "CATEGORIA" => "Asesor Comercial",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Cumplimiento de Procesos Operativos",
        "DESCRIPCION" => "Cumple con los procedimientos establecidos por la organización de manera correcta."
      ],
      [
        "CATEGORIA" => "Asesor Comercial",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Atención Efectiva a consultas",
        "DESCRIPCION" => "Responde oportunamente a las solicitudes y consultas de los clientes."
      ],
      [
        "CATEGORIA" => "Asesor Comercial",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Seguimiento a la atención",
        "DESCRIPCION" => "Vigila la adecuada atención del cliente y pide al responsable a que se haga cargo."
      ],
      [
        "CATEGORIA" => "Asesor Comercial",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Atención presencial oportuna",
        "DESCRIPCION" => "Hace todo lo necesario para que el cliente sea bien atendido en el menor tiempo posible."
      ],
      [
        "CATEGORIA" => "Asesor Comercial",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Amabilidad y cercanía",
        "DESCRIPCION" => "Trata al cliente con amabilidad generando cercanía y confianza."
      ],
      [
        "CATEGORIA" => "Asesor De Repuestos",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Claridad en el mensaje",
        "DESCRIPCION" => "Comunica sus ideas de forma clara y precisa."
      ],
      [
        "CATEGORIA" => "Asesor De Repuestos",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Respuesta Oportuna",
        "DESCRIPCION" => "Comparte información e ideas en el momento adecuado."
      ],
      [
        "CATEGORIA" => "Asesor De Repuestos",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Escucha",
        "DESCRIPCION" => "Escucha atentamente las opiniones y sugerencias."
      ],
      [
        "CATEGORIA" => "Asesor De Repuestos",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Asertividad",
        "DESCRIPCION" => "Expresa sus opiniones sin herir los sentimientos de los demás."
      ],
      [
        "CATEGORIA" => "Asesor De Repuestos",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Orientación al Detalle",
        "DESCRIPCION" => "Es minucioso en el manejo de datos."
      ],
      [
        "CATEGORIA" => "Asesor De Repuestos",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Organización",
        "DESCRIPCION" => "Mantiene un orden en los documentos que maneja."
      ],
      [
        "CATEGORIA" => "Asesor De Repuestos",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Cumplimiento de Procesos Operativos",
        "DESCRIPCION" => "Cumple con los procedimientos establecidos por la organización de manera correcta."
      ],
      [
        "CATEGORIA" => "Asesor De Repuestos",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Atención Efectiva a consultas",
        "DESCRIPCION" => "Responde oportunamente a las solicitudes y consultas de los clientes."
      ],
      [
        "CATEGORIA" => "Asesor De Repuestos",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Seguimiento a la atención",
        "DESCRIPCION" => "Vigilo la adecuada atención del cliente y pido al responsable a que se haga cargo."
      ],
      [
        "CATEGORIA" => "Asesor De Repuestos",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Atención presencial oportuna",
        "DESCRIPCION" => "Hago todo lo necesario para que el cliente sea bien atendido en el menor tiempo posible."
      ],
      [
        "CATEGORIA" => "Asesor De Repuestos",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Amabilidad y cercanía",
        "DESCRIPCION" => "Trata al cliente con amabilidad generando cercanía y confianza."
      ],
      [
        "CATEGORIA" => "Asesor De Servicios",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Claridad en el mensaje",
        "DESCRIPCION" => "Comunica sus ideas de forma clara y precisa."
      ],
      [
        "CATEGORIA" => "Asesor De Servicios",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Respuesta Oportuna",
        "DESCRIPCION" => "Comparte información e ideas en el momento adecuado."
      ],
      [
        "CATEGORIA" => "Asesor De Servicios",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Escucha",
        "DESCRIPCION" => "Escucha atentamente las opiniones y sugerencias."
      ],
      [
        "CATEGORIA" => "Asesor De Servicios",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Asertividad",
        "DESCRIPCION" => "Expresa sus opiniones sin herir los sentimientos de los demás."
      ],
      [
        "CATEGORIA" => "Asesor De Servicios",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Gestión de Recursos",
        "DESCRIPCION" => "Identifica y asigna los recursos de manera responsable."
      ],
      [
        "CATEGORIA" => "Asesor De Servicios",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Metodología",
        "DESCRIPCION" => "Utiliza metodologías o procesos estandarizados que garantizan los resultados esperados."
      ],
      [
        "CATEGORIA" => "Asesor De Servicios",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Orientación al logro",
        "DESCRIPCION" => "Logra los resultados esperados en los tiempos establecidos."
      ],
      [
        "CATEGORIA" => "Asesor De Servicios",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Orientación al Detalle",
        "DESCRIPCION" => "Es minucioso en el manejo de datos."
      ],
      [
        "CATEGORIA" => "Asesor De Servicios",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Organización",
        "DESCRIPCION" => "Mantiene un orden en los documentos que maneja."
      ],
      [
        "CATEGORIA" => "Asesor De Servicios",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Cumplimiento de Procesos Operativos",
        "DESCRIPCION" => "Cumple con los procedimientos establecidos por la organización de manera correcta."
      ],
      [
        "CATEGORIA" => "Asesor De Servicios",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Atención Efectiva a consultas",
        "DESCRIPCION" => "Responde oportunamente a las solicitudes y consultas de los clientes vía presencial, telefónica y por correo electrónico."
      ],
      [
        "CATEGORIA" => "Asesor De Servicios",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Seguimiento a la atención",
        "DESCRIPCION" => "Vigilo la adecuada atención del cliente y pido al responsable a que se haga cargo."
      ],
      [
        "CATEGORIA" => "Asesor De Servicios",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Atención presencial oportuna",
        "DESCRIPCION" => "Hago todo lo necesario para que el cliente sea bien atendido en el menor tiempo posible."
      ],
      [
        "CATEGORIA" => "Asesor De Servicios",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Amabilidad y cercanía",
        "DESCRIPCION" => "Trata al cliente con amabilidad generando cercanía y confianza."
      ],
      [
        "CATEGORIA" => "Asistente Administrativo Ap",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Claridad en el mensaje",
        "DESCRIPCION" => "Comunica sus ideas de forma clara y precisa."
      ],
      [
        "CATEGORIA" => "Asistente Administrativo Ap",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Respuesta Oportuna",
        "DESCRIPCION" => "Comparte información e ideas en el momento adecuado."
      ],
      [
        "CATEGORIA" => "Asistente Administrativo Ap",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Escucha",
        "DESCRIPCION" => "Escucha atentamente las opiniones y sugerencias."
      ],
      [
        "CATEGORIA" => "Asistente Administrativo Ap",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Asertividad",
        "DESCRIPCION" => "Expresa sus opiniones sin herir los sentimientos de los demás."
      ],
      [
        "CATEGORIA" => "Asistente Administrativo Ap",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Orientación al Detalle",
        "DESCRIPCION" => "Es minucioso en el manejo de datos."
      ],
      [
        "CATEGORIA" => "Asistente Administrativo Ap",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Organización",
        "DESCRIPCION" => "Mantiene un orden en los documentos que maneja."
      ],
      [
        "CATEGORIA" => "Asistente Administrativo Ap",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Cumplimiento de Procesos Operativos",
        "DESCRIPCION" => "Cumple con los procedimientos establecidos por la organización de manera correcta."
      ],
      [
        "CATEGORIA" => "Asistente Administrativo Ap",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Atención Efectiva a consultas",
        "DESCRIPCION" => "Responde oportunamente a las solicitudes y consultas de los clientes."
      ],
      [
        "CATEGORIA" => "Asistente Administrativo Ap",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Seguimiento a la atención",
        "DESCRIPCION" => "Vigila la adecuada atención del cliente y pide al responsable a que se haga cargo."
      ],
      [
        "CATEGORIA" => "Asistente Administrativo Ap",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Atención presencial oportuna",
        "DESCRIPCION" => "Hace todo lo necesario para que el cliente sea bien atendido en el menor tiempo posible."
      ],
      [
        "CATEGORIA" => "Asistente Administrativo Ap",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Amabilidad y cercanía",
        "DESCRIPCION" => "Trata al cliente con amabilidad generando cercanía y confianza."
      ],
      [
        "CATEGORIA" => "Asistente Administrativo Ap",
        "COMPETENCIA" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "SUBCOMPETENCIA" => "Traspase de información",
        "DESCRIPCION" => "Verifica que la información que le brinda al otro llegue de manera efectiva y completa."
      ],
      [
        "CATEGORIA" => "Asistente Administrativo Ap",
        "COMPETENCIA" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "SUBCOMPETENCIA" => "Colaboración",
        "DESCRIPCION" => "Colabora con los demás para cumplir con el objetivo en común."
      ],
      [
        "CATEGORIA" => "Asistente Administrativo Ap",
        "COMPETENCIA" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "SUBCOMPETENCIA" => "Responsabilidad",
        "DESCRIPCION" => "Asume la responsabilidad que le corresponde, no espera que sólo los demás trabajen."
      ],
      [
        "CATEGORIA" => "Asistente Administrativo Ap",
        "COMPETENCIA" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "SUBCOMPETENCIA" => "Resuelve conflictos",
        "DESCRIPCION" => "Resuelve de manera oportuna las dificultades que pueda tener con sus compañeros"
      ],
      [
        "CATEGORIA" => "Asistente Administrativo Ap",
        "COMPETENCIA" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "SUBCOMPETENCIA" => "Pasión",
        "DESCRIPCION" => "Pone la mejor energía para hacer sus tareas y jugar en equipo."
      ],
      [
        "CATEGORIA" => "Asistente Administrativo DP",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Claridad en el mensaje",
        "DESCRIPCION" => "Comunica sus ideas de forma clara y precisa."
      ],
      [
        "CATEGORIA" => "Asistente Administrativo DP",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Respuesta Oportuna",
        "DESCRIPCION" => "Comparte información e ideas en el momento adecuado."
      ],
      [
        "CATEGORIA" => "Asistente Administrativo DP",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Escucha",
        "DESCRIPCION" => "Escucha atentamente las opiniones y sugerencias."
      ],
      [
        "CATEGORIA" => "Asistente Administrativo DP",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Asertividad",
        "DESCRIPCION" => "Expresa sus opiniones sin herir los sentimientos de los demás."
      ],
      [
        "CATEGORIA" => "Asistente Administrativo DP",
        "COMPETENCIA" => "Registro y Manejo de Información - Versión 2 AP: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Orientación al Detalle",
        "DESCRIPCION" => "Es minucioso en el manejo de datos."
      ],
      [
        "CATEGORIA" => "Asistente Administrativo DP",
        "COMPETENCIA" => "Registro y Manejo de Información - Versión 2 AP: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Organización",
        "DESCRIPCION" => "Mantiene un orden en los documentos que maneja."
      ],
      [
        "CATEGORIA" => "Asistente Administrativo DP",
        "COMPETENCIA" => "Registro y Manejo de Información - Versión 2 AP: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Cumplimiento de Procesos Operativos",
        "DESCRIPCION" => "Cumple con los procedimientos establecidos por la organización de manera correcta."
      ],
      [
        "CATEGORIA" => "Asistente Administrativo DP",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Atención Efectiva a consultas",
        "DESCRIPCION" => "Responde oportunamente a las solicitudes y consultas de los clientes."
      ],
      [
        "CATEGORIA" => "Asistente Administrativo DP",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Seguimiento a la atención",
        "DESCRIPCION" => "Vigila la adecuada atención del cliente y pide al responsable a que se haga cargo."
      ],
      [
        "CATEGORIA" => "Asistente Administrativo DP",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Atención presencial oportuna",
        "DESCRIPCION" => "Hace todo lo necesario para que el cliente sea bien atendido en el menor tiempo posible."
      ],
      [
        "CATEGORIA" => "Asistente Administrativo DP",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Amabilidad y cercanía",
        "DESCRIPCION" => "Trata al cliente con amabilidad generando cercanía y confianza."
      ],
      [
        "CATEGORIA" => "Asistente Administrativo DP",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",

        "SUBCOMPETENCIA" => "Proactividad",
        "DESCRIPCION" => "Se anticipa a los requerimientos o necesidades que se presentan."
      ],
      [
        "CATEGORIA" => "Asistente Administrativo DP",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",

        "SUBCOMPETENCIA" => "Solución de Problemas",
        "DESCRIPCION" => "Resuelve los problemas a pesar de las dificultades."
      ],
      [
        "CATEGORIA" => "Asistente Administrativo DP",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",

        "SUBCOMPETENCIA" => "Cumplimiento de Acuerdos",
        "DESCRIPCION" => "Cumple con los acuerdos de la organización."
      ],
      [
        "CATEGORIA" => "Asistente Administrativo DP",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",

        "SUBCOMPETENCIA" => "Resultados",
        "DESCRIPCION" => "Logra los resultados que se le asignan."
      ],
      [
        "CATEGORIA" => "Asistente Administrativo DP",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",

        "SUBCOMPETENCIA" => "Asumo las consecuencias",
        "DESCRIPCION" => "Se hace cargo de los errores que realiza y los resuelve. No espera a que otros lo solucionen."
      ],
      [
        "CATEGORIA" => "Asistente Administrativo DP",
        "COMPETENCIA" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "SUBCOMPETENCIA" => "Traspase de información",
        "DESCRIPCION" => "Verifica que la información que le brinda al otro llegue de manera efectiva y completa."
      ],
      [
        "CATEGORIA" => "Asistente Administrativo DP",
        "COMPETENCIA" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "SUBCOMPETENCIA" => "Colaboración",
        "DESCRIPCION" => "Colabora con los demás para cumplir con el objetivo en común."
      ],
      [
        "CATEGORIA" => "Asistente Administrativo DP",
        "COMPETENCIA" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "SUBCOMPETENCIA" => "Responsabilidad",
        "DESCRIPCION" => "Asume la responsabilidad que le corresponde, no espera que sólo los demás trabajen."
      ],
      [
        "CATEGORIA" => "Asistente Administrativo DP",
        "COMPETENCIA" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "SUBCOMPETENCIA" => "Resuelve conflictos",
        "DESCRIPCION" => "Resuelve de manera oportuna las dificultades que pueda tener con sus compañeros"
      ],
      [
        "CATEGORIA" => "Asistente Administrativo DP",
        "COMPETENCIA" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "SUBCOMPETENCIA" => "Pasión",
        "DESCRIPCION" => "Pone la mejor energía para hacer sus tareas y jugar en equipo."
      ],
      [
        "CATEGORIA" => "Asistente De Almacén Ap",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Claridad en el mensaje",
        "DESCRIPCION" => "Comunica sus ideas de forma clara y precisa."
      ],
      [
        "CATEGORIA" => "Asistente De Almacén Ap",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Respuesta Oportuna",
        "DESCRIPCION" => "Comparte información e ideas en el momento adecuado."
      ],
      [
        "CATEGORIA" => "Asistente De Almacén Ap",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Escucha",
        "DESCRIPCION" => "Escucha atentamente las opiniones y sugerencias."
      ],
      [
        "CATEGORIA" => "Asistente De Almacén Ap",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Asertividad",
        "DESCRIPCION" => "Expresa sus opiniones sin herir los sentimientos de los demás."
      ],
      [
        "CATEGORIA" => "Asistente De Almacén Ap",
        "COMPETENCIA" => "Cooperación Operativa: Coopera de manera activa con las necesidades del equipo y unidades con las que interactúa. Respeta los acuerdos generando un espacio colaborativo y de confianza.",
        "SUBCOMPETENCIA" => "Cooperación",
        "DESCRIPCION" => "Muestra predisposición y apoyo para hacer trabajos operativos."
      ],
      [
        "CATEGORIA" => "Asistente De Almacén Ap",
        "COMPETENCIA" => "Cooperación Operativa: Coopera de manera activa con las necesidades del equipo y unidades con las que interactúa. Respeta los acuerdos generando un espacio colaborativo y de confianza.",
        "SUBCOMPETENCIA" => "Orientación a la meta común",
        "DESCRIPCION" => "Apoya a los demás para realizar un entregable de manera rápida."
      ],
      [
        "CATEGORIA" => "Asistente De Almacén Ap",
        "COMPETENCIA" => "Cooperación Operativa: Coopera de manera activa con las necesidades del equipo y unidades con las que interactúa. Respeta los acuerdos generando un espacio colaborativo y de confianza.",
        "SUBCOMPETENCIA" => "Relaciones interpersonales",
        "DESCRIPCION" => "Tiene buenas relaciones con los demás y genera confianza."
      ],
      [
        "CATEGORIA" => "Asistente De Almacén Ap",
        "COMPETENCIA" => "Mentalidad de Dueño V2: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Anticipación",
        "DESCRIPCION" => "Se anticipa a los requerimientos o necesidades que se presentan."
      ],
      [
        "CATEGORIA" => "Asistente De Almacén Ap",
        "COMPETENCIA" => "Mentalidad de Dueño V2: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Solución de Problemas",
        "DESCRIPCION" => "Resuelve los problemas a pesar de las dificultades."
      ],
      [
        "CATEGORIA" => "Asistente De Almacén Ap",
        "COMPETENCIA" => "Mentalidad de Dueño V2: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Pertenencia",
        "DESCRIPCION" => "El colaborador se siente dueño de sus procesos y los ejecuta proactivamente."
      ],
      [
        "CATEGORIA" => "Asistente De Almacén Ap",
        "COMPETENCIA" => "Organización y Control de Mercadería: Garantizar la cantidad de recursos que necesita la organización a través de una adecuada supervisión, organización, clasificación y control.",
        "SUBCOMPETENCIA" => "Control de Movimientos",
        "DESCRIPCION" => "Controla el ingreso y salida de la mercadería de manera adecuada."
      ],
      [
        "CATEGORIA" => "Asistente De Almacén Ap",
        "COMPETENCIA" => "Organización y Control de Mercadería: Garantizar la cantidad de recursos que necesita la organización a través de una adecuada supervisión, organización, clasificación y control.",
        "SUBCOMPETENCIA" => "Protección de Recursos",
        "DESCRIPCION" => "Verifica con detalle si la mercadería llega averiada o incompleta."
      ],
      [
        "CATEGORIA" => "Asistente De Almacén Ap",
        "COMPETENCIA" => "Organización y Control de Mercadería: Garantizar la cantidad de recursos que necesita la organización a través de una adecuada supervisión, organización, clasificación y control.",
        "SUBCOMPETENCIA" => "Manejo de Inventario",
        "DESCRIPCION" => "Ejecuta de manera adecuada los inventarios de almacén (respuestos, accesorios, vehículos)."
      ],
      [
        "CATEGORIA" => "Asistente De Almacén Ap",
        "COMPETENCIA" => "Organización y Control de Mercadería: Garantizar la cantidad de recursos que necesita la organización a través de una adecuada supervisión, organización, clasificación y control.",
        "SUBCOMPETENCIA" => "Organización de Recursos",
        "DESCRIPCION" => "Clasifica los recursos con un orden claro y práctico."
      ],
      [
        "CATEGORIA" => "Asistente De Almacén Ap",
        "COMPETENCIA" => "Organización y Control de Mercadería: Garantizar la cantidad de recursos que necesita la organización a través de una adecuada supervisión, organización, clasificación y control.",
        "SUBCOMPETENCIA" => "Reclamos de ingreso de Recursos",
        "DESCRIPCION" => "Realiza de manera oportuna los reclamos ante mercaderías averiadas o faltantes."
      ],
      [
        "CATEGORIA" => "Asistente De Almacén Ap",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Orientación al Detalle",
        "DESCRIPCION" => "Es minucioso en el manejo de datos."
      ],
      [
        "CATEGORIA" => "Asistente De Almacén Ap",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Organización",
        "DESCRIPCION" => "Mantiene un orden en los documentos que maneja."
      ],
      [
        "CATEGORIA" => "Asistente De Almacén Ap",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Cumplimiento de Procesos Operativos",
        "DESCRIPCION" => "Cumple con los procedimientos establecidos por la organización de manera correcta."
      ],
      [
        "CATEGORIA" => "Asistente De Bienestar Y Clima Laboral",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Claridad en el mensaje",
        "DESCRIPCION" => "Comunica sus ideas de forma clara y precisa."
      ],
      [
        "CATEGORIA" => "Asistente De Bienestar Y Clima Laboral",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Respuesta Oportuna",
        "DESCRIPCION" => "Comparte información e ideas en el momento adecuado."
      ],
      [
        "CATEGORIA" => "Asistente De Bienestar Y Clima Laboral",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Escucha",
        "DESCRIPCION" => "Escucha atentamente las opiniones y sugerencias."
      ],
      [
        "CATEGORIA" => "Asistente De Bienestar Y Clima Laboral",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Asertividad",
        "DESCRIPCION" => "Expresa sus opiniones sin herir los sentimientos de los demás."
      ],
      [
        "CATEGORIA" => "Asistente De Bienestar Y Clima Laboral",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Gestión de Recursos",
        "DESCRIPCION" => "Identifica y asigna los recursos de manera responsable."
      ],
      [
        "CATEGORIA" => "Asistente De Bienestar Y Clima Laboral",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Metodología",
        "DESCRIPCION" => "Utiliza metodologías o procesos estandarizados que garantizan los resultados esperados."
      ],
      [
        "CATEGORIA" => "Asistente De Bienestar Y Clima Laboral",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Orientación al logro",
        "DESCRIPCION" => "Logra los resultados esperados en los tiempos establecidos."
      ],
      [
        "CATEGORIA" => "Asistente De Bienestar Y Clima Laboral",
        "COMPETENCIA" => "Gestión de Trámites: Garantiza la recepción y entrega de documentos asegurando que cumpla con los requisitos solicitados.",
        "SUBCOMPETENCIA" => "Construcción de Red de Contactos",
        "DESCRIPCION" => "Construye las relaciones necesarias para agilizar los trámites documentarios."
      ],
      [
        "CATEGORIA" => "Asistente De Bienestar Y Clima Laboral",
        "COMPETENCIA" => "Gestión de Trámites: Garantiza la recepción y entrega de documentos asegurando que cumpla con los requisitos solicitados.",
        "SUBCOMPETENCIA" => "Perseverancia en la entrega",
        "DESCRIPCION" => "Insiste en los trámites documentarios a pesar de las dificultades."
      ],
      [
        "CATEGORIA" => "Asistente De Bienestar Y Clima Laboral",
        "COMPETENCIA" => "Gestión de Trámites: Garantiza la recepción y entrega de documentos asegurando que cumpla con los requisitos solicitados.",
        "SUBCOMPETENCIA" => "Despacho documentario",
        "DESCRIPCION" => "Entrega y recibe documentos con rapidez."
      ],
      [
        "CATEGORIA" => "Asistente De Bienestar Y Clima Laboral",
        "COMPETENCIA" => "Gestión de Trámites: Garantiza la recepción y entrega de documentos asegurando que cumpla con los requisitos solicitados.",
        "SUBCOMPETENCIA" => "Control Documentario",
        "DESCRIPCION" => "Verifica que el documento cuente con los requisitos solicitados."
      ],
      [
        "CATEGORIA" => "Asistente De Bienestar Y Clima Laboral",
        "COMPETENCIA" => "Gestión de Trámites: Garantiza la recepción y entrega de documentos asegurando que cumpla con los requisitos solicitados.",
        "SUBCOMPETENCIA" => "Persuasión",
        "DESCRIPCION" => "Convence a sus contactos para conseguir los resultados en el menor tiempo posible."
      ],
      [
        "CATEGORIA" => "Asistente De Bienestar Y Clima Laboral",
        "COMPETENCIA" => "Servicio al colaborador: Resuelve las consultas y solicitudes de los colaboradores con empatía y amabilidad.",
        "SUBCOMPETENCIA" => "Atención Efectiva a consultas",
        "DESCRIPCION" => "Responde oportunamente a las solicitudes y consultas de los colaboradores."
      ],
      [
        "CATEGORIA" => "Asistente De Bienestar Y Clima Laboral",
        "COMPETENCIA" => "Servicio al colaborador: Resuelve las consultas y solicitudes de los colaboradores con empatía y amabilidad.",
        "SUBCOMPETENCIA" => "Empatía",
        "DESCRIPCION" => "Muestra preocupación por los problemas que tienen los colaboradores."
      ],
      [
        "CATEGORIA" => "Asistente De Bienestar Y Clima Laboral",
        "COMPETENCIA" => "Servicio al colaborador: Resuelve las consultas y solicitudes de los colaboradores con empatía y amabilidad.",
        "SUBCOMPETENCIA" => "Atención oportuna",
        "DESCRIPCION" => "Responde de manera oportuna ante las problemáticas de los colaboradores."
      ],
      [
        "CATEGORIA" => "Asistente De Bienestar Y Clima Laboral",
        "COMPETENCIA" => "Servicio al colaborador: Resuelve las consultas y solicitudes de los colaboradores con empatía y amabilidad.",
        "SUBCOMPETENCIA" => "Amabilidad y cercanía",
        "DESCRIPCION" => "Trata al colaborador con amabilidad generando cercanía y confianza."
      ],
      [
        "CATEGORIA" => "Asistente De Contabilidad",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Claridad en el mensaje",
        "DESCRIPCION" => "Comunica sus ideas de forma clara y precisa."
      ],
      [
        "CATEGORIA" => "Asistente De Contabilidad",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Respuesta Oportuna",
        "DESCRIPCION" => "Comparte información e ideas en el momento adecuado."
      ],
      [
        "CATEGORIA" => "Asistente De Contabilidad",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Escucha",
        "DESCRIPCION" => "Escucha atentamente las opiniones y sugerencias."
      ],
      [
        "CATEGORIA" => "Asistente De Contabilidad",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Asertividad",
        "DESCRIPCION" => "Expresa sus opiniones sin herir los sentimientos de los demás."
      ],
      [
        "CATEGORIA" => "Asistente De Contabilidad",
        "COMPETENCIA" => "Gestión de Trámites: Garantiza la recepción y entrega de documentos asegurando que cumpla con los requisitos solicitados.",
        "SUBCOMPETENCIA" => "Construcción de Red de Contactos",
        "DESCRIPCION" => "Construye las relaciones necesarias para agilizar los trámites documentarios."
      ],
      [
        "CATEGORIA" => "Asistente De Contabilidad",
        "COMPETENCIA" => "Gestión de Trámites: Garantiza la recepción y entrega de documentos asegurando que cumpla con los requisitos solicitados.",
        "SUBCOMPETENCIA" => "Perseverancia en la entrega",
        "DESCRIPCION" => "Insiste en los trámites documentarios a pesar de las dificultades."
      ],
      [
        "CATEGORIA" => "Asistente De Contabilidad",
        "COMPETENCIA" => "Gestión de Trámites: Garantiza la recepción y entrega de documentos asegurando que cumpla con los requisitos solicitados.",
        "SUBCOMPETENCIA" => "Despacho documentario",
        "DESCRIPCION" => "Entrega y recibe documentos con rapidez."
      ],
      [
        "CATEGORIA" => "Asistente De Contabilidad",
        "COMPETENCIA" => "Gestión de Trámites: Garantiza la recepción y entrega de documentos asegurando que cumpla con los requisitos solicitados.",
        "SUBCOMPETENCIA" => "Control Documentario",
        "DESCRIPCION" => "Verifica que el documento cuente con los requisitos solicitados."
      ],
      [
        "CATEGORIA" => "Asistente De Contabilidad",
        "COMPETENCIA" => "Gestión de Trámites: Garantiza la recepción y entrega de documentos asegurando que cumpla con los requisitos solicitados.",
        "SUBCOMPETENCIA" => "Persuasión",
        "DESCRIPCION" => "Convence a sus contactos para conseguir los resultados en el menor tiempo posible."
      ],
      [
        "CATEGORIA" => "Asistente De Contabilidad",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Orientación al Detalle",
        "DESCRIPCION" => "Es minucioso en el manejo de datos."
      ],
      [
        "CATEGORIA" => "Asistente De Contabilidad",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Organización",
        "DESCRIPCION" => "Mantiene un orden en los documentos que maneja."
      ],
      [
        "CATEGORIA" => "Asistente De Contabilidad",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Cumplimiento de Procesos Operativos",
        "DESCRIPCION" => "Cumple con los procedimientos establecidos por la organización de manera correcta."
      ],
      [
        "CATEGORIA" => "Asistente De Contabilidad",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Gestión de Recursos",
        "DESCRIPCION" => "Identifica y asigna los recursos de manera responsable."
      ],
      [
        "CATEGORIA" => "Asistente De Contabilidad",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Metodología",
        "DESCRIPCION" => "Utiliza metodologías o procesos estandarizados que garantizan los resultados esperados."
      ],
      [
        "CATEGORIA" => "Asistente De Contabilidad",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Orientación al logro",
        "DESCRIPCION" => "Logra los resultados esperados en los tiempos establecidos."
      ],
      [
        "CATEGORIA" => "Asistente De Contabilidad",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Claridad en el mensaje",
        "DESCRIPCION" => "Comunica sus ideas de forma clara y precisa."
      ],
      [
        "CATEGORIA" => "Asistente De Contabilidad",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Respuesta Oportuna",
        "DESCRIPCION" => "Comparte información e ideas en el momento adecuado."
      ],
      [
        "CATEGORIA" => "Asistente De Contabilidad",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Escucha",
        "DESCRIPCION" => "Escucha atentamente las opiniones y sugerencias."
      ],
      [
        "CATEGORIA" => "Asistente De Contabilidad",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Asertividad",
        "DESCRIPCION" => "Expresa sus opiniones sin herir los sentimientos de los demás."
      ],
      [
        "CATEGORIA" => "Asistente De Contabilidad Dp",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Claridad en el mensaje",
        "DESCRIPCION" => "Comunica sus ideas de forma clara y precisa."
      ],
      [
        "CATEGORIA" => "Asistente De Contabilidad Dp",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Respuesta Oportuna",
        "DESCRIPCION" => "Comparte información e ideas en el momento adecuado."
      ],
      [
        "CATEGORIA" => "Asistente De Contabilidad Dp",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Escucha",
        "DESCRIPCION" => "Escucha atentamente las opiniones y sugerencias."
      ],
      [
        "CATEGORIA" => "Asistente De Contabilidad Dp",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Asertividad",
        "DESCRIPCION" => "Expresa sus opiniones sin herir los sentimientos de los demás."
      ],
      [
        "CATEGORIA" => "Asistente De Contabilidad Dp",
        "COMPETENCIA" => "Gestión de Trámites: Garantiza la recepción y entrega de documentos asegurando que cumpla con los requisitos solicitados.",
        "SUBCOMPETENCIA" => "Construcción de Red de Contactos",
        "DESCRIPCION" => "Construye las relaciones necesarias para agilizar los trámites documentarios."
      ],
      [
        "CATEGORIA" => "Asistente De Contabilidad Dp",
        "COMPETENCIA" => "Gestión de Trámites: Garantiza la recepción y entrega de documentos asegurando que cumpla con los requisitos solicitados.",
        "SUBCOMPETENCIA" => "Perseverancia en la entrega",
        "DESCRIPCION" => "Insiste en los trámites documentarios a pesar de las dificultades."
      ],
      [
        "CATEGORIA" => "Asistente De Contabilidad Dp",
        "COMPETENCIA" => "Gestión de Trámites: Garantiza la recepción y entrega de documentos asegurando que cumpla con los requisitos solicitados.",
        "SUBCOMPETENCIA" => "Despacho documentario",
        "DESCRIPCION" => "Entrega y recibe documentos con rapidez."
      ],
      [
        "CATEGORIA" => "Asistente De Contabilidad Dp",
        "COMPETENCIA" => "Gestión de Trámites: Garantiza la recepción y entrega de documentos asegurando que cumpla con los requisitos solicitados.",
        "SUBCOMPETENCIA" => "Control Documentario",
        "DESCRIPCION" => "Verifica que el documento cuente con los requisitos solicitados."
      ],
      [
        "CATEGORIA" => "Asistente De Contabilidad Dp",
        "COMPETENCIA" => "Gestión de Trámites: Garantiza la recepción y entrega de documentos asegurando que cumpla con los requisitos solicitados.",
        "SUBCOMPETENCIA" => "Persuasión",
        "DESCRIPCION" => "Convence a sus contactos para conseguir los resultados en el menor tiempo posible."
      ],
      [
        "CATEGORIA" => "Asistente De Contabilidad Dp",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Orientación al Detalle",
        "DESCRIPCION" => "Es minucioso en el manejo de datos."
      ],
      [
        "CATEGORIA" => "Asistente De Contabilidad Dp",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Organización",
        "DESCRIPCION" => "Mantiene un orden en los documentos que maneja."
      ],
      [
        "CATEGORIA" => "Asistente De Contabilidad Dp",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Cumplimiento de Procesos Operativos",
        "DESCRIPCION" => "Cumple con los procedimientos establecidos por la organización de manera correcta."
      ],
      [
        "CATEGORIA" => "Asistente De Contabilidad Dp",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Gestión de Recursos",
        "DESCRIPCION" => "Identifica y asigna los recursos de manera responsable."
      ],
      [
        "CATEGORIA" => "Asistente De Contabilidad Dp",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Metodología",
        "DESCRIPCION" => "Utiliza metodologías o procesos estandarizados que garantizan los resultados esperados."
      ],
      [
        "CATEGORIA" => "Asistente De Contabilidad Dp",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Orientación al logro",
        "DESCRIPCION" => "Logra los resultados esperados en los tiempos establecidos."
      ],
      [
        "CATEGORIA" => "Asistente De Contabilidad Dp",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Claridad en el mensaje",
        "DESCRIPCION" => "Comunica sus ideas de forma clara y precisa."
      ],
      [
        "CATEGORIA" => "Asistente De Contabilidad Dp",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Respuesta Oportuna",
        "DESCRIPCION" => "Comparte información e ideas en el momento adecuado."
      ],
      [
        "CATEGORIA" => "Asistente De Contabilidad Dp",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Escucha",
        "DESCRIPCION" => "Escucha atentamente las opiniones y sugerencias."
      ],
      [
        "CATEGORIA" => "Asistente De Contabilidad Dp",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Asertividad",
        "DESCRIPCION" => "Expresa sus opiniones sin herir los sentimientos de los demás."
      ],
      [
        "CATEGORIA" => "Asistente De Facturacion Y Cobranza",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Claridad en el mensaje",
        "DESCRIPCION" => "Comunica sus ideas de forma clara y precisa."
      ],
      [
        "CATEGORIA" => "Asistente De Facturacion Y Cobranza",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Respuesta Oportuna",
        "DESCRIPCION" => "Comparte información e ideas en el momento adecuado."
      ],
      [
        "CATEGORIA" => "Asistente De Facturacion Y Cobranza",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Escucha",
        "DESCRIPCION" => "Escucha atentamente las opiniones y sugerencias."
      ],
      [
        "CATEGORIA" => "Asistente De Facturacion Y Cobranza",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Asertividad",
        "DESCRIPCION" => "Expresa sus opiniones sin herir los sentimientos de los demás."
      ],
      [
        "CATEGORIA" => "Asistente De Facturacion Y Cobranza",
        "COMPETENCIA" => "Control de las cobranzas: Realiza seguimiento efectivo logrando persuadir y comprometer al cliente con el pago del servicio. Identifica proactivamente los desfases de los clientes.",
        "SUBCOMPETENCIA" => "Seguimiento al cliente",
        "DESCRIPCION" => "Realiza un seguimiento de cobranzas oportuno."
      ],
      [
        "CATEGORIA" => "Asistente De Facturacion Y Cobranza",
        "COMPETENCIA" => "Control de las cobranzas: Realiza seguimiento efectivo logrando persuadir y comprometer al cliente con el pago del servicio. Identifica proactivamente los desfases de los clientes.",
        "SUBCOMPETENCIA" => "Acciones para persuadir",
        "DESCRIPCION" => "Define acciones estratégicas que garantizan el pago del cliente."
      ],
      [
        "CATEGORIA" => "Asistente De Facturacion Y Cobranza",
        "COMPETENCIA" => "Control de las cobranzas: Realiza seguimiento efectivo logrando persuadir y comprometer al cliente con el pago del servicio. Identifica proactivamente los desfases de los clientes.",
        "SUBCOMPETENCIA" => "Control de Desfases",
        "DESCRIPCION" => "Identifica los desfases de los clientes y toma acción con autonomía."
      ],
      [
        "CATEGORIA" => "Asistente De Facturacion Y Cobranza",
        "COMPETENCIA" => "Control de las cobranzas: Realiza seguimiento efectivo logrando persuadir y comprometer al cliente con el pago del servicio. Identifica proactivamente los desfases de los clientes.",
        "SUBCOMPETENCIA" => "Influencia y Persuasión",
        "DESCRIPCION" => "Logra comprometer al cliente a fin de evitar desfases prolongados."
      ],
      [
        "CATEGORIA" => "Asistente De Facturacion Y Cobranza",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Proactividad",
        "DESCRIPCION" => "Se anticipa a los requerimientos o necesidades que se presentan."
      ],
      [
        "CATEGORIA" => "Asistente De Facturacion Y Cobranza",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Solución de Problemas",
        "DESCRIPCION" => "Resuelve los problemas a pesar de las dificultades."
      ],
      [
        "CATEGORIA" => "Asistente De Facturacion Y Cobranza",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Cumplimiento de Acuerdos",
        "DESCRIPCION" => "Cumple con los acuerdos de la organización."
      ],
      [
        "CATEGORIA" => "Asistente De Facturacion Y Cobranza",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Resultados",
        "DESCRIPCION" => "Logra los resultados que se le asignan."
      ],
      [
        "CATEGORIA" => "Asistente De Facturacion Y Cobranza",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Asumo las consecuencias",
        "DESCRIPCION" => "Me hago cargo de los errores que realizo y los resuelvo. No espero a que otros lo solucionen."
      ],
      [
        "CATEGORIA" => "Asistente De Facturacion Y Cobranza",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Orientación al Detalle",
        "DESCRIPCION" => "Es minucioso en el manejo de datos."
      ],
      [
        "CATEGORIA" => "Asistente De Facturacion Y Cobranza",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Organización",
        "DESCRIPCION" => "Mantiene un orden en los documentos que maneja."
      ],
      [
        "CATEGORIA" => "Asistente De Facturacion Y Cobranza",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Cumplimiento de Procesos Operativos",
        "DESCRIPCION" => "Cumple con los procedimientos establecidos por la organización de manera correcta."
      ],
      [
        "CATEGORIA" => "Asistente De Facturacion Y Cobranza",
        "COMPETENCIA" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "SUBCOMPETENCIA" => "Traspase de información",
        "DESCRIPCION" => "Verifica que la información que le brinda al otro llegue de manera efectiva y completa."
      ],
      [
        "CATEGORIA" => "Asistente De Facturacion Y Cobranza",
        "COMPETENCIA" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "SUBCOMPETENCIA" => "Colaboración",
        "DESCRIPCION" => "Colabora con los demás para cumplir con el objetivo en común."
      ],
      [
        "CATEGORIA" => "Asistente De Facturacion Y Cobranza",
        "COMPETENCIA" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "SUBCOMPETENCIA" => "Responsabilidad",
        "DESCRIPCION" => "Asume la responsabilidad que le corresponde, no espera que sólo los demás trabajen."
      ],
      [
        "CATEGORIA" => "Asistente De Facturacion Y Cobranza",
        "COMPETENCIA" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "SUBCOMPETENCIA" => "Resuelve conflictos",
        "DESCRIPCION" => "Resuelve de manera oportuna las dificultades que pueda tener con sus compañeros"
      ],
      [
        "CATEGORIA" => "Asistente De Facturacion Y Cobranza",
        "COMPETENCIA" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "SUBCOMPETENCIA" => "Pasión",
        "DESCRIPCION" => "Pone la mejor energía para hacer sus tareas y jugar en equipo."
      ],
      [
        "CATEGORIA" => "Asistente De Logistica",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Claridad en el mensaje",
        "DESCRIPCION" => "Comunica sus ideas de forma clara y precisa."
      ],
      [
        "CATEGORIA" => "Asistente De Logistica",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Respuesta Oportuna",
        "DESCRIPCION" => "Comparte información e ideas en el momento adecuado."
      ],
      [
        "CATEGORIA" => "Asistente De Logistica",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Escucha",
        "DESCRIPCION" => "Escucha atentamente las opiniones y sugerencias."
      ],
      [
        "CATEGORIA" => "Asistente De Logistica",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Asertividad",
        "DESCRIPCION" => "Expresa sus opiniones sin herir los sentimientos de los demás."
      ],
      [
        "CATEGORIA" => "Asistente De Logistica",
        "COMPETENCIA" => "Organización y Control de Mercadería: Garantizar la cantidad de recursos que necesita la organización a través de una adecuada supervisión, organización, clasificación y control.",
        "SUBCOMPETENCIA" => "Control de Movimientos",
        "DESCRIPCION" => "Controla el ingreso y salida de la mercadería de manera adecuada."
      ],
      [
        "CATEGORIA" => "Asistente De Logistica",
        "COMPETENCIA" => "Organización y Control de Mercadería: Garantizar la cantidad de recursos que necesita la organización a través de una adecuada supervisión, organización, clasificación y control.",
        "SUBCOMPETENCIA" => "Protección de Recursos",
        "DESCRIPCION" => "Verifica con detalle si la mercadería llega averiada o incompleta."
      ],
      [
        "CATEGORIA" => "Asistente De Logistica",
        "COMPETENCIA" => "Organización y Control de Mercadería: Garantizar la cantidad de recursos que necesita la organización a través de una adecuada supervisión, organización, clasificación y control.",
        "SUBCOMPETENCIA" => "Manejo de Inventario",
        "DESCRIPCION" => "Comprueba de manera adecuada la existencia de la mercadería real vs la mercadería registrada en el sistema."
      ],
      [
        "CATEGORIA" => "Asistente De Logistica",
        "COMPETENCIA" => "Organización y Control de Mercadería: Garantizar la cantidad de recursos que necesita la organización a través de una adecuada supervisión, organización, clasificación y control.",
        "SUBCOMPETENCIA" => "Organización de Recursos",
        "DESCRIPCION" => "Clasifica los recursos con un orden claro y práctico."
      ],
      [
        "CATEGORIA" => "Asistente De Logistica",
        "COMPETENCIA" => "Organización y Control de Mercadería: Garantizar la cantidad de recursos que necesita la organización a través de una adecuada supervisión, organización, clasificación y control.",
        "SUBCOMPETENCIA" => "Reclamos de ingreso de Recursos",
        "DESCRIPCION" => "Realiza de manera oportuna los reclamos ante mercaderías averiadas o faltantes."
      ],
      [
        "CATEGORIA" => "Asistente De Logistica",
        "COMPETENCIA" => "Mentalidad de Dueño V2: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Proactividad",
        "DESCRIPCION" => "Se anticipa a los requerimientos o necesidades que se presentan."
      ],
      [
        "CATEGORIA" => "Asistente De Logistica",
        "COMPETENCIA" => "Mentalidad de Dueño V2: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Solución de Problemas",
        "DESCRIPCION" => "Resuelve los problemas a pesar de las dificultades."
      ],
      [
        "CATEGORIA" => "Asistente De Logistica",
        "COMPETENCIA" => "Mentalidad de Dueño V2: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Cumplimiento de Acuerdos",
        "DESCRIPCION" => "Cumple con los acuerdos de la organización."
      ],
      [
        "CATEGORIA" => "Asistente De Logistica",
        "COMPETENCIA" => "Mentalidad de Dueño V2: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Resultados",
        "DESCRIPCION" => "Logra los resultados que se le asignan."
      ],
      [
        "CATEGORIA" => "Asistente De Logistica",
        "COMPETENCIA" => "Mentalidad de Dueño V2: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Asumo las consecuencias",
        "DESCRIPCION" => "Se hace cargo de los errores que realiza y los resuelve. No espera a que otros lo solucionen."
      ],
      [
        "CATEGORIA" => "Asistente De Logistica",
        "COMPETENCIA" => "Registro y Manejo de Información - Versión 2 AP: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Orientación al Detalle",
        "DESCRIPCION" => "Es minucioso en el manejo de datos."
      ],
      [
        "CATEGORIA" => "Asistente De Logistica",
        "COMPETENCIA" => "Registro y Manejo de Información - Versión 2 AP: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Organización",
        "DESCRIPCION" => "Mantiene un orden en los documentos que maneja."
      ],
      [
        "CATEGORIA" => "Asistente De Logistica",
        "COMPETENCIA" => "Registro y Manejo de Información - Versión 2 AP: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Cumplimiento de Procesos Operativos",
        "DESCRIPCION" => "Cumple con los procedimientos establecidos por la organización de manera correcta."
      ],
      [
        "CATEGORIA" => "Asistente De Logistica",
        "COMPETENCIA" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "SUBCOMPETENCIA" => "Traspase de información",
        "DESCRIPCION" => "Verifica que la información que le brinda al otro llegue de manera efectiva y completa."
      ],
      [
        "CATEGORIA" => "Asistente De Logistica",
        "COMPETENCIA" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "SUBCOMPETENCIA" => "Colaboración",
        "DESCRIPCION" => "Colabora con los demás para cumplir con el objetivo en común."
      ],
      [
        "CATEGORIA" => "Asistente De Logistica",
        "COMPETENCIA" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "SUBCOMPETENCIA" => "Responsabilidad",
        "DESCRIPCION" => "Asume la responsabilidad que le corresponde, no espera que sólo los demás trabajen."
      ],
      [
        "CATEGORIA" => "Asistente De Logistica",
        "COMPETENCIA" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "SUBCOMPETENCIA" => "Resuelve conflictos",
        "DESCRIPCION" => "Resuelve de manera oportuna las dificultades que pueda tener con sus compañeros"
      ],
      [
        "CATEGORIA" => "Asistente De Logistica",
        "COMPETENCIA" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "SUBCOMPETENCIA" => "Pasión",
        "DESCRIPCION" => "Pone la mejor energía para hacer sus tareas y jugar en equipo."
      ],
      [
        "CATEGORIA" => "Asistente De Operaciones",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Asertividad",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Asistente De Operaciones",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Claridad en el mensaje",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Asistente De Operaciones",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Escucha",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Asistente De Operaciones",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Informe Escrito",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Asistente De Operaciones",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Orientación A La Meta Común",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Asistente De Operaciones",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Respuesta Oportuna",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Asistente De Operaciones",
        "COMPETENCIA" => "Cooperación Operativa: Coopera de manera activa con las necesidades del equipo y unidades con las que interactúa. Respeta los acuerdos generando un espacio colaborativo y de confianza.",
        "SUBCOMPETENCIA" => "Cooperación",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Asistente De Operaciones",
        "COMPETENCIA" => "Cooperación Operativa: Coopera de manera activa con las necesidades del equipo y unidades con las que interactúa. Respeta los acuerdos generando un espacio colaborativo y de confianza.",
        "SUBCOMPETENCIA" => "Orientación A La Meta Común",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Asistente De Operaciones",
        "COMPETENCIA" => "Cooperación Operativa: Coopera de manera activa con las necesidades del equipo y unidades con las que interactúa. Respeta los acuerdos generando un espacio colaborativo y de confianza.",
        "SUBCOMPETENCIA" => "Relaciones Interpersonales",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Asistente De Operaciones",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Gestión De Recursos",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Asistente De Operaciones",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Metodología",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Asistente De Operaciones",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Orientación Al Logro",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Asistente De Operaciones",
        "COMPETENCIA" => "Organización y Control: Garantizar el uso de recursos a través de una adecuada organización, clasificación y control.",
        "SUBCOMPETENCIA" => "Control De Recursos",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Asistente De Operaciones",
        "COMPETENCIA" => "Organización y Control: Garantizar el uso de recursos a través de una adecuada organización, clasificación y control.",
        "SUBCOMPETENCIA" => "Organización De Recursos Internos",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Asistente De Operaciones",
        "COMPETENCIA" => "Organización y Control: Garantizar el uso de recursos a través de una adecuada organización, clasificación y control.",
        "SUBCOMPETENCIA" => "Protección De Recursos De La Organización",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Asistente De Post Venta",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Gestión de Recursos",
        "DESCRIPCION" => "Identifica y asigna los recursos de manera responsable."
      ],
      [
        "CATEGORIA" => "Asistente De Post Venta",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Metodología",
        "DESCRIPCION" => "Utiliza metodologías o procesos estandarizados que garantizan los resultados esperados."
      ],
      [
        "CATEGORIA" => "Asistente De Post Venta",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Orientación al logro",
        "DESCRIPCION" => "Logra los resultados esperados en los tiempos establecidos."
      ],
      [
        "CATEGORIA" => "Asistente De Post Venta",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Claridad en el mensaje",
        "DESCRIPCION" => "Comunica sus ideas de forma clara y precisa."
      ],
      [
        "CATEGORIA" => "Asistente De Post Venta",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Respuesta Oportuna",
        "DESCRIPCION" => "Comparte información e ideas en el momento adecuado."
      ],
      [
        "CATEGORIA" => "Asistente De Post Venta",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Escucha",
        "DESCRIPCION" => "Escucha atentamente las opiniones y sugerencias."
      ],
      [
        "CATEGORIA" => "Asistente De Post Venta",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Asertividad",
        "DESCRIPCION" => "Expresa sus opiniones sin herir los sentimientos de los demás."
      ],
      [
        "CATEGORIA" => "Asistente De Post Venta",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Orientación al Detalle",
        "DESCRIPCION" => "Es minucioso en el manejo de datos."
      ],
      [
        "CATEGORIA" => "Asistente De Post Venta",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Organización",
        "DESCRIPCION" => "Mantiene un orden en los documentos que maneja."
      ],
      [
        "CATEGORIA" => "Asistente De Post Venta",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Cumplimiento de Procesos Operativos",
        "DESCRIPCION" => "Cumple con los procedimientos establecidos por la organización de manera correcta."
      ],
      [
        "CATEGORIA" => "Asistente De Post Venta",
        "COMPETENCIA" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "SUBCOMPETENCIA" => "Traspase de información",
        "DESCRIPCION" => "Verifica que la información que le brinda al otro llegue de manera efectiva y completa."
      ],
      [
        "CATEGORIA" => "Asistente De Post Venta",
        "COMPETENCIA" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "SUBCOMPETENCIA" => "Colaboración",
        "DESCRIPCION" => "Colabora con los demás para cumplir con el objetivo en común."
      ],
      [
        "CATEGORIA" => "Asistente De Post Venta",
        "COMPETENCIA" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "SUBCOMPETENCIA" => "Responsabilidad",
        "DESCRIPCION" => "Asume la responsabilidad que le corresponde, no espera que sólo los demás trabajen."
      ],
      [
        "CATEGORIA" => "Asistente De Post Venta",
        "COMPETENCIA" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "SUBCOMPETENCIA" => "Resuelve conflictos",
        "DESCRIPCION" => "Resuelve de manera oportuna las dificultades que pueda tener con sus compañeros"
      ],
      [
        "CATEGORIA" => "Asistente De Post Venta",
        "COMPETENCIA" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "SUBCOMPETENCIA" => "Pasión",
        "DESCRIPCION" => "Pone la mejor energía para hacer sus tareas y jugar en equipo."
      ],
      [
        "CATEGORIA" => "Asistente De Post Venta",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Atención Efectiva a consultas",
        "DESCRIPCION" => "Responde oportunamente a las solicitudes y consultas de los clientes vía presencial, telefónica y por correo electrónico."
      ],
      [
        "CATEGORIA" => "Asistente De Post Venta",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Seguimiento a la atención",
        "DESCRIPCION" => "Vigilo la adecuada atención del cliente y pido al responsable a que se haga cargo."
      ],
      [
        "CATEGORIA" => "Asistente De Post Venta",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Atención presencial oportuna",
        "DESCRIPCION" => "Hago todo lo necesario para que el cliente sea bien atendido en el menor tiempo posible."
      ],
      [
        "CATEGORIA" => "Asistente De Post Venta",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Amabilidad y cercanía",
        "DESCRIPCION" => "Trata al cliente con amabilidad generando cercanía y confianza."
      ],
      [
        "CATEGORIA" => "Asistente De Reparto",
        "COMPETENCIA" => "Calidad en la entrega del Producto: Velar por la calidad y recursos que utiliza en la entrega del producto al cliente a fin de generar una adecuada satisfacción.",
        "SUBCOMPETENCIA" => "Control de Calidad",
        "DESCRIPCION" => "Entrega el producto completo de acuerdo a la solicitud del cliente."
      ],
      [
        "CATEGORIA" => "Asistente De Reparto",
        "COMPETENCIA" => "Calidad en la entrega del Producto: Velar por la calidad y recursos que utiliza en la entrega del producto al cliente a fin de generar una adecuada satisfacción.",
        "SUBCOMPETENCIA" => "Conocimiento de Procesos",
        "DESCRIPCION" => "Tiene claridad del proceso y los lineamientos que debe tener cada producto."
      ],
      [
        "CATEGORIA" => "Asistente De Reparto",
        "COMPETENCIA" => "Calidad en la entrega del Producto: Velar por la calidad y recursos que utiliza en la entrega del producto al cliente a fin de generar una adecuada satisfacción.",
        "SUBCOMPETENCIA" => "Protección del producto",
        "DESCRIPCION" => "Protege cada parte del producto durante su traslado a fin de garantizar la calidad."
      ],
      [
        "CATEGORIA" => "Asistente De Reparto",
        "COMPETENCIA" => "Calidad en la entrega del Producto: Velar por la calidad y recursos que utiliza en la entrega del producto al cliente a fin de generar una adecuada satisfacción.",
        "SUBCOMPETENCIA" => "Entrega del Producto",
        "DESCRIPCION" => "Realiza la descarga y apila el producto de manera eficiente."
      ],
      [
        "CATEGORIA" => "Asistente De Reparto",
        "COMPETENCIA" => "Calidad en la entrega del Producto: Velar por la calidad y recursos que utiliza en la entrega del producto al cliente a fin de generar una adecuada satisfacción.",
        "SUBCOMPETENCIA" => "Protección del vehículo",
        "DESCRIPCION" => "Cuida el vehículo como si fuera propio: realiza el mantenimiento de manera adecuada y cuida cada parte del vehículo a fin de brindar una buena imagen al cliente."
      ],
      [
        "CATEGORIA" => "Asistente De Reparto",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Claridad en el mensaje",
        "DESCRIPCION" => "Comunica sus ideas de forma clara y precisa."
      ],
      [
        "CATEGORIA" => "Asistente De Reparto",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Respuesta Oportuna",
        "DESCRIPCION" => "Comparte información e ideas en el momento adecuado."
      ],
      [
        "CATEGORIA" => "Asistente De Reparto",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Escucha",
        "DESCRIPCION" => "Escucha atentamente las opiniones y sugerencias."
      ],
      [
        "CATEGORIA" => "Asistente De Reparto",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Asertividad",
        "DESCRIPCION" => "Expresa sus opiniones sin herir los sentimientos de los demás."
      ],
      [
        "CATEGORIA" => "Asistente De Reparto",
        "COMPETENCIA" => "Cooperación Operativa: Coopera de manera activa con las necesidades del equipo y unidades con las que interactúa. Respeta los acuerdos generando un espacio colaborativo y de confianza.",
        "SUBCOMPETENCIA" => "Cooperación",
        "DESCRIPCION" => "Muestra predisposición y apoyo para hacer trabajos operativos."
      ],
      [
        "CATEGORIA" => "Asistente De Reparto",
        "COMPETENCIA" => "Cooperación Operativa: Coopera de manera activa con las necesidades del equipo y unidades con las que interactúa. Respeta los acuerdos generando un espacio colaborativo y de confianza.",
        "SUBCOMPETENCIA" => "Orientación a la meta común",
        "DESCRIPCION" => "Apoya a los demás para realizar un entregable de manera rápida."
      ],
      [
        "CATEGORIA" => "Asistente De Reparto",
        "COMPETENCIA" => "Cooperación Operativa: Coopera de manera activa con las necesidades del equipo y unidades con las que interactúa. Respeta los acuerdos generando un espacio colaborativo y de confianza.",
        "SUBCOMPETENCIA" => "Relaciones interpersonales",
        "DESCRIPCION" => "Tiene buenas relaciones con los demás y genera confianza."
      ],
      [
        "CATEGORIA" => "Asistente De Reparto",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Gestión de Recursos",
        "DESCRIPCION" => "Identifica y asigna los recursos de manera responsable."
      ],
      [
        "CATEGORIA" => "Asistente De Reparto",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Metodología",
        "DESCRIPCION" => "Utiliza metodologías o procesos estandarizados que garantizan los resultados esperados."
      ],
      [
        "CATEGORIA" => "Asistente De Reparto",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Orientación al logro",
        "DESCRIPCION" => "Logra los resultados esperados en los tiempos establecidos."
      ],
      [
        "CATEGORIA" => "Asistente De Reparto",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Solicitudes del Cliente",
        "DESCRIPCION" => "Responde oportunamente a las solicitudes y consultas de los clientes vía presencial, telefónica y por correo electrónico."
      ],
      [
        "CATEGORIA" => "Asistente De Reparto",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Atención Efectiva",
        "DESCRIPCION" => "Vigila la adecuada atención del cliente y pide al responsable que se haga cargo."
      ],
      [
        "CATEGORIA" => "Asistente De Reparto",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Dueño del Cliente",
        "DESCRIPCION" => "Hace todo lo necesario para que el cliente sea bien atendido en el menor tiempo posible."
      ],
      [
        "CATEGORIA" => "Asistente De Reparto",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Amabilidad y cercanía",
        "DESCRIPCION" => "Trata al cliente con amabilidad generando cercanía y confianza."
      ],
      [
        "CATEGORIA" => "Asistente De Seguimiento Y Monitoreo",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Claridad en el mensaje",
        "DESCRIPCION" => "Comunica sus ideas de forma clara y precisa."
      ],
      [
        "CATEGORIA" => "Asistente De Seguimiento Y Monitoreo",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Respuesta Oportuna",
        "DESCRIPCION" => "Comparte información e ideas en el momento adecuado."
      ],
      [
        "CATEGORIA" => "Asistente De Seguimiento Y Monitoreo",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Escucha",
        "DESCRIPCION" => "Escucha atentamente las opiniones y sugerencias."
      ],
      [
        "CATEGORIA" => "Asistente De Seguimiento Y Monitoreo",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Asertividad",
        "DESCRIPCION" => "Expresa sus opiniones sin herir los sentimientos de los demás."
      ],
      [
        "CATEGORIA" => "Asistente De Seguimiento Y Monitoreo",
        "COMPETENCIA" => "Supervisión Efectiva: Supervisar el desempeño de los colaboradores brindando retrolimentación a partir del mismo y gestionar los resultados obtenidos.",
        "SUBCOMPETENCIA" => "Seguimiento",
        "DESCRIPCION" => "Supervisa los avances, entregables y acciones de los colaboradores para lograr los objetivos."
      ],
      [
        "CATEGORIA" => "Asistente De Seguimiento Y Monitoreo",
        "COMPETENCIA" => "Supervisión Efectiva: Supervisar el desempeño de los colaboradores brindando retrolimentación a partir del mismo y gestionar los resultados obtenidos.",
        "SUBCOMPETENCIA" => "Retroalimentación",
        "DESCRIPCION" => "Brinda retroalimentación de manera oportuna sobre los resultados individuales."
      ],
      [
        "CATEGORIA" => "Asistente De Seguimiento Y Monitoreo",
        "COMPETENCIA" => "Supervisión Efectiva: Supervisar el desempeño de los colaboradores brindando retrolimentación a partir del mismo y gestionar los resultados obtenidos.",
        "SUBCOMPETENCIA" => "Gestión de Consecuencias",
        "DESCRIPCION" => "Felicita o corrige las acciones de los colaboradores de acuerdo al desempeño que tienen."
      ],
      [
        "CATEGORIA" => "Asistente De Seguimiento Y Monitoreo",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Gestión de Recursos",
        "DESCRIPCION" => "Identifica y asigna los recursos de manera responsable."
      ],
      [
        "CATEGORIA" => "Asistente De Seguimiento Y Monitoreo",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Metodología",
        "DESCRIPCION" => "Utiliza metodologías o procesos estandarizados que garantizan los resultados esperados."
      ],
      [
        "CATEGORIA" => "Asistente De Seguimiento Y Monitoreo",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Orientación al logro",
        "DESCRIPCION" => "Logra los resultados esperados en los tiempos establecidos."
      ],
      [
        "CATEGORIA" => "Asistente De Seguimiento Y Monitoreo",
        "COMPETENCIA" => "Gestión de normas y procedimientos: Se preocupa y promueve respeto de las normas y procedimientos. Vigila el cumplimiento de las normas e interviene para corregir faltas.",
        "SUBCOMPETENCIA" => "Respeto a la Norma",
        "DESCRIPCION" => "Respeta con firmeza las normas y procedimientos."
      ],
      [
        "CATEGORIA" => "Asistente De Seguimiento Y Monitoreo",
        "COMPETENCIA" => "Gestión de normas y procedimientos: Se preocupa y promueve respeto de las normas y procedimientos. Vigila el cumplimiento de las normas e interviene para corregir faltas.",
        "SUBCOMPETENCIA" => "Claridad en la orientación",
        "DESCRIPCION" => "Explica de manera clara las normas y reglamentos."
      ],
      [
        "CATEGORIA" => "Asistente De Seguimiento Y Monitoreo",
        "COMPETENCIA" => "Gestión de normas y procedimientos: Se preocupa y promueve respeto de las normas y procedimientos. Vigila el cumplimiento de las normas e interviene para corregir faltas.",
        "SUBCOMPETENCIA" => "Revisión y Seguimiento",
        "DESCRIPCION" => "Verifica y realiza seguimiento constante al cumplimiento de las normas y reglamentos."
      ],
      [
        "CATEGORIA" => "Asistente De Seguimiento Y Monitoreo",
        "COMPETENCIA" => "Gestión de normas y procedimientos: Se preocupa y promueve respeto de las normas y procedimientos. Vigila el cumplimiento de las normas e interviene para corregir faltas.",
        "SUBCOMPETENCIA" => "Gestión de Consecuencias",
        "DESCRIPCION" => "Logra corregir un comportamiento que no está acorde a las reglas y procedimientos."
      ],
      [
        "CATEGORIA" => "Asistente De Seguimiento Y Monitoreo",
        "COMPETENCIA" => "Registro y Manejo de Información - Versión 2 AP: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Orientación al Detalle",
        "DESCRIPCION" => "Es minucioso en el manejo de datos."
      ],
      [
        "CATEGORIA" => "Asistente De Seguimiento Y Monitoreo",
        "COMPETENCIA" => "Registro y Manejo de Información - Versión 2 AP: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Organización",
        "DESCRIPCION" => "Mantiene un orden en los documentos que maneja."
      ],
      [
        "CATEGORIA" => "Asistente De Seguimiento Y Monitoreo",
        "COMPETENCIA" => "Registro y Manejo de Información - Versión 2 AP: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Cumplimiento de Procesos Operativos",
        "DESCRIPCION" => "Cumple con los procedimientos establecidos por la organización de manera correcta."
      ],
      [
        "CATEGORIA" => "Asistente De Vehiculos (PDI)",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Claridad en el mensaje",
        "DESCRIPCION" => "Comunica sus ideas de forma clara y precisa."
      ],
      [
        "CATEGORIA" => "Asistente De Vehiculos (PDI)",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Respuesta Oportuna",
        "DESCRIPCION" => "Comparte información e ideas en el momento adecuado."
      ],
      [
        "CATEGORIA" => "Asistente De Vehiculos (PDI)",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Escucha",
        "DESCRIPCION" => "Escucha atentamente las opiniones y sugerencias."
      ],
      [
        "CATEGORIA" => "Asistente De Vehiculos (PDI)",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Asertividad",
        "DESCRIPCION" => "Expresa sus opiniones sin herir los sentimientos de los demás."
      ],
      [
        "CATEGORIA" => "Asistente De Vehiculos (PDI)",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Gestión de Recursos",
        "DESCRIPCION" => "Identifica y asigna los recursos de manera responsable."
      ],
      [
        "CATEGORIA" => "Asistente De Vehiculos (PDI)",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Metodología",
        "DESCRIPCION" => "Utiliza metodologías o procesos estandarizados que garantizan los resultados esperados."
      ],
      [
        "CATEGORIA" => "Asistente De Vehiculos (PDI)",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Orientación al logro",
        "DESCRIPCION" => "Logra los resultados esperados en los tiempos establecidos."
      ],
      [
        "CATEGORIA" => "Asistente De Vehiculos (PDI)",
        "COMPETENCIA" => "Mentalidad de Dueño V2: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Anticipación",
        "DESCRIPCION" => "Se anticipa a los requerimientos o necesidades que se presentan."
      ],
      [
        "CATEGORIA" => "Asistente De Vehiculos (PDI)",
        "COMPETENCIA" => "Mentalidad de Dueño V2: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Solución de Problemas",
        "DESCRIPCION" => "Resuelve los problemas a pesar de las dificultades."
      ],
      [
        "CATEGORIA" => "Asistente De Vehiculos (PDI)",
        "COMPETENCIA" => "Mentalidad de Dueño V2: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Pertenencia",
        "DESCRIPCION" => "El colaborador se siente dueño de sus procesos y los ejecuta proactivamente."
      ],
      [
        "CATEGORIA" => "Asistente De Vehiculos (PDI)",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Orientación al Detalle",
        "DESCRIPCION" => "Es minucioso en el manejo de datos."
      ],
      [
        "CATEGORIA" => "Asistente De Vehiculos (PDI)",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Organización",
        "DESCRIPCION" => "Mantiene un orden en los documentos que maneja."
      ],
      [
        "CATEGORIA" => "Asistente De Vehiculos (PDI)",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Cumplimiento de Procesos Operativos",
        "DESCRIPCION" => "Cumple con los procedimientos establecidos por la organización de manera correcta."
      ],
      [
        "CATEGORIA" => "Asistente Mecánico Tp",
        "COMPETENCIA" => "Cooperación Operativa: Coopera de manera activa con las necesidades del equipo y unidades con las que interactúa. Respeta los acuerdos generando un espacio colaborativo y de confianza.",
        "SUBCOMPETENCIA" => "Cooperación",
        "DESCRIPCION" => "Muestra predisposición y apoyo para hacer trabajos operativos."
      ],
      [
        "CATEGORIA" => "Asistente Mecánico Tp",
        "COMPETENCIA" => "Cooperación Operativa: Coopera de manera activa con las necesidades del equipo y unidades con las que interactúa. Respeta los acuerdos generando un espacio colaborativo y de confianza.",
        "SUBCOMPETENCIA" => "Orientación a la meta común",
        "DESCRIPCION" => "Apoya a los demás para realizar un entregable de manera rápida."
      ],
      [
        "CATEGORIA" => "Asistente Mecánico Tp",
        "COMPETENCIA" => "Cooperación Operativa: Coopera de manera activa con las necesidades del equipo y unidades con las que interactúa. Respeta los acuerdos generando un espacio colaborativo y de confianza.",
        "SUBCOMPETENCIA" => "Relaciones interpersonales",
        "DESCRIPCION" => "Tiene buenas relaciones con los demás y genera confianza."
      ],
      [
        "CATEGORIA" => "Asistente Mecánico Tp",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Gestión de Recursos",
        "DESCRIPCION" => "Identifica y asigna los recursos de manera responsable."
      ],
      [
        "CATEGORIA" => "Asistente Mecánico Tp",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Metodología",
        "DESCRIPCION" => "Utiliza metodologías o procesos estandarizados que garantizan los resultados esperados."
      ],
      [
        "CATEGORIA" => "Asistente Mecánico Tp",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Orientación al logro",
        "DESCRIPCION" => "Logra los resultados esperados en los tiempos establecidos."
      ],
      [
        "CATEGORIA" => "Asistente Mecánico Tp",
        "COMPETENCIA" => "Habilidad para reparar: Soluciona las fallas técnicas con rapidez y efectividad, identifica las causas de manera oportuna y verifica el correcto funcionamiento una vez reparado.",
        "SUBCOMPETENCIA" => "Diagnóstico",
        "DESCRIPCION" => "Identifica rápidamente la causa de las fallas mecánicas, eléctricas, de soldadura o de neumáticos."
      ],
      [
        "CATEGORIA" => "Asistente Mecánico Tp",
        "COMPETENCIA" => "Habilidad para reparar: Soluciona las fallas técnicas con rapidez y efectividad, identifica las causas de manera oportuna y verifica el correcto funcionamiento una vez reparado.",
        "SUBCOMPETENCIA" => "Reparación",
        "DESCRIPCION" => "Repara con rapidez las fallas mecánicas, eléctricas, de soldadura o de neumáticos sin perjudicar la calidad de lo reparado."
      ],
      [
        "CATEGORIA" => "Asistente Mecánico Tp",
        "COMPETENCIA" => "Habilidad para reparar: Soluciona las fallas técnicas con rapidez y efectividad, identifica las causas de manera oportuna y verifica el correcto funcionamiento una vez reparado.",
        "SUBCOMPETENCIA" => "Contol de Calidad",
        "DESCRIPCION" => "Verifica el correcto funcionamiento de lo reparado antes de entregarlo."
      ],
      [
        "CATEGORIA" => "Asistente Mecánico Tp",
        "COMPETENCIA" => "Organización y Control: Garantizar el uso de recursos a través de una adecuada organización, clasificación y control.",
        "SUBCOMPETENCIA" => "Control de Recursos",
        "DESCRIPCION" => "Controla la cantidad de recursos necesarios para realizar las tareas."
      ],
      [
        "CATEGORIA" => "Asistente Mecánico Tp",
        "COMPETENCIA" => "Organización y Control: Garantizar el uso de recursos a través de una adecuada organización, clasificación y control.",
        "SUBCOMPETENCIA" => "Protección de Recursos",
        "DESCRIPCION" => "Cuida los recursos para mantener su calidad."
      ],
      [
        "CATEGORIA" => "Asistente Mecánico Tp",
        "COMPETENCIA" => "Organización y Control: Garantizar el uso de recursos a través de una adecuada organización, clasificación y control.",
        "SUBCOMPETENCIA" => "Organización de Recursos",
        "DESCRIPCION" => "Clasifica los recursos con un orden claro y práctico."
      ],
      [
        "CATEGORIA" => "Auditor Interno",
        "COMPETENCIA" => "Administración Estratégica: Fomúla las acciones del negocio definiendo procedimientos claros, evaluando las acciones efectivas y controlando el presupuesto.",
        "SUBCOMPETENCIA" => "Control Presupuestal",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Auditor Interno",
        "COMPETENCIA" => "Administración Estratégica: Fomúla las acciones del negocio definiendo procedimientos claros, evaluando las acciones efectivas y controlando el presupuesto.",
        "SUBCOMPETENCIA" => "Evaluación Del Negocio",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Auditor Interno",
        "COMPETENCIA" => "Administración Estratégica: Fomúla las acciones del negocio definiendo procedimientos claros, evaluando las acciones efectivas y controlando el presupuesto.",
        "SUBCOMPETENCIA" => "Fórmulación De Estrategia",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Auditor Interno",
        "COMPETENCIA" => "Administración Estratégica: Fomúla las acciones del negocio definiendo procedimientos claros, evaluando las acciones efectivas y controlando el presupuesto.",
        "SUBCOMPETENCIA" => "Implementación",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Auditor Interno",
        "COMPETENCIA" => "Consultoría: Brinda alternativas de solución a partir del conocimiento del negocio; la identificación de las necesidades de los colaboradores y la organización.",
        "SUBCOMPETENCIA" => "Asesoría",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Auditor Interno",
        "COMPETENCIA" => "Consultoría: Brinda alternativas de solución a partir del conocimiento del negocio; la identificación de las necesidades de los colaboradores y la organización.",
        "SUBCOMPETENCIA" => "Conoce El Negocio",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Auditor Interno",
        "COMPETENCIA" => "Consultoría: Brinda alternativas de solución a partir del conocimiento del negocio; la identificación de las necesidades de los colaboradores y la organización.",
        "SUBCOMPETENCIA" => "Evaluación De Colaboradores",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Auditor Interno",
        "COMPETENCIA" => "Consultoría: Brinda alternativas de solución a partir del conocimiento del negocio; la identificación de las necesidades de los colaboradores y la organización.",
        "SUBCOMPETENCIA" => "Identificación De Expectativas Y Necesidades",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Auditor Interno",
        "COMPETENCIA" => "Control del Mantenimiento: Soluciona los problemas de manera efectiva. Evalúa el rendimiento de mantenimiento e identifica oportunidades de reducción de costos.",
        "SUBCOMPETENCIA" => "Control De Costos",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Auditor Interno",
        "COMPETENCIA" => "Control del Mantenimiento: Soluciona los problemas de manera efectiva. Evalúa el rendimiento de mantenimiento e identifica oportunidades de reducción de costos.",
        "SUBCOMPETENCIA" => "Evaluación De Indicadores",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Auditor Interno",
        "COMPETENCIA" => "Control del Mantenimiento: Soluciona los problemas de manera efectiva. Evalúa el rendimiento de mantenimiento e identifica oportunidades de reducción de costos.",
        "SUBCOMPETENCIA" => "Solución De Problemas",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Auditor Interno",
        "COMPETENCIA" => "Gestión Comercial - Versión 2 AP: Propone las estrategias comerciales, objetivos y metas de acuerdo al conocimiento del negocio, producto y el funcionamiento de la competencia.",
        "SUBCOMPETENCIA" => "Conocimiento De La Competencia",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Auditor Interno",
        "COMPETENCIA" => "Gestión Comercial - Versión 2 AP: Propone las estrategias comerciales, objetivos y metas de acuerdo al conocimiento del negocio, producto y el funcionamiento de la competencia.",
        "SUBCOMPETENCIA" => "Conocimiento Del Negocio",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Auditor Interno",
        "COMPETENCIA" => "Gestión Comercial - Versión 2 AP: Propone las estrategias comerciales, objetivos y metas de acuerdo al conocimiento del negocio, producto y el funcionamiento de la competencia.",
        "SUBCOMPETENCIA" => "Propone Estrategias, Objetivos Y Metas",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Auditor Interno",
        "COMPETENCIA" => "Gestión Comercial - Versión 2 AP: Propone las estrategias comerciales, objetivos y metas de acuerdo al conocimiento del negocio, producto y el funcionamiento de la competencia.",
        "SUBCOMPETENCIA" => "Reajustes Comerciales",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Auditor Interno",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Control",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Auditor Interno",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Gestión De Consecuencias",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Auditor Interno",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Retroalimentación",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Auditor Interno",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Seguimiento",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Auditor Interno",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Supervisión",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Auditor Interno",
        "COMPETENCIA" => "Gestión de normas y procedimientos - Versión GP: Gestion la creación de normas y procedimientos transversales para los negocios explicando efectivamente el contenido y los lineamientos.",
        "SUBCOMPETENCIA" => "Definición de Políticas",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Auditor Interno",
        "COMPETENCIA" => "Gestión de normas y procedimientos - Versión GP: Gestion la creación de normas y procedimientos transversales para los negocios explicando efectivamente el contenido y los lineamientos.",
        "SUBCOMPETENCIA" => "Definición De Políticas Gp",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Auditor Interno",
        "COMPETENCIA" => "Gestión de normas y procedimientos: Se preocupa y promueve respeto de las normas y procedimientos. Vigila el cumplimiento de las normas e interviene para corregir faltas.",
        "SUBCOMPETENCIA" => "Claridad En La Orientación",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Auditor Interno",
        "COMPETENCIA" => "Gestión de normas y procedimientos: Se preocupa y promueve respeto de las normas y procedimientos. Vigila el cumplimiento de las normas e interviene para corregir faltas.",
        "SUBCOMPETENCIA" => "Gestión De Consecuencias V1",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Auditor Interno",
        "COMPETENCIA" => "Gestión de normas y procedimientos: Se preocupa y promueve respeto de las normas y procedimientos. Vigila el cumplimiento de las normas e interviene para corregir faltas.",
        "SUBCOMPETENCIA" => "Respeto A La Norma",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Auditor Interno",
        "COMPETENCIA" => "Gestión de normas y procedimientos: Se preocupa y promueve respeto de las normas y procedimientos. Vigila el cumplimiento de las normas e interviene para corregir faltas.",
        "SUBCOMPETENCIA" => "Revisión Y Seguimiento",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Auditor Interno",
        "COMPETENCIA" => "Habilidad analítica: Capacidad de razonamiento y organización cognitivamente del trabajo para realizar un análisis lógico identificando problemas, información y datos significativos.",
        "SUBCOMPETENCIA" => "Análisis Lógico",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Auditor Interno",
        "COMPETENCIA" => "Habilidad analítica: Capacidad de razonamiento y organización cognitivamente del trabajo para realizar un análisis lógico identificando problemas, información y datos significativos.",
        "SUBCOMPETENCIA" => "Capacidad De Comprensión",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Auditor Interno",
        "COMPETENCIA" => "Habilidad analítica: Capacidad de razonamiento y organización cognitivamente del trabajo para realizar un análisis lógico identificando problemas, información y datos significativos.",
        "SUBCOMPETENCIA" => "Identificación De Problemas",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Auditor Interno",
        "COMPETENCIA" => "Habilidad analítica: Capacidad de razonamiento y organización cognitivamente del trabajo para realizar un análisis lógico identificando problemas, información y datos significativos.",
        "SUBCOMPETENCIA" => "Tratamiento De La Información",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Auditor Interno",
        "COMPETENCIA" => "Habilidad de Enseñanza: Logra explicar con claridad y paciencia el funcionamiento de la operación, brinda retroalmentación clara para incorporar los procedimientos e instrucciones.",
        "SUBCOMPETENCIA" => "Capacidad Para Explicar",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Auditor Interno",
        "COMPETENCIA" => "Habilidad de Enseñanza: Logra explicar con claridad y paciencia el funcionamiento de la operación, brinda retroalmentación clara para incorporar los procedimientos e instrucciones.",
        "SUBCOMPETENCIA" => "Evaluación",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Auditor Interno",
        "COMPETENCIA" => "Habilidad de Enseñanza: Logra explicar con claridad y paciencia el funcionamiento de la operación, brinda retroalmentación clara para incorporar los procedimientos e instrucciones.",
        "SUBCOMPETENCIA" => "Retroalimentación En La Enseñanza",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Auditor Interno",
        "COMPETENCIA" => "Habilidad de Enseñanza: Logra explicar con claridad y paciencia el funcionamiento de la operación, brinda retroalmentación clara para incorporar los procedimientos e instrucciones.",
        "SUBCOMPETENCIA" => "Tolerancia En La Enseñanza",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Auditor Interno",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Asumo Las Consecuencias",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Auditor Interno",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Cumplimiento De Acuerdos",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Auditor Interno",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Proactividad",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Auditor Interno",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Resultados",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Auditor Interno",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Solución De Problema",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Auditor Interno",
        "COMPETENCIA" => "Registro y Manejo de Información - Versión 2 AP: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Cumplimiento De Procesos Operativos",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Auditor Interno",
        "COMPETENCIA" => "Registro y Manejo de Información - Versión 2 AP: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Organización",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Auditor Interno",
        "COMPETENCIA" => "Registro y Manejo de Información - Versión 2 AP: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Orientación Al Detalle",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Auxiliar De Lavado Y Limpieza",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Claridad en el mensaje",
        "DESCRIPCION" => "Comunica sus ideas de forma clara y precisa."
      ],
      [
        "CATEGORIA" => "Auxiliar De Lavado Y Limpieza",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Respuesta Oportuna",
        "DESCRIPCION" => "Comparte información e ideas en el momento adecuado."
      ],
      [
        "CATEGORIA" => "Auxiliar De Lavado Y Limpieza",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Escucha",
        "DESCRIPCION" => "Escucha atentamente las opiniones y sugerencias."
      ],
      [
        "CATEGORIA" => "Auxiliar De Lavado Y Limpieza",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Asertividad",
        "DESCRIPCION" => "Expresa sus opiniones sin herir los sentimientos de los demás."
      ],
      [
        "CATEGORIA" => "Auxiliar De Lavado Y Limpieza",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Gestión de Recursos",
        "DESCRIPCION" => "Identifica y asigna los recursos de manera responsable."
      ],
      [
        "CATEGORIA" => "Auxiliar De Lavado Y Limpieza",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Metodología",
        "DESCRIPCION" => "Utiliza metodologías o procesos estandarizados que garantizan los resultados esperados."
      ],
      [
        "CATEGORIA" => "Auxiliar De Lavado Y Limpieza",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Orientación al logro",
        "DESCRIPCION" => "Logra los resultados esperados en los tiempos establecidos."
      ],
      [
        "CATEGORIA" => "Auxiliar De Lavado Y Limpieza",
        "COMPETENCIA" => "Organización y Control: Garantizar el uso de recursos a través de una adecuada organización, clasificación y control.",
        "SUBCOMPETENCIA" => "Control de Recursos",
        "DESCRIPCION" => "Controla la cantidad de recursos necesarios para realizar las tareas."
      ],
      [
        "CATEGORIA" => "Auxiliar De Lavado Y Limpieza",
        "COMPETENCIA" => "Organización y Control: Garantizar el uso de recursos a través de una adecuada organización, clasificación y control.",
        "SUBCOMPETENCIA" => "Protección de Recursos",
        "DESCRIPCION" => "Cuida los recursos para mantener su calidad."
      ],
      [
        "CATEGORIA" => "Auxiliar De Lavado Y Limpieza",
        "COMPETENCIA" => "Organización y Control: Garantizar el uso de recursos a través de una adecuada organización, clasificación y control.",
        "SUBCOMPETENCIA" => "Organización de Recursos",
        "DESCRIPCION" => "Clasifica los recursos con un orden claro y práctico."
      ],
      [
        "CATEGORIA" => "Auxiliar De Limpieza Y Conserjeria",
        "COMPETENCIA" => "Mentalidad de Dueño V2: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Proactividad",
        "DESCRIPCION" => "Se anticipa a los requerimientos o necesidades que se presentan."
      ],
      [
        "CATEGORIA" => "Auxiliar De Limpieza Y Conserjeria",
        "COMPETENCIA" => "Mentalidad de Dueño V2: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Solución de Problemas",
        "DESCRIPCION" => "Resuelve los problemas a pesar de las dificultades."
      ],
      [
        "CATEGORIA" => "Auxiliar De Limpieza Y Conserjeria",
        "COMPETENCIA" => "Mentalidad de Dueño V2: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Cumplimiento de Acuerdos",
        "DESCRIPCION" => "Cumple con los acuerdos de la organización."
      ],
      [
        "CATEGORIA" => "Auxiliar De Limpieza Y Conserjeria",
        "COMPETENCIA" => "Mentalidad de Dueño V2: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Resultados",
        "DESCRIPCION" => "Logra los resultados que se le asignan."
      ],
      [
        "CATEGORIA" => "Auxiliar De Limpieza Y Conserjeria",
        "COMPETENCIA" => "Mentalidad de Dueño V2: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Asumo las consecuencias",
        "DESCRIPCION" => "Me hago cargo de los errores que realizo y los resuelvo. No espero a que otros lo solucionen."
      ],
      [
        "CATEGORIA" => "Auxiliar De Limpieza Y Conserjeria",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Claridad en el mensaje",
        "DESCRIPCION" => "Comunica sus ideas de forma clara y precisa."
      ],
      [
        "CATEGORIA" => "Auxiliar De Limpieza Y Conserjeria",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Respuesta Oportuna",
        "DESCRIPCION" => "Comparte información e ideas en el momento adecuado."
      ],
      [
        "CATEGORIA" => "Auxiliar De Limpieza Y Conserjeria",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Escucha",
        "DESCRIPCION" => "Escucha atentamente las opiniones y sugerencias."
      ],
      [
        "CATEGORIA" => "Auxiliar De Limpieza Y Conserjeria",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Asertividad",
        "DESCRIPCION" => "Expresa sus opiniones sin herir los sentimientos de los demás."
      ],
      [
        "CATEGORIA" => "Auxiliar De Limpieza Y Conserjeria",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Atención Efectiva a consultas",
        "DESCRIPCION" => "Responde oportunamente a las solicitudes y consultas de los clientes vía presencial, telefónica y por correo electrónico."
      ],
      [
        "CATEGORIA" => "Auxiliar De Limpieza Y Conserjeria",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Seguimiento a la atención",
        "DESCRIPCION" => "Vigilo la adecuada atención del cliente y pido al responsable a que se haga cargo."
      ],
      [
        "CATEGORIA" => "Auxiliar De Limpieza Y Conserjeria",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Atención presencial oportuna",
        "DESCRIPCION" => "Hago todo lo necesario para que el cliente sea bien atendido en el menor tiempo posible."
      ],
      [
        "CATEGORIA" => "Auxiliar De Limpieza Y Conserjeria",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Amabilidad y cercanía",
        "DESCRIPCION" => "Trata al cliente con amabilidad generando cercanía y confianza."
      ],
      [
        "CATEGORIA" => "Auxiliar De Limpieza Y Conserjeria",
        "COMPETENCIA" => "Organización y Control: Garantizar el uso de recursos a través de una adecuada organización, clasificación y control.",
        "SUBCOMPETENCIA" => "Control de Recursos",
        "DESCRIPCION" => "Controla la cantidad de recursos necesarios para realizar las tareas."
      ],
      [
        "CATEGORIA" => "Auxiliar De Limpieza Y Conserjeria",
        "COMPETENCIA" => "Organización y Control: Garantizar el uso de recursos a través de una adecuada organización, clasificación y control.",
        "SUBCOMPETENCIA" => "Protección de Recursos",
        "DESCRIPCION" => "Cuida los recursos para mantener su calidad."
      ],
      [
        "CATEGORIA" => "Auxiliar De Limpieza Y Conserjeria",
        "COMPETENCIA" => "Organización y Control: Garantizar el uso de recursos a través de una adecuada organización, clasificación y control.",
        "SUBCOMPETENCIA" => "Organización de Recursos",
        "DESCRIPCION" => "Clasifica los recursos con un orden claro y práctico."
      ],
      [
        "CATEGORIA" => "Auxiliar De Operaciones - Chiclayo",
        "COMPETENCIA" => "Cooperación Operativa: Coopera de manera activa con las necesidades del equipo y unidades con las que interactúa. Respeta los acuerdos generando un espacio colaborativo y de confianza.",
        "SUBCOMPETENCIA" => "Cooperación",
        "DESCRIPCION" => "Muestra predisposición y apoyo para hacer trabajos operativos."
      ],
      [
        "CATEGORIA" => "Auxiliar De Operaciones - Chiclayo",
        "COMPETENCIA" => "Cooperación Operativa: Coopera de manera activa con las necesidades del equipo y unidades con las que interactúa. Respeta los acuerdos generando un espacio colaborativo y de confianza.",
        "SUBCOMPETENCIA" => "Orientación a la meta común",
        "DESCRIPCION" => "Apoya a los demás para realizar un entregable de manera rápida."
      ],
      [
        "CATEGORIA" => "Auxiliar De Operaciones - Chiclayo",
        "COMPETENCIA" => "Cooperación Operativa: Coopera de manera activa con las necesidades del equipo y unidades con las que interactúa. Respeta los acuerdos generando un espacio colaborativo y de confianza.",
        "SUBCOMPETENCIA" => "Relaciones interpersonales",
        "DESCRIPCION" => "Tiene buenas relaciones con los demás y genera confianza."
      ],
      [
        "CATEGORIA" => "Auxiliar De Operaciones - Chiclayo",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Gestión de Recursos",
        "DESCRIPCION" => "Identifica y asigna los recursos de manera responsable."
      ],
      [
        "CATEGORIA" => "Auxiliar De Operaciones - Chiclayo",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Metodología",
        "DESCRIPCION" => "Utiliza metodologías o procesos estandarizados que garantizan los resultados esperados."
      ],
      [
        "CATEGORIA" => "Auxiliar De Operaciones - Chiclayo",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Orientación al logro",
        "DESCRIPCION" => "Logra los resultados esperados en los tiempos establecidos."
      ],
      [
        "CATEGORIA" => "Auxiliar De Operaciones - Chiclayo",
        "COMPETENCIA" => "Organización y Control: Garantizar el uso de recursos a través de una adecuada organización, clasificación y control.",
        "SUBCOMPETENCIA" => "Control de Recursos",
        "DESCRIPCION" => "Controla la cantidad de recursos necesarios para realizar las tareas."
      ],
      [
        "CATEGORIA" => "Auxiliar De Operaciones - Chiclayo",
        "COMPETENCIA" => "Organización y Control: Garantizar el uso de recursos a través de una adecuada organización, clasificación y control.",
        "SUBCOMPETENCIA" => "Protección de Recursos",
        "DESCRIPCION" => "Cuida los recursos para mantener su calidad."
      ],
      [
        "CATEGORIA" => "Auxiliar De Operaciones - Chiclayo",
        "COMPETENCIA" => "Organización y Control: Garantizar el uso de recursos a través de una adecuada organización, clasificación y control.",
        "SUBCOMPETENCIA" => "Organización de Recursos",
        "DESCRIPCION" => "Clasifica los recursos con un orden claro y práctico."
      ],
      [
        "CATEGORIA" => "Auxiliar De Operaciones Lima",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Claridad en el mensaje",
        "DESCRIPCION" => "Comunica sus ideas de forma clara y precisa."
      ],
      [
        "CATEGORIA" => "Auxiliar De Operaciones Lima",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Respuesta Oportuna",
        "DESCRIPCION" => "Comparte información e ideas en el momento adecuado."
      ],
      [
        "CATEGORIA" => "Auxiliar De Operaciones Lima",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Escucha",
        "DESCRIPCION" => "Escucha atentamente las opiniones y sugerencias."
      ],
      [
        "CATEGORIA" => "Auxiliar De Operaciones Lima",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Asertividad",
        "DESCRIPCION" => "Expresa sus opiniones sin herir los sentimientos de los demás."
      ],
      [
        "CATEGORIA" => "Auxiliar De Operaciones Lima",
        "COMPETENCIA" => "Cooperación Operativa: Coopera de manera activa con las necesidades del equipo y unidades con las que interactúa. Respeta los acuerdos generando un espacio colaborativo y de confianza.",
        "SUBCOMPETENCIA" => "Cooperación",
        "DESCRIPCION" => "Muestra predisposición y apoyo para hacer trabajos operativos."
      ],
      [
        "CATEGORIA" => "Auxiliar De Operaciones Lima",
        "COMPETENCIA" => "Cooperación Operativa: Coopera de manera activa con las necesidades del equipo y unidades con las que interactúa. Respeta los acuerdos generando un espacio colaborativo y de confianza.",
        "SUBCOMPETENCIA" => "Orientación a la meta común",
        "DESCRIPCION" => "Apoya a los demás para realizar un entregable de manera rápida."
      ],
      [
        "CATEGORIA" => "Auxiliar De Operaciones Lima",
        "COMPETENCIA" => "Cooperación Operativa: Coopera de manera activa con las necesidades del equipo y unidades con las que interactúa. Respeta los acuerdos generando un espacio colaborativo y de confianza.",
        "SUBCOMPETENCIA" => "Relaciones interpersonales",
        "DESCRIPCION" => "Tiene buenas relaciones con los demás y genera confianza."
      ],
      [
        "CATEGORIA" => "Auxiliar De Operaciones Lima",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Gestión de Recursos",
        "DESCRIPCION" => "Identifica y asigna los recursos de manera responsable."
      ],
      [
        "CATEGORIA" => "Auxiliar De Operaciones Lima",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Metodología",
        "DESCRIPCION" => "Utiliza metodologías o procesos estandarizados que garantizan los resultados esperados."
      ],
      [
        "CATEGORIA" => "Auxiliar De Operaciones Lima",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Orientación al logro",
        "DESCRIPCION" => "Logra los resultados esperados en los tiempos establecidos."
      ],
      [
        "CATEGORIA" => "Auxiliar De Operaciones Lima",
        "COMPETENCIA" => "Organización y Control: Garantizar el uso de recursos a través de una adecuada organización, clasificación y control.",
        "SUBCOMPETENCIA" => "Control de Recursos",
        "DESCRIPCION" => "Controla la cantidad de recursos necesarios para realizar las tareas."
      ],
      [
        "CATEGORIA" => "Auxiliar De Operaciones Lima",
        "COMPETENCIA" => "Organización y Control: Garantizar el uso de recursos a través de una adecuada organización, clasificación y control.",
        "SUBCOMPETENCIA" => "Protección de Recursos",
        "DESCRIPCION" => "Cuida los recursos para mantener su calidad."
      ],
      [
        "CATEGORIA" => "Auxiliar De Operaciones Lima",
        "COMPETENCIA" => "Organización y Control: Garantizar el uso de recursos a través de una adecuada organización, clasificación y control.",
        "SUBCOMPETENCIA" => "Organización de Recursos",
        "DESCRIPCION" => "Clasifica los recursos con un orden claro y práctico."
      ],
      [
        "CATEGORIA" => "Caja Ap",
        "COMPETENCIA" => "Registro y Manejo de Caja: Control y registro de información adecuada de la Caja, revisando de manera adecuada los billetes y cumpliendo con los procedimientos para el cierre de caja.",
        "SUBCOMPETENCIA" => "Revisión de ingresos",
        "DESCRIPCION" => "Revisa de manera oportuna los billetes recibidos por los clientes."
      ],
      [
        "CATEGORIA" => "Caja Ap",
        "COMPETENCIA" => "Registro y Manejo de Caja: Control y registro de información adecuada de la Caja, revisando de manera adecuada los billetes y cumpliendo con los procedimientos para el cierre de caja.",
        "SUBCOMPETENCIA" => "Cierre de Caja",
        "DESCRIPCION" => "Realiza un cierre de caja adecuado y en el tiempo que corresponde."
      ],
      [
        "CATEGORIA" => "Caja Ap",
        "COMPETENCIA" => "Registro y Manejo de Caja: Control y registro de información adecuada de la Caja, revisando de manera adecuada los billetes y cumpliendo con los procedimientos para el cierre de caja.",
        "SUBCOMPETENCIA" => "Entrega de Dinero",
        "DESCRIPCION" => "Entrega el dinero de las ventas de manera oportuna."
      ],
      [
        "CATEGORIA" => "Caja Ap",
        "COMPETENCIA" => "Registro y Manejo de Caja: Control y registro de información adecuada de la Caja, revisando de manera adecuada los billetes y cumpliendo con los procedimientos para el cierre de caja.",
        "SUBCOMPETENCIA" => "Reportes",
        "DESCRIPCION" => "Emite reportes claros y completos de caja."
      ],
      [
        "CATEGORIA" => "Caja Ap",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Orientación al Detalle",
        "DESCRIPCION" => "Es minucioso en el manejo de datos."
      ],
      [
        "CATEGORIA" => "Caja Ap",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Organización",
        "DESCRIPCION" => "Mantiene un orden en los documentos que maneja."
      ],
      [
        "CATEGORIA" => "Caja Ap",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Cumplimiento de Procesos Operativos",
        "DESCRIPCION" => "Cumple con los procedimientos establecidos por la organización de manera correcta."
      ],
      [
        "CATEGORIA" => "Caja Ap",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Atención Efectiva a consultas",
        "DESCRIPCION" => "Responde oportunamente a las solicitudes y consultas de los clientes vía presencial, telefónica y por correo electrónico."
      ],
      [
        "CATEGORIA" => "Caja Ap",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Seguimiento a la atención",
        "DESCRIPCION" => "Vigilo la adecuada atención del cliente y pido al responsable a que se haga cargo."
      ],
      [
        "CATEGORIA" => "Caja Ap",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Atención presencial oportuna",
        "DESCRIPCION" => "Hago todo lo necesario para que el cliente sea bien atendido en el menor tiempo posible."
      ],
      [
        "CATEGORIA" => "Caja Ap",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Amabilidad y cercanía",
        "DESCRIPCION" => "Trata al cliente con amabilidad generando cercanía y confianza."
      ],
      [
        "CATEGORIA" => "Caja Ap",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",

        "SUBCOMPETENCIA" => "Proactividad",
        "DESCRIPCION" => "Se anticipa a los requerimientos o necesidades que se presentan."
      ],
      [
        "CATEGORIA" => "Caja Ap",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",

        "SUBCOMPETENCIA" => "Solución de Problemas",
        "DESCRIPCION" => "Resuelve los problemas a pesar de las dificultades."
      ],
      [
        "CATEGORIA" => "Caja Ap",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",

        "SUBCOMPETENCIA" => "Cumplimiento de Acuerdos",
        "DESCRIPCION" => "Cumple con los acuerdos de la organización."
      ],
      [
        "CATEGORIA" => "Caja Ap",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",

        "SUBCOMPETENCIA" => "Resultados",
        "DESCRIPCION" => "Logra los resultados que se le asignan."
      ],
      [
        "CATEGORIA" => "Caja Ap",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",

        "SUBCOMPETENCIA" => "Asumo las consecuencias",
        "DESCRIPCION" => "Me hago cargo de los errores que realizo y los resuelvo. No espero a que otros lo solucionen."
      ],
      [
        "CATEGORIA" => "Caja Ap",
        "COMPETENCIA" => "Seguimiento a las cobranzas",
        "SUBCOMPETENCIA" => "Solicitud de información",
        "DESCRIPCION" => "Solicita la información adecuada para el seguimiento de facturas."
      ],
      [
        "CATEGORIA" => "Caja Ap",
        "COMPETENCIA" => "Seguimiento a las cobranzas",
        "SUBCOMPETENCIA" => "Seguimiento de facturas",
        "DESCRIPCION" => "Realiza el seguimiento oportuno a los clientes con facturas vencidas."
      ],
      [
        "CATEGORIA" => "Caja Ap",
        "COMPETENCIA" => "Seguimiento a las cobranzas",
        "SUBCOMPETENCIA" => "Revisión de cobranza",
        "DESCRIPCION" => "Revisa que los responsables de cobranza cumplan con los parámetros establecidos."
      ],
      [
        "CATEGORIA" => "Caja Ap",
        "COMPETENCIA" => "Seguimiento a las cobranzas",
        "SUBCOMPETENCIA" => "Comunicación de Avances",
        "DESCRIPCION" => "Informa oportunamente sobre el avance de las cuentas por pagar."
      ],
      [
        "CATEGORIA" => "Caja Dp",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Claridad en el mensaje",
        "DESCRIPCION" => "Comunica sus ideas de forma clara y precisa."
      ],
      [
        "CATEGORIA" => "Caja Dp",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Respuesta Oportuna",
        "DESCRIPCION" => "Comparte información e ideas en el momento adecuado."
      ],
      [
        "CATEGORIA" => "Caja Dp",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Escucha",
        "DESCRIPCION" => "Escucha atentamente las opiniones y sugerencias."
      ],
      [
        "CATEGORIA" => "Caja Dp",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Asertividad",
        "DESCRIPCION" => "Expresa sus opiniones sin herir los sentimientos de los demás."
      ],
      [
        "CATEGORIA" => "Caja Dp",
        "COMPETENCIA" => "Registro y Manejo de Caja: Control y registro de información adecuada de la Caja, revisando de manera adecuada los billetes y cumpliendo con los procedimientos para el cierre de caja.",
        "SUBCOMPETENCIA" => "Revisión de ingresos",
        "DESCRIPCION" => "Revisa de manera oportuna los billetes recibidos por los clientes."
      ],
      [
        "CATEGORIA" => "Caja Dp",
        "COMPETENCIA" => "Registro y Manejo de Caja: Control y registro de información adecuada de la Caja, revisando de manera adecuada los billetes y cumpliendo con los procedimientos para el cierre de caja.",
        "SUBCOMPETENCIA" => "Cierre de Caja",
        "DESCRIPCION" => "Realiza un cierre de caja adecuado y en el tiempo que corresponde."
      ],
      [
        "CATEGORIA" => "Caja Dp",
        "COMPETENCIA" => "Registro y Manejo de Caja: Control y registro de información adecuada de la Caja, revisando de manera adecuada los billetes y cumpliendo con los procedimientos para el cierre de caja.",
        "SUBCOMPETENCIA" => "Entrega de Dinero",
        "DESCRIPCION" => "Entrega el dinero de las ventas de manera oportuna."
      ],
      [
        "CATEGORIA" => "Caja Dp",
        "COMPETENCIA" => "Registro y Manejo de Caja: Control y registro de información adecuada de la Caja, revisando de manera adecuada los billetes y cumpliendo con los procedimientos para el cierre de caja.",
        "SUBCOMPETENCIA" => "Reportes",
        "DESCRIPCION" => "Emite reportes claros y completos de caja."
      ],
      [
        "CATEGORIA" => "Caja Dp",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Orientación al Detalle",
        "DESCRIPCION" => "Es minucioso en el manejo de datos."
      ],
      [
        "CATEGORIA" => "Caja Dp",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Organización",
        "DESCRIPCION" => "Mantiene un orden en los documentos que maneja."
      ],
      [
        "CATEGORIA" => "Caja Dp",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Cumplimiento de Procesos Operativos",
        "DESCRIPCION" => "Cumple con los procedimientos establecidos por la organización de manera correcta."
      ],
      [
        "CATEGORIA" => "Caja Dp",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Atención Efectiva a consultas",
        "DESCRIPCION" => "Responde oportunamente a las solicitudes y consultas de los clientes vía presencial, telefónica y por correo electrónico."
      ],
      [
        "CATEGORIA" => "Caja Dp",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Seguimiento a la atención",
        "DESCRIPCION" => "Vigila la adecuada atención del cliente y pide al responsable que se haga cargo."
      ],
      [
        "CATEGORIA" => "Caja Dp",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Atención presencial oportuna",
        "DESCRIPCION" => "Hace todo lo necesario para que el cliente sea bien atendido en el menor tiempo posible."
      ],
      [
        "CATEGORIA" => "Caja Dp",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Amabilidad y cercanía",
        "DESCRIPCION" => "Trata al cliente con amabilidad generando cercanía y confianza."
      ],
      [
        "CATEGORIA" => "Caja Dp",
        "COMPETENCIA" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "SUBCOMPETENCIA" => "Traspase de información",
        "DESCRIPCION" => "Verifica que la información que le brinda al otro llegue de manera efectiva y completa."
      ],
      [
        "CATEGORIA" => "Caja Dp",
        "COMPETENCIA" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "SUBCOMPETENCIA" => "Colaboración",
        "DESCRIPCION" => "Colabora con los demás para cumplir con el objetivo en común."
      ],
      [
        "CATEGORIA" => "Caja Dp",
        "COMPETENCIA" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "SUBCOMPETENCIA" => "Responsabilidad",
        "DESCRIPCION" => "Asume la responsabilidad que le corresponde, no espera que sólo los demás trabajen."
      ],
      [
        "CATEGORIA" => "Caja Dp",
        "COMPETENCIA" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "SUBCOMPETENCIA" => "Resuelve conflictos",
        "DESCRIPCION" => "Resuelve de manera oportuna las dificultades que pueda tener con sus compañeros"
      ],
      [
        "CATEGORIA" => "Caja Dp",
        "COMPETENCIA" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "SUBCOMPETENCIA" => "Pasión",
        "DESCRIPCION" => "Pone la mejor energía para hacer sus tareas y jugar en equipo."
      ],
      [
        "CATEGORIA" => "Caja General",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Claridad en el mensaje",
        "DESCRIPCION" => "Comunica sus ideas de forma clara y precisa."
      ],
      [
        "CATEGORIA" => "Caja General",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Respuesta Oportuna",
        "DESCRIPCION" => "Comparte información e ideas en el momento adecuado."
      ],
      [
        "CATEGORIA" => "Caja General",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Escucha",
        "DESCRIPCION" => "Escucha atentamente las opiniones y sugerencias."
      ],
      [
        "CATEGORIA" => "Caja General",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Asertividad",
        "DESCRIPCION" => "Expresa sus opiniones sin herir los sentimientos de los demás."
      ],
      [
        "CATEGORIA" => "Caja General",
        "COMPETENCIA" => "Mentalidad de Dueño V2: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Proactividad",
        "DESCRIPCION" => "Se anticipa a los requerimientos o necesidades que se presentan."
      ],
      [
        "CATEGORIA" => "Caja General",
        "COMPETENCIA" => "Mentalidad de Dueño V2: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Solución de Problemas",
        "DESCRIPCION" => "Resuelve los problemas a pesar de las dificultades."
      ],
      [
        "CATEGORIA" => "Caja General",
        "COMPETENCIA" => "Mentalidad de Dueño V2: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Cumplimiento de Acuerdos",
        "DESCRIPCION" => "Cumple con los acuerdos de la organización."
      ],
      [
        "CATEGORIA" => "Caja General",
        "COMPETENCIA" => "Mentalidad de Dueño V2: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Resultados",
        "DESCRIPCION" => "Logra los resultados que se le asignan."
      ],
      [
        "CATEGORIA" => "Caja General",
        "COMPETENCIA" => "Mentalidad de Dueño V2: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Asumo las consecuencias",
        "DESCRIPCION" => "Se hace cargo de los errores que realiza y los resuelve. No espera a que otros lo solucionen"
      ],
      [
        "CATEGORIA" => "Caja General",
        "COMPETENCIA" => "Registro y Manejo de Caja - Caja General",
        "SUBCOMPETENCIA" => "Revisión de ingresos",
        "DESCRIPCION" => "Revisa de manera oportuna los billetes recibidos por los clientes."
      ],
      [
        "CATEGORIA" => "Caja General",
        "COMPETENCIA" => "Registro y Manejo de Caja - Caja General",
        "SUBCOMPETENCIA" => "Cierre de Caja",
        "DESCRIPCION" => "Realiza un cierre de caja adecuado y en el tiempo que corresponde."
      ],
      [
        "CATEGORIA" => "Caja General",
        "COMPETENCIA" => "Registro y Manejo de Caja - Caja General",
        "SUBCOMPETENCIA" => "Entrega de Dinero",
        "DESCRIPCION" => "Entrega el dinero de las ventas de manera oportuna."
      ],
      [
        "CATEGORIA" => "Caja General",
        "COMPETENCIA" => "Registro y Manejo de Caja - Caja General",
        "SUBCOMPETENCIA" => "Reportes",
        "DESCRIPCION" => "Emite reportes claros y completos de caja."
      ],
      [
        "CATEGORIA" => "Caja General",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Orientación al Detalle",
        "DESCRIPCION" => "Es minucioso en el manejo de datos."
      ],
      [
        "CATEGORIA" => "Caja General",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Organización",
        "DESCRIPCION" => "Mantiene un orden en los documentos que maneja."
      ],
      [
        "CATEGORIA" => "Caja General",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Cumplimiento de Procesos Operativos",
        "DESCRIPCION" => "Cumple con los procedimientos establecidos por la organización de manera correcta."
      ],
      [
        "CATEGORIA" => "Caja General",
        "COMPETENCIA" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "SUBCOMPETENCIA" => "Traspase de información",
        "DESCRIPCION" => "Verifica que la información que le brinda al otro llegue de manera efectiva y completa."
      ],
      [
        "CATEGORIA" => "Caja General",
        "COMPETENCIA" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "SUBCOMPETENCIA" => "Colaboración",
        "DESCRIPCION" => "Colabora con los demás para cumplir con el objetivo en común."
      ],
      [
        "CATEGORIA" => "Caja General",
        "COMPETENCIA" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "SUBCOMPETENCIA" => "Responsabilidad",
        "DESCRIPCION" => "Asume la responsabilidad que le corresponde, no espera que sólo los demás trabajen."
      ],
      [
        "CATEGORIA" => "Caja General",
        "COMPETENCIA" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "SUBCOMPETENCIA" => "Resuelve conflictos",
        "DESCRIPCION" => "Resuelve de manera oportuna las dificultades que pueda tener con sus compañeros"
      ],
      [
        "CATEGORIA" => "Caja General",
        "COMPETENCIA" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "SUBCOMPETENCIA" => "Pasión",
        "DESCRIPCION" => "Pone la mejor energía para hacer sus tareas y jugar en equipo."
      ],
      [
        "CATEGORIA" => "Caja Tp",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Asertividad",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Caja Tp",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Claridad en el mensaje",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Caja Tp",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Escucha",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Caja Tp",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Informe Escrito",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Caja Tp",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Orientación A La Meta Común",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Caja Tp",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Respuesta Oportuna",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Caja Tp",
        "COMPETENCIA" => "Mentalidad de Dueño V2: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Anticipación A Requerimientos",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Caja Tp",
        "COMPETENCIA" => "Mentalidad de Dueño V2: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Pertenencia",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Caja Tp",
        "COMPETENCIA" => "Mentalidad de Dueño V2: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Solución De Problemas Difíciles",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Caja Tp",
        "COMPETENCIA" => "Registro y Manejo de Caja - Caja General",
        "SUBCOMPETENCIA" => "Cierre De Caja Cg",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Caja Tp",
        "COMPETENCIA" => "Registro y Manejo de Caja - Caja General",
        "SUBCOMPETENCIA" => "Entrega De Dinero Cg",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Caja Tp",
        "COMPETENCIA" => "Registro y Manejo de Caja - Caja General",
        "SUBCOMPETENCIA" => "Reportes Cg",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Caja Tp",
        "COMPETENCIA" => "Registro y Manejo de Caja - Caja General",
        "SUBCOMPETENCIA" => "Revisión De Ingresos Cg",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Caja Tp",
        "COMPETENCIA" => "Registro y Manejo de Caja - Caja General",
        "SUBCOMPETENCIA" => "Seguimiento De Cobranzas Cg",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Caja Tp",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Cumplimiento De Procedimientos",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Caja Tp",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Organización De Información",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Caja Tp",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Orientación Al Registro Detallado",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Caja Tp",
        "COMPETENCIA" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "SUBCOMPETENCIA" => "Colaboración",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Caja Tp",
        "COMPETENCIA" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "SUBCOMPETENCIA" => "Pasión",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Caja Tp",
        "COMPETENCIA" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "SUBCOMPETENCIA" => "Responsabilidad",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Caja Tp",
        "COMPETENCIA" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "SUBCOMPETENCIA" => "Resuelve Conflictos",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Caja Tp",
        "COMPETENCIA" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "SUBCOMPETENCIA" => "Traspase De Información En Equipo",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Codificador De Repuestos",
        "COMPETENCIA" => "Cooperación Operativa: Coopera de manera activa con las necesidades del equipo y unidades con las que interactúa. Respeta los acuerdos generando un espacio colaborativo y de confianza.",
        "SUBCOMPETENCIA" => "Cooperación",
        "DESCRIPCION" => "Muestra predisposición y apoyo para hacer trabajos operativos."
      ],
      [
        "CATEGORIA" => "Codificador De Repuestos",
        "COMPETENCIA" => "Cooperación Operativa: Coopera de manera activa con las necesidades del equipo y unidades con las que interactúa. Respeta los acuerdos generando un espacio colaborativo y de confianza.",
        "SUBCOMPETENCIA" => "Orientación a la meta común",
        "DESCRIPCION" => "Apoya a los demás para realizar un entregable de manera rápida."
      ],
      [
        "CATEGORIA" => "Codificador De Repuestos",
        "COMPETENCIA" => "Cooperación Operativa: Coopera de manera activa con las necesidades del equipo y unidades con las que interactúa. Respeta los acuerdos generando un espacio colaborativo y de confianza.",
        "SUBCOMPETENCIA" => "Relaciones interpersonales",
        "DESCRIPCION" => "Tiene buenas relaciones con los demás y genera confianza."
      ],
      [
        "CATEGORIA" => "Codificador De Repuestos",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Orientación al Detalle",
        "DESCRIPCION" => "Es minucioso en el manejo de datos."
      ],
      [
        "CATEGORIA" => "Codificador De Repuestos",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Organización",
        "DESCRIPCION" => "Mantiene un orden en los documentos que maneja."
      ],
      [
        "CATEGORIA" => "Codificador De Repuestos",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Cumplimiento de Procesos Operativos",
        "DESCRIPCION" => "Cumple con los procedimientos establecidos por la organización de manera correcta."
      ],
      [
        "CATEGORIA" => "Codificador De Repuestos",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Atención Efectiva a consultas",
        "DESCRIPCION" => "Responde oportunamente a las solicitudes y consultas de los clientes."
      ],
      [
        "CATEGORIA" => "Codificador De Repuestos",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Seguimiento a la atención",
        "DESCRIPCION" => "Vigilo la adecuada atención del cliente y pido al responsable a que se haga cargo."
      ],
      [
        "CATEGORIA" => "Codificador De Repuestos",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Atención presencial oportuna",
        "DESCRIPCION" => "Hago todo lo necesario para que el cliente sea bien atendido en el menor tiempo posible."
      ],
      [
        "CATEGORIA" => "Codificador De Repuestos",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Amabilidad y cercanía",
        "DESCRIPCION" => "Trata al cliente con amabilidad generando cercanía y confianza."
      ],
      [
        "CATEGORIA" => "Conductor De Tracto Camion",
        "COMPETENCIA" => "Calidad en la entrega del Producto: Velar por la calidad y recursos que utiliza en la entrega del producto al cliente a fin de generar una adecuada satisfacción.",
        "SUBCOMPETENCIA" => "Control de Calidad",
        "DESCRIPCION" => "Entrega el producto completo de acuerdo a la solicitud del cliente."
      ],
      [
        "CATEGORIA" => "Conductor De Tracto Camion",
        "COMPETENCIA" => "Calidad en la entrega del Producto: Velar por la calidad y recursos que utiliza en la entrega del producto al cliente a fin de generar una adecuada satisfacción.",
        "SUBCOMPETENCIA" => "Conocimiento de Procesos",
        "DESCRIPCION" => "Tiene claridad del proceso y los lineamientos que debe tener cada producto."
      ],
      [
        "CATEGORIA" => "Conductor De Tracto Camion",
        "COMPETENCIA" => "Calidad en la entrega del Producto: Velar por la calidad y recursos que utiliza en la entrega del producto al cliente a fin de generar una adecuada satisfacción.",
        "SUBCOMPETENCIA" => "Protección del producto",
        "DESCRIPCION" => "Protege cada parte del producto durante su traslado a fin de garantizar la calidad."
      ],
      [
        "CATEGORIA" => "Conductor De Tracto Camion",
        "COMPETENCIA" => "Calidad en la entrega del Producto: Velar por la calidad y recursos que utiliza en la entrega del producto al cliente a fin de generar una adecuada satisfacción.",
        "SUBCOMPETENCIA" => "Entrega del Producto",
        "DESCRIPCION" => "Realiza la descarga y apila el producto de manera eficiente."
      ],
      [
        "CATEGORIA" => "Conductor De Tracto Camion",
        "COMPETENCIA" => "Calidad en la entrega del Producto: Velar por la calidad y recursos que utiliza en la entrega del producto al cliente a fin de generar una adecuada satisfacción.",
        "SUBCOMPETENCIA" => "Protección del vehículo",
        "DESCRIPCION" => "Cuida el vehículo como si fuera propio: realiza el mantenimiento de manera adecuada y cuida cada parte del vehículo a fin de brindar una buena imagen al cliente."
      ],
      [
        "CATEGORIA" => "Conductor De Tracto Camion",
        "COMPETENCIA" => "Gestión de normas y procedimientos: Se preocupa y promueve respeto de las normas y procedimientos. Vigila el cumplimiento de las normas e interviene para corregir faltas.",
        "SUBCOMPETENCIA" => "Respeto a la Norma",
        "DESCRIPCION" => "Respeta con firmeza las normas y procedimientos."
      ],
      [
        "CATEGORIA" => "Conductor De Tracto Camion",
        "COMPETENCIA" => "Gestión de normas y procedimientos: Se preocupa y promueve respeto de las normas y procedimientos. Vigila el cumplimiento de las normas e interviene para corregir faltas.",
        "SUBCOMPETENCIA" => "Claridad en la orientación",
        "DESCRIPCION" => "Explica de manera clara las normas y reglamentos."
      ],
      [
        "CATEGORIA" => "Conductor De Tracto Camion",
        "COMPETENCIA" => "Gestión de normas y procedimientos: Se preocupa y promueve respeto de las normas y procedimientos. Vigila el cumplimiento de las normas e interviene para corregir faltas.",
        "SUBCOMPETENCIA" => "Revisión y Seguimiento",
        "DESCRIPCION" => "Verifica y realiza seguimiento constante al cumplimiento de las normas y reglamentos."
      ],
      [
        "CATEGORIA" => "Conductor De Tracto Camion",
        "COMPETENCIA" => "Gestión de normas y procedimientos: Se preocupa y promueve respeto de las normas y procedimientos. Vigila el cumplimiento de las normas e interviene para corregir faltas.",
        "SUBCOMPETENCIA" => "Gestión de Consecuencias",
        "DESCRIPCION" => "Logra corregir un comportamiento que no está acorde a las reglas y procedimientos."
      ],
      [
        "CATEGORIA" => "Conductor De Tracto Camion",
        "COMPETENCIA" => "Organización y Control de Mercadería: Garantizar la cantidad de recursos que necesita la organización a través de una adecuada supervisión, organización, clasificación y control.",
        "SUBCOMPETENCIA" => "Control de Recursos",
        "DESCRIPCION" => "Controla la cantidad de recursos necesarios para realizar las tareas."
      ],
      [
        "CATEGORIA" => "Conductor De Tracto Camion",
        "COMPETENCIA" => "Organización y Control de Mercadería: Garantizar la cantidad de recursos que necesita la organización a través de una adecuada supervisión, organización, clasificación y control.",
        "SUBCOMPETENCIA" => "Protección de Recursos",
        "DESCRIPCION" => "Cuida los recursos para mantener su calidad."
      ],
      [
        "CATEGORIA" => "Conductor De Tracto Camion",
        "COMPETENCIA" => "Organización y Control de Mercadería: Garantizar la cantidad de recursos que necesita la organización a través de una adecuada supervisión, organización, clasificación y control.",
        "SUBCOMPETENCIA" => "Organización de Recursos",
        "DESCRIPCION" => "Clasifica los recursos con un orden claro y práctico."
      ],
      [
        "CATEGORIA" => "Conductor De Tracto Camion",
        "COMPETENCIA" => "Registro y Manejo de Información - Versión 2 AP: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Orientación al Detalle",
        "DESCRIPCION" => "Es minucioso en el manejo de datos."
      ],
      [
        "CATEGORIA" => "Conductor De Tracto Camion",
        "COMPETENCIA" => "Registro y Manejo de Información - Versión 2 AP: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Organización",
        "DESCRIPCION" => "Mantiene un orden en los documentos que maneja."
      ],
      [
        "CATEGORIA" => "Conductor De Tracto Camion",
        "COMPETENCIA" => "Registro y Manejo de Información - Versión 2 AP: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Cumplimiento de Procesos Operativos",
        "DESCRIPCION" => "Cumple con los procedimientos establecidos por la organización de manera correcta."
      ],
      [
        "CATEGORIA" => "Conductor De Tracto Camion",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Claridad en el mensaje",
        "DESCRIPCION" => "Comunica sus ideas de forma clara y precisa."
      ],
      [
        "CATEGORIA" => "Conductor De Tracto Camion",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Respuesta Oportuna",
        "DESCRIPCION" => "Comparte información e ideas en el momento adecuado."
      ],
      [
        "CATEGORIA" => "Conductor De Tracto Camion",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Escucha",
        "DESCRIPCION" => "Escucha atentamente las opiniones y sugerencias."
      ],
      [
        "CATEGORIA" => "Conductor De Tracto Camion",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Asertividad",
        "DESCRIPCION" => "Expresa sus opiniones sin herir los sentimientos de los demás."
      ],
      [
        "CATEGORIA" => "Conductor I",
        "COMPETENCIA" => "Calidad en la entrega del Producto: Velar por la calidad y recursos que utiliza en la entrega del producto al cliente a fin de generar una adecuada satisfacción.",
        "SUBCOMPETENCIA" => "Control de Calidad",
        "DESCRIPCION" => "Entrega el producto completo de acuerdo a la solicitud del cliente."
      ],
      [
        "CATEGORIA" => "Conductor I",
        "COMPETENCIA" => "Calidad en la entrega del Producto: Velar por la calidad y recursos que utiliza en la entrega del producto al cliente a fin de generar una adecuada satisfacción.",
        "SUBCOMPETENCIA" => "Conocimiento de Procesos",
        "DESCRIPCION" => "Tiene claridad del proceso y los lineamientos que debe tener cada producto."
      ],
      [
        "CATEGORIA" => "Conductor I",
        "COMPETENCIA" => "Calidad en la entrega del Producto: Velar por la calidad y recursos que utiliza en la entrega del producto al cliente a fin de generar una adecuada satisfacción.",
        "SUBCOMPETENCIA" => "Protección del producto",
        "DESCRIPCION" => "Protege cada parte del producto durante su traslado a fin de garantizar la calidad."
      ],
      [
        "CATEGORIA" => "Conductor I",
        "COMPETENCIA" => "Calidad en la entrega del Producto: Velar por la calidad y recursos que utiliza en la entrega del producto al cliente a fin de generar una adecuada satisfacción.",
        "SUBCOMPETENCIA" => "Entrega del Producto",
        "DESCRIPCION" => "Realiza la descarga y apila el producto de manera eficiente."
      ],
      [
        "CATEGORIA" => "Conductor I",
        "COMPETENCIA" => "Calidad en la entrega del Producto: Velar por la calidad y recursos que utiliza en la entrega del producto al cliente a fin de generar una adecuada satisfacción.",
        "SUBCOMPETENCIA" => "Protección del vehículo",
        "DESCRIPCION" => "Cuida el vehículo como si fuera propio: realiza el mantenimiento de manera adecuada y cuida cada parte del vehículo a fin de brindar una buena imagen al cliente."
      ],
      [
        "CATEGORIA" => "Conductor I",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Claridad en el mensaje",
        "DESCRIPCION" => "Comunica sus ideas de forma clara y precisa."
      ],
      [
        "CATEGORIA" => "Conductor I",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Respuesta Oportuna",
        "DESCRIPCION" => "Comparte información e ideas en el momento adecuado."
      ],
      [
        "CATEGORIA" => "Conductor I",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Escucha",
        "DESCRIPCION" => "Escucha atentamente las opiniones y sugerencias."
      ],
      [
        "CATEGORIA" => "Conductor I",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Asertividad",
        "DESCRIPCION" => "Expresa sus opiniones sin herir los sentimientos de los demás."
      ],
      [
        "CATEGORIA" => "Conductor I",
        "COMPETENCIA" => "Cooperación Operativa: Coopera de manera activa con las necesidades del equipo y unidades con las que interactúa. Respeta los acuerdos generando un espacio colaborativo y de confianza.",
        "SUBCOMPETENCIA" => "Cooperación",
        "DESCRIPCION" => "Muestra predisposición y apoyo para hacer trabajos operativos."
      ],
      [
        "CATEGORIA" => "Conductor I",
        "COMPETENCIA" => "Cooperación Operativa: Coopera de manera activa con las necesidades del equipo y unidades con las que interactúa. Respeta los acuerdos generando un espacio colaborativo y de confianza.",
        "SUBCOMPETENCIA" => "Orientación a la meta común",
        "DESCRIPCION" => "Apoya a los demás para realizar un entregable de manera rápida."
      ],
      [
        "CATEGORIA" => "Conductor I",
        "COMPETENCIA" => "Cooperación Operativa: Coopera de manera activa con las necesidades del equipo y unidades con las que interactúa. Respeta los acuerdos generando un espacio colaborativo y de confianza.",
        "SUBCOMPETENCIA" => "Relaciones interpersonales",
        "DESCRIPCION" => "Tiene buenas relaciones con los demás y genera confianza."
      ],
      [
        "CATEGORIA" => "Conductor I",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Solicitudes del Cliente",
        "DESCRIPCION" => "Responde oportunamente a las solicitudes y consultas de los clientes vía presencial, telefónica y por correo electrónico."
      ],
      [
        "CATEGORIA" => "Conductor I",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Atención Efectiva",
        "DESCRIPCION" => "Vigila la adecuada atención del cliente y pide al responsable que se haga cargo."
      ],
      [
        "CATEGORIA" => "Conductor I",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Dueño del Cliente",
        "DESCRIPCION" => "Hace todo lo necesario para que el cliente sea bien atendido en el menor tiempo posible."
      ],
      [
        "CATEGORIA" => "Conductor I",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Amabilidad y cercanía",
        "DESCRIPCION" => "Trata al cliente con amabilidad generando cercanía y confianza."
      ],
      [
        "CATEGORIA" => "Conductor I",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",

        "SUBCOMPETENCIA" => "Proactividad",
        "DESCRIPCION" => "Se anticipa a los requerimientos o necesidades que se presentan."
      ],
      [
        "CATEGORIA" => "Conductor I",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",

        "SUBCOMPETENCIA" => "Solución de Problemas",
        "DESCRIPCION" => "Resuelve los problemas a pesar de las dificultades."
      ],
      [
        "CATEGORIA" => "Conductor I",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",

        "SUBCOMPETENCIA" => "Cumplimiento de Acuerdos",
        "DESCRIPCION" => "Cumple con los acuerdos de la organización."
      ],
      [
        "CATEGORIA" => "Conductor I",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",

        "SUBCOMPETENCIA" => "Resultados",
        "DESCRIPCION" => "Logra los resultados que se le asignan."
      ],
      [
        "CATEGORIA" => "Conductor I",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",

        "SUBCOMPETENCIA" => "Asumo las consecuencias",
        "DESCRIPCION" => "Se hace cargo de los errores que realiza y los resuelve. No espera a que otros lo solucionen"
      ],
      [
        "CATEGORIA" => "Conductor Ii",
        "COMPETENCIA" => "Calidad en la entrega del Producto: Velar por la calidad y recursos que utiliza en la entrega del producto al cliente a fin de generar una adecuada satisfacción.",
        "SUBCOMPETENCIA" => "Control de Calidad",
        "DESCRIPCION" => "Entrega el producto completo de acuerdo a la solicitud del cliente."
      ],
      [
        "CATEGORIA" => "Conductor Ii",
        "COMPETENCIA" => "Calidad en la entrega del Producto: Velar por la calidad y recursos que utiliza en la entrega del producto al cliente a fin de generar una adecuada satisfacción.",
        "SUBCOMPETENCIA" => "Conocimiento de Procesos",
        "DESCRIPCION" => "Tiene claridad del proceso y los lineamientos que debe tener cada producto."
      ],
      [
        "CATEGORIA" => "Conductor Ii",
        "COMPETENCIA" => "Calidad en la entrega del Producto: Velar por la calidad y recursos que utiliza en la entrega del producto al cliente a fin de generar una adecuada satisfacción.",
        "SUBCOMPETENCIA" => "Protección del producto",
        "DESCRIPCION" => "Protege cada parte del producto durante su traslado a fin de garantizar la calidad."
      ],
      [
        "CATEGORIA" => "Conductor Ii",
        "COMPETENCIA" => "Calidad en la entrega del Producto: Velar por la calidad y recursos que utiliza en la entrega del producto al cliente a fin de generar una adecuada satisfacción.",
        "SUBCOMPETENCIA" => "Entrega del Producto",
        "DESCRIPCION" => "Realiza la descarga y apila el producto de manera eficiente."
      ],
      [
        "CATEGORIA" => "Conductor Ii",
        "COMPETENCIA" => "Calidad en la entrega del Producto: Velar por la calidad y recursos que utiliza en la entrega del producto al cliente a fin de generar una adecuada satisfacción.",
        "SUBCOMPETENCIA" => "Protección del vehículo",
        "DESCRIPCION" => "Cuida el vehículo como si fuera propio: realiza el mantenimiento de manera adecuada y cuida cada parte del vehículo a fin de brindar una buena imagen al cliente."
      ],
      [
        "CATEGORIA" => "Conductor Ii",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Claridad en el mensaje",
        "DESCRIPCION" => "Comunica sus ideas de forma clara y precisa."
      ],
      [
        "CATEGORIA" => "Conductor Ii",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Respuesta Oportuna",
        "DESCRIPCION" => "Comparte información e ideas en el momento adecuado."
      ],
      [
        "CATEGORIA" => "Conductor Ii",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Escucha",
        "DESCRIPCION" => "Escucha atentamente las opiniones y sugerencias."
      ],
      [
        "CATEGORIA" => "Conductor Ii",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Asertividad",
        "DESCRIPCION" => "Expresa sus opiniones sin herir los sentimientos de los demás."
      ],
      [
        "CATEGORIA" => "Conductor Ii",
        "COMPETENCIA" => "Cooperación Operativa: Coopera de manera activa con las necesidades del equipo y unidades con las que interactúa. Respeta los acuerdos generando un espacio colaborativo y de confianza.",
        "SUBCOMPETENCIA" => "Cooperación",
        "DESCRIPCION" => "Muestra predisposición y apoyo para hacer trabajos operativos."
      ],
      [
        "CATEGORIA" => "Conductor Ii",
        "COMPETENCIA" => "Cooperación Operativa: Coopera de manera activa con las necesidades del equipo y unidades con las que interactúa. Respeta los acuerdos generando un espacio colaborativo y de confianza.",
        "SUBCOMPETENCIA" => "Orientación a la meta común",
        "DESCRIPCION" => "Apoya a los demás para realizar un entregable de manera rápida."
      ],
      [
        "CATEGORIA" => "Conductor Ii",
        "COMPETENCIA" => "Cooperación Operativa: Coopera de manera activa con las necesidades del equipo y unidades con las que interactúa. Respeta los acuerdos generando un espacio colaborativo y de confianza.",
        "SUBCOMPETENCIA" => "Relaciones interpersonales",
        "DESCRIPCION" => "Tiene buenas relaciones con los demás y genera confianza."
      ],
      [
        "CATEGORIA" => "Conductor Ii",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Solicitudes del Cliente",
        "DESCRIPCION" => "Responde oportunamente a las solicitudes y consultas de los clientes vía presencial, telefónica y por correo electrónico."
      ],
      [
        "CATEGORIA" => "Conductor Ii",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Atención Efectiva",
        "DESCRIPCION" => "Vigila la adecuada atención del cliente y pide al responsable que se haga cargo."
      ],
      [
        "CATEGORIA" => "Conductor Ii",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Dueño del Cliente",
        "DESCRIPCION" => "Hace todo lo necesario para que el cliente sea bien atendido en el menor tiempo posible."
      ],
      [
        "CATEGORIA" => "Conductor Ii",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Amabilidad y cercanía",
        "DESCRIPCION" => "Trata al cliente con amabilidad generando cercanía y confianza."
      ],
      [
        "CATEGORIA" => "Conductor Ii",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",

        "SUBCOMPETENCIA" => "Proactividad",
        "DESCRIPCION" => "Se anticipa a los requerimientos o necesidades que se presentan."
      ],
      [
        "CATEGORIA" => "Conductor Ii",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",

        "SUBCOMPETENCIA" => "Solución de Problemas",
        "DESCRIPCION" => "Resuelve los problemas a pesar de las dificultades."
      ],
      [
        "CATEGORIA" => "Conductor Ii",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",

        "SUBCOMPETENCIA" => "Cumplimiento de Acuerdos",
        "DESCRIPCION" => "Cumple con los acuerdos de la organización."
      ],
      [
        "CATEGORIA" => "Conductor Ii",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",

        "SUBCOMPETENCIA" => "Resultados",
        "DESCRIPCION" => "Logra los resultados que se le asignan."
      ],
      [
        "CATEGORIA" => "Conductor Ii",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",

        "SUBCOMPETENCIA" => "Asumo las consecuencias",
        "DESCRIPCION" => "Se hace cargo de los errores que realiza y los resuelve. No espera a que otros lo solucionen"
      ],
      [
        "CATEGORIA" => "Coordinador De Mantenimiento",
        "COMPETENCIA" => "Control del Mantenimiento: Soluciona los problemas de manera efectiva. Evalúa el rendimiento de mantenimiento e identifica oportunidades de reducción de costos.",
        "SUBCOMPETENCIA" => "Control De Costos",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Coordinador De Mantenimiento",
        "COMPETENCIA" => "Control del Mantenimiento: Soluciona los problemas de manera efectiva. Evalúa el rendimiento de mantenimiento e identifica oportunidades de reducción de costos.",
        "SUBCOMPETENCIA" => "Evaluación De Indicadores",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Coordinador De Mantenimiento",
        "COMPETENCIA" => "Control del Mantenimiento: Soluciona los problemas de manera efectiva. Evalúa el rendimiento de mantenimiento e identifica oportunidades de reducción de costos.",
        "SUBCOMPETENCIA" => "Solución De Problemas",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Coordinador De Mantenimiento",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Control",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Coordinador De Mantenimiento",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Gestión De Consecuencias",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Coordinador De Mantenimiento",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Retroalimentación",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Coordinador De Mantenimiento",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Seguimiento",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Coordinador De Mantenimiento",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Supervisión",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Coordinador De Mantenimiento",
        "COMPETENCIA" => "Gestión de normas y procedimientos: Se preocupa y promueve respeto de las normas y procedimientos. Vigila el cumplimiento de las normas e interviene para corregir faltas.",
        "SUBCOMPETENCIA" => "Claridad En La Orientación",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Coordinador De Mantenimiento",
        "COMPETENCIA" => "Gestión de normas y procedimientos: Se preocupa y promueve respeto de las normas y procedimientos. Vigila el cumplimiento de las normas e interviene para corregir faltas.",
        "SUBCOMPETENCIA" => "Gestión De Consecuencias V1",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Coordinador De Mantenimiento",
        "COMPETENCIA" => "Gestión de normas y procedimientos: Se preocupa y promueve respeto de las normas y procedimientos. Vigila el cumplimiento de las normas e interviene para corregir faltas.",
        "SUBCOMPETENCIA" => "Respeto A La Norma",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Coordinador De Mantenimiento",
        "COMPETENCIA" => "Gestión de normas y procedimientos: Se preocupa y promueve respeto de las normas y procedimientos. Vigila el cumplimiento de las normas e interviene para corregir faltas.",
        "SUBCOMPETENCIA" => "Revisión Y Seguimiento",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Coordinador De Mantenimiento",
        "COMPETENCIA" => "Supervisión Efectiva: Supervisar el desempeño de los colaboradores brindando retrolimentación a partir del mismo y gestionar los resultados obtenidos.",
        "SUBCOMPETENCIA" => "Gestión De Consecuencias Del Desempeño",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Coordinador De Mantenimiento",
        "COMPETENCIA" => "Supervisión Efectiva: Supervisar el desempeño de los colaboradores brindando retrolimentación a partir del mismo y gestionar los resultados obtenidos.",
        "SUBCOMPETENCIA" => "Retroalimentación Sobre El Proceso",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Coordinador De Mantenimiento",
        "COMPETENCIA" => "Supervisión Efectiva: Supervisar el desempeño de los colaboradores brindando retrolimentación a partir del mismo y gestionar los resultados obtenidos.",
        "SUBCOMPETENCIA" => "Seguimiento Efectivo",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Coordinador De Post Venta",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Claridad en el mensaje",
        "DESCRIPCION" => "Comunica sus ideas de forma clara y precisa."
      ],
      [
        "CATEGORIA" => "Coordinador De Post Venta",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Respuesta Oportuna",
        "DESCRIPCION" => "Comparte información e ideas en el momento adecuado."
      ],
      [
        "CATEGORIA" => "Coordinador De Post Venta",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Escucha",
        "DESCRIPCION" => "Escucha atentamente las opiniones y sugerencias."
      ],
      [
        "CATEGORIA" => "Coordinador De Post Venta",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Asertividad",
        "DESCRIPCION" => "Expresa sus opiniones sin herir los sentimientos de los demás."
      ],
      [
        "CATEGORIA" => "Coordinador De Post Venta",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Supervisión",
        "DESCRIPCION" => "Supervisa los avances, entregables y acciones del equipo para el lograr los objetivos."
      ],
      [
        "CATEGORIA" => "Coordinador De Post Venta",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Seguimiento",
        "DESCRIPCION" => "Realiza seguimiento a los avances de su equipo."
      ],
      [
        "CATEGORIA" => "Coordinador De Post Venta",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Retroalimentación",
        "DESCRIPCION" => "Brinda retroalimentación de manera oportuna sobre los resultados individuales"
      ],
      [
        "CATEGORIA" => "Coordinador De Post Venta",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Control",
        "DESCRIPCION" => "Controla los avances del equipo a fin de intervenir de manera oportuna."
      ],
      [
        "CATEGORIA" => "Coordinador De Post Venta",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Gestión de Consecuencias",
        "DESCRIPCION" => "Felicita o corrige las acciones de su equipo."
      ],
      [
        "CATEGORIA" => "Coordinador De Post Venta",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Orientación al Detalle",
        "DESCRIPCION" => "Es minucioso en el manejo de datos."
      ],
      [
        "CATEGORIA" => "Coordinador De Post Venta",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Organización",
        "DESCRIPCION" => "Mantiene un orden en los documentos que maneja."
      ],
      [
        "CATEGORIA" => "Coordinador De Post Venta",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Cumplimiento de Procesos Operativos",
        "DESCRIPCION" => "Cumple con los procedimientos establecidos por la organización de manera correcta."
      ],
      [
        "CATEGORIA" => "Coordinador De Post Venta",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Atención Efectiva a consultas",
        "DESCRIPCION" => "Responde oportunamente a las solicitudes y consultas de los clientes."
      ],
      [
        "CATEGORIA" => "Coordinador De Post Venta",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Seguimiento a la atención",
        "DESCRIPCION" => "Vigilo la adecuada atención del cliente y pido al responsable a que se haga cargo."
      ],
      [
        "CATEGORIA" => "Coordinador De Post Venta",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Atención presencial oportuna",
        "DESCRIPCION" => "Hago todo lo necesario para que el cliente sea bien atendido en el menor tiempo posible."
      ],
      [
        "CATEGORIA" => "Coordinador De Post Venta",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Amabilidad y cercanía",
        "DESCRIPCION" => "Trata al cliente con amabilidad generando cercanía y confianza."
      ],
      [
        "CATEGORIA" => "Coordinador De Post Venta - Almacen",
        "COMPETENCIA" => "Calidad de Servicio: Supervisa la calidad de los servicios y la satisfacción de los clientes. Interviene para que la calidad y satisfacción se mantengan dentro de lo esperado.",
        "SUBCOMPETENCIA" => "Supervisión del servicio",
        "DESCRIPCION" => "Supervisa que los servicios y lo estándares de calidad se mantegan en niveles óptimos."
      ],
      [
        "CATEGORIA" => "Coordinador De Post Venta - Almacen",
        "COMPETENCIA" => "Calidad de Servicio: Supervisa la calidad de los servicios y la satisfacción de los clientes. Interviene para que la calidad y satisfacción se mantengan dentro de lo esperado.",
        "SUBCOMPETENCIA" => "Coordinación efectiva",
        "DESCRIPCION" => "Supervisa que todas las áreas involucradas en un servicio estén coordinando de manera adecuada."
      ],
      [
        "CATEGORIA" => "Coordinador De Post Venta - Almacen",
        "COMPETENCIA" => "Calidad de Servicio: Supervisa la calidad de los servicios y la satisfacción de los clientes. Interviene para que la calidad y satisfacción se mantengan dentro de lo esperado.",
        "SUBCOMPETENCIA" => "Monitoreo de Satisfacción",
        "DESCRIPCION" => "Monitorea los indicadores de Satisfacción del cliente."
      ],
      [
        "CATEGORIA" => "Coordinador De Post Venta - Almacen",
        "COMPETENCIA" => "Calidad de Servicio: Supervisa la calidad de los servicios y la satisfacción de los clientes. Interviene para que la calidad y satisfacción se mantengan dentro de lo esperado.",
        "SUBCOMPETENCIA" => "Reporte de Calidad",
        "DESCRIPCION" => "Presenta informes técnicos sobre los servicios realizados."
      ],
      [
        "CATEGORIA" => "Coordinador De Post Venta - Almacen",
        "COMPETENCIA" => "Calidad de Servicio: Supervisa la calidad de los servicios y la satisfacción de los clientes. Interviene para que la calidad y satisfacción se mantengan dentro de lo esperado.",
        "SUBCOMPETENCIA" => "Gestión de Consecuencias",
        "DESCRIPCION" => "Interviene de manera oportuna a las acciones que no están alineadas a un servicio al cliente adecuado."
      ],
      [
        "CATEGORIA" => "Coordinador De Post Venta - Almacen",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Claridad en el mensaje",
        "DESCRIPCION" => "Comunica sus ideas de forma clara y precisa."
      ],
      [
        "CATEGORIA" => "Coordinador De Post Venta - Almacen",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Respuesta Oportuna",
        "DESCRIPCION" => "Comparte información e ideas en el momento adecuado."
      ],
      [
        "CATEGORIA" => "Coordinador De Post Venta - Almacen",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Escucha",
        "DESCRIPCION" => "Escucha atentamente las opiniones y sugerencias."
      ],
      [
        "CATEGORIA" => "Coordinador De Post Venta - Almacen",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Asertividad",
        "DESCRIPCION" => "Expresa sus opiniones sin herir los sentimientos de los demás."
      ],
      [
        "CATEGORIA" => "Coordinador De Post Venta - Almacen",
        "COMPETENCIA" => "Gestión de Proveedores - Admin: Controlar el cumplimiento de los acuerdos del proveedor, interviene ante los problemas y reclamos en la entrega de mercadería.",
        "SUBCOMPETENCIA" => "Identificación",
        "DESCRIPCION" => "Busca e identifica los mejores proveedores de acuerdo a los lineamientos de la organización."
      ],
      [
        "CATEGORIA" => "Coordinador De Post Venta - Almacen",
        "COMPETENCIA" => "Gestión de Proveedores - Admin: Controlar el cumplimiento de los acuerdos del proveedor, interviene ante los problemas y reclamos en la entrega de mercadería.",
        "SUBCOMPETENCIA" => "Seguimiento",
        "DESCRIPCION" => "Realiza el seguimiento oportuno a las acciones que tiene que realizar los proveedores."
      ],
      [
        "CATEGORIA" => "Coordinador De Post Venta - Almacen",
        "COMPETENCIA" => "Gestión de Proveedores - Admin: Controlar el cumplimiento de los acuerdos del proveedor, interviene ante los problemas y reclamos en la entrega de mercadería.",
        "SUBCOMPETENCIA" => "Manejo de Reclamos",
        "DESCRIPCION" => "Resuelve los reclamos con los proveedores que no entregan con la calidad definida."
      ],
      [
        "CATEGORIA" => "Coordinador De Post Venta - Almacen",
        "COMPETENCIA" => "Gestión de Proveedores - Admin: Controlar el cumplimiento de los acuerdos del proveedor, interviene ante los problemas y reclamos en la entrega de mercadería.",
        "SUBCOMPETENCIA" => "Solución de Problemas",
        "DESCRIPCION" => "Muestra una actitud colaborativa y propone soluciones ante las problemáticas que surgan con los proveedores."
      ],
      [
        "CATEGORIA" => "Coordinador De Post Venta - Almacen",
        "COMPETENCIA" => "Gestión Financiera - Post Venta: Realiza una gestión financiera relacionada a la post-venta para contar con información relevante que ayude a tomar decisiones de manera oportuna.",
        "SUBCOMPETENCIA" => "Actualización de Indicadores y Recursos",
        "DESCRIPCION" => "Mantiene actualizados los indicadores de gestión y recursos de post-venta (cotizaciones, garantías, etc.)"
      ],
      [
        "CATEGORIA" => "Coordinador De Post Venta - Almacen",
        "COMPETENCIA" => "Gestión Financiera - Post Venta: Realiza una gestión financiera relacionada a la post-venta para contar con información relevante que ayude a tomar decisiones de manera oportuna.",
        "SUBCOMPETENCIA" => "Reporte de Indicadores y Recursos",
        "DESCRIPCION" => "Presenta resportes del avance de los indicadores de gestión y recursos para tomar decisiones de manera oportuna."
      ],
      [
        "CATEGORIA" => "Coordinador De Post Venta - Almacen",
        "COMPETENCIA" => "Gestión Financiera - Post Venta: Realiza una gestión financiera relacionada a la post-venta para contar con información relevante que ayude a tomar decisiones de manera oportuna.",
        "SUBCOMPETENCIA" => "Estructuración de precios",
        "DESCRIPCION" => "Estructura los precios de respuestos y servicios de acuerdo al análisis de la cobertura que se quiere realizar."
      ],
      [
        "CATEGORIA" => "Coordinador De Post Venta - Almacen",
        "COMPETENCIA" => "Gestión Financiera - Post Venta: Realiza una gestión financiera relacionada a la post-venta para contar con información relevante que ayude a tomar decisiones de manera oportuna.",
        "SUBCOMPETENCIA" => "Segumineto a Cobranzas",
        "DESCRIPCION" => "Realiza un seguimiento de cobranzas oportuno."
      ],
      [
        "CATEGORIA" => "Coordinador De Post Venta - Almacen",
        "COMPETENCIA" => "Registro y Manejo de Información - Versión 2 AP: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Orientación al Detalle",
        "DESCRIPCION" => "Es minucioso en el manejo de datos."
      ],
      [
        "CATEGORIA" => "Coordinador De Post Venta - Almacen",
        "COMPETENCIA" => "Registro y Manejo de Información - Versión 2 AP: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Organización",
        "DESCRIPCION" => "Mantiene un orden en los documentos que maneja."
      ],
      [
        "CATEGORIA" => "Coordinador De Post Venta - Almacen",
        "COMPETENCIA" => "Registro y Manejo de Información - Versión 2 AP: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Cumplimiento de Procesos Operativos",
        "DESCRIPCION" => "Cumple con los procedimientos establecidos por la organización de manera correcta."
      ],
      [
        "CATEGORIA" => "Coordinador De Post Venta - Almacen",
        "COMPETENCIA" => "Registro y Manejo de Información - Versión 2 AP: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Gestión de Garantías - Almacenes",
        "DESCRIPCION" => "Analiza información de las garantías técnicas de fábrica, garantías de repuestos y accesorios."
      ],
      [
        "CATEGORIA" => "Coordinador De Taller",
        "COMPETENCIA" => "Calidad de Servicio: Supervisa la calidad de los servicios y la satisfacción de los clientes. Interviene para que la calidad y satisfacción se mantengan dentro de lo esperado.",
        "SUBCOMPETENCIA" => "Supervisión del servicio",
        "DESCRIPCION" => "Supervisa que los servicios y lo estándares de calidad se mantegan en niveles óptimos."
      ],
      [
        "CATEGORIA" => "Coordinador De Taller",
        "COMPETENCIA" => "Calidad de Servicio: Supervisa la calidad de los servicios y la satisfacción de los clientes. Interviene para que la calidad y satisfacción se mantengan dentro de lo esperado.",
        "SUBCOMPETENCIA" => "Coordinación efectiva",
        "DESCRIPCION" => "Supervisa que todas las áreas involucradas en un servicio estén coordinando de manera adecuada."
      ],
      [
        "CATEGORIA" => "Coordinador De Taller",
        "COMPETENCIA" => "Calidad de Servicio: Supervisa la calidad de los servicios y la satisfacción de los clientes. Interviene para que la calidad y satisfacción se mantengan dentro de lo esperado.",
        "SUBCOMPETENCIA" => "Monitoreo de Satisfacción",
        "DESCRIPCION" => "Monitorea los indicadores de Satisfacción del cliente."
      ],
      [
        "CATEGORIA" => "Coordinador De Taller",
        "COMPETENCIA" => "Calidad de Servicio: Supervisa la calidad de los servicios y la satisfacción de los clientes. Interviene para que la calidad y satisfacción se mantengan dentro de lo esperado.",
        "SUBCOMPETENCIA" => "Reporte de Calidad",
        "DESCRIPCION" => "Presenta informes técnicos sobre los servicios realizados."
      ],
      [
        "CATEGORIA" => "Coordinador De Taller",
        "COMPETENCIA" => "Calidad de Servicio: Supervisa la calidad de los servicios y la satisfacción de los clientes. Interviene para que la calidad y satisfacción se mantengan dentro de lo esperado.",
        "SUBCOMPETENCIA" => "Gestión de Consecuencias",
        "DESCRIPCION" => "Interviene de manera oportuna a las acciones que no están alineadas a un servicio al cliente adecuado."
      ],
      [
        "CATEGORIA" => "Coordinador De Taller",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Dirección o Supervisión",
        "DESCRIPCION" => "Supervisa los avances, entregables y acciones del equipo para el lograr los objetivos."
      ],
      [
        "CATEGORIA" => "Coordinador De Taller",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Seguimiento",
        "DESCRIPCION" => "Realiza seguimiento a los avances de su equipo."
      ],
      [
        "CATEGORIA" => "Coordinador De Taller",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Retroalimentación",
        "DESCRIPCION" => "Brinda retroalimentación de manera oportuna sobre los resultados individuales"
      ],
      [
        "CATEGORIA" => "Coordinador De Taller",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Control",
        "DESCRIPCION" => "Controla los avances del equipo a fin de intervenir de manera oportuna."
      ],
      [
        "CATEGORIA" => "Coordinador De Taller",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Gestión de Consecuencias",
        "DESCRIPCION" => "Felicita o corrige las acciones de su equipo."
      ],
      [
        "CATEGORIA" => "Coordinador De Taller",
        "COMPETENCIA" => "Habilidad de Enseñanza: Logra explicar con claridad y paciencia el funcionamiento de la operación, brinda retroalmentación clara para incorporar los procedimientos e instrucciones.",
        "SUBCOMPETENCIA" => "Tolerancia",
        "DESCRIPCION" => "Tiene paciencia para explicar las instrucciones y lineamientos."
      ],
      [
        "CATEGORIA" => "Coordinador De Taller",
        "COMPETENCIA" => "Habilidad de Enseñanza: Logra explicar con claridad y paciencia el funcionamiento de la operación, brinda retroalmentación clara para incorporar los procedimientos e instrucciones.",
        "SUBCOMPETENCIA" => "Capacidad para explicar",
        "DESCRIPCION" => "Enseña con claridad a los demás sobre el funcionamiento de la operación."
      ],
      [
        "CATEGORIA" => "Coordinador De Taller",
        "COMPETENCIA" => "Habilidad de Enseñanza: Logra explicar con claridad y paciencia el funcionamiento de la operación, brinda retroalmentación clara para incorporar los procedimientos e instrucciones.",
        "SUBCOMPETENCIA" => "Evaluación",
        "DESCRIPCION" => "Evalúa desempeño o habilidades de acuerdo a criterios establecidos."
      ],
      [
        "CATEGORIA" => "Coordinador De Taller",
        "COMPETENCIA" => "Habilidad de Enseñanza: Logra explicar con claridad y paciencia el funcionamiento de la operación, brinda retroalmentación clara para incorporar los procedimientos e instrucciones.",
        "SUBCOMPETENCIA" => "Retroalimentación",
        "DESCRIPCION" => "Brinda retroalimentación clara y objetiva sobre el desempeño del otro."
      ],
      [
        "CATEGORIA" => "Coordinador De Taller",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Orientación al Detalle",
        "DESCRIPCION" => "Es minucioso en el manejo de datos."
      ],
      [
        "CATEGORIA" => "Coordinador De Taller",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Organización",
        "DESCRIPCION" => "Mantiene un orden en los documentos que maneja."
      ],
      [
        "CATEGORIA" => "Coordinador De Taller",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Cumplimiento de Procesos Operativos",
        "DESCRIPCION" => "Cumple con los procedimientos establecidos por la organización de manera correcta."
      ],
      [
        "CATEGORIA" => "Coordinador De Taller",
        "COMPETENCIA" => "Gestión de Mercadería, Equipos y Herramientas",
        "SUBCOMPETENCIA" => "Control de Movimientos",
        "DESCRIPCION" => "Controla el ingreso y salida de la mercadería de manera adecuada."
      ],
      [
        "CATEGORIA" => "Coordinador De Taller",
        "COMPETENCIA" => "Gestión de Mercadería, Equipos y Herramientas",
        "SUBCOMPETENCIA" => "Protección de Recursos",
        "DESCRIPCION" => "Verifica con detalle si la mercadería llega averiada o incompleta."
      ],
      [
        "CATEGORIA" => "Coordinador De Taller",
        "COMPETENCIA" => "Gestión de Mercadería, Equipos y Herramientas",
        "SUBCOMPETENCIA" => "Organización de Recursos",
        "DESCRIPCION" => "Clasifica los recursos con un orden claro y práctico."
      ],
      [
        "CATEGORIA" => "Coordinador De Taller",
        "COMPETENCIA" => "Gestión de Mercadería, Equipos y Herramientas",
        "SUBCOMPETENCIA" => "Reclamos de ingreso de Recursos",
        "DESCRIPCION" => "Realiza de manera oportuna los reclamos ante mercaderías averiadas o faltantes."
      ],
      [
        "CATEGORIA" => "Coordinador De Ventas",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Claridad en el mensaje",
        "DESCRIPCION" => "Comunica sus ideas de forma clara y precisa."
      ],
      [
        "CATEGORIA" => "Coordinador De Ventas",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Respuesta Oportuna",
        "DESCRIPCION" => "Comparte información e ideas en el momento adecuado."
      ],
      [
        "CATEGORIA" => "Coordinador De Ventas",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Escucha",
        "DESCRIPCION" => "Escucha atentamente las opiniones y sugerencias."
      ],
      [
        "CATEGORIA" => "Coordinador De Ventas",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Asertividad",
        "DESCRIPCION" => "Expresa sus opiniones sin herir los sentimientos de los demás."
      ],
      [
        "CATEGORIA" => "Coordinador De Ventas",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Orientación al Detalle",
        "DESCRIPCION" => "Es minucioso en el manejo de datos."
      ],
      [
        "CATEGORIA" => "Coordinador De Ventas",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Organización",
        "DESCRIPCION" => "Mantiene un orden en los documentos que maneja."
      ],
      [
        "CATEGORIA" => "Coordinador De Ventas",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Cumplimiento de Procesos Operativos",
        "DESCRIPCION" => "Cumple con los procedimientos establecidos por la organización de manera correcta."
      ],
      [
        "CATEGORIA" => "Coordinador De Ventas",
        "COMPETENCIA" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "SUBCOMPETENCIA" => "Traspase de información",
        "DESCRIPCION" => "Verifica que la información que le brinda al otro llegue de manera efectiva y completa."
      ],
      [
        "CATEGORIA" => "Coordinador De Ventas",
        "COMPETENCIA" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "SUBCOMPETENCIA" => "Colaboración",
        "DESCRIPCION" => "Colabora con los demás para cumplir con el objetivo en común."
      ],
      [
        "CATEGORIA" => "Coordinador De Ventas",
        "COMPETENCIA" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "SUBCOMPETENCIA" => "Responsabilidad",
        "DESCRIPCION" => "Asume la responsabilidad que le corresponde, no espera que sólo los demás trabajen."
      ],
      [
        "CATEGORIA" => "Coordinador De Ventas",
        "COMPETENCIA" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "SUBCOMPETENCIA" => "Resuelve conflictos",
        "DESCRIPCION" => "Resuelve de manera oportuna las dificultades que pueda tener con sus compañeros"
      ],
      [
        "CATEGORIA" => "Coordinador De Ventas",
        "COMPETENCIA" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "SUBCOMPETENCIA" => "Pasión",
        "DESCRIPCION" => "Pone la mejor energía para hacer sus tareas y jugar en equipo."
      ],
      [
        "CATEGORIA" => "Coordinador De Ventas",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Gestión de Recursos",
        "DESCRIPCION" => "Identifica y asigna los recursos de manera responsable."
      ],
      [
        "CATEGORIA" => "Coordinador De Ventas",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Metodología",
        "DESCRIPCION" => "Utiliza metodologías o procesos estandarizados que garantizan los resultados esperados."
      ],
      [
        "CATEGORIA" => "Coordinador De Ventas",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Orientación al logro",
        "DESCRIPCION" => "Logra los resultados esperados en los tiempos establecidos."
      ],
      [
        "CATEGORIA" => "Diseñador Grafico",
        "COMPETENCIA" => "Calidad de Servicio: Supervisa la calidad de los servicios y la satisfacción de los clientes. Interviene para que la calidad y satisfacción se mantengan dentro de lo esperado.",
        "SUBCOMPETENCIA" => "Coordinación Efectiva",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Diseñador Grafico",
        "COMPETENCIA" => "Calidad de Servicio: Supervisa la calidad de los servicios y la satisfacción de los clientes. Interviene para que la calidad y satisfacción se mantengan dentro de lo esperado.",
        "SUBCOMPETENCIA" => "Gestión De Consecuencias Del Servicio",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Diseñador Grafico",
        "COMPETENCIA" => "Calidad de Servicio: Supervisa la calidad de los servicios y la satisfacción de los clientes. Interviene para que la calidad y satisfacción se mantengan dentro de lo esperado.",
        "SUBCOMPETENCIA" => "Monitoreo De Satisfacción",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Diseñador Grafico",
        "COMPETENCIA" => "Calidad de Servicio: Supervisa la calidad de los servicios y la satisfacción de los clientes. Interviene para que la calidad y satisfacción se mantengan dentro de lo esperado.",
        "SUBCOMPETENCIA" => "Reporte De Calidad",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Diseñador Grafico",
        "COMPETENCIA" => "Calidad de Servicio: Supervisa la calidad de los servicios y la satisfacción de los clientes. Interviene para que la calidad y satisfacción se mantengan dentro de lo esperado.",
        "SUBCOMPETENCIA" => "Supervisión Del Servicio",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Diseñador Grafico",
        "COMPETENCIA" => "Ejecucion de Piezas Comunicacionales: Define, coordina y ejecuta el desarrollo de piezas comunicacionales de acuerdo a los planes, campañas y proyectos a realizar.",
        "SUBCOMPETENCIA" => "Ejecucion Diseño Creativo",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Diseñador Grafico",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Control",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Diseñador Grafico",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Gestión De Consecuencias",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Diseñador Grafico",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Retroalimentación",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Diseñador Grafico",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Seguimiento",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Diseñador Grafico",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Supervisión",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Diseñador Grafico",
        "COMPETENCIA" => "Supervisión de Piezas Comunicacionales: Define y coordinar el desarrollo de piezas comunicacionales de acuerdo a los planes, campañas y eventos a realizar.",
        "SUBCOMPETENCIA" => "Claridad En La Pieza",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Diseñador Grafico",
        "COMPETENCIA" => "Supervisión de Piezas Comunicacionales: Define y coordinar el desarrollo de piezas comunicacionales de acuerdo a los planes, campañas y eventos a realizar.",
        "SUBCOMPETENCIA" => "Definir Pieza Comunicacional",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Diseñador Grafico",
        "COMPETENCIA" => "Supervisión de Piezas Comunicacionales: Define y coordinar el desarrollo de piezas comunicacionales de acuerdo a los planes, campañas y eventos a realizar.",
        "SUBCOMPETENCIA" => "Identidad Visual De La Marca",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Diseñador Grafico",
        "COMPETENCIA" => "Supervisión de Piezas Comunicacionales: Define y coordinar el desarrollo de piezas comunicacionales de acuerdo a los planes, campañas y eventos a realizar.",
        "SUBCOMPETENCIA" => "Supervisión Diseño Creativo",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Ejecutivo Comercial",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Claridad en el mensaje",
        "DESCRIPCION" => "Comunica sus ideas de forma clara y precisa."
      ],
      [
        "CATEGORIA" => "Ejecutivo Comercial",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Respuesta Oportuna",
        "DESCRIPCION" => "Comparte información e ideas en el momento adecuado."
      ],
      [
        "CATEGORIA" => "Ejecutivo Comercial",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Escucha",
        "DESCRIPCION" => "Escucha atentamente las opiniones y sugerencias."
      ],
      [
        "CATEGORIA" => "Ejecutivo Comercial",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Asertividad",
        "DESCRIPCION" => "Expresa sus opiniones sin herir los sentimientos de los demás."
      ],
      [
        "CATEGORIA" => "Ejecutivo Comercial",
        "COMPETENCIA" => "Gestión de Clientes Relevantes: Fideliza clientes relevantes del negocio y resuelve sus problemas. Cuenta con un profundo conocimiento del cliente.",
        "SUBCOMPETENCIA" => "Conocimiento del Cliente",
        "DESCRIPCION" => "Tiene un profundo conocimiento del negocio del cliente y sus productos."
      ],
      [
        "CATEGORIA" => "Ejecutivo Comercial",
        "COMPETENCIA" => "Gestión de Clientes Relevantes: Fideliza clientes relevantes del negocio y resuelve sus problemas. Cuenta con un profundo conocimiento del cliente.",
        "SUBCOMPETENCIA" => "Resolución de Problemas",
        "DESCRIPCION" => "Resuelve los problemas tantos simples como complejos que tiene el cliente."
      ],
      [
        "CATEGORIA" => "Ejecutivo Comercial",
        "COMPETENCIA" => "Gestión de Clientes Relevantes: Fideliza clientes relevantes del negocio y resuelve sus problemas. Cuenta con un profundo conocimiento del cliente.",
        "SUBCOMPETENCIA" => "Fidelización con el Cliente",
        "DESCRIPCION" => "Establece acciones que generan valor al cliente o se anticipa a los problemas que puede tener el mismo."
      ],
      [
        "CATEGORIA" => "Ejecutivo Comercial",
        "COMPETENCIA" => "Gestión de Clientes Relevantes: Fideliza clientes relevantes del negocio y resuelve sus problemas. Cuenta con un profundo conocimiento del cliente.",
        "SUBCOMPETENCIA" => "Relación con el Cliente",
        "DESCRIPCION" => "Realiza acciones para mantener una relación cercana y de confianza con el cliente."
      ],
      [
        "CATEGORIA" => "Ejecutivo Comercial",
        "COMPETENCIA" => "Habilidad Comercial: Logra persuadir al cliente a través de argumentos poderosos, realiza seguimiento oportuno manteniéndose cerca del cliente y aprovechando oportunidades.",
        "SUBCOMPETENCIA" => "Disciplina Comercial",
        "DESCRIPCION" => "Cumple de manera rigurosa con la metodología comercial (visita de clientes, puntualidad, orden en las visitas, manejo de objeciones del cliente y cobranza efectiva) sin bajar la guardia."
      ],
      [
        "CATEGORIA" => "Ejecutivo Comercial",
        "COMPETENCIA" => "Habilidad Comercial: Logra persuadir al cliente a través de argumentos poderosos, realiza seguimiento oportuno manteniéndose cerca del cliente y aprovechando oportunidades.",
        "SUBCOMPETENCIA" => "Búsqueda de Oportunidades",
        "DESCRIPCION" => "Persevera en la búsqueda de nuevos clientes, realiza visitas periódicas y llamadas a nuevos clientes."
      ],
      [
        "CATEGORIA" => "Ejecutivo Comercial",
        "COMPETENCIA" => "Habilidad Comercial: Logra persuadir al cliente a través de argumentos poderosos, realiza seguimiento oportuno manteniéndose cerca del cliente y aprovechando oportunidades.",
        "SUBCOMPETENCIA" => "Negociación",
        "DESCRIPCION" => "Negocia considerando los beneficios del cliente y los beneficios de la empresa."
      ],
      [
        "CATEGORIA" => "Ejecutivo Comercial",
        "COMPETENCIA" => "Habilidad Comercial: Logra persuadir al cliente a través de argumentos poderosos, realiza seguimiento oportuno manteniéndose cerca del cliente y aprovechando oportunidades.",
        "SUBCOMPETENCIA" => "Seguimiento",
        "DESCRIPCION" => "Realiza seguimiento al cliente sobre su decisión, se mantiene cerca y realiza un seguimiento de cobranza efectiva."
      ],
      [
        "CATEGORIA" => "Ejecutivo Comercial",
        "COMPETENCIA" => "Habilidad Comercial: Logra persuadir al cliente a través de argumentos poderosos, realiza seguimiento oportuno manteniéndose cerca del cliente y aprovechando oportunidades.",
        "SUBCOMPETENCIA" => "Manejo de Objeciones",
        "DESCRIPCION" => "Cuando el cliente se opone o tiene una idea negativa, le responde con seguridad utilizando las fortalezas, ventajas y beneficios de la organización."
      ],
      [
        "CATEGORIA" => "Ejecutivo Comercial",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Atención Efectiva a consultas",
        "DESCRIPCION" => "Responde oportunamente a las solicitudes y consultas de los clientes vía presencial, telefónica y por correo electrónico."
      ],
      [
        "CATEGORIA" => "Ejecutivo Comercial",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Seguimiento a la atención",
        "DESCRIPCION" => "Vigila la adecuada atención del cliente y pide al responsable que se haga cargo."
      ],
      [
        "CATEGORIA" => "Ejecutivo Comercial",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Atención presencial oportuna",
        "DESCRIPCION" => "Hace todo lo necesario para que el cliente sea bien atendido en el menor tiempo posible."
      ],
      [
        "CATEGORIA" => "Ejecutivo Comercial",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Amabilidad y cercanía",
        "DESCRIPCION" => "Trata al cliente con amabilidad generando cercanía y confianza."
      ],
      [
        "CATEGORIA" => "Ejecutivo Comercial",
        "COMPETENCIA" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "SUBCOMPETENCIA" => "Traspase de información",
        "DESCRIPCION" => "Verifica que la información que le brinda al otro llegue de manera efectiva y completa."
      ],
      [
        "CATEGORIA" => "Ejecutivo Comercial",
        "COMPETENCIA" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "SUBCOMPETENCIA" => "Colaboración",
        "DESCRIPCION" => "Colabora con los demás para cumplir con el objetivo en común."
      ],
      [
        "CATEGORIA" => "Ejecutivo Comercial",
        "COMPETENCIA" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "SUBCOMPETENCIA" => "Responsabilidad",
        "DESCRIPCION" => "Asume la responsabilidad que le corresponde, no espera que sólo los demás trabajen."
      ],
      [
        "CATEGORIA" => "Ejecutivo Comercial",
        "COMPETENCIA" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "SUBCOMPETENCIA" => "Resuelve conflictos",
        "DESCRIPCION" => "Resuelve de manera oportuna las dificultades que pueda tener con sus compañeros"
      ],
      [
        "CATEGORIA" => "Ejecutivo Comercial",
        "COMPETENCIA" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "SUBCOMPETENCIA" => "Pasión",
        "DESCRIPCION" => "Pone la mejor energía para hacer sus tareas y jugar en equipo."
      ],
      [
        "CATEGORIA" => "Ejecutivo De Trade Marketing",
        "COMPETENCIA" => "Gestión de Campañas y Eventos de Marketing: Define, desarrolla e implementa Campañas y Eventos de acuerdo al plan de marketing e imagen corporativa.",
        "SUBCOMPETENCIA" => "Creación De Campañas Y Eventos",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Ejecutivo De Trade Marketing",
        "COMPETENCIA" => "Gestión de Campañas y Eventos de Marketing: Define, desarrolla e implementa Campañas y Eventos de acuerdo al plan de marketing e imagen corporativa.",
        "SUBCOMPETENCIA" => "Definición De Campañas Y Eventos",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Ejecutivo De Trade Marketing",
        "COMPETENCIA" => "Gestión de Campañas y Eventos de Marketing: Define, desarrolla e implementa Campañas y Eventos de acuerdo al plan de marketing e imagen corporativa.",
        "SUBCOMPETENCIA" => "Evaluación De Campañas Y Eventos",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Ejecutivo De Trade Marketing",
        "COMPETENCIA" => "Gestión de Campañas y Eventos de Marketing: Define, desarrolla e implementa Campañas y Eventos de acuerdo al plan de marketing e imagen corporativa.",
        "SUBCOMPETENCIA" => "Implementación De Campañas Y Eventos",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Ejecutivo De Trade Marketing",
        "COMPETENCIA" => "Gestión de Proveedores - Admin: Controlar el cumplimiento de los acuerdos del proveedor, interviene ante los problemas y reclamos en la entrega de mercadería.",
        "SUBCOMPETENCIA" => "Identificación",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Ejecutivo De Trade Marketing",
        "COMPETENCIA" => "Gestión de Proveedores - Admin: Controlar el cumplimiento de los acuerdos del proveedor, interviene ante los problemas y reclamos en la entrega de mercadería.",
        "SUBCOMPETENCIA" => "Manejo De Reclamos",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Ejecutivo De Trade Marketing",
        "COMPETENCIA" => "Gestión de Proveedores - Admin: Controlar el cumplimiento de los acuerdos del proveedor, interviene ante los problemas y reclamos en la entrega de mercadería.",
        "SUBCOMPETENCIA" => "Seguimiento A Proveedores",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Ejecutivo De Trade Marketing",
        "COMPETENCIA" => "Gestión de Proveedores - Admin: Controlar el cumplimiento de los acuerdos del proveedor, interviene ante los problemas y reclamos en la entrega de mercadería.",
        "SUBCOMPETENCIA" => "Solución De Problemas Con Proveedores",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Ejecutivo De Trade Marketing",
        "COMPETENCIA" => "Supervisión de Piezas Comunicacionales: Define y coordinar el desarrollo de piezas comunicacionales de acuerdo a los planes, campañas y eventos a realizar.",
        "SUBCOMPETENCIA" => "Claridad En La Pieza",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Ejecutivo De Trade Marketing",
        "COMPETENCIA" => "Supervisión de Piezas Comunicacionales: Define y coordinar el desarrollo de piezas comunicacionales de acuerdo a los planes, campañas y eventos a realizar.",
        "SUBCOMPETENCIA" => "Definir Pieza Comunicacional",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Ejecutivo De Trade Marketing",
        "COMPETENCIA" => "Supervisión de Piezas Comunicacionales: Define y coordinar el desarrollo de piezas comunicacionales de acuerdo a los planes, campañas y eventos a realizar.",
        "SUBCOMPETENCIA" => "Identidad Visual De La Marca",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Ejecutivo De Trade Marketing",
        "COMPETENCIA" => "Supervisión de Piezas Comunicacionales: Define y coordinar el desarrollo de piezas comunicacionales de acuerdo a los planes, campañas y eventos a realizar.",
        "SUBCOMPETENCIA" => "Supervisión Diseño Creativo",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Ejecutivo De Trade Marketing",
        "COMPETENCIA" => "Supervisión de Producción de Eventos: Asegura que la ejecución del evento sea óptima y eficiente controlando todas las variables y asgurando la correcta ejecución.",
        "SUBCOMPETENCIA" => "Calidad De Producción",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Ejecutivo De Trade Marketing",
        "COMPETENCIA" => "Supervisión de Producción de Eventos: Asegura que la ejecución del evento sea óptima y eficiente controlando todas las variables y asgurando la correcta ejecución.",
        "SUBCOMPETENCIA" => "Control De Presupuesto",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Ejecutivo De Trade Marketing",
        "COMPETENCIA" => "Supervisión de Producción de Eventos: Asegura que la ejecución del evento sea óptima y eficiente controlando todas las variables y asgurando la correcta ejecución.",
        "SUBCOMPETENCIA" => "Solución De Problemas En Eventos",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Ejecutivo De Trade Marketing",
        "COMPETENCIA" => "Supervisión de Producción de Eventos: Asegura que la ejecución del evento sea óptima y eficiente controlando todas las variables y asgurando la correcta ejecución.",
        "SUBCOMPETENCIA" => "Supervisión De Despliegue",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Gerente Comercial Ap",
        "COMPETENCIA" => "Gestión Comercial: Define las estrategias comerciales, objetivos y metas de acuerdo al conocimiento del negocio, producto y el funcionamiento de la competencia.",
        "SUBCOMPETENCIA" => "Conocimiento del Negocio",
        "DESCRIPCION" => "Conoce a profundidad las características del negocio y de los productos."
      ],
      [
        "CATEGORIA" => "Gerente Comercial Ap",
        "COMPETENCIA" => "Gestión Comercial: Define las estrategias comerciales, objetivos y metas de acuerdo al conocimiento del negocio, producto y el funcionamiento de la competencia.",
        "SUBCOMPETENCIA" => "Conocimiento de la Competencia",
        "DESCRIPCION" => "Se mantiene actualizado sobre las estrategias y novedades de la competencia."
      ],
      [
        "CATEGORIA" => "Gerente Comercial Ap",
        "COMPETENCIA" => "Gestión Comercial: Define las estrategias comerciales, objetivos y metas de acuerdo al conocimiento del negocio, producto y el funcionamiento de la competencia.",
        "SUBCOMPETENCIA" => "Reajustes Comerciales",
        "DESCRIPCION" => "Identifica nuevas alternativas de acuerdo al análisis de la competencia y necesidades de los clientes."
      ],
      [
        "CATEGORIA" => "Gerente Comercial Ap",
        "COMPETENCIA" => "Gestión Comercial: Define las estrategias comerciales, objetivos y metas de acuerdo al conocimiento del negocio, producto y el funcionamiento de la competencia.",
        "SUBCOMPETENCIA" => "Definición Estrategias, Objetivos y metas",
        "DESCRIPCION" => "Define de manera adecuada las estrategias comerciales, objetivos y metas comerciales."
      ],
      [
        "CATEGORIA" => "Gerente Comercial Ap",
        "COMPETENCIA" => "Gestión de Marketing: Define las estrategias de marketing, analiza el funcionamiento del mercado para definir las acciones adecuadas.",
        "SUBCOMPETENCIA" => "Análisis de mercado",
        "DESCRIPCION" => "Define las acciones necesarias para comprender el funcionamiento del mercado."
      ],
      [
        "CATEGORIA" => "Gerente Comercial Ap",
        "COMPETENCIA" => "Gestión de Marketing: Define las estrategias de marketing, analiza el funcionamiento del mercado para definir las acciones adecuadas.",
        "SUBCOMPETENCIA" => "Evaluación de la Demanda",
        "DESCRIPCION" => "Evalúa la demanda de manera efectiva e identifica nuevas oportunidades en el mercado."
      ],
      [
        "CATEGORIA" => "Gerente Comercial Ap",
        "COMPETENCIA" => "Gestión de Marketing: Define las estrategias de marketing, analiza el funcionamiento del mercado para definir las acciones adecuadas.",
        "SUBCOMPETENCIA" => "Definición del precio de venta",
        "DESCRIPCION" => "Define de manera adecuada el precio de venta del producto."
      ],
      [
        "CATEGORIA" => "Gerente Comercial Ap",
        "COMPETENCIA" => "Gestión de Marketing: Define las estrategias de marketing, analiza el funcionamiento del mercado para definir las acciones adecuadas.",
        "SUBCOMPETENCIA" => "Definición de Estrategias y Objetivos",
        "DESCRIPCION" => "Define de manera adecuada la estrategia de Marketing y sus objetivos."
      ],
      [
        "CATEGORIA" => "Gerente Comercial Ap",
        "COMPETENCIA" => "Gestión de Presupuesto: Define el presupuesto de inversión adecuado considerando los costos y la rentabilidad del negocio.",
        "SUBCOMPETENCIA" => "Evaluación de estructura de Costos",
        "DESCRIPCION" => "Define de manera adecuada los costos asociados con los vehículos."
      ],
      [
        "CATEGORIA" => "Gerente Comercial Ap",
        "COMPETENCIA" => "Gestión de Presupuesto: Define el presupuesto de inversión adecuado considerando los costos y la rentabilidad del negocio.",
        "SUBCOMPETENCIA" => "Definición de Presupuesto",
        "DESCRIPCION" => "Define de manera adecuada el presupuesto de inversión"
      ],
      [
        "CATEGORIA" => "Gerente Comercial Ap",
        "COMPETENCIA" => "Gestión de Presupuesto: Define el presupuesto de inversión adecuado considerando los costos y la rentabilidad del negocio.",
        "SUBCOMPETENCIA" => "Control y Monitoreo",
        "DESCRIPCION" => "Controla y monitorea el presupuesto asignado y el cumplimiento de la proyección de venta."
      ],
      [
        "CATEGORIA" => "Gerente Comercial Ap",
        "COMPETENCIA" => "Gestión de Presupuesto: Define el presupuesto de inversión adecuado considerando los costos y la rentabilidad del negocio.",
        "SUBCOMPETENCIA" => "Ajustes",
        "DESCRIPCION" => "Realiza los cambios y ajustes necesarios para proteger la rentabilidad."
      ],
      [
        "CATEGORIA" => "Gerente Comercial Ap",
        "COMPETENCIA" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "SUBCOMPETENCIA" => "Dirección de Equipos",
        "DESCRIPCION" => "Dirige al equipo al logro de resultados de manera efectiva."
      ],
      [
        "CATEGORIA" => "Gerente Comercial Ap",
        "COMPETENCIA" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "SUBCOMPETENCIA" => "Resolución de Conflictos",
        "DESCRIPCION" => "Destraba o resuelve los conflictos internos que impactan en el desempeño del negocio."
      ],
      [
        "CATEGORIA" => "Gerente Comercial Ap",
        "COMPETENCIA" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "SUBCOMPETENCIA" => "Convicción",
        "DESCRIPCION" => "Transmite credibilidad y convicción a los demás."
      ],
      [
        "CATEGORIA" => "Gerente Comercial Ap",
        "COMPETENCIA" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "SUBCOMPETENCIA" => "Alineamiento",
        "DESCRIPCION" => "Define con claridad los roles y responsabilidades del equipo."
      ],
      [
        "CATEGORIA" => "Gerente Comercial Ap",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Proactividad",
        "DESCRIPCION" => "Se anticipa a los requerimientos o necesidades que se presentan."
      ],
      [
        "CATEGORIA" => "Gerente Comercial Ap",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Solución de Problemas",
        "DESCRIPCION" => "Resuelve los problemas a pesar de las dificultades."
      ],
      [
        "CATEGORIA" => "Gerente Comercial Ap",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Cumplimiento de Acuerdos",
        "DESCRIPCION" => "Cumple con los acuerdos de la organización."
      ],
      [
        "CATEGORIA" => "Gerente Comercial Ap",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Resultados",
        "DESCRIPCION" => "Logra los resultados que se le asignan."
      ],
      [
        "CATEGORIA" => "Gerente Comercial Ap",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Asumo las consecuencias",
        "DESCRIPCION" => "Me hago cargo de los errores que realizo y los resuelvo. No espero a que otros lo solucionen."
      ],
      [
        "CATEGORIA" => "Gerente Comercial Ap",
        "COMPETENCIA" => "Planificación: Determinar las metas y prioridades de los proyectos, estableciendo las acciones, los plazos y los recursos necesarios. Así como los instrumentos de medición y segumiento.",
        "SUBCOMPETENCIA" => "Creación de Planes",
        "DESCRIPCION" => "Crea planes o proyectos que generan valor a la organización."
      ],
      [
        "CATEGORIA" => "Gerente Comercial Ap",
        "COMPETENCIA" => "Planificación: Determinar las metas y prioridades de los proyectos, estableciendo las acciones, los plazos y los recursos necesarios. Así como los instrumentos de medición y segumiento.",
        "SUBCOMPETENCIA" => "Priorización",
        "DESCRIPCION" => "Establece el orden de las acciones que tiene un plan o proyecto de acuerdo a criterios establecidos."
      ],
      [
        "CATEGORIA" => "Gerente Comercial Ap",
        "COMPETENCIA" => "Planificación: Determinar las metas y prioridades de los proyectos, estableciendo las acciones, los plazos y los recursos necesarios. Así como los instrumentos de medición y segumiento.",
        "SUBCOMPETENCIA" => "Definir metas e indciadores",
        "DESCRIPCION" => "Define las metas e indicadores de éxito de las tareas o proyectos asignados."
      ],
      [
        "CATEGORIA" => "Gerente Comercial Ap",
        "COMPETENCIA" => "Planificación: Determinar las metas y prioridades de los proyectos, estableciendo las acciones, los plazos y los recursos necesarios. Así como los instrumentos de medición y segumiento.",
        "SUBCOMPETENCIA" => "Gestión de Recursos",
        "DESCRIPCION" => "Identifica y asigna los recursos de manera responsable."
      ],
      [
        "CATEGORIA" => "Gerente De Administración Y Finanzas",
        "COMPETENCIA" => "Administración Estratégica: Fomúla las acciones del negocio definiendo procedimientos claros, evaluando las acciones efectivas y controlando el presupuesto.",
        "SUBCOMPETENCIA" => "Fórmulación de estrategia",
        "DESCRIPCION" => "Define políticas, acciones y objetivos que permiten el avance adecuado del negocio."
      ],
      [
        "CATEGORIA" => "Gerente De Administración Y Finanzas",
        "COMPETENCIA" => "Administración Estratégica: Fomúla las acciones del negocio definiendo procedimientos claros, evaluando las acciones efectivas y controlando el presupuesto.",
        "SUBCOMPETENCIA" => "Implementación",
        "DESCRIPCION" => "Define los procedimientos y métodos adecuados para el cumplimiento de la estrategia."
      ],
      [
        "CATEGORIA" => "Gerente De Administración Y Finanzas",
        "COMPETENCIA" => "Administración Estratégica: Fomúla las acciones del negocio definiendo procedimientos claros, evaluando las acciones efectivas y controlando el presupuesto.",
        "SUBCOMPETENCIA" => "Evaluación",
        "DESCRIPCION" => "Realiza acciones efectivas para la evaluación del desempeño del negocio."
      ],
      [
        "CATEGORIA" => "Gerente De Administración Y Finanzas",
        "COMPETENCIA" => "Administración Estratégica: Fomúla las acciones del negocio definiendo procedimientos claros, evaluando las acciones efectivas y controlando el presupuesto.",
        "SUBCOMPETENCIA" => "Control Presupuestal",
        "DESCRIPCION" => "Define de ajustes y acciones que permitan mantener el control del Presupuesto."
      ],
      [
        "CATEGORIA" => "Gerente De Administración Y Finanzas",
        "COMPETENCIA" => "Dirección Financiera: Dirige las operaciones contables, define propuestas y acciones para la optimización de recursos.",
        "SUBCOMPETENCIA" => "Dirección de operaciones financieras",
        "DESCRIPCION" => "Dirige las operaciones contables y financieras de manera adecuada."
      ],
      [
        "CATEGORIA" => "Gerente De Administración Y Finanzas",
        "COMPETENCIA" => "Dirección Financiera: Dirige las operaciones contables, define propuestas y acciones para la optimización de recursos.",
        "SUBCOMPETENCIA" => "Propuestas de Inversión",
        "DESCRIPCION" => "Realiza propuestas de inversión adecuadas."
      ],
      [
        "CATEGORIA" => "Gerente De Administración Y Finanzas",
        "COMPETENCIA" => "Dirección Financiera: Dirige las operaciones contables, define propuestas y acciones para la optimización de recursos.",
        "SUBCOMPETENCIA" => "Estrategias de optimización de recursos",
        "DESCRIPCION" => "Define acciones y estrategias adecuadas para la gestión de deudas y optimización de recursos."
      ],
      [
        "CATEGORIA" => "Gerente De Administración Y Finanzas",
        "COMPETENCIA" => "Dirección Financiera: Dirige las operaciones contables, define propuestas y acciones para la optimización de recursos.",
        "SUBCOMPETENCIA" => "Gestión del riesgo",
        "DESCRIPCION" => "Gestiona el riesgo de manera adecuada."
      ],
      [
        "CATEGORIA" => "Gerente De Administración Y Finanzas",
        "COMPETENCIA" => "Dirección Financiera: Dirige las operaciones contables, define propuestas y acciones para la optimización de recursos.",
        "SUBCOMPETENCIA" => "Negociación",
        "DESCRIPCION" => "Negocia de manera adecuada, defendiendo los intereses de la organización y cuidando la relación con quien se negocia."
      ],
      [
        "CATEGORIA" => "Gerente De Administración Y Finanzas",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Supervisión",
        "DESCRIPCION" => "Supervisa los avances, entregables y acciones del equipo para el lograr los objetivos."
      ],
      [
        "CATEGORIA" => "Gerente De Administración Y Finanzas",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Seguimiento",
        "DESCRIPCION" => "Realiza seguimiento a los avances de su equipo."
      ],
      [
        "CATEGORIA" => "Gerente De Administración Y Finanzas",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Retroalimentación",
        "DESCRIPCION" => "Brinda retroalimentación de manera oportuna sobre los resultados individuales"
      ],
      [
        "CATEGORIA" => "Gerente De Administración Y Finanzas",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Control",
        "DESCRIPCION" => "Controla los avances del equipo a fin de intervenir de manera oportuna."
      ],
      [
        "CATEGORIA" => "Gerente De Administración Y Finanzas",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Gestión de Consecuencias",
        "DESCRIPCION" => "Felicita o corrige las acciones de su equipo."
      ],
      [
        "CATEGORIA" => "Gerente De Administración Y Finanzas",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Proactividad",
        "DESCRIPCION" => "Se anticipa a los requerimientos o necesidades que se presentan."
      ],
      [
        "CATEGORIA" => "Gerente De Administración Y Finanzas",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Solución de Problemas",
        "DESCRIPCION" => "Resuelve los problemas a pesar de las dificultades."
      ],
      [
        "CATEGORIA" => "Gerente De Administración Y Finanzas",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Cumplimiento de Acuerdos",
        "DESCRIPCION" => "Cumple con los acuerdos de la organización."
      ],
      [
        "CATEGORIA" => "Gerente De Administración Y Finanzas",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Resultados",
        "DESCRIPCION" => "Logra los resultados que se le asignan."
      ],
      [
        "CATEGORIA" => "Gerente De Administración Y Finanzas",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Asumo las consecuencias",
        "DESCRIPCION" => "Se hace cargo de los errores que realiza y los resuelve. No espera a que otros lo solucionen."
      ],
      [
        "CATEGORIA" => "Gerente De Administración Y Finanzas",
        "COMPETENCIA" => "Planificación: Determinar las metas y prioridades de los proyectos, estableciendo las acciones, los plazos y los recursos necesarios. Así como los instrumentos de medición y segumiento.",
        "SUBCOMPETENCIA" => "Creación de Planes",
        "DESCRIPCION" => "Crea planes o proyectos que generan valor a la organización."
      ],
      [
        "CATEGORIA" => "Gerente De Administración Y Finanzas",
        "COMPETENCIA" => "Planificación: Determinar las metas y prioridades de los proyectos, estableciendo las acciones, los plazos y los recursos necesarios. Así como los instrumentos de medición y segumiento.",
        "SUBCOMPETENCIA" => "Priorización",
        "DESCRIPCION" => "Establece el orden de las acciones que tiene un plan o proyecto de acuerdo a criterios establecidos."
      ],
      [
        "CATEGORIA" => "Gerente De Administración Y Finanzas",
        "COMPETENCIA" => "Planificación: Determinar las metas y prioridades de los proyectos, estableciendo las acciones, los plazos y los recursos necesarios. Así como los instrumentos de medición y segumiento.",
        "SUBCOMPETENCIA" => "Definir metas e indicadores",
        "DESCRIPCION" => "Define las metas e indicadores de éxito de las tareas o proyectos asignados."
      ],
      [
        "CATEGORIA" => "Gerente De Administración Y Finanzas",
        "COMPETENCIA" => "Planificación: Determinar las metas y prioridades de los proyectos, estableciendo las acciones, los plazos y los recursos necesarios. Así como los instrumentos de medición y segumiento.",
        "SUBCOMPETENCIA" => "Gestión de Recursos",
        "DESCRIPCION" => "Identifica y asigna los recursos de manera responsable."
      ],
      [
        "CATEGORIA" => "Gerente De Gestión Humana",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Claridad en el mensaje",
        "DESCRIPCION" => "Comunica sus ideas de forma clara y precisa."
      ],
      [
        "CATEGORIA" => "Gerente De Gestión Humana",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Respuesta Oportuna",
        "DESCRIPCION" => "Comparte información e ideas en el momento adecuado."
      ],
      [
        "CATEGORIA" => "Gerente De Gestión Humana",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Escucha",
        "DESCRIPCION" => "Escucha atentamente las opiniones y sugerencias."
      ],
      [
        "CATEGORIA" => "Gerente De Gestión Humana",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Asertividad",
        "DESCRIPCION" => "Expresa sus opiniones sin herir los sentimientos de los demás."
      ],
      [
        "CATEGORIA" => "Gerente De Gestión Humana",
        "COMPETENCIA" => "Consultoría: Brinda alternativas de solución a partir del conocimiento del negocio; la identificación de las necesidades de los colaboradores y la organización.",
        "SUBCOMPETENCIA" => "Conocimiento del Negocio",
        "DESCRIPCION" => "Conoce a profundidad las características del negocio, los productos y los procesos de la organización."
      ],
      [
        "CATEGORIA" => "Gerente De Gestión Humana",
        "COMPETENCIA" => "Consultoría: Brinda alternativas de solución a partir del conocimiento del negocio; la identificación de las necesidades de los colaboradores y la organización.",
        "SUBCOMPETENCIA" => "Identificación de Expectativas y Necesidades",
        "DESCRIPCION" => "Entiende las necesidades, expectativas y dolores de los colaboradores y la organización."
      ],
      [
        "CATEGORIA" => "Gerente De Gestión Humana",
        "COMPETENCIA" => "Consultoría: Brinda alternativas de solución a partir del conocimiento del negocio; la identificación de las necesidades de los colaboradores y la organización.",
        "SUBCOMPETENCIA" => "Asesoría",
        "DESCRIPCION" => "Proporciona orientación y alternativas de solución considerando el impacto en los colaboradores y la organización."
      ],
      [
        "CATEGORIA" => "Gerente De Gestión Humana",
        "COMPETENCIA" => "Consultoría: Brinda alternativas de solución a partir del conocimiento del negocio; la identificación de las necesidades de los colaboradores y la organización.",
        "SUBCOMPETENCIA" => "Evaluación",
        "DESCRIPCION" => "Evalúa los procesos los procesos relacionados a los colaboradores y la organización."
      ],
      [
        "CATEGORIA" => "Gerente De Gestión Humana",
        "COMPETENCIA" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "SUBCOMPETENCIA" => "Dirección de Equipos",
        "DESCRIPCION" => "Dirige al equipo al logro de resultados de manera efectiva."
      ],
      [
        "CATEGORIA" => "Gerente De Gestión Humana",
        "COMPETENCIA" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "SUBCOMPETENCIA" => "Resolución de Conflictos",
        "DESCRIPCION" => "Destraba o resuelve los conflictos internos que impactan en el desempeño del negocio."
      ],
      [
        "CATEGORIA" => "Gerente De Gestión Humana",
        "COMPETENCIA" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "SUBCOMPETENCIA" => "Convicción",
        "DESCRIPCION" => "Transmite credibilidad y convicción a los demás."
      ],
      [
        "CATEGORIA" => "Gerente De Gestión Humana",
        "COMPETENCIA" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "SUBCOMPETENCIA" => "Alineamiento",
        "DESCRIPCION" => "Define con claridad los roles y responsabilidades del equipo."
      ],
      [
        "CATEGORIA" => "Gerente De Gestión Humana",
        "COMPETENCIA" => "Planificación: Determinar las metas y prioridades de los proyectos, estableciendo las acciones, los plazos y los recursos necesarios. Así como los instrumentos de medición y segumiento.",
        "SUBCOMPETENCIA" => "Creación de Planes",
        "DESCRIPCION" => "Crea planes o proyectos que generan valor a la organización."
      ],
      [
        "CATEGORIA" => "Gerente De Gestión Humana",
        "COMPETENCIA" => "Planificación: Determinar las metas y prioridades de los proyectos, estableciendo las acciones, los plazos y los recursos necesarios. Así como los instrumentos de medición y segumiento.",
        "SUBCOMPETENCIA" => "Priorización",
        "DESCRIPCION" => "Establece el orden de las acciones que tiene un plan o proyecto de acuerdo a criterios establecidos."
      ],
      [
        "CATEGORIA" => "Gerente De Gestión Humana",
        "COMPETENCIA" => "Planificación: Determinar las metas y prioridades de los proyectos, estableciendo las acciones, los plazos y los recursos necesarios. Así como los instrumentos de medición y segumiento.",
        "SUBCOMPETENCIA" => "Definir metas e indciadores",
        "DESCRIPCION" => "Define las metas e indicadores de éxito de las tareas o proyectos asignados."
      ],
      [
        "CATEGORIA" => "Gerente De Gestión Humana",
        "COMPETENCIA" => "Planificación: Determinar las metas y prioridades de los proyectos, estableciendo las acciones, los plazos y los recursos necesarios. Así como los instrumentos de medición y segumiento.",
        "SUBCOMPETENCIA" => "Gestión de Recursos",
        "DESCRIPCION" => "Identifica y asigna los recursos de manera responsable."
      ],
      [
        "CATEGORIA" => "Gerente De Negocio Ap",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Claridad en el mensaje",
        "DESCRIPCION" => "Comunica sus ideas de forma clara y precisa."
      ],
      [
        "CATEGORIA" => "Gerente De Negocio Ap",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Respuesta Oportuna",
        "DESCRIPCION" => "Comparte información e ideas en el momento adecuado."
      ],
      [
        "CATEGORIA" => "Gerente De Negocio Ap",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Escucha",
        "DESCRIPCION" => "Escucha atentamente las opiniones y sugerencias."
      ],
      [
        "CATEGORIA" => "Gerente De Negocio Ap",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Asertividad",
        "DESCRIPCION" => "Expresa sus opiniones sin herir los sentimientos de los demás."
      ],
      [
        "CATEGORIA" => "Gerente De Negocio Ap",
        "COMPETENCIA" => "Dirección de Negocio: Dirige el Plan de Negocio de manera efectiva controlando y evaluando el despliegue e interviniendo con ajustes oportunos.",
        "SUBCOMPETENCIA" => "Dirige",
        "DESCRIPCION" => "Dirige de manera efectiva el Plan de Negocio."
      ],
      [
        "CATEGORIA" => "Gerente De Negocio Ap",
        "COMPETENCIA" => "Dirección de Negocio: Dirige el Plan de Negocio de manera efectiva controlando y evaluando el despliegue e interviniendo con ajustes oportunos.",
        "SUBCOMPETENCIA" => "Controla",
        "DESCRIPCION" => "Controla el despliegue del negocio de manera efectiva."
      ],
      [
        "CATEGORIA" => "Gerente De Negocio Ap",
        "COMPETENCIA" => "Dirección de Negocio: Dirige el Plan de Negocio de manera efectiva controlando y evaluando el despliegue e interviniendo con ajustes oportunos.",
        "SUBCOMPETENCIA" => "Ajustes Oportunos",
        "DESCRIPCION" => "Realiza ajustes oportunos en los despliegues de las empresas."
      ],
      [
        "CATEGORIA" => "Gerente De Negocio Ap",
        "COMPETENCIA" => "Dirección de Negocio: Dirige el Plan de Negocio de manera efectiva controlando y evaluando el despliegue e interviniendo con ajustes oportunos.",
        "SUBCOMPETENCIA" => "Evalúa",
        "DESCRIPCION" => "Evalúa los avances del negocio de manera oportuna."
      ],
      [
        "CATEGORIA" => "Gerente De Negocio Ap",
        "COMPETENCIA" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "SUBCOMPETENCIA" => "Dirección de Equipos",
        "DESCRIPCION" => "Dirige al equipo al logro de resultados de manera efectiva."
      ],
      [
        "CATEGORIA" => "Gerente De Negocio Ap",
        "COMPETENCIA" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "SUBCOMPETENCIA" => "Resolución de Conflictos",
        "DESCRIPCION" => "Destraba o resuelve los conflictos internos que impactan en el desempeño del negocio."
      ],
      [
        "CATEGORIA" => "Gerente De Negocio Ap",
        "COMPETENCIA" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "SUBCOMPETENCIA" => "Convicción",
        "DESCRIPCION" => "Transmite credibilidad y convicción a los demás."
      ],
      [
        "CATEGORIA" => "Gerente De Negocio Ap",
        "COMPETENCIA" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "SUBCOMPETENCIA" => "Alineamiento",
        "DESCRIPCION" => "Define con claridad los roles y responsabilidades del equipo."
      ],
      [
        "CATEGORIA" => "Gerente De Negocio Ap",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Proactividad",
        "DESCRIPCION" => "Se anticipa a los requerimientos o necesidades que se presentan."
      ],
      [
        "CATEGORIA" => "Gerente De Negocio Ap",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Solución de Problemas",
        "DESCRIPCION" => "Resuelve los problemas a pesar de las dificultades."
      ],
      [
        "CATEGORIA" => "Gerente De Negocio Ap",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Cumplimiento de Acuerdos",
        "DESCRIPCION" => "Cumple con los acuerdos de la organización."
      ],
      [
        "CATEGORIA" => "Gerente De Negocio Ap",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Resultados",
        "DESCRIPCION" => "Logra los resultados que se le asignan."
      ],
      [
        "CATEGORIA" => "Gerente De Negocio Ap",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Asumo las consecuencias",
        "DESCRIPCION" => "Me hago cargo de los errores que realizo y los resuelvo. No espero a que otros lo solucionen."
      ],
      [
        "CATEGORIA" => "Gerente De Negocio Ap",
        "COMPETENCIA" => "Socio Estratégico: Establece relaciones de alto valor para la organización, viendo las oportunidades en el mercado y tomando decisiones estratégicas de manera íntegra.",
        "SUBCOMPETENCIA" => "Visión de Negocio",
        "DESCRIPCION" => "Identifica oportunidades para ser competitivo y efectivo en el negocio."
      ],
      [
        "CATEGORIA" => "Gerente De Negocio Ap",
        "COMPETENCIA" => "Socio Estratégico: Establece relaciones de alto valor para la organización, viendo las oportunidades en el mercado y tomando decisiones estratégicas de manera íntegra.",
        "SUBCOMPETENCIA" => "Pensamiento estratégico",
        "DESCRIPCION" => "Define el Plan de Negocio adecuado para la organización."
      ],
      [
        "CATEGORIA" => "Gerente De Negocio Ap",
        "COMPETENCIA" => "Socio Estratégico: Establece relaciones de alto valor para la organización, viendo las oportunidades en el mercado y tomando decisiones estratégicas de manera íntegra.",
        "SUBCOMPETENCIA" => "Construcción de Relaciones",
        "DESCRIPCION" => "Desarrolla relaciones con personas claves en la organización y en el sector."
      ],
      [
        "CATEGORIA" => "Gerente De Negocio Ap",
        "COMPETENCIA" => "Socio Estratégico: Establece relaciones de alto valor para la organización, viendo las oportunidades en el mercado y tomando decisiones estratégicas de manera íntegra.",
        "SUBCOMPETENCIA" => "Toma de Decisiones",
        "DESCRIPCION" => "Toma decisiones de modo adecuado y en el momento oportuno."
      ],
      [
        "CATEGORIA" => "Gerente De Negocio Ap",
        "COMPETENCIA" => "Socio Estratégico: Establece relaciones de alto valor para la organización, viendo las oportunidades en el mercado y tomando decisiones estratégicas de manera íntegra.",
        "SUBCOMPETENCIA" => "Integridad",
        "DESCRIPCION" => "Sus acciones son congruentes y con honestidad."
      ],
      [
        "CATEGORIA" => "Gerente De Negocio Tp",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Asertividad",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Gerente De Negocio Tp",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Claridad en el mensaje",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Gerente De Negocio Tp",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Escucha",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Gerente De Negocio Tp",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Informe Escrito",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Gerente De Negocio Tp",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Orientación A La Meta Común",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Gerente De Negocio Tp",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Respuesta Oportuna",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Gerente De Negocio Tp",
        "COMPETENCIA" => "Dirección de Negocio: Dirige el Plan de Negocio de manera efectiva controlando y evaluando el despliegue e interviniendo con ajustes oportunos.",
        "SUBCOMPETENCIA" => "Ajustes Oportunos",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Gerente De Negocio Tp",
        "COMPETENCIA" => "Dirección de Negocio: Dirige el Plan de Negocio de manera efectiva controlando y evaluando el despliegue e interviniendo con ajustes oportunos.",
        "SUBCOMPETENCIA" => "Controla",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Gerente De Negocio Tp",
        "COMPETENCIA" => "Dirección de Negocio: Dirige el Plan de Negocio de manera efectiva controlando y evaluando el despliegue e interviniendo con ajustes oportunos.",
        "SUBCOMPETENCIA" => "Dirige",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Gerente De Negocio Tp",
        "COMPETENCIA" => "Dirección de Negocio: Dirige el Plan de Negocio de manera efectiva controlando y evaluando el despliegue e interviniendo con ajustes oportunos.",
        "SUBCOMPETENCIA" => "Evalúa",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Gerente De Negocio Tp",
        "COMPETENCIA" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "SUBCOMPETENCIA" => "Alineamiento",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Gerente De Negocio Tp",
        "COMPETENCIA" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "SUBCOMPETENCIA" => "Asumo Las Consecuencias",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Gerente De Negocio Tp",
        "COMPETENCIA" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "SUBCOMPETENCIA" => "Convicción",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Gerente De Negocio Tp",
        "COMPETENCIA" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "SUBCOMPETENCIA" => "Cumplimiento De Acuerdos",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Gerente De Negocio Tp",
        "COMPETENCIA" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "SUBCOMPETENCIA" => "Dirección De Equipos",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Gerente De Negocio Tp",
        "COMPETENCIA" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "SUBCOMPETENCIA" => "Proactividad",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Gerente De Negocio Tp",
        "COMPETENCIA" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "SUBCOMPETENCIA" => "Resolución De Conflictos",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Gerente De Negocio Tp",
        "COMPETENCIA" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "SUBCOMPETENCIA" => "Resultados",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Gerente De Negocio Tp",
        "COMPETENCIA" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "SUBCOMPETENCIA" => "Solución De Problema",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Gerente De Negocio Tp",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Asumo Las Consecuencias",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Gerente De Negocio Tp",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Cumplimiento De Acuerdos",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Gerente De Negocio Tp",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Proactividad",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Gerente De Negocio Tp",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Resultados",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Gerente De Negocio Tp",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Solución De Problema",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Gerente De Negocio Tp",
        "COMPETENCIA" => "Socio Estratégico: Establece relaciones de alto valor para la organización, viendo las oportunidades en el mercado y tomando decisiones estratégicas de manera íntegra.",
        "SUBCOMPETENCIA" => "Construcción De Relaciones",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Gerente De Negocio Tp",
        "COMPETENCIA" => "Socio Estratégico: Establece relaciones de alto valor para la organización, viendo las oportunidades en el mercado y tomando decisiones estratégicas de manera íntegra.",
        "SUBCOMPETENCIA" => "Integridad",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Gerente De Negocio Tp",
        "COMPETENCIA" => "Socio Estratégico: Establece relaciones de alto valor para la organización, viendo las oportunidades en el mercado y tomando decisiones estratégicas de manera íntegra.",
        "SUBCOMPETENCIA" => "Pensamiento Estratégico Del Negocio",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Gerente De Negocio Tp",
        "COMPETENCIA" => "Socio Estratégico: Establece relaciones de alto valor para la organización, viendo las oportunidades en el mercado y tomando decisiones estratégicas de manera íntegra.",
        "SUBCOMPETENCIA" => "Toma De Decisiones",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Gerente De Negocio Tp",
        "COMPETENCIA" => "Socio Estratégico: Establece relaciones de alto valor para la organización, viendo las oportunidades en el mercado y tomando decisiones estratégicas de manera íntegra.",
        "SUBCOMPETENCIA" => "Visión De Negocio",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Gerente De Post Venta",
        "COMPETENCIA" => "Gestión Financiera - Post Venta: Realiza una gestión financiera relacionada a la post-venta para contar con información relevante que ayude a tomar decisiones de manera oportuna.",
        "SUBCOMPETENCIA" => "Actualización de Indicadores y Recursos",
        "DESCRIPCION" => "Mantiene actualizados los indicadores de gestión y recursos de post-venta (cotizaciones, garantías, etc.)"
      ],
      [
        "CATEGORIA" => "Gerente De Post Venta",
        "COMPETENCIA" => "Gestión Financiera - Post Venta: Realiza una gestión financiera relacionada a la post-venta para contar con información relevante que ayude a tomar decisiones de manera oportuna.",
        "SUBCOMPETENCIA" => "Reporte de Indicadores y Recursos",
        "DESCRIPCION" => "Presenta reportes del avance de los indicadores de gestión y recursos para tomar decisiones de manera oportuna."
      ],
      [
        "CATEGORIA" => "Gerente De Post Venta",
        "COMPETENCIA" => "Gestión Financiera - Post Venta: Realiza una gestión financiera relacionada a la post-venta para contar con información relevante que ayude a tomar decisiones de manera oportuna.",
        "SUBCOMPETENCIA" => "Gestión de Garantías",
        "DESCRIPCION" => "Supervisa las garantías técnicas de fábrica, garantías de repuestos y accesorios."
      ],
      [
        "CATEGORIA" => "Gerente De Post Venta",
        "COMPETENCIA" => "Gestión Financiera - Post Venta: Realiza una gestión financiera relacionada a la post-venta para contar con información relevante que ayude a tomar decisiones de manera oportuna.",
        "SUBCOMPETENCIA" => "Estructuración de precios",
        "DESCRIPCION" => "Estructura los precios de respuestos y servicios de acuerdo al análisis de la cobertura que se quiere realizar."
      ],
      [
        "CATEGORIA" => "Gerente De Post Venta",
        "COMPETENCIA" => "Gestión Financiera - Post Venta: Realiza una gestión financiera relacionada a la post-venta para contar con información relevante que ayude a tomar decisiones de manera oportuna.",
        "SUBCOMPETENCIA" => "Segumineto a Cobranzas",
        "DESCRIPCION" => "Realiza un seguimiento de cobranzas oportuno."
      ],
      [
        "CATEGORIA" => "Gerente De Post Venta",
        "COMPETENCIA" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "SUBCOMPETENCIA" => "Dirección de Equipos",
        "DESCRIPCION" => "Dirige al equipo al logro de resultados de manera efectiva."
      ],
      [
        "CATEGORIA" => "Gerente De Post Venta",
        "COMPETENCIA" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "SUBCOMPETENCIA" => "Resolución de Conflictos",
        "DESCRIPCION" => "Destraba o resuelve los conflictos internos que impactan en el desempeño del negocio."
      ],
      [
        "CATEGORIA" => "Gerente De Post Venta",
        "COMPETENCIA" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "SUBCOMPETENCIA" => "Convicción",
        "DESCRIPCION" => "Transmite credibilidad y convicción a los demás."
      ],
      [
        "CATEGORIA" => "Gerente De Post Venta",
        "COMPETENCIA" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "SUBCOMPETENCIA" => "Alineamiento",
        "DESCRIPCION" => "Define con claridad los roles y responsabilidades del equipo."
      ],
      [
        "CATEGORIA" => "Gerente De Post Venta",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Proactividad",
        "DESCRIPCION" => "Se anticipa a los requerimientos o necesidades que se presentan."
      ],
      [
        "CATEGORIA" => "Gerente De Post Venta",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Solución de Problemas",
        "DESCRIPCION" => "Resuelve los problemas a pesar de las dificultades."
      ],
      [
        "CATEGORIA" => "Gerente De Post Venta",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Cumplimiento de Acuerdos",
        "DESCRIPCION" => "Cumple con los acuerdos de la organización."
      ],
      [
        "CATEGORIA" => "Gerente De Post Venta",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Resultados",
        "DESCRIPCION" => "Logra los resultados que se le asignan."
      ],
      [
        "CATEGORIA" => "Gerente De Post Venta",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Asumo las consecuencias",
        "DESCRIPCION" => "Me hago cargo de los errores que realizo y los resuelvo. No espero a que otros lo solucionen."
      ],
      [
        "CATEGORIA" => "Gerente De Post Venta",
        "COMPETENCIA" => "Planificación: Determinar las metas y prioridades de los proyectos, estableciendo las acciones, los plazos y los recursos necesarios. Así como los instrumentos de medición y segumiento.",
        "SUBCOMPETENCIA" => "Creación de Planes",
        "DESCRIPCION" => "Crea planes o proyectos que generan valor a la organización."
      ],
      [
        "CATEGORIA" => "Gerente De Post Venta",
        "COMPETENCIA" => "Planificación: Determinar las metas y prioridades de los proyectos, estableciendo las acciones, los plazos y los recursos necesarios. Así como los instrumentos de medición y segumiento.",
        "SUBCOMPETENCIA" => "Priorización",
        "DESCRIPCION" => "Establece el orden de las acciones que tiene un plan o proyecto de acuerdo a criterios establecidos."
      ],
      [
        "CATEGORIA" => "Gerente De Post Venta",
        "COMPETENCIA" => "Planificación: Determinar las metas y prioridades de los proyectos, estableciendo las acciones, los plazos y los recursos necesarios. Así como los instrumentos de medición y segumiento.",
        "SUBCOMPETENCIA" => "Definir metas e indciadores",
        "DESCRIPCION" => "Define las metas e indicadores de éxito de las tareas o proyectos asignados."
      ],
      [
        "CATEGORIA" => "Gerente De Post Venta",
        "COMPETENCIA" => "Planificación: Determinar las metas y prioridades de los proyectos, estableciendo las acciones, los plazos y los recursos necesarios. Así como los instrumentos de medición y segumiento.",
        "SUBCOMPETENCIA" => "Gestión de Recursos",
        "DESCRIPCION" => "Identifica y asigna los recursos de manera responsable."
      ],
      [
        "CATEGORIA" => "Gerente General",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Creación de Planes",
        "DESCRIPCION" => "Crea planes o proyectos que generan valor a la organización."
      ],
      [
        "CATEGORIA" => "Gerente General",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Priorización",
        "DESCRIPCION" => "Establece el orden de las acciones que tiene un plan o proyecto de acuerdo a criterios establecidos."
      ],
      [
        "CATEGORIA" => "Gerente General",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Definir metas e indicadores",
        "DESCRIPCION" => "Define las metas e indicadores de éxito de las tareas o proyectos asignados."
      ],
      [
        "CATEGORIA" => "Gerente General",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Gestión de Recursos",
        "DESCRIPCION" => "Identifica y asigna los recursos de manera responsable."
      ],
      [
        "CATEGORIA" => "Gerente General",
        "COMPETENCIA" => "Dirección de Negocio: Dirige el Plan de Negocio de manera efectiva controlando y evaluando el despliegue e interviniendo con ajustes oportunos.",
        "SUBCOMPETENCIA" => "Dirige",
        "DESCRIPCION" => "Dirige de manera efectiva el Plan de Negocio."
      ],
      [
        "CATEGORIA" => "Gerente General",
        "COMPETENCIA" => "Dirección de Negocio: Dirige el Plan de Negocio de manera efectiva controlando y evaluando el despliegue e interviniendo con ajustes oportunos.",
        "SUBCOMPETENCIA" => "Controla",
        "DESCRIPCION" => "Controla el despliegue del negocio de manera efectiva."
      ],
      [
        "CATEGORIA" => "Gerente General",
        "COMPETENCIA" => "Dirección de Negocio: Dirige el Plan de Negocio de manera efectiva controlando y evaluando el despliegue e interviniendo con ajustes oportunos.",
        "SUBCOMPETENCIA" => "Ajustes Oportunos",
        "DESCRIPCION" => "Realiza ajustes oportunos en los despliegues de las empresas."
      ],
      [
        "CATEGORIA" => "Gerente General",
        "COMPETENCIA" => "Dirección de Negocio: Dirige el Plan de Negocio de manera efectiva controlando y evaluando el despliegue e interviniendo con ajustes oportunos.",
        "SUBCOMPETENCIA" => "Evalúa",
        "DESCRIPCION" => "Evalúa los avances del negocio de manera oportuna."
      ],
      [
        "CATEGORIA" => "Gerente General",
        "COMPETENCIA" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "SUBCOMPETENCIA" => "Dirección de Equipos",
        "DESCRIPCION" => "Dirige al equipo al logro de resultados de manera efectiva."
      ],
      [
        "CATEGORIA" => "Gerente General",
        "COMPETENCIA" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "SUBCOMPETENCIA" => "Resolución de Conflictos",
        "DESCRIPCION" => "Destraba o resuelve los conflictos internos que impactan en el desempeño del negocio."
      ],
      [
        "CATEGORIA" => "Gerente General",
        "COMPETENCIA" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "SUBCOMPETENCIA" => "Convicción",
        "DESCRIPCION" => "Transmite credibilidad y convicción a los demás."
      ],
      [
        "CATEGORIA" => "Gerente General",
        "COMPETENCIA" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "SUBCOMPETENCIA" => "Alineamiento",
        "DESCRIPCION" => "Define con claridad los roles y responsabilidades del equipo."
      ],
      [
        "CATEGORIA" => "Gerente General",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Proactividad",
        "DESCRIPCION" => "Se anticipa a los requerimientos o necesidades que se presentan."
      ],
      [
        "CATEGORIA" => "Gerente General",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Solución de Problemas",
        "DESCRIPCION" => "Resuelve los problemas a pesar de las dificultades."
      ],
      [
        "CATEGORIA" => "Gerente General",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Cumplimiento de Acuerdos",
        "DESCRIPCION" => "Cumple con los acuerdos de la organización."
      ],
      [
        "CATEGORIA" => "Gerente General",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Resultados",
        "DESCRIPCION" => "Logra los resultados que se le asignan."
      ],
      [
        "CATEGORIA" => "Gerente General",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Asumo las consecuencias",
        "DESCRIPCION" => "Me hago cargo de los errores que realizo y los resuelvo. No espero a que otros lo solucionen."
      ],
      [
        "CATEGORIA" => "Gerente General",
        "COMPETENCIA" => "Socio Estratégico: Establece relaciones de alto valor para la organización, viendo las oportunidades en el mercado y tomando decisiones estratégicas de manera íntegra.",
        "SUBCOMPETENCIA" => "Visión de Negocio",
        "DESCRIPCION" => "Identifica oportunidades para ser competitivo y efectivo en el negocio."
      ],
      [
        "CATEGORIA" => "Gerente General",
        "COMPETENCIA" => "Socio Estratégico: Establece relaciones de alto valor para la organización, viendo las oportunidades en el mercado y tomando decisiones estratégicas de manera íntegra.",
        "SUBCOMPETENCIA" => "Pensamiento estratégico",
        "DESCRIPCION" => "Define el Plan de Negocio adecuado para la organización."
      ],
      [
        "CATEGORIA" => "Gerente General",
        "COMPETENCIA" => "Socio Estratégico: Establece relaciones de alto valor para la organización, viendo las oportunidades en el mercado y tomando decisiones estratégicas de manera íntegra.",
        "SUBCOMPETENCIA" => "Construcción de Relaciones",
        "DESCRIPCION" => "Desarrolla relaciones con personas claves en la organización y en el sector."
      ],
      [
        "CATEGORIA" => "Gerente General",
        "COMPETENCIA" => "Socio Estratégico: Establece relaciones de alto valor para la organización, viendo las oportunidades en el mercado y tomando decisiones estratégicas de manera íntegra.",
        "SUBCOMPETENCIA" => "Toma de Decisiones",
        "DESCRIPCION" => "Toma decisiones de modo adecuado y en el momento oportuno."
      ],
      [
        "CATEGORIA" => "Gerente General",
        "COMPETENCIA" => "Socio Estratégico: Establece relaciones de alto valor para la organización, viendo las oportunidades en el mercado y tomando decisiones estratégicas de manera íntegra.",
        "SUBCOMPETENCIA" => "Integridad",
        "DESCRIPCION" => "Sus acciones son congruentes y con honestidad."
      ],
      [
        "CATEGORIA" => "Gestor Comercial",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Claridad en el mensaje",
        "DESCRIPCION" => "Comunica sus ideas de forma clara y precisa."
      ],
      [
        "CATEGORIA" => "Gestor Comercial",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Respuesta Oportuna",
        "DESCRIPCION" => "Comparte información e ideas en el momento adecuado."
      ],
      [
        "CATEGORIA" => "Gestor Comercial",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Escucha",
        "DESCRIPCION" => "Escucha atentamente las opiniones y sugerencias."
      ],
      [
        "CATEGORIA" => "Gestor Comercial",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Asertividad",
        "DESCRIPCION" => "Expresa sus opiniones sin herir los sentimientos de los demás."
      ],
      [
        "CATEGORIA" => "Gestor Comercial",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Gestión de Recursos",
        "DESCRIPCION" => "Identifica y asigna los recursos de manera responsable."
      ],
      [
        "CATEGORIA" => "Gestor Comercial",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Metodología",
        "DESCRIPCION" => "Utiliza metodologías o procesos estandarizados que garantizan los resultados esperados."
      ],
      [
        "CATEGORIA" => "Gestor Comercial",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Orientación al logro",
        "DESCRIPCION" => "Logra los resultados esperados en los tiempos establecidos."
      ],
      [
        "CATEGORIA" => "Gestor Comercial",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Atención Efectiva a consultas",
        "DESCRIPCION" => "Responde oportunamente a las solicitudes y consultas de los clientes vía presencial, telefónica y por correo electrónico."
      ],
      [
        "CATEGORIA" => "Gestor Comercial",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Seguimiento a la atención",
        "DESCRIPCION" => "Vigila la adecuada atención del cliente y pide al responsable a que se haga cargo."
      ],
      [
        "CATEGORIA" => "Gestor Comercial",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Atención presencial oportuna",
        "DESCRIPCION" => "Hace todo lo necesario para que el cliente sea bien atendido en el menor tiempo posible."
      ],
      [
        "CATEGORIA" => "Gestor Comercial",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Amabilidad y cercanía",
        "DESCRIPCION" => "Trata al cliente con amabilidad generando cercanía y confianza."
      ],
      [
        "CATEGORIA" => "Gestor Comercial",
        "COMPETENCIA" => "Supervisión Efectiva: Supervisar el desempeño de los colaboradores brindando retrolimentación a partir del mismo y gestionar los resultados obtenidos.",
        "SUBCOMPETENCIA" => "Seguimiento",
        "DESCRIPCION" => "Supervisa los avances, entregables y acciones de los colaboradores para lograr los objetivos."
      ],
      [
        "CATEGORIA" => "Gestor Comercial",
        "COMPETENCIA" => "Supervisión Efectiva: Supervisar el desempeño de los colaboradores brindando retrolimentación a partir del mismo y gestionar los resultados obtenidos.",
        "SUBCOMPETENCIA" => "Retroalimentación",
        "DESCRIPCION" => "Brinda retroalimentación de manera oportuna sobre los resultados individuales."
      ],
      [
        "CATEGORIA" => "Gestor Comercial",
        "COMPETENCIA" => "Supervisión Efectiva: Supervisar el desempeño de los colaboradores brindando retrolimentación a partir del mismo y gestionar los resultados obtenidos.",
        "SUBCOMPETENCIA" => "Gestión de Consecuencias",
        "DESCRIPCION" => "Felicita o corrige las acciones de los colaboradores de acuerdo al desempeño que tienen."
      ],
      [
        "CATEGORIA" => "Jefe De Administracion",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Supervisión",
        "DESCRIPCION" => "Supervisa los avances, entregables y acciones del equipo para el lograr los objetivos."
      ],
      [
        "CATEGORIA" => "Jefe De Administracion",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Seguimiento",
        "DESCRIPCION" => "Realiza seguimiento a los avances de su equipo."
      ],
      [
        "CATEGORIA" => "Jefe De Administracion",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Retroalimentación",
        "DESCRIPCION" => "Brinda retroalimentación de manera oportuna sobre los resultados individuales"
      ],
      [
        "CATEGORIA" => "Jefe De Administracion",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Control",
        "DESCRIPCION" => "Controla los avances del equipo a fin de intervenir de manera oportuna."
      ],
      [
        "CATEGORIA" => "Jefe De Administracion",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Gestión de Consecuencias",
        "DESCRIPCION" => "Felicita o corrige las acciones de su equipo."
      ],
      [
        "CATEGORIA" => "Jefe De Administracion",
        "COMPETENCIA" => "Gestión de Proveedores - Admin: Controlar el cumplimiento de los acuerdos del proveedor, interviene ante los problemas y reclamos en la entrega de mercadería.",
        "SUBCOMPETENCIA" => "Seguimiento de cobranzas",
        "DESCRIPCION" => "Realiza el seguimiento oportuno de las notas de crédito de los proveedores."
      ],
      [
        "CATEGORIA" => "Jefe De Administracion",
        "COMPETENCIA" => "Gestión de Proveedores - Admin: Controlar el cumplimiento de los acuerdos del proveedor, interviene ante los problemas y reclamos en la entrega de mercadería.",
        "SUBCOMPETENCIA" => "Traspase de información",
        "DESCRIPCION" => "Avisa de manera oportuna las problemáticas con el proveedor a la unidad Comercial."
      ],
      [
        "CATEGORIA" => "Jefe De Administracion",
        "COMPETENCIA" => "Gestión de Proveedores - Admin: Controlar el cumplimiento de los acuerdos del proveedor, interviene ante los problemas y reclamos en la entrega de mercadería.",
        "SUBCOMPETENCIA" => "Manejo de Reclamos",
        "DESCRIPCION" => "Resuelve los reclamos con los proveedores que entregan materiales sin la calidad definida."
      ],
      [
        "CATEGORIA" => "Jefe De Administracion",
        "COMPETENCIA" => "Gestión de Proveedores - Admin: Controlar el cumplimiento de los acuerdos del proveedor, interviene ante los problemas y reclamos en la entrega de mercadería.",
        "SUBCOMPETENCIA" => "Solución de Problemas",
        "DESCRIPCION" => "Muestra una actitud colaborativa y propone soluciones ante las problemáticas que surgan con los proveedores."
      ],
      [
        "CATEGORIA" => "Jefe De Administracion",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Proactividad",
        "DESCRIPCION" => "Se anticipa a los requerimientos o necesidades que se presentan."
      ],
      [
        "CATEGORIA" => "Jefe De Administracion",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Solución de Problemas",
        "DESCRIPCION" => "Resuelve los problemas a pesar de las dificultades."
      ],
      [
        "CATEGORIA" => "Jefe De Administracion",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Cumplimiento de Acuerdos",
        "DESCRIPCION" => "Cumple con los acuerdos de la organización."
      ],
      [
        "CATEGORIA" => "Jefe De Administracion",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Resultados",
        "DESCRIPCION" => "Logra los resultados que se le asignan."
      ],
      [
        "CATEGORIA" => "Jefe De Administracion",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Asumo las consecuencias",
        "DESCRIPCION" => "Se hace cargo de los errores que realiza y los resuelve. No espera a que otros lo solucionen"
      ],
      [
        "CATEGORIA" => "Jefe De Administracion",
        "COMPETENCIA" => "Organización y Control de Recursos JA",
        "SUBCOMPETENCIA" => "Control de Recursos",
        "DESCRIPCION" => "Controla el ingreso y salida de los recursos."
      ],
      [
        "CATEGORIA" => "Jefe De Administracion",
        "COMPETENCIA" => "Organización y Control de Recursos JA",
        "SUBCOMPETENCIA" => "Manejo de Procedimientos",
        "DESCRIPCION" => "Realiza los registros de cada recurso de acuerdo los lineamientos definidos."
      ],
      [
        "CATEGORIA" => "Jefe De Administracion",
        "COMPETENCIA" => "Organización y Control de Recursos JA",
        "SUBCOMPETENCIA" => "Solicitud oportuna",
        "DESCRIPCION" => "Repone de manera oportuna los recursos que se requieren."
      ],
      [
        "CATEGORIA" => "Jefe De Administracion",
        "COMPETENCIA" => "Organización y Control de Recursos JA",
        "SUBCOMPETENCIA" => "Organización y Clasificación",
        "DESCRIPCION" => "Clasifica los recursos con un orden claro y práctico."
      ],
      [
        "CATEGORIA" => "Jefe De Administracion",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Orientación al Detalle",
        "DESCRIPCION" => "Es minucioso en el manejo de datos."
      ],
      [
        "CATEGORIA" => "Jefe De Administracion",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Organización",
        "DESCRIPCION" => "Mantiene un orden en los documentos que maneja."
      ],
      [
        "CATEGORIA" => "Jefe De Administracion",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Cumplimiento de Procesos Operativos",
        "DESCRIPCION" => "Cumple con los procedimientos establecidos por la organización de manera correcta."
      ],
      [
        "CATEGORIA" => "Jefe de Administración Comercial",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Asertividad",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe de Administración Comercial",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Claridad en el mensaje",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe de Administración Comercial",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Escucha",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe de Administración Comercial",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Informe Escrito",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe de Administración Comercial",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Orientación A La Meta Común",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe de Administración Comercial",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Respuesta Oportuna",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe de Administración Comercial",
        "COMPETENCIA" => "Gestión de Trámites: Garantiza la recepción y entrega de documentos asegurando que cumpla con los requisitos solicitados.",
        "SUBCOMPETENCIA" => "Construcción De Red De Contactos",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe de Administración Comercial",
        "COMPETENCIA" => "Gestión de Trámites: Garantiza la recepción y entrega de documentos asegurando que cumpla con los requisitos solicitados.",
        "SUBCOMPETENCIA" => "Control Documentario",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe de Administración Comercial",
        "COMPETENCIA" => "Gestión de Trámites: Garantiza la recepción y entrega de documentos asegurando que cumpla con los requisitos solicitados.",
        "SUBCOMPETENCIA" => "Despacho Documentario",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe de Administración Comercial",
        "COMPETENCIA" => "Gestión de Trámites: Garantiza la recepción y entrega de documentos asegurando que cumpla con los requisitos solicitados.",
        "SUBCOMPETENCIA" => "Perseverancia En La Entrega",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe de Administración Comercial",
        "COMPETENCIA" => "Mentalidad de Dueño V2: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Anticipación A Requerimientos",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe de Administración Comercial",
        "COMPETENCIA" => "Mentalidad de Dueño V2: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Pertenencia",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe de Administración Comercial",
        "COMPETENCIA" => "Mentalidad de Dueño V2: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Solución De Problemas Difíciles",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe de Administración Comercial",
        "COMPETENCIA" => "Registro y Manejo de Información - Versión 2 AP: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Cumplimiento De Procesos Operativos",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe de Administración Comercial",
        "COMPETENCIA" => "Registro y Manejo de Información - Versión 2 AP: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Organización",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe de Administración Comercial",
        "COMPETENCIA" => "Registro y Manejo de Información - Versión 2 AP: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Orientación Al Detalle",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe de Administración Comercial",
        "COMPETENCIA" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "SUBCOMPETENCIA" => "Colaboración",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe de Administración Comercial",
        "COMPETENCIA" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "SUBCOMPETENCIA" => "Pasión",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe de Administración Comercial",
        "COMPETENCIA" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "SUBCOMPETENCIA" => "Responsabilidad",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe de Administración Comercial",
        "COMPETENCIA" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "SUBCOMPETENCIA" => "Resuelve Conflictos",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe de Administración Comercial",
        "COMPETENCIA" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "SUBCOMPETENCIA" => "Traspase De Información En Equipo",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Almacen",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Claridad en el mensaje",
        "DESCRIPCION" => "Comunica sus ideas de forma clara y precisa."
      ],
      [
        "CATEGORIA" => "Jefe De Almacen",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Respuesta Oportuna",
        "DESCRIPCION" => "Comparte información e ideas en el momento adecuado."
      ],
      [
        "CATEGORIA" => "Jefe De Almacen",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Escucha",
        "DESCRIPCION" => "Escucha atentamente las opiniones y sugerencias."
      ],
      [
        "CATEGORIA" => "Jefe De Almacen",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Asertividad",
        "DESCRIPCION" => "Expresa sus opiniones sin herir los sentimientos de los demás."
      ],
      [
        "CATEGORIA" => "Jefe De Almacen",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Dirección o Supervisión",
        "DESCRIPCION" => "Supervisa los avances, entregables y acciones del equipo para el lograr los objetivos."
      ],
      [
        "CATEGORIA" => "Jefe De Almacen",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Seguimiento",
        "DESCRIPCION" => "Realiza seguimiento a los avances de su equipo."
      ],
      [
        "CATEGORIA" => "Jefe De Almacen",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Retroalimentación",
        "DESCRIPCION" => "Brinda retroalimentación de manera oportuna sobre los resultados individuales"
      ],
      [
        "CATEGORIA" => "Jefe De Almacen",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Control",
        "DESCRIPCION" => "Controla los avances del equipo a fin de intervenir de manera oportuna."
      ],
      [
        "CATEGORIA" => "Jefe De Almacen",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Gestión de Consecuencias",
        "DESCRIPCION" => "Felicita o corrige las acciones de su equipo."
      ],
      [
        "CATEGORIA" => "Jefe De Almacen",
        "COMPETENCIA" => "Gestión de Proveedores - Admin: Controlar el cumplimiento de los acuerdos del proveedor, interviene ante los problemas y reclamos en la entrega de mercadería.",
        "SUBCOMPETENCIA" => "Identificación",
        "DESCRIPCION" => "Busca e identifica los mejores proveedores de acuerdo a los lineamientos de la organización."
      ],
      [
        "CATEGORIA" => "Jefe De Almacen",
        "COMPETENCIA" => "Gestión de Proveedores - Admin: Controlar el cumplimiento de los acuerdos del proveedor, interviene ante los problemas y reclamos en la entrega de mercadería.",
        "SUBCOMPETENCIA" => "Seguimiento",
        "DESCRIPCION" => "Realiza el seguimiento oportuno a las acciones que tiene que realizar los proveedores."
      ],
      [
        "CATEGORIA" => "Jefe De Almacen",
        "COMPETENCIA" => "Gestión de Proveedores - Admin: Controlar el cumplimiento de los acuerdos del proveedor, interviene ante los problemas y reclamos en la entrega de mercadería.",
        "SUBCOMPETENCIA" => "Manejo de Reclamos",
        "DESCRIPCION" => "Resuelve los reclamos con los proveedores que no entregan con la calidad definida."
      ],
      [
        "CATEGORIA" => "Jefe De Almacen",
        "COMPETENCIA" => "Gestión de Proveedores - Admin: Controlar el cumplimiento de los acuerdos del proveedor, interviene ante los problemas y reclamos en la entrega de mercadería.",
        "SUBCOMPETENCIA" => "Solución de Problemas",
        "DESCRIPCION" => "Muestra una actitud colaborativa y propone soluciones ante las problemáticas que surgan con los proveedores."
      ],
      [
        "CATEGORIA" => "Jefe De Almacen",
        "COMPETENCIA" => "Organización y Control de Mercadería: Garantizar la cantidad de recursos que necesita la organización a través de una adecuada supervisión, organización, clasificación y control.",
        "SUBCOMPETENCIA" => "Control de Movimientos",
        "DESCRIPCION" => "Controla el ingreso y salida de la mercadería de manera adecuada."
      ],
      [
        "CATEGORIA" => "Jefe De Almacen",
        "COMPETENCIA" => "Organización y Control de Mercadería: Garantizar la cantidad de recursos que necesita la organización a través de una adecuada supervisión, organización, clasificación y control.",
        "SUBCOMPETENCIA" => "Protección de Recursos",
        "DESCRIPCION" => "Verifica con detalle si la mercadería llega averiada o incompleta."
      ],
      [
        "CATEGORIA" => "Jefe De Almacen",
        "COMPETENCIA" => "Organización y Control de Mercadería: Garantizar la cantidad de recursos que necesita la organización a través de una adecuada supervisión, organización, clasificación y control.",
        "SUBCOMPETENCIA" => "Supervisión de Inventario",
        "DESCRIPCION" => "Supervisa de manera adecuada los inventarios de almacen (respuestos, accesorios, vehículos)."
      ],
      [
        "CATEGORIA" => "Jefe De Almacen",
        "COMPETENCIA" => "Organización y Control de Mercadería: Garantizar la cantidad de recursos que necesita la organización a través de una adecuada supervisión, organización, clasificación y control.",
        "SUBCOMPETENCIA" => "Organización de Recursos",
        "DESCRIPCION" => "Clasifica los recursos con un orden claro y práctico."
      ],
      [
        "CATEGORIA" => "Jefe De Almacen",
        "COMPETENCIA" => "Organización y Control de Mercadería: Garantizar la cantidad de recursos que necesita la organización a través de una adecuada supervisión, organización, clasificación y control.",
        "SUBCOMPETENCIA" => "Reclamos de ingreso de Recursos",
        "DESCRIPCION" => "Realiza de manera oportuna los reclamos ante mercaderías averiadas o faltantes."
      ],
      [
        "CATEGORIA" => "Jefe De Contabilidad",
        "COMPETENCIA" => "Gestión Contable: Analiza de manera adecuada la información contable y define acciones adecuadas para el sistema de contable.",
        "SUBCOMPETENCIA" => "Análisis de estados financieros",
        "DESCRIPCION" => "Analiza de manera efectiva los estados financieros."
      ],
      [
        "CATEGORIA" => "Jefe De Contabilidad",
        "COMPETENCIA" => "Gestión Contable: Analiza de manera adecuada la información contable y define acciones adecuadas para el sistema de contable.",
        "SUBCOMPETENCIA" => "Sistemas correctivos",
        "DESCRIPCION" => "Realiza acciones adecuadas para mejorar los sistemas correctivos."
      ],
      [
        "CATEGORIA" => "Jefe De Contabilidad",
        "COMPETENCIA" => "Gestión Contable: Analiza de manera adecuada la información contable y define acciones adecuadas para el sistema de contable.",
        "SUBCOMPETENCIA" => "Plazos de Entrega",
        "DESCRIPCION" => "Cumple con los plazos establecidos para las declaraciones en las instituciones."
      ],
      [
        "CATEGORIA" => "Jefe De Contabilidad",
        "COMPETENCIA" => "Gestión Contable: Analiza de manera adecuada la información contable y define acciones adecuadas para el sistema de contable.",
        "SUBCOMPETENCIA" => "Informes contables",
        "DESCRIPCION" => "Ejecuta informes financieros con información adecuada."
      ],
      [
        "CATEGORIA" => "Jefe De Contabilidad",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Supervisión",
        "DESCRIPCION" => "Supervisa los avances, entregables y acciones del equipo para el lograr los objetivos."
      ],
      [
        "CATEGORIA" => "Jefe De Contabilidad",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Seguimiento",
        "DESCRIPCION" => "Realiza seguimiento a los avances de su equipo."
      ],
      [
        "CATEGORIA" => "Jefe De Contabilidad",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Retroalimentación",
        "DESCRIPCION" => "Brinda retroalimentación de manera oportuna sobre los resultados individuales"
      ],
      [
        "CATEGORIA" => "Jefe De Contabilidad",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Control",
        "DESCRIPCION" => "Controla los avances del equipo a fin de intervenir de manera oportuna."
      ],
      [
        "CATEGORIA" => "Jefe De Contabilidad",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Gestión de Consecuencias",
        "DESCRIPCION" => "Felicita o corrige las acciones de su equipo."
      ],
      [
        "CATEGORIA" => "Jefe De Contabilidad",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Proactividad",
        "DESCRIPCION" => "Se anticipa a los requerimientos o necesidades que se presentan."
      ],
      [
        "CATEGORIA" => "Jefe De Contabilidad",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Solución de Problemas",
        "DESCRIPCION" => "Resuelve los problemas a pesar de las dificultades."
      ],
      [
        "CATEGORIA" => "Jefe De Contabilidad",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Cumplimiento de Acuerdos",
        "DESCRIPCION" => "Cumple con los acuerdos de la organización."
      ],
      [
        "CATEGORIA" => "Jefe De Contabilidad",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Resultados",
        "DESCRIPCION" => "Logra los resultados que se le asignan."
      ],
      [
        "CATEGORIA" => "Jefe De Contabilidad",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Asumo las consecuencias",
        "DESCRIPCION" => "Se hace cargo de los errores que realiza y los resuelve. No espera a que otros lo solucionen."
      ],
      [
        "CATEGORIA" => "Jefe De Contabilidad",
        "COMPETENCIA" => "Perspicacia Contable: Garantizar el correcto registro de los ingresos de la organización identificando posibles riesgos y auditando. Actuar con transparencia durante la auditoría.",
        "SUBCOMPETENCIA" => "Auditorías Oportunas",
        "DESCRIPCION" => "Realiza revisiones sorpresa para la verificación del cumplimiento de la normativa."
      ],
      [
        "CATEGORIA" => "Jefe De Contabilidad",
        "COMPETENCIA" => "Perspicacia Contable: Garantizar el correcto registro de los ingresos de la organización identificando posibles riesgos y auditando. Actuar con transparencia durante la auditoría.",
        "SUBCOMPETENCIA" => "Control de información Sensible",
        "DESCRIPCION" => "Controla de manera adecuada la información contable y financiera de la empresa"
      ],
      [
        "CATEGORIA" => "Jefe De Contabilidad",
        "COMPETENCIA" => "Perspicacia Contable: Garantizar el correcto registro de los ingresos de la organización identificando posibles riesgos y auditando. Actuar con transparencia durante la auditoría.",
        "SUBCOMPETENCIA" => "Fraudes",
        "DESCRIPCION" => "Identifica posibles actos fraudulentos que impacten en la rentabilidad."
      ],
      [
        "CATEGORIA" => "Jefe De Contabilidad",
        "COMPETENCIA" => "Perspicacia Contable: Garantizar el correcto registro de los ingresos de la organización identificando posibles riesgos y auditando. Actuar con transparencia durante la auditoría.",
        "SUBCOMPETENCIA" => "Congruente",
        "DESCRIPCION" => "Actúa con honestidad, sus acciones son confiables para la empresa."
      ],
      [
        "CATEGORIA" => "Jefe De Contabilidad",
        "COMPETENCIA" => "Perspicacia Contable: Garantizar el correcto registro de los ingresos de la organización identificando posibles riesgos y auditando. Actuar con transparencia durante la auditoría.",
        "SUBCOMPETENCIA" => "Análisis de información Contable",
        "DESCRIPCION" => "Interpreta de manera adecuada la información contable y la información financiera para la toma de decisiones gerenciales."
      ],
      [
        "CATEGORIA" => "Jefe De Contabilidad",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Orientación al Detalle",
        "DESCRIPCION" => "Es minucioso en el manejo de datos."
      ],
      [
        "CATEGORIA" => "Jefe De Contabilidad",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Organización",
        "DESCRIPCION" => "Mantiene un orden en los documentos que maneja."
      ],
      [
        "CATEGORIA" => "Jefe De Contabilidad",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Cumplimiento de Procesos Operativos",
        "DESCRIPCION" => "Cumple con los procedimientos establecidos por la organización de manera correcta."
      ],
      [
        "CATEGORIA" => "Jefe De Contabilidad Y Caja",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Control",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Contabilidad Y Caja",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Gestión De Consecuencias",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Contabilidad Y Caja",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Retroalimentación",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Contabilidad Y Caja",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Seguimiento",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Contabilidad Y Caja",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Supervisión",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Contabilidad Y Caja",
        "COMPETENCIA" => "Gestión de Flujo de Caja: Evalúa los recursos financieros, analiza la información del flujo de caja y realiza un adecuado control y reporte de su funcionamiento.",
        "SUBCOMPETENCIA" => "Análisis De Reportes",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Contabilidad Y Caja",
        "COMPETENCIA" => "Gestión de Flujo de Caja: Evalúa los recursos financieros, analiza la información del flujo de caja y realiza un adecuado control y reporte de su funcionamiento.",
        "SUBCOMPETENCIA" => "Creación De Reportes",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Contabilidad Y Caja",
        "COMPETENCIA" => "Gestión de Flujo de Caja: Evalúa los recursos financieros, analiza la información del flujo de caja y realiza un adecuado control y reporte de su funcionamiento.",
        "SUBCOMPETENCIA" => "Evaluación De Recursos Financieros",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Contabilidad Y Caja",
        "COMPETENCIA" => "Gestión de Flujo de Caja: Evalúa los recursos financieros, analiza la información del flujo de caja y realiza un adecuado control y reporte de su funcionamiento.",
        "SUBCOMPETENCIA" => "Pago De Servicios",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Contabilidad Y Caja",
        "COMPETENCIA" => "Gestión de Trámites: Garantiza la recepción y entrega de documentos asegurando que cumpla con los requisitos solicitados.",
        "SUBCOMPETENCIA" => "Construcción De Red De Contactos",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Contabilidad Y Caja",
        "COMPETENCIA" => "Gestión de Trámites: Garantiza la recepción y entrega de documentos asegurando que cumpla con los requisitos solicitados.",
        "SUBCOMPETENCIA" => "Control Documentario",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Contabilidad Y Caja",
        "COMPETENCIA" => "Gestión de Trámites: Garantiza la recepción y entrega de documentos asegurando que cumpla con los requisitos solicitados.",
        "SUBCOMPETENCIA" => "Despacho Documentario",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Contabilidad Y Caja",
        "COMPETENCIA" => "Gestión de Trámites: Garantiza la recepción y entrega de documentos asegurando que cumpla con los requisitos solicitados.",
        "SUBCOMPETENCIA" => "Perseverancia En La Entrega",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Contabilidad Y Caja",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Asumo Las Consecuencias",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Contabilidad Y Caja",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Cumplimiento De Acuerdos",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Contabilidad Y Caja",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Proactividad",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Contabilidad Y Caja",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Resultados",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Contabilidad Y Caja",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Solución De Problema",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Contabilidad Y Caja",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Cumplimiento De Procedimientos",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Contabilidad Y Caja",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Organización De Información",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Contabilidad Y Caja",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Orientación Al Registro Detallado",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Legal",
        "COMPETENCIA" => "Construcción de relaciones de negocio: Comprende el funcionamiento de los negocios, analiza los cambios del entorno para aprovechar las oportunidades del mercado.",
        "SUBCOMPETENCIA" => "Análisis De Negocios",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Legal",
        "COMPETENCIA" => "Construcción de relaciones de negocio: Comprende el funcionamiento de los negocios, analiza los cambios del entorno para aprovechar las oportunidades del mercado.",
        "SUBCOMPETENCIA" => "Anticipación",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Legal",
        "COMPETENCIA" => "Construcción de relaciones de negocio: Comprende el funcionamiento de los negocios, analiza los cambios del entorno para aprovechar las oportunidades del mercado.",
        "SUBCOMPETENCIA" => "Construcción De Relación",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Legal",
        "COMPETENCIA" => "Construcción de relaciones de negocio: Comprende el funcionamiento de los negocios, analiza los cambios del entorno para aprovechar las oportunidades del mercado.",
        "SUBCOMPETENCIA" => "Pensamiento Estratégico",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Legal",
        "COMPETENCIA" => "Habilidad analítica: Capacidad de razonamiento y organización cognitivamente del trabajo para realizar un análisis lógico identificando problemas, información y datos significativos.",
        "SUBCOMPETENCIA" => "Análisis Lógico",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Legal",
        "COMPETENCIA" => "Habilidad analítica: Capacidad de razonamiento y organización cognitivamente del trabajo para realizar un análisis lógico identificando problemas, información y datos significativos.",
        "SUBCOMPETENCIA" => "Capacidad De Comprensión",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Legal",
        "COMPETENCIA" => "Habilidad analítica: Capacidad de razonamiento y organización cognitivamente del trabajo para realizar un análisis lógico identificando problemas, información y datos significativos.",
        "SUBCOMPETENCIA" => "Identificación De Problemas",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Legal",
        "COMPETENCIA" => "Habilidad analítica: Capacidad de razonamiento y organización cognitivamente del trabajo para realizar un análisis lógico identificando problemas, información y datos significativos.",
        "SUBCOMPETENCIA" => "Tratamiento De La Información",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Legal",
        "COMPETENCIA" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "SUBCOMPETENCIA" => "Alineamiento",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Legal",
        "COMPETENCIA" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "SUBCOMPETENCIA" => "Asumo Las Consecuencias",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Legal",
        "COMPETENCIA" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "SUBCOMPETENCIA" => "Convicción",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Legal",
        "COMPETENCIA" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "SUBCOMPETENCIA" => "Cumplimiento De Acuerdos",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Legal",
        "COMPETENCIA" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "SUBCOMPETENCIA" => "Dirección De Equipos",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Legal",
        "COMPETENCIA" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "SUBCOMPETENCIA" => "Proactividad",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Legal",
        "COMPETENCIA" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "SUBCOMPETENCIA" => "Resolución De Conflictos",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Legal",
        "COMPETENCIA" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "SUBCOMPETENCIA" => "Resultados",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Legal",
        "COMPETENCIA" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "SUBCOMPETENCIA" => "Solución De Problema",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Legal",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Asumo Las Consecuencias",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Legal",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Cumplimiento De Acuerdos",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Legal",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Proactividad",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Legal",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Resultados",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Legal",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Solución De Problema",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Legal",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Amabilidad Y Cercanía",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Legal",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Amabilidad Y Cercanía (seguridad)",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Legal",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Amabilidad Y Cercanía Con El Colaborador",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Legal",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Atención Efectiva A Consultas",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Legal",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Atención Efectiva A Consultas (seguridad)",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Legal",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Atención Presencial Oportuna",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Legal",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Atención Presencial Oportuna (seguridad)",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Legal",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Seguimiento A La Atención",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Legal",
        "COMPETENCIA" => "Servicio al colaborador: Resuelve las consultas y solicitudes de los colaboradores con empatía y amabilidad.",
        "SUBCOMPETENCIA" => "Amabilidad Y Cercanía Con El Colaborador",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Legal",
        "COMPETENCIA" => "Servicio al colaborador: Resuelve las consultas y solicitudes de los colaboradores con empatía y amabilidad.",
        "SUBCOMPETENCIA" => "Atención Efectiva A Consultas De Colaboradores",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Legal",
        "COMPETENCIA" => "Servicio al colaborador: Resuelve las consultas y solicitudes de los colaboradores con empatía y amabilidad.",
        "SUBCOMPETENCIA" => "Atención Oportuna",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Legal",
        "COMPETENCIA" => "Servicio al colaborador: Resuelve las consultas y solicitudes de los colaboradores con empatía y amabilidad.",
        "SUBCOMPETENCIA" => "Empatía",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Marketing",
        "COMPETENCIA" => "Gestión de Marketing: Define las estrategias de marketing, analiza el funcionamiento del mercado para definir las acciones adecuadas.",
        "SUBCOMPETENCIA" => "Análisis De Mercado",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Marketing",
        "COMPETENCIA" => "Gestión de Marketing: Define las estrategias de marketing, analiza el funcionamiento del mercado para definir las acciones adecuadas.",
        "SUBCOMPETENCIA" => "Definición De Estrategias Y Objetivos",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Marketing",
        "COMPETENCIA" => "Gestión de Marketing: Define las estrategias de marketing, analiza el funcionamiento del mercado para definir las acciones adecuadas.",
        "SUBCOMPETENCIA" => "Definición Del Precio De Venta",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Marketing",
        "COMPETENCIA" => "Gestión de Marketing: Define las estrategias de marketing, analiza el funcionamiento del mercado para definir las acciones adecuadas.",
        "SUBCOMPETENCIA" => "Evaluación De La Demanda",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Marketing",
        "COMPETENCIA" => "Gestión de Presupuesto: Define el presupuesto de inversión adecuado considerando los costos y la rentabilidad del negocio.",
        "SUBCOMPETENCIA" => "Ajustes Comerciales",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Marketing",
        "COMPETENCIA" => "Gestión de Presupuesto: Define el presupuesto de inversión adecuado considerando los costos y la rentabilidad del negocio.",
        "SUBCOMPETENCIA" => "Control Y Monitoreo Comercial",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Marketing",
        "COMPETENCIA" => "Gestión de Presupuesto: Define el presupuesto de inversión adecuado considerando los costos y la rentabilidad del negocio.",
        "SUBCOMPETENCIA" => "Definición Presupuestal",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Marketing",
        "COMPETENCIA" => "Gestión de Presupuesto: Define el presupuesto de inversión adecuado considerando los costos y la rentabilidad del negocio.",
        "SUBCOMPETENCIA" => "Evaluación De Estructura De Costos Comerciales",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Marketing",
        "COMPETENCIA" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "SUBCOMPETENCIA" => "Alineamiento",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Marketing",
        "COMPETENCIA" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "SUBCOMPETENCIA" => "Asumo Las Consecuencias",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Marketing",
        "COMPETENCIA" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "SUBCOMPETENCIA" => "Convicción",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Marketing",
        "COMPETENCIA" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "SUBCOMPETENCIA" => "Cumplimiento De Acuerdos",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Marketing",
        "COMPETENCIA" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "SUBCOMPETENCIA" => "Dirección De Equipos",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Marketing",
        "COMPETENCIA" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "SUBCOMPETENCIA" => "Proactividad",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Marketing",
        "COMPETENCIA" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "SUBCOMPETENCIA" => "Resolución De Conflictos",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Marketing",
        "COMPETENCIA" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "SUBCOMPETENCIA" => "Resultados",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Marketing",
        "COMPETENCIA" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "SUBCOMPETENCIA" => "Solución De Problema",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Marketing",
        "COMPETENCIA" => "Planificación: Determinar las metas y prioridades de los proyectos, estableciendo las acciones, los plazos y los recursos necesarios. Así como los instrumentos de medición y segumiento.",
        "SUBCOMPETENCIA" => "Creación De Planes",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Marketing",
        "COMPETENCIA" => "Planificación: Determinar las metas y prioridades de los proyectos, estableciendo las acciones, los plazos y los recursos necesarios. Así como los instrumentos de medición y segumiento.",
        "SUBCOMPETENCIA" => "Definir Metas E Indciadores",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Marketing",
        "COMPETENCIA" => "Planificación: Determinar las metas y prioridades de los proyectos, estableciendo las acciones, los plazos y los recursos necesarios. Así como los instrumentos de medición y segumiento.",
        "SUBCOMPETENCIA" => "Definir Metas e indicadores",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Marketing",
        "COMPETENCIA" => "Planificación: Determinar las metas y prioridades de los proyectos, estableciendo las acciones, los plazos y los recursos necesarios. Así como los instrumentos de medición y segumiento.",
        "SUBCOMPETENCIA" => "Planificación De Los Recursos",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Marketing",
        "COMPETENCIA" => "Planificación: Determinar las metas y prioridades de los proyectos, estableciendo las acciones, los plazos y los recursos necesarios. Así como los instrumentos de medición y segumiento.",
        "SUBCOMPETENCIA" => "Priorización",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Operaciones Y Mantenimiento",
        "COMPETENCIA" => "Control del Mantenimiento: Soluciona los problemas de manera efectiva. Evalúa el rendimiento de mantenimiento e identifica oportunidades de reducción de costos.",
        "SUBCOMPETENCIA" => "Control De Costos",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Operaciones Y Mantenimiento",
        "COMPETENCIA" => "Control del Mantenimiento: Soluciona los problemas de manera efectiva. Evalúa el rendimiento de mantenimiento e identifica oportunidades de reducción de costos.",
        "SUBCOMPETENCIA" => "Evaluación De Indicadores",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Operaciones Y Mantenimiento",
        "COMPETENCIA" => "Control del Mantenimiento: Soluciona los problemas de manera efectiva. Evalúa el rendimiento de mantenimiento e identifica oportunidades de reducción de costos.",
        "SUBCOMPETENCIA" => "Solución De Problemas",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Operaciones Y Mantenimiento",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Control",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Operaciones Y Mantenimiento",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Gestión De Consecuencias",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Operaciones Y Mantenimiento",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Retroalimentación",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Operaciones Y Mantenimiento",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Seguimiento",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Operaciones Y Mantenimiento",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Supervisión",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Operaciones Y Mantenimiento",
        "COMPETENCIA" => "Gestión de normas y procedimientos: Se preocupa y promueve respeto de las normas y procedimientos. Vigila el cumplimiento de las normas e interviene para corregir faltas.",
        "SUBCOMPETENCIA" => "Claridad En La Orientación",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Operaciones Y Mantenimiento",
        "COMPETENCIA" => "Gestión de normas y procedimientos: Se preocupa y promueve respeto de las normas y procedimientos. Vigila el cumplimiento de las normas e interviene para corregir faltas.",
        "SUBCOMPETENCIA" => "Gestión De Consecuencias V1",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Operaciones Y Mantenimiento",
        "COMPETENCIA" => "Gestión de normas y procedimientos: Se preocupa y promueve respeto de las normas y procedimientos. Vigila el cumplimiento de las normas e interviene para corregir faltas.",
        "SUBCOMPETENCIA" => "Respeto A La Norma",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Operaciones Y Mantenimiento",
        "COMPETENCIA" => "Gestión de normas y procedimientos: Se preocupa y promueve respeto de las normas y procedimientos. Vigila el cumplimiento de las normas e interviene para corregir faltas.",
        "SUBCOMPETENCIA" => "Revisión Y Seguimiento",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Operaciones Y Mantenimiento",
        "COMPETENCIA" => "Supervisión Efectiva: Supervisar el desempeño de los colaboradores brindando retrolimentación a partir del mismo y gestionar los resultados obtenidos.",
        "SUBCOMPETENCIA" => "Gestión De Consecuencias Del Desempeño",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Operaciones Y Mantenimiento",
        "COMPETENCIA" => "Supervisión Efectiva: Supervisar el desempeño de los colaboradores brindando retrolimentación a partir del mismo y gestionar los resultados obtenidos.",
        "SUBCOMPETENCIA" => "Retroalimentación Sobre El Proceso",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Operaciones Y Mantenimiento",
        "COMPETENCIA" => "Supervisión Efectiva: Supervisar el desempeño de los colaboradores brindando retrolimentación a partir del mismo y gestionar los resultados obtenidos.",
        "SUBCOMPETENCIA" => "Seguimiento Efectivo",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Taller",
        "COMPETENCIA" => "Calidad de Servicio: Supervisa la calidad de los servicios y la satisfacción de los clientes. Interviene para que la calidad y satisfacción se mantengan dentro de lo esperado.",
        "SUBCOMPETENCIA" => "Supervisión del servicio",
        "DESCRIPCION" => "Supervisa que los servicios y lo estándares de calidad se mantegan en niveles óptimos."
      ],
      [
        "CATEGORIA" => "Jefe De Taller",
        "COMPETENCIA" => "Calidad de Servicio: Supervisa la calidad de los servicios y la satisfacción de los clientes. Interviene para que la calidad y satisfacción se mantengan dentro de lo esperado.",
        "SUBCOMPETENCIA" => "Coordinación efectiva",
        "DESCRIPCION" => "Supervisa que todas las áreas involucradas en un servicio estén coordinando de manera adecuada."
      ],
      [
        "CATEGORIA" => "Jefe De Taller",
        "COMPETENCIA" => "Calidad de Servicio: Supervisa la calidad de los servicios y la satisfacción de los clientes. Interviene para que la calidad y satisfacción se mantengan dentro de lo esperado.",
        "SUBCOMPETENCIA" => "Monitoreo de Satisfacción",
        "DESCRIPCION" => "Monitorea los indicadores de Satisfacción del cliente."
      ],
      [
        "CATEGORIA" => "Jefe De Taller",
        "COMPETENCIA" => "Calidad de Servicio: Supervisa la calidad de los servicios y la satisfacción de los clientes. Interviene para que la calidad y satisfacción se mantengan dentro de lo esperado.",
        "SUBCOMPETENCIA" => "Reporte de Calidad",
        "DESCRIPCION" => "Presenta informes técnicos sobre los servicios realizados."
      ],
      [
        "CATEGORIA" => "Jefe De Taller",
        "COMPETENCIA" => "Calidad de Servicio: Supervisa la calidad de los servicios y la satisfacción de los clientes. Interviene para que la calidad y satisfacción se mantengan dentro de lo esperado.",
        "SUBCOMPETENCIA" => "Gestión de Consecuencias",
        "DESCRIPCION" => "Interviene de manera oportuna a las acciones que no están alineadas a un servicio al cliente adecuado."
      ],
      [
        "CATEGORIA" => "Jefe De Taller",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Claridad en el mensaje",
        "DESCRIPCION" => "Comunica sus ideas de forma clara y precisa."
      ],
      [
        "CATEGORIA" => "Jefe De Taller",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Respuesta Oportuna",
        "DESCRIPCION" => "Comparte información e ideas en el momento adecuado."
      ],
      [
        "CATEGORIA" => "Jefe De Taller",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Escucha",
        "DESCRIPCION" => "Escucha atentamente las opiniones y sugerencias."
      ],
      [
        "CATEGORIA" => "Jefe De Taller",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Asertividad",
        "DESCRIPCION" => "Expresa sus opiniones sin herir los sentimientos de los demás."
      ],
      [
        "CATEGORIA" => "Jefe De Taller",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Supervisión",
        "DESCRIPCION" => "Supervisa los avances, entregables y acciones del equipo para el lograr los objetivos."
      ],
      [
        "CATEGORIA" => "Jefe De Taller",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Seguimiento",
        "DESCRIPCION" => "Realiza seguimiento a los avances de su equipo."
      ],
      [
        "CATEGORIA" => "Jefe De Taller",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Retroalimentación",
        "DESCRIPCION" => "Brinda retroalimentación de manera oportuna sobre los resultados individuales"
      ],
      [
        "CATEGORIA" => "Jefe De Taller",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Control",
        "DESCRIPCION" => "Controla los avances del equipo a fin de intervenir de manera oportuna."
      ],
      [
        "CATEGORIA" => "Jefe De Taller",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Gestión de Consecuencias",
        "DESCRIPCION" => "Felicita o corrige las acciones de su equipo."
      ],
      [
        "CATEGORIA" => "Jefe De Taller",
        "COMPETENCIA" => "Operación Efectiva: Garantiza una operación efectiva y rentable desde la llegada hasta la entrega del vehículo.",
        "SUBCOMPETENCIA" => "Cuidado del Producto",
        "DESCRIPCION" => "Asegura la entrega del vehículo en óptimas condiciones."
      ],
      [
        "CATEGORIA" => "Jefe De Taller",
        "COMPETENCIA" => "Operación Efectiva: Garantiza una operación efectiva y rentable desde la llegada hasta la entrega del vehículo.",
        "SUBCOMPETENCIA" => "Rentabilidad",
        "DESCRIPCION" => "Define las acciones necesarias para rentabilizar los metros cuadrados disponibles."
      ],
      [
        "CATEGORIA" => "Jefe De Taller",
        "COMPETENCIA" => "Operación Efectiva: Garantiza una operación efectiva y rentable desde la llegada hasta la entrega del vehículo.",
        "SUBCOMPETENCIA" => "Control de Calidad",
        "DESCRIPCION" => "Realiza de manera adecuada el control de calidad en la entrega del servicio."
      ],
      [
        "CATEGORIA" => "Jefe De Taller",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Atención Efectiva a consultas",
        "DESCRIPCION" => "Responde oportunamente a las solicitudes y consultas de los clientes vía presencial, telefónica y por correo electrónico."
      ],
      [
        "CATEGORIA" => "Jefe De Taller",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Seguimiento a la atención",
        "DESCRIPCION" => "Vigilo la adecuada atención del cliente y pido al responsable a que se haga cargo."
      ],
      [
        "CATEGORIA" => "Jefe De Taller",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Atención presencial oportuna",
        "DESCRIPCION" => "Hago todo lo necesario para que el cliente sea bien atendido en el menor tiempo posible."
      ],
      [
        "CATEGORIA" => "Jefe De Taller",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Amabilidad y cercanía",
        "DESCRIPCION" => "Trata al cliente con amabilidad generando cercanía y confianza."
      ],
      [
        "CATEGORIA" => "Jefe De TICs",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Claridad en el mensaje",
        "DESCRIPCION" => "Comunica sus ideas de forma clara y precisa."
      ],
      [
        "CATEGORIA" => "Jefe De TICs",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Respuesta Oportuna",
        "DESCRIPCION" => "Comparte información e ideas en el momento adecuado."
      ],
      [
        "CATEGORIA" => "Jefe De TICs",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Escucha",
        "DESCRIPCION" => "Escucha atentamente las opiniones y sugerencias."
      ],
      [
        "CATEGORIA" => "Jefe De TICs",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Asertividad",
        "DESCRIPCION" => "Expresa sus opiniones sin herir los sentimientos de los demás."
      ],
      [
        "CATEGORIA" => "Jefe De TICs",
        "COMPETENCIA" => "Gestión Tecnológica: Garantiza el adecuado funcionamiento de las herramientas tecnológicas.",
        "SUBCOMPETENCIA" => "Métodos",
        "DESCRIPCION" => "Define de manera adecuada los métodos de desarrollo de productos tecnológicos."
      ],
      [
        "CATEGORIA" => "Jefe De TICs",
        "COMPETENCIA" => "Gestión Tecnológica: Garantiza el adecuado funcionamiento de las herramientas tecnológicas.",
        "SUBCOMPETENCIA" => "Diagnóstico de problemáticas",
        "DESCRIPCION" => "Identifica de manera efectiva la problemática en las herramientas tecnológicas."
      ],
      [
        "CATEGORIA" => "Jefe De TICs",
        "COMPETENCIA" => "Gestión Tecnológica: Garantiza el adecuado funcionamiento de las herramientas tecnológicas.",
        "SUBCOMPETENCIA" => "Evaluación de Sistemas",
        "DESCRIPCION" => "Evalúa los sistemas y procesos de manera adecuada."
      ],
      [
        "CATEGORIA" => "Jefe De TICs",
        "COMPETENCIA" => "Gestión Tecnológica: Garantiza el adecuado funcionamiento de las herramientas tecnológicas.",
        "SUBCOMPETENCIA" => "Definición de acciones",
        "DESCRIPCION" => "Define las acciones adecuadas para la mejora de las herramientas tecnológicas."
      ],
      [
        "CATEGORIA" => "Jefe De TICs",
        "COMPETENCIA" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "SUBCOMPETENCIA" => "Dirección de Equipos",
        "DESCRIPCION" => "Dirige al equipo al logro de resultados de manera efectiva."
      ],
      [
        "CATEGORIA" => "Jefe De TICs",
        "COMPETENCIA" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "SUBCOMPETENCIA" => "Resolución de Conflictos",
        "DESCRIPCION" => "Destraba o resuelve los conflictos internos que impactan en el desempeño del negocio."
      ],
      [
        "CATEGORIA" => "Jefe De TICs",
        "COMPETENCIA" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "SUBCOMPETENCIA" => "Convicción",
        "DESCRIPCION" => "Transmite credibilidad y convicción a los demás."
      ],
      [
        "CATEGORIA" => "Jefe De TICs",
        "COMPETENCIA" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "SUBCOMPETENCIA" => "Alineamiento",
        "DESCRIPCION" => "Define con claridad los roles y responsabilidades del equipo."
      ],
      [
        "CATEGORIA" => "Jefe De TICs",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Proactividad",
        "DESCRIPCION" => "Se anticipa a los requerimientos o necesidades que se presentan."
      ],
      [
        "CATEGORIA" => "Jefe De TICs",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Solución de Problemas",
        "DESCRIPCION" => "Resuelve los problemas a pesar de las dificultades."
      ],
      [
        "CATEGORIA" => "Jefe De TICs",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Cumplimiento de Acuerdos",
        "DESCRIPCION" => "Cumple con los acuerdos de la organización."
      ],
      [
        "CATEGORIA" => "Jefe De TICs",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Resultados",
        "DESCRIPCION" => "Logra los resultados que se le asignan."
      ],
      [
        "CATEGORIA" => "Jefe De TICs",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Asumo las consecuencias",
        "DESCRIPCION" => "Se hace cargo de los errores que realiza y los resuelve. No espera a que otros lo solucionen."
      ],
      [
        "CATEGORIA" => "Jefe De TICs",
        "COMPETENCIA" => "Planificación: Determinar las metas y prioridades de los proyectos, estableciendo las acciones, los plazos y los recursos necesarios. Así como los instrumentos de medición y segumiento.",
        "SUBCOMPETENCIA" => "Creación de Planes",
        "DESCRIPCION" => "Crea planes o proyectos que generan valor a la organización."
      ],
      [
        "CATEGORIA" => "Jefe De TICs",
        "COMPETENCIA" => "Planificación: Determinar las metas y prioridades de los proyectos, estableciendo las acciones, los plazos y los recursos necesarios. Así como los instrumentos de medición y segumiento.",
        "SUBCOMPETENCIA" => "Priorización",
        "DESCRIPCION" => "Establece el orden de las acciones que tiene un plan o proyecto de acuerdo a criterios establecidos."
      ],
      [
        "CATEGORIA" => "Jefe De TICs",
        "COMPETENCIA" => "Planificación: Determinar las metas y prioridades de los proyectos, estableciendo las acciones, los plazos y los recursos necesarios. Así como los instrumentos de medición y segumiento.",
        "SUBCOMPETENCIA" => "Definir metas e indciadores",
        "DESCRIPCION" => "Define las metas e indicadores de éxito de las tareas o proyectos asignados."
      ],
      [
        "CATEGORIA" => "Jefe De TICs",
        "COMPETENCIA" => "Planificación: Determinar las metas y prioridades de los proyectos, estableciendo las acciones, los plazos y los recursos necesarios. Así como los instrumentos de medición y segumiento.",
        "SUBCOMPETENCIA" => "Gestión de Recursos",
        "DESCRIPCION" => "Identifica y asigna los recursos de manera responsable."
      ],
      [
        "CATEGORIA" => "Jefe de Ventas - Camiones",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Asertividad",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe de Ventas - Camiones",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Claridad en el mensaje",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe de Ventas - Camiones",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Escucha",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe de Ventas - Camiones",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Informe Escrito",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe de Ventas - Camiones",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Orientación A La Meta Común",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe de Ventas - Camiones",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Respuesta Oportuna",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe de Ventas - Camiones",
        "COMPETENCIA" => "Gestión Comercial - Versión 2 AP: Propone las estrategias comerciales, objetivos y metas de acuerdo al conocimiento del negocio, producto y el funcionamiento de la competencia.",
        "SUBCOMPETENCIA" => "Conocimiento De La Competencia",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe de Ventas - Camiones",
        "COMPETENCIA" => "Gestión Comercial - Versión 2 AP: Propone las estrategias comerciales, objetivos y metas de acuerdo al conocimiento del negocio, producto y el funcionamiento de la competencia.",
        "SUBCOMPETENCIA" => "Conocimiento Del Negocio",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe de Ventas - Camiones",
        "COMPETENCIA" => "Gestión Comercial - Versión 2 AP: Propone las estrategias comerciales, objetivos y metas de acuerdo al conocimiento del negocio, producto y el funcionamiento de la competencia.",
        "SUBCOMPETENCIA" => "Propone Estrategias, Objetivos Y Metas",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe de Ventas - Camiones",
        "COMPETENCIA" => "Gestión Comercial - Versión 2 AP: Propone las estrategias comerciales, objetivos y metas de acuerdo al conocimiento del negocio, producto y el funcionamiento de la competencia.",
        "SUBCOMPETENCIA" => "Reajustes Comerciales",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe de Ventas - Camiones",
        "COMPETENCIA" => "Gestión de Clientes Relevantes: Fideliza clientes relevantes del negocio y resuelve sus problemas. Cuenta con un profundo conocimiento del cliente.",
        "SUBCOMPETENCIA" => "Conocimiento Del Cliente",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe de Ventas - Camiones",
        "COMPETENCIA" => "Gestión de Clientes Relevantes: Fideliza clientes relevantes del negocio y resuelve sus problemas. Cuenta con un profundo conocimiento del cliente.",
        "SUBCOMPETENCIA" => "Fidelización Con El Cliente",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe de Ventas - Camiones",
        "COMPETENCIA" => "Gestión de Clientes Relevantes: Fideliza clientes relevantes del negocio y resuelve sus problemas. Cuenta con un profundo conocimiento del cliente.",
        "SUBCOMPETENCIA" => "Manejo De Clientes Díficiles",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe de Ventas - Camiones",
        "COMPETENCIA" => "Gestión de Clientes Relevantes: Fideliza clientes relevantes del negocio y resuelve sus problemas. Cuenta con un profundo conocimiento del cliente.",
        "SUBCOMPETENCIA" => "Relación Con El Cliente",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe de Ventas - Camiones",
        "COMPETENCIA" => "Gestión de Clientes Relevantes: Fideliza clientes relevantes del negocio y resuelve sus problemas. Cuenta con un profundo conocimiento del cliente.",
        "SUBCOMPETENCIA" => "Resolución De Problemas",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe de Ventas - Camiones",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Control",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe de Ventas - Camiones",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Gestión De Consecuencias",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe de Ventas - Camiones",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Retroalimentación",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe de Ventas - Camiones",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Seguimiento",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe de Ventas - Camiones",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Supervisión",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe de Ventas - Camiones",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Asumo Las Consecuencias",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe de Ventas - Camiones",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Cumplimiento De Acuerdos",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe de Ventas - Camiones",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Proactividad",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe de Ventas - Camiones",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Resultados",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe de Ventas - Camiones",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Solución De Problema",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Jefe De Ventas Ap",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Claridad en el mensaje",
        "DESCRIPCION" => "Comunica sus ideas de forma clara y precisa."
      ],
      [
        "CATEGORIA" => "Jefe De Ventas Ap",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Respuesta Oportuna",
        "DESCRIPCION" => "Comparte información e ideas en el momento adecuado."
      ],
      [
        "CATEGORIA" => "Jefe De Ventas Ap",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Escucha",
        "DESCRIPCION" => "Escucha atentamente las opiniones y sugerencias."
      ],
      [
        "CATEGORIA" => "Jefe De Ventas Ap",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Asertividad",
        "DESCRIPCION" => "Expresa sus opiniones sin herir los sentimientos de los demás."
      ],
      [
        "CATEGORIA" => "Jefe De Ventas Ap",
        "COMPETENCIA" => "Gestión Comercial - Versión 2 AP: Propone las estrategias comerciales, objetivos y metas de acuerdo al conocimiento del negocio, producto y el funcionamiento de la competencia.",
        "SUBCOMPETENCIA" => "Conocimiento del Negocio",
        "DESCRIPCION" => "Conoce a profundidad las características del negocio y de los productos."
      ],
      [
        "CATEGORIA" => "Jefe De Ventas Ap",
        "COMPETENCIA" => "Gestión Comercial - Versión 2 AP: Propone las estrategias comerciales, objetivos y metas de acuerdo al conocimiento del negocio, producto y el funcionamiento de la competencia.",
        "SUBCOMPETENCIA" => "Conocimiento de la Competencia",
        "DESCRIPCION" => "Se mantiene actualizado sobre las estrategias y novedades de la competencia."
      ],
      [
        "CATEGORIA" => "Jefe De Ventas Ap",
        "COMPETENCIA" => "Gestión Comercial - Versión 2 AP: Propone las estrategias comerciales, objetivos y metas de acuerdo al conocimiento del negocio, producto y el funcionamiento de la competencia.",
        "SUBCOMPETENCIA" => "Reajustes Comerciales",
        "DESCRIPCION" => "Identifica nuevas alternativas de acuerdo al análisis de la competencia y necesidades de los clientes."
      ],
      [
        "CATEGORIA" => "Jefe De Ventas Ap",
        "COMPETENCIA" => "Gestión Comercial - Versión 2 AP: Propone las estrategias comerciales, objetivos y metas de acuerdo al conocimiento del negocio, producto y el funcionamiento de la competencia.",
        "SUBCOMPETENCIA" => "Propone Estrategias, Objetivos y metas",
        "DESCRIPCION" => "Propone de manera adecuada las estrategias comerciales, objetivos y metas comerciales."
      ],
      [
        "CATEGORIA" => "Jefe De Ventas Ap",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Supervisión",
        "DESCRIPCION" => "Supervisa los avances, entregables y acciones del equipo para el lograr los objetivos."
      ],
      [
        "CATEGORIA" => "Jefe De Ventas Ap",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Seguimiento",
        "DESCRIPCION" => "Realiza seguimiento a los avances de su equipo."
      ],
      [
        "CATEGORIA" => "Jefe De Ventas Ap",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Retroalimentación",
        "DESCRIPCION" => "Brinda retroalimentación de manera oportuna sobre los resultados individuales"
      ],
      [
        "CATEGORIA" => "Jefe De Ventas Ap",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Control",
        "DESCRIPCION" => "Controla los avances del equipo a fin de intervenir de manera oportuna."
      ],
      [
        "CATEGORIA" => "Jefe De Ventas Ap",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Gestión de Consecuencias",
        "DESCRIPCION" => "Felicita o corrige las acciones de su equipo."
      ],
      [
        "CATEGORIA" => "Jefe De Ventas Ap",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Proactividad",
        "DESCRIPCION" => "Se anticipa a los requerimientos o necesidades que se presentan."
      ],
      [
        "CATEGORIA" => "Jefe De Ventas Ap",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Solución de Problemas",
        "DESCRIPCION" => "Resuelve los problemas a pesar de las dificultades."
      ],
      [
        "CATEGORIA" => "Jefe De Ventas Ap",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Cumplimiento de Acuerdos",
        "DESCRIPCION" => "Cumple con los acuerdos de la organización."
      ],
      [
        "CATEGORIA" => "Jefe De Ventas Ap",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Resultados",
        "DESCRIPCION" => "Logra los resultados que se le asignan."
      ],
      [
        "CATEGORIA" => "Jefe De Ventas Ap",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Asumo las consecuencias",
        "DESCRIPCION" => "Me hago cargo de los errores que realizo y los resuelvo. No espero a que otros lo solucionen."
      ],
      [
        "CATEGORIA" => "Jefe De Ventas Ap",
        "COMPETENCIA" => "Gestión de Clientes Relevantes  - Versión 2 AP",
        "SUBCOMPETENCIA" => "Conocimiento del Cliente",
        "DESCRIPCION" => "Tiene un profundo conocimiento del negocio del cliente y sus productos."
      ],
      [
        "CATEGORIA" => "Jefe De Ventas Ap",
        "COMPETENCIA" => "Gestión de Clientes Relevantes  - Versión 2 AP",
        "SUBCOMPETENCIA" => "Resolución de Problemas",
        "DESCRIPCION" => "Resuelve los problemas tantos simples como complejos que tiene el cliente."
      ],
      [
        "CATEGORIA" => "Jefe De Ventas Ap",
        "COMPETENCIA" => "Gestión de Clientes Relevantes  - Versión 2 AP",
        "SUBCOMPETENCIA" => "Fidelización con el Cliente",
        "DESCRIPCION" => "Establece acciones que generan valor al cliente o se anticipa a los problemas que puede tener el mismo."
      ],
      [
        "CATEGORIA" => "Jefe De Ventas Ap",
        "COMPETENCIA" => "Gestión de Clientes Relevantes  - Versión 2 AP",
        "SUBCOMPETENCIA" => "Relación con el Cliente",
        "DESCRIPCION" => "Realiza acciones para mantener una relación cercana y de confianza con el cliente."
      ],
      [
        "CATEGORIA" => "Jefe De Ventas Ap",
        "COMPETENCIA" => "Gestión de Clientes Relevantes  - Versión 2 AP",
        "SUBCOMPETENCIA" => "Manejo de Clientes Díficiles",
        "DESCRIPCION" => "Interviene ante reclamos u objeciones de clientes complejos de lidiar."
      ],
      [
        "CATEGORIA" => "Jefe De Ventas Dp",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Claridad en el mensaje",
        "DESCRIPCION" => "Comunica sus ideas de forma clara y precisa."
      ],
      [
        "CATEGORIA" => "Jefe De Ventas Dp",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Respuesta Oportuna",
        "DESCRIPCION" => "Comparte información e ideas en el momento adecuado."
      ],
      [
        "CATEGORIA" => "Jefe De Ventas Dp",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Escucha",
        "DESCRIPCION" => "Escucha atentamente las opiniones y sugerencias."
      ],
      [
        "CATEGORIA" => "Jefe De Ventas Dp",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Asertividad",
        "DESCRIPCION" => "Expresa sus opiniones sin herir los sentimientos de los demás."
      ],
      [
        "CATEGORIA" => "Jefe De Ventas Dp",
        "COMPETENCIA" => "Gestión Comercial: Define las estrategias comerciales, objetivos y metas de acuerdo al conocimiento del negocio, producto y el funcionamiento de la competencia.",
        "SUBCOMPETENCIA" => "Conocimiento del Negocio",
        "DESCRIPCION" => "Conoce a profundidad las características del negocio y de los productos."
      ],
      [
        "CATEGORIA" => "Jefe De Ventas Dp",
        "COMPETENCIA" => "Gestión Comercial: Define las estrategias comerciales, objetivos y metas de acuerdo al conocimiento del negocio, producto y el funcionamiento de la competencia.",
        "SUBCOMPETENCIA" => "Conocimiento de la Competencia",
        "DESCRIPCION" => "Se mantiene actualizado sobre las estrategias y novedades de la competencia."
      ],
      [
        "CATEGORIA" => "Jefe De Ventas Dp",
        "COMPETENCIA" => "Gestión Comercial: Define las estrategias comerciales, objetivos y metas de acuerdo al conocimiento del negocio, producto y el funcionamiento de la competencia.",
        "SUBCOMPETENCIA" => "Reajustes Comerciales",
        "DESCRIPCION" => "Identifica nuevas alternativas de acuerdo al análisis de la competencia y necesidades de los clientes."
      ],
      [
        "CATEGORIA" => "Jefe De Ventas Dp",
        "COMPETENCIA" => "Gestión Comercial: Define las estrategias comerciales, objetivos y metas de acuerdo al conocimiento del negocio, producto y el funcionamiento de la competencia.",
        "SUBCOMPETENCIA" => "Definición Estrategias, Objetivos y metas",
        "DESCRIPCION" => "Define de manera adecuada las estrategias comerciales, objetivos y metas comerciales."
      ],
      [
        "CATEGORIA" => "Jefe De Ventas Dp",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Supervisión",
        "DESCRIPCION" => "Supervisa los avances, entregables y acciones del equipo para el lograr los objetivos."
      ],
      [
        "CATEGORIA" => "Jefe De Ventas Dp",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Seguimiento",
        "DESCRIPCION" => "Realiza seguimiento a los avances de su equipo."
      ],
      [
        "CATEGORIA" => "Jefe De Ventas Dp",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Retroalimentación",
        "DESCRIPCION" => "Brinda retroalimentación de manera oportuna sobre los resultados individuales"
      ],
      [
        "CATEGORIA" => "Jefe De Ventas Dp",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Control",
        "DESCRIPCION" => "Controla los avances del equipo a fin de intervenir de manera oportuna."
      ],
      [
        "CATEGORIA" => "Jefe De Ventas Dp",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Gestión de Consecuencias",
        "DESCRIPCION" => "Felicita o corrige las acciones de su equipo."
      ],
      [
        "CATEGORIA" => "Jefe De Ventas Dp",
        "COMPETENCIA" => "Gestión de Proveedores - Comercial: Garantizar una adecuada relación con los proveedores. Lograr convencerlos para obtener beneficios preferenciales manejando de manera adecuada los reclamos.",
        "SUBCOMPETENCIA" => "Persuasión",
        "DESCRIPCION" => "Logra convencer al Proveedor de obtener precios preferenciales."
      ],
      [
        "CATEGORIA" => "Jefe De Ventas Dp",
        "COMPETENCIA" => "Gestión de Proveedores - Comercial: Garantizar una adecuada relación con los proveedores. Lograr convencerlos para obtener beneficios preferenciales manejando de manera adecuada los reclamos.",
        "SUBCOMPETENCIA" => "Traspase de información",
        "DESCRIPCION" => "Brinda la información que le corresponde a la unidad de Administración de manera completa y oportuna."
      ],
      [
        "CATEGORIA" => "Jefe De Ventas Dp",
        "COMPETENCIA" => "Gestión de Proveedores - Comercial: Garantizar una adecuada relación con los proveedores. Lograr convencerlos para obtener beneficios preferenciales manejando de manera adecuada los reclamos.",
        "SUBCOMPETENCIA" => "Seguimiento de cobranzas",
        "DESCRIPCION" => "Interviene ante la tardanza de los proveedores por el no pago de las notas de crédito."
      ],
      [
        "CATEGORIA" => "Jefe De Ventas Dp",
        "COMPETENCIA" => "Gestión de Proveedores - Comercial: Garantizar una adecuada relación con los proveedores. Lograr convencerlos para obtener beneficios preferenciales manejando de manera adecuada los reclamos.",
        "SUBCOMPETENCIA" => "Relación con el Proveedor",
        "DESCRIPCION" => "Realiza acciones para mantener una relación cercana y de confianza con el proveedor."
      ],
      [
        "CATEGORIA" => "Jefe De Ventas Dp",
        "COMPETENCIA" => "Gestión de Proveedores - Comercial: Garantizar una adecuada relación con los proveedores. Lograr convencerlos para obtener beneficios preferenciales manejando de manera adecuada los reclamos.",
        "SUBCOMPETENCIA" => "Solución de Problemas",
        "DESCRIPCION" => "Muestra una actitud colaborativa y propone soluciones ante las problemáticas que surgan con los proveedores."
      ],
      [
        "CATEGORIA" => "Jefe De Ventas Dp",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Proactividad",
        "DESCRIPCION" => "Se anticipa a los requerimientos o necesidades que se presentan."
      ],
      [
        "CATEGORIA" => "Jefe De Ventas Dp",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Solución de Problemas",
        "DESCRIPCION" => "Resuelve los problemas a pesar de las dificultades."
      ],
      [
        "CATEGORIA" => "Jefe De Ventas Dp",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Cumplimiento de Acuerdos",
        "DESCRIPCION" => "Cumple con los acuerdos de la organización."
      ],
      [
        "CATEGORIA" => "Jefe De Ventas Dp",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Resultados",
        "DESCRIPCION" => "Logra los resultados que se le asignan."
      ],
      [
        "CATEGORIA" => "Jefe De Ventas Dp",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Asumo las consecuencias",
        "DESCRIPCION" => "Se hace cargo de los errores que realiza y los resuelve. No espera a que otros lo solucionen"
      ],
      [
        "CATEGORIA" => "Maestro Mecanico",
        "COMPETENCIA" => "Cooperación Operativa: Coopera de manera activa con las necesidades del equipo y unidades con las que interactúa. Respeta los acuerdos generando un espacio colaborativo y de confianza.",
        "SUBCOMPETENCIA" => "Cooperación",
        "DESCRIPCION" => "Muestra predisposición y apoyo para hacer trabajos operativos."
      ],
      [
        "CATEGORIA" => "Maestro Mecanico",
        "COMPETENCIA" => "Cooperación Operativa: Coopera de manera activa con las necesidades del equipo y unidades con las que interactúa. Respeta los acuerdos generando un espacio colaborativo y de confianza.",
        "SUBCOMPETENCIA" => "Orientación a la meta común",
        "DESCRIPCION" => "Apoya a los demás para realizar un entregable de manera rápida."
      ],
      [
        "CATEGORIA" => "Maestro Mecanico",
        "COMPETENCIA" => "Cooperación Operativa: Coopera de manera activa con las necesidades del equipo y unidades con las que interactúa. Respeta los acuerdos generando un espacio colaborativo y de confianza.",
        "SUBCOMPETENCIA" => "Relaciones interpersonales",
        "DESCRIPCION" => "Tiene buenas relaciones con los demás y genera confianza."
      ],
      [
        "CATEGORIA" => "Maestro Mecanico",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Gestión de Recursos",
        "DESCRIPCION" => "Identifica y asigna los recursos de manera responsable."
      ],
      [
        "CATEGORIA" => "Maestro Mecanico",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Metodología",
        "DESCRIPCION" => "Utiliza metodologías o procesos estandarizados que garantizan los resultados esperados."
      ],
      [
        "CATEGORIA" => "Maestro Mecanico",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Orientación al logro",
        "DESCRIPCION" => "Logra los resultados esperados en los tiempos establecidos."
      ],
      [
        "CATEGORIA" => "Maestro Mecanico",
        "COMPETENCIA" => "Habilidad para reparar: Soluciona las fallas técnicas con rapidez y efectividad, identifica las causas de manera oportuna y verifica el correcto funcionamiento una vez reparado.",
        "SUBCOMPETENCIA" => "Diagnóstico",
        "DESCRIPCION" => "Identifica rápidamente la causa de las fallas mecánicas, eléctricas, de soldadura o de neumáticos."
      ],
      [
        "CATEGORIA" => "Maestro Mecanico",
        "COMPETENCIA" => "Habilidad para reparar: Soluciona las fallas técnicas con rapidez y efectividad, identifica las causas de manera oportuna y verifica el correcto funcionamiento una vez reparado.",
        "SUBCOMPETENCIA" => "Reparación",
        "DESCRIPCION" => "Repara con rapidez las fallas mecánicas, eléctricas, de soldadura o de neumáticos sin perjudicar la calidad de lo reparado."
      ],
      [
        "CATEGORIA" => "Maestro Mecanico",
        "COMPETENCIA" => "Habilidad para reparar: Soluciona las fallas técnicas con rapidez y efectividad, identifica las causas de manera oportuna y verifica el correcto funcionamiento una vez reparado.",
        "SUBCOMPETENCIA" => "Contol de Calidad",
        "DESCRIPCION" => "Verifica el correcto funcionamiento de lo reparado antes de entregarlo."
      ],
      [
        "CATEGORIA" => "Maestro Mecanico",
        "COMPETENCIA" => "Organización y Control: Garantizar el uso de recursos a través de una adecuada organización, clasificación y control.",
        "SUBCOMPETENCIA" => "Control de Recursos",
        "DESCRIPCION" => "Controla la cantidad de recursos necesarios para realizar las tareas."
      ],
      [
        "CATEGORIA" => "Maestro Mecanico",
        "COMPETENCIA" => "Organización y Control: Garantizar el uso de recursos a través de una adecuada organización, clasificación y control.",
        "SUBCOMPETENCIA" => "Protección de Recursos",
        "DESCRIPCION" => "Cuida los recursos para mantener su calidad."
      ],
      [
        "CATEGORIA" => "Maestro Mecanico",
        "COMPETENCIA" => "Organización y Control: Garantizar el uso de recursos a través de una adecuada organización, clasificación y control.",
        "SUBCOMPETENCIA" => "Organización de Recursos",
        "DESCRIPCION" => "Clasifica los recursos con un orden claro y práctico."
      ],
      [
        "CATEGORIA" => "Maestro Mecanico",
        "COMPETENCIA" => "Supervisión Efectiva: Supervisar el desempeño de los colaboradores brindando retrolimentación a partir del mismo y gestionar los resultados obtenidos.",
        "SUBCOMPETENCIA" => "Seguimiento",
        "DESCRIPCION" => "Supervisa los avances, entregables y acciones de los colaboradores para lograr los objetivos."
      ],
      [
        "CATEGORIA" => "Maestro Mecanico",
        "COMPETENCIA" => "Supervisión Efectiva: Supervisar el desempeño de los colaboradores brindando retrolimentación a partir del mismo y gestionar los resultados obtenidos.",
        "SUBCOMPETENCIA" => "Retroalimentación",
        "DESCRIPCION" => "Brinda retroalimentación de manera oportuna sobre los resultados individuales."
      ],
      [
        "CATEGORIA" => "Maestro Mecanico",
        "COMPETENCIA" => "Supervisión Efectiva: Supervisar el desempeño de los colaboradores brindando retrolimentación a partir del mismo y gestionar los resultados obtenidos.",
        "SUBCOMPETENCIA" => "Gestión de Consecuencias",
        "DESCRIPCION" => "Felicita o corrige las acciones de los colaboradores de acuerdo al desempeño que tienen."
      ],
      [
        "CATEGORIA" => "Operador De Cctv",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Asertividad",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Operador De Cctv",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Claridad en el mensaje",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Operador De Cctv",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Escucha",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Operador De Cctv",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Informe Escrito",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Operador De Cctv",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Orientación A La Meta Común",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Operador De Cctv",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Respuesta Oportuna",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Operador De Cctv",
        "COMPETENCIA" => "Gestión de normas y procedimientos: Se preocupa y promueve respeto de las normas y procedimientos. Vigila el cumplimiento de las normas e interviene para corregir faltas.",
        "SUBCOMPETENCIA" => "Claridad En La Orientación",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Operador De Cctv",
        "COMPETENCIA" => "Gestión de normas y procedimientos: Se preocupa y promueve respeto de las normas y procedimientos. Vigila el cumplimiento de las normas e interviene para corregir faltas.",
        "SUBCOMPETENCIA" => "Gestión De Consecuencias V1",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Operador De Cctv",
        "COMPETENCIA" => "Gestión de normas y procedimientos: Se preocupa y promueve respeto de las normas y procedimientos. Vigila el cumplimiento de las normas e interviene para corregir faltas.",
        "SUBCOMPETENCIA" => "Respeto A La Norma",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Operador De Cctv",
        "COMPETENCIA" => "Gestión de normas y procedimientos: Se preocupa y promueve respeto de las normas y procedimientos. Vigila el cumplimiento de las normas e interviene para corregir faltas.",
        "SUBCOMPETENCIA" => "Revisión Y Seguimiento",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Operador De Cctv",
        "COMPETENCIA" => "Pensamiento claro y tranquilo: Mantiene la calma necesaria para poder reaccionar de manera correcta y proactiva ante cualquier situacion de riesgo.",
        "SUBCOMPETENCIA" => "Calma Ante La Crisis",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Operador De Cctv",
        "COMPETENCIA" => "Pensamiento claro y tranquilo: Mantiene la calma necesaria para poder reaccionar de manera correcta y proactiva ante cualquier situacion de riesgo.",
        "SUBCOMPETENCIA" => "Respuesta Anticipada",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Operador De Cctv",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Cumplimiento De Procedimientos",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Operador De Cctv",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Organización De Información",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Operador De Cctv",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Orientación Al Registro Detallado",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Operador De Cctv",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Amabilidad Y Cercanía",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Operador De Cctv",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Amabilidad Y Cercanía (seguridad)",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Operador De Cctv",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Amabilidad Y Cercanía Con El Colaborador",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Operador De Cctv",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Atención Efectiva A Consultas",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Operador De Cctv",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Atención Efectiva A Consultas (seguridad)",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Operador De Cctv",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Atención Presencial Oportuna",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Operador De Cctv",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Atención Presencial Oportuna (seguridad)",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Operador De Cctv",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Seguimiento A La Atención",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Partner De Gestión Humana",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Claridad en el mensaje",
        "DESCRIPCION" => "Comunica sus ideas de forma clara y precisa."
      ],
      [
        "CATEGORIA" => "Partner De Gestión Humana",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Respuesta Oportuna",
        "DESCRIPCION" => "Comparte información e ideas en el momento adecuado."
      ],
      [
        "CATEGORIA" => "Partner De Gestión Humana",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Escucha",
        "DESCRIPCION" => "Escucha atentamente las opiniones y sugerencias."
      ],
      [
        "CATEGORIA" => "Partner De Gestión Humana",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Asertividad",
        "DESCRIPCION" => "Expresa sus opiniones sin herir los sentimientos de los demás."
      ],
      [
        "CATEGORIA" => "Partner De Gestión Humana",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Orientación al Detalle",
        "DESCRIPCION" => "Es minucioso en el manejo de datos."
      ],
      [
        "CATEGORIA" => "Partner De Gestión Humana",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Organización",
        "DESCRIPCION" => "Mantiene un orden en los documentos que maneja."
      ],
      [
        "CATEGORIA" => "Partner De Gestión Humana",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Cumplimiento de Procesos Operativos",
        "DESCRIPCION" => "Cumple con los procedimientos establecidos por la organización de manera correcta."
      ],
      [
        "CATEGORIA" => "Partner De Gestión Humana",
        "COMPETENCIA" => "Habilidad analítica: Capacidad de razonamiento y organización cognitivamente del trabajo para realizar un análisis lógico identificando problemas, información, datos significativos y soluciones.",
        "SUBCOMPETENCIA" => "Capacidad de comprensión",
        "DESCRIPCION" => "Comprende los procesos relacionados con su trabajo y áreas con las que se relaciona y el porque de su aplicación."
      ],
      [
        "CATEGORIA" => "Partner De Gestión Humana",
        "COMPETENCIA" => "Habilidad analítica: Capacidad de razonamiento y organización cognitivamente del trabajo para realizar un análisis lógico identificando problemas, información, datos significativos y soluciones.",
        "SUBCOMPETENCIA" => "Tratamiento de la información",
        "DESCRIPCION" => "Identifica, analiza, organiza y presenta información para establecer conexiones relevantes entre datos."
      ],
      [
        "CATEGORIA" => "Partner De Gestión Humana",
        "COMPETENCIA" => "Habilidad analítica: Capacidad de razonamiento y organización cognitivamente del trabajo para realizar un análisis lógico identificando problemas, información, datos significativos y soluciones.",
        "SUBCOMPETENCIA" => "Análisis lógico",
        "DESCRIPCION" => "Logra identificar, entender y presentar las causas e impacto de la información y hechos que analiza."
      ],
      [
        "CATEGORIA" => "Partner De Gestión Humana",
        "COMPETENCIA" => "Habilidad analítica: Capacidad de razonamiento y organización cognitivamente del trabajo para realizar un análisis lógico identificando problemas, información, datos significativos y soluciones.",
        "SUBCOMPETENCIA" => "Identificación de problemas",
        "DESCRIPCION" => "Identifica problemas analizando, organizando y conectando datos relevantes."
      ],
      [
        "CATEGORIA" => "Partner De Gestión Humana",
        "COMPETENCIA" => "Servicio al Colaborador: Resuelve las consultas y solicitudes de los colaboradores con empatía y amabilidad. ",
        "SUBCOMPETENCIA" => "Atención Efectiva a consultas",
        "DESCRIPCION" => "Responde oportunamente a las solicitudes y consultas de los colaboradores."
      ],
      [
        "CATEGORIA" => "Partner De Gestión Humana",
        "COMPETENCIA" => "Servicio al Colaborador: Resuelve las consultas y solicitudes de los colaboradores con empatía y amabilidad. ",
        "SUBCOMPETENCIA" => "Empatía",
        "DESCRIPCION" => "Muestra preocupación por los problemas que tienen los colaboradores."
      ],
      [
        "CATEGORIA" => "Partner De Gestión Humana",
        "COMPETENCIA" => "Servicio al Colaborador: Resuelve las consultas y solicitudes de los colaboradores con empatía y amabilidad. ",
        "SUBCOMPETENCIA" => "Atención oportuna",
        "DESCRIPCION" => "Responde de manera oportuna ante las problemáticas de los colaboradores."
      ],
      [
        "CATEGORIA" => "Partner De Gestión Humana",
        "COMPETENCIA" => "Servicio al Colaborador: Resuelve las consultas y solicitudes de los colaboradores con empatía y amabilidad. ",
        "SUBCOMPETENCIA" => "Amabilidad y cercanía",
        "DESCRIPCION" => "Trata al colaborador con amabilidad generando cercanía y confianza."
      ],
      [
        "CATEGORIA" => "Partner De Gestión Humana",
        "COMPETENCIA" => "Consultoría: Brinda alternativas de solución a partir del conocimiento del negocio; la identificación de las necesidades de los colaboradores y la organización; y la evaluación de los procesos de la misma. ",
        "SUBCOMPETENCIA" => "Conocimiento del Negocio",
        "DESCRIPCION" => "Conoce a profundidad las características del negocio, los productos y los procesos de la organización."
      ],
      [
        "CATEGORIA" => "Partner De Gestión Humana",
        "COMPETENCIA" => "Consultoría: Brinda alternativas de solución a partir del conocimiento del negocio; la identificación de las necesidades de los colaboradores y la organización; y la evaluación de los procesos de la misma. ",
        "SUBCOMPETENCIA" => "Identificación de Expectativas y Necesidades",
        "DESCRIPCION" => "Entiende las necesidades, expectativas y dolores de los colaboradores y la organización."
      ],
      [
        "CATEGORIA" => "Partner De Gestión Humana",
        "COMPETENCIA" => "Consultoría: Brinda alternativas de solución a partir del conocimiento del negocio; la identificación de las necesidades de los colaboradores y la organización; y la evaluación de los procesos de la misma. ",
        "SUBCOMPETENCIA" => "Asesoría",
        "DESCRIPCION" => "Proporciona orientación y alternativas de solución considerando el impacto en los colaboradores y la organización"
      ],
      [
        "CATEGORIA" => "Partner De Gestión Humana",
        "COMPETENCIA" => "Consultoría: Brinda alternativas de solución a partir del conocimiento del negocio; la identificación de las necesidades de los colaboradores y la organización; y la evaluación de los procesos de la misma. ",
        "SUBCOMPETENCIA" => "Evaluación",
        "DESCRIPCION" => "Evalúa los procesos los procesos relacionados a los colaboradores y la organización."
      ],
      [
        "CATEGORIA" => "Supervisor Comercial",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Claridad en el mensaje",
        "DESCRIPCION" => "Comunica sus ideas de forma clara y precisa."
      ],
      [
        "CATEGORIA" => "Supervisor Comercial",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Respuesta Oportuna",
        "DESCRIPCION" => "Comparte información e ideas en el momento adecuado."
      ],
      [
        "CATEGORIA" => "Supervisor Comercial",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Escucha",
        "DESCRIPCION" => "Escucha atentamente las opiniones y sugerencias."
      ],
      [
        "CATEGORIA" => "Supervisor Comercial",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Asertividad",
        "DESCRIPCION" => "Expresa sus opiniones sin herir los sentimientos de los demás."
      ],
      [
        "CATEGORIA" => "Supervisor Comercial",
        "COMPETENCIA" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "SUBCOMPETENCIA" => "Dirección de Equipos",
        "DESCRIPCION" => "Dirige al equipo al logro de resultados de manera efectiva."
      ],
      [
        "CATEGORIA" => "Supervisor Comercial",
        "COMPETENCIA" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "SUBCOMPETENCIA" => "Resolución de Conflictos",
        "DESCRIPCION" => "Destraba o resuelve los conflictos internos que impactan en el desempeño del negocio."
      ],
      [
        "CATEGORIA" => "Supervisor Comercial",
        "COMPETENCIA" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "SUBCOMPETENCIA" => "Convicción",
        "DESCRIPCION" => "Transmite credibilidad y convicción a los demás."
      ],
      [
        "CATEGORIA" => "Supervisor Comercial",
        "COMPETENCIA" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "SUBCOMPETENCIA" => "Alineamiento",
        "DESCRIPCION" => "Define con claridad los roles y responsabilidades del equipo."
      ],
      [
        "CATEGORIA" => "Supervisor Comercial",
        "COMPETENCIA" => "Negociación: Capacidad para dirigir o controlar una discusión utilizando técnicas ganar-ganar planificando alternativas para negociar los mejores acuerdos.",
        "SUBCOMPETENCIA" => "Planificación de la estrategia",
        "DESCRIPCION" => "Planifica la estrategia de negociación a utilizar en el momento de la verdad."
      ],
      [
        "CATEGORIA" => "Supervisor Comercial",
        "COMPETENCIA" => "Negociación: Capacidad para dirigir o controlar una discusión utilizando técnicas ganar-ganar planificando alternativas para negociar los mejores acuerdos.",
        "SUBCOMPETENCIA" => "Recolección de información",
        "DESCRIPCION" => "Reune toda la información necesaria para tener opiniones y argumentos sólidos."
      ],
      [
        "CATEGORIA" => "Supervisor Comercial",
        "COMPETENCIA" => "Negociación: Capacidad para dirigir o controlar una discusión utilizando técnicas ganar-ganar planificando alternativas para negociar los mejores acuerdos.",
        "SUBCOMPETENCIA" => "Comunicación asertiva",
        "DESCRIPCION" => "Durante el proceso de comunicación en la negociación sus mensajes son emitidos de manera asertiva."
      ],
      [
        "CATEGORIA" => "Supervisor Comercial",
        "COMPETENCIA" => "Negociación: Capacidad para dirigir o controlar una discusión utilizando técnicas ganar-ganar planificando alternativas para negociar los mejores acuerdos.",
        "SUBCOMPETENCIA" => "Despacho documentario",
        "DESCRIPCION" => "Concreta acuerdos exitosos y satisfactorios para la organización."
      ],
      [
        "CATEGORIA" => "Supervisor Comercial",
        "COMPETENCIA" => "Planificación: Determinar las metas y prioridades de los proyectos, estableciendo las acciones, los plazos y los recursos necesarios. Así como los instrumentos de medición y segumiento.",
        "SUBCOMPETENCIA" => "Creación de Planes",
        "DESCRIPCION" => "Crea planes o proyectos que generan valor a la organización."
      ],
      [
        "CATEGORIA" => "Supervisor Comercial",
        "COMPETENCIA" => "Planificación: Determinar las metas y prioridades de los proyectos, estableciendo las acciones, los plazos y los recursos necesarios. Así como los instrumentos de medición y segumiento.",
        "SUBCOMPETENCIA" => "Priorización",
        "DESCRIPCION" => "Establece el orden de las acciones que tiene un plan o proyecto de acuerdo a criterios establecidos."
      ],
      [
        "CATEGORIA" => "Supervisor Comercial",
        "COMPETENCIA" => "Planificación: Determinar las metas y prioridades de los proyectos, estableciendo las acciones, los plazos y los recursos necesarios. Así como los instrumentos de medición y segumiento.",
        "SUBCOMPETENCIA" => "Definir metas e indciadores",
        "DESCRIPCION" => "Define las metas e indicadores de éxito de las tareas o proyectos asignados."
      ],
      [
        "CATEGORIA" => "Supervisor Comercial",
        "COMPETENCIA" => "Planificación: Determinar las metas y prioridades de los proyectos, estableciendo las acciones, los plazos y los recursos necesarios. Así como los instrumentos de medición y segumiento.",
        "SUBCOMPETENCIA" => "Gestión de Recursos",
        "DESCRIPCION" => "Identifica y asigna los recursos de manera responsable."
      ],
      [
        "CATEGORIA" => "Supervisor Comercial",
        "COMPETENCIA" => "Supervisión Efectiva: Supervisar el desempeño de los colaboradores brindando retrolimentación a partir del mismo y gestionar los resultados obtenidos.",
        "SUBCOMPETENCIA" => "Seguimiento",
        "DESCRIPCION" => "Supervisa los avances, entregables y acciones de los colaboradores para lograr los objetivos."
      ],
      [
        "CATEGORIA" => "Supervisor Comercial",
        "COMPETENCIA" => "Supervisión Efectiva: Supervisar el desempeño de los colaboradores brindando retrolimentación a partir del mismo y gestionar los resultados obtenidos.",
        "SUBCOMPETENCIA" => "Retroalimentación",
        "DESCRIPCION" => "Brinda retroalimentación de manera oportuna sobre los resultados individuales."
      ],
      [
        "CATEGORIA" => "Supervisor Comercial",
        "COMPETENCIA" => "Supervisión Efectiva: Supervisar el desempeño de los colaboradores brindando retrolimentación a partir del mismo y gestionar los resultados obtenidos.",
        "SUBCOMPETENCIA" => "Gestión de Consecuencias",
        "DESCRIPCION" => "Felicita o corrige las acciones de los colaboradores de acuerdo al desempeño que tienen."
      ],
      [
        "CATEGORIA" => "Supervisor De Almacen",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Claridad en el mensaje",
        "DESCRIPCION" => "Comunica sus ideas de forma clara y precisa."
      ],
      [
        "CATEGORIA" => "Supervisor De Almacen",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Respuesta Oportuna",
        "DESCRIPCION" => "Comparte información e ideas en el momento adecuado."
      ],
      [
        "CATEGORIA" => "Supervisor De Almacen",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Escucha",
        "DESCRIPCION" => "Escucha atentamente las opiniones y sugerencias."
      ],
      [
        "CATEGORIA" => "Supervisor De Almacen",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Asertividad",
        "DESCRIPCION" => "Expresa sus opiniones sin herir los sentimientos de los demás."
      ],
      [
        "CATEGORIA" => "Supervisor De Almacen",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Dirección o Supervisión",
        "DESCRIPCION" => "Supervisa los avances, entregables y acciones del equipo para el lograr los objetivos."
      ],
      [
        "CATEGORIA" => "Supervisor De Almacen",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Seguimiento",
        "DESCRIPCION" => "Realiza seguimiento a los avances de su equipo."
      ],
      [
        "CATEGORIA" => "Supervisor De Almacen",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Retroalimentación",
        "DESCRIPCION" => "Brinda retroalimentación de manera oportuna sobre los resultados individuales"
      ],
      [
        "CATEGORIA" => "Supervisor De Almacen",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Control",
        "DESCRIPCION" => "Controla los avances del equipo a fin de intervenir de manera oportuna."
      ],
      [
        "CATEGORIA" => "Supervisor De Almacen",
        "COMPETENCIA" => "Gestión de Equipos: Dirige a su equipo a realizar las acciones necesarias para el logro de resultados. Realiza seguimiento y corrige comportamientos. Motiva a que se logren los resultados.",
        "SUBCOMPETENCIA" => "Gestión de Consecuencias",
        "DESCRIPCION" => "Felicita o corrige las acciones de su equipo."
      ],
      [
        "CATEGORIA" => "Supervisor De Almacen",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Proactividad",
        "DESCRIPCION" => "Se anticipa a los requerimientos o necesidades que se presentan."
      ],
      [
        "CATEGORIA" => "Supervisor De Almacen",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Solución de Problemas",
        "DESCRIPCION" => "Resuelve los problemas a pesar de las dificultades."
      ],
      [
        "CATEGORIA" => "Supervisor De Almacen",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Cumplimiento de Acuerdos",
        "DESCRIPCION" => "Cumple con los acuerdos de la organización."
      ],
      [
        "CATEGORIA" => "Supervisor De Almacen",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Resultados",
        "DESCRIPCION" => "Logra los resultados que se le asignan."
      ],
      [
        "CATEGORIA" => "Supervisor De Almacen",
        "COMPETENCIA" => "Mentalidad de Dueño: Asume los problemas como propios, mostrando predisposición y esforzándose por conseguir los resultados que se le plantean.",
        "SUBCOMPETENCIA" => "Asumo las consecuencias",
        "DESCRIPCION" => "Se hace cargo de los errores que realiza y los resuelve. No espera a que otros lo solucionen"
      ],
      [
        "CATEGORIA" => "Supervisor De Almacen",
        "COMPETENCIA" => "Organización y Control: Garantizar el uso de recursos a través de una adecuada organización, clasificación y control.",
        "SUBCOMPETENCIA" => "Control de Movimientos",
        "DESCRIPCION" => "Controla el ingreso y salida de la mercadería de manera adecuada."
      ],
      [
        "CATEGORIA" => "Supervisor De Almacen",
        "COMPETENCIA" => "Organización y Control: Garantizar el uso de recursos a través de una adecuada organización, clasificación y control.",
        "SUBCOMPETENCIA" => "Protección de Recursos",
        "DESCRIPCION" => "Verifica con detalle si la mercadería llega averiada o incompleta."
      ],
      [
        "CATEGORIA" => "Supervisor De Almacen",
        "COMPETENCIA" => "Organización y Control: Garantizar el uso de recursos a través de una adecuada organización, clasificación y control.",
        "SUBCOMPETENCIA" => "Manejo de Inventario",
        "DESCRIPCION" => "Comprueba de manera adecuada la existencia de la mercadería real vs la mercadería registrada en el sistema."
      ],
      [
        "CATEGORIA" => "Supervisor De Almacen",
        "COMPETENCIA" => "Organización y Control: Garantizar el uso de recursos a través de una adecuada organización, clasificación y control.",
        "SUBCOMPETENCIA" => "Organización de Recursos",
        "DESCRIPCION" => "Clasifica los recursos con un orden claro y práctico."
      ],
      [
        "CATEGORIA" => "Supervisor De Almacen",
        "COMPETENCIA" => "Organización y Control: Garantizar el uso de recursos a través de una adecuada organización, clasificación y control.",
        "SUBCOMPETENCIA" => "Reclamos de ingreso de Recursos",
        "DESCRIPCION" => "Realiza de manera oportuna los reclamos ante mercaderías averiadas o faltantes."
      ],
      [
        "CATEGORIA" => "Supervisor De Almacen",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Atención Efectiva a consultas",
        "DESCRIPCION" => "Responde oportunamente a las solicitudes y consultas de los clientes vía presencial, telefónica y por correo electrónico."
      ],
      [
        "CATEGORIA" => "Supervisor De Almacen",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Seguimiento a la atención",
        "DESCRIPCION" => "Vigila la adecuada atención del cliente y pide al responsable que se haga cargo."
      ],
      [
        "CATEGORIA" => "Supervisor De Almacen",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Atención presencial oportuna",
        "DESCRIPCION" => "Se hace cargo de los errores que realiza y los resuelve. No espera a que otros lo solucionen"
      ],
      [
        "CATEGORIA" => "Supervisor De Almacen",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Amabilidad y cercanía",
        "DESCRIPCION" => "Trata al cliente con amabilidad generando cercanía y confianza."
      ],
      [
        "CATEGORIA" => "Supervisor De Operaciones Y Mantenimiento",
        "COMPETENCIA" => "Gestión de normas y procedimientos: Se preocupa y promueve respeto de las normas y procedimientos. Vigila el cumplimiento de las normas e interviene para corregir faltas.",
        "SUBCOMPETENCIA" => "Claridad En La Orientación",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Supervisor De Operaciones Y Mantenimiento",
        "COMPETENCIA" => "Gestión de normas y procedimientos: Se preocupa y promueve respeto de las normas y procedimientos. Vigila el cumplimiento de las normas e interviene para corregir faltas.",
        "SUBCOMPETENCIA" => "Gestión De Consecuencias V1",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Supervisor De Operaciones Y Mantenimiento",
        "COMPETENCIA" => "Gestión de normas y procedimientos: Se preocupa y promueve respeto de las normas y procedimientos. Vigila el cumplimiento de las normas e interviene para corregir faltas.",
        "SUBCOMPETENCIA" => "Respeto A La Norma",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Supervisor De Operaciones Y Mantenimiento",
        "COMPETENCIA" => "Gestión de normas y procedimientos: Se preocupa y promueve respeto de las normas y procedimientos. Vigila el cumplimiento de las normas e interviene para corregir faltas.",
        "SUBCOMPETENCIA" => "Revisión Y Seguimiento",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Supervisor De Operaciones Y Mantenimiento",
        "COMPETENCIA" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "SUBCOMPETENCIA" => "Alineamiento",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Supervisor De Operaciones Y Mantenimiento",
        "COMPETENCIA" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "SUBCOMPETENCIA" => "Asumo Las Consecuencias",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Supervisor De Operaciones Y Mantenimiento",
        "COMPETENCIA" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "SUBCOMPETENCIA" => "Convicción",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Supervisor De Operaciones Y Mantenimiento",
        "COMPETENCIA" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "SUBCOMPETENCIA" => "Cumplimiento De Acuerdos",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Supervisor De Operaciones Y Mantenimiento",
        "COMPETENCIA" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "SUBCOMPETENCIA" => "Dirección De Equipos",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Supervisor De Operaciones Y Mantenimiento",
        "COMPETENCIA" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "SUBCOMPETENCIA" => "Proactividad",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Supervisor De Operaciones Y Mantenimiento",
        "COMPETENCIA" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "SUBCOMPETENCIA" => "Resolución De Conflictos",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Supervisor De Operaciones Y Mantenimiento",
        "COMPETENCIA" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "SUBCOMPETENCIA" => "Resultados",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Supervisor De Operaciones Y Mantenimiento",
        "COMPETENCIA" => "Liderazgo: Dirige a su equipo de Líderes al logro de objetivos, destraba decisiones que impacten en el desempeño del negocio.",
        "SUBCOMPETENCIA" => "Solución De Problema",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Supervisor De Operaciones Y Mantenimiento",
        "COMPETENCIA" => "Manejo de problemas complejos: Analiza los problemas utilizando diferentes fuentesde información a fin de definir estrategias que le permitan solucionar las dificultades de manera adecuada.",
        "SUBCOMPETENCIA" => "Adaptabilidad",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Supervisor De Operaciones Y Mantenimiento",
        "COMPETENCIA" => "Manejo de problemas complejos: Analiza los problemas utilizando diferentes fuentesde información a fin de definir estrategias que le permitan solucionar las dificultades de manera adecuada.",
        "SUBCOMPETENCIA" => "Diagnóstico De Problemas",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Supervisor De Operaciones Y Mantenimiento",
        "COMPETENCIA" => "Manejo de problemas complejos: Analiza los problemas utilizando diferentes fuentesde información a fin de definir estrategias que le permitan solucionar las dificultades de manera adecuada.",
        "SUBCOMPETENCIA" => "Solución De Problemas Internos",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Supervisor De Operaciones Y Mantenimiento",
        "COMPETENCIA" => "Planificación: Determinar las metas y prioridades de los proyectos, estableciendo las acciones, los plazos y los recursos necesarios. Así como los instrumentos de medición y segumiento.",
        "SUBCOMPETENCIA" => "Creación De Planes",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Supervisor De Operaciones Y Mantenimiento",
        "COMPETENCIA" => "Planificación: Determinar las metas y prioridades de los proyectos, estableciendo las acciones, los plazos y los recursos necesarios. Así como los instrumentos de medición y segumiento.",
        "SUBCOMPETENCIA" => "Definir Metas E Indciadores",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Supervisor De Operaciones Y Mantenimiento",
        "COMPETENCIA" => "Planificación: Determinar las metas y prioridades de los proyectos, estableciendo las acciones, los plazos y los recursos necesarios. Así como los instrumentos de medición y segumiento.",
        "SUBCOMPETENCIA" => "Definir Metas e indicadores",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Supervisor De Operaciones Y Mantenimiento",
        "COMPETENCIA" => "Planificación: Determinar las metas y prioridades de los proyectos, estableciendo las acciones, los plazos y los recursos necesarios. Así como los instrumentos de medición y segumiento.",
        "SUBCOMPETENCIA" => "Planificación De Los Recursos",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Supervisor De Operaciones Y Mantenimiento",
        "COMPETENCIA" => "Planificación: Determinar las metas y prioridades de los proyectos, estableciendo las acciones, los plazos y los recursos necesarios. Así como los instrumentos de medición y segumiento.",
        "SUBCOMPETENCIA" => "Priorización",
        "DESCRIPCION" => ""
      ],
      [
        "CATEGORIA" => "Tecnico Electricista",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Claridad en el mensaje",
        "DESCRIPCION" => "Comunica sus ideas de forma clara y precisa."
      ],
      [
        "CATEGORIA" => "Tecnico Electricista",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Respuesta Oportuna",
        "DESCRIPCION" => "Comparte información e ideas en el momento adecuado."
      ],
      [
        "CATEGORIA" => "Tecnico Electricista",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Escucha",
        "DESCRIPCION" => "Escucha atentamente las opiniones y sugerencias."
      ],
      [
        "CATEGORIA" => "Tecnico Electricista",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Asertividad",
        "DESCRIPCION" => "Expresa sus opiniones sin herir los sentimientos de los demás."
      ],
      [
        "CATEGORIA" => "Tecnico Electricista",
        "COMPETENCIA" => "Cooperación Operativa: Coopera de manera activa con las necesidades del equipo y unidades con las que interactúa. Respeta los acuerdos generando un espacio colaborativo y de confianza.",
        "SUBCOMPETENCIA" => "Cooperación",
        "DESCRIPCION" => "Muestra predisposición y apoyo para hacer trabajos operativos."
      ],
      [
        "CATEGORIA" => "Tecnico Electricista",
        "COMPETENCIA" => "Cooperación Operativa: Coopera de manera activa con las necesidades del equipo y unidades con las que interactúa. Respeta los acuerdos generando un espacio colaborativo y de confianza.",
        "SUBCOMPETENCIA" => "Orientación a la meta común",
        "DESCRIPCION" => "Apoya a los demás para realizar un entregable de manera rápida."
      ],
      [
        "CATEGORIA" => "Tecnico Electricista",
        "COMPETENCIA" => "Cooperación Operativa: Coopera de manera activa con las necesidades del equipo y unidades con las que interactúa. Respeta los acuerdos generando un espacio colaborativo y de confianza.",
        "SUBCOMPETENCIA" => "Relaciones interpersonales",
        "DESCRIPCION" => "Tiene buenas relaciones con los demás y genera confianza."
      ],
      [
        "CATEGORIA" => "Tecnico Electricista",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Gestión de Recursos",
        "DESCRIPCION" => "Identifica y asigna los recursos de manera responsable."
      ],
      [
        "CATEGORIA" => "Tecnico Electricista",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Metodología",
        "DESCRIPCION" => "Utiliza metodologías o procesos estandarizados que garantizan los resultados esperados."
      ],
      [
        "CATEGORIA" => "Tecnico Electricista",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Orientación al logro",
        "DESCRIPCION" => "Logra los resultados esperados en los tiempos establecidos."
      ],
      [
        "CATEGORIA" => "Tecnico Electricista",
        "COMPETENCIA" => "Habilidad para reparar: Soluciona las fallas técnicas con rapidez y efectividad, identifica las causas de manera oportuna y verifica el correcto funcionamiento una vez reparado.",
        "SUBCOMPETENCIA" => "Diagnóstico",
        "DESCRIPCION" => "Identifica rápidamente la causa de las fallas mecánicas, eléctricas, de soldadura o de neumáticos."
      ],
      [
        "CATEGORIA" => "Tecnico Electricista",
        "COMPETENCIA" => "Habilidad para reparar: Soluciona las fallas técnicas con rapidez y efectividad, identifica las causas de manera oportuna y verifica el correcto funcionamiento una vez reparado.",
        "SUBCOMPETENCIA" => "Reparación",
        "DESCRIPCION" => "Repara con rapidez las fallas mecánicas, eléctricas, de soldadura o de neumáticos sin perjudicar la calidad de lo reparado."
      ],
      [
        "CATEGORIA" => "Tecnico Electricista",
        "COMPETENCIA" => "Habilidad para reparar: Soluciona las fallas técnicas con rapidez y efectividad, identifica las causas de manera oportuna y verifica el correcto funcionamiento una vez reparado.",
        "SUBCOMPETENCIA" => "Contol de Calidad",
        "DESCRIPCION" => "Verifica el correcto funcionamiento de lo reparado antes de entregarlo."
      ],
      [
        "CATEGORIA" => "Tecnico Electricista",
        "COMPETENCIA" => "Organización y Control: Garantizar el uso de recursos a través de una adecuada organización, clasificación y control.",
        "SUBCOMPETENCIA" => "Control de Recursos",
        "DESCRIPCION" => "Controla la cantidad de recursos necesarios para realizar las tareas."
      ],
      [
        "CATEGORIA" => "Tecnico Electricista",
        "COMPETENCIA" => "Organización y Control: Garantizar el uso de recursos a través de una adecuada organización, clasificación y control.",
        "SUBCOMPETENCIA" => "Protección de Recursos",
        "DESCRIPCION" => "Cuida los recursos para mantener su calidad."
      ],
      [
        "CATEGORIA" => "Tecnico Electricista",
        "COMPETENCIA" => "Organización y Control: Garantizar el uso de recursos a través de una adecuada organización, clasificación y control.",
        "SUBCOMPETENCIA" => "Organización de Recursos",
        "DESCRIPCION" => "Clasifica los recursos con un orden claro y práctico."
      ],
      [
        "CATEGORIA" => "Tecnico Mecanico Ap",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Gestión de Recursos",
        "DESCRIPCION" => "Identifica y asigna los recursos de manera responsable."
      ],
      [
        "CATEGORIA" => "Tecnico Mecanico Ap",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Metodología",
        "DESCRIPCION" => "Utiliza metodologías o procesos estandarizados que garantizan los resultados esperados."
      ],
      [
        "CATEGORIA" => "Tecnico Mecanico Ap",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Orientación al logro",
        "DESCRIPCION" => "Logra los resultados esperados en los tiempos establecidos."
      ],
      [
        "CATEGORIA" => "Tecnico Mecanico Ap",
        "COMPETENCIA" => "Habilidad de Enseñanza: Logra explicar con claridad y paciencia el funcionamiento de la operación, brinda retroalmentación clara para incorporar los procedimientos e instrucciones.",
        "SUBCOMPETENCIA" => "Tolerancia",
        "DESCRIPCION" => "Tiene paciencia para explicar las instrucciones y lineamientos."
      ],
      [
        "CATEGORIA" => "Tecnico Mecanico Ap",
        "COMPETENCIA" => "Habilidad de Enseñanza: Logra explicar con claridad y paciencia el funcionamiento de la operación, brinda retroalmentación clara para incorporar los procedimientos e instrucciones.",
        "SUBCOMPETENCIA" => "Capacidad para explicar",
        "DESCRIPCION" => "Enseña con claridad a los demás sobre el funcionamiento de la operación."
      ],
      [
        "CATEGORIA" => "Tecnico Mecanico Ap",
        "COMPETENCIA" => "Habilidad de Enseñanza: Logra explicar con claridad y paciencia el funcionamiento de la operación, brinda retroalmentación clara para incorporar los procedimientos e instrucciones.",
        "SUBCOMPETENCIA" => "Evaluación",
        "DESCRIPCION" => "Evalúa desempeño o habilidades de acuerdo a criterios establecidos."
      ],
      [
        "CATEGORIA" => "Tecnico Mecanico Ap",
        "COMPETENCIA" => "Habilidad de Enseñanza: Logra explicar con claridad y paciencia el funcionamiento de la operación, brinda retroalmentación clara para incorporar los procedimientos e instrucciones.",
        "SUBCOMPETENCIA" => "Retroalimentación",
        "DESCRIPCION" => "Brinda retroalimentación clara y objetiva sobre el desempeño del otro."
      ],
      [
        "CATEGORIA" => "Tecnico Mecanico Ap",
        "COMPETENCIA" => "Habilidad para reparar: Soluciona las fallas técnicas con rapidez y efectividad, identifica las causas de manera oportuna y verifica el correcto funcionamiento una vez reparado.",
        "SUBCOMPETENCIA" => "Diagnóstico",
        "DESCRIPCION" => "Identifica rápidamente la causa de las fallas mecánicas o eléctricas o de soldadura."
      ],
      [
        "CATEGORIA" => "Tecnico Mecanico Ap",
        "COMPETENCIA" => "Habilidad para reparar: Soluciona las fallas técnicas con rapidez y efectividad, identifica las causas de manera oportuna y verifica el correcto funcionamiento una vez reparado.",
        "SUBCOMPETENCIA" => "Reparación",
        "DESCRIPCION" => "Repara con rapidez las fallas mecánicas o eléctricas o de soldadura sin perjudicar la calidad de lo reparado."
      ],
      [
        "CATEGORIA" => "Tecnico Mecanico Ap",
        "COMPETENCIA" => "Habilidad para reparar: Soluciona las fallas técnicas con rapidez y efectividad, identifica las causas de manera oportuna y verifica el correcto funcionamiento una vez reparado.",
        "SUBCOMPETENCIA" => "Contol de Calidad",
        "DESCRIPCION" => "Verifica el correcto funcionamiento de lo reparado antes de entregarlo."
      ],
      [
        "CATEGORIA" => "Tecnico Mecanico Ap",
        "COMPETENCIA" => "Organización y Control: Garantizar el uso de recursos a través de una adecuada organización, clasificación y control.",
        "SUBCOMPETENCIA" => "Control de Recursos",
        "DESCRIPCION" => "Controla la cantidad de recursos necesarios para realizar las tareas."
      ],
      [
        "CATEGORIA" => "Tecnico Mecanico Ap",
        "COMPETENCIA" => "Organización y Control: Garantizar el uso de recursos a través de una adecuada organización, clasificación y control.",
        "SUBCOMPETENCIA" => "Protección de Recursos",
        "DESCRIPCION" => "Cuida los recursos para mantener su calidad."
      ],
      [
        "CATEGORIA" => "Tecnico Mecanico Ap",
        "COMPETENCIA" => "Organización y Control: Garantizar el uso de recursos a través de una adecuada organización, clasificación y control.",
        "SUBCOMPETENCIA" => "Organización de Recursos",
        "DESCRIPCION" => "Clasifica los recursos con un orden claro y práctico."
      ],
      [
        "CATEGORIA" => "Tecnico Mecanico Ap",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Orientación al Detalle",
        "DESCRIPCION" => "Es minucioso en el manejo de datos."
      ],
      [
        "CATEGORIA" => "Tecnico Mecanico Ap",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Organización",
        "DESCRIPCION" => "Mantiene un orden en los documentos que maneja."
      ],
      [
        "CATEGORIA" => "Tecnico Mecanico Ap",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Cumplimiento de Procesos Operativos",
        "DESCRIPCION" => "Cumple con los procedimientos establecidos por la organización de manera correcta."
      ],
      [
        "CATEGORIA" => "Tecnico Soldador",
        "COMPETENCIA" => "Cooperación Operativa: Coopera de manera activa con las necesidades del equipo y unidades con las que interactúa. Respeta los acuerdos generando un espacio colaborativo y de confianza.",
        "SUBCOMPETENCIA" => "Cooperación",
        "DESCRIPCION" => "Muestra predisposición y apoyo para hacer trabajos operativos."
      ],
      [
        "CATEGORIA" => "Tecnico Soldador",
        "COMPETENCIA" => "Cooperación Operativa: Coopera de manera activa con las necesidades del equipo y unidades con las que interactúa. Respeta los acuerdos generando un espacio colaborativo y de confianza.",
        "SUBCOMPETENCIA" => "Orientación a la meta común",
        "DESCRIPCION" => "Apoya a los demás para realizar un entregable de manera rápida."
      ],
      [
        "CATEGORIA" => "Tecnico Soldador",
        "COMPETENCIA" => "Cooperación Operativa: Coopera de manera activa con las necesidades del equipo y unidades con las que interactúa. Respeta los acuerdos generando un espacio colaborativo y de confianza.",
        "SUBCOMPETENCIA" => "Relaciones interpersonales",
        "DESCRIPCION" => "Tiene buenas relaciones con los demás y genera confianza."
      ],
      [
        "CATEGORIA" => "Tecnico Soldador",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Gestión de Recursos",
        "DESCRIPCION" => "Identifica y asigna los recursos de manera responsable."
      ],
      [
        "CATEGORIA" => "Tecnico Soldador",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Metodología",
        "DESCRIPCION" => "Utiliza metodologías o procesos estandarizados que garantizan los resultados esperados."
      ],
      [
        "CATEGORIA" => "Tecnico Soldador",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Orientación al logro",
        "DESCRIPCION" => "Logra los resultados esperados en los tiempos establecidos."
      ],
      [
        "CATEGORIA" => "Tecnico Soldador",
        "COMPETENCIA" => "Habilidad para reparar: Soluciona las fallas técnicas con rapidez y efectividad, identifica las causas de manera oportuna y verifica el correcto funcionamiento una vez reparado.",
        "SUBCOMPETENCIA" => "Diagnóstico",
        "DESCRIPCION" => "Identifica rápidamente la causa de las fallas mecánicas, eléctricas, de soldadura o de neumáticos."
      ],
      [
        "CATEGORIA" => "Tecnico Soldador",
        "COMPETENCIA" => "Habilidad para reparar: Soluciona las fallas técnicas con rapidez y efectividad, identifica las causas de manera oportuna y verifica el correcto funcionamiento una vez reparado.",
        "SUBCOMPETENCIA" => "Reparación",
        "DESCRIPCION" => "Repara con rapidez las fallas mecánicas, eléctricas, de soldadura o de neumáticos sin perjudicar la calidad de lo reparado."
      ],
      [
        "CATEGORIA" => "Tecnico Soldador",
        "COMPETENCIA" => "Habilidad para reparar: Soluciona las fallas técnicas con rapidez y efectividad, identifica las causas de manera oportuna y verifica el correcto funcionamiento una vez reparado.",
        "SUBCOMPETENCIA" => "Contol de Calidad",
        "DESCRIPCION" => "Verifica el correcto funcionamiento de lo reparado antes de entregarlo."
      ],
      [
        "CATEGORIA" => "Tecnico Soldador",
        "COMPETENCIA" => "Organización y Control: Garantizar el uso de recursos a través de una adecuada organización, clasificación y control.",
        "SUBCOMPETENCIA" => "Control de Recursos",
        "DESCRIPCION" => "Controla la cantidad de recursos necesarios para realizar las tareas."
      ],
      [
        "CATEGORIA" => "Tecnico Soldador",
        "COMPETENCIA" => "Organización y Control: Garantizar el uso de recursos a través de una adecuada organización, clasificación y control.",
        "SUBCOMPETENCIA" => "Protección de Recursos",
        "DESCRIPCION" => "Cuida los recursos para mantener su calidad."
      ],
      [
        "CATEGORIA" => "Tecnico Soldador",
        "COMPETENCIA" => "Organización y Control: Garantizar el uso de recursos a través de una adecuada organización, clasificación y control.",
        "SUBCOMPETENCIA" => "Organización de Recursos",
        "DESCRIPCION" => "Clasifica los recursos con un orden claro y práctico."
      ],
      [
        "CATEGORIA" => "Tecnico Soldador",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Orientación al Detalle",
        "DESCRIPCION" => "Es minucioso en el manejo de datos."
      ],
      [
        "CATEGORIA" => "Tecnico Soldador",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Organización",
        "DESCRIPCION" => "Mantiene un orden en los documentos que maneja."
      ],
      [
        "CATEGORIA" => "Tecnico Soldador",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Cumplimiento de Procesos Operativos",
        "DESCRIPCION" => "Cumple con los procedimientos establecidos por la organización de manera correcta."
      ],
      [
        "CATEGORIA" => "Gestor de Inmatriculaciones",
        "COMPETENCIA" => "Mentalidad de Dueño V2: Asume los problemas como propios, mostrando predisposición  y esforzándose por conseguir los resultados que se le plantean. ",
        "SUBCOMPETENCIA" => "Anticipación",
        "DESCRIPCION" => "Se anticipa a los requerimientos o necesidades que se presentan."
      ],
      [
        "CATEGORIA" => "Gestor de Inmatriculaciones",
        "COMPETENCIA" => "Mentalidad de Dueño V2: Asume los problemas como propios, mostrando predisposición  y esforzándose por conseguir los resultados que se le plantean. ",
        "SUBCOMPETENCIA" => "Solución de Problemas",
        "DESCRIPCION" => "Resuelve los problemas a pesar de las dificultades."
      ],
      [
        "CATEGORIA" => "Gestor de Inmatriculaciones",
        "COMPETENCIA" => "Mentalidad de Dueño V2: Asume los problemas como propios, mostrando predisposición  y esforzándose por conseguir los resultados que se le plantean. ",
        "SUBCOMPETENCIA" => "Pertenencia",
        "DESCRIPCION" => "El colaborador se siente dueño de sus procesos y los ejecuta proactivamente."
      ],
      [
        "CATEGORIA" => "Gestor de Inmatriculaciones",
        "COMPETENCIA" => "Habilidad de Enseñanza: Logra explicar con claridad y paciencia el funcionamiento de la operación, brinda retroalmentación clara para incorporar los procedimientos e instrucciones.",
        "SUBCOMPETENCIA" => "Tolerancia",
        "DESCRIPCION" => "Tiene paciencia para explicar las instrucciones y lineamientos."
      ],
      [
        "CATEGORIA" => "Gestor de Inmatriculaciones",
        "COMPETENCIA" => "Habilidad de Enseñanza: Logra explicar con claridad y paciencia el funcionamiento de la operación, brinda retroalmentación clara para incorporar los procedimientos e instrucciones.",
        "SUBCOMPETENCIA" => "Capacidad para explicar",
        "DESCRIPCION" => "Enseña con claridad a los demás sobre el funcionamiento de la operación."
      ],
      [
        "CATEGORIA" => "Gestor de Inmatriculaciones",
        "COMPETENCIA" => "Habilidad de Enseñanza: Logra explicar con claridad y paciencia el funcionamiento de la operación, brinda retroalmentación clara para incorporar los procedimientos e instrucciones.",
        "SUBCOMPETENCIA" => "Evaluación",
        "DESCRIPCION" => "Evalúa desempeño o habilidades de acuerdo a criterios establecidos."
      ],
      [
        "CATEGORIA" => "Gestor de Inmatriculaciones",
        "COMPETENCIA" => "Habilidad de Enseñanza: Logra explicar con claridad y paciencia el funcionamiento de la operación, brinda retroalmentación clara para incorporar los procedimientos e instrucciones.",
        "SUBCOMPETENCIA" => "Retroalimentación",
        "DESCRIPCION" => "Brinda retroalimentación clara y objetiva sobre el desempeño del otro."
      ],
      [
        "CATEGORIA" => "Gestor de Inmatriculaciones",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Claridad en el mensaje",
        "DESCRIPCION" => "Comunica sus ideas de forma clara y precisa."
      ],
      [
        "CATEGORIA" => "Gestor de Inmatriculaciones",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Respuesta Oportuna",
        "DESCRIPCION" => "Comparte información e ideas en el momento adecuado."
      ],
      [
        "CATEGORIA" => "Gestor de Inmatriculaciones",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Escucha",
        "DESCRIPCION" => "Escucha atentamente las opiniones y sugerencias."
      ],
      [
        "CATEGORIA" => "Gestor de Inmatriculaciones",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Asertividad",
        "DESCRIPCION" => "Expresa sus opiniones sin herir los sentimientos de los demás."
      ],
      [
        "CATEGORIA" => "Gestor de Inmatriculaciones",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Gestión de Recursos",
        "DESCRIPCION" => "Identifica y asigna los recursos de manera responsable."
      ],
      [
        "CATEGORIA" => "Gestor de Inmatriculaciones",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Metodología",
        "DESCRIPCION" => "Utiliza metodologías o procesos estandarizados que garantizan los resultados esperados."
      ],
      [
        "CATEGORIA" => "Gestor de Inmatriculaciones",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Orientación al logro",
        "DESCRIPCION" => "Logra los resultados esperados en los tiempos establecidos."
      ],
      [
        "CATEGORIA" => "Gestor de Inmatriculaciones",
        "COMPETENCIA" => "Registro y Manejo de Información - Versión 2 AP: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Orientación al Detalle",
        "DESCRIPCION" => "Es minucioso en el manejo de datos."
      ],
      [
        "CATEGORIA" => "Gestor de Inmatriculaciones",
        "COMPETENCIA" => "Registro y Manejo de Información - Versión 2 AP: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Organización",
        "DESCRIPCION" => "Mantiene un orden en los documentos que maneja."
      ],
      [
        "CATEGORIA" => "Gestor de Inmatriculaciones",
        "COMPETENCIA" => "Registro y Manejo de Información - Versión 2 AP: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Cumplimiento de Procesos Operativos",
        "DESCRIPCION" => "Cumple con los procedimientos establecidos por la organización de manera correcta."
      ],
      [
        "CATEGORIA" => "Asistente de Marketing",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Claridad en el mensaje",
        "DESCRIPCION" => "Comunica sus ideas de forma clara y precisa."
      ],
      [
        "CATEGORIA" => "Asistente de Marketing",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Respuesta Oportuna",
        "DESCRIPCION" => "Comparte información e ideas en el momento adecuado."
      ],
      [
        "CATEGORIA" => "Asistente de Marketing",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Escucha",
        "DESCRIPCION" => "Escucha atentamente las opiniones y sugerencias."
      ],
      [
        "CATEGORIA" => "Asistente de Marketing",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Asertividad",
        "DESCRIPCION" => "Expresa sus opiniones sin herir los sentimientos de los demás."
      ],
      [
        "CATEGORIA" => "Asistente de Marketing",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Gestión de Recursos",
        "DESCRIPCION" => "Identifica y asigna los recursos de manera responsable."
      ],
      [
        "CATEGORIA" => "Asistente de Marketing",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Metodología",
        "DESCRIPCION" => "Utiliza metodologías o procesos estandarizados que garantizan los resultados esperados."
      ],
      [
        "CATEGORIA" => "Asistente de Marketing",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Orientación al logro",
        "DESCRIPCION" => "Logra los resultados esperados en los tiempos establecidos."
      ],
      [
        "CATEGORIA" => "Asistente de Marketing",
        "COMPETENCIA" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "SUBCOMPETENCIA" => "Traspase de información",
        "DESCRIPCION" => "Verifica que la información que le brinda al otro llegue de manera efectiva y completa."
      ],
      [
        "CATEGORIA" => "Asistente de Marketing",
        "COMPETENCIA" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "SUBCOMPETENCIA" => "Colaboración",
        "DESCRIPCION" => "Colabora con los demás para cumplir con el objetivo en común."
      ],
      [
        "CATEGORIA" => "Asistente de Marketing",
        "COMPETENCIA" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "SUBCOMPETENCIA" => "Responsabilidad",
        "DESCRIPCION" => "Asume la responsabilidad que le corresponde, no espera que sólo los demás trabajen."
      ],
      [
        "CATEGORIA" => "Asistente de Marketing",
        "COMPETENCIA" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "SUBCOMPETENCIA" => "Resuelve conflictos",
        "DESCRIPCION" => "Resuelve de manera oportuna las dificultades que pueda tener con sus compañeros"
      ],
      [
        "CATEGORIA" => "Asistente de Marketing",
        "COMPETENCIA" => "Trabajo en Equipo: Promueve el trabajo colaborativo asumiendo la responsabilidad que le corresponde para el logro de los objetivos. Comparte la información de manera oportuna y correcta.",
        "SUBCOMPETENCIA" => "Pasión",
        "DESCRIPCION" => "Pone la mejor energía para hacer sus tareas y jugar en equipo."
      ],
      [
        "CATEGORIA" => "Asistente de Marketing",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Atención Efectiva a consultas",
        "DESCRIPCION" => "Responde oportunamente a las solicitudes y consultas de los clientes vía presencial, telefónica y por correo electrónico."
      ],
      [
        "CATEGORIA" => "Asistente de Marketing",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Seguimiento a la atención",
        "DESCRIPCION" => "Vigilo la adecuada atención del cliente y pido al responsable a que se haga cargo."
      ],
      [
        "CATEGORIA" => "Asistente de Marketing",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Atención presencial oportuna",
        "DESCRIPCION" => "Hago todo lo necesario para que el cliente sea bien atendido en el menor tiempo posible."
      ],
      [
        "CATEGORIA" => "Asistente de Marketing",
        "COMPETENCIA" => "Servicio al Cliente: Atiende las solicitudes del cliente de manera amable y oportuna garantizando su experiencia de principio a fin.",
        "SUBCOMPETENCIA" => "Amabilidad y cercanía",
        "DESCRIPCION" => "Trata al cliente con amabilidad generando cercanía y confianza."
      ],
      [
        "CATEGORIA" => "Asistente De Vehiculos (PDI)",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Gestión de Recursos",
        "DESCRIPCION" => "Identifica y asigna los recursos de manera responsable."
      ],
      [
        "CATEGORIA" => "Asistente De Vehiculos (PDI)",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Metodología",
        "DESCRIPCION" => "Utiliza metodologías o procesos estandarizados que garantizan los resultados esperados."
      ],
      [
        "CATEGORIA" => "Asistente De Vehiculos (PDI)",
        "COMPETENCIA" => "Ejecución Efectiva: Realizar una ejecución efectiva a partir de una adecuada gestión de los recursos y el uso de metodologías o procesos estandarizados en tiempos previamente establecidos.",
        "SUBCOMPETENCIA" => "Orientación al logro",
        "DESCRIPCION" => "Logra los resultados esperados en los tiempos establecidos."
      ],
      [
        "CATEGORIA" => "Asistente De Vehiculos (PDI)",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Orientación al Detalle",
        "DESCRIPCION" => "Es minucioso en el manejo de datos."
      ],
      [
        "CATEGORIA" => "Asistente De Vehiculos (PDI)",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Organización",
        "DESCRIPCION" => "Mantiene un orden en los documentos que maneja."
      ],
      [
        "CATEGORIA" => "Asistente De Vehiculos (PDI)",
        "COMPETENCIA" => "Registro y manejo de información: Controla y Registra información de manera adecuada garantizando la calidad en el registro y cumpliendo con los procedimientos establecidos.",
        "SUBCOMPETENCIA" => "Cumplimiento de Procesos Operativos",
        "DESCRIPCION" => "Cumple con los procedimientos establecidos por la organización de manera correcta."
      ],
      [
        "CATEGORIA" => "Asistente De Vehiculos (PDI)",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Claridad en el mensaje",
        "DESCRIPCION" => "Comunica sus ideas de forma clara y precisa."
      ],
      [
        "CATEGORIA" => "Asistente De Vehiculos (PDI)",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Respuesta Oportuna",
        "DESCRIPCION" => "Comparte información e ideas en el momento adecuado."
      ],
      [
        "CATEGORIA" => "Asistente De Vehiculos (PDI)",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Escucha",
        "DESCRIPCION" => "Escucha atentamente las opiniones y sugerencias."
      ],
      [
        "CATEGORIA" => "Asistente De Vehiculos (PDI)",
        "COMPETENCIA" => "Comunicación Efectiva: Intercambia opiniones e información respetando las emociones de los demás y expresando sus ideas de manera clara y oportuna.",
        "SUBCOMPETENCIA" => "Asertividad",
        "DESCRIPCION" => "Expresa sus opiniones sin herir los sentimientos de los demás."
      ],
      [
        "CATEGORIA" => "Asistente De Vehiculos (PDI)",
        "COMPETENCIA" => "Mentalidad de Dueño V2: Asume los problemas como propios, mostrando predisposición  y esforzándose por conseguir los resultados que se le plantean. ",
        "SUBCOMPETENCIA" => "Anticipación",
        "DESCRIPCION" => "Se anticipa a los requerimientos o necesidades que se presentan."
      ],
      [
        "CATEGORIA" => "Asistente De Vehiculos (PDI)",
        "COMPETENCIA" => "Mentalidad de Dueño V2: Asume los problemas como propios, mostrando predisposición  y esforzándose por conseguir los resultados que se le plantean. ",
        "SUBCOMPETENCIA" => "Solución de Problemas",
        "DESCRIPCION" => "Resuelve los problemas a pesar de las dificultades."
      ],
      [
        "CATEGORIA" => "Asistente De Vehiculos (PDI)",
        "COMPETENCIA" => "Mentalidad de Dueño V2: Asume los problemas como propios, mostrando predisposición  y esforzándose por conseguir los resultados que se le plantean. ",
        "SUBCOMPETENCIA" => "Pertenencia",
        "DESCRIPCION" => "El colaborador se siente dueño de sus procesos y los ejecuta proactivamente."
      ]
    ];

    /**
     * [
     * "competence" => "Control del Mantenimiento: Soluciona los problemas de manera efectiva. Evalúa el rendimiento de mantenimiento e identifica oportunidades de reducción de costos.",
     * "subCompetence" => "Control De Costos",
     * "category" => "Jefe De Operaciones Y Mantenimiento"
     * ],
     */

    $assignations = [
      [
        'category' => 'Asistente De Vehiculos (PDI)',
        'children' => [132, 249]
      ],
    ];

    DB::statement('SET FOREIGN_KEY_CHECKS=0;');

    EvaluationPersonCycleDetail::query()->truncate();
    EvaluationPersonCompetenceDetail::query()->truncate();
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

//    3. Definir Asignaciones
    foreach ($assignations as $assignation) {
      $category = HierarchicalCategory::where('name', $assignation['category'])->first();
      if (!$category) {
        $category = HierarchicalCategory::create([
          'name' => $assignation['category'],
          'description' => 'Description for the category ' . $assignation['category'],
          'hasObjectives' => 0,
          'excluded_from_evaluation' => 0,
        ]);

        foreach ($assignation['children'] as $item) {
          HierarchicalCategoryDetail::create([
            'hierarchical_category_id' => $category->id,
            'position_id' => $item,
          ]);
        }
      }
    }

//    foreach ($data as &$item) {
//      $category = HierarchicalCategory::withTrashed()->where('name', $item['CATEGORIA'])->first();
//      if (!$category) {
//        $this->command->info('La categoría ' . $item['CATEGORIA'] . ' no existe, por favor crearla antes de continuar.');
//      }
//    }
//    return;

//    4. Recorrer el array e insertar los datos en las tablas correspondientes
    foreach ($data as $item) {
      $category = HierarchicalCategory::where('name', $item['CATEGORIA'])->first();
      if (!$category) {
        $category = HierarchicalCategory::create([
          'name' => $item['CATEGORIA'],
          'description' => 'Description for the category ' . $item['CATEGORIA'],
          'hasObjectives' => 0,
          'excluded_from_evaluation' => 0,
        ]);
      }

      if ($category) {
        $competence = EvaluationCompetence::firstOrCreate([
          'nombre' => $item['COMPETENCIA'],
        ]);

        EvaluationSubCompetence::firstOrCreate([
          'nombre' => $item['SUBCOMPETENCIA'],
          'definicion' => $item['DESCRIPCION'],
          'competencia_id' => $competence->id,
        ]);

        $categoryCompetenceService->store([
          'category_id' => $category->id,
          'competence_id' => $competence->id,
        ]);
      }
    }

    $categoryCompetenceService->assignCompetencesToWorkers();

  }
}
