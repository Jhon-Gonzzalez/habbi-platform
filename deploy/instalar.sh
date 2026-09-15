#!/usr/bin/env bash
#
# HABBI — Instalación en hosting compartido (Namecheap / cPanel)
#
#   Uso:  cd ~/habbi && bash deploy/instalar.sh
#
# El script detecta tu usuario de cPanel, busca una versión de PHP válida,
# instala las dependencias, te pide los datos de la base de datos y deja
# el sitio funcionando. Es seguro ejecutarlo más de una vez.

set -euo pipefail

RAIZ="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$RAIZ"

# ---------- Colores ----------
if [ -t 1 ]; then
    AZUL=$'\033[1;34m'; VERDE=$'\033[1;32m'; ROJO=$'\033[1;31m'
    AMBAR=$'\033[1;33m'; GRIS=$'\033[0;90m'; FIN=$'\033[0m'
else
    AZUL=''; VERDE=''; ROJO=''; AMBAR=''; GRIS=''; FIN=''
fi

paso()  { printf '\n%s▸ %s%s\n' "$AZUL" "$1" "$FIN"; }
ok()    { printf '  %s✓%s %s\n' "$VERDE" "$FIN" "$1"; }
aviso() { printf '  %s!%s %s\n' "$AMBAR" "$FIN" "$1"; }
error() { printf '\n  %s✗ %s%s\n\n' "$ROJO" "$1" "$FIN" >&2; exit 1; }
nota()  { printf '    %s%s%s\n' "$GRIS" "$1" "$FIN"; }

printf '\n%s╔════════════════════════════════════════════╗%s\n' "$AZUL" "$FIN"
printf '%s║   HABBI · Instalación en cPanel            ║%s\n' "$AZUL" "$FIN"
printf '%s╚════════════════════════════════════════════╝%s\n' "$AZUL" "$FIN"

# ============================================================
paso "1/9 · Detectando tu cuenta"
# ============================================================
USUARIO_CPANEL="$(whoami)"
ok "Usuario de cPanel: ${VERDE}${USUARIO_CPANEL}${FIN}"
nota "Este es tu PREFIJO. Las bases de datos que crees se llamarán"
nota "${USUARIO_CPANEL}_loquesea — cPanel añade el prefijo solo."
ok "Proyecto en: $RAIZ"

# ============================================================
paso "2/9 · Buscando PHP 8.1 o superior"
# ============================================================
PHP=""
for candidato in \
    /opt/cpanel/ea-php83/root/usr/bin/php \
    /opt/cpanel/ea-php82/root/usr/bin/php \
    /opt/cpanel/ea-php81/root/usr/bin/php \
    /usr/local/bin/ea-php83 /usr/local/bin/ea-php82 /usr/local/bin/ea-php81 \
    "$(command -v php || true)"
do
    [ -n "$candidato" ] && [ -x "$candidato" ] || continue
    version="$("$candidato" -r 'echo PHP_VERSION;' 2>/dev/null || echo 0)"
    if "$candidato" -r 'exit(PHP_VERSION_ID >= 80100 ? 0 : 1);' 2>/dev/null; then
        PHP="$candidato"
        ok "PHP $version → $PHP"
        break
    fi
    nota "descartado: $candidato (PHP $version)"
done

[ -n "$PHP" ] || error "No encontré PHP 8.1+. Ve a cPanel → MultiPHP Manager y selecciona PHP 8.2 para tu dominio."

# ---------- Extensiones ----------
FALTAN=""
for ext in pdo_mysql mbstring openssl tokenizer xml ctype json fileinfo; do
    "$PHP" -m | grep -qi "^${ext}$" || FALTAN="$FALTAN $ext"
done
if [ -n "$FALTAN" ]; then
    aviso "Faltan extensiones de PHP:$FALTAN"
    nota "Actívalas en cPanel → Select PHP Version → Extensions, y vuelve a ejecutar."
    error "Instalación detenida."
fi
ok "Todas las extensiones necesarias están activas"

# ============================================================
paso "3/9 · Instalando dependencias (composer)"
# ============================================================
if command -v composer >/dev/null 2>&1; then
    COMPOSER="composer"
elif [ -f composer.phar ]; then
    COMPOSER="$PHP composer.phar"
else
    nota "Composer no está instalado, lo descargo…"
    curl -sS https://getcomposer.org/installer | "$PHP" -- --quiet
    COMPOSER="$PHP composer.phar"
fi

$COMPOSER install --no-dev --optimize-autoloader --no-interaction 2>&1 | tail -4
ok "Dependencias instaladas"

# ============================================================
paso "4/9 · Configurando el archivo .env"
# ============================================================
if [ -f .env ]; then
    aviso "Ya existe un archivo .env"
    read -rp "  ¿Quieres reescribir la configuración? [s/N]: " REHACER
    REHACER="${REHACER:-n}"
else
    REHACER="s"
    cp .env.example .env
    ok "Creado .env a partir de .env.example"
fi

# Escribe una variable en .env. Delega en un helper de PHP porque sed se
# rompe con contraseñas que contienen / & | # o comillas.
escribir() {
    "$PHP" deploy/env-set.php "$1" "$2"
}

if [[ "$REHACER" =~ ^[sSyY] ]]; then
    printf '\n  %sDatos de la base de datos%s\n' "$AZUL" "$FIN"
    nota "Créala antes en cPanel → MySQL® Databases."
    nota "Escribe los nombres COMPLETOS, con el prefijo ${USUARIO_CPANEL}_"
    printf '\n'

    read -rp "  Nombre de la base de datos [${USUARIO_CPANEL}_habbi]: " BD
    BD="${BD:-${USUARIO_CPANEL}_habbi}"

    read -rp "  Usuario de la base de datos [${USUARIO_CPANEL}_habbi]: " BD_USER
    BD_USER="${BD_USER:-${USUARIO_CPANEL}_habbi}"

    read -rsp "  Contraseña de la base de datos: " BD_PASS; printf '\n'
    [ -n "$BD_PASS" ] || error "La contraseña de la base de datos no puede estar vacía."

    printf '\n  %sDatos del sitio%s\n' "$AZUL" "$FIN"
    read -rp "  URL del sitio [https://eliandval.com]: " URL
    URL="${URL:-https://eliandval.com}"

    read -rp "  Correo del administrador: " ADMIN_MAIL
    [ -n "$ADMIN_MAIL" ] || error "Necesito un correo para la cuenta de administrador."

    read -rsp "  Contraseña del administrador (mín. 8, con letras y números): " ADMIN_PASS; printf '\n'
    [ ${#ADMIN_PASS} -ge 8 ] || error "La contraseña del administrador debe tener al menos 8 caracteres."

    escribir APP_NAME        "HABBI"
    escribir APP_ENV         "production"
    escribir APP_DEBUG       "false"
    escribir APP_URL         "$URL"
    escribir APP_TIMEZONE    "America/Bogota"
    escribir LOG_LEVEL       "error"
    escribir DB_CONNECTION   "mysql"
    escribir DB_HOST         "localhost"
    escribir DB_PORT         "3306"
    escribir DB_DATABASE     "$BD"
    escribir DB_USERNAME     "$BD_USER"
    escribir DB_PASSWORD     "$BD_PASS"
    escribir FILESYSTEM_DISK "public"
    escribir ADMIN_EMAIL     "$ADMIN_MAIL"
    escribir ADMIN_PASSWORD  "$ADMIN_PASS"

    ok "Configuración guardada en .env"
    ok "APP_DEBUG=false (obligatorio en producción)"
fi

chmod 600 .env
ok "Permisos del .env restringidos (600)"

# ============================================================
paso "5/9 · Clave de cifrado"
# ============================================================
if grep -qE '^APP_KEY=base64:.+' .env; then
    ok "La aplicación ya tiene APP_KEY"
else
    "$PHP" artisan key:generate --force --no-interaction >/dev/null
    ok "APP_KEY generada"
fi

# ============================================================
paso "6/9 · Comprobando la conexión a la base de datos"
# ============================================================
"$PHP" artisan config:clear >/dev/null 2>&1 || true
if ! "$PHP" -r '
    require "vendor/autoload.php";
    $app = require "bootstrap/app.php";
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    try { Illuminate\Support\Facades\DB::connection()->getPdo(); }
    catch (Throwable $e) { fwrite(STDERR, $e->getMessage()."\n"); exit(1); }
' 2>/tmp/habbi_db_error; then
    printf '\n'
    aviso "No pude conectar con la base de datos:"
    nota "$(head -2 /tmp/habbi_db_error)"
    printf '\n'
    nota "Revisa en cPanel → MySQL® Databases:"
    nota "  · que la base y el usuario existan con el prefijo ${USUARIO_CPANEL}_"
    nota "  · que el usuario esté asignado a la base con ALL PRIVILEGES"
    error "Corrige eso y vuelve a ejecutar el script."
fi
ok "Conexión con MySQL correcta"

# ============================================================
paso "7/9 · Creando las tablas"
# ============================================================
"$PHP" artisan migrate --force --no-interaction 2>&1 | tail -6
ok "Migraciones aplicadas"

"$PHP" artisan db:seed --class=AdminSeeder --force --no-interaction 2>&1 | tail -2
ok "Cuenta de administrador lista"

# ============================================================
paso "8/9 · Fotos y permisos"
# ============================================================
if [ -L public/storage ]; then
    ok "El enlace public/storage ya existe"
else
    "$PHP" artisan storage:link >/dev/null && ok "Enlace public/storage creado"
fi

chmod -R 755 storage bootstrap/cache
ok "Permisos de storage y bootstrap/cache ajustados (755)"

# ============================================================
paso "9/9 · Optimizando"
# ============================================================
"$PHP" artisan optimize >/dev/null
ok "Configuración, rutas y vistas cacheadas"

# ============================================================
URL_FINAL="$(grep '^APP_URL=' .env | cut -d= -f2-)"
ADMIN_FINAL="$(grep '^ADMIN_EMAIL=' .env | cut -d= -f2-)"

printf '\n%s╔════════════════════════════════════════════╗%s\n' "$VERDE" "$FIN"
printf '%s║   Instalación completada                   ║%s\n' "$VERDE" "$FIN"
printf '%s╚════════════════════════════════════════════╝%s\n\n' "$VERDE" "$FIN"
printf '  Sitio:  %s\n' "$URL_FINAL"
printf '  Panel:  %s/admin\n' "$URL_FINAL"
printf '  Admin:  %s\n\n' "$ADMIN_FINAL"
printf '  %sQueda un paso manual:%s\n' "$AMBAR" "$FIN"
printf '  cPanel → Domains → Manage → Document Root debe ser:\n'
printf '      %s%s/public%s\n\n' "$VERDE" "${RAIZ#"$HOME/"}" "$FIN"
printf '  Para actualizar el sitio más adelante:  %sbash deploy/actualizar.sh%s\n\n' "$AZUL" "$FIN"
