<?php

namespace App\Http\Services\Ai;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;

class GeminiService
{
    protected string $apiKey;
    protected string $model;
    protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
        $this->model = config('services.gemini.model', 'gemini-2.5-flash');
    }

    public function generateText(string $prompt, string $thinkingLevel = 'MEDIUM', bool $useSearch = false, ?string $systemInstruction = null): array
    {
        if (empty($this->apiKey)) {
            throw new Exception('Gemini API key is not configured.');
        }

        $contents = [
            [
                'role' => 'user',
                'parts' => [['text' => $prompt]],
            ],
        ];

        $generationConfig = [
            'thinkingConfig' => ['thinkingBudget' => $this->thinkingBudget($thinkingLevel)],
        ];

        $defaultSystemInstruction = implode("\n", [
            'Responde siempre en español.',
            'Sé breve y directo.',
            'Nunca excedas las 25 palabras.',
            'No uses listas ni explicaciones adicionales.',
            'Entrega únicamente la respuesta final.',
        ]);

        $body = [
            'systemInstruction' => [
                'parts' => [['text' => $systemInstruction ?? $defaultSystemInstruction]],
            ],
            'contents' => $contents,
            'generationConfig' => $generationConfig,
        ];

        if ($useSearch) {
            $body['tools'] = [['googleSearch' => new \stdClass()]];
        }

        $url = "{$this->baseUrl}/{$this->model}:generateContent?key={$this->apiKey}";

        try {
            $response = Http::timeout(60)
                ->post($url, $body);

            if ($response->failed()) {
                $error = $response->json('error.message', 'Unknown Gemini API error');
                throw new Exception("Gemini API error: {$error}");
            }

            return $this->parseResponse($response->json());

        } catch (RequestException $e) {
            throw new Exception("HTTP request to Gemini failed: {$e->getMessage()}");
        }
    }

    protected function parseResponse(array $json): array
    {
        $candidates = $json['candidates'] ?? [];

        if (empty($candidates)) {
            throw new Exception('Gemini returned no candidates.');
        }

        $parts = $candidates[0]['content']['parts'] ?? [];
        $text = '';

        foreach ($parts as $part) {
            if (isset($part['text'])) {
                $text .= $part['text'];
            }
        }

        return [
            'text' => trim($text),
            'model' => $json['modelVersion'] ?? $this->model,
            'finish_reason' => $candidates[0]['finishReason'] ?? null,
            'usage' => $json['usageMetadata'] ?? null,
        ];
    }

    protected function thinkingBudget(string $level): int
    {
        return match (strtoupper($level)) {
            'LOW'    => 1024,
            'HIGH'   => 8192,
            default  => 4096, // MEDIUM
        };
    }
}
