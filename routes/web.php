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

// Preview temporal del correo de evaluación abierta (opened)
Route::get('/preview/evaluation-opened/{chiefId}', [EvaluationNotificationController::class, 'previewEvaluationOpened'])
  ->whereNumber('chiefId')
  ->name('preview.evaluation-opened');

// Preview temporal del correo de evaluación finalizada (closed)
Route::get('/preview/evaluation-closed/{chiefId}', [EvaluationNotificationController::class, 'previewEvaluationClosed'])
  ->whereNumber('chiefId')
  ->name('preview.evaluation-closed');

// Preview temporal del PDF de Gantt (HTML crudo, para ajustar el diseño sin pasar por dompdf en cada vuelta)
Route::get('/preview/scrum-project-gantt/{id}', function (int $id) {
  return app(\App\Http\Services\gp\tics\pm\ScrumProjectService::class)->ganttPdfHtml($id);
})->whereNumber('id')->name('preview.scrum-project-gantt');

// Preview temporal del PDF de Gantt (el archivo real generado por dompdf)
Route::get('/preview/scrum-project-gantt/{id}/pdf', function (int $id) {
  return app(\App\Http\Services\gp\tics\pm\ScrumProjectService::class)->ganttPdf($id);
})->whereNumber('id')->name('preview.scrum-project-gantt.pdf');

