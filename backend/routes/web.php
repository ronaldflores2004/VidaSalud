<?php

/*
|--------------------------------------------------------------------------
| Rutas web – Sistema de Gestión de Citas Médicas "Clínica Vida Salud"
|--------------------------------------------------------------------------
| Ordenadas por paquete, con la misma numeración del documento (P1 … P6).
| El middleware "rol" aplica RN12: cada perfil solo ejecuta lo que le corresponde.
*/

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FrontendAssetController;
use App\Http\Controllers\P1_SeguridadAcceso\LoginController;
use App\Http\Controllers\P1_SeguridadAcceso\UsuarioController;
use App\Http\Controllers\P2_GestionMedica\EspecialidadController;
use App\Http\Controllers\P2_GestionMedica\HorarioController;
use App\Http\Controllers\P2_GestionMedica\MedicoController;
use App\Http\Controllers\P3_GestionPacientes\PacienteController;
use App\Http\Controllers\P3_GestionPacientes\RegistroPacienteController;
use App\Http\Controllers\P4_GestionCitas\CitaController;
use App\Http\Controllers\P4_GestionCitas\DisponibilidadController;
use App\Http\Controllers\P4_GestionCitas\PortalController;
use Illuminate\Support\Facades\Route;

// Archivos estáticos del frontend (frontend/assets)
Route::get('assets/{ruta}', FrontendAssetController::class)->where('ruta', '.*')->name('assets');

/* ======================= P1 Seguridad y Acceso ======================= */
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'mostrar'])->name('login');              // CU1
    Route::post('/login', [LoginController::class, 'ingresar'])->name('login.ingresar');   // CU1
});

Route::middleware(['auth', 'rol'])->group(function () {
    Route::post('/logout', [LoginController::class, 'salir'])->name('logout');             // CU2
    Route::get('/panel', DashboardController::class)->name('dashboard');
});

Route::middleware(['auth', 'rol:Administrador'])->group(function () {
    // CU3 Gestionar usuarios
    Route::resource('usuarios', UsuarioController::class)->except(['show', 'destroy'])->parameters(['usuarios' => 'usuario']);
    Route::patch('usuarios/{usuario}/estado', [UsuarioController::class, 'cambiarEstado'])->name('usuarios.estado');
});

/* ======================= P2 Gestión Médica ======================= */
Route::middleware(['auth', 'rol:Administrador'])->group(function () {
    // CU4 Gestionar especialidades
    Route::resource('especialidades', EspecialidadController::class)->except(['show', 'destroy'])->parameters(['especialidades' => 'especialidad']);
    Route::patch('especialidades/{especialidad}/estado', [EspecialidadController::class, 'cambiarEstado'])->name('especialidades.estado');

    // CU5 Gestionar médicos (registro y modificación)
    Route::resource('medicos', MedicoController::class)->only(['create', 'store', 'edit', 'update']);
    Route::patch('medicos/{medico}/estado', [MedicoController::class, 'cambiarEstado'])->name('medicos.estado');

    // CU6 Gestionar horarios de atención (fichas de 15 min)
    Route::get('medicos/{medico}/horarios', [HorarioController::class, 'index'])->name('horarios.index');
    Route::post('medicos/{medico}/horarios', [HorarioController::class, 'store'])->name('horarios.store');
    Route::patch('medicos/{medico}/horarios/{horario}/estado', [HorarioController::class, 'cambiarEstado'])->name('horarios.estado');
    Route::delete('medicos/{medico}/horarios/{horario}', [HorarioController::class, 'destroy'])->name('horarios.destroy');
});

Route::middleware(['auth', 'rol:Administrador,Recepcionista'])->group(function () {
    // CU5 Gestionar médicos (consulta)
    Route::get('medicos', [MedicoController::class, 'index'])->name('medicos.index');
    Route::get('medicos/{medico}', [MedicoController::class, 'show'])->name('medicos.show')->whereNumber('medico');
});

/* ======================= P3 Gestión de Pacientes ======================= */
Route::middleware('guest')->group(function () {
    // CU8 Registrarse como paciente (desde la web)
    Route::get('/registro', [RegistroPacienteController::class, 'mostrar'])->name('registro');
    Route::post('/registro', [RegistroPacienteController::class, 'registrar'])->name('registro.guardar');
});

Route::middleware(['auth', 'rol:Administrador,Recepcionista'])->group(function () {
    // CU7 Gestionar pacientes (recepción)
    Route::resource('pacientes', PacienteController::class)->except(['destroy']);
    Route::patch('pacientes/{paciente}/estado', [PacienteController::class, 'cambiarEstado'])->name('pacientes.estado');
    Route::get('api/pacientes', [DisponibilidadController::class, 'pacientes'])->name('api.pacientes');
});

/* ======================= P4 Gestión de Citas (Fichas) ======================= */
// CU9 Consultar disponibilidad: portal público, sin iniciar sesión
Route::get('/', [PortalController::class, 'inicio'])->name('portal');
Route::get('/horarios', [PortalController::class, 'horarios'])->name('portal.horarios');
Route::get('api/especialidades/{especialidad}/medicos', [DisponibilidadController::class, 'medicos'])->name('api.medicos');
Route::get('api/medicos/{medico}/bloques', [DisponibilidadController::class, 'bloques'])->name('api.bloques');

Route::middleware(['auth', 'rol:Administrador,Recepcionista,Paciente'])->group(function () {
    // CU10 Reservar ficha: el paciente para sí mismo o la recepcionista para un paciente
    Route::get('citas/nueva', [CitaController::class, 'create'])->name('citas.create');
    Route::post('citas', [CitaController::class, 'store'])->name('citas.store');
});

Route::middleware(['auth', 'rol:Administrador,Recepcionista,Medico,Paciente'])->group(function () {
    // Consulta de citas: cada perfil ve solo lo suyo (el médico, solo sus pacientes)
    Route::get('citas', [CitaController::class, 'index'])->name('citas.index');
    Route::get('citas/{cita}', [CitaController::class, 'show'])->name('citas.show')->whereNumber('cita');
});

/* ======================= P5 Atención Médica e Historial Clínico — Ciclo II ======================= */
/* ======================= P6 Reportes — Ciclo II ======================= */
