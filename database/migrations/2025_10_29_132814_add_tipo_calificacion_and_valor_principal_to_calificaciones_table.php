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
        Schema::table('calificaciones', function (Blueprint $table) {
            // Agregar tipo_calificacion (ENUM)
            $table->enum('tipo_calificacion', ['csat', 'nps', 'fcr'])->nullable()->after('sede_id');
            
            // Agregar valor_principal (INT)
            $table->integer('valor_principal')->nullable()->after('tipo_calificacion')->comment('CSAT: 1-4, NPS: 0-10, FCR: 0=Sí, 1=No');
        });
        
        // Migrar datos existentes: inferir tipo desde nivel_calificacion_id o preguntas
        // CSAT: nivel_calificacion_id 1-4
        DB::statement("UPDATE calificaciones SET tipo_calificacion = 'csat', valor_principal = nivel_calificacion_id WHERE nivel_calificacion_id BETWEEN 1 AND 4");
        
        // NPS y FCR se dejarán NULL por ahora, se pueden migrar manualmente si hay datos históricos
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calificaciones', function (Blueprint $table) {
            $table->dropColumn(['tipo_calificacion', 'valor_principal']);
        });
    }
};
