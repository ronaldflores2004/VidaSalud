# Clínica Vida Salud – Sistema Web de Gestión de Citas Médicas

Proyecto del **Segundo Examen Parcial – Ingeniería de Requisitos (INF342-SC)**, UAGRM.
Aplicación web para registrar pacientes, médicos, especialidades y horarios, y programar citas
**sin cruces de horario**, conservando el historial de cada paciente.

**Stack:** PHP 8.2+ · Laravel 12 · PostgreSQL 16 · Blade + HTML5/CSS3 + JavaScript (sin frameworks de frontend)

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

### Cómo se evita la doble reserva (RN07)
1. El formulario solo ofrece los bloques libres (`DisponibilidadService`).
2. Al guardar, `CitaService` vuelve a verificar la disponibilidad dentro de una transacción con `SELECT … FOR UPDATE` sobre el médico.
3. PostgreSQL lo garantiza con una restricción `EXCLUDE USING gist` sobre `(medico_id, tsrange(fecha+hora_inicio, fecha+hora_fin))` para citas no canceladas.

## Arquitectura en capas

```
app/
├── Http/Controllers/        Capa de presentación / control (por paquete)
│   ├── Seguridad/           CU1, CU2, CU3
│   ├── GestionMedica/       CU4, CU5, CU6
│   ├── Pacientes/           CU7
│   └── Citas/               CU8, CU9
├── Http/Middleware/         VerificarRol (control de acceso por rol)
├── Services/                Capa de negocio (DisponibilidadService, CitaService)
├── Exceptions/              ReglaNegocioException
└── Models/                  Capa de datos (Eloquent: Rol, User, Medico, Especialidad, Horario, Paciente, Cita)
resources/views/             Vistas Blade
public/css, public/js        Estilos y validación del lado del cliente
database/migrations          Esquema PostgreSQL (con CHECK y EXCLUDE)
database/sql                 Script SQL del esquema
```

## Instalación (XAMPP / Laragon / cualquier PHP 8.2+)

Requisitos: PHP 8.2+ con `pdo_pgsql`, Composer y PostgreSQL 13+.

```bash
git clone <URL-DEL-REPO>
cd clinica-vida-salud
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
Abrir http://127.0.0.1:8000

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
