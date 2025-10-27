<?php

namespace App\Http\Controllers\ap\facturacion;

use App\Http\Controllers\Controller;
use App\Http\Requests\ap\facturacion\IndexElectronicDocumentRequest;
use App\Http\Requests\ap\facturacion\StoreElectronicDocumentRequest;
use App\Http\Requests\ap\facturacion\UpdateElectronicDocumentRequest;
use App\Http\Services\ap\facturacion\ElectronicDocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class ElectronicDocumentController extends Controller
{
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
      return response()->json([
        'success' => false,
        'message' => $e->getMessage()
      ], 500);
    }
  }

  /**
   * Store a newly created electronic document
   */
  public function store(StoreElectronicDocumentRequest $request): JsonResponse
  {
    try {
      $document = $this->service->store($request->validated());

      return response()->json([
        'success' => true,
        'message' => 'Documento electrónico creado correctamente',
        'data' => $document
      ], 201);
    } catch (Exception $e) {
      return response()->json([
        'success' => false,
        'message' => $e->getMessage()
      ], 500);
    }
  }

  /**
   * Display the specified electronic document
   */
  public function show($id): JsonResponse
  {
    try {
      $document = $this->service->find($id);

      return response()->json([
        'success' => true,
        'data' => $document
      ]);
    } catch (Exception $e) {
      return response()->json([
        'success' => false,
        'message' => $e->getMessage()
      ], 404);
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

      return response()->json([
        'success' => true,
        'message' => 'Documento electrónico actualizado correctamente',
        'data' => $document
      ]);
    } catch (Exception $e) {
      return response()->json([
        'success' => false,
        'message' => $e->getMessage()
      ], 500);
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
      return response()->json([
        'success' => false,
        'message' => $e->getMessage()
      ], 500);
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
      return response()->json([
        'success' => false,
        'message' => $e->getMessage()
      ], 500);
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
      return response()->json([
        'success' => false,
        'message' => $e->getMessage()
      ], 500);
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
      return response()->json([
        'success' => false,
        'message' => $e->getMessage()
      ], 500);
    }
  }

  /**
   * Create credit note from existing document
   */
  public function createCreditNote(Request $request, $id): JsonResponse
  {
    try {
      $creditNote = $this->service->createCreditNote($id, $request->all());

      return response()->json([
        'success' => true,
        'message' => 'Nota de crédito creada correctamente',
        'data' => $creditNote
      ], 201);
    } catch (Exception $e) {
      return response()->json([
        'success' => false,
        'message' => $e->getMessage()
      ], 500);
    }
  }

  /**
   * Create debit note from existing document
   */
  public function createDebitNote(Request $request, $id): JsonResponse
  {
    try {
      $debitNote = $this->service->createDebitNote($id, $request->all());

      return response()->json([
        'success' => true,
        'message' => 'Nota de débito creada correctamente',
        'data' => $debitNote
      ], 201);
    } catch (Exception $e) {
      return response()->json([
        'success' => false,
        'message' => $e->getMessage()
      ], 500);
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
      return response()->json([
        'success' => false,
        'message' => $e->getMessage()
      ], 500);
    }
  }
}
