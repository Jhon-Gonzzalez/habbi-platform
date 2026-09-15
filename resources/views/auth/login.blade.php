@extends('layouts.auth')

@section('titulo', 'Iniciar sesión')

@section('formulario')
    <h1>Iniciar sesión</h1>
    <p class="hb-muted">Entra para publicar, calificar y contactar arrendadores.</p>

    <form method="POST" action="{{ route('login') }}" style="margin-top:2rem">
        @csrf

        <div class="hb-field">
            <label class="hb-label" for="email">Correo electrónico</label>
            <input class="hb-input @error('email') is-invalid @enderror" type="email" id="email" name="email"
                   value="{{ old('email') }}" required autofocus autocomplete="email" placeholder="tucorreo@ejemplo.com">
            @error('email') <span class="hb-error">{{ $message }}</span> @enderror
        </div>

        <div class="hb-field">
            <label class="hb-label" for="password">Contraseña</label>
            <input class="hb-input @error('password') is-invalid @enderror" type="password" id="password"
                   name="password" required autocomplete="current-password" placeholder="••••••••">
            @error('password') <span class="hb-error">{{ $message }}</span> @enderror
        </div>

        <div class="hb-row hb-row--between" style="margin-bottom:1.5rem">
            <label class="hb-check" style="border:0;padding:0;background:none">
                <input type="checkbox" name="remember" @checked(old('remember'))>
                <span>Recordarme</span>
            </label>

            @if (Route::has('password.request'))
                <a class="hb-small" href="{{ route('password.request') }}">¿Olvidaste tu contraseña?</a>
            @endif
        </div>

        <button class="hb-btn hb-btn--primary hb-btn--block" type="submit">Entrar</button>
    </form>

    <p class="hb-center hb-small" style="margin-top:1.75rem">
        ¿No tienes cuenta? <a href="{{ route('register') }}">Créala gratis</a>
    </p>
@endsection
