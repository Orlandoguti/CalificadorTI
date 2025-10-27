<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'google_id',
        'name',
        'email',
        'avatar',
        'email_verified_at',
        'role',
        'sede_id'
    ];

    protected $hidden = [
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // Relación con sede
    public function sede()
    {
        return $this->belongsTo(Sede::class);
    }

    // Relación con calificaciones
    public function calificaciones()
    {
        return $this->hasMany(Calificacion::class);
    }

    // Métodos para verificar roles
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isGestor()
    {
        return $this->role === 'gestor';
    }

    public function isUser()
    {
        return $this->role === 'user';
    }

    public function isInvitado()
    {
        return auth()->guest(); // Usuario no autenticado
    }
    
}