<p align="center">
  <img src="public/assets/img/images/logo.png" alt="HABBI" width="220">
</p>

<h1 align="center">HABBI · Plataforma de vivienda estudiantil</h1>

<p align="center">
  Buscar, publicar y calificar alojamiento cerca de la universidad.<br>
  <strong>Laravel 12 · PHP 8.2–8.4 · MySQL o SQLite · Blade</strong>
</p>

---

## Qué hace

HABBI conecta a estudiantes que buscan dónde vivir con arrendadores que tienen
habitaciones, apartaestudios o apartamentos disponibles. Está pensada para
Montería y la Universidad de Córdoba, aunque nada en el código la ata a esa
ciudad.

| Módulo | Qué incluye |
|---|---|
| **Buscador** | Filtros por texto, precio, tipo, capacidad, comodidades y **distancia a la universidad**. Resultados en lista **y en un mapa con pines**. Orden por precio, fecha o calificación. |
| **Mapa** | Leaflet y OpenStreetMap, servidos desde el propio proyecto: sin clave de API y sin depender de un CDN. El arrendador marca el punto arrastrando un pin; no hay geocodificación. |
| **Publicaciones** | Alta con hasta 8 fotos, edición con gestión de galería, pausar/reactivar y borrado con limpieza de archivos. **Nacen en revisión**: no se ven hasta que un administrador las aprueba. |
| **Chat interno** | Conversación por alojamiento entre estudiante y arrendador, con **cierre de acuerdo a dos manos**: lo propone uno y lo confirma el otro. Al confirmarse baja solo el número de plazas, y al llegar a cero el anuncio sale del buscador. |
| **Reseñas** | De 1 a 5 estrellas con comentario. Una por persona y alojamiento, y nadie puede reseñar lo suyo. |
| **Cuentas y roles** | Registro, login, recuperación de contraseña y verificación de correo. Registrarse te hace **estudiante**; para publicar hay que pasar una **verificación como arrendador** que aprueba un administrador. |
| **Perfiles** | Foto, descripción, ciudad y universidad. El arrendador tiene perfil público con su sello de verificado y el promedio de sus reseñas. |
| **Favoritos** | Guardar alojamientos con un corazón y volver a ellos en `/mis-favoritos`. |
| **Reportes** | Avisar de anuncios falsos, estafas o precios que no corresponden. Un reporte **no** oculta la publicación: lo revisa una persona. |
| **Panel de administración** | Métricas, cola de revisión, CRUD de usuarios, solicitudes de arrendador, bandeja de reportes, **informes con gráficas y PDF** y **registro de auditoría**. |
| **Ingresos** | **Campañas** de pago (el arrendador elige cuántos días sale de primero) y **aliados**, publicidad de negocios locales vendida directamente. Las tarifas y los datos de cobro los cambia el administrador desde el panel. |
| **Operación** | Copia de seguridad diaria con rotación, borrado automático de los documentos de identidad al resolver una solicitud, y límites de velocidad contra el abuso. |

## Decisiones técnicas

- **Sin paso de compilación en el frontend.** El CSS y el JS son archivos planos
  en `public/assets/`. No hace falta Node ni `npm run build` para desplegar,
  algo que en hosting compartido evita la mayoría de los problemas.
- **Leaflet se sirve desde el repositorio,** no desde un CDN. La primera versión
  lo cargaba de cdnjs con un hash `integrity`; el hash estaba mal, el navegador
  rechazaba el archivo y el mapa salía como un recuadro vacío, sin ningún error
  visible. Con el archivo dentro no hay hash que equivocar ni dominio ajeno que
  pueda caerse.
- **Autorización con policies.** `AlojamientoPolicy`, `RatingPolicy` y
  `SolicitudArrendadorPolicy` deciden quién edita y quién borra; el middleware
  `admin` protege el panel y `puede-publicar` la publicación.
- **Consultas sin N+1.** El scope `conResenas()` usa `withAvg` + `withCount`, así
  el listado de 12 tarjetas hace una consulta en lugar de veinticinco. La
  portada hace 1 consulta, el buscador 3 y la ficha 7.
- **Las fotos viven fuera del repositorio,** en `storage/app/public`, expuestas
  mediante el enlace simbólico `public/storage`.
- **Los documentos de verificación NO son públicos.** Van al disco `private`
  (`storage/app/private`), sin enlace simbólico, y solo se sirven a través de un
  controlador que comprueba permisos. Además **se borran solos** en cuanto el
  administrador aprueba o rechaza: una cédula escaneada que se filtra sirve para
  suplantar a alguien, y guardarla después de haberla comprobado solo añade
  riesgo.
- **El teléfono no se imprime en la página.** Hay que pedirlo, cada petición
  queda registrada y hay un tope por hora y por día. El panel avisa si alguien
  pide demasiados. El contacto normal es el chat, que además deja constancia de
  lo que se acuerda.
- **El administrador no puede ver ni poner contraseñas.** No hay campo para
  escribir la de otro: se genera un enlace de restablecimiento que caduca.
  `bcrypt` es irreversible, así que ni consultando la base se leen.
- **Las acciones del panel quedan auditadas.** `Auditor::registrar()` escribe
  quién hizo qué, cuándo y sobre qué. No hay ruta para borrar ni editar ese
  registro, y copia el nombre del actor en la fila para que borrar su cuenta no
  borre su historial.
- **El dinero no compra el orden que el usuario pidió.** Una campaña sube la
  publicación solo en el orden por defecto; si el estudiante ordenó por precio o
  filtró por cercanía, manda lo que él eligió. Y la etiqueta dice «Destacado» en
  claro. Hay pruebas dedicadas a las dos reglas.
- **La publicidad no entra en la rejilla de resultados,** ni en la ficha, ni en
  el chat. Tampoco hay scripts de terceros ni cookies de rastreo: los anuncios
  salen de la base de datos de este servidor.
- **Solo universidades con coordenadas comprobadas.** El filtro por distancia
  ofrece únicamente Unicórdoba y Unisinú, que son las que se pudieron verificar
  en fuentes públicas. Ponerlas a ojo daría distancias falsas, y una distancia
  falsa es peor que no ofrecer el filtro.
- **El `composer.lock` se resuelve para PHP 8.2,** aunque quien ejecute
  `composer update` tenga 8.4. Lo fija `config.platform.php`. Sin eso, actualizar
  desde una máquina con 8.4 produce un lock que solo se instala en 8.4, y el
  despliegue en un hosting con 8.2 falla. Lo descubrió el CI, no una suposición.
- **El CI corre `composer audit`.** El proyecto llegó a estar en Laravel 10.50.3
  con cinco vulnerabilidades conocidas, una crítica, porque nada lo comprobaba.

## Estructura

```
app/
├── Console/Commands/    habbi:correo · habbi:enlace · habbi:respaldar · habbi:limpiar-documentos
├── Http/
│   ├── Controllers/
│   │   ├── Admin/       Panel: usuarios, revisión, solicitudes, reportes,
│   │   │                informes, campañas, tarifas, aliados, auditoría
│   │   └── …            Alojamientos, chat, favoritos, perfiles, reportes,
│   │                    teléfono, campañas, aliados, legal
│   ├── Middleware/      EnsureUserIsAdmin · EnsureUserPuedePublicar · VerificarCorreo
│   └── Requests/        Validación por formulario
├── Listeners/           EnviarVerificacionDeCorreo (no tumba el registro si falla el SMTP)
├── Models/              13 modelos: Alojamiento, User, Rating, Conversacion, Mensaje,
│                        Campana, TarifaCampana, Aliado, Reporte, Auditoria,
│                        SolicitudArrendador, TelefonoVisto, Ajuste
├── Policies/            Quién edita y quién borra
├── Services/            La lógica que no cabe en un controlador:
│                        ChatService, CampanaService, RevisionAlojamientoService,
│                        VerificacionArrendadorService, RespaldoBaseDeDatos,
│                        ReporteEstadisticas, AlojamientoPhotoService,
│                        ImagenMarcador, Auditor
├── Support/             ArchivoEnv, Subidas, Texto (helpers sin estado)
└── View/Composers/      MenuAdmin (los contadores del menú del panel)

resources/views/
├── layouts/        app · auth · admin
├── partials/       navbar · footer · listing-card · mapa · aliados · leaflet
├── components/     avatar · estrellas · sello-verificado
├── alojamientos/   index · show · create · edit · mine · campana · _campos
├── mensajes/       index · show          (el chat)
├── admin/          revisión · campañas · aliados · informes · usuarios · auditoría…
└── legal/          términos · privacidad

public/assets/
├── css/habbi.css        Sistema de diseño (tokens + componentes)
├── js/                  habbi.js · mapa.js · selector-mapa.js
├── vendor/leaflet/      Leaflet 1.9.4, servido desde aquí y no desde un CDN
└── img/images/          Logo, favicon e imagen de portada

docs/
├── donde-verlo.md              Dónde ver cada requisito funcionando
├── verificacion-requisitos.md  Contraste con el documento del proyecto
└── respaldos.md                Copias de seguridad: qué cubren y qué no

deploy/
├── probar-local.sh      Levanta el proyecto con un comando
├── recorrido.sh         Visita las 41 pantallas con cada rol
├── instalar.sh          Primera instalación en el servidor
└── actualizar.sh        Traer los cambios en el servidor
```

## Cómo verlo funcionando

Hay tres formas, de la más rápida a la más completa.

### 1. En el navegador, sin instalar nada (GitHub Codespaces)

En la página del repositorio: botón verde **Code** → pestaña **Codespaces** →
**Create codespace**. GitHub levanta una máquina Linux con PHP 8.3 ya puesto,
instala todo solo y crea la base de datos con los 20 alojamientos de ejemplo.

Cuando termine, abre la pestaña **PORTS** de VS Code y pincha el enlace del
puerto 8000.

Las cuentas de prueba (todas con contraseña `habbi1234`):

| Correo | Qué puede hacer |
|---|---|
| `admin@habbi.test` | Todo: panel, reportes, auditoría |
| `dolores.arrieta@ejemplo.test` | Arrendadora verificada: publicar |
| `laura.hoyos@ejemplo.test` | Estudiante: buscar, reseñar, favoritos |

> **Ojo con el gasto.** Codespaces es gratis hasta cierto número de horas al
> mes en cuentas personales; pasado eso, se paga. **Párala cuando termines**
> desde <https://github.com/codespaces> (no basta con cerrar la pestaña: la
> máquina sigue encendida hasta que se apaga por inactividad).

La configuración está en `.devcontainer/`. Si quieres saber qué hace, está
comentado en `.devcontainer/preparar.sh`.

### 2. GitHub Pages: **no sirve** para esto

Vale la pena decirlo porque es la confusión más común: GitHub Pages solo sirve
archivos estáticos (HTML, CSS, imágenes). HABBI es Laravel, o sea PHP
ejecutándose en un servidor con una base de datos detrás. En Pages no
funcionaría: verías el código fuente o una página en blanco.

### 3. En tu propio computador

```bash
git clone https://github.com/Jhon-Gonzzalez/habbi-platform.git habbi
cd habbi
bash deploy/probar-local.sh
```

Un solo comando: instala dependencias, configura **SQLite** (no hace falta
instalar MySQL), crea las tablas, genera datos de ejemplo y levanta el
servidor en <http://localhost:8000>.

Entra como administrador con `admin@habbi.test` / `habbi1234`.

> **¿No tienes PHP?** En Mac lo más cómodo es [Laravel Herd](https://herd.laravel.com)
> (gratis, instalador normal). En Linux, `sudo apt install php php-sqlite3 php-mbstring php-xml`.

### Instalación manual, con MySQL

```bash
composer install
cp .env.example .env
php artisan key:generate
# Configura DB_DATABASE, DB_USERNAME y DB_PASSWORD en .env, luego:
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

El seeder crea un administrador con las credenciales de `ADMIN_EMAIL` y
`ADMIN_PASSWORD` del `.env`, las tres tarifas de campaña de partida, y 20
alojamientos de ejemplo en Montería que **no** se generan cuando
`APP_ENV=production`. Las tarifas sí se crean también en producción: son
configuración del negocio, no datos de prueba, y se cambian desde el panel.

### Sobre los datos de ejemplo

Los barrios, calles, universidades y precios son **reales** (El Recreo, La
Granja, Los Alcázares, Mocarí, Cantaclaro, Santa Fe...), así que la aplicación
se prueba contra la ciudad de verdad y los enlaces a Google Maps llevan a
sitios que existen.

Los **negocios y las personas son inventados**, a propósito: no se copiaron
pensiones reales de Google Maps porque esos negocios no han dado permiso para
aparecer aquí, sus teléfonos recibirían llamadas por un dato de prueba y las
reseñas serían opiniones falsas sobre un negocio con nombre propio. Los
teléfonos usan el prefijo `300 555 ····` y los correos terminan en `.test`;
hay tests que lo vigilan.

Todas las cuentas de ejemplo usan la contraseña `habbi1234`.

### Verificación de correo: apagada por defecto

`HABBI_VERIFICAR_CORREO=false` en `config/habbi.php`. Con eso, quien se
registra **queda verificado al instante** y no se envía ningún correo, así
que el proyecto funciona recién clonado sin montar un servidor de correo.

**El código de verificación sigue entero.** No se borró nada: el middleware,
las rutas, la pantalla y los tests están ahí. Para encenderlo:

```ini
HABBI_VERIFICAR_CORREO=true
```

Y para las cuentas que ya existían sin verificar:

```bash
php artisan habbi:enlace --todos
```

> **Enciéndelo antes de publicar con usuarios reales.** Sin verificación,
> cualquiera se registra con un correo inventado las veces que quiera y lo
> usa para reseñar o reportar. Si la aplicación está en producción con esto
> apagado, el panel de administración lo avisa en rojo.

### El correo

**En local no se envía ningún correo de verdad.** `MAIL_MAILER=log` escribe
los mensajes en `storage/logs/laravel.log` en vez de mandarlos, así que por
más que te registres con tu Gmail, a tu Gmail no va a llegar nada. Es a
propósito: desarrollando no quieres mandar correos reales sin querer.

Para verificar una cuenta mientras trabajas en local, lo más corto:

```bash
php artisan habbi:enlace tucorreo@gmail.com              # te da el enlace
php artisan habbi:enlace tucorreo@gmail.com --verificar  # o la verifica ya
```

También puedes entrar como administrador y usar
**Usuarios → editar → Marcar el correo como verificado**.

#### Que lleguen de verdad

```bash
php artisan habbi:correo
```

Pregunta tu Gmail y la contraseña de aplicación, lo guarda en el `.env` y
**manda un correo de prueba** para que sepas en el momento si funcionó, en
vez de descubrirlo cuando un usuario se queje. Si falla, traduce el error de
SMTP a algo accionable.

Antes necesitas una contraseña de aplicación de Google, que es lo único que
no se puede automatizar:

1. Activa la verificación en dos pasos en
   <https://myaccount.google.com/security>
2. Crea la contraseña en <https://myaccount.google.com/apppasswords>

Son 16 letras. **No es la contraseña de tu Gmail**: Google eliminó el acceso
con la contraseña normal, así que no hay atajo. Si la página del paso 2 dice
que no está disponible, es que falta el paso 1.

Otros comandos:

```bash
php artisan habbi:correo --probar    # probar la configuración que ya hay
php artisan habbi:correo --log       # volver al modo local (no envía)
php artisan habbi:correo --host=smtp.otro.com --port=587   # otro proveedor
```

Gmail tiene un límite de envíos diarios, así que vale para probar y para
enseñárselo a alguien, pero **no para una plataforma con usuarios reales**.
Para eso hace falta un servicio de correo transaccional (Brevo, Resend,
Postmark) con su propio SMTP, que se configura con `--host` y `--port`.

#### Sacar el enlace del log a mano

Si prefieres el camino largo:

```bash
grep -o 'https\?://[^"<]*email/verify[^"<]*' storage/logs/laravel.log | tail -1 | sed 's/&amp;/\&/g'
```

(El `sed` del final es necesario: en el log el enlace va dentro de HTML y el
`&` aparece escapado como `&amp;`, que rompería la firma al pegarlo.)

Si prefieres no pelear con eso, entra como administrador y usa
**Usuarios → editar → Marcar el correo como verificado**.

## Tests

```bash
php artisan test
```

También corren **en GitHub** en cada push a `master` o a una rama `claude/*`,
contra PHP 8.2 y 8.4 (los dos extremos del rango soportado). Si algo se rompe,
aparece una X roja en el commit sin que tengas que probarlo a mano. La
configuración está en `.github/workflows/tests.yml`.

**392 pruebas, 1021 comprobaciones.** Además de lo obvio (publicación,
permisos, fotos, reseñas, renderizado de las vistas), buena parte existen para
fijar decisiones que de otro modo se perderían en el próximo refactor. Casi
todas comprueban que algo **NO** pasa:

- El administrador **no** puede ponerle contraseña a otro (`SeguridadAdminTest`).
- Un documento de verificación **no** es accesible para un tercero, y **no**
  sigue en el disco después de resolver la solicitud.
- Reportar un anuncio **no** lo oculta.
- **No** existe ninguna ruta para borrar la auditoría, y la auditoría **no**
  guarda ni el token de restablecimiento ni los números de cobro.
- El registro se completa **aunque el correo falle** (el test apunta el SMTP a
  un host inexistente y comprueba que redirige en vez de dar un 500).
- Una campaña pagada **no** se salta el orden que el estudiante pidió a mano:
  ni por precio, ni por calificación, ni por cercanía.
- La publicidad **no** aparece dentro de la rejilla de resultados, ni en la
  ficha, ni en el chat, y **no** carga ningún script de terceros.
- Media coordenada **no** se guarda, y un formulario vacío **no** deja el punto
  en 0,0 (que es una coordenada real, en el golfo de Guinea).
- Cambiar una tarifa **no** reescribe lo que alguien ya pagó.
- Los teléfonos de ejemplo usan un prefijo sin líneas asignadas.

Hay además un recorrido que entra con cada rol y visita las 41 pantallas:

```bash
bash deploy/recorrido.sh        # con el servidor levantado
```

Comprueba también que las que tienen que cerrar la puerta la cierran: un
estudiante en el panel debe recibir 403, no 200.

## Despliegue

Con acceso SSH al hosting, el despliegue son tres comandos:

```bash
git clone https://github.com/Jhon-Gonzzalez/habbi-platform.git habbi
cd habbi
bash deploy/instalar.sh
```

El script detecta el usuario de cPanel y su prefijo, localiza una versión
de PHP válida, instala dependencias, pide los datos de la base de datos,
migra, crea el administrador y enlaza el almacenamiento de fotos.

Para actualizar más adelante: `bash deploy/actualizar.sh`.

Guía completa, incluido el caso sin SSH: **[DEPLOY.md](DEPLOY.md)**.

## Comandos útiles

| Comando | Para qué |
|---|---|
| `php artisan migrate --seed` | Crear tablas y datos iniciales |
| `php artisan db:seed --class=AdminSeeder` | Solo el administrador (producción) |
| `php artisan storage:link` | Enlace para que se vean las fotos |
| `php artisan optimize` | Cachear configuración, rutas y vistas |
| `php artisan optimize:clear` | Limpiar todas las cachés |
| `php artisan habbi:respaldar --listar` | Ver las copias de seguridad que hay |
| `php artisan habbi:correo --probar` | Comprobar que el envío de correo funciona |
| `php artisan habbi:enlace correo@ejemplo.com` | Enlace de verificación de un usuario |
| `php artisan habbi:limpiar-documentos` | Borrar documentos de solicitudes ya resueltas |
| `composer audit` | Vulnerabilidades conocidas en las dependencias |

## Documentación

| Documento | Para qué |
|---|---|
| [`docs/donde-verlo.md`](docs/donde-verlo.md) | Dónde abrir cada requisito y qué tiene que pasar |
| [`docs/verificacion-requisitos.md`](docs/verificacion-requisitos.md) | Contraste con el documento del proyecto |
| [`docs/respaldos.md`](docs/respaldos.md) | Copias de seguridad: qué cubren, qué no, y cómo se restaura |
| [`DEPLOY.md`](DEPLOY.md) | Despliegue paso a paso, con y sin SSH |
