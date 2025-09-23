<?php

namespace App\Http\Services\DocumentValidation\Contracts;

interface ResponseFormatterInterface
{
    /**
     * Format the provider response to a standardized format
     *
     * @param array $providerResponse
     * @param string $documentType
     * @param string $documentNumber
     * @return array
     */
    public function format(array $providerResponse, string $documentType, string $documentNumber): array;

    /**
     * Format error response
     *
     * @param string $error
     * @param string $documentType
     * @param string $documentNumber
     * @return array
     */
    public function formatError(string $error, string $documentType, string $documentNumber): array;
}