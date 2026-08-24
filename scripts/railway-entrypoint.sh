#!/usr/bin/env sh
# Railway / Docker entrypoint:
# 1) امسح كاش Laravel مع كل إقلاع
# 2) شغّل PHP built-in server مع public/router.php دائماً
#    (أي startCommand يمرّر index.php بدون router يكسر CSS → 302 /login)
set -eu

cd /var/www/html

echo "==> Clearing Laravel caches (optimize:clear)"
php artisan optimize:clear --no-interaction 2>/dev/null || true

if [ "${MIGRATE_ON_START:-false}" = "true" ]; then
  echo "==> Running migrations (MIGRATE_ON_START=true)"
  php artisan migrate --force --no-interaction 2>/dev/null || true
fi

PORT="${PORT:-8080}"

if [ "$#" -gt 0 ]; then
  echo "==> Ignoring custom start command (use Dockerfile router): $*"
fi

if [ ! -f public/router.php ]; then
  echo "==> FATAL: public/router.php missing — rebuild image from latest main" >&2
  exit 1
fi

echo "==> Starting PHP server on 0.0.0.0:${PORT} (public/router.php)"
exec php -d opcache.enable_cli=1 -S "0.0.0.0:${PORT}" -t public public/router.php
