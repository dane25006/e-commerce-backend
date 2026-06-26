<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthApiController extends Controller
{
    // ── POST /api/register ───────────────────────────────────────────────
    public function register(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        try {
            $user = User::create([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'password' => Hash::make($data['password']),
                'role'     => 'customer',
            ]);

            $token = $user->createToken('api-token')->plainTextToken;
            Auth::login($user);
            $request->session()->regenerate();

            return response()->json([
                'message' => 'Account created successfully.',
                'user'    => $this->formatUser($user),
                'token'   => $token,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Registration failed. Please try again.',
            ], 500);
        }
    }

    // ── POST /api/login ──────────────────────────────────────────────────
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt([
            'email' => $request->email,
            'password' => $request->password,
        ])) {
            return response()->json([
                'message' => 'Invalid email or password.',
            ], 401);
        }

        $user = Auth::user();

        if ($user->isAdmin()) {
            Auth::logout();

            return response()->json([
                'message' => 'Admin accounts must use the admin panel.',
            ], 403);
        }

        $token = $user->createToken('api-token')->plainTextToken;
        $request->session()->regenerate();

        return response()->json([
            'message' => 'Logged in successfully.',
            'user' => $this->formatUser($user),
            'token' => $token,
        ]);
    }

    // ── POST /api/logout  [auth:sanctum] ─────────────────────────────────
    public function logout(Request $request)
    {
        // $request->user()->currentAccessToken()->delete();
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Logged out successfully.',
        ])->cookie(
            'token',
            '',
            -1   // expire immediately
        );
    }

    // ── GET /api/profile  [auth:sanctum] ─────────────────────────────────
    public function profile(Request $request)
    {
        return response()->json([
            'user' => $this->formatUser($request->user()),
        ]);
    }

    // ── PUT /api/profile  [auth:sanctum] ─────────────────────────────────
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,' . $user->id],
        ]);

        $user->update($data);

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user'    => $this->formatUser($user->fresh()),
        ]);
    }

    // ── PUT /api/password  [auth:sanctum] ────────────────────────────────
    public function changePassword(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'confirmed', Password::min(8)],
        ]);

        if (! Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors'  => [
                    'current_password' => ['The current password is incorrect.'],
                ],
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        // Revoke ALL tokens — user must log in again
        $user->tokens()->delete();

        return response()->json([
            'message' => 'Password changed successfully. Please log in again.',
        ])->cookie('token', '', -1);
    }

    // ── Private helper ────────────────────────────────────────────────────
    private function formatUser(User $user): array
    {
        return [
            'id'         => $user->id,
            'name'       => $user->name,
            'email'      => $user->email,
            'role'       => $user->role,
            'created_at' => $user->created_at->toDateTimeString(),
        ];
    }
}
