<?php

namespace App\Http\Services\gp\gestionhumana\asistencias;

use App\Http\Resources\gp\gestionhumana\asistencias\AttendanceExclusionResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionhumana\asistencias\AttendanceExclusion;
use Illuminate\Http\Request;

class AttendanceExclusionService extends BaseService
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      AttendanceExclusion::query()->with('person'),
      $request,
      AttendanceExclusion::filters,
      AttendanceExclusion::sorts,
      AttendanceExclusionResource::class,
    );
  }

  public function show(int $id): AttendanceExclusionResource
  {
    $record = AttendanceExclusion::with('person')->findOrFail($id);

    return new AttendanceExclusionResource($record);
  }

  public function store(Request $request): AttendanceExclusionResource
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

    return new AttendanceExclusionResource($record->load('person'));
  }

  public function update(Request $request, int $id): AttendanceExclusionResource
  {
    $record = AttendanceExclusion::findOrFail($id);

    $request->validate([
      'reason' => ['nullable', 'string', 'max:500'],
      'active' => ['sometimes', 'boolean'],
    ]);

    $record->update($request->only(['reason', 'active']));

    return new AttendanceExclusionResource($record->load('person'));
  }

  public function destroy(int $id): array
  {
    AttendanceExclusion::findOrFail($id)->delete();

    return ['message' => 'Exclusión eliminada.'];
  }
}
