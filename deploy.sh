#!/bin/bash
# =============================================================
# deploy.sh — Script deployment AKURAT ke Azure VM
# Dipanggil oleh: GitHub Actions (ci.yml job: deploy)
# Prasyarat: Docker & Docker Compose sudah terinstall di VM
# =============================================================
set -e  # Hentikan seluruh script jika ada perintah yang gagal

echo "🐳 [1/8] Build dan start container..."
docker compose up -d --build

echo "📦 [2/8] Memperbarui dependensi Composer (vendor)..."
docker exec akurat_app composer install --no-dev --optimize-autoloader --ignore-platform-reqs

echo "🔄 [3/8] Merestart container aplikasi agar autoloader baru terbaca..."
docker compose restart app

echo "⏳ [4/8] Menunggu database PostgreSQL siap..."
# Loop health-check — lebih andal daripada sleep statis
MAX_RETRIES=30
COUNT=0
until docker exec akurat_db pg_isready -U postgres -d db_akurat > /dev/null 2>&1; do
  COUNT=$((COUNT + 1))
  if [ "$COUNT" -ge "$MAX_RETRIES" ]; then
    echo "❌ Database tidak merespons setelah ${MAX_RETRIES} percobaan. Deployment dibatalkan."
    exit 1
  fi
  echo "   Menunggu... (percobaan $COUNT/$MAX_RETRIES)"
  sleep 2
done
echo "   ✅ Database siap!"

echo "🗃️  [5/8] Menjalankan migrasi database..."
docker exec akurat_app php artisan migrate --force

echo "🔗 [6/8] Menyiapkan storage link & cache..."
docker exec akurat_app php artisan storage:link
docker exec akurat_app php artisan config:clear
docker exec akurat_app php artisan config:cache
docker exec akurat_app php artisan route:cache
docker exec akurat_app php artisan view:cache
docker exec akurat_app php artisan optimize

echo "⚡ [7/8] Menjalankan Pulse worker di latar belakang..."
docker exec -d akurat_app php artisan pulse:work

echo ""
echo "✅ [8/8] AKURAT BERHASIL DEPLOY!"
echo "   Waktu  : $(date '+%Y-%m-%d %H:%M:%S WIB')"
echo "   Branch : $(git rev-parse --abbrev-ref HEAD)"
echo "   Commit : $(git rev-parse --short HEAD)"