<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $credentials = $request->only('username', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $token = $user->createToken('AuthToken', expiresAt: now()->addDays(7));

            $user = User::with('person')->find($user->id);

            return response()->json([
                'access_token' => $token->plainTextToken,
//                'user' => UserResource::make($user),
                'user' => $user,
            ]);
        } else {
            return response()->json(['message' => 'Credenciales Inválidades'], 422);
        }
    }
}
