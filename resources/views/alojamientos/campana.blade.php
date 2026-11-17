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

            <h2 style="font-size:1.05rem;margin:1.75rem 0 1rem">Elija cuánto tiempo</h2>

            @if ($tarifas->isEmpty())
                <p class="hb-muted">
                    Aún no hay tarifas publicadas. Comuníquese con la administración
                    para consultar las vigentes.
                </p>
            @else
                <div class="hb-planes">
                    @foreach ($tarifas as $tarifa)
                        <div class="hb-plan {{ $tarifa->destacada ? 'hb-plan--destacado' : '' }}">
                            <div class="hb-plan__nombre">
                                <span>{{ $tarifa->nombre }}</span>
                                @if ($tarifa->destacada)
                                    <span class="hb-badge hb-badge--success">{{ $tarifa->nota ?: 'La más pedida' }}</span>
                                @endif
                            </div>
                            <div class="hb-plan__precio">{{ $tarifa->precioFormateado() }}</div>
                            <div class="hb-plan__detalle">
                                {{ $tarifa->dias }} días · ${{ number_format($tarifa->precioPorDia(), 0, ',', '.') }} al día
                            </div>
                            @if ($tarifa->nota && ! $tarifa->destacada)
                                <div class="hb-plan__detalle">{{ $tarifa->nota }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>

                <span class="hb-hint" style="margin-top:.85rem">
                    El precio por día se indica para facilitar la comparación: según
                    el tiempo que estime necesario para arrendar, puede convenirle un
                    plan más largo o uno más corto.
                </span>
            @endif

            <h2 style="font-size:1.05rem;margin:1.75rem 0 1rem">Cómo contratarla</h2>

            @if ($instrucciones)
                <p style="white-space:pre-line">{{ $instrucciones }}</p>
            @else
                <ol style="padding-left:1.1rem;line-height:1.8">
                    <li>
                        Solicítela
                        @if ($correo)escribiendo a <strong>{{ $correo }}</strong>@else a través de los datos de contacto del pie de página @endif
                        e indique la publicación y el número de días.
                    </li>
                    <li>
                        Realice el pago
                        @if ($nequi || $daviplata)
                            @if ($nequi) por <strong>Nequi al {{ $nequi }}</strong>@endif
                            @if ($nequi && $daviplata) o @endif
                            @if ($daviplata) por <strong>Daviplata al {{ $daviplata }}</strong>@endif.
                        @else
                            por Nequi, Daviplata o transferencia bancaria; los datos
                            se le enviarán en la respuesta.
                        @endif
                    </li>
                    <li>Envíe el comprobante de pago. La campaña se activa el mismo día.</li>
                </ol>
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
