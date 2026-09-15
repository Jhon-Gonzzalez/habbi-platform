@extends('layouts.app')

@section('titulo', 'Editar publicación')

@section('cabecera')
    <div>
        <h1>Editar publicación</h1>
        <p>{{ $alojamiento->title }}</p>
    </div>
    <a class="hb-btn hb-btn--white" href="{{ route('alojamientos.show', $alojamiento) }}">Ver publicación</a>
@endsection

@section('contenido')
<form method="POST" action="{{ route('alojamientos.update', $alojamiento) }}" enctype="multipart/form-data"
      style="max-width:820px;margin-inline:auto">
    @csrf
    @method('PUT')

    @include('alojamientos._campos', ['a' => $alojamiento])

    {{-- Fotos actuales --}}
    <div class="hb-card" style="margin-top:1.5rem">
        <div class="hb-card__header">
            <h2 style="font-size:1.1rem">Fotos actuales</h2>
            <span class="hb-muted hb-small">{{ count($galeria) }} de 8</span>
        </div>
        <div class="hb-card__body">

            @if (count($galeria))
                <input type="hidden" name="cover_path" id="coverPath" value="{{ $alojamiento->cover_path }}">

                <div class="hb-thumbs" id="gestorFotos">
                    @foreach ($galeria as $foto)
                        @php $esPortada = $foto['path'] === $alojamiento->cover_path; @endphp
                        <div class="hb-thumb {{ $esPortada ? 'is-cover' : '' }}" data-ruta="{{ $foto['path'] }}">
                            <img src="{{ $foto['url'] }}" alt="">
                            @if ($esPortada)
                                <span class="hb-thumb__tag">Portada</span>
                            @endif

                            <input type="hidden" name="delete_photos[]" value="{{ $foto['path'] }}" disabled>

                            <div class="hb-thumb__bar">
                                <button type="button" data-accion="portada">Portada</button>
                                <button type="button" data-accion="quitar">Quitar</button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <span class="hb-hint" style="margin-top:.75rem">
                    Las fotos marcadas como «Quitar» se borran definitivamente al guardar.
                </span>
            @else
                <p class="hb-muted hb-mb-0">Esta publicación no tiene fotos todavía.</p>
            @endif

        </div>
    </div>

    {{-- Añadir fotos --}}
    <div class="hb-card" style="margin-top:1.5rem">
        <div class="hb-card__header"><h2 style="font-size:1.1rem">Añadir fotos</h2></div>
        <div class="hb-card__body">
            <div class="hb-drop" data-uploader="#previsualizacionNuevas">
                <div class="hb-drop__icon" aria-hidden="true">🖼</div>
                <strong>Arrastra fotos nuevas o haz clic para elegirlas</strong>
                <span class="hb-hint">JPG, PNG o WEBP · máximo 5 MB cada una</span>
                <input type="file" name="new_photos[]" accept="image/jpeg,image/png,image/webp" multiple>
            </div>

            <div class="hb-thumbs" id="previsualizacionNuevas"></div>

            @error('new_photos')   <span class="hb-error">{{ $message }}</span> @enderror
            @error('new_photos.*') <span class="hb-error">{{ $message }}</span> @enderror
        </div>
    </div>

    {{-- Visibilidad --}}
    <div class="hb-card" style="margin-top:1.5rem">
        <div class="hb-card__body">
            <label class="hb-check" style="max-width:420px">
                <input type="checkbox" name="is_active" value="1"
                       @checked(old('is_active', $alojamiento->is_active))>
                <span>Mantener la publicación visible en las búsquedas</span>
            </label>
        </div>
    </div>

    <div class="hb-row hb-row--between" style="margin-top:1.75rem">
        <a class="hb-btn hb-btn--ghost" href="{{ route('alojamientos.mine') }}">Cancelar</a>
        <button class="hb-btn hb-btn--primary" type="submit">Guardar cambios</button>
    </div>
</form>

<form method="POST" action="{{ route('alojamientos.destroy', $alojamiento) }}"
      data-confirmar="Se eliminarán la publicación, sus fotos y sus reseñas. ¿Continuar?"
      style="max-width:820px;margin:2.5rem auto 0;text-align:center">
    @csrf @method('DELETE')
    <button class="hb-btn hb-btn--danger hb-btn--sm" type="submit">Eliminar esta publicación</button>
</form>
@endsection
