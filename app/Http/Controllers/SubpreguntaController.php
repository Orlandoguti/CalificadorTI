<?php

namespace App\Http\Controllers;

use App\Models\Subpregunta;
use App\Models\OpcionPregunta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SubpreguntaController extends Controller
{
    public function index($opcionId)
    {
        try {
            $subpreguntas = Subpregunta::where('opcion_pregunta_id', $opcionId)
                ->where('is_active', true)
                ->get();
                
            return response()->json($subpreguntas);
        } catch (\Exception $e) {
            Log::error('Error cargando subpreguntas: ' . $e->getMessage());
            return response()->json(['error' => 'Error al cargar subpreguntas'], 500);
        }
    }

    public function store(Request $request)
{
    try {
        Log::info("🔄 ========== INICIANDO CREACIÓN DE SUBPREGUNTA ==========");
        Log::info("📦 Datos recibidos: " . json_encode($request->all()));

        $validated = $request->validate([
            'opcion_pregunta_id' => 'required|exists:opciones_pregunta,id',
            'pregunta_texto' => 'required|string|max:500',
            'tipo' => 'required|in:opcion_unica,opcion_unica_texto_libre,opcion_multiple,texto_libre,indicador_0_10',
            'opciones' => 'sometimes|array'
        ]);

        Log::info("✅ Datos validados: " . json_encode($validated));

        $subpregunta = Subpregunta::create([
            'opcion_pregunta_id' => $validated['opcion_pregunta_id'],
            'pregunta_texto' => $validated['pregunta_texto'],
            'tipo' => $validated['tipo'],
            'opciones' => isset($validated['opciones']) ? json_encode($validated['opciones']) : null,
            'is_active' => true
        ]);

        Log::info("🎉 Subpregunta creada exitosamente - ID: " . $subpregunta->id);

        // 🔥 ACTUALIZAR EL ESTADO DE LA OPCIÓN - VERSIÓN MÁS ROBUSTA
        $opcionId = $validated['opcion_pregunta_id'];
        Log::info("🔍 Buscando opción con ID: " . $opcionId);
        
        $opcion = OpcionPregunta::find($opcionId);
        
        if ($opcion) {
            Log::info("📋 Opción encontrada:");
            Log::info("   - ID: " . $opcion->id);
            Log::info("   - Texto: " . $opcion->opcion);
            Log::info("   - Estado actual de subpreguntas: " . ($opcion->tiene_subpreguntas ? 'TRUE' : 'FALSE'));
            
            // Intentar actualizar
            $actualizado = $opcion->update(['tiene_subpreguntas' => true]);
            
            if ($actualizado) {
                Log::info("✅ ¡ÉXITO! Estado actualizado a TRUE para opción ID: " . $opcion->id);
                
                // Verificar el nuevo estado
                $opcionActualizada = OpcionPregunta::find($opcionId);
                Log::info("🔍 Verificación - Nuevo estado: " . ($opcionActualizada->tiene_subpreguntas ? 'TRUE' : 'FALSE'));
            } else {
                Log::error("❌ FALLO: No se pudo actualizar el estado de la opción");
            }
        } else {
            Log::error("❌ OPCIÓN NO ENCONTRADA: No existe opción con ID: " . $opcionId);
        }

        Log::info("========== FINALIZADA CREACIÓN DE SUBPREGUNTA ==========");

        return response()->json($subpregunta, 201);
    } catch (\Exception $e) {
        Log::error('❌ ERROR CRÍTICO creando subpregunta: ' . $e->getMessage());
        Log::error('❌ Stack trace: ' . $e->getTraceAsString());
        return response()->json(['error' => 'Error al crear subpregunta: ' . $e->getMessage()], 500);
    }
}

    public function update(Request $request, $id)
    {
        try {
            $subpregunta = Subpregunta::findOrFail($id);
            
            $validated = $request->validate([
                'pregunta_texto' => 'required|string|max:500',
                'tipo' => 'required|in:opcion_unica,opcion_unica_texto_libre,opcion_multiple,texto_libre,indicador_0_10',
                'opciones' => 'sometimes|array'
            ]);

            $subpregunta->update([
                'pregunta_texto' => $validated['pregunta_texto'],
                'tipo' => $validated['tipo'],
                'opciones' => isset($validated['opciones']) ? json_encode($validated['opciones']) : null
            ]);

            return response()->json($subpregunta);
        } catch (\Exception $e) {
            Log::error('Error actualizando subpregunta: ' . $e->getMessage());
            return response()->json(['error' => 'Error al actualizar subpregunta'], 500);
        }
    }

    public function destroy($id)
{
    try {
        $subpregunta = Subpregunta::findOrFail($id);
        $opcionId = $subpregunta->opcion_pregunta_id;
        
        $subpregunta->delete();

        // 🔥 CORRECIÓN: Verificar si la opción aún tiene subpreguntas
        $tieneSubpreguntas = Subpregunta::where('opcion_pregunta_id', $opcionId)->exists();
        
        $opcion = OpcionPregunta::find($opcionId);
        if ($opcion) {
            $opcion->update(['tiene_subpreguntas' => $tieneSubpreguntas]);
            Log::info("✅ Actualizado tiene_subpreguntas a " . ($tieneSubpreguntas ? 'TRUE' : 'FALSE') . " para opción: " . $opcionId);
        }

        return response()->json(['message' => 'Subpregunta eliminada correctamente']);
    } catch (\Exception $e) {
        Log::error('Error eliminando subpregunta: ' . $e->getMessage());
        return response()->json(['error' => 'Error al eliminar subpregunta'], 500);
    }
}
}