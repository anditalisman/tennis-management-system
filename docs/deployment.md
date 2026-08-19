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
| `MAIL_MAILER` | `log` di dev (tidak benar-benar mengirim email) | Ganti ke `smtp` (mailserver self-hosted di bawah) atau provider lain sebelum rilis agar notifikasi §Sprint 7 benar-benar terkirim |
| `MAIL_PASSWORD` | Placeholder `change-me-mail-secret` | Ganti dengan secret kuat — **harus sama persis** dengan password mailbox yang dibuat lewat `setup email add` (lihat §2a) |
| `WHATSAPP_PROVIDER` | `log` (OTP registrasi dicatat, tidak benar-benar dikirim) | Ganti ke `openwa` setelah `OPENWA_API_KEY`/`OPENWA_SESSION_ID` diisi dan sesi WhatsApp ter-pairing di gateway — lihat §1a |
| `SANCTUM_STATEFUL_DOMAINS`, `SPA_URL` | Diarahkan ke `localhost:3000` | Ganti ke domain frontend production |

### 1a. WhatsApp OTP (verifikasi registrasi)

Registrasi peserta (`/pendaftaran` di situs publik) mengirim kode OTP 6
digit lewat WhatsApp, bukan email — lihat `WhatsappOtpService` dan
`SendNotificationJob`. Login diblokir sampai `whatsapp_verified_at` terisi.
Selama `WHATSAPP_PROVIDER=log` (default), kode OTP hanya tercatat di
`notification_logs`, **tidak benar-benar terkirim** — peserta baru tidak
akan pernah bisa memverifikasi akun. Sebelum mengumumkan pendaftaran
publik, pastikan:

1. Sesi WhatsApp sudah ter-*pairing* (login QR) di gateway OpenWA
   (`OPENWA_BASE_URL`).
2. `OPENWA_API_KEY` dan `OPENWA_SESSION_ID` terisi di Environment
   Variables.
3. `WHATSAPP_PROVIDER=openwa`.
4. Tes kirim manual (daftar satu akun uji, pastikan kode OTP benar-benar
   masuk ke WhatsApp).

Setelah mengganti `.env`, jalankan `php artisan config:cache` di production
image (jangan cache config saat development — env berubah-ubah).

## 2. Alur rilis

`backend/docker/entrypoint.sh` menjalankan `php artisan migrate --force`
otomatis setiap container `app`/`queue`/`scheduler` start (di-guard dengan
`flock` yang sama seperti langkah `composer install`, jadi hanya salah satu
dari ketiganya yang benar-benar menjalankan migrasi per deploy) — tidak
perlu lagi dijalankan manual. Sisa langkah rilis:

```bash
docker compose -f docker-compose.yml pull        # atau build image production
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
interaktif tanpa flag ini — entrypoint sudah memakai flag ini secara
otomatis. Karena migrasi berjalan di dalam entrypoint container baru,
sebelum `exec php-fpm`/`queue:work`/`schedule:work`, migrasi tetap selesai
**sebelum** container itu mulai melayani traffic atau job, konsisten dengan
prinsip zero-downtime di atas.

### 2a. Setup mail server (sekali di awal)

Layanan `mailserver` (docker-mailserver) **menolak untuk start** sampai
minimal satu mailbox ada — tanpa ini container akan mati sendiri ~2 menit
setelah boot ("You need at least one mail account to start Dovecot"). Sekali
saja setelah deploy pertama:

```bash
docker compose exec mailserver setup email add no-reply@zultennis.my.id 'ISI_SAMA_DENGAN_MAIL_PASSWORD'
```

Password ini **harus identik** dengan `MAIL_PASSWORD` di Environment
Variables — docker-mailserver selalu mewajibkan SMTP AUTH begitu klien
(Laravel) menawarkan username, jadi kredensial yang tidak cocok gagal
total, tidak jatuh ke "relay tanpa autentikasi" meski `PERMIT_DOCKER=network`
sudah diaktifkan.

Sebelum mail benar-benar terkirim ke penyedia lain (Gmail, dst.) tanpa
ditolak/dianggap spam, pastikan DNS domain berikut sudah diset mengarah ke
host Dokploy ini:

- `A` record: `smtp.zultennis.my.id` dan `mail.zultennis.my.id`
- `MX` record: domain pengirim → `smtp.zultennis.my.id`
- `SPF`, `DKIM` (docker-mailserver punya `setup config dkim` untuk generate
  key-nya), `DMARC`, dan `PTR` (reverse DNS di sisi provider VPS)

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
