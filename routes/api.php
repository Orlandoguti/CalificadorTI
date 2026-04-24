<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SedeController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\NivelCalificacionController;
use App\Http\Controllers\PreguntaController;
use App\Http\Controllers\CalificacionController;
use App\Http\Controllers\SubpreguntaController;
use App\Http\Controllers\OpcionPreguntaController;
use App\Models\Subpregunta;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\EstadisticaController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\GestorController;
use App\Http\Controllers\ExportacionController;
use App\Http\Controllers\TipoCalificacionController;

/*
|--------------------------------------------------------------------------
| API Routes (SOLO RUTAS PÚBLICAS - Sin autenticación)
|--------------------------------------------------------------------------
*/

// Rutas públicas para el calificador de invitados
Route::get('/sedes', [SedeController::class, 'index']);
Route::post('/detect-sede', [SedeController::class, 'detectSede']);
Route::get('/areas', [AreaController::class, 'index']);
Route::get('/areas/public', [AreaController::class, 'indexPublic']);
Route::get('/areas/{id}', [AreaController::class, 'show']);

// 🔥 NUEVO: Tipos de calificación
Route::get('/tipos-calificacion', [TipoCalificacionController::class, 'index']);
Route::get('/tipos-calificacion/{id}', [TipoCalificacionController::class, 'show']);
Route::apiResource('usuarios', UserController::class);
Route::put('/usuarios/{user}/rol', [UserController::class, 'cambiarRol']);
Route::get('/usuarios/{user}/areas', [UserController::class, 'getAreasAsignadas']);
Route::post('/usuarios/{user}/areas/sync', [UserController::class, 'syncAreas']);
Route::get('/niveles-calificacion', [NivelCalificacionController::class, 'index']);
Route::get('/preguntas', [PreguntaController::class, 'index']);

// Ruta para buscar sede por nombre
Route::get('/sedes/buscar', function (Request $request) {
    $nombre = $request->query('nombre');
    $sede = \App\Models\Sede::where('nombre', 'like', "%{$nombre}%")->first();
    
    if ($sede) {
        return response()->json($sede);
    }
    
    return response()->json(['error' => 'Sede no encontrada'], 404);
});

// Rutas de calificación para invitados
Route::post('/calificaciones', [CalificacionController::class, 'guardarSimple']);
Route::post('/calificaciones/completa', [CalificacionController::class, 'guardarCompleta']);



// Rutas para subpreguntas
Route::get('/subpreguntas/{opcionId}', [SubpreguntaController::class, 'index']);
Route::post('/subpreguntas', [SubpreguntaController::class, 'store']);
Route::put('/subpreguntas/{id}', [SubpreguntaController::class, 'update']);
Route::delete('/subpreguntas/{id}', [SubpreguntaController::class, 'destroy']);
// Ruta para actualizar estado de subpreguntas en opciones
Route::put('/opciones-pregunta/{id}', [OpcionPreguntaController::class, 'update']);


// 🔥 CORRECCIÓN: Solo una ruta para preguntas de rango
Route::get('/preguntas/{preguntaId}/rango/{valor}', function ($preguntaId, $valor) {
    try {
        Log::info("🔍 API: Buscando pregunta de rango para pregunta: {$preguntaId}, valor: {$valor}");
        
        $preguntaRango = \App\Models\Subpregunta::porRangoIndicador($preguntaId, $valor)->first();
        
        if ($preguntaRango) {
            Log::info("✅ API: Pregunta de rango encontrada: " . $preguntaRango->id);
            return response()->json($preguntaRango);
        }
        
        Log::info("📭 API: No se encontró pregunta de rango");
        return response()->json(null);
        
    } catch (\Exception $e) {
        Log::error('❌ API: Error obteniendo pregunta de rango: ' . $e->getMessage());
        return response()->json(['error' => 'Error interno del servidor'], 500);
    }
});

Route::get('/estadisticas', [EstadisticaController::class, 'index']);
Route::get('/estadisticas/exportar', [EstadisticaController::class, 'exportar']);

Route::middleware(['auth:sanctum', 'checkgestorsede'])->group(function () {
    Route::get('/gestor/stats', [GestorController::class, 'stats']);
    Route::get('/gestor/calificaciones-por-area', [GestorController::class, 'calificacionesPorArea']);
});

// Rutas para eliminación de preguntas
Route::get('/preguntas/{pregunta}/verificar-eliminacion', [PreguntaController::class, 'verificarEliminacion']);
Route::delete('/preguntas/{pregunta}/eliminar-forzado', [PreguntaController::class, 'eliminarForzado']);
Route::delete('/preguntas/{pregunta}', [PreguntaController::class, 'destroy']);

Route::get('/estadisticas/exportar', [ExportacionController::class, 'exportarEstadisticas']);
Route::get('/estadisticas/exportar-avanzado', [ExportacionController::class, 'exportarEstadisticasAvanzado']);
Route::get('/calificaciones/exportar', [ExportacionController::class, 'exportarCalificaciones']);