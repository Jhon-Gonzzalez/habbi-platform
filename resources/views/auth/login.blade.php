@extends('layouts.app')

@section('content')
{{-- Estilo del diseño HABBI (ajusta la ruta si lo tienes en otro lugar) --}}
<link rel="stylesheet" href="{{ asset('assets/styles.css') }}">


<div class="auth-wrap">
  <div class="auth-card">

    {{-- Lateral / Branding --}}
    <aside class="auth-side">
      <a href="{{ url('/') }}" class="auth-brand" aria-label="Ir a inicio">
        <svg viewBox="0 0 24 24" aria-hidden="true">
          <path d="M12 3 2.5 10.5 4 12l1-0.8V20a2 2 0 0 0 2 2h4v-6h2v6h4a2 2 0 0 0 2-2v-9.2l1 .8 1.5-1.5L12 3z" fill="white"/>
        </svg>
        <span>HABBI</span>
      </a>
      <h2>Encuentra tu alojamiento ideal</h2>
      <p>Inicia sesión para guardar favoritos, comparar opciones y contactar arrendadores.</p>
    </aside>

    {{-- Contenido principal --}}
    <main class="auth-main">
      <h1 class="auth__title" style="margin:0 0 10px">{{ __('Login') }}</h1>

      {{-- Mensajes de error de Laravel en bloque (opcional) --}}
      @if ($errors->any())
        <div class="auth-alert">
          <ul style="margin:0;padding-left:18px">
            @foreach ($errors->all() as $e)
              <li>{{ $e }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <form class="auth-form active" method="POST" action="{{ route('login') }}" autocomplete="off">
        @csrf

        {{-- Email --}}
        <div class="auth-field">
          <label class="auth-label" for="email">{{ __('Email Address') }}</label>
          <div class="auth-control">
            <svg viewBox="0 0 24 24" aria-hidden="true">
              <path d="M20 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2Zm0 4-8 5L4 8V6l8 5 8-5Z"/>
            </svg>
            <input id="email" type="email"
                   class="@error('email') is-invalid @enderror"
                   name="email" value="{{ old('email') }}" required autofocus
                   placeholder="tu@correo.com">
          </div>
          @error('email')
            <small class="text-danger d-block mt-1">{{ $message }}</small>
          @enderror
        </div>

        {{-- Password --}}
        <div class="auth-field">
          <label class="auth-label" for="password">{{ __('Password') }}</label>
          <div class="auth-control">
            <svg viewBox="0 0 24 24" aria-hidden="true">
              <path d="M12 2a5 5 0 0 0-5 5v3H6a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2h-1V7a5 5 0 0 0-5-5Zm-3 8V7a3 3 0 1 1 6 0v3H9Z"/>
            </svg>
            <input id="password" type="password"
                   class="@error('password') is-invalid @enderror"
                   name="password" required placeholder="••••••" autocomplete="current-password">
            <button type="button" class="auth-eye" data-target="password" aria-label="Mostrar u ocultar contraseña">👁️</button>
          </div>
          @error('password')
            <small class="text-danger d-block mt-1">{{ $message }}</small>
          @enderror
        </div>

        {{-- Remember + Forgot --}}
        <div class="auth-row" style="align-items:center; justify-content:space-between;">
          <label class="form-check-label" style="display:flex; gap:.5rem; align-items:center;">
            <input class="form-check-input" type="checkbox" name="remember" id="remember"
                   {{ old('remember') ? 'checked' : '' }}>
            {{ __('Remember Me') }}
          </label>

          @if (Route::has('password.request'))
            <a href="{{ route('password.request') }}">{{ __('Forgot Your Password?') }}</a>
          @endif
        </div>

        {{-- Submit --}}
        <button class="auth-btn" type="submit">{{ __('LogiIn') }}</button>
        

        {{-- Register link opcional --}}
        @if (Route::has('register'))
          <p class="auth-note">¿Aún no tienes cuenta? <a href="{{ route('register') }}">Crear cuenta</a>.</p>
        @endif
      </form>
    </main>

  </div>
</div>

{{-- Mostrar/ocultar contraseña --}}
<script>
document.querySelectorAll('.auth-eye').forEach(btn=>{
  btn.addEventListener('click', ()=>{
    const target = document.getElementById(btn.dataset.target);
    target.type = target.type === 'password' ? 'text' : 'password';
  });
});
</script>
@endsection
