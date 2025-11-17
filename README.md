# HABBI – Plataforma de Alojamiento Estudiantil

<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>


HABBI es una plataforma desarrollada para facilitar la búsqueda, gestión y publicación de alojamientos estudiantiles. Permite que estudiantes encuentren opciones de vivienda cercanas a sus universidades, mientras que arrendadores pueden administrar sus propiedades de manera sencilla.

## 🚀 Tecnologías Utilizadas
- **PHP 8+**
- **Laravel** (Framework principal)
- **MySQL / MariaDB**
- **Blade Templates**
- **Composer**
- **JavaScript / Node.js**
- **CSS / Tailwind o Bootstrap** (dependiendo del proyecto)

## 📂 Estructura del Proyecto
- **app/** – Lógica principal del backend, controladores, modelos.
- **bootstrap/** – Inicialización del framework.
- **config/** – Configuraciones generales.
- **database/** – Migraciones, seeders y factories.
- **public/** – Archivos públicos (CSS, JS, imágenes).
- **resources/** – Vistas Blade, assets.
- **routes/** – Rutas web y API.
- **storage/** – Archivos generados, logs, caché.
- **vendor/** – Dependencias instaladas vía Composer.

## 🛠 Instalación
Sigue los pasos para instalar el proyecto localmente.

### 1. Clonar el repositorio
```bash
git clone https://github.com/Jhon-Gonzzalez/habbi-platform.git
cd habbi-platform
```

### 2. Instalar dependencias
```bash
composer install
npm install
```

### 3. Crear archivo .env
```bash
cp .env.example .env
```
Configura tu base de datos en el `.env`:
```
DB_DATABASE=habbi
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Generar la llave de la app
```bash
php artisan key:generate
```

### 5. Migrar base de datos
```bash
php artisan migrate --seed
```

### 6. Iniciar servidor
```bash
php artisan serve
```
Accede en tu navegador a:
```
http://127.0.0.1:8000
```

## 🌟 Funcionalidades Principales
- Registro y autenticación de estudiantes y arrendadores.
- Publicación de alojamientos.
- Buscador de habitaciones y apartamentos.
- Gestión de propiedades.
- Sistema de favoritos.
- Panel administrativo.

## 🧪 Tests
```bash
php artisan test
```

## 📌 Estado del Proyecto
En desarrollo activo.

## 🤝 Contribuciones
Las contribuciones son bienvenidas. Para contribuir:
1. Crea un fork.
2. Crea una rama nueva.
3. Envía tu pull request.

## 📄 Licencia
Proyecto académico – Uso libre para fines educativos.

---
Creado por **Jhon González** ✨
