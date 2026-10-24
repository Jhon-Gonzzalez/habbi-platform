@extends('layouts.app')

@section('titulo', 'Buscar alojamiento')
@section('descripcion', 'Explora alojamientos estudiantiles verificados y filtra por precio, tipo, barrio y comodidades.')

@section('cabecera')
    <div>
        <h1>Buscar alojamiento</h1>
        <p>{{ $alojamientos->total() }} @plural('resultado', $alojamientos->total()) disponibles</p>
    </div>
    <a class="hb-btn hb-btn--white" href="{{ route('alojamientos.create') }}">Publicar el mío</a>
@endsection

@section('contenido')
<div class="hb-layout">

    {{-- ===== Filtros ===== --}}
    <aside class="hb-filters">
        {{-- La publicidad va en la barra lateral, NUNCA en la rejilla de
             resultados: un anuncio con forma de tarjeta de alojamiento es
             un engaño y, en una página de vivienda, un vector de estafa.

             Arriba y no al fondo porque al fondo no se veía: la barra es
             más alta que la pantalla y, al estar fija, su parte inferior
             no se alcanza por mucho que se baje. Un anuncio invisible no
             se le puede cobrar a nadie. --}}
        @include('partials.aliados')

        @php($filtrosPuestos = count(array_filter($filtros, fn ($v) => $v !== null && $v !== '' && $v !== [])))

        {{--
            <details> y no un panel con JavaScript.

            En el teléfono, el panel de filtros abierto mide casi 1.400 px:
            había que bajar casi tres pantallas para ver el primer
            alojamiento, en el aparato desde el que entra la mayoría. Aquí
            nace plegado en móvil y abierto en escritorio.

            Viene con «open» puesto desde el servidor y es el JavaScript el
            que lo pliega en pantallas pequeñas. Al revés —cerrado y que el
            JS lo abra— quien tenga el JavaScript bloqueado se quedaría sin
            poder filtrar. Así, si algo falla, el peor caso es lo que había
            hasta ahora.
        --}}
        <details class="hb-card hb-filtros" id="filtros" open
                 @if ($filtrosPuestos) data-con-filtros @endif>
            <summary class="hb-card__header hb-filtros__resumen">
                <span class="hb-filtros__titulo">
                    <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true">
                        <path fill="currentColor" d="M3 5h18l-7 8v6l-4 2v-8z"/>
                    </svg>
                    Filtros
                    @if ($filtrosPuestos)
                        <span class="hb-badge hb-badge--primary">{{ $filtrosPuestos }}</span>
                    @endif
                </span>
                <span class="hb-filtros__flecha" aria-hidden="true">▾</span>
            </summary>

            @if ($filtrosPuestos)
                <p class="hb-filtros__limpiar">
                    <a class="hb-btn hb-btn--link" href="{{ route('alojamientos.index') }}">Quitar los filtros</a>
                </p>
            @endif

            <form class="hb-filters__body" method="GET" action="{{ route('alojamientos.index') }}">

                <div class="hb-field">
                    <label class="hb-label" for="q">Buscar</label>
                    <input class="hb-input" type="search" id="q" name="q"
                           value="{{ $filtros['q'] ?? '' }}" placeholder="Ciudad, barrio o título">
                </div>

                <div class="hb-fields-2">
                    <div class="hb-field">
                        <label class="hb-label" for="price_min">Precio mín.</label>
                        <input class="hb-input" type="number" id="price_min" name="price_min" min="0" step="10000"
                               value="{{ $filtros['price_min'] ?? '' }}" placeholder="0">
                    </div>
                    <div class="hb-field">
                        <label class="hb-label" for="price_max">Precio máx.</label>
                        <input class="hb-input" type="number" id="price_max" name="price_max" min="0" step="10000"
                               value="{{ $filtros['price_max'] ?? '' }}" placeholder="Sin límite">
                    </div>
                </div>

                <div class="hb-field">
                    <label class="hb-label" for="type">Tipo</label>
                    <select class="hb-select" id="type" name="type">
                        <option value="">Cualquiera</option>
                        @foreach (\App\Models\Alojamiento::TIPOS as $tipo)
                            <option value="{{ $tipo }}" @selected(($filtros['type'] ?? '') === $tipo)>{{ $tipo }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="hb-field">
                    <label class="hb-label" for="universidad">Cerca de</label>
                    <select class="hb-input" id="universidad" name="universidad">
                        <option value="">Cualquier zona</option>
                        @foreach (\App\Models\Alojamiento::universidadesConCoordenadas() as $u)
                            <option value="{{ $u['nombre'] }}"
                                @selected(($filtros['universidad'] ?? '') === $u['nombre'])>
                                {{ $u['corto'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="hb-field">
                    <label class="hb-label" for="km">A no más de</label>
                    <select class="hb-input" id="km" name="km">
                        @foreach (['' => 'Sin límite', '1' => '1 km', '2' => '2 km', '3' => '3 km', '5' => '5 km', '10' => '10 km'] as $v => $t)
                            <option value="{{ $v }}" @selected((string) ($filtros['km'] ?? '') === $v)>{{ $t }}</option>
                        @endforeach
                    </select>
                    <span class="hb-hint">Distancia en línea recta, no caminando.</span>
                </div>

                <div class="hb-field">
                    <label class="hb-label" for="guests">Huéspedes mínimos</label>
                    <select class="hb-select" id="guests" name="guests">
                        <option value="">Cualquiera</option>
                        @for ($i = 1; $i <= 8; $i++)
                            <option value="{{ $i }}" @selected((string) ($filtros['guests'] ?? '') === (string) $i)>
                                {{ $i }}{{ $i === 8 ? '+' : '' }} @plural('huésped', $i)
                            </option>
                        @endfor
                    </select>
                </div>

                <fieldset class="hb-field" style="border:0;padding:0;margin-inline:0">
                    <legend class="hb-label">Comodidades</legend>
                    <div class="hb-checks" style="grid-template-columns:1fr">
                        @foreach (\App\Models\Alojamiento::COMODIDADES as $comodidad)
                            <label class="hb-check">
                                <input type="checkbox" name="amenities[]" value="{{ $comodidad }}"
                                       @checked(in_array($comodidad, (array) ($filtros['amenities'] ?? []), true))>
                                <span>{{ $comodidad }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <input type="hidden" name="sort" value="{{ $filtros['sort'] ?? '' }}">
                <button class="hb-btn hb-btn--primary hb-btn--block" type="submit">Aplicar filtros</button>
            </form>
        </details>

    </aside>

    {{-- ===== Resultados ===== --}}
    <section>
        <div class="hb-results__bar">
            <p class="hb-mb-0 hb-muted hb-small">
                Mostrando {{ $alojamientos->firstItem() ?? 0 }}–{{ $alojamientos->lastItem() ?? 0 }}
                de {{ $alojamientos->total() }}
            </p>

            <form method="GET" action="{{ route('alojamientos.index') }}">
                @foreach (Arr::except($filtros, 'sort') as $clave => $valor)
                    @if (is_array($valor))
                        @foreach ($valor as $v)
                            <input type="hidden" name="{{ $clave }}[]" value="{{ $v }}">
                        @endforeach
                    @elseif (!is_null($valor) && $valor !== '')
                        <input type="hidden" name="{{ $clave }}" value="{{ $valor }}">
                    @endif
                @endforeach

                <label class="hb-sr-only" for="sort">Ordenar por</label>
                <select class="hb-select" id="sort" name="sort" data-autosubmit>
                    <option value="">Más recientes</option>
                    <option value="price_asc"  @selected(($filtros['sort'] ?? '') === 'price_asc')>Precio: menor a mayor</option>
                    <option value="price_desc" @selected(($filtros['sort'] ?? '') === 'price_desc')>Precio: mayor a menor</option>
                    <option value="rating"     @selected(($filtros['sort'] ?? '') === 'rating')>Mejor calificados</option>
                    <option value="oldest"     @selected(($filtros['sort'] ?? '') === 'oldest')>Más antiguos</option>
                </select>
            </form>
        </div>

        @if ($alojamientos->isEmpty())
            <div class="hb-empty">
                <div class="hb-empty__icon" aria-hidden="true">⌕</div>
                <h3>No encontramos alojamientos con esos filtros</h3>
                <p>Prueba a ampliar el rango de precio, quitar alguna comodidad o buscar en otra ciudad.</p>
                <p style="margin-top:1.25rem">
                    <a class="hb-btn hb-btn--ghost" href="{{ route('alojamientos.index') }}">Ver todos</a>
                </p>
            </div>
        @else
            {{-- Los mismos resultados en mapa (CU-04). Si ninguno tiene
                 coordenadas, el partial no pinta nada. --}}
            @include('partials.mapa', ['puntos' => $puntos, 'id' => 'mapa-resultados'])

            <div class="hb-grid">
                @foreach ($alojamientos as $a)
                    @include('partials.listing-card', ['a' => $a])
                @endforeach
            </div>

            <div class="hb-pagination">{{ $alojamientos->links() }}</div>
        @endif
    </section>

</div>
@endsection
