@extends('layouts.admin')

@section('titulo', $aliado->exists ? 'Editar aliado' : 'Nuevo aliado')
@section('subtitulo', $aliado->exists ? $aliado->nombre : 'Un negocio local que paga por aparecer')

@section('acciones')
    <a class="hb-btn hb-btn--ghost" href="{{ route('admin.aliados.index') }}">Volver</a>
@endsection

@section('contenido')
<div style="max-width:640px">
    <div class="hb-card">
        <div class="hb-card__body">

            <form method="POST" enctype="multipart/form-data"
                  action="{{ $aliado->exists ? route('admin.aliados.update', $aliado) : route('admin.aliados.store') }}">
                @csrf
                @if ($aliado->exists) @method('PUT') @endif

                <div class="hb-field">
                    <label class="hb-label" for="nombre">Nombre del negocio <span class="hb-req">*</span></label>
                    <input class="hb-input @error('nombre') is-invalid @enderror" type="text" id="nombre"
                           name="nombre" value="{{ old('nombre', $aliado->nombre) }}" maxlength="120" required>
                    @error('nombre') <span class="hb-error">{{ $message }}</span> @enderror
                </div>

                <div class="hb-field">
                    <label class="hb-label" for="descripcion">Una línea <span class="hb-req">*</span></label>
                    <input class="hb-input @error('descripcion') is-invalid @enderror" type="text" id="descripcion"
                           name="descripcion" value="{{ old('descripcion', $aliado->descripcion) }}"
                           maxlength="160" required placeholder="Ej: Almuerzos a $12.000 a dos cuadras de Unicórdoba">
                    <span class="hb-hint">Corto de verdad. El bloque es pequeño a propósito; un texto largo lo convierte en un banner.</span>
                    @error('descripcion') <span class="hb-error">{{ $message }}</span> @enderror
                </div>

                <div class="hb-field">
                    <label class="hb-label" for="url">Enlace <span class="hb-req">*</span></label>
                    <input class="hb-input @error('url') is-invalid @enderror" type="url" id="url"
                           name="url" value="{{ old('url', $aliado->url) }}" required
                           placeholder="https://...">
                    <span class="hb-hint">
                        Tiene que empezar por <code>https://</code>. Un enlace sin cifrar desde una
                        página segura hace que el navegador avise, y eso queda fatal justo aquí.
                    </span>
                    @error('url') <span class="hb-error">{{ $message }}</span> @enderror
                </div>

                <div class="hb-fields-2">
                    <div class="hb-field">
                        <label class="hb-label" for="categoria">Categoría</label>
                        <select class="hb-select" id="categoria" name="categoria">
                            <option value="">Sin categoría</option>
                            @foreach (\App\Models\Aliado::CATEGORIAS as $c)
                                <option value="{{ $c }}" @selected(old('categoria', $aliado->categoria) === $c)>{{ $c }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="hb-field">
                        <label class="hb-label" for="orden">Orden</label>
                        <input class="hb-input" type="number" id="orden" name="orden"
                               value="{{ old('orden', $aliado->orden ?? 0) }}" min="0" max="999">
                        <span class="hb-hint">Menor, más arriba.</span>
                    </div>
                </div>

                <div class="hb-fields-2">
                    <div class="hb-field">
                        <label class="hb-label" for="desde">Desde <span class="hb-req">*</span></label>
                        <input class="hb-input @error('desde') is-invalid @enderror" type="date" id="desde"
                               name="desde" value="{{ old('desde', $aliado->desde?->format('Y-m-d')) }}" required>
                        @error('desde') <span class="hb-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="hb-field">
                        <label class="hb-label" for="hasta">Hasta <span class="hb-req">*</span></label>
                        <input class="hb-input @error('hasta') is-invalid @enderror" type="date" id="hasta"
                               name="hasta" value="{{ old('hasta', $aliado->hasta?->format('Y-m-d')) }}" required>
                        <span class="hb-hint">Pasada esta fecha deja de salir solo.</span>
                        @error('hasta') <span class="hb-error">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="hb-field">
                    <label class="hb-label" for="monto">Lo que pagó (COP)</label>
                    <input class="hb-input @error('monto') is-invalid @enderror" type="number" id="monto"
                           name="monto" value="{{ old('monto', $aliado->monto ?? 0) }}" min="0" step="1000">
                    @error('monto') <span class="hb-error">{{ $message }}</span> @enderror
                </div>

                <div class="hb-field">
                    <label class="hb-label" for="logo">Logo</label>
                    <input class="hb-input @error('logo') is-invalid @enderror" type="file" id="logo"
                           name="logo" accept="image/jpeg,image/png,image/webp">
                    <span class="hb-hint">
                        Cuadrado, máximo 1 MB. Si no manda logo, se muestran sus iniciales.
                        @if ($aliado->logoUrl())
                            Ya tiene uno; si subes otro, el anterior se borra.
                        @endif
                    </span>
                    @error('logo') <span class="hb-error">{{ $message }}</span> @enderror
                </div>

                <label class="hb-check" style="margin-bottom:1.25rem">
                    <input type="checkbox" name="activo" value="1"
                           @checked(old('activo', $aliado->activo ?? true))>
                    <span>Publicado</span>
                </label>

                <button class="hb-btn hb-btn--primary hb-btn--block" type="submit">
                    {{ $aliado->exists ? 'Guardar cambios' : 'Crear aliado' }}
                </button>
            </form>

        </div>
    </div>
</div>
@endsection
