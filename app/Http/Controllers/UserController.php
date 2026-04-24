<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Sede;
use App\Models\Area;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = User::with('sede', 'areas');
            
            // Filtros
            if ($request->has('role') && $request->role !== '') {
                $query->where('role', $request->role);
            }
            
            if ($request->has('sede_id') && $request->sede_id !== '') {
                $query->where('sede_id', $request->sede_id);
            }
            
            $users = $query->orderBy('name')->paginate(15);
            
            return response()->json($users);
            
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al cargar usuarios: ' . $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
{
    try {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'nullable|string|min:8', // Cambiar a nullable
            'role' => 'required|in:admin,gestor,user',
            'sede_id' => 'nullable|exists:sedes,id',
            'email_verified' => 'boolean'
        ]);

        // Validar que los gestores tengan sede asignada
        if ($validated['role'] === 'gestor' && empty($validated['sede_id'])) {
            return response()->json([
                'error' => 'Los gestores deben tener una sede asignada'
            ], 422);
        }

        // 🔥 CORRECIÓN: Generar contraseña automática si no se proporciona
        $password = !empty($validated['password']) 
            ? Hash::make($validated['password']) 
            : Hash::make(Str::random(12)); // Contraseña aleatoria segura

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $password,
            'role' => $validated['role'],
            'sede_id' => $validated['sede_id'],
            'email_verified_at' => $validated['email_verified'] ? now() : null,
        ]);

        $user->load('sede');

        return response()->json($user, 201);
        
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'error' => 'Error de validación',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Error al crear usuario: ' . $e->getMessage()], 500);
    }
}

    public function update(Request $request, $usuario)
{
    try {
        // 🔥 CORRECCIÓN: Convertir a ID si viene como modelo o string
        $userId = is_object($usuario) ? $usuario->id : $usuario;
        $user = User::findOrFail($userId);
        
        \Log::info('🔄 Actualizando usuario:', [
            'user_id' => $user->id,
            'email_actual' => $user->email,
            'email_nuevo' => $request->email,
            'request_id' => $request->get('id'),
            'param_usuario' => $usuario
        ]);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id)
            ],
            'password' => 'nullable|string|min:8',
            'role' => 'required|in:admin,gestor,user',
            'sede_id' => 'nullable|exists:sedes,id',
            'email_verified' => 'boolean'
        ]);

        // 🔥 CORRECIÓN: Validar que los gestores tengan sede asignada
        if ($validated['role'] === 'gestor' && empty($validated['sede_id'])) {
            return response()->json([
                'error' => 'Los gestores deben tener una sede asignada'
            ], 422);
        }

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'sede_id' => $validated['sede_id'],
            'email_verified_at' => $validated['email_verified'] ? now() : null,
        ];

        // 🔥 CORRECIÓN: Solo actualizar password si se proporciona
        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);
        $user->load('sede');

        return response()->json($user);
        
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'error' => 'Error de validación',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Error al actualizar usuario: ' . $e->getMessage()], 500);
    }
}

    public function destroy(User $user)
    {
        try {
            // No permitir eliminar el propio usuario
            if ($user->id === auth()->id()) {
                return response()->json([
                    'error' => 'No puedes eliminar tu propio usuario'
                ], 422);
            }

            $user->delete();

            return response()->json(['message' => 'Usuario eliminado correctamente']);
            
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al eliminar usuario: ' . $e->getMessage()], 500);
        }
    }

    public function cambiarRol(Request $request, User $user)
    {
        try {
            $validated = $request->validate([
                'role' => 'required|in:admin,gestor,user',
                'sede_id' => 'nullable|exists:sedes,id'
            ]);

            // Validar que los gestores tengan sede asignada
            if ($validated['role'] === 'gestor' && empty($validated['sede_id']) && empty($user->sede_id)) {
                return response()->json([
                    'error' => 'Los gestores deben tener una sede asignada'
                ], 422);
            }

            $user->update([
                'role' => $validated['role'],
                'sede_id' => $validated['sede_id'] ?? $user->sede_id
            ]);

            $user->load('sede');

            return response()->json($user);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al cambiar rol: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Obtener las áreas asignadas a un usuario
     */
    public function getAreasAsignadas(User $user)
    {
        try {
            $areas = $user->areas()->with('sede')->get();
            return response()->json($areas);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al obtener áreas: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Sincronizar áreas asignadas a un usuario
     * Solo para gestores, y solo áreas de su sede
     */
    public function syncAreas(Request $request, User $user)
    {
        try {
            // Solo permitir para gestores
            if ($user->role !== 'gestor') {
                return response()->json([
                    'error' => 'Solo se pueden asignar áreas a gestores'
                ], 422);
            }

            // 🔥 CORRECCIÓN: Validar que area_ids sea un array (puede estar vacío)
            $validated = $request->validate([
                'area_ids' => 'required|array',
                'area_ids.*' => 'exists:areas,id'
            ]);

            \Log::info('🔄 Sincronizando áreas para usuario:', [
                'user_id' => $user->id,
                'area_ids' => $validated['area_ids'],
                'count' => count($validated['area_ids'])
            ]);

            // Verificar que todas las áreas pertenezcan a la sede del gestor
            // 🔥 CORRECCIÓN: Solo validar si hay áreas en el array
            if ($user->sede_id && !empty($validated['area_ids'])) {
                $areasInvalidas = Area::whereIn('id', $validated['area_ids'])
                    ->where('sede_id', '!=', $user->sede_id)
                    ->pluck('id')
                    ->toArray();

                if (!empty($areasInvalidas)) {
                    return response()->json([
                        'error' => 'Algunas áreas no pertenecen a la sede del gestor'
                    ], 422);
                }
            }

            // 🔥 CORRECCIÓN: Sincronizar áreas (array vacío elimina todas las relaciones)
            $user->areas()->sync($validated['area_ids']);

            \Log::info('✅ Áreas sincronizadas correctamente:', [
                'user_id' => $user->id,
                'areas_count' => $user->areas()->count()
            ]);

            // Recargar con relaciones
            $user->load('areas', 'sede');

            return response()->json([
                'message' => 'Áreas asignadas correctamente',
                'user' => $user
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('❌ Error de validación en syncAreas:', $e->errors());
            return response()->json([
                'error' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('❌ Error en syncAreas:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Error al asignar áreas: ' . $e->getMessage()], 500);
        }
    }
}