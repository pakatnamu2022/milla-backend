<?php

namespace App\Http\Services\ap\comercial;

use App\Http\Resources\ap\comercial\BusinessPartnersResource;
use App\Http\Resources\ap\comercial\BusinessPartnersEstablishmentResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Http\Services\DatabaseSyncService;
use App\Http\Utils\Constants;
use App\Http\Utils\Helpers;
use App\Jobs\ProcessEstablishments;
use App\Jobs\UpdateEstablishments;
use App\Models\ap\ApCommercialMasters;
use App\Models\ap\comercial\BusinessPartners;
use App\Models\ap\comercial\Opportunity;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BusinessPartnersService extends BaseService implements BaseServiceInterface
{
  protected DatabaseSyncService $syncService;

  public function __construct(DatabaseSyncService $syncService)
  {
    $this->syncService = $syncService;
  }

  public function list(Request $request)
  {
    return $this->getFilteredResults(
      BusinessPartners::class,
      $request,
      BusinessPartners::filters,
      BusinessPartners::sorts,
      BusinessPartnersResource::class,
    );
  }

  public function find($id)
  {
    $businessPartner = BusinessPartners::where('id', $id)->first();
    if (!$businessPartner) {
      throw new Exception('Socio comercial no encontrado');
    }
    return $businessPartner;
  }

  public function store(mixed $data)
  {
    DB::beginTransaction();
    try {
      $data = $this->getData($data);

      // Verificar si existe
      $existingPartner = BusinessPartners::where('num_doc', $data['num_doc'])
        ->whereNull('deleted_at')
        ->first();

      if ($existingPartner) {
        // Si existe y tiene un type diferente, actualizar a AMBOS
        if ($existingPartner->type !== $data['type'] && $existingPartner->type !== BusinessPartners::BOTH) {
          $data['type'] = BusinessPartners::BOTH;
          $data['id'] = $existingPartner->id;
          $existingPartner->update($data);

          DB::commit();
          return new BusinessPartnersResource($existingPartner);
        }

        // Si ya es AMBOS o mismo tipo, no hacer nada
        DB::commit();
        return new BusinessPartnersResource($existingPartner);
      }

      // Si no existe, crear nuevo
      $businessPartner = BusinessPartners::create($data);

      if ($data['document_type_id'] == Constants::TYPE_DOCUMENT_RUC_ID && str_starts_with($data['num_doc'], '20')) {
        ProcessEstablishments::dispatch($businessPartner->id, $data['num_doc']);
      }

      // Sincronizar a otras bases de datos
      if ($businessPartner->type === BusinessPartners::SUPPLIER) {
        $this->syncService->sync('business_partners_ap_supplier', $businessPartner->toArray(), 'create');
        $this->syncService->sync('business_partners_directions_ap_supplier', $businessPartner->toArray(), 'create');
      }

      DB::commit();
      return new BusinessPartnersResource($businessPartner);
    } catch (Exception $e) {
      DB::rollBack();
      throw new Exception($e->getMessage());
    }
  }

  public function show($id)
  {
    return new BusinessPartnersResource($this->find($id));
  }

  public function update(mixed $data)
  {
    DB::beginTransaction();
    try {
      $businessPartner = $this->find($data['id']);

      // Guardar el RUC anterior para comparar
      $previousNumDoc = $businessPartner->num_doc;
      $previousDocumentTypeId = $businessPartner->document_type_id;

      $data = $this->getData($data);
      $businessPartner->update($data);

      // Solo procesar establecimientos si es RUC
      if ($data['document_type_id'] == Constants::TYPE_DOCUMENT_RUC_ID) {
        $rucChanged = $previousNumDoc !== $data['num_doc'];
        $wasRuc = $previousDocumentTypeId == Constants::TYPE_DOCUMENT_RUC_ID;

        if (!$wasRuc) {
          ProcessEstablishments::dispatch($businessPartner->id, $data['num_doc']);
        } else {
          UpdateEstablishments::dispatch(
            $businessPartner->id,
            $data['num_doc'],
            $rucChanged ? $previousNumDoc : null
          );
        }
      } elseif ($previousDocumentTypeId == Constants::TYPE_DOCUMENT_RUC_ID) {
        $businessPartner->establishments()->delete();
      }

      // Sincronizar a otras bases de datos
      //$this->syncService->sync('business_partners', $businessPartner->toArray(), 'update');

      DB::commit();
      return new BusinessPartnersResource($businessPartner);
    } catch (Exception $e) {
      DB::rollBack();
      throw new Exception($e->getMessage());
    }
  }

  public function destroy($id, $typeToRemove = null)
  {
    DB::beginTransaction();
    try {
      $businessPartner = $this->find($id);

      if (!$typeToRemove) {
        $businessPartner->delete();
        DB::commit();
        return response()->json(['message' => 'Registro eliminado correctamente']);
      }

      $validTypes = ['CLIENTE', 'PROVEEDOR'];
      if (!in_array($typeToRemove, $validTypes)) {
        throw new Exception('Tipo inválido. Debe ser CLIENTE o PROVEEDOR');
      }

      $currentType = $businessPartner->type;

      switch ($currentType) {
        case 'CLIENTE':
          if ($typeToRemove === 'CLIENTE') {
            $businessPartner->delete();
            $message = 'Cliente eliminado correctamente';
          } else {
            throw new Exception('Este registro es solo CLIENTE, no se puede remover como PROVEEDOR');
          }
          break;

        case 'PROVEEDOR':
          if ($typeToRemove === 'PROVEEDOR') {
            $businessPartner->delete();
            $message = 'Proveedor eliminado correctamente';
          } else {
            throw new Exception('Este registro es solo PROVEEDOR, no se puede remover como CLIENTE');
          }
          break;

        case 'AMBOS':
          if ($typeToRemove === 'CLIENTE') {
            $businessPartner->update(['type' => 'PROVEEDOR']);
            $message = 'Cliente eliminado correctamente';
          } elseif ($typeToRemove === 'PROVEEDOR') {
            $businessPartner->update(['type' => 'CLIENTE']);
            $message = 'Proveedor eliminado correctamente';
          }
          break;

        default:
          throw new Exception('Registro no reconocido');
      }

      DB::commit();
      return response()->json(['message' => $message]);
    } catch (Exception $e) {
      DB::rollBack();
      throw new Exception($e->getMessage());
    }
  }

  /**
   * @param mixed $data
   * @return mixed
   * @throws Exception
   */
  public function getData(mixed $data): mixed
  {
    if (isset($data['type_person_id']) && $data['type_person_id'] == Constants::TYPE_PERSON_NATURAL_ID && $data['type'] != 'PROVEEDOR') {
      if (empty($data['birth_date'])) {
        throw new Exception('La fecha de nacimiento es requerida para personas naturales');
      }

      $isAdult = Helpers::isAdult($data['birth_date']);
      if (!$isAdult) {
        throw new Exception('El cliente debe ser mayor de edad');
      }
    }

    $TypeDocument = ApCommercialMasters::findOrFail($data['document_type_id']);
    $NumCharDoc = strlen($data['num_doc']);
    if ($TypeDocument->code != $NumCharDoc) {
      throw new Exception("El número de documento debe tener {$TypeDocument->code} caracteres para el tipo de documento seleccionado");
    }
    return $data;
  }

  /**
   * Obtener establecimientos de un socio comercial
   */
  public function getEstablishments($businessPartnerId)
  {
    $businessPartner = $this->find($businessPartnerId);
    return BusinessPartnersEstablishmentResource::collection($businessPartner->establishments);
  }

  /**
   * Validar si un socio comercial tiene oportunidades abiertas
   */
  public function validateOpportunity($id)
  {
    $businessPartner = $this->find($id);
    if (!$businessPartner->status_ap) throw new Exception('El socio comercial no es un cliente activo');
    $statusIds = ApCommercialMasters::where('type', 'OPPORTUNITY_STATUS')->whereIn('code', Opportunity::OPEN_STATUS_CODES)->pluck('id')->toArray();
    $opportunity = Opportunity::where('client_id', $businessPartner->id)->whereIn('opportunity_status_id', $statusIds)->first();
    if ($opportunity) {
      throw new Exception('El cliente tiene oportunidades abiertas');
    }
    return new BusinessPartnersResource($businessPartner);
  }
}
