#!/bin/bash
# Dipanggil oleh .cpanel.yml setelah git pull di /home/seaf5528/searia
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
DEPLOY_PATH="${DEPLOY_PATH:-/home/seaf5528/public_html}"
PHP_BIN="${PHP_BIN:-/usr/local/bin/php}"

if [ -x /opt/cpanel/composer/bin/composer ]; then
  COMPOSER_BIN=/opt/cpanel/composer/bin/composer
elif command -v composer >/dev/null 2>&1; then
  COMPOSER_BIN="$(command -v composer)"
else
  COMPOSER_BIN=""
fi

echo "==> Sync public/ -> ${DEPLOY_PATH}"
mkdir -p "$DEPLOY_PATH"

# Salin aset publik (css, js, images, .htaccess, dll.)
# index.php diganti terpisah dengan versi cPanel.
cp -RF "${REPO_ROOT}/public/." "${DEPLOY_PATH}/"
cp -f "${REPO_ROOT}/public/index.cpanel.php" "${DEPLOY_PATH}/index.php"
rm -f "${DEPLOY_PATH}/index.cpanel.php" "${DEPLOY_PATH}/hot"

# Symlink storage publik (unggahan file)
if [ -d "${REPO_ROOT}/storage/app/public" ]; then
  rm -rf "${DEPLOY_PATH}/storage"
  ln -sfn "${REPO_ROOT}/storage/app/public" "${DEPLOY_PATH}/storage"
fi

cd "$REPO_ROOT"

chmod -R ug+rwx storage bootstrap/cache 2>/dev/null || true

if [ -n "$COMPOSER_BIN" ] && [ -f composer.json ]; then
  echo "==> composer install"
  "$PHP_BIN" "$COMPOSER_BIN" install --no-dev --optimize-autoloader --no-interaction
fi

if [ -f .env ] && [ -f artisan ]; then
  echo "==> artisan optimize"
  "$PHP_BIN" artisan migrate --force
  "$PHP_BIN" artisan config:cache
  "$PHP_BIN" artisan route:cache
  "$PHP_BIN" artisan view:cache
else
  echo "!! Lewati artisan: pastikan .env sudah ada di ${REPO_ROOT}/.env"
fi

echo "==> Deploy selesai"
