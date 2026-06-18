<?php

namespace App\Http\Controllers;

use App\Http\Resources\gp\gestionsistema\UserResource;
use App\Http\Services\common\AuthService;
use App\Http\Services\common\TotpService;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class TotpController extends Controller
{
  public function __construct(
    private TotpService $totp,
    private AuthService $auth,
  ) {}

  public function setup()
  {
    $user = Auth::user();
    $secret = $this->totp->generateSecret();
    $qrUrl = $this->totp->getQrUrl($user, $secret);

    return response()->json([
      'secret' => $secret,
      'qr_url' => $qrUrl,
    ]);
  }

  public function enable(Request $request)
  {
    $request->validate([
      'secret' => 'required|string',
      'code'   => 'required|string|size:6',
    ]);

    try {
      $this->totp->enable(Auth::user(), $request->secret, $request->code);
      return response()->json(['message' => 'Autenticación de dos factores activada correctamente.']);
    } catch (Exception $e) {
      return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 422);
    }
  }

  public function disable(Request $request)
  {
    $request->validate([
      'code' => 'required|string|size:6',
    ]);

    try {
      $this->totp->disable(Auth::user(), $request->code);
      return response()->json(['message' => 'Autenticación de dos factores desactivada.']);
    } catch (Exception $e) {
      return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 422);
    }
  }

  public function verify(Request $request)
  {
    $request->validate([
      'pending_token' => 'required|string',
      'code'          => 'required|string|size:6',
    ]);

    $cacheKey = "2fa_pending:{$request->pending_token}";
    $userId = Cache::get($cacheKey);

    if (!$userId) {
      return response()->json(['message' => 'Sesión expirada. Inicia sesión nuevamente.'], 422);
    }

    $user = User::with('person')->find($userId);

    if (!$user || !$user->totp_secret) {
      Cache::forget($cacheKey);
      return response()->json(['message' => 'Usuario no encontrado.'], 404);
    }

    if (!$this->totp->verify($user->totp_secret, $request->code)) {
      return response()->json(['message' => 'Código inválido. Verifica tu Authenticator.'], 422);
    }

    Cache::forget($cacheKey);

    $token = $user->createToken('AuthToken', expiresAt: now()->addDays(7));
    $permissionsData = $this->auth->permissions($user->id);

    return response()->json([
      'access_token' => $token->plainTextToken,
      'user'         => UserResource::make($user),
      'permissions'  => $permissionsData['permissions'],
    ]);
  }
}
