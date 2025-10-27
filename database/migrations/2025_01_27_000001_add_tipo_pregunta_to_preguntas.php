<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('preguntas', function (Blueprint $table) {
            // Agregar columna para el tipo de pregunta (csat, nps, fcr) solo si no existe
            if (!Schema::hasColumn('preguntas', 'tipo_pregunta')) {
                $table->enum('tipo_pregunta', ['csat', 'nps', 'fcr'])
                      ->nullable()
                      ->after('tipo');
            }
            
            // Agregar descripción solo si no existe
            if (!Schema::hasColumn('preguntas', 'descripcion')) {
                $table->string('descripcion')->nullable()->after('pregunta');
            }
        });
        
        // Crear tabla pivote para relación many-to-many entre áreas y preguntas
        if (!Schema::hasTable('area_pregunta')) {
            DB::statement('CREATE TABLE area_pregunta (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                area_id BIGINT UNSIGNED NOT NULL,
                pregunta_id BIGINT UNSIGNED NOT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                UNIQUE KEY unique_area_pregunta (area_id, pregunta_id),
                INDEX idx_area_id (area_id),
                INDEX idx_pregunta_id (pregunta_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('area_pregunta');
        
        Schema::table('preguntas', function (Blueprint $table) {
            $table->dropColumn(['tipo_pregunta', 'descripcion']);
        });
    }
};

