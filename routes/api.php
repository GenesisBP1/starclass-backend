<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClaseController;
use App\Http\Controllers\Api\TareaController;
use App\Http\Controllers\Api\EntregaController;
use App\Http\Controllers\Api\AsistenciaController;
use App\Http\Controllers\Api\QrController;
use App\Http\Controllers\Api\ExportController;


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/clases', [ClaseController::class, 'store']);
Route::get('/clases/maestro/{id}', [ClaseController::class, 'clasesMaestro']);
Route::get('/clases', [ClaseController::class, 'index']);
Route::get('/clases/alumno/{id}', [ClaseController::class, 'clasesAlumno']);
Route::get('/clases/{id}', [ClaseController::class, 'show']);
Route::put('/clases/{id}', [ClaseController::class, 'update']);
Route::delete('/clases/{id}', [ClaseController::class, 'destroy']);
Route::post('/clases/unirse', [ClaseController::class, 'unirse']);
Route::post('/tareas', [TareaController::class, 'store']);
Route::get('/clases/{id}/tareas', [TareaController::class, 'tareasPorClase']);
Route::post('/tareas/entregar', [EntregaController::class, 'entregar']);
Route::get('/tareas/{id}/entregas', [EntregaController::class, 'entregasPorTarea']);
Route::post('/asistencias/registrar', [AsistenciaController::class, 'registrar']);
Route::get('/clases/{id}/asistencias', [AsistenciaController::class, 'asistenciasPorClase']);
Route::post('/qr/generar', [QrController::class, 'generar']);
Route::post('/qr/validar', [QrController::class, 'validar']);
Route::put('/clases/{id}', [ClaseController::class, 'actualizar']);
Route::delete('/clases/{id}', [ClaseController::class, 'eliminar']);
Route::put('/tareas/{id}', [TareaController::class, 'actualizar']);
Route::delete('/tareas/{id}', [TareaController::class, 'eliminar']);
Route::get('/clases/{id}/alumnos', [ClaseController::class, 'alumnosClase']);
Route::get('/tareas/{id}/reporte', [EntregaController::class, 'reporteTarea']);
Route::get('/clases/{id}/reporte-tareas', [TareaController::class, 'reporteTareasClase']);
Route::get('/export/asistencias/{id}', [ExportController::class, 'exportarAsistencias']);
Route::get('/export/entregas/{id}', [ExportController::class, 'exportarEntregas']);
Route::get('/export/tareas/{id}', [ExportController::class, 'exportarTareas']);

