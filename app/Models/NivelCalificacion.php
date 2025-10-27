<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NivelCalificacion extends Model
{
    use HasFactory;

    protected $table = 'niveles_calificacion';

    protected $fillable = [
        'nombre',
        'emoji', 
        'valor',
        'color',
        'is_active'
    ];

    // Si tienes relaciones, mantenerlas
    public function preguntas()
    {
        return $this->hasMany(Pregunta::class, 'niveles_calificacion_id');
    }

    public function calificaciones()
    {
        return $this->hasMany(Calificacion::class, 'nivel_calificacion_id');
    }
}