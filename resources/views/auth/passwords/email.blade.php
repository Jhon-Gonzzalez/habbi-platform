@extends('layouts.auth')

@section('titulo', 'Recuperar contraseña')

@section('formulario')
    <h1>Recuperar contraseña</h1>
    <p class="hb-muted">Te enviaremos un enlace para crear una nueva.</p>

    @if (session('status'))
        <div class="hb-alert hb-alert--success" role="status">
            <span class="hb-alert__icon">✓</span>
            <div>{{ session('status') }}</div>
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" style="margin-top:2rem">
        @csrf

        <div class="hb-field">
            <label class="hb-label" for="email">Correo electrónico</label>
            <input class="hb-input @error('email') is-invalid @enderror" type="email" id="email" name="email"
                   value="{{ old('email') }}" required autofocus placeholder="tucorreo@ejemplo.com">
            @error('email') <span class="hb-error">{{ $message }}</span> @enderror
        </div>

        <button class="hb-btn hb-btn--primary hb-btn--block" type="submit">Enviar enlace</button>
    </form>

    <p class="hb-center hb-small" style="margin-top:1.75rem">
        <a href="{{ route('login') }}">Volver a iniciar sesión</a>
    </p>
@endsection
