<?php

namespace App\Http\Controllers;

use App\Http\Services\Ai\GeminiService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiController extends Controller
{
    protected GeminiService $gemini;

    public function __construct(GeminiService $gemini)
    {
        $this->gemini = $gemini;
    }

    public function generateText(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'prompt'              => 'required|string|max:10000',
            'thinking_level'      => 'sometimes|string|in:LOW,MEDIUM,HIGH',
            'use_search'          => 'sometimes|boolean',
            'system_instruction'  => 'sometimes|nullable|string|max:2000',
        ]);

        try {
            $result = $this->gemini->generateText(
                $validated['prompt'],
                $validated['thinking_level'] ?? 'MEDIUM',
                $validated['use_search'] ?? false,
                $validated['system_instruction'] ?? null,
            );

            return $this->success($result);

        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
