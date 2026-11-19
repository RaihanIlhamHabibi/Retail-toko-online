# Toko Online Abangkuh

Abangkuh adalah aplikasi toko online yang dibangun dengan Laravel dan Aimeos. Aplikasi ini menyediakan katalog produk, pencarian, keranjang dan checkout, akun pelanggan, serta panel administrasi toko.

## Fitur

- Toko tunggal dengan antarmuka Bahasa Indonesia dan Inggris.
- Katalog, kategori, pencarian, keranjang, checkout, dan riwayat pesanan.
- Login pelanggan dan admin melalui portal yang sama; akses panel admin memerlukan akun dengan hak admin.
- Halaman CMS tentang, kontak, pengiriman, privasi, dan syarat ketentuan.
- Integrasi provider Midtrans tersedia, tetapi pembayaran tetap nonaktif sampai kredensial sandbox dan harga produk IDR disiapkan.
- API order/inventory untuk tes engineering, dengan JWT, reservasi stok transaksional, role admin, dan audit log.
- Frontend latihan Next.js 15.5.29 di `storefront/`, terpisah dari storefront Aimeos yang sudah ada.

Abangkuh menggunakan Aimeos sebagai mesin e-commerce. Data produk, pesanan, akun, dan media yang ada di instalasi lokal tidak disertakan dalam repository ini.

## Persyaratan

- PHP dan ekstensi yang dibutuhkan oleh dependensi Composer.
- PHP 8.3 atau lebih baru untuk Laravel 13; aktifkan ekstensi PHP yang diminta Composer (termasuk `zip` untuk Aimeos).
- Composer 2.
- MySQL atau MariaDB.
- Node.js 22/npm untuk frontend Next.js.

## Menjalankan di Windows/Laragon

Clone repository, lalu pasang dependensi:

```powershell
git clone https://github.com/RaihanIlhamHabibi/Retail-toko-online.git
Set-Location .\Retail-toko-online
composer install
Copy-Item .env.example .env
php artisan key:generate
```

Edit `.env` dan sesuaikan `APP_URL`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, serta `DB_PASSWORD` dengan konfigurasi Laragon Anda. Jangan pernah mengunggah file `.env`.

### Database baru

Langkah ini hanya untuk instalasi baru dengan database kosong. Buat database baru (misalnya `abangkuh_shop_dev`) di MySQL, lalu atur `DB_DATABASE` di `.env` ke nama tersebut. Setelah itu jalankan:

```powershell
php artisan migrate
php artisan aimeos:setup
php artisan db:seed
php artisan aimeos:account
```

Ikuti instruksi `aimeos:account` untuk membuat akun admin. Perintah setup dan migrasi ditujukan untuk database kosong; jangan menjalankannya sembarangan terhadap database toko yang sudah berisi data. Jangan gunakan `migrate:fresh` pada database yang datanya ingin dipertahankan.

### Database toko yang sudah ada

Jika Anda sudah memiliki database Abangkuh, atur kredensial `.env` agar menunjuk ke database tersebut. Jangan membuat ulang database atau menjalankan `migrate:fresh`. Buat backup sebelum menjalankan perubahan skema atau seeder.

## Menjalankan aplikasi

Jalankan server lokal:

```powershell
php artisan serve --host=127.0.0.1 --port=8010
```

- Toko: <http://127.0.0.1:8010/id>
- Login: <http://127.0.0.1:8010/id/login>
- Registrasi: <http://127.0.0.1:8010/id/register>
- Panel admin: <http://127.0.0.1:8010/admin>

Gunakan akun admin yang dibuat untuk instalasi Anda sendiri. Repository ini tidak menyediakan kredensial admin bawaan.

## Konfigurasi pembayaran dan halaman toko

Midtrans belum aktif secara default. Untuk mengaktifkannya, isi `MIDTRANS_SERVER_KEY` dan `MIDTRANS_CLIENT_KEY` di `.env`, gunakan kredensial sandbox saat pengujian, pastikan produk memiliki harga IDR, lalu aktifkan layanan Midtrans dari panel admin. Jangan memakai mode produksi sebelum konfigurasi dan alur pembayaran diuji.

Halaman privasi, syarat dan ketentuan, kontak, serta pengiriman perlu ditinjau dan dilengkapi dengan informasi resmi Abangkuh sebelum toko dipublikasikan.

## Pengujian

```powershell
php artisan test tests/Feature/CommerceApiTest.php
npm --prefix storefront run build
```

`php artisan test` menjalankan seluruh suite yang ada. Suite lama `MultiRouteTest` pada branch ini memiliki deklarasi `createApplication()` dengan akses `private`, yang dapat menggagalkan discovery PHPUnit; pengujian fitur API di atas sengaja menunjuk file tes baru secara langsung.

## API order challenge

Fitur API latihan ini menggunakan tabel `products`, `orders`, `order_items`, dan `inventory_logs`; tabel pesanan Aimeos yang sudah ada tidak diubah. Migrasi role hanya menambah `api_role` dengan default `user`. Jangan menjalankan `migrate:fresh` pada database toko berisi data.

Untuk konfigurasi lokal, isi `JWT_SECRET` dengan nilai acak minimal 32 karakter (misalnya hasil `php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"`) dan atur `CORS_ALLOWED_ORIGINS`. Jangan menyimpan secret di source control. Setelah database siap:

```powershell
php artisan migrate
php artisan db:seed --class="Database\Seeders\CommerceDemoSeeder"
php artisan serve --host=127.0.0.1 --port=8010
```

Jalankan Next.js di terminal lain:

```powershell
Copy-Item storefront\.env.example storefront\.env.local
npm --prefix storefront install
npm --prefix storefront run dev
```

Halaman latihan tersedia di <http://localhost:3000>. API berada di port 8010 secara lokal; sesuaikan `storefront/.env.local` bila port API berbeda. JWT disimpan di memori frontend dan berakhir setelah 60 menit; refresh halaman mengharuskan login ulang. Untuk produksi, gunakan BFF dengan cookie `HttpOnly`, `Secure`, `SameSite` dan strategi refresh/revocation yang sesuai.

Untuk memberi role API admin kepada akun yang memang Anda kelola, jalankan perintah berikut dan ganti alamat email:

```powershell
php artisan tinker --execute='$user = \App\Models\User::where("email", "you@example.com")->firstOrFail(); $user->forceFill(["api_role" => "admin"])->save();'
```

Tidak ada akun admin bawaan. API pembayaran hanya mensimulasikan perpindahan state order; integrasikan dan verifikasi callback provider pembayaran sebelum menerima pembayaran sungguhan.

### Endpoint

| Method | Path | Akses |
|---|---|---|
| POST | `/api/auth/register`, `/api/auth/login` | Publik, 5 request/menit per email + IP |
| POST | `/api/auth/logout` | JWT |
| GET | `/api/products`, `/api/products/{id}` | Publik; search, category, pagination |
| POST | `/api/orders` | JWT user; body `{"items":[{"product_id":1,"quantity":2}]}` |
| GET | `/api/orders`, `/api/orders/{id}` | JWT; order hanya terlihat oleh pemilik/admin |
| POST | `/api/orders/{id}/payment`, `/api/orders/{id}/cancel` | JWT pemilik/admin |
| PATCH | `/api/orders/{id}/status` | JWT admin; state machine |
| POST | `/api/inventory/restock` | JWT admin |
| GET | `/api/reports/low-stock`, `/api/reports/daily-sales?date=YYYY-MM-DD` | JWT admin |

Setiap pemesanan mengurangi stok tersedia dan mencatat reservasi di satu transaksi. Pembayaran mengonfirmasi reservasi tanpa pengurangan kedua. Pembatalan sebelum pengiriman mengembalikan stok; seluruh perubahan inventori memiliki audit log. Produk dikunci dalam urutan ID stabil untuk mengurangi deadlock. Untuk uji konkurensi nyata, gunakan MySQL; SQLite yang dipakai tes fitur tidak menegakkan row lock dengan cara yang sama.

## Docker Compose

Compose menyediakan Laravel/PHP 8.3, MySQL, Redis berpassword tanpa port publik, MinIO dengan kredensial environment, dan storefront Next.js. Sebelum menjalankan, siapkan `.env` dengan secret unik untuk `JWT_SECRET`, `REDIS_PASSWORD`, `DB_PASSWORD`, `DB_ROOT_PASSWORD`, `MINIO_ROOT_USER`, dan `MINIO_ROOT_PASSWORD`; Compose menolak nilai yang kosong. Periksa dan backup volume database/MinIO yang sudah ada sebelum mengganti konfigurasi.

```powershell
Copy-Item .env.example .env
# Isi semua secret/konfigurasi yang diwajibkan di .env, lalu:
docker compose up --build
docker compose exec laravel.test php artisan migrate --force
docker compose exec laravel.test php artisan db:seed --class="Database\Seeders\CommerceDemoSeeder"
```

Frontend: <http://localhost:3000>; API Laravel: <http://localhost:8000/api>. Compose tidak mem-publish port Redis; batasi juga akses host MySQL/MinIO ke loopback.

## Jawaban tes

Lihat [TECHNICAL_ANSWERS.md](./TECHNICAL_ANSWERS.md) untuk penjelasan desain, keamanan, refleksi, serta jawaban pengalaman pribadi yang konsisten dengan implementasi. Bagian pengalaman insiden hanya boleh dilaporkan berdasarkan kejadian yang benar-benar Anda alami.
