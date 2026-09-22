#!/usr/bin/env bash
#
# HABBI — Ejecutar comandos de Laravel (artisan) con el PHP correcto
#
#   bash deploy/consola.sh migrate
#   bash deploy/consola.sh migrate:fresh --seed
#   bash deploy/consola.sh route:list
#   bash deploy/consola.sh tinker
#
# Existe porque «php artisan» usaría el PHP del sistema, que puede ser una
# versión que el proyecto no admite. Esto busca el correcto y se lo pasa.

set -euo pipefail

RAIZ="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$RAIZ"

# shellcheck source=deploy/buscar-php.sh
. "$RAIZ/deploy/buscar-php.sh"

if ! buscar_php; then
    printf '\n✗ No encontré un PHP compatible (necesito 8.1, 8.2, 8.3 o 8.4).\n\n' >&2
    [ -n "$PHP_DESCARTADOS_VERSION" ] && { printf '  Fuera de rango:'; printf "$PHP_DESCARTADOS_VERSION\n\n"; }
    printf '  Instálalo con:  brew install php@8.3\n\n'
    exit 1
fi

if [ $# -eq 0 ]; then
    printf '\nUso: bash deploy/consola.sh <comando de artisan>\n\n'
    printf '  Ejemplos:\n'
    printf '    bash deploy/consola.sh migrate              aplicar migraciones pendientes\n'
    printf '    bash deploy/consola.sh migrate:fresh --seed borrar todo y recrear con datos\n'
    printf '    bash deploy/consola.sh migrate:status       ver qué migraciones corrieron\n'
    printf '    bash deploy/consola.sh route:list           listar las rutas\n'
    printf '    bash deploy/consola.sh tinker               consola interactiva\n\n'
    printf '  PHP en uso: %s (%s)\n\n' "$PHP_VERSION_ENCONTRADA" "$PHP"
    exit 0
fi

# Los límites de subida también hacen falta en la consola (p. ej. tinker).
export PHP_INI_SCAN_DIR=":$RAIZ/deploy/php.d"

exec "$PHP" artisan "$@"
