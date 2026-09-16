<?php

namespace App\Http\Controllers\gp\gestionhumana\reclutamiento;

use App\Http\Controllers\Controller;
use App\Http\Requests\gp\gestionhumana\reclutamiento\IndexInterviewRequest;
use App\Http\Requests\gp\gestionhumana\reclutamiento\ScoreInterviewRequest;
use App\Http\Requests\gp\gestionhumana\reclutamiento\StoreInterviewRequest;
use App\Http\Requests\gp\gestionhumana\reclutamiento\SyncProcessCompetencesRequest;
use App\Http\Requests\gp\gestionhumana\reclutamiento\UpdateInterviewRequest;
use App\Http\Resources\gp\gestionhumana\reclutamiento\RecruitmentProcessCompetenceResource;
use App\Http\Services\gp\gestionhumana\reclutamiento\InterviewService;
use Illuminate\Http\JsonResponse;

class InterviewController extends Controller
{
  public function __construct(protected InterviewService $service) {}

  public function index(IndexInterviewRequest $request): JsonResponse
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

  public function store(StoreInterviewRequest $request): JsonResponse
  {
    try {
      return $this->success($this->service->store($request->validated()));
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function update(UpdateInterviewRequest $request, int $id): JsonResponse
  {
    try {
      return $this->success($this->service->update($id, $request->validated()));
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function score(ScoreInterviewRequest $request, int $id): JsonResponse
  {
    try {
      return $this->success($this->service->score($id, $request->validated()['scores']));
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function destroy(int $id): JsonResponse
  {
    try {
      $this->service->destroy($id);
      return $this->success(['message' => 'Entrevista eliminada.']);
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function competences(int $processId): JsonResponse
  {
    try {
      return $this->success(RecruitmentProcessCompetenceResource::collection($this->service->listCompetences($processId)));
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }

  public function syncCompetences(SyncProcessCompetencesRequest $request, int $processId): JsonResponse
  {
    try {
      $this->service->syncCompetences($processId, $request->validated()['sub_competencias']);
      return $this->success(RecruitmentProcessCompetenceResource::collection($this->service->listCompetences($processId)));
    } catch (\Throwable $e) {
      return $this->error($e->getMessage());
    }
  }
}
