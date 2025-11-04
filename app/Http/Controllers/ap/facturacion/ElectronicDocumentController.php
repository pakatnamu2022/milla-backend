<?php

namespace App\Http\Controllers\ap\facturacion;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\facturacion\IndexElectronicDocumentRequest;
use App\Http\Requests\ap\facturacion\NextCorrelativeElectronicDocumentRequest;
use App\Http\Requests\ap\facturacion\StoreElectronicDocumentRequest;
use App\Http\Requests\ap\facturacion\UpdateElectronicDocumentRequest;
use App\Http\Services\ap\facturacion\ElectronicDocumentService;
use App\Http\Traits\HasApiResponse;
use App\Models\ap\maestroGeneral\AssignSalesSeries;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class ElectronicDocumentController extends Controller
{
  use HasApiResponse;

  protected ElectronicDocumentService $service;

  public function __construct(ElectronicDocumentService $service)
  {
    $this->service = $service;
  }

  /**
   * Display a listing of electronic documents
   */
  public function index(IndexElectronicDocumentRequest $request): JsonResponse
  {
    try {
      return $this->service->list($request);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Get next correlative document number
   * @param NextCorrelativeElectronicDocumentRequest $request
   * @return JsonResponse
   */
  public function nextDocumentNumber(NextCorrelativeElectronicDocumentRequest $request): JsonResponse
  {
    try {
      $series = AssignSalesSeries::find($request->input('series'));
      return $this->success($this->service->nextDocumentNumber(
        $request->input('document_type'),
        $series->series
      ));
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Store a newly created electronic document
   */
  public function store(StoreElectronicDocumentRequest $request): JsonResponse
  {
    try {
      return $this->success($this->service->store($request->validated()));
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Display the specified electronic document
   */
  public function show($id): JsonResponse
  {
    try {
      $document = $this->service->find($id);

      return $this->success([
        'success' => true,
        'data' => $document
      ]);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Update the specified electronic document
   */
  public function update(UpdateElectronicDocumentRequest $request, $id): JsonResponse
  {
    try {
      $data = $request->validated();
      $data['id'] = $id;
      $document = $this->service->update($data);

      return $this->success([
        'success' => true,
        'message' => 'Documento electrónico actualizado correctamente',
        'data' => $document
      ]);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Remove the specified electronic document
   */
  public function destroy($id): JsonResponse
  {
    try {
      return $this->service->destroy($id);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Send document to Nubefact/SUNAT
   */
  public function sendToNubefact($id): JsonResponse
  {
    try {
      return $this->service->sendToNubefact($id);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Query document status from Nubefact
   */
  public function queryFromNubefact($id): JsonResponse
  {
    try {
      return $this->service->queryFromNubefact($id);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Cancel document in Nubefact (Comunicación de baja)
   */
  public function cancelInNubefact(Request $request, $id): JsonResponse
  {
    $request->validate([
      'reason' => 'required|string|min:10|max:250'
    ]);

    try {
      return $this->service->cancelInNubefact($id, $request->input('reason'));
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Create credit note from existing document
   */
  public function createCreditNote(Request $request, $id): JsonResponse
  {
    try {
      $creditNote = $this->service->createCreditNote($id, $request->all());

      return $this->success([
        'success' => true,
        'message' => 'Nota de crédito creada correctamente',
        'data' => $creditNote
      ]);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Create debit note from existing document
   */
  public function createDebitNote(Request $request, $id): JsonResponse
  {
    try {
      $debitNote = $this->service->createDebitNote($id, $request->all());

      return $this->success([
        'success' => true,
        'message' => 'Nota de débito creada correctamente',
        'data' => $debitNote
      ]);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Get documents by origin entity
   */
  public function getByOriginEntity($module, $entityType, $entityId): JsonResponse
  {
    try {
      return $this->service->getByOriginEntity($module, $entityType, $entityId);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }

  /**
   * Generate PDF for electronic document
   */
  public function generatePDF($id)
  {
    try {
      $pdf = $this->service->generatePDF($id);

      // Get document info for filename
      $document = $this->service->find($id);
      $filename = "documento-electronico-{$document->serie}-{$document->numero}.pdf";

      return $pdf->download($filename);
    } catch (Exception $e) {
      return $this->error($e->getMessage());
    }
  }
}
