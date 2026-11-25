<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use JsonException;
use UnexpectedValueException;

class DashboardInsightsService
{
    /**
     * @param  array<string, mixed>  $aggregateCounts
     * @return list<string>
     */
    public function generate(array $aggregateCounts): array
    {
        $apiKey = GeminiService::resolveApiKey();
        $model = GeminiService::resolveModel();

        if (! is_string($apiKey) || trim($apiKey) === '' || ! is_string($model) || trim($model) === '') {
            return [];
        }

        $cacheKey = 'dashboard-insights-'.hash('sha256', serialize($aggregateCounts));

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($apiKey, $model, $aggregateCounts): array {
            try {
                // Send only aggregate totals; report text and personal data never enter this request.
                $response = Http::withHeaders(['x-goog-api-key' => $apiKey])
                    ->connectTimeout(2)
                    ->timeout(5)
                    ->post(
                        'https://generativelanguage.googleapis.com/v1beta/models/'.$model.':generateContent',
                        [
                            'contents' => [[
                                'role' => 'user',
                                'parts' => [[
                                    'text' => 'Give up to three short operational insights based only on these aggregate report counts. Return JSON with an "insights" array of strings. Counts: '.json_encode($aggregateCounts, JSON_THROW_ON_ERROR),
                                ]],
                            ]],
                            'generationConfig' => [
                                'responseMimeType' => 'application/json',
                                'responseSchema' => [
                                    'type' => 'OBJECT',
                                    'properties' => [
                                        'insights' => [
                                            'type' => 'ARRAY',
                                            'items' => ['type' => 'STRING'],
                                        ],
                                    ],
                                    'required' => ['insights'],
                                ],
                                'maxOutputTokens' => 256,
                            ],
                        ]
                    );

                $rawResponse = $response->throw()->json();
                $responseText = data_get($rawResponse, 'candidates.0.content.parts.0.text');

                if (! is_string($responseText)) {
                    throw new UnexpectedValueException('Gemini did not return dashboard insights.');
                }

                $result = json_decode($responseText, true, 512, JSON_THROW_ON_ERROR);
                $insights = data_get($result, 'insights');

                if (! is_array($insights)) {
                    throw new UnexpectedValueException('Gemini returned invalid dashboard insights.');
                }

                return collect($insights)
                    ->filter(fn (mixed $insight): bool => is_string($insight) && trim($insight) !== '')
                    ->map(fn (string $insight): string => mb_substr(trim($insight), 0, 180))
                    ->take(3)
                    ->values()
                    ->all();
            } catch (ConnectionException|RequestException|JsonException|UnexpectedValueException $exception) {
                Log::warning('Gemini dashboard insights are unavailable.', [
                    'exception' => $exception::class,
                ]);

                return [];
            }
        });
    }
}
