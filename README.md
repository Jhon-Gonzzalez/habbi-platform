<p align="center">
  <img src="public/assets/img/images/logo.png" alt="HABBI" width="220">
</p>

<h1 align="center">HABBI · Plataforma de vivienda estudiantil</h1>

<p align="center">
  Buscar, publicar y calificar alojamiento cerca de la universidad.<br>
  <strong>Laravel 10 · PHP 8.1+ · MySQL · Blade</strong>
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
| **Cuentas** | Registro, login, recuperación de contraseña y panel personal con el resumen de publicaciones y reseñas. |
| **Panel de administración** | Métricas generales, CRUD de usuarios con roles y moderación de publicaciones. |

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

## Instalación local

```bash
git clone https://github.com/jhon-gonzzalez/habbi-platform.git
cd habbi-platform

composer install
cp .env.example .env
php artisan key:generate

# Configura DB_DATABASE, DB_USERNAME y DB_PASSWORD en .env, luego:
php artisan migrate --seed
php artisan storage:link

php artisan serve
```

Abre <http://localhost:8000>.

El seeder crea un administrador con las credenciales de `ADMIN_EMAIL` y
`ADMIN_PASSWORD` del `.env`, más datos de ejemplo (usuarios, alojamientos y
reseñas) que **no** se generan cuando `APP_ENV=production`.

## Tests

```bash
php artisan test
```

39 pruebas funcionales que cubren publicación, permisos de edición, gestión de
fotos, reglas de las reseñas, acceso al panel de administración y el renderizado
de todas las vistas.

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
