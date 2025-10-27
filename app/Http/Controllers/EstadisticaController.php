<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Calificacion;
use App\Models\Pregunta;
use App\Models\Area;
use App\Models\Sede;

class EstadisticaController extends Controller
{
    public function index(Request $request)
    {
        try {
            $fechaInicio = $request->get('fecha_inicio');
            $fechaFin = $request->get('fecha_fin');
            $areaId = $request->get('area_id');
            $nivelId = $request->get('nivel_id');
            $sedeId = $request->get('sede_id');

            $estadisticas = [
                'totales' => $this->getTotales($fechaInicio, $fechaFin, $areaId, $nivelId, $sedeId),
                'distribucionNiveles' => $this->getDistribucionNiveles($fechaInicio, $fechaFin, $areaId, $nivelId, $sedeId),
                'calificacionesAreas' => $this->getCalificacionesAreas($fechaInicio, $fechaFin, $areaId, $nivelId, $sedeId),
                'preguntasPopulares' => $this->getPreguntasPopulares($fechaInicio, $fechaFin, $areaId, $nivelId, $sedeId),
                'evolucionTemporal' => $this->getEvolucionTemporal($fechaInicio, $fechaFin, $areaId, $nivelId, $sedeId),
                'topAreas' => $this->getTopAreas($fechaInicio, $fechaFin, $areaId, $nivelId, $sedeId),
                'distribucionTipos' => $this->getDistribucionTipos($fechaInicio, $fechaFin, $areaId, $nivelId, $sedeId)
            ];

            return response()->json($estadisticas);
            
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al cargar estadísticas: ' . $e->getMessage()], 500);
        }
    }

    private function getTotales($fechaInicio, $fechaFin, $areaId, $nivelId, $sedeId)
    {
        $query = Calificacion::query();
        
        $this->aplicarFiltros($query, $fechaInicio, $fechaFin, $areaId, $nivelId, $sedeId);

        $totalCalificaciones = $query->count();
        
        $totalAreas = $query->distinct('area_id')->count('area_id');
        
        $totalPreguntas = DB::table('respuestas_calificacion')
            ->join('calificaciones', 'respuestas_calificacion.calificacion_id', '=', 'calificaciones.id')
            ->when($fechaInicio, function($q) use ($fechaInicio) {
                $q->where('calificaciones.created_at', '>=', $fechaInicio);
            })
            ->when($fechaFin, function($q) use ($fechaFin) {
                $q->where('calificaciones.created_at', '<=', $fechaFin . ' 23:59:59');
            })
            ->when($areaId, function($q) use ($areaId) {
                $q->where('calificaciones.area_id', $areaId);
            })
            ->when($nivelId, function($q) use ($nivelId) {
                $q->where('calificaciones.nivel_calificacion_id', $nivelId);
            })
            ->when($sedeId, function($q) use ($sedeId) {
                $q->where('calificaciones.sede_id', $sedeId);
            })
            ->count();

        // Calcular promedio general de indicadores
        $promedioGeneral = DB::table('respuestas_calificacion')
            ->join('calificaciones', 'respuestas_calificacion.calificacion_id', '=', 'calificaciones.id')
            ->join('preguntas', 'respuestas_calificacion.pregunta_id', '=', 'preguntas.id')
            ->where('preguntas.tipo', 'indicador_0_10')
            ->whereNotNull('respuestas_calificacion.respuesta_texto')
            ->when($fechaInicio, function($q) use ($fechaInicio) {
                $q->where('calificaciones.created_at', '>=', $fechaInicio);
            })
            ->when($fechaFin, function($q) use ($fechaFin) {
                $q->where('calificaciones.created_at', '<=', $fechaFin . ' 23:59:59');
            })
            ->when($areaId, function($q) use ($areaId) {
                $q->where('calificaciones.area_id', $areaId);
            })
            ->when($nivelId, function($q) use ($nivelId) {
                $q->where('calificaciones.nivel_calificacion_id', $nivelId);
            })
            ->when($sedeId, function($q) use ($sedeId) {
                $q->where('calificaciones.sede_id', $sedeId);
            })
            ->avg(DB::raw('CAST(respuestas_calificacion.respuesta_texto AS DECIMAL(10,2))'));

        return [
            'calificaciones' => $totalCalificaciones,
            'areas' => $totalAreas,
            'preguntas' => $totalPreguntas,
            'promedioGeneral' => $promedioGeneral ? round($promedioGeneral, 1) : 0
        ];
    }

    private function getDistribucionNiveles($fechaInicio, $fechaFin, $areaId, $nivelId, $sedeId)
    {
        $query = DB::table('calificaciones')
            ->join('niveles_calificacion', 'calificaciones.nivel_calificacion_id', '=', 'niveles_calificacion.id')
            ->select(
                'niveles_calificacion.nombre as nivel',
                DB::raw('COUNT(*) as cantidad')
            )
            ->groupBy('niveles_calificacion.id', 'niveles_calificacion.nombre');

        $this->aplicarFiltros($query, $fechaInicio, $fechaFin, $areaId, $nivelId, $sedeId);

        return $query->get()->toArray();
    }

    private function getCalificacionesAreas($fechaInicio, $fechaFin, $areaId, $nivelId, $sedeId)
    {
        $query = DB::table('calificaciones')
            ->join('areas', 'calificaciones.area_id', '=', 'areas.id')
            ->select(
                'areas.id',
                'areas.nombre as area_nombre',
                'areas.codigo',
                DB::raw('COUNT(*) as total_respuestas'),
                DB::raw('AVG(nivel_calificacion_id) as promedio')
            )
            ->groupBy('areas.id', 'areas.nombre', 'areas.codigo');

        $this->aplicarFiltros($query, $fechaInicio, $fechaFin, $areaId, $nivelId, $sedeId);

        return $query->get()->map(function($item) {
            $item->promedio = round($item->promedio, 1);
            return $item;
        })->toArray();
    }

    private function getPreguntasPopulares($fechaInicio, $fechaFin, $areaId, $nivelId, $sedeId)
    {
        $query = DB::table('respuestas_calificacion')
            ->join('calificaciones', 'respuestas_calificacion.calificacion_id', '=', 'calificaciones.id')
            ->join('preguntas', 'respuestas_calificacion.pregunta_id', '=', 'preguntas.id')
            ->select(
                'preguntas.id',
                'preguntas.pregunta as pregunta_texto',
                'preguntas.tipo',
                DB::raw('COUNT(*) as total_respuestas')
            )
            ->groupBy('preguntas.id', 'preguntas.pregunta', 'preguntas.tipo')
            ->orderBy('total_respuestas', 'DESC')
            ->limit(10);

        $this->aplicarFiltrosRespuestas($query, $fechaInicio, $fechaFin, $areaId, $nivelId, $sedeId);

        return $query->get()->toArray();
    }

    private function getEvolucionTemporal($fechaInicio, $fechaFin, $areaId, $nivelId, $sedeId)
    {
        $query = DB::table('calificaciones')
            ->select(
                DB::raw('DATE(created_at) as fecha'),
                DB::raw('AVG(nivel_calificacion_id) as promedio'),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('fecha');

        $this->aplicarFiltros($query, $fechaInicio, $fechaFin, $areaId, $nivelId, $sedeId);

        return $query->get()->map(function($item) {
            $item->promedio = round($item->promedio, 1);
            return $item;
        })->toArray();
    }

    private function getTopAreas($fechaInicio, $fechaFin, $areaId, $nivelId, $sedeId)
    {
        $query = DB::table('calificaciones')
            ->join('areas', 'calificaciones.area_id', '=', 'areas.id')
            ->select(
                'areas.id',
                'areas.nombre',
                'areas.codigo',
                DB::raw('AVG(nivel_calificacion_id) as promedio'),
                DB::raw('COUNT(*) as total_respuestas')
            )
            ->groupBy('areas.id', 'areas.nombre', 'areas.codigo')
            ->orderBy('promedio', 'DESC')
            ->limit(5);

        $this->aplicarFiltros($query, $fechaInicio, $fechaFin, $areaId, $nivelId, $sedeId);

        return $query->get()->map(function($item) {
            $item->promedio = round($item->promedio, 1);
            return $item;
        })->toArray();
    }

    private function getDistribucionTipos($fechaInicio, $fechaFin, $areaId, $nivelId, $sedeId)
    {
        $query = DB::table('respuestas_calificacion')
            ->join('calificaciones', 'respuestas_calificacion.calificacion_id', '=', 'calificaciones.id')
            ->join('preguntas', 'respuestas_calificacion.pregunta_id', '=', 'preguntas.id')
            ->select(
                'preguntas.tipo',
                DB::raw('COUNT(*) as cantidad')
            )
            ->groupBy('preguntas.tipo');

        $this->aplicarFiltrosRespuestas($query, $fechaInicio, $fechaFin, $areaId, $nivelId, $sedeId);

        $total = $query->get()->sum('cantidad');

        return $query->get()->map(function($item) use ($total) {
            $item->porcentaje = $total > 0 ? round(($item->cantidad / $total) * 100, 1) : 0;
            return $item;
        })->toArray();
    }

    private function aplicarFiltros($query, $fechaInicio, $fechaFin, $areaId, $nivelId, $sedeId)
    {
        if ($fechaInicio) {
            $query->where('calificaciones.created_at', '>=', $fechaInicio);
        }
        if ($fechaFin) {
            $query->where('calificaciones.created_at', '<=', $fechaFin . ' 23:59:59');
        }
        if ($areaId) {
            $query->where('calificaciones.area_id', $areaId);
        }
        if ($nivelId) {
            $query->where('calificaciones.nivel_calificacion_id', $nivelId);
        }
        if ($sedeId) {
            $query->where('calificaciones.sede_id', $sedeId);
        }
    }

    private function aplicarFiltrosRespuestas($query, $fechaInicio, $fechaFin, $areaId, $nivelId, $sedeId)
    {
        if ($fechaInicio) {
            $query->where('calificaciones.created_at', '>=', $fechaInicio);
        }
        if ($fechaFin) {
            $query->where('calificaciones.created_at', '<=', $fechaFin . ' 23:59:59');
        }
        if ($areaId) {
            $query->where('calificaciones.area_id', $areaId);
        }
        if ($nivelId) {
            $query->where('calificaciones.nivel_calificacion_id', $nivelId);
        }
        if ($sedeId) {
            $query->where('calificaciones.sede_id', $sedeId);
        }
    }

    public function exportar(Request $request)
    {
        // Implementar exportación a Excel
        return response()->json(['message' => 'Exportación implementada']);
    }
}