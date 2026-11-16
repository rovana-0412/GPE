<?php

return [

    /*
     * L'interface est servie en same-origin derrière la passerelle Nginx :
     * aucune origine externe n'est autorisée par défaut. Pour autoriser une
     * origine précise, définir CORS_ALLOWED_ORIGINS (liste séparée par des
     * virgules), ex. : CORS_ALLOWED_ORIGINS=https://gpe.example.tg
     */
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('CORS_ALLOWED_ORIGINS', '')),
    ))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
