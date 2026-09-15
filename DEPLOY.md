# Desplegar HABBI en Namecheap (cPanel)

Guía completa para el plan **Stellar Plus** con cPanel.
Tiempo estimado: 30–45 minutos la primera vez.

---

## Vía rápida (tienes SSH, así que esta es la tuya)

Con acceso SSH todo el despliegue son tres comandos. El script detecta tu
usuario de cPanel, busca la versión correcta de PHP, instala dependencias,
te pregunta los datos de la base de datos y deja el sitio funcionando.

```bash
ssh TU_USUARIO@TU_SERVIDOR            # IP o dominio del hosting
git clone https://github.com/Jhon-Gonzzalez/habbi-platform.git habbi
cd habbi && bash deploy/instalar.sh
```

El script se puede ejecutar varias veces sin romper nada: si algo falla
(por ejemplo, que aún no hayas creado la base de datos), te dice qué
corregir y lo vuelves a lanzar.

**Antes de lanzarlo**, crea la base de datos siguiendo el paso 1.
**Después**, te quedará un único paso manual: apuntar el Document Root
(paso 3).

Para actualizar el sitio en el futuro:

```bash
cd ~/habbi && bash deploy/actualizar.sh
```

---

## ¿Qué es el «prefijo» de cPanel?

En hosting compartido hay cientos de cuentas en el mismo servidor MySQL, así
que **cPanel añade tu usuario delante del nombre** de cada base de datos y
cada usuario que creas. No lo eliges tú: aparece ya escrito y bloqueado en
el formulario.

Si tu usuario de cPanel es `usuario` y escribes `habbi` en el campo, la base
se llamará realmente:

```
usuario_habbi
      ↑
      este trozo es el prefijo
```

**En el `.env` tienes que poner el nombre completo con prefijo**, no solo
`habbi`. Ese es el error que más rompe los despliegues en cPanel.

Para ver tu prefijo tienes tres formas:

- Conéctate por SSH y ejecuta `whoami` — eso es exactamente tu prefijo.
- Entra en cPanel → **MySQL® Databases**: verás el prefijo escrito en gris
  a la izquierda del campo de texto.
- Míralo arriba a la derecha en cPanel, junto a «Current User».

El script `deploy/instalar.sh` lo detecta solo y te lo muestra, así que si
usas la vía rápida no necesitas averiguarlo por tu cuenta.

---

## Elegir dónde se publica

Tienes tres opciones. La correcta depende de si el dominio principal del
hosting ya se está usando para otra cosa.

| Situación | Qué hacer |
|---|---|
| El dominio principal está libre | Publica HABBI ahí directamente |
| El dominio principal ya tiene otro sitio | Crea un **subdominio**, p. ej. `habbi.tudominio.com` |
| Quieres un dominio aparte | Añádelo al hosting como **dominio adicional** |

Un subdominio es gratis, se crea en un minuto y **no afecta en nada** a lo
que ya esté publicado en el dominio principal.

### Crear un subdominio

1. cPanel → busca **Domains** en el buscador de arriba.
2. Pulsa **Create A New Domain**.
3. En *Domain* escribe el subdominio completo: `habbi.tudominio.com`
4. **Desmarca** la casilla *«Share document root with…»*.
5. En *Document Root* escribe: `habbi/public`
6. **Submit**.

Si los nameservers del dominio ya son los del hosting
(`dns1/dns2.namecheaphosting.com`), cPanel crea solo el registro DNS del
subdominio y **no hay que editar nada a mano**. Los registros del dominio
principal quedan intactos.

Para comprobar a dónde apunta un dominio antes de tocar nada:

```bash
dig +short A tudominio.com
dig +short NS tudominio.com
```

La IP de tu servidor la ves en cPanel, en el panel derecho, como
*Shared IP Address*.

### Activar HTTPS

cPanel → **SSL/TLS Status** → marca el dominio o subdominio →
**Run AutoSSL**. Tarda unos minutos.

---

## 0. Antes de empezar

Comprueba en cPanel:

| Qué | Dónde | Valor necesario |
|---|---|---|
| Versión de PHP | **MultiPHP Manager** | 8.1 o superior (ideal 8.2) |
| Extensiones | **Select PHP Version → Extensions** | `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `gd` |
| Terminal / SSH | **Terminal** en el menú | Si lo tienes, el despliegue es mucho más rápido |

> Si **Terminal** no aparece en tu cPanel, no pasa nada: la sección 6B
> explica cómo hacerlo todo sin línea de comandos.

---

## 1. Crear la base de datos

1. cPanel → **MySQL® Databases**.
2. En *Create New Database* escribe `habbi` → **Create Database**.
   cPanel le añadirá tu prefijo, quedando algo como `usuario_habbi`.
3. En *MySQL Users → Add New User*:
   - Username: `habbi`  → quedará `usuario_habbi`
   - Usa **Password Generator** y **guarda esa contraseña**, la necesitarás en el `.env`.
4. En *Add User To Database*: selecciona el usuario y la base que acabas de crear → **Add**.
5. En la pantalla de privilegios marca **ALL PRIVILEGES** → **Make Changes**.

Anota estos tres datos:

```
DB_DATABASE = usuario_habbi
DB_USERNAME = usuario_habbi
DB_PASSWORD = la-contraseña-generada
DB_HOST     = localhost
```

---

## 2. Subir el proyecto

### Opción A — con Git (recomendada, si tienes Terminal)

```bash
cd ~
git clone https://github.com/jhon-gonzzalez/habbi-platform.git habbi
```

El proyecto queda en `/home/usuario/habbi`, **fuera** de `public_html`.
Esto es lo más seguro: nadie puede acceder por web a tu `.env` ni a `app/`.

### Opción B — sin Git

1. Descarga el repositorio como ZIP desde GitHub.
2. cPanel → **File Manager** → sube el ZIP a `/home/usuario/`.
3. Clic derecho → **Extract**. Renombra la carpeta a `habbi`.

---

## 3. Apuntar el dominio a la carpeta `public`

Laravel **nunca** debe servirse desde la raíz del proyecto: solo la carpeta
`public` puede ser accesible desde internet. Todo lo demás (tu `.env`, el
código, las dependencias) tiene que quedar fuera del alcance del navegador.

Por eso el dominio (o subdominio) se crea con *Document Root* =
`habbi/public`, como se explica en **«Elegir dónde se publica»** más arriba.

Si por lo que sea el Document Root te quedó apuntando a `habbi` en lugar de
`habbi/public`, tienes dos salidas:

- **Corregirlo** en cPanel → Domains → tu dominio → *Manage* →
  Document Root. Es lo recomendable.
- **Dejarlo así** y confiar en el `.htaccess` de la raíz del repositorio,
  que reescribe las peticiones hacia `/public` y bloquea el acceso directo
  a `.env`, `composer.json` y `artisan`.

---

## 4. Instalar las dependencias

### Con Terminal

```bash
cd ~/habbi
php -v                      # confirma que sea 8.1+
composer install --no-dev --optimize-autoloader
```

Si `composer` no está instalado:

```bash
cd ~/habbi
curl -sS https://getcomposer.org/installer | php
php composer.phar install --no-dev --optimize-autoloader
```

### Sin Terminal

Ejecuta en tu computador `composer install --no-dev --optimize-autoloader`,
comprime la carpeta `vendor/` resultante y súbela por File Manager junto al
resto del proyecto. Asegúrate de que tu PHP local sea de la misma versión mayor
que la del servidor.

---

## 5. Configurar el `.env`

```bash
cd ~/habbi
cp .env.example .env
php artisan key:generate
```

Edita `.env` (con `nano .env` o desde File Manager → *Edit*):

```ini
APP_NAME=HABBI
APP_ENV=production
APP_KEY=base64:...        # lo generó key:generate, no lo toques
APP_DEBUG=false           # IMPORTANTE: false en producción
APP_URL=https://tudominio.com
APP_TIMEZONE=America/Bogota

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=usuario_habbi
DB_USERNAME=usuario_habbi
DB_PASSWORD=la-contraseña-del-paso-1

FILESYSTEM_DISK=public
LOG_LEVEL=error

ADMIN_NAME="Tu Nombre"
ADMIN_EMAIL=tucorreo@tudominio.com
ADMIN_PASSWORD=una-contraseña-fuerte-y-única
```

> **`APP_DEBUG=false` no es opcional.** En `true`, cualquier error muestra tus
> credenciales de base de datos en pantalla a quien visite el sitio.

---

## 6. Crear las tablas

### 6A. Con Terminal

```bash
cd ~/habbi
php artisan migrate --force
php artisan db:seed --class=AdminSeeder --force
php artisan storage:link
php artisan optimize
```

### 6B. Sin Terminal

1. **Migraciones:** en tu computador ejecuta `php artisan migrate` contra una
   base local, exporta el SQL resultante y súbelo por **phpMyAdmin → Importar**.
2. **Administrador:** créalo desde phpMyAdmin insertando una fila en `users`
   con `role = 'admin'` y el campo `password` con un hash bcrypt
   (genera uno en tu máquina con `php artisan tinker` → `bcrypt('tuclave')`).
3. **Enlace de fotos:** en File Manager, entra en `habbi/public` y crea el
   enlace simbólico `storage` apuntando a `habbi/storage/app/public`.
   Si File Manager no permite enlaces simbólicos, crea el archivo
   `public/enlace.php` con:

   ```php
   <?php symlink(__DIR__.'/../storage/app/public', __DIR__.'/storage'); echo 'listo';
   ```

   Visítalo una vez en el navegador y **bórralo inmediatamente después**.

---

## 7. Permisos de carpetas

```bash
cd ~/habbi
chmod -R 755 storage bootstrap/cache
```

Desde File Manager: selecciona `storage` y `bootstrap/cache` → **Permissions** →
`755`, marcando *Recurse into subdirectories*.

Si el sitio responde con error 500 al escribir, prueba `775`.

---

## 8. Activar HTTPS

1. cPanel → **SSL/TLS Status** → marca tu dominio → **Run AutoSSL**.
2. Espera unos minutos a que el certificado se emita.
3. El `.htaccess` de `public/` ya fuerza la redirección a HTTPS.

---

## 9. Comprobaciones finales

| Comprueba | Debe pasar |
|---|---|
| `https://tudominio.com` | Carga la página de inicio |
| `https://tudominio.com/alojamientos` | Carga el buscador |
| Registrar una cuenta | Redirige a «Mi cuenta» |
| Publicar un alojamiento con fotos | **Las fotos se ven** (si no, falta `storage:link`) |
| `https://tudominio.com/.env` | Debe dar **403 o 404**, nunca mostrar el contenido |
| Entrar con el admin → `/admin` | Carga el panel |

---

## 10. Actualizar el sitio más adelante

```bash
cd ~/habbi
php artisan down                    # modo mantenimiento
git pull origin master
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
php artisan optimize
php artisan up
```

---

## Problemas frecuentes

| Síntoma | Causa | Solución |
|---|---|---|
| Pantalla en blanco | `APP_KEY` vacía o permisos de `storage` | `php artisan key:generate` y `chmod -R 755 storage` |
| Error 500 | Revisa `storage/logs/laravel.log` | Casi siempre es la conexión a la base de datos |
| Las fotos no se ven | Falta el enlace simbólico | `php artisan storage:link` |
| «No application encryption key» | `APP_KEY` vacía | `php artisan key:generate` |
| Ves el listado de carpetas | El Document Root no apunta a `habbi/public` | Corrígelo en cPanel → Domains |
| Los cambios no aparecen | Caché de configuración | `php artisan optimize:clear` |
| Error de conexión a MySQL | Falta el prefijo del usuario | El nombre real es `usuario_habbi`, no `habbi` |

---

## Resumen de lo que queda en tus manos

1. **Crear la base de datos** en cPanel (paso 1) — el nombre real llevará tu prefijo.
2. **Ejecutar `bash deploy/instalar.sh`** por SSH.
3. **Apuntar el Document Root** a `habbi/public` (paso 3).
4. **Crear el dominio o subdominio** en cPanel con Document Root `habbi/public`.

Nunca compartas tu `.env` ni tus contraseñas: el script te las pide en el
servidor y las escribe directamente allí.
