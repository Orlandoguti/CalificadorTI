<?php

namespace App\Http\Controllers;

use App\Models\OpcionPregunta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OpcionPreguntaController extends Controller
{
    public function update(Request $request, $id)
    {
        try {
            Log::info("🔄 Actualizando opción ID: " . $id . " con datos: " . json_encode($request->all()));
            
            $opcion = OpcionPregunta::findOrFail($id);
            
            $validated = $request->validate([
                'tiene_subpreguntas' => 'required|boolean'
            ]);

            Log::info("📊 Estado actual: " . $opcion->tiene_subpreguntas . " -> Nuevo estado: " . $validated['tiene_subpreguntas']);
            
            $opcion->update($validated);
            
            Log::info("✅ Opción actualizada correctamente. Nuevo estado: " . $opcion->tiene_subpreguntas);

            return response()->json($opcion);
        } catch (\Exception $e) {
            Log::error('❌ Error actualizando opción: ' . $e->getMessage());
            return response()->json(['error' => 'Error al actualizar opción: ' . $e->getMessage()], 500);
        }
    }
}