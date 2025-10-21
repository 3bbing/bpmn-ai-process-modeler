<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        collect(['reader', 'author', 'reviewer', 'owner', 'admin'])->each(function ($role) {
            Role::findOrCreate($role, 'web');
        });
    }
}
