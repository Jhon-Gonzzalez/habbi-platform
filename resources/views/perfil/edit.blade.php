@extends('layouts.app')

@section('titulo', 'Mi perfil')

@section('cabecera')
    <div>
        <h1>Mi perfil</h1>
        <p>Así te ven los demás en HABBI.</p>
    </div>
    @if ($usuario->esArrendador() || $usuario->isAdmin())
        <a class="hb-btn hb-btn--white" href="{{ route('arrendadores.perfil', $usuario) }}">Ver mi perfil público</a>
    @endif
@endsection

@section('contenido')
<form method="POST" action="{{ route('perfil.update') }}" enctype="multipart/form-data"
      style="max-width:760px;margin-inline:auto">
    @csrf
    @method('PUT')

    {{-- Foto --}}
    <div class="hb-card">
        <div class="hb-card__header"><h2 style="font-size:1.1rem">Foto de perfil</h2></div>
        <div class="hb-card__body">
            <div class="hb-row" style="gap:1.5rem">
                <x-avatar :usuario="$usuario" :tamano="96" />

                <div style="flex:1;min-width:200px">
                    <div class="hb-field hb-mb-0">
                        <label class="hb-label" for="foto">Cambiar foto</label>
                        <input class="hb-input @error('foto') is-invalid @enderror" type="file" id="foto"
                               name="foto" accept="image/jpeg,image/png,image/webp">
                        <span class="hb-hint">JPG, PNG o WEBP · máximo 3 MB. Una foto tuya da mucha más confianza.</span>
                        @error('foto') <span class="hb-error">{{ $message }}</span> @enderror
                    </div>

                    @if ($usuario->fotoUrl())
                        <label class="hb-check" style="margin-top:.75rem;border:0;padding:0;background:none">
                            <input type="checkbox" name="quitar_foto" value="1">
                            <span>Quitar la foto actual</span>
                        </label>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Datos --}}
    <div class="hb-card" style="margin-top:1.5rem">
        <div class="hb-card__header"><h2 style="font-size:1.1rem">Tus datos</h2></div>
        <div class="hb-card__body">

            <div class="hb-fields-2">
                <div class="hb-field">
                    <label class="hb-label" for="name">Nombre <span class="hb-req">*</span></label>
                    <input class="hb-input @error('name') is-invalid @enderror" type="text" id="name"
                           name="name" value="{{ old('name', $usuario->name) }}" required maxlength="120">
                    @error('name') <span class="hb-error">{{ $message }}</span> @enderror
                </div>

                <div class="hb-field">
                    <label class="hb-label" for="phone">Teléfono</label>
                    <input class="hb-input @error('phone') is-invalid @enderror" type="tel" id="phone"
                           name="phone" value="{{ old('phone', $usuario->phone) }}" placeholder="+57 300 000 0000">
                    <span class="hb-hint">Solo lo ven los usuarios con sesión iniciada.</span>
                    @error('phone') <span class="hb-error">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="hb-fields-2">
                <div class="hb-field">
                    <label class="hb-label" for="ciudad">Ciudad</label>
                    <input class="hb-input @error('ciudad') is-invalid @enderror" type="text" id="ciudad"
                           name="ciudad" value="{{ old('ciudad', $usuario->ciudad) }}" placeholder="Montería">
                    @error('ciudad') <span class="hb-error">{{ $message }}</span> @enderror
                </div>

                <div class="hb-field">
                    <label class="hb-label" for="universidad">Universidad</label>
                    <input class="hb-input @error('universidad') is-invalid @enderror" type="text" id="universidad"
                           name="universidad" value="{{ old('universidad', $usuario->universidad) }}"
                           list="universidades" placeholder="Universidad de Córdoba">
                    <datalist id="universidades">
                        @foreach (\App\Models\Alojamiento::UNIVERSIDADES as $u)
                            <option value="{{ $u['nombre'] }}">
                        @endforeach
                    </datalist>
                    <span class="hb-hint">Si eres estudiante, ayuda a que te recomienden lo que te queda cerca.</span>
                    @error('universidad') <span class="hb-error">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="hb-field hb-mb-0">
                <label class="hb-label" for="bio">Sobre ti</label>
                <textarea class="hb-textarea @error('bio') is-invalid @enderror" id="bio" name="bio"
                          rows="4" maxlength="600"
                          placeholder="Ej: Llevo cuatro años arrendando habitaciones a estudiantes de Unicórdoba. Vivo en la misma casa, así que siempre hay alguien.">{{ old('bio', $usuario->bio) }}</textarea>
                <span class="hb-hint">Máximo 600 caracteres. Aparece en tu perfil público.</span>
                @error('bio') <span class="hb-error">{{ $message }}</span> @enderror
            </div>

        </div>
    </div>

    {{-- Cuenta --}}
    <div class="hb-card" style="margin-top:1.5rem">
        <div class="hb-card__header"><h2 style="font-size:1.1rem">Tu cuenta</h2></div>
        <div class="hb-card__body">
            <div class="hb-specs">
                <div class="hb-spec">
                    <div class="hb-spec__label">Correo</div>
                    <div class="hb-spec__value hb-small">{{ $usuario->email }}</div>
                </div>
                <div class="hb-spec">
                    <div class="hb-spec__label">Rol</div>
                    <div class="hb-spec__value">{{ $usuario->nombreRol() }}</div>
                </div>
                <div class="hb-spec">
                    <div class="hb-spec__label">Verificación</div>
                    <div class="hb-spec__value hb-small">
                        {{ $usuario->estaVerificado() ? 'Verificado' : 'Sin verificar' }}
                    </div>
                </div>
            </div>

            @unless ($usuario->puedePublicar())
                <div class="hb-alert hb-alert--info" style="margin:1.25rem 0 0">
                    <span class="hb-alert__icon">i</span>
                    <div>
                        ¿Quieres arrendar? Necesitas verificar tu cuenta primero.
                        <a href="{{ route('arrendador.solicitud') }}">Empezar la verificación</a>.
                    </div>
                </div>
            @endunless
        </div>
    </div>

    <div class="hb-row hb-row--between" style="margin-top:1.75rem">
        <a class="hb-btn hb-btn--ghost" href="{{ route('home') }}">Cancelar</a>
        <button class="hb-btn hb-btn--primary" type="submit">Guardar cambios</button>
    </div>
</form>
@endsection
