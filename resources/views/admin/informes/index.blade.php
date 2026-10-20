@extends('layouts.admin')

@section('titulo', 'Informes')
@section('subtitulo', $datos->desde->format('d/m/Y') . ' — ' . $datos->hasta->format('d/m/Y') . ' · ' . $datos->dias() . ' días')

@section('contenido')

<div class="hb-card">
    <div class="hb-card__body">
        <form method="GET" action="{{ route('admin.informes.index') }}"
              class="hb-row" style="gap:.75rem;align-items:flex-end;flex-wrap:wrap">
            <div class="hb-field hb-mb-0">
                <label class="hb-label" for="desde">Desde</label>
                <input class="hb-input" type="date" id="desde" name="desde"
                       value="{{ $datos->desde->format('Y-m-d') }}">
            </div>
            <div class="hb-field hb-mb-0">
                <label class="hb-label" for="hasta">Hasta</label>
                <input class="hb-input" type="date" id="hasta" name="hasta"
                       value="{{ $datos->hasta->format('Y-m-d') }}">
            </div>

            <button class="hb-btn hb-btn--primary" type="submit">Ver</button>

            <a class="hb-btn hb-btn--ghost"
               href="{{ route('admin.informes.pdf', ['desde' => $datos->desde->format('Y-m-d'), 'hasta' => $datos->hasta->format('Y-m-d')]) }}">
                Descargar PDF
            </a>
        </form>

        @error('desde') <span class="hb-error">{{ $message }}</span> @enderror
        @error('hasta') <span class="hb-error">{{ $message }}</span> @enderror
    </div>
</div>

<div class="hb-card hb-informe" style="margin-top:1.5rem">
    <div class="hb-card__body">
        @include('admin.informes._cuerpo', ['datos' => $datos])
    </div>
</div>

@endsection
