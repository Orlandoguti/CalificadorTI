<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pregunta extends Model
{
    use HasFactory;

    protected $fillable = [
        'pregunta', 
        'tipo',
        'tipo_pregunta', // csat, nps, fcr
        'descripcion',
        'niveles_calificacion_id', 
        'area_id', // Mantener para compatibilidad
        'sede_id',
        'is_active',
    ];

    public function nivelCalificacion()
    {
        return $this->belongsTo(NivelCalificacion::class, 'niveles_calificacion_id');
    }

    public function area()
    {
        return $this->belongsTo(Area::class);
    }
    
    // 🔥 NUEVA RELACIÓN: Many-to-Many con áreas (para preguntas genéricas)
    public function areas()
    {
        return $this->belongsToMany(Area::class, 'area_pregunta')
                    ->withPivot('is_active')
                    ->withTimestamps();
    }

    public function opciones()
    {
        return $this->hasMany(OpcionPregunta::class);
    }

    public function respuestasCalificacion()
    {
        return $this->hasMany(RespuestaCalificacion::class, 'pregunta_id');
    }

    // NUEVA RELACIÓN CON SEDE
    public function sede()
    {
        return $this->belongsTo(Sede::class);
    }

    // 🔥 NUEVA RELACIÓN: Opciones que llevan a esta pregunta
    public function opcionesOrigen()
    {
        return $this->hasMany(OpcionPregunta::class, 'pregunta_siguiente_id');
    }

    // 🔥 NUEVO MÉTODO: Verificar si es pregunta raíz (no condicional)
    public function esPreguntaRaiz()
    {
        return !$this->es_condicional && $this->opcionesOrigen->isEmpty();
    }

    public function subpreguntasRango()
    {
        return $this->hasMany(Subpregunta::class, 'pregunta_indicador_id')
                    ->where('es_rango_indicador', true);
    }
}