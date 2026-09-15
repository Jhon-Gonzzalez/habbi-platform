# Desplegar HABBI en Namecheap (cPanel) — eliandval.com

Guía completa para el plan **Stellar Plus** con cPanel.
Tiempo estimado: 30–45 minutos la primera vez.

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
   cPanel le añadirá tu prefijo, quedando algo como `elianXXX_habbi`.
3. En *MySQL Users → Add New User*:
   - Username: `habbi`  → quedará `elianXXX_habbi`
   - Usa **Password Generator** y **guarda esa contraseña**, la necesitarás en el `.env`.
4. En *Add User To Database*: selecciona el usuario y la base que acabas de crear → **Add**.
5. En la pantalla de privilegios marca **ALL PRIVILEGES** → **Make Changes**.

Anota estos tres datos:

```
DB_DATABASE = elianXXX_habbi
DB_USERNAME = elianXXX_habbi
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

El proyecto queda en `/home/elianXXX/habbi`, **fuera** de `public_html`.
Esto es lo más seguro: nadie puede acceder por web a tu `.env` ni a `app/`.

### Opción B — sin Git

1. Descarga el repositorio como ZIP desde GitHub.
2. cPanel → **File Manager** → sube el ZIP a `/home/elianXXX/`.
3. Clic derecho → **Extract**. Renombra la carpeta a `habbi`.

---

## 3. Apuntar el dominio a la carpeta `public`

Laravel **nunca** debe servirse desde la raíz del proyecto: solo la carpeta
`public` puede ser accesible desde internet.

### Opción A — cambiar el Document Root (la correcta)

1. cPanel → **Domains**.
2. Busca `eliandval.com` → **Manage**.
3. En *Document Root* pon: `habbi/public`
4. **Save**.

### Opción B — si no puedes cambiar el Document Root

Sube todo el proyecto **dentro** de `public_html` y usa el archivo `.htaccess`
que ya viene en la raíz del repositorio: reescribe las peticiones hacia
`/public` automáticamente y bloquea el acceso a `.env`, `composer.json` y
`artisan`.

> La opción A es más segura y más rápida. Usa la B solo si la A no está disponible.

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
APP_URL=https://eliandval.com
APP_TIMEZONE=America/Bogota

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=elianXXX_habbi
DB_USERNAME=elianXXX_habbi
DB_PASSWORD=la-contraseña-del-paso-1

FILESYSTEM_DISK=public
LOG_LEVEL=error

ADMIN_NAME="Tu Nombre"
ADMIN_EMAIL=tucorreo@eliandval.com
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

1. cPanel → **SSL/TLS Status** → marca `eliandval.com` → **Run AutoSSL**.
2. Espera unos minutos a que el certificado se emita.
3. El `.htaccess` de `public/` ya fuerza la redirección a HTTPS.

---

## 9. Comprobaciones finales

| Comprueba | Debe pasar |
|---|---|
| `https://eliandval.com` | Carga la página de inicio |
| `https://eliandval.com/alojamientos` | Carga el buscador |
| Registrar una cuenta | Redirige a «Mi cuenta» |
| Publicar un alojamiento con fotos | **Las fotos se ven** (si no, falta `storage:link`) |
| `https://eliandval.com/.env` | Debe dar **403 o 404**, nunca mostrar el contenido |
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
| Ves el listado de carpetas | El Document Root no apunta a `public` | Repite el paso 3 |
| Los cambios no aparecen | Caché de configuración | `php artisan optimize:clear` |
| Error de conexión a MySQL | Falta el prefijo del usuario | El nombre real es `elianXXX_habbi`, no `habbi` |

---

## Qué necesito de ti para ayudarte con el resto

Si quieres que deje el despliegue terminado, hazme llegar:

1. Si tu cPanel tiene **Terminal / acceso SSH** (sí o no).
2. La **versión de PHP** que aparece en MultiPHP Manager.
3. Tu **prefijo de cPanel** (el `elianXXX` de los ejemplos).
4. Si el dominio `eliandval.com` ya apunta a este hosting o sigue en otro sitio.

**Nunca me envíes contraseñas ni el contenido de tu `.env`.** Esos valores los
escribes tú directamente en el servidor.
