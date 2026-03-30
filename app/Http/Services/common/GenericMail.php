<?php

namespace App\Http\Services\common;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GenericMail extends Mailable
{
  use Queueable, SerializesModels;

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
      } elseif (is_array($attachment) && isset($attachment['url'])) {
        $content = @file_get_contents($attachment['url']);
        if ($content !== false) {
          $name = $attachment['name'] ?? basename(parse_url($attachment['url'], PHP_URL_PATH));
          $attachmentObj = Attachment::fromData(fn() => $content, $name);
          if (isset($attachment['mime'])) {
            $attachmentObj = $attachmentObj->withMime($attachment['mime']);
          }
          $attachments[] = $attachmentObj;
        }
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
