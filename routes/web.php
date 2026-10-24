<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AliadoController;
use App\Http\Controllers\AlojamientoController;
use App\Http\Controllers\ArrendadorController;
use App\Http\Controllers\ConversacionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CampanaController;
use App\Http\Controllers\FavoritoController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\MarcadorController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\TelefonoController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas públicas
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])->name('index');

Auth::routes(['verify' => true]);

/* Textos legales: públicos, sin sesión */
Route::get('/terminos', [LegalController::class, 'terminos'])->name('legal.terminos');
Route::get('/privacidad', [LegalController::class, 'privacidad'])->name('legal.privacidad');

/*
|--------------------------------------------------------------------------
| Alojamientos
|--------------------------------------------------------------------------
| El orden importa: las rutas estáticas van antes que la dinámica {alojamiento}
| para que /alojamientos/publicar no se interprete como un id.
*/

Route::prefix('alojamientos')->name('alojamientos.')->group(function () {
    // Público
    Route::get('/', [AlojamientoController::class, 'index'])->name('index');

    // Imagen de marcador para los anuncios sin foto. Es pública a
    // propósito: también la piden WhatsApp y las redes al previsualizar
    // un enlace compartido, y esos no tienen sesión.
    Route::get('/{alojamiento}/marcador.png', MarcadorController::class)
        ->whereNumber('alojamiento')
        ->name('marcador');

    // Privado
    Route::middleware('auth')->group(function () {
        // Publicar exige ser arrendador verificado, no solo tener sesión.
        Route::middleware('puede.publicar')->group(function () {
            Route::get('/publicar', [AlojamientoController::class, 'create'])->name('create');
            Route::post('/', [AlojamientoController::class, 'store'])
                ->middleware('throttle:publicar')
                ->name('store');
        });
        Route::get('/mis-publicaciones', [AlojamientoController::class, 'mine'])->name('mine');

        Route::get('/{alojamiento}/editar', [AlojamientoController::class, 'edit'])
            ->whereNumber('alojamiento')
            ->name('edit');

        Route::put('/{alojamiento}', [AlojamientoController::class, 'update'])
            ->whereNumber('alojamiento')
            ->name('update');

        Route::patch('/{alojamiento}/estado', [AlojamientoController::class, 'toggle'])
            ->whereNumber('alojamiento')
            ->name('toggle');

        Route::delete('/{alojamiento}', [AlojamientoController::class, 'destroy'])
            ->whereNumber('alojamiento')
            ->name('destroy');
    });

    // Detalle público — al final para no capturar las rutas estáticas de arriba.
    Route::get('/{alojamiento}', [AlojamientoController::class, 'show'])
        ->whereNumber('alojamiento')
        ->name('show');
});

/*
|--------------------------------------------------------------------------
| Reseñas
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    // Reseñar exige correo verificado: afecta la reputación de otra persona,
    // y sin verificar basta un correo inventado para repetirlo cuantas veces
    // se quiera. Buscar, guardar favoritos y editar el perfil no lo exigen:
    // castigar la navegación por no haber abierto un correo no protege a nadie.
    Route::post('/alojamientos/{alojamiento}/resenas', [RatingController::class, 'store'])
        ->middleware(['verified', 'throttle:resenas'])
        ->whereNumber('alojamiento')
        ->name('ratings.store');

    Route::delete('/resenas/{rating}', [RatingController::class, 'destroy'])
        ->whereNumber('rating')
        ->name('ratings.destroy');

    Route::get('/mi-cuenta', [DashboardController::class, 'index'])->name('home');

    /* ----- Perfil propio ----- */
    Route::get('/mi-perfil', [PerfilController::class, 'edit'])->name('perfil.edit');
    Route::put('/mi-perfil', [PerfilController::class, 'update'])->name('perfil.update');

    /* ----- Ver el teléfono de un alojamiento ----- */
    // No se imprime en la página: hay que pedirlo, y cada petición queda
    // registrada. Si saliera impreso, bastaría una cuenta nueva y un
    // recorrido por el listado para llevarse todos los contactos.
    Route::get('/alojamientos/{alojamiento}/telefono', TelefonoController::class)
        ->middleware('throttle:telefonos')
        ->whereNumber('alojamiento')
        ->name('alojamientos.telefono');

    /* ----- Mensajes entre estudiante y arrendador ----- */
    Route::get('/mensajes', [ConversacionController::class, 'index'])->name('mensajes.index');
    Route::get('/mensajes/{conversacion}', [ConversacionController::class, 'show'])
        ->whereNumber('conversacion')->name('mensajes.show');
    Route::post('/mensajes/{conversacion}', [ConversacionController::class, 'enviar'])
        ->middleware('throttle:mensajes')->whereNumber('conversacion')->name('mensajes.enviar');

    Route::post('/alojamientos/{alojamiento}/escribir', [ConversacionController::class, 'abrir'])
        ->middleware('throttle:mensajes')->whereNumber('alojamiento')->name('mensajes.abrir');

    Route::post('/mensajes/{conversacion}/acuerdo', [ConversacionController::class, 'proponer'])
        ->whereNumber('conversacion')->name('mensajes.acuerdo.proponer');
    Route::post('/mensajes/{conversacion}/acuerdo/confirmar', [ConversacionController::class, 'confirmar'])
        ->whereNumber('conversacion')->name('mensajes.acuerdo.confirmar');
    Route::post('/mensajes/{conversacion}/acuerdo/retirar', [ConversacionController::class, 'retirar'])
        ->whereNumber('conversacion')->name('mensajes.acuerdo.retirar');

    /* ----- Favoritos ----- */
    Route::get('/mis-favoritos', [FavoritoController::class, 'index'])->name('favoritos.index');
    Route::post('/alojamientos/{alojamiento}/favorito', [FavoritoController::class, 'toggle'])
        ->whereNumber('alojamiento')
        ->name('favoritos.toggle');

    /* ----- Reportar una publicación ----- */
    Route::middleware('verified')->group(function () {
        Route::get('/alojamientos/{alojamiento}/reportar', [ReporteController::class, 'create'])
            ->whereNumber('alojamiento')
            ->name('reportes.create');
        Route::post('/alojamientos/{alojamiento}/reportar', [ReporteController::class, 'store'])
            ->middleware('throttle:reportes')
            ->whereNumber('alojamiento')
            ->name('reportes.store');
    });

    /* ----- Verificación como arrendador ----- */
    // Pedir ser arrendador también lo exige: si no podemos escribirle al
    // correo, no hay forma de avisarle si se aprueba o se rechaza.
    Route::get('/quiero-arrendar', [ArrendadorController::class, 'solicitud'])
        ->middleware('verified')->name('arrendador.solicitud');
    Route::post('/quiero-arrendar', [ArrendadorController::class, 'enviarSolicitud'])
        ->middleware('verified')->name('arrendador.solicitud.enviar');

    Route::get('/solicitudes/{solicitud}/documento/{tipo}', [ArrendadorController::class, 'documento'])
        ->whereNumber('solicitud')
        ->whereIn('tipo', ['documento', 'prueba'])
        ->name('arrendador.documento');
});

/* Perfil público del arrendador */
Route::get('/arrendadores/{usuario}', [ArrendadorController::class, 'perfil'])
    ->whereNumber('usuario')
    ->name('arrendadores.perfil');

/*
 * Salida hacia el sitio de un aliado. El enlace pasa por aquí para poder
 * contar los clics; se cuenta el clic, no quién lo hizo.
 */
Route::get('/ir/{aliado}', [AliadoController::class, 'salir'])
    ->whereNumber('aliado')
    ->name('aliados.salir');

/* Campaña de una publicación: qué es, cuánto cuesta y cómo se pide. */
Route::get('/alojamientos/{alojamiento}/campana', [CampanaController::class, 'show'])
    ->whereNumber('alojamiento')
    ->name('alojamientos.campana');

/*
|--------------------------------------------------------------------------
| Panel de administración
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', [Admin\DashboardController::class, 'index'])->name('index');

        Route::resource('usuarios', Admin\UsuarioController::class)
            ->parameters(['usuarios' => 'usuario']);

        Route::post('/usuarios/{usuario}/enlace-reset', [Admin\UsuarioController::class, 'enlaceReset'])
            ->whereNumber('usuario')
            ->name('usuarios.enlace-reset');

        Route::post('/usuarios/{usuario}/verificar-correo', [Admin\UsuarioController::class, 'verificarCorreo'])
            ->whereNumber('usuario')
            ->name('usuarios.verificar-correo');

        Route::get('/solicitudes', [Admin\SolicitudController::class, 'index'])->name('solicitudes.index');
        Route::get('/solicitudes/{solicitud}', [Admin\SolicitudController::class, 'show'])
            ->whereNumber('solicitud')->name('solicitudes.show');
        Route::post('/solicitudes/{solicitud}/aprobar', [Admin\SolicitudController::class, 'aprobar'])
            ->whereNumber('solicitud')->name('solicitudes.aprobar');
        Route::post('/solicitudes/{solicitud}/rechazar', [Admin\SolicitudController::class, 'rechazar'])
            ->whereNumber('solicitud')->name('solicitudes.rechazar');
        Route::post('/usuarios/{usuario}/revocar', [Admin\SolicitudController::class, 'revocar'])
            ->whereNumber('usuario')->name('usuarios.revocar');

        // La auditoría solo se consulta: no hay ruta para borrarla ni editarla.
        Route::get('/auditoria', [Admin\AuditoriaController::class, 'index'])->name('auditoria.index');

        /* Descargar datos en CSV para trabajarlos fuera del panel. */
        Route::get('/exportar/{tipo}', Admin\ExportacionController::class)
            ->whereIn('tipo', ['publicaciones', 'usuarios', 'campanas'])
            ->name('exportar');

        /* Informes de gestión con gráficas y PDF (RF-11, CU-08). */
        Route::get('/informes', [Admin\InformeController::class, 'index'])->name('informes.index');
        Route::get('/informes/pdf', [Admin\InformeController::class, 'pdf'])->name('informes.pdf');

        /*
         * Publicaciones: revisión y moderación en la misma pantalla.
         *
         * Antes eran dos (/revision y /alojamientos) que listaban lo
         * mismo con la mitad de las acciones cada una.
         */
        Route::get('/publicaciones', [Admin\PublicacionController::class, 'index'])->name('publicaciones.index');
        Route::get('/publicaciones/{alojamiento}', [Admin\PublicacionController::class, 'show'])
            ->whereNumber('alojamiento')->name('publicaciones.show');
        Route::post('/publicaciones/{alojamiento}/aprobar', [Admin\PublicacionController::class, 'aprobar'])
            ->whereNumber('alojamiento')->name('publicaciones.aprobar');
        Route::post('/publicaciones/{alojamiento}/rechazar', [Admin\PublicacionController::class, 'rechazar'])
            ->whereNumber('alojamiento')->name('publicaciones.rechazar');
        Route::patch('/publicaciones/{alojamiento}/estado', [Admin\PublicacionController::class, 'toggle'])
            ->whereNumber('alojamiento')->name('publicaciones.toggle');
        Route::delete('/publicaciones/{alojamiento}', [Admin\PublicacionController::class, 'destroy'])
            ->whereNumber('alojamiento')->name('publicaciones.destroy');

        Route::get('/reportes', [Admin\ReporteController::class, 'index'])->name('reportes.index');
        Route::post('/reportes/{reporte}/resolver', [Admin\ReporteController::class, 'resolver'])
            ->whereNumber('reporte')->name('reportes.resolver');

        /* Negocios aliados: publicidad local vendida directamente. */
        Route::resource('aliados', Admin\AliadoController::class)
            ->parameters(['aliados' => 'aliado'])
            ->except(['show']);

        /* Campañas de pago: cobro manual por Nequi/Daviplata. */
        Route::get('/campanas', [Admin\CampanaController::class, 'index'])->name('campanas.index');
        Route::post('/campanas', [Admin\CampanaController::class, 'store'])->name('campanas.store');
        Route::delete('/campanas/{alojamiento}', [Admin\CampanaController::class, 'destroy'])
            ->whereNumber('alojamiento')->name('campanas.destroy');

        /* Tarifas y datos de cobro: los cambia el administrador, no el código. */
        Route::get('/campanas/tarifas', [Admin\TarifaController::class, 'index'])->name('tarifas.index');
        Route::post('/campanas/tarifas', [Admin\TarifaController::class, 'store'])->name('tarifas.store');
        Route::put('/campanas/tarifas/{tarifa}', [Admin\TarifaController::class, 'update'])
            ->whereNumber('tarifa')->name('tarifas.update');
        Route::delete('/campanas/tarifas/{tarifa}', [Admin\TarifaController::class, 'destroy'])
            ->whereNumber('tarifa')->name('tarifas.destroy');
        Route::post('/campanas/cobro', [Admin\TarifaController::class, 'guardarAjustes'])->name('cobro.guardar');

        /* Las direcciones viejas siguen funcionando: pueden estar
           guardadas en un marcador o pegadas en un correo. */
        Route::redirect('/revision', '/admin/publicaciones');
        Route::redirect('/alojamientos', '/admin/publicaciones?estado=todas');
    });
