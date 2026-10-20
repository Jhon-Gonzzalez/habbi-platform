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

    /*
    |--------------------------------------------------------------------------
    | Copias de seguridad (RNF-3)
    |--------------------------------------------------------------------------
    |
    | El programador de tareas hace una copia cada noche y borra las que
    | pasen de 'dias'. Catorce días es suficiente para darse cuenta de un
    | borrado por error sin llenar el disco: la base de una plataforma de
    | este tamaño pesa megabytes, no gigas.
    |
    | OJO: el respaldo se guarda en el MISMO servidor. Eso protege de un
    | borrado por error, no de que el servidor se pierda. Para lo segundo
    | hace falta copiar la carpeta a otro sitio; está explicado en
    | docs/respaldos.md, y no se hace desde el código porque depende de
    | dónde se despliegue.
    |
    */

    'respaldos' => [
        'dias' => (int) env('HABBI_RESPALDOS_DIAS', 14),
        'hora' => env('HABBI_RESPALDOS_HORA', '03:20'),
    ],

];
