#!/usr/bin/env sh
# Railway / Docker entrypoint:
# 1) امسح كاش Laravel مع كل إقلاع
# 2) شغّل السيرفر بطريقة توصل كل المسارات إلى public/index.php
#    (php -S -t public بدون router يكسر /login و /admin ويعرض فقط /)
set -eu

cd /var/www/html

echo "==> Clearing Laravel caches (optimize:clear)"
php artisan optimize:clear --no-interaction 2>/dev/null || true

if [ "${MIGRATE_ON_START:-false}" = "true" ]; then
  echo "==> Running migrations (MIGRATE_ON_START=true)"
  php artisan migrate --force --no-interaction 2>/dev/null || true
fi

PORT="${PORT:-8080}"
echo "==> Starting PHP server on 0.0.0.0:${PORT} (Laravel router)"

# إذا مرّر Railway أوامر إضافية، نفّذها بعد المسح
if [ "$#" -gt 0 ]; then
  echo "==> Executing start command: $*"
  exec "$@"
fi

# Router script = public/index.php حتى تعمل /login و /admin وكل routes التطبيق
exec php -d opcache.enable_cli=1 -S "0.0.0.0:${PORT}" -t public public/index.php
