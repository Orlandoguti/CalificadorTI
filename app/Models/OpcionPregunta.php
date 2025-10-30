<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpcionPregunta extends Model
{
    use HasFactory;

    protected $table = 'opciones_pregunta';

    protected $fillable = [
        'pregunta_id', 
        'opcion', 
        'tiene_subpreguntas' ,
    ];

    public function pregunta()
    {
        return $this->belongsTo(Pregunta::class);
    }

    // 🔥 NUEVA RELACIÓN: Pregunta siguiente condicional
    public function preguntaSiguiente()
    {
        return $this->belongsTo(Pregunta::class, 'pregunta_siguiente_id');
    }

    public function respuestasCalificacion()
    {
        return $this->hasMany(RespuestaCalificacion::class, 'opcion_seleccionada_id');
    }
    
    // Mantener compatibilidad con código que use calificaciones()
    public function calificaciones()
    {
        return $this->respuestasCalificacion();
    }

    /**
 * Relación con las subpreguntas de esta opción
 */
public function subpreguntas()
{
    return $this->hasMany(Subpregunta::class, 'opcion_pregunta_id')->active();
}
}