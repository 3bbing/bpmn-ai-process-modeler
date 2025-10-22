<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserStoreRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    public function index()
    {
        $users = User::with('roles')->orderBy('name')->get();
        $roles = Role::query()->orderBy('name')->pluck('name')->values();

        return UserResource::collection($users)->additional([
            'meta' => [
                'roles' => $roles,
            ],
        ]);
    }

    public function store(UserStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $roles = $validated['roles'] ?? [];
        unset($validated['roles']);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now(),
        ]);

        if (! empty($roles)) {
            $user->syncRoles($roles);
        }

        return (new UserResource($user->fresh('roles')))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UserUpdateRequest $request, User $user): UserResource
    {
        $validated = $request->validated();
        $roles = $validated['roles'] ?? null;
        unset($validated['roles']);

        if (array_key_exists('password', $validated) && $validated['password']) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->fill($validated)->save();

        if (is_array($roles)) {
            $user->syncRoles($roles);
        }

        return new UserResource($user->fresh('roles'));
    }

    public function destroy(User $user): JsonResponse
    {
        $currentUser = request()->user();

        if ($user->is($currentUser)) {
            return response()->json([
                'message' => __('You cannot delete your own account.'),
            ], 422);
        }

        $user->delete();

        return response()->json([
            'message' => __('User deleted.'),
        ]);
    }
}
