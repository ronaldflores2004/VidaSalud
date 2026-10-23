<?php

namespace Database\Seeders;

use App\Models\P1_SeguridadAcceso\Rol;
use Illuminate\Database\Seeder;

/**
 * P1 Seguridad y Acceso – los 4 perfiles del sistema.
 * Ejecutar solo este seeder:  php artisan db:seed --class=P1_RolSeeder
 */
class P1_RolSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            Rol::ADMINISTRADOR => 'Configura y supervisa el sistema',
            Rol::RECEPCIONISTA => 'Gestiona pacientes y citas',
            Rol::MEDICO => 'Consulta su agenda de atención',
            Rol::PACIENTE => 'Gestiona su perfil y sus citas',
        ];
        foreach ($roles as $nombre => $descripcion) {
            Rol::updateOrCreate(['nombre' => $nombre], ['descripcion' => $descripcion]);
        }
    }
}
