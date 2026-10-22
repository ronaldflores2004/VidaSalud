/* =============================================================================
   SISTEMA WEB DE GESTIÓN DE CITAS MÉDICAS – CLÍNICA VIDA SALUD
   Script de la base de datos (PostgreSQL 13 o superior)
   Grupo 4 – Ingeniería de Requisitos (INF342-SC) – UAGRM 2-2026
   -----------------------------------------------------------------------------
   Uso en pgAdmin o psql:
     1) CREATE DATABASE clinica_vida_salud;
     2) Conectarse a la base clinica_vida_salud y ejecutar este archivo completo.
   En el proyecto Laravel las mismas tablas se crean con: php artisan migrate --seed
   -----------------------------------------------------------------------------
   Tablas por paquete (misma numeración del documento):
     P1 Seguridad y Acceso ............ roles, users, bitacora
     P2 Gestión Médica ................ especialidades, medicos, especialidad_medico, horarios, bloqueos_agenda
     P3 Gestión de Pacientes .......... pacientes
     P4 Gestión de Citas .............. citas, historial_citas, notificaciones
     P5 Atención Médica e Historial ... historias_clinicas, consultas, tratamientos, estudios
     (16 tablas del sistema)
     Soporte del framework ............ sessions, password_reset_tokens, cache, cache_locks
   ============================================================================= */

-- Extensiones: btree_gist permite la restricción EXCLUDE (citas sin cruce);
-- unaccent permite buscar pacientes sin importar las tildes.
CREATE EXTENSION IF NOT EXISTS btree_gist;
CREATE EXTENSION IF NOT EXISTS unaccent;


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

-- nombre_normalizado (minúsculas y sin tildes) impide "Pediatría" y "pediatria" duplicados (RF04)
CREATE TABLE especialidades (
    id                  BIGSERIAL     PRIMARY KEY,
    nombre              VARCHAR(80)   NOT NULL,
    nombre_normalizado  VARCHAR(80)   NOT NULL UNIQUE,
    descripcion         VARCHAR(255),
    activo              BOOLEAN       NOT NULL DEFAULT TRUE,
    created_at          TIMESTAMP,
    updated_at          TIMESTAMP
);

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

-- Horario semanal de atención. Cada bloque se divide en fichas de 15 minutos por paciente.
CREATE TABLE horarios (
    id             BIGSERIAL  PRIMARY KEY,
    medico_id      BIGINT     NOT NULL REFERENCES medicos (id) ON DELETE CASCADE,
    dia_semana     SMALLINT   NOT NULL,                     -- 1 = lunes … 7 = domingo
    hora_inicio    TIME       NOT NULL,
    hora_fin       TIME       NOT NULL,
    duracion_cita  SMALLINT   NOT NULL DEFAULT 15,          -- minutos por paciente
    activo         BOOLEAN    NOT NULL DEFAULT TRUE,
    created_at     TIMESTAMP,
    updated_at     TIMESTAMP,
    CONSTRAINT chk_horario_rango     CHECK (hora_fin > hora_inicio),
    CONSTRAINT chk_horario_dia       CHECK (dia_semana BETWEEN 1 AND 7),
    CONSTRAINT chk_horario_duracion  CHECK (duracion_cita = 15)
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
    id                            BIGSERIAL     PRIMARY KEY,
    codigo                        VARCHAR(12)   NOT NULL UNIQUE,     -- PAC-0001
    ci                            VARCHAR(20)   UNIQUE,              -- único cuando se proporciona (RF02)
    nombres                       VARCHAR(80)   NOT NULL,
    apellidos                     VARCHAR(80)   NOT NULL,
    fecha_nacimiento              DATE          NOT NULL,
    sexo                          CHAR(1)       NOT NULL,            -- M, F, O
    telefono                      VARCHAR(20)   NOT NULL,
    email                         VARCHAR(120),
    direccion                     VARCHAR(200),
    tipo_sangre                   VARCHAR(3),
    alergias                      VARCHAR(255),
    contacto_emergencia_nombre    VARCHAR(120),
    contacto_emergencia_parentesco VARCHAR(40),
    contacto_emergencia_telefono  VARCHAR(20),
    activo                        BOOLEAN       NOT NULL DEFAULT TRUE,
    acepta_notificaciones         BOOLEAN       NOT NULL DEFAULT TRUE, -- recordatorios de citas
    user_id                       BIGINT        UNIQUE REFERENCES users (id) ON DELETE SET NULL,
    created_at                    TIMESTAMP,
    updated_at                    TIMESTAMP
);
CREATE INDEX idx_pacientes_apellidos_nombres ON pacientes (apellidos, nombres);


/* =============================================================================
   P4 GESTIÓN DE CITAS (FICHAS)
   ============================================================================= */

-- La cita nunca se borra; cambia de estado (RF09, RF10).
CREATE TABLE citas (
    id                  BIGSERIAL     PRIMARY KEY,
    codigo              VARCHAR(12)   NOT NULL UNIQUE,               -- CM-0001
    paciente_id         BIGINT        NOT NULL REFERENCES pacientes (id) ON DELETE RESTRICT,
    medico_id           BIGINT        NOT NULL REFERENCES medicos (id) ON DELETE RESTRICT,
    especialidad_id     BIGINT        NOT NULL REFERENCES especialidades (id) ON DELETE RESTRICT,
    fecha               DATE          NOT NULL,
    hora_inicio         TIME          NOT NULL,
    hora_fin            TIME          NOT NULL,
    modalidad           VARCHAR(10)   NOT NULL DEFAULT 'PRESENCIAL',  -- PRESENCIAL, VIRTUAL
    motivo              VARCHAR(255),
    estado              VARCHAR(15)   NOT NULL DEFAULT 'PROGRAMADA',
    motivo_cancelacion  VARCHAR(255),
    registrado_por      BIGINT        NOT NULL REFERENCES users (id),
    created_at          TIMESTAMP,
    updated_at          TIMESTAMP,
    CONSTRAINT chk_cita_estado CHECK (estado IN ('PROGRAMADA','CONFIRMADA','ATENDIDA','CANCELADA','INASISTENCIA')),
    CONSTRAINT chk_cita_rango  CHECK (hora_fin > hora_inicio),
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

-- Historial de cada cita: creación, reprogramación y cambios de estado (trazabilidad, RF08–RF12)
CREATE TABLE historial_citas (
    id               BIGSERIAL     PRIMARY KEY,
    cita_id          BIGINT        NOT NULL REFERENCES citas (id) ON DELETE CASCADE,
    accion           VARCHAR(15)   NOT NULL,
    estado_anterior  VARCHAR(15),
    estado_nuevo     VARCHAR(15)   NOT NULL,
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

-- Recordatorios automáticos de próximas consultas y avisos al paciente
CREATE TABLE notificaciones (
    id               BIGSERIAL     PRIMARY KEY,
    paciente_id      BIGINT        NOT NULL REFERENCES pacientes (id) ON DELETE CASCADE,
    cita_id          BIGINT        REFERENCES citas (id) ON DELETE CASCADE,
    tipo             VARCHAR(15)   NOT NULL,                         -- RECORDATORIO, CANCELACION, REPROGRAMACION, RESULTADO
    canal            VARCHAR(10)   NOT NULL DEFAULT 'SISTEMA',       -- SISTEMA, EMAIL
    mensaje          VARCHAR(255)  NOT NULL,
    programada_para  TIMESTAMP     NOT NULL,
    enviada_en       TIMESTAMP,
    leida_en         TIMESTAMP,
    created_at       TIMESTAMP,
    updated_at       TIMESTAMP,
    CONSTRAINT chk_notificacion_tipo  CHECK (tipo IN ('RECORDATORIO','CANCELACION','REPROGRAMACION','RESULTADO')),
    CONSTRAINT chk_notificacion_canal CHECK (canal IN ('SISTEMA','EMAIL'))
);
CREATE INDEX idx_notificaciones_paciente_leida ON notificaciones (paciente_id, leida_en);


/* =============================================================================
   P5 ATENCIÓN MÉDICA E HISTORIAL CLÍNICO
   (tablas listas desde el Ciclo I; pantallas y lógica en el Ciclo II)
   ============================================================================= */

-- Un historial clínico por paciente
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

-- Una consulta por cita atendida; queda registrada en el historial del paciente
CREATE TABLE consultas (
    id                   BIGSERIAL     PRIMARY KEY,
    cita_id              BIGINT        NOT NULL UNIQUE REFERENCES citas (id) ON DELETE RESTRICT,
    historia_clinica_id  BIGINT        NOT NULL REFERENCES historias_clinicas (id) ON DELETE RESTRICT,
    medico_id            BIGINT        NOT NULL REFERENCES medicos (id) ON DELETE RESTRICT,
    fecha_hora           TIMESTAMP     NOT NULL,
    motivo               VARCHAR(255)  NOT NULL,
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
CREATE INDEX idx_consultas_historia_fecha ON consultas (historia_clinica_id, fecha_hora);

-- Tratamiento indicado en la consulta (uno o varios medicamentos)
CREATE TABLE tratamientos (
    id            BIGSERIAL     PRIMARY KEY,
    consulta_id   BIGINT        NOT NULL REFERENCES consultas (id) ON DELETE CASCADE,
    medicamento   VARCHAR(120)  NOT NULL,
    dosis         VARCHAR(60)   NOT NULL,                            -- 500 mg
    frecuencia    VARCHAR(60)   NOT NULL,                            -- cada 8 horas
    duracion      VARCHAR(60)   NOT NULL,                            -- 7 días
    indicaciones  VARCHAR(255),
    created_at    TIMESTAMP,
    updated_at    TIMESTAMP
);

-- Estudios que el médico manda al paciente (pueden ser varios) y su resultado
CREATE TABLE estudios (
    id                 BIGSERIAL     PRIMARY KEY,
    consulta_id        BIGINT        NOT NULL REFERENCES consultas (id) ON DELETE CASCADE,
    tipo               VARCHAR(15)   NOT NULL,                       -- LABORATORIO, IMAGEN, OTRO
    nombre             VARCHAR(120)  NOT NULL,                       -- Hemograma completo, Rayos X…
    indicaciones       VARCHAR(255),
    estado             VARCHAR(12)   NOT NULL DEFAULT 'SOLICITADO',
    fecha_solicitud    DATE          NOT NULL,
    fecha_resultado    DATE,
    resultado          TEXT,
    archivo_resultado  VARCHAR(255),                                 -- ruta del PDF o imagen
    created_at         TIMESTAMP,
    updated_at         TIMESTAMP,
    CONSTRAINT chk_estudio_tipo    CHECK (tipo IN ('LABORATORIO','IMAGEN','OTRO')),
    CONSTRAINT chk_estudio_estado  CHECK (estado IN ('SOLICITADO','REALIZADO','ENTREGADO')),
    CONSTRAINT chk_estudio_fechas  CHECK (fecha_resultado IS NULL OR fecha_resultado >= fecha_solicitud)
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
INSERT INTO especialidades (nombre, nombre_normalizado, descripcion, created_at, updated_at) VALUES
    ('Medicina general',     'medicina general',     'Atención primaria, control y derivación',         NOW(), NOW()),
    ('Pediatría',            'pediatria',            'Atención de niños y adolescentes',                NOW(), NOW()),
    ('Geriatría',            'geriatria',            'Atención del adulto mayor',                       NOW(), NOW()),
    ('Cardiología',          'cardiologia',          'Enfermedades del corazón y presión arterial',     NOW(), NOW()),
    ('Ginecología',          'ginecologia',          'Salud de la mujer',                               NOW(), NOW()),
    ('Traumatología',        'traumatologia',        'Lesiones de huesos, músculos y articulaciones',   NOW(), NOW()),
    ('Reumatología',         'reumatologia',         'Enfermedades de las articulaciones y autoinmunes', NOW(), NOW()),
    ('Oftalmología',         'oftalmologia',         'Salud visual',                                    NOW(), NOW()),
    ('Otorrinolaringología', 'otorrinolaringologia', 'Oído, nariz y garganta',                          NOW(), NOW());

-- Feriado nacional: bloquea la agenda de toda la clínica
INSERT INTO bloqueos_agenda (medico_id, fecha, tipo, motivo, registrado_por, created_at, updated_at)
SELECT NULL, DATE '2026-11-02', 'FERIADO', 'Día de Todos los Difuntos', id, NOW(), NOW()
FROM users WHERE username = 'admin';


/* =============================================================================
   CONSULTAS DE VERIFICACIÓN (opcionales)
   ============================================================================= */
-- Fichas libres de un médico en una fecha se calculan en la aplicación (DisponibilidadService).
-- Citas activas de un médico en una fecha:
--   SELECT codigo, hora_inicio, hora_fin, estado FROM citas
--   WHERE medico_id = 1 AND fecha = CURRENT_DATE AND estado <> 'CANCELADA' ORDER BY hora_inicio;
-- Prueba de la restricción EXCLUDE: insertar dos citas del mismo médico a la misma hora
-- debe fallar con: conflicting key value violates exclusion constraint "excl_cita_medico_sin_cruce"
