<?php

namespace App\Http\Controllers;

use App\Models\Calificacion;
use App\Models\Area;
use App\Models\Pregunta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GestorController extends Controller
{
    public function getStats()
    {
        $user = Auth::user();
        $sedeId = $user->sede_id;

        $stats = [
            'totalCalificaciones' => Calificacion::where('sede_id', $sedeId)->count(),
            'totalAreas' => Area::where('is_active', true)->count(),
            'totalPreguntas' => Pregunta::where('is_active', true)->count(),
            'promedioCalificacion' => Calificacion::where('sede_id', $sedeId)->avg('nivel_calificacion_id') ?? 0,
            'sede' => $user->sede->nombre
        ];

        return response()->json($stats);
    }

    public function getCalificacionesPorArea()
    {
        $user = Auth::user();
        $sedeId = $user->sede_id;

        $calificaciones = Calificacion::where('sede_id', $sedeId)
            ->with(['area', 'nivelCalificacion'])
            ->get()
            ->groupBy('area_id')
            ->map(function ($califs) {
                return [
                    'area' => $califs->first()->area->nombre,
                    'total' => $califs->count(),
                    'promedio' => $califs->avg('nivel_calificacion_id')
                ];
            });

        return response()->json($calificaciones->values());
    }
}