<?php

namespace App\Http\Services\gp\gestionhumana\contratos;

use App\Http\Resources\gp\gestionhumana\contratos\SignerResource;
use App\Http\Services\BaseService;
use App\Http\Services\common\ExportService;
use App\Models\gp\gestionhumana\contratos\Signer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SignerService extends BaseService
{
  public function __construct(private ExportService $exportService) {}

  public function list(Request $request): JsonResponse
  {
    return $this->getFilteredResults(
      Signer::query()->with(['worker', 'sede']),
      $request,
      Signer::filters,
      Signer::sorts,
      SignerResource::class,
    );
  }

  public function show(int $id): SignerResource
  {
    return new SignerResource(Signer::with(['worker', 'sede'])->findOrFail($id));
  }

  /** @param array<string, UploadedFile|null> $files */
  public function store(array $data, array $files): SignerResource
  {
    return DB::transaction(function () use ($data, $files) {
      $paths = $this->storeFiles('certificados_firmantes/' . Str::uuid(), $files);

      $signer = Signer::create([
        ...collect($data)->except(['file', 'key', 'firmaimg'])->all(),
        ...$paths,
        'write_id'       => auth()->id(),
        'status_deleted' => 1,
      ]);

      return $this->show($signer->id);
    });
  }

  /** @param array<string, UploadedFile|null> $files */
  public function update(int $id, array $data, array $files): SignerResource
  {
    $signer = Signer::findOrFail($id);

    return DB::transaction(function () use ($signer, $data, $files) {
      $paths = $this->storeFiles('certificados_firmantes/' . Str::uuid(), $files);

      $signer->update([
        ...collect($data)->except(['file', 'key', 'firmaimg'])->all(),
        ...$paths,
      ]);

      return $this->show($signer->id);
    });
  }

  public function destroy(int $id): void
  {
    $signer = Signer::findOrFail($id);
    $signer->update(['status_deleted' => 0]);
  }

  public function export(Request $request)
  {
    return $this->exportService->exportFromRequest($request, Signer::class);
  }

  /** @param array<string, UploadedFile|null> $files */
  private function storeFiles(string $folder, array $files): array
  {
    $paths = [];

    foreach (['file', 'key', 'firmaimg'] as $field) {
      $uploaded = $files[$field] ?? null;

      if ($uploaded instanceof UploadedFile) {
        $paths[$field] = Storage::disk('local')->putFile($folder, $uploaded);
      }
    }

    return $paths;
  }
}
