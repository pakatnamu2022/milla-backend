<?php

namespace App\Http\Controllers\ap\facturacion;

use App\Http\Controllers\Controller;
use App\Models\ap\facturacion\DocumentType;
use App\Models\ap\facturacion\TransactionType;
use App\Models\ap\facturacion\IdentityDocumentType;
use App\Models\ap\facturacion\IgvType;
use App\Models\ap\facturacion\CreditNoteType;
use App\Models\ap\facturacion\DebitNoteType;
use App\Models\ap\facturacion\Currency;
use App\Models\ap\facturacion\DetractionType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class BillingCatalogController extends Controller
{
    /**
     * Cache duration in seconds (24 hours)
     */
    const CACHE_DURATION = 86400;

    /**
     * Get all document types (Factura, Boleta, NC, ND)
     */
    public function getDocumentTypes(Request $request): JsonResponse
    {
        $onlyActive = $request->boolean('only_active', true);
        $cacheKey = "billing_catalogs.document_types." . ($onlyActive ? 'active' : 'all');

        $data = Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($onlyActive) {
            $query = DocumentType::query();

            if ($onlyActive) {
                $query->where('is_active', true);
            }

            return $query->orderBy('code')->get();
        });

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Get all transaction types (Venta Interna, Exportación, etc.)
     */
    public function getTransactionTypes(Request $request): JsonResponse
    {
        $onlyActive = $request->boolean('only_active', true);
        $cacheKey = "billing_catalogs.transaction_types." . ($onlyActive ? 'active' : 'all');

        $data = Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($onlyActive) {
            $query = TransactionType::query();

            if ($onlyActive) {
                $query->where('is_active', true);
            }

            return $query->orderBy('code')->get();
        });

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Get all identity document types (DNI, RUC, etc.)
     */
    public function getIdentityDocumentTypes(Request $request): JsonResponse
    {
        $onlyActive = $request->boolean('only_active', true);
        $cacheKey = "billing_catalogs.identity_document_types." . ($onlyActive ? 'active' : 'all');

        $data = Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($onlyActive) {
            $query = IdentityDocumentType::query();

            if ($onlyActive) {
                $query->where('is_active', true);
            }

            return $query->orderBy('code')->get();
        });

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Get all IGV types (Gravado, Exonerado, Inafecto, etc.)
     */
    public function getIgvTypes(Request $request): JsonResponse
    {
        $onlyActive = $request->boolean('only_active', true);
        $cacheKey = "billing_catalogs.igv_types." . ($onlyActive ? 'active' : 'all');

        $data = Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($onlyActive) {
            $query = IgvType::query();

            if ($onlyActive) {
                $query->where('is_active', true);
            }

            return $query->orderBy('code')->get();
        });

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Get all credit note types
     */
    public function getCreditNoteTypes(Request $request): JsonResponse
    {
        $onlyActive = $request->boolean('only_active', true);
        $cacheKey = "billing_catalogs.credit_note_types." . ($onlyActive ? 'active' : 'all');

        $data = Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($onlyActive) {
            $query = CreditNoteType::query();

            if ($onlyActive) {
                $query->where('is_active', true);
            }

            return $query->orderBy('code')->get();
        });

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Get all debit note types
     */
    public function getDebitNoteTypes(Request $request): JsonResponse
    {
        $onlyActive = $request->boolean('only_active', true);
        $cacheKey = "billing_catalogs.debit_note_types." . ($onlyActive ? 'active' : 'all');

        $data = Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($onlyActive) {
            $query = DebitNoteType::query();

            if ($onlyActive) {
                $query->where('is_active', true);
            }

            return $query->orderBy('code')->get();
        });

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Get all currencies
     */
    public function getCurrencies(Request $request): JsonResponse
    {
        $onlyActive = $request->boolean('only_active', true);
        $cacheKey = "billing_catalogs.currencies." . ($onlyActive ? 'active' : 'all');

        $data = Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($onlyActive) {
            $query = Currency::query();

            if ($onlyActive) {
                $query->where('is_active', true);
            }

            return $query->orderBy('code')->get();
        });

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Get all detraction types
     */
    public function getDetractionTypes(Request $request): JsonResponse
    {
        $onlyActive = $request->boolean('only_active', true);
        $cacheKey = "billing_catalogs.detraction_types." . ($onlyActive ? 'active' : 'all');

        $data = Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($onlyActive) {
            $query = DetractionType::query();

            if ($onlyActive) {
                $query->where('is_active', true);
            }

            return $query->orderBy('code')->get();
        });

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Get all catalogs at once (útil para inicializar el frontend)
     */
    public function getAllCatalogs(Request $request): JsonResponse
    {
        $onlyActive = $request->boolean('only_active', true);
        $cacheKey = "billing_catalogs.all." . ($onlyActive ? 'active' : 'all');

        $data = Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($onlyActive) {
            $query = function ($model) use ($onlyActive) {
                $q = $model::query();
                if ($onlyActive) {
                    $q->where('is_active', true);
                }
                return $q->orderBy('code')->get();
            };

            return [
                'document_types' => $query(DocumentType::class),
                'transaction_types' => $query(TransactionType::class),
                'identity_document_types' => $query(IdentityDocumentType::class),
                'igv_types' => $query(IgvType::class),
                'credit_note_types' => $query(CreditNoteType::class),
                'debit_note_types' => $query(DebitNoteType::class),
                'currencies' => $query(Currency::class),
                'detraction_types' => $query(DetractionType::class),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Clear all catalog caches (útil después de ejecutar seeders)
     */
    public function clearCache(): JsonResponse
    {
        $patterns = [
            'billing_catalogs.document_types.*',
            'billing_catalogs.transaction_types.*',
            'billing_catalogs.identity_document_types.*',
            'billing_catalogs.igv_types.*',
            'billing_catalogs.credit_note_types.*',
            'billing_catalogs.debit_note_types.*',
            'billing_catalogs.currencies.*',
            'billing_catalogs.detraction_types.*',
            'billing_catalogs.all.*',
        ];

        foreach ($patterns as $pattern) {
            // Clear both active and all versions
            Cache::forget(str_replace('*', 'active', $pattern));
            Cache::forget(str_replace('*', 'all', $pattern));
        }

        // Alternative: flush all cache (más agresivo)
        // Cache::flush();

        return response()->json([
            'success' => true,
            'message' => 'Caché de catálogos limpiado correctamente'
        ]);
    }
}
