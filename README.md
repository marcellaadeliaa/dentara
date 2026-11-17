# DENTARA

**Sistem Informasi Manajemen Klinik Gigi Berbasis Web**

DENTARA merupakan aplikasi berbasis web yang dikembangkan untuk membantu mengelola kegiatan pelayanan dan administrasi klinik gigi dalam satu sistem terintegrasi.

---

## Fitur Utama

1. **Halaman Publik**: Informasi klinik, promo, daftar tarif layanan, jadwal dokter, pendaftaran online (*online booking*), pemantauan antrean (*queue tracker*), dan FAQ.
2. **Portal Pasien**: Profil dan nomor rekam medis, kelola jadwal kunjungan, riwayat pemeriksaan & resep, tagihan dan kuitansi.
3. **Front Desk (Resepsionis)**: Data pasien, pendaftaran walk-in & check-in booking online, manajemen nomor antrean.
4. **EMR Dokter**: Antrean dokter, riwayat kesehatan & alergi, pencatatan rekam medis SOAP, diagnosis ICD-10, tindakan medis gigi, odontogram, dan resep elektronik (*E-Prescription*).
5. **Apotek**: Pengelolaan resep masuk, pemrosesan obat, monitoring stok minimum & tanggal kedaluwarsa.
6. **Kasir**: Perhitungan otomatis (*auto-billing*), multi-metode pembayaran, dan cetak kuitansi.
7. **Dashboard Manajemen**: Pengelolaan staf & hak akses (RBAC), tarif tindakan, fee sharing dokter, dan laporan operasional/keuangan.

---

## Teknologi

- **Back-end:** PHP Native
- **Front-end:** HTML, CSS, JavaScript
- **Database:** MySQL
- **Web Server Lokal:** Laragon / XAMPP (Apache + PHP 8.x + MySQL)
- **Version Control:** Git & GitHub

---

## Panduan Instalasi & Menjalankan Aplikasi

### 1. Prasyarat Sistem
- Web server lokal: **Laragon** (direkomendasikan) atau **XAMPP**.
- PHP versi 8.0 atau lebih baru dengan ekstensi `pdo_mysql` aktif.
- MySQL 8.0 atau MariaDB 10.4+.

### 2. Langkah Instalasi
1. Kloning repository ini ke direktori root web server Anda:
   - **Laragon:** `C:\laragon\www\dentara`
   - **XAMPP:** `C:\xampp\htdocs\dentara`
   ```bash
   git clone https://github.com/marcellaadeliaa/dentara.git
   ```
2. Pastikan service **Apache** dan **MySQL** sudah berjalan di Laragon/XAMPP.
3. Buka **phpMyAdmin** (`http://localhost/phpmyadmin`) atau GUI database lainnya (HeidiSQL/DBeaver).
4. Buat database baru bernama: `dentara`.
5. Import file basis data awal dari path:
   ```text
   includes/dentara.sql
   ```
6. Buka aplikasi di peramban (browser):
   - **Laragon:** `http://dentara.test` atau `http://localhost/dentara`
   - **XAMPP:** `http://localhost/dentara`

---

## Akun Demo Pengujian Sistem

Semua akun demo menggunakan kata sandi bawaan: **`password123`**

| Role / Hak Akses | Username | Kata Sandi | Deskripsi |
| :--- | :--- | :--- | :--- |
| **Administrator / Owner** | `admin` | `password123` | Akses penuh dashboard analitik & manajemen |
| **Dokter Gigi Spesialis** | `drg_sarah` | `password123` | Akses EMR poli dokter spesialis |
| **Dokter Gigi Umum** | `drg_fajar` | `password123` | Akses EMR poli dokter umum |
| **Front Desk / Resepsionis** | `resepsionis` | `password123` | Akses antrean & pendaftaran |
| **Apoteker / Farmasi** | `apoteker` | `password123` | Akses penyiapan resep & stok obat |
| **Kasir / Billing** | `kasir` | `password123` | Akses kasir, tagihan, & kuitansi |
| **Pasien Portal** | `pasien_budi` | `password123` | Akses portal pasien & rekam medis mandiri |

---

## Tim Pengembang

- **Cella** (Marcella Adelia Putri - 24.N1.0004)
- **Nesia** (Nesia Rani Wijaya)
- **Issa**
