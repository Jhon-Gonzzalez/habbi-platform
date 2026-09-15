@extends('layouts.app')

@section('titulo', 'Mis publicaciones')

@section('cabecera')
    <div>
        <h1>Mis publicaciones</h1>
        <p>{{ $alojamientos->total() }} @plural('alojamiento', $alojamientos->total()) publicados</p>
    </div>
    <a class="hb-btn hb-btn--white" href="{{ route('alojamientos.create') }}">Publicar otro</a>
@endsection

@section('contenido')
    @if ($alojamientos->isEmpty())
        <div class="hb-empty">
            <div class="hb-empty__icon" aria-hidden="true">⌂</div>
            <h3>Todavía no has publicado nada</h3>
            <p>Publicar es gratis y toma un par de minutos. Sube tus fotos, pon el precio y empieza a recibir contactos.</p>
            <p style="margin-top:1.5rem">
                <a class="hb-btn hb-btn--primary" href="{{ route('alojamientos.create') }}">Publicar mi primer alojamiento</a>
            </p>
        </div>
    @else
        <div class="hb-grid">
            @foreach ($alojamientos as $a)
                @include('partials.listing-card', ['a' => $a, 'gestion' => true])
            @endforeach
        </div>

        <div class="hb-pagination">{{ $alojamientos->links() }}</div>
    @endif
@endsection
