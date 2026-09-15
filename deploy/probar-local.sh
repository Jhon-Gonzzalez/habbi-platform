#!/usr/bin/env bash
#
# HABBI — Arrancar el proyecto en tu propio ordenador
#
#   Uso:  bash deploy/probar-local.sh
#
# Usa SQLite en lugar de MySQL: no hay que instalar ni configurar
# ninguna base de datos. Crea datos de ejemplo y levanta el servidor.

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

# ---------- PHP ----------
PHP="$(command -v php || true)"
if [ -z "$PHP" ] || ! "$PHP" -r 'exit(PHP_VERSION_ID >= 80100 ? 0 : 1);' 2>/dev/null; then
    printf '\n%s✗ Necesitas PHP 8.1 o superior.%s\n\n' "$ROJO" "$FIN" >&2
    printf '  En Mac, lo más fácil es instalar Laravel Herd (gratis):\n'
    printf '      https://herd.laravel.com\n\n'
    printf '  O con Homebrew:  brew install php\n\n'
    exit 1
fi
ok "PHP $("$PHP" -r 'echo PHP_VERSION;')"

"$PHP" -m | grep -qi '^pdo_sqlite$' || {
    printf '\n%s✗ Falta la extensión pdo_sqlite de PHP.%s\n\n' "$ROJO" "$FIN" >&2
    exit 1
}
ok "Extensión sqlite disponible"

# ---------- Dependencias ----------
paso "Instalando dependencias"
if command -v composer >/dev/null 2>&1; then
    COMPOSER="composer"
elif [ -f composer.phar ]; then
    COMPOSER="$PHP composer.phar"
else
    curl -sS https://getcomposer.org/installer | "$PHP" -- --quiet
    COMPOSER="$PHP composer.phar"
fi
$COMPOSER install --no-interaction 2>&1 | tail -3
ok "Dependencias listas"

# ---------- Configuración ----------
paso "Preparando la configuración"
[ -f .env ] || cp .env.example .env

touch database/database.sqlite

"$PHP" deploy/env-set.php APP_ENV       "local"
"$PHP" deploy/env-set.php APP_DEBUG     "true"
"$PHP" deploy/env-set.php APP_URL       "http://localhost:8000"
"$PHP" deploy/env-set.php DB_CONNECTION "sqlite"
"$PHP" deploy/env-set.php DB_DATABASE   "$RAIZ/database/database.sqlite"
"$PHP" deploy/env-set.php ADMIN_EMAIL   "admin@habbi.test"
"$PHP" deploy/env-set.php ADMIN_PASSWORD "habbi1234"

grep -qE '^APP_KEY=base64:.+' .env || "$PHP" artisan key:generate --force >/dev/null
"$PHP" artisan config:clear >/dev/null
ok "Configurado con SQLite (no hace falta MySQL)"

# ---------- Base de datos ----------
paso "Creando las tablas y los datos de ejemplo"
"$PHP" artisan migrate:fresh --seed --force 2>&1 | tail -4
ok "Base de datos lista"

[ -L public/storage ] || "$PHP" artisan storage:link >/dev/null
ok "Almacenamiento de fotos enlazado"

# ---------- Arrancar ----------
printf '\n%s╔════════════════════════════════════════════╗%s\n' "$VERDE" "$FIN"
printf '%s║   HABBI funcionando en tu ordenador        ║%s\n' "$VERDE" "$FIN"
printf '%s╚════════════════════════════════════════════╝%s\n\n' "$VERDE" "$FIN"
printf '  Abre en el navegador:  %shttp://localhost:8000%s\n\n' "$AZUL" "$FIN"
printf '  Entrar como administrador:\n'
printf '      correo ....... admin@habbi.test\n'
printf '      contraseña ... habbi1234\n\n'
printf '  Para parar el servidor: pulsa Ctrl + C\n\n'

exec "$PHP" artisan serve
