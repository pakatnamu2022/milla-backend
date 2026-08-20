<?php

namespace App\Exceptions;

use Exception;

/**
 * Excepción lanzada cuando se intenta registrar un movimiento en un período contable cerrado.
 *
 * Los períodos cerrados no pueden ser modificados para garantizar la integridad contable
 * y cumplimiento normativo. Solo usuarios con permisos especiales pueden reabrir períodos.
 */
class ClosedPeriodException extends Exception
{
    /**
     * Crea una nueva instancia de la excepción
     *
     * @param string $message Mensaje descriptivo para el usuario
     * @param int $code Código de error (opcional)
     * @param \Throwable|null $previous Excepción anterior (opcional)
     */
    public function __construct(string $message = "", int $code = 0, ?\Throwable $previous = null)
    {
        // Si no se proporciona mensaje, usar uno por defecto
        if (empty($message)) {
            $message = "No se puede registrar el movimiento porque corresponde a un período contable cerrado. Contacta al área contable si necesitas hacer un ajuste.";
        }

        parent::__construct($message, $code, $previous);
    }

    /**
     * Renderizar la excepción para respuestas HTTP
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function render($request)
    {
        return response()->json([
            'success' => false,
            'error' => 'CLOSED_PERIOD',
            'message' => $this->getMessage(),
        ], 422); // 422 Unprocessable Entity
    }

    /**
     * Reportar la excepción (opcional, para logging)
     */
    public function report(): void
    {
        // Se puede agregar logging adicional si es necesario
        // Por ahora, dejamos que Laravel maneje el logging por defecto
    }
}