<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Services\AuthService;

class AuthController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function login(LoginRequest $request)
    {
        return $this->authService->login($request);
    }

    public function authenticate()
    {
        return $this->authService->authenticate();
    }

    public function permissions()
    {
        return $this->authService->permissions();
    }

}
