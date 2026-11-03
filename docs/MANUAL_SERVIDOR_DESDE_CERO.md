# Manual Completo: Instalación y Despliegue de "EduHost" desde Cero

Este manual está diseñado paso a paso para tomar una PC estándar del instituto, formatearla con **Ubuntu Server 24.04 LTS**, convertirla en un servidor de alojamiento web real para **30 alumnos** (PHP Puro, PHP + MariaDB y Laravel) y publicarla **100% gratis en Internet con HTTPS** usando **Tailscale Funnel**, sin necesidad de tener IP pública ni abrir puertos en el router del instituto.

---

## Índice

1. [Requisitos de la PC Servidor](#1-requisitos-de-la-pc-servidor)
2. [Fase 1: Crear la USB Booteable e Instalar Ubuntu Server 24.04 LTS](#2-fase-1-crear-la-usb-booteable-e-instalar-ubuntu-server-2404-lts)
3. [Fase 2: Conectarse al Servidor y Ejecutar el Autoinstalador](#3-fase-2-conectarse-al-servidor-y-ejecutar-el-autoinstalador)
4. [Fase 3: Publicar el Servidor Gratis en Internet con Tailscale Funnel](#4-fase-3-publicar-el-servidor-gratis-en-internet-con-tailscale-funnel)
5. [Fase 4: Guía de Uso para el Profesor y los Alumnos](#5-fase-4-guía-de-uso-para-el-profesor-y-los-alumnos)
6. [Fase 5: Mantenimiento, Copias de Seguridad y Solución de Problemas](#6-fase-5-mantenimiento-copias-de-seguridad-y-solución-de-problemas)

---

## 1. Requisitos de la PC Servidor

Cualquier PC de escritorio del laboratorio del instituto de los últimos 8 años funcionará de maravilla para 30 alumnos:

- **Procesador:** Intel Core i3 / i5 / i7 (4ta generación o superior) o AMD Ryzen.
- **Memoria RAM:** Mínimo **4 GB** (Recomendado **8 GB o 16 GB**). Ubuntu Server sin entorno gráfico consume apenas ~350 MB de RAM en reposo.
- **Disco Duro / SSD:** Mínimo **120 GB** (Recomendado **SSD de 240 GB+** para que Composer y las bases de datos de los 30 alumnos vuelen).
- **Red:** Conectada por **cable Ethernet (RJ45)** al switch o módem del instituto para máxima estabilidad.
- **Extra:** Una memoria USB de al menos 4 GB para instalar el sistema operativo.

---

## 2. Fase 1: Crear la USB Booteable e Instalar Ubuntu Server 24.04 LTS

### Paso 1.1: Descargar Ubuntu Server y Rufus (en tu PC personal)
1. Descarga la imagen ISO oficial y gratuita de **Ubuntu Server 24.04 LTS (64-bit)**:
   - Sitio oficial: [https://ubuntu.com/download/server](https://ubuntu.com/download/server)
2. Descarga **Rufus** (herramienta gratuita para grabar la ISO en la USB):
   - Sitio oficial: [https://rufus.ie/es/](https://rufus.ie/es/)
3. Inserta tu memoria USB, abre **Rufus**, selecciona el archivo `.iso` de Ubuntu Server y haz clic en **EMPEZAR**. (Acepta el modo *Escribir en modo imagen ISO*).

### Paso 1.2: Arrancar la PC del Instituto desde la USB
1. Conecta la memoria USB a la PC que será servidor (asegúrate de que ya tenga el cable de red Ethernet conectado).
2. Enciende la PC y presiona repetidamente la tecla de menú de arranque (**F12**, **F11**, **F9**, **F2** o **Supr** según la marca: Dell/Lenovo suele ser F12, HP suele ser F9).
3. Selecciona tu memoria USB (UEFI) y elige **`Try or Install Ubuntu Server`**.

### Paso 1.3: Asistente de Instalación de Ubuntu Server
En las pantallas del instalador (te mueves con las **flechas del teclado**, **Tab** y confirmas con **Enter** o **Espacio**):

1. **Idioma:** Selecciona *Español* (o *English*).
2. **Teclado:** Selecciona *Spanish (Latin American)* o el de tu teclado.
3. **Tipo de instalación:** Deja seleccionado `[X] Ubuntu Server` (normal, no minimizado).
4. **Conexión de Red (Importante):**
   - Verás que la tarjeta Ethernet (`eno1` o `enp3s0`) obtiene una IP automáticamente por DHCP (ejemplo: `192.168.1.85/24`).
   - **Anesa esa dirección IP en un papel**, la usarás para entrar desde tu laptop.
   - *(Opcional pero recomendado)*: Si quieres que esa IP local nunca cambie, entra a `Edit IPv4` -> cámbialo de `Automatic (DHCP)` a `Manual` y pon los mismos datos que te dio el DHCP.
5. **Proxy y Mirror:** Déjalos como vienen por defecto y dale a *Done*.
6. **Almacenamiento (Storage):**
   - Selecciona `[X] Use an entire disk`.
   - **Desmarca** (quita la X con barra espaciadora) la opción *Set up this disk as an LVM group* para que aproveche el 100% del disco directamente sin dejar espacio reservado sin usar.
   - Confirma en *Done* -> *Continue*.
7. **Configuración de Perfil (Profile configuration):**
   - **Your name:** `Profesor Admin`
   - **Your server's name:** `eduhost-server`
   - **Pick a username:** `profesor`
   - **Choose a password:** *(Elige una contraseña segura y recuérdala)*.
8. **Ubuntu Pro:** Déjalo en `Skip for now` -> *Continue*.
9. **SSH Setup (¡MUY IMPORTANTE!):**
   - Marca con la barra espaciadora: `[X] Install OpenSSH server`.
   - Esto te permitirá administrar el servidor cómodamente desde tu laptop o PC de escritorio sin tener que conectar monitor ni teclado a la PC servidor nunca más.
10. **Featured Server Snaps:** No marques ninguno (todo lo instalaremos limpio con nuestro script). Dale a *Done*.
11. Cuando termine y diga **Reboot Now**, presiona Enter, retira la memoria USB cuando te lo pida y presiona Enter de nuevo.

---

## 3. Fase 2: Conectarse al Servidor y Ejecutar el Autoinstalador

Una vez que la PC servidor haya reiniciado, puedes hacerlo directamente en su teclado o (mucho más cómodo) abrir **PowerShell** o **CMD** en tu propia laptop conectada a la misma red del instituto:

### Paso 2.1: Entrar por SSH desde tu PC
```bash
ssh profesor@IP_DEL_SERVIDOR
# Ejemplo: ssh profesor@192.168.1.85
```
*(Escribe `yes` si pregunta por el fingerprint y luego tu contraseña).*

### Paso 2.2: Subir o Clonar el Proyecto "EduHost"
Tienes dos formas muy fáciles de pasar este proyecto (`e:\ws\servidor`) a la PC con Ubuntu:

#### Opción A: Copiarlo directamente desde tu Windows por red (con `scp`)
Abre una ventana de PowerShell en tu Windows y ejecuta:
```powershell
scp -r E:\ws\servidor profesor@IP_DEL_SERVIDOR:/home/profesor/eduhost-repo
```

#### Opción B: Desde GitHub (si subes esta carpeta a tu cuenta de GitHub)
En la terminal del servidor Ubuntu:
```bash
git clone https://github.com/tu-usuario/eduhost.git ~/eduhost-repo
```

### Paso 2.3: Ejecutar el Script Autoinstalador (`setup-server.sh`)
Dentro de la terminal del servidor Ubuntu, ejecuta estos 3 comandos:

```bash
cd ~/eduhost-repo
chmod +x deploy/setup-server.sh
sudo bash deploy/setup-server.sh
```

**¿Qué hace este script automáticamente por ti en ~3 minutos?**
1. Instala **Nginx**, **MariaDB 11**, **PHP 8.3** (con todas las extensiones para Laravel) y **Composer**.
2. Crea la base de datos maestra `eduhost` y el usuario administrador con permisos para crear las bases de datos de los alumnos.
3. Descarga **Adminer** en `/var/www/adminer` accesible desde `/db-manager/`.
4. Configura el **Pool Aislado de PHP-FPM** (`students-pool.conf`) con `open_basedir` por alumno (para que ningún alumno pueda ver ni borrar carpetas de otro) y bloquea funciones peligrosas (`exec`, `shell_exec`, `system`).
5. Configura **Nginx** (`eduhost.conf`) para servir la plataforma principal en `/` y los sitios de todos tus alumnos en `/~matricula/nombre-practica/` (detectando automáticamente si es PHP puro o Laravel).
6. Instala **EduHost**, corre las migraciones y crea tu usuario de Profesor.
7. Instala **Tailscale**.

---

## 4. Fase 3: Publicar el Servidor Gratis en Internet con Tailscale Funnel

Para que tus 30 alumnos (y tú desde tu casa) puedan entrar al servidor desde cualquier lugar con un dominio real HTTPS gratuito (sin pagar nada, sin tarjeta de crédito y sin configurar el módem del instituto):

### Paso 3.1: Crear cuenta gratuita en Tailscale
1. Entra desde tu navegador a [https://login.tailscale.com/start](https://login.tailscale.com/start) e inicia sesión gratis con tu cuenta de Google, Microsoft o GitHub.

### Paso 3.2: Vincular el servidor Ubuntu
En la terminal del servidor Ubuntu, ejecuta:
```bash
sudo tailscale up
```
- Aparecerá un enlace URL en la pantalla. Cópialo, ábrelo en tu navegador y haz clic en **Connect** para autorizar el servidor.

### Paso 3.3: Habilitar HTTPS y Funnel en el panel de Tailscale (solo se hace 1 vez)
1. En el panel web de Tailscale ([https://login.tailscale.com/admin/dns](https://login.tailscale.com/admin/dns)):
   - Asegúrate de que **MagicDNS** esté activado.
   - Baja hasta **HTTPS Certificates** y haz clic en **Enable HTTPS**.
2. Ve a la pestaña **Access Controls (ACLs)** ([https://login.tailscale.com/admin/acls](https://login.tailscale.com/admin/acls)):
   - Si cuando ejecutes el comando del paso 3.4 te pide habilitar Funnel, el mismo comando de consola te dará un link directo de 1 clic para autorizar el atributo `funnel` en tu cuenta gratuita.

### Paso 3.4: Encender la publicación en Internet 24/7
Ejecuta en la terminal del servidor Ubuntu:
```bash
sudo tailscale funnel --bg 80
```
*(El parámetro `--bg` hace que quede activo en segundo plano de forma permanente, incluso si la PC del instituto se apaga y vuelve a encender).*

Tailscale te mostrará en pantalla tu dirección pública gratuita, por ejemplo:
```text
Available on the internet:
https://eduhost-server.tail12345.ts.net/
```

### Paso 3.5: Actualizar `APP_URL` en EduHost
Para que todos los enlaces generados por la plataforma usen tu nuevo dominio público HTTPS:
```bash
sudo sed -i "s|APP_URL=.*|APP_URL=https://eduhost-server.tail12345.ts.net|" /var/www/eduhost/.env
cd /var/www/eduhost && sudo php artisan config:clear
```

¡Listo! Ahora cualquier persona desde cualquier parte del mundo (en PC o celular, con datos móviles o WiFi de casa) puede entrar a tu servidor sin instalar nada.

---

## 5. Fase 4: Guía de Uso para el Profesor y los Alumnos

### 5.1 Acceso del Profesor
1. Abre tu navegador en `https://eduhost-server.tail12345.ts.net/login`
2. Ingresa con las credenciales iniciales:
   - **Correo:** `profesor@instituto.edu`
   - **Contraseña:** `password123`
3. **Primeros pasos recomendados:**
   - Ve a **Grupos** y crea tus grupos del semestre (ej. `5A - Desarrollo Web`, `6B - Frameworks Laravel`).
   - Ve a **Alumnos** -> puedes dar de alta a tus 30 alumnos uno por uno o pegando una lista CSV (`matricula,nombre,email,password`).
   - Al registrar a un alumno, **EduHost crea automáticamente**:
     - Su carpeta privada en `/var/www/students/{matricula}/`
     - Su usuario aislado de MariaDB `alu_{matricula}` con su contraseña propia.
   - Ve a **Actividades** y asigna una práctica indicando si es:
     - **PHP Puro**
     - **PHP + MariaDB**
     - **Laravel**

### 5.2 ¿Cómo suben sus prácticas los Alumnos?
Cuando un alumno inicia sesión con su matrícula/correo:

1. **Para una práctica de PHP Puro:**
   - Entra a la actividad o a **Mis Proyectos**, crea el proyecto (ej. `calculadora-imc`) y sube su archivo `.zip` (o crea su `index.php` directamente desde el **Editor de Código Web** integrado).
   - Su sitio queda inmediatamente publicado en:
     `https://eduhost-server.tail12345.ts.net/~2026001/calculadora-imc/`

2. **Para una práctica de PHP + MariaDB:**
   - Va a la sección **Mis Bases de Datos** y hace clic en **+ Nueva Base de Datos** (ej. `tienda`).
   - El sistema crea la base de datos `alu_2026001_tienda` y le muestra en pantalla el código exacto de conexión PDO / `mysqli` listo para copiar y pegar.
   - Puede importar su archivo `.sql` con un botón o hacer clic en **Abrir en Adminer** para crear tablas y registros visualmente.

3. **Para una práctica de Laravel:**
   - El alumno comprime su proyecto Laravel en `.zip` *(no necesita incluir la carpeta `vendor/`, ahorrando tiempo y megas; si la incluye por error, el servidor la optimiza)* y lo sube.
   - En el panel de su proyecto Laravel tiene la **Consola 1-Clic para Laravel**:
     - **Autoconfigurar `.env`**: Configura automáticamente su `APP_URL`, `ASSET_URL` y conecta su base de datos MariaDB seleccionada.
     - **Composer Install**: Instala las dependencias usando la caché ultrarrápida del servidor.
     - **Artisan Key:Generate**
     - **Artisan Migrate** y **Migrate:Fresh --seed**
     - **Artisan Storage:Link** y **Limpiar Caché**
     - **Visor de Logs (`laravel.log` y PHP Error Log)**: Para revisar en tiempo real por qué falló una ruta o consulta.

### 5.3 ¿Cómo revisa y califica el Profesor?
Desde **Actividades -> Ver Entregas -> Revisar Proyecto**:
- Tienes el **Super-Visor del Profesor**:
  1. **Botón "Abrir Sitio en Vivo"**: Abre la práctica del alumno ejecutándose realmente en el servidor.
  2. **Explorador y Editor de Código (Monaco Editor)**: Navega por todas las carpetas del alumno (`app/Http/Controllers`, `resources/views`, `index.php`, etc.), revisa su código con colores de sintaxis tipo VS Code e incluso edita y guarda un archivo en vivo si quieres mostrarle en el proyector del salón cómo corregir un error.
  3. **Inspector de Base de Datos**: Selecciona cualquiera de las bases de datos MariaDB del alumno para ver sus tablas, columnas y primeros registros sin salir de la pantalla, o ábrela en Adminer con 1 clic.
  4. **Calificación y Retroalimentación**: Asigna la nota (0 a 100) y guarda tus comentarios para el alumno.

---

## 6. Fase 5: Mantenimiento, Copias de Seguridad y Solución de Problemas

### Evitar que la PC se suspenda
Ubuntu Server por defecto no entra en suspensión, pero si estás usando una laptop como servidor y vas a cerrar la tapa, ejecuta esto una sola vez para que no se duerma al cerrar la pantalla:
```bash
sudo sed -i 's/#HandleLidSwitch=suspend/HandleLidSwitch=ignore/' /etc/systemd/logind.conf
sudo systemctl restart systemd-logind
```

### Hacer un respaldo completo (Bases de datos + Prácticas de los 30 alumnos)
Puedes ejecutar este comando al final de cada parcial para generar un respaldo completo en `/home/profesor/`:
```bash
mysqldump -u root --all-databases > ~/respaldo_bds_$(date +%F).sql
tar -czf ~/respaldo_proyectos_$(date +%F).tar.gz /var/www/students
```

### Ver el estado de los servicios del servidor
```bash
sudo systemctl status nginx
sudo systemctl status php8.3-fpm
sudo systemctl status mariadb
sudo tailscale funnel status
```

