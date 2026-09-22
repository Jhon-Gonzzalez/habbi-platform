#!/usr/bin/env bash
#
# Localiza un binario de PHP compatible con el proyecto.
# No se ejecuta directamente: los demás scripts hacen `source` de este.
#
#   source deploy/buscar-php.sh
#   buscar_php pdo_sqlite     # deja la ruta en $PHP, o devuelve 1
#
# Rango admitido: lo marca Laravel 10 por abajo (^8.1) y la dependencia
# nette/schema por arriba ("8.1 - 8.4"). Actualízalo si se sube Laravel.

PHP_MIN=${PHP_MIN:-80100}
PHP_MAX=${PHP_MAX:-80500}   # exclusivo

# Rellenadas por buscar_php para que quien llame pueda dar un mensaje útil.
PHP_DESCARTADOS_VERSION=""
PHP_DESCARTADOS_EXTENSION=""

buscar_php() {
    local extension="${1:-}"
    local candidatos=() patron ruta candidato version

    [ -n "${PHP_BIN:-}" ] && candidatos+=("$PHP_BIN")
    candidatos+=("$(command -v php || true)")

    for patron in \
        "$HOME/Library/Application Support/Herd/bin/php" \
        "$HOME/Library/Application Support/Herd/config/php/8*/bin/php" \
        '/opt/homebrew/opt/php@8.[1-9]/bin/php' \
        '/opt/homebrew/bin/php' \
        '/usr/local/opt/php@8.[1-9]/bin/php' \
        '/usr/local/bin/php' \
        '/opt/alt/php8[0-9]/usr/bin/php' \
        '/opt/cpanel/ea-php8[0-9]/root/usr/bin/php' \
        '/usr/local/bin/ea-php8[0-9]'
    do
        while IFS= read -r ruta; do
            [ -n "$ruta" ] && candidatos+=("$ruta")
        done < <(compgen -G "$patron" 2>/dev/null | sort -rV)
    done

    PHP=""
    PHP_DESCARTADOS_VERSION=""
    PHP_DESCARTADOS_EXTENSION=""

    for candidato in "${candidatos[@]}"; do
        [ -n "$candidato" ] && [ -x "$candidato" ] || continue

        # php-cgi acepta -r pero imprime su ayuda en vez de ejecutar, así que
        # se exige que la salida sea exactamente un número de versión.
        version="$("$candidato" -r 'echo PHP_VERSION;' 2>/dev/null | head -1)"
        [[ "$version" =~ ^[0-9]+\.[0-9]+\.[0-9]+ ]] || continue

        if ! "$candidato" -r "exit(PHP_VERSION_ID >= $PHP_MIN && PHP_VERSION_ID < $PHP_MAX ? 0 : 1);" 2>/dev/null; then
            PHP_DESCARTADOS_VERSION="$PHP_DESCARTADOS_VERSION\n      · $candidato (PHP $version)"
            continue
        fi

        if [ -n "$extension" ] && ! "$candidato" -m 2>/dev/null | grep -qi "^${extension}$"; then
            PHP_DESCARTADOS_EXTENSION="$PHP_DESCARTADOS_EXTENSION\n      · $candidato (PHP $version)"
            continue
        fi

        PHP="$candidato"
        PHP_VERSION_ENCONTRADA="$version"
        return 0
    done

    return 1
}
