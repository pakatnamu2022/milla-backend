<?php

namespace App\Http\Controllers\ap\configuracionComercial\venta;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;

class ApPeriodSnapshotController extends Controller
{
  public function store()
  {
    $commands = [
      'app:snapshot-assign-company-branch-periods',
      'app:snapshot-assign-brand-consultant',
      'app:snapshot-assignment-leadership-periods',
      'app:snapshot-commercial-manager-brand-group-periods',
    ];

    $results = [];
    $hasError = false;

    foreach ($commands as $command) {
      $exitCode = Artisan::call($command);
      $output = trim(Artisan::output());

      if ($exitCode !== 0) {
        $hasError = true;
      }

      $results[] = [
        'command' => $command,
        'success' => $exitCode === 0,
        'message' => $output,
      ];
    }

    $status = $hasError ? 207 : 200;

    return response()->json([
      'message' => $hasError
        ? 'Algunos snapshots no pudieron completarse (puede que ya existan para este período).'
        : 'Período iniciado correctamente. Todos los snapshots fueron copiados.',
      'results' => $results,
    ], $status);
  }
}
