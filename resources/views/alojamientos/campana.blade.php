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

            @if ($alojamiento->estaDestacado())
                <div class="hb-alert hb-alert--success" style="margin-bottom:1.25rem">
                    <span class="hb-alert__icon" aria-hidden="true">★</span>
                    <span>
                        <strong>Tu campaña está activa.</strong>
                        Le quedan {{ $alojamiento->diasDestacadoRestantes() }} día(s),
                        hasta el {{ $alojamiento->campana_hasta->translatedFormat('d \d\e F') }}.
                        Si renuevas antes, los días nuevos se suman a los que te quedan.
                    </span>
                </div>
            @endif

            <p>
                Durante la campaña, tu publicación <strong>aparece de primera en el
                buscador</strong> y lleva una etiqueta que la distingue.
                <strong>Tú eliges cuántos días</strong>: no hay suscripción ni pago
                mensual, porque no tendría sentido pagar meses en los que nadie está
                buscando vivienda.
            </p>

            {{-- Decir también lo que NO hace. Prometer de más es lo que
                 hace que nadie renueve. --}}
            <div class="hb-alert hb-alert--info" style="margin:1.25rem 0">
                <span class="hb-alert__icon" aria-hidden="true">i</span>
                <span>
                    <strong>Lo que no hace, para que no te lleves un chasco:</strong>
                    si el estudiante ordena por precio o busca por cercanía a la
                    universidad, manda lo que él eligió, no la campaña. Y no te
                    garantiza arrendar: te da visibilidad, el resto lo hacen tus
                    fotos, tu precio y lo rápido que contestes el chat.
                </span>
            </div>

            <h2 style="font-size:1.05rem;margin:1.75rem 0 1rem">Elige cuánto tiempo</h2>

            @if ($tarifas->isEmpty())
                <p class="hb-muted">
                    Todavía no hay tarifas publicadas. Escríbenos y te decimos.
                </p>
            @else
                <div class="hb-stats">
                    @foreach ($tarifas as $tarifa)
                        <div class="hb-stat">
                            <div class="hb-stat__label">
                                {{ $tarifa->nombre }}
                                @if ($tarifa->destacada)
                                    <span class="hb-badge hb-badge--success">{{ $tarifa->nota ?: 'La más pedida' }}</span>
                                @endif
                            </div>
                            <div class="hb-stat__value">{{ $tarifa->precioFormateado() }}</div>
                            <div class="hb-muted hb-small">
                                {{ $tarifa->dias }} días
                                · ${{ number_format($tarifa->precioPorDia(), 0, ',', '.') }} al día
                            </div>
                            @if ($tarifa->nota && ! $tarifa->destacada)
                                <div class="hb-muted hb-small">{{ $tarifa->nota }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>

                <span class="hb-hint" style="margin-top:.85rem">
                    El precio por día está ahí para que puedas comparar: a veces el
                    plan largo sale mejor y a veces no te conviene, según cuánto
                    tiempo creas que vas a tardar en arrendar.
                </span>
            @endif

            <h2 style="font-size:1.05rem;margin:1.75rem 0 1rem">Cómo se pide</h2>

            @if ($instrucciones)
                <p style="white-space:pre-line">{{ $instrucciones }}</p>
            @else
                <ol style="padding-left:1.1rem;line-height:1.8">
                    <li>
                        Escríbenos
                        @if ($correo)a <strong>{{ $correo }}</strong>@else por los datos del pie de página @endif
                        diciendo cuál publicación y cuántos días quieres.
                    </li>
                    <li>
                        Haces el pago
                        @if ($nequi || $daviplata)
                            @if ($nequi) por <strong>Nequi al {{ $nequi }}</strong>@endif
                            @if ($nequi && $daviplata) o @endif
                            @if ($daviplata) por <strong>Daviplata al {{ $daviplata }}</strong>@endif.
                        @else
                            por Nequi, Daviplata o transferencia; te pasamos los datos al responderte.
                        @endif
                    </li>
                    <li>Nos mandas el comprobante y te la activamos. Queda andando el mismo día.</li>
                </ol>
            @endif

            <p class="hb-hint" style="margin-top:1.25rem">
                <strong>Consejo honesto sobre cuándo pagarla:</strong> los estudiantes
                buscan vivienda en <strong>enero</strong> y entre <strong>julio y agosto</strong>,
                cuando arrancan los semestres. Fuera de esas semanas pasa muy poca
                gente por el buscador y una campaña rinde bastante menos. Guarda la
                plata para la temporada.
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
