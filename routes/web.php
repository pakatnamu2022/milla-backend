<?php

use App\Http\Controllers\gp\tics\EquipmentAssigmentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
  return view('welcome');
});

// Preview de acta de asignación sin autenticación
Route::get('/preview/acta-asignacion/{id}', [EquipmentAssigmentController::class, 'previewEquipmentAssignment'])
  ->name('preview.equipment-assignment');

