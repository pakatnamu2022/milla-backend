# Seguimiento de Entregas — Proyecto Planillas (Grupo Pakatnamu)
**Base de fechas:** cronograma oficial del proyecto (17/08/26 → 21/12/26), confirmado por el usuario 2026-08-26.
**Relación con el resto de la documentación:**
- Detalle técnico de gaps/estado del sistema → [`PLAN_IMPLEMENTACION_PLANILLAS.md`](./PLAN_IMPLEMENTACION_PLANILLAS.md)
- Levantamiento de negocio original → [`LEVANTAMIENTO_PROCESO_PLANILLAS_TP_SAC.md`](./LEVANTAMIENTO_PROCESO_PLANILLAS_TP_SAC.md)

**Cómo usar este documento:** es el tablero de avance por entregable del cronograma (no reemplaza al plan técnico, lo resume a nivel de "qué le mostramos al cliente y cuándo"). Cada entregable se marca con fecha real de cierre + qué se puede demostrar en vivo. La próxima presentación es el **28/08/26**.

---

## 0. Próxima presentación: 28/08/26

Corresponde al cierre de **"Aportes SCTR, EsSalud, Vida Ley"** (17/08 → 28/08, 10 días hábiles). Lo que se debe poder mostrar ese día: por cada trabajador activo de TP SAC, el sistema calcula y muestra correctamente en `gh_payroll_register`:
- SCTR (salud + pensión), solo para puestos marcados como riesgo (`is_risk_position`)
- Aporte ESSALUD (9%, con piso RMV)
- Vida Ley

**Estado actual (2026-08-26):** en construcción — ver sección 1.

---

## 1. Aportes SCTR, EsSalud, Vida Ley — ✅ IMPLEMENTADO (2026-08-26)
**Ventana cronograma:** lun 17/08/26 → vie 28/08/26 (10 días)

- [x] Fuente de tasas: **`general_masters`** (patrón ya usado para RMV/horas/recargo nocturno). El referencial (RMV, ES=EsSalud, RMA) ya estaba sembrado; se agregaron `SCTR SALUD`/`SCTR PENSION`/`IGV` y se reutilizó el id `ES-VI` (sin uso real) como tasa de Vida Ley.
- [x] Columnas de `gh_payroll_register` completadas: `sctr_health`, `sctr_pension`, `sctr_total`, `essalud_employer`, `life_insurance`, `employer_contributions_total`
- [x] Base remunerativa: `total_income` (básico + asig. familiar + horas extra 25/35% + feriado + descanso compensatorio + bono nocturno + bonos producción/comercial) para SCTR/EsSalud; `basic_salary` para Vida Ley (así calcula el Excel real de la aseguradora).
- [x] **Hallazgo importante:** la regla planeada en Fase 1 ("SCTR = cargos de riesgo") no correspondía con la realidad — se encontraron cargos de oficina en la lista real de afiliados. El usuario indicó que ya existen `rrhh_persona.estado_sctr`/`entidad_sctr` — **se usa `estado_sctr='SI'`** en vez de una regla por cargo.
- [x] EsSalud: 9% sobre `total_income`, piso RMV.
- [x] Vida Ley: `(básico × 3.12%) × 1.18 IGV ÷ 12`.
- [x] Implementado en `PayrollRegisterService::calculateEmployerContributions()`, reemplaza el bloque hardcodeado a `0.00`.
- [x] Validado contra caso real (Elizabeth Alban Talavera, DNI 47620737): SCTR salud=9.07, SCTR pensión=9.07, Vida Ley=5.56 → **coincide exacto** con `SCTR JUNIO 2026 TP SAC.xls` y `CALCULO VIDA LEY...xlsx`.
- [x] Corrida masiva real vía `PayrollRegisterService::generate(1, 47)` (TP SAC, Junio 2026) — **576 → 92 registros** tras el fix de scope (ver bug abajo).
- [x] **Bug encontrado y corregido en la corrida real:** `generate()` traía los 576 registros históricos de `rrhh_persona` de la empresa (incluye personal cesado/inactivo de años atrás) en vez de solo los trabajadores activos actuales. Corregido agregando el scope `Worker::working()` (`status_deleted=1` + `b_empleado=1` + `status_id=22`) → universo correcto: **92 trabajadores activos** en TP SAC.
- [x] Con datos de cálculo (`gh_payroll_calculations`) presentes, el flujo completo funciona end-to-end: ej. FLORES MOROCHO CESAR MIGUEL (SCTR=SI) → básico 565.00, total_income 910.36 → SCTR salud 4.55 + pensión 4.55, EsSalud 101.70 (piso RMV), Vida Ley 1.73.
- [ ] **Gap upstream identificado (no es de este entregable):** `gh_payroll_calculations` (básico/horas/bonos por trabajador-periodo) solo tiene 2 de 92 trabajadores calculados para Junio 2026 TP SAC — depende de `PayrollScheduleService::generatePayrollCalculations()`, que a su vez depende de horarios/asistencias (`gh_payroll_schedules`) cargados, y esos también están muy incompletos para ese periodo (60 registros para 92 trabajadores). Sin eso, SCTR/EsSalud/Vida Ley calculan sobre básico=0 para la mayoría. Es el módulo de "Cálculo"/asistencias, no el de aportes patronales — a definir con el usuario si se prioriza antes del 28/08 o se corre con un periodo/subconjunto donde sí haya asistencia cargada.
- [ ] Demo lista para el 28/08 — pendiente decidir con qué dataset se demuestra (ideal: periodo con asistencia completa, o cargar asistencia real de un grupo pequeño de trabajadores para la demo)

**Nota de calidad de dato (no bloqueante):** `estado_sctr` tiene algo de desfase — un trabajador del Excel real de junio 2026 figura `NO` en BD. Es tarea de RRHH mantenerlo actualizado, no es un bug del cálculo.

**Commit:** `e3abe014` (implementación aportes SCTR/EsSalud/Vida Ley) + `52486747` (fix scope `working()` en `generate()`), rama `hector` — migración `2026_08_26_120000_add_sctr_vida_ley_igv_rates_to_general_masters_table.php` + `GeneralMaster.php` + `PayrollRegisterService.php`.

---

## 2. Retenciones AFP / ONP (validadas contra boleta real) — ⚪ PENDIENTE
**Ventana:** lun 31/08/26 → vie 11/09/26 (10 días) — depende de (1)

- [ ] Tasas por AFP (Habitat/Integra/Prima/Profuturo: aporte obligatorio + prima seguro + comisión, fija o mixta) — catálogo `rrhh_sist_pensiones` ya existe con `obl/prima_seg/com_var`, falta enganchar al cálculo
- [ ] ONP (13%)
- [ ] Validar contra boleta real de un trabajador (referencia: caso "ALBAN TALAVERA ELIZABETH KIARAMELIZA" ya compartido)

---

## 3. Bolsa de trabajo de conductores — alcance y reglas de negocio — ⚪ PENDIENTE (Levantamiento)
**Ventana:** lun 14/09/26 → mar 15/09/26 (2 días)

- [ ] Sesión de levantamiento con el usuario/RRHH

---

## 4. Archivo de pago bancario masivo + asiento contable automático — ⚪ PENDIENTE
**Ventana:** lun 14/09/26 → vie 25/09/26 (10 días, en paralelo con la anterior)

- [ ] Confirmar si existe módulo contable existente al que enganchar el asiento (pregunta abierta del plan técnico, sección 2, ítem 4)
- [ ] Plantillas de carga masiva por banco: BBVA/BCP/Interbank/Caja Trujillo/Caja Piura (pregunta abierta, ítem 5)

---

## 5. Bolsa de trabajo de conductores — ⚪ PENDIENTE (Desarrollo)
**Ventana:** mié 16/09/26 → vie 2/10/26 (13 días)

---

## 6. Boleta individual y consolidado de planilla — ⚪ PENDIENTE
**Ventana:** lun 28/09/26 → vie 9/10/26 (10 días)

- [ ] Reparar `payslip` (relaciones `earnings()/deductions()/employerContributions()` inexistentes, detectado en auditoría Fase 0)
- [ ] Consolidado `gh_payroll_register` completo (depende de que (1) y (2) ya estén cerrados)

---

## 7. Especificidades de cálculo por sede (AP/DP/GP) — alcance — ⚪ PENDIENTE (Levantamiento)
**Ventana:** lun 5/10/26 → mar 6/10/26 (2 días)

---

## 8. Gratificación — cálculo automático — ⚪ PENDIENTE
**Ventana:** lun 12/10/26 → vie 23/10/26 (10 días)

- [ ] Remuneración computable (básica + asig. familiar + promedio 6 meses vía `calcularPromedioUltimos6Meses()`, ya real)
- [ ] Gratificación (sueldo computable/6 × meses) + bonificación extraordinaria 9%

---

## 9. Especificidades de cálculo por sede (AP/DP/GP) — Desarrollo — ⚪ PENDIENTE
**Ventana:** mié 7/10/26 → vie 6/11/26 (23 días)

---

## 10. CTS + ajuste Ley de Servicios de Guardianía y Habilitación — ⚪ PENDIENTE
**Ventana:** lun 26/10/26 → vie 6/11/26 (10 días)

---

## 11. Renta de 5ta, subsidios EsSalud, cese y liquidación automática — ⚪ PENDIENTE
**Ventana:** lun 9/11/26 → vie 20/11/26 (10 días)

- [ ] Tabla `payroll_tax_brackets` (tramos UIT 2026)
- [ ] Proyección anual + retención en 10 cuotas desde agosto
- [ ] Subsidios EsSalud (regla de los 20 días — pendiente de confirmar con RRHH)
- [ ] LBS automática al cese (CTS trunca, vacaciones pendientes/truncas, gratificación trunca)

---

## 12. Pruebas integrales — QA, incidencias, capacitación — ⚪ PENDIENTE
**Ventana:** lun 23/11/26 → vie 18/12/26 (20 días)

---

## 13. Planillas completo en producción — cierre del proyecto — ⚪ PENDIENTE
**Fecha:** lun 21/12/26

---

## Historial de avance (bitácora corta)

| Fecha | Entregable | Qué se hizo | Commit/PR |
|---|---|---|---|
| 2026-08-26 | (1) Aportes SCTR/EsSalud/Vida Ley | Inicio: exploración de código real (`gh_payroll_register`, `GeneralMaster`, `is_risk_position`) para definir dónde engancha el cálculo | — |
| 2026-08-26 | (1) Aportes SCTR/EsSalud/Vida Ley | Implementación de fórmulas + validación aislada contra Excel real | `e3abe014` |
| 2026-08-26 | (1) Aportes SCTR/EsSalud/Vida Ley | Corrida real `generate(1,47)` (Junio TP SAC): detectado y corregido bug de scope (576→92 trabajadores activos); identificado gap upstream en `gh_payroll_calculations`/asistencias | `52486747` |

---
*Actualizar este documento después de cada avance real (no al final del sprint) — es el tablero que se usa para preparar cada presentación quincenal/mensual al cliente. El detalle técnico de cómo se implementa cada punto vive en `PLAN_IMPLEMENTACION_PLANILLAS.md`.*
