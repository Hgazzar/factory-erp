#!/usr/bin/env sh
# Railway / Docker entrypoint: امسح كاش Laravel مع كل إقلاع حاوية (Deploy).
# يمنع بقاء routes/config/views القديمة بعد تحديث الكود.
set -eu

cd /var/www/html

echo "==> Clearing Laravel caches (optimize:clear)"
php artisan optimize:clear --no-interaction || true

if [ "${MIGRATE_ON_START:-false}" = "true" ]; then
  echo "==> Running migrations (MIGRATE_ON_START=true)"
  php artisan migrate --force --no-interaction || true
fi

# إذا مرّر Railway startCommand كـ args لـ ENTRYPOINT، نفّذه بعد مسح الكاش
if [ "$#" -gt 0 ]; then
  echo "==> Executing start command: $*"
  exec "$@"
fi

PORT="${PORT:-8080}"
echo "==> Starting app on 0.0.0.0:${PORT}"
exec php artisan serve --host=0.0.0.0 --port="${PORT}"
