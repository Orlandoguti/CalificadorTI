<?php

namespace App\Http\Controllers;

use App\Models\Sede;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SedeController extends Controller
{
    public function index()
    {
        $sedes = Sede::all();
        return response()->json($sedes);
    }

    /**
     * 🔥 NUEVO MÉTODO: Buscar sede por nombre
     */
    public function buscarPorNombre(Request $request)
    {
        try {
            $nombre = $request->get('nombre');
            
            if (!$nombre) {
                return response()->json(['error' => 'Nombre de sede requerido'], 400);
            }

            Log::info("🔍 Buscando sede por nombre: {$nombre}");

            $sede = Sede::where('nombre', 'like', "%{$nombre}%")->first();

            if (!$sede) {
                Log::warning("❌ Sede no encontrada para nombre: {$nombre}");
                return response()->json(['error' => 'Sede no encontrada'], 404);
            }

            Log::info("✅ Sede encontrada: {$sede->nombre} (ID: {$sede->id})");

            return response()->json($sede);

        } catch (\Exception $e) {
            Log::error('Error buscando sede por nombre: ' . $e->getMessage());
            return response()->json(['error' => 'Error interno del servidor'], 500);
        }
    }

    public function detectSede(Request $request)
    {
        $lat = $request->input('lat');
        $lng = $request->input('lng');

        $sedes = Sede::all();
        $closest = null;
        $minDist = INF;

        foreach ($sedes as $sede) {
            $dist = $this->haversine($lat, $lng, $sede->lat, $sede->lng);
            if ($dist < $minDist) {
                $minDist = $dist;
                $closest = $sede;
            }
        }

        // 🔥 NUEVA LÓGICA: Solo devolver sede si está dentro del radio permitido
        // Radio configurado en km - aumentado a 3 km para cubrir campus grandes
        $radioPermitido = 3.0; // 3 kilómetros para cubrir campus universitarios grandes
        
        // Log detallado para debugging
        Log::info("🔍 Detección de sede:", [
            'coordenadas_usuario' => [
                'lat' => $lat,
                'lng' => $lng
            ],
            'sede_mas_cercana' => $closest ? [
                'nombre' => $closest->nombre,
                'lat' => $closest->lat,
                'lng' => $closest->lng
            ] : 'Ninguna',
            'distancia_km' => round($minDist, 3),
            'radio_permitido_km' => $radioPermitido
        ]);
        
        if ($minDist <= $radioPermitido && $closest) {
            Log::info("✅ Usuario dentro del área de sede: {$closest->nombre} (distancia: {$minDist} km)");
            return response()->json([
                'sede' => $closest,
                'distancia' => round($minDist, 3)
            ]);
        } else {
            Log::warning("❌ Usuario fuera del área de todas las sedes.", [
                'distancia_minima' => round($minDist, 3) . ' km',
                'radio_permitido' => $radioPermitido . ' km',
                'sede_mas_cercana' => $closest ? $closest->nombre : 'Ninguna'
            ]);
            return response()->json([
                'sede' => null,
                'distancia' => round($minDist, 3),
                'radio_permitido' => $radioPermitido,
                'mensaje' => 'No estás dentro del área de ninguna sede de UNIFRANZ. Por favor, acércate a una sede para usar el sistema.'
            ], 400);
        }
    }

    /**
     * Calcular distancia entre dos coordenadas con fórmula Haversine
     */
    private function haversine($lat1, $lon1, $lat2, $lon2)
    {
        $R = 6371; // Radio de la Tierra en km
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        return $R * $c;
    }
}