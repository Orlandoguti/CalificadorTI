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

    public function calificaciones()
    {
        return $this->hasMany(Calificacion::class, 'opcion_seleccionada_id');
    }

    /**
 * Relación con las subpreguntas de esta opción
 */
public function subpreguntas()
{
    return $this->hasMany(Subpregunta::class, 'opcion_pregunta_id')->active();
}
}