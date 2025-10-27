<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RespuestaCalificacion extends Model
{
    use HasFactory;

    protected $table = 'respuestas_calificacion';

    protected $fillable = [
        'calificacion_id',
        'pregunta_id',
        'pregunta_rango_id',
        'opcion_seleccionada_id',
        'respuesta_texto',
        'opciones_seleccionadas',
        'es_pregunta_rango'
    ];

    protected $casts = [
        'opciones_seleccionadas' => 'array',
        'es_pregunta_rango' => 'boolean'
    ];

    public function calificacion()
    {
        return $this->belongsTo(Calificacion::class);
    }

    public function pregunta()
    {
        return $this->belongsTo(Pregunta::class);
    }

    // 🔥 NUEVA RELACIÓN: Pregunta de rango
    public function preguntaRango()
    {
        return $this->belongsTo(Subpregunta::class, 'pregunta_rango_id');
    }

    public function opcionSeleccionada()
    {
        return $this->belongsTo(OpcionPregunta::class, 'opcion_seleccionada_id');
    }
}