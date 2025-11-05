<?php

namespace App\Http\Controllers;

use App\Models\Calificacion;
use App\Models\Area;
use App\Models\Pregunta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class GestorController extends Controller
{
    public function stats(Request $request)
    {
        $user = Auth::user();
        $sedeId = $user->sede_id;

        if (!$sedeId) {
            return response()->json([
                'totalCalificaciones' => 0,
                'areasEvaluadas' => 0,
                'porcentajeGeneral' => 0
            ]);
        }

        // Construir query base con filtros
        $query = Calificacion::where('sede_id', $sedeId);

        // Filtro por área
        if ($request->has('area_id') && $request->area_id) {
            $query->where('area_id', $request->area_id);
        }

        // Filtro por rango de fechas
        if ($request->has('fecha_inicio') && $request->fecha_inicio) {
            $query->whereDate('created_at', '>=', $request->fecha_inicio);
        }
        if ($request->has('fecha_fin') && $request->fecha_fin) {
            $query->whereDate('created_at', '<=', $request->fecha_fin);
        }

        // Filtro por tipo de calificación
        if ($request->has('tipo_calificacion') && $request->tipo_calificacion) {
            $query->where('tipo_calificacion', $request->tipo_calificacion);
        }

        // 1. Total Calificaciones
        $totalCalificaciones = $query->count();

        // 2. Áreas Evaluadas (cantidad de áreas distintas con calificaciones)
        $areasEvaluadas = $query->distinct('area_id')->count('area_id');

        // 3. Porcentaje General (depende del tipo de calificación)
        $porcentajeGeneral = 0;
        
        if ($totalCalificaciones > 0) {
            $tipoCalificacion = $request->tipo_calificacion;
            
            if ($tipoCalificacion === 'csat') {
                // CSAT: promedio de nivel_calificacion_id (1-4) convertido a porcentaje
                // Nivel 1 = 0%, Nivel 2 = 33.33%, Nivel 3 = 66.66%, Nivel 4 = 100%
                $promedioNivel = $query->avg('nivel_calificacion_id');
                if ($promedioNivel) {
                    $porcentajeGeneral = (($promedioNivel - 1) / 3) * 100;
                }
            } elseif ($tipoCalificacion === 'nps') {
                // NPS: promedio de valor_principal (0-10) convertido a porcentaje
                $promedioNPS = $query->avg('valor_principal');
                if ($promedioNPS !== null) {
                    $porcentajeGeneral = ($promedioNPS / 10) * 100;
                }
            } elseif ($tipoCalificacion === 'fcr') {
                // FCR: porcentaje de valor_principal = 1 (sí/resuelto) sobre el total
                $fcrResueltas = $query->where('valor_principal', 1)->count();
                $porcentajeGeneral = ($fcrResueltas / $totalCalificaciones) * 100;
            } else {
                // General: calcular promedio ponderado según el tipo
                // Construir queries independientes para cada tipo
                $csatQuery = Calificacion::where('sede_id', $sedeId)
                    ->where('tipo_calificacion', 'csat');
                $npsQuery = Calificacion::where('sede_id', $sedeId)
                    ->where('tipo_calificacion', 'nps');
                $fcrQuery = Calificacion::where('sede_id', $sedeId)
                    ->where('tipo_calificacion', 'fcr');
                
                // Aplicar filtros adicionales a cada query
                if ($request->has('area_id') && $request->area_id) {
                    $csatQuery->where('area_id', $request->area_id);
                    $npsQuery->where('area_id', $request->area_id);
                    $fcrQuery->where('area_id', $request->area_id);
                }
                if ($request->has('fecha_inicio') && $request->fecha_inicio) {
                    $csatQuery->whereDate('created_at', '>=', $request->fecha_inicio);
                    $npsQuery->whereDate('created_at', '>=', $request->fecha_inicio);
                    $fcrQuery->whereDate('created_at', '>=', $request->fecha_inicio);
                }
                if ($request->has('fecha_fin') && $request->fecha_fin) {
                    $csatQuery->whereDate('created_at', '<=', $request->fecha_fin);
                    $npsQuery->whereDate('created_at', '<=', $request->fecha_fin);
                    $fcrQuery->whereDate('created_at', '<=', $request->fecha_fin);
                }
                
                $csatCount = $csatQuery->count();
                $npsCount = $npsQuery->count();
                $fcrCount = $fcrQuery->count();
                
                $sumaPonderada = 0;
                $totalPonderado = 0;
                
                // CSAT: convertir nivel (1-4) a porcentaje
                if ($csatCount > 0) {
                    $csatPromedio = $csatQuery->avg('nivel_calificacion_id');
                    if ($csatPromedio) {
                        $csatPorcentaje = (($csatPromedio - 1) / 3) * 100;
                        $sumaPonderada += $csatPorcentaje * $csatCount;
                        $totalPonderado += $csatCount;
                    }
                }
                
                // NPS: convertir valor (0-10) a porcentaje
                if ($npsCount > 0) {
                    $npsPromedio = $npsQuery->avg('valor_principal');
                    if ($npsPromedio !== null) {
                        $npsPorcentaje = ($npsPromedio / 10) * 100;
                        $sumaPonderada += $npsPorcentaje * $npsCount;
                        $totalPonderado += $npsCount;
                    }
                }
                
                // FCR: porcentaje de resueltas
                if ($fcrCount > 0) {
                    $fcrResueltas = $fcrQuery->where('valor_principal', 1)->count();
                    $fcrPorcentaje = ($fcrResueltas / $fcrCount) * 100;
                    $sumaPonderada += $fcrPorcentaje * $fcrCount;
                    $totalPonderado += $fcrCount;
                }
                
                if ($totalPonderado > 0) {
                    $porcentajeGeneral = $sumaPonderada / $totalPonderado;
                }
            }
        }

        $stats = [
            'totalCalificaciones' => $totalCalificaciones,
            'areasEvaluadas' => $areasEvaluadas,
            'porcentajeGeneral' => round($porcentajeGeneral, 2)
        ];

        return response()->json($stats);
    }

    public function getStats(Request $request)
    {
        return $this->stats($request);
    }

    public function getCalificacionesPorArea(Request $request)
    {
        $user = Auth::user();
        $sedeId = $user->sede_id;

        if (!$sedeId) {
            return response()->json([]);
        }

        // Construir query base con filtros
        $query = Calificacion::where('sede_id', $sedeId);

        // Filtro por área
        if ($request->has('area_id') && $request->area_id) {
            $query->where('area_id', $request->area_id);
        }

        // Filtro por rango de fechas
        if ($request->has('fecha_inicio') && $request->fecha_inicio) {
            $query->whereDate('created_at', '>=', $request->fecha_inicio);
        }
        if ($request->has('fecha_fin') && $request->fecha_fin) {
            $query->whereDate('created_at', '<=', $request->fecha_fin);
        }

        // Filtro por tipo de calificación
        if ($request->has('tipo_calificacion') && $request->tipo_calificacion) {
            $query->where('tipo_calificacion', $request->tipo_calificacion);
        }

        $calificaciones = $query
            ->with(['area', 'nivelCalificacion'])
            ->get()
            ->groupBy('area_id')
            ->map(function ($califs) {
                return [
                    'area' => $califs->first()->area->nombre,
                    'total' => $califs->count(),
                    'promedio' => round($califs->avg('nivel_calificacion_id'), 2)
                ];
            })
            ->values();

        return response()->json($calificaciones);
    }
}