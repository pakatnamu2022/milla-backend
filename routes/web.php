<?php

use App\Http\Controllers\gp\gestionhumana\evaluacion\EvaluationNotificationController;
use App\Http\Controllers\gp\tics\EquipmentAssigmentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
  return view('welcome');
});

// Preview de acta de asignación sin autenticación
Route::get('/preview/acta-asignacion/{id}', [EquipmentAssigmentController::class, 'previewEquipmentAssignment'])
  ->name('preview.equipment-assignment');

// Preview temporal del correo de resultados disponibles por jefe
Route::get('/preview/evaluation-results-available/{chiefId}', [EvaluationNotificationController::class, 'previewResultsAvailable'])
  ->whereNumber('chiefId')
  ->name('preview.evaluation-results-available');

// Preview temporal del correo de recordatorio de evaluaciones pendientes
Route::get('/preview/evaluation-reminder/{chiefId}', [EvaluationNotificationController::class, 'previewEvaluationReminder'])
  ->whereNumber('chiefId')
  ->name('preview.evaluation-reminder');

