<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subpregunta extends Model
{
    use HasFactory;

    protected $table = 'subpreguntas';

    protected $fillable = [
        'opcion_pregunta_id',
        'pregunta_indicador_id',
        'pregunta_texto',
        'tipo',
        'opciones',
        'is_active',
        'es_rango_indicador',
        'rango_min',
        'rango_max'
    ];

    protected $casts = [
        'opciones' => 'array',
        'is_active' => 'boolean',
        'es_rango_indicador' => 'boolean'
    ];

    public function opcion()
    {
        return $this->belongsTo(OpcionPregunta::class, 'opcion_pregunta_id');
    }

    public function preguntaIndicador()
    {
        return $this->belongsTo(Pregunta::class, 'pregunta_indicador_id');
    }

    // 🔥 NUEVO: Relación con respuestas de rango
    public function respuestasRango()
    {
        return $this->hasMany(RespuestaCalificacion::class, 'pregunta_rango_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePorRangoIndicador($query, $preguntaIndicadorId, $valor)
    {
        return $query->where('es_rango_indicador', true)
                    ->where('pregunta_indicador_id', $preguntaIndicadorId)
                    ->where('rango_min', '<=', $valor)
                    ->where('rango_max', '>=', $valor)
                    ->active();
    }

    public function getOpcionesArrayAttribute()
    {
        if (is_array($this->opciones)) {
            return $this->opciones;
        }
        
        if (is_string($this->opciones)) {
            try {
                return json_decode($this->opciones, true) ?: [];
            } catch (\Exception $e) {
                return [];
            }
        }
        
        return [];
    }

    public function getTieneOpcionesAttribute()
    {
        return in_array($this->tipo, ['opcion_unica', 'opcion_multiple']) && 
               !empty($this->opciones_array);
    }
}