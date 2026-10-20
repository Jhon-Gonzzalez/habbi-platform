{{--
    El estado del acuerdo. Espera $conversacion, $yo y $alojamiento.

    La regla clave: propone uno, confirma el OTRO. Por eso quien propuso ve
    «esperando» y la otra parte ve el botón de confirmar.
--}}

@if ($conversacion->acuerdoConfirmado())

    <div class="hb-acuerdo is-cerrado">
        <div>
            <strong>Acuerdo cerrado</strong>
            <p class="hb-mb-0">
                {{ $conversacion->acuerdo_personas }}
                @plural('plaza', $conversacion->acuerdo_personas)
                · {{ $conversacion->acuerdo_confirmado_en->translatedFormat('j \d\e F \a \l\a\s H:i') }}
            </p>
            @if ($alojamiento->estaArrendado())
                <p class="hb-small hb-mb-0" style="margin-top:.35rem">
                    La publicación salió del buscador: no quedan plazas libres.
                </p>
            @else
                <p class="hb-small hb-mb-0" style="margin-top:.35rem">
                    Quedan {{ $alojamiento->cupos }} @plural('plaza', $alojamiento->cupos) por arrendar.
                </p>
            @endif
        </div>
    </div>

@elseif ($conversacion->acuerdoPropuesto())

    <div class="hb-acuerdo is-propuesto">
        <div>
            <strong>Hay un acuerdo propuesto</strong>
            <p class="hb-mb-0">
                {{ $conversacion->proponente?->name }} propuso cerrar por
                {{ $conversacion->acuerdo_personas }}
                @plural('plaza', $conversacion->acuerdo_personas).
            </p>
        </div>

        @if ($conversacion->puedeConfirmar($yo))
            <div class="hb-row" style="gap:.5rem;flex-wrap:wrap">
                <form method="POST" action="{{ route('mensajes.acuerdo.confirmar', $conversacion) }}"
                      data-confirmar="Al confirmar, la publicación bajará {{ $conversacion->acuerdo_personas }} plaza(s). ¿Continuar?">
                    @csrf
                    <button class="hb-btn hb-btn--success" type="submit">Confirmar el acuerdo</button>
                </form>
            </div>
        @else
            <span class="hb-badge hb-badge--warning">Esperando a {{ $conversacion->otro($yo)?->name }}</span>
            <form method="POST" action="{{ route('mensajes.acuerdo.retirar', $conversacion) }}">
                @csrf
                <button class="hb-btn hb-btn--link hb-btn--sm" type="submit">Retirar la propuesta</button>
            </form>
        @endif
    </div>

@elseif (! $alojamiento->estaArrendado())

    <details class="hb-acuerdo is-abierto">
        <summary><strong>¿Ya se pusieron de acuerdo?</strong> Ciérralo aquí</summary>

        <p class="hb-muted hb-small" style="margin-top:.75rem">
            Lo propone uno y lo confirma el otro. Al confirmarse, la publicación
            baja las plazas automáticamente y deja de salir en el buscador si se
            queda sin ninguna. Así nadie escribe por algo que ya se arrendó.
        </p>

        <form method="POST" action="{{ route('mensajes.acuerdo.proponer', $conversacion) }}"
              class="hb-row" style="gap:.6rem;align-items:flex-end;flex-wrap:wrap">
            @csrf
            <div class="hb-field hb-mb-0" style="max-width:180px">
                <label class="hb-label" for="personas">¿Cuántas plazas?</label>
                <input class="hb-input @error('personas') is-invalid @enderror" type="number"
                       id="personas" name="personas" value="{{ old('personas', 1) }}"
                       min="1" max="{{ $alojamiento->cupos }}" required>
            </div>
            <button class="hb-btn hb-btn--primary" type="submit">Proponer acuerdo</button>
        </form>

        @error('personas') <span class="hb-error">{{ $message }}</span> @enderror

        <p class="hb-hint" style="margin-top:.6rem">
            Quedan {{ $alojamiento->cupos }} @plural('plaza', $alojamiento->cupos) libres.
        </p>
    </details>

@else

    <div class="hb-acuerdo is-cerrado">
        <div>
            <strong>Esta publicación ya no tiene plazas libres</strong>
            <p class="hb-mb-0 hb-small">
                Puedes seguir escribiendo, pero el arrendador tendría que
                reabrirla para poder cerrar un acuerdo.
            </p>
        </div>
    </div>

@endif
