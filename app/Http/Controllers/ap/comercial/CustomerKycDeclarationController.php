<?php

namespace App\Http\Controllers\ap\comercial;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\comercial\IndexCustomerKycDeclarationRequest;
use App\Http\Requests\ap\comercial\StoreCustomerKycDeclarationRequest;
use App\Http\Requests\ap\comercial\UpdateCustomerKycDeclarationRequest;
use App\Http\Requests\ap\comercial\UploadSignedKycDeclarationRequest;
use App\Http\Services\ap\comercial\CustomerKycDeclarationService;
use Throwable;

class CustomerKycDeclarationController extends Controller
{
  protected CustomerKycDeclarationService $service;

  public function __construct(CustomerKycDeclarationService $service)
  {
    $this->service = $service;
  }

  public function index(IndexCustomerKycDeclarationRequest $request)
  {
    try {
      return $this->service->list($request);
    } catch (Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function store(StoreCustomerKycDeclarationRequest $request)
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

  public function update(UpdateCustomerKycDeclarationRequest $request, $id)
  {
    try {
      $data = $request->all();
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
}
