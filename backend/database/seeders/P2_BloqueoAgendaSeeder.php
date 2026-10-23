<?php

namespace Database\Seeders;

use App\Models\P1_SeguridadAcceso\User;
use App\Models\P2_GestionMedica\BloqueoAgenda;
use Illuminate\Database\Seeder;

/**
 * P2 Gestión Médica – feriados y bloqueos de agenda (medico_id NULL = toda la clínica).
 * Ejecutar solo este seeder:  php artisan db:seed --class=P2_BloqueoAgendaSeeder
 */
class P2_BloqueoAgendaSeeder extends Seeder
{
    public function run(): void
    {
        $this->callOnce(P1_UsuarioSeeder::class);

        BloqueoAgenda::firstOrCreate(
            ['medico_id' => null, 'fecha' => '2026-11-02', 'tipo' => 'FERIADO'],
            ['motivo' => 'Día de Todos los Difuntos', 'registrado_por' => User::where('username', 'admin')->value('id')]
        );
    }
}
