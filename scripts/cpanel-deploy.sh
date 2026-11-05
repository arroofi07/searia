#!/bin/bash
# Dijalankan otomatis oleh Git cPanel (.cpanel.yml) — tidak perlu Terminal.
set -u

REPO_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
DEPLOY_PATH="${DEPLOY_PATH:-/home/seaf5528/public_html}"
PHP_BIN="${PHP_BIN:-/usr/local/bin/php}"

if [ ! -x "$PHP_BIN" ]; then
  PHP_BIN="$(command -v php || true)"
fi

COMPOSER_BIN=""
if [ -x /opt/cpanel/composer/bin/composer ]; then
  COMPOSER_BIN=/opt/cpanel/composer/bin/composer
elif command -v composer >/dev/null 2>&1; then
  COMPOSER_BIN="$(command -v composer)"
fi

echo "==> Sync public/ -> ${DEPLOY_PATH}"
mkdir -p "$DEPLOY_PATH"
cp -RF "${REPO_ROOT}/public/." "${DEPLOY_PATH}/"
cp -f "${REPO_ROOT}/public/index.cpanel.php" "${DEPLOY_PATH}/index.php"
rm -f "${DEPLOY_PATH}/index.cpanel.php" "${DEPLOY_PATH}/hot"

if [ -d "${REPO_ROOT}/storage/app/public" ]; then
  rm -rf "${DEPLOY_PATH}/storage"
  ln -sfn "${REPO_ROOT}/storage/app/public" "${DEPLOY_PATH}/storage"
fi

cd "$REPO_ROOT"
chmod -R ug+rwx storage bootstrap/cache 2>/dev/null || true

# Composer opsional: jika gagal/tidak ada, pakai folder vendor yang sudah diupload.
if [ -n "$COMPOSER_BIN" ] && [ -n "$PHP_BIN" ] && [ -f composer.json ]; then
  echo "==> composer install"
  if ! "$PHP_BIN" "$COMPOSER_BIN" install --no-dev --optimize-autoloader --no-interaction; then
    echo "!! composer gagal — pastikan vendor/ sudah diupload manual"
  fi
elif [ ! -f vendor/autoload.php ]; then
  echo "!! vendor/autoload.php belum ada — upload folder vendor dari lokal"
fi

if [ -f .env ] && [ -f artisan ] && [ -n "$PHP_BIN" ] && [ -f vendor/autoload.php ]; then
  echo "==> artisan migrate + cache"
  "$PHP_BIN" artisan migrate --force || echo "!! migrate gagal"
  "$PHP_BIN" artisan config:cache || true
  "$PHP_BIN" artisan route:cache || true
  "$PHP_BIN" artisan view:cache || true
else
  echo "!! Lewati artisan: cek .env dan vendor/ di ${REPO_ROOT}"
fi

echo "==> Deploy selesai"
exit 0
