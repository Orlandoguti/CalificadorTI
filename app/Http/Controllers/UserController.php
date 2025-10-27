<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Sede;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = User::with('sede');
            
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

    public function update(Request $request, User $user)
{
    try {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
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
}