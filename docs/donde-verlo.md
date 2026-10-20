# Dónde ver cada requisito funcionando

Guía para abrir el proyecto y comprobar, pantalla por pantalla, que cada
requisito del documento está hecho. Pensada para la sustentación: cada
fila dice qué abrir, qué hacer y qué tiene que pasar.

## Antes de empezar

```bash
cd ~/habbi
git pull
bash deploy/probar-local.sh
```

Y abre <http://127.0.0.1:8000>.

Las cuentas de ejemplo (todas con la contraseña `habbi1234`):

| Rol | Correo | Para qué |
|---|---|---|
| Administrador | `admin@habbi.test` | El panel entero |
| Arrendador | `dolores.arrieta@ejemplo.test` | Publicar, editar, campañas |
| Estudiante | `laura.hoyos@ejemplo.test` | Buscar, favoritos, chat, reportar |

¿Quieres comprobarlo todo de una vez en vez de a mano? Con el servidor
levantado, en otra terminal:

```bash
bash deploy/recorrido.sh
```

Entra con cada rol, visita las 41 pantallas y dice cuáles responden y
cuáles no. También comprueba que las que tienen que cerrar la puerta la
cierran.

---

## Requisitos funcionales

### RF-1 · Registro e inicio de sesión

**Dónde:** <http://127.0.0.1:8000/register>

Crea una cuenta. Entras como **estudiante**: no hay desplegable de rol, y
eso es deliberado (ver el apartado final). Para ser arrendador:
**Quiero arrendar** en el menú → envías cédula y prueba del inmueble →
lo aprueba un administrador en `/admin/solicitudes`.

### RF-2 · Publicar alojamientos

**Dónde:** entra como arrendador → **Publicar** →
<http://127.0.0.1:8000/alojamientos/publicar>

Precio, ubicación, servicios, hasta 8 fotos, y el **mapa donde arrastras
el pin** para marcar dónde queda. Al guardar verás el aviso de que queda
**en revisión**: no sale en el buscador hasta que un administrador la
apruebe.

### RF-3 · Editar y eliminar

**Dónde:** **Mis publicaciones** →
<http://127.0.0.1:8000/alojamientos/mis-publicaciones>

Botones **Editar**, **Pausar** y **Eliminar** en cada tarjeta. Al
eliminar pide confirmación y borra también las fotos del disco.

### RF-4 · Buscar con filtros

**Dónde:** <http://127.0.0.1:8000/alojamientos>

En la barra izquierda: precio mínimo y máximo, tipo, huéspedes,
comodidades, y **«Cerca de» + «A no más de»**, que es el filtro por
distancia. Pruébalo con *Universidad de Córdoba* y *2 km*: de 18
resultados bajan a los que de verdad están cerca, ordenados del más
próximo al más lejano.

Enlace directo:
<http://127.0.0.1:8000/alojamientos?universidad=Universidad+de+C%C3%B3rdoba&km=2>

### RF-5 · Recuperar contraseña

**Dónde:** <http://127.0.0.1:8000/password/reset>

Con el correo apagado en local, el enlace se escribe en
`storage/logs/laravel.log`. El administrador puede además generar uno
desde `/admin/usuarios` sin llegar a conocer la contraseña.

### RF-6 · Mapa con pines

**Dónde:** <http://127.0.0.1:8000/alojamientos> y cualquier ficha.

En el buscador, encima de los resultados: pines azules (alojamientos) y
morados (universidades). Pulsa un pin y sale el título, el precio y el
enlace. Los botones **Ampliar** y **Ocultar** cambian su tamaño y lo
recuerdan.

En la ficha del alojamiento hay otro mapa, centrado en ese inmueble.

> Necesita internet para los mosaicos de OpenStreetMap. Sin conexión el
> recuadro sale gris y el listado sigue funcionando igual.

### RF-7 · Rutas de transporte · PARCIAL

**Dónde:** ficha de cualquier alojamiento → **Ver ruta a Unicórdoba**.

Abre Google Maps con la ruta **a pie**. Arriba dice a cuántos kilómetros
en línea recta queda. Las rutas de **bus** no están: ver el apartado
final.

### RF-8 · Calificar y comentar

**Dónde:** ficha de un alojamiento, al final.

Inicia sesión como estudiante y califica de 1 a 5 con comentario. Prueba
a calificar dos veces el mismo: no deja. Y entra como el arrendador
dueño: tampoco puede calificarse a sí mismo.

### RF-9 · Favoritos

**Dónde:** el corazón de cada tarjeta, y
<http://127.0.0.1:8000/mis-favoritos>

### RF-10 · Aprobar o rechazar publicaciones

**Dónde:** entra como administrador →
<http://127.0.0.1:8000/admin/revision>

Hay dos esperando. Ábrelas: **Aprobar** la deja visible al instante;
**Rechazar** exige un motivo de al menos 15 caracteres, que el
arrendador va a leer. Una rechazada vuelve a la cola cuando su dueño la
corrige.

### RF-11 · Generar reportes

**Dónde:** <http://127.0.0.1:8000/admin/informes>

Elige un rango de fechas. Salen las cifras y las gráficas de barras.
**Descargar PDF** baja el mismo informe: lo que ves es lo que se
descarga, porque la gráfica es HTML y CSS, no una librería de
JavaScript.

---

## Requisitos no funcionales

| ID | Dónde comprobarlo |
|---|---|
| **RNF-1** · Búsquedas < 3 s | Cualquier búsqueda. Medido: 1 ms con filtros normales, 9 ms con el de distancia, sobre los 20 alojamientos de ejemplo |
| **RNF-2** · Contraseñas cifradas | `/admin/usuarios` → edita a alguien: **no hay campo de contraseña**. Solo se genera un enlace de restablecimiento |
| **RNF-3** · Respaldos diarios | `php artisan habbi:respaldar --listar`. La tarea nocturna está en `app/Console/Kernel.php`; se ve con `php artisan schedule:list`. El panel avisa en rojo si la última copia pasa de 48 h |
| **RNF-4** · Disponibilidad 99 % | **No verificable en local.** Depende del hosting |
| **RNF-5** · Arquitectura modular | `app/`: Controllers, Requests, Policies, Services, Middleware separados |
| **RNF-6** · Compatibilidad | HTML y CSS estándar, sin paso de compilación |
| **RNF-7** · 1000 usuarios a la vez | **No verificable en local.** Requiere prueba de carga sobre el servidor real |
| **RNF-8** · Interfaz responsiva | Achica la ventana del navegador, o F12 → vista de móvil. El menú, el mapa y el selector de coordenadas se adaptan |

---

## Casos de uso

| Caso | Dónde |
|---|---|
| CU-01 Registro e inicio de sesión | `/register`, `/login` |
| CU-02 Publicar residencia | `/alojamientos/publicar` → queda en revisión |
| CU-03 Editar / eliminar | `/alojamientos/mis-publicaciones` |
| CU-04 Buscar y filtrar | `/alojamientos`: lista **y** mapa, con filtro por distancia |
| CU-05 Calificar y comentar | Final de cualquier ficha |
| CU-06 Guardar en favoritos | El corazón, y `/mis-favoritos` |
| CU-07 Validar publicaciones | `/admin/revision` |
| CU-08 Generar reportes | `/admin/informes` |

---

## Lo que no estaba en el documento y también funciona

| Qué | Dónde |
|---|---|
| **Chat interno con cierre de acuerdo** | Ficha → **Escríbele por el chat**. Uno propone el acuerdo, el otro lo confirma, y al confirmarse **baja solo el número de plazas**; si llega a cero el anuncio sale del buscador |
| **Verificación de arrendadores** | `/quiero-arrendar` y `/admin/solicitudes`. Los documentos se borran solos al resolver |
| **Protección del teléfono** | El número no se imprime en la página: hay que pedirlo, y cada petición queda registrada. `/admin` avisa si alguien pide demasiados |
| **Campañas de pago** | `/alojamientos/1/campana` (arrendador) y `/admin/campanas` |
| **Tarifas editables** | `/admin/campanas/tarifas`: precios, días y el Nequi, sin tocar código |
| **Publicidad local** | Barra lateral del buscador, y `/admin/aliados` |
| **Auditoría** | `/admin/auditoria`. No hay forma de borrarla desde la aplicación |
| **Reportes de abuso** | Ficha → **Reportar**, y `/admin/reportes` |

---

## Las dos cosas que hay que explicar, no arreglar

**1. RF-7, transporte público.** Montería no publica los recorridos de su
transporte en ningún formato de datos abiertos. Una ruta en bus que
mostrara la plataforma estaría inventada, y un dato inventado sobre cómo
llegar a clase es peor que ninguno. La propuesta es reformular el
requisito como «distancia y ruta a pie hasta la universidad, y filtro por
cercanía al campus», que es lo que hace y lo que el estudiante usa.

**2. RF-1, el rol al registrarse.** El documento decía que el usuario
elige su rol. Aquí todos entran como estudiante y ser arrendador exige
pasar una verificación. Si el rol se eligiera en un desplegable,
cualquiera se declararía arrendador y publicaría, lo que vaciaría de
sentido el RF-10. Es una mejora, no un incumplimiento, y merece un
párrafo en el documento.

**No verificables en un prototipo local:** RNF-4 (disponibilidad del
99 %) y RNF-7 (1000 usuarios simultáneos). Conviene decirlo así en la
sustentación en vez de darlos por cumplidos.
