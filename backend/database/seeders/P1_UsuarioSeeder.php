<?php

namespace Database\Seeders;

use App\Models\P1_SeguridadAcceso\Rol;
use App\Models\P1_SeguridadAcceso\User;
use Illuminate\Database\Seeder;

/**
 * P1 Seguridad y Acceso – cuentas de prueba (una por perfil).
 * Ejecutar solo este seeder:  php artisan db:seed --class=P1_UsuarioSeeder
 *
 * Contraseña para todos: Clinica2026  (se guarda con hash bcrypt)
 * ┌───────────────┬───────────────┬────────────────────────┬──────────────────────┐
 * │ Perfil        │ Usuario       │ Correo                 │ Nombre               │
 * ├───────────────┼───────────────┼────────────────────────┼──────────────────────┤
 * │ Administrador │ admin         │ admin@vidasalud.bo     │ Ana Gutiérrez Rojas  │
 * │ Recepcionista │ recepcion     │ recepcion@vidasalud.bo │ Carla Méndez Suárez  │
 * │ Médico        │ dr.perez      │ jperez@vidasalud.bo    │ Juan Pérez Vargas    │
 * │ Paciente      │ 8456123 (CI)  │ maria.lopez@correo.bo  │ María López Saavedra │
 * └───────────────┴───────────────┴────────────────────────┴──────────────────────┘
 * El médico y la paciente se vinculan con su ficha en P2_MedicoSeeder y P3_PacienteSeeder.
 */
class P1_UsuarioSeeder extends Seeder
{
    public const CLAVE = 'Clinica2026';

    public function run(): void
    {
        $this->callOnce(P1_RolSeeder::class);
        $rol = Rol::pluck('id', 'nombre');

        $usuarios = [
            ['admin', 'Ana Gutiérrez Rojas', 'admin@vidasalud.bo', Rol::ADMINISTRADOR],
            ['recepcion', 'Carla Méndez Suárez', 'recepcion@vidasalud.bo', Rol::RECEPCIONISTA],
            ['dr.perez', 'Juan Pérez Vargas', 'jperez@vidasalud.bo', Rol::MEDICO],
            ['8456123', 'María López Saavedra', 'maria.lopez@correo.bo', Rol::PACIENTE],
        ];
        foreach ($usuarios as [$username, $nombre, $email, $perfil]) {
            User::updateOrCreate(['username' => $username], [
                'name' => $nombre, 'email' => $email, 'password' => self::CLAVE,
                'rol_id' => $rol[$perfil], 'activo' => true,
            ]);
        }
    }
}
