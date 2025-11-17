@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('assets/styles.css') }}">

<div class="auth-wrap">
  <div class="auth-card">

    {{-- Brand / side --}}
    <aside class="auth-side">
      <a href="{{ url('/') }}" class="auth-brand" aria-label="Home">
        <svg viewBox="0 0 24 24" aria-hidden="true">
          <path d="M12 3 2.5 10.5 4 12l1-0.8V20a2 2 0 0 0 2 2h4v-6h2v6h4a2 2 0 0 0 2-2v-9.2l1 .8 1.5-1.5L12 3z" fill="white"/>
        </svg>
        <span>HABBI</span>
      </a>
      <h2>Únete a HABBI</h2>
      <p>Crea tu cuenta para publicar alojamientos, guardar favoritos y contactar arrendadores.</p>
    </aside>

    {{-- Main --}}
    <main class="auth-main">
      <h1 class="auth__title" style="margin:0 0 10px">{{ __('Register') }}</h1>

      @if ($errors->any())
        <div class="auth-alert">
          <ul style="margin:0;padding-left:18px">
            @foreach ($errors->all() as $e)
              <li>{{ $e }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <form class="auth-form active" method="POST" action="{{ route('register') }}" autocomplete="off">
        @csrf

        {{-- Name --}}
        <div class="auth-field">
          <label class="auth-label" for="name">{{ __('Name') }}</label>
          <div class="auth-control">
            <svg viewBox="0 0 24 24" aria-hidden="true">
              <path d="M12 2a5 5 0 1 1 0 10A5 5 0 0 1 12 2Zm0 12c-5 0-9 3-9 6v2h18v-2c0-3-4-6-9-6Z"/>
            </svg>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus placeholder="Tu nombre">
          </div>
          @error('name') <small class="text-danger d-block mt-1">{{ $message }}</small> @enderror
        </div>

        {{-- Email --}}
        <div class="auth-field">
          <label class="auth-label" for="email">{{ __('Email Address') }}</label>
          <div class="auth-control">
            <svg viewBox="0 0 24 24" aria-hidden="true">
              <path d="M20 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2Zm0 4-8 5L4 8V6l8 5 8-5Z"/>
            </svg>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required placeholder="tu@correo.com">
          </div>
          @error('email') <small class="text-danger d-block mt-1">{{ $message }}</small> @enderror
        </div>

        {{-- Password --}}
        <div class="auth-field">
          <label class="auth-label" for="password">{{ __('Password') }}</label>
          <div class="auth-control">
            <svg viewBox="0 0 24 24" aria-hidden="true">
              <path d="M12 2a5 5 0 0 0-5 5v3H6a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2h-1V7a5 5 0 0 0-5-5Zm-3 8V7a3 3 0 1 1 6 0v3H9Z"/>
            </svg>
            <input id="password" type="password" name="password" required placeholder="Mínimo 6 caracteres" autocomplete="new-password">
            <button type="button" class="auth-eye" data-target="password" aria-label="Show/Hide">👁️</button>
          </div>
          @error('password') <small class="text-danger d-block mt-1">{{ $message }}</small> @enderror
        </div>

        {{-- Confirm Password --}}
        <div class="auth-field">
          <label class="auth-label" for="password_confirmation">{{ __('Confirm Password') }}</label>
          <div class="auth-control">
            <svg viewBox="0 0 24 24" aria-hidden="true">
              <path d="M12 2a5 5 0 0 0-5 5v3H6a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2h-1V7a5 5 0 0 0-5-5Zm-3 8V7a3 3 0 1 1 6 0v3H9Z"/>
            </svg>
            <input id="password_confirmation" type="password" name="password_confirmation" required placeholder="Repite tu contraseña" autocomplete="new-password">
            <button type="button" class="auth-eye" data-target="password_confirmation" aria-label="Show/Hide">👁️</button>
          </div>
          @error('password_confirmation') <small class="text-danger d-block mt-1">{{ $message }}</small> @enderror
        </div>

        <button class="auth-btn" type="submit">{{ __('Register') }}</button>
        @if (Route::has('login'))
          <p class="auth-note">¿Ya tienes cuenta? <a href="{{ route('login') }}">Iniciar sesión</a>.</p>
        @endif
      </form>
    </main>

  </div>
</div>

<script>
document.querySelectorAll('.auth-eye').forEach(btn=>{
  btn.addEventListener('click', ()=>{
    const input = document.getElementById(btn.dataset.target);
    if (!input) return;
    input.type = input.type === 'password' ? 'text' : 'password';
  });
});
</script>
@endsection
