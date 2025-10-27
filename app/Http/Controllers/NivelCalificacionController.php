<?php

namespace App\Http\Controllers;

use App\Models\NivelCalificacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NivelCalificacionController extends Controller
{
    public function index()
    {
        try {
            $niveles = NivelCalificacion::where('is_active', true)->get();
            return response()->json($niveles);
        } catch (\Exception $e) {
            Log::error('Error en NivelCalificacionController: ' . $e->getMessage());
            return response()->json(['error' => 'Error interno del servidor'], 500);
        }
    }
}