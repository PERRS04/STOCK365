#!/bin/bash
# ============================================================
# STOCK365 — Deploy Script (Ubuntu 24 + Nginx + PHP 8.3)
# Ejecutar como root en el VPS: bash deploy.sh
# ============================================================
set -e

# ── CONFIG ──────────────────────────────────────────────────
REPO_URL="__GITHUB_REPO_URL__"      # ← reemplazado por setup-github.sh
APP_DIR="/var/www/stock365"
DOMAIN="app-stock365.com"
PHP="php8.3"
DB_HOST="127.0.0.1"
DB_DATABASE="stock365"
DB_USERNAME="stock365"
DB_PASSWORD="Stock365Secure2026!"
ADMIN_EMAIL="luisrosaless10@gmail.com"
# ────────────────────────────────────────────────────────────

GREEN="\033[0;32m"; YELLOW="\033[1;33m"; RED="\033[0;31m"; NC="\033[0m"
ok()   { echo -e "${GREEN}  ✓ $1${NC}"; }
info() { echo -e "${YELLOW}  → $1${NC}"; }
die()  { echo -e "${RED}  ✗ $1${NC}"; exit 1; }

echo ""
echo "════════════════════════════════════════════════"
echo "  STOCK365 — Deployment Profesional"
echo "  Dominio: https://$DOMAIN"
echo "════════════════════════════════════════════════"
echo ""

# ── 1. Dependencias del sistema ──────────────────────────────
info "Verificando dependencias..."
apt-get install -y -q git curl unzip certbot python3-certbot-nginx > /dev/null 2>&1
ok "Dependencias OK"

# ── 2. Clonar o actualizar repo ──────────────────────────────
if [ -d "$APP_DIR/.git" ]; then
    info "Actualizando repo existente..."
    cd "$APP_DIR"
    git fetch origin
    git reset --hard origin/main
    ok "Repo actualizado"
else
    info "Clonando repo..."
    git clone "$REPO_URL" "$APP_DIR"
    cd "$APP_DIR"
    ok "Repo clonado"
fi

cd "$APP_DIR"

# ── 3. PHP deps ──────────────────────────────────────────────
info "Instalando dependencias PHP..."
composer install --no-dev --optimize-autoloader --no-interaction --quiet
ok "Composer OK"

# ── 4. .env producción ──────────────────────────────────────
info "Configurando .env..."
cat > .env << ENVEOF
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

$PHP artisan key:generate --force --quiet
ok ".env configurado con APP_KEY generado"

# ── 5. Migraciones ───────────────────────────────────────────
info "Ejecutando migraciones..."
$PHP artisan migrate --force --quiet
ok "Migraciones OK"

# ── 6. Seed inicial (solo si tablas vacías) ──────────────────
USER_COUNT=$($PHP artisan tinker --execute="echo App\Models\User::count();" 2>/dev/null | tail -1)
if [ "$USER_COUNT" = "0" ] || [ -z "$USER_COUNT" ]; then
    info "Seedeando datos iniciales..."
    $PHP artisan db:seed --force --quiet
    ok "Seed OK"
else
    ok "DB ya tiene datos — seed omitido"
fi

# ── 7. Storage y permisos ────────────────────────────────────
info "Configurando storage..."
$PHP artisan storage:link --quiet 2>/dev/null || true
chown -R www-data:www-data "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"
chmod -R 775 "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"
ok "Permisos OK"

# ── 8. Optimización Laravel ──────────────────────────────────
info "Optimizando Laravel..."
$PHP artisan config:cache --quiet
$PHP artisan route:cache --quiet
$PHP artisan view:cache --quiet
ok "Cache OK"

# ── 9. Nginx ─────────────────────────────────────────────────
info "Configurando Nginx..."
# Config sin SSL primero (para que certbot pueda verificar)
cat > /etc/nginx/sites-available/stock365 << 'NGINXEOF'
server {
    listen 80;
    listen [::]:80;
    server_name app-stock365.com www.app-stock365.com;
    root /var/www/stock365/public;
    index index.php;
    client_max_body_size 20M;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        fastcgi_read_timeout 300;
    }

    location ~ /\.(?!well-known).* { deny all; }
}
NGINXEOF

ln -sf /etc/nginx/sites-available/stock365 /etc/nginx/sites-enabled/stock365
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx
ok "Nginx configurado"

# ── 10. SSL con Certbot ──────────────────────────────────────
info "Obteniendo certificado SSL..."
if [ ! -d "/etc/letsencrypt/live/$DOMAIN" ]; then
    certbot --nginx \
        -d "$DOMAIN" \
        -d "www.$DOMAIN" \
        --non-interactive \
        --agree-tos \
        --email "$ADMIN_EMAIL" \
        --redirect \
        --quiet
    ok "SSL instalado"
else
    certbot renew --quiet 2>/dev/null || true
    ok "SSL ya existe — renovación verificada"
fi

# Copiar config nginx final con headers de seguridad
cp "$APP_DIR/deploy/nginx.conf" /etc/nginx/sites-available/stock365
nginx -t && systemctl reload nginx
ok "Nginx con SSL y headers de seguridad activado"

# ── 11. Systemd services ─────────────────────────────────────
info "Configurando servicios systemd..."
cp "$APP_DIR/deploy/stock365-worker.service"    /etc/systemd/system/
cp "$APP_DIR/deploy/stock365-scheduler.service" /etc/systemd/system/
systemctl daemon-reload
systemctl enable stock365-worker stock365-scheduler --quiet
systemctl restart stock365-worker stock365-scheduler
ok "Queue worker y scheduler activos"

# ── 12. Backup cron ──────────────────────────────────────────
info "Configurando backup automático..."
CRON_JOB="0 2 * * * bash $APP_DIR/deploy/backup.sh >> $APP_DIR/storage/logs/backup.log 2>&1"
(crontab -l 2>/dev/null | grep -v "backup.sh"; echo "$CRON_JOB") | crontab -
ok "Backup diario 2:00 AM configurado"

# ── 13. PHP-FPM tuning ───────────────────────────────────────
info "Ajustando PHP-FPM..."
PHP_INI="/etc/php/8.3/fpm/php.ini"
if [ -f "$PHP_INI" ]; then
    sed -i 's/^upload_max_filesize.*/upload_max_filesize = 20M/'     "$PHP_INI" 2>/dev/null || true
    sed -i 's/^post_max_size.*/post_max_size = 25M/'                 "$PHP_INI" 2>/dev/null || true
    sed -i 's/^memory_limit.*/memory_limit = 256M/'                  "$PHP_INI" 2>/dev/null || true
    sed -i 's/^max_execution_time.*/max_execution_time = 300/'        "$PHP_INI" 2>/dev/null || true
    systemctl restart php8.3-fpm
fi
ok "PHP-FPM ajustado"

# ── 14. Permisos finales ─────────────────────────────────────
chown -R www-data:www-data "$APP_DIR"
chmod -R 755 "$APP_DIR"
chmod -R 775 "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"
ok "Permisos finales aplicados"

# ── RESUMEN ──────────────────────────────────────────────────
echo ""
echo "════════════════════════════════════════════════"
echo -e "${GREEN}  ✅  STOCK365 deployado exitosamente${NC}"
echo "════════════════════════════════════════════════"
echo ""
echo "  URL: https://$DOMAIN"
echo ""
echo "  Credenciales:"
echo "  Boss:       boss@stock365.com       / Boss123456"
echo "  Supervisor: supervisor@stock365.com / 123456"
echo "  Centenario: centenario@stock365.com / 123456"
echo ""
echo "  Servicios activos:"
systemctl is-active stock365-worker    | xargs echo "  queue:work —"
systemctl is-active stock365-scheduler | xargs echo "  scheduler  —"
echo ""
