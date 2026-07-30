# Panduan Pengguna ZTCMS

Panduan ini menjelaskan setiap menu dan proses di **Zul Tennis Clinic Management
System** — situs publik dan portal internal. Ditulis untuk pengguna sehari-hari
(admin, pelatih, peserta, wali, keuangan), bukan untuk developer. Untuk
dokumentasi API/teknis, lihat `docs/openapi.yaml` dan `docs/deployment.md`.

## Daftar Isi

1. [Peran Pengguna](#1-peran-pengguna)
2. [Situs Publik](#2-situs-publik)
3. [Masuk & Pendaftaran](#3-masuk--pendaftaran)
4. [Portal — Umum](#4-portal--umum)
5. [Portal — Operasional](#5-portal--operasional)
6. [Portal — Perkembangan & Dokumentasi](#6-portal--perkembangan--dokumentasi)
7. [Portal — Keuangan](#7-portal--keuangan)
8. [Portal — Pengaturan](#8-portal--pengaturan)
9. [Alur Kerja Umum](#9-alur-kerja-umum)
10. [Matriks Akses per Peran](#10-matriks-akses-per-peran)

---

## 1. Peran Pengguna

Sistem membedakan akses berdasarkan **peran** (satu akun bisa punya lebih dari
satu peran):

| Peran | Kode | Deskripsi |
|---|---|---|
| Super Admin | `super-admin` | Akses penuh ke semua menu, satu-satunya yang bisa kelola pengguna & peran. |
| Manajemen | `management` | Melihat data lintas operasional (mirip staf), tanpa hak kelola pengguna. |
| Administrator | `administrator` | Operasional harian: peserta, kelas, jadwal, dsb. |
| Pelatih | `coach` | Akses ke kelas yang diampu, absensi, evaluasi, galeri. |
| Peserta | `participant` | Portal mandiri: lihat jadwal, kelas, tagihan, evaluasi sendiri. |
| Wali/Orang Tua | `guardian` | Sama seperti peserta, tapi untuk anak yang terhubung ke akunnya. |
| Keuangan | `finance` | Tagihan, verifikasi pembayaran, voucher, laporan. |

Istilah **"Staf"** pada panduan ini berarti Super Admin, Manajemen, dan
Administrator (akses operasional penuh).

Catatan: sistem ini beroperasi **satu lokasi** — tidak ada lagi menu/pilihan
"Cabang" di mana pun.

---

## 2. Situs Publik

Situs publik (`/`) bisa diakses siapa saja tanpa login. Navbar utama hanya
menampilkan 4 menu inti; menu lain ada di footer.

**Navbar:** Program · Paket & Biaya · Pelatih · Kontak — plus tombol **Masuk**
dan **Daftar Sekarang**.

**Footer:** Profil · Jadwal · Lapangan · Galeri · Testimoni · FAQ.

| Halaman | Isi |
|---|---|
| Beranda (`/`) | Hero + tombol CTA (Daftar, Lihat Program, Cek Jadwal, WhatsApp), ringkasan jumlah program & level, cuplikan 3 program teratas. |
| Program (`/program`) | Semua program aktif dengan kelompok usia, level, target kompetensi, deskripsi. |
| Paket & Biaya (`/paket`) | Daftar paket per program: harga, jumlah sesi, masa berlaku. |
| Pelatih (`/pelatih`) | Kartu profil pelatih: bio dan sertifikasi (tanpa email/kontak pribadi). |
| Jadwal (`/jadwal`) | Jadwal 14 hari ke depan, dikelompokkan per tanggal. |
| Lapangan (`/lapangan`) | Fasilitas lapangan beserta jenis permukaan dan jam operasional. |
| Galeri (`/galeri`) | Foto/video yang sudah disetujui admin. |
| Testimoni, FAQ, Profil, Kontak | Konten statis tentang klinik. |

---

## 3. Masuk & Pendaftaran

### Masuk (`/login`)
Isi **Email** dan **Kata sandi**, klik **Masuk**. Belum punya akun? Klik
"Daftar sekarang" untuk ke formulir pendaftaran.

### Formulir Pendaftaran (`/pendaftaran`)
Dipakai calon peserta baru mendaftar sendiri (tanpa login). Formnya adaptif —
jumlah langkah berubah tergantung **Kategori Usia** yang dipilih.

**Langkah 1 — Data Peserta:**
- Nama lengkap, Email, Nomor telepon, Jenis kelamin, Level kemampuan
- **Kategori usia**: U10, U12, U14, U16, Dewasa, atau Prestasi

**Jika kategori usia bukan "Dewasa"** (yaitu U10/U12/U14/U16/Prestasi) —
**Langkah 2 — Data Wali** muncul:
- Nama wali, Hubungan (Ayah/Ibu/Wali), Nomor telepon, Email wali, Kata sandi
- Akun wali inilah yang dipakai login untuk memantau anaknya, bukan peserta.

**Jika kategori usia "Dewasa"** — tidak ada langkah Data Wali. Sebagai
gantinya, peserta membuat **kata sandi sendiri** di Langkah 1, dan langsung
punya akun untuk login sendiri.

**Langkah terakhir — Konfirmasi:** centang persetujuan kebijakan privasi, klik
**Daftar Sekarang**. Pendaftaran berstatus "menunggu verifikasi" sampai staf
menyetujuinya (lihat [Peserta](#peserta)).

---

## 4. Portal — Umum

### Dashboard
Isinya berbeda per peran:
- **Staf**: Peserta aktif, Menunggu verifikasi, Jadwal 7 hari ke depan, Galeri
  menunggu moderasi.
- **Pelatih**: Jadwal mengajar 7 hari ke depan.
- **Peserta**: Kelas aktif saya.
- **Wali**: Jumlah anak yang dipantau.
- **Keuangan**: belum ada ringkasan khusus di dashboard (langsung ke menu
  Tagihan/Laporan).

### Notifikasi
Daftar pemberitahuan untuk akun Anda sendiri (misal: hasil verifikasi
pendaftaran, pembayaran diverifikasi). Filter **Semua** / **Belum dibaca**,
tombol **Tandai dibaca** per item.

### Profil Saya
Menampilkan nama, email, telepon, peran, dan waktu login terakhir. Tombol
**Keluar dari Akun** untuk logout.

---

## 5. Portal — Operasional

### Peserta
*(Staf saja)* Daftar semua peserta dengan pencarian nama/nomor registrasi.
Klik **Detail** untuk membuka satu peserta:

- **Data Peserta** — bisa diedit staf.
- **Wali** — daftar wali terhubung.
- **Paket Latihan** — paket aktif dan sisa sesi.
- **Riwayat Evaluasi** — skor per aspek dari pelatih.
- **Kode Referral** — tombol **Buat Kode Baru** untuk membuatkan kode referral
  bagi peserta ini, dibagikan ke calon peserta lain sebagai insentif rujukan.
- Jika status **"menunggu verifikasi"**: tombol **Setujui** / **Tolak**
  muncul untuk staf memproses pendaftaran baru.

### Wali & Anak
*(Staf saja)* Daftar wali dengan pencarian, kolom jumlah anak. Klik **Lihat
Anak** untuk melihat semua peserta yang terhubung ke satu wali (link ke
detail peserta masing-masing).

### Program
Daftar program latihan (Nama, Kelompok usia, Level, Status). Staf bisa
**Tambah Program** / **Edit** / **Hapus**. Form: nama, kelompok usia
(Anak-anak/Remaja/Dewasa), level, target kompetensi, deskripsi.

### Kelas
Daftar kelas dengan jumlah anggota vs kapasitas dan sisa kuota. Detail kelas
berisi:

- **Daftarkan Peserta** *(staf)* — pilih peserta aktif untuk didaftarkan
  langsung ke kelas.
- **Kelas Percobaan (Trial)** *(staf)* — booking sesi coba sebelum peserta
  daftar penuh: pilih peserta + tanggal trial. Setiap trial yang tercatat
  bisa **dikonversi menjadi anggota** dengan tombol **Konversi ke Anggota**
  begitu peserta memutuskan lanjut.
- **Anggota Kelas** — daftar anggota aktif, staf bisa **Keluarkan** anggota.
- **Waiting List** — peserta yang menunggu kalau kelas penuh (otomatis naik
  jika ada slot kosong).
- **Hapus Kelas** *(staf)* — tombol di halaman detail kelas untuk menghapus
  kelas beserta jadwalnya.

### Jadwal
Daftar sesi latihan (tanggal, waktu, tipe, status). Staf bisa **Tambah
Jadwal** (pilih kelas, lapangan, pelatih, tanggal, jam, tipe sesi).

Detail jadwal menampilkan lapangan dan pelatih sesi tersebut, lalu:
- **Peserta**: tombol **Check-in Sekarang** untuk presensi mandiri —
  langsung tercatat **Hadir** saat itu juga, tidak perlu menunggu persetujuan
  pelatih. Pelatih/staf tetap bisa mengoreksi statusnya lewat **Verifikasi
  Absensi** jika ternyata keliru (mis. peserta check-in lalu pulang lebih
  awal).
- **Staf/Pelatih**: kartu **Verifikasi Absensi** — set status tiap anggota
  kelas (Hadir/Terlambat/Tidak Hadir/Izin/Sakit/Pulang Awal) lalu **Simpan
  Absensi** sekaligus. Dipakai juga untuk mencatat kehadiran peserta yang
  tidak check-in mandiri lewat aplikasi.
- **Staf**: kartu **Batalkan Jadwal** — wajib isi alasan pembatalan. (Tidak
  ada fitur reschedule terpisah — kelola lewat batal + buat jadwal baru bila
  perlu.)

### Absensi
*(Staf/Pelatih)* Daftar ringkas sesi hari ini & sebelumnya. Klik **Kelola
Absensi** untuk masuk ke halaman detail jadwal (lihat di atas) tempat
verifikasi absensi sebenarnya dilakukan.

### Lapangan
Daftar fasilitas lapangan (nama, permukaan, biaya sewa, status). Staf bisa
**Tambah** / **Edit** / **Hapus**.

### Inventaris
Daftar barang inventaris (nama, kategori, stok, kondisi). Staf bisa **Tambah
Barang**. Detail barang:
- **Catat Transaksi** — jenis (Masuk/Keluar/Dipinjam/Dikembalikan/
  Rusak/Hilang), jumlah, catatan opsional — otomatis menyesuaikan stok.
- **Riwayat Transaksi** — log semua transaksi barang tersebut.

### Pelatih
Daftar pelatih (nama, email, status kepegawaian). Staf bisa **Tambah
Pelatih** (termasuk membuat akun login pelatih) / **Edit** / **Hapus**.

---

## 6. Portal — Perkembangan & Dokumentasi

### Evaluasi
Formulir penilaian peserta (tidak ada daftar riwayat di sini — riwayat per
peserta ada di halaman detail Peserta). Pelatih memilih kelas → sistem
memuat anggota kelas → pilih peserta → isi tanggal evaluasi, skor tiap aspek
(1–5), dan target latihan berikutnya. Staf yang mengisi harus memilih
pelatih penilai; pelatih yang login langsung menilai atas namanya sendiri.

### Galeri
Grid foto/video kegiatan. **Hanya Pelatih dan Super Admin** yang bisa
**Unggah Galeri** (pilih kelas, judul opsional, upload beberapa file
foto/video sekaligus) — maksimum 10MB per file; foto otomatis dikompresi
server tanpa terlihat menurunkan kualitas. Galeri baru berstatus menunggu
moderasi. Di halaman detail, staf/pelatih dari kelas yang sama bisa
**Tambah Media** lagi, **Publikasikan** (agar tampil di situs publik), atau
**Hapus Galeri**.

### Pengumuman
Daftar pengumuman (judul, tanggal terbit, status Terbit/Draf). **Super Admin
dan Administrator** bisa **Buat Pengumuman** (judul, isi, status
terbit/draf langsung) dan **Hapus**. Pengumuman selalu ditujukan ke semua
pengguna (tidak ada lagi target per cabang).

---

## 7. Portal — Keuangan

### Paket
Daftar paket harga (nama, sesi, harga, status). **Super Admin dan
Administrator** bisa **Tambah Paket** / **Edit** — pilih program, nama
paket, jumlah sesi, masa berlaku (hari), harga, status.

### Tagihan
Daftar tagihan peserta dengan status (belum bayar/sebagian/lunas/jatuh
tempo/batal). Staf/Keuangan bisa **Buat Tagihan** (pilih peserta, jatuh
tempo, item paket atau item bebas, diskon opsional, kode voucher opsional).

Detail tagihan:
- Rincian item, subtotal, diskon, pajak, total, sisa tagihan.
- **Riwayat Pembayaran** — semua pembayaran yang pernah masuk beserta status
  (menunggu/terverifikasi/ditolak) dan bukti transfer bila ada.
- **Kirim Pembayaran** *(peserta/wali/super admin)* — pilih metode
  (Transfer/Tunai/QRIS), jumlah, no. referensi, unggah bukti transfer
  (maksimum 10MB, foto otomatis dikompresi).
- **Verifikasi/Tolak** *(finance/super admin)* — menyetujui atau menolak
  pembayaran yang masuk; disetujui → tagihan otomatis jadi lunas/sebagian.

### Voucher
*(Super Admin, Manajemen, Keuangan)* Daftar kode voucher aktif dengan diskon
dan sisa pemakaian. **Super Admin/Keuangan** bisa **Buat Voucher**: kode,
jenis diskon (persentase/nominal), nilai diskon, batas pemakaian opsional,
periode berlaku opsional. Kode ini dipakai peserta saat mengisi "Kode
voucher" di form Buat Tagihan.

### Laporan
Dua tab:
- **Absensi** — ringkasan status kehadiran + rincian per kelas, filter
  tanggal/program/kelas.
- **Pendapatan** — total pendapatan terverifikasi + rincian per periode
  bulanan, filter tanggal.

Tombol **Export CSV** mengunduh data sesuai tab & filter yang sedang aktif.

---

## 8. Portal — Pengaturan

### Pengguna & Peran
*(Super Admin saja)* Daftar seluruh akun staf/pelatih/dsb dengan peran
masing-masing. **Tambah Pengguna**: nama, email, telepon, password, dan
centang satu atau lebih **Peran**. **Edit**: sama, password opsional
(kosongkan jika tidak ingin diganti), plus ubah status (Aktif/Ditangguhkan/
Nonaktif). **Nonaktifkan** menonaktifkan akun & mencabut sesi login mereka.

### Profil Saya
Lihat [bagian 4](#portal--umum).

---

## 9. Alur Kerja Umum

**Mendaftarkan peserta baru (anak-anak):**
1. Calon wali mengisi `/pendaftaran`, pilih kategori usia U10–U16 (atau
   Prestasi), isi Data Wali.
2. Staf membuka menu **Peserta**, cari peserta berstatus "menunggu
   verifikasi", buka detail, klik **Setujui**.
3. Peserta otomatis dapat notifikasi hasil verifikasi.

**Menagih dan menerima pembayaran:**
1. Staf/Keuangan buka **Tagihan** → **Buat Tagihan**, pilih peserta & paket.
2. Peserta/wali buka tagihan tersebut, isi **Kirim Pembayaran** + unggah
   bukti.
3. Keuangan buka detail tagihan yang sama, klik **Verifikasi** pada
   pembayaran → tagihan otomatis lunas.

**Booking dan konversi trial class:**
1. Staf buka detail **Kelas** yang dituju, isi form **Kelas Percobaan**
   dengan peserta & tanggal trial.
2. Setelah sesi trial berjalan dan peserta setuju lanjut, staf klik
   **Konversi ke Anggota** pada baris trial tersebut di kelas yang sama.

**Mencatat kehadiran sesi latihan:**
1. Peserta klik **Check-in Sekarang** di halaman detail jadwal saat sesi
   berlangsung — langsung tercatat Hadir, tidak ada langkah persetujuan
   pelatih lagi.
2. Untuk peserta yang tidak check-in mandiri (atau bila status perlu
   dikoreksi), pelatih/staf buka jadwal yang sama, isi status kehadiran di
   **Verifikasi Absensi**, klik **Simpan Absensi**.

---

## 10. Matriks Akses per Peran

`✓` = bisa akses menu (detail hak kelola vs. lihat-saja mengikuti penjelasan
di bagian masing-masing di atas).

| Menu | Super Admin | Manajemen | Admin | Pelatih | Peserta | Wali | Keuangan |
|---|:-:|:-:|:-:|:-:|:-:|:-:|:-:|
| Dashboard, Notifikasi, Profil Saya | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Peserta, Wali & Anak | ✓ | ✓ | ✓ | | | | |
| Program, Kelas, Jadwal, Pelatih | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | |
| Absensi, Lapangan, Inventaris | ✓ | ✓ | ✓ | ✓ | | | |
| Evaluasi, Galeri | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | |
| Pengumuman | ✓ | ✓ | ✓ | | ✓ | ✓ | |
| Paket, Tagihan | ✓ | ✓ | ✓ | | ✓ | ✓ | ✓ |
| Voucher | ✓ | ✓ | | | | | ✓ |
| Laporan | ✓ | ✓ | ✓ | ✓ | | | ✓ |
| Pengguna & Peran | ✓ | | | | | | |

Catatan: beberapa menu tampil di navigasi tapi tombol **kelola** (tambah/
edit/hapus) dibatasi lebih sempit dari daftar di atas — misalnya di menu
Paket dan Pengumuman hanya Super Admin & Administrator (bukan Manajemen) yang
punya tombol kelola, dan di menu Galeri hanya Pelatih & Super Admin yang bisa
mengunggah. Ini disebutkan di bagian masing-masing menu.
