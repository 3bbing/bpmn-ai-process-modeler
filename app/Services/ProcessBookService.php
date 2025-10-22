<?php

namespace App\Services;

use App\Models\Process;
use App\Models\ProcessVersion;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use OpenAI\Contracts\ClientContract;
use OpenAI\Exceptions\ErrorException;
use OpenAI\Exceptions\TransporterException;

class ProcessBookService
{
    public function __construct(private ClientContract $client)
    {
    }

    public function generateDescriptions(Process $process, ProcessVersion $version): array
    {
        $temperature = $this->preferredTemperature();
        $maxTokens = (int) config('services.openai.chat_max_tokens_descriptions', 1200);

        try {
            $response = $this->client->chat()->create([
                'model' => config('services.openai.llm_model'),
                'messages' => [
                    ['role' => 'system', 'content' => 'You create concise and detailed process descriptions.'],
                    ['role' => 'user', 'content' => sprintf(
                        "Erstelle kurze und lange Beschreibungen für Prozess %s (%s). BPMN XML: %s",
                        $process->title,
                        $process->level,
                        Str::limit($version->bpmn_xml, 10000)
                    )],
                ],
                'temperature' => $temperature,
                'max_completion_tokens' => $maxTokens,
            ]);
        } catch (ErrorException|TransporterException $exception) {
            throw new \RuntimeException('Description generation failed: '.$exception->getMessage(), $exception->getCode(), $exception);
        }

        $payload = $response->toArray();
        $text = data_get($payload, 'choices.0.message.content');
        $parts = explode('\n---\n', $text ?? '');

        return [
            'summary' => trim($parts[0] ?? ''),
            'details' => trim($parts[1] ?? ''),
        ];
    }

    public function export(Process $process, ProcessVersion $version, string $format): string
    {
        return match ($format) {
            'bpmn' => $this->exportBpmn($process, $version),
            'png', 'svg' => $this->exportDiagram($process, $version, $format),
            'pdf' => $this->exportPdf($process, $version),
            'zip' => $this->exportZip($process, $version),
            default => throw new \InvalidArgumentException('Unsupported format'),
        };
    }

    protected function exportBpmn(Process $process, ProcessVersion $version): string
    {
        $path = 'exports/'.$process->id.'/v'.$version->version.'.bpmn';
        $this->storage()->put($path, $version->bpmn_xml);

        return $path;
    }

    protected function exportDiagram(Process $process, ProcessVersion $version, string $format): string
    {
        $path = 'exports/'.$process->id.'/v'.$version->version.'.'.$format;
        $content = json_encode([
            'bpmn_xml' => $version->bpmn_xml,
            'format' => $format,
        ]);
        $this->storage()->put($path, $content);

        return $path;
    }

    protected function exportPdf(Process $process, ProcessVersion $version): string
    {
        $path = 'exports/'.$process->id.'/v'.$version->version.'.pdf';
        $content = "# Prozessbuch {$process->title}\n\n".$version->sop_md;
        $this->storage()->put($path, $content);

        return $path;
    }

    protected function exportZip(Process $process, ProcessVersion $version): string
    {
        $path = 'exports/'.$process->id.'/v'.$version->version.'.zip';
        $manifest = [
            'bpmn_xml' => $version->bpmn_xml,
            'sop_md' => $version->sop_md,
            'meta' => $version->meta,
        ];
        $this->storage()->put($path, json_encode($manifest));

        return $path;
    }

    protected function storage(): Filesystem
    {
        return Storage::disk(config('filesystems.default', 'local'));
    }

    protected function preferredTemperature(): float
    {
        $model = config('services.openai.llm_model');
        $temperature = (float) config('services.openai.chat_temperature_descriptions', 0.4);

        if (str_starts_with($model, 'gpt-5')) {
            return 1.0;
        }

        return $temperature;
    }
}
