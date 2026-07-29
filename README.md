# Zul Tennis Clinic Management System (ZTCMS)

Train Better, Play Stronger.

Platform terpadu untuk mengelola pendaftaran peserta, program latihan, jadwal,
absensi, pembayaran, pelatih, lapangan, dan operasional Zul Tennis Clinic.

Rancangan lengkap (aktor, ERD, arsitektur, API, backlog, rencana sprint) ada di
dokumen Tahap 1 yang dibagikan terpisah. Repo ini adalah implementasinya,
dibangun bertahap mengikuti rencana sprint tersebut.

## Status implementasi

**Backend API: Sprint 0–10 selesai** (seluruh rencana sprint backend di
dokumen Tahap 1 §13 — MVP/Tahap 2, Keuangan &amp; Evaluasi/Tahap 3, dan
Multi-cabang/Inventaris/Growth/Payment Gateway/Pengerasan/Tahap 4). Lihat
`docs/openapi.yaml` untuk daftar modul &amp; endpoint, `docs/deployment.md`
untuk checklist rilis dan prosedur backup/restore.

**Frontend: implementasi utama selesai.** Situs publik (beranda, program,
paket, pelatih, jadwal, lapangan, galeri, testimoni, FAQ, kontak) dan portal
(admin/staf, pelatih, peserta/wali, keuangan) sudah tersambung ke API
sungguhan dengan autentikasi Sanctum, role-based access, dan CRUD penuh untuk
seluruh modul Sprint 0–10 — termasuk detail/pembayaran tagihan, voucher,
laporan (absensi & pendapatan + ekspor CSV), manajemen cabang, manajemen
pengguna & peran, notifikasi in-app, kode referral, dan trial class.

Keterbatasan yang diketahui (lihat `docs/deployment.md` §7 untuk detail):
WhatsApp/Telegram notification masih stub (email sungguhan sudah berfungsi),
payment gateway webhook memakai kontrak generik belum tersambung provider
nyata, ekspor laporan hanya format CSV, load testing formal belum dilakukan,
dan peserta/wali belum punya jalur navigasi self-service ke halaman profil
peserta mereka sendiri (staf mengakses via portal admin).

## Stack

- **Backend**: Laravel 13 (PHP 8.4), REST API di `/api/v1`, autentikasi Laravel Sanctum (Bearer token).
- **Frontend**: Next.js 16 (App Router) + TypeScript + Tailwind CSS v4 — situs publik (SSR) dan portal (route group terpisah).
- **Database**: MySQL 8.
- **Cache & Queue**: Redis 7.
- **Object storage**: MinIO (S3-compatible).
- **Reverse proxy**: Nginx.
- **Orkestrasi lokal**: Docker Compose.

## Struktur direktori

```
backend/    Laravel API
frontend/   Next.js app (publik + portal)
infra/      Konfigurasi Nginx
docs/       OpenAPI skeleton (docs/openapi.yaml)
```

## Menjalankan secara lokal

1. Salin file environment:

   ```bash
   cp .env.example .env
   cp backend/.env.example backend/.env
   cp frontend/.env.example frontend/.env.local
   ```

   Pastikan nilai `DB_*` di root `.env` sama persis dengan `backend/.env` —
   keduanya dibaca oleh service yang berbeda (MySQL container vs Laravel).

2. Bila port default (`8090` untuk API, `3000` untuk frontend, `9010`/`9011`
   untuk MinIO, `3307` untuk MySQL) bentrok dengan service lain di mesin Anda,
   ubah `APP_PORT` / `FRONTEND_PORT` / `MINIO_API_PORT` / `MINIO_CONSOLE_PORT`
   di root `.env` sebelum menjalankan compose.

3. Build dan jalankan seluruh stack:

   ```bash
   docker compose up -d --build
   ```

4. Siapkan database (migration + seeder — termasuk 7 akun demo, lihat
   `database/seeders/DemoUserSeeder.php`, password semuanya `password`):

   ```bash
   docker compose exec app php artisan migrate --force
   docker compose exec app php artisan db:seed --force
   ```

5. Akses:

   - Situs publik & portal: http://localhost:3000
   - API: http://localhost:8090/api/v1 (health check di `/up` [Laravel] dan `/health` [nginx])
   - MinIO console: http://localhost:9011

## Menjalankan test & lint

```bash
docker compose exec app vendor/bin/pint --test   # lint backend
docker compose exec app php artisan test         # test backend (sqlite in-memory)
docker compose exec app composer audit           # audit dependency PHP

cd frontend
npm run lint
npm run build
```

## CI

`.github/workflows/ci.yml` menjalankan lint + audit + migration (terhadap MySQL
sungguhan) + test untuk backend, dan lint + audit + build untuk frontend, pada
setiap push/PR ke `main`.

## Dokumentasi API

Skema OpenAPI lengkap ada di `docs/openapi.yaml` (25+ modul, dari Autentikasi
sampai Payment Gateway webhook). Endpoint Sprint 0–1 (Auth, Branches, Users)
didokumentasikan detail penuh; endpoint Sprint 2 ke atas didokumentasikan
ringkas (path + ringkasan otorisasi) — lihat controller & test terkait di
`backend/app/Http/Controllers/Api/V1/` dan `backend/tests/Feature/` untuk
detail request/response.

## Deployment & restore

Lihat `docs/deployment.md` untuk checklist sebelum production (secret yang
wajib diganti), alur rilis, prosedur backup/restore database &amp; object
storage, serta operasi harian queue/scheduler.
