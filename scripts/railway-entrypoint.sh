#!/usr/bin/env sh
# Railway production entrypoint: Nginx + PHP-FPM + Laravel caches
set -eu

cd /var/www/html

PORT="${PORT:-8080}"
export PORT

echo "==> Preparing storage / bootstrap cache"
# ملاحظة: ملفات الشعارات تحت storage/app/public تُفقد عند كل Redeploy
# ما لم يُربط Volume ثابت على Railway لمسار /var/www/html/storage/app/public
mkdir -p storage/app/public/tenant \
  storage/framework/cache \
  storage/framework/sessions \
  storage/framework/views \
  bootstrap/cache \
  /var/log/nginx \
  /var/lib/nginx/body \
  /run/nginx \
  /run/php
chown -R www-data:www-data storage bootstrap/cache /var/log/nginx /var/lib/nginx /run/nginx 2>/dev/null || true
chmod -R ug+rwx storage bootstrap/cache 2>/dev/null || true

if [ ! -e public/storage ]; then
  php artisan storage:link --no-interaction 2>/dev/null \
    || ln -sfn ../storage/app/public public/storage 2>/dev/null \
    || true
fi

if [ "${MIGRATE_ON_START:-false}" = "true" ]; then
  echo "==> Running migrations (MIGRATE_ON_START=true)"
  php artisan migrate --force --no-interaction || true
fi

echo "==> Building Laravel production caches (config / route / view)"
# لا optimize:clear في كل إقلاع — يبطّئ كل طلب
run_artisan() {
  if command -v runuser >/dev/null 2>&1; then
    runuser -u www-data -- php artisan "$@"
  else
    su -s /bin/sh www-data -c "php artisan $(printf '%q ' "$@")"
  fi
}

run_artisan config:cache --no-interaction
run_artisan route:cache --no-interaction
if ! run_artisan view:cache --no-interaction; then
  echo "==> WARN: view:cache failed — continuing" >&2
fi

# ضمان قراءة الكاش بواسطة FPM
chown -R www-data:www-data bootstrap/cache storage 2>/dev/null || true

echo "==> Rendering Nginx config (listen ${PORT})"
envsubst '${PORT}' < /etc/nginx/templates/default.conf.template \
  > /etc/nginx/conf.d/default.conf

# تعطيل أي default متعارض من حزمة nginx
rm -f /etc/nginx/sites-enabled/default 2>/dev/null || true

# تأكد أن nginx الرئيسي يحمّل conf.d
if ! grep -q 'include /etc/nginx/conf.d/\*.conf' /etc/nginx/nginx.conf 2>/dev/null; then
  # بعض الصور تعتمد sites-enabled فقط
  mkdir -p /etc/nginx/sites-enabled
  cp /etc/nginx/conf.d/default.conf /etc/nginx/sites-available/railway-app.conf
  ln -sfn /etc/nginx/sites-available/railway-app.conf /etc/nginx/sites-enabled/railway-app.conf
fi

# nginx يجب ألا يستمع على 80 إن PORT مختلف — عطّل listen الافتراضي إن وُجد
sed -i 's/listen \[::\]:80/# listen [::]:80/g; s/listen 80/# listen 80/g' /etc/nginx/sites-available/default 2>/dev/null || true

echo "==> Starting PHP-FPM"
php-fpm -D

echo "==> Starting Nginx (production) on 0.0.0.0:${PORT}"
exec nginx -g 'daemon off;'
