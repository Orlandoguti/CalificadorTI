<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Eliminar tabla si existe (ya no se usa, ahora usamos subpreguntas con es_rango_indicador)
        if (Schema::hasTable('pregunta_indicador_rangos')) {
            Schema::dropIfExists('pregunta_indicador_rangos');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No recreamos la tabla en rollback, ya no se necesita
    }
};
