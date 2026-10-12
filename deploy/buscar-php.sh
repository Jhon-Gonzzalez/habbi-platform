#!/usr/bin/env bash
#
# Localiza un binario de PHP compatible con el proyecto.
# No se ejecuta directamente: los demás scripts hacen `source` de este.
#
#   source deploy/buscar-php.sh
#   buscar_php pdo_sqlite     # deja la ruta en $PHP, o devuelve 1
#
# Rango admitido: 8.2 a 8.4.
#
# El suelo NO lo marca Laravel (acepta ^8.1) sino las dependencias ya
# resueltas: symfony/string y symfony/event-dispatcher exigen >=8.2.
# Compruébalo con «composer why-not php 8.1» antes de bajarlo.
#
# El techo lo marca nette/schema, que admite hasta 8.4.

PHP_MIN=${PHP_MIN:-80200}
PHP_MAX=${PHP_MAX:-80500}   # exclusivo

# Rellenadas por buscar_php para que quien llame pueda dar un mensaje útil.
PHP_DESCARTADOS_VERSION=""
PHP_DESCARTADOS_EXTENSION=""
PHP_FORZADO=0

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
    PHP_FORZADO=0

    for candidato in "${candidatos[@]}"; do
        [ -n "$candidato" ] && [ -x "$candidato" ] || continue

        # Un PHP indicado a mano manda sobre cualquier comprobación nuestra:
        # si el usuario sabe cuál quiere, no se lo discutimos.
        if [ -n "${PHP_BIN:-}" ] && [ "$candidato" = "$PHP_BIN" ]; then
            version="$("$candidato" -r 'echo PHP_VERSION;' 2>/dev/null | head -1)"
            PHP="$candidato"
            PHP_VERSION_ENCONTRADA="${version:-desconocida}"
            PHP_FORZADO=1
            return 0
        fi

        # php-cgi acepta -r pero imprime su ayuda en vez de ejecutar, así que
        # se exige que la salida sea exactamente un número de versión.
        version="$("$candidato" -r 'echo PHP_VERSION;' 2>/dev/null | head -1)"
        [[ "$version" =~ ^[0-9]+\.[0-9]+\.[0-9]+ ]] || continue

        if ! "$candidato" -r "exit(PHP_VERSION_ID >= $PHP_MIN && PHP_VERSION_ID < $PHP_MAX ? 0 : 1);" 2>/dev/null; then
            PHP_DESCARTADOS_VERSION="$PHP_DESCARTADOS_VERSION\n      · $candidato (PHP $version)"
            continue
        fi

        # Se pregunta a PHP directamente en lugar de rastrear la salida de
        # «php -m»: esa lista varía de formato entre versiones y sistemas, y
        # basta un grep que falle para descartar un binario perfectamente
        # válido. extension_loaded() es la respuesta autorizada.
        if [ -n "$extension" ] && ! "$candidato" -r "exit(extension_loaded('$extension') ? 0 : 1);" 2>/dev/null; then
            PHP_DESCARTADOS_EXTENSION="$PHP_DESCARTADOS_EXTENSION\n      · $candidato (PHP $version)"
            continue
        fi

        PHP="$candidato"
        PHP_VERSION_ENCONTRADA="$version"
        return 0
    done

    return 1
}
