<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Sede;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            
            // Verificar que el correo sea institucional
            $email = $googleUser->getEmail();
            if (!str_ends_with($email, '@unifranz.edu.bo')) {
                return redirect('/login')->with('error', 'Solo se permiten correos institucionales @unifranz.edu.bo');
            }

            // Buscar o crear usuario
            $user = User::where('email', $email)->first();

            if (!$user) {
                // Determinar rol basado en el correo
                $role = $this->determineRole($email);
                
                $user = User::create([
                    'google_id' => $googleUser->getId(),
                    'name' => $googleUser->getName(),
                    'email' => $email,
                    'avatar' => $googleUser->getAvatar(),
                    'email_verified_at' => now(),
                    'role' => $role
                ]);

                // Asignar sede automáticamente a gestores si es posible
                if ($role === 'gestor') {
                    $this->assignSedeToGestor($user, $email);
                }
            } else {
                // Actualizar datos de Google
                $user->google_id = $googleUser->getId();
                $user->avatar = $googleUser->getAvatar();
                $user->save();
            }

            // Iniciar sesión
            Auth::login($user);

            // Redirigir según el rol
            return $this->redirectByRole($user);

        } catch (\Exception $e) {
            Log::error('Error en login Google: ' . $e->getMessage());
            return redirect('/login')->with('error', 'Error al iniciar sesión con Google');
        }
    }

    /**
     * Determinar rol basado en el correo
     */
    private function determineRole($email)
    {
        if ($this->isAdminEmail($email)) {
            return 'admin';
        }

        if ($this->isGestorEmail($email)) {
            return 'gestor';
        }

        return 'user';
    }

    /**
     * Determinar si el correo es de administrador
     */
    private function isAdminEmail($email)
    {
        $adminEmails = [
            'admin@unifranz.edu.bo',
            'sistemas@unifranz.edu.bo',
            'ti@unifranz.edu.bo',
            'lpze.beymarjesus.villca.rh@unifranz.edu.bo'
        ];
        
        return in_array($email, $adminEmails) || str_contains($email, 'admin');
    }

    /**
     * Determinar si el correo es de gestor
     */
    private function isGestorEmail($email)
    {
        $gestorEmails = [
            'gestor.lapaz@unifranz.edu.bo',
            'gestor.elalto@unifranz.edu.bo',
            'gestor.santacruz@unifranz.edu.bo',
            'gestor.cochabamba@unifranz.edu.bo',
            // Agregar más emails de gestores según necesites
        ];
        
        return in_array($email, $gestorEmails) || str_contains($email, 'gestor');
    }

    /**
     * Asignar sede automáticamente a gestores basado en el email
     */
    private function assignSedeToGestor($user, $email)
    {
        $sedeMap = [
            'lapaz' => 'La Paz',
            'elalto' => 'El Alto', 
            'santacruz' => 'Santa Cruz',
            'cochabamba' => 'Cochabamba'
        ];

        foreach ($sedeMap as $key => $sedeNombre) {
            if (str_contains(strtolower($email), $key)) {
                $sede = Sede::where('nombre', $sedeNombre)->first();
                if ($sede) {
                    $user->sede_id = $sede->id;
                    $user->save();
                    break;
                }
            }
        }
    }

    /**
     * Redirigir según el rol del usuario
     */
    private function redirectByRole($user)
    {
        switch ($user->role) {
            case 'admin':
                return redirect('/admin/dashboard');
                
            case 'gestor':
                // Si el gestor no tiene sede, redirigir a ubicación
                if (!$user->sede_id) {
                    return redirect('/ubicacion');
                }
                return redirect('/gestor/dashboard');
                
            case 'user':
                // Si el usuario normal no tiene sede, redirigir a ubicación
                if (!$user->sede_id) {
                    return redirect('/ubicacion');
                }
                return redirect('/areas');
                
            default:
                return redirect('/ubicacion');
        }
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect('/');
    }
}
