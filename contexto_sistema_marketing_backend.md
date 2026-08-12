# Contexto funcional y técnico --- Sistema de Gestión de Marketing, Presupuestos, OC y Sustentos

## 1. Propósito de este documento

Este documento consolida el contexto obtenido de:

-   Reunión de Marketing.
-   Archivo `SEGUIMIWENTO OC 2025_TICS.xlsx`.
-   Archivo `Plan_Marketing_Q4_CHANGAN.xlsx`.
-   Imágenes y anotaciones revisadas durante el análisis.

Su objetivo es servir como **documento de contexto para una futura
implementación en Laravel**, incluyendo:

-   entendimiento del proceso;
-   conceptos del negocio;
-   flujo funcional;
-   estructuras observadas en Excel;
-   reglas conocidas;
-   puntos que todavía deben descubrirse/confirmarse;
-   posibles entidades y relaciones de backend;
-   funciones que debería soportar el sistema;
-   estrategia de exploración de la base de datos y del código
    existente;
-   criterios para que una herramienta como Claude Code pueda comenzar
    una implementación sin inventar reglas de negocio.

> Este documento es una especificación inicial y no debe tratarse como
> una definición contractual de todas las reglas. Las partes marcadas
> como "por confirmar" deben verificarse durante el descubrimiento.

------------------------------------------------------------------------

## 2. Resumen ejecutivo

El sistema debe centralizar el seguimiento del proceso de Marketing que
actualmente se gestiona mediante Excel.

El dominio observado gira alrededor de:

``` text
Plan de Marketing
        ↓
Presupuesto
        ↓
Acciones / Actividades
        ↓
Propuesta / Presupuesto del proveedor (cuando corresponda)
        ↓
Orden de Compra (OC)
        ↓
Ejecución
        ↓
Sustentos
        ├── Comprobantes
        └── Evidencias / fotografías
        ↓
Envío / Facturación
        ↓
Estado de la OC
        ↓
Resultados / KPIs
```

Hay dos grandes bloques presupuestales observados:

1.  **Plan de Marketing Regular**
2.  **Actividades / Presupuestos Adicionales**

No debe asumirse que:

``` text
Regular = 100% AP
Adicional = 100% proveedor
```

Los Excel muestran que existen aportes de **MARCAS** y **AP** en ambos
contextos.

Por tanto, el modelo debe contemplar explícitamente **fuentes de
financiamiento**, en lugar de codificar la fuente únicamente a partir de
si el presupuesto es regular o adicional.

------------------------------------------------------------------------

# 3. Conceptos principales del negocio

## 3.1. Marca

Existen marcas como:

-   SUBARU
-   DFSK
-   CHANGAN
-   JAC
-   SUZUKI
-   MAZDA
-   RENAULT
-   GWM

También aparecen valores como:

-   RED
-   POSVENTA
-   SWIFT
-   TANK 300
-   JAC T9
-   MOTOR SHOP
-   etc.

Estos últimos **no deben clasificarse automáticamente como marcas ni
como modelos**.

Hipótesis actual:

-   algunos pueden representar líneas;
-   algunos pueden representar productos/modelos;
-   algunos pueden representar planes o campañas;
-   algunos pueden representar áreas/conceptos de Marketing.

Debe descubrirse su significado real antes de fijar una jerarquía rígida
en backend.

------------------------------------------------------------------------

## 3.2. Plan de Marketing

Representa la planificación de Marketing para un período.

El ejemplo `Plan_Marketing_Q4_CHANGAN.xlsx` contiene:

``` text
Plan de Marketing Q4 – CHANGAN
```

Con:

-   presupuesto regular de octubre;
-   presupuesto regular de noviembre;
-   presupuesto regular de diciembre;
-   presupuesto adicional de octubre;
-   presupuesto adicional de noviembre;
-   presupuesto adicional de diciembre.

El plan puede tener múltiples acciones.

------------------------------------------------------------------------

# 4. Presupuesto regular

El archivo de CHANGAN muestra:

``` text
Presupuesto regular
Octubre       S/ 4,400
Noviembre     S/ 4,400
Diciembre     S/ 4,400
Total        S/ 13,200
```

Las acciones regulares observadas incluyen:

-   Trabajos de campo.
-   Rompetráfico.
-   Pauta digital.

Cada acción contiene datos como:

-   acción/actividad;
-   descripción;
-   producto;
-   objetivo;
-   responsable;
-   lugar/canal;
-   fecha/mes;
-   presupuesto estimado;
-   estado.

Ejemplo:

``` text
Trabajos de campo
Descripción: Visita a distritos aledaños con volanteo
Producto: SUV / pick up
Objetivo: Prospectos y ventas
Responsable: Mkt y asesores
Lugar: distritos de la sierra
Mes: Octubre
Presupuesto: S/ 500
Estado: Planeado
```

El presupuesto regular debe entenderse como una bolsa de planificación
para las acciones regulares del plan.

------------------------------------------------------------------------

# 5. Presupuesto / actividad adicional

El mismo archivo contiene un bloque separado:

``` text
Presupuesto adicional
Octubre       S/ 2,150
Noviembre     S/ 2,150
Diciembre     S/ 2,150
Total         S/ 6,450
```

La actividad adicional del ejemplo es:

``` text
Radio
Pauta radial en 2 regiones
Chiclayo / Piura
Presupuesto: S/ 2,150
```

El seguimiento histórico muestra que un adicional puede representar
diferentes campañas/actividades, por ejemplo:

-   RADIO
-   EVENTOS
-   INFLUENCER
-   CAMPO
-   EXPO PROVEEDORES
-   MOTOR SHOP
-   DIGITAL
-   SORTEO
-   IMPLEMENTACIÓN
-   etc.

Por tanto:

``` text
Presupuesto adicional
        ↓
Puede contener varias actividades
```

No debe modelarse como:

``` text
1 adicional = 1 actividad
```

------------------------------------------------------------------------

# 6. Fuente de financiamiento

Este punto es crítico.

En `SEGUIMIWENTO OC 2025_TICS.xlsx`, la hoja `Hoja3` presenta para el
Plan de MKT Regular:

``` text
PLAN DE MKT REGULAR

MARCAS | AP
```

Ejemplos:

``` text
SUBARU   MARCAS 2,000 | AP 2,300
DFSK     MARCAS 2,500 | AP 2,000
CHANGAN  MARCAS 2,000 | AP 1,800
JAC      MARCAS 2,200 | AP 1,500
SUZUKI   MARCAS 2,200 | AP 1,800
MAZDA    MARCAS 1,500 | AP 1,000
RENAULT  MARCAS 1,000 | AP 1,000
GWM      MARCAS 2,000 | AP 2,000
```

También existen bloques de adicionales donde aparecen combinaciones de:

``` text
MARCAS
AP
```

Ejemplo:

``` text
ADICIONAL 1Q
RADIO FEBRERO Y MARZO
MARCAS | AP
```

Y otros bloques de adicionales con campañas específicas.

Por esto, el backend debe contemplar:

``` text
Fuente de financiamiento
    ├── AP
    └── Marca
```

La fuente debe ser una dimensión independiente.

No implementar como regla:

``` text
if budget.type == regular:
    source = AP

if budget.type == additional:
    source = supplier
```

Esa regla no está respaldada por toda la información disponible.

------------------------------------------------------------------------

# 7. Relación entre presupuesto y financiamiento

La forma conceptual recomendada es:

``` text
Presupuesto
    ↓
Fuentes de financiamiento
    ├── AP
    └── Marca
```

Una actividad podría eventualmente financiarse con una o varias fuentes.

Ejemplo conceptual:

``` text
Actividad: Radio
Costo total: S/ 2,150

AP:     S/ X
Marca:  S/ Y
----------------
Total:  S/ 2,150
```

La posibilidad de múltiples fuentes debe considerarse desde el diseño,
aunque primero debe validarse con Marketing si realmente una misma
actividad puede tener financiamiento mixto.

------------------------------------------------------------------------

# 8. Actividades regulares

Las acciones regulares del archivo Q4 tienen estructura:

``` text
Acción / Actividad
Descripción
Productos
Objetivo
Responsable
Lugar / Canal
Fecha
Presupuesto Estimado
Estado
```

Ejemplos:

### Trabajos de campo

Puede existir más de una ejecución:

``` text
Trabajos de campo - Sierra
Trabajos de campo - Norte
```

### Rompetráfico

Puede repetirse por sede:

``` text
Cajamarca
Piura
Chiclayo
```

### Pauta digital

Puede cubrir varias regiones:

``` text
Lambayeque
Piura
Cajamarca
```

Esto demuestra que una actividad no debe asumir una única sede.

------------------------------------------------------------------------

# 9. Actividades adicionales y sedes

El adicional de radio de CHANGAN tiene:

``` text
Total: S/ 2,150
```

Descompuesto en:

``` text
Chiclayo
La Caribeña
S/ 1,100

Piura
Radio Nova
S/ 1,050
```

Total:

``` text
S/ 1,100 + S/ 1,050 = S/ 2,150
```

Por tanto:

``` text
Actividad adicional
        ↓
Ejecuciones
        ├── Sede Chiclayo
        └── Sede Piura
```

Una actividad adicional puede involucrar múltiples sedes.

------------------------------------------------------------------------

# 10. Detalle específico de una actividad

El Excel contiene una hoja `Detalle de radio`.

Para Chiclayo:

``` text
Marca: SUZUKI CELERIO
Radio: LA CARIBEÑA
Días: De lunes a viernes
Avisos al día: 5
Duración: 20 SG
Bloque: rotativo
Ratio horario: 6:00 hr a 22:00 hrs
Pauta: 25 días
Bonificación: SAB Y DOM 5 avisos
Total de avisos: 125
Inversión: S/ 1,100
```

Para Piura:

``` text
Marca: SUZUKI CELERIO
Radio: RADIO NOVA
Días: De lunes a viernes
Avisos al día: 5
Duración: 20 SG
Bloque: rotativo
Ratio horario: 6:00 hr a 22:00 hrs
Pauta: 25 días
Bonificación: SAB Y DOM 5 avisos
Total de avisos: 125
Inversión: S/ 1,050
```

Esto demuestra que una actividad puede tener un **detalle operativo
específico**.

No conviene meter todos los campos específicos de radio directamente en
`marketing_activities`.

Posible enfoque:

``` text
marketing_activities
        ↓
activity_details / activity_type_data
```

O, si el dominio lo justifica:

``` text
marketing_radio_details
```

Esto debe decidirse después de explorar el sistema y conocer cuántos
tipos de actividad existirán.

------------------------------------------------------------------------

# 11. Proveedor

Según la reunión:

-   el proveedor presenta/reconoce una necesidad de Marketing mediante
    un informe y presupuesto;
-   Marketing revisa;
-   después se envía/genera una OC;
-   posteriormente se ejecuta la actividad;
-   se reúnen sustentos;
-   Javier interviene en el proceso de facturación.

Debe diferenciarse:

``` text
Proveedor
Marca
Fuente de financiamiento
```

No asumir que son la misma entidad.

Una marca puede ser quien financia o solicita una actividad y el
proveedor puede ser quien ejecuta el servicio.

Ejemplo:

``` text
Marca: SUZUKI
Proveedor: Radio La Caribeña
Actividad: Pauta radial
```

Esta separación es importante para el backend.

------------------------------------------------------------------------

# 12. Orden de Compra (OC)

La reunión indica:

``` text
Proveedor presenta presupuesto
        ↓
Marketing revisa
        ↓
Se genera/envía OC
```

La OC representa el compromiso formal de la compra/servicio.

El archivo `SEGUIMIWENTO OC 2025_TICS.xlsx` muestra que el seguimiento
actual está muy orientado a:

``` text
Mes
Marca / actividad
Soles
Dólares
Estado
```

Por lo tanto, la OC debe poder tener:

-   número;
-   fecha;
-   proveedor;
-   moneda;
-   monto;
-   actividades asociadas;
-   estado;
-   documentos;
-   observaciones;
-   información de facturación.

------------------------------------------------------------------------

# 13. Estado de la OC

En el Excel aparecen diferentes estados:

``` text
FACTURADO
FACTURADA
ENVIADO
ENVIADA
NO ENVIADO
FALTA
```

El sistema debería normalizar estos estados.

No guardar textos libres si se puede evitar.

Posible catálogo:

``` text
draft
pending_review
approved
purchase_order_generated
sent
in_execution
pending_support
supported
pending_billing
billed
closed
cancelled
rejected
```

Pero los estados definitivos deben validarse con
Marketing/Administración.

La interfaz puede mostrar textos en español aunque el backend use
valores estables.

------------------------------------------------------------------------

# 14. Sustentos

La reunión indica que para justificar el gasto se necesitan:

-   presupuesto;
-   fotos;
-   facturas;
-   comprobantes.

También se indicó:

> Hay varios comprobantes por el monto de una acción o actividad.

Por lo tanto:

``` text
Actividad
    ├── Comprobante 1
    ├── Comprobante 2
    ├── Comprobante 3
    └── Evidencias
```

No asumir:

``` text
Actividad = 1 comprobante
```

------------------------------------------------------------------------

# 15. Comprobantes

Un comprobante debería permitir registrar, como mínimo:

-   tipo;
-   serie;
-   número;
-   fecha;
-   proveedor/emisor;
-   moneda;
-   monto;
-   archivo;
-   observaciones;
-   relación con actividad;
-   relación con OC.

Debe considerarse que una acción puede tener varios comprobantes.

También debe poder comprobarse:

``` text
Monto de la actividad
        vs
Suma de comprobantes
```

Ejemplo:

``` text
Actividad: S/ 1,000

Comprobante 1: S/ 400
Comprobante 2: S/ 350
Comprobante 3: S/ 250

Sustentado: S/ 1,000
```

El sistema debería poder detectar diferencias:

``` text
Presupuestado: S/ 1,000
Sustentado:    S/   900
Diferencia:    S/   100
```

------------------------------------------------------------------------

# 16. Evidencias

Además de comprobantes, deben poder adjuntarse evidencias:

-   fotografías;
-   capturas;
-   piezas;
-   documentos;
-   otros archivos.

Debe existir una entidad de evidencias o un sistema de adjuntos.

Ejemplo:

``` text
Actividad
    ├── factura.pdf
    ├── boleta.pdf
    ├── foto-01.jpg
    ├── foto-02.jpg
    └── informe.pdf
```

------------------------------------------------------------------------

# 17. Facturación

La reunión indica:

> "lo manda a facturar Javier"

y:

> "ADV factura la OC de lo que resta del presupuesto de Marketing."

Este punto todavía necesita validación funcional.

No asumir todavía:

-   quién genera físicamente la factura;
-   si ADV es una empresa, área o sistema;
-   qué significa exactamente "lo que resta";
-   si se factura la OC completa o solo un saldo;
-   si la factura está relacionada con la OC, con el presupuesto o con
    el proveedor;
-   si existe integración con otro sistema.

El backend debe dejar espacio para registrar:

``` text
Factura
    ├── número
    ├── fecha
    ├── monto
    ├── moneda
    ├── proveedor
    ├── OC
    └── estado
```

pero la regla exacta debe descubrirse.

------------------------------------------------------------------------

# 18. Moneda

El seguimiento histórico utiliza:

``` text
SOLES
DÓLARES
```

Por tanto, las entidades monetarias relevantes deben contemplar:

``` text
currency
amount
```

No usar un único campo ambiguo.

Si una misma OC o documento puede tener moneda única, establecer esa
regla.

Si una actividad puede mezclar monedas, debe descubrirse antes de
implementar.

------------------------------------------------------------------------

# 19. Dashboard mensual

La estructura:

``` text
MARCA / CONCEPTO
SOLES
DÓLARES
ESTADO
```

que aparece repetida por mes en `SEGUIMIWENTO OC 2025_TICS.xlsx` debe
considerarse principalmente como una **vista de seguimiento/dashboard**,
no necesariamente como una estructura de tablas.

Por ejemplo:

``` text
OCTUBRE

Marca / Concepto     Soles     Dólares     Estado
-------------------------------------------------
SUBARU               2,000        0        FACTURADO
DFSK                 2,500        0        FACTURADO
CHANGAN              2,000        0        FACTURADO
JAC                  2,200        0        FACTURADO
SUZUKI               2,200        0        FACTURADO
...
```

El backend debería almacenar datos normalizados y el dashboard construir
esta vista mediante consultas/agregaciones.

No replicar la estructura horizontal del Excel en la base de datos.

------------------------------------------------------------------------

# 20. Dashboard esperado

Una posible vista mensual:

``` text
┌───────────────────────────────────────────────┐
│ MARKETING — OCTUBRE                           │
├───────────────────────────────────────────────┤
│ Presupuesto       Ejecutado       Saldo       │
│ S/ XX,XXX         S/ XX,XXX       S/ X,XXX    │
├───────────────────────────────────────────────┤
│ OCs             Pendientes       Facturadas  │
│ 25              4                21           │
├───────────────────────────────────────────────┤
│ Marca / Concepto   Soles  USD     Estado      │
│ SUBARU             ...    ...     FACTURADO   │
│ CHANGAN            ...    ...     PENDIENTE   │
└───────────────────────────────────────────────┘
```

Filtros potenciales:

-   año;
-   mes;
-   marca;
-   campaña/plan;
-   tipo regular/adicional;
-   fuente AP/marca;
-   sede;
-   proveedor;
-   estado;
-   moneda.

------------------------------------------------------------------------

# 21. KPIs

El Excel `KPIs` contiene:

``` text
Acción regular
Mes
Leads/prospectos
Ventas prospectadas
Winrate/ROI
```

Ejemplo:

``` text
Trabajos de campo norte
Octubre
10 leads
3 ventas
30%

Trabajos de campo sierra
Octubre
15 leads
4 ventas
26.67%

Pauta digital
Octubre
200 leads
10 ventas
5%
```

Esto demuestra que el sistema puede tener una etapa posterior a la
ejecución:

``` text
Actividad
    ↓
Ejecución
    ↓
Resultados
    ↓
KPIs
```

Los KPIs no necesariamente son obligatorios para todas las actividades.

Debe poder definirse qué actividades tienen medición.

------------------------------------------------------------------------

# 22. Flujo funcional consolidado

## Flujo regular

``` text
AP / presupuesto de Marketing
        ↓
Plan de Marketing
        ↓
Presupuesto regular
        ↓
Acciones regulares
        ↓
Planificación
        ↓
Ejecución
        ↓
Sustentos
        ↓
OC / comprobación del gasto
        ↓
Facturación / cierre
        ↓
KPIs
```

## Flujo adicional

``` text
Marca / fuente adicional
        ↓
Necesidad / campaña adicional
        ↓
Presupuesto / propuesta
        ↓
Marketing revisa
        ↓
Actividad adicional
        ↓
Una o varias sedes / ejecuciones
        ↓
OC
        ↓
Ejecución
        ↓
Sustentos
        ↓
Facturación
        ↓
Cierre
```

La fuente exacta del dinero debe ser configurable y no derivarse
automáticamente del tipo de actividad.

------------------------------------------------------------------------

# 23. Modelo conceptual inicial

No es todavía una definición final de tablas.

``` text
Brand
  │
  └── MarketingPlan
          │
          ├── MarketingBudget
          │       │
          │       ├── FundingSource
          │       │
          │       └── MarketingActivity
          │               │
          │               ├── ActivityExecution
          │               │       └── Location
          │               │
          │               ├── Proposal
          │               │       └── Supplier
          │               │
          │               ├── PurchaseOrder
          │               │
          │               ├── SupportingDocument
          │               │       ├── Receipt
          │               │       └── Evidence
          │               │
          │               └── KPI
          │
          └── Campaign / Concept
```

------------------------------------------------------------------------

# 24. Posibles entidades backend

Estas son hipótesis iniciales y deben validarse contra la base
existente.

## Catálogos / maestros

-   brands
-   suppliers
-   locations
-   currencies
-   funding_sources
-   activity_types
-   campaign_types
-   statuses

## Planificación

-   marketing_plans
-   marketing_budgets
-   marketing_budget_fundings
-   marketing_activities
-   marketing_activity_locations

## Proveedores / propuestas

-   marketing_proposals
-   marketing_proposal_items

## Compras

-   purchase_orders
-   purchase_order_items

## Sustentos

-   supporting_documents
-   receipts
-   evidences
-   attachments

## Facturación

-   invoices
-   invoice_items o relaciones correspondientes

## Resultados

-   marketing_kpis
-   marketing_activity_results

No crear todas estas tablas automáticamente. Primero se debe comprobar
qué entidades ya existen en el sistema.

------------------------------------------------------------------------

# 25. Posibles relaciones

Una primera hipótesis:

``` text
MarketingPlan
  hasMany MarketingBudget

MarketingBudget
  belongsTo MarketingPlan
  belongsTo Brand
  hasMany MarketingBudgetFunding
  hasMany MarketingActivity

MarketingBudgetFunding
  belongsTo MarketingBudget
  belongsTo FundingSource

MarketingActivity
  belongsTo MarketingBudget
  belongsTo ActivityType
  hasMany MarketingActivityLocation
  hasMany MarketingProposal
  hasMany PurchaseOrder
  hasMany SupportingDocument
  hasMany MarketingKpi

Supplier
  hasMany MarketingProposal
  hasMany PurchaseOrder

PurchaseOrder
  belongsTo Supplier
  hasMany PurchaseOrderItem
  hasMany SupportingDocument
  hasMany Invoice
```

Estas relaciones deben revisarse después de explorar la base real.

------------------------------------------------------------------------

# 26. Importante: no duplicar el dominio de compras

El Excel se llama:

``` text
SEGUIMIWENTO OC 2025_TICS
```

Esto sugiere que posiblemente el sistema actual ya tiene:

-   órdenes de compra;
-   proveedores;
-   comprobantes;
-   facturas;
-   estados;
-   monedas;
-   usuarios;
-   empresas;
-   áreas.

Antes de crear:

``` text
purchase_orders
suppliers
invoices
```

se debe revisar si ya existen en la aplicación.

Si ya existe un módulo de compras, Marketing debería **integrarse con
ese dominio**, no crear otro sistema paralelo de OC.

------------------------------------------------------------------------

# 27. Exploración obligatoria antes de implementar

Claude Code / desarrollador debe comenzar en modo **discovery**, no
creando migraciones inmediatamente.

## Paso 1 --- Explorar el proyecto

Revisar:

-   estructura Laravel;
-   módulos;
-   Models;
-   migrations;
-   Form Requests;
-   Resources;
-   Controllers;
-   Services;
-   Policies;
-   Enums;
-   Events/Listeners;
-   Jobs;
-   rutas;
-   permisos;
-   frontend relacionado;
-   almacenamiento de archivos;
-   integración con ERP/contabilidad/compras.

Buscar términos:

``` text
purchase
purchase_order
order
oc
supplier
provider
invoice
invoice
receipt
comprobante
factura
marketing
budget
presupuesto
brand
marca
campaign
campaña
expense
gasto
evidence
sustento
attachment
document
currency
moneda
```

------------------------------------------------------------------------

# 28. Exploración de base de datos

Antes de crear nuevas tablas:

1.  Identificar conexión y motor de BD.
2.  Listar todas las tablas.
3.  Revisar migraciones.
4.  Buscar tablas relacionadas con compras.
5.  Buscar tablas de proveedores.
6.  Buscar tablas de marcas.
7.  Buscar tablas de productos/modelos.
8.  Buscar tablas de empresas/sedes.
9.  Buscar tablas de facturación.
10. Buscar tablas de documentos/archivos.
11. Buscar tablas de usuarios/roles/permisos.
12. Identificar claves foráneas existentes.
13. Identificar vistas existentes.
14. Identificar stored procedures/triggers si existen.
15. Revisar datos reales antes de decidir nombres.

La regla debe ser:

> Reutilizar entidades existentes cuando representen el mismo concepto
> de negocio.

------------------------------------------------------------------------

# 29. Exploración de datos reales

No basta con mirar las migraciones.

Se deben ejecutar consultas de muestra para descubrir:

-   cómo se identifican las marcas;
-   cómo se identifican los proveedores;
-   cómo se representan las OCs;
-   cómo se representan las monedas;
-   cómo se guardan estados;
-   cómo se relacionan las facturas;
-   cómo se manejan las sedes;
-   cómo se almacenan documentos;
-   qué significa actualmente AP en la base;
-   si existe una estructura de campañas/planes;
-   si existen actividades de Marketing históricas.

Especial atención a:

``` text
RED
POSVENTA
SWIFT
TANK 300
JAC T9
MOTOR SHOP
```

No clasificarlos automáticamente como marcas, modelos o conceptos.

Primero consultar cómo están representados actualmente y contrastarlo
con usuarios de negocio.

------------------------------------------------------------------------

# 30. Revisión de módulos existentes

Si existe un módulo de compras:

``` text
Marketing
   ↓
Solicitud / actividad
   ↓
OC existente
```

debería reutilizarse.

Si existe un módulo de proveedores:

``` text
Marketing Proposal
   ↓
Supplier existente
```

debería reutilizarse.

Si existe un módulo de facturación:

``` text
OC
   ↓
Invoice existente
```

debería reutilizarse.

No crear duplicados sin justificación.

------------------------------------------------------------------------

# 31. Descubrimiento de reglas faltantes

Antes de cerrar las migraciones se deben resolver:

### Presupuesto

-   ¿Quién crea el presupuesto?
-   ¿Quién lo aprueba?
-   ¿El regular se registra por mes?
-   ¿El adicional se registra por mes?
-   ¿Una marca puede tener varios presupuestos en el mismo mes?
-   ¿Un presupuesto puede tener varias fuentes?
-   ¿El monto puede modificarse después de aprobado?
-   ¿Cómo se registra una ampliación?

### Actividades

-   ¿Una actividad pertenece siempre a un presupuesto?
-   ¿Una actividad puede usar presupuesto de varias fuentes?
-   ¿Una actividad puede tener varias sedes?
-   ¿Una actividad puede tener varios proveedores?
-   ¿Una actividad puede tener varias OCs?

### Proveedores

-   ¿Quién presenta el presupuesto?
-   ¿El proveedor siempre está asociado a una marca?
-   ¿La marca y el proveedor son entidades distintas?
-   ¿El proveedor puede participar en varias marcas?

### OC

-   ¿Quién crea la OC?
-   ¿La OC nace del sistema o se registra después de existir
    externamente?
-   ¿Una OC puede contener varias actividades?
-   ¿Una actividad puede dividirse en varias OCs?
-   ¿Una OC puede tener varias monedas?
-   ¿La OC tiene aprobación?

### Sustentos

-   ¿Quién sube los documentos?
-   ¿Cuándo una OC se considera sustentada?
-   ¿Todos los comprobantes son obligatorios?
-   ¿Las fotos son obligatorias?
-   ¿Existe validación del monto sustentado?
-   ¿Se puede cerrar una OC con diferencia?

### Facturación

-   ¿Qué hace exactamente Javier?
-   ¿Qué hace exactamente ADV?
-   ¿La factura se registra o se genera?
-   ¿Qué significa "facturar lo que resta"?
-   ¿Una OC puede tener varias facturas?
-   ¿Una factura puede cubrir varias OCs?

### KPIs

-   ¿Son obligatorios?
-   ¿Quién los registra?
-   ¿Se calculan automáticamente?
-   ¿Qué significa exactamente ROI en este proceso?

------------------------------------------------------------------------

# 32. Funciones que debería soportar el sistema

## Planificación

-   Crear plan de Marketing.
-   Seleccionar período.
-   Asociar marcas.
-   Registrar presupuesto regular.
-   Registrar presupuesto adicional.
-   Registrar fuentes de financiamiento.
-   Distribuir presupuesto por actividades.
-   Distribuir presupuesto por mes.
-   Distribuir presupuesto por sede.
-   Consultar saldo.

## Actividades

-   Crear actividad.
-   Editar actividad.
-   Duplicar actividad para otro mes.
-   Asociar marca.
-   Asociar producto/línea.
-   Asociar sede.
-   Asociar canal.
-   Definir responsable.
-   Definir presupuesto estimado.
-   Cambiar estado.
-   Registrar ejecución.
-   Registrar detalle específico por tipo de actividad.

## Propuestas

-   Registrar propuesta del proveedor.
-   Adjuntar documento.
-   Registrar monto.
-   Asociar proveedor.
-   Asociar actividad.
-   Aprobar/rechazar propuesta.
-   Registrar observaciones.

## Órdenes de compra

-   Registrar OC.
-   Generar/solicitar OC si existe integración.
-   Asociar actividades.
-   Registrar monto.
-   Registrar moneda.
-   Consultar estado.
-   Cambiar estado.
-   Registrar fecha de envío.
-   Registrar fecha de facturación.
-   Consultar OCs pendientes.

## Sustentos

-   Subir facturas.
-   Subir boletas.
-   Subir fotos.
-   Subir informes.
-   Subir otros documentos.
-   Registrar datos del comprobante.
-   Asociar comprobante a actividad.
-   Asociar comprobante a OC.
-   Calcular monto sustentado.
-   Detectar diferencia entre presupuesto y sustento.
-   Validar expediente.

## Facturación

-   Registrar factura.
-   Asociar factura a OC.
-   Registrar monto.
-   Registrar moneda.
-   Registrar estado.
-   Registrar fecha.
-   Consultar pendientes de facturación.

## Dashboard

-   Dashboard mensual.
-   Dashboard anual.
-   Presupuesto total.
-   Presupuesto por marca.
-   Presupuesto por fuente.
-   Presupuesto regular.
-   Presupuesto adicional.
-   Monto comprometido.
-   Monto ejecutado.
-   Monto sustentado.
-   Monto facturado.
-   Saldo.
-   OCs pendientes.
-   OCs enviadas.
-   OCs facturadas.
-   Actividades pendientes.
-   Actividades ejecutadas.
-   Filtros por mes.
-   Filtros por marca.
-   Filtros por sede.
-   Filtros por proveedor.
-   Filtros por estado.
-   Filtros por tipo.

## KPIs

-   Registrar leads/prospectos.
-   Registrar ventas prospectadas.
-   Registrar métricas por actividad.
-   Calcular porcentajes.
-   Comparar actividades.
-   Comparar meses.
-   Comparar marcas.
-   Comparar inversión contra resultados.

## Reportes

-   Exportar presupuesto.
-   Exportar seguimiento de OCs.
-   Exportar sustentos.
-   Exportar facturación.
-   Exportar actividades.
-   Exportar KPIs.
-   Reporte mensual.
-   Reporte por marca.
-   Reporte de adicionales.
-   Reporte de saldos.

------------------------------------------------------------------------

# 33. Arquitectura sugerida para Laravel

No implementar necesariamente todo como CRUD.

Se recomienda separar:

``` text
Controllers
    ↓
Form Requests
    ↓
Services / Actions
    ↓
Models / Repositories si el proyecto ya los usa
    ↓
Database
```

Ejemplos de Services/Actions que podrían aparecer:

``` text
CreateMarketingPlan
CreateMarketingBudget
AllocateMarketingBudget
CreateMarketingActivity
SubmitMarketingProposal
ApproveMarketingProposal
CreatePurchaseOrder
AttachSupportingDocument
ValidateMarketingSupport
RegisterInvoice
ClosePurchaseOrder
RegisterMarketingKpi
```

Los nombres deben adaptarse a las convenciones existentes del proyecto.

------------------------------------------------------------------------

# 34. Estados

Evitar estados escritos manualmente en múltiples lugares.

Preferir Enums Laravel si el proyecto los utiliza.

Ejemplo:

``` text
MarketingPlanStatus
MarketingBudgetStatus
MarketingActivityStatus
ProposalStatus
PurchaseOrderStatus
SupportingStatus
InvoiceStatus
```

Pero primero revisar si ya existe una estrategia de estados/catálogos en
el proyecto.

------------------------------------------------------------------------

# 35. Auditoría

Este proceso tiene impacto financiero.

Se recomienda registrar:

-   quién creó;
-   quién modificó;
-   quién aprobó;
-   cuándo se aprobó;
-   quién cambió el estado;
-   quién subió documentos;
-   cuándo se envió a facturación;
-   cuándo se facturó.

Idealmente utilizar el mecanismo de auditoría existente en el proyecto.

No inventar un sistema de auditoría paralelo si ya existe.

------------------------------------------------------------------------

# 36. Archivos

El sistema manejará potencialmente:

-   presupuestos;
-   propuestas;
-   facturas;
-   boletas;
-   fotografías;
-   informes;
-   piezas publicitarias;
-   otros sustentos.

Antes de implementar revisar:

-   filesystem actual;
-   S3/Spaces;
-   estructura de carpetas;
-   nombres de archivos;
-   modelo existente de attachments;
-   permisos;
-   límites de tamaño;
-   MIME types.

Si el sistema ya tiene una entidad genérica de archivos, reutilizarla.

------------------------------------------------------------------------

# 37. Dashboard vs base de datos

Una regla importante:

El Excel presenta información en formato de dashboard/reporte.

No copiar esa estructura directamente.

Excel:

``` text
OCTUBRE

MARCA | SOLES | DÓLARES | ESTADO
```

Backend:

``` text
purchase_orders
    id
    brand_id
    currency_id
    amount
    status
    month
```

Y el dashboard genera:

``` text
GROUP BY month, brand, currency, status
```

La estructura de presentación no debe dictar la estructura de
persistencia.

------------------------------------------------------------------------

# 38. Principios de implementación

1.  No duplicar entidades existentes.
2.  No asumir que proveedor = marca.
3.  No asumir que regular = AP.
4.  No asumir que adicional = proveedor.
5.  Separar fuente de financiamiento del tipo de presupuesto.
6.  Una actividad puede tener varias sedes.
7.  Una actividad puede tener varios comprobantes.
8.  Una actividad puede tener varias evidencias.
9.  Un adicional puede tener varias actividades.
10. No asumir una sola OC por actividad hasta confirmarlo.
11. No asumir una sola factura por OC.
12. No mezclar datos de dashboard con tablas transaccionales.
13. Usar moneda explícita.
14. Mantener trazabilidad de aprobaciones y cambios.
15. No convertir valores como RED, SWIFT, TANK 300 o MOTOR SHOP en
    entidades específicas sin descubrir primero su significado.

------------------------------------------------------------------------

# 39. Estrategia para Claude Code

Claude Code puede utilizar este documento como **contexto funcional
inicial**, pero no debería comenzar directamente con:

``` text
php artisan make:model ...
```

La primera instrucción debería ser realizar un **Discovery del
repositorio y la base de datos**.

Objetivo del discovery:

``` text
Documentación actual
        ↓
Código existente
        ↓
Migrations
        ↓
Base de datos real
        ↓
Datos reales
        ↓
Módulos existentes
        ↓
Identificar reutilización
        ↓
Identificar gaps
        ↓
Proponer modelo
        ↓
Validar ambigüedades
        ↓
Implementar
```

------------------------------------------------------------------------

# 40. Prompt de trabajo recomendado para Claude Code

Usar este documento como contexto y pedirle inicialmente:

``` text
Lee el documento de contexto de Marketing antes de modificar código.

No implementes todavía.

Realiza primero un discovery completo del proyecto Laravel y de la base de datos.

Objetivos:

1. Identificar la arquitectura actual.
2. Identificar módulos existentes relacionados con:
   - compras
   - órdenes de compra
   - proveedores
   - marcas
   - productos/modelos
   - empresas
   - sedes
   - facturación
   - comprobantes
   - documentos/archivos
   - usuarios/permisos
3. Revisar migrations y modelos.
4. Revisar datos reales de las tablas relevantes.
5. Identificar qué entidades del contexto ya existen.
6. Identificar qué entidades deben crearse.
7. Detectar posibles duplicaciones.
8. Identificar relaciones existentes que puedan reutilizarse.
9. Identificar inconsistencias entre el contexto y la base actual.
10. Identificar reglas de negocio que no pueden inferirse técnicamente.

No inventes reglas de negocio.

Para cada ambigüedad, documenta:
- qué sabemos;
- qué encontramos en el código;
- qué encontramos en la BD;
- qué falta confirmar.

Al finalizar el discovery, genera una propuesta técnica que incluya:

- entidades;
- relaciones;
- migrations necesarias;
- enums;
- services/actions;
- endpoints;
- permisos;
- flujo de estados;
- estrategia de archivos;
- consultas para dashboard;
- riesgos;
- dudas funcionales.

No ejecutes migraciones ni hagas cambios destructivos hasta que el modelo sea consistente con la base existente.
```

------------------------------------------------------------------------

# 41. Orden recomendado de implementación

Después del discovery:

### Fase 1 --- Maestros

-   marcas;
-   proveedores;
-   sedes;
-   monedas;
-   fuentes;
-   tipos de actividad;
-   catálogos necesarios.

### Fase 2 --- Planificación

-   planes;
-   presupuestos;
-   financiamiento;
-   actividades;
-   sedes de actividades.

### Fase 3 --- Propuestas

-   propuestas;
-   documentos;
-   aprobación.

### Fase 4 --- OC

-   integración o reutilización del módulo existente;
-   relación OC ↔ actividades;
-   estados.

### Fase 5 --- Sustentos

-   comprobantes;
-   evidencias;
-   validaciones.

### Fase 6 --- Facturación

-   relación con factura existente;
-   seguimiento;
-   saldos.

### Fase 7 --- Dashboard

-   seguimiento mensual;
-   presupuestos;
-   OCs;
-   estados;
-   saldos.

### Fase 8 --- KPIs

-   leads;
-   ventas;
-   resultados;
-   indicadores.

### Fase 9 --- Reportes

-   Excel;
-   PDF;
-   reportes mensuales;
-   reportes por marca/campaña.

------------------------------------------------------------------------

# 42. Criterio de éxito

El sistema debería permitir responder sin abrir Excel:

### Presupuesto

> ¿Cuánto presupuesto tiene Marketing este mes?

### Fuente

> ¿Cuánto corresponde a AP y cuánto a la marca?

### Plan

> ¿En qué actividades se está utilizando?

### Adicional

> ¿Qué adicionales existen y para qué campaña?

### Ejecución

> ¿Qué actividades ya se ejecutaron?

### OC

> ¿Qué OCs están pendientes?

### Sustento

> ¿Qué OCs tienen comprobantes y fotos?

### Facturación

> ¿Qué falta facturar?

### Saldo

> ¿Cuánto presupuesto queda?

### KPIs

> ¿Qué resultados generó cada actividad?

### Auditoría

> ¿Quién aprobó, sustentó, envió y facturó?

------------------------------------------------------------------------

# 43. Estado actual del conocimiento

## Confirmado por Excel

-   Existe Plan de Marketing.
-   Existe presupuesto regular.
-   Existe presupuesto adicional.
-   El presupuesto se maneja por períodos/meses.
-   Existen marcas.
-   Existen aportes MARCAS y AP.
-   Existen actividades regulares.
-   Existen actividades adicionales.
-   Un adicional puede tener varias actividades.
-   Una actividad puede cubrir varias sedes.
-   Existen detalles específicos para determinados tipos de actividad.
-   Existen OCs.
-   Las OCs tienen estado.
-   Se manejan soles y dólares.
-   Existen comprobantes/sustentos.
-   Existen actividades con resultados/KPIs.
-   Existe seguimiento mensual.

## Confirmado por la reunión / notas

-   El proveedor puede presentar informe y presupuesto.
-   Marketing revisa.
-   Después de la revisión se genera/envía una OC.
-   Las actividades necesitan sustentos.
-   Una acción puede tener varios comprobantes.
-   Se utilizan fotos como sustento.
-   Javier participa en el envío a facturación.
-   ADV participa en facturación.
-   Un presupuesto adicional puede estar asociado a varias sedes.
-   Existe el concepto de presupuesto adicional asociado a una
    marca/proveedor.

## Por confirmar

-   Relación exacta Marca ↔ Proveedor.
-   Quién es exactamente AP dentro del modelo.
-   Qué significa cada valor de la columna/concepto.
-   Qué son exactamente RED, POSVENTA, SWIFT, TANK 300, JAC T9 y MOTOR
    SHOP.
-   Si una actividad puede tener múltiples fuentes de financiamiento.
-   Si una actividad puede tener múltiples OCs.
-   Si una OC puede contener múltiples actividades.
-   Si una OC puede tener múltiples facturas.
-   Regla exacta de facturación.
-   Significado exacto de "facturar lo que resta".
-   Quién aprueba cada etapa.
-   Estados oficiales.
-   Qué documentos son obligatorios.
-   Cómo se calcula el saldo.
-   Cómo se calcula ROI/Winrate.
-   Qué datos vienen de sistemas externos.

------------------------------------------------------------------------

# 44. Conclusión

El objetivo no es digitalizar literalmente los dos Excel.

El objetivo es convertir el proceso actual en un dominio estructurado:

``` text
PLAN
  ↓
PRESUPUESTO
  ↓
FUENTE DE FINANCIAMIENTO
  ↓
ACTIVIDAD / CAMPAÑA
  ↓
EJECUCIÓN
  ↓
OC
  ↓
SUSTENTOS
  ↓
FACTURACIÓN
  ↓
CIERRE
  ↓
RESULTADOS / KPIs
```

Los Excel deben considerarse **fuente de conocimiento del proceso y
ejemplo de reportes**, no como diseño de base de datos.

La primera tarea técnica debe ser descubrir qué parte de este dominio ya
existe en el proyecto Laravel y en la base de datos.

Después de ese discovery, se puede construir el modelo definitivo y
comenzar la implementación de forma segura.
