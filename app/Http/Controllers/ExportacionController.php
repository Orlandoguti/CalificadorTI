<?php

namespace App\Http\Controllers;

use App\Exports\EstadisticasExport;
use App\Exports\CalificacionesExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PDF;

class ExportacionController extends Controller
{
    public function exportarEstadisticas(Request $request)
    {
        try {
            $filtros = [
                'fecha_inicio' => $request->get('fecha_inicio'),
                'fecha_fin' => $request->get('fecha_fin'),
                'area_id' => $request->get('area_id'),
                'nivel_id' => $request->get('nivel_id'),
                'sede_id' => $request->get('sede_id'),
                'formato' => $request->get('formato', 'excel')
            ];

            $formato = $request->get('formato', 'excel');

            switch ($formato) {
                case 'excel':
                    return Excel::download(
                        new EstadisticasExport($filtros),
                        $this->generarNombreArchivo($filtros, 'xlsx'),
                        \Maatwebsite\Excel\Excel::XLSX
                    );

                case 'csv':
                    return Excel::download(
                        new EstadisticasExport($filtros),
                        $this->generarNombreArchivo($filtros, 'csv'),
                        \Maatwebsite\Excel\Excel::CSV
                    );

                case 'pdf':
                    return $this->exportarPDF($filtros);

                default:
                    return Excel::download(
                        new EstadisticasExport($filtros),
                        $this->generarNombreArchivo($filtros, 'xlsx'),
                        \Maatwebsite\Excel\Excel::XLSX
                    );
            }

        } catch (\Exception $e) {
            \Log::error('Error en exportación: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al generar el reporte: ' . $e->getMessage()
            ], 500);
        }
    }

    private function exportarPDF($filtros)
    {
        // Obtener datos para el PDF
        $estadisticas = $this->obtenerDatosEstadisticas($filtros);
        
        $pdf = PDF::loadView('exports.estadisticas-pdf', [
            'estadisticas' => $estadisticas,
            'filtros' => $filtros,
            'fechaGeneracion' => now()->format('d/m/Y H:i:s')
        ]);

        return $pdf->download($this->generarNombreArchivo($filtros, 'pdf'));
    }

    private function obtenerDatosEstadisticas($filtros)
    {
        // Tu lógica para obtener estadísticas
        return [
            'totales' => [
                'calificaciones' => 100,
                'areas' => 5,
                'preguntas' => 50,
                'promedioGeneral' => 8.5
            ],
            'topAreas' => [],
            'distribucionNiveles' => []
        ];
    }

    /**
     * Exportar calificaciones a Excel
     */
    public function exportarCalificaciones(Request $request)
    {
        try {
            $filtros = [
                'fecha_inicio' => $request->get('fecha_inicio'),
                'fecha_fin' => $request->get('fecha_fin'),
                'area_id' => $request->get('area_id'),
                'nivel_id' => $request->get('nivel_id'),
                'sede_id' => $request->get('sede_id'),
                'tipo_calificacion' => $request->get('tipo_calificacion')
            ];

            // Si el usuario autenticado es gestor, forzar su sede.
            // Esto evita exportar datos de otras sedes aunque manipulen el query string.
            $user = Auth::user();
            if ($user && $user->role === 'gestor' && !empty($user->sede_id)) {
                $filtros['sede_id'] = $user->sede_id;
            }

            $nombreArchivo = $this->generarNombreArchivoCalificaciones($filtros);

            return Excel::download(
                new CalificacionesExport($filtros),
                $nombreArchivo,
                \Maatwebsite\Excel\Excel::XLSX
            );

        } catch (\Exception $e) {
            \Log::error('Error en exportación de calificaciones: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'error' => 'Error al generar el reporte: ' . $e->getMessage()
            ], 500);
        }
    }

    private function generarNombreArchivo($filtros, $extension)
    {
        $sede = isset($filtros['sede_id']) ? '_Sede_' . $filtros['sede_id'] : '';
        $fecha = now()->format('Y-m-d');
        
        return "Reporte_Estadisticas{$sede}_{$fecha}.{$extension}";
    }

    private function generarNombreArchivoCalificaciones($filtros)
    {
        $partes = ['Calificaciones'];
        
        if (!empty($filtros['sede_id'])) {
            $partes[] = 'Sede_' . $filtros['sede_id'];
        }
        
        if (!empty($filtros['area_id'])) {
            $partes[] = 'Area_' . $filtros['area_id'];
        }
        
        if (!empty($filtros['tipo_calificacion'])) {
            $partes[] = strtoupper($filtros['tipo_calificacion']);
        }
        
        if (!empty($filtros['fecha_inicio']) && !empty($filtros['fecha_fin'])) {
            $fechaInicio = date('Y-m-d', strtotime($filtros['fecha_inicio']));
            $fechaFin = date('Y-m-d', strtotime($filtros['fecha_fin']));
            $partes[] = $fechaInicio . '_' . $fechaFin;
        } else {
            $partes[] = now()->format('Y-m-d');
        }
        
        return implode('_', $partes) . '.xlsx';
    }
}