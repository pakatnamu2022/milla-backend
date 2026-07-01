<?php

namespace App\Http\Services\gp\gestionhumana\asistencias;

use App\Http\Resources\gp\gestionhumana\asistencias\AttendanceExclusionResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionhumana\asistencias\AttendanceExclusion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceExclusionService extends BaseService
{
  public function list(Request $request): JsonResponse
  {
    return $this->getFilteredResults(
      AttendanceExclusion::query()->with('person'),
      $request,
      AttendanceExclusion::filters,
      AttendanceExclusion::sorts,
      AttendanceExclusionResource::class,
    );
  }

  public function show(int $id): JsonResponse
  {
    $record = AttendanceExclusion::with('person')->findOrFail($id);

    return response()->json(new AttendanceExclusionResource($record));
  }

  public function store(Request $request): JsonResponse
  {
    $request->validate([
      'person_id' => ['required', 'integer', 'exists:rrhh_persona,id'],
      'reason'    => ['nullable', 'string', 'max:500'],
      'active'    => ['sometimes', 'boolean'],
    ]);

    $record = AttendanceExclusion::create([
      'person_id'  => $request->person_id,
      'reason'     => $request->reason,
      'active'     => $request->boolean('active', true),
      'created_by' => auth()->id(),
    ]);

    return response()->json(new AttendanceExclusionResource($record->load('person')), 201);
  }

  public function update(Request $request, int $id): JsonResponse
  {
    $record = AttendanceExclusion::findOrFail($id);

    $request->validate([
      'reason' => ['nullable', 'string', 'max:500'],
      'active' => ['sometimes', 'boolean'],
    ]);

    $record->update($request->only(['reason', 'active']));

    return response()->json(new AttendanceExclusionResource($record->load('person')));
  }

  public function destroy(int $id): JsonResponse
  {
    AttendanceExclusion::findOrFail($id)->delete();

    return response()->json(['message' => 'Exclusión eliminada.']);
  }
}
