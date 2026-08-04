<?php

namespace App\Console\Commands;

use App\Http\Services\common\EmailService;
use App\Models\ap\ApMasters;
use App\Models\ap\comercial\ApVehicleDelivery;
use Carbon\Carbon;
use Illuminate\Console\Command;

class TestVehicleWelcomeEmailCommand extends Command
{
  protected $signature = 'test:vehicle-welcome-email
                          {to : Destinatario del correo de prueba}
                          {--id= : ID de la entrega real (si se omite usa la última con email)}';

  protected $description = 'Envía el email de bienvenida de vehículo con datos reales a Mailpit';

  public function handle(EmailService $emailService): void
  {
    $to = $this->argument('to');
    $deliveryId = $this->option('id');

    $query = ApVehicleDelivery::with(['vehicle.model.family.brand', 'vehicle.color', 'client', 'advisor', 'sede'])
      ->whereHas('client', fn($q) => $q->whereNotNull('email'));

    $delivery = $deliveryId
      ? $query->findOrFail($deliveryId)
      : $query->whereNotNull('real_delivery_date')->latest()->first();

    if (!$delivery) {
      $this->error('No se encontró ninguna entrega con cliente que tenga email.');
      return;
    }

    $this->info("Entrega ID: {$delivery->id}");
    $this->line("Cliente:    " . $delivery->client?->full_name . " <" . $delivery->client?->email . ">");
    $this->line("VIN:        " . $delivery->vehicle?->vin);
    $this->line("Modelo:     " . $delivery->vehicle?->model?->version . " " . $delivery->vehicle?->model?->model_year);
    $this->line("Destino:    {$to}");
    $this->newLine();

    $welcomeConfigs = ApMasters::ofType(ApMasters::TYPE_VEHICLE_WELCOME)->get()->keyBy('code');
    $letterUrl = $welcomeConfigs->get(ApMasters::VEHICLE_WELCOME_LETTER_CODE)?->description;
    $videoUrl  = $welcomeConfigs->get(ApMasters::VEHICLE_WELCOME_VIDEO_CODE)?->description;

    $attachments = [];
    if ($letterUrl) {
      $attachments[] = [
        'url'  => $letterUrl,
        'name' => 'Carta de Bienvenida.pdf',
        'mime' => 'application/pdf',
      ];
      $this->line("📎 Adjunto carta: {$letterUrl}");
    }

    preg_match('/(?:v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $videoUrl ?? '', $ytMatches);
    $videoThumbnail = isset($ytMatches[1])
      ? "https://img.youtube.com/vi/{$ytMatches[1]}/maxresdefault.jpg"
      : null;

    if ($videoUrl) {
      $this->line("🎬 Video YouTube: {$videoUrl}");
      $this->line("   Thumbnail:     " . ($videoThumbnail ?? 'no detectado'));
    }

    $modelVersion = $delivery->vehicle?->model?->version ?? '';
    $modelYear    = $delivery->vehicle?->model?->model_year ?? '';
    $brandName    = $delivery->vehicle?->model?->family?->brand?->name ?? '';

    $result = $emailService->send([
      'to'          => $to,
      'subject'     => '¡Bienvenido a la familia! Tu ' . trim("{$modelVersion} {$modelYear}") . ' te espera',
      'template'    => 'emails.vehicle-welcome',
      'attachments' => $attachments,
      'data'        => [
        'client_name'     => $delivery->client?->full_name ?? 'Cliente',
        'brand_name'      => $brandName,
        'model_version'   => $modelVersion,
        'model_year'      => $modelYear,
        'vehicle_vin'     => $delivery->vehicle?->vin ?? '',
        'color_name'      => $delivery->vehicle?->color?->description ?? '',
        'advisor_name'    => $delivery->advisor?->nombre_completo ?? '',
        'sede_name'       => $delivery->sede?->abreviatura ?? '',
        'delivery_date'   => Carbon::parse($delivery->real_delivery_date ?? now())->format('d \d\e F \d\e Y'),
        'has_letter'      => !empty($letterUrl),
        'video_url'       => $videoUrl,
        'video_thumbnail' => $videoThumbnail,
      ],
    ]);

    $this->newLine();
    if ($result) {
      $this->info('✅ Email enviado correctamente.');
      $this->line('Revísalo en Mailpit: http://localhost:8025');
    } else {
      $this->error('❌ EmailService retornó false. Revisa storage/logs/laravel.log');
    }
  }
}
