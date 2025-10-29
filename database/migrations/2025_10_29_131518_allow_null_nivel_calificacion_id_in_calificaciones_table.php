<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Buscar y eliminar foreign key constraint si existe
        $foreignKeys = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'calificaciones' 
            AND COLUMN_NAME = 'nivel_calificacion_id' 
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        
        foreach ($foreignKeys as $fk) {
            try {
                DB::statement("ALTER TABLE calificaciones DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
            } catch (\Exception $e) {
                // Ignorar si no existe
            }
        }
        
        // Modificar columna para permitir NULL
        // Usar el tipo que ya tiene la columna pero con NULL permitido
        DB::statement('ALTER TABLE calificaciones MODIFY nivel_calificacion_id INT UNSIGNED NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Actualizar los NULL a un valor por defecto si existen
        DB::statement('UPDATE calificaciones SET nivel_calificacion_id = 1 WHERE nivel_calificacion_id IS NULL');
        
        // Revertir a NOT NULL
        DB::statement('ALTER TABLE calificaciones MODIFY nivel_calificacion_id INT UNSIGNED NOT NULL');
    }
};
