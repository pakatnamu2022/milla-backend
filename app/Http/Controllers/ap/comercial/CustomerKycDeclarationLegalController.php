<?php

namespace App\Http\Controllers\ap\comercial;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\comercial\IndexCustomerKycDeclarationLegalRequest;
use App\Http\Requests\ap\comercial\StoreCustomerKycDeclarationLegalRequest;
use App\Http\Requests\ap\comercial\UpdateCustomerKycDeclarationLegalRequest;
use App\Http\Requests\ap\comercial\UploadSignedKycDeclarationRequest;
use App\Http\Requests\ap\comercial\LegalReviewRejectKycDeclarationRequest;
use App\Http\Services\ap\comercial\CustomerKycDeclarationLegalService;
use Throwable;

class CustomerKycDeclarationLegalController extends Controller
{
  protected CustomerKycDeclarationLegalService $service;

  public function __construct(CustomerKycDeclarationLegalService $service)
  {
    $this->service = $service;
  }

  public function index(IndexCustomerKycDeclarationLegalRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreCustomerKycDeclarationLegalRequest $request)
  {
    try {
      return $this->success($this->service->store($request->all()));
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function show($id)
  {
    try {
      return $this->success($this->service->show($id));
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function update(UpdateCustomerKycDeclarationLegalRequest $request, $id)
  {
    try {
      $data       = $request->all();
      $data['id'] = $id;
      return $this->success($this->service->update($data));
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function destroy($id)
  {
    try {
      return $this->service->destroy($id);
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function downloadPdf($id)
  {
    try {
      return $this->service->downloadPdf($id);
    } catch (Throwable $th) {
      return response()->json(['error' => $th->getMessage()], 500);
    }
  }

  public function uploadSignedDocument(UploadSignedKycDeclarationRequest $request, $id)
  {
    try {
      return $this->success(
        $this->service->uploadSignedDocument($id, $request->file('signed_file')),
        'Documento firmado registrado correctamente.'
      );
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function confirmLegalReview($id)
  {
    try {
      $userId = auth()->id();
      return $this->success(
        $this->service->confirmLegalReview($id, $userId),
        'Declaración KYC confirmada correctamente.'
      );
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function rejectLegalReview(LegalReviewRejectKycDeclarationRequest $request, $id)
  {
    try {
      $userId = auth()->id();
      return $this->success(
        $this->service->rejectLegalReview($id, $userId, $request->comments),
        'Declaración KYC rechazada correctamente.'
      );
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }
}
