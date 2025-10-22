<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        collect(['reader', 'author', 'reviewer', 'owner', 'admin'])->each(
            fn ($role) => Role::findOrCreate($role, 'web')
        );

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    public function test_admin_can_list_users(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->admin)->getJson('/api/users');

        $response->assertOk()
            ->assertJsonFragment(['email' => $user->email])
            ->assertJsonPath('meta.roles', ['admin', 'author', 'owner', 'reader', 'reviewer']);
    }

    public function test_admin_can_create_user_with_roles(): void
    {
        $payload = [
            'name' => 'Example User',
            'email' => 'example@example.com',
            'password' => 'Secret123!',
            'password_confirmation' => 'Secret123!',
            'roles' => ['author', 'reviewer'],
        ];

        $response = $this->actingAs($this->admin)->postJson('/api/users', $payload);

        $response->assertCreated();

        $this->assertDatabaseHas('users', [
            'email' => 'example@example.com',
        ]);

        $user = User::whereEmail('example@example.com')->first();
        $this->assertTrue($user->hasRole('author'));
        $this->assertTrue($user->hasRole('reviewer'));
    }

    public function test_admin_can_update_user(): void
    {
        $user = User::factory()->create();
        $user->assignRole('reader');

        $payload = [
            'name' => 'Updated Name',
            'roles' => ['owner'],
        ];

        $response = $this->actingAs($this->admin)->patchJson("/api/users/{$user->id}", $payload);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Name');

        $this->assertTrue($user->fresh()->hasRole('owner'));
    }

    public function test_admin_can_delete_other_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->admin)->deleteJson("/api/users/{$user->id}");

        $response->assertOk()
            ->assertJson(['message' => __('User deleted.')]);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_admin_cannot_delete_self(): void
    {
        $response = $this->actingAs($this->admin)->deleteJson("/api/users/{$this->admin->id}");

        $response->assertStatus(422);
    }
}
