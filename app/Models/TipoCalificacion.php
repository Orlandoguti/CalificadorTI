<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoCalificacion extends Model
{
    use HasFactory;

    protected $table = 'tipos_calificacion';

    protected $fillable = [
        'nombre',
        'codigo',
        'descripcion',
        'is_active'
    ];

    // Relación con áreas
    public function areas()
    {
        return $this->belongsToMany(Area::class, 'area_tipo_calificacion')
                    ->withPivot('is_active')
                    ->withTimestamps();
    }
}

