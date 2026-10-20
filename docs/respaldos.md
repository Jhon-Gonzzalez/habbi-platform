# Copias de seguridad (RNF-3)

## Qué hace el proyecto solo

Cada noche, a la hora que diga `HABBI_RESPALDOS_HORA` (por defecto 03:20
hora de Colombia), el programador de tareas de Laravel ejecuta:

```
php artisan habbi:respaldar
```

Eso:

1. Hace una copia de la base de datos en `storage/app/respaldos/`.
   - En **SQLite** copia el fichero, después de volcar el WAL para que no
     se queden fuera los últimos cambios.
   - En **MySQL** usa `mysqldump --single-transaction` y comprime con
     `gzip`, así el volcado no bloquea la aplicación mientras corre.
2. Borra los respaldos con más de `HABBI_RESPALDOS_DIAS` días (14).
3. Escribe una línea en la auditoría, para que desde el panel se pueda
   responder «¿cuándo fue el último respaldo?» sin entrar por SSH.

La carpeta se crea con permisos `0700` y los archivos con `0600`: un
volcado de la base lleva correos, teléfonos y contraseñas cifradas, y
está **fuera de `public/`**, así que no hay ninguna dirección web que
llegue a él.

Comandos sueltos:

```
php artisan habbi:respaldar            # respaldar y rotar ahora
php artisan habbi:respaldar --listar   # ver qué hay guardado
php artisan habbi:respaldar --dias=30  # conservar 30 días esta vez
```

## Lo que hay que hacer en el servidor (una sola vez)

El programador de Laravel no corre por su cuenta: necesita que el cron
del sistema lo despierte cada minuto. En el servidor, con `crontab -e`:

```
* * * * * cd /ruta/al/proyecto && php artisan schedule:run >> /dev/null 2>&1
```

Sin esa línea, **no hay respaldos**, aunque el código esté. Para
comprobar que la tarea está registrada:

```
php artisan schedule:list
```

Si el servidor es MySQL, `mysqldump` tiene que estar instalado y en el
`PATH` del usuario que corre el cron. Se comprueba con
`php artisan habbi:respaldar`: si falla, lo dice con el error de
`mysqldump` tal cual.

## Lo que esto NO resuelve

**El respaldo está en el mismo disco que la base de datos.** Eso protege
de un borrado por error, de una migración mal hecha o de un administrador
que elimina lo que no debía. No protege de perder el servidor: si el
disco se corrompe o el proveedor cierra la cuenta, se pierden la base y
las copias a la vez.

Para cubrir eso hace falta sacar la carpeta del servidor. No está en el
código porque depende de dónde se despliegue, y escribir credenciales de
un proveedor concreto en el repositorio sería peor que no tenerlo. Dos
formas, de menos a más trabajo:

- **A mano, una vez por semana.** Descargar la carpeta con `scp`:
  ```
  scp -r usuario@servidor:/ruta/al/proyecto/storage/app/respaldos ./copias
  ```
  Es poco, pero es muchísimo más que nada.

- **Automático.** Añadir al cron del servidor una copia a un
  almacenamiento externo (`rclone` sirve para Google Drive, Dropbox, S3 o
  Backblaze). Por ejemplo:
  ```
  30 4 * * * rclone copy /ruta/al/proyecto/storage/app/respaldos remoto:habbi-respaldos
  ```

Las fotos de los alojamientos tampoco están en la base de datos: viven
en `storage/app/public`. Un respaldo completo incluye esa carpeta además
de la base.

## Cómo se restaura

Lo que no se ha probado a restaurar no es un respaldo. Conviene hacerlo
una vez, en local, antes de necesitarlo de verdad.

**SQLite:**

```
php artisan down
cp storage/app/respaldos/habbi_2026-01-15_032000.sqlite database/database.sqlite
php artisan up
```

**MySQL:**

```
php artisan down
gunzip < storage/app/respaldos/habbi_2026-01-15_032000.sql.gz | mysql -u USUARIO -p BASE
php artisan up
```

Después, `php artisan migrate --pretend` dice si al respaldo le faltan
migraciones respecto al código que hay desplegado.
