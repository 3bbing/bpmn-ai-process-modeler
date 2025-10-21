<?php

namespace App\Services;

use App\Models\Process;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SearchService
{
    public function search(?string $query, ?string $domain, ?string $level, ?string $status): LengthAwarePaginator
    {
        $builder = Process::query()->with(['domain', 'owner'])
            ->when($query, fn ($q) => $q->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('summary', 'like', "%{$query}%")
                    ->orWhereJsonContains('meta->labels', $query);
            }))
            ->when($domain, fn ($q) => $q->whereHas('domain', fn ($q) => $q->where('name', $domain)))
            ->when($level, fn ($q) => $q->where('level', $level))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->where('status', 'published');

        return $builder->paginate(15);
    }
}
