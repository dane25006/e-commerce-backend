<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Responses\ApiResponse;
use App\Http\Resources\UserResource;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthApiController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected AuthService $authService
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return $this->created([
            'user'  => $result['user'],
            'token' => $result['token'],
        ], 'Welcome! Your account is ready.');
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login(
            $request->input('email'),
            $request->input('password')
        );

        if (! $result) {
            return $this->error(
                "That email or password doesn't seem right. Please try again.",
                401
            );
        }

        /** @var \App\Models\User $user */
        $user = $result['user']->resource;

        if ($user->isAdmin()) {
            return $this->forbidden('Admin accounts need to use the admin panel to sign in.');
        }

        return $this->success([
            'user'  => $result['user'],
            'token' => $result['token'],
        ], "Welcome back! You're signed in.");
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user('sanctum')->currentAccessToken()->delete();

        return $this->success(null, "You've been signed out. Come back soon!");
    }

    public function profile(Request $request): JsonResponse
    {
        return $this->success([
            'user' => new UserResource($request->user('sanctum')),
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user('sanctum');

        $data = $request->validate([
            'name'   => ['required', 'string', 'max:255'],
            'email'  => ['required', 'email', 'unique:users,email,' . $user->id],
            'avatar' => ['nullable', 'string', 'max:2048'],
        ]);

        $user->update($data);

        return $this->success([
            'user' => new UserResource($user->fresh()),
        ], 'Your profile has been updated.');
    }

    public function changePassword(Request $request): JsonResponse
    {
        $user = $request->user('sanctum');

        $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::min(8)],
        ]);

        if (! Hash::check($request->current_password, $user->password)) {
            return $this->unprocessable(
                'Please check the information you entered.',
                ['current_password' => ['The current password you entered is incorrect.']]
            );
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        $user->tokens()->delete();

        return $this->success(null, 'Your password has been changed. Please sign in again to continue.');
    }
}
