<?php

namespace App\Http\Controllers;

use App\Http\Requests\PasswordUpdateRequest;
use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserProfileController extends Controller
{
    public function show(Request $request): UserResource
    {
        return new UserResource($request->user()->load('roles'));
    }

    public function update(ProfileUpdateRequest $request): UserResource
    {
        $user = $request->user();

        $user->forceFill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return new UserResource($user->load('roles'));
    }

    public function updatePassword(PasswordUpdateRequest $request): JsonResponse
    {
        $user = $request->user();

        $user->forceFill([
            'password' => Hash::make($request->validated()['password']),
        ])->save();

        return response()->json([
            'message' => __('Password updated successfully.'),
        ]);
    }
}
