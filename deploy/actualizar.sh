#!/usr/bin/env bash
#
# HABBI — Actualizar el sitio con los últimos cambios de GitHub
#
#   Uso:  cd ~/habbi && bash deploy/actualizar.sh
#
# Pone el sitio en mantenimiento, baja los cambios, actualiza dependencias
# y base de datos, y lo vuelve a levantar.

set -euo pipefail

RAIZ="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$RAIZ"

if [ -t 1 ]; then
    AZUL=$'\033[1;34m'; VERDE=$'\033[1;32m'; ROJO=$'\033[1;31m'; FIN=$'\033[0m'
else
    AZUL=''; VERDE=''; ROJO=''; FIN=''
fi
paso() { printf '\n%s▸ %s%s\n' "$AZUL" "$1" "$FIN"; }
ok()   { printf '  %s✓%s %s\n' "$VERDE" "$FIN" "$1"; }

# Buscar el mismo PHP que usó el instalador
PHP=""
for c in /opt/cpanel/ea-php83/root/usr/bin/php /opt/cpanel/ea-php82/root/usr/bin/php \
         /opt/cpanel/ea-php81/root/usr/bin/php "$(command -v php || true)"; do
    [ -n "$c" ] && [ -x "$c" ] || continue
    "$c" -r 'exit(PHP_VERSION_ID >= 80100 ? 0 : 1);' 2>/dev/null && { PHP="$c"; break; }
done
[ -n "$PHP" ] || { printf '%s✗ No encontré PHP 8.1+%s\n' "$ROJO" "$FIN" >&2; exit 1; }

COMPOSER="composer"
command -v composer >/dev/null 2>&1 || COMPOSER="$PHP composer.phar"

# Si algo falla, sacar el sitio del modo mantenimiento igualmente
restaurar() { "$PHP" artisan up >/dev/null 2>&1 || true; }
trap restaurar EXIT

paso "Activando el modo mantenimiento"
"$PHP" artisan down --render="errors::503" >/dev/null 2>&1 || "$PHP" artisan down >/dev/null
ok "El sitio muestra la página de mantenimiento"

paso "Descargando los últimos cambios"
git pull --ff-only origin master
ok "Código actualizado"

paso "Actualizando dependencias"
$COMPOSER install --no-dev --optimize-autoloader --no-interaction 2>&1 | tail -3
ok "Dependencias al día"

paso "Aplicando migraciones"
"$PHP" artisan migrate --force --no-interaction 2>&1 | tail -5
ok "Base de datos actualizada"

paso "Regenerando cachés"
"$PHP" artisan optimize:clear >/dev/null
"$PHP" artisan optimize >/dev/null
[ -L public/storage ] || "$PHP" artisan storage:link >/dev/null
ok "Cachés regeneradas"

paso "Levantando el sitio"
"$PHP" artisan up >/dev/null
trap - EXIT
ok "El sitio vuelve a estar en línea"

printf '\n  %sActualización completada.%s\n\n' "$VERDE" "$FIN"
