# 🦷 DENTARA - Sistem Informasi Manajemen Klinik Gigi Berbasis Web

> **DENTARA (Dental Care Management System)** adalah sistem informasi manajemen klinik gigi modern, lengkap, dan terintegrasi dari pendaftaran online hingga pembayaran kasir, berbasis **PHP 8.x Native**, **MySQL/MariaDB**, dan **Progressive Web App (PWA)** dengan nuansa visual bertema klinik gigi modern (Mint Teal & Sky Blue).

---

## 🌟 Fitur Utama & Keunggulan Arsitektur

1. **Role-Based Access Control (RBAC) 6 Level:**
   - **Admin / Owner:** Dashboard eksekutif analitik omset, fee sharing dokter, SLA analytics waktu tunggu, manajemen staff, jam operasional 2 sesi & libur mendadak, master layanan & tarif, pengaturan pembayaran & profil klinik, audit trail.
   - **Dokter Gigi:** Panel antrean ruang periksa, Rekam Medis Elektronik (RME) komprehensif (SOAP, Odontogram visual interaktif FDI 11-48 & 51-85, ICD-10 dental autocomplete, e-resep, rencana perawatan / treatment plan, auto-billing generator, cetak berkas RM resmi).
   - **Resepsionis:** Dashboard antrean real-time, scanner kamera jsQR & verifikasi kode booking manual, konfirmasi reservasi online, pendaftaran pasien walk-in, manajemen prioritas antrean (Lansia/Darurat), master data pasien & No RM otomatis.
   - **Apoteker / Farmasi:** Antrean e-resep real-time dengan alert suara, pemotongan stok otomatis saat resep selesai, detail resep read-only & fitur kembalikan ke dokter dengan alasan wajib, CRUD stok obat & dental supply/BMHP, stock opname fisik vs sistem, dashboard peringatan stok kritis & kadaluarsa.
   - **Kasir:** Dashboard tagihan pasien terintegrasi dengan tindakan dokter & resep farmasi, split-bill (klaim asuransi & copay pasien), kalkulator uang diterima & kembalian tunai cepat, pembayaran non-tunai (QRIS & Bank Transfer), cetak kuitansi dual layout (Standar A4 & 80mm POS Thermal) + tombol kirim via WhatsApp, manajemen buka/tutup shift kasir & rekap selisih kas.
   - **Pasien & Keluarga:** Portal mandiri pasien PWA, pendaftaran akun & login terpisah, multi-profil keluarga (sub-patient anak/orang tua/pasangan) dalam satu akun, janji temu online, tracker live antrean, riwayat rekam medis & odontogram personal, riwayat tagihan, gamifikasi "Smiling Guardians" gacha kartu reward untuk pasien anak.

2. **Layar TV Ruang Tunggu (`/tv/monitor.php`):**
   - Tampilan antrean fullscreen responsif untuk TV ruang tunggu.
   - Suara panggilan otomatis berbahasa Indonesia memakai **Web Speech API (`speechSynthesis`)** dipadukan dengan nada ding-dong Web Audio synthesizer.
   - Banner promo berjalan, jam digital real-time, dan status setiap poli dokter.

3. **Keamanan Standar Industri (Anti-SQLi & XSS):**
   - 100% PHP PDO dengan prepared statements (`PDO::ATTR_EMULATE_PREPARES => false`).
   - Password terenkripsi menggunakan algoritma `password_hash()` (Bcrypt).
   - CSRF Token aktif pada setiap formulir POST.
   - Sanitasi output menggunakan `htmlspecialchars()` melalui fungsi helper `e()`.
   - Regenerasi ID session saat otentikasi login untuk mencegah session fixation.
   - Audit trail lengkap di tabel `log_aktivitas`.

4. **Pengalaman Pengguna Modern (SPA & PWA):**
   - Transisi halaman mulus ala SPA via AJAX Fetch (`assets/js/global.js`) yang meng-inject konten ke `#content-area` dengan progress bar tipis dan history pushState.
   - PWA siap pasang dengan `manifest.json` dan `sw.js` (Service Worker cache offline).
   - Seluruh interaksi dialog, toast, dan konfirmasi menggunakan **SweetAlert2 v11** berbahasa ramah.
   - Mode Gelap (Dark Mode) tersinkronisasi otomatis dengan `localStorage`.

---

## 🛠️ Spesifikasi Teknologi & Dependensi

| Komponen | Spesifikasi |
|---|---|
| **Bahasa Pemrograman** | PHP 8.1 / 8.2 / 8.3 Native (Tanpa Framework) |
| **Basis Data** | MySQL 5.7+ / MariaDB 10.4+ (`InnoDB`, `utf8mb4`) |
| **Styling & Desain** | Vanilla CSS3 Modern (CSS Variables / Design Tokens, Flexbox, CSS Grid) |
| **Tipografi** | Google Fonts: *Fredoka* (Heading & Angka Besar), *Nunito* (Body Text) |
| **Ikon** | Boxicons 2.1.4 & FontAwesome 6 Free |
| **Library CDN** | SweetAlert2 v11, Chart.js 4.x, jsQR 1.4.0 (Camera QR Scanner) |
| **Library QR Code** | Standalone PHP `phpqrcode/qrlib.php` (Output PNG via `qr.php`) |
| **Cetak & Invoice** | Print CSS (`@media print`) + `window.print()` (Tanpa dependensi PDF pihak ketiga) |

---

## 🚀 Panduan Instalasi & Menjalankan di Laragon / XAMPP

### Langkah 1: Penempatan Berkas
Salin seluruh folder repositori proyek ke direktori web server lokal Anda:
- **Laragon:** `C:\laragon\www\dentara`
- **XAMPP:** `C:\xampp\htdocs\dentara`

### Langkah 2: Buat Basis Data & Impor Skema
1. Buka aplikasi Laragon / XAMPP dan pastikan modul **Apache** dan **MySQL** telah berjalan (*Start All*).
2. Buka antarmuka manajemen database (HeidiSQL, DBeaver, atau phpMyAdmin di `http://localhost/phpmyadmin`).
3. Buat database baru dengan nama:
   ```sql
   CREATE DATABASE dentara CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
4. Impor berkas SQL yang telah disediakan:
   - Lokasi berkas: `c:\laragon\www\dentara\includes\dentara.sql`
   - Berkas ini mencakup **39 tabel lengkap**, integritas foreign key cascade/set null, indeks, serta **seeder data demo lengkap**.

### Langkah 3: Konfigurasi Koneksi (Jika Dibutuhkan)
Konfigurasi database berada di file `includes/db_connect.php`:
```php
$host = '127.0.0.1';
$dbname = 'dentara';
$username = 'root';
$password = ''; // Kosong untuk standar Laragon / XAMPP
$port = '3306';
```
*Catatan: Konfigurasi default di atas sudah langsung cocok untuk Laragon dan XAMPP.*

### Langkah 4: Buka Aplikasi di Browser
Buka browser Anda dan akses URL berikut:
- **Halaman Utama / Publik:** `http://localhost/dentara/`
- **Login Staff / Karyawan:** `http://localhost/dentara/login_staff.php`
- **Login Portal Pasien:** `http://localhost/dentara/login_pasien.php`
- **Layar TV Antrean:** `http://localhost/dentara/tv/monitor.php`

---

## 🔑 Akun Demo Login (Semua Role)

Semua akun staf di bawah ini menggunakan kata sandi bawaan yang sama:  
👉 **Password:** `password123`

| Role | Username | Nama Staff / Dokter | Keterangan Akses |
|---|---|---|---|
| **Admin** | `admin` | drg. Hendra Wijaya, Sp.Ort | Akses penuh seluruh sistem, tarif, laporan, dan RBAC |
| **Dokter** | `dokter_budi` | drg. Budi Pratama, Sp.KG | Panel ruang periksa, odontogram, diagnosa SOAP & e-resep |
| **Dokter** | `dokter_siti` | drg. Siti Rahmawati | Dokter gigi umum & anak |
| **Dokter** | `dokter_fajar` | drg. Fajar Nugraha, Sp.BM | Spesialis bedah mulut & operasi odontektomi |
| **Resepsionis** | `resepsionis` | Rina Permata | Konfirmasi booking, scan QR check-in, pendaftaran walk-in |
| **Apoteker** | `apoteker` | apt. Dewi Sartika, S.Farm | Peracikan obat e-resep, manajemen stok & stock opname |
| **Kasir** | `kasir` | Doni Kusuma | Pembayaran tagihan, buka/tutup shift kasir, kuitansi A4/thermal |

### Akun Demo Pasien (Portal Pasien & PWA):
👉 **Password:** `password123`

| Email Pasien | Nama Pasien | Profil Sub-Patient Tersedia |
|---|---|---|
| `budi.santoso@gmail.com` | Budi Santoso | Budi Santoso (RM-2026-0001), Kevin Santoso [Anak] (RM-2026-0002) |
| `dewi.lestari@gmail.com` | Dewi Lestari | Dewi Lestari (RM-2026-0003), Anisa [Anak] (RM-2026-0004) |
| `ahmad.fauzi@gmail.com` | Ahmad Fauzi | Ahmad Fauzi (RM-2026-0005) |

---

## 🔄 Alur Uji Coba End-to-End (Workflow Lengkap)

Untuk menguji integritas seluruh modul DENTARA dari hulu ke hilir:

```
[1. Pasien Booking Online] ➡️ [2. Resepsionis Check-In & Cetak Antrean] ➡️ [3. TV Panggil Pasien]
                                                                                ⬇️
[6. Kasir Pembayaran & Cetak Kuitansi] ⬅️ [5. Apoteker Racik E-Resep] ⬅️ [4. Dokter EMR & Odontogram]
       ⬇️
[7. Admin Analitik Keuangan & Fee Sharing Dokter]
```

1. **Reservasi Pasien:**
   - Buka `http://localhost/dentara/booking.php`.
   - Pilih dokter (misal: *drg. Budi Pratama*), pilih tanggal & sesi jam, pilih jenis layanan, dan masukkan keluhan.
   - Sistem akan menerbitkan **Kode Booking Unik** (misal: `DNT482`) beserta kode QR.
2. **Check-In Resepsionis:**
   - Login sebagai resepsionis (`resepsionis` / `password123`).
   - Masuk ke menu **Check-In & Scan QR** (`resepsionis/checkin.php`).
   - Masukkan kode booking atau scan QR code pasien. Klik **Check-In Pasien**. Nomor antrean harian resmi diterbitkan.
3. **Monitor TV Ruang Tunggu:**
   - Buka tab baru di `tv/monitor.php`.
   - Layar TV akan menampilkan nomor antrean terbaru. Saat dokter memanggil pasien, browser akan menyuarakan panggilan audio otomatis dalam Bahasa Indonesia.
4. **Pemeriksaan Dokter (EMR Lengkap):**
   - Login sebagai dokter (`dokter_budi` / `password123`).
   - Buka **Antrean Ruangan** (`dokter/panel_antrian.php`), lalu klik **Panggil** kemudian **Mulai Periksa**.
   - Di formulir pemeriksaan:
     - Perhatikan banner peringatan jika pasien memiliki alergi obat atau penyakit penyerta.
     - Isi catatan **SOAP** (Subjektif, Objektif, Asesmen, Plan).
     - Klik gigi pada **Odontogram Interaktif FDI** untuk mencatat karies, tambalan, atau saluran akar.
     - Pilih kode diagnosis **ICD-10 Gigi** (autocomplete).
     - Pilih tindakan yang dilakukan (tarif tindakan akan otomatis masuk ke auto-billing).
     - Buat resep elektronik obat dan masukkan aturan pakai (signa).
     - Klik **Simpan & Selesaikan Pemeriksaan**.
5. **Farmasi / Apotek:**
   - Login sebagai apoteker (`apoteker` / `password123`).
   - Masuk ke **Antrean Resep** (`apoteker/antrean_resep.php`). Resep baru dari dokter akan langsung muncul.
   - Klik **Mulai Siapkan**, lalu setelah obat dikemas rapi, klik **Selesai (Siap Diambil)**. Stok obat di apotek akan terpotong secara otomatis melalui database transaction.
6. **Kasir & Pelunasan:**
   - Login sebagai kasir (`kasir` / `password123`).
   - Buka sesi kasir di menu **Shift & Kas** dengan memasukkan kas awal modal kembalian.
   - Masuk ke **Tagihan Pasien** (`kasir/dashboard_tagihan.php`), klik **Bayar** pada pasien yang bersangkutan.
   - Sistem merinci biaya konsultasi, biaya tindakan dokter, resep obat, dan biaya administrasi.
   - Masukkan potongan klaim asuransi (jika ada), pilih metode bayar (Tunai, QRIS, Kartu Debit, atau Transfer).
   - Masukkan nominal uang yang diterima, sistem akan menghitung uang kembalian secara otomatis.
   - Klik **Terima Pembayaran**. Sistem menerbitkan nomor kuitansi resmi `KW/YYYYMM/XXXX`.
   - Tampilan kuitansi mendukung dua mode cetak: **Standar A4 Surat Resmi** atau **Struk Kasir 80mm POS Thermal**, serta tautan instan untuk mengirimkan bukti pembayaran via WhatsApp pasien.
7. **Portal Pasien & Gamifikasi:**
   - Login sebagai pasien (`budi.santoso@gmail.com` / `password123`).
   - Pasien melihat riwayat rekam medis, odontogram personal gigi mereka, dan invoice tagihan yang telah lunas.
   - Pada menu **Gamifikasi Smiling Guardians**, pasien anak dapat memainkan mini-game gacha kartu karakter gigi untuk ditambahkan ke album koleksi digital.
8. **Dashboard Analitik & Pemilik Klinik (Owner):**
   - Login sebagai admin (`admin` / `password123`).
   - Buka **Dashboard Analitik** untuk melihat grafik omset harian dan ranking tindakan terlaris via Chart.js.
   - Buka **Fee Sharing Dokter** untuk menghitung bagi hasil jasa medis dokter per bulan dengan satu kali klik dan cetak slip fee sharing.
   - Buka **SLA Waktu Layanan** untuk mengevaluasi rata-rata waktu tunggu pasien dan durasi tindakan dokter.

---

## 📁 Struktur Direktori Berkas

```
dentara/
├── 403.php                          # Tampilan Halaman Akses Ditolak
├── 404.php                          # Tampilan Halaman Halaman Tidak Ditemukan
├── 500.php                          # Tampilan Halaman Kesalahan Server
├── artikel.php                      # Daftar Artikel Edukasi Publik
├── artikel_detail.php               # Baca Detail Artikel Edukasi
├── booking.php                      # Formulir Janji Temu Online
├── booking_sukses.php               # Halaman Sukses Booking + Tiket & QR Code
├── daftar_pasien.php                # Registrasi Akun Pasien Baru
├── dokter.php                       # Profil & Jadwal Dokter Publik
├── faq.php                          # FAQ Publik Interaktif
├── index.php                        # Halaman Beranda Publik (Hero, Keunggulan, Layanan, Testimoni)
├── layanan.php                      # Katalog Tindakan & Estimasi Tarif
├── login_pasien.php                 # Halaman Masuk Pasien
├── login_staff.php                  # Halaman Masuk Petugas & Dokter
├── manifest.json                    # Konfigurasi PWA Web App Manifest
├── offline.html                     # Fallback Halaman Offline PWA
├── qr.php                           # Generator Gambar PNG QR Code
├── styleguide.php                   # Katalog Desain Komponen & Design Tokens
├── sw.js                            # Service Worker Cache PWA
├── tracker.php                      # Pelacak Status Antrean Live Pasien
├── admin/                           # PORTAL ADMIN & OWNER
│   ├── dashboard.php                # Dashboard Utama Analitik Omset & Grafik
│   ├── data_pasien.php              # CRUD Master Pasien & Rekam Medis
│   ├── fee_sharing.php              # Kalkulator & Slip Bagi Hasil Dokter
│   ├── jadwal_dokter.php            # Manajemen Jadwal Mingguan & Cuti Dokter
│   ├── jadwal_klinik.php            # Jam Operasional 2 Sesi & Libur Mendadak
│   ├── kelola_artikel.php           # CMS Artikel Edukasi
│   ├── kelola_dokter.php            # Profil Dokter, STR, dan Persentase Fee
│   ├── kelola_faq.php               # Kelola Pertanyaan & Jawaban
│   ├── kelola_kartu.php             # Master Kartu Gamifikasi & Reward Anak
│   ├── kelola_pengguna.php          # Manajemen Akun Pengguna & RBAC
│   ├── kelola_promo.php             # Kelola Banner & Promo Diskon
│   ├── kirim_notifikasi.php         # Broadcast & Blast Notifikasi Pasien
│   ├── laporan_pendapatan.php       # Laporan Kas & Pembayaran (Filter & CSV)
│   ├── libur_mendadak.php           # Shortcut Kalender Libur
│   ├── log_aktivitas.php            # Audit Trail & Log Keamanan Sistem
│   ├── master_layanan.php           # Master Tindakan Medis & Tarif Layanan
│   ├── pengaturan_klinik.php        # Profil Klinik, Biaya Konsul & Rekening Bank
│   ├── pengaturan_pembayaran.php    # Shortcut Pengaturan Pembayaran
│   ├── sla_analytics.php            # Analitik Waktu Tunggu & Kinerja Layanan
│   └── template_notifikasi.php      # Master Format Pesan / Template Notifikasi
├── api/                             # ENDPOINT API REAL-TIME (AJAX JSON)
│   ├── antrian_status.php           # JSON Antrean Aktif untuk Dashboard TV & Polling
│   ├── antrian_tracker.php          # JSON Status Antrean untuk Pelacak Publik
│   ├── cek_kuota.php                # Cek Sisa Kuota Booking Dokter
│   ├── icd10_search.php             # Autocomplete Kode ICD-10 Gigi
│   ├── notifikasi_count.php         # Jumlah Notifikasi Belum Dibaca
│   ├── obat_search.php              # Pencarian Obat & Sisa Stok
│   ├── odontogram_simpan.php        # Simpan Status Permukaan Gigi ke Database
│   ├── resep_antrian.php            # JSON Antrean Resep Farmasi
│   └── verifikasi_kode.php          # Verifikasi Kode Booking untuk Check-In
├── apoteker/                        # PORTAL APOTEKER & FARMASI
│   ├── alert_stok.php               # Dashboard Peringatan Stok Kritis & Kadaluarsa
│   ├── antrean_resep.php            # Pemrosesan Antrean E-Resep & Potong Stok
│   ├── detail_resep.php             # Detail Resep Read-Only & Kembalikan ke Dokter
│   ├── stock_opname.php             # Pencocokan Stok Fisik vs Sistem & Penyesuaian
│   ├── stok_obat.php                # CRUD Inventaris Obat & Harga Beli/Jual
│   └── stok_supply.php              # CRUD Dental Supply & Bahan Habis Pakai (BMHP)
├── assets/
│   ├── css/                         # ARSITEKTUR GAYA MODULAR
│   │   ├── admin_theme.css          # Tema Dashboard Staff & Sidebar Navigation
│   │   ├── components.css           # Tombol, Form, Badge, Modal, dan Alert
│   │   ├── design_tokens.css        # Variabel Warna, Tipografi, Spasi, & Radius
│   │   ├── gamifikasi.css           # Styling Kartu Koleksi 3D Flip Pasien Anak
│   │   ├── landing.css              # Tata Letak Halaman Publik & Booking
│   │   ├── login.css                # Formulir Otentikasi Pasien & Staff
│   │   ├── modern_ui.css            # Utilitas Layout Modern & Responsif
│   │   ├── odontogram.css           # Styling Gigi FDI 11-48 & 51-85 & Warna Kondisi
│   │   ├── pasien_theme.css         # Tema Portal Pasien
│   │   ├── print.css                # Layout Khusus Cetak Kuitansi, EMR, & Slip Fee
│   │   └── tv.css                   # Tampilan Fullscreen Display Antrean TV
│   ├── img/                         # Asset Ikon & Logo SVG
│   └── js/
│       ├── global.js                # Router SPA Link Interceptor, Toast, & Helper
│       └── odontogram.js            # Interaktivitas Odontogram Visual Dokter
├── dokter/                          # PORTAL DOKTER GIGI (EMR)
│   ├── cetak_rekam.php              # Cetak Berkas Rekam Medis Standar Resmi
│   ├── panel_antrian.php            # Panel Antrean Ruang Periksa & Kontrol Panggil
│   ├── pemeriksaan.php              # EMR Lengkap (SOAP, Odontogram, ICD-10, Tindakan, Resep)
│   └── riwayat_pasien.php           # Arsip Riwayat Rekam Medis Pasien
├── includes/                        # SISTEM DASAR & KONFIGURASI
│   ├── auth.php                     # Logika Session RBAC & Pengalihan Hak Akses
│   ├── db_connect.php               # Koneksi Database Singleton PDO
│   ├── dentara.sql                  # Skema Database (39 Tabel + FK + Seeder Lengkap)
│   ├── helpers.php                  # Helper Format Tanggal, Rupiah, No RM, dan Kuitansi
│   ├── layout_pasien.php            # Wrapper Template Portal Pasien
│   ├── layout_public.php            # Wrapper Template Halaman Publik & Navigasi
│   ├── layout_staff.php             # Wrapper Template Staff & Sidebar Responsif
│   └── process/                     # Handler Proses POST Otentikasi & Booking
├── kasir/                           # PORTAL KASIR & KEUANGAN
│   ├── dashboard_tagihan.php        # Antrean Tagihan Belum Dibayar
│   ├── detail_tagihan.php           # Rincian Tagihan, Split-Bill, & Kalkulator Kasir
│   ├── kuitansi.php                 # Cetak Kuitansi Dual Layout (A4 & 80mm) + WhatsApp
│   └── rekap_shift.php              # Buka/Tutup Shift Kasir & Rekapitulasi Kas
├── pasien/                          # PORTAL PASIEN & KELUARGA
│   ├── dashboard.php                # Beranda Pasien, Tiket Antrean, & Ringkasan EMR
│   ├── gamifikasi.php               # Mini-Game Smiling Guardians & Album Kartu Anak
│   ├── janji_temu.php               # Jadwal Janji Temu & Reschedule Pasien
│   ├── keluarga.php                 # Manajemen Multi-Profil Anggota Keluarga
│   ├── pesan.php                    # Kotak Masuk Pesan Notifikasi & Pengingat Kontrol
│   ├── rekam_medis.php              # Riwayat Rekam Medis & Odontogram Personal
│   ├── setting.php                  # Pengaturan Akun & Ganti Password
│   └── tagihan.php                  # Riwayat & Rincian Invoice Pembayaran
├── phpqrcode/                       # Standalone PHP QR Code Library (qrlib.php)
└── tv/
    └── monitor.php                  # Layar TV Display Antrean Fullscreen + Suara TTS
```

---

## 🎨 Palet Desain & Tipografi (Design Tokens)

DENTARA mengusung identitas visual klinik gigi yang higienis, ramah, dan menenangkan:

- **Primary Teal:** `#2BB5A0` (Warna utama identitas gigi bersih dan sehat)
- **Primary Teal Dark:** `#1E8E7D`
- **Sky Blue:** `#5B9DF5` (Aksen teknologi dan kenyamanan visual)
- **Honey Gold:** `#FFD166` (Warna reward gamifikasi dan status perhatian)
- **Coral Orange:** `#FF6B6B` (Alergi obat dan peringatan kritis)
- **Soft Violet:** `#8B7CF6` (Aksen edukasi dan kartu gacha Epic)
- **Font Heading & Nilai Besar:** `'Fredoka', sans-serif`
- **Font Body Teks:** `'Nunito', sans-serif`

---

## 👥 Tim Pengembang (Development Team)

Proyek ini dikembangkan secara kolaboratif oleh:
- **Marcella Adelia (Cella)** - Backend & MVC Scaffolding
- **Nesia** - Analis Sistem & Dokumentasi Medis
- **Issa** - UI/UX & Flow Pasien
- **Lead Developer & Software Architect** - Arsitektur Native PHP, RME Odontogram, PWA, SLA Analytics & Billing

---

## 🤝 Panduan Kolaborasi Git 2 Pengembang (Mencegah Crash & Conflict)

Untuk memastikan pekerjaan 2 developer berjalan berdampingan tanpa saling menimpa (*non-conflicting workflow*):

1. **Sinkronisasi Kode Sebelum Memulai:**
   ```bash
   git pull origin main
   ```
2. **Aturan Pembagian Berkas & Modul:**
   - **Modul Native PHP & Fitur Lengkap:** Berada pada direktori `admin/`, `dokter/`, `resepsionis/`, `apoteker/`, `kasir/`, `pasien/`, `tv/`, `api/`, dan `includes/`.
   - **Modul MVC & Core Classes:** Berada pada direktori `app/`, `config/`, `database/`, dan `tests/`.
   - Hindari mengubah file yang sedang aktif dikerjakan oleh rekan tim tanpa konfirmasi terlebih dahulu.
3. **Aturan Penambahan Kolom / Tabel Database:**
   - Gunakan format SQL non-destruktif:
     ```sql
     CREATE TABLE IF NOT EXISTS ...
     ALTER TABLE nama_tabel ADD COLUMN IF NOT EXISTS ...
     INSERT IGNORE INTO ...
     ```
   - Catat setiap perubahan struktur database pada file dokumentasi migrasi sebelum dibagikan ke rekan tim.
4. **Sebelum Melakukan Push:**
   Selalu jalankan `php -l` pada file yang diubah dan lakukan commit dengan pesan yang jelas:
   ```bash
   git add .
   git commit -m "feat: [nama fitur / modul] oleh [nama developer]"
   git push origin main
   ```

---

## 📄 Lisensi & Hak Cipta
Dikembangkan untuk sistem informasi manajemen klinik gigi modern di Indonesia.  
© 2026 **DENTARA Dental Care Management System**. All rights reserved.

