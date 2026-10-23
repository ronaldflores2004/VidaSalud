<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Carga todos los datos de prueba (ficticios), en el orden de los paquetes del documento.
 *   php artisan migrate:fresh --seed          ← crea las tablas y carga todo
 *   php artisan db:seed --class=P1_UsuarioSeeder   ← carga solo una parte
 *
 * Cada seeder se puede ejecutar solo: si necesita datos de otro (por ejemplo, las citas
 * necesitan médicos y pacientes) los carga primero, y no duplica lo que ya existe.
 * Los estados de cita y la configuración (ficha de 15 min) los inserta la migración.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->callOnce([
            P1_RolSeeder::class,             // P1 Seguridad y Acceso: 4 perfiles
            P1_UsuarioSeeder::class,         //    cuentas de prueba (contraseñas y correos)
            P2_EspecialidadSeeder::class,    // P2 Gestión Médica: 9 especialidades
            P2_MedicoSeeder::class,          //    9 médicos con especialidades y horarios
            P2_BloqueoAgendaSeeder::class,   //    feriados
            P3_PacienteSeeder::class,        // P3 Gestión de Pacientes: 5 pacientes + historial + contacto
            P4_CitaSeeder::class,            // P4 Gestión de Citas: 3 citas de ejemplo
            P5_MedicamentoSeeder::class,     // P5 Atención Médica: catálogo de medicamentos
            P5_CatalogoEstudioSeeder::class, //    catálogo de estudios
        ]);
    }
}
