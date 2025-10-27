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
        Schema::table('areas', function (Blueprint $table) {
            $table->boolean('permite_csat')->default(true)->after('is_active');
            $table->boolean('permite_nps')->default(false)->after('permite_csat');
            $table->boolean('permite_fcr')->default(false)->after('permite_nps');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('areas', function (Blueprint $table) {
            $table->dropColumn(['permite_csat', 'permite_nps', 'permite_fcr']);
        });
    }
};

