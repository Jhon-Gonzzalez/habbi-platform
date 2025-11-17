@extends('layouts.app')

@section('title','Editar alojamiento')

@section('content')

  <link rel="stylesheet" href="{{ asset('assets/styles.css') }}">
<div class="container publish-wrap">
  <div class="form-card">
    <div class="form-head">
      <h1>Editar alojamiento</h1>
      <p class="muted" style="margin:0">Actualiza la información y las fotos.</p>
    </div>

    <form class="publish-form" method="POST" action="{{ route('alojamiento.update',$a) }}" enctype="multipart/form-data">
      @csrf @method('PUT')

      <div class="form-grid">
        <label class="field">
          <span class="label">Título</span>
          <input type="text" name="title" value="{{ old('title',$a->title) }}" required>
        </label>

        <label class="field">
          <span class="label">Tipo</span>
          <select name="type" required>
            @foreach(['Habitación','Estudio','Apartamento','Loft','Suite'] as $t)
              <option value="{{ $t }}" @selected(old('type',$a->type)===$t)>{{ $t }}</option>
            @endforeach
          </select>
        </label>

        <label class="field">
          <span class="label">Precio (COP)</span>
          <input type="number" name="price" value="{{ old('price',$a->price) }}" required>
        </label>

        <label class="field">
          <span class="label">Periodo</span>
          <select name="price_period" required>
            @foreach(['mes','noche'] as $p)
              <option value="{{ $p }}" @selected(old('price_period',$a->price_period)===$p)>{{ $p }}</option>
            @endforeach
          </select>
        </label>

        <label class="field">
          <span class="label">Huéspedes</span>
          <input type="number" name="guests" min="1" max="8" value="{{ old('guests',$a->guests) }}" required>
        </label>

        <label class="field">
          <span class="label">Ciudad</span>
          <input type="text" name="city" value="{{ old('city',$a->city) }}" required>
        </label>

        <label class="field">
          <span class="label">Barrio</span>
          <input type="text" name="neighborhood" value="{{ old('neighborhood',$a->neighborhood) }}">
        </label>

        <label class="field" style="grid-column:1 / -1">
          <span class="label">Dirección</span>
          <input type="text" name="address" value="{{ old('address',$a->address) }}">
        </label>

        <label class="field" style="grid-column:1 / -1">
          <span class="label">Descripción</span>
          <textarea name="description" rows="4" required>{{ old('description',$a->description) }}</textarea>
        </label>

        <fieldset class="field" style="grid-column:1 / -1">
          <legend class="label">Comodidades</legend>
          @php
            $amenList = ['Wifi','Lavadora','Parqueadero','Cocina','Pet Friendly','Amoblado','Aire acondicionado','Baño privado'];
            $sel = old('amenities', $a->amenities ?? []);
          @endphp
          <div class="chipset">
            @foreach($amenList as $am)
              <label class="chip">
                <input type="checkbox" name="amenities[]" value="{{ $am }}" {{ in_array($am,$sel) ? 'checked' : '' }}>
                <span>{{ $am }}</span>
              </label>
            @endforeach
          </div>
        </fieldset>

        @if($a->photos && count($a->photos))
        <div class="field" style="grid-column:1 / -1">
          <span class="label">Fotos actuales (elige portada)</span>
          <div class="thumbs" style="flex-direction:row; gap:10px; max-height:none;">
            @foreach(($a->photos ?? []) as $i => $p)
              <label class="thumb" style="width:110px">
                <img src="{{ Storage::url($p) }}" alt="Foto {{ $i }}" style="height:80px; object-fit:cover; width:100%">
                <input type="radio" name="cover_index" value="{{ $i }}" {{ $a->cover_path===$p ? 'checked' : '' }}>
                <small>Portada</small>
              </label>
            @endforeach
          </div>
        </div>
        @endif

        <label class="field" style="grid-column:1 / -1">
          <span class="label">Agregar nuevas fotos (opcional)</span>
          <input type="file" name="new_photos[]" multiple accept="image/*">
        </label>
      </div>

      <div class="form-actions">
        <a class="btn" href="{{ route('alojamiento.mine') }}">Cancelar</a>
        <button class="btn btn-primary" type="submit">Guardar cambios</button>
      </div>
    </form>
  </div>
</div>
@endsection
