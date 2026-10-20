@extends('layouts.app')

@section('titulo', 'Destacar · ' . $alojamiento->title)

@section('contenido')
<div style="max-width:720px;margin-inline:auto">

    <div class="hb-card">
        <div class="hb-card__header">
            <h1 style="font-size:1.25rem">Destacar «{{ \Illuminate\Support\Str::limit($alojamiento->title, 40) }}»</h1>
        </div>
        <div class="hb-card__body">

            @if ($alojamiento->estaDestacado())
                <div class="hb-alert hb-alert--success" style="margin-bottom:1.25rem">
                    <span class="hb-alert__icon" aria-hidden="true">★</span>
                    <span>
                        <strong>Tu publicación ya está destacada.</strong>
                        Le quedan {{ $alojamiento->diasDestacadoRestantes() }} día(s),
                        hasta el {{ $alojamiento->destacado_hasta->translatedFormat('d \d\e F') }}.
                        Si renuevas antes, los días nuevos se suman a los que te quedan.
                    </span>
                </div>
            @endif

            <p>
                Una publicación destacada <strong>aparece de primera en el buscador</strong>
                y lleva una etiqueta que la distingue.
            </p>

            {{-- Decir también lo que NO hace. Prometer de más es lo que
                 hace que nadie renueve. --}}
            <div class="hb-alert hb-alert--info" style="margin:1.25rem 0">
                <span class="hb-alert__icon" aria-hidden="true">i</span>
                <span>
                    <strong>Lo que no hace, para que no te lleves un chasco:</strong>
                    si el estudiante ordena por precio o busca por cercanía a la
                    universidad, manda lo que él eligió, no el destaque. Y no te
                    garantiza arrendar: te da visibilidad, el resto lo hacen tus
                    fotos, tu precio y lo rápido que contestes el chat.
                </span>
            </div>

            <h2 style="font-size:1.05rem;margin:1.75rem 0 1rem">Planes</h2>

            <div class="hb-stats">
                @foreach ($planes as $plan)
                    <div class="hb-stat">
                        <div class="hb-stat__label">
                            {{ $plan['etiqueta'] }}
                            @if ($plan['recomendado'] ?? false)
                                <span class="hb-badge hb-badge--success">El más pedido</span>
                            @endif
                        </div>
                        <div class="hb-stat__value">${{ number_format($plan['precio'], 0, ',', '.') }}</div>
                        <div class="hb-muted hb-small">{{ $plan['dias'] }} días</div>
                    </div>
                @endforeach
            </div>

            <h2 style="font-size:1.05rem;margin:1.75rem 0 1rem">Cómo se pide</h2>

            <ol style="padding-left:1.1rem;line-height:1.8">
                <li>Escríbenos @if ($contacto['correo'])a <strong>{{ $contacto['correo'] }}</strong>@else por los datos del pie de página @endif
                    diciendo cuál publicación y qué plan quieres.</li>
                <li>Te confirmamos el valor y haces el pago
                    @if ($contacto['nequi'])
                        por <strong>Nequi al {{ $contacto['nequi'] }}</strong>.
                    @else
                        por Nequi, Daviplata o transferencia.
                    @endif
                </li>
                <li>Nos pasas el comprobante y te lo activamos. Queda andando el mismo día.</li>
            </ol>

            <p class="hb-hint" style="margin-top:1.25rem">
                <strong>Consejo honesto sobre cuándo pagarlo:</strong> los estudiantes
                buscan vivienda en <strong>enero</strong> y entre <strong>julio y agosto</strong>,
                cuando arrancan los semestres. Fuera de esas semanas pasa muy poca
                gente por el buscador y destacar rinde bastante menos. Guarda la plata
                para la temporada.
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
