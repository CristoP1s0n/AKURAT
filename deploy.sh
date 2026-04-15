#!/bin/bash
# Merakit container
docker-compose up -d --build

# Tunggu database siap
echo "Menunggu database siap..."
sleep 10

# Migrasi struktur database terbaru
docker exec akurat_app php artisan migrate --force
docker exec akurat_app php artisan storage:link
docker exec akurat_app php artisan config:cache

echo "🚀 AKURAT BERHASIL DEPLOY KE INTERNET!"