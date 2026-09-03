# Proceso de Selección y Onboarding — Levantamiento y plan de migración a milla-backend

> Estado: **Levantamiento cerrado** — decisiones en la sección 0. Fecha: 2026-09-03.
> Fuente del proceso actual: `web_millagp_2` (Laravel + Blade, monolito legacy).
> Destino: `milla-backend` (API) + `namu-frontend` (Next.js).
> Ambos sistemas comparten la **misma base de datos** MySQL `db_milla_gp`, por lo que la
> migración es incremental: se puede reimplementar pantalla por pantalla sobre las
> mismas tablas sin migración de datos.

---

## 0. Decisiones cerradas con el área usuaria (2026-09-03)

| # | Tema | Decisión |
|---|------|----------|
| 1 | Registro de postulantes | **Manual por RRHH** (sin portal público). |
| 2 | Aprobación de ficha (`rrhh_temp_data_persona` 17/18/19) | **Se conserva igual**: el postulante edita su ficha desde su portal → cola de aprobación → RRHH aprueba/rechaza → fusión a `rrhh_persona`. |
| 3 | Carta oferta | **Generada por el sistema** desde plantilla con merge de datos (cargo, área, sede, sueldo, fecha de ingreso). |
| 4 | Firma de la carta oferta | **Bloqueante**: no se da de alta hasta tener la carta firmada (`status_carta_oferta_id = 21`). |
| 5 | Alta / baja de trabajador | **Solo RRHH central** (rol único, todas las sedes). |
| 6 | Reingreso de cesados | **Dentro del MVP** (es frecuente). |
| 7 | **Contratos** (`rrhh_contrato`) | **Dentro del alcance — versión completa con firma digital** (plantillas, generación de PDF, flujo de firmantes con certificado X.509 vía TCPDF, lotes de firma, confirmación de lectura). Se adelanta trabajo respecto al cronograma original. |
| 8 | Onboarding (tipo por cargo, cronograma, inducciones SSOMA) | **Fase posterior**, NO entra en este MVP. **Excepción**: el **email de bienvenida** (`config_onboarding`) sí se envía desde Selección. |

> ⚠️ **Riesgo de plazo**: contratos con firma digital completa es un módulo en sí mismo
> (~1.600 líneas de lógica legacy + certificados + lotes). Se aborda como **Fase 4**
> (ver sección 9). Corte natural si el 20/11 aprieta: entregar Selección hasta el alta y
> liberar Contratos poco después.

---

## 1. Resumen del proceso (de la vacante al alta como empleado)

```
┌─────────────────────┐   ┌──────────────────────────┐   ┌───────────────────────┐   ┌──────────────────┐
│ 1. PROCESO DE       │   │ 2. ADMINISTRACIÓN DE     │   │ 3. CONTRATACIÓN /     │   │ 4. SELECCIONADOS  │
│    POSTULACIÓN      │──▶│    POSTULANTES           │──▶│    CARTA OFERTA       │──▶│    / ALTA          │
│  (vacante abierta)  │   │  (registro + evaluación) │   │  (onboarding inicial) │   │  (empleado activo) │
└─────────────────────┘   └──────────────────────────┘   └───────────────────────┘   └──────────────────┘
     idVista 50                   idVista 52                   (dentro de idVista 52)        idVista 71
 rrhh_proceso_postulacion    rrhh_persona (tipo 1)        rrhh_persona (tipo 6) +        rrhh_persona (tipo 2)
                                                          carta_oferta + cronograma      + rrhh_contrato
                                                          + asig_doc + usuario           + rrhh_estado_trabajador
```

### Narrativa

1. **RRHH abre un proceso de postulación** para un `cargo` de una `área`/`sede`, indicando
   cuántos trabajadores solicita y la fecha de inicio. El plazo de cierre se calcula
   automáticamente a partir de `rrhh_cargo.plazo_proceso_seleccion` (días hábiles).
2. **RRHH registra postulantes** manualmente contra ese proceso (no hay portal público de
   postulación). Cada postulante nace como fila en `rrhh_persona` con
   `tipo_trabajador_id = 1` (POSTULANTE) y `b_empleado = 1`. Se le crea **usuario**
   (`usr_users`, username = DNI, password = DNI) y se le asignan **documentos iniciales**
   según sede/área/cargo.
3. **El postulante completa su ficha de datos** desde el portal del trabajador
   (`ManagementController` / `ApiManagementController` en el legacy). Sus cambios se
   guardan en `rrhh_temp_data_persona` con `status_id = 17` (PENDIENTE) y **RRHH los
   aprueba** (`status 19`) o **rechaza** (`status 18`); al aprobar, los campos del `temp`
   se fusionan sobre `rrhh_persona`.
4. **RRHH cambia el estado del postulante** (`cambiar_status`):
   - `SELECCIONADO` (`tipo_trabajador_id = 6`) → dispara **Contratación** (ver 5).
   - `RECHAZADO` (3), `FUERA DE CUPO` (4), `LISTA NEGRA` (5) → sólo guarda `motivo_status`.
5. **Contratación / Carta oferta** (al marcar SELECCIONADO):
   - Se fija `fecha_inicio` (fecha de ingreso) y `presupuesto` (rango salarial del cargo).
   - Se genera **carta oferta** (`config_mail_carta` como plantilla; el PDF se adjunta y se
     guarda en `rrhh_persona.carta_oferta`). `status_carta_oferta_id = 20` (PENDIENTE).
   - Se asignan **documentos iniciales obligatorios** para `tipo_trabajador_id = 6`
     (`rrhh_asig_doc_obligatorio`, `status_id = 6` pendiente).
   - Se **genera el cronograma de onboarding** del cargo: se clona
     `rrhh_cronograma / grupo / tarea` → `rrhh_cronograma_asignado / grupo_asignado /
     tarea_asignada`, con el `jefe_id` como evaluador de cada tarea.
   - Se **envía el email de carta oferta + bienvenida** al correo del postulante
     (con CC a `soporte@grupopakatnamu.com`), adjuntando la carta y los documentos de sede.
6. **Seguimiento del seleccionado** (pantalla "Seleccionados", `idVista 71`):
   - Subir **carta oferta firmada** → `status_carta_oferta_id = 21` (CONFIRMADO).
   - Enviar **email de bienvenida** (plantilla `config_onboarding`) →
     `status_envio_mail_carta_oferta = 21`.
   - **Generar / reactivar usuario** del trabajador.
   - Completar la **ficha completa** del trabajador (datos bancarios, AFP/SNP, sueldo,
     asignación familiar, SCTR, EsSalud, jefe, supervisor, centro de costo, etc.).
   - **Dar de alta** (`darAltaBaja`, `status_id = 22` Alta) o baja (`23`). Cada movimiento
     genera fila en `rrhh_estado_trabajador` y activa/desactiva el `usr_users`.
   - **Reingreso**: reasignar una persona ya cesada a un nuevo proceso.
7. **Alta efectiva**: al dar de alta y generarse el **contrato** (`rrhh_contrato`, hoy en el
   módulo legacy "Administración de Personal → Contratos", **fuera del alcance de
   Selección**), la persona queda con `tipo_trabajador_id = 2` (CONTRATADO) y
   `status_id = 22`. A partir de aquí es un empleado normal (planilla, asistencia,
   evaluaciones, viáticos…).

---

## 2. Catálogos de estados (tabla `config_status`)

| id | estado | tipo | Uso |
|----|--------|------|-----|
| 9  | ABIERTO | tipo_4 | `rrhh_proceso_postulacion.status_id` — proceso recién creado |
| 10 | EN PROCESO | tipo_4 | proceso con al menos un postulante registrado |
| 11 | CERRADO | tipo_4 | proceso finalizado (`finalizar()` / `fecha_fin_cierre`) |
| 17 | PENDIENTE | tipo_7 | `rrhh_temp_data_persona.status_id` — cambio de ficha por aprobar |
| 18 | RECHAZADO | tipo_7 | cambio de ficha rechazado (`obs_rechazado`) |
| 19 | APROBADO | tipo_7 | cambio de ficha aprobado y fusionado a `rrhh_persona` |
| 20 | PENDIENTE | tipo_8 | `status_carta_oferta_id` / `status_envio_mail_carta_oferta` |
| 21 | CONFIRMADO | tipo_8 | carta oferta firmada subida / email enviado |
| 22 | Alta | tipo_9 | `rrhh_persona.status_id` y `rrhh_estado_trabajador.estado` — activo |
| 23 | Baja | tipo_9 | cesado |

### `rrhh_tipo_empleado` (`rrhh_persona.tipo_trabajador_id`)

| id | name | Etapa |
|----|------|-------|
| 1 | POSTULANTE | registrado en un proceso, en evaluación |
| 2 | CONTRATADO | empleado con contrato vigente |
| 3 | RECHAZADO | descartado en el proceso |
| 4 | FUERA DE CUPO | apto pero sin vacante |
| 5 | LISTA NEGRA | no recontratable |
| 6 | SELECCIONADO | elegido; en proceso de contratación/onboarding |

> Nota: en producción hay `tipo_trabajador_id = 4` con `b_empleado = 1` para empleados
> activos (93 filas). Es una inconsistencia del legacy — validar reglas al migrar.

---

## 3. Tablas involucradas

| Tabla | Rol | Modelo milla-backend existente |
|-------|-----|-------------------------------|
| `rrhh_proceso_postulacion` | Vacante / proceso | ❌ (crear `RecruitmentProcess`) |
| `rrhh_persona` | Postulante → seleccionado → empleado (misma fila) | ✅ `Worker` (`personal/Worker.php`) |
| `rrhh_temp_data_persona` | Cambios de ficha por aprobar | ❌ |
| `rrhh_log_data_persona` | Historial de cambios de ficha (log append-only) | ❌ (parcial en otros) |
| `rrhh_asig_doc_obligatorio` | Documentos iniciales asignados a la persona | ❌ |
| `config_doc_obligatorio_inic` | Catálogo de documentos por sede/área/cargo/tipo | ❌ |
| `rrhh_cronograma` / `_grupo_cronograma` / `_tareas_cronograma` | Plantilla de cronograma de onboarding por cargo | ❌ |
| `rrhh_cronograma_asignado` / `_grupo_asignado` / `_tarea_asignada` | Cronograma instanciado por persona | ❌ |
| `config_mail_carta` | Plantilla de carta oferta | ❌ |
| `config_onboarding` | Plantilla de email de bienvenida | ❌ |
| `onboarding` | Seguimiento de onboarding (llamadas día 1/2/3/7/15, visita sede) | ❌ |
| `rrhh_tipo_onboarding` | Catálogo de tipos de onboarding | ❌ |
| `rrhh_contrato` | Contrato laboral (módulo aparte, fuera de alcance) | ✅ `WorkerContract` |
| `rrhh_estado_trabajador` | Historial alta/baja | ✅ `WorkerStatusHistory` |
| `usr_users` | Usuario del sistema (login DNI/DNI) | ✅ `User` |
| `rrhh_parientes` | Familiares / carga familiar | ❌ (revisar) |
| `rrhh_experiencia_laboral` | Experiencia laboral declarada | ❌ |

### Esquema `rrhh_proceso_postulacion`

```
id, nombre_postulacion (varchar 250), status_id (9/10/11), cant_trab_solicita (int),
sede_id, area_id, cargo_id, centro_costo_id, fecha_inicio (date),
fecha_fin_plazo (date, calculada), fecha_fin_cierre (date), dias_plazo (int),
status_deleted (int 0/1), created_at, updated_at
```

### Esquema `rrhh_asig_doc_obligatorio`

```
id, placa_id, persona_id, doc_inic_id (→ config_doc_obligatorio_inic),
doc_adjunto (varchar 250), fecha_vencimiento, fecha_hora_adjunto,
fecha_hora_porvalidar, status_id (6 pendiente), status_deleted, write_id,
created_at, updated_at, observado
```

### Esquema `onboarding`

```
id, persona_id, tipo_proceso (int), status_envio_manual (int),
status_envio_cronograma (int), visita_sede_id (int),
llamada_1, llamada_2, llamada_3, llamada_7, llamada_15 (int, checklist),
observaciones (varchar 255), status_id, tipo_onboarding_id, status_deleted,
created_at, updated_at
```

---

## 4. Controladores legacy de referencia (`web_millagp_2`)

| Archivo | idVista | Métodos clave |
|---------|---------|---------------|
| `app/Http/Controllers/Reclutamiento/ProcesoPostulacionController.php` | 50 | `index`, `save`, `edit`, `finalizar` (status→11), `delete`, `fetchArea`, `fetchCargo`, `sumasdiasemana` (cálculo de plazo) |
| `app/Http/Controllers/Reclutamiento/AdministracionPostulanteController.php` | 52 | `index`, `save` (crea postulante + usuario + docs), `edit`, `cambiar_status` (SELECCIONADO → carta oferta + cronograma + email), `aprobar`/`rechazar` (temp data), `obtenerjefes`, `obtenerPresupuestos`, `download` |
| `app/Http/Controllers/Reclutamiento/SeleccionadoController.php` | 71 | `index` (datatable tipo 2 y 6), `subir_cartafirmada` (→21), `enviar_mail_bienvenida` (→21), `generarUsuario`, `darAltaBaja` (22/23 + `rrhh_estado_trabajador`), `reingreso`, `grabarPersonaEditar` (ficha completa), `asignarEvaluadores`, parientes / experiencia, `PartnerPDF`, `carnetPdf` |
| `app/Http/Controllers/Reclutamiento/FueraCupoController.php` | — | `postular` (repostular un "fuera de cupo") |
| `app/Http/Controllers/Reclutamiento/ListaNegraxRechazadosController.php` | — | listado |
| `app/Http/Controllers/Configuraciones/TemplateCartaOfertaController.php`, `TemplateOnboardingController.php`, `TemplateDiaAntesController.php`, `TemplateLiderController.php` | — | plantillas de correo |

Vistas: `resources/views/reclutamiento/{proceso_postulacion,admin_postulantes,seleccionado,FueraCupo,ListaNegraxRechazados}/`
JS: `resources/js/reclutamiento/**`

---

## 5. Ejemplo real trazado — trabajador `rrhh_persona.id = 9036`

**PEREZ CABALLERO OSCAR DAVID** (DNI 32987755), CONDUCTOR, sede 1, cargo 11.
Ingresó, trabajó ~2 meses y renunció. Rastro completo:

| # | Evento | Tabla / registro | Fecha |
|---|--------|------------------|-------|
| 1 | Registrado como POSTULANTE en proceso 556 | `rrhh_persona` (tipo 1), `rrhh_log_data_persona #11669` (tipo_empleado 1, proceso 556, cargo 11) | 2025-08-01 16:57 |
| 2 | Usuario creado | `usr_users #2862` (username 32987755, partner_id 9036, status_deleted 1) | 2025-08-01 16:57 |
| 3 | Documentos iniciales asignados (doc_inic_id 31–43+) | `rrhh_asig_doc_obligatorio` (status_id 6) | 2025-08-13 12:10 |
| 4 | Marcado SELECCIONADO | `rrhh_log_data_persona #11778` (tipo_empleado 6) | 2025-08-13 12:10 |
| 5 | Alta | `rrhh_estado_trabajador #4477` (estado 22, sede 1) + `rrhh_persona.status_id = 22` | 2025-08-15 08:11 |
| 6 | Contrato generado (tipo 4, sueldo 2000, 15/08→31/10) | `rrhh_contrato #10033` (lote CE-LOTE-1, firmante 1, conformidad_rrhh 1, firma solicitada y confirmada) | 2025-08-15 08:53–09:33 |
| 7 | Cambios de ficha posteriores (cargo 11) | `rrhh_log_data_persona #11796–12072` | ago–sep 2025 |
| 8 | **Baja** — "RENUNCIA VOLUNTARIA POR TEMAS LABORALES" | `rrhh_estado_trabajador #4551` (estado 23) | 2025-10-08 |
| 9 | Reasignado a proceso 714 como SELECCIONADO (reingreso en curso) | `rrhh_persona.proceso_postulacion_id = 714`, `tipo 6`, `rrhh_log_data_persona #13863` | 2026-05-02 |

Observaciones de la traza:
- `rrhh_persona` es **una sola fila mutada** a lo largo de todo el ciclo de vida
  (postulante→seleccionado→contratado→cesado→reingreso). El historial vive en
  `rrhh_log_data_persona` y `rrhh_estado_trabajador`.
- El `proceso_postulacion_id` de la persona apunta **al último proceso** (se sobrescribe en
  reingreso). Para saber "por qué proceso entró originalmente" hay que mirar el primer
  `rrhh_log_data_persona`.
- La carta oferta y su firma **no siempre se completan** (`status_carta_oferta_id = 20` en
  este caso), el proceso real es más laxo que el diseño.
- El contrato (`rrhh_contrato`) lo generó el módulo de Contratos, no Selección.

---

## 6. Qué implica llevar TODO el proceso en Milla — checklist funcional

### Etapa 1 — Proceso de postulación
- [ ] CRUD de `rrhh_proceso_postulacion` (crear / listar con paginación / editar / anular).
- [ ] Cálculo automático de `fecha_fin_plazo` = `fecha_inicio` + `rrhh_cargo.plazo_proceso_seleccion` días hábiles.
- [ ] Selects dependientes sede → área → cargo (`centro_costo_id` se hereda del área).
- [ ] Acción **Finalizar proceso** (`status_id → 11`, `fecha_fin_cierre = hoy`).
- [ ] Listado con conteo de postulantes por proceso y estado del proceso.

### Etapa 2 — Administración de postulantes
- [ ] Registrar postulante contra un proceso (nace `tipo_trabajador_id = 1`, `b_empleado = 1`,
      hereda sede/área/cargo/centro_costo del proceso).
- [ ] Validación anti-duplicado (`b_empleado = 1` + `sede_id` + `vat`).
- [ ] Subida de CV y foto (disk `private`, rutas `resources_personas/cv/{id}`, `.../profile/{id}`).
- [ ] Crear `usr_users` (username = DNI, password = DNI).
- [ ] Asignar documentos iniciales (`config_doc_obligatorio_inic` filtrado por
      `tipo_trabajador_id` + sede/área/cargo con `LIKE`) → `rrhh_asig_doc_obligatorio`.
- [ ] Cambiar `rrhh_proceso_postulacion.status_id` a 10 al registrar el primer postulante.
- [ ] Editar postulante (con `rrhh_log_data_persona` en cada cambio).
- [ ] **Aprobar / rechazar cambios de ficha** (`rrhh_temp_data_persona` 17→19 / 17→18,
      fusión de campos no vacíos sobre `rrhh_persona`).
- [ ] Cambiar estado del postulante: SELECCIONADO (6) / RECHAZADO (3) / FUERA DE CUPO (4) / LISTA NEGRA (5), con `motivo_status`.
- [ ] Vistas "Fuera de cupo" y "Lista negra / Rechazados" + acción **repostular**.

### Etapa 3 — Contratación / carta oferta
- [ ] Al marcar SELECCIONADO: capturar `fecha_inicio` (ingreso), `presupuesto`, `jefe_id`.
- [ ] **Generar carta oferta (PDF)** desde plantilla `config_mail_carta` con merge de datos
      (`{$cargo} {$area} {$sede} {$postulante}` + sueldo + fecha ingreso) → guardar en
      `rrhh_persona.carta_oferta`. `status_carta_oferta_id = 20`.
- [ ] Asignar documentos iniciales para `tipo_trabajador_id = 6`.
- [ ] Enviar **email de carta oferta** con adjuntos (carta + `config_sede.doc1_rrhh..doc8_rrhh`), CC configurable.
- [ ] ~~Instanciar cronograma de onboarding del cargo~~ → **fase posterior (Onboarding)**.

### Etapa 4 — Seleccionados / alta
- [ ] Listado datatable de `tipo_trabajador_id IN (2, 6)` con estado de carta oferta, email y alta/baja.
- [ ] Subir carta oferta **firmada** → `status_carta_oferta_id = 21`. **Requisito bloqueante para el alta.**
- [ ] Enviar **email de bienvenida** (plantilla `config_onboarding`) → `status_envio_mail_carta_oferta = 21` + fecha.
- [ ] **Generar / reactivar** `usr_users`.
- [ ] Formulario de **ficha completa** del trabajador (datos bancarios haberes/CTS,
      AFP/SNP + CUSPP, sueldo, asignación familiar, escolaridad, SCTR, EsSalud vida,
      centro de costo, jefe, supervisor — con reasignación de evaluaciones pendientes al
      cambiar supervisor).
- [ ] Gestión de **parientes** (`rrhh_parientes`) y **experiencia laboral** (`rrhh_experiencia_laboral`).
- [ ] **Dar de alta / baja** — solo rol RRHH central — (`rrhh_persona.status_id` 22/23 +
      fila en `rrhh_estado_trabajador` + activar/desactivar `usr_users`). Ya existe
      `WorkerStatusHistoryController` en milla-backend; **reusar**. Validar carta firmada antes del alta.
- [ ] **Reingreso** de personas cesadas a un nuevo proceso (dentro del MVP).
- [ ] PDFs: ficha del trabajador (`PartnerPDF`), carnet con QR (`carnetPdf`).

### Etapa 5 — Contratos (versión completa con firma digital)
Legacy de referencia: `app/Http/Controllers/AdministracionPersonal/ContratoController.php`
(~1.629 líneas), `PlantillaContratoController`, `Configuraciones/FirmantesController`,
`Configuraciones/TipoContratoController`. Firma con **TCPDF** (`elibyy/tcpdf-laravel`) +
certificado X.509 por firmante (`.crt` + `.key` + password + imagen de firma).

- [ ] CRUD **tipos de contrato** (`rrhh_tipo_contrato`: días de vacaciones, descripción…).
- [ ] CRUD **plantillas de contrato** (`rrhh_plantilla_contrato` / `template_contrato_id`) con editor HTML y variables de merge.
- [ ] CRUD **firmantes** (`rrhh_firmante`: certificado, key, password, imagen de firma) — subida segura de credenciales.
- [ ] **Crear contrato** para un empleado: tipo, fechas inicio/fin, sueldo, cargo, sede, plantilla, firmante(s) principal y secundario, `grupo_contrato`, `lote`.
- [ ] Generación de **PDF sin firma** (`generatePDFSF`) para revisión.
- [ ] Flujo por **lotes** de firma: `SolcititudFirmalote` → `viewFirmarlote` (firmante firma con su certificado) → `viewGHlote` / `aprobarGHlote` (conformidad RRHH) → `enviartrabajador`.
- [ ] Flujo individual: `solicitarfirma` → `solicitarfirmante` → `aprobarSolicitud` → `firmarContrato` (aplica firma digital TCPDF) → `downloadfirmado`.
- [ ] **Confirmación de lectura** del trabajador (`confirmarlectura`, `conformidad_lectura`, `fecha_lectura`).
- [ ] Estados en `rrhh_contrato`: `solicitar_firma`, `estado_envio_email`, `confirmacion_firmante`, `conformidad_rrhh`, con sus fechas.
- [ ] Carga masiva (`masivoTrabajador`, `masivoFirmante`, `masivoGH`).
- [ ] Reporte de **contratos vencidos** / por vencer (`ContratosVencidosExport`, `MailContratosVencidos`).
- [ ] Reusar / extender `WorkerContract` (ya mapea `rrhh_contrato` con `salaryForWorkerAtDate`).

### Transversal
- [ ] **Permisos por vista** en `namu-frontend` / `gp/gestion-humana`: "Proceso de
      Postulación", "Administración de Postulantes", "Seleccionados", "Contratos"
      (equivalentes a idVista 50 / 52 / 71 + contratos del legacy).
- [ ] Plantillas de correo configurables (carta oferta, bienvenida, día antes, líder).
- [ ] Emails con el layout Apple-minimalista ya adoptado en Milla (ver memoria
      `project_viaticos_email_redesign`, `project_evaluation_email_redesign`).

---

## 7. Fuera de alcance de este MVP

- **Onboarding operativo**: tipo de onboarding por cargo, cronograma de actividades
  instanciado (`rrhh_cronograma_asignado*`), seguimiento (`onboarding`: llamadas día
  1/2/3/7/15, visita a sede), documentos iniciales como checklist de cumplimiento,
  **inducciones SSOMA** (`sso_inducciones_clientes` — validar activación). → **Fase siguiente**.
  - *Excepción*: el **email de bienvenida** (`config_onboarding`) sí se envía desde Selección.
- Portal público de postulación / bolsa de trabajo externa (hoy no existe, no se hará).
- Cese completo con LBS automática, entrega de equipos (Fase 4 de otro plan — ver
  comentario en `WorkerStatusHistory.php`).

> **Contratos SÍ entran** en este alcance (decisión #7), como Fase 5 / track paralelo.

---

## 8. Arquitectura propuesta en milla-backend

Seguir el patrón del proyecto (`Controller` fino → `Service` → `Resource`, `FormRequest`
por acción, `BaseModel` con `Auditable`, respuestas `success()/error()`):

```
app/Models/gp/gestionhumana/reclutamiento/
  RecruitmentProcess.php            (rrhh_proceso_postulacion)
  ApplicantDataChange.php           (rrhh_temp_data_persona)
  PersonDataLog.php                 (rrhh_log_data_persona)
  MandatoryDocAssignment.php        (rrhh_asig_doc_obligatorio)
  MandatoryDocConfig.php            (config_doc_obligatorio_inic)
  OfferLetterTemplate.php           (config_mail_carta)
  WelcomeEmailTemplate.php          (config_onboarding)
  Relative.php                      (rrhh_parientes)
  WorkExperience.php                (rrhh_experiencia_laboral)

app/Models/gp/gestionhumana/personal/   (Fase 5 — contratos)
  ContractTemplate.php              (rrhh_plantilla_contrato)
  ContractType.php                  (rrhh_tipo_contrato)
  Signer.php                        (rrhh_firmante)
  # WorkerContract.php ya existe (rrhh_contrato) — extender

app/Http/Controllers/gp/gestionhumana/reclutamiento/
  RecruitmentProcessController.php
  ApplicantController.php
  SelectedWorkerController.php
  # OnboardingController.php → fase posterior

app/Http/Services/gp/gestionhumana/reclutamiento/
  RecruitmentProcessService.php
  ApplicantService.php        (registro, docs, usuario, cambio de estado)
  OfferLetterService.php      (carta oferta PDF + email)
  SelectedWorkerService.php   (ficha completa, alta/baja, reingreso)

app/Http/Services/gp/gestionhumana/personal/   (Fase 5)
  ContractService.php         (crear, generar PDF, lotes)
  ContractSignatureService.php (firma digital TCPDF + certificado X.509)

routes/api.php  → grupos prefix 'gp/gestionhumana/reclutamiento' y '.../personal'
```

- **Reusar** `Worker`, `WorkerStatusHistory`, `WorkerContract`, `User`, `EmailService`,
  `DigitalFileService`.
- Contratos: paquete de firma ya disponible en el ecosistema (`elibyy/tcpdf-laravel`);
  evaluar si milla-backend ya lo tiene o hay que añadirlo.
- Usar el trait `App\Http\Traits\Reportable` en los modelos que se exporten a Excel
  (ver memoria `feedback_reportable_trait`).
- FKs a `usr_users`: usar `integer()` y tabla `usr_users` (ver memoria `feedback_fk_usr_users`).
- Toda consulta a BD durante el desarrollo: `php artisan tinker` (memoria `feedback_use_tinker`).

---

## 9. Plan de trabajo mapeado al cronograma

| Fase | Fechas objetivo | Entregable milla-backend + namu-frontend |
|------|-----------------|------------------------------------------|
| Levantamiento | hecho (03/09) | **Este documento** + decisiones sección 0 |
| **F1 — Postulación** | 04/09–25/09 | `RecruitmentProcess` CRUD + cálculo de plazo + finalizar. Registro/edición de postulantes + `usr_users` + docs iniciales. Cola de aprobación de ficha (`rrhh_temp_data_persona` 17/18/19). Cambio de estado (SELECCIONADO/RECHAZADO/FUERA DE CUPO/LISTA NEGRA). Pantallas namu: "Proceso de Postulación", "Administración de Postulantes" |
| Pruebas F1 | 28–30/09 | 2 días internos + 1 día con área usuaria |
| **F2 — Contratación + alta** | 01/10–28/10 | Carta oferta generada (plantilla + PDF merge + email). Pantalla "Seleccionados": subir carta firmada (bloqueante), email de bienvenida, generar usuario, ficha completa del trabajador, parientes/experiencia, **alta/baja** (reusar `WorkerStatusHistory`), **reingreso**, PDFs ficha/carnet |
| Pruebas F2 | 29/10–02/11 | 2 días internos + 1 día con área usuaria |
| **F3 — Cierre + permisos** | 03/11–16/11 | Vistas "Fuera de cupo" / "Lista negra" + repostular. **Permisos por vista** (50/52/71). Endurecer validaciones y reportes Excel (`Reportable`) |
| Pruebas F3 | 17–19/11 | 2 días internos + 1 día con área usuaria |
| **Presentación** | 20/11 | Selección (hasta el alta) en producción |
| **F5 — Contratos (track paralelo)** | arranca 01/10, entrega ~2ª quincena nov | Tipos de contrato + plantillas + firmantes (certificado X.509) + crear contrato + PDF sin firma + flujo de firma individual y por lotes + confirmación de lectura + contratos vencidos. Pantalla namu: "Contratos". *Si no llega al 20/11, entrega inmediatamente después sin bloquear la presentación.* |
| Onboarding | Fase siguiente (post 20/11) | Tipo de onboarding por cargo, cronograma instanciado, seguimiento, inducciones SSOMA |

---

## 10. Estado del levantamiento

Todas las preguntas de negocio quedaron **cerradas** — ver **sección 0**. Sub-decisiones
técnicas pendientes de confirmar durante el desarrollo (no bloquean el arranque):

1. ¿`milla-backend` ya tiene un paquete de PDF con firma digital, o se añade `elibyy/tcpdf-laravel`?
2. ¿Los certificados de firmantes se guardan en disco `private` (como el legacy) o en el `DigitalFileService` de Milla?
3. ¿El email de bienvenida se manda al confirmar la carta firmada, al dar de alta, o es acción manual? (hoy es manual)
4. Migrar plantillas existentes (`config_mail_carta`, `config_onboarding`, `rrhh_plantilla_contrato`) tal cual, o rehacerlas con el layout Apple-minimalista.
```
