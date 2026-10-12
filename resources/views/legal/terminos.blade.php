@extends('layouts.app')

@section('titulo', 'Términos de uso')

@section('cabecera')
    <div>
        <h1>Términos de uso</h1>
        <p>Las reglas de la plataforma · Actualizados el {{ now()->translatedFormat('d \d\e F \d\e Y') }}</p>
    </div>
@endsection

@section('contenido')
<div class="hb-card" style="max-width:820px;margin-inline:auto">
    <div class="hb-card__body" style="line-height:1.75">

        <h2 style="font-size:1.15rem">1. Qué es HABBI</h2>
        <p>
            Una plataforma donde arrendadores publican alojamiento para estudiantes
            y los estudiantes lo buscan y lo valoran. <strong>HABBI no participa en
            el contrato de arriendo</strong>: no cobra, no media y no es parte del
            acuerdo al que lleguen las partes.
        </p>

        <h2 style="font-size:1.15rem;margin-top:2rem">2. Tu cuenta</h2>
        <ul>
            <li>Los datos que registres deben ser verdaderos.</li>
            <li>Eres responsable de lo que ocurra con tu cuenta. No la compartas.</li>
            <li>Una persona, una cuenta.</li>
        </ul>

        <h2 style="font-size:1.15rem;margin-top:2rem">3. Publicar</h2>
        <p>Para publicar hay que verificarse como arrendador. Al publicar te comprometes a que:</p>
        <ul>
            <li>El alojamiento <strong>existe</strong> y está disponible.</li>
            <li>Las fotos <strong>son del inmueble real</strong>, no de otro ni de catálogo.</li>
            <li>El precio y las condiciones son los que de verdad vas a cobrar.</li>
            <li>Tienes derecho a arrendarlo.</li>
        </ul>
        <p>Publicar algo falso es motivo de retirada inmediata y de pérdida de la verificación.</p>

        <h2 style="font-size:1.15rem;margin-top:2rem">4. Las reseñas</h2>
        <ul>
            <li>Solo puedes reseñar alojamientos que no sean tuyos.</li>
            <li>Una reseña por persona y alojamiento.</li>
            <li>Escribe sobre tu experiencia real. Nada de insultos ni datos personales de terceros.</li>
            <li>Retiramos las reseñas que incumplan esto, pero <strong>no por ser negativas</strong>: una mala experiencia contada con respeto se queda.</li>
        </ul>

        <h2 style="font-size:1.15rem;margin-top:2rem">5. Qué no se permite</h2>
        <ul>
            <li>Suplantar a otra persona o a un negocio.</li>
            <li>Publicar datos de contacto de terceros sin su permiso.</li>
            <li>Pedir dinero por adelantado antes de que el estudiante vea el inmueble.</li>
            <li>Discriminar por origen, género, religión, orientación o condición alguna.</li>
        </ul>

        <h2 style="font-size:1.15rem;margin-top:2rem">6. Nuestra responsabilidad</h2>
        <p>
            Verificamos la identidad de los arrendadores y que el inmueble existe,
            pero <strong>eso no garantiza nada sobre el estado de la vivienda ni
            sobre el trato que recibas</strong>. Antes de pagar nada, visita el
            lugar en persona. Si algo va mal, repórtalo y lo revisamos.
        </p>

        <h2 style="font-size:1.15rem;margin-top:2rem">7. Cierre de cuentas</h2>
        <p>
            Podemos suspender una cuenta que incumpla estos términos. Tú puedes
            cerrar la tuya cuando quieras.
        </p>

    </div>
</div>
@endsection
