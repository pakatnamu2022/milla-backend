<?php

namespace App\Http\Services\common;

use App\Models\User;
use Exception;
use PragmaRX\Google2FA\Google2FA;

class TotpService
{
  private Google2FA $google2fa;

  public function __construct()
  {
    $this->google2fa = new Google2FA();
  }

  public function generateSecret(): string
  {
    return $this->google2fa->generateSecretKey();
  }

  public function getQrUrl(User $user, string $secret): string
  {
    return $this->google2fa->getQRCodeUrl(
      config('app.name', 'Sian'),
      $user->username,
      $secret
    );
  }

  public function verify(string $secret, string $code): bool
  {
    return $this->google2fa->verifyKey($secret, $code);
  }

  public function enable(User $user, string $secret, string $code): void
  {
    if (!$this->verify($secret, $code)) {
      throw new Exception('Código inválido. Verifica tu Authenticator.', 422);
    }

    $user->update([
      'totp_secret'        => $secret,
      'two_factor_enabled' => true,
    ]);
  }

  public function disable(User $user, string $code): void
  {
    if (!$user->two_factor_enabled || !$user->totp_secret) {
      throw new Exception('El 2FA no está activo en esta cuenta.', 422);
    }

    if (!$this->verify($user->totp_secret, $code)) {
      throw new Exception('Código inválido. Verifica tu Authenticator.', 422);
    }

    $user->update([
      'totp_secret'        => null,
      'two_factor_enabled' => false,
    ]);
  }
}
