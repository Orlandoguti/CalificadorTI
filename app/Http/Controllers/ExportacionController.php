<?php

namespace App\Http\Controllers;

use App\Exports\EstadisticasExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
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

    private function generarNombreArchivo($filtros, $extension)
    {
        $sede = isset($filtros['sede_id']) ? '_Sede_' . $filtros['sede_id'] : '';
        $fecha = now()->format('Y-m-d');
        
        return "Reporte_Estadisticas{$sede}_{$fecha}.{$extension}";
    }
}