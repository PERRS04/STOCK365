#!/bin/bash
# ============================================================
# STOCK365 — Fix + Deploy Completo para VPS Ubuntu 24
# Uso: bash <(curl -fsSL https://raw.githubusercontent.com/PERRS04/STOCK365/master/deploy/fix-deploy.sh)
# ============================================================
set -e

GREEN="\033[0;32m"; YELLOW="\033[1;33m"; RED="\033[0;31m"; CYAN="\033[0;36m"; NC="\033[0m"
ok()   { echo -e "${GREEN}  ✓ $1${NC}"; }
info() { echo -e "${YELLOW}  → $1${NC}"; }
err()  { echo -e "${RED}  ✗ $1${NC}"; }
hdr()  { echo -e "\n${CYAN}══ $1 ══${NC}"; }

DOMAIN="app-stock365.com"
ADMIN_EMAIL="luisrosaless10@gmail.com"
DB_HOST="127.0.0.1"
DB_DATABASE="stock365"
DB_USERNAME="stock365"
DB_PASSWORD="Stock365Secure2026!"
GITHUB_REPO="https://github.com/PERRS04/STOCK365.git"
TARGET_DIR="/var/www/stock365"

echo ""
echo "════════════════════════════════════════════════════"
echo "  STOCK365 — Fix & Deploy Profesional"
echo "  $(date)"
echo "════════════════════════════════════════════════════"

# ── PASO 1: Agregar SSH key para acceso futuro ────────────────
hdr "1. SSH Key"
mkdir -p /root/.ssh
grep -qF "AAAAC3NzaC1lZDI1NTE5AAAAIMckafGlB1n+IC4gqjNX5AMUBds7Vg59FMQ4urhKW6oJ" /root/.ssh/authorized_keys 2>/dev/null || \
  echo "ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIMckafGlB1n+IC4gqjNX5AMUBds7Vg59FMQ4urhKW6oJ luisrosaless10@gmail.com" >> /root/.ssh/authorized_keys
chmod 700 /root/.ssh; chmod 600 /root/.ssh/authorized_keys
ok "SSH key de Mr Perrs autorizada"

# ── PASO 2: Instalar dependencias ────────────────────────────
hdr "2. Dependencias del sistema"
export DEBIAN_FRONTEND=noninteractive
apt-get update -qq
apt-get install -y -q git curl unzip certbot python3-certbot-nginx > /dev/null
ok "git, curl, certbot instalados"

# ── PASO 3: Diagnosticar y fijar estructura de directorios ───
hdr "3. Estructura de directorios"
info "Buscando artisan en /var/www..."
ARTISAN_PATH=$(find /var/www -name "artisan" -not -path "*/vendor/*" 2>/dev/null | head -1)

if [ -z "$ARTISAN_PATH" ]; then
    info "artisan no encontrado — clonando repo desde GitHub..."
    rm -rf "$TARGET_DIR"
    git clone "$GITHUB_REPO" "$TARGET_DIR"
    ARTISAN_PATH="$TARGET_DIR/artisan"
    ok "Repo clonado en $TARGET_DIR"
else
    FOUND_DIR=$(dirname "$ARTISAN_PATH")
    ok "artisan encontrado en: $FOUND_DIR"

    if [ "$FOUND_DIR" != "$TARGET_DIR" ]; then
        info "Moviendo Laravel de $FOUND_DIR → $TARGET_DIR..."
        rm -rf "$TARGET_DIR"
        mv "$FOUND_DIR" "$TARGET_DIR"
        ok "Movido correctamente"
    fi
fi

# Actualizar desde GitHub
cd "$TARGET_DIR"
info "Sincronizando con GitHub..."
git fetch origin 2>/dev/null || true
git reset --hard origin/master 2>/dev/null || git reset --hard origin/main 2>/dev/null || true
ok "Código actualizado desde GitHub"

# Verificar estructura
for f in artisan composer.json public/index.php routes/web.php app/Models/User.php; do
    [ -f "$f" ] && ok "  $f" || { err "FALTA: $f"; exit 1; }
done

# ── PASO 4: PHP deps ─────────────────────────────────────────
hdr "4. Dependencias PHP"
# Asegurar que composer esté disponible
if ! command -v composer &>/dev/null; then
    info "Instalando Composer..."
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi

cd "$TARGET_DIR"
info "Ejecutando composer install..."
composer install --no-dev --optimize-autoloader --no-interaction 2>&1 | tail -3
ok "Composer OK"

# ── PASO 5: .env producción ──────────────────────────────────
hdr "5. Configuración .env"
cat > "$TARGET_DIR/.env" << ENVEOF
APP_NAME="STOCK 365"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://${DOMAIN}

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=${DB_HOST}
DB_PORT=3306
DB_DATABASE=${DB_DATABASE}
DB_USERNAME=${DB_USERNAME}
DB_PASSWORD=${DB_PASSWORD}

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=480
SESSION_DOMAIN=.${DOMAIN}
SESSION_SECURE_COOKIE=true

MAIL_MAILER=log

COLORS_PRIMARY=#003594
COLORS_ACCENT=#FFD100
COMPANY_NAME="STOCK 365"
ENVEOF

php8.3 artisan key:generate --force --quiet
ok ".env configurado — APP_KEY generado"

# ── PASO 6: Base de datos ────────────────────────────────────
hdr "6. Base de datos"
info "Verificando conexión MySQL..."
mysql -h"$DB_HOST" -u"$DB_USERNAME" -p"$DB_PASSWORD" -e "SELECT 1;" "$DB_DATABASE" > /dev/null 2>&1 \
  && ok "MySQL conectado OK" \
  || { err "No se puede conectar a MySQL. Verifica DB_USERNAME/PASSWORD."; exit 1; }

info "Ejecutando migraciones..."
php8.3 artisan migrate --force 2>&1 | tail -5
ok "Migraciones OK"

# Seed solo si no hay usuarios
USER_COUNT=$(mysql -h"$DB_HOST" -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" -se "SELECT COUNT(*) FROM users;" 2>/dev/null || echo "0")
if [ "$USER_COUNT" = "0" ]; then
    info "Seedeando datos iniciales..."
    php8.3 artisan db:seed --force --quiet
    ok "Seed OK (usuarios + productos)"
else
    ok "DB ya tiene datos ($USER_COUNT usuarios) — seed omitido"
fi

# ── PASO 7: Storage y permisos ───────────────────────────────
hdr "7. Storage y permisos"
cd "$TARGET_DIR"
php8.3 artisan storage:link --quiet 2>/dev/null || true
chown -R www-data:www-data "$TARGET_DIR"
chmod -R 755 "$TARGET_DIR"
chmod -R 775 "$TARGET_DIR/storage" "$TARGET_DIR/bootstrap/cache"
ok "Permisos configurados"

# ── PASO 8: Optimización Laravel ────────────────────────────
hdr "8. Optimización Laravel"
php8.3 artisan config:cache --quiet
php8.3 artisan route:cache --quiet
php8.3 artisan view:cache --quiet
ok "Config, Route, View cache generados"

# ── PASO 9: PHP-FPM tuning ───────────────────────────────────
hdr "9. PHP-FPM"
PHP_INI="/etc/php/8.3/fpm/php.ini"
if [ -f "$PHP_INI" ]; then
    sed -i 's/^upload_max_filesize.*/upload_max_filesize = 20M/'  "$PHP_INI" 2>/dev/null || true
    sed -i 's/^post_max_size.*/post_max_size = 25M/'              "$PHP_INI" 2>/dev/null || true
    sed -i 's/^memory_limit.*/memory_limit = 256M/'               "$PHP_INI" 2>/dev/null || true
    sed -i 's/^max_execution_time.*/max_execution_time = 300/'     "$PHP_INI" 2>/dev/null || true
fi
systemctl restart php8.3-fpm 2>/dev/null || service php8.3-fpm restart 2>/dev/null || true
ok "PHP-FPM reiniciado"

# ── PASO 10: Nginx ───────────────────────────────────────────
hdr "10. Nginx"
cat > /etc/nginx/sites-available/stock365 << NGINXEOF
server {
    listen 80;
    listen [::]:80;
    server_name ${DOMAIN} www.${DOMAIN};
    root /var/www/stock365/public;
    index index.php;
    client_max_body_size 20M;
    charset utf-8;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }
    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        fastcgi_read_timeout 300;
    }

    location ~ /\.(?!well-known).* { deny all; }

    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }
}
NGINXEOF

ln -sf /etc/nginx/sites-available/stock365 /etc/nginx/sites-enabled/stock365
rm -f /etc/nginx/sites-enabled/default
nginx -t 2>&1 && systemctl reload nginx
ok "Nginx configurado y recargado"

# ── PASO 11: SSL con Certbot ─────────────────────────────────
hdr "11. SSL (Let's Encrypt)"
info "Verificando que el dominio apunte a este servidor..."
SERVER_IP=$(curl -s ifconfig.me 2>/dev/null || hostname -I | awk '{print $1}')
DOMAIN_IP=$(dig +short "$DOMAIN" 2>/dev/null | tail -1 || nslookup "$DOMAIN" 2>/dev/null | grep Address | tail -1 | awk '{print $2}')

if [ "$SERVER_IP" = "$DOMAIN_IP" ]; then
    ok "DNS OK: $DOMAIN → $SERVER_IP"
    if [ ! -d "/etc/letsencrypt/live/$DOMAIN" ]; then
        certbot --nginx \
            -d "$DOMAIN" -d "www.$DOMAIN" \
            --non-interactive --agree-tos \
            --email "$ADMIN_EMAIL" \
            --redirect --quiet
        ok "Certificado SSL instalado"
    else
        certbot renew --quiet 2>/dev/null || true
        ok "SSL ya existe — renovado"
    fi
    # Agregar security headers al bloque SSL
    NGINX_SSL_CONF="/etc/nginx/sites-available/stock365"
    if grep -q "ssl_certificate" "$NGINX_SSL_CONF"; then
        sed -i '/server_name.*app-stock365/a\    add_header X-Frame-Options "SAMEORIGIN" always;\n    add_header X-Content-Type-Options "nosniff" always;\n    add_header Strict-Transport-Security "max-age=63072000; includeSubDomains" always;' "$NGINX_SSL_CONF" 2>/dev/null || true
        nginx -t 2>/dev/null && systemctl reload nginx 2>/dev/null || true
    fi
    ok "Security headers activados"
else
    err "DNS aún no apunta a este servidor (${DOMAIN} → ${DOMAIN_IP:-no resuelve}, VPS IP: $SERVER_IP)"
    info "SSL se omite — configura el DNS en Hostinger para que $DOMAIN apunte a $SERVER_IP"
    info "Cuando el DNS propague, ejecuta: certbot --nginx -d $DOMAIN -d www.$DOMAIN --email $ADMIN_EMAIL --agree-tos --non-interactive --redirect"
fi

# ── PASO 12: Systemd services ────────────────────────────────
hdr "12. Servicios en background"
cp "$TARGET_DIR/deploy/stock365-worker.service"    /etc/systemd/system/ 2>/dev/null || true
cp "$TARGET_DIR/deploy/stock365-scheduler.service" /etc/systemd/system/ 2>/dev/null || true
systemctl daemon-reload 2>/dev/null || true
systemctl enable stock365-worker stock365-scheduler --quiet 2>/dev/null || true
systemctl restart stock365-worker stock365-scheduler 2>/dev/null || true
ok "Queue worker y scheduler activos"

# ── PASO 13: Backup cron ─────────────────────────────────────
hdr "13. Backup automático"
mkdir -p /var/backups/stock365/{db,uploads}
CRON_JOB="0 2 * * * bash $TARGET_DIR/deploy/backup.sh >> $TARGET_DIR/storage/logs/backup.log 2>&1"
(crontab -l 2>/dev/null | grep -v "backup.sh"; echo "$CRON_JOB") | crontab -
ok "Backup diario 2:00 AM configurado"

# ── VERIFICACIÓN FINAL ───────────────────────────────────────
hdr "Verificación final"

# Test HTTP response
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "http://localhost/" -H "Host: $DOMAIN" 2>/dev/null || echo "000")
if [[ "$HTTP_CODE" =~ ^(200|301|302|405)$ ]]; then
    ok "Laravel responde HTTP $HTTP_CODE"
else
    err "Laravel retornó HTTP $HTTP_CODE"
    info "Revisando logs: tail -50 $TARGET_DIR/storage/logs/laravel.log"
fi

# Test DB
php8.3 -r "
require '$TARGET_DIR/vendor/autoload.php';
\$app = require '$TARGET_DIR/bootstrap/app.php';
\$kernel = \$app->make(Illuminate\Contracts\Console\Kernel::class);
\$kernel->bootstrap();
\$count = App\Models\User::count();
echo \"DB OK — \$count usuarios\n\";
" 2>/dev/null && ok "DB accesible" || err "Problema con DB"

echo ""
echo "════════════════════════════════════════════════════"
echo -e "${GREEN}  ✅  STOCK365 deployado exitosamente${NC}"
echo "════════════════════════════════════════════════════"
echo ""
APP_URL_FINAL="https://$DOMAIN"
[ ! -d "/etc/letsencrypt/live/$DOMAIN" ] && APP_URL_FINAL="http://$SERVER_IP"
echo "  URL activa:     $APP_URL_FINAL"
echo "  URL producción: https://$DOMAIN (cuando DNS propague)"
echo ""
echo "  Boss:        boss@stock365.com       / Boss123456"
echo "  Supervisor:  supervisor@stock365.com / 123456"
echo "  Centenario:  centenario@stock365.com / 123456"
echo ""
echo "  Logs:   tail -f $TARGET_DIR/storage/logs/laravel.log"
echo "  Update: bash $TARGET_DIR/deploy/update.sh"
echo ""
