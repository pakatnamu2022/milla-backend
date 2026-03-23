<?php

namespace App\Http\Services\common;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailService
{
  public function send(array $config): bool
  {
    try {
      $mailable = new GenericMail(
        $config['data'] ?? [],
        $config['template'] ?? 'emails.default',
        $config['subject'] ?? 'Correo electrónico',
        $config['attachments'] ?? []
      );

      $ticsMail = config('mail.tics') ?? [];

      $mailsTo = [
        ...(array)($config['to'] ?? []),
        ...(array)$ticsMail
      ];

      $config['to'] = array_unique($mailsTo);

      $mailInstance = Mail::to($config['to']);

      if (isset($config['cc'])) {
        $mailInstance = $mailInstance->cc($config['cc']);
      }

      if (isset($config['bcc'])) {
        $mailInstance = $mailInstance->bcc($config['bcc']);
      }

      $mailInstance->send($mailable);

      return true;
    } catch (\Exception $e) {
      Log::error($e->getMessage());
      return false;
    }
  }

  public function queue(array $config): bool
  {
    try {
      $mailable = new GenericMail(
        $config['data'] ?? [],
        $config['template'] ?? 'emails.default',
        $config['subject'] ?? 'Correo electrónico',
        $config['attachments'] ?? []
      );

      $ticsMail = config('mail.tics') ?? [];

      $mailsTo = [
        ...(array)($config['to'] ?? []),
        ...(array)$ticsMail
      ];

      $config['to'] = array_unique($mailsTo);

      $mailInstance = Mail::to($config['to']);

      if (isset($config['cc'])) {
        $mailInstance = $mailInstance->cc($config['cc']);
      }

      if (isset($config['bcc'])) {
        $mailInstance = $mailInstance->bcc($config['bcc']);
      }

      $mailInstance->queue($mailable->onQueue('mail'));

      return true;
    } catch (\Exception $e) {
      Log::error($e->getMessage());
      return false;
    }
  }
}
