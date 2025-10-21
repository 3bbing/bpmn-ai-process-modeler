<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProcessResource extends JsonResource
{
    /** @var \App\Models\Process */
    public $resource;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'domain' => $this->whenLoaded('domain'),
            'code' => $this->code,
            'title' => $this->title,
            'level' => $this->level,
            'owner' => $this->whenLoaded('owner'),
            'status' => $this->status,
            'summary' => $this->summary,
            'guidance' => $this->guidance,
            'meta' => $this->meta,
            'versions' => ProcessVersionResource::collection($this->whenLoaded('versions')),
        ];
    }
}
