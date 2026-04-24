<?php

namespace App\Http\Controllers;

use App\Models\Calificacion;
use App\Models\RespuestaCalificacion;
use App\Models\Pregunta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;
use Carbon\Carbon;

class CalificacionController extends Controller
{
    /**
     * Guardar calificación simple (para compatibilidad)
     */
    public function guardarSimple(Request $request)
    {
        $data = $request->validate([
            'area_id' => 'required|exists:areas,id',
            'nivel_calificacion_id' => 'required|exists:niveles_calificacion,id',
            'sede_id' => 'nullable|exists:sedes,id',
            'sede_nombre' => 'required|string',
            'respuestas' => 'sometimes|array',
            'observacion' => 'nullable|string',
            'es_invitado' => 'boolean',
            'user_id' => 'nullable|exists:users,id'
        ]);

        try {
            // Crear la calificación principal
            $calificacion = Calificacion::create([
                'user_id' => $data['user_id'] ?? null,
                'area_id' => $data['area_id'],
                'sede_id' => $data['sede_id'],
                'nivel_calificacion_id' => $data['nivel_calificacion_id'],
                'created_at' => now()
            ]);

            // Guardar respuestas individuales si existen
            if (isset($data['respuestas']) && !empty($data['respuestas'])) {
                foreach ($data['respuestas'] as $preguntaId => $respuesta) {
                    Log::info('Respuesta guardada', [
                        'calificacion_id' => $calificacion->id,
                        'pregunta_id' => $preguntaId,
                        'respuesta' => $respuesta
                    ]);
                }
            }

            return response()->json([
                'message' => 'Calificación guardada exitosamente',
                'calificacion_id' => $calificacion->id,
                'datos_guardados' => [
                    'area' => $calificacion->area->nombre,
                    'sede' => $data['sede_nombre'],
                    'nivel' => $calificacion->nivelCalificacion->nombre,
                    'fecha' => $calificacion->created_at
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error guardando calificación: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al guardar la calificación',
                'message' => $e->getMessage()
            ], 500);
        }
    }

public function guardarCompleta(Request $request)
{
    DB::beginTransaction();

    try {
        Log::info('=== INICIANDO guardarCompleta CON RANGOS ===');
        Log::info('📦 DATOS COMPLETOS RECIBIDOS:', $request->all());

        // Validar los datos PRIMERO
        $validated = $request->validate([
            'area_id' => 'required|exists:areas,id',
            'sede_id' => 'required|exists:sedes,id',
            'nivel_calificacion_id' => 'nullable|exists:niveles_calificacion,id', // 🔥 CORRECCIÓN FCR: Opcional, puede ser NULL
            'respuestas' => 'sometimes|array', // 🔥 CORRECCIÓN: Cambiar a 'sometimes' para permitir FCR sin respuestas normales
            'respuestas_subpreguntas' => 'sometimes|array',
            'respuestas_rangos' => 'sometimes|array',
            // Fecha/hora en que el usuario envió (offline sync); ISO 8601 desde el cliente
            'registrado_en' => 'nullable|string|max:64',
        ]);

        $fechaRegistroCliente = null;
        if (! empty($validated['registrado_en'])) {
            try {
                $parsed = Carbon::parse($validated['registrado_en']);
                // Aceptar si no es futuro (margen reloj) y no es ridículamente antiguo
                if ($parsed->lte(now()->addMinutes(10)) && $parsed->gte(now()->subYears(5))) {
                    $fechaRegistroCliente = $parsed;
                }
            } catch (\Throwable $e) {
                Log::warning('registrado_en inválido, se usa hora del servidor', ['valor' => $validated['registrado_en']]);
            }
        }
        unset($validated['registrado_en']);
        
        // 🔥 CORRECCIÓN: Inicializar respuestas como array vacío si no está presente
        if (!isset($validated['respuestas'])) {
            $validated['respuestas'] = [];
        }
        if (!isset($validated['respuestas_subpreguntas'])) {
            $validated['respuestas_subpreguntas'] = [];
        }
        if (!isset($validated['respuestas_rangos'])) {
            $validated['respuestas_rangos'] = [];
        }
        
        // 🔥 CORRECCIÓN FCR: Validar que haya al menos respuestas normales O subpreguntas O rangos
        $tieneRespuestas = !empty($validated['respuestas']);
        $tieneSubpreguntas = !empty($validated['respuestas_subpreguntas']) && count($validated['respuestas_subpreguntas']) > 0;
        $tieneRangos = !empty($validated['respuestas_rangos']) && count($validated['respuestas_rangos']) > 0;
        
        if (!$tieneRespuestas && !$tieneSubpreguntas && !$tieneRangos) {
            return response()->json([
                'error' => 'Debe haber al menos una respuesta (normal, subpregunta o rango)'
            ], 422);
        }

        // AHORA SÍ podemos loguear los datos validados
        Log::info('🔍 RESPuestas_rangos:', isset($validated['respuestas_rangos']) ? $validated['respuestas_rangos'] : 'NO HAY');
        Log::info('Datos validados:', $validated);

        // 🔥 NUEVO: Determinar tipo_calificacion y valor_principal
        $tipoCalificacion = null;
        $valorPrincipal = null;
        $nivelCalificacionId = $validated['nivel_calificacion_id'] ?? null;
        
        // Buscar en las respuestas para determinar el tipo
        foreach ($validated['respuestas'] as $preguntaId => $respuesta) {
            if (!is_numeric($preguntaId)) continue;
            
            $pregunta = Pregunta::find($preguntaId);
            if (!$pregunta) continue;
            
            // Si la pregunta tiene tipo_pregunta, usamos ese
            if ($pregunta->tipo_pregunta) {
                $tipoCalificacion = $pregunta->tipo_pregunta;
                
                if ($tipoCalificacion === 'nps') {
                    // Para NPS, el valor está en la respuesta (valor_indicador)
                    $valorPrincipal = is_array($respuesta) && isset($respuesta['valor']) 
                        ? (int)$respuesta['valor'] 
                        : (is_numeric($respuesta) ? (int)$respuesta : null);
                    $nivelCalificacionId = null; // NPS no tiene nivel
                } elseif ($tipoCalificacion === 'fcr') {
                    // Para FCR, determinar si es Sí (0) o No (1) desde opcion_seleccionada_id
                    if (is_array($respuesta) && isset($respuesta['opcion_seleccionada_id'])) {
                        // Buscar la opción para ver si es "Sí" o "No"
                        $opcion = \App\Models\OpcionPregunta::find($respuesta['opcion_seleccionada_id']);
                        if ($opcion) {
                            $valorPrincipal = strtolower(trim($opcion->opcion)) === 'sí' ? 0 : 1;
                        }
                    }
                    $nivelCalificacionId = null; // FCR no tiene nivel
                }
                break; // Encontramos el tipo, salir del loop
            } elseif ($nivelCalificacionId && $nivelCalificacionId >= 1 && $nivelCalificacionId <= 4) {
                // Si tiene nivel_calificacion_id 1-4, es CSAT
                $tipoCalificacion = 'csat';
                $valorPrincipal = $nivelCalificacionId;
            }
        }
        
        // Si no se determinó por respuestas, usar nivel_calificacion_id
        if (!$tipoCalificacion && $nivelCalificacionId) {
            if ($nivelCalificacionId >= 1 && $nivelCalificacionId <= 4) {
                $tipoCalificacion = 'csat';
                $valorPrincipal = $nivelCalificacionId;
            }
        }
        
        // Si aún no se determinó, intentar desde respuestas_subpreguntas (FCR directo)
        if (!$tipoCalificacion && $tieneSubpreguntas) {
            // Verificar si las subpreguntas son de una pregunta FCR
            foreach ($validated['respuestas_subpreguntas'] as $respSub) {
                $subpregunta = \App\Models\Subpregunta::with(['opcion.pregunta'])->find($respSub['subpregunta_id'] ?? null);
                if ($subpregunta && $subpregunta->opcion && $subpregunta->opcion->pregunta) {
                    $preguntaFCR = $subpregunta->opcion->pregunta;
                    if ($preguntaFCR->tipo_pregunta === 'fcr') {
                        $tipoCalificacion = 'fcr';
                        $valorPrincipal = 1; // Si hay subpreguntas, es "No"
                        $nivelCalificacionId = null;
                        break;
                    }
                }
            }
        }

        // Crear la calificación principal
        $calificacionData = [
            'user_id' => Auth::id() ?? null,
            'area_id' => $validated['area_id'],
            'sede_id' => $validated['sede_id'],
            'tipo_calificacion' => $tipoCalificacion, // 🔥 NUEVO
            'valor_principal' => $valorPrincipal, // 🔥 NUEVO
            'nivel_calificacion_id' => $nivelCalificacionId,
        ];

        Log::info('Creando calificación con datos:', $calificacionData);

        $calificacion = new Calificacion();
        $calificacion->forceFill($calificacionData);
        if ($fechaRegistroCliente) {
            $calificacion->created_at = $fechaRegistroCliente;
            $calificacion->updated_at = $fechaRegistroCliente;
        }
        $calificacion->save();
        Log::info('Calificación creada ID: ' . $calificacion->id . ($fechaRegistroCliente ? ' (fecha cliente: ' . $fechaRegistroCliente->toIso8601String() . ')' : ''));

        // 1. GUARDAR RESPUESTAS NORMALES (preguntas principales)
        foreach ($validated['respuestas'] as $preguntaId => $respuesta) {
            // 🔥 CORRECCIÓN: Ignorar claves que no son IDs numéricos
            if (!is_numeric($preguntaId)) {
                Log::info("🔍 Ignorando clave no numérica: {$preguntaId}");
                continue;
            }

            Log::info("📝 Procesando respuesta - Pregunta ID: {$preguntaId}, Respuesta: " . json_encode($respuesta));
            
            $respuestaData = [
                'calificacion_id' => $calificacion->id,
                'pregunta_id' => $preguntaId,
            ];

            $pregunta = Pregunta::find($preguntaId);
            if (!$pregunta) {
                Log::warning('❌ Pregunta no encontrada ID: ' . $preguntaId);
                continue;
            }

            Log::info("🎯 Tipo de pregunta: {$pregunta->tipo}");

            // Manejo de diferentes tipos de respuesta
            switch ($pregunta->tipo) {
                case 'opcion_unica':
                    // 🔥 CORRECCIÓN: Asegurar que sea numérico, no array
                    if (is_array($respuesta)) {
                        $respuestaData['opcion_seleccionada_id'] = isset($respuesta['opcion_seleccionada_id']) ? (int)$respuesta['opcion_seleccionada_id'] : null;
                    } else {
                        $respuestaData['opcion_seleccionada_id'] = is_numeric($respuesta) ? (int)$respuesta : null;
                    }
                    break;
                    
                case 'opcion_multiple':
                    // 🔥 CORRECCIÓN: Guardar como array en opciones_seleccionadas (será convertido a JSON por el cast)
                    $respuestaData['opciones_seleccionadas'] = is_array($respuesta) ? $respuesta : [$respuesta];
                    break;
                    
                case 'texto_libre':
                    $respuestaData['respuesta_texto'] = is_array($respuesta) ? json_encode($respuesta) : (string)$respuesta;
                    break;
                    
                case 'indicador_0_10':
                    // 🔥 CORRECCIÓN: Asegurar que sea string, no array
                    if (is_array($respuesta)) {
                        $respuestaData['respuesta_texto'] = isset($respuesta['valor']) ? (string)$respuesta['valor'] : json_encode($respuesta);
                    } else {
                        $respuestaData['respuesta_texto'] = (string)$respuesta;
                    }
                    break;
                    
                case 'opcion_unica_texto_libre':
                    if (is_array($respuesta) && isset($respuesta['opcion_seleccionada_id'])) {
                        $respuestaData['opcion_seleccionada_id'] = (int)$respuesta['opcion_seleccionada_id'];
                        if (!empty($respuesta['texto_libre'])) {
                            $respuestaData['respuesta_texto'] = (string)$respuesta['texto_libre'];
                        }
                    } else {
                        $respuestaData['respuesta_texto'] = is_array($respuesta) ? json_encode($respuesta) : (string)$respuesta;
                    }
                    break;
                    
                default:
                    $respuestaData['respuesta_texto'] = is_array($respuesta) ? json_encode($respuesta) : (string)$respuesta;
                    break;
            }

            Log::info('💾 Guardando respuesta:', $respuestaData);
            RespuestaCalificacion::create($respuestaData);
            Log::info('✅ Respuesta guardada exitosamente');
        }

        // 2. 🔥 CORREGIDO: GUARDAR RESPUESTAS DE PREGUNTAS DE RANGO CON ELIMINACIÓN DE DUPLICADOS
if (isset($validated['respuestas_rangos']) && is_array($validated['respuestas_rangos'])) {
    Log::info('📥 Procesando respuestas de rangos:', $validated['respuestas_rangos']);
    
    // 🔥 NUEVO: Eliminar duplicados
    $respuestasUnicas = [];
    $clavesProcesadas = [];
    
    foreach ($validated['respuestas_rangos'] as $index => $respuestaRango) {
        // Crear clave única para identificar duplicados
        $claveUnica = $respuestaRango['pregunta_rango_id'] . '_' . 
                     ($respuestaRango['opcion_seleccionada'] ?? '') . '_' . 
                     ($respuestaRango['texto_respuesta'] ?? '');
        
        if (!in_array($claveUnica, $clavesProcesadas)) {
            $clavesProcesadas[] = $claveUnica;
            $respuestasUnicas[] = $respuestaRango;
        } else {
            Log::warning("🔍 Eliminando respuesta duplicada: {$claveUnica}");
        }
    }
    
    Log::info("🎯 Respuestas únicas después de eliminar duplicados: " . count($respuestasUnicas));
    
    foreach ($respuestasUnicas as $index => $respuestaRango) {
        Log::info("🔄 Procesando respuesta de rango {$index}:", $respuestaRango);
        
        if (!isset($respuestaRango['pregunta_rango_id'])) {
            Log::warning('❌ Respuesta de rango sin pregunta_rango_id, saltando...');
            continue;
        }

        // 🔥 CORRECCIÓN: Las respuestas de rango deben guardarse en respuestas_subpreguntas, no en respuestas_calificacion
        // El pregunta_rango_id es en realidad un subpregunta_id
        $subpreguntaId = $respuestaRango['pregunta_rango_id'];
        
        // Preparar datos para respuestas_subpreguntas
        $respuestaSubpreguntaData = [
            'calificacion_id' => $calificacion->id,
            'subpregunta_id' => $subpreguntaId,
        ];

        // Procesar respuesta según el tipo
        if (isset($respuestaRango['texto_respuesta']) && !empty(trim($respuestaRango['texto_respuesta']))) {
            $respuestaSubpreguntaData['texto_respuesta'] = trim($respuestaRango['texto_respuesta']);
        }
        elseif (isset($respuestaRango['opcion_seleccionada'])) {
            // Si viene opcion_seleccionada (ID), guardarlo y obtener el texto
            if (is_numeric($respuestaRango['opcion_seleccionada'])) {
                $opcionId = (int)$respuestaRango['opcion_seleccionada'];
                
                // Intentar obtener el texto de la opción
                try {
                    $opcion = \App\Models\OpcionPregunta::find($opcionId);
                    if ($opcion) {
                        $respuestaSubpreguntaData['opcion_seleccionada'] = $opcion->texto;
                        $respuestaSubpreguntaData['texto_respuesta'] = $opcion->texto;
                    }
                } catch (\Exception $e) {
                    Log::warning('No se pudo obtener texto de opción:', ['error' => $e->getMessage()]);
                    $respuestaSubpreguntaData['texto_respuesta'] = (string)$opcionId;
                }
            } else {
                // Si no es numérico, guardar como texto
                $respuestaSubpreguntaData['opcion_seleccionada'] = trim($respuestaRango['opcion_seleccionada']);
                $respuestaSubpreguntaData['texto_respuesta'] = trim($respuestaRango['opcion_seleccionada']);
            }
        }
        elseif (isset($respuestaRango['opciones_seleccionadas']) && is_array($respuestaRango['opciones_seleccionadas']) && !empty($respuestaRango['opciones_seleccionadas'])) {
            // El modelo tiene un cast 'array' que convertirá automáticamente a JSON
            $respuestaSubpreguntaData['opciones_seleccionadas'] = $respuestaRango['opciones_seleccionadas'];
        }
        elseif (isset($respuestaRango['valor_indicador']) && $respuestaRango['valor_indicador'] !== '') {
            $respuestaSubpreguntaData['valor_indicador'] = (string)$respuestaRango['valor_indicador'];
        }

        // Si no hay datos válidos, saltar
        $tieneTexto = !empty($respuestaSubpreguntaData['texto_respuesta']);
        $tieneOpciones = !empty($respuestaSubpreguntaData['opciones_seleccionadas']);
        $tieneOpcion = !empty($respuestaSubpreguntaData['opcion_seleccionada']);
        $tieneValor = !empty($respuestaSubpreguntaData['valor_indicador']);
        
        if (!$tieneTexto && !$tieneOpciones && !$tieneOpcion && !$tieneValor) {
            Log::warning('❌ Respuesta de rango sin datos válidos, saltando...', $respuestaSubpreguntaData);
            continue;
        }

        Log::info('💾 Guardando respuesta de rango en respuestas_subpreguntas:', $respuestaSubpreguntaData);
        \App\Models\RespuestaSubpregunta::create($respuestaSubpreguntaData);
        Log::info('✅ Respuesta de rango guardada exitosamente en respuestas_subpreguntas');
            }
        } else {
            Log::info('📭 No hay respuestas de rangos para procesar');
        }

        // 3. Procesar respuestas de subpreguntas
        if (isset($validated['respuestas_subpreguntas']) && !empty($validated['respuestas_subpreguntas'])) {
            Log::info('📥 Procesando respuestas de subpreguntas:', $validated['respuestas_subpreguntas']);
            
            foreach ($validated['respuestas_subpreguntas'] as $respuestaSubpregunta) {
                Log::info('🔍 Procesando respuesta de subpregunta recibida:', $respuestaSubpregunta);
                
                // Preparar datos solo con campos que tienen valores
                $dataSubpregunta = [
                    'calificacion_id' => $calificacion->id,
                    'subpregunta_id' => $respuestaSubpregunta['subpregunta_id']
                ];
                
                // Verificar qué campos tienen datos válidos
                $tieneOpcion = isset($respuestaSubpregunta['opcion_seleccionada']) 
                    && $respuestaSubpregunta['opcion_seleccionada'] !== null 
                    && $respuestaSubpregunta['opcion_seleccionada'] !== '';
                
                $tieneOpciones = isset($respuestaSubpregunta['opciones_seleccionadas']) 
                    && $respuestaSubpregunta['opciones_seleccionadas'] !== null;
                
                $tieneTexto = isset($respuestaSubpregunta['texto_respuesta']) 
                    && $respuestaSubpregunta['texto_respuesta'] !== null 
                    && trim($respuestaSubpregunta['texto_respuesta']) !== '';
                
                $tieneValor = isset($respuestaSubpregunta['valor_indicador']) 
                    && $respuestaSubpregunta['valor_indicador'] !== null;
                
                // Agregar solo los campos que tienen valores
                if ($tieneOpcion) {
                    $dataSubpregunta['opcion_seleccionada'] = trim($respuestaSubpregunta['opcion_seleccionada']);
                }
                
                if ($tieneOpciones) {
                    // 🔥 CORRECCIÓN: El modelo tiene cast 'array', así que pasar como array
                    if (is_string($respuestaSubpregunta['opciones_seleccionadas'])) {
                        // Si viene como string JSON, decodificar primero
                        $decoded = json_decode($respuestaSubpregunta['opciones_seleccionadas'], true);
                        $dataSubpregunta['opciones_seleccionadas'] = $decoded !== null ? $decoded : [];
                    } else {
                        // Si ya es array, pasar directamente
                        $dataSubpregunta['opciones_seleccionadas'] = $respuestaSubpregunta['opciones_seleccionadas'];
                    }
                }
                
                if ($tieneTexto) {
                    $dataSubpregunta['texto_respuesta'] = trim($respuestaSubpregunta['texto_respuesta']);
                }
                
                if ($tieneValor) {
                    $dataSubpregunta['valor_indicador'] = (int)$respuestaSubpregunta['valor_indicador'];
                }
                
                // 🔥 VALIDACIÓN: Debe tener al menos un campo de respuesta válido
                $tieneDatos = $tieneOpcion || $tieneOpciones || $tieneTexto || $tieneValor;
                
                if (!$tieneDatos) {
                    Log::warning('⚠️ Respuesta de subpregunta sin datos válidos, saltando...', [
                        'subpregunta_id' => $respuestaSubpregunta['subpregunta_id'],
                        'datos_recibidos' => $respuestaSubpregunta
                    ]);
                    continue; // Saltar esta respuesta
                }
                
                Log::info('💾 Guardando respuesta de subpregunta:', $dataSubpregunta);
                try {
                    \App\Models\RespuestaSubpregunta::create($dataSubpregunta);
                    Log::info('✅ Respuesta de subpregunta guardada exitosamente');
                } catch (\Exception $e) {
                    Log::error('❌ Error guardando respuesta de subpregunta: ' . $e->getMessage());
                    Log::error('❌ Stack trace: ' . $e->getTraceAsString());
                    Log::error('❌ Datos que causaron el error:', $dataSubpregunta);
                    throw $e; // Re-lanzar para que se capture en el catch principal
                }
            }
        }

        DB::commit();

        Log::info('🎉 Calificación completada exitosamente');

        return response()->json([
            'message' => 'Calificación guardada exitosamente',
            'calificacion_id' => $calificacion->id
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('❌ Error guardando calificación completa: ' . $e->getMessage());
        Log::error('❌ Stack trace: ' . $e->getTraceAsString());
        return response()->json(['error' => 'Error al guardar calificación: ' . $e->getMessage()], 500);
    }
}

}