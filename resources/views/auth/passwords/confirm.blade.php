@extends('layouts.auth')

@section('titulo', 'Confirmar contraseña')

@section('formulario')
    <h1>Confirma tu contraseña</h1>
    <p class="hb-muted">Por seguridad, vuelve a introducirla para continuar.</p>

    <form method="POST" action="{{ route('password.confirm') }}" style="margin-top:2rem">
        @csrf

        <div class="hb-field">
            <label class="hb-label" for="password">Contraseña</label>
            <input class="hb-input @error('password') is-invalid @enderror" type="password" id="password"
                   name="password" required autofocus autocomplete="current-password">
            @error('password') <span class="hb-error">{{ $message }}</span> @enderror
        </div>

        <button class="hb-btn hb-btn--primary hb-btn--block" type="submit">Confirmar</button>
    </form>
@endsection
