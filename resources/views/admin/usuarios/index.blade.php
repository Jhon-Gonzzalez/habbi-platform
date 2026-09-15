@extends('layouts.admin')

@section('titulo', 'Usuarios')
@section('subtitulo', $usuarios->total() . ' ' . \App\Support\Texto::plural('usuario', $usuarios->total()) . ' registrados')

@section('acciones')
    <a class="hb-btn hb-btn--primary" href="{{ route('admin.usuarios.create') }}">Nuevo usuario</a>
@endsection

@section('contenido')
<div class="hb-card">
    <div class="hb-card__header">
        <form method="GET" action="{{ route('admin.usuarios.index') }}" class="hb-row">
            <label class="hb-sr-only" for="q">Buscar usuario</label>
            <input class="hb-input" type="search" id="q" name="q" value="{{ request('q') }}"
                   placeholder="Buscar por nombre o correo" style="min-width:260px">
            <button class="hb-btn hb-btn--ghost hb-btn--sm" type="submit">Buscar</button>
            @if (request('q'))
                <a class="hb-btn hb-btn--link" href="{{ route('admin.usuarios.index') }}">Limpiar</a>
            @endif
        </form>
    </div>

    <div class="hb-table-wrap">
        <table class="hb-table">
            <thead>
                <tr>
                    <th>Usuario</th>
                    <th>Rol</th>
                    <th>Publicaciones</th>
                    <th>Reseñas</th>
                    <th>Registro</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($usuarios as $usuario)
                    <tr>
                        <td>
                            <div class="hb-table__user">
                                <span class="hb-table__avatar">{{ $usuario->initials() }}</span>
                                <div>
                                    <a href="{{ route('admin.usuarios.show', $usuario) }}">{{ $usuario->name }}</a>
                                    <div class="hb-muted hb-small">{{ $usuario->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="hb-badge {{ $usuario->isAdmin() ? 'hb-badge--primary' : 'hb-badge--muted' }}">
                                {{ $usuario->isAdmin() ? 'Admin' : 'Usuario' }}
                            </span>
                        </td>
                        <td>{{ $usuario->alojamientos_count }}</td>
                        <td>{{ $usuario->ratings_count }}</td>
                        <td class="hb-muted hb-small">{{ $usuario->created_at->format('d/m/Y') }}</td>
                        <td>
                            <div class="hb-table__actions">
                                <a class="hb-btn hb-btn--ghost hb-btn--sm" href="{{ route('admin.usuarios.edit', $usuario) }}">Editar</a>

                                @unless ($usuario->is(auth()->user()))
                                    <form method="POST" action="{{ route('admin.usuarios.destroy', $usuario) }}"
                                          data-confirmar="¿Eliminar a {{ $usuario->name }}? Se borrarán también sus publicaciones y reseñas.">
                                        @csrf @method('DELETE')
                                        <button class="hb-btn hb-btn--danger hb-btn--sm" type="submit">Eliminar</button>
                                    </form>
                                @endunless
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="hb-muted hb-center" style="padding:2.5rem">No se encontraron usuarios.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="hb-pagination">{{ $usuarios->links() }}</div>
@endsection
