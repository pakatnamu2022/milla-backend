<?php

namespace App\Http\Services\gp\gestionhumana\asistencias;

use App\Http\Resources\gp\gestionhumana\asistencias\AttendanceCodeMappingResource;
use App\Http\Services\BaseService;
use App\Models\gp\gestionhumana\asistencias\AttendanceCodeMapping;
use Illuminate\Http\Request;

class AttendanceCodeMappingService extends BaseService
{
  public function list(Request $request)
  {
    return $this->getFilteredResults(
      AttendanceCodeMapping::query(),
      $request,
      AttendanceCodeMapping::filters,
      AttendanceCodeMapping::sorts,
      AttendanceCodeMappingResource::class,
    );
  }

  public function show(int $id): AttendanceCodeMappingResource
  {
    return new AttendanceCodeMappingResource(AttendanceCodeMapping::findOrFail($id));
  }

  public function store(Request $request): AttendanceCodeMappingResource
  {
    $request->validate([
      'emp_code' => ['required', 'string', 'max:50', 'unique:attendance_code_mappings,emp_code'],
      'vat'      => ['required', 'string', 'max:20'],
      'note'     => ['nullable', 'string', 'max:500'],
    ]);

    $record = AttendanceCodeMapping::create([
      'emp_code'   => trim($request->emp_code),
      'vat'        => trim($request->vat),
      'note'       => $request->note,
      'created_by' => auth()->id(),
    ]);

    return new AttendanceCodeMappingResource($record);
  }

  public function update(Request $request, int $id): AttendanceCodeMappingResource
  {
    $record = AttendanceCodeMapping::findOrFail($id);

    $request->validate([
      'emp_code' => ['sometimes', 'string', 'max:50', 'unique:attendance_code_mappings,emp_code,' . $id],
      'vat'      => ['sometimes', 'string', 'max:20'],
      'note'     => ['nullable', 'string', 'max:500'],
    ]);

    $record->update(array_filter([
      'emp_code' => $request->filled('emp_code') ? trim($request->emp_code) : null,
      'vat'      => $request->filled('vat') ? trim($request->vat) : null,
      'note'     => $request->note,
    ], fn($v) => $v !== null));

    return new AttendanceCodeMappingResource($record->fresh());
  }

  public function destroy(int $id): array
  {
    AttendanceCodeMapping::findOrFail($id)->delete();

    return ['message' => 'Mapeo eliminado.'];
  }
}
