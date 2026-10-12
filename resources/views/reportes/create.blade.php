@extends('layouts.app')

@section('titulo', 'Reportar publicación')

@section('cabecera')
    <div>
        <h1>Reportar esta publicación</h1>
        <p>Nos ayudas a que HABBI no se llene de anuncios falsos.</p>
    </div>
@endsection

@section('contenido')
<div style="max-width:760px;margin-inline:auto">

    <p class="hb-muted">
        Estás reportando <strong>«{{ $alojamiento->title }}»</strong>,
        publicada por {{ $alojamiento->user->name }}.
        <a href="{{ route('alojamientos.show', $alojamiento) }}">Volver a la publicación</a>
    </p>

    @if ($yaReportado)
        <div class="hb-alert hb-alert--info">
            <span class="hb-alert__icon" aria-hidden="true">i</span>
            <span>
                Ya reportaste esta publicación antes. Si el problema cambió o tienes
                algo nuevo que contarnos, puedes enviarlo otra vez.
            </span>
        </div>
    @endif

    <form method="POST" action="{{ route('reportes.store', $alojamiento) }}">
        @csrf

        <div class="hb-card">
            <div class="hb-card__header">
                <h2 style="font-size:1.1rem">¿Qué problema tiene?</h2>
            </div>
            <div class="hb-card__body">
                <div class="hb-field hb-mb-0">
                    @foreach (\App\Models\Reporte::MOTIVOS as $valor => $texto)
                        <label class="hb-check">
                            <input type="radio" name="motivo" value="{{ $valor }}"
                                   @checked(old('motivo') === $valor) required>
                            <span>{{ $texto }}</span>
                        </label>
                    @endforeach

                    @error('motivo') <span class="hb-error">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        <div class="hb-card" style="margin-top:1.5rem">
            <div class="hb-card__body">
                <div class="hb-field hb-mb-0">
                    <label class="hb-label" for="detalle">Cuéntanos qué pasó</label>
                    <textarea class="hb-textarea @error('detalle') is-invalid @enderror"
                              id="detalle" name="detalle" rows="5"
                              maxlength="1000">{{ old('detalle') }}</textarea>
                    <span class="hb-hint">
                        Obligatorio si eliges «Otro motivo». Entre más concreto seas
                        —fechas, qué te dijeron, cuánto te pidieron—, más rápido podemos revisarlo.
                    </span>
                    @error('detalle') <span class="hb-error">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        <div class="hb-alert hb-alert--info" style="margin-top:1.5rem">
            <span class="hb-alert__icon" aria-hidden="true">i</span>
            <span>
                <strong>Un reporte no borra la publicación.</strong>
                La revisa una persona del equipo antes de tomar cualquier decisión.
                Si te estafaron dinero o estás en peligro, denuncia también ante la
                autoridad: eso no lo podemos hacer nosotros por ti.
            </span>
        </div>

        <div class="hb-row hb-row--between" style="margin-top:1.5rem">
            <a class="hb-btn hb-btn--ghost" href="{{ route('alojamientos.show', $alojamiento) }}">Cancelar</a>
            <button class="hb-btn hb-btn--danger" type="submit">Enviar reporte</button>
        </div>
    </form>

</div>
@endsection
