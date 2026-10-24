#!/usr/bin/env bash
#
# HABBI — Recorrido de verificación
#
#   Uso:  bash deploy/recorrido.sh
#         bash deploy/recorrido.sh http://127.0.0.1:8000
#
# Entra con cada rol y visita todas las pantallas del proyecto, diciendo
# qué responde cada una. Sirve para comprobar de un vistazo que no hay
# nada roto después de un cambio, y para la sustentación: una tabla en
# verde vale más que decir «funciona».
#
# Necesita el servidor levantado (bash deploy/probar-local.sh) y los
# datos de ejemplo cargados.

set -uo pipefail

BASE="${1:-http://127.0.0.1:8000}"
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT

if [ -t 1 ]; then
    VERDE=$'\033[0;32m'; ROJO=$'\033[0;31m'; GRIS=$'\033[0;90m'; NEGRITA=$'\033[1m'; FIN=$'\033[0m'
else
    VERDE=''; ROJO=''; GRIS=''; NEGRITA=''; FIN=''
fi

ACIERTOS=0
FALLOS=0

entrar() {   # entrar <correo> <contraseña> <nombre-del-tarro>
    local jar="$TMP/$3" token
    token=$(curl -s -c "$jar" "$BASE/login" | grep -o 'name="_token" value="[^"]*"' | head -1 | cut -d'"' -f4)
    curl -s -o /dev/null -b "$jar" -c "$jar" -L \
        -d "_token=$token" -d "email=$1" -d "password=$2" "$BASE/login"
}

ver() {      # ver <tarro|-> <ruta> <esperado> <descripción>
    local jar_arg=() code marca color
    [ "$1" != "-" ] && jar_arg=(-b "$TMP/$1")

    code=$(curl -s -o /dev/null -w '%{http_code}' "${jar_arg[@]}" "$BASE$2")

    if [[ " $3 " == *" $code "* ]]; then
        marca="✓"; color="$VERDE"; ACIERTOS=$((ACIERTOS + 1))
    else
        marca="✗"; color="$ROJO"; FALLOS=$((FALLOS + 1))
    fi

    printf '  %s%s%s %-3s %-44s %s%s%s\n' "$color" "$marca" "$FIN" "$code" "$2" "$GRIS" "$4" "$FIN"
}

titulo() { printf '\n%s%s%s\n' "$NEGRITA" "$1" "$FIN"; }

# Los correos son los de los datos de ejemplo (MonteriaSeeder).
entrar "admin@habbi.test"             "habbi1234" admin
entrar "dolores.arrieta@ejemplo.test" "habbi1234" arrendador
entrar "laura.hoyos@ejemplo.test"     "habbi1234" estudiante

titulo "PÚBLICO · sin iniciar sesión"
ver - "/"                             200 "Portada"
ver - "/alojamientos"                 200 "Buscador: filtros, mapa y publicidad"
ver - "/alojamientos/1"               200 "Ficha: mapa, reseñas y ruta a la universidad"
ver - "/alojamientos?universidad=Universidad+de+C%C3%B3rdoba&km=2" 200 "Filtro por distancia (RF-4)"
ver - "/alojamientos?sort=price_asc"  200 "Orden por precio"
ver - "/arrendadores/2"               200 "Perfil público del arrendador"
ver - "/alojamientos/1/marcador.png"  200 "Imagen para compartir"
ver - "/privacidad"                   200 "Política de privacidad (Ley 1581)"
ver - "/terminos"                     200 "Términos"
ver - "/login"                        200 "Iniciar sesión"
ver - "/register"                     200 "Crear cuenta"
ver - "/password/reset"               200 "Recuperar contraseña (RF-5)"
ver - "/ir/1"                         "302 404" "Salida a un aliado (cuenta el clic)"

titulo "ESTUDIANTE"
ver estudiante "/mi-cuenta"               200 "Su panel"
ver estudiante "/mis-favoritos"           200 "Favoritos (RF-9)"
ver estudiante "/mensajes"                200 "Chat con los arrendadores"
ver estudiante "/mi-perfil"               200 "Editar su perfil"
ver estudiante "/alojamientos/1/reportar" 200 "Reportar una publicación"
ver estudiante "/alojamientos/1/telefono" 200 "Pedir el teléfono (queda registrado)"
ver estudiante "/quiero-arrendar"         200 "Pedir ser arrendador"

titulo "ARRENDADOR"
ver arrendador "/alojamientos/mis-publicaciones" 200 "Sus publicaciones"
ver arrendador "/alojamientos/publicar"          200 "Publicar, con selector de mapa (RF-2)"
ver arrendador "/alojamientos/1/editar"          200 "Editar (RF-3)"
ver arrendador "/alojamientos/1/campana"         200 "Pedir una campaña de pago"
ver arrendador "/mensajes"                       200 "Chat con los estudiantes"

titulo "ADMINISTRADOR"
ver admin "/admin"                    200 "Resumen y avisos"
ver admin "/admin/publicaciones"      200 "Publicaciones: revisión y moderación (RF-10, CU-07)"
ver admin "/admin/publicaciones?estado=todas" 200 "Todas las publicaciones"
ver admin "/admin/publicaciones?q=granja" 200 "Buscador de publicaciones"
ver admin "/admin/informes"           200 "Informes con gráficas (RF-11, CU-08)"
ver admin "/admin/informes/pdf"       200 "Descargar el informe en PDF"
ver admin "/admin/campanas"           200 "Campañas y cobros"
ver admin "/admin/campanas/tarifas"   200 "Tarifas y datos de cobro"
ver admin "/admin/aliados"            200 "Publicidad local"
ver admin "/admin/usuarios"           200 "Usuarios y roles"
ver admin "/admin/solicitudes"        200 "Solicitudes de arrendador"
ver admin "/admin/reportes"           200 "Reportes de abuso"
ver admin "/admin/auditoria"          200 "Registro de auditoría"
ver admin "/admin/usuarios?rol=arrendador" 200 "Filtrar usuarios por rol"
ver admin "/admin/exportar/publicaciones" 200 "Descargar publicaciones en CSV"
ver admin "/admin/exportar/usuarios"  200 "Descargar usuarios en CSV"

titulo "PERMISOS · estas TIENEN que cerrar la puerta"
ver estudiante "/admin"                  403 "Estudiante en el panel"
ver estudiante "/admin/campanas/tarifas" 403 "Estudiante en las tarifas"
ver -          "/admin"                  "302 403" "Sin sesión en el panel"
ver -          "/mis-favoritos"          "302 403" "Sin sesión en favoritos"

printf '\n  %s%d en verde%s' "$VERDE" "$ACIERTOS" "$FIN"
if [ "$FALLOS" -gt 0 ]; then
    printf ', %s%d en rojo%s\n\n' "$ROJO" "$FALLOS" "$FIN"
    exit 1
fi
printf ', ninguna en rojo.\n\n'
