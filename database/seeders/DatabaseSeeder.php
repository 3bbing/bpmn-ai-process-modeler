<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        collect(['reader', 'author', 'reviewer', 'owner', 'admin'])->each(
            fn ($role) => Role::findOrCreate($role, 'web')
        );

        $this->seedDefaultAdmin();
    }

    protected function seedDefaultAdmin(): void
    {
        $email = config('app.default_admin.email');
        $password = config('app.default_admin.password');

        if (! $email || ! $password) {
            return;
        }

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Admin',
                'password' => Hash::make($password),
            ]
        );

        $user->assignRole('admin');
    }
}
