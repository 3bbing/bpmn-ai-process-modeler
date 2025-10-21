<?php

namespace App\Services;

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

        try {
            $response = $this->client->responses()->create([
                'model' => config('services.openai.llm_model'),
                'response_format' => [
                    'type' => 'json_schema',
                    'json_schema' => $this->schema(),
                ],
                'input' => $prompt,
            ]);
        } catch (ErrorException|TransporterException $exception) {
            throw new \RuntimeException('Extraction failed: '.$exception->getMessage(), $exception->getCode(), $exception);
        }

        $payload = $response->toArray();
        $content = data_get($payload, 'output.0.content.0.text');

        return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
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
}
