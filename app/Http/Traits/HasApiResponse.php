<?php

namespace App\Http\Traits;

use Illuminate\Http\JsonResponse;

trait HasApiResponse
{

  public function success(mixed $data = null): JsonResponse
  {
    return response()->json($data);
  }

  public function error(string $message): JsonResponse
  {
    return response()->json(['message' => $message], 500);
  }

  public function errorValidation(string $message): JsonResponse
  {
    return response()->json(['message' => $message], 422);
  }
}

