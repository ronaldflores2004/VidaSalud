<?php

// Mensajes de validación en español (lado del servidor)
return [
    'required' => 'El campo :attribute es obligatorio.',
    'string' => 'El campo :attribute debe ser texto.',
    'integer' => 'El campo :attribute debe ser un número entero.',
    'email' => 'Ingrese un correo electrónico válido.',
    'unique' => 'El :attribute ya está registrado.',
    'exists' => 'El :attribute seleccionado no es válido.',
    'confirmed' => 'La confirmación de :attribute no coincide.',
    'date' => 'El campo :attribute no es una fecha válida.',
    'date_format' => 'El campo :attribute no tiene el formato :format.',
    'after' => 'El campo :attribute debe ser posterior a :date.',
    'after_or_equal' => 'El campo :attribute debe ser una fecha igual o posterior a hoy.',
    'before_or_equal' => 'El campo :attribute no puede ser una fecha futura.',
    'between' => ['numeric' => 'El campo :attribute debe estar entre :min y :max.'],
    'in' => 'El valor de :attribute no es válido.',
    'regex' => 'El formato de :attribute no es válido.',
    'alpha_dash' => 'El :attribute solo puede tener letras, números y guiones.',
    'array' => 'El campo :attribute no es válido.',
    'min' => ['string' => 'El campo :attribute debe tener al menos :min caracteres.', 'array' => 'Seleccione al menos :min opción.'],
    'max' => ['string' => 'El campo :attribute no puede superar :max caracteres.'],
    'password' => [
        'letters' => 'La contraseña debe contener al menos una letra.',
        'numbers' => 'La contraseña debe contener al menos un número.',
    ],
    'attributes' => [
        'nombres' => 'nombres', 'apellidos' => 'apellidos', 'telefono' => 'teléfono', 'email' => 'correo electrónico',
        'direccion' => 'dirección', 'fecha' => 'fecha', 'hora_inicio' => 'hora', 'motivo' => 'motivo',
        'registro_profesional' => 'número de registro profesional', 'descripcion' => 'descripción', 'nombre' => 'nombre',
        'ci' => 'CI', 'sexo' => 'sexo', 'paciente_id' => 'paciente', 'especialidades' => 'especialidades',
    ],
];
