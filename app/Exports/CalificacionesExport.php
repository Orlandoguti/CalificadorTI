<?php

namespace App\Exports;

use App\Models\Calificacion;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class CalificacionesExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $filtros;

    public function __construct($filtros = [])
    {
        $this->filtros = $filtros;
    }

    /**
     * Obtener la colección de calificaciones con filtros aplicados
     */
    public function collection()
    {
        $query = Calificacion::with([
            'area', 
            'sede', 
            'nivelCalificacion',
            'respuestasCalificacion.pregunta',
            'respuestasCalificacion.opcionSeleccionada',
            'respuestasSubpreguntas.subpregunta'
        ]);

        // Aplicar filtros
        if (!empty($this->filtros['fecha_inicio'])) {
            $query->where('created_at', '>=', $this->filtros['fecha_inicio']);
        }

        if (!empty($this->filtros['fecha_fin'])) {
            $query->where('created_at', '<=', $this->filtros['fecha_fin'] . ' 23:59:59');
        }

        if (!empty($this->filtros['area_id'])) {
            $query->where('area_id', $this->filtros['area_id']);
        }

        if (!empty($this->filtros['sede_id'])) {
            $query->where('sede_id', $this->filtros['sede_id']);
        }

        if (!empty($this->filtros['tipo_calificacion'])) {
            $query->where('tipo_calificacion', $this->filtros['tipo_calificacion']);
        }

        if (!empty($this->filtros['nivel_id'])) {
            $query->where('nivel_calificacion_id', $this->filtros['nivel_id']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Encabezados de las columnas
     */
    public function headings(): array
    {
        return [
            'ID',
            'Fecha y Hora',
            'Área',
            'Sede',
            'Tipo Calificación',
            'Nivel',
            'Valor Principal (Sí/No)',
            'Respuestas Detalladas'
        ];
    }

    /**
     * Mapear cada fila de datos
     */
    public function map($calificacion): array
    {
        // Convertir valor_principal para FCR: 1 = No, 0 = Sí (o null = Sí)
        $valorPrincipal = 'N/A';
        if ($calificacion->tipo_calificacion === 'fcr') {
            if ($calificacion->valor_principal === 1) {
                $valorPrincipal = 'No';
            } elseif ($calificacion->valor_principal === 0 || $calificacion->valor_principal === null) {
                $valorPrincipal = 'Sí';
            }
        } else {
            $valorPrincipal = $calificacion->valor_principal ?? 'N/A';
        }

        // Obtener nivel
        $nivel = 'N/A';
        if ($calificacion->nivelCalificacion) {
            $nivel = $calificacion->nivelCalificacion->nombre;
        } elseif ($calificacion->valor_principal !== null && $calificacion->tipo_calificacion !== 'fcr') {
            $nivel = $calificacion->valor_principal;
        }

        // Construir respuestas detalladas de preguntas principales
        $respuestasDetalladas = [];
        foreach ($calificacion->respuestasCalificacion as $respuesta) {
            $detalle = [];
            
            // Agregar pregunta
            if ($respuesta->pregunta) {
                $detalle[] = 'Pregunta: ' . $respuesta->pregunta->pregunta;
            }
            
            // Agregar opción seleccionada
            if ($respuesta->opcionSeleccionada) {
                $detalle[] = 'Opción: ' . $respuesta->opcionSeleccionada->opcion;
            }
            
            // Agregar opciones múltiples si existen
            if (!empty($respuesta->opciones_seleccionadas) && is_array($respuesta->opciones_seleccionadas)) {
                $opciones = implode(', ', $respuesta->opciones_seleccionadas);
                $detalle[] = 'Opciones múltiples: ' . $opciones;
            }
            
            // Agregar texto libre
            if (!empty($respuesta->respuesta_texto)) {
                $detalle[] = 'Texto libre: ' . $respuesta->respuesta_texto;
            }
            
            if (!empty($detalle)) {
                $respuestasDetalladas[] = implode(' | ', $detalle);
            }
        }
        
        // Construir respuestas de subpreguntas
        foreach ($calificacion->respuestasSubpreguntas as $respuestaSub) {
            $detalle = [];
            
            // Agregar subpregunta
            if ($respuestaSub->subpregunta) {
                $detalle[] = 'Subpregunta: ' . $respuestaSub->subpregunta->pregunta_texto;
            }
            
            // Agregar opción seleccionada
            if (!empty($respuestaSub->opcion_seleccionada)) {
                $detalle[] = 'Opción: ' . $respuestaSub->opcion_seleccionada;
            }
            
            // Agregar opciones múltiples si existen
            if (!empty($respuestaSub->opciones_seleccionadas) && is_array($respuestaSub->opciones_seleccionadas)) {
                $opciones = implode(', ', $respuestaSub->opciones_seleccionadas);
                $detalle[] = 'Opciones múltiples: ' . $opciones;
            }
            
            // Agregar texto libre
            if (!empty($respuestaSub->texto_respuesta)) {
                $detalle[] = 'Texto libre: ' . $respuestaSub->texto_respuesta;
            }
            
            // Agregar valor indicador si existe
            if ($respuestaSub->valor_indicador !== null) {
                $detalle[] = 'Valor: ' . $respuestaSub->valor_indicador;
            }
            
            if (!empty($detalle)) {
                $respuestasDetalladas[] = implode(' | ', $detalle);
            }
        }
        
        $respuestasTexto = !empty($respuestasDetalladas) 
            ? implode(' || ', $respuestasDetalladas) 
            : 'Sin respuestas detalladas';

        return [
            $calificacion->id,
            $calificacion->created_at ? $calificacion->created_at->format('d/m/Y H:i:s') : '',
            $calificacion->area ? $calificacion->area->nombre : 'N/A',
            $calificacion->sede ? $calificacion->sede->nombre : 'N/A',
            strtoupper($calificacion->tipo_calificacion ?? 'N/A'),
            $nivel,
            $valorPrincipal,
            $respuestasTexto
        ];
    }

    /**
     * Estilos para el archivo Excel
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F46E5']
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER
                ]
            ],
        ];
    }
}
