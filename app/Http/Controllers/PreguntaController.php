<?php

namespace App\Http\Controllers;

use App\Models\Pregunta;
use App\Models\OpcionPregunta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Calificacion;
use App\Models\Subpregunta;
use App\Models\RespuestaSubpregunta;

class PreguntaController extends Controller
{

 public function index(Request $request)
{
    try {
        $sedeId = $request->get('sede_id');
        $areaId = $request->get('area_id');
        $nivelId = $request->get('nivel_id');
        
        $query = Pregunta::with(['opciones', 'area', 'nivelCalificacion', 'sede'])
            ->where('is_active', true);

        // ✅ FILTRAR POR NIVEL DE CALIFICACIÓN (siempre necesario)
        if ($nivelId && $nivelId !== 'todas') {
            Log::info("🔍 FILTRANDO preguntas por nivel_id: " . $nivelId);
            $query->where('niveles_calificacion_id', $nivelId);
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
            
            // Incluir: preguntas con area_id directo O preguntas relacionadas en tabla pivote
            $query->where(function($q) use ($areaId, $preguntasIdsRelacionadas) {
                $q->where('area_id', $areaId)
                  ->orWhereIn('id', $preguntasIdsRelacionadas);
            });
        } else if ($sedeId && $sedeId !== 'todas') {
            // Si solo hay filtro por sede (sin área)
            Log::info("🔍 FILTRANDO preguntas por sede_id: " . $sedeId);
            $preguntasIdsConSede = DB::table('area_pregunta')
                ->where('sede_id', $sedeId)
                ->where('is_active', true)
                ->pluck('pregunta_id');
            
            $query->where(function($q) use ($sedeId, $preguntasIdsConSede) {
                $q->where('sede_id', $sedeId)
                  ->orWhereIn('id', $preguntasIdsConSede);
            });
        }
        
        $preguntas = $query->orderBy('id', 'desc')->get();
        
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
            
            // Si tiene sede_id, incluir la sede
            if ($pregunta->sede_id) {
                $pregunta->sede_participante = DB::table('sedes')
                    ->where('id', $pregunta->sede_id)
                    ->select('id', 'nombre')
                    ->first();
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
            'area_id' => 'nullable|exists:areas,id', // Ahora es opcional
            'niveles_calificacion_id' => 'required|exists:niveles_calificacion,id',
            'sede_id' => 'nullable|exists:sedes,id', // Ahora es opcional
            'tipo' => 'required|in:opcion_unica,opcion_multiple,texto_libre,indicador_0_10,opcion_unica_texto_libre',
            'tipo_pregunta' => 'nullable|in:csat,nps,fcr',
            'is_active' => 'boolean',
            'opciones' => 'sometimes|array',
            'configuracion_rangos' => 'sometimes|array',
            // 🔥 NUEVO: Arrays de áreas y sedes
            'areas_id' => 'sometimes|array',
            'sedes_id' => 'sometimes|array',
        ]);

        // Crear la pregunta principal
        $pregunta = Pregunta::create([
            'pregunta' => $validated['pregunta'],
            'area_id' => $validated['area_id'] ?? null,
            'niveles_calificacion_id' => $validated['niveles_calificacion_id'],
            'sede_id' => $validated['sede_id'] ?? null,
            'tipo' => $validated['tipo'],
            'tipo_pregunta' => $validated['tipo_pregunta'] ?? null,
            'is_active' => $validated['is_active'] ?? true
        ]);

        // Crear opciones si es necesario (para tipos que las requieren)
        if (isset($validated['opciones']) && is_array($validated['opciones'])) {
            foreach ($validated['opciones'] as $index => $opcionTexto) {
                $opcionTexto = trim($opcionTexto);
                if (!empty($opcionTexto)) {
                    OpcionPregunta::create([
                        'pregunta_id' => $pregunta->id,
                        'opcion' => $opcionTexto,
                        'tiene_subpreguntas' => false,
                        'orden' => $index + 1
                    ]);
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
    $rangosConfig = [
        '0-6' => [0, 6],
        '7-8' => [7, 8], 
        '9-10' => [9, 10]
    ];

    foreach ($configuracionRangos as $rangoKey => $config) {
        // Solo crear si el rango está activo y tiene pregunta
        if ($config['activo'] && !empty(trim($config['pregunta_texto']))) {
            list($rangoMin, $rangoMax) = $rangosConfig[$rangoKey];

            // Crear la subpregunta (pregunta de rango)
            $subpregunta = Subpregunta::create([
                'pregunta_indicador_id' => $preguntaIndicadorId,
                'pregunta_texto' => trim($config['pregunta_texto']),
                'tipo' => $config['tipo'],
                'opciones' => !empty($config['opciones']) 
                    ? json_encode(array_map(function($op) { 
                          return trim($op['texto']); 
                      }, array_filter($config['opciones'], function($op) {
                          return !empty(trim($op['texto']));
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
                if (!empty($config['opciones'])) {
                    $opcionesArray = array_map(function($op) { 
                        return trim($op['texto']); 
                    }, array_filter($config['opciones'], function($op) {
                        return !empty(trim($op['texto']));
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
            'niveles_calificacion_id' => 'required|exists:niveles_calificacion,id',
            'sede_id' => 'nullable|exists:sedes,id', // 🔥 Cambiado a nullable para preguntas genéricas
            'tipo' => 'required|in:opcion_unica,opcion_multiple,texto_libre,indicador_0_10,opcion_unica_texto_libre',
            'tipo_pregunta' => 'nullable|in:csat,nps,fcr', // 🔥 Agregar tipo_pregunta
            'is_active' => 'boolean',
            'opciones' => 'sometimes|array',
            'configuracion_rangos' => 'sometimes|array' // 🔥 NUEVO
        ]);

        // 🔥 Preparar datos para actualizar (excluir campos null para preguntas genéricas)
        $datosUpdate = [
            'pregunta' => $validated['pregunta'],
            'niveles_calificacion_id' => $validated['niveles_calificacion_id'],
            'tipo' => $validated['tipo'],
            'is_active' => $validated['is_active'] ?? $pregunta->is_active
        ];
        
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
 * 🔥 NUEVO: Actualizar preguntas de rango
 */
private function actualizarPreguntasRango($preguntaIndicadorId, $configuracionRangos)
{
    // Primero eliminar todas las preguntas de rango existentes
    $this->eliminarPreguntasRango($preguntaIndicadorId);
    
    // Luego crear las nuevas preguntas de rango
    if (!empty($configuracionRangos)) {
        $this->crearPreguntasRango($preguntaIndicadorId, $configuracionRangos);
    }
}

// 🔥 NUEVO MÉTODO: Actualizar opciones de forma segura
private function actualizarOpcionesPregunta(Pregunta $pregunta, array $nuevasOpciones)
{
    $opcionesExistentes = $pregunta->opciones;
    $opcionesAEliminar = [];
    
    // Identificar opciones a eliminar (solo las que no tienen respuestas)
    foreach ($opcionesExistentes as $opcionExistente) {
        $encontrada = false;
        foreach ($nuevasOpciones as $nuevaOpcionTexto) {
            if (trim($nuevaOpcionTexto) === $opcionExistente->opcion) {
                $encontrada = true;
                break;
            }
        }
        
        if (!$encontrada) {
            // Verificar si la opción tiene respuestas asociadas
            if (!$opcionExistente->calificaciones()->exists()) {
                $opcionesAEliminar[] = $opcionExistente->id;
            }
        }
    }
    
    // Eliminar solo opciones sin respuestas
    if (!empty($opcionesAEliminar)) {
        OpcionPregunta::whereIn('id', $opcionesAEliminar)->delete();
    }
    
    // Agregar nuevas opciones
    foreach ($nuevasOpciones as $index => $opcionTexto) {
        $opcionTexto = trim($opcionTexto);
        if (!empty($opcionTexto)) {
            // Verificar si ya existe
            $existe = $pregunta->opciones()
                ->where('opcion', $opcionTexto)
                ->exists();
                
            if (!$existe) {
                OpcionPregunta::create([
                    'pregunta_id' => $pregunta->id,
                    'opcion' => $opcionTexto,
                    'orden' => $index + 1
                ]);
            }
        }
    }
    
    // 🔥 CORRECCIÓN: Asegurar opción "Otro" para opcion_unica_texto_libre
    if ($pregunta->tipo === 'opcion_unica_texto_libre') {
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
}