<?php

namespace App\Http\Services\ap\comercial;

use App\Http\Resources\ap\comercial\BusinessPartnersResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Http\Utils\Constants;
use App\Http\Utils\Helpers;
use App\Models\ap\ApCommercialMasters;
use App\Models\ap\comercial\BusinessPartners;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BusinessPartnersService extends BaseService implements BaseServiceInterface
{
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
      $businessPartner = BusinessPartners::create($data);
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
      $data = $this->getData($data);
      $businessPartner->update($data);
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

      // Si no se especifica qué type remover, eliminar completamente
      if (!$typeToRemove) {
        $businessPartner->delete();
        DB::commit();
        return response()->json(['message' => 'Socio comercial eliminado correctamente']);
      }

      // Validar que el type a remover sea válido
      $validTypes = ['CLIENTE', 'PROVEEDOR'];
      if (!in_array($typeToRemove, $validTypes)) {
        throw new Exception('Tipo inválido. Debe ser CLIENTE o PROVEEDOR');
      }

      $currentType = $businessPartner->type;

      // Lógica según el tipo actual
      switch ($currentType) {
        case 'CLIENTE':
          if ($typeToRemove === 'CLIENTE') {
            // Si es solo CLIENTE y quiere remover CLIENTE, eliminar completamente
            $businessPartner->delete();
            $message = 'Cliente eliminado correctamente';
          } else {
            throw new Exception('Este socio comercial es solo CLIENTE, no se puede remover como PROVEEDOR');
          }
          break;

        case 'PROVEEDOR':
          if ($typeToRemove === 'PROVEEDOR') {
            // Si es solo PROVEEDOR y quiere remover PROVEEDOR, eliminar completamente
            $businessPartner->delete();
            $message = 'Proveedor eliminado correctamente';
          } else {
            throw new Exception('Este socio comercial es solo PROVEEDOR, no se puede remover como CLIENTE');
          }
          break;

        case 'AMBOS':
          if ($typeToRemove === 'CLIENTE') {
            // Remover CLIENTE, queda como PROVEEDOR
            $businessPartner->update(['type' => 'PROVEEDOR']);
            $message = 'Cliente removido. Socio comercial ahora es solo PROVEEDOR';
          } elseif ($typeToRemove === 'PROVEEDOR') {
            // Remover PROVEEDOR, queda como CLIENTE
            $businessPartner->update(['type' => 'CLIENTE']);
            $message = 'Proveedor removido. Socio comercial ahora es solo CLIENTE';
          }
          break;

        default:
          throw new Exception('Tipo de socio comercial no reconocido');
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
    if (isset($data['type_person_id']) && $data['type_person_id'] == Constants::TYPE_PERSON_ID) {
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
}
