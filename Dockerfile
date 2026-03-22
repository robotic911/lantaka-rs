# ============================================================
# Stage 1 — PHP dependencies (composer)
# ============================================================
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./

# Install production deps inside the image using PHP 8.4 platform.
# --ignore-platform-reqs is intentional here: the composer image ships
# its own PHP, but the actual runtime PHP 8.4 container has all exts.
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --ignore-platform-reqs

# ============================================================
# Stage 2 — Frontend assets (Node/Vite)
# ============================================================
FROM node:20-bookworm-slim AS frontend

WORKDIR /app

COPY package.json package-lock.json* ./
RUN npm ci --no-audit --prefer-offline 2>/dev/null || npm install --no-audit

COPY vite.config.js ./
COPY resources/ ./resources/
COPY public/ ./public/

RUN npm run build

# ============================================================
# Stage 3 — Runtime image
# ============================================================
FROM php:8.4-fpm-bookworm

# ── System packages ──────────────────────────────────────────
# Includes: nginx, supervisor, GD libs (for phpspreadsheet/maatwebsite),
# PostgreSQL libs, zip, xml/mbstring, intl, curl
RUN apt-get update && apt-get install -y --no-install-recommends \
    nginx \
    supervisor \
    gettext-base \
    curl \
    unzip \
    zip \
    git \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libwebp-dev \
    libzip-dev \
    libpq-dev \
    libxml2-dev \
    libonig-dev \
    libicu-dev \
    libcurl4-openssl-dev \
    && rm -rf /var/lib/apt/lists/*

# ── PHP extensions ───────────────────────────────────────────
RUN docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
        --with-webp \
    && docker-php-ext-install -j$(nproc) \
        gd \
        pdo \
        pdo_pgsql \
        pgsql \
        zip \
        mbstring \
        xml \
        ctype \
        bcmath \
        intl \
        opcache \
        pcntl \
        curl \
        fileinfo \
        exif

# ── PHP production INI tweaks ─────────────────────────────────
COPY docker/php.ini /usr/local/etc/php/conf.d/laravel.ini

# ── PHP-FPM: listen on TCP socket (nginx talks to 127.0.0.1:9000) ──
RUN sed -i 's|listen = /run/php/.*|listen = 127.0.0.1:9000|' \
    /usr/local/etc/php-fpm.d/www.conf 2>/dev/null || true

# ── Application ──────────────────────────────────────────────
WORKDIR /var/www/html

# Copy vendor from Stage 1
COPY --from=vendor /app/vendor ./vendor

# Copy application source
COPY . .

# Copy compiled frontend assets from Stage 2 (overwrites any placeholder public/build)
COPY --from=frontend /app/public/build ./public/build

# Run post-autoload-dump (package discovery) — no artisan calls yet,
# DB isn't available at build time
RUN php artisan package:discover --ansi 2>/dev/null || true

# Storage & cache permissions
RUN mkdir -p \
        storage/logs \
        storage/framework/sessions \
        storage/framework/views \
        storage/framework/cache \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# ── Nginx & Supervisor config ─────────────────────────────────
COPY docker/nginx.conf.template /etc/nginx/nginx.conf.template
COPY docker/supervisord.conf    /etc/supervisor/conf.d/laravel.conf

# Remove default nginx site
RUN rm -f /etc/nginx/sites-enabled/default /etc/nginx/conf.d/default.conf

# ── Startup ───────────────────────────────────────────────────
COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

# Railway injects PORT at runtime — default 8080 as fallback
EXPOSE 8080

CMD ["/start.sh"]
