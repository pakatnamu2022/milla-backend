# Catálogo de Vistas y Permisos — Selección, Onboarding y Ficha del Trabajador

> Archivo vivo. Cada vez que se agrega una pantalla nueva en `namu-frontend` dentro
> del módulo **Gestión de Personal** (`/gp/gestion-humana/gestion-de-personal/...`)
> o **Reclutamiento**, se registra aquí primero y luego se traslada al seeder
> idempotente correspondiente.
>
> Patrón de referencia: `Database\Seeders\gp\gestionhumana\payroll\PayrollViewsPermissionsSeeder`
> (usa `View::updateOrCreate` + `Permission::updateOrCreate` + `PermissionService::savePermissionsToRole`).
>
> Convención de `code`: `<route>.<action>` con action ∈ `view | create | update | delete`.
> `policy_method` = mismo nombre del action.
> `company_id` (GP) = **4**. `idPadre` legacy del árbol GH = **381**.

---

## 0. Estado actual en BD (ya existe, NO recrear)

| view id | descripción | slug / route | parent_id | company_id |
|--------:|-------------|--------------|----------:|-----------:|
| 77  | Gestión Humana (raíz GH) | — | — | 4 |
| 456 | Gestión de Personal | `gestion-de-personal` | 77 | 4 |
| 457 | Trabajadores | `trabajadores` | 456 | 4 |
| 45  | Reclutamiento y Selección (legacy) | `reclutamiento-y-seleccion` | 77 | 4 |
| 52  | Gestión de reclutamiento y selección (legacy) | — | 45 | 4 |
| 71  | Administración de trabajadores (legacy) | — | 46 | 4 |

Permisos ya creados para la vista **457 (Trabajadores)**:

| permission id | code | policy_method |
|--------------:|------|---------------|
| 410 | `trabajadores.view`   | view |
| 411 | `trabajadores.create` | create |
| 412 | `trabajadores.update` | update |
| 413 | `trabajadores.delete` | delete |

---

## 1. Ficha del Trabajador — Detalle de Perfil (Fase actual)

Pantalla nueva en frontend: `trabajadores/[id]` (Detalle) con pestañas que replican
el modal legacy *Detalle Perfil* de `reclutamiento/seleccionados`.

Decisión de diseño de permisos: **las pestañas NO son vistas nuevas**; son
sub-permisos de la vista 457 (misma entrada de menú). Así el menú no se llena de
hijos y el control es por acción dentro de la ficha.

| code | nombre | policy_method | notas |
|------|--------|---------------|-------|
| `trabajadores.detalle.view`            | Ver detalle de trabajador        | view | pestaña Datos Personales (lectura ampliada) |
| `trabajadores.datos-personales.update` | Editar datos personales          | update | reemplaza `grabarPersonaEditar` legacy |
| `trabajadores.vacaciones.view`         | Ver vacaciones del trabajador    | view | `rrhh_vacaciones` + `rrhh_tipo_contrato.dias_vacaciones` |
| `trabajadores.altas-bajas.view`        | Ver altas y bajas                | view | `rrhh_estado_trabajador` (ya hay `WorkerStatusHistory`) |
| `trabajadores.contratos.view`          | Ver contratos del trabajador     | view | `rrhh_contrato` (ya hay `WorkerContract` parcial) |
| `trabajadores.prestamos.view`          | Ver préstamos del trabajador     | view | `rrhh_prestamos` + `rrhh_concepto` |

Roles destino (mismos que hoy ven la vista 457 — confirmar con `role_permission`):
`98` (TICS), `127` (Gerente GH), `68` (Analista Proyectos GH), `54` (Gestor Planillas).

Seeder objetivo: `Database\Seeders\gp\gestionhumana\personal\WorkerDetailViewsPermissionsSeeder`.

---

## 2. Reclutamiento — Fase 1 (Postulación, 04–25/09)

**Decisión (03/09/2026):** las vistas de reclutamiento **NO** llevan contenedor propio —
cuelgan directamente de **Gestión de Personal (vista 456)**, como **hermanas de Trabajadores (457)**.
Ruta frontend base: `/gp/gestion-humana/gestion-de-personal/<route>`.

| route | descripción | icon | permisos | estado |
|-------|-------------|------|----------|--------|
| `procesos-postulacion` | Procesos de Postulación | `ClipboardList` | `.view .create .update .delete` | ✅ backend + frontend + seeder (vista **588**, permisos creados, roles 98/102/127/68/24/138) |
| `postulantes`          | Administración de Postulantes | `Users` | `.view .create .update .delete` | pendiente |

Mapea legacy: `ProcesoPostulacionController` (idVista 50), `AdministracionPostulanteController` (idVista 52).

**Seeder:** `Database\Seeders\gp\gestionhumana\reclutamiento\RecruitmentViewsPermissionsSeeder`
(ya creado con `procesos-postulacion`). Roles destino: 98 TICS, 102 TIC's TP, 127 Gerente GH,
68 Analista Proyectos GH, 24 Gestión Humana, 138 Gestión Humana AP.
**Ejecutado el 04/09/2026** — creó la vista **588** (hija de 456) y los 4 permisos `procesos-postulacion.*`.
Frontend: `src/features/gp/gestionhumana/gestion-de-personal/procesos-postulacion/` + páginas en
`src/app/gp/gestion-humana/gestion-de-personal/procesos-postulacion/{page,agregar,actualizar/[id]}`
+ rutas en `App.tsx` vía `RouterCrud("gestion-de-personal/procesos-postulacion", ...)`.

### Backend F1 — Procesos de Postulación (implementado)

- `app/Models/gp/gestionhumana/reclutamiento/RecruitmentProcess.php` (`rrhh_proceso_postulacion`)
- `RecruitmentProcessService` / `RecruitmentProcessController` / `RecruitmentProcessResource`
- FormRequests: `Index/Store/UpdateRecruitmentProcessRequest`
- Rutas (`routes/api.php`, prefix `gp/gh/reclutamiento`):
  - `GET|POST|PUT|DELETE recruitment-process` (apiResource sin `create`/`edit`)
  - `POST recruitment-process/{id}/close` — finaliza (status 11 + `fecha_fin_cierre`)
- `dias_plazo` ← `rrhh_cargo.plazo_proceso_seleccion`; `fecha_fin_plazo` = `fecha_inicio` + N
  días hábiles (lun-vie) con `CarbonImmutable` (⚠️ el legacy `sumasdiasemana()` daba un
  resultado ~2 días menor por un bug; aquí se calcula exacto).
- `centro_costo_id` ← `rrhh_area.centro_costo_id`. Anulación lógica = `status_deleted = 0`.

---

## 3. Reclutamiento — Fase 2 (Contratación + Alta, 01–28/10)

| route | descripción | icon | permisos |
|-------|-------------|------|----------|
| `seleccionados`   | Seleccionados / Alta | `UserCheck` | `.view .update` + acciones especiales |
| `carta-oferta`    | Cartas de Oferta (plantillas) | `FileSignature` | `.view .create .update .delete` |

Acciones especiales (sub-permisos de `seleccionados`):

| code | nombre |
|------|--------|
| `seleccionados.carta-oferta.generar`  | Generar carta oferta desde plantilla |
| `seleccionados.carta-oferta.subir`    | Subir carta oferta firmada |
| `seleccionados.email-bienvenida.enviar` | Enviar email de bienvenida |
| `seleccionados.alta.ejecutar`         | Dar de alta (solo RRHH central) |
| `seleccionados.baja.ejecutar`         | Dar de baja |
| `seleccionados.reingreso.ejecutar`    | Reingreso de cesado |

Mapea legacy: `SeleccionadoController` (idVista 71).

---

## 4. Contratos con firma digital — Fase 5 (track paralelo)

| route | descripción | icon | permisos |
|-------|-------------|------|----------|
| `contratos`            | Contratos | `FileText` | `.view .create .update .delete` |
| `plantillas-contrato`  | Plantillas de Contrato | `FileCode` | `.view .create .update .delete` |
| `tipos-contrato`       | Tipos de Contrato | `Tags` | `.view .create .update .delete` |
| `firmantes`            | Firmantes | `PenTool` | `.view .create .update .delete` |

Sub-permisos de flujo de firma (sobre `contratos`):

| code | nombre |
|------|--------|
| `contratos.firma.solicitar`        | Solicitar firma |
| `contratos.firma.aprobar-gh`       | Conformidad RRHH |
| `contratos.firma.firmar`           | Firmar (firmante) |
| `contratos.firma.enviar-trabajador`| Enviar al trabajador |
| `contratos.lectura.confirmar`      | Confirmar lectura |
| `contratos.lote.gestionar`         | Gestión por lotes |

Mapea legacy: `AdministracionPersonal/ContratoController`.

---

## 5. Checklist al agregar una pantalla nueva

1. Añadir fila(s) a este archivo (route, descripción, icon, permisos, roles).
2. Agregar el bloque al seeder idempotente de la fase.
3. `php artisan db:seed --class="<Seeder>"`.
4. Verificar en frontend que la vista aparece en el menú del rol y que
   `useCurrentModule()` resuelve la ruta.
