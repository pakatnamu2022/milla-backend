# Guía de Uso de Permisos en Frontend

## Estructura del Response de Login/Authenticate

Cuando el usuario se loguea o se autentica, el backend retorna:

```json
{
  "access_token": "1|abc123...",
  "user": {
    "id": 1,
    "name": "Juan Pérez",
    "username": "jperez",
    ...
  },
  "permissions": {
    "access_tree": [
      {
        "empresa_id": 1,
        "empresa_nombre": "Aplicaciones",
        "empresa_abreviatura": "ap",
        "menu": [
          {
            "id": 50,
            "descripcion": "Órdenes de Compra",
            "slug": "vehicle_purchase_order",
            "route": "/vehicle-purchase-orders",
            "children": [...]
          }
        ]
      }
    ],
    "granular_permissions": [
      "vehicle_purchase_order.export",
      "vehicle_purchase_order.resend",
      "opportunity.view_all_users",
      "user.reset_password"
    ]
  }
}
```

---

## Tipos de Permisos

### 1. **Permisos Básicos (CRUD)** - En `access_tree`
Cada nodo del árbol de menú tiene permisos básicos (implícitos en la vista):
- `view` (ver)
- `create` (crear)
- `edit` (editar)
- `delete` (anular)

### 2. **Permisos Granulares** - En `granular_permissions`
Array plano de códigos de permisos específicos:
- `vehicle_purchase_order.export`
- `opportunity.view_all_users`
- `user.reset_password`
- etc.

---

## Implementación en Frontend

### Opción 1: Vuex/Pinia Store (Recomendado)

#### **Store de Permisos:**

```javascript
// store/permissions.js
export const usePermissionsStore = defineStore('permissions', {
  state: () => ({
    accessTree: [],
    granularPermissions: [],
  }),

  getters: {
    /**
     * Verifica si el usuario tiene un permiso granular
     */
    hasPermission: (state) => (permissionCode) => {
      return state.granularPermissions.includes(permissionCode);
    },

    /**
     * Verifica si tiene alguno de los permisos
     */
    hasAnyPermission: (state) => (permissions) => {
      return permissions.some(perm => state.granularPermissions.includes(perm));
    },

    /**
     * Verifica si tiene todos los permisos
     */
    hasAllPermissions: (state) => (permissions) => {
      return permissions.every(perm => state.granularPermissions.includes(perm));
    },

    /**
     * Obtiene el árbol de menú para una empresa
     */
    getMenuByCompany: (state) => (companyId) => {
      return state.accessTree.find(tree => tree.empresa_id === companyId)?.menu || [];
    },

    /**
     * Obtiene todas las empresas disponibles
     */
    availableCompanies: (state) => {
      return state.accessTree.map(tree => ({
        id: tree.empresa_id,
        name: tree.empresa_nombre,
        abbreviation: tree.empresa_abreviatura,
      }));
    },
  },

  actions: {
    /**
     * Inicializar permisos desde el login
     */
    setPermissions(permissions) {
      this.accessTree = permissions.access_tree || [];
      this.granularPermissions = permissions.granular_permissions || [];
    },

    /**
     * Limpiar permisos al logout
     */
    clearPermissions() {
      this.accessTree = [];
      this.granularPermissions = [];
    },
  },
});
```

#### **Store de Auth:**

```javascript
// store/auth.js
import { usePermissionsStore } from './permissions';

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: null,
  }),

  actions: {
    async login(credentials) {
      try {
        const response = await api.post('/auth/login', credentials);

        // Guardar token y usuario
        this.token = response.data.access_token;
        this.user = response.data.user;

        // Guardar permisos en store separado
        const permissionsStore = usePermissionsStore();
        permissionsStore.setPermissions(response.data.permissions);

        // Guardar token en localStorage
        localStorage.setItem('token', this.token);

        return true;
      } catch (error) {
        console.error('Login error:', error);
        throw error;
      }
    },

    async logout() {
      // Limpiar stores
      this.token = null;
      this.user = null;

      const permissionsStore = usePermissionsStore();
      permissionsStore.clearPermissions();

      // Limpiar localStorage
      localStorage.removeItem('token');

      // Llamar al endpoint de logout
      await api.post('/auth/logout');
    },
  },
});
```

---

### Opción 2: Composable/Hook

```javascript
// composables/usePermissions.js
import { computed } from 'vue';
import { usePermissionsStore } from '@/store/permissions';

export function usePermissions() {
  const permissionsStore = usePermissionsStore();

  const hasPermission = (permissionCode) => {
    return permissionsStore.hasPermission(permissionCode);
  };

  const hasAnyPermission = (permissions) => {
    return permissionsStore.hasAnyPermission(permissions);
  };

  const hasAllPermissions = (permissions) => {
    return permissionsStore.hasAllPermissions(permissions);
  };

  return {
    hasPermission,
    hasAnyPermission,
    hasAllPermissions,
    granularPermissions: computed(() => permissionsStore.granularPermissions),
    accessTree: computed(() => permissionsStore.accessTree),
  };
}
```

---

## Uso en Componentes

### Ejemplo 1: Mostrar Select Condicionalmente (Tu Caso)

```vue
<template>
  <div>
    <!-- Select solo visible si tiene permiso para ver oportunidades de todos -->
    <div v-if="canViewAllUsers" class="form-group">
      <label>Filtrar por Usuario:</label>
      <select v-model="selectedUserId" @change="fetchOpportunities">
        <option value="">Mis oportunidades</option>
        <option v-for="user in users" :key="user.id" :value="user.id">
          {{ user.name }}
        </option>
      </select>
    </div>

    <!-- Lista de oportunidades -->
    <div v-for="opp in opportunities" :key="opp.id">
      {{ opp.title }}
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { usePermissions } from '@/composables/usePermissions';

const { hasPermission } = usePermissions();

const opportunities = ref([]);
const users = ref([]);
const selectedUserId = ref(null);

// Computed para verificar permiso
const canViewAllUsers = computed(() => {
  return hasPermission('opportunity.view_all_users');
});

// Cargar usuarios solo si tiene permiso
if (canViewAllUsers.value) {
  loadUsers();
}

async function loadUsers() {
  const response = await api.get('/users');
  users.value = response.data;
}

async function fetchOpportunities() {
  const params = {};

  // Solo enviar user_id si tiene permiso y hay selección
  if (canViewAllUsers.value && selectedUserId.value) {
    params.user_id = selectedUserId.value;
  }

  const response = await api.get('/opportunities/my', { params });
  opportunities.value = response.data;
}
</script>
```

### Ejemplo 2: Botones de Acciones

```vue
<template>
  <div class="actions">
    <button @click="viewDetails">Ver Detalles</button>

    <button v-if="canEdit" @click="edit">Editar</button>

    <button v-if="canExport" @click="exportData">Exportar</button>

    <button v-if="canApprove" @click="approve">Aprobar</button>

    <button
      v-if="canResend && order.status === 'anulado'"
      @click="resend"
    >
      Reenviar
    </button>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { usePermissions } from '@/composables/usePermissions';

const props = defineProps({
  order: Object,
});

const { hasPermission } = usePermissions();

// Permisos básicos (del árbol)
const canEdit = computed(() => {
  // Esto requeriría buscar en el árbol, o mejor usar el backend
  return true; // Por simplicidad
});

// Permisos granulares
const canExport = computed(() => hasPermission('vehicle_purchase_order.export'));
const canApprove = computed(() => hasPermission('vehicle_purchase_order.approve'));
const canResend = computed(() => hasPermission('vehicle_purchase_order.resend'));
</script>
```

### Ejemplo 3: Directiva Personalizada (Opcional)

```javascript
// plugins/permissions.js
export default {
  install(app) {
    app.directive('permission', {
      mounted(el, binding) {
        const permissionsStore = usePermissionsStore();
        const hasPermission = permissionsStore.hasPermission(binding.value);

        if (!hasPermission) {
          el.style.display = 'none';
        }
      },
    });
  },
};

// Uso en componente
<button v-permission="'vehicle_purchase_order.export'">Exportar</button>
```

---

## Guards de Rutas

```javascript
// router/index.js
import { usePermissionsStore } from '@/store/permissions';

router.beforeEach((to, from, next) => {
  const permissionsStore = usePermissionsStore();

  // Si la ruta requiere permisos específicos
  if (to.meta.requiredPermission) {
    const hasPermission = permissionsStore.hasPermission(to.meta.requiredPermission);

    if (!hasPermission) {
      // Redirigir a página de acceso denegado
      return next({ name: 'Forbidden' });
    }
  }

  next();
});

// Definición de rutas
const routes = [
  {
    path: '/vehicle-purchase-orders/export',
    name: 'VehiclePurchaseOrderExport',
    component: ExportView,
    meta: {
      requiredPermission: 'vehicle_purchase_order.export',
    },
  },
];
```

---

## Tabla de Permisos Granulares Disponibles

| Código | Descripción | Uso |
|--------|-------------|-----|
| `vehicle_purchase_order.export` | Exportar OCs | Botón exportar |
| `vehicle_purchase_order.resend` | Reenviar OC anulada | Acción especial |
| `vehicle_purchase_order.approve` | Aprobar OC | Botón aprobar |
| `opportunity.view_all_users` | Ver oportunidades de todos | Select de filtro |
| `opportunity.assign` | Asignar oportunidades | Acción transferir |
| `user.reset_password` | Resetear contraseñas | Panel de admin |
| `evaluation.publish` | Publicar evaluaciones | Botón publicar |
| `report.view_financial` | Ver reportes financieros | Acceso a vista |

---

## Best Practices

### 1. **Cachear permisos en el store**
No hacer llamadas adicionales al backend para verificar permisos. Todo debe estar en el store desde el login.

### 2. **Verificar permisos en múltiples lugares**
- **Frontend**: Para UX (ocultar botones)
- **Backend**: Para seguridad (validar requests)

### 3. **Usar computed properties**
```javascript
const canExport = computed(() => hasPermission('vehicle_purchase_order.export'));
```

### 4. **Combinar con lógica de negocio**
```javascript
const canResend = computed(() => {
  return hasPermission('vehicle_purchase_order.resend')
    && order.value.status === 'anulado';
});
```

### 5. **Reload permisos en cambios de rol**
Si el rol del usuario cambia, recargar permisos:
```javascript
async function reloadPermissions() {
  const response = await api.get('/auth/authenticate');
  permissionsStore.setPermissions(response.data.permissions);
}
```

---

## Debugging

### Ver permisos actuales en DevTools:

```javascript
// En la consola del navegador
const permissionsStore = usePermissionsStore();
console.log('Permisos granulares:', permissionsStore.granularPermissions);
console.log('Árbol de acceso:', permissionsStore.accessTree);
```

### Helper para verificar permisos:

```javascript
// Agregar al window global en desarrollo
if (import.meta.env.DEV) {
  window.checkPermission = (code) => {
    const store = usePermissionsStore();
    console.log(`Permission ${code}:`, store.hasPermission(code));
  };
}

// Uso: checkPermission('vehicle_purchase_order.export')
```

---

## Migración desde Sistema Anterior

Si ya tienes un sistema de permisos, migrar es simple:

1. **Mantener compatibilidad:** El árbol `access_tree` sigue igual
2. **Agregar nueva funcionalidad:** Usar `granular_permissions` para nuevas features
3. **Refactorizar gradualmente:** Mover lógica de permisos al nuevo sistema

```javascript
// Antes (solo árbol)
const permissions = response.data.permissions;

// Después (nuevo formato)
const permissions = response.data.permissions;
const accessTree = permissions.access_tree; // mismo formato
const granularPermissions = permissions.granular_permissions; // NUEVO
```
