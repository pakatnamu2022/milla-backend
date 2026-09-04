<?php

namespace App\Http\Services\gp\gestionhumana\reclutamiento;

use App\Http\Resources\gp\gestionhumana\reclutamiento\ApplicantResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionhumana\reclutamiento\Applicant;
use App\Models\gp\gestionhumana\reclutamiento\RecruitmentProcess;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ApplicantService extends BaseService
{
  private const RELATIONS = ['sede', 'area', 'position', 'process', 'user'];

  public function list(Request $request): JsonResponse
  {
    return $this->getFilteredResults(
      Applicant::query()->with(self::RELATIONS),
      $request,
      Applicant::filters,
      Applicant::sorts,
      ApplicantResource::class,
    );
  }

  public function show(int $id): ApplicantResource
  {
    return new ApplicantResource(
      Applicant::with(self::RELATIONS)->findOrFail($id)
    );
  }

  public function store(array $data, ?UploadedFile $cv = null, ?UploadedFile $foto = null): ApplicantResource
  {
    return DB::transaction(function () use ($data, $cv, $foto) {
      /** @var RecruitmentProcess $process */
      $process = RecruitmentProcess::withoutGlobalScopes()->findOrFail($data['proceso_postulacion_id']);

      $vat = trim((string) $data['vat']);
      $duplicate = Applicant::withoutGlobalScopes()
        ->where('b_empleado', 1)
        ->where('sede_id', $process->sede_id)
        ->where('vat', $vat)
        ->exists();
      if ($duplicate) {
        throw new \RuntimeException('Ya existe una persona con ese documento en la sede del proceso.');
      }

      $applicant = Applicant::create([
        ...$this->onlyPersonAttributes($data),
        'vat'                    => $vat,
        'sede_id'                => $process->sede_id,
        'area_id'                => $process->area_id,
        'cargo_id'               => $process->cargo_id,
        'centro_costo_id'        => $process->centro_costo_id,
        'proceso_postulacion_id' => $process->id,
        'tipo_trabajador_id'     => Applicant::TIPO_POSTULANTE,
        'b_empleado'             => 1,
        'status_deleted'         => 1,
      ]);

      $this->storeFiles($applicant, $cv, $foto);

      // Primer postulante mueve el proceso a EN PROCESO (10).
      if ($process->status_id === RecruitmentProcess::STATUS_OPEN) {
        $process->update(['status_id' => RecruitmentProcess::STATUS_IN_PROCESS]);
      }

      $this->createUser($applicant, $vat);
      $this->assignInitialDocuments($applicant, Applicant::TIPO_POSTULANTE);
      $this->logChange($applicant);

      return $this->show($applicant->id);
    });
  }

  public function update(int $id, array $data, ?UploadedFile $cv = null, ?UploadedFile $foto = null): ApplicantResource
  {
    return DB::transaction(function () use ($id, $data, $cv, $foto) {
      $applicant = Applicant::findOrFail($id);
      $applicant->update($this->onlyPersonAttributes($data));
      $this->storeFiles($applicant, $cv, $foto);
      $this->logChange($applicant);

      return $this->show($applicant->id);
    });
  }

  /**
   * Cambia el estado del postulante (SELECCIONADO / RECHAZADO / FUERA DE CUPO / LISTA NEGRA).
   * La carta oferta, el alta y el cronograma se manejan en F2 (Seleccionados).
   */
  public function changeStatus(int $id, array $data): ApplicantResource
  {
    return DB::transaction(function () use ($id, $data) {
      $applicant = Applicant::findOrFail($id);

      $type = (int) $data['tipo_trabajador_id'];
      $applicant->update([
        'tipo_trabajador_id' => $type,
        'motivo_status'      => $data['motivo_status'] ?? null,
        'jefe_id'            => $data['jefe_id'] ?? $applicant->jefe_id,
      ]);

      if ($type === Applicant::TIPO_SELECCIONADO) {
        $this->assignInitialDocuments($applicant, Applicant::TIPO_SELECCIONADO);
      }

      $this->logChange($applicant);

      return $this->show($applicant->id);
    });
  }

  /**
   * Anulacion logica del postulante (status_deleted = 0).
   */
  public function destroy(int $id): void
  {
    $applicant = Applicant::findOrFail($id);
    $applicant->update(['status_deleted' => 0]);
  }

  private function onlyPersonAttributes(array $data): array
  {
    unset($data['file_cv'], $data['file_foto']);
    return array_intersect_key($data, array_flip((new Applicant())->getFillable()));
  }

  private function storeFiles(Applicant $applicant, ?UploadedFile $cv, ?UploadedFile $foto): void
  {
    $dirty = false;
    if ($cv) {
      $applicant->cv_actualizado = $cv->storeAs(
        'resources_personas/cv/' . $applicant->id,
        $cv->getClientOriginalName(),
        'private'
      );
      $applicant->fecha_hora_ult_act_cv = now();
      $dirty = true;
    }
    if ($foto) {
      $applicant->foto_adjunto = $foto->storeAs(
        'resources_personas/profile/' . $applicant->id,
        $foto->getClientOriginalName(),
        'private'
      );
      $dirty = true;
    }
    if ($dirty) {
      $applicant->save();
    }
  }

  private function createUser(Applicant $applicant, string $vat): void
  {
    if (User::where('partner_id', $applicant->id)->exists()) {
      return;
    }

    User::create([
      'partner_id'     => $applicant->id,
      'name'           => $applicant->nombre_completo,
      'username'       => $vat,
      'password'       => Hash::make($vat),
      'status_deleted' => 1,
    ]);
  }

  /**
   * Replica la asignacion legacy: documentos de `config_doc_obligatorio_inic`
   * cuyo tipo_trabajador_id coincide y cuya sede/area/cargo (listas separadas por
   * coma) contienen la del postulante.
   */
  private function assignInitialDocuments(Applicant $applicant, int $tipoTrabajadorId): void
  {
    $docs = DB::table('config_doc_obligatorio_inic')
      ->where('tipo_trabajador_id', $tipoTrabajadorId)
      ->where(function ($q) use ($applicant) {
        foreach (['sede_id' => $applicant->sede_id, 'area_id' => $applicant->area_id, 'cargo_id' => $applicant->cargo_id] as $col => $value) {
          $q->orWhereRaw("FIND_IN_SET(?, REPLACE($col, ' ', '')) > 0", [$value]);
        }
      })
      ->get();

    foreach ($docs as $doc) {
      $exists = DB::table('rrhh_asig_doc_obligatorio')
        ->where('persona_id', $applicant->id)
        ->where('doc_inic_id', $doc->id)
        ->exists();
      if ($exists) {
        continue;
      }

      DB::table('rrhh_asig_doc_obligatorio')->insert([
        'persona_id'     => $applicant->id,
        'doc_inic_id'    => $doc->id,
        'status_id'      => 6,
        'status_deleted' => 1,
        'created_at'     => now(),
        'updated_at'     => now(),
      ]);
    }
  }

  private function logChange(Applicant $applicant): void
  {
    DB::table('rrhh_log_data_persona')->insert([
      'empleado_id'            => $applicant->id,
      'nombre_completo'        => $applicant->nombre_completo,
      'vat'                    => $applicant->vat,
      'email'                  => $applicant->email,
      'cel_personal'           => $applicant->cel_personal,
      'sede_id'                => $applicant->sede_id,
      'area_id'                => $applicant->area_id,
      'cargo_id'               => $applicant->cargo_id,
      'centro_costo_id'        => $applicant->centro_costo_id,
      'proceso_postulacion_id' => $applicant->proceso_postulacion_id,
      'sexo'                   => $applicant->sexo,
      'tipo_empleado'          => $applicant->tipo_trabajador_id,
      'motivo_status'          => $applicant->motivo_status,
      'created_at'             => now(),
      'updated_at'             => now(),
    ]);
  }
}
