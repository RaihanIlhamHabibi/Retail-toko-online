# Toko Online Abangkuh

Abangkuh adalah aplikasi toko online yang dibangun dengan Laravel dan Aimeos. Aplikasi ini menyediakan katalog produk, pencarian, keranjang dan checkout, akun pelanggan, serta panel administrasi toko.

## Fitur

- Toko tunggal dengan antarmuka Bahasa Indonesia dan Inggris.
- Katalog, kategori, pencarian, keranjang, checkout, dan riwayat pesanan.
- Login pelanggan dan admin melalui portal yang sama; akses panel admin memerlukan akun dengan hak admin.
- Halaman CMS tentang, kontak, pengiriman, privasi, dan syarat ketentuan.
- Integrasi provider Midtrans tersedia, tetapi pembayaran tetap nonaktif sampai kredensial sandbox dan harga produk IDR disiapkan.

Abangkuh menggunakan Aimeos sebagai mesin e-commerce. Data produk, pesanan, akun, dan media yang ada di instalasi lokal tidak disertakan dalam repository ini.

## Persyaratan

- PHP dan ekstensi yang dibutuhkan oleh dependensi Composer.
- Composer 2.
- MySQL atau MariaDB.
- Node.js/npm hanya jika ingin membangun ulang aset frontend.

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
php artisan test
```
