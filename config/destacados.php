<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Publicaciones destacadas
    |--------------------------------------------------------------------------
    |
    | Precios y datos de contacto para el cobro. Están en configuración y no
    | escritos en una vista porque van a cambiar: la temporada fuerte son
    | enero y julio-agosto, cuando empiezan los semestres, y fuera de ahí
    | conviene bajarlos o regalarlos.
    |
    | El número de Nequi sale del .env para que el repositorio no lleve
    | escrito un número de teléfono real de nadie.
    |
    */

    'activo' => env('HABBI_DESTACADOS_ACTIVO', true),

    /*
     * Los planes que se muestran al arrendador. El administrador puede
     * cobrar cualquier cifra al registrar el pago; esto es solo la lista
     * de precios publicada.
     */
    'planes' => [
        ['dias' => 7,  'precio' => 12000, 'etiqueta' => 'Una semana'],
        ['dias' => 15, 'precio' => 20000, 'etiqueta' => 'Quincena',  'recomendado' => true],
        ['dias' => 30, 'precio' => 35000, 'etiqueta' => 'Un mes'],
    ],

    'contacto' => [
        'nequi'  => env('HABBI_NEQUI'),
        'correo' => env('HABBI_CONTACT_EMAIL'),
    ],

];
