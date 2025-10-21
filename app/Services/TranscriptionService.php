<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use OpenAI\Contracts\ClientContract;
use OpenAI\Exceptions\ErrorException;
use OpenAI\Exceptions\TransporterException;

class TranscriptionService
{
    public function __construct(
        private ClientContract $client,
        private TextStitchingService $stitching
    )
    {
    }

    public function transcribe(array $fileRefs, ?string $language = null): array
    {
        $responses = [];

        foreach ($fileRefs as $fileRef) {
            $responses[] = $this->callOpenAiTranscription($fileRef, $language);
        }

        return $this->stitch($responses);
    }

    protected function callOpenAiTranscription(string $fileRef, ?string $language = null): array
    {
        $path = storage_path('app/'.$fileRef);

        if (! is_file($path)) {
            throw new \InvalidArgumentException("Transcription source {$fileRef} not found.");
        }

        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new \RuntimeException("Unable to open transcription source {$fileRef}.");
        }

        $payload = [
            'model' => config('services.openai.transcription_model'),
            'file' => $handle,
            'response_format' => 'verbose_json',
        ];

        if ($language) {
            $payload['language'] = $language;
        }

        try {
            $response = $this->client->audio()->transcriptions()->create($payload);
        } catch (ErrorException $exception) {
            if (is_resource($handle)) {
                fclose($handle);
            }

            if (in_array($exception->getCode(), [413, 422], true)) {
                throw ValidationException::withMessages([
                    'file_refs' => ['Upload chunk exceeds the 15 MB limit enforced by OpenAI. Bitte kürzere Abschnitte aufnehmen oder das Audio splitten.'],
                ]);
            }

            Log::warning('OpenAI transcription error', [
                'file_ref' => $fileRef,
                'code' => $exception->getCode(),
                'message' => $exception->getMessage(),
            ]);

            throw new \RuntimeException('OpenAI transcription failed: '.$exception->getMessage(), $exception->getCode(), $exception);
        } catch (TransporterException $exception) {
            if (is_resource($handle)) {
                fclose($handle);
            }

            Log::warning('OpenAI transcription transport error', [
                'file_ref' => $fileRef,
                'message' => $exception->getMessage(),
            ]);

            throw new \RuntimeException('OpenAI transcription request failed: '.$exception->getMessage(), $exception->getCode(), $exception);
        }

        if (is_resource($handle)) {
            fclose($handle);
        }

        $data = $response->toArray();

        $segments = [];

        foreach ($data['segments'] ?? [] as $segment) {
            $segments[] = [
                'start' => (float) ($segment['start'] ?? 0),
                'end' => (float) ($segment['end'] ?? 0),
                'text' => $segment['text'] ?? '',
            ];
        }

        return [
            'text' => $data['text'] ?? '',
            'segments' => $segments,
        ];
    }

    protected function stitch(array $responses): array
    {
        $text = '';
        $segments = [];
        $offset = 0.0;

        foreach ($responses as $response) {
            $chunkText = trim($response['text'] ?? '');
            if ($chunkText === '') {
                continue;
            }

            if ($text !== '') {
                $text .= "\n\n";
            }
            $text .= $chunkText;

            if (! empty($response['segments'])) {
                $chunkSegments = [];
                foreach ($response['segments'] as $segment) {
                    $chunkSegments[] = [
                        'start' => $offset + ($segment['start'] ?? 0),
                        'end' => $offset + ($segment['end'] ?? 0),
                        'text' => $segment['text'] ?? '',
                    ];
                }

                $stitched = $this->stitching->stitchSegments($chunkSegments);
                foreach ($stitched as $segment) {
                    $segments[] = $segment;
                }

                $offset = $segments[array_key_last($segments)]['end'] ?? $offset;
            }
        }

        return [
            'text' => $text,
            'segments' => $segments,
        ];
    }
}
