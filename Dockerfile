# Dockerfile untuk deploy SakuSaka (MSBD) ke Railway.
#
# Memakai dua tahap (multi-stage):
#   Tahap 1 (node:20)  -> membangun aset frontend ke public/build
#   Tahap 2 (php:8.3)  -> menyalin hasil build itu, tanpa Node di image akhir
#
# Kenapa begini:
# - Vite 8 butuh Node 20+. nodejs dari apt Debian hanya 18, `vite build` gagal.
# - Menyalin binary node ke image PHP rawan masalah shared library (libnode.so).
#   Tahap terpisah menghindarinya sepenuhnya dan membuat image akhir lebih kecil.
# - npm run build butuh vite.config.js + resources/ + tailwind.config.js +
#   postcss.config.js. Tanpa dua berkas konfigurasi terakhir, `vite build` TETAP
#   berhasil (exit 0) tetapi CSS keluar hanya ~1 kB karena plugin Tailwind tidak
#   terdaftar, dan `@apply` gagal. Gejalanya: /kas dan /denah tampil tanpa gaya.
#   Karena itu seluruh sumber disalin lebih dulu di tahap 1.
# - .npmrc ikut disalin: isinya `ignore-scripts=true` (hanya memengaruhi
#   fsevents, dependensi opsional macOS) dan menjaga perilaku npm sama seperti
#   di mesin pengembang.
# - DocumentRoot diarahkan ke public/, karena Laravel menaruh index.php di sana.
# - storage:link TIDAK dijalankan di sini, melainkan di entrypoint.sh, supaya
#   symlink menunjuk ke volume yang sudah ter-mount saat container berjalan.

# ---------------------------------------------------------------- tahap 1: aset
FROM node:20-bookworm-slim AS aset

WORKDIR /app

# Berkas yang dibutuhkan vite. tailwind.config.js + postcss.config.js WAJIB ada,
# kalau tidak plugin Tailwind tidak aktif dan CSS keluar hampir kosong.
COPY package.json package-lock.json vite.config.js tailwind.config.js postcss.config.js ./
COPY .npmrc ./
COPY resources ./resources
COPY public ./public

RUN npm ci \
    && npm run build \
    && test -f public/build/manifest.json \
    && ls -la public/build/assets

# ---------------------------------------------------------------- tahap 2: php
FROM php:8.3-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
        libonig-dev \
        libxml2-dev \
        libpng-dev \
        libzip-dev \
        unzip \
        git \
    && docker-php-ext-install pdo_mysql mbstring dom fileinfo iconv gd zip \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# DocumentRoot -> public/
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
        /etc/apache2/sites-available/*.conf \
        /etc/apache2/apache2.conf \
        /etc/apache2/conf-available/*.conf

WORKDIR /var/www/html

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Dependensi PHP lebih dulu supaya layer cache tidak rusak tiap ada perubahan kode
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

# Seluruh kode proyek
COPY . .

# Hasil build frontend dari tahap 1 (menimpa public/build kalau ada)
COPY --from=aset /app/public/build ./public/build

RUN composer dump-autoload --optimize \
    && mkdir -p storage/framework/cache storage/framework/sessions \
                storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Railway mengisi PORT lewat environment
ENV PORT=8080
EXPOSE 8080

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
