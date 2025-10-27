<?php

namespace App\Models\ap\facturacion;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class NubefactLog extends BaseModel
{
    use SoftDeletes;

    protected $table = 'ap_billing_nubefact_logs';

    protected $fillable = [
        'ap_billing_electronic_document_id',
        'action',
        'endpoint',
        'request_payload',
        'response_payload',
        'response_code',
        'response_status',
        'error_message',
        'execution_time',
        'ip_address',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array',
        'response_code' => 'integer',
        'execution_time' => 'float',
    ];

    // Acciones
    const ACTION_SEND = 'send';
    const ACTION_QUERY = 'query';
    const ACTION_CANCEL = 'cancel';
    const ACTION_DOWNLOAD_PDF = 'download_pdf';
    const ACTION_DOWNLOAD_XML = 'download_xml';
    const ACTION_DOWNLOAD_CDR = 'download_cdr';
    const ACTION_SEND_EMAIL = 'send_email';

    // Estados de respuesta
    const STATUS_SUCCESS = 'success';
    const STATUS_ERROR = 'error';
    const STATUS_PENDING = 'pending';

    /**
     * Relaciones
     */
    public function electronicDocument(): BelongsTo
    {
        return $this->belongsTo(ElectronicDocument::class, 'ap_billing_electronic_document_id');
    }

    /**
     * Scopes
     */
    public function scopeByDocument($query, int $documentId)
    {
        return $query->where('ap_billing_electronic_document_id', $documentId);
    }

    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('response_status', self::STATUS_SUCCESS);
    }

    public function scopeFailed($query)
    {
        return $query->where('response_status', self::STATUS_ERROR);
    }

    public function scopePending($query)
    {
        return $query->where('response_status', self::STATUS_PENDING);
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeSendActions($query)
    {
        return $query->where('action', self::ACTION_SEND);
    }

    public function scopeQueryActions($query)
    {
        return $query->where('action', self::ACTION_QUERY);
    }

    public function scopeCancelActions($query)
    {
        return $query->where('action', self::ACTION_CANCEL);
    }

    /**
     * Accessors
     */
    public function getIsSuccessAttribute(): bool
    {
        return $this->response_status === self::STATUS_SUCCESS;
    }

    public function getIsErrorAttribute(): bool
    {
        return $this->response_status === self::STATUS_ERROR;
    }

    public function getIsPendingAttribute(): bool
    {
        return $this->response_status === self::STATUS_PENDING;
    }

    public function getFormattedExecutionTimeAttribute(): string
    {
        if ($this->execution_time < 1) {
            return round($this->execution_time * 1000, 2) . ' ms';
        }
        return round($this->execution_time, 2) . ' s';
    }

    public function getActionLabelAttribute(): string
    {
        return match($this->action) {
            self::ACTION_SEND => 'Envío a SUNAT',
            self::ACTION_QUERY => 'Consulta de estado',
            self::ACTION_CANCEL => 'Anulación',
            self::ACTION_DOWNLOAD_PDF => 'Descarga PDF',
            self::ACTION_DOWNLOAD_XML => 'Descarga XML',
            self::ACTION_DOWNLOAD_CDR => 'Descarga CDR',
            self::ACTION_SEND_EMAIL => 'Envío de email',
            default => 'Acción desconocida',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->response_status) {
            self::STATUS_SUCCESS => 'Exitoso',
            self::STATUS_ERROR => 'Error',
            self::STATUS_PENDING => 'Pendiente',
            default => 'Desconocido',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->response_status) {
            self::STATUS_SUCCESS => 'success',
            self::STATUS_ERROR => 'danger',
            self::STATUS_PENDING => 'warning',
            default => 'secondary',
        };
    }

    /**
     * Métodos de negocio
     */
    public static function logRequest(
        int $documentId,
        string $action,
        string $endpoint,
        array $requestPayload,
        array $responsePayload = [],
        int $responseCode = null,
        string $responseStatus = self::STATUS_PENDING,
        string $errorMessage = null,
        float $executionTime = null
    ): self {
        return self::create([
            'ap_billing_electronic_document_id' => $documentId,
            'action' => $action,
            'endpoint' => $endpoint,
            'request_payload' => $requestPayload,
            'response_payload' => $responsePayload,
            'response_code' => $responseCode,
            'response_status' => $responseStatus,
            'error_message' => $errorMessage,
            'execution_time' => $executionTime,
            'ip_address' => request()->ip(),
        ]);
    }

    public function updateResponse(
        array $responsePayload,
        int $responseCode,
        string $responseStatus,
        string $errorMessage = null,
        float $executionTime = null
    ): void {
        $this->update([
            'response_payload' => $responsePayload,
            'response_code' => $responseCode,
            'response_status' => $responseStatus,
            'error_message' => $errorMessage,
            'execution_time' => $executionTime,
        ]);
    }

    public function markAsSuccess(array $responsePayload, int $responseCode, float $executionTime = null): void
    {
        $this->updateResponse(
            $responsePayload,
            $responseCode,
            self::STATUS_SUCCESS,
            null,
            $executionTime
        );
    }

    public function markAsError(string $errorMessage, array $responsePayload = [], int $responseCode = null, float $executionTime = null): void
    {
        $this->updateResponse(
            $responsePayload,
            $responseCode ?? 500,
            self::STATUS_ERROR,
            $errorMessage,
            $executionTime
        );
    }
}
