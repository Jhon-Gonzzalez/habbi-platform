# Verificación del prototipo frente al documento del proyecto

Contraste entre lo que el anteproyecto comprometió (Tablas 1 y 2, y los
casos de uso del apartado 7.2) y lo que el prototipo hace hoy.

Cada fila dice dónde está la evidencia: el archivo del código y, cuando
existe, el test que lo comprueba automáticamente. Lo que no está hecho se
dice que no está hecho.

- **Commit verificado:** `c57c63e`
- **Suite de pruebas:** 250 tests, 651 comprobaciones, todas en verde

---

## Resumen

| Estado | Requisitos funcionales | No funcionales | Casos de uso |
|---|---|---|---|
| Cumple | 6 de 11 | 5 de 8 | 5 de 8 |
| Parcial | 3 de 11 | — | 2 de 8 |
| No implementado | 2 de 11 | 1 de 8 | 1 de 8 |
| No verificable aún | — | 2 de 8 | — |

Lo que falta se concentra en **tres cosas**: la validación previa de las
publicaciones, el mapa embebido y los reportes del administrador.

---

## Tabla 1 — Requisitos funcionales

### RF-1 · Registro e inicio de sesión · CUMPLE, con una desviación deliberada

Registro, inicio de sesión y roles funcionan. `AuthTest`, `VerificacionArrendadorTest`.

**Desviación:** el documento dice que el usuario elige su rol al registrarse.
El prototipo **no** lo permite: todo el mundo entra como estudiante y para ser
arrendador hay que enviar cédula y prueba del inmueble, que revisa un
administrador (`ArrendadorController`, `VerificacionArrendadorService`).

El motivo: si el rol se elige en un desplegable, cualquiera se declara
arrendador y publica. Eso vacía de sentido el RF-10, que pide precisamente un
filtro de veracidad. La verificación se movió de la publicación a la persona.
**Conviene justificarlo en el documento, no dejarlo como un incumplimiento.**

### RF-2 · Publicar alojamientos · PARCIAL

Publicar con precio, ubicación, servicios y hasta 8 fotos: hecho
(`AlojamientoController@store`, `AlojamientoTest`, `SubidaFotosTest`).

**Falta:** el documento dice que la publicación nace en estado *«Pendiente de
validación»*. En el prototipo nace **visible** (`is_active` por defecto `true`).

### RF-3 · Editar y eliminar publicaciones · CUMPLE

Con confirmación explícita antes de borrar y limpieza de las fotos del disco.
`AlojamientoController@update/destroy`, `AlojamientoTest`.

### RF-4 · Buscar con filtros · PARCIAL

| Filtro pedido | Estado |
|---|---|
| Rango de precio | Sí (`price_min`, `price_max`) |
| Servicios requeridos | Sí (`amenities`) |
| **Distancia** | **No** |

Hay además texto libre, tipo y capacidad, que el documento no pedía.
`Alojamiento::scopeFiltrar`, `AlojamientoTest`.

### RF-5 · Recuperar contraseña por correo · CUMPLE

Seis rutas de recuperación activas. El administrador puede además generar un
enlace sin llegar a conocer la contraseña (`SeguridadAdminTest`).

### RF-6 · Mostrar residencias en un mapa · PARCIAL

Hay un botón que abre Google Maps en la ubicación y otro que abre la ruta
(`Alojamiento::urlMapa`, `urlRuta`, `RutaMapaTest`).

**Falta:** el mapa **embebido con pines** dentro de la plataforma. Hoy se sale
a Google Maps en otra pestaña.

### RF-7 · Rutas de transporte público · PARCIAL

Hay ruta **a pie** desde el alojamiento hasta cualquiera de las cinco
universidades de Montería (`travelmode=walking`).

**Falta:** las rutas de **transporte público** que pide el documento.

### RF-8 · Calificar y comentar · CUMPLE

1 a 5 estrellas con comentario, promedio recalculado, una reseña por persona y
alojamiento, y nadie puede reseñar lo suyo. `RatingController`, `RatingTest`.

### RF-9 · Favoritos · CUMPLE

Con índice único que impide duplicados. `FavoritoController`, `FavoritoTest`.

### RF-10 · Aprobar o rechazar publicaciones · NO IMPLEMENTADO

El administrador puede **pausar** o **eliminar** una publicación ya visible
(`Admin\AlojamientoController`), pero no existe el flujo
*pendiente → aprobada/rechazada* antes de publicarse.

Lo que sí existe es la aprobación de **arrendadores** (ver RF-1), que cubre la
misma preocupación por otro camino: en vez de revisar cada anuncio, se revisa
una vez a quien publica.

### RF-11 · Generar reportes · NO IMPLEMENTADO

El panel muestra métricas en pantalla (usuarios, alojamientos, reseñas,
promedio) y hay un registro de auditoría consultable y filtrable.

**Falta:** la exportación a **PDF** y las **gráficas** que pide el documento.

---

## Tabla 2 — Requisitos no funcionales

| ID | Requisito | Estado | Evidencia |
|---|---|---|---|
| RNF-1 | Búsquedas < 3 s | **Cumple** | Medido: 12–17 ms con filtros. El listado hace 2 consultas, no 25 (`scopeConResenas` con `withAvg`/`withCount`) |
| RNF-2 | Contraseñas encriptadas | **Cumple** | `bcrypt`, irreversible. Ni el administrador puede fijar la de otro (`SeguridadAdminTest`) |
| RNF-3 | Respaldos diarios | **No implementado** | `Console/Kernel.php` no tiene ninguna tarea programada |
| RNF-4 | Disponibilidad 99 % | **No verificable** | Depende del hosting; el prototipo no está desplegado |
| RNF-5 | Arquitectura modular | **Cumple** | Controladores, Form Requests, Policies, Services y Middleware separados |
| RNF-6 | Compatibilidad | **Cumple** | HTML y CSS estándar sin dependencias de navegador; 21 media queries |
| RNF-7 | 1000 usuarios concurrentes | **No verificable** | Requiere prueba de carga sobre el servidor real |
| RNF-8 | Interfaz responsiva | **Cumple** | 21 media queries, menú móvil |

---

## Casos de uso

| Caso | Estado | Nota |
|---|---|---|
| CU-01 Registro e inicio de sesión | Cumple | Con la desviación del rol (ver RF-1) |
| CU-02 Publicar residencia | Parcial | Falta el estado «pendiente de validación» y el aviso al administrador |
| CU-03 Editar / eliminar | Cumple | Con confirmación explícita |
| CU-04 Buscar y filtrar | Parcial | Falta el filtro por distancia y la vista en mapa |
| CU-05 Calificar y comentar | Cumple | Incluye la regla de no duplicar reseña |
| CU-06 Guardar en favoritos | Cumple | Incluye la regla de no duplicar |
| CU-07 Validar publicaciones | No implementado | Ver RF-10 |
| CU-08 Generar reportes | No implementado | Ver RF-11 |

---

## Lo que el prototipo tiene de más

No estaba en el documento y hoy funciona:

- **Verificación de arrendadores** con cédula y prueba del inmueble, en disco
  privado fuera de `public/`, y borrado automático al resolver la solicitud
  (`BorradoDeDocumentosTest`).
- **Protección de los datos de contacto:** el teléfono no se imprime en la
  página; hay que pedirlo y cada petición queda registrada, con aviso al
  administrador si alguien pide demasiados (`TelefonoProtegidoTest`).
- **Límites de velocidad** en registro, reseñas, reportes, publicaciones y
  teléfonos (`LimitesDeAbusoTest`).
- **Registro de auditoría** de las acciones del administrador, sin forma de
  borrarlo desde la aplicación (`AuditoriaTest`).
- **Reportes de publicaciones** por parte de los estudiantes (`ReporteTest`).
- **Perfiles públicos** con sello de verificación.
- **Textos legales** conforme a la Ley 1581 de 2012.
- **Fecha de disponibilidad** por alojamiento (`DisponibilidadTest`).

---

## Qué haría falta para cerrar las brechas

Ordenado por lo que más pesa en una sustentación:

1. **Validación previa de publicaciones** (RF-10, RF-2, CU-02, CU-07). Es la
   brecha más visible: el documento la describe en dos requisitos y un caso de
   uso. Un campo de estado, la pantalla de revisión y el filtro en el listado.
2. **Reportes del administrador** (RF-11, CU-08). Gráficas en pantalla y
   exportación a PDF.
3. **Mapa embebido con pines** (RF-6, CU-04) y **filtro por distancia** (RF-4).
   Requiere guardar coordenadas, para lo que ya existen las columnas `lat` y
   `lng`.
4. **Respaldos diarios** (RNF-3). Una tarea programada que vuelque la base de
   datos y rote las copias viejas.
5. **Transporte público** (RF-7). Es lo más dudoso: Montería no tiene un sistema
   de transporte con datos abiertos publicados, así que convendría revisar si
   ese requisito es realizable o si debe reformularse en el documento.
