<?php

namespace App\Http\Controllers;

use App\Http\Requests\Profile\UpdateAvatarRequest;
use App\Http\Requests\Profile\UpdatePasswordRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return $this->ok(UserResource::make($request->user()), 'Profile retrieved');
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $request->user()->update($request->validated());

        return $this->ok(UserResource::make($request->user()->fresh()), 'Profile updated');
    }

    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $request->user()->update(['password' => $request->password]);

        return $this->ok(null, 'Password updated');
    }

    public function updateAvatar(UpdateAvatarRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $path = $request->file('avatar')->store('avatars', 'public');

        $user->update(['avatar' => $path]);

        return $this->ok(UserResource::make($user->fresh()), 'Avatar updated');
    }

    public function destroyAvatar(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->avatar) {
            return $this->fail('No avatar to remove', 404);
        }

        Storage::disk('public')->delete($user->avatar);
        $user->update(['avatar' => null]);

        return $this->ok(UserResource::make($user->fresh()), 'Avatar removed');
    }

    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->currentAccessToken()->delete();
        $user->delete();

        return $this->ok(null, 'Account deleted');
    }
}
