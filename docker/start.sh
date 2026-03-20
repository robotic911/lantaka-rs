#!/bin/bash
# ============================================================
# Lantaka Reservation System — Container Startup
# ============================================================
set -e

# ── 1. PORT ──────────────────────────────────────────────────
# Railway injects PORT. Default to 8080 so local docker run works.
export PORT="${PORT:-8080}"
echo "[start] Binding on port $PORT"

# ── 2. Generate nginx config from template ───────────────────
# envsubst '${PORT}' only substitutes PORT — leaves nginx $uri etc. alone.
envsubst '${PORT}' < /etc/nginx/nginx.conf.template > /etc/nginx/nginx.conf
echo "[start] Nginx config written"

# ── 3. Ensure storage/cache dirs exist and are writable ──────
mkdir -p \
    /var/www/html/storage/logs \
    /var/www/html/storage/framework/sessions \
    /var/www/html/storage/framework/views \
    /var/www/html/storage/framework/cache \
    /var/www/html/bootstrap/cache

chown -R www-data:www-data \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache

chmod -R 775 \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache

# ── 4. App key guard ─────────────────────────────────────────
if [ -z "$APP_KEY" ]; then
    echo "[start] WARNING: APP_KEY is not set. Generating one now..."
    php /var/www/html/artisan key:generate --force --ansi
fi

# ── 5. Database migrations ───────────────────────────────────
echo "[start] Running migrations..."
php /var/www/html/artisan migrate --force --ansi
echo "[start] Migrations complete"

# ── 6. Laravel production caches ────────────────────────────
echo "[start] Caching config / routes / views..."
php /var/www/html/artisan config:cache
php /var/www/html/artisan route:cache
php /var/www/html/artisan view:cache
echo "[start] Caches written"

# ── 7. Hand off to Supervisor ────────────────────────────────
echo "[start] Starting PHP-FPM + Nginx via supervisord..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/laravel.conf
