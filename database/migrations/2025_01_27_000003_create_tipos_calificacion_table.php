<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_calificacion', function (Blueprint $table) {
            $table->id();
            $table->string('nombre'); // CSAT, NPS, FCR
            $table->string('codigo')->unique(); // csat, nps, fcr
            $table->text('descripcion')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        
        // Crear tabla pivote entre áreas y tipos de calificación
        Schema::create('area_tipo_calificacion', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('area_id');
            $table->unsignedBigInteger('tipo_calificacion_id');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['area_id', 'tipo_calificacion_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('area_tipo_calificacion');
        Schema::dropIfExists('tipos_calificacion');
    }
};

