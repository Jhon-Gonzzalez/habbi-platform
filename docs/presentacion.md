# HABBI — Guion para la sustentación

Todo lo que hay aquí sale del código, no de la memoria. Los números son
los de hoy; si algo cambia, se vuelven a sacar con los comandos del final.

---

## 1. Qué es, en una frase

Una plataforma web donde los estudiantes de Montería encuentran
alojamiento cerca de su universidad, y los arrendadores publican lo que
tienen, con un chat interno que deja constancia de lo que se promete.

**Está en línea:** https://habbi.space

---

## 2. Con qué está hecho

| Capa | Tecnología | Versión |
|---|---|---|
| Lenguaje | **PHP** | 8.4 en el servidor (mínimo 8.2) |
| Framework | **Laravel** | 12 |
| Base de datos | **MariaDB** (compatible MySQL) | 10.x |
| Plantillas | **Blade** (el motor de Laravel) | — |
| Mapas | **Leaflet** + OpenStreetMap | 1.9.4 |
| PDF | **dompdf** (`barryvdh/laravel-dompdf`) | 3.1 |
| Servidor web | Apache / LiteSpeed sobre cPanel | — |
| Pruebas | **PHPUnit** | 11.5 |

### Librerías y para qué sirve cada una

- **laravel/framework** — el esqueleto: rutas, ORM, sesiones, validación, seguridad.
- **barryvdh/laravel-dompdf** — genera los informes del administrador en PDF.
- **guzzlehttp/guzzle** — cliente HTTP (lo usa Laravel por dentro).
- **laravel/ui** — el andamiaje de registro e inicio de sesión.
- **laravel/tinker** — consola interactiva, solo para desarrollo.
- **Leaflet** — el mapa. Va **descargado dentro del proyecto**, no desde internet.

> **Dato que conviene decir:** Leaflet está servido desde el propio
> servidor, no desde un CDN externo. Así la página no depende de que un
> tercero esté arriba, y ningún servicio ajeno ve quién visita HABBI.

### Una decisión que vale la pena defender

**No hay React, ni Vue, ni paso de compilación.** No existe `package.json`
ni `npm run build`. El JavaScript son 718 líneas escritas a mano en 4
archivos, y el CSS son 1.750 líneas propias.

Por qué, si te preguntan:

1. **Funciona sin JavaScript.** El buscador, los filtros y los formularios
   funcionan con el JS apagado. En Montería mucha gente entra con datos
   móviles y teléfonos modestos.
2. **Se despliega en hosting compartido de $1 al mes.** Un proyecto con
   React necesita Node en el servidor o un paso de compilación. Esto se
   sube y funciona.
3. **Menos dependencias, menos vulnerabilidades.** Un proyecto típico de
   React arrastra cientos de paquetes de terceros.

---

## 3. Cuánto código es

```
PHP            19.740 líneas   ·   200 archivos
Plantillas      6.032 líneas   ·    68 vistas Blade
CSS             1.750 líneas
JavaScript        718 líneas   ·     4 archivos
───────────────────────────────────────────────
Pruebas           454 automáticas · 1.178 comprobaciones
```

---

## 4. Cómo está organizado (arquitectura)

Es **MVC** (Modelo–Vista–Controlador), el patrón de Laravel, con una capa
extra de **servicios** para la lógica de negocio.

```
Navegador
   ↓
Rutas (routes/web.php)        ← qué URL lleva a dónde
   ↓
Middleware (12)               ← ¿está autenticado? ¿verificado? ¿es admin?
   ↓
Controladores (32)            ← reciben la petición, responden
   ↓
Form Requests (6)             ← validan los datos de entrada
   ↓
Servicios (10)                ← LA LÓGICA DE NEGOCIO
   ↓
Modelos (14)                  ← hablan con la base de datos
   ↓
Vistas Blade (68)             ← el HTML que se devuelve
```

**Políticas (3)** — responden «¿este usuario puede hacer esto con este
objeto?». Por ejemplo: solo el dueño edita su publicación.

### Los 10 servicios, y qué hace cada uno

| Servicio | Responsabilidad |
|---|---|
| `ChatService` | Abrir conversaciones, enviar mensajes y adjuntos, el acuerdo |
| `CampanaService` | Activar y retirar campañas pagadas, registrar el cobro |
| `RevisionAlojamientoService` | Aprobar o rechazar publicaciones |
| `VerificacionArrendadorService` | Documentos de identidad, aprobar arrendadores |
| `AlojamientoPhotoService` | Subir, ordenar y borrar las fotos |
| `ImagenOptimizada` | Reducir y recomprimir imágenes antes de guardarlas |
| `ImagenMarcador` | Genera la imagen de la publicación para compartir |
| `ReporteEstadisticas` | Los números de los informes |
| `RespaldoBaseDeDatos` | La copia de seguridad diaria |
| `Auditor` | Deja rastro de cada acción del administrador |

> **Por qué hay servicios y no todo en el controlador:** el controlador
> solo debe recibir la petición y responder. Si la lógica vive ahí, no se
> puede reutilizar ni probar por separado. Activar una campaña se hace hoy
> desde el panel; mañana podría hacerlo una pasarela de pagos, y solo
> cambiaría quién llama al servicio.

---

## 5. La base de datos

**19 tablas.** Las que importan:

| Tabla | Qué guarda | Columnas |
|---|---|---|
| `users` | Las personas. Un campo `role` define qué es cada una | 15 |
| `alojamientos` | Las publicaciones: precio, barrio, coordenadas, fotos, estado | 29 |
| `conversaciones` | Un hilo entre un estudiante y un arrendador | 11 |
| `mensajes` | Cada mensaje, con su adjunto opcional | 11 |
| `ratings` | Las reseñas y calificaciones | 7 |
| `solicitudes_arrendador` | Verificación de identidad de quien quiere publicar | 13 |
| `solicitudes_campana` | Peticiones de promoción, con comprobante de pago | 17 |
| `campanas` | El registro contable de lo que se cobró | 12 |
| `tarifas_campana` | Los planes y sus precios, editables desde el panel | 10 |
| `auditorias` | Rastro de todo lo que hace un administrador | 12 |
| `reportes` | Denuncias de publicaciones | 11 |
| `aliados` | Los anuncios de negocios locales | 14 |
| `favoritos` | Lo que cada estudiante guardó | 5 |

### Cómo se habla con ella

**No se escribe SQL a mano casi nunca.** Se usa **Eloquent**, el ORM de
Laravel: cada tabla tiene una clase PHP, y las consultas se escriben como
métodos encadenados.

```php
Alojamiento::disponibles()
    ->conResenas()
    ->orderByDesc('ratings_avg_rating')
    ->take(4)
    ->get();
```

Eso genera el SQL solo, **y escapa los parámetros**, que es lo que impide
la inyección SQL.

### Las migraciones

La estructura de la base de datos **está en el código**, no se hace a mano
en phpMyAdmin. Cada cambio es un archivo en `database/migrations/`. Eso
significa que montar la base en un servidor nuevo es un solo comando, y que
el historial de cambios está en Git.

---

## 6. Los tres roles

```php
ROLE_ESTUDIANTE   ROLE_ARRENDADOR   ROLE_ADMIN
```

| Puede… | Estudiante | Arrendador | Admin |
|---|---|---|---|
| Buscar y filtrar | Sí | Sí | Sí |
| Guardar favoritos | Sí | Sí | Sí |
| Escribir por el chat | Sí | Sí | — |
| Dejar reseñas | Sí | Sí | — |
| Publicar alojamientos | — | Sí *(verificado)* | — |
| Pedir campañas | — | Sí | — |
| Aprobar publicaciones | — | — | Sí |
| Verificar arrendadores | — | — | Sí |
| Ver informes y auditoría | — | — | Sí |
| **Leer conversaciones ajenas** | **No** | **No** | **No** |

> Esa última fila es importante y conviene destacarla: **ni siquiera el
> administrador puede leer los chats**. Hay una prueba automática que lo
> comprueba, y otra que comprueba que tampoco puede abrir los adjuntos.

---

## 7. Los flujos principales

### Publicar un alojamiento
1. El usuario pide ser arrendador y **sube su cédula y una prueba del inmueble**.
2. Un administrador la verifica. Los documentos se **borran solos** al resolverla.
3. Ya verificado, publica: fotos, precio, barrio, punto en el mapa.
4. La publicación **nace pendiente**: no se ve hasta que un administrador la aprueba.

### Buscar
Filtros por ciudad, barrio, precio, tipo, comodidades y **distancia a la
universidad**. La distancia se calcula con la **fórmula de Haversine**
directamente en SQL, con un prefiltro por caja para no recorrer toda la tabla.

### El chat y el acuerdo
1. El estudiante escribe desde la ficha. Se abre un hilo.
2. Pueden mandarse **fotos y PDF**.
3. Cuando se ponen de acuerdo, uno **propone el acuerdo** y el otro lo confirma.
4. Al confirmarse, **bajan los cupos** y la publicación sale del buscador.

> **Por qué el chat está dentro y no es WhatsApp:** porque queda constancia.
> Si alguien promete algo y luego lo niega, está escrito.

### Monetización
Dos vías, las dos sin publicidad invasiva ni rastreo de terceros:
1. **Campañas** — el arrendador paga para salir primero, por días, no por meses.
2. **Aliados** — anuncios de negocios locales, vendidos directamente.

---

## 8. Seguridad y privacidad

Esto suele ser lo que más preguntan.

| Qué | Cómo está resuelto |
|---|---|
| Contraseñas | Se guardan con **bcrypt**, nunca en texto plano |
| Inyección SQL | Eloquent escapa todos los parámetros |
| XSS | Blade escapa toda la salida por defecto (`{{ }}`) |
| CSRF | Token obligatorio en cada formulario |
| Documentos de identidad | Disco **privado**, fuera de la web, y **se borran al resolver** |
| Comprobantes y adjuntos | Disco privado, entregados tras comprobar quién pide |
| Permisos | Políticas por objeto, no solo por rol |
| Rastro | Toda acción de administrador queda en `auditorias` |
| HTTPS | Certificado activo, con redirección forzada |

**Ley 1581 de 2012** (protección de datos en Colombia): hay política de
privacidad y términos, y el usuario puede pedir que se borren sus datos.

### Lo que hay que decir con honestidad si preguntan

- **Los mensajes del chat NO están cifrados de extremo a extremo.** Están
  en texto plano en la base de datos. Solo los leen los dos participantes
  —lo impide el código, no la criptografía— y aparecen en los respaldos.
- **Los respaldos cubren la base de datos, no los archivos subidos.**

Decirlo tú antes de que te lo pregunten se ve mucho mejor que lo contrario.

---

## 9. Calidad: 454 pruebas automáticas

```bash
php artisan test
→ 454 passed (1178 assertions)
```

No son adorno. Ejemplos de lo que comprueban:

- Que un tercero no pueda leer una conversación ajena.
- Que pedir una campaña **no** la active (solo activarla la activa).
- Que el administrador no pueda abrir los adjuntos de un chat.
- Que una publicación pendiente no salga en el buscador.
- Que el buscador funcione **con JavaScript apagado**.
- Que las fotos se reduzcan al subirlas.

> Si te preguntan cómo sabes que funciona: esa es la respuesta.

---

## 10. El despliegue: cómo llegó a internet

### Dónde vive

| | |
|---|---|
| Hosting | **Namecheap**, plan Stellar compartido con cPanel |
| Servidor | `server394.web-hosting.com` — datacenter en Estados Unidos |
| Dominio | **habbi.space**, registrado en Namecheap |
| PHP | 8.4, elegido desde cPanel |
| Base de datos | MariaDB, creada desde cPanel |

### Los pasos, en orden

1. **Se contrató el hosting y el dominio.** Namecheap apunta el dominio al
   servidor automáticamente, porque los dos están en la misma cuenta.
2. **Se eligió PHP 8.4** en cPanel y se activaron las extensiones que el
   proyecto necesita (`pdo_mysql`, `mbstring`, `gd`, `fileinfo`…).
3. **Se creó la base de datos** en cPanel. Importante: en hosting
   compartido, cPanel **añade tu usuario como prefijo**, así que la base no
   se llama `habbi` sino `habbbmxj_habbi`.
4. **Se activó el acceso SSH** y se conectó por terminal:
   `ssh -p 21098 habbbmxj@68.65.123.88`
5. **Se clonó el proyecto desde GitHub** y se lanzó el instalador:
   `bash deploy/instalar.sh`, que hace nueve pasos (dependencias, `.env`,
   clave de cifrado, migraciones, cuenta de administrador, permisos, caché).
6. **Se apuntó el dominio a la carpeta correcta.** Este es el paso fino:

   > Laravel **nunca** se sirve desde la raíz del proyecto, porque ahí está
   > el `.env` con las contraseñas. Solo la carpeta `public/` puede verse
   > desde internet. Como cPanel no deja cambiar la carpeta del dominio
   > principal, se resolvió con un **enlace simbólico**:
   > `public_html → habbi/public`.

7. **Se programó una tarea** (cron job) cada cinco minutos que ejecuta el
   programador de Laravel. De ahí salen los **respaldos diarios** de la
   base de datos a las 3:20 a.m.
8. **SSL automático** de Namecheap, con redirección forzada a HTTPS.

### Cómo se actualiza

```bash
ssh -p 21098 habbbmxj@68.65.123.88
cd ~/habbi && bash deploy/actualizar.sh
```

Ese script pone el sitio en mantenimiento, baja los cambios de GitHub,
actualiza dependencias, aplica migraciones, regenera cachés y lo levanta.
**Unos 30 segundos.**

---

## 11. Preguntas que probablemente te hagan

**«¿Por qué PHP y no Python o JavaScript?»**
Porque es lo que corre en el 99% del hosting compartido asequible en
Colombia. El proyecto está en línea por un dólar. Con Node o Django habría
hecho falta un servidor dedicado o un servicio de pago.

**«¿Cómo evitas que alguien publique algo falso?»**
Dos filtros: el arrendador se verifica con cédula y prueba del inmueble, y
cada publicación la aprueba un administrador antes de verse.

**«¿Qué pasa si alguien denuncia una publicación?»**
Hay un sistema de reportes. El administrador los ve en su panel, los
resuelve, y queda registrado quién lo hizo y cuándo.

**«¿Cómo se gana dinero?»**
Campañas por días y anuncios de negocios locales. Sin rastreo de terceros,
porque los usuarios son estudiantes y sus datos no son el producto.

**«¿Funciona en el celular?»**
Sí, y se diseñó pensando en eso: dos columnas de resultados, filtros que se
plegan, botones de tamaño suficiente para el dedo, y las fotos se reducen
en el navegador antes de subirlas para no gastar los datos.

**«¿Qué falta?»**
Ser honesto: pasarela de pagos automática, cifrado de extremo a extremo en
el chat, respaldo de los archivos subidos (hoy solo la base de datos), y
notificaciones por correo en producción.

---

## 12. Comandos para volver a sacar los números

```bash
php artisan test                       # las pruebas
php artisan route:list                 # todas las rutas
php artisan migrate:status             # las migraciones
find app -name '*.php' | wc -l         # archivos PHP
composer audit                         # vulnerabilidades
```
