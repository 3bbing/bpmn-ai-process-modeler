<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProcessVersionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'version' => $this->version,
            'is_published' => $this->is_published,
            'status' => $this->status,
            'meta' => $this->meta,
            'created_at' => $this->created_at,
        ];
    }
}
