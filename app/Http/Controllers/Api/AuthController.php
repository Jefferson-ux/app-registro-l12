<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Los datos enviados no son válidos.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $credentials = $validator->validated();
        $user = User::where('email', $credentials['email'])->first();

        if (! $user || $user->status !== 'active' || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciales inválidas.',
            ], 401);
        }

        $token = $user->createToken('kiosk-api')->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'tenant_id' => $user->tenant_id,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada.',
        ]);
    }
}
