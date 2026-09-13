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

    /*
    | Si la visibilidad de los archivos se maneja con ACL de objeto (`private`
    | o `public-read`). Los buckets creados desde abril de 2023 traen los ACL
    | desactivados (Object Ownership: "Bucket owner enforced") y rechazan el
    | parametro, asi que por defecto no se usan: las subidas quedan privadas,
    | subir como publico o cambiar la visibilidad responde 422 y el indice
    | informa `private`. Activalo solo si el bucket admite ACL.
    */
    'use_acl' => env('AWS_FILE_MANAGER_USE_ACL', false),

];
