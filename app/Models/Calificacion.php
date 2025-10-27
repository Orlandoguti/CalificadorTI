<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Calificacion extends Model
{
    use HasFactory;

    protected $table = 'calificaciones';

    protected $fillable = [
        'user_id',
        'area_id', 
        'sede_id',
        'nivel_calificacion_id',
    ];

    // Relaciones
    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    public function sede()
    {
        return $this->belongsTo(Sede::class);
    }

    public function nivelCalificacion()
    {
        return $this->belongsTo(NivelCalificacion::class);
    }

    public function respuestasCalificacion()
    {
        return $this->hasMany(RespuestaCalificacion::class, 'calificacion_id');
    }
}