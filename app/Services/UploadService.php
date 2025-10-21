<?php

namespace App\Services;

use App\Models\MediaAsset;
use App\Models\Upload;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadService
{
    public function initiate(int $userId, array $meta = []): Upload
    {
        $upload = Upload::create([
            'user_id' => $userId,
            'status' => 'pending',
            'disk' => config('filesystems.default', 'local'),
            'path_prefix' => 'uploads/'.Str::uuid(),
            'meta' => $meta,
        ]);

        Storage::disk($upload->disk)->makeDirectory($upload->path_prefix);

        return $upload;
    }

    public function storeChunk(Upload $upload, string $filename, string $contents, int $index, string $checksum): array
    {
        $disk = $this->disk($upload);
        $chunkPath = $this->chunkPath($upload, $index.'-'.$filename);

        $serverChecksum = hash('crc32b', $contents);
        if (strcasecmp($serverChecksum, $checksum) !== 0) {
            throw new \RuntimeException('Checksum mismatch for chunk '.$index);
        }

        $disk->put($chunkPath, $contents);

        return [
            'idx' => $index,
            'checksum' => $serverChecksum,
            'path' => $chunkPath,
        ];
    }

    public function finalize(Upload $upload, bool $concatOgg = false): array
    {
        $disk = $this->disk($upload);
        $files = collect($disk->files($upload->path_prefix))->sort()->values();

        $upload->update(['status' => 'completed']);

        if ($concatOgg && $this->shouldConcatOgg($files)) {
            $concatPath = $upload->path_prefix.'/concat.ogg';
            $stream = '';
            foreach ($files as $path) {
                $stream .= $disk->get($path);
            }
            $disk->put($concatPath, $stream);

            return [
                'file_refs' => [$concatPath],
                'file_ref_concat' => $concatPath,
            ];
        }

        return [
            'file_refs' => $files->all(),
        ];
    }

    public function persistAsset(int $processId, string $type, string $path, array $meta = []): MediaAsset
    {
        return MediaAsset::create([
            'process_id' => $processId,
            'type' => $type,
            'path' => $path,
            'meta' => $meta,
        ]);
    }

    protected function shouldConcatOgg($files): bool
    {
        return $files->every(fn ($path) => str_ends_with($path, '.ogg'));
    }

    protected function chunkPath(Upload $upload, string $filename): string
    {
        return $upload->path_prefix.'/'.$filename;
    }

    protected function disk(Upload $upload): Filesystem
    {
        return Storage::disk($upload->disk);
    }
}
