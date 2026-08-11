<?php

namespace App\Http\Services\ap\comercial;

use App\Http\Resources\ap\comercial\BusinessPartnersResource;
use App\Http\Resources\ap\comercial\BusinessPartnersEstablishmentResource;
use App\Http\Resources\ap\comercial\OpportunityResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Http\Services\DatabaseSyncService;
use App\Http\Utils\Constants;
use App\Http\Utils\Helpers;
use App\Jobs\ProcessEstablishments;
use App\Jobs\UpdateEstablishments;
use App\Models\ap\ApMasters;
use App\Models\ap\comercial\BusinessPartners;
use App\Models\ap\comercial\Opportunity;
use App\Models\ap\comercial\PotentialBuyers;
use App\Models\ap\comercial\PurchaseRequestQuote;
use App\Models\ap\facturacion\ElectronicDocument;
use Carbon\Carbon;
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
      $data['legal_representative_full_name'] =
        ($data['legal_representative_name'] ?? '') . ' ' .
        ($data['legal_representative_paternal_surname'] ?? '') . ' ' .
        ($data['legal_representative_maternal_surname'] ?? '');

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

      // Crear establecimiento según el tipo
      if ($data['type'] === BusinessPartners::CLIENT && $data['type_person_id'] == Constants::TYPE_NATURAL_PERSON_ID) {
        // Para clientes, crear establecimiento por defecto
        $businessPartner->establishments()->create([
          'code'                => '0000',
          'type'                => 'CENTRAL',
          'activity_economic'   => $businessPartner->activityEconomic->name ?? null,
          'address'             => $businessPartner->direction ?? '-',
          'full_address'        => $businessPartner->direction ?? null,
          'ubigeo'              => $businessPartner->district->ubigeo ?? null,
          'business_partner_id' => $businessPartner->id,
        ]);
      } elseif ($data['type'] === BusinessPartners::SUPPLIER && $data['type_person_id'] == Constants::TYPE_LEGAL_PERSON_ID) {
        // Para proveedores con RUC que inicia con '20', consultar SUNAT
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
      $data['legal_representative_full_name'] =
        ($data['legal_representative_name'] ?? '') . ' ' .
        ($data['legal_representative_paternal_surname'] ?? '') . ' ' .
        ($data['legal_representative_maternal_surname'] ?? '');

      $data = $this->getData($data);
      $businessPartner->update($data);

      // Si es persona natural, actualizar también el establecimiento
      if ($businessPartner->type_person_id == Constants::TYPE_NATURAL_PERSON_ID) {
        $establishment = $businessPartner->establishments()->first();
        if ($establishment) {
          $establishment->update([
            'activity_economic' => $businessPartner->activityEconomic->name ?? null,
            'address'           => $businessPartner->direction ?? '-',
            'full_address'      => $businessPartner->direction ?? null,
            'ubigeo'            => $businessPartner->district->ubigeo ?? null,
          ]);
        }
      }

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
    if (isset($data['type_person_id']) && $data['type_person_id'] == ApMasters::TYPE_PERSON_NATURAL_ID && $data['type'] != 'PROVEEDOR' && isset($data['origin_id'])) {
      if (empty($data['birth_date'])) {
        throw new Exception('La fecha de nacimiento es requerida para personas naturales');
      }

      $birth = Carbon::parse($data['birth_date']);
      $today = Carbon::today();

      if ($birth->isSameDay($today)) {
        $data['birth_date'] = null;
      } else {
        $isAdult = Helpers::isAdult($birth->toDateString());
        if (!$isAdult) {
          throw new Exception('El cliente debe ser mayor de edad');
        }
      }
    }

    $TypeDocument = ApMasters::findOrFail($data['document_type_id']);
    $NumCharDoc = strlen($data['num_doc']);
    if (!ApMasters::validateDocumentLength($TypeDocument->id, $NumCharDoc)) {
      $desc = ApMasters::getDocumentLengthDescription($TypeDocument->id);
      throw new Exception("El número de documento debe tener {$desc} caracteres para el tipo de documento seleccionado");
    }
    return $data;
  }

  /**
   * <<<<<<< HEAD
   * =======
   * Obtener establecimientos de un socio comercial
   */
  public function getEstablishments($businessPartnerId)
  {
    $businessPartner = $this->find($businessPartnerId);
    return BusinessPartnersEstablishmentResource::collection($businessPartner->establishments);
  }

  /**
   * Obtener establecimientos de un socio comercial
   */
  public function getOpportunities($businessPartnerId)
  {
    $businessPartner = $this->find($businessPartnerId);
    return OpportunityResource::collection($businessPartner->opportunities);
  }

  /**
   * Validar si un socio comercial tiene oportunidades abiertas
   */
  public function validateOpportunity($id, $leadId): BusinessPartnersResource
  {
    $businessPartner = $this->find($id);
    if (!$businessPartner->status_ap) throw new Exception('El socio comercial no es un cliente activo');
    $statusIds = ApMasters::where('type', 'OPPORTUNITY_STATUS')->whereIn('code', Opportunity::OPEN_STATUS_CODES)->pluck('id')->toArray();
    $opportunities = Opportunity::where('client_id', $businessPartner->id)
      ->whereIn('opportunity_status_id', $statusIds)
      ->with('family.brand')
      ->get();

    if ($opportunities->count() > 0) {
      // Cerrar oportunidades sin seguimiento: última acción sin resultado y con más de 5 días,
      // o sin ninguna acción y con más de 5 días desde su creación
      foreach ($opportunities as $opportunity) {
        $lastAction = $opportunity->actions()
          ->orderByDesc('datetime')
          ->first();

        $hasApprovedQuote = $opportunity->purchaseRequestsQuote()->where('is_approved', 1)->exists();

        $sinAccionYVencida = !$lastAction && $opportunity->created_at->diffInDays(now()) >= 5;
        $ultimaAccionSinResultado = $lastAction && $lastAction->result === false && $lastAction->datetime->diffInDays(now()) >= 5;

        if (!$hasApprovedQuote && ($sinAccionYVencida || $ultimaAccionSinResultado)) {
          $opportunity->update(
            ['opportunity_status_id' => Opportunity::CLOSED_ID,
             'comment'               => 'Oportunidad cerrada automáticamente por falta de seguimiento después de 5 días']
          );
        }
      }

    }

    // Obtener la marca del lead para scoping de validaciones por marca
    $lead = PotentialBuyers::findOrFail($leadId);
    $newBrandId = $lead->vehicle_brand_id;

    // Verificar solicitudes no facturadas (activas o creadas sin documento válido) para la misma marca
    $hasUnbilledQuote = fn(int $partnerId): bool => PurchaseRequestQuote::where('holder_id', $partnerId)
      ->whereHas('apModelsVn.family', fn($q) => $q->where('brand_id', $newBrandId))
      ->whereHas('opportunity', fn($q) => $q->whereIn('opportunity_status_id', $statusIds))
      ->whereDoesntHave('electronicDocuments', fn($q) => $q
        ->where('aceptada_por_sunat', 1)
        ->where('anulado', 0)
        ->whereIn('sunat_concept_document_type_id', [ElectronicDocument::TYPE_FACTURA, ElectronicDocument::TYPE_BOLETA])
        ->where('is_advance_payment', 0)
      )
      ->exists();

    // Caso 1: El cliente tiene una solicitud no facturada para esta marca
    if ($hasUnbilledQuote($businessPartner->id)) {
      throw new Exception('El cliente ya tiene una solicitud activa sin facturar para esta marca.');
    }

    // Caso 2: El cliente (DNI) es representante legal de una empresa con solicitud no facturada para esta marca
    if ($businessPartner->num_doc) {
      $companyIds = BusinessPartners::where('legal_representative_num_doc', $businessPartner->num_doc)->pluck('id');
      foreach ($companyIds as $companyId) {
        if ($hasUnbilledQuote($companyId)) {
          throw new Exception('El cliente es representante legal de una empresa que ya tiene una solicitud activa sin facturar para esta marca.');
        }
      }
    }

    // Caso 3: El representante legal de la empresa (cliente) ya tiene una solicitud no facturada para esta marca
    if ($businessPartner->legal_representative_num_doc) {
      $representative = BusinessPartners::where('num_doc', $businessPartner->legal_representative_num_doc)->first();
      if ($representative && $hasUnbilledQuote($representative->id)) {
        throw new Exception('El representante legal del cliente (Doc: ' . $businessPartner->legal_representative_num_doc . ') ya tiene una solicitud activa sin facturar para esta marca.');
      }
    }

    // Caso 4: El cliente tiene RUC tipo 10 → el DNI embebido (posiciones 2-9) ya tiene solicitud sin facturar
    // Ejemplo: RUC 10731442121 contiene el DNI 73144212
    $numDoc = $businessPartner->num_doc ?? '';
    $embeddedDni = (strlen($numDoc) === 11 && str_starts_with($numDoc, '10'))
      ? substr($numDoc, 2, 8)
      : null;
    if ($embeddedDni) {
      $dniPartner = BusinessPartners::where('num_doc', $embeddedDni)->first();
      if ($dniPartner && $hasUnbilledQuote($dniPartner->id)) {
        throw new Exception('El cliente comparte número de documento con otro cliente registrado con DNI (' . $embeddedDni . ') que ya tiene una solicitud activa sin facturar para esta marca.');
      }
    }

    // Caso 5: El cliente tiene DNI → existe un RUC tipo 10 derivado que ya tiene solicitud sin facturar
    // Ejemplo: DNI 73144212 está contenido en el RUC 10731442121
    if (strlen($numDoc) === 8) {
      $ruc10Partners = BusinessPartners::where('num_doc', 'like', '10' . $numDoc . '_')->get(['id', 'num_doc']);
      foreach ($ruc10Partners as $ruc10Partner) {
        if ($hasUnbilledQuote($ruc10Partner->id)) {
          throw new Exception('El cliente comparte número de documento con otro cliente registrado con RUC tipo 10 (' . $ruc10Partner->num_doc . ') que ya tiene una solicitud activa sin facturar para esta marca.');
        }
      }
    }

    // Verificar si el mismo cliente ya tiene una oportunidad abierta para esta marca
    $hasSameBrandOpenOpp = Opportunity::where('client_id', $businessPartner->id)
      ->whereIn('opportunity_status_id', $statusIds)
      ->whereHas('family', fn($q) => $q->where('brand_id', $newBrandId))
      ->exists();

    if ($hasSameBrandOpenOpp) {
      throw new Exception('El cliente ya tiene una oportunidad activa para esta marca.');
    }

    // Validar partes relacionadas (misma marca): representante legal y cónyuge/copropietario
    $clientIdsWithOpenOpps = Opportunity::whereIn('opportunity_status_id', $statusIds)
      ->where('client_id', '!=', $businessPartner->id)
      ->whereHas('family', fn($q) => $q->where('brand_id', $newBrandId))
      ->pluck('client_id');

    if ($clientIdsWithOpenOpps->isNotEmpty()) {
      $partnersWithOpenOpps = BusinessPartners::whereIn('id', $clientIdsWithOpenOpps)
        ->whereNotNull('num_doc')
        ->get(['num_doc', 'legal_representative_num_doc', 'spouse_num_doc']);

      // RUC/Empresa: el representante legal del cliente ya tiene oportunidad activa (misma marca)
      if ($businessPartner->legal_representative_num_doc) {
        $conflict = $partnersWithOpenOpps->where('num_doc', $businessPartner->legal_representative_num_doc)->first();
        if ($conflict) {
          throw new Exception('El representante legal del cliente (Doc: ' . $businessPartner->legal_representative_num_doc . ') ya tiene una oportunidad activa para esta marca.');
        }
      }

      // RUC/Empresa: el cliente es representante legal de una empresa con oportunidad activa (misma marca)
      if ($businessPartner->num_doc) {
        $conflict = $partnersWithOpenOpps->where('legal_representative_num_doc', $businessPartner->num_doc)->first();
        if ($conflict) {
          throw new Exception('El cliente es representante legal de una empresa que ya tiene una oportunidad activa para esta marca.');
        }
      }

      // Cónyuge: el cónyuge del cliente ya tiene oportunidad activa (misma marca)
      if ($businessPartner->spouse_num_doc) {
        $conflict = $partnersWithOpenOpps->where('num_doc', $businessPartner->spouse_num_doc)->first();
        if ($conflict) {
          throw new Exception('El cónyuge/copropietario del cliente (Doc: ' . $businessPartner->spouse_num_doc . ') ya tiene una oportunidad activa para esta marca.');
        }
      }

      // Cónyuge: el cliente es cónyuge de alguien con oportunidad activa (misma marca)
      if ($businessPartner->num_doc) {
        $conflict = $partnersWithOpenOpps->where('spouse_num_doc', $businessPartner->num_doc)->first();
        if ($conflict) {
          throw new Exception('El cliente es cónyuge/copropietario de alguien que ya tiene una oportunidad activa para esta marca.');
        }
      }
    }

    // DNI/RUC10 cruzado (oportunidades): RUC tipo 10 → buscar DNI embebido con oportunidad activa
    if ($embeddedDni) {
      $dniPartnerIds = BusinessPartners::where('num_doc', $embeddedDni)->pluck('id');
      if ($dniPartnerIds->isNotEmpty()) {
        $conflict = Opportunity::whereIn('client_id', $dniPartnerIds)
          ->whereIn('opportunity_status_id', $statusIds)
          ->whereHas('family', fn($q) => $q->where('brand_id', $newBrandId))
          ->exists();
        if ($conflict) {
          throw new Exception('El cliente comparte número de documento con otro cliente registrado con DNI (' . $embeddedDni . ') que ya tiene una oportunidad activa para esta marca.');
        }
      }
    }

    // DNI/RUC10 cruzado (oportunidades): DNI → buscar RUC tipo 10 derivado con oportunidad activa
    if (strlen($numDoc) === 8) {
      $ruc10Ids = BusinessPartners::where('num_doc', 'like', '10' . $numDoc . '_')->pluck('id');
      if ($ruc10Ids->isNotEmpty()) {
        $conflictingRuc = BusinessPartners::whereIn('id', $ruc10Ids)
          ->whereIn('id', Opportunity::whereIn('opportunity_status_id', $statusIds)
            ->whereHas('family', fn($q) => $q->where('brand_id', $newBrandId))
            ->pluck('client_id')
          )
          ->value('num_doc');
        if ($conflictingRuc) {
          throw new Exception('El cliente comparte número de documento con otro cliente registrado con RUC tipo 10 (' . $conflictingRuc . ') que ya tiene una oportunidad activa para esta marca.');
        }
      }
    }

    return new BusinessPartnersResource($businessPartner);
  }

  /**
   * Reprocesar los establecimientos de un socio comercial
   */
  public function reprocessEstablishments($id)
  {
    DB::beginTransaction();
    try {
      $businessPartner = $this->find($id);

      // Validar que sea RUC
      if ($businessPartner->document_type_id != Constants::TYPE_DOCUMENT_RUC_ID) {
        throw new Exception('Solo se pueden procesar establecimientos para RUC');
      }

      // Eliminar establecimientos existentes
      $businessPartner->establishments()->delete();

      // Resetear el estado
      $businessPartner->update(['establishments_status' => 'pending']);

      // Despachar el job para procesar los establecimientos
      ProcessEstablishments::dispatch($businessPartner->id, $businessPartner->num_doc);

      DB::commit();

      return [
        'message'             => 'Los establecimientos se están reprocesando',
        'business_partner_id' => $businessPartner->id,
        'status'              => 'pending'
      ];
    } catch (Exception $e) {
      DB::rollBack();
      throw new Exception($e->getMessage());
    }
  }
}
