<?php

namespace App\Http\Traits;

use App\DTO\ServiceResponse;
use Illuminate\Http\JsonResponse;

trait HasApiResponse
{
    public function apiResponse(ServiceResponse $response): JsonResponse
    {
        return response()->json(
            $response->status !== 200
                ? ['message' => $response->message]
                : $response->data,
            $response->status
        );
    }

    public function success(mixed $data = null, int $status = 200): JsonResponse
    {
        return response()->json($data, $status);
    }

    public function error(string $message, int $status = 500): JsonResponse
    {
        return response()->json(['message' => $message], $status);
    }
}

