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
                'nivelIndicador' => $this->getNivelIndicador($fechaInicio, $fechaFin, $areaId, $nivelId, $sedeId, $tipoCalificacion),
                'encuestasPorArea' => $this->getEncuestasPorArea($fechaInicio, $fechaFin, $areaId, $nivelId, $sedeId, $tipoCalificacion),
                'relacionNivelEncuestas' => $this->getRelacionNivelEncuestas($fechaInicio, $fechaFin, $areaId, $nivelId, $sedeId, $tipoCalificacion),
                'indicadoresDimensiones' => $this->getIndicadoresDimensiones($fechaInicio, $fechaFin, $areaId, $nivelId, $sedeId, $tipoCalificacion),
                'distribucionNPS' => $this->getDistribucionNPS($fechaInicio, $fechaFin, $areaId, $nivelId, $sedeId)
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
        
        // Contar áreas evaluadas (áreas que tienen al menos una calificación del tipo filtrado)
        $totalAreas = $query->distinct('area_id')->count('area_id');
        
        // Calcular valor del indicador si hay filtro por tipo
        $valorIndicador = null;
        $preguntasRespondidas = 0;
        
        if ($tipoCalificacion) {
            $valorIndicador = $encuestasRespondidas / Calificacion::query()->count() * 100;
        }
        
        // Calcular promedio general de indicadores (solo si no hay filtro por tipo)
        $promedioGeneral = null;
        if (!$tipoCalificacion) {
           $promedioGeneral = $encuestasRespondidas / $encuestasRespondidas * 100;
           $promedioGeneral = round($promedioGeneral, 1);
        }

        return [
            'encuestasRespondidas' => $encuestasRespondidas,
            'calificaciones' => $encuestasRespondidas, // Mantener compatibilidad
            'areas' => $totalAreas,
            'valorIndicador' => $valorIndicador ? round($valorIndicador, 1) : 0,
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
        // Determinar si es un query builder de Eloquent o DB
        $esEloquent = $query instanceof \Illuminate\Database\Eloquent\Builder;
        $prefijo = $esEloquent ? '' : 'calificaciones.';
        
        if ($fechaInicio) {
            $query->where($prefijo . 'created_at', '>=', $fechaInicio);
        }
        if ($fechaFin) {
            $query->where($prefijo . 'created_at', '<=', $fechaFin . ' 23:59:59');
        }
        if ($areaId) {
            $query->where($prefijo . 'area_id', $areaId);
        }
        if ($nivelId) {
            $query->where($prefijo . 'nivel_calificacion_id', $nivelId);
        }
        if ($sedeId) {
            $query->where($prefijo . 'sede_id', $sedeId);
        }
        if ($tipoCalificacion) {
            $query->where($prefijo . 'tipo_calificacion', $tipoCalificacion);
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

    /**
     * Obtener el nivel del indicador (CSAT, FCR, NPS) para mostrar en semáforo
     */
    private function getNivelIndicador($fechaInicio, $fechaFin, $areaId, $nivelId, $sedeId, $tipoCalificacion = null)
    {
        $niveles = [];
        
        $tipos = $tipoCalificacion ? [$tipoCalificacion] : ['csat', 'fcr', 'nps'];
        
        foreach ($tipos as $tipo) {
            $queryTotal = Calificacion::query()
                ->where('tipo_calificacion', $tipo);
                
            $this->aplicarFiltros($queryTotal, $fechaInicio, $fechaFin, $areaId, $nivelId, $sedeId, $tipo);
            $totalRespuestas = $queryTotal->count();
            
            $valor = $this->calcularIndicador($tipo, $fechaInicio, $fechaFin, $areaId, $nivelId, $sedeId);
            
            // Asegurar que los valores sean numéricos
            $valor = is_numeric($valor) ? (float)$valor : 0.0;
            $totalRespuestas = is_numeric($totalRespuestas) ? (int)$totalRespuestas : 0;
            
            \Log::info("📊 Indicador {$tipo}: valor={$valor}, totalRespuestas={$totalRespuestas}");
            
            $niveles[$tipo] = [
                'valor' => $valor,
                'totalRespuestas' => $totalRespuestas
            ];
        }
        
        return $niveles;
    }

    /**
     * Obtener cantidad de encuestas por área (solo cantidad, no dimensiones)
     */
    private function getEncuestasPorArea($fechaInicio, $fechaFin, $areaId, $nivelId, $sedeId, $tipoCalificacion = null)
    {
        $query = DB::table('calificaciones')
            ->join('areas', 'calificaciones.area_id', '=', 'areas.id')
            ->select(
                'areas.id',
                'areas.nombre as area_nombre',
                'calificaciones.tipo_calificacion',
                DB::raw('COUNT(DISTINCT calificaciones.id) as cantidad_encuestas')
            )
            ->groupBy('areas.id', 'areas.nombre', 'calificaciones.tipo_calificacion');

        $this->aplicarFiltros($query, $fechaInicio, $fechaFin, $areaId, $nivelId, $sedeId, $tipoCalificacion);

        return $query->get()->toArray();
    }

    /**
     * Obtener relación entre nivel de indicador y cantidad de encuestas por día/mes
     */
    private function getRelacionNivelEncuestas($fechaInicio, $fechaFin, $areaId, $nivelId, $sedeId, $tipoCalificacion = null)
    {
        // Determinar si agrupar por día o mes
        $agruparPorMes = false;
        if ($fechaInicio && $fechaFin) {
            $fecha1 = new \DateTime($fechaInicio);
            $fecha2 = new \DateTime($fechaFin);
            $diff = $fecha1->diff($fecha2);
            $agruparPorMes = $diff->days > 31;
        }

        $tipos = $tipoCalificacion ? [$tipoCalificacion] : ['csat', 'fcr', 'nps'];
        $resultados = [];

        foreach ($tipos as $tipo) {
            $query = Calificacion::query()
                ->where('tipo_calificacion', $tipo)
                ->select(
                    $agruparPorMes 
                        ? DB::raw('DATE_FORMAT(created_at, "%Y-%m") as fecha')
                        : DB::raw('DATE(created_at) as fecha'),
                    DB::raw('COUNT(*) as cantidad_encuestas')
                )
                ->groupBy('fecha')
                ->orderBy('fecha');

            if ($fechaInicio) {
                $query->where('created_at', '>=', $fechaInicio);
            }
            if ($fechaFin) {
                $query->where('created_at', '<=', $fechaFin . ' 23:59:59');
            }
            if ($areaId) {
                $query->where('area_id', $areaId);
            }
            if ($sedeId) {
                $query->where('sede_id', $sedeId);
            }

            $datos = $query->get()->map(function($item) use ($tipo, $agruparPorMes, $areaId, $nivelId, $sedeId) {
                // Calcular porcentaje del indicador para esta fecha
                $fechaStart = $item->fecha;
                
                if ($agruparPorMes) {
                    // Si es por mes, calcular para todo el mes
                    $porcentaje = $this->calcularIndicadorPorMes($tipo, $fechaStart, $areaId, $nivelId, $sedeId);
                } else {
                    // Si es por día, calcular para ese día específico
                    $porcentaje = $this->calcularIndicadorPorFecha($tipo, $fechaStart, null, $areaId, $nivelId, $sedeId);
                }
                
                return [
                    'fecha' => $item->fecha,
                    'cantidad_encuestas' => $item->cantidad_encuestas,
                    'porcentaje' => $porcentaje
                ];
            })->toArray();

            $resultados[$tipo] = $datos;
        }

        return $resultados;
    }

    /**
     * Calcular indicador para un día específico
     */
    private function calcularIndicadorPorFecha($tipoCalificacion, $fecha, $fechaFin, $areaId, $nivelId, $sedeId)
    {
        $queryTotal = Calificacion::query()
            ->where('tipo_calificacion', $tipoCalificacion)
            ->whereDate('created_at', '=', $fecha);
            
        if ($areaId) {
            $queryTotal->where('area_id', $areaId);
        }
        if ($sedeId) {
            $queryTotal->where('sede_id', $sedeId);
        }
        
        $totalEncuestas = $queryTotal->count();
        
        if ($totalEncuestas == 0) {
            return 0;
        }
        
        switch ($tipoCalificacion) {
            case 'fcr':
                $querySi = Calificacion::query()
                    ->where('tipo_calificacion', 'fcr')
                    ->where('valor_principal', 0)
                    ->whereDate('created_at', '=', $fecha);
                if ($areaId) $querySi->where('area_id', $areaId);
                if ($sedeId) $querySi->where('sede_id', $sedeId);
                $totalSi = $querySi->count();
                return round(($totalSi / $totalEncuestas) * 100, 1);
                
            case 'csat':
                $querySatisfechos = Calificacion::query()
                    ->where('tipo_calificacion', 'csat')
                    ->whereIn('valor_principal', [3, 4])
                    ->whereDate('created_at', '=', $fecha);
                if ($areaId) $querySatisfechos->where('area_id', $areaId);
                if ($sedeId) $querySatisfechos->where('sede_id', $sedeId);
                $totalSatisfechos = $querySatisfechos->count();
                return round(($totalSatisfechos / $totalEncuestas) * 100, 1);
                
            case 'nps':
                $queryPromotores = Calificacion::query()
                    ->where('tipo_calificacion', 'nps')
                    ->whereBetween('valor_principal', [9, 10])
                    ->whereDate('created_at', '=', $fecha);
                if ($areaId) $queryPromotores->where('area_id', $areaId);
                if ($sedeId) $queryPromotores->where('sede_id', $sedeId);
                $totalPromotores = $queryPromotores->count();
                return round(($totalPromotores / $totalEncuestas) * 100, 1);
                
            default:
                return 0;
        }
    }

    /**
     * Calcular indicador para un mes específico (formato: YYYY-MM)
     */
    private function calcularIndicadorPorMes($tipoCalificacion, $mes, $areaId, $nivelId, $sedeId)
    {
        $queryTotal = Calificacion::query()
            ->where('tipo_calificacion', $tipoCalificacion)
            ->whereRaw('DATE_FORMAT(created_at, "%Y-%m") = ?', [$mes]);
            
        if ($areaId) {
            $queryTotal->where('area_id', $areaId);
        }
        if ($sedeId) {
            $queryTotal->where('sede_id', $sedeId);
        }
        
        $totalEncuestas = $queryTotal->count();
        
        if ($totalEncuestas == 0) {
            return 0;
        }
        
        switch ($tipoCalificacion) {
            case 'fcr':
                $querySi = Calificacion::query()
                    ->where('tipo_calificacion', 'fcr')
                    ->where('valor_principal', 0)
                    ->whereRaw('DATE_FORMAT(created_at, "%Y-%m") = ?', [$mes]);
                if ($areaId) $querySi->where('area_id', $areaId);
                if ($sedeId) $querySi->where('sede_id', $sedeId);
                $totalSi = $querySi->count();
                return round(($totalSi / $totalEncuestas) * 100, 1);
                
            case 'csat':
                $querySatisfechos = Calificacion::query()
                    ->where('tipo_calificacion', 'csat')
                    ->whereIn('valor_principal', [3, 4])
                    ->whereRaw('DATE_FORMAT(created_at, "%Y-%m") = ?', [$mes]);
                if ($areaId) $querySatisfechos->where('area_id', $areaId);
                if ($sedeId) $querySatisfechos->where('sede_id', $sedeId);
                $totalSatisfechos = $querySatisfechos->count();
                return round(($totalSatisfechos / $totalEncuestas) * 100, 1);
                
            case 'nps':
                $queryPromotores = Calificacion::query()
                    ->where('tipo_calificacion', 'nps')
                    ->whereBetween('valor_principal', [9, 10])
                    ->whereRaw('DATE_FORMAT(created_at, "%Y-%m") = ?', [$mes]);
                if ($areaId) $queryPromotores->where('area_id', $areaId);
                if ($sedeId) $queryPromotores->where('sede_id', $sedeId);
                $totalPromotores = $queryPromotores->count();
                return round(($totalPromotores / $totalEncuestas) * 100, 1);
                
            default:
                return 0;
        }
    }

    /**
     * Obtener indicadores y sus dimensiones (subpreguntas)
     */
    private function getIndicadoresDimensiones($fechaInicio, $fechaFin, $areaId, $nivelId, $sedeId, $tipoCalificacion = null)
    {
        $tipos = $tipoCalificacion ? [$tipoCalificacion] : ['csat', 'fcr', 'nps'];
        $resultados = [];

        foreach ($tipos as $tipo) {
            // Obtener todas las subpreguntas relacionadas con este tipo de indicador
            $subpreguntas = DB::table('subpreguntas')
                ->join('opciones_pregunta', 'subpreguntas.opcion_pregunta_id', '=', 'opciones_pregunta.id')
                ->join('preguntas', 'opciones_pregunta.pregunta_id', '=', 'preguntas.id')
                ->where('preguntas.tipo_pregunta', $tipo)
                ->where('subpreguntas.is_active', true)
                ->select(
                    'subpreguntas.id',
                    'subpreguntas.pregunta_texto as dimension',
                    'subpreguntas.tipo as tipo_dimension'
                )
                ->distinct()
                ->get();

            $dimensionesData = [];
            
            foreach ($subpreguntas as $subpregunta) {
                // Contar respuestas por dimensión
                $query = DB::table('respuestas_subpreguntas')
                    ->join('calificaciones', 'respuestas_subpreguntas.calificacion_id', '=', 'calificaciones.id')
                    ->where('respuestas_subpreguntas.subpregunta_id', $subpregunta->id)
                    ->where('calificaciones.tipo_calificacion', $tipo);

                if ($fechaInicio) {
                    $query->where('calificaciones.created_at', '>=', $fechaInicio);
                }
                if ($fechaFin) {
                    $query->where('calificaciones.created_at', '<=', $fechaFin . ' 23:59:59');
                }
                if ($areaId) {
                    $query->where('calificaciones.area_id', $areaId);
                }
                if ($sedeId) {
                    $query->where('calificaciones.sede_id', $sedeId);
                }

                // Si es opción única, agrupar por opción seleccionada
                if ($subpregunta->tipo_dimension == 'opcion_unica') {
                    $respuestas = $query
                        ->select(
                            'respuestas_subpreguntas.opcion_seleccionada',
                            DB::raw('COUNT(*) as cantidad')
                        )
                        ->groupBy('respuestas_subpreguntas.opcion_seleccionada')
                        ->get();

                    $dimensionesData[] = [
                        'dimension' => $subpregunta->dimension,
                        'tipo' => $subpregunta->tipo_dimension,
                        'respuestas' => $respuestas->toArray()
                    ];
                } else {
                    // Para otros tipos, solo contar total
                    $total = $query->count();
                    $dimensionesData[] = [
                        'dimension' => $subpregunta->dimension,
                        'tipo' => $subpregunta->tipo_dimension,
                        'total' => $total
                    ];
                }
            }

            $resultados[$tipo] = $dimensionesData;
        }

        return $resultados;
    }

    /**
     * Obtener distribución de NPS (Promotores, Pasivos, Detractores)
     */
    private function getDistribucionNPS($fechaInicio, $fechaFin, $areaId, $nivelId, $sedeId)
    {
        $query = Calificacion::query()
            ->where('tipo_calificacion', 'nps');

        if ($fechaInicio) {
            $query->where('created_at', '>=', $fechaInicio);
        }
        if ($fechaFin) {
            $query->where('created_at', '<=', $fechaFin . ' 23:59:59');
        }
        if ($areaId) {
            $query->where('area_id', $areaId);
        }
        if ($sedeId) {
            $query->where('sede_id', $sedeId);
        }

        $total = $query->count();
        
        $promotores = (clone $query)->whereBetween('valor_principal', [9, 10])->count();
        $pasivos = (clone $query)->whereBetween('valor_principal', [7, 8])->count();
        $detractores = (clone $query)->whereBetween('valor_principal', [1, 6])->count();

        return [
            'promotores' => $promotores,
            'pasivos' => $pasivos,
            'detractores' => $detractores,
            'total' => $total
        ];
    }
}