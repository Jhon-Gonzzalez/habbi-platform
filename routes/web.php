<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AlojamientoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
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
        Route::get('/publicar', [AlojamientoController::class, 'create'])->name('create');
        Route::post('/', [AlojamientoController::class, 'store'])->name('store');
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
});

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

        Route::get('/alojamientos', [Admin\AlojamientoController::class, 'index'])->name('alojamientos.index');

        Route::patch('/alojamientos/{alojamiento}/estado', [Admin\AlojamientoController::class, 'toggle'])
            ->whereNumber('alojamiento')
            ->name('alojamientos.toggle');

        Route::delete('/alojamientos/{alojamiento}', [Admin\AlojamientoController::class, 'destroy'])
            ->whereNumber('alojamiento')
            ->name('alojamientos.destroy');
    });
