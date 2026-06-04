#!/bin/bash
set -e
echo "Starting deploy..."

git pull origin main

# اگر فایل .env.production وجود ندارد، از نمونه کپی می‌گیریم و می‌خواهیم پر شود
[ ! -f .env.production ] && \
  cp .env.production.example .env.production && \
  echo "⚠️  Please fill .env.production then run again" && \
  exit 1

# APP_KEY باید پر باشد وگرنه Laravel اصلاً boot نمی‌کند
APP_KEY_VAL=$(grep '^APP_KEY=' .env.production | cut -d= -f2-)
if [ -z "$APP_KEY_VAL" ]; then
  echo "❌ APP_KEY is empty in .env.production"
  echo "   Run: docker compose --env-file .env.production -f docker-compose.prod.yml run --rm laravel php artisan key:generate --show"
  echo "   Then paste the result into .env.production as APP_KEY=..."
  exit 1
fi

docker compose --env-file .env.production -f docker-compose.prod.yml up -d --build

docker compose --env-file .env.production -f docker-compose.prod.yml exec laravel php artisan migrate --force
docker compose --env-file .env.production -f docker-compose.prod.yml exec laravel php artisan config:cache
docker compose --env-file .env.production -f docker-compose.prod.yml exec laravel php artisan route:cache
# storage:link نیاز به نوشتن در public/ دارد — با root اجرا می‌شود
docker compose --env-file .env.production -f docker-compose.prod.yml exec --user root laravel php artisan storage:link

echo "✅ Deploy complete!"
