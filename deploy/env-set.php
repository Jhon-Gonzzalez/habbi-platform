<?php
/**
 * Escribe una variable en el archivo .env de forma segura.
 *
 *   php deploy/env-set.php CLAVE "valor"
 *
 * Usar sed para esto es frágil: una contraseña con /, &, | o # rompe el
 * comando o corrompe el archivo. Aquí el valor se trata como dato, y se
 * entrecomilla cuando contiene espacios o caracteres especiales.
 */

$rutaEnv = dirname(__DIR__) . '/.env';

if ($argc < 3) {
    fwrite(STDERR, "Uso: php deploy/env-set.php CLAVE VALOR\n");
    exit(1);
}

[$clave, $valor] = [$argv[1], $argv[2]];

if (!preg_match('/^[A-Z_][A-Z0-9_]*$/', $clave)) {
    fwrite(STDERR, "Clave no válida: {$clave}\n");
    exit(1);
}

if (!is_file($rutaEnv)) {
    fwrite(STDERR, "No existe el archivo .env\n");
    exit(1);
}

// Entrecomillar si el valor tiene espacios, almohadillas, comillas o está vacío.
$necesitaComillas = $valor === '' || preg_match('/[\s#"\'\\\\$]/', $valor) === 1;
$formateado = $necesitaComillas
    ? '"' . str_replace(['\\', '"', '$'], ['\\\\', '\\"', '\\$'], $valor) . '"'
    : $valor;

$linea      = $clave . '=' . $formateado;
$contenido  = file_get_contents($rutaEnv);
$patron     = '/^' . preg_quote($clave, '/') . '=.*$/m';

// Se usa un callback para que $ y \ del valor no se interpreten como retrorreferencias.
$contenido = preg_match($patron, $contenido)
    ? preg_replace_callback($patron, fn () => $linea, $contenido, 1)
    : rtrim($contenido, "\n") . "\n" . $linea . "\n";

file_put_contents($rutaEnv, $contenido);
