<?php

namespace App\Services;

use GuzzleHttp\Exception\ConnectException;
use Illuminate\Support\Facades\Log;
use OpenAI\Contracts\ClientContract;
use OpenAI\Exceptions\ErrorException;
use OpenAI\Exceptions\TransporterException;

class ExtractionService
{
    public function __construct(private ClientContract $client)
    {
    }

    public function extract(string $text, string $level, ?string $domain = null): array
    {
        $prompt = $this->buildPrompt($text, $level, $domain);

        $temperature = $this->preferredTemperature();
        $maxTokens = (int) config('services.openai.chat_max_tokens', 1600);
        $reasoningEffort = $this->reasoningEffortValue();

        $model = config('services.openai.llm_model');
        if (empty($model)) {
            throw new \RuntimeException('OpenAI LLM model is not configured.');
        }

        $payload = [
            'model' => $model,
            'response_format' => [
                'type' => 'json_schema',
                'json_schema' => $this->schema(),
            ],
            'messages' => [
                ['role' => 'system', 'content' => 'You ara a BPMN Process Designer. Return ONE valid BPMN 2.0 XML (bpmn.io / bpmn-js importable) in a single ```xml code block:
- Include definitions, collaboration+participant→process, laneSet with lanes, start/end, tasks/gateways, sequenceFlows.
- Include full DI (participant, lanes, all nodes, all flows); IDs consistent.
- No boundaryEventRef; no unresolved refs.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => $temperature,
            'max_completion_tokens' => $maxTokens,
        ];

        if ($reasoningEffort) {
            $payload['reasoning_effort'] = $reasoningEffort;
        }

        try {
            $response = $this->client->chat()->create($payload);
        } catch (ConnectException $exception) {
            throw new \RuntimeException('OpenAI request timed out while generating the BPMN model.', 504, $exception);
        } catch (ErrorException|TransporterException $exception) {
            throw new \RuntimeException('Extraction failed: '.$exception->getMessage(), $exception->getCode(), $exception);
        }

        $payload = $response->toArray();

        $finishReason = data_get($payload, 'choices.0.finish_reason');
        if ($finishReason === 'length') {
            Log::warning('OpenAI extraction truncated (length)', [
                'usage' => data_get($payload, 'usage'),
            ]);

            throw new \RuntimeException('Extraction aborted because the output exceeded the model limit. Bitte Text kürzen oder den Prozess in kleinere Abschnitte teilen.');
        }

        $content = data_get($payload, 'choices.0.message.content');

        if (is_array($content)) {
            $content = collect($content)
                ->map(function ($item) {
                    if (is_string($item)) {
                        return $item;
                    }

                    if (is_array($item) && isset($item['text'])) {
                        return $item['text'];
                    }

                    return '';
                })
                ->implode("\n");
        }

        if (! is_string($content) || trim($content) === '') {
            Log::warning('OpenAI extraction returned empty content', [
                'payload' => $payload,
            ]);

            throw new \RuntimeException('Extraction failed: model returned empty response.');
        }

        $json = $this->extractJsonFrom($content);

        try {
            return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \RuntimeException('Extraction returned invalid JSON: '.$json, previous: $exception);
        }
    }

    protected function preferredTemperature(): float
    {
        $model = config('services.openai.llm_model');
        $temperature = (float) config('services.openai.chat_temperature', 0.2);

        if (str_starts_with($model, 'gpt-5')) {
            return 1.0;
        }

        return $temperature;
    }

    protected function extractJsonFrom(string $content): string
    {
        $trimmed = trim($content);
        if ($trimmed === '') {
            return $trimmed;
        }

        if ($trimmed[0] === '{' && str_ends_with($trimmed, '}')) {
            return $trimmed;
        }

        $start = strpos($trimmed, '{');
        $end = strrpos($trimmed, '}');

        if ($start === false || $end === false || $start > $end) {
            return $trimmed;
        }

        return substr($trimmed, $start, $end - $start + 1);
    }

    protected function buildPrompt(string $text, string $level, ?string $domain): string
    {
        $guidance = match ($level) {
            'L1' => 'Skizziere 6–12 Bereiche. Für jeden Bereich Zweck und Ergebnisse.',
            'L2' => 'Nenne für jeden Prozess Zweck, Start, 1–3 Ergebnisse, Verantwortliche.',
            'L3' => 'Beschreibe Ablauf in kurzen Sätzen, Entscheidungen mit Wenn...dann..., Rollen/Systeme nennen.',
            'L4' => 'Für jeden Schritt: Ziel, Voraussetzungen, Eingaben, genaue Schritte, Ergebnis, Vorlagen/Links.',
            default => 'Strukturiere den Prozess klar und vollständig.',
        };

        $domainSuffix = $domain ? "Domäne: {$domain}." : '';

        return <<<PROMPT
Du bist ein BPMN-2.0-Assistent. ${guidance}
${domainSuffix}
Antworte ausschließlich als JSON, das dem bereitgestellten Schema entspricht. Nutze sprechende IDs (z. B. T1, G1).

Transkript:
"""
{$text}
"""
PROMPT;
    }

    protected function schema(): array
    {
        return [
            'name' => 'bpmn_schema',
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'lanes' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                    ],
                    'tasks' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'required' => ['id', 'lane', 'label'],
                            'properties' => [
                                'id' => ['type' => 'string'],
                                'lane' => ['type' => 'string'],
                                'label' => ['type' => 'string'],
                                'description' => ['type' => 'string'],
                                'sop' => ['type' => 'object'],
                            ],
                        ],
                    ],
                    'gateways' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'required' => ['id', 'label'],
                            'properties' => [
                                'id' => ['type' => 'string'],
                                'label' => ['type' => 'string'],
                                'conditions' => [
                                    'type' => 'array',
                                    'items' => ['type' => 'string'],
                                ],
                            ],
                        ],
                    ],
                    'events' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'required' => ['id', 'type'],
                            'properties' => [
                                'id' => ['type' => 'string'],
                                'type' => ['type' => 'string'],
                                'label' => ['type' => 'string'],
                            ],
                        ],
                    ],
                    'flows' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                        ],
                    ],
                    'artifacts' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'required' => ['id', 'type', 'label'],
                            'properties' => [
                                'id' => ['type' => 'string'],
                                'type' => ['type' => 'string'],
                                'label' => ['type' => 'string'],
                                'attachedTo' => ['type' => 'string'],
                            ],
                        ],
                    ],
                ],
                'required' => ['lanes', 'tasks', 'events', 'flows'],
            ],
        ];
    }
    protected function reasoningEffortValue(): ?string
    {
        $model = config('services.openai.llm_model');
        if (! str_starts_with($model, 'gpt-5')) {
            return null;
        }

        $effort = config('services.openai.reasoning_effort');

        return $effort ?: null;
    }
}
