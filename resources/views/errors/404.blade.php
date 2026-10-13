@extends('layouts.app')

@section('titulo', 'No encontramos esa página')

@section('contenido')
<div style="max-width:620px;margin-inline:auto">
    <div class="hb-card">
        <div class="hb-card__body hb-center">
            <div style="font-size:3rem;line-height:1" aria-hidden="true">🔍</div>

            <h1 style="margin-top:1rem;font-size:1.5rem">Aquí no hay nada</h1>

            <p class="hb-muted">
                Puede que el enlace esté mal escrito, o que el alojamiento que
                buscabas ya no esté publicado. Los arrendadores pueden pausar o
                retirar sus anuncios en cualquier momento.
            </p>

            <div class="hb-row hb-center" style="gap:.6rem;margin-top:1.5rem;flex-wrap:wrap">
                <a class="hb-btn hb-btn--primary" href="{{ route('alojamientos.index') }}">
                    Buscar alojamiento
                </a>
                <a class="hb-btn hb-btn--ghost" href="{{ route('index') }}">Ir al inicio</a>
            </div>
        </div>
    </div>
</div>
@endsection
