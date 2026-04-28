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
        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function store(StoreCustomerKycDeclarationRequest $request)
    {
        try {
            return $this->success($this->service->store($request->validated()));
        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function show($id)
    {
        try {
            return $this->success($this->service->show($id));
        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function update(UpdateCustomerKycDeclarationRequest $request, $id)
    {
        try {
            $data = $request->validated();
            $data['id'] = $id;
            return $this->success($this->service->update($data));
        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $this->service->destroy($id);
            return $this->success(null, 'Declaración eliminada correctamente.');
        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function downloadPdf($id)
    {
        try {
            return $this->service->downloadPdf($id);
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function uploadSignedDocument(UploadSignedKycDeclarationRequest $request, $id)
    {
        try {
            return $this->success(
                $this->service->uploadSignedDocument($id, $request->file('signed_file')),
                'Documento firmado registrado correctamente.'
            );
        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }
}
