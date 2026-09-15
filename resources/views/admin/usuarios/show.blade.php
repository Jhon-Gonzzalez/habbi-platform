@extends('layouts.admin')

@section('titulo', $usuario->name)
@section('subtitulo', $usuario->email)

@section('acciones')
    <a class="hb-btn hb-btn--ghost" href="{{ route('admin.usuarios.index') }}">Volver</a>
    <a class="hb-btn hb-btn--primary" href="{{ route('admin.usuarios.edit', $usuario) }}">Editar</a>
@endsection

@section('contenido')

<div class="hb-stats" style="margin-bottom:1.5rem">
    <div class="hb-stat">
        <div class="hb-stat__icon" aria-hidden="true">⌂</div>
        <div>
            <div class="hb-stat__value">{{ $usuario->alojamientos_count }}</div>
            <div class="hb-stat__label">Publicaciones</div>
        </div>
    </div>
    <div class="hb-stat">
        <div class="hb-stat__icon" aria-hidden="true">★</div>
        <div>
            <div class="hb-stat__value">{{ $usuario->ratings_count }}</div>
            <div class="hb-stat__label">Reseñas escritas</div>
        </div>
    </div>
    <div class="hb-stat">
        <div class="hb-stat__icon" aria-hidden="true">◍</div>
        <div>
            <div class="hb-stat__value" style="font-size:1.1rem">{{ $usuario->isAdmin() ? 'Administrador' : 'Usuario' }}</div>
            <div class="hb-stat__label">Registrado el {{ $usuario->created_at->format('d/m/Y') }}</div>
        </div>
    </div>
</div>

<div class="hb-card">
    <div class="hb-card__header"><h2 style="font-size:1.05rem">Publicaciones de este usuario</h2></div>

    <div class="hb-table-wrap">
        <table class="hb-table">
            <thead>
                <tr><th></th><th>Título</th><th>Ciudad</th><th>Precio</th><th>Calificación</th><th>Estado</th></tr>
            </thead>
            <tbody>
                @forelse ($alojamientos as $a)
                    <tr>
                        <td style="width:60px"><img class="hb-table__thumb" src="{{ $a->coverUrl() }}" alt=""></td>
                        <td><a href="{{ route('alojamientos.show', $a) }}">{{ Str::limit($a->title, 45) }}</a></td>
                        <td>{{ $a->city }}</td>
                        <td>{{ $a->precioFormateado() }}</td>
                        <td><x-estrellas :valor="$a->promedio()" :total="$a->totalResenas()" /></td>
                        <td>
                            <span class="hb-badge {{ $a->is_active ? 'hb-badge--success' : 'hb-badge--muted' }}">
                                {{ $a->is_active ? 'Activo' : 'Pausado' }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="hb-muted hb-center" style="padding:2.5rem">Este usuario no tiene publicaciones.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
