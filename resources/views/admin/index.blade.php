@extends('layouts.admin')

@section('titulo', 'Resumen')
@section('subtitulo', 'Estado general de la plataforma')

@section('contenido')

    {{-- Solo en producción: en local tener la verificación apagada es lo
         normal y el aviso sería ruido en cada carga. --}}
    @if (app()->environment('production') && ! config('habbi.verificar_correo'))
        <div class="hb-alert hb-alert--error" style="margin-bottom:1.5rem">
            <span class="hb-alert__icon" aria-hidden="true">!</span>
            <span>
                <strong>La verificación de correo está apagada.</strong>
                Cualquiera puede registrarse con un correo inventado, las veces que
                quiera, y usarlo para reseñar o reportar. Para encenderla, pon
                <code>HABBI_VERIFICAR_CORREO=true</code> en el <code>.env</code> y
                comprueba el envío con <code>php artisan habbi:correo</code>.
            </span>
        </div>
    @endif

    {{-- Salud de los respaldos (RNF-3). Solo en producción: en local
         nadie tiene el cron puesto y el aviso sería ruido. --}}
    @if (app()->environment('production') && (! $ultimoRespaldo || $ultimoRespaldo->lt(now()->subHours(48))))
        <div class="hb-alert hb-alert--error" style="margin-bottom:1.5rem">
            <span class="hb-alert__icon" aria-hidden="true">!</span>
            <span>
                @if (! $ultimoRespaldo)
                    <strong>No hay ninguna copia de seguridad.</strong>
                @else
                    <strong>La última copia de seguridad es del
                    {{ $ultimoRespaldo->translatedFormat('d \d\e F \a \l\a\s H:i') }}</strong>
                    ({{ $ultimoRespaldo->diffForHumans() }}).
                @endif
                La tarea está programada en el código, pero solo corre si el servidor
                tiene <code>php artisan schedule:run</code> en el cron. Compruébalo con
                <code>php artisan habbi:respaldar --listar</code>; los pasos están en
                <code>docs/respaldos.md</code>.
            </span>
        </div>
    @elseif ($ultimoRespaldo)
        <p class="hb-muted hb-small" style="margin-bottom:1.5rem">
            Última copia de seguridad: {{ $ultimoRespaldo->diffForHumans() }}.
        </p>
    @endif

    {{--
        Trabajo pendiente, no cifras de lucimiento.

        Las métricas de abajo (cuántos usuarios, cuántas publicaciones)
        están bien para contar cómo va la plataforma, pero no le dicen a
        nadie qué hacer al entrar. Esto sí: cada línea es un problema
        concreto con un enlace para ir a arreglarlo.
    --}}
    @if (!empty($pendientes))
        <div class="hb-card" style="margin-bottom:1.5rem">
            <div class="hb-card__header">
                <h2 style="font-size:1.05rem">Qué conviene mirar</h2>
            </div>
            <ul class="hb-pendientes">
                @foreach ($pendientes as $aviso)
                    <li>
                        <span class="hb-badge {{ $aviso['grave'] ? 'hb-badge--warning' : 'hb-badge--muted' }}">
                            {{ $aviso['numero'] }}
                        </span>
                        <span>{{ $aviso['texto'] }}</span>
                        <a class="hb-btn hb-btn--ghost hb-btn--sm" href="{{ $aviso['url'] }}">Ver</a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($cosechadores->isNotEmpty())
        <div class="hb-alert hb-alert--error" style="margin-bottom:1.5rem">
            <span class="hb-alert__icon" aria-hidden="true">!</span>
            <span>
                <strong>Alguien está pidiendo muchos teléfonos.</strong>
                En las últimas 24 horas:
                <ul style="margin:.5rem 0 0;padding-left:1.1rem">
                    @foreach ($cosechadores as $c)
                        <li>
                            {{ $c->user?->name ?? 'cuenta eliminada' }}
                            ({{ \App\Support\Texto::ocultarCorreo($c->user?->email ?? '—') }})
                            · <strong>{{ $c->total }}</strong> teléfonos
                            @if ($c->user)
                                · cuenta creada {{ $c->user->created_at->diffForHumans() }}
                                · <a href="{{ route('admin.usuarios.show', $c->user) }}">ver</a>
                            @endif
                        </li>
                    @endforeach
                </ul>
                Una persona buscando cuarto mira diez o quince. Si la cuenta es
                nueva y pidió decenas, probablemente esté recogiendo contactos.
            </span>
        </div>
    @endif

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
                <a class="hb-btn hb-btn--link" href="{{ route('admin.publicaciones.index') }}">Ver todos</a>
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
                                    <span class="hb-badge {{ $u->isAdmin() ? 'hb-badge--primary' : ($u->esArrendador() ? 'hb-badge--success' : 'hb-badge--muted') }}">
                                        {{ $u->nombreRol() }}
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
