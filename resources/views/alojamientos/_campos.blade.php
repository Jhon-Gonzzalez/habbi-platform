{{-- Campos comunes al formulario de publicación y de edición. Espera $a (Alojamiento|null). --}}
@php $a = $a ?? null; @endphp

<div class="hb-card">
    <div class="hb-card__header"><h2 style="font-size:1.1rem">Información básica</h2></div>
    <div class="hb-card__body">

        <div class="hb-field">
            <label class="hb-label" for="title">Título de la publicación <span class="hb-req">*</span></label>
            <input class="hb-input @error('title') is-invalid @enderror" type="text" id="title" name="title"
                   value="{{ old('title', $a->title ?? '') }}" maxlength="255" required
                   placeholder="Ej: Habitación amoblada a 5 min de la universidad">
            <span class="hb-hint">Sé concreto: tipo de espacio, zona y algo que lo distinga.</span>
            @error('title') <span class="hb-error">{{ $message }}</span> @enderror
        </div>

        <div class="hb-fields-2">
            <div class="hb-field">
                <label class="hb-label" for="type">Tipo de alojamiento <span class="hb-req">*</span></label>
                <select class="hb-select @error('type') is-invalid @enderror" id="type" name="type" required>
                    <option value="">Selecciona…</option>
                    @foreach (\App\Models\Alojamiento::TIPOS as $tipo)
                        <option value="{{ $tipo }}" @selected(old('type', $a->type ?? '') === $tipo)>{{ $tipo }}</option>
                    @endforeach
                </select>
                @error('type') <span class="hb-error">{{ $message }}</span> @enderror
            </div>

            <div class="hb-field">
                <label class="hb-label" for="guests">Capacidad <span class="hb-req">*</span></label>
                <select class="hb-select @error('guests') is-invalid @enderror" id="guests" name="guests" required>
                    @for ($i = 1; $i <= 8; $i++)
                        <option value="{{ $i }}" @selected((int) old('guests', $a->guests ?? 1) === $i)>
                            {{ $i }} @plural('huésped', $i)
                        </option>
                    @endfor
                </select>
                @error('guests') <span class="hb-error">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="hb-field">
            <label class="hb-label" for="price">Precio <span class="hb-req">*</span></label>
            <div class="hb-input-group">
                <span class="hb-input-group__addon">$</span>
                <input class="hb-input @error('price') is-invalid @enderror" type="number" id="price" name="price"
                       value="{{ old('price', $a->price ?? '') }}" min="1" step="1000" required placeholder="650000">
                <select class="hb-select" name="price_period" aria-label="Periodo del precio">
                    @foreach (\App\Models\Alojamiento::PERIODOS as $periodo)
                        <option value="{{ $periodo }}" @selected(old('price_period', $a->price_period ?? 'mes') === $periodo)>
                            por {{ $periodo }}
                        </option>
                    @endforeach
                </select>
            </div>
            <span class="hb-hint">En pesos colombianos, sin puntos ni comas.</span>
            @error('price') <span class="hb-error">{{ $message }}</span> @enderror
            @error('price_period') <span class="hb-error">{{ $message }}</span> @enderror
        </div>

    </div>
</div>

<div class="hb-card" style="margin-top:1.5rem">
    <div class="hb-card__header"><h2 style="font-size:1.1rem">Ubicación</h2></div>
    <div class="hb-card__body">

        <div class="hb-fields-2">
            <div class="hb-field">
                <label class="hb-label" for="city">Ciudad <span class="hb-req">*</span></label>
                <input class="hb-input @error('city') is-invalid @enderror" type="text" id="city" name="city"
                       value="{{ old('city', $a->city ?? '') }}" maxlength="120" required placeholder="Ej: Bogotá">
                @error('city') <span class="hb-error">{{ $message }}</span> @enderror
            </div>

            <div class="hb-field">
                <label class="hb-label" for="neighborhood">Barrio</label>
                <input class="hb-input @error('neighborhood') is-invalid @enderror" type="text" id="neighborhood"
                       name="neighborhood" value="{{ old('neighborhood', $a->neighborhood ?? '') }}"
                       maxlength="120" placeholder="Ej: Chapinero">
                @error('neighborhood') <span class="hb-error">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="hb-field">
            <label class="hb-label" for="address">Dirección aproximada</label>
            <input class="hb-input @error('address') is-invalid @enderror" type="text" id="address" name="address"
                   value="{{ old('address', $a->address ?? '') }}" maxlength="255"
                   placeholder="Ej: Calle 53 con Carrera 13">
            <span class="hb-hint">No publiques el número exacto del apartamento; compártelo solo al contactar.</span>
            @error('address') <span class="hb-error">{{ $message }}</span> @enderror
        </div>

    </div>
</div>

<div class="hb-card" style="margin-top:1.5rem">
    <div class="hb-card__header"><h2 style="font-size:1.1rem">Descripción y comodidades</h2></div>
    <div class="hb-card__body">

        <div class="hb-field">
            <label class="hb-label" for="description">Descripción <span class="hb-req">*</span></label>
            <textarea class="hb-textarea @error('description') is-invalid @enderror" id="description"
                      name="description" required minlength="30" maxlength="5000"
                      placeholder="Cuenta cómo es el espacio, qué incluye el precio, las normas de convivencia y qué hay cerca.">{{ old('description', $a->description ?? '') }}</textarea>
            @error('description') <span class="hb-error">{{ $message }}</span> @enderror
        </div>

        <fieldset class="hb-field" style="border:0;padding:0;margin-inline:0">
            <legend class="hb-label">Comodidades</legend>
            @php $seleccionadas = (array) old('amenities', $a->amenities ?? []); @endphp
            <div class="hb-checks">
                @foreach (\App\Models\Alojamiento::COMODIDADES as $comodidad)
                    <label class="hb-check">
                        <input type="checkbox" name="amenities[]" value="{{ $comodidad }}"
                               @checked(in_array($comodidad, $seleccionadas, true))>
                        <span>{{ $comodidad }}</span>
                    </label>
                @endforeach
            </div>
        </fieldset>

        <div class="hb-field hb-mb-0">
            <label class="hb-label" for="phone">Teléfono de contacto <span class="hb-req">*</span></label>
            <input class="hb-input @error('phone') is-invalid @enderror" type="tel" id="phone" name="phone"
                   value="{{ old('phone', $a->phone ?? auth()->user()->phone ?? '') }}" required
                   placeholder="+57 300 000 0000">
            <span class="hb-hint">Solo lo ven los usuarios que han iniciado sesión. Se usa para el botón de WhatsApp.</span>
            @error('phone') <span class="hb-error">{{ $message }}</span> @enderror
        </div>

    </div>
</div>
