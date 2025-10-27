<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sede extends Model
{
    use HasFactory;

    protected $fillable = ['nombre', 'lat', 'lng'];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function calificaciones()
    {
        return $this->hasMany(Calificacion::class);
    }

    public function gestores()
    {
        return $this->hasMany(User::class)->where('role', 'gestor');
    }
}