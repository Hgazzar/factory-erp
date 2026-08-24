# -----------------------------------------------------------------------------
# مرحلة Node: بناء Vite (manifest + assets)
# -----------------------------------------------------------------------------
FROM node:22-bookworm-slim AS frontend
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY vite.config.js postcss.config.js tailwind.config.js ./
COPY resources ./resources
RUN mkdir -p public && npm run build

# -----------------------------------------------------------------------------
# مرحلة PHP: Nginx + PHP-FPM (إنتاج) — ليس php -S
# -----------------------------------------------------------------------------
FROM php:8.2-fpm-bookworm

LABEL maintainer="factory-erp"

ENV APP_ENV=production \
    APP_DEBUG=false \
    APP_PORT=8080 \
    CACHE_STORE=file \
    SESSION_DRIVER=file \
    MIGRATE_ON_START=true

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    curl \
    nginx \
    gettext-base \
    libpng-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libzip-dev \
    libxml2-dev \
    libpq-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd zip bcmath pdo pdo_pgsql pgsql opcache \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock* ./
RUN composer install \
    --no-dev \
    --prefer-dist \
    --optimize-autoloader \
    --no-interaction \
    --no-scripts \
    --ignore-platform-reqs

COPY . .

RUN php artisan package:discover --ansi || true

COPY --from=frontend /app/public/build ./public/build
RUN rm -f public/hot

ARG RAILWAY_GIT_COMMIT_SHA=local
RUN printf '%s\nbuilt_at=%s\nstack=nginx-php-fpm\n' "${RAILWAY_GIT_COMMIT_SHA}" "$(date -u +%Y-%m-%dT%H:%M:%SZ)" \
    > public/deploy-marker.txt

RUN mkdir -p storage/app/public \
    && rm -rf public/storage \
    && ln -sfn ../storage/app/public public/storage

# PHP / Opcache / FPM
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/zz-railway.conf

# Nginx template (PORT substituted at runtime)
RUN mkdir -p /etc/nginx/templates \
    && rm -f /etc/nginx/sites-enabled/default
COPY docker/nginx/default.conf.template /etc/nginx/templates/default.conf.template

# Entrypoint
COPY scripts/railway-entrypoint.sh /usr/local/bin/railway-entrypoint.sh
RUN chmod +x /usr/local/bin/railway-entrypoint.sh

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R ug+rwx /var/www/html/storage /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/lib/nginx /var/log/nginx \
    && mkdir -p /run/nginx \
    && chown -R www-data:www-data /run/nginx

# root: entrypoint يشغّل php-fpm + nginx (الـ workers كـ www-data)
USER root

EXPOSE 8080

ENTRYPOINT ["/usr/local/bin/railway-entrypoint.sh"]
