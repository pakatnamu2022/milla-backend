<?php

namespace App\Http\Services\ap\comercial;

use App\Http\Resources\ap\comercial\CustomerKycDeclarationResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Models\ap\comercial\CustomerKycDeclaration;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CustomerKycDeclarationService extends BaseService implements BaseServiceInterface
{
  public function list(Request $request)
  {
    $query = CustomerKycDeclaration::query()
      ->with(['businessPartner.documentType', 'businessPartner.maritalStatus', 'businessPartner.district.province.department']);

    return $this->getFilteredResults(
      $query,
      $request,
      CustomerKycDeclaration::filters,
      CustomerKycDeclaration::sorts,
      CustomerKycDeclarationResource::class,
    );
  }

  public function store(mixed $data)
  {
    $declaration = CustomerKycDeclaration::create($data);

    return new CustomerKycDeclarationResource(
      CustomerKycDeclaration::with(['businessPartner.documentType', 'businessPartner.maritalStatus', 'businessPartner.district.province.department'])
        ->find($declaration->id)
    );
  }

  public function show($id)
  {
    return new CustomerKycDeclarationResource($this->find($id));
  }

  public function update(mixed $data)
  {
    $declaration = $this->find($data['id']);
    unset($data['id']);
    $declaration->update($data);

    return new CustomerKycDeclarationResource(
      CustomerKycDeclaration::with(['businessPartner.documentType', 'businessPartner.maritalStatus', 'businessPartner.district.province.department'])
        ->find($declaration->id)
    );
  }

  public function destroy($id)
  {
    $declaration = $this->find($id);
    $declaration->delete();
    return response()->json(['message' => 'Declaración eliminada correctamente']);
  }

  public function find($id)
  {
    $declaration = CustomerKycDeclaration::with([
      'businessPartner.documentType',
      'businessPartner.maritalStatus',
      'businessPartner.district.province.department',
    ])->where('id', $id)->first();

    if (!$declaration) {
      throw new \Exception("Declaración jurada KYC con ID {$id} no encontrada.");
    }

    return $declaration;
  }

  public function downloadPdf($id)
  {
    $declaration = $this->find($id);

    if ($declaration->status === CustomerKycDeclaration::STATUS_PENDIENTE) {
      $declaration->setAttribute('status', CustomerKycDeclaration::STATUS_GENERADO);
      $declaration->save();
    }

    $filename = "declaracion-jurada-kyc_{$declaration->id}_{$declaration->declaration_date->format('Y-m-d')}.pdf";

    return Pdf::loadView('exports.customer-kyc-declaration', compact('declaration'))
      ->setPaper('a4', 'portrait')
      ->stream($filename);
  }

  public function uploadSignedDocument($id, UploadedFile $file)
  {
    $declaration = $this->find($id);

    if ($declaration->status === CustomerKycDeclaration::STATUS_FIRMADO) {
      Storage::delete($declaration->signed_file_path);
    }

    $path = $file->storeAs(
      "kyc-declarations/signed/{$id}",
      now()->format('YmdHis') . '_' . $file->getClientOriginalName()
    );

    $declaration->setAttribute('status', CustomerKycDeclaration::STATUS_FIRMADO);
    $declaration->setAttribute('signed_file_path', $path);
    $declaration->save();

    return new CustomerKycDeclarationResource(
      CustomerKycDeclaration::with(['businessPartner.documentType', 'businessPartner.maritalStatus', 'businessPartner.district.province.department'])
        ->find($declaration->id)
    );
  }
}
