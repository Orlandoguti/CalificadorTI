<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\SedeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PreguntaController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\NivelCalificacionController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\GestorController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Ruta principal y redirecciones por rol
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    if (auth()->check()) {
        $user = auth()->user();
        
        // Redirigir según rol
        if ($user->role === 'admin') {
            return redirect('/admin/dashboard');
        } elseif ($user->role === 'gestor') {
            return redirect('/gestor/dashboard');
        } else {
            return redirect('/calificar');
        }
    }
    return redirect('/login');
});

/*
|--------------------------------------------------------------------------
| Rutas públicas (SPA - Single Page Application)
|--------------------------------------------------------------------------
*/
Route::view('/login', 'app')->name('login');
Route::view('/ubicacion', 'app')->name('ubicacion');
Route::view('/areas', 'app')->name('areas');
Route::view('/calificar/{any}', 'app')->where('any', '.*');

/*
|--------------------------------------------------------------------------
| Rutas de autenticación con Google
|--------------------------------------------------------------------------
*/
Route::get('/auth/google', [GoogleAuthController::class, 'redirectToGoogle'])
    ->name('google.login');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback']);

/*
|--------------------------------------------------------------------------
| Rutas protegidas (requieren autenticación)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    
    // Logout
    Route::post('/logout', [GoogleAuthController::class, 'logout'])->name('logout');

    // API protegida (para usuarios autenticados)
    Route::prefix('api')->group(function () {
        
     // Datos del usuario - VERSIÓN CORREGIDA
    Route::get('/user', function () {
        $user = auth()->user();
        
        if (!$user) {
            return response()->json(['error' => 'No autenticado'], 401);
        }
        
        // 🔥 CORRECIÓN: Cargar la relación sede y devolver los datos
        $user = \App\Models\User::with('sede')->find($user->id);
        
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $user->avatar,
            'role' => $user->role,
            'sede_id' => $user->sede_id,
            'sede' => $user->sede, // Incluir la relación cargada
            'email_verified_at' => $user->email_verified_at,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at
        ]);
    });
        
        // Asignación de sede
        Route::post('/user/sede', [UserController::class, 'assignSede']);

        // Gestión de Áreas (CRUD completo)
        Route::post('/areas', [AreaController::class, 'store']);
        Route::put('/areas/{area}', [AreaController::class, 'update']);
        Route::delete('/areas/{area}', [AreaController::class, 'destroy']);
        Route::put('/areas/{area}/toggle', [AreaController::class, 'toggleStatus']);

        // Gestión de Preguntas (CRUD completo)
        Route::post('/preguntas', [PreguntaController::class, 'store']);
        Route::put('/preguntas/{pregunta}', [PreguntaController::class, 'update']);
        Route::delete('/preguntas/{pregunta}', [PreguntaController::class, 'destroy']);
        Route::put('/preguntas/{pregunta}/toggle', [PreguntaController::class, 'toggleStatus']);

        // Estadísticas del Dashboard Admin
        Route::get('/admin/stats', [AdminController::class, 'getStats']);
        // Nuevas rutas para estadísticas filtradas
        Route::get('/admin/stats-por-area', [AdminController::class, 'getStatsPorArea']);
        Route::get('/admin/actividad-reciente', [AdminController::class, 'getActividadReciente']);
        // Estadísticas del Dashboard Gestor
        Route::get('/gestor/stats', [GestorController::class, 'getStats']);
        Route::get('/gestor/calificaciones-por-area', [GestorController::class, 'getCalificacionesPorArea']);
    
    });

    // Rutas que requieren ubicación válida
    Route::middleware([\App\Http\Middleware\CheckLocation::class])->group(function () {
        Route::view('/calificar', 'app')->name('calificar');
    });

    // Dashboard Admin
    Route::prefix('admin')->group(function () {
        Route::get('/dashboard', function () {
            if (auth()->user()->role !== 'admin') {
                abort(403);
            }
            return view('app');
        })->name('admin.dashboard');
    });

    // Dashboard Gestor
    Route::prefix('gestor')->group(function () {
        Route::get('/dashboard', function () {
            if (auth()->user()->role !== 'gestor') {
                abort(403);
            }
            return view('app');
        })->name('gestor.dashboard');
    });
});

/*
|--------------------------------------------------------------------------
| Catch-all (fallback) - EXCLUIR API
|--------------------------------------------------------------------------
*/
Route::get('{any}', function () {
    return view('app');
})->where('any', '^(?!api\/).*$');
