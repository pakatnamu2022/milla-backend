<?php

namespace App\Http\Services;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AuthService
{
    public function login($request)
    {
        $credentials = $request->only('username', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $token = $user->createToken('AuthToken', expiresAt: now()->addDays(7));

            $user = User::with('person')->find($user->id);

            return response()->json([
                'access_token' => $token->plainTextToken,
                'user' => UserResource::make($user),
            ]);
        } else {
            return response()->json(['message' => 'Credenciales Inválidades'], 422);
        }
    }

    public function authenticate()
    {
        $user = Auth::user();

        if ($user) {
            $user = User::with('person')->find($user->id);
            return response()->json([
                'user' => UserResource::make($user),
            ]);
        } else {
            return response()->json(['message' => 'No autenticado'], 401);
        }
    }

    public function logout()
    {
        $user = Auth::user();
        if ($user) {
            $user->tokens()->delete();
            return response()->json(['message' => 'Sesión cerrada correctamente'], 200);
        } else {
            return response()->json(['message' => 'No autenticado'], 401);
        }
    }
}
