<?php

namespace App\Policies;

use App\Models\Process;
use App\Models\User;

class ProcessPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['reader', 'author', 'reviewer', 'owner', 'admin']);
    }

    public function view(User $user, Process $process): bool
    {
        if ($process->status === 'published') {
            return true;
        }

        return $user->hasAnyRole(['author', 'reviewer', 'owner', 'admin'])
            || $user->id === $process->owner_user_id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['author', 'admin']);
    }

    public function update(User $user, Process $process): bool
    {
        if ($process->status === 'published') {
            return $user->hasRole('admin');
        }

        return $user->hasAnyRole(['author', 'admin']) || $user->id === $process->owner_user_id;
    }

    public function delete(User $user, Process $process): bool
    {
        return $user->hasRole('admin');
    }

    public function publish(User $user, Process $process): bool
    {
        return $user->hasAnyRole(['owner', 'admin']);
    }

    public function review(User $user, Process $process): bool
    {
        return $user->hasAnyRole(['reviewer', 'admin']);
    }
}
