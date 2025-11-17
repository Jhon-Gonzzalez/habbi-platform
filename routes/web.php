<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AlojamientoController;
use Illuminate\Support\Facades\Auth;

/* ===== Páginas públicas ===== */
Route::get('/', fn () => view('index'))->name('index');

Auth::routes();
Route::get('/home', [HomeController::class, 'index'])->name('home');

/* ===== Solo autenticados (rutas estáticas ANTES de la dinámica) ===== */
Route::middleware('auth')->group(function () {
    // Publicar (DEBE ir antes de la dinámica)
    Route::get('/alojamientos/publicar', [AlojamientoController::class, 'create'])->name('publicar');
    Route::post('/alojamientos', [AlojamientoController::class, 'store'])->name('alojamientos.store');

    // Mis alojamientos + edición
    Route::get('/mis-alojamientos', [AlojamientoController::class, 'mine'])->name('alojamiento.mine');

    Route::get('/alojamientos/{alojamiento}/editar', [AlojamientoController::class, 'edit'])
        ->name('alojamiento.edit')
        ->middleware('can:update,alojamiento');

    Route::put('/alojamientos/{alojamiento}', [AlojamientoController::class, 'update'])
        ->name('alojamiento.update')
        ->middleware('can:update,alojamiento');

    Route::delete('/alojamientos/{alojamiento}', [AlojamientoController::class, 'destroy'])
        ->name('alojamiento.destroy')
        ->middleware('can:delete,alojamiento');
});

/* ===== Listado público ===== */
Route::get('/alojamientos', [AlojamientoController::class, 'index'])->name('alojamiento.index');

/* ===== Detalle dinámico (AL FINAL y restringido) ===== */
Route::get('/alojamientos/{alojamiento}', [AlojamientoController::class, 'show'])
    ->whereNumber('alojamiento')
    ->name('alojamientos.show');
