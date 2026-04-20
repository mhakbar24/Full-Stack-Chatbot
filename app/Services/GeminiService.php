<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Throwable;

class GeminiService
{
    public function generateText(string $prompt, ?string $systemInstruction = null, bool $forceJson = false): ?string
    {
        $apiKey = config('services.gemini.api_key');
        $model = config('services.gemini.model', 'gemini-2.5-flash');
        $timeout = (int) config('services.gemini.timeout', 60);
        $retryTimes = (int) config('services.gemini.retry_times', 1);
        $retrySleepMs = (int) config('services.gemini.retry_sleep_ms', 500);

        if (!$apiKey) {
            return null;
        }

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
        ];

        if ($systemInstruction) {
            $payload['systemInstruction'] = [
                'parts' => [
                    ['text' => $systemInstruction],
                ],
            ];
        }

        if ($forceJson) {
            $payload['generationConfig'] = [
                'responseMimeType' => 'application/json',
            ];
        }

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-goog-api-key' => $apiKey,
            ])
                ->timeout(max(5, $timeout))
                ->retry(max(0, $retryTimes), max(0, $retrySleepMs))
                ->post(
                    "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent",
                    $payload
                );
        } catch (ConnectionException|Throwable $e) {
            return null;
        }

        if (!$response->successful()) {
            return null;
        }

        $data = $response->json();

        return $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
    }
}
