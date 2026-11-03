# EduHost — Plataforma LMS y Mini-Hosting Académico (PHP Puro, MariaDB y Laravel 11)

**EduHost** es una plataforma web desarrollada en **Laravel 11** + **Tailwind CSS** + **Monaco Editor (VS Code Web)** diseñada para convertir una PC del instituto en un servidor de alojamiento web real y seguro para **30 alumnos**, publicado gratuitamente en Internet mediante **Tailscale Funnel (HTTPS)** sobre **Ubuntu Server 24.04 LTS**.

---

## Características Principales

### Para el Profesor
- **Gestión de Grupos y Alumnos:** Alta individual o importación masiva por lote (CSV) de los 30 alumnos. Al registrar un alumno, se crea automáticamente su directorio web aislado (`/~matricula/`) y su usuario MySQL/MariaDB propio (`alu_matricula`).
- **Asignación de Actividades:** Creación de prácticas clasificadas por tecnología (`PHP Puro`, `PHP + MariaDB` o `Laravel 11`) con instrucciones, puntaje y fecha límite.
- **Super-Visor de Revisión y Calificación:**
  - Enlace directo al sitio web en vivo del alumno ejecutándose en el servidor.
  - **Explorador de archivos y Editor de Código Web (Monaco Editor)** para revisar (o corregir en clase) el código fuente del alumno.
  - **Inspector en vivo de Bases de Datos MariaDB:** Visualiza las tablas, tipos de columnas, llaves primarias y primeros 50 registros de las bases de datos del alumno sin salir de la plataforma, además de integración con **Adminer** (`/db-manager/`).
  - Evaluación numérica (0-100) y retroalimentación directa para el alumno.

### Para los Alumnos (Mini-cPanel Educativo)
- **Despliegue de Sitios Web Reales:** Cada proyecto creado queda accesible públicamente en `https://tu-servidor.ts.net/~matricula/nombre-practica/`.
- **Subida de `.ZIP` y Archivos Sueltos:** Descompresión automática inteligente en su espacio web (filtrando carpetas `vendor/` pesadas para ahorrar espacio en el servidor).
- **Editor de Código en el Navegador (Monaco Editor):** Permite crear y editar archivos `.php`, `.blade.php`, `.sql`, `.css`, `.js` y `.env` directamente en el navegador con atajo `Ctrl+S`.
- **Gestor de Bases de Datos MariaDB:** Creación de bases de datos con su prefijo (`alu_matricula_nombre`), código de conexión PDO listo para copiar, importador de archivos `.sql` y acceso directo a **Adminer**.
- **Consola 1-Clic para Proyectos Laravel:** Botones seguros para autoconfigurar `.env`, ejecutar `composer install` (con caché compartida ultrarrápida), `php artisan key:generate`, `php artisan migrate`, `storage:link` y visor de `storage/logs/laravel.log`.

---

## Estructura del Proyecto

- [`docs/MANUAL_SERVIDOR_DESDE_CERO.md`](docs/MANUAL_SERVIDOR_DESDE_CERO.md): **Manual completo paso a paso** desde cómo crear la memoria USB booteable de Ubuntu Server 24.04 LTS hasta publicar el servidor en Internet con Tailscale Funnel.
- [`deploy/setup-server.sh`](deploy/setup-server.sh): **Script autoinstalador** que configura Nginx, MariaDB, PHP 8.3-FPM, Composer, Adminer, aislamiento de alumnos y Tailscale con un solo comando.
- [`deploy/nginx/eduhost.conf`](deploy/nginx/eduhost.conf): Configuración de Nginx con enrutamiento dinámico para `/~matricula/proyecto/` (compatible con PHP puro y proyectos Laravel con carpeta `public/`) y enjaulado `open_basedir`.
- [`deploy/php-fpm/students-pool.conf`](deploy/php-fpm/students-pool.conf): Pool de PHP-FPM aislado para el código de los alumnos con funciones peligrosas de sistema deshabilitadas.
- [`app/Services/`](app/Services/):
  - [`HostingProvisionerService.php`](app/Services/HostingProvisionerService.php)
  - [`FileManagerService.php`](app/Services/FileManagerService.php)
  - [`DatabaseManagerService.php`](app/Services/DatabaseManagerService.php)
  - [`LaravelRunnerService.php`](app/Services/LaravelRunnerService.php)

---

## Instalación Rápida en el Servidor Ubuntu 24.04 LTS

Una vez instalado Ubuntu Server en la PC del instituto (ver guía detallada en [`docs/MANUAL_SERVIDOR_DESDE_CERO.md`](docs/MANUAL_SERVIDOR_DESDE_CERO.md)), copia esta carpeta al servidor y ejecuta:

```bash
chmod +x deploy/setup-server.sh
sudo bash deploy/setup-server.sh
```

Para publicarlo gratis en Internet con HTTPS:
```bash
sudo tailscale up
sudo tailscale funnel --bg 80
```

### Credenciales por Defecto
- **Profesor:** `profesor@instituto.edu` / `password123`
- **Alumno Demo:** Matrícula `2026001` (o `alumno01@instituto.edu`) / `alumno123`

