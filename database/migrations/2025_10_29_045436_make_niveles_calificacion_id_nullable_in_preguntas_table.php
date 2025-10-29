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
        // Primero eliminar la foreign key si existe (puede tener diferentes nombres)
        $foreignKeys = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'preguntas' 
            AND COLUMN_NAME = 'niveles_calificacion_id'
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        
        foreach ($foreignKeys as $fk) {
            DB::statement("ALTER TABLE preguntas DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
        }
        
        // Hacer nullable la columna usando SQL directo para evitar problemas con foreign keys
        DB::statement('ALTER TABLE preguntas MODIFY COLUMN niveles_calificacion_id BIGINT UNSIGNED NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('preguntas', function (Blueprint $table) {
            // Eliminar la foreign key
            $table->dropForeign(['niveles_calificacion_id']);
        });
        
        Schema::table('preguntas', function (Blueprint $table) {
            // Revertir a NOT NULL (requiere un valor por defecto o actualizar registros existentes)
            $table->unsignedBigInteger('niveles_calificacion_id')->nullable(false)->change();
        });
        
        // Recrear la foreign key original
        Schema::table('preguntas', function (Blueprint $table) {
            $table->foreign('niveles_calificacion_id')
                  ->references('id')
                  ->on('niveles_calificacion')
                  ->onDelete('restrict');
        });
    }
};