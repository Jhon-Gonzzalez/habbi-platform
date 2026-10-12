<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AlojamientoController;
use App\Http\Controllers\ArrendadorController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\RatingController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas públicas
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])->name('index');

Auth::routes(['verify' => false]);

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
            Route::post('/', [AlojamientoController::class, 'store'])->name('store');
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
    Route::post('/alojamientos/{alojamiento}/resenas', [RatingController::class, 'store'])
        ->whereNumber('alojamiento')
        ->name('ratings.store');

    Route::delete('/resenas/{rating}', [RatingController::class, 'destroy'])
        ->whereNumber('rating')
        ->name('ratings.destroy');

    Route::get('/mi-cuenta', [DashboardController::class, 'index'])->name('home');

    /* ----- Perfil propio ----- */
    Route::get('/mi-perfil', [PerfilController::class, 'edit'])->name('perfil.edit');
    Route::put('/mi-perfil', [PerfilController::class, 'update'])->name('perfil.update');

    /* ----- Verificación como arrendador ----- */
    Route::get('/quiero-arrendar', [ArrendadorController::class, 'solicitud'])->name('arrendador.solicitud');
    Route::post('/quiero-arrendar', [ArrendadorController::class, 'enviarSolicitud'])->name('arrendador.solicitud.enviar');

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

        Route::get('/solicitudes', [Admin\SolicitudController::class, 'index'])->name('solicitudes.index');
        Route::get('/solicitudes/{solicitud}', [Admin\SolicitudController::class, 'show'])
            ->whereNumber('solicitud')->name('solicitudes.show');
        Route::post('/solicitudes/{solicitud}/aprobar', [Admin\SolicitudController::class, 'aprobar'])
            ->whereNumber('solicitud')->name('solicitudes.aprobar');
        Route::post('/solicitudes/{solicitud}/rechazar', [Admin\SolicitudController::class, 'rechazar'])
            ->whereNumber('solicitud')->name('solicitudes.rechazar');
        Route::post('/usuarios/{usuario}/revocar', [Admin\SolicitudController::class, 'revocar'])
            ->whereNumber('usuario')->name('usuarios.revocar');

        Route::get('/alojamientos', [Admin\AlojamientoController::class, 'index'])->name('alojamientos.index');

        Route::patch('/alojamientos/{alojamiento}/estado', [Admin\AlojamientoController::class, 'toggle'])
            ->whereNumber('alojamiento')
            ->name('alojamientos.toggle');

        Route::delete('/alojamientos/{alojamiento}', [Admin\AlojamientoController::class, 'destroy'])
            ->whereNumber('alojamiento')
            ->name('alojamientos.destroy');
    });
