# Migración `status_deleted` → `deleted_at`

## Objetivo

Estandarizar todos los modelos nuevos/migrados a `BaseModel` para que usen
`SoftDeletes` nativo de Laravel (`deleted_at`) en vez del patrón legacy de
`web_millagp_2` (columna `status_deleted` int: 1 = activo, 0 = eliminado, con
un global scope manual `where('status_deleted', 1)`).

**`status_deleted` NO se elimina todavía.** Sigue viva porque el legacy
(`web_millagp_2`) la lee/escribe directamente sobre la misma BD compartida.
Mientras el legacy exista, cada tabla que migremos queda temporalmente con
**ambas** columnas conviviendo (una migración le agrega `deleted_at`, pero
`status_deleted` no se toca ni se sincroniza — así quedó decidido el
15/09/2026, sin trait de sync).

Este archivo es el registro central de qué tablas/modelos ya se movieron a
`deleted_at`, para que cuando `web_millagp_2` se dé de baja definitivamente
podamos:

1. Verificar que todas las tablas de esta lista ya no tienen filas donde
   `status_deleted = 0` y `deleted_at IS NULL` estén desincronizadas (si el
   legacy borró algo por su cuenta después de la migración, esa fila quedará
   con `status_deleted = 0` pero `deleted_at` NULL — hay que revisar antes de
   dropear la columna).
2. Generar una migración única que haga `dropColumn('status_deleted')` en
   todas las tablas de la lista.
3. Quitar el global scope manual `where('status_deleted', 1)` de cada modelo
   (el scope de `SoftDeletes` sobre `deleted_at` ya cubre el filtrado).

**⚠️ Por qué el scope manual (`where('status_deleted', 1)`) NO se puede quitar
todavía, aunque parezca redundante con el de `SoftDeletes`:** como no se
sincronizan las columnas, un borrado hecho desde el legacy (`status_deleted =
0`, sin tocar `deleted_at` porque el legacy no conoce esa columna) solo queda
oculto en namu-frontend gracias a ESTE scope. Si se quita antes de que el
legacy deje de escribir en estas tablas, cualquier fila borrada desde el
legacy después de este punto se seguiría viendo como "activa" en
namu-frontend. Solo es seguro quitarlo cuando `web_millagp_2` ya no
escriba en la tabla (o se dé de baja del todo).

## Tablas migradas

| Tabla | Modelo | Migración que agregó `deleted_at` | Fecha | Nota |
|---|---|---|---|---|
| `rrhh_contrato` | `App\Models\gp\gestionhumana\contratos\Contract` | `2026_09_15_090000_add_deleted_at_to_contratos_tables` | 15/09/2026 | F5 — Contratos |
| `rrhh_firmante` | `App\Models\gp\gestionhumana\contratos\Signer` | `2026_09_15_090000_add_deleted_at_to_contratos_tables` | 15/09/2026 | F5 — Contratos |
| `rrhh_plantilla_contrato` | `App\Models\gp\gestionhumana\contratos\ContractTemplate` | `2026_09_15_090000_add_deleted_at_to_contratos_tables` | 15/09/2026 | F5 — Contratos |
| `rrhh_tipo_contrato` | `App\Models\gp\gestionhumana\contratos\ContractType` | `2026_09_15_090000_add_deleted_at_to_contratos_tables` | 15/09/2026 | F5 — Contratos |

**Backfill aplicado** (`2026_09_15_091500_backfill_deleted_at_from_status_deleted_on_contratos_tables`,
15/09/2026): al agregar la columna, 211 filas ya tenían `status_deleted = 0`
(borradas en el legacy antes de esta migración) y quedaron con `deleted_at`
NULL — es decir, se hubieran visto como "activas" en namu-frontend. Se les
puso `deleted_at = COALESCE(updated_at, created_at, NOW())` como aproximación
(el legacy no guarda la fecha real de borrado). Desglose: `rrhh_contrato` 144,
`rrhh_firmante` 1, `rrhh_plantilla_contrato` 66, `rrhh_tipo_contrato` 0.

**⚠️ Checklist para la próxima tabla que se migre a este patrón**: correr
siempre este backfill (o uno equivalente) en la misma tanda que se agrega
`deleted_at`, ANTES de dar la migración por terminada — si la tabla ya tenía
filas con `status_deleted = 0`, sin backfill quedan inconsistentes.

**Comando central de backfill**: `App\Console\Commands\BackfillDeletedAtCommand`
(`php artisan deleted-at:backfill`, con `--dry-run` para solo contar sin
escribir). Reemplaza la idea de crear una migración de backfill por cada
tabla nueva: en vez de eso, se agrega el nombre de la tabla a la constante
`TABLES` de ese comando. Es idempotente (solo toca filas con `deleted_at
IS NULL`), así que también sirve para correrlo periódicamente mientras el
legacy siga borrando filas sin tocar `deleted_at`.

## Pendientes / al agregar una tabla nueva a esta lista

Cuando se migre otra tabla del módulo GH (o de cualquier otro módulo legacy)
a `deleted_at`:

1. Migración `Schema::table($tabla)->softDeletes()` (agregar, nunca reemplazar
   `status_deleted`).
2. En el modelo: `use SoftDeletes;` (nativo, `Illuminate\Database\Eloquent\SoftDeletes`),
   dejando el scope legacy `where('status_deleted', 1)` como está — no
   sincronizar columnas, no sobrescribir `delete()`/`restore()`.
3. Agregar el nombre de la tabla a `TABLES` en `BackfillDeletedAtCommand` y
   correr `php artisan deleted-at:backfill`.
4. Agregar la fila correspondiente a la tabla de arriba.

## Tablas del módulo GH que TODAVÍA usan solo `status_deleted` (sin `deleted_at`)

Pendientes de evaluar cuándo migrarlas (no son parte de este cambio, solo
referencia para no perderlas de vista):

- `rrhh_proceso_postulacion` (`RecruitmentProcess`)
- `rrhh_persona` (`Worker`)
- (completar según se vayan tocando otros módulos)
