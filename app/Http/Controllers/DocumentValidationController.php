<?php

namespace App\Http\Controllers;

use App\Http\Services\DocumentValidation\DocumentValidationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class DocumentValidationController extends Controller
{
  protected DocumentValidationService $documentValidationService;

  public function __construct(DocumentValidationService $documentValidationService)
  {
    $this->documentValidationService = $documentValidationService;
  }

  /**
   * Validate a document
   *
   * @param Request $request
   * @return JsonResponse
   */
  public function validateGeneral(Request $request): JsonResponse
  {
    $availableTypes = array_keys($this->documentValidationService->getAvailableDocumentTypes());

    $validated = $request->validate([
      'document_type' => ['required', 'string', Rule::in($availableTypes)],
      'document_number' => 'required|string|max:20',
      'use_cache' => 'sometimes|boolean',
      'additional_params' => 'sometimes|array',
    ]);

    $result = $this->documentValidationService->validateDocument(
      $validated['document_type'],
      $validated['document_number'],
      $validated['additional_params'] ?? [],
      $validated['use_cache'] ?? true
    );

    $statusCode = $result['success'] ? 200 : 400;

    return response()->json($result, $statusCode);
  }

  /**
   * Validate DNI specifically
   *
   * @param Request $request
   * @return JsonResponse
   */
  public function validateDni(Request $request): JsonResponse
  {
    $validated = $request->validate([
      'dni' => 'required|string|size:8|regex:/^[0-9]+$/',
      'use_cache' => 'sometimes|boolean',
    ]);

    $result = $this->documentValidationService->validateDocument(
      'dni',
      $validated['dni'],
      [],
      $validated['use_cache'] ?? true
    );

    //$statusCode = $result['success'] ? 200 : 400;

    return response()->json($result, Response::HTTP_OK);
  }

  /**
   * Validate RUC specifically
   *
   * @param Request $request
   * @return JsonResponse
   */
  public function validateRuc(Request $request): JsonResponse
  {
    $validated = $request->validate([
      'ruc' => 'required|string|size:11|regex:/^[0-9]+$/',
      'use_cache' => 'sometimes|boolean',
    ]);

    $result = $this->documentValidationService->validateDocument(
      'ruc',
      $validated['ruc'],
      [],
      $validated['use_cache'] ?? true
    );

    //$statusCode = $result['success'] ? 200 : 400;

    return response()->json($result, Response::HTTP_OK);
  }

  /**
   * Validate driver license specifically
   *
   * @param Request $request
   * @return JsonResponse
   */
  public function validateLicense(Request $request): JsonResponse
  {
    $validated = $request->validate([
      'license' => 'required|string|max:20',
      'use_cache' => 'sometimes|boolean',
    ]);

    $result = $this->documentValidationService->validateDocument(
      'license',
      $validated['license'],
      [],
      $validated['use_cache'] ?? true
    );

    //$statusCode = $result['success'] ? 200 : 400;

    return response()->json($result, Response::HTTP_OK);
  }

  /**
   * Get available document types
   *
   * @return JsonResponse
   */
  public function documentTypes(): JsonResponse
  {
    return response()->json([
      'success' => true,
      'data' => $this->documentValidationService->getAvailableDocumentTypes(),
    ]);
  }

  /**
   * Get provider information
   *
   * @return JsonResponse
   */
  public function providerInfo(): JsonResponse
  {
    return response()->json([
      'success' => true,
      'data' => $this->documentValidationService->getProviderInfo(),
    ]);
  }

  /**
   * Clear cache for a specific document
   *
   * @param Request $request
   * @return JsonResponse
   */
  public function clearCache(Request $request): JsonResponse
  {
    $availableTypes = array_keys($this->documentValidationService->getAvailableDocumentTypes());

    $validated = $request->validate([
      'document_type' => ['required', 'string', Rule::in($availableTypes)],
      'document_number' => 'required|string|max:20',
      'additional_params' => 'sometimes|array',
    ]);

    $cleared = $this->documentValidationService->clearCache(
      $validated['document_type'],
      $validated['document_number'],
      $validated['additional_params'] ?? []
    );

    return response()->json([
      'success' => true,
      'message' => $cleared ? 'Cache cleared successfully' : 'Cache entry not found',
      'cleared' => $cleared,
    ]);
  }

  /**
   * Clear all document validation cache
   *
   * @return JsonResponse
   */
  public function clearAllCache(): JsonResponse
  {
    $cleared = $this->documentValidationService->clearAllCache();

    return response()->json([
      'success' => true,
      'message' => $cleared ? 'All cache cleared successfully' : 'Failed to clear cache',
      'cleared' => $cleared,
    ]);
  }
}
