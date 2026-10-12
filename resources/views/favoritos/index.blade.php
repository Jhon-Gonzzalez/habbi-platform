@extends('layouts.app')

@section('titulo', 'Mis favoritos')

@section('cabecera')
    <div>
        <h1>Mis favoritos</h1>
        <p>Los alojamientos que guardaste para pensarlo con calma.</p>
    </div>
@endsection

@section('contenido')

    @if ($favoritos->isEmpty())
        <div class="hb-empty">
            <div class="hb-empty__icon" aria-hidden="true">♡</div>
            <h3>Todavía no has guardado nada</h3>
            <p>Cuando veas un alojamiento que te interese, toca el corazón de la tarjeta y lo encontrarás aquí.</p>
            <p style="margin-top:1.25rem">
                <a class="hb-btn hb-btn--primary" href="{{ route('alojamientos.index') }}">Buscar alojamiento</a>
            </p>
        </div>
    @else
        <div class="hb-row hb-row--between" style="margin-bottom:1.25rem">
            <p class="hb-muted hb-mb-0">
                {{ $favoritos->total() }} @plural('alojamiento', $favoritos->total())
                @plural('guardado', $favoritos->total()).
            </p>
            <a class="hb-btn hb-btn--ghost hb-btn--sm" href="{{ route('alojamientos.index') }}">Seguir buscando</a>
        </div>

        <div class="hb-grid">
            @foreach ($favoritos as $a)
                @include('partials.listing-card', ['a' => $a])
            @endforeach
        </div>

        @if ($favoritos->hasPages())
            <div class="hb-pagination">{{ $favoritos->links() }}</div>
        @endif
    @endif

@endsection
