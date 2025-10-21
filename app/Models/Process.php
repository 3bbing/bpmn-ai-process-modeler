<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Process extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $fillable = [
        'domain_id',
        'code',
        'title',
        'level',
        'owner_user_id',
        'status',
        'summary',
        'guidance',
        'meta',
    ];

    protected $casts = [
        'guidance' => 'array',
        'meta' => 'array',
    ];

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ProcessVersion::class);
    }

    public function mediaAssets(): HasMany
    {
        return $this->hasMany(MediaAsset::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('process')
            ->setDescriptionForEvent(fn (string $eventName) => "Process {$eventName}");
    }
}
