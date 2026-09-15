@extends('layouts.auth')

@section('titulo', 'Nueva contraseña')

@section('formulario')
    <h1>Nueva contraseña</h1>
    <p class="hb-muted">Elige una contraseña que no uses en otro sitio.</p>

    <form method="POST" action="{{ route('password.update') }}" style="margin-top:2rem">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="hb-field">
            <label class="hb-label" for="email">Correo electrónico</label>
            <input class="hb-input @error('email') is-invalid @enderror" type="email" id="email" name="email"
                   value="{{ $email ?? old('email') }}" required autofocus>
            @error('email') <span class="hb-error">{{ $message }}</span> @enderror
        </div>

        <div class="hb-field">
            <label class="hb-label" for="password">Nueva contraseña</label>
            <input class="hb-input @error('password') is-invalid @enderror" type="password" id="password"
                   name="password" required autocomplete="new-password">
            @error('password') <span class="hb-error">{{ $message }}</span> @enderror
        </div>

        <div class="hb-field">
            <label class="hb-label" for="password-confirm">Repite la contraseña</label>
            <input class="hb-input" type="password" id="password-confirm" name="password_confirmation" required>
        </div>

        <button class="hb-btn hb-btn--primary hb-btn--block" type="submit">Guardar contraseña</button>
    </form>
@endsection
