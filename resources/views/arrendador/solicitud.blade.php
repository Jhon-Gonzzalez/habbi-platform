@extends('layouts.app')

@section('titulo', 'Quiero arrendar')

@section('cabecera')
    <div>
        <h1>Conviértete en arrendador</h1>
        <p>Verificamos a quien publica para que los estudiantes sepan con quién tratan.</p>
    </div>
@endsection

@section('contenido')
<div style="max-width:760px;margin-inline:auto">

    @if ($usuario->puedePublicar())
        {{-- Ya está aprobado --}}
        <div class="hb-card">
            <div class="hb-card__body hb-center">
                <div style="font-size:2.5rem;line-height:1" aria-hidden="true">✓</div>
                <h2 style="margin-top:.75rem">Ya eres arrendador verificado</h2>
                <p class="hb-muted">Tu perfil muestra el sello y puedes publicar cuando quieras.</p>
                <div class="hb-row hb-center" style="justify-content:center;margin-top:1.5rem">
                    <a class="hb-btn hb-btn--primary" href="{{ route('alojamientos.create') }}">Publicar alojamiento</a>
                    <a class="hb-btn hb-btn--ghost" href="{{ route('arrendadores.perfil', $usuario) }}">Ver mi perfil público</a>
                </div>
            </div>
        </div>

    @elseif ($solicitud && $solicitud->estaPendiente())
        {{-- En revisión --}}
        <div class="hb-card">
            <div class="hb-card__body hb-center">
                <div style="font-size:2.5rem;line-height:1" aria-hidden="true">⏳</div>
                <h2 style="margin-top:.75rem">Tu solicitud está en revisión</h2>
                <p class="hb-muted">
                    La enviaste {{ $solicitud->created_at->diffForHumans() }}.
                    Revisamos los documentos a mano, así que puede tardar un día hábil.
                </p>
                <p class="hb-small hb-muted">Te avisaremos por correo en cuanto haya respuesta.</p>
            </div>
        </div>

    @else
        {{-- Formulario, con el rechazo anterior si lo hubo --}}
        @if ($solicitud && $solicitud->fueRechazada())
            <div class="hb-alert hb-alert--error">
                <span class="hb-alert__icon">!</span>
                <div>
                    <strong>Tu solicitud anterior fue rechazada</strong>
                    <p style="margin:.4rem 0 0">{{ $solicitud->motivo_rechazo }}</p>
                    <p class="hb-small hb-mb-0" style="margin-top:.4rem">
                        Corrige lo indicado y vuelve a enviarla: no hay límite de intentos.
                    </p>
                </div>
            </div>
        @endif

        <div class="hb-card">
            <div class="hb-card__header">
                <h2 style="font-size:1.1rem">Por qué pedimos esto</h2>
            </div>
            <div class="hb-card__body">
                <p class="hb-muted hb-mb-0">
                    Un estudiante que llega a tu publicación no tiene forma de saber si
                    eres real. Verificar tu identidad y que el inmueble existe es lo que
                    convierte tu anuncio en algo en lo que se puede confiar. Los documentos
                    los ve <strong>únicamente el equipo de HABBI</strong>, nunca se publican
                    y puedes pedir que los borremos cuando quieras.
                </p>
            </div>
        </div>

        <form method="POST" action="{{ route('arrendador.solicitud.enviar') }}"
              enctype="multipart/form-data" style="margin-top:1.5rem">
            @csrf

            <div class="hb-card">
                <div class="hb-card__header"><h2 style="font-size:1.1rem">Tus datos</h2></div>
                <div class="hb-card__body">

                    <div class="hb-field">
                        <label class="hb-label" for="telefono">Teléfono de contacto <span class="hb-req">*</span></label>
                        <input class="hb-input @error('telefono') is-invalid @enderror" type="tel" id="telefono"
                               name="telefono" value="{{ old('telefono', $usuario->phone) }}" required
                               placeholder="+57 300 000 0000">
                        <span class="hb-hint">Podemos llamarte para confirmar la solicitud.</span>
                        @error('telefono') <span class="hb-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="hb-field hb-mb-0">
                        <label class="hb-label" for="mensaje">Cuéntanos sobre lo que vas a arrendar <span class="hb-req">*</span></label>
                        <textarea class="hb-textarea @error('mensaje') is-invalid @enderror" id="mensaje"
                                  name="mensaje" required minlength="40" maxlength="1000"
                                  placeholder="Ej: Tengo tres habitaciones en una casa del barrio La Granja, a diez minutos de Unicórdoba. Llevo cuatro años arrendando a estudiantes.">{{ old('mensaje') }}</textarea>
                        @error('mensaje') <span class="hb-error">{{ $message }}</span> @enderror
                    </div>

                </div>
            </div>

            <div class="hb-card" style="margin-top:1.5rem">
                <div class="hb-card__header"><h2 style="font-size:1.1rem">Documentos</h2></div>
                <div class="hb-card__body">

                    <div class="hb-field">
                        <label class="hb-label" for="documento">Documento de identidad <span class="hb-req">*</span></label>
                        <input class="hb-input @error('documento') is-invalid @enderror" type="file" id="documento"
                               name="documento" accept="image/jpeg,image/png,image/webp,application/pdf" required>
                        <span class="hb-hint">Cédula por ambas caras, o pasaporte. JPG, PNG o PDF, máximo 5 MB.</span>
                        @error('documento') <span class="hb-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="hb-field hb-mb-0">
                        <label class="hb-label" for="prueba">Prueba del inmueble <span class="hb-req">*</span></label>
                        <input class="hb-input @error('prueba') is-invalid @enderror" type="file" id="prueba"
                               name="prueba" accept="image/jpeg,image/png,image/webp,application/pdf" required>
                        <span class="hb-hint">
                            Recibo de servicios, impuesto predial o contrato de arriendo donde
                            aparezca la dirección. Sirve cualquiera de los tres.
                        </span>
                        @error('prueba') <span class="hb-error">{{ $message }}</span> @enderror
                    </div>

                </div>
            </div>

            <div class="hb-card" style="margin-top:1.5rem">
                <div class="hb-card__body">
                    <label class="hb-check" style="align-items:flex-start">
                        <input type="checkbox" name="acepta" value="1" required @checked(old('acepta'))>
                        <span>
                            Autorizo a HABBI a tratar estos documentos con la única finalidad de
                            verificar mi identidad, conforme a la
                            <a href="{{ route('legal.privacidad') }}" target="_blank">política de privacidad</a>.
                            Sé que puedo pedir su eliminación en cualquier momento.
                        </span>
                    </label>
                    @error('acepta') <span class="hb-error">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="hb-row hb-row--between" style="margin-top:1.75rem">
                <a class="hb-btn hb-btn--ghost" href="{{ route('home') }}">Cancelar</a>
                <button class="hb-btn hb-btn--primary" type="submit">Enviar solicitud</button>
            </div>
        </form>
    @endif

</div>
@endsection
