<?php

namespace App\Http\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DatabaseSyncService
{
  /**
   * Sincroniza datos a múltiples bases de datos según la configuración
   * @param string $entity
   * @param array $data
   * @param string $action
   * @return array
   * @throws Exception
   */
  public function sync(string $entity, array $data, string $action = 'create'): array
  {
    $config = config("database_sync.{$entity}");

    if (!$config) {
      throw new Exception("No existe configuración de sincronización para la entidad: {$entity}");
    }

    $results = [];

    foreach ($config as $connectionName => $syncConfig) {
      if (!$this->isEnabled($syncConfig)) {
        continue;
      }

      $result = $this->syncToDatabase($connectionName, $syncConfig, $data, $action);
      Log::info("Sincronización exitosa a {$connectionName} para la entidad {$entity} con acción {$action}");
      $results[$connectionName] = [
        'success' => true,
        'result' => $result,
      ];
    }

    return $results;
  }

  /**
   * Sincroniza a una base de datos específica
   *
   * @param string $connectionName
   * @param array $config
   * @param array $data
   * @param string $action
   * @return mixed
   * @throws Exception
   */
  protected function syncToDatabase(string $connectionName, array $config, array $data, string $action)
  {
    // Verificar si esta acción está habilitada para esta conexión
    if (!$this->isActionEnabled($config, $action)) {
      return null; // Saltar esta sincronización
    }

    $connection = DB::connection($config['connection']);
    $table = $config['table'];
    $mappedData = $this->mapData($data, $config);

    if (empty($mappedData)) {
      throw new Exception("No hay datos para sincronizar después del mapeo");
    }

    switch ($action) {
      case 'create':
        return $this->handleCreate($connection, $table, $mappedData, $config);

      case 'update':
        return $this->handleUpdate($connection, $table, $mappedData, $config);

      case 'delete':
        return $this->handleDelete($connection, $table, $mappedData, $config);

      default:
        throw new Exception("Acción no soportada: {$action}");
    }
  }

  /**
   * Mapea los datos según la configuración
   *
   * @param array $data
   * @param array $config
   * @return array
   */
  protected function mapData(array $data, array $config): array
  {
    $mappedData = [];
    $mapping = $config['mapping'] ?? [];
    $optionalMapping = $config['optional_mapping'] ?? [];

    // NUEVO: Pasar el original_purchase_order_id si existe para la lógica de actualización
    if (isset($data['original_purchase_order_id']) && !empty($data['original_purchase_order_id'])) {
      $mappedData['original_purchase_order_id'] = $data['original_purchase_order_id'];
    }

    // Mapeo obligatorio
    foreach ($mapping as $localColumn => $externalColumn) {
      $mappedData = $this->mapColumn($data, $mappedData, $localColumn, $externalColumn);
    }

    // Mapeo opcional (solo si existe el dato)
    foreach ($optionalMapping as $localColumn => $externalColumn) {
      if (isset($data[$localColumn]) && $data[$localColumn] !== null) {
        $mappedData = $this->mapColumn($data, $mappedData, $localColumn, $externalColumn);
      }
    }

    return $mappedData;
  }

  /**
   * Mapea una columna individual
   *
   * @param array $data
   * @param array $mappedData
   * @param string $localColumn
   * @param mixed $externalColumn
   * @return array
   */
  protected function mapColumn(array $data, array $mappedData, string $localColumn, $externalColumn): array
  {
    // Si es un closure, ejecutar
    if ($externalColumn instanceof \Closure) {
      $value = $externalColumn($data);
      $mappedData[$localColumn] = $value;
    } // Si es string, mapear directamente
    elseif (is_string($externalColumn)) {
      if (isset($data[$localColumn])) {
        $mappedData[$externalColumn] = $data[$localColumn];
      }
    } // Si es null, usar el mismo nombre
    elseif ($externalColumn === null) {
      if (isset($data[$localColumn])) {
        $mappedData[$localColumn] = $data[$localColumn];
      }
    } // Si es un valor estático
    else {
      $mappedData[$localColumn] = $externalColumn;
    }

    return $mappedData;
  }

  /**
   * Maneja la creación de registros
   *
   * @param \Illuminate\Database\Connection $connection
   * @param string $table
   * @param array $data
   * @param array $config
   * @return mixed
   */
  protected function handleCreate($connection, string $table, array $data, array $config)
  {
    $syncMode = $config['sync_mode'] ?? 'insert';

    if ($syncMode === 'upsert' && isset($config['unique_key'])) {
      // Usar upsert si está configurado
      $uniqueKey = $config['unique_key'];
      return $connection->table($table)->updateOrInsert(
        [$uniqueKey => $data[$uniqueKey]],
        $data
      );
    }

    // Insert normal
    return $connection->table($table)->insert($data);
  }

  /**
   * Maneja la actualización de registros
   *
   * @param \Illuminate\Database\Connection $connection
   * @param string $table
   * @param array $data
   * @param array $config
   * @return mixed
   * @throws Exception
   */
  protected function handleUpdate($connection, string $table, array $data, array $config)
  {
    if (!isset($config['unique_key'])) {
      throw new Exception("Se requiere 'unique_key' en la configuración para actualizar");
    }

    $uniqueKey = $config['unique_key'];

    if (!isset($data[$uniqueKey])) {
      throw new Exception("No se encontró el campo '{$uniqueKey}' en los datos");
    }

    $uniqueValue = $data[$uniqueKey];
    unset($data[$uniqueKey]); // Remover la clave única de los datos a actualizar

    return $connection->table($table)
      ->where($uniqueKey, $uniqueValue)
      ->update($data);
  }

  /**
   * Maneja la eliminación de registros
   *
   * @param \Illuminate\Database\Connection $connection
   * @param string $table
   * @param array $data
   * @param array $config
   * @return mixed
   * @throws Exception
   */
  protected function handleDelete($connection, string $table, array $data, array $config)
  {
    if (!isset($config['unique_key'])) {
      throw new Exception("Se requiere 'unique_key' en la configuración para eliminar");
    }

    $uniqueKey = $config['unique_key'];

    if (!isset($data[$uniqueKey])) {
      throw new Exception("No se encontró el campo '{$uniqueKey}' en los datos");
    }

    return $connection->table($table)
      ->where($uniqueKey, $data[$uniqueKey])
      ->delete();
  }

  /**
   * Verifica si la sincronización está habilitada
   *
   * @param array $config
   * @return bool
   */
  protected function isEnabled(array $config): bool
  {
    return $config['enabled'] ?? true;
  }

  /**
   * Verifica si una acción específica está habilitada para esta conexión
   *
   * @param array $config
   * @param string $action
   * @return bool
   */
  protected function isActionEnabled(array $config, string $action): bool
  {
    // Si no se especifica 'actions', se asume que todas están habilitadas
    if (!isset($config['actions'])) {
      return true;
    }

    // Si existe la configuración de actions, verificar si esta acción está habilitada
    return $config['actions'][$action] ?? true;
  }

  /**
   * Sincroniza de forma asíncrona (puedes implementar con Jobs)
   *
   * @param string $entity
   * @param array $data
   * @param string $action
   * @return void
   */
  public function syncAsync(string $entity, array $data, string $action = 'create'): void
  {
    // Aquí podrías despachar un Job para sincronización asíncrona
    // dispatch(new SyncDatabaseJob($entity, $data, $action));

    // Por ahora ejecuta de forma síncrona
    $this->sync($entity, $data, $action);
  }
}
