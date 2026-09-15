<?php

namespace App\Http\Services\gp\gestionhumana\reclutamiento;

use App\Http\Resources\gp\gestionhumana\reclutamiento\RelativeResource;
use App\Http\Resources\gp\gestionhumana\reclutamiento\SelectedWorkerResource;
use App\Http\Resources\gp\gestionhumana\reclutamiento\WorkExperienceResource;
use App\Http\Services\BaseService;
use App\Http\Services\common\EmailService;
use App\Http\Services\gp\gestionhumana\personal\WorkerStatusHistoryService;
use App\Models\gp\gestionhumana\reclutamiento\Applicant;
use App\Models\gp\gestionhumana\reclutamiento\RecruitmentProcess;
use App\Models\gp\gestionhumana\reclutamiento\Relative;
use App\Models\gp\gestionhumana\reclutamiento\SelectedWorker;
use App\Models\gp\gestionhumana\reclutamiento\WelcomeEmailTemplate;
use App\Models\gp\gestionhumana\reclutamiento\WorkExperience;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

/**
 * Etapa 4 del plan F2 (idVista 71, `SeleccionadoController` del legacy):
 * seguimiento del seleccionado hasta el alta como empleado.
 */
class SelectedWorkerService extends BaseService
{
  private const RELATIONS = ['sede', 'area', 'position', 'process', 'user', 'boss', 'supervisor'];

  public function __construct(private WorkerStatusHistoryService $statusHistoryService) {}

  public function list(Request $request): JsonResponse
  {
    return $this->getFilteredResults(
      SelectedWorker::query()->with(self::RELATIONS),
      $request,
      SelectedWorker::filters,
      SelectedWorker::sorts,
      SelectedWorkerResource::class,
    );
  }

  public function show(int $id): SelectedWorkerResource
  {
    return new SelectedWorkerResource(
      SelectedWorker::with([...self::RELATIONS, 'relatives', 'workExperiences'])->findOrFail($id)
    );
  }

  /**
   * Sube la carta oferta firmada. Requisito bloqueante para el alta (decision de negocio #4).
   */
  public function uploadSignedLetter(int $id, UploadedFile $file): SelectedWorkerResource
  {
    return DB::transaction(function () use ($id, $file) {
      $worker = SelectedWorker::findOrFail($id);

      if (!is_null($worker->carta_oferta)) {
        Storage::disk('private')->delete($worker->carta_oferta);
      }

      $path = 'resources_cartaoferta/' . $worker->id;
      $worker->carta_oferta = $file->storeAs($path, $file->getClientOriginalName(), 'private');
      $worker->status_carta_oferta_id = SelectedWorker::STATUS_CARTA_OFERTA_COMPLETADO;
      $worker->save();

      return $this->show($worker->id);
    });
  }

  public function sendWelcomeEmail(int $id): SelectedWorkerResource
  {
    return DB::transaction(function () use ($id) {
      $worker = SelectedWorker::with(['sede', 'area', 'position'])->findOrFail($id);

      $template = WelcomeEmailTemplate::query()->first();
      if (!$template) {
        throw new \RuntimeException('No hay una plantilla de bienvenida configurada (config_onboarding).');
      }
      if (!$worker->email) {
        throw new \RuntimeException('El trabajador no tiene un email registrado.');
      }

      $content = str_replace('{$cargo}', '<strong>' . ($worker->position?->name ?? '') . '</strong>', $template->contenido);
      $content = str_replace('{$area}', '<strong>' . ($worker->area?->name ?? '') . '</strong>', $content);
      $content = str_replace('{$sede}', '<strong>' . ($worker->sede?->abreviatura ?? '') . '</strong>', $content);
      $content = str_replace('{$postulante}', '<strong>' . $worker->nombre_completo . '</strong>', $content);

      (new EmailService())->send([
        'to'       => [$worker->email],
        'subject'  => $template->asunto ?: 'Bienvenida',
        'template' => 'emails.reclutamiento-notification',
        'data'     => ['title' => $template->asunto, 'body_html' => $content],
      ]);

      $worker->status_envio_mail_carta_oferta = 21; // config_status: CONFIRMADO (tipo_8)
      $worker->fecha_envio_mail_carta_oferta = now();
      $worker->save();

      return $this->show($worker->id);
    });
  }

  /**
   * Crea el usuario del trabajador si no existe, o lo reactiva si estaba desactivado.
   */
  public function generateUser(int $id): array
  {
    $worker = SelectedWorker::findOrFail($id);

    $userByPartner = User::where('partner_id', $worker->id)->first();
    if ($userByPartner) {
      if ($userByPartner->status_deleted == 1) {
        return ['tipo' => 'info', 'mensaje' => "El trabajador ya tiene un usuario activo (username: {$userByPartner->username})."];
      }
      $userByPartner->update(['status_deleted' => 1]);
      return ['tipo' => 'success', 'mensaje' => "El usuario de {$worker->nombre_completo} estaba desactivado y fue reactivado."];
    }

    $userByUsername = User::where('username', $worker->vat)->first();
    if ($userByUsername) {
      throw new \RuntimeException("El username {$worker->vat} ya esta registrado en el sistema para otra persona.");
    }

    User::create([
      'partner_id'     => $worker->id,
      'name'           => $worker->nombre_completo,
      'username'       => $worker->vat,
      'password'       => Hash::make($worker->vat),
      'status_deleted' => 1,
    ]);

    return ['tipo' => 'success', 'mensaje' => "Usuario creado correctamente para {$worker->nombre_completo}."];
  }

  /**
   * Ficha completa del trabajador: datos bancarios, AFP/SNP, sueldo, asignacion familiar,
   * escolaridad, SCTR, EsSalud, centro de costo, jefe, supervisor.
   */
  public function updateProfile(int $id, array $data): SelectedWorkerResource
  {
    return DB::transaction(function () use ($id, $data) {
      $worker = SelectedWorker::findOrFail($id);
      $worker->update(array_intersect_key($data, array_flip($worker->getFillable())));

      return $this->show($worker->id);
    });
  }

  /**
   * Alta (22) o baja (23). El alta exige la carta oferta firmada (decision de negocio #4).
   * Reutiliza WorkerStatusHistoryService::store() para el historial (`rrhh_estado_trabajador`).
   */
  public function changeLifeStatus(int $id, array $data, ?int $userId): SelectedWorkerResource
  {
    return DB::transaction(function () use ($id, $data, $userId) {
      $worker = SelectedWorker::findOrFail($id);
      $estado = (int) $data['estado'];

      if ($estado === SelectedWorker::STATUS_ALTA && (int) $worker->status_carta_oferta_id !== SelectedWorker::STATUS_CARTA_OFERTA_COMPLETADO) {
        throw new \RuntimeException('No se puede dar de alta sin la carta oferta firmada.');
      }

      $user = User::where('partner_id', $worker->id)->first();
      if (!$user) {
        $user = User::create([
          'partner_id'     => $worker->id,
          'name'           => $worker->nombre_completo,
          'username'       => $worker->vat,
          'password'       => Hash::make($worker->vat),
          'status_deleted' => 1,
        ]);
      }
      $user->update(['status_deleted' => $estado === SelectedWorker::STATUS_ALTA ? 1 : 0]);

      $this->statusHistoryService->store([
        'empleado_id' => $worker->id,
        'fecha'       => $data['fecha'],
        'estado'      => $estado,
        'motivo'      => $data['motivo'] ?? null,
        'sucursal_id' => $worker->sede_id,
      ], $userId);

      $worker->status_id = $estado;
      if ($estado === SelectedWorker::STATUS_ALTA) {
        $worker->tipo_trabajador_id = Applicant::TIPO_CONTRATADO;
      }
      $worker->save();

      return $this->show($worker->id);
    });
  }

  /**
   * Reasigna a un trabajador cesado (status_id = 23) a un nuevo proceso de postulacion,
   * volviendo a POSTULANTE (equivale a `SeleccionadoController::reingreso` con kt_status_select=1).
   * Devuelve el registro ya actualizado directamente: tras el reingreso el postulante deja
   * de cumplir el scope de `SelectedWorker` (tipo_trabajador_id ya no es 2/6), por lo que
   * no se puede usar `show()` (que reconsulta con ese scope) para la respuesta.
   */
  public function rehire(int $id, int $procesoPostulacionId): SelectedWorkerResource
  {
    return DB::transaction(function () use ($id, $procesoPostulacionId) {
      $worker = SelectedWorker::findOrFail($id);

      if ((int) $worker->status_id !== SelectedWorker::STATUS_BAJA) {
        throw new \RuntimeException('Solo se puede reingresar a un trabajador cesado.');
      }

      $process = RecruitmentProcess::findOrFail($procesoPostulacionId);
      if ($process->status_id === RecruitmentProcess::STATUS_CLOSED) {
        throw new \RuntimeException('No se puede reingresar contra un proceso cerrado.');
      }

      $worker->update([
        'sede_id'                => $process->sede_id,
        'area_id'                => $process->area_id,
        'cargo_id'                => $process->cargo_id,
        'centro_costo_id'        => $process->centro_costo_id,
        'proceso_postulacion_id' => $process->id,
        'tipo_trabajador_id'     => Applicant::TIPO_POSTULANTE,
        'motivo_status'          => null,
        'b_empleado'             => 1,
      ]);

      if ($process->status_id === RecruitmentProcess::STATUS_OPEN) {
        $process->update(['status_id' => RecruitmentProcess::STATUS_IN_PROCESS]);
      }

      User::where('partner_id', $worker->id)->update(['status_deleted' => 1]);

      return new SelectedWorkerResource($worker->load(self::RELATIONS));
    });
  }

  public function listRelatives(int $workerId)
  {
    return RelativeResource::collection(
      Relative::where('persona_id', $workerId)->get()
    );
  }

  public function addRelative(int $workerId, array $data, ?int $userId): RelativeResource
  {
    $exists = Relative::where('persona_id', $workerId)->where('dni', $data['dni'])->exists();
    if ($exists) {
      throw new \RuntimeException('Ya se ha registrado un pariente con el mismo DNI.');
    }

    $relative = Relative::create([
      ...$data,
      'persona_id'     => $workerId,
      'status_id'      => 19,
      'status_deleted' => 1,
      'write_id'       => $userId,
    ]);

    return new RelativeResource($relative);
  }

  public function removeRelative(int $workerId, int $relativeId, ?int $userId): void
  {
    $relative = Relative::where('id', $relativeId)->where('persona_id', $workerId)->firstOrFail();
    $relative->update(['status_deleted' => 0, 'write_id' => $userId]);
  }

  public function listWorkExperiences(int $workerId)
  {
    return WorkExperienceResource::collection(
      WorkExperience::where('persona_id', $workerId)->get()
    );
  }

  public function addWorkExperience(int $workerId, array $data, ?int $userId): WorkExperienceResource
  {
    $experience = WorkExperience::create([
      ...$data,
      'persona_id'     => $workerId,
      'status_deleted' => 1,
      'write_id'       => $userId,
    ]);

    return new WorkExperienceResource($experience);
  }

  public function removeWorkExperience(int $workerId, int $experienceId, ?int $userId): void
  {
    $experience = WorkExperience::where('id', $experienceId)->where('persona_id', $workerId)->firstOrFail();
    $experience->update(['status_deleted' => 0, 'write_id' => $userId]);
  }
}
