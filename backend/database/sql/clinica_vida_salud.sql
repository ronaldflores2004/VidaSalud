/* =============================================================================
   SISTEMA WEB DE GESTIÓN DE CITAS MÉDICAS – CLÍNICA VIDA SALUD
   Script de la base de datos normalizada (3FN) – PostgreSQL 13 o superior
   Grupo 4 – Ingeniería de Requisitos (INF342-SC) – UAGRM 2-2026
   -----------------------------------------------------------------------------
   Uso en pgAdmin o psql:
     1) CREATE DATABASE clinica_vida_salud;
     2) Conectarse a la base clinica_vida_salud y ejecutar este archivo completo.
   En el proyecto Laravel las mismas tablas se crean con: php artisan migrate --seed
   -----------------------------------------------------------------------------
   Tablas por paquete (misma numeración del documento) – 21 tablas del sistema:
     P1 Seguridad y Acceso ............ roles, users, bitacora
     P2 Gestión Médica ................ configuracion, especialidades, medicos, especialidad_medico,
                                        horarios, bloqueos_agenda
     P3 Gestión de Pacientes .......... pacientes, contactos_emergencia
     P4 Gestión de Citas .............. estados_cita, citas, historial_citas
     P5 Atención Médica e Historial ... historias_clinicas, consultas, medicamentos, tratamientos,
                                        catalogo_estudios, estudios, notificaciones
     Soporte del framework ............ sessions, password_reset_tokens, cache, cache_locks
   ============================================================================= */

-- btree_gist: restricción EXCLUDE (citas sin cruce). unaccent: comparar textos sin tildes.
CREATE EXTENSION IF NOT EXISTS btree_gist;
CREATE EXTENSION IF NOT EXISTS unaccent;

-- Normaliza un texto: minúsculas, sin tildes y sin espacios repetidos ("  Pediatría " -> "pediatria").
-- IMMUTABLE para poder usarla en un índice único.
CREATE OR REPLACE FUNCTION f_normalizar(texto text) RETURNS text
LANGUAGE sql IMMUTABLE STRICT AS
$$ SELECT lower(regexp_replace(btrim(public.unaccent('public.unaccent'::regdictionary, texto)), '\s+', ' ', 'g')) $$;


/* =============================================================================
   P1 SEGURIDAD Y ACCESO
   ============================================================================= */

-- Los 4 perfiles del sistema: Administrador, Recepcionista, Medico, Paciente
CREATE TABLE roles (
    id           BIGSERIAL     PRIMARY KEY,
    nombre       VARCHAR(30)   NOT NULL UNIQUE,
    descripcion  VARCHAR(150),
    created_at   TIMESTAMP,
    updated_at   TIMESTAMP
);

-- Cuentas de acceso. La contraseña se guarda con hash bcrypt (password_hash de PHP).
-- El paciente que se registra desde la web usa su CI como username.
CREATE TABLE users (
    id              BIGSERIAL     PRIMARY KEY,
    name            VARCHAR(120)  NOT NULL,
    username        VARCHAR(40)   NOT NULL UNIQUE,
    email           VARCHAR(120)  NOT NULL UNIQUE,
    password        VARCHAR(255)  NOT NULL,
    rol_id          BIGINT        NOT NULL REFERENCES roles (id),
    activo          BOOLEAN       NOT NULL DEFAULT TRUE,
    ultimo_acceso   TIMESTAMP,
    remember_token  VARCHAR(100),
    created_at      TIMESTAMP,
    updated_at      TIMESTAMP
);

-- Bitácora de auditoría: quién hizo qué, cuándo y desde qué IP
CREATE TABLE bitacora (
    id           BIGSERIAL     PRIMARY KEY,
    user_id      BIGINT        REFERENCES users (id) ON DELETE SET NULL,
    accion       VARCHAR(20)   NOT NULL,                             -- LOGIN, LOGOUT, CREAR, MODIFICAR…
    modulo       VARCHAR(40)   NOT NULL,                             -- P1_SeguridadAcceso, P4_GestionCitas…
    tabla        VARCHAR(40),
    registro_id  BIGINT,
    descripcion  TEXT,
    ip           VARCHAR(45),
    created_at   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_bitacora_usuario_fecha ON bitacora (user_id, created_at);
CREATE INDEX idx_bitacora_tabla_registro ON bitacora (tabla, registro_id);


/* =============================================================================
   P2 GESTIÓN MÉDICA
   ============================================================================= */

-- Parámetros generales del sistema: un valor único se guarda una sola vez
CREATE TABLE configuracion (
    clave        VARCHAR(40)   PRIMARY KEY,
    valor        VARCHAR(100)  NOT NULL,
    descripcion  VARCHAR(200),
    created_at   TIMESTAMP,
    updated_at   TIMESTAMP
);

-- RF04: el nombre no se repite aunque cambien mayúsculas, tildes o espacios
CREATE TABLE especialidades (
    id           BIGSERIAL     PRIMARY KEY,
    nombre       VARCHAR(80)   NOT NULL,
    descripcion  VARCHAR(255),
    activo       BOOLEAN       NOT NULL DEFAULT TRUE,
    created_at   TIMESTAMP,
    updated_at   TIMESTAMP
);
CREATE UNIQUE INDEX uq_especialidades_nombre ON especialidades (f_normalizar(nombre));

-- Un médico inactivo no recibe nuevas citas pero conserva su historial (RN04, RN11)
CREATE TABLE medicos (
    id                    BIGSERIAL     PRIMARY KEY,
    codigo                VARCHAR(12)   NOT NULL UNIQUE,              -- MED-0001
    registro_profesional  VARCHAR(30)   NOT NULL UNIQUE,
    nombres               VARCHAR(80)   NOT NULL,
    apellidos             VARCHAR(80)   NOT NULL,
    telefono              VARCHAR(20)   NOT NULL,
    email                 VARCHAR(120),
    activo                BOOLEAN       NOT NULL DEFAULT TRUE,
    user_id               BIGINT        UNIQUE REFERENCES users (id) ON DELETE SET NULL,
    created_at            TIMESTAMP,
    updated_at            TIMESTAMP
);

-- Relación N:M: un médico tiene una o varias especialidades y una especialidad varios médicos
CREATE TABLE especialidad_medico (
    medico_id        BIGINT  NOT NULL REFERENCES medicos (id) ON DELETE CASCADE,
    especialidad_id  BIGINT  NOT NULL REFERENCES especialidades (id) ON DELETE RESTRICT,
    PRIMARY KEY (medico_id, especialidad_id)
);

-- Horario semanal de atención. Se divide en fichas de DURACION_FICHA_MIN (tabla configuracion).
CREATE TABLE horarios (
    id           BIGSERIAL  PRIMARY KEY,
    medico_id    BIGINT     NOT NULL REFERENCES medicos (id) ON DELETE CASCADE,
    dia_semana   SMALLINT   NOT NULL,                     -- 1 = lunes … 7 = domingo
    hora_inicio  TIME       NOT NULL,
    hora_fin     TIME       NOT NULL,
    activo       BOOLEAN    NOT NULL DEFAULT TRUE,
    created_at   TIMESTAMP,
    updated_at   TIMESTAMP,
    CONSTRAINT chk_horario_rango CHECK (hora_fin > hora_inicio),
    CONSTRAINT chk_horario_dia   CHECK (dia_semana BETWEEN 1 AND 7)
);

-- Feriados y bloqueos de agenda (RF05). medico_id NULL = toda la clínica; horas NULL = todo el día.
CREATE TABLE bloqueos_agenda (
    id              BIGSERIAL     PRIMARY KEY,
    medico_id       BIGINT        REFERENCES medicos (id) ON DELETE CASCADE,
    fecha           DATE          NOT NULL,
    hora_inicio     TIME,
    hora_fin        TIME,
    tipo            VARCHAR(10)   NOT NULL,                          -- FERIADO, VACACION, PERMISO, OTRO
    motivo          VARCHAR(150)  NOT NULL,
    registrado_por  BIGINT        NOT NULL REFERENCES users (id),
    created_at      TIMESTAMP,
    updated_at      TIMESTAMP,
    CONSTRAINT chk_bloqueo_tipo  CHECK (tipo IN ('FERIADO','VACACION','PERMISO','OTRO')),
    CONSTRAINT chk_bloqueo_rango CHECK ((hora_inicio IS NULL AND hora_fin IS NULL)
                                     OR (hora_inicio IS NOT NULL AND hora_fin IS NOT NULL AND hora_fin > hora_inicio))
);
CREATE INDEX idx_bloqueos_fecha_medico ON bloqueos_agenda (fecha, medico_id);


/* =============================================================================
   P3 GESTIÓN DE PACIENTES
   ============================================================================= */

-- El paciente nunca se elimina: se desactiva para conservar su historial (RN11).
-- user_id se llena cuando el paciente se registra desde la web (CU8).
CREATE TABLE pacientes (
    id                     BIGSERIAL     PRIMARY KEY,
    codigo                 VARCHAR(12)   NOT NULL UNIQUE,            -- PAC-0001
    ci                     VARCHAR(20)   UNIQUE,                     -- único cuando se proporciona (RF02)
    nombres                VARCHAR(80)   NOT NULL,
    apellidos              VARCHAR(80)   NOT NULL,
    fecha_nacimiento       DATE          NOT NULL,
    sexo                   CHAR(1)       NOT NULL,                   -- M, F, O
    telefono               VARCHAR(20)   NOT NULL,
    email                  VARCHAR(120),
    direccion              VARCHAR(200),
    tipo_sangre            VARCHAR(3),
    acepta_notificaciones  BOOLEAN       NOT NULL DEFAULT TRUE,      -- recordatorios de citas
    activo                 BOOLEAN       NOT NULL DEFAULT TRUE,
    user_id                BIGINT        UNIQUE REFERENCES users (id) ON DELETE SET NULL,
    created_at             TIMESTAMP,
    updated_at             TIMESTAMP
);
CREATE INDEX idx_pacientes_apellidos_nombres ON pacientes (apellidos, nombres);

-- 1FN: el contacto de emergencia es un grupo de datos propio (un paciente puede tener varios)
CREATE TABLE contactos_emergencia (
    id           BIGSERIAL     PRIMARY KEY,
    paciente_id  BIGINT        NOT NULL REFERENCES pacientes (id) ON DELETE CASCADE,
    nombre       VARCHAR(120)  NOT NULL,
    parentesco   VARCHAR(40)   NOT NULL,
    telefono     VARCHAR(20)   NOT NULL,
    created_at   TIMESTAMP,
    updated_at   TIMESTAMP
);


/* =============================================================================
   P4 GESTIÓN DE CITAS (FICHAS)
   ============================================================================= */

-- Catálogo de estados de la cita
CREATE TABLE estados_cita (
    codigo         VARCHAR(15)   PRIMARY KEY,
    nombre         VARCHAR(40)   NOT NULL,
    descripcion    VARCHAR(150)  NOT NULL,
    ocupa_horario  BOOLEAN       NOT NULL                          -- FALSE = libera la ficha
);

-- La cita nunca se borra; cambia de estado (RF09, RF10).
-- (medico_id, especialidad_id) → especialidad_medico: solo especialidades que el médico atiende.
CREATE TABLE citas (
    id               BIGSERIAL     PRIMARY KEY,
    codigo           VARCHAR(12)   NOT NULL UNIQUE,                  -- CM-0001
    paciente_id      BIGINT        NOT NULL REFERENCES pacientes (id) ON DELETE RESTRICT,
    medico_id        BIGINT        NOT NULL,
    especialidad_id  BIGINT        NOT NULL,
    fecha            DATE          NOT NULL,
    hora_inicio      TIME          NOT NULL,
    hora_fin         TIME          NOT NULL,
    modalidad        VARCHAR(10)   NOT NULL DEFAULT 'PRESENCIAL',    -- PRESENCIAL, VIRTUAL
    motivo           VARCHAR(255),
    estado           VARCHAR(15)   NOT NULL DEFAULT 'PROGRAMADA' REFERENCES estados_cita (codigo),
    registrado_por   BIGINT        NOT NULL REFERENCES users (id),
    created_at       TIMESTAMP,
    updated_at       TIMESTAMP,
    FOREIGN KEY (medico_id, especialidad_id) REFERENCES especialidad_medico (medico_id, especialidad_id) ON DELETE RESTRICT,
    CONSTRAINT chk_cita_rango     CHECK (hora_fin > hora_inicio),
    CONSTRAINT chk_cita_modalidad CHECK (modalidad IN ('PRESENCIAL','VIRTUAL')),
    -- RN07: un médico no puede tener dos citas activas que se crucen en el tiempo.
    -- Las citas CANCELADAS no participan, por eso liberan la ficha (RN09).
    CONSTRAINT excl_cita_medico_sin_cruce EXCLUDE USING gist (
        medico_id WITH =,
        tsrange(fecha + hora_inicio, fecha + hora_fin) WITH &&
    ) WHERE (estado <> 'CANCELADA')
);
CREATE INDEX idx_citas_medico_fecha   ON citas (medico_id, fecha);
CREATE INDEX idx_citas_paciente_fecha ON citas (paciente_id, fecha);

-- Historial de cada cita: creación, reprogramación y cambios de estado (trazabilidad, RF08–RF12).
-- Aquí queda también el motivo de una cancelación o reprogramación.
CREATE TABLE historial_citas (
    id               BIGSERIAL     PRIMARY KEY,
    cita_id          BIGINT        NOT NULL REFERENCES citas (id) ON DELETE CASCADE,
    accion           VARCHAR(15)   NOT NULL,
    estado_anterior  VARCHAR(15)   REFERENCES estados_cita (codigo),
    estado_nuevo     VARCHAR(15)   NOT NULL REFERENCES estados_cita (codigo),
    fecha_anterior   DATE,
    hora_anterior    TIME,
    fecha_nueva      DATE,
    hora_nueva       TIME,
    motivo           VARCHAR(255),
    user_id          BIGINT        NOT NULL REFERENCES users (id),
    created_at       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_historial_accion CHECK (accion IN
        ('CREADA','REPROGRAMADA','CONFIRMADA','ATENDIDA','CANCELADA','INASISTENCIA'))
);
CREATE INDEX idx_historial_citas_cita_fecha ON historial_citas (cita_id, created_at);


/* =============================================================================
   P5 ATENCIÓN MÉDICA E HISTORIAL CLÍNICO
   (tablas listas desde el Ciclo I; pantallas y lógica en el Ciclo II)
   ============================================================================= */

-- Un historial clínico por paciente (aquí viven las alergias y antecedentes)
CREATE TABLE historias_clinicas (
    id                       BIGSERIAL     PRIMARY KEY,
    numero                   VARCHAR(12)   NOT NULL UNIQUE,          -- HC-0001
    paciente_id              BIGINT        NOT NULL UNIQUE REFERENCES pacientes (id) ON DELETE RESTRICT,
    antecedentes_personales  TEXT,
    antecedentes_familiares  TEXT,
    alergias                 VARCHAR(255),
    enfermedades_cronicas    VARCHAR(255),
    medicacion_habitual      VARCHAR(255),
    created_at               TIMESTAMP,
    updated_at               TIMESTAMP
);

-- Una consulta por cita atendida. El médico y el historial se obtienen de la cita (3FN).
CREATE TABLE consultas (
    id                   BIGSERIAL     PRIMARY KEY,
    cita_id              BIGINT        NOT NULL UNIQUE REFERENCES citas (id) ON DELETE RESTRICT,
    fecha_hora           TIMESTAMP     NOT NULL,
    motivo_consulta      VARCHAR(255)  NOT NULL,
    peso_kg              NUMERIC(5,2),
    talla_cm             NUMERIC(5,1),
    presion_arterial     VARCHAR(10),                                -- 120/80
    temperatura_c        NUMERIC(4,1),
    frecuencia_cardiaca  SMALLINT,
    examen_fisico        TEXT,
    diagnostico          TEXT          NOT NULL,
    observaciones        TEXT,
    created_at           TIMESTAMP,
    updated_at           TIMESTAMP,
    CONSTRAINT chk_consulta_temperatura CHECK (temperatura_c IS NULL OR temperatura_c BETWEEN 30 AND 45)
);

-- Catálogo de medicamentos
CREATE TABLE medicamentos (
    id            BIGSERIAL     PRIMARY KEY,
    nombre        VARCHAR(120)  NOT NULL,                            -- Paracetamol
    presentacion  VARCHAR(60)   NOT NULL,                            -- Tabletas 500 mg
    activo        BOOLEAN       NOT NULL DEFAULT TRUE,
    created_at    TIMESTAMP,
    updated_at    TIMESTAMP,
    UNIQUE (nombre, presentacion)
);

-- Tratamiento indicado en la consulta (uno o varios medicamentos)
CREATE TABLE tratamientos (
    id              BIGSERIAL     PRIMARY KEY,
    consulta_id     BIGINT        NOT NULL REFERENCES consultas (id) ON DELETE CASCADE,
    medicamento_id  BIGINT        NOT NULL REFERENCES medicamentos (id) ON DELETE RESTRICT,
    dosis           VARCHAR(60)   NOT NULL,                          -- 1 tableta
    frecuencia      VARCHAR(60)   NOT NULL,                          -- cada 8 horas
    duracion        VARCHAR(60)   NOT NULL,                          -- 7 días
    indicaciones    VARCHAR(255),
    created_at      TIMESTAMP,
    updated_at      TIMESTAMP
);

-- Catálogo de estudios que se pueden solicitar
CREATE TABLE catalogo_estudios (
    id           BIGSERIAL     PRIMARY KEY,
    nombre       VARCHAR(120)  NOT NULL UNIQUE,                      -- Hemograma completo
    categoria    VARCHAR(15)   NOT NULL,                             -- LABORATORIO, IMAGEN, OTRO
    descripcion  VARCHAR(255),
    activo       BOOLEAN       NOT NULL DEFAULT TRUE,
    created_at   TIMESTAMP,
    updated_at   TIMESTAMP,
    CONSTRAINT chk_catalogo_categoria CHECK (categoria IN ('LABORATORIO','IMAGEN','OTRO'))
);

-- Estudios que el médico manda al paciente (pueden ser varios por consulta) y su resultado
CREATE TABLE estudios (
    id                   BIGSERIAL     PRIMARY KEY,
    consulta_id          BIGINT        NOT NULL REFERENCES consultas (id) ON DELETE CASCADE,
    catalogo_estudio_id  BIGINT        NOT NULL REFERENCES catalogo_estudios (id) ON DELETE RESTRICT,
    indicaciones         VARCHAR(255),
    estado               VARCHAR(12)   NOT NULL DEFAULT 'SOLICITADO',
    fecha_solicitud      DATE          NOT NULL,
    fecha_resultado      DATE,
    resultado            TEXT,
    archivo_resultado    VARCHAR(255),                               -- ruta del PDF o imagen
    created_at           TIMESTAMP,
    updated_at           TIMESTAMP,
    CONSTRAINT chk_estudio_estado CHECK (estado IN ('SOLICITADO','REALIZADO','ENTREGADO')),
    CONSTRAINT chk_estudio_fechas CHECK (fecha_resultado IS NULL OR fecha_resultado >= fecha_solicitud)
);

-- Recordatorios y avisos al paciente. Se refieren a una cita o a un estudio (exactamente uno);
-- el paciente se obtiene de ellos, por eso no se guarda paciente_id (3FN).
CREATE TABLE notificaciones (
    id               BIGSERIAL     PRIMARY KEY,
    cita_id          BIGINT        REFERENCES citas (id) ON DELETE CASCADE,
    estudio_id       BIGINT        REFERENCES estudios (id) ON DELETE CASCADE,
    tipo             VARCHAR(15)   NOT NULL,                         -- RECORDATORIO, CANCELACION, REPROGRAMACION, RESULTADO
    canal            VARCHAR(10)   NOT NULL DEFAULT 'SISTEMA',       -- SISTEMA, EMAIL
    mensaje          VARCHAR(255)  NOT NULL,
    programada_para  TIMESTAMP     NOT NULL,
    enviada_en       TIMESTAMP,
    leida_en         TIMESTAMP,
    created_at       TIMESTAMP,
    updated_at       TIMESTAMP,
    CONSTRAINT chk_notificacion_tipo   CHECK (tipo IN ('RECORDATORIO','CANCELACION','REPROGRAMACION','RESULTADO')),
    CONSTRAINT chk_notificacion_canal  CHECK (canal IN ('SISTEMA','EMAIL')),
    CONSTRAINT chk_notificacion_origen CHECK ((cita_id IS NULL) <> (estudio_id IS NULL))
);


/* =============================================================================
   SOPORTE DEL FRAMEWORK (sesiones, recuperación de contraseña y caché de Laravel)
   ============================================================================= */

CREATE TABLE sessions (
    id             VARCHAR(255)  PRIMARY KEY,
    user_id        BIGINT,
    ip_address     VARCHAR(45),
    user_agent     TEXT,
    payload        TEXT          NOT NULL,
    last_activity  INTEGER       NOT NULL
);
CREATE INDEX idx_sessions_user_id ON sessions (user_id);
CREATE INDEX idx_sessions_last_activity ON sessions (last_activity);

CREATE TABLE password_reset_tokens (
    email       VARCHAR(255)  PRIMARY KEY,
    token       VARCHAR(255)  NOT NULL,
    created_at  TIMESTAMP
);

CREATE TABLE cache (
    key         VARCHAR(255)  PRIMARY KEY,
    value       TEXT          NOT NULL,
    expiration  INTEGER       NOT NULL
);

CREATE TABLE cache_locks (
    key         VARCHAR(255)  PRIMARY KEY,
    owner       VARCHAR(255)  NOT NULL,
    expiration  INTEGER       NOT NULL
);


/* =============================================================================
   DATOS INICIALES
   ============================================================================= */

INSERT INTO configuracion (clave, valor, descripcion, created_at, updated_at) VALUES
    ('DURACION_FICHA_MIN', '15', 'Minutos de atención por paciente (una ficha)', NOW(), NOW());

INSERT INTO estados_cita (codigo, nombre, descripcion, ocupa_horario) VALUES
    ('PROGRAMADA',   'Programada',   'La cita está reservada',                   TRUE),
    ('CONFIRMADA',   'Confirmada',   'Se confirmó la asistencia del paciente',   TRUE),
    ('ATENDIDA',     'Atendida',     'La consulta se realizó',                   TRUE),
    ('CANCELADA',    'Cancelada',    'La cita fue cancelada y liberó la ficha',  FALSE),
    ('INASISTENCIA', 'Inasistencia', 'El paciente no se presentó',               TRUE);

INSERT INTO roles (nombre, descripcion, created_at, updated_at) VALUES
    ('Administrador', 'Configura y supervisa el sistema', NOW(), NOW()),
    ('Recepcionista', 'Gestiona pacientes y citas',       NOW(), NOW()),
    ('Medico',        'Consulta su agenda de atención',   NOW(), NOW()),
    ('Paciente',      'Gestiona su perfil y sus citas',   NOW(), NOW());

-- Usuario administrador inicial. Contraseña: Clinica2026 (hash bcrypt)
INSERT INTO users (name, username, email, password, rol_id, activo, created_at, updated_at)
SELECT 'Administrador del sistema', 'admin', 'admin@vidasalud.bo',
       '$2y$12$zg8mHoU4ov.FZW9E7aB4z.XaP5OL/tTwldm7FC5pmVCMXA8poQZAO', id, TRUE, NOW(), NOW()
FROM roles WHERE nombre = 'Administrador';

-- Especialidades elegidas por el grupo (sin terapia intensiva)
INSERT INTO especialidades (nombre, descripcion, created_at, updated_at) VALUES
    ('Medicina general',     'Atención primaria, control y derivación',          NOW(), NOW()),
    ('Pediatría',            'Atención de niños y adolescentes',                 NOW(), NOW()),
    ('Geriatría',            'Atención del adulto mayor',                        NOW(), NOW()),
    ('Cardiología',          'Enfermedades del corazón y presión arterial',      NOW(), NOW()),
    ('Ginecología',          'Salud de la mujer',                                NOW(), NOW()),
    ('Traumatología',        'Lesiones de huesos, músculos y articulaciones',    NOW(), NOW()),
    ('Reumatología',         'Enfermedades de las articulaciones y autoinmunes', NOW(), NOW()),
    ('Oftalmología',         'Salud visual',                                     NOW(), NOW()),
    ('Otorrinolaringología', 'Oído, nariz y garganta',                           NOW(), NOW());

INSERT INTO medicamentos (nombre, presentacion, created_at, updated_at) VALUES
    ('Paracetamol', 'Tabletas 500 mg', NOW(), NOW()),
    ('Ibuprofeno',  'Tabletas 400 mg', NOW(), NOW()),
    ('Amoxicilina', 'Cápsulas 500 mg', NOW(), NOW()),
    ('Losartán',    'Tabletas 50 mg',  NOW(), NOW()),
    ('Omeprazol',   'Cápsulas 20 mg',  NOW(), NOW()),
    ('Salbutamol',  'Inhalador 100 mcg', NOW(), NOW());

INSERT INTO catalogo_estudios (nombre, categoria, created_at, updated_at) VALUES
    ('Hemograma completo',      'LABORATORIO', NOW(), NOW()),
    ('Glucosa en ayunas',       'LABORATORIO', NOW(), NOW()),
    ('Perfil lipídico',         'LABORATORIO', NOW(), NOW()),
    ('Examen general de orina', 'LABORATORIO', NOW(), NOW()),
    ('Rayos X de tórax',        'IMAGEN',      NOW(), NOW()),
    ('Ecografía abdominal',     'IMAGEN',      NOW(), NOW()),
    ('Electrocardiograma',      'OTRO',        NOW(), NOW());

-- Feriado nacional: bloquea la agenda de toda la clínica
INSERT INTO bloqueos_agenda (medico_id, fecha, tipo, motivo, registrado_por, created_at, updated_at)
SELECT NULL, DATE '2026-11-02', 'FERIADO', 'Día de Todos los Difuntos', id, NOW(), NOW()
FROM users WHERE username = 'admin';


/* =============================================================================
   CONSULTAS DE VERIFICACIÓN (opcionales)
   ============================================================================= */
-- Paciente de una consulta (se obtiene por la cita, no se repite):
--   SELECT p.nombres, p.apellidos FROM consultas co JOIN citas c ON c.id = co.cita_id
--   JOIN pacientes p ON p.id = c.paciente_id WHERE co.id = 1;
-- Prueba de la restricción EXCLUDE: insertar dos citas del mismo médico a la misma hora
-- debe fallar con: conflicting key value violates exclusion constraint "excl_cita_medico_sin_cruce"
