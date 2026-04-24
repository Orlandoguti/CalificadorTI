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
        if (!Schema::hasTable('area_user')) {
            Schema::create('area_user', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id');
                $table->unsignedInteger('area_id'); // areas.id es int, no bigint
                $table->timestamps();
                
                // Foreign keys
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('area_id')->references('id')->on('areas')->onDelete('cascade');
                
                // Índice único para evitar duplicados
                $table->unique(['user_id', 'area_id']);
            });
        } else {
            // Si la tabla ya existe, verificar y agregar columnas/índices si faltan
            Schema::table('area_user', function (Blueprint $table) {
                if (!Schema::hasColumn('area_user', 'user_id')) {
                    $table->unsignedBigInteger('user_id')->first();
                }
                if (!Schema::hasColumn('area_user', 'area_id')) {
                    $table->unsignedInteger('area_id')->after('user_id');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('area_user');
    }
};
