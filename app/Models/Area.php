<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    use HasFactory;

    protected $fillable = ['nombre', 'codigo', 'password', 'is_active', 'sede_id', 'permite_csat', 'permite_nps', 'permite_fcr', 'descripcion'];

    public function preguntas()
    {
        return $this->belongsToMany(Pregunta::class, 'area_pregunta')
                    ->withPivot('is_active', 'sede_id')
                    ->wherePivot('is_active', true)
                    ->withTimestamps();
    }
    
    // 🔥 NUEVA RELACIÓN: Many-to-Many con preguntas genéricas (CSAT, NPS, FCR)
    public function preguntasGenericas()
    {
        return $this->belongsToMany(Pregunta::class, 'area_pregunta')
                    ->withPivot('is_active')
                    ->withTimestamps();
    }
    
    // 🔥 NUEVA RELACIÓN: Many-to-Many con tipos de calificación (CSAT, NPS, FCR)
    public function tiposCalificacion()
    {
        return $this->belongsToMany(TipoCalificacion::class, 'area_tipo_calificacion')
                    ->withPivot('is_active')
                    ->withTimestamps();
    }

    public function calificaciones()
    {
        return $this->hasMany(Calificacion::class);
    }

    // NUEVA RELACIÓN CON SEDE
    public function sede()
    {
        return $this->belongsTo(Sede::class);
    }
}