<?php

namespace App\Services\Auth;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthService
{
    public function register(array $data): array
    {
        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'role'     => 'customer',
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        return [
            'user'  => new UserResource($user),
            'token' => $token,
        ];
    }

    public function login(string $email, string $password): ?array
    {
        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            return null;
        }

        return [
            'user'  => new UserResource($user),
            'token' => $user->createToken('api-token')->plainTextToken,
        ];
    }

    public function findOrCreateSocialUser(array $googleUser): array
    {
        $user = User::where('email', $googleUser['email'])->first();

        if ($user) {
            $user->update([
                'google_id'         => $googleUser['id'],
                'avatar'            => $googleUser['avatar'],
                'email_verified_at' => $user->email_verified_at ?? now(),
            ]);
        } else {
            $user = User::create([
                'name'              => $googleUser['name'] ?? $googleUser['nickname'] ?? 'Google User',
                'email'             => $googleUser['email'],
                'google_id'         => $googleUser['id'],
                'avatar'            => $googleUser['avatar'],
                'password'          => Hash::make(Str::random(32)),
                'role'              => 'customer',
                'email_verified_at' => now(),
            ]);
        }

        return [
            'user'  => $user,
            'token' => $user->createToken('api-token')->plainTextToken,
        ];
    }
}
