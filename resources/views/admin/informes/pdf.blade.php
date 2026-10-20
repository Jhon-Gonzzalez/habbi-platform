{{--
    Versión para PDF. Documento suelto, sin el layout del panel.

    Los estilos van aquí dentro y no en la hoja del sitio porque dompdf no
    descarga archivos externos: si la hoja estuviera fuera, el PDF saldría
    sin ningún formato. Son los mismos colores de la marca, escritos a mano.
--}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $datos->titulo() }}</title>
    <style>
        @page { margin: 2cm 1.8cm; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10pt;
            color: #1A2233;
            line-height: 1.45;
        }

        .cabecera {
            border-bottom: 3px solid #2091F9;
            padding-bottom: .6rem;
            margin-bottom: 1.4rem;
        }

        .cabecera h1 { font-size: 16pt; margin: 0 0 .2rem; color: #001A49; }
        .cabecera p  { margin: 0; font-size: 9pt; color: #667085; }

        .hb-inf__h2 {
            font-size: 11.5pt;
            color: #0B6FD0;
            margin: 1.3rem 0 .4rem;
            border-bottom: 1px solid #E3E8F0;
            padding-bottom: .2rem;
        }

        .hb-inf__tabla { width: 100%; border-collapse: collapse; }
        .hb-inf__tabla td { padding: .28rem 0; border-bottom: 1px solid #EEF1F6; }
        .hb-inf__tabla td:last-child { text-align: right; white-space: nowrap; }

        .hb-inf__barras { width: 100%; border-collapse: collapse; }
        .hb-inf__barras td { padding: .18rem 0; vertical-align: middle; }

        .hb-inf__etiqueta { width: 28%; font-size: 9pt; color: #667085; }
        .hb-inf__pista    { width: 57%; }
        .hb-inf__valor    { width: 15%; text-align: right; font-size: 9pt; white-space: nowrap; }

        /* La barra es un span con ancho en porcentaje: dompdf lo pinta sin
           problema, y una librería de JavaScript no la habría pintado. */
        .hb-inf__barra {
            display: block;
            height: 11px;
            background: #2091F9;
            border-radius: 3px;
        }

        .hb-inf__vacio { color: #667085; font-style: italic; font-size: 9pt; }

        .pie {
            margin-top: 2rem;
            padding-top: .5rem;
            border-top: 1px solid #E3E8F0;
            font-size: 8pt;
            color: #98A2B3;
        }
    </style>
</head>
<body>

<div class="cabecera">
    <h1>HABBI · Informe de gestión</h1>
    <p>
        Periodo del {{ $datos->desde->format('d/m/Y') }}
        al {{ $datos->hasta->format('d/m/Y') }}
        ({{ $datos->dias() }} días)
        · generado el {{ now()->format('d/m/Y \a \l\a\s H:i') }}
    </p>
</div>

@include('admin.informes._cuerpo', ['datos' => $datos])

<div class="pie">
    Plataforma de vivienda estudiantil HABBI · Universidad de Córdoba, Montería.
    Las cifras corresponden al momento en que se generó el documento.
</div>

</body>
</html>
