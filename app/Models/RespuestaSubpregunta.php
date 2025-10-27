<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RespuestaSubpregunta extends Model
{
    use HasFactory;

    protected $table = 'respuestas_subpreguntas';

    protected $fillable = [
        'calificacion_id',
        'subpregunta_id',
        'opcion_seleccionada',
        'opciones_seleccionadas', 
        'texto_respuesta',
        'valor_indicador'
    ];

    protected $casts = [
        'opciones_seleccionadas' => 'array'
    ];

    public function calificacion()
    {
        return $this->belongsTo(Calificacion::class);
    }

    public function subpregunta()
    {
        return $this->belongsTo(Subpregunta::class);
    }
}