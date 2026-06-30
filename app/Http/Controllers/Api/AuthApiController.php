<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use OpenApi\Attributes as OA;
use Illuminate\Support\Facades\Validator;

class AuthApiController extends Controller
{


    #[OA\Post(
        path: '/api/register',
        operationId: 'registerUser',
        tags: ['Authentication'],
        summary: 'Register a new user',
        description: 'Create a new user account and return an API token',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password123'),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'password123'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'User registered successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Register Successfully'),
                        new OA\Property(property: 'token', type: 'string', example: '1|abc123def456...'),
                        new OA\Property(property: 'user', ref: '#/components/schemas/User'),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Validation Error'),
                        new OA\Property(property: 'errors', type: 'object'),
                    ]
                )
            ),
        ]
    )]
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
                'success' => true,
                'message' => 'Welcome! Your account is ready.',
                'user'    => new UserResource($user),
                'token'   => $token,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'We couldn\'t create your account. Please try again.',
            ], 500);
        }
    }

    #[OA\Post(
        path: '/api/login',
        operationId: 'loginUser',
        tags: ['Authentication'],
        summary: 'Login user',
        description: 'Authenticate a user and return an API token',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password123'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login successful',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Login Successfully'),
                        new OA\Property(property: 'token', type: 'string', example: '1|abc123def456...'),
                        new OA\Property(property: 'user', ref: '#/components/schemas/User'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Invalid credentials',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Invalid email or password.'),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error'
            ),
        ]
    )]
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
                'message' => 'That email or password doesn\'t seem right. Please try again.',
            ], 401);
        }

        $user = Auth::user();

        if ($user->isAdmin()) {
            Auth::logout();

            return response()->json([
                'message' => 'Admin accounts need to use the admin panel to sign in.',
            ], 403);
        }

        $token = $user->createToken('api-token')->plainTextToken;
        $request->session()->regenerate();

        return response()->json([
                'success' => true,
                'message' => 'Welcome back! You\'re signed in.',
                'user' => new UserResource($user),
                'token' => $token,
            ]);
    }

    #[OA\Post(
        path: '/api/logout',
        operationId: 'logoutUser',
        tags: ['Authentication'],
        summary: 'Logout a user',
        description: 'Invalidate the session and log out the authenticated user',
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Logged out successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Logged out successfully.'),
                    ]
                )
            ),
        ]
    )]
    // ── POST /api/logout  [auth:sanctum] ─────────────────────────────────
    public function logout(Request $request)
    {
        // $request->user()->currentAccessToken()->delete();
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'You\'ve been signed out. Come back soon!',
        ])->cookie(
            'token',
            '',
            -1   // expire immediately
        );
    }

    #[OA\Get(
        path: '/api/profile',
        operationId: 'getProfile',
        tags: ['Authentication'],
        summary: 'Get authenticated user profile',
        description: 'Return the profile of the currently authenticated user',
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Authenticated user data',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'user', ref: '#/components/schemas/User'),
                    ]
                )
            ),
        ]
    )]
    // ── GET /api/profile  [auth:sanctum] ─────────────────────────────────
    public function profile(Request $request)
    {
        return response()->json([
            'success' => true,
            'user' => new UserResource($request->user()),
        ]);
    }

    #[OA\Put(
        path: '/api/profile',
        operationId: 'updateProfile',
        tags: ['Authentication'],
        summary: 'Update authenticated user profile',
        description: 'Update the name and/or email of the currently authenticated user',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Profile updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Profile updated successfully.'),
                        new OA\Property(property: 'user', ref: '#/components/schemas/User'),
                    ]
                )
            ),
        ]
    )]
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
            'success' => true,
            'message' => 'Your profile has been updated.',
            'user'    => new UserResource($user->fresh()),
        ]);
    }

    #[OA\Put(
        path: '/api/password',
        operationId: 'changePassword',
        tags: ['Authentication'],
        summary: 'Change authenticated user password',
        description: 'Change the password for the currently authenticated user. All existing tokens are revoked.',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['current_password', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'current_password', type: 'string', format: 'password', example: 'oldpassword123'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'newpassword123'),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'newpassword123'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Password changed successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Password changed successfully. Please log in again.'),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error or incorrect current password',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Validation failed.'),
                        new OA\Property(property: 'errors', type: 'object'),
                    ]
                )
            ),
        ]
    )]
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
                'message' => 'Please check the information you entered.',
                'errors'  => [
                    'current_password' => ['The current password you entered is incorrect.'],
                ],
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        // Revoke ALL tokens — user must log in again
        $user->tokens()->delete();

        return response()->json([
            'message' => 'Your password has been changed. Please sign in again to continue.',
        ])->cookie('token', '', -1);
    }


}
