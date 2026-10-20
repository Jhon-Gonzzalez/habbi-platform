@extends('layouts.app')

@section('titulo', 'Contacto · ' . $alojamiento->title)

@section('contenido')
<div style="max-width:560px;margin-inline:auto">

    <div class="hb-card">
        <div class="hb-card__header">
            <h1 style="font-size:1.25rem">Contactar al arrendador</h1>
        </div>
        <div class="hb-card__body">
            <p class="hb-muted">
                Sobre <strong>«{{ $alojamiento->title }}»</strong>,
                de {{ $alojamiento->user->name }}.
            </p>

            <p style="font-size:1.6rem;font-weight:600;margin:1.25rem 0">
                {{ $alojamiento->phone }}
            </p>

            <form method="POST" action="{{ route('mensajes.abrir', $alojamiento) }}">
                @csrf
                <button class="hb-btn hb-btn--primary hb-btn--block" type="submit">
                    Mejor escríbele por el chat
                </button>
            </form>

            <a class="hb-btn hb-btn--ghost hb-btn--block" style="margin-top:.6rem"
               href="tel:{{ $alojamiento->telefonoLimpio() }}">Llamar</a>

            <p class="hb-hint" style="margin-top:.75rem">
                Por el chat queda constancia de lo que se acuerda y la publicación
                se actualiza sola al cerrar el trato. Por teléfono, no.
            </p>

            <p class="hb-muted hb-small" style="margin-top:1.25rem">
                Escribe desde tu propio número y acuerda ver el lugar en persona
                antes de pagar nada. <strong>Nunca envíes dinero por adelantado</strong>
                a alguien que no conoces, ni siquiera como «reserva».
            </p>
        </div>
        <div class="hb-card__footer">
            <a class="hb-btn hb-btn--ghost hb-btn--sm" href="{{ route('alojamientos.show', $alojamiento) }}">
                ← Volver a la publicación
            </a>
        </div>
    </div>

</div>
@endsection
