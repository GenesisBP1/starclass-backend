<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClaseController;
use App\Http\Controllers\Api\TareaController;
use App\Http\Controllers\Api\EntregaController;
use App\Http\Controllers\Api\AsistenciaController;
use App\Http\Controllers\Api\QrController;
use App\Http\Controllers\Api\ExportController;

/*
|--------------------------------------------------------------------------
| Rutas públicas
|--------------------------------------------------------------------------
*/
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| Rutas protegidas con Sanctum
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // Clases
    Route::post('/clases', [ClaseController::class, 'store']);
    Route::get('/clases', [ClaseController::class, 'index']);
    Route::get('/clases/maestro/{id}', [ClaseController::class, 'clasesMaestro']);
    Route::get('/clases/alumno/{id}', [ClaseController::class, 'clasesAlumno']);
    Route::get('/clases/{id}', [ClaseController::class, 'show']);
    Route::put('/clases/{id}', [ClaseController::class, 'actualizar']);
    Route::delete('/clases/{id}', [ClaseController::class, 'eliminar']);
    Route::post('/clases/unirse', [ClaseController::class, 'unirse']);
    Route::get('/clases/{id}/alumnos', [ClaseController::class, 'alumnosClase']);

    // Tareas
    Route::post('/tareas', [TareaController::class, 'store']);
    Route::get('/clases/{id}/tareas', [TareaController::class, 'tareasPorClase']);
    Route::put('/tareas/{id}', [TareaController::class, 'actualizar']);
    Route::delete('/tareas/{id}', [TareaController::class, 'eliminar']);
    Route::get('/clases/{id}/reporte-tareas', [TareaController::class, 'reporteTareasClase']);

    // Entregas
    Route::post('/tareas/entregar', [EntregaController::class, 'entregar']);
    Route::get('/tareas/{id}/entregas', [EntregaController::class, 'entregasPorTarea']);
    Route::get('/tareas/{id}/reporte', [EntregaController::class, 'reporteTarea']);

    // Asistencias
    Route::post('/asistencias/registrar', [AsistenciaController::class, 'registrar']);
    Route::get('/clases/{id}/asistencias', [AsistenciaController::class, 'asistenciasPorClase']);

    // QR
    Route::post('/qr/generar', [QrController::class, 'generar']);
    Route::post('/qr/validar', [QrController::class, 'validar']);

    // Exportaciones
    Route::get('/export/asistencias/{id}', [ExportController::class, 'exportarAsistencias']);
    Route::get('/export/entregas/{id}', [ExportController::class, 'exportarEntregas']);
    Route::get('/export/tareas/{id}', [ExportController::class, 'exportarTareas']);
});