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

## Avance – Ciclo I y Ciclo II (100 %)

| CU | Caso de uso | Paquete | Ciclo |
|----|-------------|---------|-------|
| CU1 | Iniciar sesión (usuario, CI o correo; bloqueo tras 3 intentos) | P1 | I ✅ |
| CU2 | Cerrar sesión | P1 | I ✅ |
| CU3 | Gestionar usuarios y roles (4 perfiles) | P1 | I ✅ |
| CU4 | Gestionar especialidades | P2 | I ✅ |
| CU5 | Gestionar médicos y sus especialidades | P2 | I ✅ |
| CU6 | Gestionar horarios de atención (fichas de 15 min) | P2 | I ✅ |
| CU7 | Gestionar pacientes (recepción) | P3 | I ✅ |
| CU8 | Registrarse como paciente desde la web | P3 | I ✅ |
| CU9 | Consultar disponibilidad de horarios (portal público) | P4 | I ✅ |
| CU10 | Reservar ficha médica | P4 | I ✅ |
| CU11 | Reprogramar cita (mismo médico, sin cruces; recepción o el propio paciente) | P4 | II ✅ |
| CU12 | Cancelar cita (libera la ficha, guarda el motivo) | P4 | II ✅ |
| CU13 | Registrar asistencia (llegada / inasistencia) | P4 | II ✅ |
| CU14 | Consultar agenda médica (solo sus pacientes) | P4 | II ✅ |
| CU15 | Registrar consulta médica (signos vitales y diagnóstico) | P5 | II ✅ |
| CU16 | Gestionar historial clínico | P5 | II ✅ |
| CU17 | Registrar tratamiento | P5 | II ✅ |
| CU18 | Gestionar estudios médicos (varios por consulta, resultado y archivo) | P5 | II ✅ |
| CU19 | Consultar historial y resultados (paciente) | P5 | II ✅ |
| CU20 | Generar reportes (vista previa, PDF y Excel; incluye pagos e ingresos) | P6 | II ✅ |
| CU21 | Pagar ficha (en línea con PayPal o en recepción con efectivo, QR o tarjeta) | P7 | II ✅ |
| CU22 | Gestionar notificaciones (bandeja y notificaciones push en el navegador) | P7 | II ✅ |

### Base de datos
La base de datos está **diseñada completa desde el Ciclo I y normalizada en 3FN: 23 tablas** (21 desde el Ciclo I + `pagos` y `suscripciones_push` del paquete P7), incluidas las que se
usan en la atención médica (historial clínico, consultas, tratamientos, estudios y sus catálogos), el historial de cada cita,
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

### Pagos (CU21) – PayPal en modo de pruebas
- Al reservar desde la web, el paciente pasa a **Pagar ficha**. PayPal no acepta bolivianos: se cobra en USD con el
  tipo de cambio de `configuracion.TIPO_CAMBIO_USD` (6.96). La orden y la captura se hacen **en el servidor**
  (`PaypalService`, API Orders v2), así el monto no se puede alterar desde el navegador.
- Si no paga en línea, la recepcionista cobra en **Asistencia de hoy** o en la ficha (efectivo, QR o tarjeta).
  Sin pago no se registra la llegada ni se atiende la consulta (RN14). Una ficha pagada que se cancela queda
  **por reembolsar** hasta que recepción registra la devolución (RN15).
- **Configurar PayPal (sandbox):** cree una cuenta en https://developer.paypal.com → *Apps & Credentials* →
  *Sandbox* → *Create App*, copie el *Client ID* y el *Secret* en `.env` (`PAYPAL_CLIENT_ID`, `PAYPAL_SECRET`)
  y ejecute `php artisan config:clear`. Para pagar use la cuenta *Personal* de prueba que aparece en
  *Sandbox accounts* (no se cobra dinero real). **Sin credenciales** el botón funciona como
  *«pago de prueba simulado»* (marcado así en pantalla), útil para demostrar sin internet.

### Notificaciones push (CU22)
- Web Push estándar (VAPID + cifrado aes128gcm) implementado en PHP sin librerías (`app/Support/WebPush.php`).
- Generar las claves una sola vez: `php artisan push:claves` → copiar las dos líneas en `.env` → `php artisan config:clear`.
- El paciente o el médico entra a **Notificaciones → Activar notificaciones** y acepta el permiso del navegador.
  Funciona en Chrome, Edge y Firefox con `http://localhost` / `127.0.0.1` o con HTTPS (no con una IP de la red sin HTTPS),
  y necesita internet porque el navegador recibe el mensaje a través del servicio push de Google/Mozilla.
- Avisos: nueva ficha y cancelación (al médico); recordatorio el día anterior, reprogramación, cancelación,
  pago confirmado y resultado de estudio (al paciente). Los recordatorios los envía la tarea programada:
  `php artisan schedule:work` (dejar abierta) o `php artisan notificaciones:enviar`.

### Política de citas (RN17 – RN20)
Valores por defecto (el administrador los cambia en **Política de citas**, se guardan en la tabla `configuracion`):

| Regla | Valor | Qué pasa |
|-------|-------|----------|
| RN17 Cancelar con reembolso | hasta 24 h antes | Con menos de 24 h se puede cancelar, pero el pago no se devuelve |
| RN18 Reprogramar | hasta 2 h antes, máximo 2 veces | El pago se mantiene; recepción o el propio paciente desde la web |
| RN19 Tolerancia de llegada | 10 min | Recién entonces se puede marcar la inasistencia; el pago no se reembolsa |
| RN20 Inasistencias | 3 en 90 días | El paciente ya no reserva por la web: debe hacerlo en recepción |

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
│   │   │   ├── CU11_ReprogramarCita/              ReprogramarCitaController
│   │   │   ├── CU12_CancelarCita/                 CancelarCitaController
│   │   │   ├── CU13_RegistrarAsistencia/          AsistenciaController
│   │   │   └── CU14_ConsultarAgendaMedica/        AgendaController
│   │   ├── P5_AtencionMedica/
│   │   │   ├── CU15_RegistrarConsultaMedica/      ConsultaController
│   │   │   ├── CU16_GestionarHistorialClinico/    HistorialClinicoController
│   │   │   ├── CU17_RegistrarTratamiento/         TratamientoController
│   │   │   ├── CU18_GestionarEstudiosMedicos/     EstudioController
│   │   │   └── CU19_ConsultarHistorialResultados/ MiHistorialController
│   │   ├── P6_Reportes/
│   │   │   └── CU20_GenerarReportes/              ReporteController
│   │   └── P7_PagosNotificaciones/
│   │       ├── CU21_PagarFicha/                   PagoController
│   │       └── CU22_GestionarNotificaciones/      NotificacionController
│   ├── app/Services/                              reglas de negocio, una carpeta por caso de uso
│   │   ├── P4_GestionCitas/CU9 … CU13/            Disponibilidad, Cita, ReprogramarCita, CancelarCita, Asistencia
│   │   ├── P5_AtencionMedica/CU15, CU18/          ConsultaService, EstudioService
│   │   ├── P6_Reportes/CU20_GenerarReportes/      ReporteService (PDF y Excel)
│   │   └── P7_PagosNotificaciones/CU21, CU22/     PagoService, PaypalService, NotificacionService
│   ├── app/Support/                               PdfSimple, XlsxSimple y WebPush (sin librerías)
│   ├── app/Http/Middleware/P1_SeguridadAcceso/    VerificarRol (control de acceso por perfil)
│   ├── app/Models/P1_SeguridadAcceso … P7_PagosNotificaciones/   entidades compartidas
│   ├── app/Exceptions/                            ReglaNegocioException
│   ├── database/migrations, seeders, sql/         Esquema PostgreSQL normalizado (3FN)
│   ├── routes/web.php                             Rutas ordenadas por paquete y caso de uso
│   └── routes/console.php                         push:claves, notificaciones:enviar y tarea programada
└── frontend/                                  FRONT END – HTML5 (Blade) / CSS3 / JavaScript
    ├── views/
    │   ├── compartido/                            layouts, partials y paneles de inicio
    │   ├── P1_SeguridadAcceso/  CU1_IniciarSesion · CU3_GestionarUsuarios
    │   ├── P2_GestionMedica/    CU4_GestionarEspecialidades · CU5_GestionarMedicos · CU6_GestionarHorarios
    │   ├── P3_GestionPacientes/ CU7_GestionarPacientes · CU8_RegistrarsePaciente
    │   ├── P4_GestionCitas/     CU9 … CU14 (disponibilidad, reservar, reprogramar, cancelar, asistencia, agenda)
    │   ├── P5_AtencionMedica/   CU15 … CU19 (consulta, historial, tratamiento, estudios, mi historial)
    │   ├── P6_Reportes/         CU20_GenerarReportes
    │   └── P7_PagosNotificaciones/  CU21_PagarFicha · CU22_GestionarNotificaciones
    └── assets/
        ├── css/compartido/app.css
        └── js/compartido/app.js
            js/P4_GestionCitas/CU9_ConsultarDisponibilidad/horarios-publicos.js
            js/P4_GestionCitas/CU10_ReservarFicha/cita.js
            js/P4_GestionCitas/CU11_ReprogramarCita/reprogramar.js
            js/P5_AtencionMedica/CU17_RegistrarTratamiento/tratamiento.js
            js/P7_PagosNotificaciones/CU21_PagarFicha/pagar.js
            js/P7_PagosNotificaciones/CU22_GestionarNotificaciones/push.js, sw.js (service worker)
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

## Despliegue en Render (gratis, con HTTPS)

El repositorio trae `Dockerfile`, `render.yaml` y la carpeta `deploy/` (Apache, PHP y arranque).
Al iniciar, el contenedor ejecuta las migraciones, carga los datos de demostración si la base está
vacía y deja corriendo la tarea programada de los recordatorios.

1. Crear una cuenta en https://render.com (con la cuenta de GitHub).
2. **New → Blueprint** → elegir este repositorio → Render lee `render.yaml` y crea la base
   PostgreSQL `clinica-db` y el servicio web `clinica-vida-salud`.
3. Completar las variables que pide: `APP_URL` (`https://<nombre>.onrender.com`), `PAYPAL_CLIENT_ID`,
   `PAYPAL_SECRET`, `VAPID_PUBLIC_KEY` y `VAPID_PRIVATE_KEY` (generadas con `php artisan push:claves`).
4. Esperar el primer despliegue (5–10 min) y abrir la URL.

Límites del plan gratuito: el servicio se duerme tras 15 min sin visitas (tarda ~1 min en despertar),
la base gratuita vence a los 30 días y los archivos subidos (resultados de estudios) se pierden al
reiniciar el servicio.

### Usuarios de prueba (contraseña `Clinica2026`)

Están en `backend/database/seeders/P1_UsuarioSeeder.php`.

| Usuario | Correo | Rol |
|---------|--------|-----|
| `admin` | admin@vidasalud.bo | Administrador |
| `recepcion` | recepcion@vidasalud.bo | Recepcionista |
| `dr.perez` | jperez@vidasalud.bo | Médico (Juan Pérez Vargas) |
| `8456123` (CI) | maria.lopez@correo.bo | Paciente (María López) |

### Datos de prueba (seeders separados por paquete)

| Seeder | Qué carga |
|--------|-----------|
| `P1_RolSeeder` | Los 4 perfiles |
| `P1_UsuarioSeeder` | Las 4 cuentas de prueba de arriba |
| `P2_EspecialidadSeeder` | 9 especialidades |
| `P2_MedicoSeeder` | 9 médicos con sus especialidades y horarios |
| `P2_BloqueoAgendaSeeder` | Feriado del 2 de noviembre |
| `P3_PacienteSeeder` | 5 pacientes con historial clínico y contacto de emergencia |
| `P4_CitaSeeder` | Citas de ejemplo: 3 próximas y 3 para hoy (asistencia y consulta) |
| `P5_MedicamentoSeeder` | Catálogo de medicamentos |
| `P5_CatalogoEstudioSeeder` | Catálogo de estudios |
| `P5_AtencionMedicaSeeder` | Historial de atenciones de las últimas 3 semanas (consultas, tratamientos, estudios) |
| `P7_PagoNotificacionSeeder` | Pagos de las fichas (CM-0002, CM-0003 y CM-0006 quedan pendientes de pago) y avisos al médico |

```bash
php artisan migrate:fresh --seed                 # borra todo, crea las tablas y carga todos los seeders
php artisan db:seed --class=P1_UsuarioSeeder     # carga solo una parte (por ejemplo, solo los usuarios)
```
Cada seeder se puede ejecutar solo: si necesita datos de otro (las citas necesitan médicos y pacientes) los
carga primero, y si se ejecuta dos veces no duplica registros. Los estados de cita y la duración de la ficha
(15 min) los inserta la propia migración, porque son parte del diseño de la base de datos.

## Pruebas

Recorridos automáticos con Playwright: Ciclo I, Ciclo II (27 pruebas), P7 pagos y notificaciones (31 pruebas) y política de citas RN17–RN20 (22 pruebas).

### Pruebas mínimas cubiertas en el Ciclo I

1. Registrar paciente nuevo → queda guardado.
2. Paciente con CI duplicado → rechazado.
3. Cita en horario disponible → registrada.
4. Cita superpuesta para el mismo médico → rechazada.
5. Cita fuera del horario del médico → no se ofrece / rechazada.
9. Página interna sin sesión → redirige al login.
10. Datos inválidos → validación en cliente (JS) y servidor (Laravel).
