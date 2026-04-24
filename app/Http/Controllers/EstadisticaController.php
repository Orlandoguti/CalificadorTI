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
            // Normalizar fechas vacías a null
            $fechaInicio = !empty($fechaInicio) ? $fechaInicio : null;
            $fechaFin = !empty($fechaFin) ? $fechaFin : null;
            
            $areaId = $request->get('area_id') ? (int)$request->get('area_id') : null;
            $nivelId = $request->get('nivel_id') ? (int)$request->get('nivel_id') : null;
            $sedeId = $request->get('sede_id') ? (int)$request->get('sede_id') : null;
            $tipoCalificacion = $request->get('tipo_calificacion'); // FCR, CSAT, NPS

            // 🔥 IMPORTANTE: Si hay filtro de sede, priorizar sede sobre área
            // Si hay filtro de área pero NO hay filtro de sede, obtener el nombre del área y filtrar por nombre (nacional)
            // Cuando hay filtro de área sin sede, el usuario quiere ver datos nacionales de todas las áreas con ese nombre
            $areaNombre = null;
            if ($areaId && !$sedeId) {
                // Solo si hay área pero NO hay sede, filtrar por nombre de área (nacional)
                $area = Area::find($areaId);
                if ($area) {
                    $areaNombre = $area->nombre;
                    // NO usar areaId, usar areaNombre para filtrar por nombre (nacional)
                    $areaId = null;
                }
            } elseif ($areaId && $sedeId) {
                // Si hay ambos, priorizar sede (filtrar por área específica dentro de la sede)
                // Mantener areaId para filtrar por área específica dentro de la sede
                $areaNombre = null;
            }

            \Log::info("Estadisticas - Filtros recibidos: fechaInicio={$fechaInicio}, fechaFin={$fechaFin}, areaId={$areaId}, areaNombre={$areaNombre}, nivelId={$nivelId}, sedeId={$sedeId}, tipoCalificacion={$tipoCalificacion}");
            \Log::info("Estadisticas - Aplicando filtro de sede: " . ($sedeId && !$areaId && !$areaNombre ? "SÍ (sedeId={$sedeId})" : "NO"));

            $estadisticas = [
                'totales' => $this->getTotales($fechaInicio, $fechaFin, $areaId, $areaNombre, $nivelId, $sedeId, $tipoCalificacion),
                'nivelIndicador' => $this->getNivelIndicador($fechaInicio, $fechaFin, $areaId, $areaNombre, $nivelId, $sedeId, $tipoCalificacion),
                'encuestasPorArea' => $this->getEncuestasPorArea($fechaInicio, $fechaFin, $areaId, $areaNombre, $nivelId, $sedeId, $tipoCalificacion),
                'relacionNivelEncuestas' => $this->getRelacionNivelEncuestas($fechaInicio, $fechaFin, $areaId, $areaNombre, $nivelId, $sedeId, $tipoCalificacion),
                'indicadoresDimensiones' => $this->getIndicadoresDimensiones($fechaInicio, $fechaFin, $areaId, $areaNombre, $nivelId, $sedeId, $tipoCalificacion),
                'distribucionNPS' => $this->getDistribucionNPS($fechaInicio, $fechaFin, $areaId, $areaNombre, $nivelId, $sedeId),
                'csatDimensionesPorNivel' => $this->getCSATDimensionesPorNivel($fechaInicio, $fechaFin, $areaId, $areaNombre, $sedeId),
                'rankingAreas' => $this->getRankingAreas($fechaInicio, $fechaFin, $sedeId),
                'rankingAreasInsatisfaccion' => $this->getRankingAreasInsatisfaccion($fechaInicio, $fechaFin, $sedeId),
                'textosMasAnotados' => $this->getTextosMasAnotados($fechaInicio, $fechaFin, $areaId, $areaNombre, $sedeId)
            ];

            return response()->json($estadisticas);
            
        } catch (\Exception $e) {
            \Log::error("Error en estadisticas: " . $e->getMessage());
            \Log::error("Stack trace: " . $e->getTraceAsString());
            return response()->json(['error' => 'Error al cargar estadísticas: ' . $e->getMessage()], 500);
        }
    }

    private function getTotales($fechaInicio, $fechaFin, $areaId, $areaNombre, $nivelId, $sedeId, $tipoCalificacion = null)
    {
        $query = Calificacion::query();
        
        // Aplicar filtros incluyendo tipo_calificacion
        $this->aplicarFiltros($query, $fechaInicio, $fechaFin, $areaId, $areaNombre, $nivelId, $sedeId, $tipoCalificacion);

        // Si hay filtro por tipo_calificacion, contar solo encuestas de ese tipo
        // Si no hay filtro, contar solo CSAT y FCR (excluir NPS para consistencia con la tabla)
        if (!$tipoCalificacion) {
            // Si hay join con areas, usar prefijo completo
            $prefijoTipo = ($areaNombre) ? 'calificaciones.' : '';
            $query->whereIn($prefijoTipo . 'tipo_calificacion', ['csat', 'fcr']);
        }
        
        // Log para debug
        \Log::info("📊 getTotales - SQL: " . $query->toSql());
        \Log::info("📊 getTotales - Bindings: " . json_encode($query->getBindings()));
        
        $encuestasRespondidas = $query->count();
        
        \Log::info("📊 getTotales - encuestasRespondidas: {$encuestasRespondidas}");
        
        // Contar áreas evaluadas (áreas que tienen al menos una calificación del tipo filtrado)
        // Necesitamos recrear el query porque count() ya lo ejecutó
        $queryAreas = Calificacion::query();
        $this->aplicarFiltros($queryAreas, $fechaInicio, $fechaFin, $areaId, $areaNombre, $nivelId, $sedeId, $tipoCalificacion);
        $totalAreas = $encuestasRespondidas > 0 ? $queryAreas->distinct('area_id')->count('area_id') : 0;
        
        // Calcular valor del indicador si hay filtro por tipo
        $valorIndicador = null;
        $preguntasRespondidas = 0;
        
        if ($tipoCalificacion && $encuestasRespondidas > 0) {
            $totalGeneral = Calificacion::query()->count();
            if ($totalGeneral > 0) {
                $valorIndicador = ($encuestasRespondidas / $totalGeneral) * 100;
            } else {
                $valorIndicador = 0;
            }
        }
        
        // Calcular promedio general de indicadores (solo si no hay filtro por tipo)
        $promedioGeneral = null;
        if (!$tipoCalificacion) {
           // Esto parece ser un error - dividir por sí mismo siempre da 100%
           // Probablemente debería ser otra lógica, pero por ahora retornamos 100 si hay datos
           if ($encuestasRespondidas > 0) {
               $promedioGeneral = 100; // Si hay encuestas, el promedio es 100%
           } else {
               $promedioGeneral = 0;
           }
        }

        return [
            'encuestasRespondidas' => $encuestasRespondidas,
            'calificaciones' => $encuestasRespondidas, // Mantener compatibilidad
            'areas' => $totalAreas,
            'valorIndicador' => $valorIndicador !== null ? round($valorIndicador, 1) : 0,
            'promedioGeneral' => $promedioGeneral !== null ? round($promedioGeneral, 1) : 0
        ];
    }
    
    /**
     * Calcular el valor del indicador según su tipo
     * FCR: Total personas que respondieron SÍ / Total encuestas respondidas * 100
     * CSAT: (Muy satisfechos + Satisfechos) / Total encuestas respondidas * 100
     * NPS: Respuestas entre 9 y 10 / Total encuestas respondidas * 100
     */
    private function calcularIndicador($tipoCalificacion, $fechaInicio, $fechaFin, $areaId, $areaNombre, $nivelId, $sedeId) 
    {
        // Query base para contar total de encuestas del tipo
        $queryTotal = Calificacion::query()
            ->where('tipo_calificacion', $tipoCalificacion);
            
        $this->aplicarFiltros($queryTotal, $fechaInicio, $fechaFin, $areaId, $areaNombre, $nivelId, $sedeId, $tipoCalificacion);
        $totalEncuestas = $queryTotal->count();
        
        if ($totalEncuestas == 0) {
            return 0;
        }
        
        switch ($tipoCalificacion) {
            case 'fcr':
                // FCR: Total personas que respondieron SÍ (valor_principal = 0) / Total encuestas * 100
                $querySi = Calificacion::query()
                ->where('tipo_calificacion', 'fcr')
                ->where(function ($q) {
                    $q->where('valor_principal', 0)
                      ->orWhereNull('valor_principal');
                });
                    
                $this->aplicarFiltros($querySi, $fechaInicio, $fechaFin, $areaId, $areaNombre, $nivelId, $sedeId, $tipoCalificacion);
                $totalSi = $querySi->count();
                
                return round(($totalSi / $totalEncuestas) * 100, 1);
                
            case 'csat':
                // CSAT: (Muy satisfechos + Satisfechos) / Total encuestas * 100
                // nivel_calificacion_id 1 = Muy Satisfecho, 2 = Satisfecho
                $querySatisfechos = Calificacion::query()
                    ->where('tipo_calificacion', 'csat')
                    ->whereIn('nivel_calificacion_id', [1, 2]);
                    
                $this->aplicarFiltros($querySatisfechos, $fechaInicio, $fechaFin, $areaId, $areaNombre, $nivelId, $sedeId, $tipoCalificacion);
                $totalSatisfechos = $querySatisfechos->count();
                
                return round(($totalSatisfechos / $totalEncuestas) * 100, 1);
                
            case 'nps':
                // NPS: Respuestas entre 9 y 10 / Total encuestas * 100
                $queryPromotores = Calificacion::query()
                    ->where('tipo_calificacion', 'nps')
                    ->whereBetween('valor_principal', [9, 10]);
                    
                $this->aplicarFiltros($queryPromotores, $fechaInicio, $fechaFin, $areaId, $areaNombre, $nivelId, $sedeId, $tipoCalificacion);
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

        $this->aplicarFiltros($query, $fechaInicio, $fechaFin, $areaId, $areaNombre ?? null, $nivelId, $sedeId, $tipoCalificacion);

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

        $this->aplicarFiltros($query, $fechaInicio, $fechaFin, $areaId, $areaNombre ?? null, $nivelId, $sedeId, $tipoCalificacion);

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

        $this->aplicarFiltrosRespuestas($query, $fechaInicio, $fechaFin, $areaId, $areaNombre ?? null, $nivelId, $sedeId, $tipoCalificacion);

        return $query->get()->toArray();
    }

    private function getEvolucionTemporal($fechaInicio, $fechaFin, $areaId, $nivelId, $sedeId, $tipoCalificacion = null)
    {
        $query = DB::table('calificaciones')
            ->select(
                DB::raw('DATE(calificaciones.created_at) as fecha'),
                DB::raw('AVG(calificaciones.nivel_calificacion_id) as promedio'),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy(DB::raw('DATE(calificaciones.created_at)'))
            ->orderBy('fecha');

        $this->aplicarFiltros($query, $fechaInicio, $fechaFin, $areaId, $areaNombre ?? null, $nivelId, $sedeId, $tipoCalificacion);

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

        $this->aplicarFiltros($query, $fechaInicio, $fechaFin, $areaId, $areaNombre ?? null, $nivelId, $sedeId, $tipoCalificacion);

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

        $this->aplicarFiltrosRespuestas($query, $fechaInicio, $fechaFin, $areaId, $areaNombre ?? null, $nivelId, $sedeId, $tipoCalificacion);

        $total = $query->get()->sum('cantidad');

        return $query->get()->map(function($item) use ($total) {
            $item->porcentaje = $total > 0 ? round(($item->cantidad / $total) * 100, 1) : 0;
            return $item;
        })->toArray();
    }

    private function aplicarFiltros($query, $fechaInicio, $fechaFin, $areaId, $areaNombre, $nivelId, $sedeId, $tipoCalificacion = null, $yaTieneJoinAreas = false)
    {
        // Determinar si es un query builder de Eloquent o DB
        $esEloquent = $query instanceof \Illuminate\Database\Eloquent\Builder;
        
        // 🔥 IMPORTANTE: Si hay join con areas (ya existe o se va a crear), usar prefijo completo para todos los campos
        // para evitar ambigüedad ya que ambas tablas pueden tener columnas con el mismo nombre
        $tendraJoinAreas = ($areaNombre || $yaTieneJoinAreas);
        $prefijo = ($esEloquent && !$tendraJoinAreas) ? '' : 'calificaciones.';
        
        if ($fechaInicio) {
            $query->where($prefijo . 'created_at', '>=', $fechaInicio);
        }
        if ($fechaFin) {
            $query->where($prefijo . 'created_at', '<=', $fechaFin . ' 23:59:59');
        }
        
        // 🔥 IMPORTANTE: Si hay filtro por nombre de área, filtrar por nombre (nacional - todas las sedes)
        // Si hay filtro por areaId, filtrar por ID específico
        if ($areaNombre) {
            if ($yaTieneJoinAreas) {
                // Si ya hay un join con areas, solo agregar el where
                $query->where('areas.nombre', $areaNombre);
            } else {
                // Si no hay join, hacer el join y agregar el where
                $query->join('areas', $prefijo . 'area_id', '=', 'areas.id')
                      ->where('areas.nombre', $areaNombre);
            }
        } elseif ($areaId) {
            $query->where($prefijo . 'area_id', $areaId);
        }
        
        if ($nivelId) {
            $query->where($prefijo . 'nivel_calificacion_id', $nivelId);
        }
        // 🔥 IMPORTANTE: Aplicar filtro de sede según la lógica:
        // - Si hay sede y área (por ID): aplicar ambos filtros (área específica dentro de la sede)
        // - Si hay sede pero NO área: aplicar solo filtro de sede (todos los datos de la sede)
        // - Si hay área (por nombre) pero NO sede: NO aplicar filtro de sede (mostrar nacional)
        if ($sedeId) {
            if ($areaNombre) {
                // Si hay filtro por nombre de área, NO aplicar filtro de sede (mostrar nacional)
                \Log::info("🔍 NO aplicando filtro de sede - hay filtro por nombre de área (nacional)");
            } else {
                // Aplicar filtro de sede (ya sea con o sin área por ID)
                \Log::info("🔍 Aplicando filtro de sede en aplicarFiltros: {$prefijo}sede_id = {$sedeId}");
                $query->where($prefijo . 'sede_id', $sedeId);
            }
        } else {
            \Log::info("🔍 NO aplicando filtro de sede - no hay sedeId");
        }
        if ($tipoCalificacion) {
            $query->where($prefijo . 'tipo_calificacion', $tipoCalificacion);
        }
    }

    private function aplicarFiltrosRespuestas($query, $fechaInicio, $fechaFin, $areaId, $areaNombre, $nivelId, $sedeId, $tipoCalificacion = null)
    {
        if ($fechaInicio) {
            $query->where('calificaciones.created_at', '>=', $fechaInicio);
        }
        if ($fechaFin) {
            $query->where('calificaciones.created_at', '<=', $fechaFin . ' 23:59:59');
        }
        
        // 🔥 IMPORTANTE: Si hay filtro por nombre de área, filtrar por nombre (nacional - todas las sedes)
        // Si hay filtro por areaId, filtrar por ID específico
        if ($areaNombre) {
            // Filtrar por nombre de área (nacional - todas las sedes con ese nombre)
            $query->join('areas', 'calificaciones.area_id', '=', 'areas.id')
                  ->where('areas.nombre', $areaNombre);
        } elseif ($areaId) {
            $query->where('calificaciones.area_id', $areaId);
        }
        
        if ($nivelId) {
            $query->where('calificaciones.nivel_calificacion_id', $nivelId);
        }
        // 🔥 IMPORTANTE: Si hay filtro de área (por nombre o ID), NO aplicar filtro de sede (mostrar nacional)
        if ($sedeId && !$areaId && !$areaNombre) {
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
    private function getNivelIndicador($fechaInicio, $fechaFin, $areaId, $areaNombre, $nivelId, $sedeId, $tipoCalificacion = null)
    {
        $niveles = [];
        
        // Excluir NPS por defecto para consistencia con la tabla
        $tipos = $tipoCalificacion ? [$tipoCalificacion] : ['csat', 'fcr'];
        
        foreach ($tipos as $tipo) {
            $queryTotal = Calificacion::query()
                ->where('tipo_calificacion', $tipo);
                
            $this->aplicarFiltros($queryTotal, $fechaInicio, $fechaFin, $areaId, $areaNombre, $nivelId, $sedeId, $tipo);
            $totalRespuestas = $queryTotal->count();
            
            $valor = $this->calcularIndicador($tipo, $fechaInicio, $fechaFin, $areaId, $areaNombre, $nivelId, $sedeId);
            
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
    private function getEncuestasPorArea($fechaInicio, $fechaFin, $areaId, $areaNombre, $nivelId, $sedeId, $tipoCalificacion = null)
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

        // Aplicar filtros indicando que ya tenemos el join con areas
        $this->aplicarFiltros($query, $fechaInicio, $fechaFin, $areaId, $areaNombre, $nivelId, $sedeId, $tipoCalificacion, true);

        return $query->get()->toArray();
    }

    /**
     * Obtener relación entre nivel de indicador y cantidad de encuestas por día/mes
     */
    private function getRelacionNivelEncuestas($fechaInicio, $fechaFin, $areaId, $areaNombre, $nivelId, $sedeId, $tipoCalificacion = null)
    {
        // Determinar si agrupar por día o mes
        $agruparPorMes = false;
        if ($fechaInicio && $fechaFin) {
            $fecha1 = new \DateTime($fechaInicio);
            $fecha2 = new \DateTime($fechaFin);
            $diff = $fecha1->diff($fecha2);
            $agruparPorMes = $diff->days > 31;
        }

        // Excluir NPS por defecto para consistencia con la tabla
        $tipos = $tipoCalificacion ? [$tipoCalificacion] : ['csat', 'fcr'];
        $resultados = [];

        foreach ($tipos as $tipo) {
            $query = Calificacion::query()
                ->where('calificaciones.tipo_calificacion', $tipo)
                ->select(
                    $agruparPorMes 
                        ? DB::raw('DATE_FORMAT(calificaciones.created_at, "%Y-%m") as fecha')
                        : DB::raw('DATE(calificaciones.created_at) as fecha'),
                    DB::raw('COUNT(*) as cantidad_encuestas')
                )
                ->groupBy('fecha')
                ->orderBy('fecha');

            if ($fechaInicio) {
                $query->where('calificaciones.created_at', '>=', $fechaInicio);
            }
            if ($fechaFin) {
                $query->where('calificaciones.created_at', '<=', $fechaFin . ' 23:59:59');
            }
            // 🔥 IMPORTANTE: Si hay filtro por nombre de área, filtrar por nombre (nacional)
            if ($areaNombre) {
                $query->join('areas', 'calificaciones.area_id', '=', 'areas.id')
                      ->where('areas.nombre', $areaNombre);
            } elseif ($areaId) {
                $query->where('calificaciones.area_id', $areaId);
            }
            // 🔥 IMPORTANTE: Si hay filtro de área (por nombre o ID), NO aplicar filtro de sede (mostrar nacional)
            if ($sedeId && !$areaId && !$areaNombre) {
                $query->where('calificaciones.sede_id', $sedeId);
            }

            $datos = $query->get()->map(function($item) use ($tipo, $agruparPorMes, $areaId, $areaNombre, $nivelId, $sedeId, $fechaInicio, $fechaFin) {
                // Calcular porcentaje del indicador para esta fecha
                $fechaStart = $item->fecha;
                
                if ($agruparPorMes) {
                    // Si es por mes, calcular para todo el mes pero respetando los filtros de fecha
                    $porcentaje = $this->calcularIndicadorPorMes($tipo, $fechaStart, $areaId, $areaNombre, $nivelId, $sedeId, $fechaInicio, $fechaFin);
                } else {
                    // Si es por día, calcular para ese día específico
                    $porcentaje = $this->calcularIndicadorPorFecha($tipo, $fechaStart, null, $areaId, $areaNombre, $nivelId, $sedeId);
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
    private function calcularIndicadorPorFecha($tipoCalificacion, $fecha, $fechaFin, $areaId, $areaNombre, $nivelId, $sedeId)
    {
        $queryTotal = Calificacion::query()
            ->where('calificaciones.tipo_calificacion', $tipoCalificacion)
            ->whereDate('calificaciones.created_at', '=', $fecha);
            
        // 🔥 IMPORTANTE: Si hay filtro por nombre de área, filtrar por nombre (nacional)
        if ($areaNombre) {
            $queryTotal->join('areas', 'calificaciones.area_id', '=', 'areas.id')
                      ->where('areas.nombre', $areaNombre);
        } elseif ($areaId) {
            $queryTotal->where('calificaciones.area_id', $areaId);
        }
        // 🔥 IMPORTANTE: Si hay filtro de área (por nombre o ID), NO aplicar filtro de sede (mostrar nacional)
        if ($sedeId && !$areaId && !$areaNombre) {
            $queryTotal->where('calificaciones.sede_id', $sedeId);
        }
        
        $totalEncuestas = $queryTotal->count();
        
        if ($totalEncuestas == 0) {
            return 0;
        }
        
        switch ($tipoCalificacion) {
            case 'fcr':
                // FCR: Total personas que respondieron SÍ (valor_principal = 0) / Total encuestas * 100
                // IMPORTANTE: valor_principal = 0 significa "Sí", valor_principal = 1 significa "No"
                $querySi = Calificacion::query()
                    ->where('calificaciones.tipo_calificacion', 'fcr')
                    ->where(function ($q) {
                        $q->where('calificaciones.valor_principal', 0)
                          ->orWhereNull('calificaciones.valor_principal');
                    })
                    ->whereDate('calificaciones.created_at', '=', $fecha);
                if ($areaNombre) {
                    $querySi->join('areas', 'calificaciones.area_id', '=', 'areas.id')
                           ->where('areas.nombre', $areaNombre);
                } elseif ($areaId) {
                    $querySi->where('calificaciones.area_id', $areaId);
                }
                if ($sedeId && !$areaId && !$areaNombre) $querySi->where('calificaciones.sede_id', $sedeId);
                $totalSi = $querySi->count();
                
                // Debug temporal
                $queryNo = Calificacion::query()
                    ->where('calificaciones.tipo_calificacion', 'fcr')
                    ->where('calificaciones.valor_principal', 1)
                    ->whereDate('calificaciones.created_at', '=', $fecha);
                if ($areaNombre) {
                    $queryNo->join('areas', 'calificaciones.area_id', '=', 'areas.id')
                           ->where('areas.nombre', $areaNombre);
                } elseif ($areaId) {
                    $queryNo->where('calificaciones.area_id', $areaId);
                }
                if ($sedeId && !$areaId && !$areaNombre) $queryNo->where('calificaciones.sede_id', $sedeId);
                $totalNo = $queryNo->count();
                
                \Log::info("🔍 FCR Por Fecha {$fecha}: Total={$totalEncuestas}, Sí={$totalSi}, No={$totalNo}, Porcentaje=" . round(($totalSi / $totalEncuestas) * 100, 1));
                
                return round(($totalSi / $totalEncuestas) * 100, 1);
                
            case 'csat':
                // CSAT: (Muy satisfechos + Satisfechos) / Total encuestas * 100
                // nivel_calificacion_id 1 = Muy Satisfecho, 2 = Satisfecho
                $querySatisfechos = Calificacion::query()
                    ->where('calificaciones.tipo_calificacion', 'csat')
                    ->whereIn('calificaciones.nivel_calificacion_id', [1, 2])
                    ->whereDate('calificaciones.created_at', '=', $fecha);
                if ($areaNombre) {
                    $querySatisfechos->join('areas', 'calificaciones.area_id', '=', 'areas.id')
                                    ->where('areas.nombre', $areaNombre);
                } elseif ($areaId) {
                    $querySatisfechos->where('calificaciones.area_id', $areaId);
                }
                if ($sedeId && !$areaId && !$areaNombre) $querySatisfechos->where('calificaciones.sede_id', $sedeId);
                $totalSatisfechos = $querySatisfechos->count();
                return round(($totalSatisfechos / $totalEncuestas) * 100, 1);
                
            case 'nps':
                $queryPromotores = Calificacion::query()
                    ->where('tipo_calificacion', 'nps')
                    ->whereBetween('valor_principal', [9, 10])
                    ->whereDate('calificaciones.created_at', '=', $fecha);
                if ($areaNombre) {
                    $queryPromotores->join('areas', 'calificaciones.area_id', '=', 'areas.id')
                                   ->where('areas.nombre', $areaNombre);
                } elseif ($areaId) {
                    $queryPromotores->where('calificaciones.area_id', $areaId);
                }
                if ($sedeId && !$areaId && !$areaNombre) $queryPromotores->where('calificaciones.sede_id', $sedeId);
                $totalPromotores = $queryPromotores->count();
                return round(($totalPromotores / $totalEncuestas) * 100, 1);
                
            default:
                return 0;
        }
    }

    /**
     * Calcular indicador para un mes específico (formato: YYYY-MM)
     * Ahora respeta los filtros de fecha_inicio y fecha_fin si están presentes
     */
    private function calcularIndicadorPorMes($tipoCalificacion, $mes, $areaId, $areaNombre, $nivelId, $sedeId, $fechaInicio = null, $fechaFin = null)
    {
        $queryTotal = Calificacion::query()
            ->where('tipo_calificacion', $tipoCalificacion)
            ->whereRaw('DATE_FORMAT(calificaciones.created_at, "%Y-%m") = ?', [$mes]);
            
        // Aplicar filtros de fecha si están presentes
        if ($fechaInicio) {
            $queryTotal->where('calificaciones.created_at', '>=', $fechaInicio);
        }
        if ($fechaFin) {
            $queryTotal->where('calificaciones.created_at', '<=', $fechaFin . ' 23:59:59');
        }
            
        // 🔥 IMPORTANTE: Si hay filtro por nombre de área, filtrar por nombre (nacional)
        if ($areaNombre) {
            $queryTotal->join('areas', 'calificaciones.area_id', '=', 'areas.id')
                      ->where('areas.nombre', $areaNombre);
        } elseif ($areaId) {
            $queryTotal->where('calificaciones.area_id', $areaId);
        }
        // 🔥 IMPORTANTE: Si hay filtro de área (por nombre o ID), NO aplicar filtro de sede (mostrar nacional)
        if ($sedeId && !$areaId && !$areaNombre) {
            $queryTotal->where('sede_id', $sedeId);
        }
        
        $totalEncuestas = $queryTotal->count();
        
        if ($totalEncuestas == 0) {
            return 0;
        }
        
        switch ($tipoCalificacion) {
            case 'fcr':
                // FCR: Total personas que respondieron SÍ (valor_principal = 0) / Total encuestas * 100
                $querySi = Calificacion::query()
                    ->where('tipo_calificacion', 'fcr')
                    ->where(function ($q) {
                        $q->where('valor_principal', 0)
                          ->orWhereNull('valor_principal');
                    })
                    ->whereRaw('DATE_FORMAT(created_at, "%Y-%m") = ?', [$mes]);
                if ($fechaInicio) $querySi->where('calificaciones.created_at', '>=', $fechaInicio);
                if ($fechaFin) $querySi->where('calificaciones.created_at', '<=', $fechaFin . ' 23:59:59');
                if ($areaNombre) {
                    $querySi->join('areas', 'calificaciones.area_id', '=', 'areas.id')
                           ->where('areas.nombre', $areaNombre);
                } elseif ($areaId) {
                    $querySi->where('calificaciones.area_id', $areaId);
                }
                if ($sedeId && !$areaId && !$areaNombre) $querySi->where('calificaciones.sede_id', $sedeId);
                $totalSi = $querySi->count();
                return round(($totalSi / $totalEncuestas) * 100, 1);
                
            case 'csat':
                // CSAT: (Muy satisfechos + Satisfechos) / Total encuestas * 100
                // nivel_calificacion_id 1 = Muy Satisfecho, 2 = Satisfecho
                $querySatisfechos = Calificacion::query()
                    ->where('tipo_calificacion', 'csat')
                    ->whereIn('nivel_calificacion_id', [1, 2])
                    ->whereRaw('DATE_FORMAT(calificaciones.created_at, "%Y-%m") = ?', [$mes]);
                if ($fechaInicio) $querySatisfechos->where('calificaciones.created_at', '>=', $fechaInicio);
                if ($fechaFin) $querySatisfechos->where('calificaciones.created_at', '<=', $fechaFin . ' 23:59:59');
                if ($areaNombre) {
                    $querySatisfechos->join('areas', 'calificaciones.area_id', '=', 'areas.id')
                                    ->where('areas.nombre', $areaNombre);
                } elseif ($areaId) {
                    $querySatisfechos->where('calificaciones.area_id', $areaId);
                }
                if ($sedeId && !$areaId && !$areaNombre) $querySatisfechos->where('calificaciones.sede_id', $sedeId);
                $totalSatisfechos = $querySatisfechos->count();
                return round(($totalSatisfechos / $totalEncuestas) * 100, 1);
                
            case 'nps':
                $queryPromotores = Calificacion::query()
                    ->where('tipo_calificacion', 'nps')
                    ->whereBetween('valor_principal', [9, 10])
                    ->whereRaw('DATE_FORMAT(calificaciones.created_at, "%Y-%m") = ?', [$mes]);
                if ($fechaInicio) $queryPromotores->where('calificaciones.created_at', '>=', $fechaInicio);
                if ($fechaFin) $queryPromotores->where('calificaciones.created_at', '<=', $fechaFin . ' 23:59:59');
                if ($areaNombre) {
                    $queryPromotores->join('areas', 'calificaciones.area_id', '=', 'areas.id')
                                   ->where('areas.nombre', $areaNombre);
                } elseif ($areaId) {
                    $queryPromotores->where('calificaciones.area_id', $areaId);
                }
                if ($sedeId && !$areaId && !$areaNombre) $queryPromotores->where('calificaciones.sede_id', $sedeId);
                $totalPromotores = $queryPromotores->count();
                return round(($totalPromotores / $totalEncuestas) * 100, 1);
                
            default:
                return 0;
        }
    }

    /**
     * Obtener indicadores y sus dimensiones (subpreguntas)
     */
    private function getIndicadoresDimensiones($fechaInicio, $fechaFin, $areaId, $areaNombre, $nivelId, $sedeId, $tipoCalificacion = null)
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
                // 🔥 IMPORTANTE: Si hay filtro por nombre de área, filtrar por nombre (nacional)
                if ($areaNombre) {
                    $query->join('areas', 'calificaciones.area_id', '=', 'areas.id')
                          ->where('areas.nombre', $areaNombre);
                } elseif ($areaId) {
                    $query->where('calificaciones.area_id', $areaId);
                }
                // 🔥 IMPORTANTE: Si hay filtro de área (por nombre o ID), NO aplicar filtro de sede (mostrar nacional)
                if ($sedeId && !$areaId && !$areaNombre) {
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

            // Agregar TOP por preguntas principales (preguntas creadas) del mismo tipo
            $preguntasQuery = DB::table('respuestas_calificacion')
                ->join('calificaciones', 'respuestas_calificacion.calificacion_id', '=', 'calificaciones.id')
                ->join('preguntas', 'respuestas_calificacion.pregunta_id', '=', 'preguntas.id')
                ->where('preguntas.tipo_pregunta', $tipo)
                ->select(
                    'preguntas.pregunta as dimension',
                    DB::raw('COUNT(*) as total')
                )
                ->groupBy('preguntas.pregunta');

            // Filtros por fecha, área y sede
            if ($fechaInicio) {
                $preguntasQuery->where('calificaciones.created_at', '>=', $fechaInicio);
            }
            if ($fechaFin) {
                $preguntasQuery->where('calificaciones.created_at', '<=', $fechaFin . ' 23:59:59');
            }
            // 🔥 IMPORTANTE: Si hay filtro por nombre de área, filtrar por nombre (nacional)
            if ($areaNombre) {
                $preguntasQuery->join('areas', 'calificaciones.area_id', '=', 'areas.id')
                              ->where('areas.nombre', $areaNombre);
            } elseif ($areaId) {
                $preguntasQuery->where('calificaciones.area_id', $areaId);
            }
            // 🔥 IMPORTANTE: Si hay filtro de área (por nombre o ID), NO aplicar filtro de sede (mostrar nacional)
            if ($sedeId && !$areaId && !$areaNombre) {
                $preguntasQuery->where('calificaciones.sede_id', $sedeId);
            }

            $preguntasData = $preguntasQuery->get()->map(function($row) {
                return [
                    'dimension' => $row->dimension,
                    'tipo' => 'pregunta',
                    'total' => (int)$row->total
                ];
            })->toArray();

            // Combinar dimensiones (subpreguntas) con preguntas principales
            $resultados[$tipo] = array_merge($dimensionesData, $preguntasData);
        }

        return $resultados;
    }

    /**
     * Obtener distribución de NPS (Promotores, Pasivos, Detractores)
     */
    private function getDistribucionNPS($fechaInicio, $fechaFin, $areaId, $areaNombre, $nivelId, $sedeId)
    {
        $query = Calificacion::query()
            ->where('tipo_calificacion', 'nps');

        if ($fechaInicio) {
            $query->where('calificaciones.created_at', '>=', $fechaInicio);
        }
        if ($fechaFin) {
            $query->where('calificaciones.created_at', '<=', $fechaFin . ' 23:59:59');
        }
        // 🔥 IMPORTANTE: Si hay filtro por nombre de área, filtrar por nombre (nacional)
        if ($areaNombre) {
            $query->join('areas', 'calificaciones.area_id', '=', 'areas.id')
                  ->where('areas.nombre', $areaNombre);
        } elseif ($areaId) {
            $query->where('calificaciones.area_id', $areaId);
        }
        // 🔥 IMPORTANTE: Si hay filtro de área (por nombre o ID), NO aplicar filtro de sede (mostrar nacional)
        if ($sedeId && !$areaId && !$areaNombre) {
            $query->where('sede_id', $sedeId);
        }

        $total = $query->count();
        
        \Log::info("NPS Distribución - Filtros: areaId={$areaId}, sedeId={$sedeId}, fechaInicio={$fechaInicio}, fechaFin={$fechaFin}");
        \Log::info("NPS Distribución - Total calificaciones NPS: {$total}");
        
        $promotores = (clone $query)->whereBetween('valor_principal', [9, 10])->count();
        $pasivos = (clone $query)->whereBetween('valor_principal', [7, 8])->count();
        $detractores = (clone $query)->whereBetween('valor_principal', [1, 6])->count();

        \Log::info("NPS Distribución - Promotores: {$promotores}, Pasivos: {$pasivos}, Detractores: {$detractores}");

        return [
            'promotores' => $promotores,
            'pasivos' => $pasivos,
            'detractores' => $detractores,
            'total' => $total
        ];
    }

    /**
     * Obtener dimensiones CSAT agrupadas por nivel de calificación (1-4)
     */
    private function getCSATDimensionesPorNivel($fechaInicio, $fechaFin, $areaId, $areaNombre, $sedeId)
    {
        $niveles = [1, 2, 3, 4]; // 1: Muy Satisfecho, 2: Satisfecho, 3: Insatisfecho, 4: Muy Insatisfecho
        $resultados = [];

        foreach ($niveles as $nivel) {
            // Primero obtener las calificaciones CSAT con este nivel y filtros aplicados
            // CSAT se identifica por: tipo_calificacion = 'csat' O (tipo_calificacion es NULL/vacío)
            // Como nivel_calificacion_id ya está filtrado por $nivel (1-4), no necesitamos verificar eso de nuevo
            $calificacionesQuery = DB::table('calificaciones')
                ->where('calificaciones.nivel_calificacion_id', $nivel)
                ->where(function($q) {
                    $q->where('calificaciones.tipo_calificacion', 'csat')
                      ->orWhereNull('calificaciones.tipo_calificacion')
                      ->orWhere('calificaciones.tipo_calificacion', '');
                });

            // Aplicar filtros a las calificaciones
            if ($fechaInicio) {
                $calificacionesQuery->where('calificaciones.created_at', '>=', $fechaInicio);
            }
            if ($fechaFin) {
                $calificacionesQuery->where('calificaciones.created_at', '<=', $fechaFin . ' 23:59:59');
            }
            // 🔥 IMPORTANTE: Si hay filtro por nombre de área, filtrar por nombre (nacional)
            if ($areaNombre) {
                $calificacionesQuery->join('areas', 'calificaciones.area_id', '=', 'areas.id')
                                   ->where('areas.nombre', $areaNombre);
            } elseif ($areaId) {
                $calificacionesQuery->where('calificaciones.area_id', $areaId);
            }
            // 🔥 IMPORTANTE: Si hay filtro de área (por nombre o ID), NO aplicar filtro de sede (mostrar nacional)
            if ($sedeId && !$areaId && !$areaNombre) {
                $calificacionesQuery->where('calificaciones.sede_id', $sedeId);
            }

            $calificacionIds = $calificacionesQuery->pluck('calificaciones.id')->toArray();
            
            // Debug: verificar la consulta SQL generada
            $sql = $calificacionesQuery->toSql();
            $bindings = $calificacionesQuery->getBindings();
            \Log::info("CSAT Nivel {$nivel} - SQL: {$sql}");
            \Log::info("CSAT Nivel {$nivel} - Bindings: " . json_encode($bindings));

            \Log::info("CSAT Nivel {$nivel} - Filtros: areaId={$areaId}, sedeId={$sedeId}, fechaInicio={$fechaInicio}, fechaFin={$fechaFin}");
            \Log::info("CSAT Nivel {$nivel} - Calificaciones encontradas: " . count($calificacionIds));

            if (empty($calificacionIds)) {
                $resultados[$nivel] = [];
                continue;
            }

            // Ahora buscar las respuestas para estas calificaciones
            // IMPORTANTE: Solo obtener respuestas de preguntas asociadas a este nivel específico
            
            // 🔥 DEBUG: Verificar qué preguntas están asociadas a este nivel
            $preguntasDelNivel = DB::table('preguntas')
                ->where('tipo_pregunta', 'csat')
                ->where('niveles_calificacion_id', $nivel)
                ->where('is_active', true)
                ->select('id', 'pregunta', 'niveles_calificacion_id')
                ->get();
            
            \Log::info("🔍 CSAT Nivel {$nivel} - Preguntas asociadas a este nivel:");
            foreach ($preguntasDelNivel as $preg) {
                \Log::info("  - Pregunta ID: {$preg->id}, Texto: {$preg->pregunta}, Nivel: {$preg->niveles_calificacion_id}");
            }
            
            $respuestasQuery = DB::table('respuestas_calificacion')
                ->whereIn('calificacion_id', $calificacionIds)
                ->leftJoin('opciones_pregunta', 'respuestas_calificacion.opcion_seleccionada_id', '=', 'opciones_pregunta.id')
                ->join('preguntas', 'respuestas_calificacion.pregunta_id', '=', 'preguntas.id')
                ->where('preguntas.tipo_pregunta', 'csat')
                ->where('preguntas.niveles_calificacion_id', $nivel); // 🔥 FILTRAR POR NIVEL DE LA PREGUNTA

            $respuestas = $respuestasQuery->select(
                'opciones_pregunta.opcion',
                'respuestas_calificacion.opciones_seleccionadas',
                'respuestas_calificacion.respuesta_texto',
                'preguntas.id as pregunta_id',
                'preguntas.pregunta as pregunta_texto',
                'preguntas.niveles_calificacion_id'
            )->get();
            
            // 🔥 DEBUG: Verificar qué respuestas se están obteniendo
            \Log::info("🔍 CSAT Nivel {$nivel} - Respuestas obtenidas: " . count($respuestas));
            foreach ($respuestas->take(5) as $resp) {
                \Log::info("  - Opción: {$resp->opcion}, Pregunta ID: {$resp->pregunta_id}, Pregunta: {$resp->pregunta_texto}, Nivel Pregunta: {$resp->niveles_calificacion_id}");
            }
            
            // Debug: verificar la consulta SQL de respuestas
            $sqlRespuestas = $respuestasQuery->toSql();
            $bindingsRespuestas = $respuestasQuery->getBindings();
            \Log::info("CSAT Nivel {$nivel} - SQL Respuestas: {$sqlRespuestas}");
            \Log::info("CSAT Nivel {$nivel} - Bindings Respuestas: " . json_encode($bindingsRespuestas));
            
            \Log::info("CSAT Nivel {$nivel} - Calificaciones encontradas: " . count($calificacionIds));
            \Log::info("CSAT Nivel {$nivel} - Respuestas encontradas: " . count($respuestas));
            
            if (count($respuestas) > 0) {
                \Log::info("CSAT Nivel {$nivel} - Primera respuesta: " . json_encode($respuestas->first()));
            } else {
                // Verificar si hay respuestas sin filtros
                $totalRespuestasSinFiltros = DB::table('respuestas_calificacion')
                    ->join('calificaciones', 'respuestas_calificacion.calificacion_id', '=', 'calificaciones.id')
                    ->join('preguntas', 'respuestas_calificacion.pregunta_id', '=', 'preguntas.id')
                    ->where('calificaciones.nivel_calificacion_id', $nivel)
                    ->where('preguntas.tipo_pregunta', 'csat')
                    ->count();
                \Log::info("CSAT Nivel {$nivel} - Total respuestas sin filtros: {$totalRespuestasSinFiltros}");
                
                // Verificar IDs de calificaciones con este nivel y filtros
                $califIdsQuery = DB::table('calificaciones')
                    ->where('calificaciones.nivel_calificacion_id', $nivel);
                if ($fechaInicio) {
                    $califIdsQuery->where('calificaciones.created_at', '>=', $fechaInicio);
                }
                if ($fechaFin) {
                    $califIdsQuery->where('calificaciones.created_at', '<=', $fechaFin . ' 23:59:59');
                }
                if ($areaId) {
                    $califIdsQuery->where('calificaciones.area_id', $areaId);
                }
                if ($sedeId) {
                    $califIdsQuery->where('calificaciones.sede_id', $sedeId);
                }
                $califIds = $califIdsQuery->pluck('id')->toArray();
                \Log::info("CSAT Nivel {$nivel} - IDs de calificaciones con filtros: " . implode(', ', array_slice($califIds, 0, 10)));
                
                // Verificar respuestas para estas calificaciones
                if (!empty($califIds)) {
                    $respuestasParaCalifs = DB::table('respuestas_calificacion')
                        ->whereIn('calificacion_id', $califIds)
                        ->count();
                    \Log::info("CSAT Nivel {$nivel} - Respuestas para estas calificaciones: {$respuestasParaCalifs}");
                }
            }

            $opcionesCount = [];
            $textoLibreCount = 0;

            foreach ($respuestas as $respuesta) {
                $tieneTextoLibre = !empty($respuesta->respuesta_texto);
                $esOpcionOtro = false;
                
                // 1. Procesar opción única (desde opciones_pregunta)
                if (!empty($respuesta->opcion)) {
                    $opcion = $respuesta->opcion;
                    // Verificar si es "Otro - especifique"
                    $esOpcionOtro = (stripos($opcion, 'otro') !== false || stripos($opcion, 'especifique') !== false);
                    
                    if ($esOpcionOtro && $tieneTextoLibre) {
                        // Si es "Otro" y tiene texto, contar solo como texto libre (no como opción)
                        $textoLibreCount++;
                    } else {
                        // Contar como opción normal (incluso si es "Otro" sin texto)
                        if (!isset($opcionesCount[$opcion])) {
                            $opcionesCount[$opcion] = 0;
                        }
                        $opcionesCount[$opcion]++;
                        
                        // Si tiene texto libre y NO es "Otro", también contar como texto libre
                        if ($tieneTextoLibre && !$esOpcionOtro) {
                            $textoLibreCount++;
                        }
                    }
                }
                
                // 2. Procesar opciones múltiples (array JSON)
                if (!empty($respuesta->opciones_seleccionadas)) {
                    $opciones = json_decode($respuesta->opciones_seleccionadas, true);
                    if (is_array($opciones)) {
                        foreach ($opciones as $opcion) {
                            if (!empty($opcion)) {
                                if (!isset($opcionesCount[$opcion])) {
                                    $opcionesCount[$opcion] = 0;
                                }
                                $opcionesCount[$opcion]++;
                            }
                        }
                    }
                }
                
                // 3. Contar texto libre si no hay opción seleccionada pero sí hay texto
                if (empty($respuesta->opcion) && 
                    empty($respuesta->opciones_seleccionadas) && 
                    $tieneTextoLibre) {
                    $textoLibreCount++;
                }
            }

            $dimensionesData = [];
            
            // Agregar todas las opciones con sus conteos
            foreach ($opcionesCount as $opcion => $cantidad) {
                $dimensionesData[] = [
                    'dimension' => $opcion,
                    'tipo' => 'opcion',
                    'total' => (int)$cantidad
                ];
            }
            
            // Agregar texto libre si existe
            if ($textoLibreCount > 0) {
                $dimensionesData[] = [
                    'dimension' => 'Texto Libre / Otro',
                    'tipo' => 'texto_libre',
                    'total' => (int)$textoLibreCount
                ];
            }

            // Ordenar por cantidad (mayor a menor)
            usort($dimensionesData, function($a, $b) {
                return $b['total'] - $a['total'];
            });

            $resultados[$nivel] = $dimensionesData;
        }

        return $resultados;
    }

    /**
     * Obtener ranking de áreas por satisfacción
     * Calcula porcentaje combinado: CSAT porcentaje + FCR porcentaje
     * Ordena por porcentaje combinado (descendente), luego por cantidad
     */
    private function getRankingAreas($fechaInicio, $fechaFin, $sedeId)
    {
        $ranking = [];
        
        if ($sedeId) {
            // Con filtro de sede: agrupar por area_id (áreas individuales de esa sede)
            $queryAreas = DB::table('areas')
                ->where('sede_id', $sedeId)
                ->select('id as area_id', 'nombre', 'sede_id');
            $areas = $queryAreas->get();
            
            foreach ($areas as $area) {
                $areaId = $area->area_id;
                $areaNombre = $area->nombre;
                
                // Usar calcularIndicador para obtener los porcentajes como en el dashboard semáforo
                $csatPorcentaje = $this->calcularIndicador('csat', $fechaInicio, $fechaFin, $areaId, null, null, $sedeId);
                $fcrPorcentaje = $this->calcularIndicador('fcr', $fechaInicio, $fechaFin, $areaId, null, null, $sedeId);
                
                // Calcular porcentaje combinado (promedio de ambos)
                $porcentajeCombinado = ($csatPorcentaje + $fcrPorcentaje) / 2;
                
                // Calcular total de calificaciones de SATISFACCIÓN (CSAT niveles 1-2 + FCR Sí)
                $queryTotal = Calificacion::query()
                    ->where('area_id', $areaId)
                    ->where('sede_id', $sedeId)
            ->where(function($q) {
                // CSAT con niveles 1 y 2 (satisfacción)
                $q->where(function($q2) {
                            $q2->where('tipo_calificacion', 'csat')
                               ->whereIn('nivel_calificacion_id', [1, 2]);
                })
                // FCR con Sí (valor_principal = 0 o null)
                ->orWhere(function($q2) {
                            $q2->where('tipo_calificacion', 'fcr')
                       ->where(function($q3) {
                                   $q3->where('valor_principal', 0)
                                      ->orWhereNull('valor_principal');
                       });
                });
            });

        if ($fechaInicio) {
                    $queryTotal->where('created_at', '>=', $fechaInicio);
        }
        if ($fechaFin) {
                    $queryTotal->where('created_at', '<=', $fechaFin . ' 23:59:59');
                }
                
                $totalCalificaciones = $queryTotal->count();
                
                // Solo incluir áreas que tengan al menos una calificación
                if ($totalCalificaciones > 0) {
                    // Calcular estrellas basadas en porcentaje combinado (0-100% = 0-5 estrellas)
                    $estrellas = round(($porcentajeCombinado / 100) * 5, 1);
                    $estrellas = min($estrellas, 5.0);
                    
                    $ranking[] = [
                        'area_id' => (int)$areaId,
                        'area_nombre' => $areaNombre,
                        'promedio_estrellas' => $estrellas,
                        'total_calificaciones' => $totalCalificaciones,
                        'porcentaje_combinado' => $porcentajeCombinado,
                        'csat_porcentaje' => $csatPorcentaje,
                        'fcr_porcentaje' => $fcrPorcentaje
                    ];
                }
            }
        } else {
            // Sin filtro de sede: agrupar por nombre de área (nacional - suma todas las sedes)
            $nombresAreas = DB::table('areas')
                ->select('nombre')
                ->distinct()
            ->get();
            
            foreach ($nombresAreas as $areaNombreObj) {
                $areaNombre = $areaNombreObj->nombre;
                
                // Usar calcularIndicador con areaNombre (no areaId) para agrupar por nombre
                $csatPorcentaje = $this->calcularIndicador('csat', $fechaInicio, $fechaFin, null, $areaNombre, null, null);
                $fcrPorcentaje = $this->calcularIndicador('fcr', $fechaInicio, $fechaFin, null, $areaNombre, null, null);
                
                // Calcular porcentaje combinado (promedio de ambos)
                $porcentajeCombinado = ($csatPorcentaje + $fcrPorcentaje) / 2;
                
                // Calcular total de calificaciones de SATISFACCIÓN (CSAT niveles 1-2 + FCR Sí)
                $queryTotal = Calificacion::query()
                    ->join('areas', 'calificaciones.area_id', '=', 'areas.id')
                    ->where('areas.nombre', $areaNombre)
                    ->where(function($q) {
                        // CSAT con niveles 1 y 2 (satisfacción)
                        $q->where(function($q2) {
                            $q2->where('calificaciones.tipo_calificacion', 'csat')
                               ->whereIn('calificaciones.nivel_calificacion_id', [1, 2]);
                        })
                        // FCR con Sí (valor_principal = 0 o null)
                        ->orWhere(function($q2) {
                            $q2->where('calificaciones.tipo_calificacion', 'fcr')
                               ->where(function($q3) {
                                   $q3->where('calificaciones.valor_principal', 0)
                                      ->orWhereNull('calificaciones.valor_principal');
                               });
                        });
                    });
                
                if ($fechaInicio) {
                    $queryTotal->where('calificaciones.created_at', '>=', $fechaInicio);
                }
                if ($fechaFin) {
                    $queryTotal->where('calificaciones.created_at', '<=', $fechaFin . ' 23:59:59');
                }
                
                $totalCalificaciones = $queryTotal->count();
                
                // Solo incluir áreas que tengan al menos una calificación
                if ($totalCalificaciones > 0) {
                    // Calcular estrellas basadas en porcentaje combinado (0-100% = 0-5 estrellas)
                    $estrellas = round(($porcentajeCombinado / 100) * 5, 1);
                    $estrellas = min($estrellas, 5.0);
            
            $ranking[] = [
                        'area_id' => null, // Nacional, no tiene ID específico
                        'area_nombre' => $areaNombre,
                'promedio_estrellas' => $estrellas,
                        'total_calificaciones' => $totalCalificaciones,
                        'porcentaje_combinado' => $porcentajeCombinado,
                        'csat_porcentaje' => $csatPorcentaje,
                        'fcr_porcentaje' => $fcrPorcentaje
                    ];
                }
            }
        }

        // Ordenar primero por porcentaje combinado (descendente), luego por cantidad
        usort($ranking, function($a, $b) {
            // Primero por porcentaje combinado
            if (abs($b['porcentaje_combinado'] - $a['porcentaje_combinado']) > 0.01) {
                return $b['porcentaje_combinado'] <=> $a['porcentaje_combinado'];
            }
            // Si tienen el mismo porcentaje, ordenar por cantidad (mayor cantidad primero)
            return $b['total_calificaciones'] <=> $a['total_calificaciones'];
        });

        return $ranking;
    }

    /**
     * Obtener ranking de áreas por insatisfacción
     * Calcula porcentaje combinado: CSAT porcentaje insatisfacción + FCR porcentaje insatisfacción
     * Ordena por porcentaje combinado (descendente), luego por cantidad
     */
    private function getRankingAreasInsatisfaccion($fechaInicio, $fechaFin, $sedeId)
    {
        $ranking = [];
        
        if ($sedeId) {
            // Con filtro de sede: agrupar por area_id (áreas individuales de esa sede)
            $queryAreas = DB::table('areas')
                ->where('sede_id', $sedeId)
                ->select('id as area_id', 'nombre', 'sede_id');
            $areas = $queryAreas->get();
            
            foreach ($areas as $area) {
                $areaId = $area->area_id;
                $areaNombre = $area->nombre;
                
                // Calcular porcentaje CSAT de insatisfacción (100 - porcentaje de satisfacción)
                $csatPorcentajeSatisfaccion = $this->calcularIndicador('csat', $fechaInicio, $fechaFin, $areaId, null, null, $sedeId);
                $csatPorcentaje = 100 - $csatPorcentajeSatisfaccion;
                
                // Calcular porcentaje FCR de insatisfacción (100 - porcentaje de satisfacción)
                $fcrPorcentajeSatisfaccion = $this->calcularIndicador('fcr', $fechaInicio, $fechaFin, $areaId, null, null, $sedeId);
                $fcrPorcentaje = 100 - $fcrPorcentajeSatisfaccion;
                
                // Calcular porcentaje combinado (promedio de ambos)
                $porcentajeCombinado = ($csatPorcentaje + $fcrPorcentaje) / 2;
                
                // Calcular total de calificaciones de INSATISFACCIÓN (CSAT niveles 3-4 + FCR No)
                $queryTotal = Calificacion::query()
                    ->where('area_id', $areaId)
                    ->where('sede_id', $sedeId)
                    ->where(function($q) {
                        // CSAT con niveles 3 y 4 (insatisfacción)
                        $q->where(function($q2) {
                            $q2->where('tipo_calificacion', 'csat')
                               ->whereIn('nivel_calificacion_id', [3, 4]);
                        })
                        // FCR con No (valor_principal = 1)
                        ->orWhere(function($q2) {
                            $q2->where('tipo_calificacion', 'fcr')
                               ->where('valor_principal', 1);
                        });
                    });
                
                if ($fechaInicio) {
                    $queryTotal->where('created_at', '>=', $fechaInicio);
                }
                if ($fechaFin) {
                    $queryTotal->where('created_at', '<=', $fechaFin . ' 23:59:59');
                }
                
                $totalCalificaciones = $queryTotal->count();
                
                // Solo incluir áreas que tengan al menos una calificación
                if ($totalCalificaciones > 0) {
                    // Calcular "X" basadas en porcentaje combinado (0-100% = 0-5 X)
                    $numeroX = round(($porcentajeCombinado / 100) * 5, 1);
                    $numeroX = min($numeroX, 5.0);
                    
                    $ranking[] = [
                        'area_id' => (int)$areaId,
                        'area_nombre' => $areaNombre,
                        'promedio_estrellas' => $numeroX, // Usar para X en insatisfacción
                        'total_calificaciones' => $totalCalificaciones,
                        'porcentaje_combinado' => $porcentajeCombinado,
                        'csat_porcentaje' => $csatPorcentaje,
                        'fcr_porcentaje' => $fcrPorcentaje
                    ];
                }
            }
        } else {
            // Sin filtro de sede: agrupar por nombre de área (nacional - suma todas las sedes)
            $nombresAreas = DB::table('areas')
                ->select('nombre')
                ->distinct()
                ->get();
            
            foreach ($nombresAreas as $areaNombreObj) {
                $areaNombre = $areaNombreObj->nombre;
                
                // Calcular porcentaje CSAT de insatisfacción (100 - porcentaje de satisfacción)
                $csatPorcentajeSatisfaccion = $this->calcularIndicador('csat', $fechaInicio, $fechaFin, null, $areaNombre, null, null);
                $csatPorcentaje = 100 - $csatPorcentajeSatisfaccion;
                
                // Calcular porcentaje FCR de insatisfacción (100 - porcentaje de satisfacción)
                $fcrPorcentajeSatisfaccion = $this->calcularIndicador('fcr', $fechaInicio, $fechaFin, null, $areaNombre, null, null);
                $fcrPorcentaje = 100 - $fcrPorcentajeSatisfaccion;
                
                // Calcular porcentaje combinado (promedio de ambos)
                $porcentajeCombinado = ($csatPorcentaje + $fcrPorcentaje) / 2;
                
                // Calcular total de calificaciones de INSATISFACCIÓN (CSAT niveles 3-4 + FCR No)
                $queryTotal = Calificacion::query()
            ->join('areas', 'calificaciones.area_id', '=', 'areas.id')
                    ->where('areas.nombre', $areaNombre)
            ->where(function($q) {
                // CSAT con niveles 3 y 4 (insatisfacción)
                $q->where(function($q2) {
                    $q2->where('calificaciones.tipo_calificacion', 'csat')
                       ->whereIn('calificaciones.nivel_calificacion_id', [3, 4]);
                })
                // FCR con No (valor_principal = 1)
                ->orWhere(function($q2) {
                    $q2->where('calificaciones.tipo_calificacion', 'fcr')
                       ->where('calificaciones.valor_principal', 1);
                });
            });

        if ($fechaInicio) {
                    $queryTotal->where('calificaciones.created_at', '>=', $fechaInicio);
        }
        if ($fechaFin) {
                    $queryTotal->where('calificaciones.created_at', '<=', $fechaFin . ' 23:59:59');
                }
                
                $totalCalificaciones = $queryTotal->count();
                
                // Solo incluir áreas que tengan al menos una calificación
                if ($totalCalificaciones > 0) {
                    // Calcular "X" basadas en porcentaje combinado (0-100% = 0-5 X)
                    $numeroX = round(($porcentajeCombinado / 100) * 5, 1);
                    $numeroX = min($numeroX, 5.0);
                    
                    $ranking[] = [
                        'area_id' => null, // Nacional, no tiene ID específico
                        'area_nombre' => $areaNombre,
                        'promedio_estrellas' => $numeroX, // Usar para X en insatisfacción
                        'total_calificaciones' => $totalCalificaciones,
                        'porcentaje_combinado' => $porcentajeCombinado,
                        'csat_porcentaje' => $csatPorcentaje,
                        'fcr_porcentaje' => $fcrPorcentaje
                    ];
                }
            }
        }

        // Ordenar primero por porcentaje combinado (descendente), luego por cantidad
        usort($ranking, function($a, $b) {
            // Primero por porcentaje combinado
            if (abs($b['porcentaje_combinado'] - $a['porcentaje_combinado']) > 0.01) {
                return $b['porcentaje_combinado'] <=> $a['porcentaje_combinado'];
            }
            // Si tienen el mismo porcentaje, ordenar por cantidad (mayor cantidad primero)
            return $b['total_calificaciones'] <=> $a['total_calificaciones'];
        });

        return $ranking;
    }

    /**
     * Obtener los textos más anotados en el campo "otros" (texto libre) de CSAT y FCR agrupados por área
     */
    private function getTextosMasAnotados($fechaInicio, $fechaFin, $areaId, $areaNombre, $sedeId)
    {
        $resultados = [
            'csat' => [],
            'fcr' => [],
            'porArea' => [] // Nueva estructura: textos agrupados por área
        ];

        // Obtener textos de CSAT con información de área
        // CSAT puede tener textos en respuestas_calificacion (opcion_unica_texto_libre) 
        // o en respuestas_subpreguntas (cuando hay subpreguntas con "Otro")
        
        // Primero: textos de respuestas_calificacion
        $queryCSAT1 = DB::table('respuestas_calificacion')
            ->join('calificaciones', 'respuestas_calificacion.calificacion_id', '=', 'calificaciones.id')
            ->join('areas', 'calificaciones.area_id', '=', 'areas.id')
            ->where('calificaciones.tipo_calificacion', 'csat')
            ->whereNotNull('respuestas_calificacion.respuesta_texto')
            ->where('respuestas_calificacion.respuesta_texto', '!=', '')
            ->whereRaw('LENGTH(TRIM(respuestas_calificacion.respuesta_texto)) > 0');

        // Aplicar filtros
        if ($fechaInicio) {
            $queryCSAT1->where('calificaciones.created_at', '>=', $fechaInicio);
        }
        if ($fechaFin) {
            $queryCSAT1->where('calificaciones.created_at', '<=', $fechaFin . ' 23:59:59');
        }
        if ($sedeId) {
            $queryCSAT1->where('calificaciones.sede_id', $sedeId);
        }
        if ($areaId) {
            $queryCSAT1->where('calificaciones.area_id', $areaId);
        } elseif ($areaNombre) {
            $queryCSAT1->where('areas.nombre', $areaNombre);
        }

        $textosCSAT1 = $queryCSAT1->select(
                'areas.id as area_id',
                'areas.nombre as area_nombre',
                DB::raw('TRIM(respuestas_calificacion.respuesta_texto) as texto'),
                DB::raw('COUNT(*) as cantidad'),
                DB::raw('MAX(calificaciones.created_at) as ultima_fecha')
            )
            ->groupBy('areas.id', 'areas.nombre', DB::raw('TRIM(respuestas_calificacion.respuesta_texto)'))
            ->get();

        // Segundo: textos de respuestas_subpreguntas (cuando opcion_seleccionada contiene "Otro")
        $queryCSAT2 = DB::table('respuestas_subpreguntas')
            ->join('calificaciones', 'respuestas_subpreguntas.calificacion_id', '=', 'calificaciones.id')
            ->join('areas', 'calificaciones.area_id', '=', 'areas.id')
            ->where('calificaciones.tipo_calificacion', 'csat')
            ->where('respuestas_subpreguntas.opcion_seleccionada', 'like', '%Otro%')
            ->whereNotNull('respuestas_subpreguntas.texto_respuesta')
            ->where('respuestas_subpreguntas.texto_respuesta', '!=', '')
            ->whereRaw('LENGTH(TRIM(respuestas_subpreguntas.texto_respuesta)) > 0');

        // Aplicar filtros
        if ($fechaInicio) {
            $queryCSAT2->where('calificaciones.created_at', '>=', $fechaInicio);
        }
        if ($fechaFin) {
            $queryCSAT2->where('calificaciones.created_at', '<=', $fechaFin . ' 23:59:59');
        }
        if ($sedeId) {
            $queryCSAT2->where('calificaciones.sede_id', $sedeId);
        }
        if ($areaId) {
            $queryCSAT2->where('calificaciones.area_id', $areaId);
        } elseif ($areaNombre) {
            $queryCSAT2->where('areas.nombre', $areaNombre);
        }

        $textosCSAT2 = $queryCSAT2->select(
                'areas.id as area_id',
                'areas.nombre as area_nombre',
                DB::raw('TRIM(respuestas_subpreguntas.texto_respuesta) as texto'),
                DB::raw('COUNT(*) as cantidad'),
                DB::raw('MAX(calificaciones.created_at) as ultima_fecha')
            )
            ->groupBy('areas.id', 'areas.nombre', DB::raw('TRIM(respuestas_subpreguntas.texto_respuesta)'))
            ->get();

        // Combinar ambos resultados
        $textosCSAT = $textosCSAT1->merge($textosCSAT2)
            ->groupBy(function($item) {
                return $item->area_id . '|' . $item->area_nombre . '|' . $item->texto;
            })
            ->map(function($group) {
                $first = $group->first();
                $fechas = $group->pluck('ultima_fecha')->filter()->values()->all();
                $ultimaFecha = !empty($fechas) ? max($fechas) : null;
                return (object)[
                    'area_id' => $first->area_id,
                    'area_nombre' => $first->area_nombre,
                    'texto' => $first->texto,
                    'cantidad' => $group->sum('cantidad'),
                    'ultima_fecha' => $ultimaFecha
                ];
            })
            ->sortByDesc('cantidad')
            ->sortBy('area_nombre')
            ->take(50)
            ->values();

        foreach ($textosCSAT as $item) {
            $resultados['csat'][] = [
                'texto' => $item->texto,
                'cantidad' => (int)$item->cantidad,
                'ultima_fecha' => $item->ultima_fecha
            ];

            // Agregar a estructura por área
            $areaKey = $item->area_nombre;
            if (!isset($resultados['porArea'][$areaKey])) {
                $resultados['porArea'][$areaKey] = [];
            }
            $resultados['porArea'][$areaKey][] = [
                'tipo' => 'csat',
                'texto' => $item->texto,
                'cantidad' => (int)$item->cantidad,
                'area_id' => (int)$item->area_id,
                'area_nombre' => $item->area_nombre,
                'ultima_fecha' => $item->ultima_fecha
            ];
        }

        usort($resultados['csat'], function ($a, $b) {
            return $this->compareUltimaFechaDesc($a['ultima_fecha'] ?? null, $b['ultima_fecha'] ?? null);
        });

        // Obtener textos de FCR con información de área
        // FCR almacena textos libres en respuestas_subpreguntas cuando opcion_seleccionada es "Otro - especifique"
        $queryFCR = DB::table('respuestas_subpreguntas')
            ->join('calificaciones', 'respuestas_subpreguntas.calificacion_id', '=', 'calificaciones.id')
            ->join('areas', 'calificaciones.area_id', '=', 'areas.id')
            ->where('calificaciones.tipo_calificacion', 'fcr')
            ->where('respuestas_subpreguntas.opcion_seleccionada', 'like', '%Otro%')
            ->whereNotNull('respuestas_subpreguntas.texto_respuesta')
            ->where('respuestas_subpreguntas.texto_respuesta', '!=', '')
            ->whereRaw('LENGTH(TRIM(respuestas_subpreguntas.texto_respuesta)) > 0');

        // Aplicar filtros
        if ($fechaInicio) {
            $queryFCR->where('calificaciones.created_at', '>=', $fechaInicio);
        }
        if ($fechaFin) {
            $queryFCR->where('calificaciones.created_at', '<=', $fechaFin . ' 23:59:59');
        }
        if ($sedeId) {
            $queryFCR->where('calificaciones.sede_id', $sedeId);
        }
        if ($areaId) {
            $queryFCR->where('calificaciones.area_id', $areaId);
        } elseif ($areaNombre) {
            $queryFCR->where('areas.nombre', $areaNombre);
        }

        // Agrupar por área, texto, contar y ordenar
        $textosFCR = $queryFCR->select(
                'areas.id as area_id',
                'areas.nombre as area_nombre',
                DB::raw('TRIM(respuestas_subpreguntas.texto_respuesta) as texto'),
                DB::raw('COUNT(*) as cantidad'),
                DB::raw('MAX(calificaciones.created_at) as ultima_fecha')
            )
            ->groupBy('areas.id', 'areas.nombre', DB::raw('TRIM(respuestas_subpreguntas.texto_respuesta)'))
            ->orderBy('cantidad', 'desc')
            ->orderBy('areas.nombre')
            ->limit(50)
            ->get();

        foreach ($textosFCR as $item) {
            $resultados['fcr'][] = [
                'texto' => $item->texto,
                'cantidad' => (int)$item->cantidad,
                'ultima_fecha' => $item->ultima_fecha
            ];

            // Agregar a estructura por área
            $areaKey = $item->area_nombre;
            if (!isset($resultados['porArea'][$areaKey])) {
                $resultados['porArea'][$areaKey] = [];
            }
            $resultados['porArea'][$areaKey][] = [
                'tipo' => 'fcr',
                'texto' => $item->texto,
                'cantidad' => (int)$item->cantidad,
                'area_id' => (int)$item->area_id,
                'area_nombre' => $item->area_nombre,
                'ultima_fecha' => $item->ultima_fecha
            ];
        }

        usort($resultados['fcr'], function ($a, $b) {
            return $this->compareUltimaFechaDesc($a['ultima_fecha'] ?? null, $b['ultima_fecha'] ?? null);
        });

        // Mantener porArea como objeto asociativo (más fácil de iterar en frontend)
        // Ordenar cada área por fecha de última respuesta (más reciente primero)
        foreach ($resultados['porArea'] as $areaNombre => &$textos) {
            usort($textos, function ($a, $b) {
                return $this->compareUltimaFechaDesc($a['ultima_fecha'] ?? null, $b['ultima_fecha'] ?? null);
            });
        }

        return $resultados;
    }

    /**
     * Comparar dos fechas para ordenar descendente (más reciente primero); sin fecha al final.
     */
    private function compareUltimaFechaDesc($fechaA, $fechaB): int
    {
        $ta = $fechaA ? strtotime((string) $fechaA) : null;
        $tb = $fechaB ? strtotime((string) $fechaB) : null;
        if ($ta === false) {
            $ta = null;
        }
        if ($tb === false) {
            $tb = null;
        }
        if ($ta === null && $tb === null) {
            return 0;
        }
        if ($ta === null) {
            return 1;
        }
        if ($tb === null) {
            return -1;
        }
        return $tb <=> $ta;
    }
}