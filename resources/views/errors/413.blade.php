{{--
    Página independiente: cuando PHP rechaza la petición por tamaño, el
    middleware ValidatePostSize corta ANTES de que arranque la sesión, así
    que aquí no se pueden usar @auth, session() ni el layout habitual.
--}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Las fotos pesan demasiado · HABBI</title>
    <link rel="icon" href="{{ asset('assets/img/images/Icono.png') }}" type="image/png">
    <link rel="stylesheet" href="{{ asset('assets/css/habbi.css') }}">
</head>
<body>
<main class="hb-container hb-page" style="max-width:640px">

    <div class="hb-card">
        <div class="hb-card__body hb-center">

            <div style="font-size:3rem;line-height:1" aria-hidden="true">🖼</div>

            <h1 style="font-size:1.6rem;margin-top:1rem">Las fotos pesan demasiado</h1>

            <p class="hb-muted">
                El servidor solo admite
                <strong>{{ \App\Support\Subidas::formatear(\App\Support\Subidas::maxPost()) }}</strong>
                por formulario, y lo que intentaste subir lo supera.
            </p>

            <div class="hb-alert hb-alert--info" style="text-align:left;margin-top:1.5rem">
                <span class="hb-alert__icon">i</span>
                <div>
                    <strong>Cómo solucionarlo:</strong>
                    <ul>
                        <li>Sube menos fotos de una vez: publica con 2 o 3 y añade el resto editando.</li>
                        <li>Reduce el tamaño de las imágenes antes de subirlas.</li>
                        <li>Cada archivo debe pesar menos de
                            <strong>{{ \App\Support\Subidas::formatear(\App\Support\Subidas::maxArchivo()) }}</strong>.</li>
                    </ul>
                </div>
            </div>

            <p style="margin-top:1.5rem">
                <a class="hb-btn hb-btn--primary" href="javascript:history.back()">Volver e intentarlo de nuevo</a>
            </p>

            <p class="hb-small hb-muted hb-mb-0" style="margin-top:1rem">
                Nada se perdió: tu publicación no llegó a crearse.
            </p>

        </div>
    </div>

</main>
</body>
</html>
