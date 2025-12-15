<?php

namespace App\Http\Services\gp\gestionhumana\personal;

use App\Http\Resources\gp\gestionhumana\personal\PersonResource;
use App\Http\Resources\PersonBirthdayResource;
use App\Http\Services\BaseService;
use App\Http\Services\gp\gestionsistema\DigitalFileService;
use App\Http\Utils\Helpers;
use App\Models\gp\gestionhumana\personal\WorkerSignature;
use App\Models\gp\gestionsistema\DigitalFile;
use App\Models\gp\gestionsistema\Person;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PersonService extends BaseService
{
  protected DigitalFileService $digitalFileService;

  private const FILE_PATHS = [
    'worker_signature' => '/gp/gestionhumana/personal/firmas/',
  ];

  public function __construct(DigitalFileService $digitalFileService)
  {
    $this->digitalFileService = $digitalFileService;
  }

  public function list(Request $request)
  {
    return $this->getFilteredResults(
      Person::where('status_deleted', 1),
      $request,
      Person::filters,
      Person::sorts,
      PersonResource::class,
    );
  }

  public function listBirthdays(Request $request)
  {
    $query = Person::query()
      ->working()
      ->select('*')
      ->selectRaw("
            DATEDIFF(
                DATE_ADD(
                    fecha_nacimiento,
                    INTERVAL (YEAR(CURDATE()) - YEAR(fecha_nacimiento)) +
                    (DATE_FORMAT(fecha_nacimiento, '%m-%d') < DATE_FORMAT(CURDATE(), '%m-%d')) YEAR
                ),
                CURDATE()
            ) as days_to_birthday
        ")
      ->orderBy('days_to_birthday');

    return $this->getFilteredResults(
      $query,
      $request,
      Person::filters,
      Person::sorts,
      PersonBirthdayResource::class
    );
  }


  public function find($id)
  {
    $person = Person::where('id', $id)->first();
    if (!$person) {
      throw new Exception('Persona no encontrada');
    }
    return $person;
  }

  public function store(array $data)
  {
    $person = Person::create($data);
    return new PersonResource($person);
  }

  public function show($id)
  {
    return new PersonResource($this->find($id));
  }

  public function update($data)
  {
    try {
      DB::beginTransaction();

      // Buscar la persona
      $person = Person::find($data['id']);
      if (!$person) {
        throw new Exception('Persona no encontrada');
      }

      // Extraer firma en base64 del array
      $workerSignature = $data['worker_signature'] ?? null;
      unset($data['worker_signature']);

      // Actualizar datos de la persona
      $person->update($data);

      // Procesar y guardar firma si existe
      if ($workerSignature) {
        $this->processWorkerSignature($person, $workerSignature);
      }

      DB::commit();

      return new PersonResource($person);
    } catch (Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  public function destroy($id)
  {
    $person = $this->find($id);
    DB::transaction(function () use ($person) {
      $person->delete();
    });
    return response()->json(['message' => 'Periodo eliminado correctamente']);
  }

  /**
   * Procesa una firma de trabajador en base64 y la guarda en Digital Ocean
   */
  private function processWorkerSignature($person, string $base64Signature): void
  {
    // Convertir base64 a UploadedFile
    $signatureFile = Helpers::base64ToUploadedFile($base64Signature, "worker_{$person->id}_signature.png");

    // Determinar la ruta
    $path = self::FILE_PATHS['worker_signature'];
    $model = 'worker_signature';

    // Subir archivo usando DigitalFileService
    $digitalFile = $this->digitalFileService->store($signatureFile, $path, 'public', $model);

    // Buscar si ya existe una firma para este trabajador
    $workerSignature = WorkerSignature::where('worker_id', $person->id)->first();

    if ($workerSignature) {
      // Si existe, eliminar la firma anterior de Digital Ocean
      if ($workerSignature->signature_url) {
        $oldDigitalFile = DigitalFile::where('url', $workerSignature->signature_url)->first();
        if ($oldDigitalFile) {
          $this->digitalFileService->destroy($oldDigitalFile->id);
        }
      }

      // Actualizar con la nueva URL
      $workerSignature->signature_url = $digitalFile->url;
      $workerSignature->save();
    } else {
      // Crear nuevo registro de firma
      WorkerSignature::create([
        'worker_id' => $person->id,
        'signature_url' => $digitalFile->url,
        'company_id' => $person->sede->empresa_id ?? null,
      ]);
    }
  }
}
