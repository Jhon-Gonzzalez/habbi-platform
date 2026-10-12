#!/usr/bin/env bash
#
# Prepara HABBI dentro de un Codespace de GitHub.
#
# Se ejecuta una sola vez, al crear el Codespace (postCreateCommand).
# Deja la aplicación lista: dependencias, base de datos SQLite con los datos
# de ejemplo de Montería y el administrador creado.

set -euo pipefail

cd "$(dirname "$0")/.."

echo "==> Instalando dependencias de PHP"
composer install --no-interaction --prefer-dist

if [ ! -f .env ]; then
    echo "==> Creando .env"
    cp .env.example .env
    php artisan key:generate --force
fi

echo "==> Configurando el entorno"
php deploy/env-set.php APP_ENV   "local"
php deploy/env-set.php APP_DEBUG "true"

# SQLite: en un Codespace no hay servidor MySQL y montar uno sería pelear
# con docker-compose para nada. Un archivo basta para probar.
php deploy/env-set.php DB_CONNECTION "sqlite"
php deploy/env-set.php DB_DATABASE   "$(pwd)/database/database.sqlite"
touch database/database.sqlite

# Los correos no se envían: van al log, igual que en local.
php deploy/env-set.php MAIL_MAILER "log"

# El puerto 8000 de un Codespace se publica en una URL https con un dominio
# propio. Si APP_URL se quedara en localhost, las hojas de estilo y las
# imágenes apuntarían a la máquina del navegador y la página saldría sin
# diseño. Estas variables las define GitHub dentro del Codespace.
if [ -n "${CODESPACE_NAME:-}" ]; then
    URL_PUBLICA="https://${CODESPACE_NAME}-8000.${GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN:-app.github.dev}"
    echo "==> URL pública: ${URL_PUBLICA}"
    php deploy/env-set.php APP_URL "$URL_PUBLICA"

    # El tráfico llega por un proxy de GitHub que termina el HTTPS. Sin esto
    # Laravel cree que la petición es http y genera enlaces y formularios con
    # http://, que el navegador bloquea por contenido mixto.
    php deploy/env-set.php TRUSTED_PROXIES "*"
else
    php deploy/env-set.php APP_URL "http://localhost:8000"
fi

echo "==> Creando la base de datos con los datos de ejemplo"
php artisan migrate:fresh --seed --force

echo "==> Enlazando el almacenamiento de fotos"
php artisan storage:link --force

php artisan config:clear >/dev/null

cat <<'FIN'

  ──────────────────────────────────────────────────────────────
   HABBI está listo.

   Abre la pestaña «PORTS» (o PUERTOS) de VS Code y haz clic en
   el enlace del puerto 8000.

   Administrador:  admin@habbi.test  /  habbi1234
   Arrendadora:    dolores.arrieta@ejemplo.test  /  habbi1234
   Estudiante:     laura.hoyos@ejemplo.test  /  habbi1234

   Los correos no se envían, se escriben en
   storage/logs/laravel.log
  ──────────────────────────────────────────────────────────────

FIN
