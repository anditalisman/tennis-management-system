# Deployment & Restore

Panduan operasional untuk menjalankan ZTCMS di luar mesin development —
checklist sebelum rilis, prosedur backup/restore, dan operasi harian untuk
queue/scheduler. Untuk setup lokal cepat, lihat `README.md`.

## 1. Checklist sebelum production

Semua konfigurasi Laravel (`APP_*`, `DB_*`, `MAIL_*`, dst.) di-forward ke
container `app`/`queue`/`scheduler` lewat `environment:` di
`docker-compose.yml`, bersumber dari root `.env` — di Dokploy, isi tab
**Environment Variables** aplikasi (bukan file di server). `backend/.env`
tidak dipakai untuk deploy. Nilai default di `.env.example` ditujukan untuk
development lokal dan **wajib diganti** sebelum rilis:

| Variabel | Risiko jika dibiarkan | Tindakan |
|---|---|---|
| `APP_DEBUG` | `true` membocorkan stack trace, query, dan path server pada setiap error 500 ke klien | Set `false` di production |
| `APP_KEY` | Kunci enkripsi sesi/cookie; tanpa ini Laravel gagal start (fail-fast) | Generate: `docker compose exec app php artisan key:generate --show`, tempel hasilnya ke `APP_KEY=` di root `.env` (atau Dokploy Environment Variables) — **bukan** `--force`, karena APP_KEY datang dari `environment:` container yang menang atas isi file `.env` di dalamnya |
| `DB_PASSWORD`, `MYSQL_ROOT_PASSWORD` | Placeholder `change-me-...` | Ganti dengan secret kuat, simpan di secret manager — jangan commit |
| `MINIO_ROOT_USER` / `MINIO_ROOT_PASSWORD` (atau kredensial S3 nyata) | Placeholder | Ganti; pertimbangkan S3 terkelola alih-alih MinIO self-hosted untuk production |
| `PAYMENT_GATEWAY_WEBHOOK_SECRET` | Placeholder `change-me-webhook-secret` | Ganti dengan secret yang diberikan provider payment gateway sesungguhnya saat integrasi Sprint 9 lanjutan dipakai untuk provider nyata |
| `MAIL_MAILER` | `log` di dev (tidak benar-benar mengirim email) | Ganti ke `smtp`/`ses`/dsb. sebelum rilis agar notifikasi §Sprint 7 benar-benar terkirim |
| `SANCTUM_STATEFUL_DOMAINS`, `SPA_URL` | Diarahkan ke `localhost:3000` | Ganti ke domain frontend production |

Setelah mengganti `.env`, jalankan `php artisan config:cache` di production
image (jangan cache config saat development — env berubah-ubah).

## 2. Alur rilis

```bash
docker compose -f docker-compose.yml pull        # atau build image production
docker compose exec app php artisan migrate --force
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose restart queue scheduler
```

`DatabaseSeeder` sengaja **tidak** membuat akun demo saat `APP_ENV=production`
(lihat `DatabaseSeeder::run()`) — database production yang baru bermigrasi
tidak punya user sama sekali. Buat akun staf pertama dengan:

```bash
docker compose exec app php artisan app:create-admin \
  --name="Nama Anda" --email="admin@domain.anda" --password="..." --role=super-admin
```

Aman dijalankan ulang (upsert berdasarkan email) — termasuk kalau database
ter-reset di deploy berikutnya.

`migrate --force` diperlukan karena `APP_ENV=production` menolak migrasi
interaktif tanpa flag ini. Jalankan migrasi **sebelum** menukar traffic ke
container baru pada deployment zero-downtime.

## 3. Backup

### Database (MySQL)

```bash
docker compose exec mysql sh -c 'exec mysqldump -uroot -p"$MYSQL_ROOT_PASSWORD" ztcms' \
  | gzip > backup-ztcms-$(date +%Y%m%d-%H%M%S).sql.gz
```

Jadwalkan ini via cron host (bukan di dalam container `scheduler`, yang
tugasnya menjalankan `artisan schedule:run` untuk job aplikasi, bukan backup
infrastruktur) — mis. cron harian di server host yang menjalankan Docker.

### Object storage (galeri, bukti bayar)

MinIO menyimpan data di volume `minio_data`. Untuk backup point-in-time:

```bash
docker compose exec minio mc mirror /data /backup-mount/minio-$(date +%Y%m%d)
```

Atau, jika memakai S3 terkelola di production, gunakan versioning + lifecycle
policy bucket S3 alih-alih backup manual.

## 4. Restore

```bash
# Database
gunzip < backup-ztcms-20260101-000000.sql.gz | \
  docker compose exec -T mysql sh -c 'exec mysql -uroot -p"$MYSQL_ROOT_PASSWORD" ztcms'

# Jalankan migrasi untuk memastikan skema sinkron dengan versi kode saat ini
docker compose exec app php artisan migrate --force
```

**Sebelum restore ke database yang sedang dipakai**, hentikan `queue` dan
`scheduler` (`docker compose stop queue scheduler`) agar tidak ada job yang
menulis ke data yang sedang ditimpa. Verifikasi hasil restore dengan
`php artisan tinker` (hitung baris tabel kunci: `users`, `participants`,
`invoices`) sebelum menyalakan kembali traffic.

## 5. Queue & scheduler (operasional harian)

- **`queue`**: menjalankan `SendNotificationJob` (retry 3x dengan backoff
  10/30/60 detik — lihat `app/Jobs/SendNotificationJob.php`). Jika container
  ini down, notifikasi menumpuk di Redis tapi tidak hilang — restart aman.
- **`scheduler`**: menjalankan `php artisan schedule:run` setiap menit.
  Saat ini belum ada scheduled command terdaftar di `routes/console.php`
  selain default Laravel — akan bertambah seiring kebutuhan (mis. tandai
  invoice `overdue` otomatis, saat ini masih manual/belum diimplementasikan).
- Pantau kegagalan job via tabel `failed_jobs` (default Laravel) dan
  `notification_logs` (status `failed` tercatat dengan pesan error di
  `provider_response`).

## 6. Observability minimum sebelum rilis

Belum ada APM/error-tracking terpasang (mis. Sentry). Sebelum rilis
production, sambungkan minimal salah satu:
- Log terstruktur `storage/logs/laravel.log` diteruskan ke agregator (mis.
  Loki/CloudWatch) — jangan andalkan `docker compose logs` sebagai satu-satunya
  akses log di production.
- Error tracking (Sentry/Bugsnag) agar kegagalan job queue dan exception 500
  tidak hanya diketahui lewat laporan pengguna.

## 7. Known limitations (jujur, bukan menyembunyikan)

- **WhatsApp/Telegram**: `SendNotificationJob` mengirim channel selain email
  sebagai stub (dicatat ke `notification_logs`, tidak benar-benar terkirim).
  Sambungkan provider BSP/bot token sebelum mengandalkan channel ini.
- **Payment gateway**: webhook memverifikasi signature HMAC generik, belum
  terhubung ke provider spesifik (Midtrans/Xendit). Sesuaikan kontrak payload
  di `PaymentGatewayWebhookController` saat integrasi nyata dimulai.
- **Ekspor laporan**: hanya `format=csv` yang berfungsi
  (`GET /reports/{type}/export`). `xlsx`/`pdf` mengembalikan 422 eksplisit —
  perlu menambah dependency (`maatwebsite/excel`, `barryvdh/laravel-dompdf`).
- **Load testing**: belum dilakukan secara formal (di luar cakupan yang bisa
  diverifikasi di lingkungan development ini). Sebelum peluncuran skala
  besar, jalankan uji beban pada endpoint dengan row-locking (enrollment
  kelas, jadwal, transaksi inventaris) untuk memastikan lock contention tidak
  jadi bottleneck di volume production.
