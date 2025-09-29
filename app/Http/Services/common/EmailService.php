<?php

namespace App\Http\Services\common;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;

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
      \Log::error('Error sending email: ' . $e->getMessage());
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

      if (isset($config['to'])) {
        Mail::to($config['to'])->queue($mailable);
      }

      return true;
    } catch (\Exception $e) {
      \Log::error('Error queuing email: ' . $e->getMessage());
      return false;
    }
  }
}

class GenericMail extends Mailable
{
  use \Illuminate\Bus\Queueable, \Illuminate\Queue\SerializesModels;

  public function __construct(
    public array  $emailData,
    public string $viewTemplate,
    public string $emailSubject,
    public array  $emailAttachments = []
  )
  {
    //
  }

  public function envelope(): Envelope
  {
    return new Envelope(
      subject: $this->emailSubject,
    );
  }

  public function content(): Content
  {
    return new Content(
      view: $this->viewTemplate,
      with: $this->emailData,
    );
  }

  public function attachments(): array
  {
    $attachments = [];

    foreach ($this->emailAttachments as $attachment) {
      if (is_string($attachment)) {
        $attachments[] = Attachment::fromPath($attachment);
      } elseif (is_array($attachment)) {
        $attachmentObj = Attachment::fromPath($attachment['path']);

        if (isset($attachment['name'])) {
          $attachmentObj->as($attachment['name']);
        }

        if (isset($attachment['mime'])) {
          $attachmentObj->withMime($attachment['mime']);
        }

        $attachments[] = $attachmentObj;
      }
    }

    return $attachments;
  }
}
