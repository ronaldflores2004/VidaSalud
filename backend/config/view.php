<?php

/*
| Las vistas viven en la carpeta frontend/ del repositorio (separación Frontend / Backend).
| Se puede cambiar la ubicación con la variable FRONTEND_PATH del archivo .env.
*/

return [
    'paths' => [
        env('FRONTEND_PATH', realpath(base_path('../frontend')) ?: base_path('../frontend')) . '/views',
    ],

    'compiled' => env('VIEW_COMPILED_PATH', realpath(storage_path('framework/views'))),
];
