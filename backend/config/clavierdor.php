<?php

return [
    'admin' => [
        'pseudo' => env(
            'ADMIN_PSEUDO',
            'Administrateur'
        ),

        'email' => env(
            'ADMIN_EMAIL',
            'admin@clavierdor.local'
        ),

        'mot_de_passe' => env('ADMIN_PASSWORD'),
    ],
];
