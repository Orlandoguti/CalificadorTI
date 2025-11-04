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
            $tipoCalificacion = $request->get('tipo_calificacion'); // FCR, CSAT, NPS

            $estadisticas = [
                'totales' => $this->getTotales($fechaInicio, $fechaFin, $areaId, $nivelId, $sedeId, $tipoCalificacion),
                'distribucionNiveles' => $this->getDistribucionNiveles($fechaInicio, $fechaFin, $areaId, $nivelId, $sedeId, $tipoCalificacion),
                'calificacionesAreas' => $this->getCalificacionesAreas($fechaInicio, $fechaFin, $areaId, $nivelId, $sedeId, $tipoCalificacion),
                'preguntasPopulares' => $this->getPreguntasPopulares($fechaInicio, $fechaFin, $areaId, $nivelId, $sedeId, $tipoCalificacion),
                'evolucionTemporal' => $this->getEvolucionTemporal($fechaInicio, $fechaFin, $areaId, $nivelId, $sedeId, $tipoCalificacion),
                'topAreas' => $this->getTopAreas($fechaInicio, $fechaFin, $areaId, $nivelId, $sedeId, $tipoCalificacion),
                'distribucionTipos' => $this->getDistribucionTipos($fechaInicio, $fechaFin, $areaId, $nivelId, $sedeId, $tipoCalificacion)
            ];

            return response()->json($estadisticas);
            
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al cargar estadísticas: ' . $e->getMessage()], 500);
        }
    }

    private function getTotales($fechaInicio, $fechaFin, $areaId, $nivelId, $sedeId, $tipoCalificacion = null)
    {
        $query = Calificacion::query();
        
        // Aplicar filtros incluyendo tipo_calificacion
        $this->aplicarFiltros($query, $fechaInicio, $fechaFin, $areaId, $nivelId, $sedeId, $tipoCalificacion);

        // Si hay filtro por tipo_calificacion, contar solo encuestas de ese tipo
        // Si no hay filtro, contar todas las calificaciones (encuestas)
        $encuestasRespondidas = $query->count();
        
        // Contar áreas evaluadas
        $totalAreas = $query->distinct('area_id')->count('area_id');
        
        // Calcular valor del indicador si hay filtro por tipo
        $valorIndicador = null;
        
        if ($tipoCalificacion) {
            $valorIndicador = $this->calcularIndicador($tipoCalificacion, $fechaInicio, $fechaFin, $areaId, $nivelId, $sedeId);
        }
        
        // Calcular promedio general de indicadores (solo si no hay filtro por tipo)
        $promedioGeneral = null;
        if (!$tipoCalificacion) {
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
        }

        return [
            'encuestasRespondidas' => $encuestasRespondidas,
            'calificaciones' => $encuestasRespondidas, // Mantener compatibilidad
            'areas' => $totalAreas,
            'valorIndicador' => $valorIndicador,
            'promedioGeneral' => $promedioGeneral ? round($promedioGeneral, 1) : 0
        ];
    }
    
    /**
     * Calcular el valor del indicador según su tipo
     * FCR: Total personas que respondieron SÍ / Total encuestas respondidas * 100
     * CSAT: (Muy satisfechos + Satisfechos) / Total encuestas respondidas * 100
     * NPS: Respuestas entre 9 y 10 / Total encuestas respondidas * 100
     */
    private function calcularIndicador($tipoCalificacion, $fechaInicio, $fechaFin, $areaId, $nivelId, $sedeId)
    {
        // Query base para contar total de encuestas del tipo
        $queryTotal = Calificacion::query()
            ->where('tipo_calificacion', $tipoCalificacion);
            
        $this->aplicarFiltros($queryTotal, $fechaInicio, $fechaFin, $areaId, $nivelId, $sedeId, $tipoCalificacion);
        $totalEncuestas = $queryTotal->count();
        
        if ($totalEncuestas == 0) {
            return 0;
        }
        
        switch ($tipoCalificacion) {
            case 'fcr':
                // FCR: Total personas que respondieron SÍ (valor_principal = 0) / Total encuestas * 100
                $querySi = Calificacion::query()
                    ->where('tipo_calificacion', 'fcr')
                    ->where('valor_principal', 0); // 0 = SÍ, 1 = No
                    
                $this->aplicarFiltros($querySi, $fechaInicio, $fechaFin, $areaId, $nivelId, $sedeId, $tipoCalificacion);
                $totalSi = $querySi->count();
                
                return round(($totalSi / $totalEncuestas) * 100, 1);
                
            case 'csat':
                // CSAT: (Muy satisfechos + Satisfechos) / Total encuestas * 100
                // valor_principal 3 = Satisfecho, 4 = Muy satisfecho
                $querySatisfechos = Calificacion::query()
                    ->where('tipo_calificacion', 'csat')
                    ->whereIn('valor_principal', [3, 4]);
                    
                $this->aplicarFiltros($querySatisfechos, $fechaInicio, $fechaFin, $areaId, $nivelId, $sedeId, $tipoCalificacion);
                $totalSatisfechos = $querySatisfechos->count();
                
                return round(($totalSatisfechos / $totalEncuestas) * 100, 1);
                
            case 'nps':
                // NPS: Respuestas entre 9 y 10 / Total encuestas * 100
                $queryPromotores = Calificacion::query()
                    ->where('tipo_calificacion', 'nps')
                    ->whereBetween('valor_principal', [9, 10]);
                    
                $this->aplicarFiltros($queryPromotores, $fechaInicio, $fechaFin, $areaId, $nivelId, $sedeId, $tipoCalificacion);
                $totalPromotores = $queryPromotores->count();
                
                return round(($totalPromotores / $totalEncuestas) * 100, 1);
                
            default:
                return 0;
        }
    }

    private function getDistribucionNiveles($fechaInicio, $fechaFin, $areaId, $nivelId, $sedeId, $tipoCalificacion = null)
    {
        $query = DB::table('calificaciones')
            ->join('niveles_calificacion', 'calificaciones.nivel_calificacion_id', '=', 'niveles_calificacion.id')
            ->select(
                'niveles_calificacion.nombre as nivel',
                DB::raw('COUNT(*) as cantidad')
            )
            ->groupBy('niveles_calificacion.id', 'niveles_calificacion.nombre');

        $this->aplicarFiltros($query, $fechaInicio, $fechaFin, $areaId, $nivelId, $sedeId, $tipoCalificacion);

        return $query->get()->toArray();
    }

    private function getCalificacionesAreas($fechaInicio, $fechaFin, $areaId, $nivelId, $sedeId, $tipoCalificacion = null)
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

        $this->aplicarFiltros($query, $fechaInicio, $fechaFin, $areaId, $nivelId, $sedeId, $tipoCalificacion);

        return $query->get()->map(function($item) {
            $item->promedio = round($item->promedio, 1);
            return $item;
        })->toArray();
    }

    private function getPreguntasPopulares($fechaInicio, $fechaFin, $areaId, $nivelId, $sedeId, $tipoCalificacion = null)
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

        $this->aplicarFiltrosRespuestas($query, $fechaInicio, $fechaFin, $areaId, $nivelId, $sedeId, $tipoCalificacion);

        return $query->get()->toArray();
    }

    private function getEvolucionTemporal($fechaInicio, $fechaFin, $areaId, $nivelId, $sedeId, $tipoCalificacion = null)
    {
        $query = DB::table('calificaciones')
            ->select(
                DB::raw('DATE(created_at) as fecha'),
                DB::raw('AVG(nivel_calificacion_id) as promedio'),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('fecha');

        $this->aplicarFiltros($query, $fechaInicio, $fechaFin, $areaId, $nivelId, $sedeId, $tipoCalificacion);

        return $query->get()->map(function($item) {
            $item->promedio = round($item->promedio, 1);
            return $item;
        })->toArray();
    }

    private function getTopAreas($fechaInicio, $fechaFin, $areaId, $nivelId, $sedeId, $tipoCalificacion = null)
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

        $this->aplicarFiltros($query, $fechaInicio, $fechaFin, $areaId, $nivelId, $sedeId, $tipoCalificacion);

        return $query->get()->map(function($item) {
            $item->promedio = round($item->promedio, 1);
            return $item;
        })->toArray();
    }

    private function getDistribucionTipos($fechaInicio, $fechaFin, $areaId, $nivelId, $sedeId, $tipoCalificacion = null)
    {
        $query = DB::table('respuestas_calificacion')
            ->join('calificaciones', 'respuestas_calificacion.calificacion_id', '=', 'calificaciones.id')
            ->join('preguntas', 'respuestas_calificacion.pregunta_id', '=', 'preguntas.id')
            ->select(
                'preguntas.tipo',
                DB::raw('COUNT(*) as cantidad')
            )
            ->groupBy('preguntas.tipo');

        $this->aplicarFiltrosRespuestas($query, $fechaInicio, $fechaFin, $areaId, $nivelId, $sedeId, $tipoCalificacion);

        $total = $query->get()->sum('cantidad');

        return $query->get()->map(function($item) use ($total) {
            $item->porcentaje = $total > 0 ? round(($item->cantidad / $total) * 100, 1) : 0;
            return $item;
        })->toArray();
    }

    private function aplicarFiltros($query, $fechaInicio, $fechaFin, $areaId, $nivelId, $sedeId, $tipoCalificacion = null)
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
        if ($tipoCalificacion) {
            $query->where('calificaciones.tipo_calificacion', $tipoCalificacion);
        }
    }

    private function aplicarFiltrosRespuestas($query, $fechaInicio, $fechaFin, $areaId, $nivelId, $sedeId, $tipoCalificacion = null)
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
        if ($tipoCalificacion) {
            $query->where('calificaciones.tipo_calificacion', $tipoCalificacion);
        }
    }

    public function exportar(Request $request)
    {
        // Implementar exportación a Excel
        return response()->json(['message' => 'Exportación implementada']);
    }
}