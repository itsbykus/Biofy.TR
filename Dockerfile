# syntax=docker/dockerfile:1
#
# Biofy.TR production image (Coolify / Docker). Apache + mod_php serving public/.
# Persistent data lives in two volumes: /var/www/html/storage and /var/www/html/public/media.
# All configuration comes from environment variables (see docs/COOLIFY.md); no .env file is baked in.

ARG PHP_VERSION=8.4
ARG NODE_VERSION=22

# --- 1. Front-end assets (Vite + Tailwind) -----------------------------------
FROM node:${NODE_VERSION}-bookworm-slim AS assets
WORKDIR /app
COPY package.json package-lock.json .npmrc ./
RUN npm ci --no-audit --no-fund
# Tailwind scans views, scripts and app/ (PHP class names), so the whole source tree is needed.
COPY . .
RUN npm run build

# --- 2. PHP runtime with the required extensions -----------------------------
FROM php:${PHP_VERSION}-apache AS base
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        libfreetype6-dev libicu-dev libjpeg62-turbo-dev libpng-dev libwebp-dev libzip-dev unzip curl; \
    docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp; \
    docker-php-ext-install -j"$(nproc)" bcmath gd intl opcache pdo_mysql zip; \
    rm -rf /var/lib/apt/lists/*; \
    mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"; \
    a2enmod rewrite headers
COPY docker/php.ini "$PHP_INI_DIR/conf.d/zz-biofy.ini"
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

# --- 3. Composer dependencies (production only) -------------------------------
FROM base AS vendor
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --no-scripts --no-autoloader
COPY . .
RUN composer dump-autoload --no-dev --optimize \
    && php artisan package:discover --ansi \
    && rm -rf tests docs scripts docker install/*.md node_modules

# --- 4. Final image ------------------------------------------------------------
FROM base AS runtime
WORKDIR /var/www/html

ENV APP_ENV=production \
    APP_DEBUG=false \
    DB_CONNECTION=mysql \
    SESSION_DRIVER=database \
    CACHE_STORE=file \
    QUEUE_CONNECTION=sync \
    LOG_CHANNEL=stack \
    LOG_STACK=daily,stderr \
    BIOFY_DISABLE_WEB_INSTALLER=1

COPY --from=vendor /app /var/www/html
COPY --from=assets /app/public/build /var/www/html/public/build
COPY public/media/.htaccess /usr/local/share/biofy/media.htaccess
COPY --chmod=755 docker/entrypoint.sh /usr/local/bin/biofy-entrypoint
COPY --chmod=755 docker/healthcheck.sh /usr/local/bin/biofy-healthcheck

# Code is read-only for the web server; only runtime directories are writable.
RUN set -eux; \
    rm -f .env bootstrap/cache/config.php bootstrap/cache/routes-*.php bootstrap/cache/events.php; \
    mkdir -p storage/app/private/backups storage/app/public storage/framework/cache/data \
             storage/framework/sessions storage/framework/views storage/logs public/media; \
    chown -R www-data:www-data storage bootstrap/cache public/media; \
    chmod -R u+rwX,g+rX,g-w,o-rwx storage bootstrap/cache public/media

VOLUME ["/var/www/html/storage", "/var/www/html/public/media"]
EXPOSE 80

HEALTHCHECK --interval=30s --timeout=10s --start-period=180s --retries=5 CMD ["biofy-healthcheck"]

ENTRYPOINT ["biofy-entrypoint"]
CMD ["apache2-foreground"]
