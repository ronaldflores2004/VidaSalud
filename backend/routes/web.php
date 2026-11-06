<?php

/*
|--------------------------------------------------------------------------
| Rutas web – Sistema de Gestión de Citas Médicas "Clínica Vida Salud"
|--------------------------------------------------------------------------
| Ordenadas por paquete y caso de uso, con la misma numeración del documento (P1 … P7, CU1 … CU22).
| Cada controlador está en la carpeta de su caso de uso: Controllers/P{n}_Paquete/CU{n}_CasoDeUso/.
| El middleware "rol" aplica RN12: cada perfil solo ejecuta lo que le corresponde.
*/

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FrontendAssetController;
use App\Http\Controllers\P1_SeguridadAcceso\CU1_IniciarSesion\LoginController;
use App\Http\Controllers\P1_SeguridadAcceso\CU2_CerrarSesion\LogoutController;
use App\Http\Controllers\P1_SeguridadAcceso\CU3_GestionarUsuarios\UsuarioController;
use App\Http\Controllers\P2_GestionMedica\CU4_GestionarEspecialidades\EspecialidadController;
use App\Http\Controllers\P2_GestionMedica\CU5_GestionarMedicos\MedicoController;
use App\Http\Controllers\P2_GestionMedica\CU6_GestionarHorarios\HorarioController;
use App\Http\Controllers\P2_GestionMedica\CU6_GestionarHorarios\PoliticaCitasController;
use App\Http\Controllers\P3_GestionPacientes\CU7_GestionarPacientes\PacienteController;
use App\Http\Controllers\P3_GestionPacientes\CU8_RegistrarsePaciente\RegistroPacienteController;
use App\Http\Controllers\P4_GestionCitas\CU9_ConsultarDisponibilidad\DisponibilidadController;
use App\Http\Controllers\P4_GestionCitas\CU9_ConsultarDisponibilidad\PortalController;
use App\Http\Controllers\P4_GestionCitas\CU10_ReservarFicha\CitaController;
use App\Http\Controllers\P4_GestionCitas\CU11_ReprogramarCita\ReprogramarCitaController;
use App\Http\Controllers\P4_GestionCitas\CU12_CancelarCita\CancelarCitaController;
use App\Http\Controllers\P4_GestionCitas\CU13_RegistrarAsistencia\AsistenciaController;
use App\Http\Controllers\P4_GestionCitas\CU14_ConsultarAgendaMedica\AgendaController;
use App\Http\Controllers\P5_AtencionMedica\CU15_RegistrarConsultaMedica\ConsultaController;
use App\Http\Controllers\P5_AtencionMedica\CU16_GestionarHistorialClinico\HistorialClinicoController;
use App\Http\Controllers\P5_AtencionMedica\CU17_RegistrarTratamiento\TratamientoController;
use App\Http\Controllers\P5_AtencionMedica\CU18_GestionarEstudiosMedicos\EstudioController;
use App\Http\Controllers\P5_AtencionMedica\CU19_ConsultarHistorialResultados\MiHistorialController;
use App\Http\Controllers\P6_Reportes\CU20_GenerarReportes\ReporteController;
use App\Http\Controllers\P7_PagosNotificaciones\CU21_PagarFicha\PagoController;
use App\Http\Controllers\P7_PagosNotificaciones\CU22_GestionarNotificaciones\NotificacionController;
use Illuminate\Support\Facades\Route;

// Archivos estáticos del frontend (frontend/assets)
Route::get('assets/{ruta}', FrontendAssetController::class)->where('ruta', '.*')->name('assets');

/* ======================= P1 Seguridad y Acceso ======================= */
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'mostrar'])->name('login');              // CU1
    Route::post('/login', [LoginController::class, 'ingresar'])->name('login.ingresar');   // CU1
});

Route::middleware(['auth', 'rol'])->group(function () {
    Route::post('/logout', [LogoutController::class, 'salir'])->name('logout');             // CU2
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

    // CU6 Política de citas: duración de la ficha y plazos RN17 – RN20
    Route::get('politica-citas', [PoliticaCitasController::class, 'index'])->name('politica.index');
    Route::put('politica-citas', [PoliticaCitasController::class, 'update'])->name('politica.update');
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
    Route::get('api/pacientes', [DisponibilidadController::class, 'pacientes'])->name('api.pacientes'); // búsqueda para CU10
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
    // CU10 Reservar ficha – consulta del comprobante y de las citas: cada perfil ve solo lo suyo (el médico, solo sus pacientes)
    Route::get('citas', [CitaController::class, 'index'])->name('citas.index');
    Route::get('citas/{cita}', [CitaController::class, 'show'])->name('citas.show')->whereNumber('cita');
});

Route::middleware(['auth', 'rol:Administrador,Recepcionista,Paciente'])->group(function () {
    // CU11 Reprogramar cita (incluye CU9: fichas libres del mismo médico); el paciente, solo las suyas (RN18)
    Route::get('citas/{cita}/reprogramar', [ReprogramarCitaController::class, 'edit'])->name('citas.reprogramar');
    Route::put('citas/{cita}/reprogramar', [ReprogramarCitaController::class, 'update'])->name('citas.reprogramar.guardar');
});

Route::middleware(['auth', 'rol:Administrador,Recepcionista'])->group(function () {

    // CU13 Registrar asistencia (citas del día)
    Route::get('asistencia', [AsistenciaController::class, 'index'])->name('asistencia.index');
    Route::post('citas/{cita}/asistencia', [AsistenciaController::class, 'store'])->name('asistencia.store');
});

Route::middleware(['auth', 'rol:Administrador,Recepcionista,Paciente'])->group(function () {
    // CU12 Cancelar cita (el paciente, solo las suyas)
    Route::get('citas/{cita}/cancelar', [CancelarCitaController::class, 'create'])->name('citas.cancelar');
    Route::post('citas/{cita}/cancelar', [CancelarCitaController::class, 'store'])->name('citas.cancelar.guardar');
});

Route::middleware(['auth', 'rol:Medico'])->group(function () {
    // CU14 Consultar agenda médica (solo sus citas y sus pacientes)
    Route::get('agenda', AgendaController::class)->name('agenda');
});

/* ======================= P5 Atención Médica e Historial Clínico ======================= */
Route::middleware(['auth', 'rol:Medico'])->group(function () {
    // CU15 Registrar consulta médica (incluye CU16; CU17 y CU18 la extienden)
    Route::get('citas/{cita}/consulta', [ConsultaController::class, 'create'])->name('consultas.create');
    Route::post('citas/{cita}/consulta', [ConsultaController::class, 'store'])->name('consultas.store');
    Route::get('consultas/{consulta}', [ConsultaController::class, 'show'])->name('consultas.show');

    // CU16 Gestionar historial clínico (solo de sus pacientes)
    Route::get('mis-pacientes', [HistorialClinicoController::class, 'index'])->name('historial.index');
    Route::get('pacientes/{paciente}/historial', [HistorialClinicoController::class, 'show'])->name('historial.show');
    Route::put('pacientes/{paciente}/historial', [HistorialClinicoController::class, 'update'])->name('historial.update');

    // CU17 Registrar tratamiento
    Route::post('consultas/{consulta}/tratamientos', [TratamientoController::class, 'store'])->name('tratamientos.store');
    Route::delete('tratamientos/{tratamiento}', [TratamientoController::class, 'destroy'])->name('tratamientos.destroy');

    // CU18 Gestionar estudios médicos (varios por consulta) y su resultado
    Route::get('estudios', [EstudioController::class, 'index'])->name('estudios.index');
    Route::post('consultas/{consulta}/estudios', [EstudioController::class, 'store'])->name('estudios.store');
    Route::get('estudios/{estudio}/resultado', [EstudioController::class, 'edit'])->name('estudios.resultado');
    Route::put('estudios/{estudio}/resultado', [EstudioController::class, 'update'])->name('estudios.resultado.guardar');
});

Route::middleware(['auth', 'rol:Medico,Paciente'])->group(function () {
    // CU18 / CU19 Archivo del resultado: el médico que lo pidió o el propio paciente
    Route::get('estudios/{estudio}/archivo', [EstudioController::class, 'archivo'])->name('estudios.archivo');
});

Route::middleware(['auth', 'rol:Paciente'])->group(function () {
    // CU19 Consultar historial y resultados (solo su propia información)
    Route::get('mi-historial', MiHistorialController::class)->name('mi-historial');
});

/* ======================= P6 Reportes ======================= */
Route::middleware(['auth', 'rol:Administrador'])->group(function () {
    // CU20 Generar reportes (vista previa, PDF y Excel)
    Route::get('reportes', [ReporteController::class, 'index'])->name('reportes.index');
    Route::get('reportes/exportar/{formato}', [ReporteController::class, 'exportar'])->name('reportes.exportar');
});

/* ======================= P7 Pagos y Notificaciones ======================= */
Route::middleware(['auth', 'rol:Paciente'])->group(function () {
    // CU21 Pagar ficha en línea con PayPal (el paciente, solo sus fichas)
    Route::get('citas/{cita}/pagar', [PagoController::class, 'create'])->name('pagos.create');
    Route::post('citas/{cita}/pago/paypal/orden', [PagoController::class, 'orden'])->name('pagos.paypal.orden');
    Route::post('citas/{cita}/pago/paypal/capturar', [PagoController::class, 'capturar'])->name('pagos.paypal.capturar');
});

Route::middleware(['auth', 'rol:Administrador,Recepcionista'])->group(function () {
    // CU21 Cobro en recepción, lista de pagos y reembolsos (RN15)
    Route::post('citas/{cita}/pago', [PagoController::class, 'cobrar'])->name('pagos.cobrar');
    Route::get('pagos', [PagoController::class, 'index'])->name('pagos.index');
    Route::patch('pagos/{pago}/reembolso', [PagoController::class, 'reembolsar'])->name('pagos.reembolsar');
});

// CU22 Service worker de las notificaciones push (debe estar en la raíz del sitio)
Route::get('sw.js', [NotificacionController::class, 'serviceWorker'])->name('push.sw');

Route::middleware(['auth', 'rol:Medico,Paciente'])->group(function () {
    // CU22 Gestionar notificaciones: bandeja y notificaciones push
    Route::get('notificaciones', [NotificacionController::class, 'index'])->name('notificaciones.index');
    Route::post('notificaciones/leidas', [NotificacionController::class, 'marcarLeidas'])->name('notificaciones.leidas');
    Route::post('notificaciones/suscripcion', [NotificacionController::class, 'suscribir'])->name('notificaciones.suscribir');
    Route::delete('notificaciones/suscripcion', [NotificacionController::class, 'desuscribir'])->name('notificaciones.desuscribir');
    Route::post('notificaciones/prueba', [NotificacionController::class, 'probar'])->name('notificaciones.probar');
});
