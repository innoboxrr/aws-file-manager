<?php

return [

    'root' => 'file-manager',

    'bucket' => env('AWS_BUCKET'),

    'url' => env('AWS_URL'),

    'region' => env('AWS_DEFAULT_REGION'),

    'credentials' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
    ],

];
