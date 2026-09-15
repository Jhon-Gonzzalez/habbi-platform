@extends('layouts.auth')

@section('titulo', 'Crear cuenta')

@section('formulario')
    <h1>Crear cuenta</h1>
    <p class="hb-muted">Gratis, en menos de un minuto.</p>

    <form method="POST" action="{{ route('register') }}" style="margin-top:2rem">
        @csrf

        <div class="hb-field">
            <label class="hb-label" for="name">Nombre completo</label>
            <input class="hb-input @error('name') is-invalid @enderror" type="text" id="name" name="name"
                   value="{{ old('name') }}" required autofocus autocomplete="name" placeholder="Ana Martínez">
            @error('name') <span class="hb-error">{{ $message }}</span> @enderror
        </div>

        <div class="hb-field">
            <label class="hb-label" for="email">Correo electrónico</label>
            <input class="hb-input @error('email') is-invalid @enderror" type="email" id="email" name="email"
                   value="{{ old('email') }}" required autocomplete="email" placeholder="tucorreo@ejemplo.com">
            @error('email') <span class="hb-error">{{ $message }}</span> @enderror
        </div>

        <div class="hb-field">
            <label class="hb-label" for="phone">Teléfono <span class="hb-muted">(opcional)</span></label>
            <input class="hb-input @error('phone') is-invalid @enderror" type="tel" id="phone" name="phone"
                   value="{{ old('phone') }}" autocomplete="tel" placeholder="+57 300 000 0000">
            <span class="hb-hint">Lo usaremos para autocompletar tus publicaciones.</span>
            @error('phone') <span class="hb-error">{{ $message }}</span> @enderror
        </div>

        <div class="hb-field">
            <label class="hb-label" for="password">Contraseña</label>
            <input class="hb-input @error('password') is-invalid @enderror" type="password" id="password"
                   name="password" required autocomplete="new-password" placeholder="••••••••">
            <span class="hb-hint">Mínimo 8 caracteres, con al menos una letra y un número.</span>
            @error('password') <span class="hb-error">{{ $message }}</span> @enderror
        </div>

        <div class="hb-field">
            <label class="hb-label" for="password-confirm">Repite la contraseña</label>
            <input class="hb-input" type="password" id="password-confirm" name="password_confirmation"
                   required autocomplete="new-password" placeholder="••••••••">
        </div>

        <button class="hb-btn hb-btn--primary hb-btn--block" type="submit">Crear mi cuenta</button>
    </form>

    <p class="hb-center hb-small" style="margin-top:1.75rem">
        ¿Ya tienes cuenta? <a href="{{ route('login') }}">Inicia sesión</a>
    </p>
@endsection
