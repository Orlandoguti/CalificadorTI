<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckGestor
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return redirect('/login');
        }

        $user = Auth::user();
        
        // CORREGIDO: Usar comparación directa en lugar de método que no existe
        if ($user->role !== 'gestor') {
            abort(403, 'No tienes permisos de gestor');
        }

        // Verificar que el gestor tenga una sede asignada
        if (!$user->sede_id) {
            return redirect('/ubicacion')->with('error', 'Debes seleccionar una sede primero');
        }

        return $next($request);
    }
}