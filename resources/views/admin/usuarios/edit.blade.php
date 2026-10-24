@extends('layouts.admin')

@section('titulo', 'Editar usuario')
@section('subtitulo', $usuario->email)

@section('contenido')

@if (session('enlace_reset'))
    <div class="hb-alert hb-alert--info" style="max-width:760px">
        <span class="hb-alert__icon">🔑</span>
        <div style="min-width:0">
            <strong>Enlace de restablecimiento generado</strong>
            <p class="hb-small" style="margin:.35rem 0">
                Pásaselo solo a esta persona. Caduca en 60 minutos y únicamente
                sirve para su cuenta; tú no puedes ver ni fijar su contraseña.
            </p>
            <input class="hb-input hb-small" type="text" readonly onclick="this.select()"
                   aria-label="Enlace de restablecimiento de contraseña"
                   value="{{ session('enlace_reset') }}">
        </div>
    </div>
@endif
<form method="POST" action="{{ route('admin.usuarios.update', $usuario) }}" style="max-width:760px">
    @csrf
    @method('PUT')

    <div class="hb-card">
        <div class="hb-card__body">
            @include('admin.usuarios._form', ['usuario' => $usuario])
        </div>
        <div class="hb-card__footer hb-row hb-row--between">
            <a class="hb-btn hb-btn--ghost" href="{{ route('admin.usuarios.index') }}">Cancelar</a>
            <button class="hb-btn hb-btn--primary" type="submit">Guardar cambios</button>
        </div>
    </div>
</form>

<form method="POST" action="{{ route('admin.usuarios.enlace-reset', $usuario) }}"
      style="max-width:760px;margin-top:1.5rem"
      data-confirmar="Se generará un enlace para que {{ $usuario->name }} cambie su propia contraseña. ¿Continuar?">
    @csrf
    <button class="hb-btn hb-btn--ghost" type="submit">
        Generar enlace de restablecimiento
    </button>
    <span class="hb-hint" style="display:block;margin-top:.5rem">
        Úsalo si esta persona perdió el acceso. No te muestra su contraseña.
    </span>
</form>

@unless ($usuario->hasVerifiedEmail())
    <form method="POST" action="{{ route('admin.usuarios.verificar-correo', $usuario) }}"
          style="max-width:760px;margin-top:1.5rem"
          data-confirmar="Solo haz esto si comprobaste por otro medio que {{ $usuario->email }} es de esta persona. ¿Continuar?">
        @csrf
        <button class="hb-btn hb-btn--ghost" type="submit">
            Marcar el correo como verificado
        </button>
        <span class="hb-hint" style="display:block;margin-top:.5rem">
            Para cuando el correo de verificación no llega. No le da ningún
            permiso nuevo: solo le deja reseñar, reportar y pedir ser arrendador.
        </span>
    </form>
@endunless
@endsection
