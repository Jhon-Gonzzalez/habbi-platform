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
habitaciones, apartaestudios o apartamentos disponibles.

| Módulo | Qué incluye |
|---|---|
| **Buscador público** | Filtros por texto, precio, tipo, capacidad y comodidades. Orden por precio, fecha o calificación. Paginación con filtros persistentes. |
| **Publicaciones** | Alta con hasta 8 fotos, edición con gestión de galería (añadir, quitar, elegir portada), pausar/reactivar y borrado con limpieza de archivos. |
| **Reseñas** | Una calificación de 1 a 5 estrellas por usuario y alojamiento, con comentario opcional. Nadie puede reseñar su propia publicación. |
| **Cuentas y roles** | Registro, login, recuperación de contraseña y verificación de correo. Registrarse te hace **estudiante**; para publicar hay que pasar una **verificación como arrendador** que aprueba un administrador. |
| **Perfiles** | Foto, descripción, ciudad y universidad. El arrendador tiene además perfil público con su sello de verificado y el promedio de sus reseñas, para que el estudiante vea con quién trata. |
| **Ruta a la universidad** | Cada publicación abre Google Maps con la ruta a pie desde el alojamiento hasta el campus (las cinco universidades de Montería). |
| **Favoritos** | Guardar alojamientos con un corazón y volver a ellos en `/mis-favoritos`. |
| **Reportes** | Avisar de anuncios falsos, estafas o precios que no corresponden. Un reporte **no** oculta la publicación: lo revisa una persona. |
| **Panel de administración** | Métricas, CRUD de usuarios con roles, moderación de publicaciones, revisión de solicitudes de arrendador, bandeja de reportes y **registro de auditoría**. |

## Decisiones técnicas

- **Sin paso de compilación en el frontend.** El CSS y el JS son archivos planos
  en `public/assets/`. No hace falta Node ni `npm run build` para desplegar,
  algo que en hosting compartido evita la mayoría de los problemas.
- **Autorización con policies.** `AlojamientoPolicy` y `RatingPolicy` deciden
  quién edita y quién borra; el middleware `admin` protege el panel.
- **Consultas sin N+1.** El scope `conResenas()` usa `withAvg` + `withCount`,
  así el listado de 12 tarjetas hace una consulta en lugar de veinticinco.
- **Las fotos viven fuera del repositorio,** en `storage/app/public`, expuestas
  mediante el enlace simbólico `public/storage`.
- **Los documentos de verificación NO son públicos.** Van al disco `private`
  (`storage/app/private`), sin enlace simbólico, y solo se sirven a través de un
  controlador que comprueba permisos. Una cédula escaneada no puede quedar
  accesible por URL.
- **El administrador no puede ver ni poner contraseñas.** No hay campo para
  escribir la contraseña de otro: se genera un enlace de restablecimiento que
  caduca y solo sirve para esa cuenta. `bcrypt` es irreversible, así que ni
  consultando la base de datos se leen.
- **Las acciones del panel quedan auditadas.** `Auditor::registrar()` escribe en
  `auditorias` quién hizo qué, cuándo y sobre qué. No hay ruta para borrar ni
  editar ese registro, y copia el nombre del actor en la fila para que borrar su
  cuenta no borre su historial.
- **Los destinos de Google Maps son una lista cerrada.** `Alojamiento::UNIVERSIDADES`
  se valida con `Rule::in`, así que el enlace nunca apunta a un sitio inventado.
- **El `composer.lock` se resuelve para PHP 8.2,** aunque quien ejecute
  `composer update` tenga 8.4. Lo fija `config.platform.php` en
  `composer.json`. Sin eso, actualizar desde una máquina con 8.4 produce un
  lock que solo se instala en 8.4, y el despliegue en un hosting con 8.2
  falla. Lo descubrió el CI, no una suposición.
- **Nada de peso muerto en `public/`.** Se borraron `public/plugins` (61 MB) y
  `public/dist` (25 MB): 2.024 archivos de una plantilla de administración que
  ninguna vista, hoja de estilo ni script referenciaba. Siguen en el historial
  de git por si alguna vez hicieran falta.
- **El fallo del correo no tumba el registro.** Un listener propio reemplaza al de
  Laravel, que devuelve un error 500 justo después de crear la cuenta cuando el
  SMTP está caído.

## Estructura

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/              Panel de administración
│   │   ├── AlojamientoController.php
│   │   ├── DashboardController.php
│   │   ├── HomeController.php
│   │   └── RatingController.php
│   ├── Middleware/
│   │   └── EnsureUserIsAdmin.php
│   └── Requests/               Validación (AlojamientoRequest, RatingRequest, UsuarioRequest)
├── Models/                     Alojamiento, Rating, User
├── Policies/                   AlojamientoPolicy, RatingPolicy
└── Services/
    └── AlojamientoPhotoService.php   Subida, borrado y orden de las fotos

resources/views/
├── layouts/        app · auth · admin
├── partials/       navbar · footer · flash · listing-card
├── components/     estrellas (componente Blade)
├── alojamientos/   index · show · create · edit · mine · _campos
├── admin/          index · usuarios/* · alojamientos/*
└── auth/           login · register · passwords/*

public/assets/
├── css/habbi.css   Sistema de diseño completo (tokens + componentes)
├── js/habbi.js     Navbar, menús, galería, estrellas, subida de fotos
└── img/            Logos e imágenes
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
`ADMIN_PASSWORD` del `.env`, más 20 alojamientos de ejemplo en Montería que
**no** se generan cuando `APP_ENV=production`.

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

164 pruebas funcionales. Además de lo obvio (publicación, permisos, fotos,
reseñas, renderizado de las vistas), varias existen para fijar decisiones que
de otro modo se perderían en el próximo refactor:

- El administrador **no** puede ponerle contraseña a otro (`SeguridadAdminTest`).
- Un documento de verificación **no** es accesible para un tercero.
- Reportar un anuncio **no** lo oculta.
- La auditoría **no** guarda el token de restablecimiento.
- **No** existe ninguna ruta para borrar la auditoría.
- El registro se completa **aunque el correo falle** (el test apunta el SMTP a
  un host inexistente y comprueba que redirige en vez de dar un 500).
- Los teléfonos de ejemplo usan un prefijo sin líneas asignadas.

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
