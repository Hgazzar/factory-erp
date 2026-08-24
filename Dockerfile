# -----------------------------------------------------------------------------
# مرحلة Node: بناء Vite (manifest + assets). بدونها قد يطلب المتصفح ملف JS غير موجود
# فيُرجع Laravel صفحة HTML → Uncaught SyntaxError: Unexpected token '<'
# -----------------------------------------------------------------------------
FROM node:22-bookworm-slim AS frontend
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY vite.config.js postcss.config.js tailwind.config.js ./
COPY resources ./resources
RUN mkdir -p public && npm run build

FROM php:8.2-fpm

LABEL maintainer="factory-erp"

# إعداد متغيرات البيئة الأساسية للـ Laravel
# CACHE_STORE/SESSION_DRIVER=file: RateLimiter على تسجيل الدخول يعتمد على الـ cache؛ بدون جدول cache في Postgres يحدث 500.
# يمكن تجاوزها من Railway Variables إذا كنت تفضّل database بعد تشغيل migrations كاملة.
ENV APP_ENV=production \
    APP_DEBUG=false \
    APP_PORT=8000 \
    CACHE_STORE=file \
    SESSION_DRIVER=file \
    MIGRATE_ON_START=true

WORKDIR /var/www/html

# تثبيت الحزم اللازمة لبناء الامتدادات (gd, zip, bcmath, pgsql) وبعض الأدوات الأساسية
# في PHP 8.x لا يوجد --with-png؛ دعم PNG يُفعّل تلقائياً عند وجود libpng-dev
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    libpng-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libzip-dev \
    libxml2-dev \
    libpq-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd zip bcmath pdo pdo_pgsql pgsql \
    && rm -rf /var/lib/apt/lists/*

# إضافة Composer من صورة رسمية خفيفة
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# نسخ ملفات composer أولاً للاستفادة من الـ cache في طبقة الـ dependencies
COPY composer.json composer.lock* ./

# تثبيت مكتبات PHP الخاصة بالتطبيق
RUN composer install \
    --no-dev \
    --prefer-dist \
    --optimize-autoloader \
    --no-interaction \
    --no-scripts \
    --ignore-platform-reqs

# نسخ بقية كود التطبيق
COPY . .

# استبدال public/build بمخرجات Vite من مرحلة Node (متسقة مع package-lock)
COPY --from=frontend /app/public/build ./public/build
RUN rm -f public/hot

# رابط public/storage أثناء البناء (root) — تشغيل storage:link وقت الـ deploy يفشل كثيراً بـ Permission denied على Railway
RUN mkdir -p storage/app/public \
    && rm -rf public/storage \
    && ln -sfn ../storage/app/public public/storage

# التأكد من صلاحيات التخزين
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/public/storage \
    && chmod -R ug+rwx /var/www/html/storage /var/www/html/bootstrap/cache

    USER www-data

    EXPOSE 8080
    
    CMD ["sh", "-c", "exec php -d opcache.enable_cli=1 -d opcache.validate_timestamps=0 -S 0.0.0.0:${PORT:-8080} -t public"]