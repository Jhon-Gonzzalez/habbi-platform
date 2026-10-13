<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AlojamientoController;
use App\Http\Controllers\ArrendadorController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FavoritoController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LegalController;
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

        Route::get('/reportes', [Admin\ReporteController::class, 'index'])->name('reportes.index');
        Route::post('/reportes/{reporte}/resolver', [Admin\ReporteController::class, 'resolver'])
            ->whereNumber('reporte')->name('reportes.resolver');

        Route::get('/alojamientos', [Admin\AlojamientoController::class, 'index'])->name('alojamientos.index');

        Route::patch('/alojamientos/{alojamiento}/estado', [Admin\AlojamientoController::class, 'toggle'])
            ->whereNumber('alojamiento')
            ->name('alojamientos.toggle');

        Route::delete('/alojamientos/{alojamiento}', [Admin\AlojamientoController::class, 'destroy'])
            ->whereNumber('alojamiento')
            ->name('alojamientos.destroy');
    });
