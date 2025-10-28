<?php

namespace App\Http\Controllers;

use App\Models\Calificacion;
use App\Models\Area;
use App\Models\Pregunta;
use App\Models\User;
use App\Models\Sede;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    public function getStats(Request $request)
    {
        $sedeId = $request->get('sede_id');
        
        // Consultas base SIN filtros iniciales
        $calificacionesQuery = Calificacion::query();
        $areasQuery = Area::where('is_active', true);
        $preguntasQuery = Pregunta::where('is_active', true);
        $usuariosQuery = User::query();

        // Filtrar por sede si se proporciona
        if ($sedeId && $sedeId !== 'todas') {
            // Calificaciones de la sede específica
            $calificacionesQuery->where('sede_id', $sedeId);
            
            // Áreas de esta sede
            $areasQuery->where('sede_id', $sedeId);
            
            // 🔥 NOTA: Preguntas NO tienen sede_id, son genéricas (CSAT/NPS/FCR)
            // Si necesitas filtrar preguntas por sede, hay que hacerlo a través de area_pregunta
            
            // Usuarios asignados a esta sede
            $usuariosQuery->where('sede_id', $sedeId);
        }

        $stats = [
            'totalCalificaciones' => $calificacionesQuery->count(),
            'totalAreas' => $areasQuery->count(),
            'totalPreguntas' => $preguntasQuery->count(),
            'promedioCalificacion' => $calificacionesQuery->avg('nivel_calificacion_id') ?? 0,
            'totalUsuarios' => $usuariosQuery->count(),
            'sede' => $sedeId ? Sede::find($sedeId) : null
        ];

        return response()->json($stats);
    }

    /**
     * Obtener estadísticas detalladas por área para una sede
     */
    public function getStatsPorArea(Request $request)
    {
        $sedeId = $request->get('sede_id');
        
        $query = Area::withCount(['calificaciones' => function($q) use ($sedeId) {
            if ($sedeId && $sedeId !== 'todas') {
                $q->where('sede_id', $sedeId);
            }
        }])
        ->withAvg(['calificaciones' => function($q) use ($sedeId) {
            if ($sedeId && $sedeId !== 'todas') {
                $q->where('sede_id', $sedeId);
            }
        }], 'nivel_calificacion_id')
        ->where('is_active', true);

        // FILTRAR ÁREAS POR SEDE
        if ($sedeId && $sedeId !== 'todas') {
            $query->where('sede_id', $sedeId);
        }

        $areas = $query->get()->map(function($area) {
            return [
                'id' => $area->id,
                'nombre' => $area->nombre,
                'codigo' => $area->codigo,
                'total_calificaciones' => $area->calificaciones_count,
                'promedio_calificacion' => round($area->calificaciones_avg_nivel_calificacion_id, 2) ?? 0
            ];
        });

        return response()->json($areas);
    }

    /**
     * Obtener actividad reciente por sede
     */
   public function getActividadReciente(Request $request)
{
    try {
        $sedeId = $request->get('sede_id');
        
        $query = Calificacion::with(['area', 'user', 'sede'])
            ->orderBy('created_at', 'desc')
            ->limit(10);

        if ($sedeId && $sedeId !== 'todas') {
            $query->where('sede_id', $sedeId);
        }

        $actividad = $query->get()->map(function($calificacion) {
            // ✅ MANEJO SEGURO DE RELACIONES
            $nombreArea = $calificacion->area ? $calificacion->area->nombre : 'Área no encontrada';
            $nombreSede = $calificacion->sede ? $calificacion->sede->nombre : 'Sede no encontrada';
            $nombreUsuario = $calificacion->user ? $calificacion->user->name : 'Usuario anónimo';
            
            return [
                'id' => $calificacion->id,
                'tipo' => 'calificacion',
                'mensaje' => "Usuario {$nombreUsuario} calificó {$nombreArea}",
                'sede' => $nombreSede,
                'fecha' => $calificacion->created_at->diffForHumans(),
                'icono' => 'fas fa-star'
            ];
        });

        return response()->json($actividad);
        
    } catch (\Exception $e) {
        Log::error('Error en getActividadReciente: ' . $e->getMessage());
        return response()->json(['error' => 'Error al cargar actividad reciente'], 500);
    }
}
}