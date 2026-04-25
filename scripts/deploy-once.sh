#!/usr/bin/env bash
# تشغيل لمرة واحدة بعد رفع الكود على السيرفر (HR + باقي التطبيق).
# الاستخدام: من جذر المشروع: bash scripts/deploy-once.sh
# أو: chmod +x scripts/deploy-once.sh && ./scripts/deploy-once.sh

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

echo "==> Composer"
if [[ "${INSTALL_DEV_DEPS:-0}" == "1" ]]; then
  composer install --no-interaction --prefer-dist
else
  composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
fi

echo "==> تحقق: يجب أن يكون APP_KEY مضبوطاً في .env (مرة أولى فقط: php artisan key:generate --show ثم انسخ القيمة)"

echo "==> Migrate"
php artisan migrate --force

echo "==> Storage link"
php artisan storage:link 2>/dev/null || true

if [[ "${BUILD_ASSETS:-0}" == "1" ]]; then
  echo "==> npm ci + build (عطّل بـ BUILD_ASSETS=0 إن كنت ترفع public/build من CI)"
  npm ci
  npm run build
fi

echo "==> Caches (إنتاج)"
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> تم. اختياري: php artisan queue:restart إن وُجدت workers للطابور"
