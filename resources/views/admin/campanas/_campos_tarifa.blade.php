{{-- Campos compartidos por el alta y la edición de una tarifa. --}}
@php($esNueva = ! $tarifa->exists)
@php($viejo = fn (string $campo, $porDefecto = null) => $esNueva ? old($campo, $porDefecto) : $tarifa->{$campo})

<div class="hb-field">
    <label class="hb-label" for="nombre-{{ $tarifa->id ?? 'nueva' }}">Nombre</label>
    <input class="hb-input @error('nombre') is-invalid @enderror" type="text"
           id="nombre-{{ $tarifa->id ?? 'nueva' }}" name="nombre" maxlength="60" required
           value="{{ $viejo('nombre') }}" placeholder="Ej: Quincena">
    @if ($esNueva) @error('nombre') <span class="hb-error">{{ $message }}</span> @enderror @endif
</div>

<div class="hb-fields-2">
    <div class="hb-field">
        <label class="hb-label" for="dias-{{ $tarifa->id ?? 'nueva' }}">Días</label>
        <input class="hb-input @error('dias') is-invalid @enderror" type="number"
               id="dias-{{ $tarifa->id ?? 'nueva' }}" name="dias" min="1" max="365" required
               value="{{ $viejo('dias', 15) }}">
        @if ($esNueva) @error('dias') <span class="hb-error">{{ $message }}</span> @enderror @endif
    </div>

    <div class="hb-field">
        <label class="hb-label" for="precio-{{ $tarifa->id ?? 'nueva' }}">Precio (COP)</label>
        <input class="hb-input @error('precio') is-invalid @enderror" type="number"
               id="precio-{{ $tarifa->id ?? 'nueva' }}" name="precio" min="0" max="1000000" step="1000" required
               value="{{ $viejo('precio', 20000) }}">
        @if ($esNueva) @error('precio') <span class="hb-error">{{ $message }}</span> @enderror @endif
    </div>
</div>

<div class="hb-field">
    <label class="hb-label" for="nota-{{ $tarifa->id ?? 'nueva' }}">Nota</label>
    <input class="hb-input" type="text" id="nota-{{ $tarifa->id ?? 'nueva' }}" name="nota"
           maxlength="160" value="{{ $viejo('nota') }}" placeholder="Ej: La más pedida">
</div>

<div class="hb-field">
    <label class="hb-label" for="orden-{{ $tarifa->id ?? 'nueva' }}">Orden</label>
    <input class="hb-input" type="number" id="orden-{{ $tarifa->id ?? 'nueva' }}" name="orden"
           min="0" max="999" value="{{ $viejo('orden', 0) }}">
    <span class="hb-hint">Menor, más a la izquierda.</span>
</div>

<label class="hb-check">
    <input type="checkbox" name="activa" value="1" @checked($esNueva ? old('activa', true) : $tarifa->activa)>
    <span>Publicada (la ven los arrendadores)</span>
</label>

<label class="hb-check" style="margin-bottom:1rem">
    <input type="checkbox" name="destacada" value="1" @checked($esNueva ? old('destacada') : $tarifa->destacada)>
    <span>Resaltarla como recomendada</span>
</label>
