@extends('layouts.auth')

@section('titulo', 'Verifica tu correo')

@section('formulario')
    <h1>Verifica tu correo</h1>
    <p class="hb-muted">
        Te enviamos un enlace a <strong>{{ auth()->user()->email }}</strong>.
        Ábrelo para confirmar que el correo es tuyo.
    </p>

    @if (session('resent'))
        <div class="hb-alert hb-alert--success" style="margin-top:1.5rem">
            <span class="hb-alert__icon" aria-hidden="true">✓</span>
            <span>Te acabamos de enviar otro enlace. Revisa también la carpeta de spam.</span>
        </div>
    @endif

    <div class="hb-alert hb-alert--info" style="margin-top:1.5rem">
        <span class="hb-alert__icon" aria-hidden="true">i</span>
        <span>
            Mientras tanto puedes buscar alojamientos y guardar favoritos con normalidad.
            El correo verificado solo hace falta para dejar reseñas, reportar publicaciones
            y pedir ser arrendador.
        </span>
    </div>

    <form method="POST" action="{{ route('verification.resend') }}" style="margin-top:1.5rem">
        @csrf
        <button class="hb-btn hb-btn--primary hb-btn--block" type="submit">
            Enviarme el enlace otra vez
        </button>
    </form>

    <p class="hb-center" style="margin-top:1.5rem">
        <a href="{{ route('alojamientos.index') }}">Seguir buscando alojamiento</a>
    </p>
@endsection
