{{--
    Página independiente, sin el layout, a propósito.

    Un error 500 puede venir justo de que la base de datos está caída. El
    layout normal pinta el navbar, que consulta la sesión y el usuario: si
    se usara aquí, la página de error fallaría también y el visitante
    acabaría viendo la pantalla en blanco de PHP.

    Por eso aquí no hay @auth, ni session(), ni rutas con nombre.
--}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Algo se rompió de nuestro lado · HABBI</title>
    <link rel="icon" href="/assets/img/images/Icono.png" type="image/png">
    <link rel="stylesheet" href="/assets/css/habbi.css">
</head>
<body>
<main class="hb-container hb-page" style="max-width:620px">

    <div class="hb-card">
        <div class="hb-card__body hb-center">
            <div style="font-size:3rem;line-height:1" aria-hidden="true">⚠</div>

            <h1 style="margin-top:1rem;font-size:1.5rem">Algo se rompió de nuestro lado</h1>

            <p class="hb-muted">No es culpa tuya y no hemos perdido nada de lo que ya estaba guardado. El fallo quedó anotado y lo vamos a revisar.</p>

            <p class="hb-muted hb-small">Si estabas escribiendo algo largo, cópialo antes de recargar.</p>

            <div class="hb-row hb-center" style="gap:.6rem;margin-top:1.5rem">
                <a class="hb-btn hb-btn--primary" href="/">Ir al inicio</a>
            </div>
        </div>
    </div>

</main>
</body>
</html>
