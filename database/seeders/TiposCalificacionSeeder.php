<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TiposCalificacionSeeder extends Seeder
{
    public function run(): void
    {
        $tipos = [
            [
                'nombre' => 'CSAT',
                'codigo' => 'csat',
                'descripcion' => 'Customer Satisfaction - Satisfacción del cliente con caritas (emojis)',
                'is_active' => true
            ],
            [
                'nombre' => 'NPS',
                'codigo' => 'nps',
                'descripcion' => 'Net Promoter Score - Probabilidad de recomendación (escala 0-10)',
                'is_active' => true
            ],
            [
                'nombre' => 'FCR',
                'codigo' => 'fcr',
                'descripcion' => 'First Contact Resolution - Resolución en primera interacción (manitas)',
                'is_active' => true
            ]
        ];
        
        foreach ($tipos as $tipo) {
            DB::table('tipos_calificacion')->insert([
                ...$tipo,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
        
        $this->command->info('✅ Tipos de calificación creados: CSAT, NPS, FCR');
    }
}

