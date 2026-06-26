<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;

class GoogleSocialiteController extends Controller
{
    // ── GET /api/auth/google/redirect ─────────────────────────────────────
    public function redirect()
    {
        return response()->json([
            'url' => Socialite::driver('google')
                ->stateless()
                ->redirect()
                ->getTargetUrl(),
        ]);
    }

    // ── GET /api/auth/google/callback ─────────────────────────────────────
    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Google authentication failed.',
            ], 401);
        }

        $user = User::where('email', $googleUser->email)->first();

        if (! $user) {
            $user = User::create([
                'name'              => $googleUser->name ?? $googleUser->nickname ?? 'Google User',
                'email'             => $googleUser->email,
                'password'          => Hash::make(str()->random(32)),
                'role'              => 'customer',
                'email_verified_at' => now(),
            ]);
        }

        $token = $user->createToken('api-token')->plainTextToken;
        Auth::login($user);

        return redirect(config('app.frontend_url') . '/?auth_token=' . $token);
    }
}
