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

        {{-- Punto en el mapa (RF-4, RF-6) --}}
        <div class="hb-field">
            <span class="hb-label">¿Dónde queda, exactamente?</span>

            {{--
                La forma de bloque y no @php(...): Blade empareja el primer
                @php del fichero con el siguiente @endphp que encuentre, sin
                importar cuál lo abrió. Con la forma corta aquí, el @endphp
                de las comodidades cerraba este bloque y se tragaba medio
                formulario como PHP.
            --}}
            @php
                $latVieja = old('lat', $a->lat ?? '');
                $lngVieja = old('lng', $a->lng ?? '');
            @endphp

            <input type="hidden" id="lat" name="lat" value="{{ $latVieja }}">
            <input type="hidden" id="lng" name="lng" value="{{ $lngVieja }}">

            <div class="hb-selector" id="selector-mapa">
                <div class="hb-selector__lienzo"></div>
                <div class="hb-selector__pie">
                    <span class="hb-hint hb-mb-0" id="selector-mapa-aviso">
                        Toca el mapa para marcar el punto. Puedes arrastrar el pin después.
                    </span>
                    <button class="hb-btn hb-btn--ghost hb-btn--sm" type="button"
                            id="selector-mapa-borrar" {{ $latVieja === '' ? 'hidden' : '' }}>
                        Quitar el punto
                    </button>
                </div>
            </div>

            <span class="hb-hint">
                Es opcional, pero sin punto tu anuncio no aparece cuando alguien
                busca «a menos de 2 km de la universidad», que es como busca casi
                todo el mundo. <strong>Marca la esquina o la cuadra, no la puerta</strong>:
                es suficiente para que te encuentren y no publica dónde vives.
            </span>
            @error('lat') <span class="hb-error">{{ $message }}</span> @enderror
            @error('lng') <span class="hb-error">{{ $message }}</span> @enderror
        </div>

        <div class="hb-field">
            <label class="hb-label" for="cupos">¿Cuántas plazas tienes libres?</label>
            <input class="hb-input @error('cupos') is-invalid @enderror" type="number"
                   id="cupos" name="cupos" value="{{ old('cupos', $a->cupos ?? 1) }}"
                   min="1" max="50" required>
            <span class="hb-hint">
                Normalmente 1: la habitación o el apartamento entero. Pon más solo si
                arriendas varias plazas iguales con este mismo anuncio. Cuando se cierre
                un acuerdo por el chat, esta cifra baja sola, y al llegar a cero el
                anuncio deja de salir en el buscador.
            </span>
            @error('cupos') <span class="hb-error">{{ $message }}</span> @enderror
        </div>

        <div class="hb-field">
            <label class="hb-label" for="disponible_desde">¿Desde cuándo está libre?</label>
            <input class="hb-input @error('disponible_desde') is-invalid @enderror" type="date"
                   id="disponible_desde" name="disponible_desde"
                   value="{{ old('disponible_desde', isset($a->disponible_desde) ? $a->disponible_desde?->format('Y-m-d') : '') }}">
            <span class="hb-hint">
                Déjalo vacío si se puede entrar ya. Ponlo si el inquilino actual
                se va en una fecha concreta: así nadie te escribe para enero
                cuando el cuarto se desocupa en junio.
            </span>
            @error('disponible_desde') <span class="hb-error">{{ $message }}</span> @enderror
        </div>

        <div class="hb-field">
            <label class="hb-label" for="universidad_referencia">Universidad más cercana</label>
            <select class="hb-input @error('universidad_referencia') is-invalid @enderror"
                    id="universidad_referencia" name="universidad_referencia">
                @foreach (\App\Models\Alojamiento::UNIVERSIDADES as $u)
                    <option value="{{ $u['nombre'] }}"
                        @selected(old('universidad_referencia', $a->universidad_referencia ?? '') === $u['nombre'])>
                        {{ $u['nombre'] }}
                    </option>
                @endforeach
            </select>
            <span class="hb-hint">
                Con esto los estudiantes pueden ver la ruta en Google Maps desde tu alojamiento hasta su universidad.
            </span>
            @error('universidad_referencia') <span class="hb-error">{{ $message }}</span> @enderror
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
            <span class="hb-hint">Es un dato secundario: hay que pedirlo y queda registrado quién lo pidió. El contacto normal es por el chat.</span>
            @error('phone') <span class="hb-error">{{ $message }}</span> @enderror
        </div>

    </div>
</div>

@include('partials.leaflet')

@push('scripts')
    <script src="{{ asset('assets/js/selector-mapa.js') }}" defer></script>
@endpush
