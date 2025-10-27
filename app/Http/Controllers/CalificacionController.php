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
            'nivel_calificacion_id' => 'required|exists:niveles_calificacion,id',
            'respuestas' => 'required|array',
            'respuestas_subpreguntas' => 'sometimes|array',
            'respuestas_rangos' => 'sometimes|array'
        ]);

        // AHORA SÍ podemos loguear los datos validados
        Log::info('🔍 RESPuestas_rangos:', isset($validated['respuestas_rangos']) ? $validated['respuestas_rangos'] : 'NO HAY');
        Log::info('Datos validados:', $validated);

        // Crear la calificación principal
        $calificacionData = [
            'user_id' => Auth::id() ?? null,
            'area_id' => $validated['area_id'],
            'sede_id' => $validated['sede_id'],
            'nivel_calificacion_id' => $validated['nivel_calificacion_id'],
        ];

        Log::info('Creando calificación con datos:', $calificacionData);

        $calificacion = Calificacion::create($calificacionData);
        Log::info('Calificación creada ID: ' . $calificacion->id);

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
                    $respuestaData['opcion_seleccionada_id'] = $respuesta;
                    break;
                    
                case 'opcion_multiple':
                    $respuestaData['respuesta_texto'] = json_encode($respuesta);
                    break;
                    
                case 'texto_libre':
                    $respuestaData['respuesta_texto'] = $respuesta;
                    break;
                    
                case 'indicador_0_10':
                    $respuestaData['respuesta_texto'] = (string)$respuesta;
                    break;
                    
                case 'opcion_unica_texto_libre':
                    if (is_array($respuesta) && isset($respuesta['opcion_seleccionada_id'])) {
                        $respuestaData['opcion_seleccionada_id'] = $respuesta['opcion_seleccionada_id'];
                        if (!empty($respuesta['texto_libre'])) {
                            $respuestaData['respuesta_texto'] = $respuesta['texto_libre'];
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

        $respuestaRangoData = [
            'calificacion_id' => $calificacion->id,
            'pregunta_id' => 1, // Usar pregunta_id válido
            'es_pregunta_rango' => true,
            'pregunta_rango_id' => $respuestaRango['pregunta_rango_id'],
        ];

        // Procesar respuesta (mantener tu código actual)
        if (isset($respuestaRango['texto_respuesta']) && !empty(trim($respuestaRango['texto_respuesta']))) {
            $respuestaRangoData['respuesta_texto'] = trim($respuestaRango['texto_respuesta']);
        }
                elseif (isset($respuestaRango['opcion_seleccionada']) && !empty(trim($respuestaRango['opcion_seleccionada']))) {
                    // Para opciones de rango, guardar el texto directamente
                    $respuestaRangoData['respuesta_texto'] = trim($respuestaRango['opcion_seleccionada']);
                }
                elseif (isset($respuestaRango['opciones_seleccionadas']) && is_array($respuestaRango['opciones_seleccionadas']) && !empty($respuestaRango['opciones_seleccionadas'])) {
                    $respuestaRangoData['opciones_seleccionadas'] = json_encode($respuestaRango['opciones_seleccionadas']);
                }
                elseif (isset($respuestaRango['valor_indicador']) && $respuestaRango['valor_indicador'] !== '') {
                    $respuestaRangoData['respuesta_texto'] = (string)$respuestaRango['valor_indicador'];
                }

                // Si no hay datos válidos, saltar
                if (empty($respuestaRangoData['respuesta_texto']) && empty($respuestaRangoData['opciones_seleccionadas'])) {
                    Log::warning('❌ Respuesta de rango sin datos válidos, saltando...', $respuestaRangoData);
                    continue;
                }

                Log::info('💾 Guardando respuesta de rango:', $respuestaRangoData);
                RespuestaCalificacion::create($respuestaRangoData);
                Log::info('✅ Respuesta de rango guardada exitosamente');
            }
        } else {
            Log::info('📭 No hay respuestas de rangos para procesar');
        }

        // 3. Procesar respuestas de subpreguntas
        if (isset($validated['respuestas_subpreguntas'])) {
            Log::info('📥 Procesando respuestas de subpreguntas:', $validated['respuestas_subpreguntas']);
            
            foreach ($validated['respuestas_subpreguntas'] as $respuestaSubpregunta) {
                \App\Models\RespuestaSubpregunta::create([
                    'calificacion_id' => $calificacion->id,
                    'subpregunta_id' => $respuestaSubpregunta['subpregunta_id'],
                    'opcion_seleccionada' => $respuestaSubpregunta['opcion_seleccionada'],
                    'opciones_seleccionadas' => $respuestaSubpregunta['opciones_seleccionadas'] 
                        ? json_encode($respuestaSubpregunta['opciones_seleccionadas'])
                        : null,
                    'texto_respuesta' => $respuestaSubpregunta['texto_respuesta'],
                    'valor_indicador' => $respuestaSubpregunta['valor_indicador']
                ]);
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