<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcessVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'process_id',
        'version',
        'bpmn_xml',
        'sop_md',
        'meta',
        'created_by',
        'is_published',
        'status',
    ];

    protected $casts = [
        'meta' => 'array',
        'is_published' => 'boolean',
    ];

    public function process(): BelongsTo
    {
        return $this->belongsTo(Process::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'process_version_id');
    }
}
