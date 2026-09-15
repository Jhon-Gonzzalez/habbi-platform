<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Datos de contacto públicos
    |--------------------------------------------------------------------------
    |
    | Aparecen en el pie de página. Se configuran por .env para que el
    | proyecto no lleve ningún dominio concreto escrito en el código.
    |
    */

    'contacto' => [
        'email' => env('HABBI_CONTACT_EMAIL'),
        'pais'  => env('HABBI_COUNTRY', 'Colombia'),
    ],

];
