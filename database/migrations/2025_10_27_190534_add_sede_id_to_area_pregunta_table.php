<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('area_pregunta', function (Blueprint $table) {
            // Verificar si la columna ya existe
            if (!Schema::hasColumn('area_pregunta', 'sede_id')) {
                $table->unsignedInteger('sede_id')->nullable()->after('area_id');
            }
        });
        
        // Agregar foreign keys con validación
        Schema::table('area_pregunta', function (Blueprint $table) {
            // Foreign key a sedes
            if (!$this->foreignExists('area_pregunta_sede_id_foreign')) {
                try {
                    $table->foreign('sede_id')->references('id')->on('sedes')->onDelete('cascade');
                } catch (\Exception $e) {
                    Log::info('No se pudo crear foreign key para sede_id: ' . $e->getMessage());
                }
            }
            
            // Foreign key a areas
            if (!$this->foreignExists('area_pregunta_area_id_foreign')) {
                try {
                    $table->foreign('area_id')->references('id')->on('areas')->onDelete('cascade');
                } catch (\Exception $e) {
                    Log::info('No se pudo crear foreign key para area_id: ' . $e->getMessage());
                }
            }
            
            // Foreign key a preguntas
            if (!$this->foreignExists('area_pregunta_pregunta_id_foreign')) {
                try {
                    $table->foreign('pregunta_id')->references('id')->on('preguntas')->onDelete('cascade');
                } catch (\Exception $e) {
                    Log::info('No se pudo crear foreign key para pregunta_id: ' . $e->getMessage());
                }
            }
        });
    }
    
    private function foreignExists($name) {
        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();
        try {
            $foreignKeys = $connection->select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'area_pregunta' AND CONSTRAINT_NAME = ?", [$databaseName, $name]);
            return count($foreignKeys) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('area_pregunta', function (Blueprint $table) {
            $table->dropForeign(['sede_id']);
            $table->dropColumn('sede_id');
        });
    }
};
