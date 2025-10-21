<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaAsset extends Model
{
    use HasFactory;

    protected $fillable = [
        'process_id',
        'type',
        'path',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function process(): BelongsTo
    {
        return $this->belongsTo(Process::class);
    }
}
