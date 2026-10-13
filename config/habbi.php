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

    /*
    |--------------------------------------------------------------------------
    | Verificación de correo
    |--------------------------------------------------------------------------
    |
    | Interruptor para exigir (o no) que el correo esté verificado antes de
    | reseñar, reportar o pedir ser arrendador.
    |
    | Está en FALSE por defecto para que el proyecto funcione recién clonado,
    | sin tener que montar un servidor de correo. Con el interruptor apagado:
    |
    |   · Quien se registra queda verificado al instante.
    |   · No se envía ningún correo de verificación.
    |   · No se bloquea nada ni aparece el aviso amarillo.
    |
    | EL CÓDIGO SIGUE AHÍ, entero. Esto no borra nada: se enciende poniendo
    | HABBI_VERIFICAR_CORREO=true en el .env, y todo vuelve a funcionar.
    |
    | Antes de publicar con usuarios reales, enciéndelo. Sin verificación,
    | cualquiera puede registrarse con un correo inventado tantas veces como
    | quiera y usar eso para reseñar o reportar. El panel de administración
    | avisa si la aplicación está en producción con esto apagado.
    |
    */

    'verificar_correo' => env('HABBI_VERIFICAR_CORREO', false),

];
