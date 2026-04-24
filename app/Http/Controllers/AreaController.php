<?php

namespace App\Http\Controllers;

use App\Models\Area;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Calificacion;

class AreaController extends Controller
{

public function indexPublic(Request $request)
{
    try {
        $sedeId = $request->get('sede_id');
        
        Log::info("🔍 [PUBLIC] Solicitando áreas públicas para sede_id: " . $sedeId);
        
        $query = Area::withCount('preguntas')
            ->with('sede')
            ->where('is_active', true);

        // ✅ FILTRAR POR SEDE SI SE PROPORCIONA
        if ($sedeId && $sedeId !== 'todas') {
            Log::info("🔍 [PUBLIC] FILTRANDO áreas por sede_id: " . $sedeId);
            $query->where('sede_id', $sedeId);
        } else {
            Log::info("🔍 [PUBLIC] Mostrando TODAS las áreas (sin filtro de sede)");
        }

        $areas = $query->get();

        Log::info("📊 [PUBLIC] Áreas encontradas: " . $areas->count() . " para sede: " . $sedeId);
        
        // ✅ INCLUIR PASSWORD (es necesario para la autenticación)
        $areasPublic = $areas->map(function($area) {
            return [
                'id' => $area->id,
                'nombre' => $area->nombre,
                'codigo' => $area->codigo,
                'descripcion' => $area->descripcion,
                'icono' => $area->icono,
                'color' => $area->color,
                'sede_id' => $area->sede_id,
                'sede' => $area->sede,
                'preguntas_count' => $area->preguntas_count,
                'password' => $area->password, // ✅ AGREGAR ESTA LÍNEA
            ];
        });

        return response()->json($areasPublic);
        
    } catch (\Exception $e) {
        Log::error('❌ [PUBLIC] Error en AreaController indexPublic: ' . $e->getMessage());
        return response()->json(['error' => 'Error interno del servidor'], 500);
    }
}

    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $sedeId = $request->get('sede_id');
            
            Log::info("🔍 Solicitando áreas para sede_id: " . $sedeId);
            
            $query = Area::withCount('preguntas')
                ->with('sede')
                ->where('is_active', true);

            // Si el usuario es gestor, aplicar filtros especiales
            if ($user && $user->role === 'gestor') {
                // Usar la sede del gestor si no se proporciona una
                if (!$sedeId || $sedeId === 'todas') {
                    $sedeId = $user->sede_id;
                }
                
                // Verificar que el gestor solo vea áreas de su sede
                if ($sedeId && $sedeId !== 'todas') {
                    $query->where('sede_id', $sedeId);
                }
                
                // Si el gestor tiene áreas asignadas específicamente, filtrar por esas
                $areasAsignadas = $user->areas()->pluck('areas.id')->toArray();
                if (!empty($areasAsignadas)) {
                    $query->whereIn('id', $areasAsignadas);
                    Log::info("🔍 Gestor con áreas asignadas: " . count($areasAsignadas) . " áreas");
                } else {
                    Log::info("🔍 Gestor sin áreas asignadas - mostrando todas las áreas de su sede");
                }
            } else {
                // Para admin y otros roles, comportamiento normal
                // ✅ FILTRAR POR SEDE SI SE PROPORCIONA
                if ($sedeId && $sedeId !== 'todas') {
                    Log::info("🔍 FILTRANDO áreas por sede_id: " . $sedeId);
                    $query->where('sede_id', $sedeId);
                } else {
                    Log::info("🔍 Mostrando TODAS las áreas (sin filtro de sede)");
                }
            }

            $areas = $query->get();

            Log::info("📊 Áreas encontradas: " . $areas->count() . " para sede: " . $sedeId);
            
            // ✅ Verificar que las relaciones se carguen correctamente
            foreach ($areas as $area) {
                Log::info("   - Área ID: {$area->id}, Nombre: {$area->nombre}, Sede ID: {$area->sede_id}, Sede: " . ($area->sede ? $area->sede->nombre : 'NO CARGADA'));
            }

            return response()->json($areas);
            
        } catch (\Exception $e) {
            Log::error('Error en AreaController index: ' . $e->getMessage());
            return response()->json(['error' => 'Error interno del servidor'], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            Log::info('Store Area Request:', $request->all());
            Log::info('Headers:', $request->headers->all());
            
            $validated = $request->validate([
                'nombre' => 'required|string|max:100', // 🔥 Sin unique para permitir mismo nombre en diferentes sedes
                'codigo' => 'required|string|max:10', // 🔥 Sin unique para permitir mismo código en diferentes sedes
                'password' => 'nullable|string|min:4',
                'sede_id' => 'required|exists:sedes,id',
                'is_active' => 'boolean',
                // 🔥 NUEVO: Indicadores de calificación
                'permite_csat' => 'nullable|boolean',
                'permite_nps' => 'nullable|boolean',
                'permite_fcr' => 'nullable|boolean'
            ]);
            
            // 🔥 NUEVO: Validar unicidad dentro de la misma sede
            $nombreExiste = Area::where('sede_id', $validated['sede_id'])
                ->where('nombre', $validated['nombre'])
                ->exists();
            
            if ($nombreExiste) {
                return response()->json([
                    'error' => 'Ya existe un área con este nombre en esta sede'
                ], 422);
            }
            
            $codigoExiste = Area::where('sede_id', $validated['sede_id'])
                ->where('codigo', $validated['codigo'])
                ->exists();
            
            if ($codigoExiste) {
                return response()->json([
                    'error' => 'Ya existe un área con este código en esta sede'
                ], 422);
            }

            Log::info('Datos validados:', $validated);

            // Si no se proporciona contraseña, generar una por defecto
            if (empty($validated['password'])) {
                $validated['password'] = strtolower($validated['codigo']) . '2025';
                Log::info('Contraseña generada automáticamente:', ['password' => $validated['password']]);
            }

            $area = Area::create($validated);
            
            // 🔥 NUEVO: Si el área tiene algún tipo de calificación habilitado, asignar preguntas genéricas
            $tiposCalificacion = ['csat', 'nps', 'fcr'];
            
            foreach ($tiposCalificacion as $tipo) {
                $indicador = 'permite_' . $tipo;
                $habilitado = $validated[$indicador] ?? false;
                
                if ($habilitado) {
                    // Se habilitó: crear registros para todas las preguntas de este tipo
                    $this->agregarPreguntasATipoArea($area->id, $tipo);
                }
            }
            
            Log::info('Área creada exitosamente:', $area->toArray());
            
            return response()->json($area, 201);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Error de validación en AreaController store: ' . $e->getMessage());
            Log::error('Errores de validación:', $e->errors());
            return response()->json([
                'error' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error en AreaController store: ' . $e->getMessage());
            return response()->json(['error' => 'Error interno del servidor: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, Area $area)
{
    try {
        Log::info('Update Area Request:', $request->all());
        
        $validated = $request->validate([
            'nombre' => 'required|string|max:100', // 🔥 Sin unique para permitir mismo nombre en diferentes sedes
            'codigo' => 'required|string|max:10', // 🔥 Sin unique para permitir mismo código en diferentes sedes
            'password' => 'nullable|string|min:4',
            'sede_id' => 'required|exists:sedes,id',
            'is_active' => 'boolean',
            // 🔥 NUEVO: Indicadores de calificación
            'permite_csat' => 'nullable|boolean',
            'permite_nps' => 'nullable|boolean',
            'permite_fcr' => 'nullable|boolean'
        ]);
        
        // 🔥 NUEVO: Validar unicidad dentro de la misma sede (excluyendo el área actual)
        $nombreExiste = Area::where('sede_id', $validated['sede_id'])
            ->where('nombre', $validated['nombre'])
            ->where('id', '!=', $area->id)
            ->exists();
        
        if ($nombreExiste) {
            return response()->json([
                'error' => 'Ya existe un área con este nombre en esta sede'
            ], 422);
        }
        
        $codigoExiste = Area::where('sede_id', $validated['sede_id'])
            ->where('codigo', $validated['codigo'])
            ->where('id', '!=', $area->id)
            ->exists();
        
        if ($codigoExiste) {
            return response()->json([
                'error' => 'Ya existe un área con este código en esta sede'
            ], 422);
        }

        Log::info('Datos validados:', $validated);

        // Si se envía contraseña vacía, mantener la actual
        if (isset($validated['password']) && empty($validated['password'])) {
            unset($validated['password']);
        }

        // 🔥 NUEVO: Guardar valores antiguos para comparar
        $valoresAntiguos = [
            'permite_csat' => $area->permite_csat ?? false,
            'permite_nps' => $area->permite_nps ?? false,
            'permite_fcr' => $area->permite_fcr ?? false,
        ];
        
        $valoresNuevos = [
            'permite_csat' => $validated['permite_csat'] ?? false,
            'permite_nps' => $validated['permite_nps'] ?? false,
            'permite_fcr' => $validated['permite_fcr'] ?? false,
        ];

        $area->update($validated);
        
        // 🔥 NUEVO: Sincronizar preguntas genéricas según cambios en los indicadores
        $tiposCalificacion = ['csat', 'nps', 'fcr'];
        
        foreach ($tiposCalificacion as $tipo) {
            $indicadorAnterior = 'permite_' . $tipo;
            $indicadorNuevo = 'permite_' . $tipo;
            
            $habilitadoAntes = $valoresAntiguos[$indicadorAnterior] ?? false;
            $habilitadoAhora = $valoresNuevos[$indicadorNuevo] ?? false;
            
            // Solo procesar si hay cambio
            if ($habilitadoAntes != $habilitadoAhora) {
                if ($habilitadoAhora) {
                    // Se habilitó: crear registros para todas las preguntas de este tipo
                    $this->agregarPreguntasATipoArea($area->id, $tipo);
                } else {
                    // Se deshabilitó: eliminar registros
                    $this->eliminarPreguntasDeTipoArea($area->id, $tipo);
                }
            }
        }
        
        Log::info('Área actualizada exitosamente:', $area->toArray());
        
        return response()->json($area);
        
    } catch (\Illuminate\Validation\ValidationException $e) {
        Log::error('Error de validación en AreaController update: ' . $e->getMessage());
        Log::error('Errores de validación:', $e->errors());
        return response()->json([
            'error' => 'Error de validación',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        Log::error('Error en AreaController update: ' . $e->getMessage());
        return response()->json(['error' => 'Error interno del servidor: ' . $e->getMessage()], 500);
    }
}
    /**
     * 🔥 NUEVO: Agregar preguntas genéricas a un área cuando se habilita un tipo de calificación
     */
    private function agregarPreguntasATipoArea($areaId, $tipoCalificacion)
    {
        Log::info("🔄 Habilitando $tipoCalificacion para área $areaId");
        
        // Obtener el área para saber su sede_id
        $area = Area::find($areaId);
        if (!$area) {
            Log::error("❌ Área no encontrada: $areaId");
            return;
        }
        
        // Obtener todas las preguntas genéricas de este tipo
        $preguntas = DB::table('preguntas')
            ->where('tipo_pregunta', $tipoCalificacion)
            ->where('is_active', 1)
            ->get();
        
        Log::info("📋 Encontradas {$preguntas->count()} preguntas {$tipoCalificacion}");
        
        // Para cada pregunta, crear el registro en area_pregunta con la sede del área
        foreach ($preguntas as $pregunta) {
            // Verificar si ya existe el registro
            $existe = DB::table('area_pregunta')
                ->where('area_id', $areaId)
                ->where('sede_id', $area->sede_id)
                ->where('pregunta_id', $pregunta->id)
                ->exists();
            
            if (!$existe) {
                DB::table('area_pregunta')->insert([
                    'area_id' => $areaId,
                    'sede_id' => $area->sede_id,
                    'pregunta_id' => $pregunta->id,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                Log::info("✅ Creado registro para pregunta {$pregunta->id} - área {$areaId} - sede {$area->sede_id}");
            }
        }
        
        Log::info("✅ Proceso completado para área $areaId con tipo $tipoCalificacion");
    }
    
    /**
     * 🔥 NUEVO: Eliminar preguntas genéricas de un área cuando se deshabilita un tipo de calificación
     */
    private function eliminarPreguntasDeTipoArea($areaId, $tipoCalificacion)
    {
        Log::info("🔄 Deshabilitando $tipoCalificacion para área $areaId");
        
        // Obtener el área para saber su sede_id
        $area = Area::find($areaId);
        if (!$area) {
            Log::error("❌ Área no encontrada: $areaId");
            return;
        }
        
        // Obtener todas las preguntas genéricas de este tipo
        $preguntas = DB::table('preguntas')
            ->where('tipo_pregunta', $tipoCalificacion)
            ->pluck('id');
        
        Log::info("📋 Encontradas {$preguntas->count()} preguntas {$tipoCalificacion} a eliminar");
        
        // Eliminar registros de area_pregunta que coincidan
        $eliminados = DB::table('area_pregunta')
            ->where('area_id', $areaId)
            ->where('sede_id', $area->sede_id)
            ->whereIn('pregunta_id', $preguntas)
            ->delete();
        
        Log::info("✅ Eliminados $eliminados registros de área $areaId con tipo $tipoCalificacion");
    }
    
    public function destroy(Area $area)
    {
        try {
            // Verificar si hay preguntas asociadas
            if ($area->preguntas()->exists()) {
                return response()->json([
                    'error' => 'No se puede eliminar el área porque tiene preguntas asociadas'
                ], 422);
            }

            // 🔥 NUEVO: Eliminar registros en area_pregunta antes de eliminar el área
            // (Las foreign keys en cascada deberían hacerlo, pero por si acaso)
            DB::table('area_pregunta')
                ->where('area_id', $area->id)
                ->delete();
            
            Log::info("🗑️ Registros eliminados de area_pregunta para área ID: {$area->id}");

            $area->delete();
            
            Log::info('Área eliminada exitosamente:', ['id' => $area->id]);
            
            return response()->json(['message' => 'Área eliminada correctamente']);
            
        } catch (\Exception $e) {
            Log::error('Error en AreaController destroy: ' . $e->getMessage());
            return response()->json(['error' => 'Error interno del servidor'], 500);
        }
    }

    public function toggleStatus(Area $area)
    {
        try {
            $area->update(['is_active' => !$area->is_active]);
            
            Log::info('Estado de área cambiado:', [
                'id' => $area->id,
                'nuevo_estado' => $area->is_active
            ]);
            
            return response()->json([
                'message' => 'Estado actualizado correctamente',
                'is_active' => $area->is_active
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error en AreaController toggleStatus: ' . $e->getMessage());
            return response()->json(['error' => 'Error interno del servidor'], 500);
        }
    }

    public function show(Request $request, $id)
    {
        try {
            Log::info("🔍 Solicitando área específica con ID: " . $id);
            
            $area = Area::find($id);
            
            if (!$area) {
                return response()->json(['error' => 'Área no encontrada'], 404);
            }
            
            // 🔥 NUEVO: Retornar con todos los campos incluyendo los indicadores
            return response()->json($area);
            
        } catch (\Exception $e) {
            Log::error('Error en AreaController show: ' . $e->getMessage());
            return response()->json(['error' => 'Error interno del servidor'], 500);
        }
    }
}