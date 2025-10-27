<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckLocation
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        
        // 🔥 SOLO aplicar a usuarios normales (role = 'user')
        if ($user && $user->role === 'user' && !$user->sede_id) {
            return redirect('/ubicacion');
        }

        return $next($request);
    }
}