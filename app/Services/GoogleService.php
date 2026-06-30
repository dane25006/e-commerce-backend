<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleService
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')
            ->stateless()
            ->with(['access_type' => 'online', 'prompt' => 'select_account'])
            ->redirect();
    }

    public function handleCallback(): array
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (\Exception $e) {
            Log::error('Google auth callback failed: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return [
                'success' => false,
                'message' => 'Could not authenticate with Google. Please try again.',
            ];
        }

        if (!$googleUser->email) {
            return [
                'success' => false,
                'message' => 'Google account has no email associated. Please try a different account.',
            ];
        }

        $user = User::where('email', $googleUser->email)->first();

        if ($user) {
            $user->update([
                'google_id' => $googleUser->id,
                'avatar' => $googleUser->avatar,
                'email_verified_at' => $user->email_verified_at ?? now(),
            ]);
        } else {
            $user = User::create([
                'name' => $googleUser->name ?? $googleUser->nickname ?? 'Google User',
                'email' => $googleUser->email,
                'google_id' => $googleUser->id,
                'avatar' => $googleUser->avatar,
                'password' => Hash::make(Str::random(32)),
                'role' => 'customer',
                'email_verified_at' => now(),
            ]);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return [
            'success' => true,
            'user' => $user,
            'token' => $token,
        ];
    }
}
