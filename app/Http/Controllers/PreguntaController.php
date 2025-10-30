<?php

namespace App\Http\Controllers;

use App\Models\Pregunta;
use App\Models\OpcionPregunta;
use App\Models\Subpregunta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Calificacion;
use App\Models\RespuestaSubpregunta;

class PreguntaController extends Controller
{

 public function index(Request $request)
{
    try {
        $sedeId = $request->get('sede_id');
        $areaId = $request->get('area_id');
        $nivelId = $request->get('nivel_id');
        
        // 🔥 CORRECCIÓN: Cargar opciones con sus subpreguntas para FCR
        $query = Pregunta::with(['opciones.subpreguntas', 'nivelCalificacion', 'subpreguntasRango'])
            ->where('is_active', true);

        // ✅ FILTRAR POR NIVEL DE CALIFICACIÓN (siempre necesario)
        // 🔥 EXCEPCIÓN: Solo las preguntas genéricas (NPS, CSAT, FCR SIN nivel específico) están disponibles para todos los niveles
        if ($nivelId && $nivelId !== 'todas') {
            Log::info("🔍 FILTRANDO preguntas por nivel_id: " . $nivelId);
            $query->where(function($q) use ($nivelId) {
                // Preguntas específicas del nivel
                $q->where('niveles_calificacion_id', $nivelId)
                  // O preguntas genéricas (tipo_pregunta no nulo Y SIN nivel específico)
                  ->orWhere(function($subQ) {
                      $subQ->whereNotNull('tipo_pregunta')
                           ->whereNull('niveles_calificacion_id');
                  });
            });
        }

        // 🔥 NUEVO: Para filtrado por área Y sede, usar tabla area_pregunta
        $preguntasIdsFiltradas = null;
        
        if ($areaId && $areaId !== 'todas') {
            Log::info("🔍 FILTRANDO preguntas por area_id: " . $areaId);
            
            // Buscar IDs de preguntas relacionadas a esta área
            $queryRelacionadas = DB::table('area_pregunta')
                ->where('area_id', $areaId)
                ->where('is_active', true);
            
            // Si hay filtro por sede, agregarlo
            if ($sedeId && $sedeId !== 'todas') {
                Log::info("🔍 FILTRANDO preguntas por sede_id: " . $sedeId);
                $queryRelacionadas->where('sede_id', $sedeId);
            }
            
            $preguntasIdsRelacionadas = $queryRelacionadas->pluck('pregunta_id');
            
            // Solo usar preguntas relacionadas en tabla pivote (ya no hay area_id directo)
            $query->whereIn('id', $preguntasIdsRelacionadas);
        } else if ($sedeId && $sedeId !== 'todas') {
            // Si solo hay filtro por sede (sin área)
            Log::info("🔍 FILTRANDO preguntas por sede_id: " . $sedeId);
            $preguntasIdsConSede = DB::table('area_pregunta')
                ->where('sede_id', $sedeId)
                ->where('is_active', true)
                ->pluck('pregunta_id');
            
            // Solo usar preguntas relacionadas en tabla pivote (ya no hay sede_id directo)
            $query->whereIn('id', $preguntasIdsConSede);
        }
        
        // 🔥 CORRECCIÓN: Mantener ordenamiento por nivel específico, luego por ID
        if ($nivelId && $nivelId !== 'todas') {
            $preguntas = $query->orderByRaw("CASE WHEN niveles_calificacion_id = ? THEN 0 ELSE 1 END", [$nivelId])
                               ->orderBy('id', 'desc')
                               ->get();
        } else {
            $preguntas = $query->orderBy('id', 'desc')->get();
        }
        
        // 🔥 NUEVO: Agregar áreas y sedes relacionadas a cada pregunta
        foreach ($preguntas as $pregunta) {
            // Cargar áreas desde tabla area_pregunta (obtener DISTINCT area_id)
            $areasRelacionadas = DB::table('area_pregunta')
                ->where('pregunta_id', $pregunta->id)
                ->where('is_active', true)
                ->distinct()
                ->pluck('area_id')
                ->toArray();
            
            $pregunta->areas_participantes = DB::table('areas')
                ->whereIn('id', $areasRelacionadas)
                ->select('id', 'nombre', 'codigo')
                ->get();
            
            // Obtener sedes relacionadas desde tabla pivote (obtener DISTINCT sede_id)
            $sedesRelacionadas = DB::table('area_pregunta')
                ->where('pregunta_id', $pregunta->id)
                ->where('is_active', true)
                ->whereNotNull('sede_id')
                ->distinct()
                ->pluck('sede_id')
                ->toArray();
            
            if (!empty($sedesRelacionadas)) {
                $pregunta->sedes_participantes = DB::table('sedes')
                    ->whereIn('id', $sedesRelacionadas)
                    ->select('id', 'nombre')
                    ->get();
            }
            
            // 🔥 NUEVO: Agregar subpreguntas de rango procesadas
            if ($pregunta->tipo === 'indicador_0_10' && $pregunta->subpreguntasRango) {
                $pregunta->subpreguntas_rango = $pregunta->subpreguntasRango->map(function($sp) {
                    // Procesar opciones desde JSON string a array
                    $opcionesArray = [];
                    if ($sp->opciones) {
                        if (is_string($sp->opciones)) {
                            try {
                                $opcionesArray = json_decode($sp->opciones, true);
                                if (!is_array($opcionesArray)) {
                                    $opcionesArray = [];
                                }
                            } catch (\Exception $e) {
                                Log::warning('Error parseando opciones de rango: ' . $e->getMessage());
                                $opcionesArray = [];
                            }
                        } elseif (is_array($sp->opciones)) {
                            $opcionesArray = $sp->opciones;
                        }
                    }
                    
                    return [
                        'id' => $sp->id,
                        'rango_min' => $sp->rango_min,
                        'rango_max' => $sp->rango_max,
                        'pregunta_texto' => $sp->pregunta_texto,
                        'tipo' => $sp->tipo,
                        'opciones' => $opcionesArray, // 🔥 CORRECCIÓN: Enviar como array, no JSON string
                        'is_active' => $sp->is_active
                    ];
                })->toArray();
            } else {
                $pregunta->subpreguntas_rango = [];
            }
            
            // 🔥 CORRECCIÓN: Procesar subpreguntas de opciones para FCR
            if ($pregunta->tipo_pregunta === 'fcr' && $pregunta->opciones) {
                foreach ($pregunta->opciones as $opcion) {
                    if ($opcion->subpreguntas) {
                        // Las subpreguntas ya vienen cargadas desde el eager loading
                        // Solo necesitamos procesar las opciones JSON
                        $opcion->subpreguntas = $opcion->subpreguntas->map(function($sub) {
                            $opcionesArray = [];
                            if ($sub->opciones) {
                                if (is_string($sub->opciones)) {
                                    try {
                                        $opcionesArray = json_decode($sub->opciones, true);
                                    } catch (\Exception $e) {
                                        Log::warning('Error parseando opciones de subpregunta: ' . $e->getMessage());
                                        $opcionesArray = [];
                                    }
                                } else {
                                    $opcionesArray = $sub->opciones;
                                }
                            }
                            return [
                                'id' => $sub->id,
                                'pregunta_texto' => $sub->pregunta_texto,
                                'tipo' => $sub->tipo,
                                'opciones' => $opcionesArray
                            ];
                        })->toArray();
                    }
                }
            }
        }

        Log::info("📊 Preguntas encontradas: " . $preguntas->count() . 
                 " para sede: " . $sedeId . 
                 ", área: " . $areaId . 
                 ", nivel: " . $nivelId);
        
        return response()->json($preguntas);
        
    } catch (\Exception $e) {
        Log::error('Error in PreguntaController index: ' . $e->getMessage());
        return response()->json(['error' => 'Error interno del servidor'], 500);
    }
}


public function store(Request $request)
{
    try {
        DB::beginTransaction();

        $validated = $request->validate([
            'pregunta' => 'required|string|max:500',
            'niveles_calificacion_id' => 'nullable|exists:niveles_calificacion,id',
            'tipo' => 'required|in:opcion_unica,opcion_multiple,texto_libre,indicador_0_10,opcion_unica_texto_libre',
            'tipo_pregunta' => 'nullable|in:csat,nps,fcr',
            'is_active' => 'boolean',
            'opciones' => 'sometimes|array',
            'configuracion_rangos' => 'sometimes|array',
            // Arrays de áreas y sedes para tabla pivote
            'areas_id' => 'sometimes|array',
            'sedes_id' => 'sometimes|array',
            // 🔥 NUEVO: Subpreguntas FCR
            'subpreguntas_fcr' => 'sometimes|array',
        ]);
        
        // 🔥 NUEVO: Validar que para CSAT no se use un nivel que ya tiene pregunta
        if (isset($validated['tipo_pregunta']) && $validated['tipo_pregunta'] === 'csat' && isset($validated['niveles_calificacion_id'])) {
            $nivelId = $validated['niveles_calificacion_id'];
            
            // Validar que el nivel esté en el rango correcto (1-4 para CSAT)
            if ($nivelId < 1 || $nivelId > 4) {
                DB::rollBack();
                return response()->json([
                    'error' => 'Los niveles de calificación CSAT deben estar entre 1 y 4 (Muy Insatisfecho a Muy Satisfecho).'
                ], 422);
            }
            
            // Verificar si ya existe una pregunta CSAT activa con este nivel
            $preguntaExistente = Pregunta::where('tipo_pregunta', 'csat')
                ->where('niveles_calificacion_id', $nivelId)
                ->where('is_active', true)
                ->first();
            
            if ($preguntaExistente) {
                DB::rollBack();
                Log::warning("Intento de crear pregunta CSAT duplicada para nivel {$nivelId}");
                return response()->json([
                    'error' => 'Ya existe una pregunta CSAT activa para este nivel de calificación. Solo se puede crear una pregunta por nivel (Muy Insatisfecho, Insatisfecho, Satisfecho, Muy Satisfecho).'
                ], 422);
            }
        }

        // Crear la pregunta principal (sin area_id ni sede_id - se usarán en tabla pivote)
        // 🔥 CORREGIDO: niveles_calificacion_id puede ser null para preguntas genéricas (FCR/NPS)
        $pregunta = Pregunta::create([
            'pregunta' => $validated['pregunta'],
            'niveles_calificacion_id' => $validated['niveles_calificacion_id'] ?? null,
            'tipo' => $validated['tipo'],
            'tipo_pregunta' => $validated['tipo_pregunta'] ?? null,
            'is_active' => $validated['is_active'] ?? true
        ]);

        // 🔥 NUEVO: Preparar mapa de opciones para subpreguntas FCR
        $opcionesMap = [];
        $opcionesCreadas = [];
        
        // Crear opciones si es necesario (para tipos que las requieren)
        if (isset($validated['opciones']) && is_array($validated['opciones'])) {
            foreach ($validated['opciones'] as $index => $opcionTexto) {
                $opcionTexto = trim($opcionTexto);
                if (!empty($opcionTexto)) {
                    $opcion = OpcionPregunta::create([
                        'pregunta_id' => $pregunta->id,
                        'opcion' => $opcionTexto,
                        'tiene_subpreguntas' => false,
                        'orden' => $index + 1
                    ]);
                    $opcionesCreadas[$index] = $opcion;
                }
            }
            
            // 🔥 CORRECCIÓN CRÍTICA: Para opcion_unica_texto_libre, asegurar que tenga opción "Otro"
            if ($validated['tipo'] === 'opcion_unica_texto_libre') {
                $tieneOpcionOtro = $pregunta->opciones()
                    ->where(function($query) {
                        $query->where('opcion', 'like', '%otro%')
                              ->orWhere('opcion', 'like', '%especifique%');
                    })
                    ->exists();
                    
                if (!$tieneOpcionOtro) {
                    // Agregar automáticamente la opción "Otro" al final
                    OpcionPregunta::create([
                        'pregunta_id' => $pregunta->id,
                        'opcion' => 'Otro - especifique',
                        'tiene_subpreguntas' => false,
                        'orden' => $pregunta->opciones()->count() + 1
                    ]);
                    Log::info("✅ Opción 'Otro - especifique' agregada automáticamente a pregunta: " . $pregunta->id);
                }
            }
        }

        // 🔥 NUEVO: Crear subpreguntas FCR si existen
        if (isset($validated['subpreguntas_fcr']) && is_array($validated['subpreguntas_fcr'])) {
            Log::info('🔥 Procesando subpreguntas FCR:', $validated['subpreguntas_fcr']);
            
            foreach ($validated['subpreguntas_fcr'] as $opcionData) {
                $indice = $opcionData['indice'] ?? null;
                $subpreguntas = $opcionData['subpreguntas'] ?? [];
                
                if ($indice !== null && isset($opcionesCreadas[$indice]) && !empty($subpreguntas)) {
                    $opcion = $opcionesCreadas[$indice];
                    $tieneSubpreguntas = false;
                    
                    foreach ($subpreguntas as $subpreguntaData) {
                        $tieneSubpreguntas = true;
                        Subpregunta::create([
                            'opcion_pregunta_id' => $opcion->id,
                            'pregunta_texto' => $subpreguntaData['pregunta_texto'],
                            'tipo' => $subpreguntaData['tipo'],
                            'opciones' => !empty($subpreguntaData['opciones']) 
                                ? json_encode($subpreguntaData['opciones']) 
                                : null,
                            'is_active' => true
                        ]);
                        
                        Log::info("✅ Subpregunta creada para opción: {$opcion->opcion}");
                    }
                    
                    // Actualizar estado de la opción
                    if ($tieneSubpreguntas) {
                        $opcion->update(['tiene_subpreguntas' => true]);
                    }
                }
            }
        }

        // 🔥 NUEVO: Crear preguntas de rango si es un indicador
        if ($validated['tipo'] === 'indicador_0_10' && isset($validated['configuracion_rangos'])) {
            $this->crearPreguntasRango($pregunta->id, $validated['configuracion_rangos']);
        }

        // 🔥 NUEVO: Guardar relaciones con múltiples áreas (preguntas específicas)
        if (isset($validated['areas_id']) && is_array($validated['areas_id']) && isset($validated['sedes_id']) && is_array($validated['sedes_id'])) {
            foreach ($validated['areas_id'] as $areaId) {
                foreach ($validated['sedes_id'] as $sedeId) {
                    DB::table('area_pregunta')->insert([
                        'area_id' => $areaId,
                        'sede_id' => $sedeId,
                        'pregunta_id' => $pregunta->id,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }
            Log::info("✅ Pregunta {$pregunta->id} asignada a " . count($validated['areas_id']) . " áreas y " . count($validated['sedes_id']) . " sedes");
        }
        
        // 🔥 NUEVO: Si es pregunta de tipo CSAT/NPS/FCR, asignar automáticamente SOLO a áreas que tienen el tipo habilitado
        if (isset($validated['tipo_pregunta']) && in_array($validated['tipo_pregunta'], ['csat', 'nps', 'fcr'])) {
            $tipo = $validated['tipo_pregunta'];
            Log::info("🔧 Creando pregunta genérica tipo: {$tipo}");
            
            // 🔥 Obtener SOLO las áreas que tienen este tipo HABILITADO
            $indicador = 'permite_' . $tipo;
            $areasHabilitadas = DB::table('areas')
                ->where('is_active', 1)
                ->where($indicador, 1) // Solo áreas con este tipo habilitado
                ->select('id', 'nombre', 'sede_id')
                ->get();
            
            Log::info("📋 Total de áreas con {$tipo} habilitado: {$areasHabilitadas->count()}");
            
            // 🔥 Crear registros para cada área con su sede específica
            $registrosCreados = 0;
            
            foreach ($areasHabilitadas as $area) {
                // Verificar si ya existe la relación
                $existe = DB::table('area_pregunta')
                    ->where('area_id', $area->id)
                    ->where('pregunta_id', $pregunta->id)
                    ->where('sede_id', $area->sede_id)
                    ->exists();
                
                if (!$existe) {
                    DB::table('area_pregunta')->insert([
                        'area_id' => $area->id,
                        'sede_id' => $area->sede_id,  // Usar la sede del área
                        'pregunta_id' => $pregunta->id,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    $registrosCreados++;
                    
                    Log::info("✅ Relación creada: Área {$area->nombre} (ID: {$area->id}) + Sede ID: {$area->sede_id}");
                }
            }
            
            Log::info("🎉 Total de relaciones creadas para pregunta {$pregunta->id}: {$registrosCreados}");
        }

        // 🔥 NUEVO: Si hay sedes múltiples, duplicar pregunta para cada sede
        if (isset($validated['sedes_id']) && is_array($validated['sedes_id']) && count($validated['sedes_id']) > 1) {
            // Ya se creó para la primera sede, crear para las demás
            $primerSedeId = $validated['sedes_id'][0];
            for ($i = 1; $i < count($validated['sedes_id']); $i++) {
                $nuevaPreguntaId = Pregunta::create([
                    'pregunta' => $pregunta->pregunta,
                    'tipo' => $pregunta->tipo,
                    'tipo_pregunta' => $pregunta->tipo_pregunta,
                    'area_id' => $validated['area_id'] ?? null,
                    'niveles_calificacion_id' => $pregunta->niveles_calificacion_id,
                    'sede_id' => $validated['sedes_id'][$i],
                    'is_active' => $pregunta->is_active,
                ])->id;
                
                // Copiar opciones
                if ($pregunta->opciones()->count() > 0) {
                    foreach ($pregunta->opciones as $opcion) {
                        OpcionPregunta::create([
                            'pregunta_id' => $nuevaPreguntaId,
                            'opcion' => $opcion->opcion,
                            'tiene_subpreguntas' => $opcion->tiene_subpreguntas,
                            'orden' => $opcion->orden
                        ]);
                    }
                }
                
                // Asignar a las mismas áreas
                if (isset($validated['areas_id'])) {
                    foreach ($validated['areas_id'] as $areaId) {
                        DB::table('area_pregunta')->insert([
                            'area_id' => $areaId,
                            'pregunta_id' => $nuevaPreguntaId,
                            'is_active' => true,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }
                }
            }
        }

        DB::commit();

        // Recargar la pregunta con las relaciones
        $pregunta->load('opciones');
        $pregunta->opciones_count = $pregunta->opciones->count();

        return response()->json($pregunta, 201);
        
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error creating pregunta: ' . $e->getMessage());
        return response()->json(['error' => 'Error al crear la pregunta: ' . $e->getMessage()], 500);
    }
}

/**
 * 🔥 NUEVO: Crear preguntas de rango para indicador
 */
private function crearPreguntasRango($preguntaIndicadorId, $configuracionRangos)
{
    foreach ($configuracionRangos as $config) {
        // Solo crear si el rango está activo y tiene pregunta
        if ($config['activo'] && !empty(trim($config['pregunta_texto']))) {
            // Ahora el rango viene directamente en el objeto
            $rangoMin = $config['inicio'];
            $rangoMax = $config['fin'];

            // Crear la subpregunta (pregunta de rango)
            $subpregunta = Subpregunta::create([
                'pregunta_indicador_id' => $preguntaIndicadorId,
                'pregunta_texto' => trim($config['pregunta_texto']),
                'tipo' => $config['tipo'],
                'opciones' => !empty($config['opciones']) && is_array($config['opciones'])
                    ? json_encode(array_map(function($op) { 
                          return trim($op['texto'] ?? ''); 
                      }, array_filter($config['opciones'], function($op) {
                          return !empty(trim($op['texto'] ?? ''));
                      })))
                    : null,
                'is_active' => true,
                'es_rango_indicador' => true,
                'rango_min' => $rangoMin,
                'rango_max' => $rangoMax
            ]);

            // 🔥 CORRECCIÓN CRÍTICA: Para preguntas de rango de tipo opcion_unica_texto_libre, asegurar opción "Otro"
            if ($config['tipo'] === 'opcion_unica_texto_libre') {
                $opcionesArray = [];
                if (!empty($config['opciones']) && is_array($config['opciones'])) {
                    $opcionesArray = array_map(function($op) { 
                        return trim($op['texto'] ?? ''); 
                    }, array_filter($config['opciones'], function($op) {
                        return !empty(trim($op['texto'] ?? ''));
                    }));
                }
                
                // Verificar si ya tiene opción "Otro"
                $tieneOpcionOtro = false;
                foreach ($opcionesArray as $opcion) {
                    if (strpos(strtolower($opcion), 'otro') !== false || 
                        strpos(strtolower($opcion), 'especifique') !== false) {
                        $tieneOpcionOtro = true;
                        break;
                    }
                }
                
                if (!$tieneOpcionOtro) {
                    // Agregar automáticamente la opción "Otro" al final
                    $opcionesArray[] = 'Otro - especifique';
                    
                    // Actualizar las opciones en la subpregunta
                    $subpregunta->update([
                        'opciones' => json_encode($opcionesArray)
                    ]);
                    
                    Log::info("✅ Opción 'Otro - especifique' agregada automáticamente a pregunta de rango: " . $subpregunta->id);
                }
            }

            Log::info("✅ Pregunta de rango creada: {$rangoMin}-{$rangoMax} para indicador {$preguntaIndicadorId}");
        }
    }
}
    public function update(Request $request, Pregunta $pregunta)
{
    try {
        DB::beginTransaction();

        $validated = $request->validate([
            'pregunta' => 'required|string|max:500',
            'area_id' => 'nullable|exists:areas,id', // 🔥 Cambiado a nullable para preguntas genéricas
            'niveles_calificacion_id' => 'nullable|exists:niveles_calificacion,id', // 🔥 Cambiado a nullable para preguntas genéricas (NPS, FCR)
            'sede_id' => 'nullable|exists:sedes,id', // 🔥 Cambiado a nullable para preguntas genéricas
            'tipo' => 'required|in:opcion_unica,opcion_multiple,texto_libre,indicador_0_10,opcion_unica_texto_libre',
            'tipo_pregunta' => 'nullable|in:csat,nps,fcr', // 🔥 Agregar tipo_pregunta
            'is_active' => 'boolean',
            'opciones' => 'sometimes|array',
            'configuracion_rangos' => 'sometimes|array', // 🔥 NUEVO
            'subpreguntas_fcr' => 'sometimes|array' // 🔥 NUEVO: Subpreguntas FCR
        ]);

        // 🔥 Preparar datos para actualizar (excluir campos null para preguntas genéricas)
        $datosUpdate = [
            'pregunta' => $validated['pregunta'],
            'tipo' => $validated['tipo'],
            'is_active' => $validated['is_active'] ?? $pregunta->is_active
        ];
        
        // Solo actualizar niveles_calificacion_id si está presente (puede ser null para NPS/FCR)
        if (isset($validated['niveles_calificacion_id'])) {
            $datosUpdate['niveles_calificacion_id'] = $validated['niveles_calificacion_id'];
        }
        
        // Solo actualizar area_id y sede_id si están presentes (no genéricas)
        if (isset($validated['area_id'])) {
            $datosUpdate['area_id'] = $validated['area_id'];
        }
        if (isset($validated['sede_id'])) {
            $datosUpdate['sede_id'] = $validated['sede_id'];
        }
        if (isset($validated['tipo_pregunta'])) {
            $datosUpdate['tipo_pregunta'] = $validated['tipo_pregunta'];
        }

        // Actualizar la pregunta principal
        $pregunta->update($datosUpdate);

        // Manejar opciones normales
        if (isset($validated['opciones']) && is_array($validated['opciones'])) {
            $this->actualizarOpcionesPregunta($pregunta, $validated['opciones']);
        }

        // 🔥 NUEVO: Manejar subpreguntas FCR
        if (isset($validated['subpreguntas_fcr']) && is_array($validated['subpreguntas_fcr'])) {
            $this->actualizarSubpreguntasFCR($pregunta, $validated['subpreguntas_fcr']);
        }

        // 🔥 NUEVO: Manejar preguntas de rango si es un indicador
        if ($validated['tipo'] === 'indicador_0_10') {
            $this->actualizarPreguntasRango($pregunta->id, $validated['configuracion_rangos'] ?? []);
        } else {
            // Si ya no es indicador, eliminar preguntas de rango
            $this->eliminarPreguntasRango($pregunta->id);
        }

        DB::commit();

        $pregunta->load('opciones');
        $pregunta->opciones_count = $pregunta->opciones->count();

        return response()->json($pregunta);
        
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error updating pregunta: ' . $e->getMessage());
        return response()->json(['error' => 'Error al actualizar la pregunta: ' . $e->getMessage()], 500);
    }
}

/**
 * 🔥 NUEVO: Eliminar preguntas de rango
 */
private function eliminarPreguntasRango($preguntaIndicadorId)
{
    Subpregunta::where('pregunta_indicador_id', $preguntaIndicadorId)
               ->where('es_rango_indicador', true)
               ->delete();
}

/**
 * 🔥 NUEVO: Actualizar preguntas de rango de forma inteligente
 */
private function actualizarPreguntasRango($preguntaIndicadorId, $configuracionRangos)
{
    Log::info("🔄 Actualizando rangos para pregunta indicador {$preguntaIndicadorId}");
    Log::info("📋 Rangos recibidos: " . count($configuracionRangos));
    
    // Obtener rangos existentes con sus IDs
    $rangosExistentes = Subpregunta::where('pregunta_indicador_id', $preguntaIndicadorId)
        ->where('es_rango_indicador', true)
        ->get()
        ->keyBy(function($sp) {
            return $sp->rango_min . '-' . $sp->rango_max;
        });
    
    // IDs de rangos que se deben mantener (vienen con ID en configuracionRangos)
    $idsAMantener = [];
    $rangosActualizados = [];
    
    foreach ($configuracionRangos as $config) {
        // Solo procesar si el rango está activo y tiene pregunta
        if ($config['activo'] && !empty(trim($config['pregunta_texto']))) {
            $rangoMin = $config['inicio'];
            $rangoMax = $config['fin'];
            $claveRango = $rangoMin . '-' . $rangoMax;
            
            // Procesar opciones (pueden venir como array de objetos {texto: '...'} o como strings)
            $opcionesFinales = [];
            if (!empty($config['opciones']) && is_array($config['opciones'])) {
                $opcionesFinales = array_map(function($op) {
                    if (is_array($op)) {
                        // Si es objeto, extraer el texto
                        return trim($op['texto'] ?? '');
                    }
                    // Si es string directo
                    return trim((string)$op);
                }, array_filter($config['opciones'], function($op) {
                    if (is_array($op)) {
                        $texto = $op['texto'] ?? '';
                    } else {
                        $texto = (string)$op;
                    }
                    return !empty(trim($texto));
                }));
            }
            
            Log::info("📋 Procesando rango {$rangoMin}-{$rangoMax}: " . count($opcionesFinales) . " opciones finales");
            
            // Si tiene ID, es un rango existente que se actualiza
            if (isset($config['id']) && $config['id']) {
                $subpregunta = Subpregunta::find($config['id']);
                if ($subpregunta && $subpregunta->pregunta_indicador_id == $preguntaIndicadorId) {
                    // Actualizar rango existente
                    $subpregunta->update([
                        'rango_min' => $rangoMin,
                        'rango_max' => $rangoMax,
                        'pregunta_texto' => trim($config['pregunta_texto']),
                        'tipo' => $config['tipo'],
                        'opciones' => !empty($opcionesFinales) ? json_encode($opcionesFinales) : null,
                        'is_active' => true,
                        'es_rango_indicador' => true
                    ]);
                    $idsAMantener[] = $subpregunta->id;
                    Log::info("✅ Rango actualizado (ID: {$subpregunta->id}): {$rangoMin}-{$rangoMax}");
                    continue;
                }
            }
            
            // Si no tiene ID o no se encontró, verificar si existe por rango
            if ($rangosExistentes->has($claveRango)) {
                $subpregunta = $rangosExistentes[$claveRango];
                $subpregunta->update([
                    'pregunta_texto' => trim($config['pregunta_texto']),
                    'tipo' => $config['tipo'],
                    'opciones' => !empty($opcionesFinales) ? json_encode($opcionesFinales) : null,
                    'is_active' => true
                ]);
                $idsAMantener[] = $subpregunta->id;
                Log::info("✅ Rango existente actualizado (ID: {$subpregunta->id}): {$rangoMin}-{$rangoMax}");
            } else {
                // Crear nuevo rango
                $subpregunta = Subpregunta::create([
                    'pregunta_indicador_id' => $preguntaIndicadorId,
                    'pregunta_texto' => trim($config['pregunta_texto']),
                    'tipo' => $config['tipo'],
                    'opciones' => !empty($opcionesFinales) ? json_encode($opcionesFinales) : null,
                    'is_active' => true,
                    'es_rango_indicador' => true,
                    'rango_min' => $rangoMin,
                    'rango_max' => $rangoMax
                ]);
                $idsAMantener[] = $subpregunta->id;
                Log::info("➕ Nuevo rango creado (ID: {$subpregunta->id}): {$rangoMin}-{$rangoMax}");
            }
            
            // Manejar "Otro" para opcion_unica_texto_libre
            if ($config['tipo'] === 'opcion_unica_texto_libre') {
                $tieneOpcionOtro = false;
                foreach ($opcionesFinales as $opcion) {
                    if (stripos($opcion, 'otro') !== false || stripos($opcion, 'especifique') !== false) {
                        $tieneOpcionOtro = true;
                        break;
                    }
                }
                
                if (!$tieneOpcionOtro) {
                    $opcionesFinales[] = 'Otro - especifique';
                    $subpregunta->update(['opciones' => json_encode($opcionesFinales)]);
                    Log::info("✅ Opción 'Otro - especifique' agregada a rango {$rangoMin}-{$rangoMax}");
                }
            }
        }
    }
    
    // Eliminar rangos que ya no están en la configuración
    if (!empty($idsAMantener)) {
        $eliminados = Subpregunta::where('pregunta_indicador_id', $preguntaIndicadorId)
            ->where('es_rango_indicador', true)
            ->whereNotIn('id', $idsAMantener)
            ->delete();
        
        if ($eliminados > 0) {
            Log::info("🗑️ Eliminados {$eliminados} rango(s) que ya no están en la configuración");
        }
    } else {
        // Si no hay rangos activos, eliminar todos
        $this->eliminarPreguntasRango($preguntaIndicadorId);
    }
}

// 🔥 NUEVO MÉTODO: Actualizar opciones de forma segura
private function actualizarOpcionesPregunta(Pregunta $pregunta, array $nuevasOpciones)
{
    // Recargar opciones frescas desde la BD
    $pregunta->refresh();
    $pregunta->load('opciones');
    $opcionesExistentes = $pregunta->opciones()->get();
    
    // Normalizar nuevas opciones: asegurarse de que son strings y limpiarlos
    $nuevasOpcionesNormalizadas = array_map(function($opcion) {
        if (is_array($opcion)) {
            return isset($opcion['texto']) ? trim($opcion['texto']) : '';
        }
        return trim((string)$opcion);
    }, $nuevasOpciones);
    
    // Filtrar opciones vacías
    $nuevasOpcionesNormalizadas = array_values(array_filter($nuevasOpcionesNormalizadas, function($opcion) {
        return !empty($opcion);
    }));
    
    Log::info("📋 === ACTUALIZANDO OPCIONES DE PREGUNTA {$pregunta->id} ===");
    Log::info("📋 Opciones existentes en BD (" . $opcionesExistentes->count() . "): " . $opcionesExistentes->pluck('opcion')->implode(', '));
    Log::info("📋 Nuevas opciones recibidas (" . count($nuevasOpcionesNormalizadas) . "): " . implode(', ', $nuevasOpcionesNormalizadas));
    
    // Crear arrays normalizados para comparación (sin mayúsculas/minúsculas)
    $nuevasOpcionesNormalizadasLower = array_map(function($op) {
        return strtolower(trim($op));
    }, $nuevasOpcionesNormalizadas);
    
    $idsAEliminar = [];
    
    // Identificar opciones a eliminar (solo las que no tienen respuestas)
    foreach ($opcionesExistentes as $opcionExistente) {
        $opcionExistenteTexto = trim($opcionExistente->opcion);
        $opcionExistenteLower = strtolower($opcionExistenteTexto);
        
        // Buscar si existe en las nuevas opciones (comparación insensible a mayúsculas)
        $encontrada = in_array($opcionExistenteLower, $nuevasOpcionesNormalizadasLower);
        
        if (!$encontrada) {
            // Verificar si la opción tiene respuestas asociadas
            $tieneRespuestas = $opcionExistente->respuestasCalificacion()->exists();
            
            if (!$tieneRespuestas) {
                $idsAEliminar[] = $opcionExistente->id;
                Log::info("🗑️ Opción marcada para eliminar (sin respuestas): '{$opcionExistenteTexto}' (ID: {$opcionExistente->id})");
            } else {
                Log::info("⚠️ Opción NO se eliminará (tiene respuestas): '{$opcionExistenteTexto}' (ID: {$opcionExistente->id})");
            }
        } else {
            Log::info("✓ Opción se mantiene: '{$opcionExistenteTexto}' (ID: {$opcionExistente->id})");
        }
    }
    
    // Eliminar opciones sin respuestas
    if (!empty($idsAEliminar)) {
        // Verificar una vez más que las opciones no tengan respuestas antes de eliminar
        $opcionesConRespuestas = [];
        foreach ($idsAEliminar as $id) {
            $opcion = OpcionPregunta::find($id);
            if ($opcion && $opcion->respuestasCalificacion()->exists()) {
                $opcionesConRespuestas[] = $id;
            }
        }
        
        $idsAEliminarFinal = array_diff($idsAEliminar, $opcionesConRespuestas);
        
        if (!empty($opcionesConRespuestas)) {
            Log::info("⚠️ Opciones con respuestas que NO se eliminarán: " . implode(', ', $opcionesConRespuestas));
        }
        
        if (!empty($idsAEliminarFinal)) {
            $eliminadas = OpcionPregunta::whereIn('id', $idsAEliminarFinal)->delete();
            Log::info("✅ Eliminadas {$eliminadas} opción(es) de la BD. IDs: " . implode(', ', $idsAEliminarFinal));
        } else {
            Log::info("ℹ️ Todas las opciones marcadas tienen respuestas, no se eliminará ninguna");
        }
    } else {
        Log::info("ℹ️ No hay opciones para eliminar");
    }
    
    // Recargar opciones después de eliminar
    $pregunta->refresh();
    $pregunta->load('opciones');
    
    // Agregar nuevas opciones o actualizar orden de existentes
    foreach ($nuevasOpcionesNormalizadas as $index => $opcionTexto) {
        $opcionTextoLower = strtolower(trim($opcionTexto));
        
        // Buscar si ya existe (comparación insensible a mayúsculas)
        $existe = $pregunta->opciones()
            ->get()
            ->first(function($op) use ($opcionTextoLower) {
                return strtolower(trim($op->opcion)) === $opcionTextoLower;
            });
            
        if (!$existe) {
            OpcionPregunta::create([
                'pregunta_id' => $pregunta->id,
                'opcion' => $opcionTexto,
                'orden' => $index + 1
            ]);
            Log::info("➕ Nueva opción creada: '{$opcionTexto}'");
        } else {
            // Actualizar el orden si cambió
            if ($existe->orden != ($index + 1)) {
                $existe->update(['orden' => $index + 1]);
                Log::info("🔄 Orden actualizado para opción: '{$opcionTexto}' (Orden: " . ($index + 1) . ")");
            }
        }
    }
    
    // Verificación final
    $pregunta->refresh();
    $pregunta->load('opciones');
    Log::info("📋 === OPCIONES FINALES EN BD (" . $pregunta->opciones->count() . "): " . $pregunta->opciones->pluck('opcion')->implode(', ') . " ===");
    
    // 🔥 CORRECCIÓN: Si NO es opcion_unica_texto_libre, eliminar cualquier opción "Otro" que exista
    if ($pregunta->tipo !== 'opcion_unica_texto_libre') {
        // Recargar opciones antes de buscar "Otro"
        $pregunta->refresh();
        $opcionesOtro = $pregunta->opciones()
            ->where(function($query) {
                $query->where('opcion', 'like', '%otro%')
                      ->orWhere('opcion', 'like', '%especifique%');
            })
            ->get();
            
        if ($opcionesOtro->count() > 0) {
            $idsOtro = $opcionesOtro->pluck('id')->toArray();
            OpcionPregunta::whereIn('id', $idsOtro)->delete();
            Log::info("🗑️ Opción(es) 'Otro - especifique' eliminada(s) porque el tipo ya no es opcion_unica_texto_libre. IDs: " . implode(', ', $idsOtro));
        }
    } else {
        // Recargar opciones antes de verificar "Otro"
        $pregunta->refresh();
        // Si SÍ es opcion_unica_texto_libre, asegurar que tenga opción "Otro"
        $tieneOpcionOtro = $pregunta->opciones()
            ->where(function($query) {
                $query->where('opcion', 'like', '%otro%')
                      ->orWhere('opcion', 'like', '%especifique%');
            })
            ->exists();
            
        if (!$tieneOpcionOtro) {
            // Agregar automáticamente la opción "Otro" al final
            $maxOrden = $pregunta->opciones()->max('orden') ?? 0;
            OpcionPregunta::create([
                'pregunta_id' => $pregunta->id,
                'opcion' => 'Otro - especifique',
                'tiene_subpreguntas' => false,
                'orden' => $maxOrden + 1
            ]);
            Log::info("✅ Opción 'Otro - especifique' agregada automáticamente a pregunta: " . $pregunta->id);
        }
    }
    
    // Recargar opciones final para reflejar todos los cambios
    $pregunta->refresh();
    $pregunta->load('opciones');
}

        public function destroy(Pregunta $pregunta)
{
    try {
        DB::beginTransaction();

        // 🔍 VERIFICAR RELACIONES EXISTENTES
        $tieneRespuestas = $pregunta->respuestasCalificacion()->exists();
        $tieneOpcionesConSubpreguntas = $pregunta->opciones()
            ->where('tiene_subpreguntas', true)
            ->exists();
        
        $tieneSubpreguntasRango = $pregunta->subpreguntasRango()->exists();

        if ($tieneRespuestas || $tieneOpcionesConSubpreguntas || $tieneSubpreguntasRango) {
            DB::rollBack();
            
            $detalles = $this->obtenerDetallesRelaciones($pregunta);
            
            return response()->json([
                'success' => false,
                'error' => 'No se puede eliminar la pregunta porque tiene datos relacionados',
                'detalles' => $detalles,
                'estadisticas' => [
                    'total_respuestas' => $pregunta->respuestasCalificacion()->count(),
                    'total_opciones_con_subpreguntas' => $pregunta->opciones()
                        ->where('tiene_subpreguntas', true)->count(),
                    'total_subpreguntas_rango' => $pregunta->subpreguntasRango()->count()
                ]
            ], 422);
        }

        // ✅ ELIMINACIÓN SEGURA - Primero eliminar opciones
        $pregunta->opciones()->delete();
        
        // Eliminar la pregunta
        $pregunta->delete();

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Pregunta eliminada correctamente'
        ]);
        
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error deleting pregunta: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'error' => 'Error al eliminar la pregunta: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Obtener detalles de las relaciones para mostrar al usuario
 */
private function obtenerDetallesRelaciones(Pregunta $pregunta)
{
    $detalles = [];

    // Respuestas directas
    if ($pregunta->respuestasCalificacion()->exists()) {
        $respuestasCount = $pregunta->respuestasCalificacion()->count();
        $detalles[] = "• {$respuestasCount} respuesta(s) de calificación registrada(s)";
    }

    // Opciones con subpreguntas
    $opcionesConSubpreguntas = $pregunta->opciones()
        ->where('tiene_subpreguntas', true)
        ->get();
    
    if ($opcionesConSubpreguntas->count() > 0) {
        $detalles[] = "• {$opcionesConSubpreguntas->count()} opción(es) con subpreguntas configuradas";
        
        foreach ($opcionesConSubpreguntas as $opcion) {
            $subpreguntasCount = $opcion->subpreguntas()->count();
            $detalles[] = "  - Opción '{$opcion->opcion}': {$subpreguntasCount} subpregunta(s)";
        }
    }

    // Subpreguntas de rango
    if ($pregunta->subpreguntasRango()->exists()) {
        $rangosCount = $pregunta->subpreguntasRango()->count();
        $detalles[] = "• {$rangosCount} pregunta(s) de rango configurada(s)";
    }

    return $detalles;
}

    public function toggleStatus(Pregunta $pregunta)
    {
        try {
            $pregunta->update([
                'is_active' => !$pregunta->is_active
            ]);

            return response()->json([
                'message' => 'Estado actualizado correctamente',
                'is_active' => $pregunta->is_active
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error toggling pregunta status: ' . $e->getMessage());
            return response()->json(['error' => 'Error al cambiar el estado: ' . $e->getMessage()], 500);
        }
    }

    // Método para obtener opciones de una pregunta
    public function getOpciones(Pregunta $pregunta)
    {
        try {
            $opciones = $pregunta->opciones;
            return response()->json($opciones);
        } catch (\Exception $e) {
            Log::error('Error getting opciones: ' . $e->getMessage());
            return response()->json(['error' => 'Error al obtener las opciones'], 500);
        }
    }
    // Agrega este método al controlador
public function obtenerPreguntaRango($preguntaId, $valor)
{
    try {
        Log::info("🔍 Buscando pregunta de rango para pregunta: {$preguntaId}, valor: {$valor}");
        
        $preguntaRango = Subpregunta::porRangoIndicador($preguntaId, $valor)->first();
        
        if ($preguntaRango) {
            Log::info("✅ Pregunta de rango encontrada: " . $preguntaRango->id);
            
            // Cargar opciones si las tiene
            $preguntaRango->opciones_array = $preguntaRango->opciones_array;
            
            return response()->json($preguntaRango);
        }
        
        Log::info("📭 No se encontró pregunta de rango");
        return response()->json(null);
        
    } catch (\Exception $e) {
        Log::error('Error obteniendo pregunta de rango: ' . $e->getMessage());
        return response()->json(['error' => 'Error al obtener pregunta de rango'], 500);
    }
}

/**
 * Verificar si una pregunta puede ser eliminada
 */
public function verificarEliminacion(Pregunta $pregunta)
{
    try {
        $tieneRespuestas = $pregunta->respuestasCalificacion()->exists();
        $tieneOpcionesConSubpreguntas = $pregunta->opciones()
            ->where('tiene_subpreguntas', true)
            ->exists();
        $tieneSubpreguntasRango = $pregunta->subpreguntasRango()->exists();

        $puedeEliminar = !($tieneRespuestas || $tieneOpcionesConSubpreguntas || $tieneSubpreguntasRango);

        return response()->json([
            'puede_eliminar' => $puedeEliminar,
            'detalles' => $puedeEliminar ? [] : $this->obtenerDetallesRelaciones($pregunta),
            'estadisticas' => [
                'total_respuestas' => $pregunta->respuestasCalificacion()->count(),
                'total_opciones_con_subpreguntas' => $pregunta->opciones()
                    ->where('tiene_subpreguntas', true)->count(),
                'total_subpreguntas_rango' => $pregunta->subpreguntasRango()->count()
            ]
        ]);

    } catch (\Exception $e) {
        Log::error('Error verificando eliminación: ' . $e->getMessage());
        return response()->json([
            'puede_eliminar' => false,
            'error' => 'Error al verificar la pregunta'
        ], 500);
    }
}

/**
 * Eliminación forzada con todos los datos relacionados
 */
public function eliminarForzado(Pregunta $pregunta)
{
    DB::beginTransaction();
    
    try {
        Log::info("🗑️ Iniciando eliminación forzada de pregunta ID: {$pregunta->id}");

        // 1. Eliminar respuestas de subpreguntas primero
        $opcionesIds = $pregunta->opciones()->pluck('id');
        if ($opcionesIds->count() > 0) {
            $subpreguntasIds = Subpregunta::whereIn('opcion_pregunta_id', $opcionesIds)
                ->pluck('id');
                
            if ($subpreguntasIds->count() > 0) {
                RespuestaSubpregunta::whereIn('subpregunta_id', $subpreguntasIds)->delete();
                Log::info("✅ Eliminadas respuestas de subpreguntas: " . $subpreguntasIds->count());
            }
        }

        // 2. Eliminar subpreguntas de opciones
        Subpregunta::whereIn('opcion_pregunta_id', $opcionesIds)->delete();
        Log::info("✅ Eliminadas subpreguntas de opciones");

        // 3. Eliminar subpreguntas de rango
        $pregunta->subpreguntasRango()->delete();
        Log::info("✅ Eliminadas subpreguntas de rango");

        // 4. Eliminar respuestas directas
        $pregunta->respuestasCalificacion()->delete();
        Log::info("✅ Eliminadas respuestas directas");

        // 5. Eliminar opciones
        $pregunta->opciones()->delete();
        Log::info("✅ Eliminadas opciones");

        // 6. Finalmente eliminar la pregunta
        $pregunta->delete();
        Log::info("✅ Pregunta eliminada");

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Pregunta y todos sus datos relacionados eliminados correctamente',
            'estadisticas' => [
                'opciones_eliminadas' => $opcionesIds->count(),
                'subpreguntas_eliminadas' => $subpreguntasIds->count() ?? 0
            ]
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error en eliminación forzada: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'error' => 'Error al eliminar la pregunta: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * 🔥 NUEVO: Actualizar subpreguntas FCR
 */
private function actualizarSubpreguntasFCR(Pregunta $pregunta, array $subpreguntasFCR)
{
    // Recargar las opciones para tener los IDs actualizados
    $pregunta->load('opciones');
    
    foreach ($subpreguntasFCR as $opcionData) {
        $indice = $opcionData['indice'] ?? null;
        $subpreguntas = $opcionData['subpreguntas'] ?? [];
        
        if ($indice !== null && isset($pregunta->opciones[$indice])) {
            $opcion = $pregunta->opciones[$indice];
            
            // Eliminar subpreguntas existentes
            Subpregunta::where('opcion_pregunta_id', $opcion->id)->delete();
            
            // Crear nuevas subpreguntas
            if (!empty($subpreguntas)) {
                foreach ($subpreguntas as $subpreguntaData) {
                    Subpregunta::create([
                        'opcion_pregunta_id' => $opcion->id,
                        'pregunta_texto' => $subpreguntaData['pregunta_texto'],
                        'tipo' => $subpreguntaData['tipo'],
                        'opciones' => !empty($subpreguntaData['opciones']) 
                            ? json_encode($subpreguntaData['opciones']) 
                            : null,
                        'is_active' => true
                    ]);
                }
                $opcion->update(['tiene_subpreguntas' => true]);
            } else {
                $opcion->update(['tiene_subpreguntas' => false]);
            }
        }
    }
}
}