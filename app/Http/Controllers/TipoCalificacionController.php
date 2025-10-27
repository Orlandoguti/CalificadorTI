<?php

namespace App\Http\Controllers;

use App\Models\TipoCalificacion;
use Illuminate\Http\Request;

class TipoCalificacionController extends Controller
{
    public function index()
    {
        try {
            $tipos = TipoCalificacion::where('is_active', true)
                ->orderBy('id')
                ->get();
            
            return response()->json($tipos);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al cargar tipos de calificación'], 500);
        }
    }
    
    public function show($id)
    {
        try {
            $tipo = TipoCalificacion::findOrFail($id);
            
            return response()->json($tipo);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Tipo de calificación no encontrado'], 404);
        }
    }
}

