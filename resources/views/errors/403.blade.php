@extends('layouts.app')

@section('titulo', 'No tienes acceso')

@section('contenido')
<div style="max-width:620px;margin-inline:auto">
    <div class="hb-card">
        <div class="hb-card__body hb-center">
            <div style="font-size:3rem;line-height:1" aria-hidden="true">🔒</div>

            <h1 style="margin-top:1rem;font-size:1.5rem">Esta parte no es para ti</h1>

            <p class="hb-muted">
                @auth
                    Tu cuenta no tiene permiso para entrar aquí. Si crees que
                    debería tenerlo, escríbenos.
                @else
                    Puede que solo te falte iniciar sesión.
                @endauth
            </p>

            <div class="hb-row hb-center" style="gap:.6rem;margin-top:1.5rem;flex-wrap:wrap">
                @guest
                    <a class="hb-btn hb-btn--primary" href="{{ route('login') }}">Iniciar sesión</a>
                @endguest
                <a class="hb-btn hb-btn--ghost" href="{{ route('alojamientos.index') }}">Volver a buscar</a>
            </div>
        </div>
    </div>
</div>
@endsection
