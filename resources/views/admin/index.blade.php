@extends('layouts.admin')

@section('titulo', 'Resumen')
@section('subtitulo', 'Estado general de la plataforma')

@section('contenido')

    <div class="hb-stats">
        <div class="hb-stat">
            <div class="hb-stat__icon" aria-hidden="true">◍</div>
            <div>
                <div class="hb-stat__value">{{ $totalUsuarios }}</div>
                <div class="hb-stat__label">Usuarios registrados · {{ $totalAdmins }} admin</div>
            </div>
        </div>

        <div class="hb-stat">
            <div class="hb-stat__icon" aria-hidden="true">⌂</div>
            <div>
                <div class="hb-stat__value">{{ $totalAlojamientos }}</div>
                <div class="hb-stat__label">Alojamientos · {{ $totalActivos }} activos</div>
            </div>
        </div>

        <div class="hb-stat">
            <div class="hb-stat__icon" aria-hidden="true">★</div>
            <div>
                <div class="hb-stat__value">{{ $totalResenas }}</div>
                <div class="hb-stat__label">Reseñas publicadas</div>
            </div>
        </div>

        <div class="hb-stat">
            <div class="hb-stat__icon" aria-hidden="true">◐</div>
            <div>
                <div class="hb-stat__value">{{ $promedioGeneral ?: '—' }}</div>
                <div class="hb-stat__label">Calificación promedio</div>
            </div>
        </div>
    </div>

    <div style="display:grid;gap:1.5rem;grid-template-columns:repeat(auto-fit,minmax(340px,1fr));margin-top:1.5rem">

        <section class="hb-card">
            <div class="hb-card__header">
                <h2 style="font-size:1.05rem">Últimos alojamientos</h2>
                <a class="hb-btn hb-btn--link" href="{{ route('admin.alojamientos.index') }}">Ver todos</a>
            </div>

            <div class="hb-table-wrap">
                <table class="hb-table">
                    <tbody>
                        @forelse ($ultimos as $a)
                            <tr>
                                <td style="width:60px">
                                    <img class="hb-table__thumb" src="{{ $a->coverUrl() }}" alt="">
                                </td>
                                <td>
                                    <a href="{{ route('alojamientos.show', $a) }}">{{ Str::limit($a->title, 40) }}</a>
                                    <div class="hb-muted hb-small">{{ $a->user->name }} · {{ $a->city }}</div>
                                </td>
                                <td style="text-align:right">
                                    <span class="hb-badge {{ $a->is_active ? 'hb-badge--success' : 'hb-badge--muted' }}">
                                        {{ $a->is_active ? 'Activo' : 'Pausado' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td class="hb-muted">Todavía no hay alojamientos.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="hb-card">
            <div class="hb-card__header">
                <h2 style="font-size:1.05rem">Últimos usuarios</h2>
                <a class="hb-btn hb-btn--link" href="{{ route('admin.usuarios.index') }}">Ver todos</a>
            </div>

            <div class="hb-table-wrap">
                <table class="hb-table">
                    <tbody>
                        @forelse ($ultimosUsuarios as $u)
                            <tr>
                                <td>
                                    <div class="hb-table__user">
                                        <span class="hb-table__avatar">{{ $u->initials() }}</span>
                                        <div>
                                            <a href="{{ route('admin.usuarios.show', $u) }}">{{ $u->name }}</a>
                                            <div class="hb-muted hb-small">{{ $u->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td style="text-align:right">
                                    <span class="hb-badge {{ $u->isAdmin() ? 'hb-badge--primary' : 'hb-badge--muted' }}">
                                        {{ $u->isAdmin() ? 'Admin' : 'Usuario' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td class="hb-muted">Todavía no hay usuarios.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

    </div>
@endsection
