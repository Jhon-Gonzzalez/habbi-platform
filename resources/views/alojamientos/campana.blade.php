@extends('layouts.app')

@section('titulo', 'Campaña · ' . $alojamiento->title)

@section('contenido')
<div style="max-width:720px;margin-inline:auto">

    <div class="hb-card">
        <div class="hb-card__header">
            <h1 style="font-size:1.25rem">
                Campaña para «{{ \Illuminate\Support\Str::limit($alojamiento->title, 40) }}»
            </h1>
        </div>
        <div class="hb-card__body">

            @if ($alojamiento->tieneCampanaActiva())
                <div class="hb-alert hb-alert--success" style="margin-bottom:1.25rem">
                    <span class="hb-alert__icon" aria-hidden="true">★</span>
                    <span>
                        <strong>Su campaña está activa.</strong>
                        Le quedan {{ $alojamiento->diasDeCampanaRestantes() }} día(s),
                        hasta el {{ $alojamiento->campana_hasta->translatedFormat('d \d\e F') }}.
                        Si la renueva antes del vencimiento, los días nuevos se suman
                        a los restantes.
                    </span>
                </div>
            @endif

            <p>
                Durante la campaña, su publicación <strong>aparece en los primeros
                lugares del buscador</strong> y se identifica con una etiqueta.
                <strong>Usted decide la duración</strong>: no existe suscripción ni
                cobro mensual, ya que no tendría sentido pagar por meses en los que
                no hay demanda de vivienda.
            </p>

            {{-- Decir también lo que NO hace. Prometer de más es lo que
                 hace que nadie renueve. --}}
            <div class="hb-alert hb-alert--info" style="margin:1.25rem 0">
                <span class="hb-alert__icon" aria-hidden="true">i</span>
                <span>
                    <strong>Alcance de la campaña.</strong>
                    Si el estudiante ordena los resultados por precio o por cercanía
                    a la universidad, prevalece el criterio que él haya elegido. La
                    campaña tampoco garantiza el arriendo: aporta visibilidad, y la
                    decisión depende de sus fotografías, su precio y su tiempo de
                    respuesta en el chat.
                </span>
            </div>

            @if ($pendiente)
                {{-- Con una solicitud en curso no se ofrece pedir otra: el
                     arrendador acabaría pagando dos veces lo mismo. --}}
                <div class="hb-alert hb-alert--info" style="margin:1.5rem 0">
                    <span class="hb-alert__icon" aria-hidden="true">⏳</span>
                    <span>
                        <strong>Solicitud enviada.</strong>
                        Pidió el plan <strong>{{ $pendiente->nombre_tarifa }}</strong>
                        ({{ $pendiente->dias }} días · {{ $pendiente->montoFormateado() }})
                        el {{ $pendiente->created_at->translatedFormat('d \d\e F \a \l\a\s H:i') }}.
                        Está pendiente de que verifiquemos el pago. En cuanto lo hagamos,
                        la campaña se activa y lo verá reflejado aquí mismo.
                    </span>
                </div>
            @else
                <h2 style="font-size:1.05rem;margin:1.75rem 0 1rem">Elija cuánto tiempo</h2>

                @if ($tarifas->isEmpty())
                    <p class="hb-muted">
                        Aún no hay tarifas publicadas. Comuníquese con la administración
                        para consultar las vigentes.
                    </p>
                @else
                    <form method="POST" action="{{ route('alojamientos.campana.solicitar', $alojamiento) }}"
                          enctype="multipart/form-data">
                        @csrf

                        <div class="hb-planes">
                            @foreach ($tarifas as $i => $tarifa)
                                <label class="hb-plan {{ $tarifa->destacada ? 'hb-plan--destacado' : '' }}">
                                    <span class="hb-plan__nombre">
                                        <input type="radio" name="tarifa_id" value="{{ $tarifa->id }}"
                                               @checked(old('tarifa_id', $tarifas->firstWhere('destacada', true)?->id ?? $tarifas->first()->id) == $tarifa->id)>
                                        <span>{{ $tarifa->nombre }}</span>
                                        @if ($tarifa->destacada)
                                            <span class="hb-badge hb-badge--success">{{ $tarifa->nota ?: 'La más pedida' }}</span>
                                        @endif
                                    </span>
                                    <span class="hb-plan__precio">{{ $tarifa->precioFormateado() }}</span>
                                    <span class="hb-plan__detalle">
                                        {{ $tarifa->dias }} días · ${{ number_format($tarifa->precioPorDia(), 0, ',', '.') }} al día
                                    </span>
                                    @if ($tarifa->nota && ! $tarifa->destacada)
                                        <span class="hb-plan__detalle">{{ $tarifa->nota }}</span>
                                    @endif
                                </label>
                            @endforeach
                        </div>
                        @error('tarifa_id') <span class="hb-error">{{ $message }}</span> @enderror

                        <span class="hb-hint" style="margin-top:.85rem">
                            El precio por día se indica para facilitar la comparación: según
                            el tiempo que estime necesario para arrendar, puede convenirle un
                            plan más largo o uno más corto.
                        </span>

                        <h2 style="font-size:1.05rem;margin:1.75rem 0 .75rem">Pague y envíe el comprobante</h2>

                        @if ($instrucciones)
                            <p style="white-space:pre-line">{{ $instrucciones }}</p>
                        @elseif ($nequi || $daviplata)
                            <div class="hb-alert hb-alert--info" style="margin-bottom:1.25rem">
                                <span class="hb-alert__icon" aria-hidden="true">$</span>
                                <span>
                                    Transfiera el valor del plan a
                                    @if ($nequi)<strong>Nequi {{ $nequi }}</strong>@endif
                                    @if ($nequi && $daviplata) o @endif
                                    @if ($daviplata)<strong>Daviplata {{ $daviplata }}</strong>@endif
                                    y adjunte abajo la captura del pago.
                                </span>
                            </div>
                        @else
                            <p class="hb-muted">
                                Escriba a <strong>{{ $correo }}</strong> para que le indiquemos
                                los datos de pago.
                            </p>
                        @endif

                        <div class="hb-field">
                            <label class="hb-label" for="comprobante">Comprobante del pago <span class="hb-req">*</span></label>
                            <input class="hb-input" id="comprobante" name="comprobante" type="file"
                                   accept="image/jpeg,image/png,image/webp,application/pdf" required>
                            <span class="hb-hint">
                                Captura o PDF, hasta 5 MB. Solo lo ve usted y el administrador:
                                se guarda fuera de la web, no en una dirección pública.
                            </span>
                            @error('comprobante') <span class="hb-error">{{ $message }}</span> @enderror
                        </div>

                        <div class="hb-fields-2">
                            <div class="hb-field">
                                <label class="hb-label" for="medio">Cómo pagó <span class="hb-req">*</span></label>
                                <select class="hb-input" id="medio" name="medio" required>
                                    @foreach (\App\Models\Campana::MEDIOS as $medio)
                                        <option value="{{ $medio }}" @selected(old('medio') === $medio)>{{ $medio }}</option>
                                    @endforeach
                                </select>
                                @error('medio') <span class="hb-error">{{ $message }}</span> @enderror
                            </div>

                            <div class="hb-field">
                                <label class="hb-label" for="referencia">Referencia del pago</label>
                                <input class="hb-input" id="referencia" name="referencia" maxlength="60"
                                       value="{{ old('referencia') }}" placeholder="Opcional">
                                <span class="hb-hint">El número del comprobante acelera la verificación.</span>
                                @error('referencia') <span class="hb-error">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="hb-field">
                            <label class="hb-label" for="nota">Algo que debamos saber</label>
                            <textarea class="hb-input" id="nota" name="nota" rows="2" maxlength="500"
                                      placeholder="Opcional">{{ old('nota') }}</textarea>
                            @error('nota') <span class="hb-error">{{ $message }}</span> @enderror
                        </div>

                        <button class="hb-btn hb-btn--primary" type="submit">Enviar solicitud</button>
                        <span class="hb-hint" style="margin-top:.6rem">
                            Verificamos el pago y activamos la campaña el mismo día. Lo verá
                            reflejado en esta misma página.
                        </span>
                    </form>
                @endif
            @endif

            @if ($ultimas->isNotEmpty())
                {{-- El historial. Importa sobre todo cuando una solicitud se
                     rechaza: el motivo tiene que llegarle a quien la pidió, o
                     escribirá para preguntar qué pasó. --}}
                <h2 style="font-size:1.05rem;margin:2rem 0 .75rem">Sus solicitudes</h2>

                <div class="hb-table-wrap">
                    <table class="hb-table">
                        <thead>
                            <tr><th>Plan</th><th>Valor</th><th>Estado</th><th>Fecha</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($ultimas as $s)
                                <tr>
                                    <td>{{ $s->nombre_tarifa }} · {{ $s->dias }} días</td>
                                    <td>{{ $s->montoFormateado() }}</td>
                                    <td>
                                        {{ $s->etiquetaEstado() }}
                                        @if ($s->respuesta)
                                            <div class="hb-muted hb-small">{{ $s->respuesta }}</div>
                                        @endif
                                    </td>
                                    <td class="hb-muted hb-small">
                                        {{ $s->created_at->translatedFormat('d \d\e F') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <p class="hb-hint" style="margin-top:1.25rem">
                <strong>Recomendación sobre el momento de contratarla:</strong> la
                búsqueda de vivienda se concentra en <strong>enero</strong> y entre
                <strong>julio y agosto</strong>, al inicio de cada semestre. Fuera de
                esos periodos el tránsito por el buscador disminuye y el rendimiento
                de la campaña es considerablemente menor.
            </p>

        </div>
        <div class="hb-card__footer">
            <a class="hb-btn hb-btn--ghost hb-btn--sm" href="{{ route('alojamientos.show', $alojamiento) }}">
                ← Volver a la publicación
            </a>
        </div>
    </div>

</div>
@endsection
