{{--
    Cuerpo del informe. Lo usan TANTO la pantalla como el PDF.

    Está compartido a propósito: si cada uno hiciera sus propias consultas
    acabarían enseñando números distintos y el informe dejaría de servir.

    Las gráficas son divs con ancho en porcentaje, no una librería de
    JavaScript: dompdf no ejecuta JS y el PDF saldría en blanco.
--}}

@php
    $u = $datos->usuarios();
    $p = $datos->publicaciones();
    $a = $datos->actividad();
@endphp

<h2 class="hb-inf__h2">Usuarios</h2>
<table class="hb-inf__tabla">
    <tr><td>Registrados en total</td><td><strong>{{ number_format($u['total']) }}</strong></td></tr>
    <tr><td>Nuevos en el periodo</td><td><strong>{{ number_format($u['nuevos']) }}</strong></td></tr>
    <tr><td>Estudiantes</td><td>{{ number_format($u['estudiantes']) }}</td></tr>
    <tr><td>Arrendadores</td><td>{{ number_format($u['arrendadores']) }}</td></tr>
    <tr><td>Con verificación aprobada</td><td>{{ number_format($u['verificados']) }}</td></tr>
</table>

<h2 class="hb-inf__h2">Publicaciones</h2>
<table class="hb-inf__tabla">
    <tr><td>Publicadas en total</td><td><strong>{{ number_format($p['total']) }}</strong></td></tr>
    <tr><td>Nuevas en el periodo</td><td><strong>{{ number_format($p['nuevas']) }}</strong></td></tr>
    <tr><td>Visibles en el buscador</td><td>{{ number_format($p['activas']) }}</td></tr>
    <tr><td>Esperando revisión</td><td>{{ number_format($p['pendientes']) }}</td></tr>
    <tr><td>Rechazadas</td><td>{{ number_format($p['rechazadas']) }}</td></tr>
    <tr><td>Ya arrendadas (sin plazas)</td><td>{{ number_format($p['arrendadas']) }}</td></tr>
</table>

<h2 class="hb-inf__h2">Actividad en el periodo</h2>
<table class="hb-inf__tabla">
    <tr><td>Reseñas escritas</td><td><strong>{{ number_format($a['resenas']) }}</strong></td></tr>
    <tr><td>Calificación media de la plataforma</td><td>{{ $a['promedio'] }} / 5</td></tr>
    <tr><td>Conversaciones abiertas</td><td>{{ number_format($a['conversaciones']) }}</td></tr>
    <tr><td>Acuerdos cerrados</td><td><strong>{{ number_format($a['acuerdos']) }}</strong></td></tr>
    <tr><td>Teléfonos consultados</td><td>{{ number_format($a['telefonos']) }}</td></tr>
</table>

@php($serieUsuarios = $datos->registrosPorDia())
@php($maxUsuarios = max(1, $serieUsuarios->max()))

<h2 class="hb-inf__h2">Registros por día</h2>
@if ($serieUsuarios->sum() === 0)
    <p class="hb-inf__vacio">No hubo registros nuevos en este periodo.</p>
@else
    <table class="hb-inf__barras">
        @foreach ($serieUsuarios as $dia => $total)
            @continue($total === 0 && $serieUsuarios->count() > 14)
            <tr>
                <td class="hb-inf__etiqueta">{{ \Carbon\Carbon::parse($dia)->format('d/m') }}</td>
                <td class="hb-inf__pista">
                    <span class="hb-inf__barra" style="width:{{ max(2, round($total / $maxUsuarios * 100)) }}%"></span>
                </td>
                <td class="hb-inf__valor">{{ $total }}</td>
            </tr>
        @endforeach
    </table>
@endif

@php($barrios = $datos->porBarrio())

<h2 class="hb-inf__h2">Oferta por barrio</h2>
@if ($barrios->isEmpty())
    <p class="hb-inf__vacio">No hay publicaciones visibles con barrio indicado.</p>
@else
    @php($maxBarrio = max(1, $barrios->max()))
    <table class="hb-inf__barras">
        @foreach ($barrios as $barrio => $total)
            <tr>
                <td class="hb-inf__etiqueta">{{ $barrio }}</td>
                <td class="hb-inf__pista">
                    <span class="hb-inf__barra" style="width:{{ round($total / $maxBarrio * 100) }}%"></span>
                </td>
                <td class="hb-inf__valor">{{ $total }}</td>
            </tr>
        @endforeach
    </table>
@endif

@php($precios = $datos->precioMedioPorTipo())

<h2 class="hb-inf__h2">Precio medio por tipo (arriendo mensual)</h2>
@if ($precios->isEmpty())
    <p class="hb-inf__vacio">No hay publicaciones mensuales visibles.</p>
@else
    @php($maxPrecio = max(1, $precios->max()))
    <table class="hb-inf__barras">
        @foreach ($precios as $tipo => $medio)
            <tr>
                <td class="hb-inf__etiqueta">{{ $tipo }}</td>
                <td class="hb-inf__pista">
                    <span class="hb-inf__barra" style="width:{{ round($medio / $maxPrecio * 100) }}%"></span>
                </td>
                <td class="hb-inf__valor">${{ number_format($medio, 0, ',', '.') }}</td>
            </tr>
        @endforeach
    </table>
@endif
