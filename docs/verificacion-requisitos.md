# Verificación del prototipo frente al documento del proyecto

Contraste entre lo que el anteproyecto comprometió (Tablas 1 y 2, y los
casos de uso del apartado 7.2) y lo que el prototipo hace hoy.

Cada fila dice dónde está la evidencia: el archivo del código y, cuando
existe, el test que lo comprueba automáticamente. Lo que no está hecho se
dice que no está hecho.

- **Commit verificado:** `1427804`
- **Suite de pruebas:** 334 tests, 841 comprobaciones, todas en verde

---

## Resumen

| Estado | Requisitos funcionales | No funcionales | Casos de uso |
|---|---|---|---|
| Cumple | 10 de 11 | 6 de 8 | 8 de 8 |
| Parcial | 1 de 11 | — | — |
| No implementado | — | — | — |
| No verificable aún | — | 2 de 8 | — |

Queda **una** brecha real, el RF-7 (transporte público), y es la única que
probablemente no deba cerrarse escribiendo código: ver el apartado final.

Hay además **una desviación deliberada** en el RF-1, que conviene justificar
en el documento en vez de dejarla como un incumplimiento.

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
filtro de veracidad. La verificación se movió de la publicación a la persona,
y además el RF-10 se implementó igualmente, así que hoy hay dos filtros y no
uno. **Conviene justificarlo en el documento, no dejarlo como un
incumplimiento.**

### RF-2 · Publicar alojamientos · CUMPLE

Publicar con precio, ubicación, servicios y hasta 8 fotos
(`AlojamientoController@store`, `AlojamientoTest`, `SubidaFotosTest`).

La publicación **nace en estado «pendiente»** y no aparece en el buscador
hasta que un administrador la aprueba, como pedía el documento
(`Alojamiento::PENDIENTE`, `scopeDisponibles`, `RevisionPublicacionTest`).

### RF-3 · Editar y eliminar publicaciones · CUMPLE

Con confirmación explícita antes de borrar y limpieza de las fotos del disco.
`AlojamientoController@update/destroy`, `AlojamientoTest`.

### RF-4 · Buscar con filtros · CUMPLE

| Filtro pedido | Estado |
|---|---|
| Rango de precio | Sí (`price_min`, `price_max`) |
| Servicios requeridos | Sí (`amenities`) |
| Distancia | Sí (`universidad` + `km`) |

El filtro por distancia calcula haversine en SQL y ordena de más cerca a más
lejos (`Alojamiento::scopeCercaDe`, `DistanciaYMapaTest`). Hay además texto
libre, tipo, capacidad y fecha de disponibilidad, que el documento no pedía.

**Decisión que conviene poder defender:** solo se ofrecen las dos
universidades cuyas coordenadas se pudieron comprobar en fuentes públicas
(Unicórdoba y Unisinú). Poner las otras tres «a ojo» daría distancias
falsas, y una distancia falsa es peor que no ofrecer el filtro. Añadirlas es
un cambio de una línea cuando se tengan las coordenadas.

### RF-5 · Recuperar contraseña por correo · CUMPLE

Seis rutas de recuperación activas. El administrador puede además generar un
enlace sin llegar a conocer la contraseña (`SeguridadAdminTest`).

### RF-6 · Mostrar residencias en un mapa · CUMPLE

Mapa **embebido con pines** en la página de resultados, con el título, el
precio y el enlace de cada alojamiento, y las universidades marcadas en otro
color como referencia (`partials/mapa.blade.php`, `public/assets/js/mapa.js`,
`DistanciaYMapaTest`).

Está hecho con Leaflet y mapas de OpenStreetMap, que son libres y no piden
clave de API: por eso funciona sin contratar nada. Si el mapa no carga, el
listado sigue intacto.

Las coordenadas las pone el arrendador arrastrando un pin en el formulario de
publicación (`selector-mapa.js`, `SelectorCoordenadasTest`). No se usa ningún
servicio de geocodificación, que exige clave de API y en Montería acierta
poco. Se conservan además los enlaces que abren Google Maps para ver la
ubicación y la ruta.

### RF-7 · Rutas de transporte público · PARCIAL

Hay ruta **a pie** desde el alojamiento hasta cualquiera de las cinco
universidades de Montería (`travelmode=walking`, `RutaMapaTest`), y el filtro
por distancia da la cercanía en línea recta.

**Falta:** las rutas de **transporte público**. Es la única brecha que queda y
la recomendación es **reformular el requisito en el documento**, no
implementarlo: Montería no publica los recorridos de su transporte en ningún
formato de datos abiertos (no hay GTFS), así que cualquier ruta que mostrara
la plataforma estaría inventada. Ver el apartado final.

### RF-8 · Calificar y comentar · CUMPLE

1 a 5 estrellas con comentario, promedio recalculado, una reseña por persona y
alojamiento, y nadie puede reseñar lo suyo. `RatingController`, `RatingTest`.

### RF-9 · Favoritos · CUMPLE

Con índice único que impide duplicados. `FavoritoController`, `FavoritoTest`.

### RF-10 · Aprobar o rechazar publicaciones · CUMPLE

Flujo completo *pendiente → aprobada / rechazada*: cola de revisión en el
panel, motivo obligatorio al rechazar (mínimo 15 caracteres, porque el
arrendador lo va a leer y tiene que poder corregir), y la publicación
rechazada vuelve a la cola al editarse
(`Admin\RevisionController`, `RevisionAlojamientoService`,
`RevisionPublicacionTest`).

Una publicación **ya aprobada** que se edita **no** vuelve a la cola: se queda
visible y la edición se anota en la auditoría. Es una decisión consciente, con
el razonamiento escrito en `RevisionAlojamientoService`: si cada corrección de
una falta de ortografía tumbara el anuncio, nadie corregiría nada.

El administrador puede además pausar o eliminar una publicación ya visible
(`Admin\AlojamientoController`).

### RF-11 · Generar reportes · CUMPLE

Informes del administrador con rango de fechas, gráficas de barras y
exportación a **PDF** (`Admin\InformeController`, `ReporteEstadisticas`,
`InformeTest`).

Las gráficas se dibujan con HTML y CSS, no con una librería de JavaScript,
porque dompdf no ejecuta JavaScript: así la misma vista sirve para la pantalla
y para el PDF, y lo que se descarga es exactamente lo que se vio.

Se mantiene el panel de métricas en pantalla y el registro de auditoría
consultable y filtrable.

---

## Tabla 2 — Requisitos no funcionales

| ID | Requisito | Estado | Evidencia |
|---|---|---|---|
| RNF-1 | Búsquedas < 3 s | **Cumple** | Medido sobre los 20 alojamientos de ejemplo: 1 ms con filtros normales, 9 ms con el filtro por distancia. El listado hace 2 consultas, no 25 (`scopeConResenas` con `withAvg`/`withCount`); el filtro por distancia prefiltra por caja para usar los índices de `lat`/`lng` |
| RNF-2 | Contraseñas encriptadas | **Cumple** | `bcrypt`, irreversible. Ni el administrador puede fijar la de otro (`SeguridadAdminTest`) |
| RNF-3 | Respaldos diarios | **Cumple, con una salvedad** | Tarea programada en `Console/Kernel.php`, comando `habbi:respaldar`, rotación a 14 días y aviso en el panel si la última copia pasa de 48 h (`RespaldoTest`). **La salvedad:** la copia queda en el mismo disco que la base, lo que cubre un borrado por error pero no la pérdida del servidor; está explicado en `docs/respaldos.md` |
| RNF-4 | Disponibilidad 99 % | **No verificable** | Depende del hosting; el prototipo no está desplegado |
| RNF-5 | Arquitectura modular | **Cumple** | Controladores, Form Requests, Policies, Services y Middleware separados |
| RNF-6 | Compatibilidad | **Cumple** | HTML y CSS estándar sin dependencias de navegador |
| RNF-7 | 1000 usuarios concurrentes | **No verificable** | Requiere prueba de carga sobre el servidor real |
| RNF-8 | Interfaz responsiva | **Cumple** | Media queries y menú móvil; el mapa y el selector de coordenadas también se adaptan |

---

## Casos de uso

| Caso | Estado | Nota |
|---|---|---|
| CU-01 Registro e inicio de sesión | Cumple | Con la desviación del rol (ver RF-1) |
| CU-02 Publicar residencia | Cumple | Nace pendiente y aparece en la cola del administrador |
| CU-03 Editar / eliminar | Cumple | Con confirmación explícita |
| CU-04 Buscar y filtrar | Cumple | Resultados en lista **y** en mapa, con filtro por distancia |
| CU-05 Calificar y comentar | Cumple | Incluye la regla de no duplicar reseña |
| CU-06 Guardar en favoritos | Cumple | Incluye la regla de no duplicar |
| CU-07 Validar publicaciones | Cumple | Aprobar / rechazar con motivo (ver RF-10) |
| CU-08 Generar reportes | Cumple | Gráficas y PDF (ver RF-11) |

---

## Lo que el prototipo tiene de más

No estaba en el documento y hoy funciona:

- **Chat interno** entre estudiante y arrendador, por alojamiento, con
  **cierre de acuerdo a dos manos**: lo propone uno y lo confirma el otro, y al
  confirmarse baja solo el número de plazas libres; si llega a cero, el anuncio
  deja de salir en el buscador (`ChatService`, `ChatTest`). El contacto ya no
  se va a WhatsApp: el teléfono queda como dato secundario.
- **Verificación de arrendadores** con cédula y prueba del inmueble, en disco
  privado fuera de `public/`, y borrado automático al resolver la solicitud
  (`BorradoDeDocumentosTest`).
- **Protección de los datos de contacto:** el teléfono no se imprime en la
  página; hay que pedirlo y cada petición queda registrada, con aviso al
  administrador si alguien pide demasiados (`TelefonoProtegidoTest`).
- **Límites de velocidad** en registro, reseñas, reportes, publicaciones,
  mensajes y teléfonos (`LimitesDeAbusoTest`).
- **Registro de auditoría** de las acciones del administrador, sin forma de
  borrarlo desde la aplicación (`AuditoriaTest`).
- **Reportes de publicaciones** por parte de los estudiantes (`ReporteTest`).
- **Perfiles públicos** con sello de verificación.
- **Verificación de correo** con interruptor, para poder trabajar sin servidor
  de correo montado (`VerificacionCorreoTest`).
- **Textos legales** conforme a la Ley 1581 de 2012.
- **Fecha de disponibilidad** por alojamiento (`DisponibilidadTest`).
- **Datos de ejemplo de Montería**: 20 alojamientos con barrios y coordenadas
  reales de la ciudad (`MonteriaSeederTest`).

---

## Qué queda, y qué conviene hacer con él

### 1. RF-7, transporte público — reformular en el documento

Montería no publica los recorridos de su transporte público en ningún formato
de datos abiertos. Sin esa fuente, una «ruta en bus» que mostrara la
plataforma sería inventada, y un dato inventado sobre cómo llegar a clase a
las siete de la mañana es peor que no dar ninguno.

Propuesta de redacción para el documento: sustituir «rutas de transporte
público» por **«distancia y ruta a pie hasta la universidad, y filtro por
cercanía al campus»**, que es lo que el prototipo hace y lo que el estudiante
realmente usa para decidir. Si más adelante aparece una fuente de datos, el
requisito original se puede retomar.

### 2. RF-1, el rol — justificar la desviación

No es una brecha, es una decisión de diseño que mejora el resultado. Merece un
párrafo en el documento explicando por qué dejar que cualquiera se declare
arrendador habría vaciado de sentido el RF-10.

### 3. RNF-3, respaldos — una tarea de despliegue, no de código

El código está. Para que corra de verdad, el servidor necesita una línea en el
cron (`php artisan schedule:run`) y, para estar cubierto de verdad, una copia
fuera del servidor. Ambas cosas están explicadas paso a paso en
`docs/respaldos.md`. El panel avisa en rojo si no hay copias recientes, así
que no es algo que pueda quedar fallando en silencio.

### 4. RNF-4 y RNF-7 — solo se comprueban desplegando

La disponibilidad del 99 % y los 1000 usuarios concurrentes no se pueden medir
en un prototipo local. Son los dos únicos puntos del documento que quedan sin
verificar, y conviene decirlo así en la sustentación en vez de darlos por
cumplidos.
