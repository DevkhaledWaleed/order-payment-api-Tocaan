<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use function Symfony\Component\String\s;

class AuthController extends Controller
{
    /**
     * Register a new user and return a JWT token.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create($request->validated());

        $token = Auth::guard('api')->login($user);

        return $this->respondWithToken($token, $user, 201);
    }

    /**
     * Authenticate and return a JWT token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $token = Auth::guard('api')->attempt($request->validated());

        if (! $token) {
            return response()->json([
                'message' => 'Invalid credentials. Please check your email and password.',
            ], 401);
        }

        return $this->respondWithToken($token, Auth::guard('api')->user());
    }

    /**
     * Return the authenticated user's profile.
     */
    public function me(): JsonResponse
    {
        return response()->json([
            'data' => UserResource::make(Auth::guard('api')->user()),
        ]);
    }

    /**
     * Invalidate the current JWT token (logout).
     */
    public function logout(): JsonResponse
    {
        Auth::guard('api')->logout();

        return response()->json([
            'message' => 'Successfully logged out.',
        ]);
    }

    /**
     * Refresh the current JWT token.
     */
    public function refresh(): JsonResponse
    {
        $token = Auth::guard('api')->refresh();

        return $this->respondWithToken($token, Auth::guard('api')->user());
    }

    // Private

    private function respondWithToken(string $token, User $user, int $status = 200): JsonResponse
    {
        return response()->json([
            'data' => [
                'user' => new UserResource($user),
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => Auth::guard('api')->factory()->getTTL() * 60,
            ],
        ], $status);
    }
}
