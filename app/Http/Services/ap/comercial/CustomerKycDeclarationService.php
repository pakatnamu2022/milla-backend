<?php

namespace App\Http\Services\ap\comercial;

use App\Http\Resources\ap\comercial\CustomerKycDeclarationResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Http\Services\gp\gestionsistema\DigitalFileService;
use App\Models\ap\comercial\CustomerKycDeclaration;
use App\Models\gp\gestionsistema\DigitalFile;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

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
    $digitalFileService = new DigitalFileService();

    if ($declaration->status === CustomerKycDeclaration::STATUS_FIRMADO && $declaration->signed_file_path) {
      $oldFile = DigitalFile::where('url', $declaration->signed_file_path)->first();
      if ($oldFile) {
        $digitalFileService->destroy($oldFile->id);
      }
    }

    $digitalFileResource = $digitalFileService->store(
      $file,
      "kyc-declarations/signed/{$id}/",
      'public',
      $declaration->getTable()
    );

    $digitalFile = $digitalFileResource->resource;
    $digitalFile->id_model = $id;
    $digitalFile->save();

    $declaration->setAttribute('status', CustomerKycDeclaration::STATUS_FIRMADO);
    $declaration->setAttribute('signed_file_path', $digitalFile->url);
    $declaration->save();

    return new CustomerKycDeclarationResource(
      CustomerKycDeclaration::with(['businessPartner.documentType', 'businessPartner.maritalStatus', 'businessPartner.district.province.department'])
        ->find($declaration->id)
    );
  }

  public function confirmLegalReview($id, $userId)
  {
    $declaration = $this->find($id);

    if ($declaration->status !== CustomerKycDeclaration::STATUS_FIRMADO) {
      throw new \Exception("Solo se pueden confirmar declaraciones en estado FIRMADO.");
    }

    $declaration->setAttribute('legal_review_status', CustomerKycDeclaration::LEGAL_REVIEW_STATUS_CONFIRMADO);
    $declaration->setAttribute('reviewed_by', $userId);
    $declaration->setAttribute('legal_review_at', now());
    $declaration->setAttribute('legal_review_comments', null);
    $declaration->save();

    return new CustomerKycDeclarationResource(
      CustomerKycDeclaration::with(['businessPartner.documentType', 'businessPartner.maritalStatus', 'businessPartner.district.province.department', 'reviewedBy'])
        ->find($declaration->id)
    );
  }

  public function rejectLegalReview($id, $userId, $comments)
  {
    $declaration = $this->find($id);

    if ($declaration->status !== CustomerKycDeclaration::STATUS_FIRMADO) {
      throw new \Exception("Solo se pueden rechazar declaraciones en estado FIRMADO.");
    }

    if (empty($comments)) {
      throw new \Exception("Debe proporcionar un motivo de rechazo.");
    }

    $declaration->setAttribute('legal_review_status', CustomerKycDeclaration::LEGAL_REVIEW_STATUS_RECHAZADO);
    $declaration->setAttribute('reviewed_by', $userId);
    $declaration->setAttribute('legal_review_at', now());
    $declaration->setAttribute('legal_review_comments', $comments);
    $declaration->save();

    return new CustomerKycDeclarationResource(
      CustomerKycDeclaration::with(['businessPartner.documentType', 'businessPartner.maritalStatus', 'businessPartner.district.province.department', 'reviewedBy'])
        ->find($declaration->id)
    );
  }
}
