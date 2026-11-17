#!/usr/bin/env bash
#
# HABBI — Publicar en internet desde tu propio computador
#
#   Uso:  bash deploy/tunel.sh
#
# Levanta un túnel de Cloudflare y te da una dirección pública con HTTPS
# que apunta a tu máquina. Sirve para enseñarle el proyecto a alguien sin
# contratar hosting: funciona HABBI entero, con sus fotos, su base y su
# PDF, porque quien corre la aplicación sigue siendo tu computador.
#
# Lo que NO es: un hosting. La página vive mientras este comando esté
# abierto y tu computador encendido. Si lo cierras, el enlace muere.
#
# Para qué sirve de verdad: una sustentación, enseñárselo al director de
# tesis, o probar en tu celular cómo se ve de verdad en un móvil.

set -uo pipefail

RAIZ="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$RAIZ"

if [ -t 1 ]; then
    AZUL=$'\033[1;34m'; VERDE=$'\033[1;32m'; ROJO=$'\033[1;31m'; GRIS=$'\033[0;90m'; FIN=$'\033[0m'
else
    AZUL=''; VERDE=''; ROJO=''; GRIS=''; FIN=''
fi
paso() { printf '\n%s▸ %s%s\n' "$AZUL" "$1" "$FIN"; }
ok()   { printf '  %s✓%s %s\n' "$VERDE" "$FIN" "$1"; }

PUERTO="${PUERTO:-8000}"
REGISTRO="$(mktemp)"

# ---------- Comprobaciones ----------
if ! command -v cloudflared >/dev/null 2>&1; then
    printf '\n%s✗ Falta cloudflared.%s\n\n' "$ROJO" "$FIN" >&2
    printf '  En Mac:\n      %sbrew install cloudflared%s\n\n' "$AZUL" "$FIN" >&2
    printf '  En Linux:\n      %ssudo apt install cloudflared%s\n' "$AZUL" "$FIN" >&2
    printf '      (o descárgalo de developers.cloudflare.com/cloudflare-one)\n\n' >&2
    exit 1
fi

# shellcheck source=deploy/buscar-php.sh
. "$RAIZ/deploy/buscar-php.sh"
if ! buscar_php pdo_sqlite; then
    printf '\n%s✗ No encontré un PHP compatible. Corre antes: bash deploy/probar-local.sh%s\n\n' "$ROJO" "$FIN" >&2
    exit 1
fi

if [ ! -f .env ]; then
    printf '\n%s✗ No hay .env. Corre antes: bash deploy/probar-local.sh%s\n\n' "$ROJO" "$FIN" >&2
    exit 1
fi

# ---------- Guardar cómo estaba todo ----------
# Al terminar hay que dejar el .env como estaba, o la próxima vez que
# trabajes en local la aplicación seguirá generando enlaces que apuntan a
# un túnel que ya no existe.
APP_URL_PREVIA="$(grep -E '^APP_URL=' .env | head -1 | cut -d= -f2- | tr -d '"')"
PROXIES_PREVIO="$(grep -E '^TRUSTED_PROXIES=' .env | head -1 | cut -d= -f2- | tr -d '"')"

restaurar() {
    printf '\n'
    paso "Cerrando y dejando todo como estaba"

    [ -n "${PID_TUNEL:-}" ] && kill "$PID_TUNEL" 2>/dev/null
    [ -n "${PID_SERVIDOR:-}" ] && kill "$PID_SERVIDOR" 2>/dev/null

    "$PHP" deploy/env-set.php APP_URL         "${APP_URL_PREVIA:-http://localhost:$PUERTO}" >/dev/null
    "$PHP" deploy/env-set.php TRUSTED_PROXIES "${PROXIES_PREVIO:-}"                          >/dev/null
    "$PHP" artisan config:clear >/dev/null 2>&1

    rm -f "$REGISTRO"
    ok "El .env volvió a su configuración local"
    printf '\n'
}
trap restaurar EXIT INT TERM

# ---------- Abrir el túnel ----------
paso "Abriendo el túnel de Cloudflare"

cloudflared tunnel --url "http://localhost:$PUERTO" > "$REGISTRO" 2>&1 &
PID_TUNEL=$!

# cloudflared tarda unos segundos en negociar y escribir la dirección.
PUBLICA=""
for _ in $(seq 1 40); do
    PUBLICA="$(grep -oE 'https://[a-z0-9-]+\.trycloudflare\.com' "$REGISTRO" | head -1)"
    [ -n "$PUBLICA" ] && break
    kill -0 "$PID_TUNEL" 2>/dev/null || break
    sleep 1
done

if [ -z "$PUBLICA" ]; then
    printf '\n%s✗ El túnel no dio ninguna dirección. Esto es lo que dijo:%s\n\n' "$ROJO" "$FIN" >&2
    tail -15 "$REGISTRO" >&2
    exit 1
fi

ok "Dirección pública: $PUBLICA"

# ---------- Ajustar la aplicación ----------
paso "Ajustando HABBI para esa dirección"

# ESTE es el ajuste que hace que funcione, y sin él la página se ve rota.
#
# Dentro de una petición, Laravel saca el dominio de los enlaces de la
# propia petición, así que el dominio del túnel ya sale bien solo. Lo que
# NO acierta es el esquema: el túnel habla https con el visitante pero
# http con tu máquina, así que Laravel cree que la conexión es insegura y
# escribe los enlaces del CSS y del JS como http://. El navegador los
# bloquea por «contenido mixto» y el sitio aparece sin estilos.
#
# Medido en este proyecto:
#   sin TRUSTED_PROXIES → href="http://…/habbi.css"   (bloqueado)
#   con TRUSTED_PROXIES → href="https://…/habbi.css"  (carga)
#
# De paso, sin esto el registro de quién pidió cada teléfono guardaría la
# IP de Cloudflare en vez de la del visitante, y el control de abusos
# dejaría de servir.
"$PHP" deploy/env-set.php TRUSTED_PROXIES "*" >/dev/null
ok "Cloudflare reconocido como proxy de confianza (arregla el https)"

# APP_URL no hace falta para los enlaces de las páginas, pero sí para los
# que se generan FUERA de una petición: el correo de recuperación de
# contraseña y el comando habbi:enlace. Sin esto apuntarían a localhost y
# no le servirían a nadie de fuera.
"$PHP" deploy/env-set.php APP_URL "$PUBLICA" >/dev/null
ok "APP_URL apunta al túnel (para los enlaces de los correos)"

"$PHP" artisan config:clear >/dev/null 2>&1
"$PHP" artisan view:clear   >/dev/null 2>&1

# ---------- Arrancar el servidor ----------
paso "Arrancando el servidor"

export PHP_INI_SCAN_DIR=":$RAIZ/deploy/php.d"
"$PHP" artisan serve --host=127.0.0.1 --port="$PUERTO" > storage/logs/serve.log 2>&1 &
PID_SERVIDOR=$!

for _ in $(seq 1 25); do
    curl -s -o /dev/null "http://127.0.0.1:$PUERTO" && break
    sleep 0.4
done
ok "Servidor escuchando en el puerto $PUERTO"

printf '\n%s╔══════════════════════════════════════════════════════╗%s\n' "$VERDE" "$FIN"
printf '%s║   HABBI está en internet                             ║%s\n' "$VERDE" "$FIN"
printf '%s╚══════════════════════════════════════════════════════╝%s\n\n' "$VERDE" "$FIN"
printf '  Comparte esta dirección:\n\n      %s%s%s\n\n' "$AZUL" "$PUBLICA" "$FIN"
printf '  Entrar como administrador:\n'
printf '      correo ....... admin@habbi.test\n'
printf '      contraseña ... habbi1234\n\n'
printf '  %sLa dirección cambia cada vez que corres esto, y deja de\n' "$GRIS"
printf '  funcionar en cuanto cierres esta ventana.%s\n\n' "$FIN"
printf '  Para parar: pulsa Ctrl + C\n'

# Esperar a que el usuario corte, o a que se caiga alguno de los dos.
wait $PID_TUNEL
