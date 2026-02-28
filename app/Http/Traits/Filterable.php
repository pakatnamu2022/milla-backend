<?php

namespace App\Http\Traits;

use App\Http\Utils\Constants;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Trait Filterable
 *
 * Proporciona funcionalidades avanzadas de filtrado, ordenamiento y paginación
 * para modelos de Eloquent. Soporta múltiples tipos de filtros incluyendo:
 * - Filtros de base de datos (columnas reales)
 * - Filtros virtuales (alias/columnas calculadas con HAVING)
 * - Filtros de accessors (atributos calculados filtrados en memoria)
 * - Búsqueda en múltiples campos (incluye relaciones)
 * - Scopes personalizados
 * - Filtros en relaciones
 *
 * @package App\Http\Traits
 */
trait Filterable
{
  /**
   * Aplica filtros a una consulta de Eloquent basándose en parámetros de la petición.
   *
   * Procesa un array de filtros y aplica condiciones WHERE a la consulta según los operadores especificados.
   * Soporta filtros en columnas directas, relaciones, búsqueda en múltiples campos, y filtros especiales
   * como virtuales, accessors y scopes.
   *
   * @param \Illuminate\Database\Eloquent\Builder $query La consulta de Eloquent a filtrar
   * @param \Illuminate\Http\Request $request La petición HTTP con los parámetros de filtrado
   * @param array $filters Array asociativo donde la clave es el nombre del filtro y el valor es el operador o configuración
   *
   * Estructura del array $filters:
   * - 'columna' => 'operador' // Operadores: '=', 'like', 'between', '>', '<', '>=', '<=', 'in', 'date_between', etc.
   * - 'search' => ['campo1', 'campo2', 'relacion.campo'] // Búsqueda en múltiples campos
   * - 'alias_virtual' => 'virtual_bool|virtual_like|virtual_numeric' // Para columnas calculadas
   * - 'accessor' => 'accessor_bool|accessor_like|accessor_numeric|accessor_gt|accessor_lt' // Para atributos calculados
   * - 'campo' => 'scope' // Llama al scope dinámicamente
   * - 'relacion.campo' => 'operador' // Filtro en relación
   *
   * @return \Illuminate\Database\Eloquent\Builder La consulta con los filtros aplicados
   */
  protected function applyFilters($query, $request, $filters)
  {
    foreach ($filters as $filter => $operator) {
      $paramName = str_replace('.', '$', $filter);
      $value = $request->query($paramName);

      if ($value === null) {
        continue;
      }

      // 👇 soporte para alias/columnas virtuales (se filtran con HAVING)
      if (is_string($operator) && str_starts_with($operator, 'virtual')) {
        $this->applyVirtualFilter($query, $filter, $operator, $value);
        continue;
      }

      // 👇 NUEVO: soporte para accessors (se filtran después de la query)
      if (is_string($operator) && str_starts_with($operator, 'accessor')) {
        // Los accessors se manejan después en getFilteredResults
        continue;
      }

      // 👇 NUEVO: soporte para scopes personalizados
      if (is_string($operator) && $operator === 'scope') {
        // Llamar al scope usando el nombre del filtro
        // Por ejemplo: 'warehouse_id' => 'scope' llamará a $query->warehouseId($value)
        $scopeName = Str::camel($filter);
        if (method_exists($query->getModel(), 'scope' . ucfirst($scopeName))) {
          $query->{$scopeName}($value);
        }
        continue;
      }

      if ($filter === 'search') {
        $fields = $operator;
        $query->where(function ($q) use ($fields, $value, $query) {
          foreach ($fields as $field) {
            if (str_contains($field, '.')) {
              $parts = explode('.', $field);
              $relation = implode('.', array_slice($parts, 0, -1));
              $relationField = end($parts);

              $q->orWhereHas($relation, function ($relQuery) use ($relationField, $value) {
                $relQuery->where($relationField, 'like', '%' . $value . '%');
              });
            } else {
              // Calificar el campo con el alias de tabla
              $qualifiedField = $this->qualifyColumn($query, $field);
              $q->orWhere($qualifiedField, 'like', '%' . $value . '%');
            }
          }
        });
      } elseif (str_contains($filter, '.')) {
        $parts = explode('.', $filter);
        $relation = implode('.', array_slice($parts, 0, -1));
        $relationField = end($parts);

        $query->whereHas($relation, function ($q) use ($relationField, $operator, $value) {
          $this->applyFilterCondition($q, $relationField, $operator, $value);
        });
      } else {
        $this->applyFilterCondition($query, $filter, $operator, $value);
      }
    }

    return $query;
  }

  /**
   * Aplica filtros a columnas virtuales (alias o calculadas) usando cláusula HAVING.
   *
   * Las columnas virtuales son alias definidos en SELECT que no existen físicamente en la tabla.
   * Se filtran usando HAVING en lugar de WHERE.
   *
   * @param \Illuminate\Database\Eloquent\Builder $query La consulta de Eloquent
   * @param string $alias El nombre del alias/columna virtual
   * @param string $operator El tipo de operador virtual (virtual_bool, virtual_like, virtual_numeric)
   * @param mixed $value El valor a filtrar
   * @return void
   */
  protected function applyVirtualFilter($query, $alias, $operator, $value): void
  {
    switch ($operator) {
      case 'virtual_bool':
        // acepta 1/0/true/false/yes/no/on/off
        $bool = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($bool === null) {
          // edge: "0" evalúa a null con FILTER_NULL_ON_FAILURE si viene como string raro;
          // forzamos falso solo si literalmente es '0'
          if ($value === '0' || $value === 0) {
            $bool = false;
          } else {
            return; // valor inválido, no aplica filtro
          }
        }
        $query->having($alias, '=', $bool ? 1 : 0);
        break;

      case 'virtual_like':
        $query->having($alias, 'like', '%' . $value . '%');
        break;

      case 'virtual_numeric':
        // útil por si luego quieres exponer total_personas, etc.
        if (is_array($value) && count($value) === 2) {
          $query->havingBetween($alias, [$value[0], $value[1]]);
        } else {
          $query->having($alias, '=', is_numeric($value) ? $value : 0);
        }
        break;

      default:
        $query->having($alias, '=', $value);
        break;
    }
  }

  /**
   * Aplica filtros a accessors de Eloquent (atributos calculados) en una colección.
   *
   * Los accessors son atributos calculados que no existen en la base de datos.
   * Dado que no pueden filtrarse con SQL, este método filtra la colección en memoria.
   *
   * @param \Illuminate\Support\Collection $collection La colección de modelos a filtrar
   * @param \Illuminate\Http\Request $request La petición HTTP con los parámetros de filtrado
   * @param array $filters Array de filtros donde los operadores comienzan con 'accessor'
   * @return \Illuminate\Support\Collection La colección filtrada y re-indexada
   */
  protected function applyAccessorFilters($collection, $request, $filters)
  {
    foreach ($filters as $filter => $operator) {
      if (!is_string($operator) || !str_starts_with($operator, 'accessor')) {
        continue;
      }

      $paramName = str_replace('.', '$', $filter);
      $value = $request->query($paramName);

      if ($value === null) {
        continue;
      }

      $collection = $collection->filter(function ($item) use ($filter, $operator, $value) {
        return $this->applyAccessorFilterCondition($item, $filter, $operator, $value);
      })->values(); // Re-indexar para obtener array secuencial
    }

    return $collection;
  }

  /**
   * Evalúa la condición de filtro para un accessor en un modelo específico.
   *
   * Soporta múltiples operadores: bool, like, numeric, gt, lt, gte, lte, between, in.
   *
   * @param mixed $item El modelo o item a evaluar
   * @param string $filter El nombre del accessor/atributo
   * @param string $operator El tipo de operador accessor (accessor_bool, accessor_like, etc.)
   * @param mixed $value El valor a comparar
   * @return bool True si el item cumple la condición, false en caso contrario
   */
  protected function applyAccessorFilterCondition($item, $filter, $operator, $value)
  {
    $itemValue = data_get($item, $filter);

    switch ($operator) {
      case 'accessor_bool':
        $bool = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($bool === null && ($value === '0' || $value === 0)) {
          $bool = false;
        }
        return $itemValue == $bool;

      case 'accessor_like':
        return stripos($itemValue, $value) !== false;

      case 'accessor_numeric':
        return $itemValue == $value;

      case 'accessor_gt':
        return $itemValue > $value;

      case 'accessor_lt':
        return $itemValue < $value;

      case 'accessor_gte':
        return $itemValue >= $value;

      case 'accessor_lte':
        return $itemValue <= $value;

      case 'accessor_between':
        if (is_array($value) && count($value) === 2) {
          return $itemValue >= $value[0] && $itemValue <= $value[1];
        }
        return false;

      case 'accessor_in':
        // $itemValue es el array del accessor; $value es el escalar buscado
        return in_array($value, (array)$itemValue);

      default:
        return $itemValue == $value;
    }
  }

  /**
   * Aplica una condición de filtro específica a la consulta de Eloquent.
   *
   * Traduce operadores de filtro a condiciones WHERE de Eloquent.
   * Califica automáticamente las columnas con el nombre de tabla para evitar ambigüedades.
   *
   * @param \Illuminate\Database\Eloquent\Builder $query La consulta de Eloquent
   * @param string $filter El nombre de la columna a filtrar
   * @param string $operator El operador de filtrado (like, between, date_btw, >, <, >=, <=, =, in, in_or_equal)
   * @param mixed $value El valor a filtrar (puede ser string, array, número, etc.)
   * @return void
   */
  protected function applyFilterCondition($query, $filter, $operator, $value)
  {
    // Obtener el alias de la tabla si existe
    $filter = $this->qualifyColumn($query, $filter);

    switch ($operator) {
      case 'like':
        $query->where($filter, 'like', '%' . $value . '%');
        break;
      case 'between':
        if (is_array($value) && count($value) === 2) {
          $query->whereBetween($filter, $value);
        }
        break;
      case 'date_btw':
      case 'date_between':
        if (is_array($value) && count($value) === 2) {
          $query->whereDate($filter, '>=', $value[0])
            ->whereDate($filter, '<=', $value[1]);
        }
        break;
      case '>':
        $query->where($filter, '>', $value);
        break;
      case '<':
        $query->where($filter, '<', $value);
        break;
      case '>=':
        $query->where($filter, '>=', $value);
        break;
      case '<=':
        $query->where($filter, '<=', $value);
        break;
      case '=':
        $query->where($filter, '=', $value);
        break;
      case 'in':
        $query->whereIn($filter, $value);
        break;
      case 'in_or_equal':
        // Acepta tanto un valor único como un array
        if (is_array($value)) {
          $query->whereIn($filter, $value);
        } else {
          $query->where($filter, '=', $value);
        }
        break;
      default:
        break;
    }
  }

  /**
   * Califica el nombre de columna con el alias de tabla si es necesario.
   *
   * Previene errores de ambigüedad cuando hay joins al prefijar columnas con el nombre de tabla.
   * Si la columna ya contiene un punto (tabla.columna), no hace ninguna modificación.
   *
   * @param \Illuminate\Database\Eloquent\Builder $query La consulta de Eloquent
   * @param string $column El nombre de la columna
   * @return string La columna calificada en formato tabla.columna
   */
  protected function qualifyColumn($query, $column)
  {
    // Si ya tiene un alias (contiene punto), no hacer nada
    if (str_contains($column, '.')) {
      return $column;
    }

    // Obtener el modelo de la query
    $model = $query->getModel();

    // Obtener el nombre de la tabla
    $table = $model->getTable();

    // Retornar columna calificada con el nombre de tabla
    return $table . '.' . $column;
  }

  /**
   * Aplica ordenamiento a la consulta basándose en parámetros de la petición.
   *
   * Lee los parámetros 'sort' (campo) y 'direction' (asc/desc) del request.
   * Si no se especifica un campo válido, ordena por 'id' descendente por defecto.
   *
   * @param \Illuminate\Database\Eloquent\Builder $query La consulta de Eloquent
   * @param \Illuminate\Http\Request $request La petición HTTP con parámetros sort y direction
   * @param array $sorts Array de campos permitidos para ordenamiento
   * @return \Illuminate\Database\Eloquent\Builder La consulta con el ordenamiento aplicado
   */
  protected function applySorting($query, $request, $sorts)
  {
    $sortField = $request->query('sort');
    $sortOrder = $request->query('direction', 'desc');

    if ($sortField !== null && in_array($sortField, $sorts)) {
      // Calificar el campo con el alias de tabla
      $qualifiedField = $this->qualifyColumn($query, $sortField);
      $query->orderBy($qualifiedField, $sortOrder);
    } else {
      // Calificar 'id' con el alias de tabla
      $qualifiedId = $this->qualifyColumn($query, 'id');
      $query->orderBy($qualifiedId, $sortOrder);
    }

    return $query;
  }

  /**
   * Aplica ordenamiento a una colección por un accessor (atributo calculado).
   *
   * Dado que los accessors no existen en la base de datos, el ordenamiento
   * se realiza en memoria sobre la colección.
   *
   * @param \Illuminate\Support\Collection $collection La colección de modelos a ordenar
   * @param \Illuminate\Http\Request $request La petición HTTP con parámetros sort y direction
   * @param array $sorts Array de campos de ordenamiento, donde los accessors tienen valor 'accessor*'
   * @return \Illuminate\Support\Collection La colección ordenada
   */
  protected function applyAccessorSorting($collection, $request, $sorts)
  {
    $sortField = $request->query('sort');
    $sortOrder = $request->query('direction', 'desc');

    if ($sortField !== null && in_array($sortField, $sorts) && str_starts_with($sorts[$sortField] ?? '', 'accessor')) {
      $collection = $collection->sortBy(function ($item) use ($sortField) {
        return data_get($item, $sortField);
      }, SORT_REGULAR, $sortOrder === 'desc');
    }

    return $collection;
  }

  /**
   * Genera los links de paginación en formato compatible con Laravel.
   *
   * Crea un array de links con formato similar al de Laravel paginate:
   * - Link Previous
   * - Links de páginas numeradas (máximo 10 páginas alrededor de la actual)
   * - Puntos suspensivos (...) cuando hay saltos en la numeración
   * - Link Next
   *
   * @param int $currentPage La página actual
   * @param int $lastPage La última página disponible
   * @param string $baseUrl La URL base para los links
   * @param array $queryParams Los parámetros de query string a preservar
   * @return array Array de links con estructura: ['url', 'label', 'active']
   */
  protected function generatePaginationLinks($currentPage, $lastPage, $baseUrl, $queryParams)
  {
    $links = [];

    // Previous link
    $links[] = [
      'url' => $currentPage > 1 ?
        $baseUrl . '?' . http_build_query(array_merge($queryParams, ['page' => $currentPage - 1])) : null,
      'label' => '&laquo; Previous',
      'active' => false
    ];

    // Generar links de páginas (mostrar máximo 10 páginas alrededor de la actual)
    $start = max(1, $currentPage - 5);
    $end = min($lastPage, $currentPage + 5);

    // Primera página si no está en el rango
    if ($start > 1) {
      $links[] = [
        'url' => $baseUrl . '?' . http_build_query(array_merge($queryParams, ['page' => 1])),
        'label' => '1',
        'active' => false
      ];

      if ($start > 2) {
        $links[] = [
          'url' => null,
          'label' => '...',
          'active' => false
        ];
      }
    }

    // Páginas del rango
    for ($page = $start; $page <= $end; $page++) {
      $links[] = [
        'url' => $page === $currentPage ? null :
          $baseUrl . '?' . http_build_query(array_merge($queryParams, ['page' => $page])),
        'label' => (string)$page,
        'active' => $page === $currentPage
      ];
    }

    // Última página si no está en el rango
    if ($end < $lastPage) {
      if ($end < $lastPage - 1) {
        $links[] = [
          'url' => null,
          'label' => '...',
          'active' => false
        ];
      }

      $links[] = [
        'url' => $baseUrl . '?' . http_build_query(array_merge($queryParams, ['page' => $lastPage])),
        'label' => (string)$lastPage,
        'active' => false
      ];
    }

    // Next link
    $links[] = [
      'url' => $currentPage < $lastPage ?
        $baseUrl . '?' . http_build_query(array_merge($queryParams, ['page' => $currentPage + 1])) : null,
      'label' => 'Next &raquo;',
      'active' => false
    ];

    return $links;
  }

  /**
   * Método principal que ejecuta filtrado, ordenamiento, paginación y transformación de recursos.
   *
   * Este método coordina todo el proceso de filtrado:
   * 1. Aplica filtros de base de datos y virtuales
   * 2. Maneja filtros y ordenamiento de accessors (en memoria)
   * 3. Aplica paginación (manual para accessors, SQL para otros)
   * 4. Transforma resultados usando Resource classes
   * 5. Retorna respuesta JSON con estructura de paginación Laravel
   *
   * Soporta dos modos:
   * - Paginado: Retorna data, links y meta con información de paginación
   * - Todo (all=true): Retorna todos los resultados sin paginación
   *
   * @param \Illuminate\Http\Request $request La petición HTTP con parámetros de filtrado
   * @param array $filters Array de configuración de filtros
   * @param array $sorts Array de campos permitidos para ordenamiento
   * @param string $resource Clase Resource para transformar los datos
   * @param array $resourceConfig Configuración adicional para el Resource (métodos y parámetros)
   * @return \Illuminate\Http\JsonResponse Respuesta JSON con datos paginados o completos
   */
  protected function getFilteredResults($modelOrQuery, $request, $filters, $sorts, $resource, $resourceConfig = [])
  {
    $query = $modelOrQuery instanceof Builder ? $modelOrQuery : $modelOrQuery::query();

    // Aplicar filtros de base de datos
    $query = $this->applyFilters($query, $request, $filters);

    // Solo aplicar ordenamiento de BD si no es por accessor
    $sortField = $request->query('sort');
    $hasAccessorSort = $sortField && isset($sorts[$sortField]) &&
      is_string($sorts[$sortField]) &&
      str_starts_with($sorts[$sortField], 'accessor');

    if (!$hasAccessorSort) {
      $query = $this->applySorting($query, $request, $sorts);
    }

    $all = $request->query('all', false) === 'true';

    // Verificar si hay filtros de accessor
    $hasAccessorFilters = collect($filters)->contains(function ($operator) {
      return is_string($operator) && str_starts_with($operator, 'accessor');
    });

    if ($hasAccessorFilters || $hasAccessorSort) {
      // Si hay filtros o ordenamiento de accessor, obtenemos todo y filtramos en memoria
      $results = $query->get();

      // Aplicar filtros de accessor
      if ($hasAccessorFilters) {
        $results = $this->applyAccessorFilters($results, $request, $filters);
      }

      // Aplicar ordenamiento de accessor
      if ($hasAccessorSort) {
        $results = $this->applyAccessorSorting($results, $request, $sorts);
        $results = $results->values(); // Re-indexar
      }

      // Configurar Resource si se proporcionan configuraciones
      $resourceCollection = $this->configureResourceCollection($resource, $results, $resourceConfig);

      // Paginación manual para accessors
      if (!$all) {
        $perPage = (int)$request->query('per_page', Constants::DEFAULT_PER_PAGE);
        $page = (int)$request->query('page', 1);
        $total = $results->count();
        $lastPage = (int)ceil($total / $perPage);
        $from = $total > 0 ? (($page - 1) * $perPage) + 1 : null;
        $to = $total > 0 ? min($from + $perPage - 1, $total) : null;

        $paginatedResults = $results->slice(($page - 1) * $perPage, $perPage)->values();

        // Configurar Resource paginado
        $paginatedResourceCollection = $this->configureResourceCollection($resource, $paginatedResults, $resourceConfig);

        // Generar URLs simples de paginación
        $baseUrl = $request->url();
        $queryParams = $request->query();

        // 👇 CORREGIDO: Formato simple de links
        $first = $baseUrl . '?' . http_build_query(array_merge($queryParams, ['page' => 1]));
        $last = $baseUrl . '?' . http_build_query(array_merge($queryParams, ['page' => $lastPage]));
        $prev = $page > 1 ? $baseUrl . '?' . http_build_query(array_merge($queryParams, ['page' => $page - 1])) : null;
        $next = $page < $lastPage ? $baseUrl . '?' . http_build_query(array_merge($queryParams, ['page' => $page + 1])) : null;

        return response()->json([
          'data' => $paginatedResourceCollection,
          'links' => [
            'first' => $first,
            'last' => $last,
            'prev' => $prev,
            'next' => $next,
          ],
          'meta' => [
            'current_page' => $page,
            'from' => $from,
            'last_page' => $lastPage,
            'links' => $this->generatePaginationLinks($page, $lastPage, $baseUrl, $queryParams),
            'path' => $baseUrl,
            'per_page' => $perPage,
            'to' => $to,
            'total' => $total,
          ]
        ]);
      }

      return response()->json($resourceCollection);
    }

    // Flujo normal sin accessors
    if ($all) {
      $results = $query->get();
      $resourceCollection = $this->configureResourceCollection($resource, $results, $resourceConfig);
      return response()->json($resourceCollection);
    } else {
      // 👇 NUEVO: Usar el mismo formato simple para todos los casos
      $perPage = (int)$request->query('per_page', Constants::DEFAULT_PER_PAGE);
      $page = (int)$request->query('page', 1);

      // Contar total sin obtener todos los registros
      $total = $query->count();
      $lastPage = (int)ceil($total / $perPage);
      $from = $total > 0 ? (($page - 1) * $perPage) + 1 : null;
      $to = $total > 0 ? min($from + $perPage - 1, $total) : null;

      // Obtener solo los registros de la página actual
      $results = $query->skip(($page - 1) * $perPage)->take($perPage)->get();

      // Configurar Resource
      $resourceCollection = $this->configureResourceCollection($resource, $results, $resourceConfig);

      // Generar URLs simples de paginación
      $baseUrl = $request->url();
      $queryParams = $request->query();

      $first = $baseUrl . '?' . http_build_query(array_merge($queryParams, ['page' => 1]));
      $last = $baseUrl . '?' . http_build_query(array_merge($queryParams, ['page' => $lastPage]));
      $prev = $page > 1 ? $baseUrl . '?' . http_build_query(array_merge($queryParams, ['page' => $page - 1])) : null;
      $next = $page < $lastPage ? $baseUrl . '?' . http_build_query(array_merge($queryParams, ['page' => $page + 1])) : null;

      return response()->json([
        'data' => $resourceCollection,
        'links' => [
          'first' => $first,
          'last' => $last,
          'prev' => $prev,
          'next' => $next,
        ],
        'meta' => [
          'current_page' => $page,
          'from' => $from,
          'last_page' => $lastPage,
          'links' => $this->generatePaginationLinks($page, $lastPage, $baseUrl, $queryParams),
          'path' => $baseUrl,
          'per_page' => $perPage,
          'to' => $to,
          'total' => $total,
        ]
      ]);
    }
  }

  /**
   * Configura una colección completa de recursos aplicando transformaciones.
   *
   * Mapea cada item de la colección a una instancia del Resource especificado,
   * aplicando las configuraciones definidas.
   *
   * @param string $resource Clase del Resource a instanciar
   * @param \Illuminate\Support\Collection $collection La colección de modelos
   * @param array $config Array de configuración para aplicar a cada instancia
   * @return \Illuminate\Support\Collection Colección de instancias Resource configuradas
   */
  protected function configureResourceCollection($resource, $collection, $config)
  {
    return $collection->map(function ($item) use ($resource, $config) {
      return $this->configureResourceInstance($resource, $item, $config);
    });
  }

  /**
   * Configura una instancia individual de Resource aplicando métodos dinámicamente.
   *
   * Permite configurar Resources llamando métodos específicos con sus parámetros.
   * Ejemplo: ['withRelations' => ['users'], 'setFormat' => 'detailed']
   *
   * @param string $resource Clase del Resource a instanciar
   * @param mixed $item El modelo o item a transformar
   * @param array $config Array donde la clave es el método y el valor son los parámetros
   * @return mixed Instancia del Resource configurada
   */
  protected function configureResourceInstance($resource, $item, $config)
  {
    $resourceInstance = new $resource($item);

    // Aplicar configuraciones
    foreach ($config as $method => $params) {
      if (method_exists($resourceInstance, $method)) {
        // Validación más robusta para prevenir "Only arrays and traversables can be unpacked"
        if (is_array($params) && count($params) > 0 && array_is_list($params)) {
          // Solo usar spread operator si es array secuencial válido y no vacío
          $resourceInstance->$method(...$params);
        } else {
          $resourceInstance->$method($params);
        }
      }
    }

    return $resourceInstance;
  }

  /**
   * Pagina manualmente una colección en memoria.
   *
   * Útil cuando ya tienes una colección procesada (agrupada, transformada, etc.)
   * y necesitas paginarla. Retorna el mismo formato que getFilteredResults.
   *
   * Características:
   * - Soporta modo all=true para retornar toda la colección
   * - Genera links de paginación compatibles con Laravel
   * - Incluye metadata completa (current_page, total, per_page, etc.)
   *
   * @param \Illuminate\Support\Collection $collection La colección a paginar
   * @param \Illuminate\Http\Request $request La petición HTTP con parámetros page, per_page, all
   * @return \Illuminate\Http\JsonResponse Respuesta JSON con estructura de paginación Laravel
   */
  protected function paginateCollection($collection, $request)
  {
    $all = filter_var($request->query('all', false), FILTER_VALIDATE_BOOLEAN);

    if ($all) {
      return response()->json($collection);
    }

    $perPage = (int)$request->query('per_page', Constants::DEFAULT_PER_PAGE);
    $page = (int)$request->query('page', 1);
    $total = $collection->count();
    $lastPage = (int)ceil($total / $perPage);
    $from = $total > 0 ? (($page - 1) * $perPage) + 1 : null;
    $to = $total > 0 ? min($from + $perPage - 1, $total) : null;

    $paginatedResults = $collection->slice(($page - 1) * $perPage, $perPage)->values();

    $baseUrl = $request->url();
    $queryParams = $request->query();

    $first = $baseUrl . '?' . http_build_query(array_merge($queryParams, ['page' => 1]));
    $last = $baseUrl . '?' . http_build_query(array_merge($queryParams, ['page' => $lastPage]));
    $prev = $page > 1 ? $baseUrl . '?' . http_build_query(array_merge($queryParams, ['page' => $page - 1])) : null;
    $next = $page < $lastPage ? $baseUrl . '?' . http_build_query(array_merge($queryParams, ['page' => $page + 1])) : null;

    return response()->json([
      'data' => $paginatedResults,
      'links' => [
        'first' => $first,
        'last' => $last,
        'prev' => $prev,
        'next' => $next,
      ],
      'meta' => [
        'current_page' => $page,
        'from' => $from,
        'last_page' => $lastPage,
        'links' => $this->generatePaginationLinks($page, $lastPage, $baseUrl, $queryParams),
        'path' => $baseUrl,
        'per_page' => $perPage,
        'to' => $to,
        'total' => $total,
      ]
    ]);
  }
}
