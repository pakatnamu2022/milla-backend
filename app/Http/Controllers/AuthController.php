<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Services\common\AuthService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
  protected AuthService $service;

  public function __construct(AuthService $service)
  {
    $this->service = $service;
  }

  public function login(LoginRequest $request)
  {
    return $this->service->login($request);
  }

  public function logout()
  {
    return $this->service->logout();
  }

  public function authenticate()
  {
    return $this->service->authenticate();
  }

  public function permissions()
  {
    return $this->service->permissions();
  }

  public function modules(Request $request)
  {
    try {
      return $this->service->modules($request);
    } catch (\Throwable $th) {
      return $this->error($th->getMessage());
    }
  }

  public function changePassword(ChangePasswordRequest $request)
  {
    return $this->service->changePassword($request);
  }

  public function resetPassword(ResetPasswordRequest $request)
  {
    return $this->service->resetPassword($request);
  }

}
