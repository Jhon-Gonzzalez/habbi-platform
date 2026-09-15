@extends('layouts.app')

@section('titulo', 'Publicar alojamiento')

@section('cabecera')
    <div>
        <h1>Publicar alojamiento</h1>
        <p>Rellena los datos y publícalo gratis. Podrás editarlo o pausarlo cuando quieras.</p>
    </div>
@endsection

@section('contenido')
<form method="POST" action="{{ route('alojamientos.store') }}" enctype="multipart/form-data"
      style="max-width:820px;margin-inline:auto">
    @csrf

    @include('alojamientos._campos', ['a' => null])

    {{-- Fotos --}}
    <div class="hb-card" style="margin-top:1.5rem">
        <div class="hb-card__header"><h2 style="font-size:1.1rem">Fotos <span class="hb-req">*</span></h2></div>
        <div class="hb-card__body">

            <div class="hb-drop @error('photos') is-invalid @enderror" data-uploader="#previsualizacion"
                 data-max-bytes="{{ \App\Support\Subidas::maxPost() }}">
                <div class="hb-drop__icon" aria-hidden="true">🖼</div>
                <strong>Arrastra tus fotos aquí o haz clic para elegirlas</strong>
                <span class="hb-hint">Hasta 8 imágenes · JPG, PNG o WEBP · máximo 5 MB cada una</span>
                <input type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple required>
            </div>

            <div class="hb-thumbs" id="previsualizacion"></div>

            <span class="hb-hint" style="margin-top:.75rem">La primera foto será la portada de tu publicación.</span>

            @error('photos')   <span class="hb-error">{{ $message }}</span> @enderror
            @error('photos.*') <span class="hb-error">{{ $message }}</span> @enderror
        </div>
    </div>

    <div class="hb-row hb-row--between" style="margin-top:1.75rem">
        <a class="hb-btn hb-btn--ghost" href="{{ route('alojamientos.index') }}">Cancelar</a>
        <button class="hb-btn hb-btn--primary" type="submit">Publicar alojamiento</button>
    </div>
</form>
@endsection
