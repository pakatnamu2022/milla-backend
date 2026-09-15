<?php

namespace App\Http\Controllers\gp\gestionhumana\reclutamiento;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\reclutamiento\ChangeSelectedWorkerLifeStatusRequest;
use App\Http\Requests\gp\gestionhumana\reclutamiento\IndexSelectedWorkerRequest;
use App\Http\Requests\gp\gestionhumana\reclutamiento\RehireSelectedWorkerRequest;
use App\Http\Requests\gp\gestionhumana\reclutamiento\StoreRelativeRequest;
use App\Http\Requests\gp\gestionhumana\reclutamiento\StoreWorkExperienceRequest;
use App\Http\Requests\gp\gestionhumana\reclutamiento\UpdateSelectedWorkerProfileRequest;
use App\Http\Requests\gp\gestionhumana\reclutamiento\UploadSignedOfferLetterRequest;
use App\Http\Services\gp\gestionhumana\reclutamiento\SelectedWorkerService;
use Illuminate\Http\JsonResponse;

class SelectedWorkerController extends Controller
{
  public function __construct(protected SelectedWorkerService $service) {}

  public function index(IndexSelectedWorkerRequest $request): JsonResponse
  {
    try {
      return $this->service->list($request);
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function show(int $id): JsonResponse
  {
    try {
      return $this->success($this->service->show($id));
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function uploadSignedLetter(UploadSignedOfferLetterRequest $request, int $id): JsonResponse
  {
    try {
      return $this->success($this->service->uploadSignedLetter($id, $request->file('carta_oferta')));
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function sendWelcomeEmail(int $id): JsonResponse
  {
    try {
      return $this->success($this->service->sendWelcomeEmail($id));
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function generateUser(int $id): JsonResponse
  {
    try {
      return $this->success($this->service->generateUser($id));
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function updateProfile(UpdateSelectedWorkerProfileRequest $request, int $id): JsonResponse
  {
    try {
      return $this->success($this->service->updateProfile($id, $request->validated()));
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function changeLifeStatus(ChangeSelectedWorkerLifeStatusRequest $request, int $id): JsonResponse
  {
    try {
      $userId = $request->user()?->id;
      return $this->success($this->service->changeLifeStatus($id, $request->validated(), $userId));
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function rehire(RehireSelectedWorkerRequest $request, int $id): JsonResponse
  {
    try {
      return $this->success($this->service->rehire($id, (int) $request->validated()['proceso_postulacion_id']));
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function indexRelatives(int $id): JsonResponse
  {
    try {
      return $this->success($this->service->listRelatives($id));
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function storeRelative(StoreRelativeRequest $request, int $id): JsonResponse
  {
    try {
      $userId = $request->user()?->id;
      return $this->success($this->service->addRelative($id, $request->validated(), $userId), 201);
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function destroyRelative(\Illuminate\Http\Request $request, int $id, int $relativeId): JsonResponse
  {
    try {
      $this->service->removeRelative($id, $relativeId, $request->user()?->id);
      return $this->success(null);
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function indexWorkExperiences(int $id): JsonResponse
  {
    try {
      return $this->success($this->service->listWorkExperiences($id));
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function storeWorkExperience(StoreWorkExperienceRequest $request, int $id): JsonResponse
  {
    try {
      $userId = $request->user()?->id;
      return $this->success($this->service->addWorkExperience($id, $request->validated(), $userId), 201);
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function destroyWorkExperience(\Illuminate\Http\Request $request, int $id, int $experienceId): JsonResponse
  {
    try {
      $this->service->removeWorkExperience($id, $experienceId, $request->user()?->id);
      return $this->success(null);
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function export(\Illuminate\Http\Request $request)
  {
    try {
      return $this->service->export($request);
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }
}
