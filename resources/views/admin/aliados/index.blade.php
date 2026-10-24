@extends('layouts.admin')

@section('titulo', 'Aliados')
@section('subtitulo', 'Negocios locales que pagan por aparecer')

@section('acciones')
    <a class="hb-btn hb-btn--primary" href="{{ route('admin.aliados.create') }}">Nuevo aliado</a>
@endsection

@section('contenido')

<div class="hb-stats" style="margin-bottom:1.5rem">
    <div class="hb-stat">
        <div class="hb-stat__label">Cobrado este mes</div>
        <div class="hb-stat__value">${{ number_format($delMes, 0, ',', '.') }}</div>
    </div>
    <div class="hb-stat">
        <div class="hb-stat__label">Publicados ahora</div>
        <div class="hb-stat__value">{{ $vigentes->count() }}</div>
    </div>
    <div class="hb-stat">
        <div class="hb-stat__label">Clics acumulados</div>
        <div class="hb-stat__value">{{ number_format($clicsTotal, 0, ',', '.') }}</div>
    </div>
</div>

<div class="hb-card">
    <div class="hb-card__header">
        <h2 style="font-size:1.05rem">Todos los aliados</h2>
    </div>

    @if ($aliados->isEmpty())
        <div class="hb-card__body">
            <p class="hb-muted">Todavía no hay ningún aliado.</p>
            <p class="hb-hint hb-mb-0">
                Los candidatos naturales son los negocios de los que vive un estudiante
                recién llegado: almuerzos, papelería, lavandería, internet, colchones y
                trasteos. Se les vende un cupo fijo al mes; en temporada vale más.
            </p>
        </div>
    @else
        <div class="hb-table-wrap">
                <table class="hb-table">
            <thead>
                <tr>
                    <th>Negocio</th>
                    <th>Vigencia</th>
                    <th>Pagó</th>
                    <th>Clics</th>
                    <th>Estado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($aliados as $aliado)
                    <tr>
                        <td>
                            <strong>{{ $aliado->nombre }}</strong>
                            <div class="hb-muted hb-small">{{ $aliado->categoria ?? 'Sin categoría' }}</div>
                        </td>
                        <td class="hb-small">
                            {{ $aliado->desde->format('d/m/Y') }} → {{ $aliado->hasta->format('d/m/Y') }}
                            @if ($aliado->estaVigente())
                                <div class="hb-muted">le quedan {{ $aliado->diasRestantes() }} día(s)</div>
                            @endif
                        </td>
                        <td>{{ $aliado->montoFormateado() }}</td>
                        <td>
                            <strong>{{ number_format($aliado->clics, 0, ',', '.') }}</strong>
                        </td>
                        <td>
                            @if ($aliado->estaVigente())
                                <span class="hb-badge hb-badge--success">Publicado</span>
                            @elseif (! $aliado->activo)
                                <span class="hb-badge hb-badge--muted">Apagado</span>
                            @else
                                <span class="hb-badge hb-badge--warning">Fuera de fecha</span>
                            @endif
                        </td>
                        <td style="text-align:right">
                            <div class="hb-row" style="justify-content:flex-end">
                                <a class="hb-btn hb-btn--ghost hb-btn--sm"
                                   href="{{ route('admin.aliados.edit', $aliado) }}">Editar</a>
                                <form method="POST" action="{{ route('admin.aliados.destroy', $aliado) }}"
                                      data-confirmar="¿Eliminar a «{{ $aliado->nombre }}»? Se pierde también su cuenta de clics.">
                                    @csrf @method('DELETE')
                                    <button class="hb-btn hb-btn--danger hb-btn--sm" type="submit">Eliminar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
                </div>

        <div class="hb-card__footer">
            <p class="hb-hint hb-mb-0">
                <strong>Los clics son tu argumento para renovar.</strong> «Te mandamos
                37 personas este mes» se puede discutir; «confía en mí» no. Se cuenta
                el clic, no quién lo hizo: ni usuario, ni IP, ni cookie.
            </p>
        </div>
    @endif
</div>
@endsection
