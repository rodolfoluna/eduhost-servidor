#!/usr/bin/env bash
# =============================================================================
# SCRIPT DE APROVISIONAMIENTO AUTOMÁTICO: SERVIDOR EDUHOST (UBUNTU 24.04 LTS)
# =============================================================================
# Este script instala y configura desde cero:
# 1. Nginx + PHP 8.3 (FPM + extensiones para Laravel) + MariaDB 11 + Composer
# 2. Pool aislado de PHP-FPM para los 30 alumnos (con open_basedir y seguridad)
# 3. Adminer (Gestor visual de Bases de Datos en /db-manager)
# 4. Usuario y permisos de MariaDB para que EduHost cree BDs de alumnos
# 5. Tailscale (listo para publicar con Tailscale Funnel 100% gratis)
# 6. Despliegue automático de la aplicación EduHost en /var/www/eduhost
# =============================================================================

set -e

GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${BLUE}======================================================================${NC}"
echo -e "${GREEN}   INSTALADOR AUTOMÁTICO DE SERVIDOR EDUHOST (UBUNTU 24.04 LTS)       ${NC}"
echo -e "${BLUE}======================================================================${NC}"

if [ "$EUID" -ne 0 ]; then
  echo -e "${RED}Por favor ejecuta este script como root o usando sudo:${NC}"
  echo -e "  sudo bash deploy/setup-server.sh"
  exit 1
fi

# Directorio desde donde se ejecuta el repositorio
REPO_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DB_EDUHOST_PASS="EduHost_Master_2026!"

echo -e "\n${YELLOW}[1/8] Actualizando paquetes del sistema e instalando utilidades base...${NC}"
apt-get update -y
apt-get upgrade -y
apt-get install -y curl wget git unzip zip acl ufw software-properties-common ca-certificates lsb-release

echo -e "\n${YELLOW}[2/8] Instalando Nginx, MariaDB Server y PHP 8.3 con todas las extensiones para Laravel...${NC}"
apt-get install -y nginx mariadb-server mariadb-client \
    php8.3-fpm php8.3-cli php8.3-common php8.3-mysql php8.3-zip \
    php8.3-gd php8.3-mbstring php8.3-curl php8.3-xml php8.3-bcmath \
    php8.3-intl php8.3-sqlite3 php8.3-readline

echo -e "\n${YELLOW}[3/8] Instalando Composer globalmente...${NC}"
if ! command -v composer &> /dev/null; then
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi
composer --version

echo -e "\n${YELLOW}[4/8] Configurando MariaDB (Base de datos principal y permisos de aprovisionamiento para alumnos)...${NC}"
systemctl enable mariadb
systemctl start mariadb

mysql -u root <<EOF
CREATE DATABASE IF NOT EXISTS eduhost CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'eduhost_admin'@'localhost' IDENTIFIED BY '${DB_EDUHOST_PASS}';
GRANT ALL PRIVILEGES ON *.* TO 'eduhost_admin'@'localhost' WITH GRANT OPTION;
FLUSH PRIVILEGES;
EOF

echo -e "\n${YELLOW}[5/8] Creando estructura de carpetas para Hosting de Alumnos y descargando Adminer...${NC}"
mkdir -p /var/www/students
mkdir -p /var/www/adminer
mkdir -p /var/www/composer-cache

# Descargar Adminer (Gestor visual de MySQL/MariaDB en un solo archivo ligero y rápido)
wget -q "https://github.com/vrana/adminer/releases/download/v4.8.1/adminer-4.8.1-mysql.php" -O /var/www/adminer/index.php
# Descargar un tema visual limpio para Adminer
wget -q "https://raw.githubusercontent.com/vrana/adminer/master/designs/pepa-linha/adminer.css" -O /var/www/adminer/adminer.css || true

chown -R www-data:www-data /var/www/students /var/www/adminer /var/www/composer-cache
chmod -R 755 /var/www/students /var/www/adminer /var/www/composer-cache

echo -e "\n${YELLOW}[6/8] Configurando Pools de PHP-FPM (Principal + Pool Aislado de Alumnos) y Nginx...${NC}"
# Ajustar límites de subida en el php.ini de FPM principal
sed -i 's/upload_max_filesize = .*/upload_max_filesize = 64M/' /etc/php/8.3/fpm/php.ini
sed -i 's/post_max_size = .*/post_max_size = 64M/' /etc/php/8.3/fpm/php.ini

# Copiar el pool aislado para los alumnos
cp "${REPO_DIR}/deploy/php-fpm/students-pool.conf" /etc/php/8.3/fpm/pool.d/students.conf
touch /var/log/php8.3-fpm-students.log
chown www-data:www-data /var/log/php8.3-fpm-students.log

# Copiar configuración de Nginx
cp "${REPO_DIR}/deploy/nginx/eduhost.conf" /etc/nginx/sites-available/eduhost.conf
ln -sf /etc/nginx/sites-available/eduhost.conf /etc/nginx/sites-enabled/eduhost.conf
rm -f /etc/nginx/sites-enabled/default

systemctl restart php8.3-fpm
nginx -t && systemctl restart nginx
systemctl enable php8.3-fpm nginx

echo -e "\n${YELLOW}[7/8] Desplegando la aplicación EduHost en /var/www/eduhost...${NC}"
mkdir -p /var/www/eduhost
cp -r "${REPO_DIR}/." /var/www/eduhost/
cd /var/www/eduhost

if [ ! -f .env ]; then
    cp .env.example .env
    sed -i "s/DB_DATABASE=.*/DB_DATABASE=eduhost/" .env
    sed -i "s/DB_USERNAME=.*/DB_USERNAME=eduhost_admin/" .env
    sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=${DB_EDUHOST_PASS}/" .env
    sed -i "s|STUDENTS_ROOT_PATH=.*|STUDENTS_ROOT_PATH=/var/www/students|" .env
fi

export COMPOSER_ALLOW_SUPERUSER=1
export COMPOSER_HOME=/var/www/composer-cache
composer install --no-interaction --prefer-dist --optimize-autoloader

php artisan key:generate --force
php artisan migrate --seed --force
php artisan storage:link || true

chown -R www-data:www-data /var/www/eduhost
chmod -R 775 /var/www/eduhost/storage /var/www/eduhost/bootstrap/cache

echo -e "\n${YELLOW}[8/8] Instalando Tailscale para publicación gratuita en Internet (Tailscale Funnel)...${NC}"
if ! command -v tailscale &> /dev/null; then
    curl -fsSL https://tailscale.com/install.sh | sh
fi

# Configurar Firewall UFW
ufw allow OpenSSH
ufw allow 'Nginx Full'
ufw --force enable

LOCAL_IP=$(hostname -I | awk '{print $1}')

echo -e "\n${GREEN}======================================================================${NC}"
echo -e "${GREEN}   ¡SERVIDOR EDUHOST INSTALADO Y FUNCIONANDO EXITOSAMENTE!            ${NC}"
echo -e "${GREEN}======================================================================${NC}"
echo -e "Acceso en Red Local (LAN del Instituto): ${BLUE}http://${LOCAL_IP}${NC}"
echo -e "Gestor de Bases de Datos (Adminer):      ${BLUE}http://${LOCAL_IP}/db-manager/${NC}"
echo -e ""
echo -e "${YELLOW}Credenciales por defecto del Profesor:${NC}"
echo -e "  Email:      profesor@instituto.edu"
echo -e "  Contraseña: password123"
echo -e ""
echo -e "${YELLOW}Último paso para publicarlo en INTERNET GRATIS con Tailscale Funnel:${NC}"
echo -e "  1. Ejecuta:  ${GREEN}sudo tailscale up${NC}  (y abre el link para iniciar sesión gratis)"
echo -e "  2. Ejecuta:  ${GREEN}sudo tailscale funnel --bg 80${NC}"
echo -e "  3. ¡Listo! Tailscale te mostrará tu URL pública HTTPS permanente."
echo -e "${BLUE}======================================================================${NC}"

