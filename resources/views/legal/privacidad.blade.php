@extends('layouts.app')

@section('titulo', 'Política de privacidad')

@section('cabecera')
    <div>
        <h1>Política de privacidad</h1>
        <p>Cómo tratamos tus datos personales · Actualizada el {{ now()->translatedFormat('d \d\e F \d\e Y') }}</p>
    </div>
@endsection

@section('contenido')
<div class="hb-card" style="max-width:820px;margin-inline:auto">
    <div class="hb-card__body" style="line-height:1.75">

        <p class="hb-muted">
            Esta política se rige por la <strong>Ley 1581 de 2012</strong> y el
            <strong>Decreto 1377 de 2013</strong> de Colombia, sobre protección
            de datos personales.
        </p>

        <h2 style="font-size:1.15rem;margin-top:2rem">1. Quién trata tus datos</h2>
        <p>
            HABBI es el responsable del tratamiento. Para cualquier asunto
            relacionado con tus datos puedes escribir a
            @if ($correo = config('habbi.contacto.email'))
                <a href="mailto:{{ $correo }}">{{ $correo }}</a>.
            @else
                la dirección de contacto que aparece en el pie de página.
            @endif
        </p>

        <h2 style="font-size:1.15rem;margin-top:2rem">2. Qué datos recogemos</h2>
        <table class="hb-table" style="margin:1rem 0">
            <thead><tr><th>Dato</th><th>Para qué</th><th>Quién lo ve</th></tr></thead>
            <tbody>
                <tr><td>Nombre y correo</td><td>Identificarte y comunicarnos contigo</td><td>Tú y el equipo de HABBI</td></tr>
                <tr><td>Contraseña</td><td>Acceder a tu cuenta</td><td><strong>Nadie.</strong> Se guarda cifrada y es irreversible</td></tr>
                <tr><td>Teléfono</td><td>Que los estudiantes contacten contigo</td><td>Usuarios con sesión iniciada</td></tr>
                <tr><td>Foto de perfil y biografía</td><td>Dar confianza a quien te contacta</td><td>Cualquier visitante</td></tr>
                <tr><td>Documento de identidad</td><td>Verificar que eres quien dices</td><td><strong>Solo el equipo de HABBI</strong></td></tr>
                <tr><td>Prueba del inmueble</td><td>Verificar que la vivienda existe</td><td><strong>Solo el equipo de HABBI</strong></td></tr>
                <tr><td>Publicaciones y reseñas</td><td>El servicio en sí</td><td>Cualquier visitante</td></tr>
            </tbody>
        </table>

        <h2 style="font-size:1.15rem;margin-top:2rem">3. Los documentos de verificación</h2>
        <p>
            Tu cédula y la prueba del inmueble reciben un trato distinto al resto:
        </p>
        <ul>
            <li>Se guardan <strong>fuera de la carpeta pública</strong> del servidor. No existe ninguna dirección web que lleve a ellos.</li>
            <li>Solo se entregan tras comprobar que quien los pide es un administrador o tú mismo.</li>
            <li>Se usan <strong>únicamente</strong> para verificar tu identidad. No se comparten con nadie ni se usan para otra cosa.</li>
            <li>Puedes pedir que los borremos en cuanto tu verificación esté resuelta.</li>
        </ul>

        <h2 style="font-size:1.15rem;margin-top:2rem">4. Tus derechos</h2>
        <p>En cualquier momento puedes:</p>
        <ul>
            <li><strong>Conocer</strong> qué datos tuyos tenemos.</li>
            <li><strong>Actualizar</strong> o <strong>rectificar</strong> los que estén mal, desde tu perfil o escribiéndonos.</li>
            <li><strong>Suprimir</strong> tus datos y tu cuenta.</li>
            <li><strong>Revocar</strong> la autorización que nos diste.</li>
            <li><strong>Presentar una queja</strong> ante la Superintendencia de Industria y Comercio.</li>
        </ul>
        <p>Respondemos a estas solicitudes en un plazo máximo de 15 días hábiles.</p>

        <h2 style="font-size:1.15rem;margin-top:2rem">5. Qué no hacemos</h2>
        <ul>
            <li>No vendemos ni cedemos tus datos a terceros.</li>
            <li>No publicamos tu teléfono a quien no haya iniciado sesión.</li>
            <li>No podemos leer tu contraseña: técnicamente es imposible.</li>
            <li>No usamos tus documentos para nada que no sea la verificación.</li>
        </ul>

        <h2 style="font-size:1.15rem;margin-top:2rem">6. Cuánto conservamos cada cosa</h2>
        <p>
            Los datos de tu cuenta, mientras la mantengas abierta.
        </p>
        <p>
            <strong>Tu cédula y la prueba del inmueble se borran solos</strong> en
            cuanto aprobamos o rechazamos tu solicitud. No se archivan ni se
            guardan «por si acaso»: se piden para comprobar una cosa concreta y,
            una vez comprobada, conservarlos solo añadiría riesgo para ti. Lo
            único que queda es la anotación de quién revisó, cuándo y qué
            decidió.
        </p>
        <p>
            Si cierras tu cuenta, borramos todo salvo lo que la ley nos obligue
            a conservar.
        </p>

    </div>
</div>
@endsection
