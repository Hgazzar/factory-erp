#!/usr/bin/env sh
# Railway / Docker entrypoint:
# 1) امسح كاش Laravel مع كل إقلاع
# 2) شغّل PHP built-in server مع public/router.php
#    (index.php كـ router يكسر /build/assets → CSS تختفي ويظهر اللوجين بدون ستايل)
set -eu

cd /var/www/html

echo "==> Clearing Laravel caches (optimize:clear)"
php artisan optimize:clear --no-interaction 2>/dev/null || true

if [ "${MIGRATE_ON_START:-false}" = "true" ]; then
  echo "==> Running migrations (MIGRATE_ON_START=true)"
  php artisan migrate --force --no-interaction 2>/dev/null || true
fi

PORT="${PORT:-8080}"
echo "==> Starting PHP server on 0.0.0.0:${PORT} (static-aware router)"

# أوامر start مخصّصة من Railway (اختياري). تجنّب php -S ... index.php بدون router.php
if [ "$#" -gt 0 ]; then
  echo "==> Executing start command: $*"
  exec "$@"
fi

exec php -d opcache.enable_cli=1 -S "0.0.0.0:${PORT}" -t public public/router.php
