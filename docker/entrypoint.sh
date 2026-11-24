#!/bin/sh
set -e

echo "== Menunggu MySQL siap =="
# Railway menyalakan container aplikasi dan MySQL hampir bersamaan, jadi tanpa
# penantian ini migration bisa gagal "Connection refused".
until php -r '
  try {
    new PDO(
      "mysql:host=".getenv("DB_HOST").";port=".getenv("DB_PORT").";dbname=".getenv("DB_DATABASE"),
      getenv("DB_USERNAME"),
      getenv("DB_PASSWORD")
    );
    exit(0);
  } catch (Throwable $e) {
    exit(1);
  }
'; do
  echo "   MySQL belum siap, coba lagi 3 detik..."
  sleep 3
done

# Pastikan folder runtime ada. Kalau volume di-mount ke storage/app/public,
# folder itu bisa menutupi isi image, jadi dibuat ulang di sini.
mkdir -p storage/framework/cache storage/framework/sessions \
         storage/framework/views storage/logs bootstrap/cache \
         storage/app/public
chown -R www-data:www-data storage bootstrap/cache

echo "== Menjalankan migration =="
php artisan migrate --force

echo "== Membuat symlink storage =="
# HARUS di sini, bukan di Dockerfile: symlink harus menunjuk ke volume yang
# baru ter-mount saat container berjalan.
# Hapus dulu: symlink sisa build bisa menunjuk path lama sehingga
# `storage:link` menolak ("The [public/storage] link already exists").
rm -f public/storage
php artisan storage:link || true

echo "== Cache konfigurasi, route, view =="
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Seed otomatis HANYA kalau tabel users masih kosong. Dengan begitu:
# - deploy pertama langsung punya akun demo,
# - redeploy tidak menghapus/menggandakan data,
# - tidak perlu menjalankan perintah manual di terminal.
echo "== Memeriksa data awal =="
JUMLAH_USER=$(php artisan tinker --execute="echo \App\Models\User::count();" 2>/dev/null | tr -d '[:space:]')

if [ "$JUMLAH_USER" = "0" ]; then
  echo "   Tabel users kosong -> menjalankan seeder"
  php artisan db:seed --force
else
  echo "   Sudah ada $JUMLAH_USER user -> seeder dilewati"
fi

echo "== Menyesuaikan port Apache =="
sed -ri -e "s/^Listen 80$/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri -e "s/:80>/:${PORT}>/" /etc/apache2/sites-available/*.conf

echo "== DIAG MODS-ENABLED =="
ls -1 /etc/apache2/mods-enabled/ | grep -i mpm || echo "  (tidak ada berkas mpm di mods-enabled)"
echo "== DIAG LoadModule mpm =="
grep -rn "LoadModule.*mpm" /etc/apache2/ 2>/dev/null || echo "  (tidak ada LoadModule mpm)"
echo "== DIAG apache2ctl -M =="
apache2ctl -M 2>&1 | grep -i mpm || echo "  (apache2ctl gagal / tidak ada mpm)"
echo "== DIAG selesai =="

echo "== Apache mulai di port ${PORT} =="
exec apache2-foreground
