# Clínica Vida Salud – Sistema Web de Gestión de Citas Médicas

Proyecto del **Segundo Examen Parcial – Ingeniería de Requisitos (INF342-SC)**, UAGRM.
Aplicación web para registrar pacientes, médicos, especialidades y horarios, y programar citas
**sin cruces de horario**, conservando el historial de cada paciente.

**Grupo 4** – Docente: M.Sc. Ing. Garzón Cuéllar Angélica

| Integrante | Registro |
|------------|----------|
| Flores Llanos Ronald | 222008350 |
| Salazar Alcocer Carlos Alfonso | 215049616 |

**Stack Full Stack:** Backend PHP 8.2+ · Laravel 12 · PostgreSQL 16 — Frontend HTML5 (Blade) · CSS3 · JavaScript

## Avance – Ciclo I (Presentación 1)

| CU | Caso de uso | Estado |
|----|-------------|--------|
| CU1 | Iniciar sesión (usuario, CI o correo; bloqueo tras 3 intentos) | ✅ |
| CU2 | Cerrar sesión | ✅ |
| CU3 | Gestionar usuarios y roles (4 perfiles) | ✅ |
| CU4 | Gestionar especialidades | ✅ |
| CU5 | Gestionar médicos y sus especialidades | ✅ |
| CU6 | Gestionar horarios de atención (fichas de 15 min) | ✅ |
| CU7 | Gestionar pacientes (recepción) | ✅ |
| CU8 | Registrarse como paciente desde la web | ✅ |
| CU9 | Consultar disponibilidad de horarios (portal público) | ✅ |
| CU10 | Reservar / programar ficha | ✅ |
| CU11–CU20 | Reprogramar, cancelar, asistencia, agenda del médico, consulta, historial clínico, tratamiento, estudios, resultados, reportes | Ciclo II |

### Base de datos
La base de datos está **diseñada completa desde el Ciclo I y normalizada en 3FN: 21 tablas**, incluidas las que se
usan en el Ciclo II (historial clínico, consultas, tratamientos, estudios y sus catálogos), el historial de cada cita,
feriados/bloqueos de agenda, recordatorios (notificaciones) y la bitácora de auditoría.

Normalización aplicada:
- **1FN:** el contacto de emergencia sale de `pacientes` a su tabla `contactos_emergencia` (un paciente puede tener varios).
- **2FN:** la única clave compuesta (`especialidad_medico`) no tiene dependencias parciales; la cita usa la FK compuesta
  `(medico_id, especialidad_id)` → `especialidad_medico`, así no puede tener una especialidad que el médico no atiende.
- **3FN:** sin dependencias transitivas: `consultas` y `notificaciones` obtienen médico/paciente por la cita;
  las alergias viven solo en `historias_clinicas`; el motivo de cancelación va en `historial_citas`; catálogos
  `estados_cita`, `medicamentos` y `catalogo_estudios`; la duración de la ficha (15 min) está una sola vez en
  `configuracion`; el nombre de especialidad es único con un índice sobre `f_normalizar(nombre)`.
 El script completo para pgAdmin/psql está en
`backend/database/sql/clinica_vida_salud.sql` y es equivalente a `php artisan migrate --seed`.
El diagrama de clases de la base de datos está en StarUML: `docs/uml/clinica_vida_salud_bd.mdj` (imagen: `docs/uml/diagrama_clases_bd.png`).

### Cómo se evita la doble reserva (RN07)
1. El formulario solo ofrece los bloques libres (`DisponibilidadService`).
2. Al guardar, `CitaService` vuelve a verificar la disponibilidad dentro de una transacción con `SELECT … FOR UPDATE` sobre el médico.
3. PostgreSQL lo garantiza con una restricción `EXCLUDE USING gist` sobre `(medico_id, tsrange(fecha+hora_inicio, fecha+hora_fin))` para citas no canceladas.

## Estructura del repositorio (Full Stack: Backend / Frontend)

Los paquetes **y los casos de uso** tienen la **misma numeración que el documento PUDS-UML**: dentro de cada
paquete `P{n}_Paquete/` hay una carpeta por caso de uso `CU{n}_CasoDeUso/`, tanto en el backend como en el frontend.
Los modelos (entidades) y el middleware se comparten entre casos de uso, por eso quedan agrupados solo por paquete.

```
clinica-vida-salud/
├── backend/                                   BACK END – PHP 8.2+ / Laravel 12 / PostgreSQL
│   ├── app/Http/Controllers/
│   │   ├── P1_SeguridadAcceso/
│   │   │   ├── CU1_IniciarSesion/                 LoginController
│   │   │   ├── CU2_CerrarSesion/                  LogoutController
│   │   │   └── CU3_GestionarUsuarios/             UsuarioController
│   │   ├── P2_GestionMedica/
│   │   │   ├── CU4_GestionarEspecialidades/       EspecialidadController
│   │   │   ├── CU5_GestionarMedicos/              MedicoController
│   │   │   └── CU6_GestionarHorarios/             HorarioController
│   │   ├── P3_GestionPacientes/
│   │   │   ├── CU7_GestionarPacientes/            PacienteController
│   │   │   └── CU8_RegistrarsePaciente/           RegistroPacienteController
│   │   ├── P4_GestionCitas/
│   │   │   ├── CU9_ConsultarDisponibilidad/       PortalController, DisponibilidadController
│   │   │   ├── CU10_ReservarFicha/                CitaController
│   │   │   └── CU11 … CU14                        Ciclo II
│   │   ├── P5_AtencionMedica/  CU15 … CU19        Ciclo II
│   │   └── P6_Reportes/        CU20               Ciclo II
│   ├── app/Services/P4_GestionCitas/
│   │   ├── CU9_ConsultarDisponibilidad/           DisponibilidadService
│   │   └── CU10_ReservarFicha/                    CitaService (CU10 incluye a CU9)
│   ├── app/Http/Middleware/P1_SeguridadAcceso/    VerificarRol (control de acceso por perfil)
│   ├── app/Models/P1_SeguridadAcceso … P5_AtencionMedica/   entidades compartidas
│   ├── app/Exceptions/                            ReglaNegocioException
│   ├── database/migrations, seeders, sql/         Esquema PostgreSQL normalizado (3FN)
│   └── routes/web.php                             Rutas ordenadas por paquete y caso de uso
└── frontend/                                  FRONT END – HTML5 (Blade) / CSS3 / JavaScript
    ├── views/
    │   ├── compartido/                            layouts, partials y paneles de inicio
    │   ├── P1_SeguridadAcceso/  CU1_IniciarSesion · CU3_GestionarUsuarios
    │   ├── P2_GestionMedica/    CU4_GestionarEspecialidades · CU5_GestionarMedicos · CU6_GestionarHorarios
    │   ├── P3_GestionPacientes/ CU7_GestionarPacientes · CU8_RegistrarsePaciente
    │   ├── P4_GestionCitas/     CU9_ConsultarDisponibilidad · CU10_ReservarFicha · CU11 … CU14 (Ciclo II)
    │   ├── P5_AtencionMedica/   CU15 … CU19 (Ciclo II)
    │   └── P6_Reportes/         CU20 (Ciclo II)
    └── assets/
        ├── css/compartido/app.css
        └── js/compartido/app.js
            js/P4_GestionCitas/CU9_ConsultarDisponibilidad/horarios-publicos.js
            js/P4_GestionCitas/CU10_ReservarFicha/cita.js
```

El backend lee las vistas desde `frontend/views` (`backend/config/view.php`) y entrega los archivos de
`frontend/assets` en la ruta `/assets/...`, sin enlaces simbólicos, así funciona igual en XAMPP (Windows) y Linux.

## Instalación (XAMPP / Laragon / cualquier PHP 8.2+)

Requisitos: PHP 8.2+ con `pdo_pgsql`, Composer y PostgreSQL 13+.

```bash
git clone <URL-DEL-REPO>
cd clinica-vida-salud/backend
composer install
cp .env.example .env          # en Windows: copy .env.example .env
php artisan key:generate
```

Cree la base de datos y ajuste `.env`:

```sql
CREATE DATABASE clinica_vida_salud;
```
```
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=clinica_vida_salud
DB_USERNAME=postgres
DB_PASSWORD=su_clave
```

```bash
php artisan migrate --seed
php artisan serve
```
Abrir http://127.0.0.1:8000 (portal público) o http://127.0.0.1:8000/login

> Con XAMPP, apunte el sitio a la carpeta `backend/public`.

> En XAMPP habilite `extension=pdo_pgsql` y `extension=pgsql` en `php.ini`.
> Las migraciones crean las extensiones `btree_gist` y `unaccent` (vienen con PostgreSQL); el usuario de la BD necesita permiso para crearlas.

### Usuarios de prueba (contraseña `Clinica2026`)

| Usuario | Rol |
|---------|-----|
| `admin` | Administrador |
| `recepcion` | Recepcionista |
| `dr.perez` | Médico |
| `8456123` (CI) | Paciente (María López) |

## Pruebas mínimas cubiertas en el Ciclo I

1. Registrar paciente nuevo → queda guardado.
2. Paciente con CI duplicado → rechazado.
3. Cita en horario disponible → registrada.
4. Cita superpuesta para el mismo médico → rechazada.
5. Cita fuera del horario del médico → no se ofrece / rechazada.
9. Página interna sin sesión → redirige al login.
10. Datos inválidos → validación en cliente (JS) y servidor (Laravel).
