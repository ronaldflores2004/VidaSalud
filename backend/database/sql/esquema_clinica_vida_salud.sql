-- PostgreSQL database dump

\restrict ccogQozbKOBx7IE3cjBps1vFQjIMjqq6AKxDSsWLrVO2GFmpeRidLhMGUs0Lntn

-- Dumped from database version 16.15 (Ubuntu 16.15-0ubuntu0.24.04.1)
-- Dumped by pg_dump version 16.15 (Ubuntu 16.15-0ubuntu0.24.04.1)

-- Name: citas; Type: TABLE; Schema: public; Owner: -

CREATE TABLE public.citas (
    id bigint NOT NULL,
    codigo character varying(12) NOT NULL,
    paciente_id bigint NOT NULL,
    medico_id bigint NOT NULL,
    especialidad_id bigint NOT NULL,
    fecha date NOT NULL,
    hora_inicio time(0) without time zone NOT NULL,
    hora_fin time(0) without time zone NOT NULL,
    motivo character varying(255),
    estado character varying(15) DEFAULT 'PROGRAMADA'::character varying NOT NULL,
    motivo_cancelacion character varying(255),
    registrado_por bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT chk_cita_estado CHECK (((estado)::text = ANY ((ARRAY['PROGRAMADA'::character varying, 'CONFIRMADA'::character varying, 'ATENDIDA'::character varying, 'CANCELADA'::character varying, 'INASISTENCIA'::character varying])::text[]))),
    CONSTRAINT chk_cita_rango CHECK ((hora_fin > hora_inicio))
);

-- Name: citas_id_seq; Type: SEQUENCE; Schema: public; Owner: -

CREATE SEQUENCE public.citas_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

-- Name: citas_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -

ALTER SEQUENCE public.citas_id_seq OWNED BY public.citas.id;

-- Name: especialidad_medico; Type: TABLE; Schema: public; Owner: -

CREATE TABLE public.especialidad_medico (
    medico_id bigint NOT NULL,
    especialidad_id bigint NOT NULL
);

-- Name: especialidades; Type: TABLE; Schema: public; Owner: -

CREATE TABLE public.especialidades (
    id bigint NOT NULL,
    nombre character varying(80) NOT NULL,
    nombre_normalizado character varying(80) NOT NULL,
    descripcion character varying(255),
    activo boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);

-- Name: especialidades_id_seq; Type: SEQUENCE; Schema: public; Owner: -

CREATE SEQUENCE public.especialidades_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

-- Name: especialidades_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -

ALTER SEQUENCE public.especialidades_id_seq OWNED BY public.especialidades.id;

-- Name: horarios; Type: TABLE; Schema: public; Owner: -

CREATE TABLE public.horarios (
    id bigint NOT NULL,
    medico_id bigint NOT NULL,
    dia_semana smallint NOT NULL,
    hora_inicio time(0) without time zone NOT NULL,
    hora_fin time(0) without time zone NOT NULL,
    duracion_cita smallint DEFAULT '30'::smallint NOT NULL,
    activo boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT chk_horario_dia CHECK (((dia_semana >= 1) AND (dia_semana <= 7))),
    CONSTRAINT chk_horario_duracion CHECK (((duracion_cita >= 10) AND (duracion_cita <= 120))),
    CONSTRAINT chk_horario_rango CHECK ((hora_fin > hora_inicio))
);

-- Name: horarios_id_seq; Type: SEQUENCE; Schema: public; Owner: -

CREATE SEQUENCE public.horarios_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

-- Name: horarios_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -

ALTER SEQUENCE public.horarios_id_seq OWNED BY public.horarios.id;

-- Name: medicos; Type: TABLE; Schema: public; Owner: -

CREATE TABLE public.medicos (
    id bigint NOT NULL,
    codigo character varying(12) NOT NULL,
    registro_profesional character varying(30) NOT NULL,
    nombres character varying(80) NOT NULL,
    apellidos character varying(80) NOT NULL,
    telefono character varying(20) NOT NULL,
    email character varying(120),
    activo boolean DEFAULT true NOT NULL,
    user_id bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);

-- Name: medicos_id_seq; Type: SEQUENCE; Schema: public; Owner: -

CREATE SEQUENCE public.medicos_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

-- Name: medicos_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -

ALTER SEQUENCE public.medicos_id_seq OWNED BY public.medicos.id;

-- Name: pacientes; Type: TABLE; Schema: public; Owner: -

CREATE TABLE public.pacientes (
    id bigint NOT NULL,
    codigo character varying(12) NOT NULL,
    ci character varying(20),
    nombres character varying(80) NOT NULL,
    apellidos character varying(80) NOT NULL,
    fecha_nacimiento date NOT NULL,
    sexo character(1) NOT NULL,
    telefono character varying(20) NOT NULL,
    email character varying(120),
    direccion character varying(200),
    tipo_sangre character varying(3),
    alergias character varying(255),
    contacto_emergencia_nombre character varying(120),
    contacto_emergencia_telefono character varying(20),
    activo boolean DEFAULT true NOT NULL,
    user_id bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);

-- Name: pacientes_id_seq; Type: SEQUENCE; Schema: public; Owner: -

CREATE SEQUENCE public.pacientes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

-- Name: pacientes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -

ALTER SEQUENCE public.pacientes_id_seq OWNED BY public.pacientes.id;

-- Name: roles; Type: TABLE; Schema: public; Owner: -

CREATE TABLE public.roles (
    id bigint NOT NULL,
    nombre character varying(30) NOT NULL,
    descripcion character varying(150),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);

-- Name: roles_id_seq; Type: SEQUENCE; Schema: public; Owner: -

CREATE SEQUENCE public.roles_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

-- Name: roles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -

ALTER SEQUENCE public.roles_id_seq OWNED BY public.roles.id;

-- Name: users; Type: TABLE; Schema: public; Owner: -

CREATE TABLE public.users (
    id bigint NOT NULL,
    name character varying(120) NOT NULL,
    username character varying(40) NOT NULL,
    email character varying(120) NOT NULL,
    password character varying(255) NOT NULL,
    rol_id bigint NOT NULL,
    activo boolean DEFAULT true NOT NULL,
    ultimo_acceso timestamp(0) without time zone,
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);

-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: -

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;

-- Name: citas id; Type: DEFAULT; Schema: public; Owner: -

ALTER TABLE ONLY public.citas ALTER COLUMN id SET DEFAULT nextval('public.citas_id_seq'::regclass);

-- Name: especialidades id; Type: DEFAULT; Schema: public; Owner: -

ALTER TABLE ONLY public.especialidades ALTER COLUMN id SET DEFAULT nextval('public.especialidades_id_seq'::regclass);

-- Name: horarios id; Type: DEFAULT; Schema: public; Owner: -

ALTER TABLE ONLY public.horarios ALTER COLUMN id SET DEFAULT nextval('public.horarios_id_seq'::regclass);

-- Name: medicos id; Type: DEFAULT; Schema: public; Owner: -

ALTER TABLE ONLY public.medicos ALTER COLUMN id SET DEFAULT nextval('public.medicos_id_seq'::regclass);

-- Name: pacientes id; Type: DEFAULT; Schema: public; Owner: -

ALTER TABLE ONLY public.pacientes ALTER COLUMN id SET DEFAULT nextval('public.pacientes_id_seq'::regclass);

-- Name: roles id; Type: DEFAULT; Schema: public; Owner: -

ALTER TABLE ONLY public.roles ALTER COLUMN id SET DEFAULT nextval('public.roles_id_seq'::regclass);

-- Name: users id; Type: DEFAULT; Schema: public; Owner: -

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);

-- Name: citas citas_codigo_unique; Type: CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.citas
    ADD CONSTRAINT citas_codigo_unique UNIQUE (codigo);

-- Name: citas citas_pkey; Type: CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.citas
    ADD CONSTRAINT citas_pkey PRIMARY KEY (id);

-- Name: especialidad_medico especialidad_medico_pkey; Type: CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.especialidad_medico
    ADD CONSTRAINT especialidad_medico_pkey PRIMARY KEY (medico_id, especialidad_id);

-- Name: especialidades especialidades_nombre_normalizado_unique; Type: CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.especialidades
    ADD CONSTRAINT especialidades_nombre_normalizado_unique UNIQUE (nombre_normalizado);

-- Name: especialidades especialidades_pkey; Type: CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.especialidades
    ADD CONSTRAINT especialidades_pkey PRIMARY KEY (id);

-- Name: citas excl_cita_medico_sin_cruce; Type: CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.citas
    ADD CONSTRAINT excl_cita_medico_sin_cruce EXCLUDE USING gist (medico_id WITH =, tsrange((fecha + hora_inicio), (fecha + hora_fin)) WITH &&) WHERE (((estado)::text <> 'CANCELADA'::text));

-- Name: horarios horarios_pkey; Type: CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.horarios
    ADD CONSTRAINT horarios_pkey PRIMARY KEY (id);

-- Name: medicos medicos_codigo_unique; Type: CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.medicos
    ADD CONSTRAINT medicos_codigo_unique UNIQUE (codigo);

-- Name: medicos medicos_pkey; Type: CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.medicos
    ADD CONSTRAINT medicos_pkey PRIMARY KEY (id);

-- Name: medicos medicos_registro_profesional_unique; Type: CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.medicos
    ADD CONSTRAINT medicos_registro_profesional_unique UNIQUE (registro_profesional);

-- Name: medicos medicos_user_id_unique; Type: CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.medicos
    ADD CONSTRAINT medicos_user_id_unique UNIQUE (user_id);

-- Name: pacientes pacientes_ci_unique; Type: CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.pacientes
    ADD CONSTRAINT pacientes_ci_unique UNIQUE (ci);

-- Name: pacientes pacientes_codigo_unique; Type: CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.pacientes
    ADD CONSTRAINT pacientes_codigo_unique UNIQUE (codigo);

-- Name: pacientes pacientes_pkey; Type: CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.pacientes
    ADD CONSTRAINT pacientes_pkey PRIMARY KEY (id);

-- Name: pacientes pacientes_user_id_unique; Type: CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.pacientes
    ADD CONSTRAINT pacientes_user_id_unique UNIQUE (user_id);

-- Name: roles roles_nombre_unique; Type: CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_nombre_unique UNIQUE (nombre);

-- Name: roles roles_pkey; Type: CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_pkey PRIMARY KEY (id);

-- Name: users users_email_unique; Type: CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);

-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);

-- Name: users users_username_unique; Type: CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_username_unique UNIQUE (username);

-- Name: citas_medico_id_fecha_index; Type: INDEX; Schema: public; Owner: -

CREATE INDEX citas_medico_id_fecha_index ON public.citas USING btree (medico_id, fecha);

-- Name: citas_paciente_id_fecha_index; Type: INDEX; Schema: public; Owner: -

CREATE INDEX citas_paciente_id_fecha_index ON public.citas USING btree (paciente_id, fecha);

-- Name: pacientes_apellidos_nombres_index; Type: INDEX; Schema: public; Owner: -

CREATE INDEX pacientes_apellidos_nombres_index ON public.pacientes USING btree (apellidos, nombres);

-- Name: citas citas_especialidad_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.citas
    ADD CONSTRAINT citas_especialidad_id_foreign FOREIGN KEY (especialidad_id) REFERENCES public.especialidades(id) ON DELETE RESTRICT;

-- Name: citas citas_medico_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.citas
    ADD CONSTRAINT citas_medico_id_foreign FOREIGN KEY (medico_id) REFERENCES public.medicos(id) ON DELETE RESTRICT;

-- Name: citas citas_paciente_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.citas
    ADD CONSTRAINT citas_paciente_id_foreign FOREIGN KEY (paciente_id) REFERENCES public.pacientes(id) ON DELETE RESTRICT;

-- Name: citas citas_registrado_por_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.citas
    ADD CONSTRAINT citas_registrado_por_foreign FOREIGN KEY (registrado_por) REFERENCES public.users(id);

-- Name: especialidad_medico especialidad_medico_especialidad_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.especialidad_medico
    ADD CONSTRAINT especialidad_medico_especialidad_id_foreign FOREIGN KEY (especialidad_id) REFERENCES public.especialidades(id) ON DELETE RESTRICT;

-- Name: especialidad_medico especialidad_medico_medico_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.especialidad_medico
    ADD CONSTRAINT especialidad_medico_medico_id_foreign FOREIGN KEY (medico_id) REFERENCES public.medicos(id) ON DELETE CASCADE;

-- Name: horarios horarios_medico_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.horarios
    ADD CONSTRAINT horarios_medico_id_foreign FOREIGN KEY (medico_id) REFERENCES public.medicos(id) ON DELETE CASCADE;

-- Name: medicos medicos_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.medicos
    ADD CONSTRAINT medicos_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;

-- Name: pacientes pacientes_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.pacientes
    ADD CONSTRAINT pacientes_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;

-- Name: users users_rol_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_rol_id_foreign FOREIGN KEY (rol_id) REFERENCES public.roles(id);

-- PostgreSQL database dump complete

\unrestrict ccogQozbKOBx7IE3cjBps1vFQjIMjqq6AKxDSsWLrVO2GFmpeRidLhMGUs0Lntn

