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
# Rango admitido por el proyecto. El suelo lo marca Laravel 10 (^8.1) y el
# techo la dependencia nette/schema, que declara "8.1 - 8.4". Actualiza
# estos valores si algún día se sube la versión de Laravel.
PHP_MIN=80100
PHP_MAX=80500   # exclusivo: 8.5 y superiores quedan fuera

# En un Mac puede haber varios PHP a la vez (Herd, Homebrew, el del sistema).
# Buscamos uno que cumpla las tres condiciones: dentro del rango y con pdo_sqlite.
candidatos=()
[ -n "${PHP_BIN:-}" ] && candidatos+=("$PHP_BIN")
candidatos+=("$(command -v php || true)")

for patron in "$HOME/Library/Application Support/Herd/bin/php" \
              "$HOME/Library/Application Support/Herd/config/php/8*/bin/php" \
              '/opt/homebrew/opt/php@8.[1-9]/bin/php' \
              '/opt/homebrew/bin/php' \
              '/usr/local/opt/php@8.[1-9]/bin/php' \
              '/usr/local/bin/php' \
              '/opt/alt/php8[0-9]/usr/bin/php'
do
    while IFS= read -r ruta; do
        [ -n "$ruta" ] && candidatos+=("$ruta")
    done < <(compgen -G "$patron" 2>/dev/null | sort -rV)
done

PHP=""
SIN_SQLITE=""
DEMASIADO_NUEVO=""
for candidato in "${candidatos[@]}"; do
    [ -n "$candidato" ] && [ -x "$candidato" ] || continue

    version="$("$candidato" -r 'echo PHP_VERSION;' 2>/dev/null | head -1)"
    [[ "$version" =~ ^[0-9]+\.[0-9]+\.[0-9]+ ]] || continue

    "$candidato" -r "exit(PHP_VERSION_ID >= $PHP_MIN ? 0 : 1);" 2>/dev/null || continue

    if ! "$candidato" -r "exit(PHP_VERSION_ID < $PHP_MAX ? 0 : 1);" 2>/dev/null; then
        DEMASIADO_NUEVO="$DEMASIADO_NUEVO\n      · $candidato (PHP $version)"
        continue
    fi

    if "$candidato" -m 2>/dev/null | grep -qi '^pdo_sqlite$'; then
        PHP="$candidato"
        ok "PHP $version → $PHP"
        ok "Extensión sqlite disponible"
        break
    fi
    SIN_SQLITE="$SIN_SQLITE\n      · $candidato (PHP $version)"
done

if [ -z "$PHP" ]; then
    printf '\n%s✗ No encontré un PHP compatible (necesito 8.1, 8.2, 8.3 o 8.4).%s\n\n' "$ROJO" "$FIN" >&2

    if [ -n "$DEMASIADO_NUEVO" ]; then
        printf '  Estos son DEMASIADO NUEVOS para este proyecto:'
        printf "$DEMASIADO_NUEVO\n"
        printf '      (la dependencia nette/schema solo admite hasta PHP 8.4)\n\n'
    fi

    if [ -n "$SIN_SQLITE" ]; then
        printf '  Estos tienen una versión válida pero les falta pdo_sqlite:'
        printf "$SIN_SQLITE\n\n"
    fi

    printf '  Solución más rápida con Homebrew:\n\n'
    printf '      %sbrew install php@8.3%s\n\n' "$AZUL" "$FIN"
    printf '  Y vuelve a ejecutar este script: lo encontrará automáticamente.\n\n'
    printf '  Alternativa: Laravel Herd (gratis, trae su propio PHP):\n'
    printf '      %shttps://herd.laravel.com%s\n\n' "$AZUL" "$FIN"
    printf '  ¿Ya tienes un PHP que sirve pero no lo encuentro? Indícalo así:\n'
    printf '      PHP_BIN=/ruta/a/php bash deploy/probar-local.sh\n\n'
    exit 1
fi

# ---------- Dependencias ----------
paso "Instalando dependencias"
# Composer debe ejecutarse CON el PHP que acabamos de elegir. Si se llama al
# ejecutable «composer» directamente, éste arranca con el PHP de su shebang
# (el primero del PATH), que puede ser una versión distinta a la elegida.
COMPOSER_BIN="$(command -v composer || true)"
if [ -n "$COMPOSER_BIN" ]; then
    COMPOSER=("$PHP" "$COMPOSER_BIN")
elif [ -f composer.phar ]; then
    COMPOSER=("$PHP" composer.phar)
else
    curl -sS https://getcomposer.org/installer | "$PHP" -- --quiet
    COMPOSER=("$PHP" composer.phar)
fi
"${COMPOSER[@]}" install --no-interaction 2>&1 | tail -3
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
printf '  Abre en el navegador:  %shttp://127.0.0.1:8000%s\n\n' "$AZUL" "$FIN"
printf '  Entrar como administrador:\n'
printf '      correo ....... admin@habbi.test\n'
printf '      contraseña ... habbi1234\n\n'
printf '  Para parar el servidor: pulsa Ctrl + C\n\n'

# Los valores por defecto de PHP (post_max_size 8M) impiden subir varias
# fotos a la vez. «artisan serve» lanza un PHP nuevo que NO hereda las
# opciones -d, pero sí la variable PHP_INI_SCAN_DIR: el colon inicial hace
# que el directorio se sume al de siempre, en vez de reemplazarlo, para no
# perder las extensiones del sistema.
export PHP_INI_SCAN_DIR=":$RAIZ/deploy/php.d"

exec "$PHP" artisan serve --host=127.0.0.1 --port=8000
