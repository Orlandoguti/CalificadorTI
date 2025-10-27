<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckGestorSede
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        
        // Solo aplicar a gestores
        if ($user && $user->role === 'gestor') {
            // Verificar que el gestor tenga sede asignada
            if (!$user->sede_id) {
                return response()->json([
                    'error' => 'No tienes una sede asignada. Contacta al administrador.'
                ], 403);
            }
            
            // Para endpoints de gestión, verificar que la sede coincida
            if ($request->has('sede_id') && $request->sede_id != $user->sede_id) {
                return response()->json([
                    'error' => 'No tienes permisos para esta sede'
                ], 403);
            }
        }
        
        return $next($request);
    }
}