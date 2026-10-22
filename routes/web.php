<?php

/*
|--------------------------------------------------------------------------
| Rutas web – Sistema de Gestión de Citas Médicas "Clínica Vida Salud"
|--------------------------------------------------------------------------
| Organizadas por paquete de análisis. El middleware "rol" aplica RN12:
| solo los usuarios autorizados pueden ejecutar cada operación.
*/

use App\Http\Controllers\Citas\CitaController;
use App\Http\Controllers\Citas\DisponibilidadController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GestionMedica\EspecialidadController;
use App\Http\Controllers\GestionMedica\HorarioController;
use App\Http\Controllers\GestionMedica\MedicoController;
use App\Http\Controllers\Pacientes\PacienteController;
use App\Http\Controllers\Seguridad\LoginController;
use App\Http\Controllers\Seguridad\UsuarioController;
use Illuminate\Support\Facades\Route;

// ---------- P1 Seguridad y Acceso: CU1 Iniciar sesión / CU2 Cerrar sesión ----------
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'mostrar'])->name('login');
    Route::post('/login', [LoginController::class, 'ingresar'])->name('login.ingresar');
});

Route::middleware(['auth', 'rol'])->group(function () {
    Route::post('/logout', [LoginController::class, 'salir'])->name('logout');
    Route::get('/', DashboardController::class)->name('dashboard');

    // ---------- Solo Administrador ----------
    Route::middleware('rol:Administrador')->group(function () {
        // CU3 Gestionar usuarios
        Route::resource('usuarios', UsuarioController::class)->except(['show', 'destroy'])
            ->parameters(['usuarios' => 'usuario']);
        Route::patch('usuarios/{usuario}/estado', [UsuarioController::class, 'cambiarEstado'])->name('usuarios.estado');

        // CU4 Gestionar especialidades
        Route::resource('especialidades', EspecialidadController::class)->except(['show', 'destroy'])
            ->parameters(['especialidades' => 'especialidad']);
        Route::patch('especialidades/{especialidad}/estado', [EspecialidadController::class, 'cambiarEstado'])->name('especialidades.estado');

        // CU5 Gestionar médicos (escritura)
        Route::resource('medicos', MedicoController::class)->only(['create', 'store', 'edit', 'update']);
        Route::patch('medicos/{medico}/estado', [MedicoController::class, 'cambiarEstado'])->name('medicos.estado');

        // CU6 Gestionar horarios de atención
        Route::get('medicos/{medico}/horarios', [HorarioController::class, 'index'])->name('horarios.index');
        Route::post('medicos/{medico}/horarios', [HorarioController::class, 'store'])->name('horarios.store');
        Route::patch('medicos/{medico}/horarios/{horario}/estado', [HorarioController::class, 'cambiarEstado'])->name('horarios.estado');
        Route::delete('medicos/{medico}/horarios/{horario}', [HorarioController::class, 'destroy'])->name('horarios.destroy');
    });

    // ---------- Administrador y Recepcionista ----------
    Route::middleware('rol:Administrador,Recepcionista')->group(function () {
        // CU5 consulta de médicos
        Route::get('medicos', [MedicoController::class, 'index'])->name('medicos.index');
        Route::get('medicos/{medico}', [MedicoController::class, 'show'])->name('medicos.show')->whereNumber('medico');

        // CU7 Gestionar pacientes
        Route::resource('pacientes', PacienteController::class)->except(['destroy']);
        Route::patch('pacientes/{paciente}/estado', [PacienteController::class, 'cambiarEstado'])->name('pacientes.estado');

        // CU8 Programar cita + CU9 Consultar disponibilidad (JSON)
        Route::get('citas/nueva', [CitaController::class, 'create'])->name('citas.create');
        Route::post('citas', [CitaController::class, 'store'])->name('citas.store');
        Route::get('api/especialidades/{especialidad}/medicos', [DisponibilidadController::class, 'medicos'])->name('api.medicos');
        Route::get('api/medicos/{medico}/bloques', [DisponibilidadController::class, 'bloques'])->name('api.bloques');
        Route::get('api/pacientes', [DisponibilidadController::class, 'pacientes'])->name('api.pacientes');
    });

    // ---------- Consulta de citas: Administrador, Recepcionista y Médico ----------
    Route::middleware('rol:Administrador,Recepcionista,Medico')->group(function () {
        Route::get('citas', [CitaController::class, 'index'])->name('citas.index');
        Route::get('citas/{cita}', [CitaController::class, 'show'])->name('citas.show')->whereNumber('cita');
    });
});
