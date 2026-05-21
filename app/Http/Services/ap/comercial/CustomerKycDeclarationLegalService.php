<?php

namespace App\Http\Services\ap\comercial;

use App\Http\Resources\ap\comercial\CustomerKycDeclarationLegalResource;
use App\Http\Services\BaseService;
use App\Http\Services\BaseServiceInterface;
use App\Http\Services\gp\gestionsistema\DigitalFileService;
use App\Models\ap\comercial\CustomerKycDeclarationLegal;
use App\Models\gp\gestionsistema\DigitalFile;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class CustomerKycDeclarationLegalService extends BaseService implements BaseServiceInterface
{
  public function list(Request $request)
  {
    $query = CustomerKycDeclarationLegal::query()
      ->with(['businessPartner.documentType', 'officeDistrict.province.department']);

    return $this->getFilteredResults(
      $query,
      $request,
      CustomerKycDeclarationLegal::filters,
      CustomerKycDeclarationLegal::sorts,
      CustomerKycDeclarationLegalResource::class,
    );
  }

  public function store(mixed $data)
  {
    $declaration = CustomerKycDeclarationLegal::create($data);

    return new CustomerKycDeclarationLegalResource($this->loadRelations($declaration->id));
  }

  public function show($id)
  {
    return new CustomerKycDeclarationLegalResource($this->find($id));
  }

  public function update(mixed $data)
  {
    $declaration = $this->find($data['id']);
    unset($data['id']);
    $declaration->update($data);

    return new CustomerKycDeclarationLegalResource($this->loadRelations($declaration->id));
  }

  public function destroy($id)
  {
    $declaration = $this->find($id);
    $declaration->delete();
    return response()->json(['message' => 'Declaración eliminada correctamente']);
  }

  public function find($id)
  {
    $declaration = $this->loadRelations($id);

    if (!$declaration) {
      throw new \Exception("Declaración jurada KYC (Persona Jurídica) con ID {$id} no encontrada.");
    }

    return $declaration;
  }

  public function downloadPdf($id)
  {
    $declaration = $this->find($id);

    if ($declaration->status === CustomerKycDeclarationLegal::STATUS_PENDIENTE) {
      $declaration->setAttribute('status', CustomerKycDeclarationLegal::STATUS_GENERADO);
      $declaration->save();
    }

    $filename = "declaracion-jurada-kyc-juridica_{$declaration->id}_{$declaration->declaration_date->format('Y-m-d')}.pdf";

    return Pdf::loadView('exports.customer-kyc-declaration-legal', compact('declaration'))
      ->setPaper('a4', 'portrait')
      ->stream($filename);
  }

  public function uploadSignedDocument($id, UploadedFile $file)
  {
    $declaration       = $this->find($id);
    $digitalFileService = new DigitalFileService();

    if ($declaration->status === CustomerKycDeclarationLegal::STATUS_FIRMADO && $declaration->signed_file_path) {
      $oldFile = DigitalFile::where('url', $declaration->signed_file_path)->first();
      if ($oldFile) {
        $digitalFileService->destroy($oldFile->id);
      }
    }

    $digitalFileResource = $digitalFileService->store(
      $file,
      "kyc-declarations-legal/signed/{$id}/",
      'public',
      $declaration->getTable()
    );

    $digitalFile         = $digitalFileResource->resource;
    $digitalFile->id_model = $id;
    $digitalFile->save();

    $declaration->setAttribute('status', CustomerKycDeclarationLegal::STATUS_FIRMADO);
    $declaration->setAttribute('signed_file_path', $digitalFile->url);
    $declaration->save();

    return new CustomerKycDeclarationLegalResource($this->loadRelations($declaration->id));
  }

  public function confirmLegalReview($id, $userId)
  {
    $declaration = $this->find($id);

    if ($declaration->status !== CustomerKycDeclarationLegal::STATUS_FIRMADO) {
      throw new \Exception("Solo se pueden confirmar declaraciones en estado FIRMADO.");
    }

    $declaration->setAttribute('legal_review_status', CustomerKycDeclarationLegal::LEGAL_REVIEW_STATUS_CONFIRMADO);
    $declaration->setAttribute('reviewed_by', $userId);
    $declaration->setAttribute('legal_review_at', now());
    $declaration->setAttribute('legal_review_comments', null);
    $declaration->save();

    return new CustomerKycDeclarationLegalResource($this->loadRelations($declaration->id));
  }

  public function rejectLegalReview($id, $userId, $comments)
  {
    $declaration = $this->find($id);

    if ($declaration->status !== CustomerKycDeclarationLegal::STATUS_FIRMADO) {
      throw new \Exception("Solo se pueden rechazar declaraciones en estado FIRMADO.");
    }

    if (empty($comments)) {
      throw new \Exception("Debe proporcionar un motivo de rechazo.");
    }

    $declaration->setAttribute('legal_review_status', CustomerKycDeclarationLegal::LEGAL_REVIEW_STATUS_RECHAZADO);
    $declaration->setAttribute('reviewed_by', $userId);
    $declaration->setAttribute('legal_review_at', now());
    $declaration->setAttribute('legal_review_comments', $comments);
    $declaration->save();

    return new CustomerKycDeclarationLegalResource($this->loadRelations($declaration->id));
  }

  private function loadRelations($id)
  {
    return CustomerKycDeclarationLegal::with([
      'businessPartner.documentType',
      'officeDistrict.province.department',
      'reviewedBy',
    ])->find($id);
  }
}
