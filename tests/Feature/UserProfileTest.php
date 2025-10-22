<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class UserProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    public function test_user_can_view_profile(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/me');

        $response->assertOk()
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_user_can_update_profile(): void
    {
        $user = User::factory()->create([
            'email' => 'old@example.com',
        ]);

        $payload = [
            'name' => 'New Name',
            'email' => 'new@example.com',
        ];

        $response = $this->actingAs($user)->patchJson('/api/me', $payload);

        $response->assertOk()
            ->assertJsonPath('data.name', 'New Name')
            ->assertJsonPath('data.email', 'new@example.com');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name',
            'email' => 'new@example.com',
        ]);
    }

    public function test_user_can_update_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('secret123'),
        ]);

        $payload = [
            'current_password' => 'secret123',
            'password' => 'newSecret123!',
            'password_confirmation' => 'newSecret123!',
        ];

        $response = $this->actingAs($user)->patchJson('/api/me/password', $payload);

        $response->assertOk()
            ->assertJson(['message' => __('Password updated successfully.')]);

        $this->assertTrue(Hash::check('newSecret123!', $user->fresh()->password));
    }

    public function test_password_update_requires_current_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->actingAs($user)->patchJson('/api/me/password', [
            'current_password' => 'wrong',
            'password' => 'newSecret123!',
            'password_confirmation' => 'newSecret123!',
        ]);

        $response->assertStatus(422);
    }
}
