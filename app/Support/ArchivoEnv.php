<?php

namespace App\Support;

/**
 * Escribe variables en el archivo .env sin romperlo.
 *
 * Usar sed o str_replace para esto es frágil: una contraseña con /, &, $ o #
 * rompe el comando o corrompe el archivo. Aquí el valor se trata siempre
 * como dato y se entrecomilla cuando hace falta.
 *
 * Nota: deploy/env-set.php hace lo mismo en un script suelto, a propósito.
 * Ese corre ANTES de «composer install», cuando todavía no existe el
 * autoload y esta clase no se puede cargar.
 */
class ArchivoEnv
{
    public function __construct(private readonly string $ruta)
    {
    }

    public static function enLaRaiz(): self
    {
        return new self(base_path('.env'));
    }

    public function existe(): bool
    {
        return is_file($this->ruta);
    }

    /** @param  array<string, string|null>  $valores */
    public function escribirVarias(array $valores): void
    {
        foreach ($valores as $clave => $valor) {
            $this->escribir($clave, $valor);
        }
    }

    public function escribir(string $clave, ?string $valor): void
    {
        if (! preg_match('/^[A-Z_][A-Z0-9_]*$/', $clave)) {
            throw new \InvalidArgumentException("Clave de .env no válida: {$clave}");
        }

        if (! $this->existe()) {
            throw new \RuntimeException("No existe el archivo {$this->ruta}");
        }

        $linea     = $clave . '=' . $this->formatear((string) $valor);
        $contenido = file_get_contents($this->ruta);
        $patron    = '/^' . preg_quote($clave, '/') . '=.*$/m';

        // Callback para que $ y \ del valor no se lean como retrorreferencias.
        $contenido = preg_match($patron, $contenido)
            ? preg_replace_callback($patron, fn () => $linea, $contenido, 1)
            : rtrim($contenido, "\n") . "\n" . $linea . "\n";

        file_put_contents($this->ruta, $contenido);
    }

    public function leer(string $clave): ?string
    {
        if (! $this->existe()) {
            return null;
        }

        $patron = '/^' . preg_quote($clave, '/') . '=(.*)$/m';

        if (! preg_match($patron, file_get_contents($this->ruta), $m)) {
            return null;
        }

        $valor = trim($m[1]);

        // Quitar comillas y deshacer los escapes que pusimos al escribir.
        if (strlen($valor) >= 2 && $valor[0] === '"' && str_ends_with($valor, '"')) {
            $valor = str_replace(['\\"', '\\$', '\\\\'], ['"', '$', '\\'], substr($valor, 1, -1));
        }

        return $valor;
    }

    private function formatear(string $valor): string
    {
        $necesitaComillas = $valor === '' || preg_match('/[\s#"\'\\\\$]/', $valor) === 1;

        return $necesitaComillas
            ? '"' . str_replace(['\\', '"', '$'], ['\\\\', '\\"', '\\$'], $valor) . '"'
            : $valor;
    }
}
