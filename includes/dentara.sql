-- ======================================================================
-- DENTARA: Sistem Informasi Manajemen Klinik Gigi Berbasis Web
-- Database: dentara
-- Charset: utf8mb4 / InnoDB
-- ======================================================================

CREATE DATABASE IF NOT EXISTS `dentara` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `dentara`;

SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------------------
-- 1. PENGGUNA (STAFF & DOKTER)
-- ----------------------------------------------------------------------
DROP TABLE IF EXISTS `pengguna`;
CREATE TABLE `pengguna` (
  `id_pengguna` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `nama_lengkap` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `no_hp` VARCHAR(20) NOT NULL,
  `role` ENUM('admin','dokter','resepsionis','apoteker','kasir') NOT NULL,
  `status` ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------
-- 2. AKUN PASIEN (PORTAL PASIEN ONLINE)
-- ----------------------------------------------------------------------
DROP TABLE IF EXISTS `akun_pasien`;
CREATE TABLE `akun_pasien` (
  `id_akun` INT AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `no_hp` VARCHAR(20) NOT NULL,
  `tanggal_daftar` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------
-- 3. LOG AKTIVITAS
-- ----------------------------------------------------------------------
DROP TABLE IF EXISTS `log_aktivitas`;
CREATE TABLE `log_aktivitas` (
  `id_log` INT AUTO_INCREMENT PRIMARY KEY,
  `id_pengguna` INT NULL,
  `aksi` VARCHAR(100) NOT NULL,
  `detail` TEXT NULL,
  `ip` VARCHAR(45) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_log_pengguna` (`id_pengguna`),
  INDEX `idx_log_created` (`created_at`),
  CONSTRAINT `fk_log_pengguna` FOREIGN KEY (`id_pengguna`) REFERENCES `pengguna` (`id_pengguna`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------
-- 4. PASIEN
-- ----------------------------------------------------------------------
DROP TABLE IF EXISTS `pasien`;
CREATE TABLE `pasien` (
  `id_pasien` INT AUTO_INCREMENT PRIMARY KEY,
  `no_rm` VARCHAR(20) NOT NULL UNIQUE,
  `id_akun` INT NULL,
  `hubungan_keluarga` ENUM('Diri Sendiri','Anak','Orang Tua','Pasangan','Lainnya') NOT NULL DEFAULT 'Diri Sendiri',
  `nama_pasien` VARCHAR(100) NOT NULL,
  `nik` VARCHAR(16) NOT NULL,
  `tgl_lahir` DATE NOT NULL,
  `jenis_kelamin` ENUM('L','P') NOT NULL,
  `gol_darah` VARCHAR(5) NULL,
  `alamat` TEXT NOT NULL,
  `no_hp` VARCHAR(20) NOT NULL,
  `alergi_obat` TEXT NULL,
  `kondisi_medis` TEXT NULL,
  `foto` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_pasien_nik` (`nik`),
  INDEX `idx_pasien_nama` (`nama_pasien`),
  INDEX `idx_pasien_akun` (`id_akun`),
  CONSTRAINT `fk_pasien_akun` FOREIGN KEY (`id_akun`) REFERENCES `akun_pasien` (`id_akun`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------
-- 5. WALI PASIEN (UNTUK PASIEN ANAK / KHUSUS)
-- ----------------------------------------------------------------------
DROP TABLE IF EXISTS `wali_pasien`;
CREATE TABLE `wali_pasien` (
  `id_wali` INT AUTO_INCREMENT PRIMARY KEY,
  `id_pasien` INT NOT NULL,
  `nama_wali` VARCHAR(100) NOT NULL,
  `hubungan` VARCHAR(50) NOT NULL,
  `kontak` VARCHAR(20) NOT NULL,
  `pekerjaan` VARCHAR(100) NULL,
  `alamat` TEXT NULL,
  INDEX `idx_wali_pasien` (`id_pasien`),
  CONSTRAINT `fk_wali_pasien` FOREIGN KEY (`id_pasien`) REFERENCES `pasien` (`id_pasien`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------
-- 6. DOKTER
-- ----------------------------------------------------------------------
DROP TABLE IF EXISTS `dokter`;
CREATE TABLE `dokter` (
  `id_dokter` INT AUTO_INCREMENT PRIMARY KEY,
  `id_pengguna` INT NOT NULL UNIQUE,
  `spesialisasi` ENUM('Umum','Konservasi','Ortodonti','Bedah Mulut','Pedodonti','Periodonti') NOT NULL DEFAULT 'Umum',
  `no_str` VARCHAR(50) NOT NULL,
  `persen_fee` DECIMAL(5,2) NOT NULL DEFAULT 40.00,
  `foto` VARCHAR(255) NULL,
  `bio` TEXT NULL,
  INDEX `idx_dokter_pengguna` (`id_pengguna`),
  CONSTRAINT `fk_dokter_pengguna` FOREIGN KEY (`id_pengguna`) REFERENCES `pengguna` (`id_pengguna`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------
-- 7. JADWAL PRAKTIK DOKTER
-- ----------------------------------------------------------------------
DROP TABLE IF EXISTS `jadwal_praktik_dokter`;
CREATE TABLE `jadwal_praktik_dokter` (
  `id_jadwal` INT AUTO_INCREMENT PRIMARY KEY,
  `id_dokter` INT NOT NULL,
  `hari` ENUM('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu') NOT NULL,
  `jam_mulai` TIME NOT NULL,
  `jam_selesai` TIME NOT NULL,
  `kuota_per_hari` INT NOT NULL DEFAULT 15,
  `status` ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  INDEX `idx_jadwal_dokter` (`id_dokter`),
  CONSTRAINT `fk_jadwal_dokter` FOREIGN KEY (`id_dokter`) REFERENCES `dokter` (`id_dokter`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------
-- 8. CUTI DOKTER
-- ----------------------------------------------------------------------
DROP TABLE IF EXISTS `cuti_dokter`;
CREATE TABLE `cuti_dokter` (
  `id_cuti` INT AUTO_INCREMENT PRIMARY KEY,
  `id_dokter` INT NOT NULL,
  `tanggal_mulai` DATE NOT NULL,
  `tanggal_selesai` DATE NOT NULL,
  `alasan` TEXT NULL,
  `status` ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  INDEX `idx_cuti_dokter` (`id_dokter`),
  CONSTRAINT `fk_cuti_dokter` FOREIGN KEY (`id_dokter`) REFERENCES `dokter` (`id_dokter`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------
-- 9. JADWAL KLINIK
-- ----------------------------------------------------------------------
DROP TABLE IF EXISTS `jadwal_klinik`;
CREATE TABLE `jadwal_klinik` (
  `id_jadwal_klinik` INT AUTO_INCREMENT PRIMARY KEY,
  `hari` ENUM('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu') NOT NULL UNIQUE,
  `status` ENUM('Buka','Tutup') NOT NULL DEFAULT 'Buka',
  `buka_1` TIME NULL,
  `tutup_1` TIME NULL,
  `buka_2` TIME NULL,
  `tutup_2` TIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------
-- 10. LIBUR MENDADAK
-- ----------------------------------------------------------------------
DROP TABLE IF EXISTS `libur_mendadak`;
CREATE TABLE `libur_mendadak` (
  `id_libur` INT AUTO_INCREMENT PRIMARY KEY,
  `tanggal_libur` DATE NOT NULL UNIQUE,
  `alasan` TEXT NOT NULL,
  `status` ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------
-- 11. LAYANAN
-- ----------------------------------------------------------------------
DROP TABLE IF EXISTS `layanan`;
CREATE TABLE `layanan` (
  `id_layanan` INT AUTO_INCREMENT PRIMARY KEY,
  `nama_layanan` VARCHAR(100) NOT NULL,
  `kategori` ENUM('Preventif','Konservasi','Ortodonti','Bedah','Anak') NOT NULL,
  `deskripsi` TEXT NULL,
  `estimasi_menit` INT NOT NULL DEFAULT 30,
  `tarif_awal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `gambar` VARCHAR(255) NULL,
  `status` ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------
-- 12. RESERVASI
-- ----------------------------------------------------------------------
DROP TABLE IF EXISTS `reservasi`;
CREATE TABLE `reservasi` (
  `id_reservasi` INT AUTO_INCREMENT PRIMARY KEY,
  `kode_booking` VARCHAR(10) NOT NULL UNIQUE,
  `id_pasien` INT NULL,
  `nama_tamu` VARCHAR(100) NULL,
  `no_hp_tamu` VARCHAR(20) NULL,
  `id_dokter` INT NOT NULL,
  `id_layanan` INT NULL,
  `tanggal` DATE NOT NULL,
  `jam` TIME NOT NULL,
  `keluhan` TEXT NULL,
  `status` ENUM('menunggu','dikonfirmasi','check_in','selesai','dibatalkan') NOT NULL DEFAULT 'menunggu',
  `sumber` ENUM('online','walkin') NOT NULL DEFAULT 'online',
  `catatan_reschedule` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_res_kode` (`kode_booking`),
  INDEX `idx_res_tgl` (`tanggal`),
  INDEX `idx_res_dokter` (`id_dokter`),
  INDEX `idx_res_pasien` (`id_pasien`),
  CONSTRAINT `fk_res_pasien` FOREIGN KEY (`id_pasien`) REFERENCES `pasien` (`id_pasien`) ON DELETE SET NULL,
  CONSTRAINT `fk_res_dokter` FOREIGN KEY (`id_dokter`) REFERENCES `dokter` (`id_dokter`) ON DELETE CASCADE,
  CONSTRAINT `fk_res_layanan` FOREIGN KEY (`id_layanan`) REFERENCES `layanan` (`id_layanan`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------
-- 13. ANTRIAN
-- ----------------------------------------------------------------------
DROP TABLE IF EXISTS `antrian`;
CREATE TABLE `antrian` (
  `id_antrian` INT AUTO_INCREMENT PRIMARY KEY,
  `id_reservasi` INT NOT NULL,
  `id_dokter` INT NOT NULL,
  `tanggal` DATE NOT NULL,
  `nomor_antrian` INT NOT NULL,
  `prioritas` TINYINT NOT NULL DEFAULT 0 COMMENT '0: normal, 1: lansia/khusus, 2: darurat',
  `alasan_prioritas` VARCHAR(255) NULL,
  `status` ENUM('menunggu','dipanggil','diperiksa','ditunda','selesai') NOT NULL DEFAULT 'menunggu',
  `waktu_checkin` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `waktu_panggil` TIMESTAMP NULL,
  `waktu_mulai` TIMESTAMP NULL,
  `waktu_selesai` TIMESTAMP NULL,
  INDEX `idx_antrian_tgl_dokter` (`tanggal`, `id_dokter`),
  INDEX `idx_antrian_status` (`status`),
  CONSTRAINT `fk_antrian_reservasi` FOREIGN KEY (`id_reservasi`) REFERENCES `reservasi` (`id_reservasi`) ON DELETE CASCADE,
  CONSTRAINT `fk_antrian_dokter` FOREIGN KEY (`id_dokter`) REFERENCES `dokter` (`id_dokter`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------
-- 14. ICD-10 (KODE GIGI)
-- ----------------------------------------------------------------------
DROP TABLE IF EXISTS `icd10`;
CREATE TABLE `icd10` (
  `kode` VARCHAR(10) PRIMARY KEY,
  `nama_penyakit` VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------
-- 15. REKAM MEDIS
-- ----------------------------------------------------------------------
DROP TABLE IF EXISTS `rekam_medis`;
CREATE TABLE `rekam_medis` (
  `id_rekam` INT AUTO_INCREMENT PRIMARY KEY,
  `id_antrian` INT NULL,
  `id_pasien` INT NOT NULL,
  `id_dokter` INT NOT NULL,
  `tanggal_periksa` DATE NOT NULL,
  `subjektif` TEXT NOT NULL,
  `objektif` TEXT NOT NULL,
  `asesmen` TEXT NOT NULL,
  `plan` TEXT NOT NULL,
  `catatan_khusus` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_rekam_pasien` (`id_pasien`),
  INDEX `idx_rekam_antrian` (`id_antrian`),
  CONSTRAINT `fk_rekam_pasien` FOREIGN KEY (`id_pasien`) REFERENCES `pasien` (`id_pasien`) ON DELETE CASCADE,
  CONSTRAINT `fk_rekam_dokter` FOREIGN KEY (`id_dokter`) REFERENCES `dokter` (`id_dokter`) ON DELETE CASCADE,
  CONSTRAINT `fk_rekam_antrian` FOREIGN KEY (`id_antrian`) REFERENCES `antrian` (`id_antrian`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------
-- 16. REKAM DIAGNOSIS (JUNCTION ICD-10)
-- ----------------------------------------------------------------------
DROP TABLE IF EXISTS `rekam_diagnosis`;
CREATE TABLE `rekam_diagnosis` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_rekam` INT NOT NULL,
  `kode_icd10` VARCHAR(10) NOT NULL,
  INDEX `idx_rd_rekam` (`id_rekam`),
  CONSTRAINT `fk_rd_rekam` FOREIGN KEY (`id_rekam`) REFERENCES `rekam_medis` (`id_rekam`) ON DELETE CASCADE,
  CONSTRAINT `fk_rd_icd10` FOREIGN KEY (`kode_icd10`) REFERENCES `icd10` (`kode`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------
-- 17. REKAM TINDAKAN
-- ----------------------------------------------------------------------
DROP TABLE IF EXISTS `rekam_tindakan`;
CREATE TABLE `rekam_tindakan` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_rekam` INT NOT NULL,
  `id_layanan` INT NOT NULL,
  `nomor_gigi` VARCHAR(10) NULL,
  `jumlah` INT NOT NULL DEFAULT 1,
  `tarif_saat_itu` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  INDEX `idx_rt_rekam` (`id_rekam`),
  CONSTRAINT `fk_rt_rekam` FOREIGN KEY (`id_rekam`) REFERENCES `rekam_medis` (`id_rekam`) ON DELETE CASCADE,
  CONSTRAINT `fk_rt_layanan` FOREIGN KEY (`id_layanan`) REFERENCES `layanan` (`id_layanan`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------
-- 18. ODONTOGRAM
-- ----------------------------------------------------------------------
DROP TABLE IF EXISTS `odontogram`;
CREATE TABLE `odontogram` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_pasien` INT NOT NULL,
  `id_rekam` INT NULL,
  `nomor_gigi` VARCHAR(5) NOT NULL,
  `permukaan` ENUM('oklusal','mesial','distal','bukal','lingual','seluruh') NOT NULL,
  `kondisi` ENUM('sehat','karies','tambalan','hilang','tumpatan_sementara','saluran_akar','mahkota','impaksi') NOT NULL,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_odonto_pasien` (`id_pasien`),
  CONSTRAINT `fk_odonto_pasien` FOREIGN KEY (`id_pasien`) REFERENCES `pasien` (`id_pasien`) ON DELETE CASCADE,
  CONSTRAINT `fk_odonto_rekam` FOREIGN KEY (`id_rekam`) REFERENCES `rekam_medis` (`id_rekam`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------
-- 19. TREATMENT PLAN
-- ----------------------------------------------------------------------
DROP TABLE IF EXISTS `treatment_plan`;
CREATE TABLE `treatment_plan` (
  `id_plan` INT AUTO_INCREMENT PRIMARY KEY,
  `id_pasien` INT NOT NULL,
  `id_rekam` INT NULL,
  `id_dokter` INT NOT NULL,
  `rencana` TEXT NOT NULL,
  `tanggal_kontrol_berikut` DATE NULL,
  `status` ENUM('direncanakan','dijalani','selesai') NOT NULL DEFAULT 'direncanakan',
  INDEX `idx_tp_pasien` (`id_pasien`),
  CONSTRAINT `fk_tp_pasien` FOREIGN KEY (`id_pasien`) REFERENCES `pasien` (`id_pasien`) ON DELETE CASCADE,
  CONSTRAINT `fk_tp_rekam` FOREIGN KEY (`id_rekam`) REFERENCES `rekam_medis` (`id_rekam`) ON DELETE SET NULL,
  CONSTRAINT `fk_tp_dokter` FOREIGN KEY (`id_dokter`) REFERENCES `dokter` (`id_dokter`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------
-- 20. OBAT
-- ----------------------------------------------------------------------
DROP TABLE IF EXISTS `obat`;
CREATE TABLE `obat` (
  `id_obat` INT AUTO_INCREMENT PRIMARY KEY,
  `nama_obat` VARCHAR(100) NOT NULL,
  `kategori` ENUM('Antibiotik','Antinyeri','Vitamin','Lainnya') NOT NULL,
  `satuan` VARCHAR(30) NOT NULL,
  `harga_beli` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `harga_jual` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `stok` INT NOT NULL DEFAULT 0,
  `stok_minimum` INT NOT NULL DEFAULT 10,
  `exp_date` DATE NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------
-- 21. DENTAL SUPPLY
-- ----------------------------------------------------------------------
DROP TABLE IF EXISTS `dental_supply`;
CREATE TABLE `dental_supply` (
  `id_supply` INT AUTO_INCREMENT PRIMARY KEY,
  `nama_barang` VARCHAR(100) NOT NULL,
  `satuan` VARCHAR(30) NOT NULL,
  `stok` INT NOT NULL DEFAULT 0,
  `stok_minimum` INT NOT NULL DEFAULT 5,
  `exp_date` DATE NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------
-- 22. RESEP
-- ----------------------------------------------------------------------
DROP TABLE IF EXISTS `resep`;
CREATE TABLE `resep` (
  `id_resep` INT AUTO_INCREMENT PRIMARY KEY,
  `id_rekam` INT NOT NULL,
  `status` ENUM('antrean','disiapkan','selesai','dikembalikan') NOT NULL DEFAULT 'antrean',
  `catatan_apoteker` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_resep_rekam` (`id_rekam`),
  CONSTRAINT `fk_resep_rekam` FOREIGN KEY (`id_rekam`) REFERENCES `rekam_medis` (`id_rekam`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------
-- 23. RESEP DETAIL
-- ----------------------------------------------------------------------
DROP TABLE IF EXISTS `resep_detail`;
CREATE TABLE `resep_detail` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_resep` INT NOT NULL,
  `id_obat` INT NOT NULL,
  `jumlah` INT NOT NULL DEFAULT 1,
  `dosis` VARCHAR(50) NOT NULL,
  `aturan_pakai` VARCHAR(100) NOT NULL,
  INDEX `idx_rdt_resep` (`id_resep`),
  CONSTRAINT `fk_rdt_resep` FOREIGN KEY (`id_resep`) REFERENCES `resep` (`id_resep`) ON DELETE CASCADE,
  CONSTRAINT `fk_rdt_obat` FOREIGN KEY (`id_obat`) REFERENCES `obat` (`id_obat`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------
-- 24. STOCK OPNAME & DETAIL
-- ----------------------------------------------------------------------
DROP TABLE IF EXISTS `stock_opname`;
CREATE TABLE `stock_opname` (
  `id_opname` INT AUTO_INCREMENT PRIMARY KEY,
  `id_pengguna` INT NOT NULL,
  `tanggal` DATE NOT NULL,
  `catatan` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_so_pengguna` (`id_pengguna`),
  CONSTRAINT `fk_so_pengguna` FOREIGN KEY (`id_pengguna`) REFERENCES `pengguna` (`id_pengguna`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `stock_opname_detail`;
CREATE TABLE `stock_opname_detail` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_opname` INT NOT NULL,
  `jenis` ENUM('obat','supply') NOT NULL,
  `id_item` INT NOT NULL,
  `stok_sistem` INT NOT NULL,
  `stok_fisik` INT NOT NULL,
  `selisih` INT NOT NULL,
  `keterangan` ENUM('hilang','rusak','lainnya','sesuai') NOT NULL DEFAULT 'sesuai',
  INDEX `idx_sod_opname` (`id_opname`),
  CONSTRAINT `fk_sod_opname` FOREIGN KEY (`id_opname`) REFERENCES `stock_opname` (`id_opname`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------
-- 25. TAGIHAN
-- ----------------------------------------------------------------------
DROP TABLE IF EXISTS `tagihan`;
CREATE TABLE `tagihan` (
  `id_tagihan` INT AUTO_INCREMENT PRIMARY KEY,
  `id_antrian` INT NULL,
  `id_pasien` INT NOT NULL,
  `biaya_konsultasi` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `biaya_tindakan` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `biaya_obat` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `biaya_admin` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total_biaya` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `ditanggung_asuransi` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `ditanggung_pasien` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('belum_bayar','lunas') NOT NULL DEFAULT 'belum_bayar',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_tagihan_pasien` (`id_pasien`),
  INDEX `idx_tagihan_antrian` (`id_antrian`),
  CONSTRAINT `fk_tagihan_pasien` FOREIGN KEY (`id_pasien`) REFERENCES `pasien` (`id_pasien`) ON DELETE CASCADE,
  CONSTRAINT `fk_tagihan_antrian` FOREIGN KEY (`id_antrian`) REFERENCES `antrian` (`id_antrian`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------
-- 26. TAGIHAN DETAIL
-- ----------------------------------------------------------------------
DROP TABLE IF EXISTS `tagihan_detail`;
CREATE TABLE `tagihan_detail` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_tagihan` INT NOT NULL,
  `jenis` ENUM('konsultasi','tindakan','obat','admin') NOT NULL,
  `deskripsi` VARCHAR(255) NOT NULL,
  `qty` INT NOT NULL DEFAULT 1,
  `harga` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  INDEX `idx_td_tagihan` (`id_tagihan`),
  CONSTRAINT `fk_td_tagihan` FOREIGN KEY (`id_tagihan`) REFERENCES `tagihan` (`id_tagihan`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------
-- 27. SHIFT KASIR
-- ----------------------------------------------------------------------
DROP TABLE IF EXISTS `shift_kasir`;
CREATE TABLE `shift_kasir` (
  `id_shift` INT AUTO_INCREMENT PRIMARY KEY,
  `id_kasir` INT NOT NULL,
  `waktu_buka` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `waktu_tutup` TIMESTAMP NULL,
  `kas_awal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total_sistem` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total_fisik` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `selisih` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('buka','tutup') NOT NULL DEFAULT 'buka',
  INDEX `idx_shift_kasir` (`id_kasir`),
  CONSTRAINT `fk_shift_kasir` FOREIGN KEY (`id_kasir`) REFERENCES `pengguna` (`id_pengguna`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------
-- 28. PEMBAYARAN
-- ----------------------------------------------------------------------
DROP TABLE IF EXISTS `pembayaran`;
CREATE TABLE `pembayaran` (
  `id_pembayaran` INT AUTO_INCREMENT PRIMARY KEY,
  `id_tagihan` INT NOT NULL,
  `id_kasir` INT NOT NULL,
  `jumlah_dibayar` DECIMAL(12,2) NOT NULL,
  `metode` ENUM('tunai','transfer','debit','kartu_kredit','qris') NOT NULL,
  `tanggal_bayar` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `no_kuitansi` VARCHAR(50) NOT NULL UNIQUE,
  `id_shift` INT NULL,
  INDEX `idx_bayar_tagihan` (`id_tagihan`),
  INDEX `idx_bayar_shift` (`id_shift`),
  CONSTRAINT `fk_bayar_tagihan` FOREIGN KEY (`id_tagihan`) REFERENCES `tagihan` (`id_tagihan`) ON DELETE CASCADE,
  CONSTRAINT `fk_bayar_kasir` FOREIGN KEY (`id_kasir`) REFERENCES `pengguna` (`id_pengguna`) ON DELETE RESTRICT,
  CONSTRAINT `fk_bayar_shift` FOREIGN KEY (`id_shift`) REFERENCES `shift_kasir` (`id_shift`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------
-- 29. PENGATURAN PEMBAYARAN
-- ----------------------------------------------------------------------
DROP TABLE IF EXISTS `pengaturan_pembayaran`;
CREATE TABLE `pengaturan_pembayaran` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama_bank` VARCHAR(50) NOT NULL,
  `no_rekening` VARCHAR(50) NOT NULL,
  `atas_nama` VARCHAR(100) NOT NULL,
  `qr_qris` VARCHAR(255) NULL,
  `status` ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------
-- 30. FEE SHARING
-- ----------------------------------------------------------------------
DROP TABLE IF EXISTS `fee_sharing`;
CREATE TABLE `fee_sharing` (
  `id_fee` INT AUTO_INCREMENT PRIMARY KEY,
  `id_dokter` INT NOT NULL,
  `periode_bulan` VARCHAR(7) NOT NULL COMMENT 'Format: YYYY-MM',
  `jumlah_pasien` INT NOT NULL DEFAULT 0,
  `total_pendapatan_tindakan` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `persen_fee` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `nominal_fee` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_fee_dokter` (`id_dokter`),
  CONSTRAINT `fk_fee_dokter` FOREIGN KEY (`id_dokter`) REFERENCES `dokter` (`id_dokter`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------
-- 31. GAMIFIKASI POIN
-- ----------------------------------------------------------------------
DROP TABLE IF EXISTS `gamifikasi_poin`;
CREATE TABLE `gamifikasi_poin` (
  `id_poin` INT AUTO_INCREMENT PRIMARY KEY,
  `id_pasien` INT NOT NULL UNIQUE,
  `total_poin` INT NOT NULL DEFAULT 0,
  `jumlah_kunjungan_selesai` INT NOT NULL DEFAULT 0,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_gp_pasien` (`id_pasien`),
  CONSTRAINT `fk_gp_pasien` FOREIGN KEY (`id_pasien`) REFERENCES `pasien` (`id_pasien`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------
-- 32. MASTER KARTU GAMIFIKASI
-- ----------------------------------------------------------------------
DROP TABLE IF EXISTS `master_kartu`;
CREATE TABLE `master_kartu` (
  `id_kartu` INT AUTO_INCREMENT PRIMARY KEY,
  `nama_kartu` VARCHAR(100) NOT NULL,
  `deskripsi_edukatif` TEXT NOT NULL,
  `rarity` ENUM('Common','Rare','Epic','Legendary') NOT NULL DEFAULT 'Common',
  `gambar` VARCHAR(255) NULL,
  `poin_diperlukan` INT NOT NULL DEFAULT 100
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------
-- 33. KOLEKSI KARTU & KOLEKSI KARTU PASIEN
-- ----------------------------------------------------------------------
DROP TABLE IF EXISTS `koleksi_kartu`;
CREATE TABLE `koleksi_kartu` (
  `id_koleksi` INT AUTO_INCREMENT PRIMARY KEY,
  `nama_seri` VARCHAR(100) NOT NULL,
  `deskripsi` TEXT NOT NULL,
  `total_kartu` INT NOT NULL DEFAULT 6
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `koleksi_kartu_pasien`;
CREATE TABLE `koleksi_kartu_pasien` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_pasien` INT NOT NULL,
  `id_kartu` INT NOT NULL,
  `tanggal_didapat` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_kkp_pasien` (`id_pasien`),
  CONSTRAINT `fk_kkp_pasien` FOREIGN KEY (`id_pasien`) REFERENCES `pasien` (`id_pasien`) ON DELETE CASCADE,
  CONSTRAINT `fk_kkp_kartu` FOREIGN KEY (`id_kartu`) REFERENCES `master_kartu` (`id_kartu`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------
-- 34. ARTIKEL
-- ----------------------------------------------------------------------
DROP TABLE IF EXISTS `artikel`;
CREATE TABLE `artikel` (
  `id_artikel` INT AUTO_INCREMENT PRIMARY KEY,
  `judul` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL UNIQUE,
  `isi` LONGTEXT NOT NULL,
  `gambar` VARCHAR(255) NULL,
  `penulis` VARCHAR(100) NOT NULL,
  `status` ENUM('draft','publish') NOT NULL DEFAULT 'publish',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------
-- 35. PROMO BANNER
-- ----------------------------------------------------------------------
DROP TABLE IF EXISTS `promo_banner`;
CREATE TABLE `promo_banner` (
  `id_promo` INT AUTO_INCREMENT PRIMARY KEY,
  `judul` VARCHAR(255) NOT NULL,
  `deskripsi` TEXT NOT NULL,
  `gambar` VARCHAR(255) NULL,
  `status` ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  `tanggal_mulai` DATE NOT NULL,
  `tanggal_selesai` DATE NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------
-- 36. FAQ
-- ----------------------------------------------------------------------
DROP TABLE IF EXISTS `faq`;
CREATE TABLE `faq` (
  `id_faq` INT AUTO_INCREMENT PRIMARY KEY,
  `pertanyaan` TEXT NOT NULL,
  `jawaban` TEXT NOT NULL,
  `urutan` INT NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------
-- 37. TEMPLATE NOTIFIKASI
-- ----------------------------------------------------------------------
DROP TABLE IF EXISTS `template_notifikasi`;
CREATE TABLE `template_notifikasi` (
  `id_tn` INT AUTO_INCREMENT PRIMARY KEY,
  `judul` VARCHAR(100) NOT NULL,
  `kategori` ENUM('Ulang Tahun','Pengingat Kontrol','Informasi','Lainnya') NOT NULL,
  `isi` TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------
-- 38. PESAN NOTIFIKASI (UNTUK PASIEN)
-- ----------------------------------------------------------------------
DROP TABLE IF EXISTS `pesan_notifikasi`;
CREATE TABLE `pesan_notifikasi` (
  `id_pesan` INT AUTO_INCREMENT PRIMARY KEY,
  `id_pasien` INT NOT NULL,
  `judul` VARCHAR(255) NOT NULL,
  `isi` TEXT NOT NULL,
  `status` ENUM('belum_dibaca','dibaca') NOT NULL DEFAULT 'belum_dibaca',
  `tanggal_kirim` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_pesan_pasien` (`id_pasien`),
  CONSTRAINT `fk_pesan_pasien` FOREIGN KEY (`id_pasien`) REFERENCES `pasien` (`id_pasien`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------
-- 39. PENGATURAN KLINIK
-- ----------------------------------------------------------------------
DROP TABLE IF EXISTS `pengaturan_klinik`;
CREATE TABLE `pengaturan_klinik` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama_klinik` VARCHAR(100) NOT NULL,
  `telepon` VARCHAR(20) NOT NULL,
  `whatsapp` VARCHAR(20) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `alamat` TEXT NOT NULL,
  `latitude` VARCHAR(50) NOT NULL,
  `longitude` VARCHAR(50) NOT NULL,
  `logo` VARCHAR(255) NULL,
  `banner` VARCHAR(255) NULL,
  `biaya_konsultasi` DECIMAL(12,2) NOT NULL DEFAULT 50000.00,
  `biaya_admin` DECIMAL(12,2) NOT NULL DEFAULT 15000.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;


-- ======================================================================
-- SEEDER DATA WAJIB & AWAL
-- Catatan Password Semua Akun: password123
-- Hash Bcrypt: $2y$10$TMpkPR0zYyavqThbjlY1CuDOnv.ujM0VC2u51Ilc1l8muoz0toGwq
-- ======================================================================

-- 1. PENGGUNA STAFF
INSERT INTO `pengguna` (`id_pengguna`, `username`, `password`, `nama_lengkap`, `email`, `no_hp`, `role`, `status`) VALUES
(1, 'admin', '$2y$10$TMpkPR0zYyavqThbjlY1CuDOnv.ujM0VC2u51Ilc1l8muoz0toGwq', 'drg. Hendra Pratama, Sp.Ort', 'owner@dentara.id', '081122334455', 'admin', 'aktif'),
(2, 'drg_budi', '$2y$10$TMpkPR0zYyavqThbjlY1CuDOnv.ujM0VC2u51Ilc1l8muoz0toGwq', 'drg. Budi Santoso, Sp.KG', 'drg.budi@dentara.id', '081234567891', 'dokter', 'aktif'),
(3, 'drg_siti', '$2y$10$TMpkPR0zYyavqThbjlY1CuDOnv.ujM0VC2u51Ilc1l8muoz0toGwq', 'drg. Siti Aminah', 'drg.siti@dentara.id', '081234567892', 'dokter', 'aktif'),
(4, 'drg_anita', '$2y$10$TMpkPR0zYyavqThbjlY1CuDOnv.ujM0VC2u51Ilc1l8muoz0toGwq', 'drg. Anita Wijaya, Sp.KGA', 'drg.anita@dentara.id', '081234567893', 'dokter', 'aktif'),
(5, 'resepsionis', '$2y$10$TMpkPR0zYyavqThbjlY1CuDOnv.ujM0VC2u51Ilc1l8muoz0toGwq', 'Maya Putri, A.Md', 'reception@dentara.id', '081234567894', 'resepsionis', 'aktif'),
(6, 'apoteker', '$2y$10$TMpkPR0zYyavqThbjlY1CuDOnv.ujM0VC2u51Ilc1l8muoz0toGwq', 'apt. Rian Firmansyah, S.Farm', 'pharmacy@dentara.id', '081234567895', 'apoteker', 'aktif'),
(7, 'kasir', '$2y$10$TMpkPR0zYyavqThbjlY1CuDOnv.ujM0VC2u51Ilc1l8muoz0toGwq', 'Cindy Claudia, S.Ak', 'cashier@dentara.id', '081234567896', 'kasir', 'aktif');

-- 2. DOKTER DATA
INSERT INTO `dokter` (`id_dokter`, `id_pengguna`, `spesialisasi`, `no_str`, `persen_fee`, `bio`) VALUES
(1, 2, 'Konservasi', 'STR-KG-2018-09871', 45.00, 'Spesialis Konservasi Gigi berfokus pada estetika, penambalan mikro, dan perawatan saluran akar dengan pendekatan bebas rasa sakit.'),
(2, 3, 'Umum', 'STR-DU-2020-04512', 40.00, 'Dokter Gigi Umum yang ramah, komunikatif, dan teliti dalam perawatan pencegahan, scaling, dan edukasi kesehatan mulut.'),
(3, 4, 'Pedodonti', 'STR-KGA-2019-01123', 50.00, 'Spesialis Kedokteran Gigi Anak yang sabar dan ceria, membuat pengalaman anak ke dokter gigi menjadi petualangan yang menyenangkan.');

-- 3. JADWAL PRAKTIK DOKTER
INSERT INTO `jadwal_praktik_dokter` (`id_dokter`, `hari`, `jam_mulai`, `jam_selesai`, `kuota_per_hari`, `status`) VALUES
(1, 'Senin', '09:00:00', '14:00:00', 15, 'aktif'),
(1, 'Rabu', '09:00:00', '14:00:00', 15, 'aktif'),
(1, 'Jumat', '14:00:00', '19:00:00', 15, 'aktif'),
(2, 'Senin', '15:00:00', '21:00:00', 18, 'aktif'),
(2, 'Selasa', '09:00:00', '15:00:00', 18, 'aktif'),
(2, 'Kamis', '09:00:00', '15:00:00', 18, 'aktif'),
(2, 'Sabtu', '09:00:00', '15:00:00', 15, 'aktif'),
(3, 'Selasa', '15:00:00', '20:00:00', 12, 'aktif'),
(3, 'Rabu', '15:00:00', '20:00:00', 12, 'aktif'),
(3, 'Sabtu', '14:00:00', '19:00:00', 12, 'aktif');

-- 4. CONTOH CUTI DOKTER
INSERT INTO `cuti_dokter` (`id_cuti`, `id_dokter`, `tanggal_mulai`, `tanggal_selesai`, `alasan`, `status`) VALUES
(1, 1, '2026-11-28', '2026-11-30', 'Seminar Ilmiah Konservasi Gigi Nasional', 'aktif');

-- 5. JADWAL KLINIK
INSERT INTO `jadwal_klinik` (`hari`, `status`, `buka_1`, `tutup_1`, `buka_2`, `tutup_2`) VALUES
('Senin', 'Buka', '08:00:00', '12:00:00', '14:00:00', '21:00:00'),
('Selasa', 'Buka', '08:00:00', '12:00:00', '14:00:00', '21:00:00'),
('Rabu', 'Buka', '08:00:00', '12:00:00', '14:00:00', '21:00:00'),
('Kamis', 'Buka', '08:00:00', '12:00:00', '14:00:00', '21:00:00'),
('Jumat', 'Buka', '08:00:00', '12:00:00', '14:00:00', '21:00:00'),
('Sabtu', 'Buka', '08:00:00', '14:00:00', '15:00:00', '20:00:00'),
('Minggu', 'Buka', '09:00:00', '15:00:00', NULL, NULL);

-- 6. CONTOH LIBUR MENDADAK
INSERT INTO `libur_mendadak` (`tanggal_libur`, `alasan`, `status`) VALUES
('2026-12-25', 'Hari Raya Natal & Tahun Baru', 'nonaktif');

-- 7. LAYANAN KLINIK
INSERT INTO `layanan` (`id_layanan`, `nama_layanan`, `kategori`, `deskripsi`, `estimasi_menit`, `tarif_awal`, `status`) VALUES
(1, 'Scaling & Polishing (Pembersihan Karang Gigi)', 'Preventif', 'Pembersihan karang gigi ultrasonik profesional dan pemolesan untuk nafas segar dan gusi sehat.', 30, 250000.00, 'aktif'),
(2, 'Topical Fluoride Application', 'Preventif', 'Aplikasi gel/varnish fluoride pelindung enamel untuk mencegah gigi berlubang.', 20, 150000.00, 'aktif'),
(3, 'Dental Check-up & Konsultasi', 'Preventif', 'Pemeriksaan komprehensif gigi, gusi, jaringan lunak, dan evaluasi kebiasaan mengunyah.', 20, 100000.00, 'aktif'),
(4, 'Penambalan Gigi Komposit Estetik', 'Konservasi', 'Restorasi tambal sewarna gigi dengan resin komposit nano-hybrid berkualitas tinggi.', 45, 350000.00, 'aktif'),
(5, 'Perawatan Saluran Akar (PSA)', 'Konservasi', 'Perawatan endodontik modern untuk menyelamatkan gigi yang terinfeksi pulpa.', 60, 850000.00, 'aktif'),
(6, 'Crown / Mahkota Tiruan Zirconia', 'Konservasi', 'Mahkota pelindung gigi berbahan zirconia kuat dan tampak sangat alami.', 60, 2500000.00, 'aktif'),
(7, 'Pemasangan Behel Gigi Logam (Metal Braces)', 'Ortodonti', 'Pemasangan kawat gigi logam untuk merapikan susunan gigi dan gigitan rahang.', 90, 6000000.00, 'aktif'),
(8, 'Kontrol & Ganti Kawat Ortodonti', 'Ortodonti', 'Pemeriksaan kemajuan ortodonti berkala dan penggantian power chain / kawat.', 30, 200000.00, 'aktif'),
(9, 'Pencabutan Gigi Dewasa Tanpa Komplikasi', 'Bedah', 'Ekstraksi gigi tetap dengan teknik anestesi lokal minim rasa sakit.', 30, 300000.00, 'aktif'),
(10, 'Operasi Gigi Bungsu (Odontektomi)', 'Bedah', 'Pembedahan pengangkatan gigi geraham bungsu yang miring atau impaksi.', 75, 1800000.00, 'aktif'),
(11, 'Pencabutan Gigi Susu Anak (Ekstraksi Sulung)', 'Anak', 'Pencabutan gigi susu goyang dengan pendekatan ramah anak tanpa jarum suntik menakutkan.', 20, 150000.00, 'aktif'),
(12, 'Fissure Sealant Gigi Anak', 'Anak', 'Penutupan ceruk dan celah gigi geraham anak untuk perlindungan maksimal dari karies.', 30, 200000.00, 'aktif');

-- 8. MASTER ICD-10 GIGI (±30 KODE)
INSERT INTO `icd10` (`kode`, `nama_penyakit`) VALUES
('K00.0', 'Anodontia (Gigi tidak tumbuh/hilang kongenital)'),
('K01.0', 'Gigi tertanam (Embedded teeth)'),
('K01.1', 'Gigi impaksi (Impacted teeth)'),
('K02.0', 'Karies terbatas pada enamel (White spot)'),
('K02.1', 'Karies dentin'),
('K02.2', 'Karies sementum'),
('K02.5', 'Karies dengan pulpa terbuka'),
('K02.9', 'Karies gigi tidak spesifik'),
('K03.0', 'Atrisi gigi berlebih'),
('K03.1', 'Abrasi gigi'),
('K03.2', 'Erosi gigi akibat asam'),
('K03.6', 'Deposit/karang gigi (Kalkulus saliva)'),
('K04.0', 'Pulpitis (Reversible & Irreversible)'),
('K04.1', 'Nekrosis pulpa (Pulpa mati)'),
('K04.4', 'Periodontitis apikalis akut'),
('K04.5', 'Periodontitis apikalis kronis'),
('K04.7', 'Abses periapikal tanpa sinus'),
('K05.0', 'Gingivitis akut'),
('K05.1', 'Gingivitis kronis'),
('K05.2', 'Periodontitis akut'),
('K05.3', 'Periodontitis kronis'),
('K05.4', 'Periodontosis'),
('K07.1', 'Anomali hubungan lengkung gigi rahang'),
('K07.2', 'Anomali posisi gigi (Maloklusi crowding/spacing)'),
('K07.4', 'Maloklusi tidak spesifik'),
('K08.1', 'Kehilangan gigi karena kecelakaan/ekstraksi'),
('K08.4', 'Kehilangan gigi parsial'),
('K08.8', 'Gangguan gusi dan prosessus alveolaris lainnya'),
('K12.0', 'Stomatitis aftosa kambuhan (Sariawan)'),
('K12.1', 'Bentuk lain dari stomatitis');

-- 9. AKUN PASIEN & PROFIL PASIEN (3 AKUN, 5 PROFIL)
INSERT INTO `akun_pasien` (`id_akun`, `email`, `password`, `no_hp`) VALUES
(1, 'ahmad.dahlan@example.com', '$2y$10$TMpkPR0zYyavqThbjlY1CuDOnv.ujM0VC2u51Ilc1l8muoz0toGwq', '081234567890'),
(2, 'rara.amelia@example.com', '$2y$10$TMpkPR0zYyavqThbjlY1CuDOnv.ujM0VC2u51Ilc1l8muoz0toGwq', '085678901234'),
(3, 'dedi.mulyadi@example.com', '$2y$10$TMpkPR0zYyavqThbjlY1CuDOnv.ujM0VC2u51Ilc1l8muoz0toGwq', '087812345678');

INSERT INTO `pasien` (`id_pasien`, `no_rm`, `id_akun`, `hubungan_keluarga`, `nama_pasien`, `nik`, `tgl_lahir`, `jenis_kelamin`, `gol_darah`, `alamat`, `no_hp`, `alergi_obat`, `kondisi_medis`) VALUES
(1, 'RM-2026-0001', 1, 'Diri Sendiri', 'Bpk. Ahmad Dahlan', '3273011204850001', '1985-04-12', 'L', 'O', 'Jl. Merpati No. 14, Bandung', '081234567890', NULL, 'Tidak ada'),
(2, 'RM-2026-0002', 1, 'Anak', 'Kenzo Dahlan', '3273012509190002', '2019-09-25', 'L', 'O', 'Jl. Merpati No. 14, Bandung', '081234567890', 'Penisilin', 'Asma ringan terkontrol'),
(3, 'RM-2026-0003', 2, 'Diri Sendiri', 'Ibu Rara Amelia', '3273026008920003', '1992-08-20', 'P', 'B', 'Jl. Cempaka Putih No. 8, Bandung', '085678901234', NULL, 'Gastritis / Maag'),
(4, 'RM-2026-0004', 2, 'Anak', 'Naura Zhafira', '3273025501210004', '2021-01-15', 'P', 'B', 'Jl. Cempaka Putih No. 8, Bandung', '085678901234', NULL, 'Sehat / Tidak ada'),
(5, 'RM-2026-0005', 3, 'Diri Sendiri', 'Bpk. Dedi Mulyadi', '3273031102780005', '1978-02-11', 'L', 'A', 'Jl. Anggrek Indah No. 22, Bandung', '087812345678', 'Amoxicillin, Novalgin', 'Hipertensi esensial (rutin Amlodipine 5mg)');

-- 10. WALI PASIEN (UNTUK 2 ANAK)
INSERT INTO `wali_pasien` (`id_pasien`, `nama_wali`, `hubungan`, `kontak`, `pekerjaan`, `alamat`) VALUES
(2, 'Bpk. Ahmad Dahlan', 'Ayah Kandung', '081234567890', 'Wiraswasta', 'Jl. Merpati No. 14, Bandung'),
(4, 'Ibu Rara Amelia', 'Ibu Kandung', '085678901234', 'Akuntan Perusahaan', 'Jl. Cempaka Putih No. 8, Bandung');

-- 11. INVENTARIS OBAT (15 ITEM, DENGAN STOK TIPIS & EXP DEMO)
INSERT INTO `obat` (`id_obat`, `nama_obat`, `kategori`, `satuan`, `harga_beli`, `harga_jual`, `stok`, `stok_minimum`, `exp_date`) VALUES
(1, 'Amoxicillin 500mg Kaplet', 'Antibiotik', 'Strip (10 kaplet)', 7000.00, 12000.00, 45, 10, '2027-10-15'),
(2, 'Asam Mefenamat 500mg Tablet', 'Antinyeri', 'Strip (10 tablet)', 6000.00, 10000.00, 60, 15, '2027-08-20'),
(3, 'Paracetamol 500mg Tablet', 'Antinyeri', 'Strip (10 tablet)', 4000.00, 7500.00, 120, 20, '2028-01-10'),
(4, 'Clindamycin 300mg Kapsul', 'Antibiotik', 'Strip (10 kapsul)', 18000.00, 28000.00, 8, 10, '2027-06-30'), -- STOK KRITIS
(5, 'Cefadroxil 500mg Kapsul', 'Antibiotik', 'Strip (10 kapsul)', 15000.00, 24000.00, 30, 10, '2027-11-20'),
(6, 'Ibuprofen 400mg Tablet', 'Antinyeri', 'Strip (10 tablet)', 5500.00, 9500.00, 55, 15, '2027-09-12'),
(7, 'Dexamethasone 0.5mg Tablet', 'Lainnya', 'Strip (10 tablet)', 3500.00, 6000.00, 40, 10, '2027-05-18'),
(8, 'Kalium Diklofenak 50mg Tablet', 'Antinyeri', 'Strip (10 tablet)', 9000.00, 15000.00, 4, 10, '2026-12-10'), -- STOK KRITIS & EXP DEKAT
(9, 'Chlorhexidine Gluconate 0.2% Gargle 150ml', 'Lainnya', 'Botol', 22000.00, 35000.00, 25, 5, '2027-12-01'),
(10, 'Aloclair Plus Oral Gel 8ml', 'Lainnya', 'Tube', 65000.00, 90000.00, 12, 5, '2027-07-15'),
(11, 'Metronidazole 500mg Tablet', 'Antibiotik', 'Strip (10 tablet)', 7500.00, 13000.00, 35, 10, '2027-04-10'),
(12, 'Vitamin C 500mg Non-Acidic Tablet', 'Vitamin', 'Botol (30 tab)', 30000.00, 48000.00, 18, 5, '2028-02-28'),
(13, 'Caviplex Multivitamin Kaplet', 'Vitamin', 'Strip (10 kaplet)', 8500.00, 14000.00, 50, 10, '2027-12-15'),
(14, 'Cataflam Fast 50mg Sachet', 'Antinyeri', 'Sachet', 8000.00, 14000.00, 40, 15, '2027-08-05'),
(15, 'Kenalog in Orabase 5g Ointment', 'Lainnya', 'Tube', 55000.00, 78000.00, 3, 5, '2026-12-05'); -- STOK KRITIS & EXP DEKAT

-- 12. DENTAL SUPPLY (8 ITEM)
INSERT INTO `dental_supply` (`id_supply`, `nama_barang`, `satuan`, `stok`, `stok_minimum`, `exp_date`) VALUES
(1, 'Surgical Mask 3-Ply Earloop Medical', 'Box (50 pcs)', 30, 5, '2028-05-20'),
(2, 'Nitrile Dental Gloves Non-Powder Size M', 'Box (100 pcs)', 25, 5, '2028-04-10'),
(3, 'Disposable Dental Bibs Waterproof', 'Pack (125 pcs)', 15, 3, '2029-01-01'),
(4, 'Terumo Spuit 3cc Luer Lock Needle', 'Box (100 pcs)', 18, 4, '2028-09-15'),
(5, 'Citoject Dental Anesthesia Needle 30G Short', 'Box (100 pcs)', 2, 5, '2027-03-10'), -- STOK KRITIS
(6, 'Bausch Articulating Paper 200 Blue/Red', 'Buku (300 strip)', 8, 2, '2029-12-31'),
(7, 'Dentara Premium Cotton Roll Absorbing', 'Pack (1000 pcs)', 12, 3, '2028-10-25'),
(8, 'Disposable Saliva Ejector Suction Tips', 'Pack (100 pcs)', 22, 5, '2028-11-15');

-- 13. MASTER KARTU GAMIFIKASI & KOLEKSI
INSERT INTO `koleksi_kartu` (`id_koleksi`, `nama_seri`, `deskripsi`, `total_kartu`) VALUES
(1, 'Seri Penjaga Senyum Ceria (Smiling Guardians)', 'Koleksi kartu edukatif ramah anak DENTARA untuk memotivasi anak merawat kebersihan gigi dan berani ke dokter gigi.', 6);

INSERT INTO `master_kartu` (`id_kartu`, `nama_kartu`, `deskripsi_edukatif`, `rarity`, `poin_diperlukan`) VALUES
(1, 'Gigi Pahlawan Super (Super Tooth)', 'Gigi yang sangat kuat dan kebal dari kuman karena rajin disikat 2 kali sehari pagi dan malam!', 'Legendary', 500),
(2, 'Ksatria Sikat Gigi (Brush Knight)', 'Ksatria pemberani bersenjatakan bulu sikat lembut yang menyapu bersih sisa makanan di setiap sudut gigi.', 'Epic', 300),
(3, 'Pelindung Fluoride (Fluoride Shield)', 'Perisai ajaib mineral fluoride yang membuat enamel gigi menjadi kokoh dan tahan terhadap asam makanan manis.', 'Rare', 200),
(4, 'Detektif Benang Gigi (Floss Detective)', 'Detektif lincah yang bertugas melacak kuman tersembunyi di sela-sela gigi yang sempit.', 'Rare', 200),
(5, 'Apel Renyah Sahabat Gigi', 'Makanan sehat segar kaya serat alami yang membantu membersihkan plak dan menguatkan gusi saat dikunyah.', 'Common', 100),
(6, 'Si Gigi Bintang Berkilau', 'Gigi putih cerah bersinar yang bangga karena selalu rutin diperiksa ke dokter gigi DENTARA setiap 6 bulan!', 'Common', 100);

-- 14. GAMIFIKASI POIN PASIEN AWAL
INSERT INTO `gamifikasi_poin` (`id_pasien`, `total_poin`, `jumlah_kunjungan_selesai`) VALUES
(2, 250, 2),
(4, 120, 1);

INSERT INTO `koleksi_kartu_pasien` (`id_pasien`, `id_kartu`) VALUES
(2, 5),
(2, 6),
(4, 5);

-- 15. ARTIKEL EDUKASI
INSERT INTO `artikel` (`id_artikel`, `judul`, `slug`, `isi`, `penulis`, `status`) VALUES
(1, 'Rahasia Senyum Bebas Gigi Berlubang: Cara Sikat Gigi yang Benar', 'rahasia-senyum-bebas-gigi-berlubang-cara-sikat-gigi-yang-benar', '<p>Banyak orang mengira menyikat gigi dengan keras akan membuat gigi lebih bersih, padahal hal ini justru berisiko mengikis lapisan enamel dan melukai gusi. Gunakan sikat gigi berbulu halus (soft) dan sikat dengan gerakan memutar lembut selama minimal 2 menit. Jangan lupa menyikat bagian dalam gigi dan lidah untuk menghilangkan bakteri penyebab bau mulut.</p>', 'drg. Siti Aminah', 'publish'),
(2, 'Kapan Sebaiknya Anak Mulai Dibawa ke Dokter Gigi?', 'kapan-sebaiknya-anak-mulai-dibawa-ke-dokter-gigi', '<p>Asosiasi Dokter Gigi Anak Dunia menganjurkan anak berkunjung ke dokter gigi saat gigi pertamanya mulai tumbuh atau selambat-lambatnya sebelum berusia 1 tahun. Kunjungan dini sangat penting untuk membiasakan anak dengan suasana klinik serta memberikan panduan nutrisi pencegahan karies botol susu (early childhood caries).</p>', 'drg. Anita Wijaya, Sp.KGA', 'publish'),
(3, 'Perawatan Saluran Akar vs Cabut Gigi: Mana yang Lebih Baik?', 'perawatan-saluran-akar-vs-cabut-gigi-mana-yang-lebih-baik', '<p>Mempertahankan gigi asli selalu menjadi prioritas utama para dokter gigi. Dengan teknologi endodontik modern, Perawatan Saluran Akar (PSA) kini hampir tidak menimbulkan rasa sakit dan dapat menyelamatkan gigi dari pencabutan. Gigi asli yang dipertahankan tetap menjaga fungsi kunyah alami dan mencegah pergeseran gigi tetangga.</p>', 'drg. Budi Santoso, Sp.KG', 'publish'),
(4, 'Mengenal Karang Gigi dan Alasan Scaling Wajib Tiap 6 Bulan', 'mengenal-karang-gigi-dan-alasan-scaling-wajib-tiap-6-bulan', '<p>Karang gigi (kalkulus) adalah plak bakteri yang mengeras akibat mineral air liur dan tidak bisa hilang hanya dengan menyikat gigi biasa. Jika dibiarkan menumpuk, karang gigi dapat memicu gingivitis (gusi berdarah) hingga periodontitis yang menyebabkan gigi goyang dan copot sendiri.</p>', 'drg. Hendra Pratama, Sp.Ort', 'publish');

-- 16. PROMO BANNER
INSERT INTO `promo_banner` (`id_promo`, `judul`, `deskripsi`, `status`, `tanggal_mulai`, `tanggal_selesai`) VALUES
(1, 'Promo Merdeka Senyum Sehat: Diskon 20% Scaling & Fluoridasi', 'Nikmati pembersihan karang gigi menyeluruh dan aplikasi fluoride pelindung gigi dengan tarif spesial selama bulan ini!', 'aktif', '2026-11-01', '2026-12-31'),
(2, 'Paket Pemeriksaan Gigi Ceria Si Kecil', 'Konsultasi ramah anak + pemolesan gigi + bonus kartu koleksi Denta eksklusif untuk setiap pendaftaran baru anak.', 'aktif', '2026-11-01', '2026-12-31');

-- 17. FAQ
INSERT INTO `faq` (`id_faq`, `pertanyaan`, `jawaban`, `urutan`) VALUES
(1, 'Apakah klinik DENTARA melayani pembayaran asuransi atau BPJS?', 'DENTARA melayani pembayaran mandiri, asuransi swasta reimbursement, dan koordinasi asuransi komersial tertentu. Kami menyediakan kuitansi resmi berstempel dan rincian diagnosa medis untuk klaim asuransi Anda.', 1),
(2, 'Bagaimana jika anak saya takut atau cemas ke dokter gigi?', 'Klinik kami didesain khusus dengan ruang bermain anak, dokter spesialis anak yang sangat ramah, serta modul gamifikasi Denta yang mengubah kunjungan menjadi petualangan berburu kartu koleksi.', 2),
(3, 'Apakah saya harus membuat janji terlebih dahulu sebelum datang?', 'Kami sangat menyarankan melakukan reservasi online melalui sistem DENTARA agar mendapatkan kepastian jadwal, nomor antrean, dan estimasi waktu tunggu tanpa perlu mengantre lama di ruang tunggu.', 3);

-- 18. TEMPLATE NOTIFIKASI
INSERT INTO `template_notifikasi` (`id_tn`, `judul`, `kategori`, `isi`) VALUES
(1, 'Selamat Ulang Tahun dari DENTARA', 'Ulang Tahun', 'Selamat ulang tahun, [NamaPasien]! Semoga senyum indah Anda selalu memancarkan kebahagiaan. Dapatkan voucher pembersihan karang gigi spesial di klinik DENTARA.'),
(2, 'Pengingat Jadwal Kontrol Perawatan', 'Pengingat Kontrol', 'Halo [NamaPasien], kami mengingatkan jadwal kontrol perawatan Anda bersama [NamaDokter] pada tanggal [TanggalKontrol]. Mohon konfirmasi kehadiran Anda.'),
(3, 'Pengingat Periksa Rutin 6 Bulan', 'Informasi', 'Halo [NamaPasien], sudah 6 bulan sejak kunjungan terakhir Anda. Mari jadwalkan pembersihan karang gigi dan periksa berkala agar gigi tetap sehat.'),
(4, 'Instruksi Pasca Tindakan Ekstraksi', 'Informasi', 'Halo [NamaPasien], ingat untuk menggigit tampon kasa selama 30-45 menit, hindari berkumur terlalu keras, dan jangan menyedot menggunakan sedotan.');

-- 19. PENGATURAN KLINIK & PEMBAYARAN
INSERT INTO `pengaturan_klinik` (`id`, `nama_klinik`, `telepon`, `whatsapp`, `email`, `alamat`, `latitude`, `longitude`, `biaya_konsultasi`, `biaya_admin`) VALUES
(1, 'DENTARA Dental Care & Clinic', '022-87654321', '6281234567890', 'info@dentara.id', 'Jl. Ir. H. Juanda No. 128, Dago, Bandung, Jawa Barat 40135', '-6.885623', '107.613942', 50000.00, 15000.00);

INSERT INTO `pengaturan_pembayaran` (`id`, `nama_bank`, `no_rekening`, `atas_nama`, `status`) VALUES
(1, 'Bank Central Asia (BCA)', '8420198822', 'KLINIK DENTARA UTAMA PT', 'aktif'),
(2, 'Bank Mandiri', '1310088921001', 'KLINIK DENTARA UTAMA PT', 'aktif'),
(3, 'QRIS DENTARA (All Payment)', 'NMID: ID102026889912', 'DENTARA DENTAL CARE', 'aktif');

-- 20. HISTORI TRANSAKSI CONTOH (AGAR GRAFIK & ANALITIK TIDAK KOSONG)
-- Reservasi lampau yang selesai
INSERT INTO `reservasi` (`id_reservasi`, `kode_booking`, `id_pasien`, `nama_tamu`, `no_hp_tamu`, `id_dokter`, `id_layanan`, `tanggal`, `jam`, `keluhan`, `status`, `sumber`) VALUES
(1, 'DNT001', 1, NULL, NULL, 1, 1, '2026-11-10', '09:30:00', 'Karang gigi menumpuk di gigi bawah depan dan gusi agak berdarah saat sikat gigi.', 'selesai', 'online'),
(2, 'DNT002', 2, NULL, NULL, 3, 11, '2026-11-12', '15:30:00', 'Gigi seri depan atas goyang dan mengganjal saat makan.', 'selesai', 'online'),
(3, 'DNT003', 3, NULL, NULL, 2, 4, '2026-11-14', '10:00:00', 'Gigi geraham belakang ngilu saat minum dingin, ada lubang hitam.', 'selesai', 'online'),
(4, 'DNT004', 5, NULL, NULL, 1, 5, '2026-11-16', '11:00:00', 'Gigi geraham bawah sakit berdenyut di malam hari sampai kepala ikut pusing.', 'selesai', 'walkin');

-- Antrian lampau
INSERT INTO `antrian` (`id_antrian`, `id_reservasi`, `id_dokter`, `tanggal`, `nomor_antrian`, `prioritas`, `status`, `waktu_checkin`, `waktu_panggil`, `waktu_mulai`, `waktu_selesai`) VALUES
(1, 1, 1, '2026-11-10', 1, 0, 'selesai', '2026-11-10 09:15:00', '2026-11-10 09:30:00', '2026-11-10 09:35:00', '2026-11-10 10:10:00'),
(2, 2, 3, '2026-11-12', 1, 1, 'selesai', '2026-11-12 15:10:00', '2026-11-12 15:25:00', '2026-11-12 15:30:00', '2026-11-12 15:55:00'),
(3, 3, 2, '2026-11-14', 1, 0, 'selesai', '2026-11-14 09:45:00', '2026-11-14 10:00:00', '2026-11-14 10:05:00', '2026-11-14 10:45:00'),
(4, 4, 1, '2026-11-16', 1, 0, 'selesai', '2026-11-16 10:40:00', '2026-11-16 10:55:00', '2026-11-16 11:00:00', '2026-11-16 11:50:00');

-- Rekam medis SOAP
INSERT INTO `rekam_medis` (`id_rekam`, `id_antrian`, `id_pasien`, `id_dokter`, `tanggal_periksa`, `subjektif`, `objektif`, `asesmen`, `plan`, `catatan_khusus`) VALUES
(1, 1, 1, 1, '2026-11-10', 'Pasien mengeluhkan karang gigi tebal di bagian lingual gigi anterior rahang bawah, kadang gusi berdarah saat menyikat gigi.', 'Kalkulus supragingiva dan subgingiva regio anterior mandibula, gingiva hiperemis ringan, sondasi (-), perkusi (-).', 'Gingivitis marginalis kronis et causa kalkulus saliva (K05.1)', 'Pro scaling ultrasonik menyeluruh rahang atas dan rahang bawah, pemolesan pumice.', 'Anjurkan kontrol 6 bulan.'),
(2, 2, 2, 3, '2026-11-12', 'Anak mengeluh gigi depan atas goyang dan sakit saat menggigit kerupuk.', 'Gigi 51 persistensi derajat kegoyangan 3, resorpsi akar fisiologis sempurna, mukosa normal.', 'Persistensi gigi susu 51 (K00.0)', 'Ekstraksi gigi 51 dengan anestesi topikal benzocaine gel rasa stroberi.', 'Anak sangat kooperatif, diberikan kartu gamifikasi Denta.'),
(3, 3, 3, 2, '2026-11-14', 'Pasien merasa linu di gigi geraham kanan bawah saat meminum es atau makanan manis.', 'Gigi 46 karies media pada permukaan oklusal, sondasi (+), perkusi (-), tes thermal dingin (+) linu menghilang cepat.', 'Karies dentin gigi 46 (K02.1)', 'Preparasi kavitas kelas I Black dan penambalan komposit nano-hybrid bulk fill shade A2.', 'Instruksi tidak makan keras selama 1 jam.'),
(4, 4, 5, 1, '2026-11-16', 'Nyeri berdenyut spontan pada geraham kiri bawah terutama saat berbaring di malam hari.', 'Gigi 36 karies profunda dengan perforasi pulpa, perkusi (+), palpasi (-), tes vitalitas (+/-). Tekanan darah 135/85 mmHg.', 'Pulpitis ireversibel simtomatik gigi 36 (K04.0)', 'Open bur, ekstirpasi pulpa, dressing cavit + cresophen, medikasi antinyeri dan antibiotik.', 'Pasien ada riwayat hipertensi dan alergi Amoxicillin! Hindari golongan penisilin.');

-- Rekam diagnosis ICD-10
INSERT INTO `rekam_diagnosis` (`id_rekam`, `kode_icd10`) VALUES
(1, 'K05.1'),
(2, 'K00.0'),
(3, 'K02.1'),
(4, 'K04.0');

-- Rekam tindakan
INSERT INTO `rekam_tindakan` (`id_rekam`, `id_layanan`, `nomor_gigi`, `jumlah`, `tarif_saat_itu`) VALUES
(1, 1, 'Seluruh', 1, 250000.00),
(2, 11, '51', 1, 150000.00),
(3, 4, '46', 1, 350000.00),
(4, 5, '36', 1, 850000.00);

-- Odontogram
INSERT INTO `odontogram` (`id_pasien`, `id_rekam`, `nomor_gigi`, `permukaan`, `kondisi`) VALUES
(1, 1, '31', 'seluruh', 'sehat'),
(1, 1, '41', 'seluruh', 'sehat'),
(2, 2, '51', 'seluruh', 'hilang'),
(3, 3, '46', 'oklusal', 'tambalan'),
(5, 4, '36', 'oklusal', 'saluran_akar');

-- Resep dokter untuk pasien 4
INSERT INTO `resep` (`id_resep`, `id_rekam`, `status`, `catatan_apoteker`) VALUES
(1, 4, 'selesai', 'Obat telah disiapkan dan diserahkan ke pasien dengan edukasi.');

INSERT INTO `resep_detail` (`id_resep`, `id_obat`, `jumlah`, `dosis`, `aturan_pakai`) VALUES
(1, 4, 1, '300mg', '3 kali sehari 1 kapsul sesudah makan (Habiskan)'), -- Clindamycin untuk alergi penisilin
(1, 2, 1, '500mg', '3 kali sehari 1 tablet bila sakit sesudah makan');

-- Treatment plan
INSERT INTO `treatment_plan` (`id_pasien`, `id_rekam`, `id_dokter`, `rencana`, `tanggal_kontrol_berikut`, `status`) VALUES
(5, 4, 1, 'Kontrol PSA tahap 2: preparasi biomekanis saluran akar dan sterilisasi', '2026-11-23', 'direncanakan'),
(3, 3, 2, 'Kontrol tambalan dan evaluasi oklusi', '2026-11-28', 'direncanakan');

-- Tagihan & Tagihan detail
INSERT INTO `tagihan` (`id_tagihan`, `id_antrian`, `id_pasien`, `biaya_konsultasi`, `biaya_tindakan`, `biaya_obat`, `biaya_admin`, `total_biaya`, `ditanggung_asuransi`, `ditanggung_pasien`, `status`) VALUES
(1, 1, 1, 50000.00, 250000.00, 0.00, 15000.00, 315000.00, 0.00, 315000.00, 'lunas'),
(2, 2, 2, 50000.00, 150000.00, 0.00, 15000.00, 215000.00, 0.00, 215000.00, 'lunas'),
(3, 3, 3, 50000.00, 350000.00, 0.00, 15000.00, 415000.00, 200000.00, 215000.00, 'lunas'),
(4, 4, 5, 50000.00, 850000.00, 38000.00, 15000.00, 953000.00, 0.00, 953000.00, 'lunas');

INSERT INTO `tagihan_detail` (`id_tagihan`, `jenis`, `deskripsi`, `qty`, `harga`, `subtotal`) VALUES
(1, 'konsultasi', 'Biaya Konsultasi & Dental Check-Up', 1, 50000.00, 50000.00),
(1, 'tindakan', 'Scaling & Polishing (Pembersihan Karang Gigi)', 1, 250000.00, 250000.00),
(1, 'admin', 'Biaya Administrasi & Sterilisasi Alat', 1, 15000.00, 15000.00),
(2, 'konsultasi', 'Biaya Konsultasi & Dental Check-Up', 1, 50000.00, 50000.00),
(2, 'tindakan', 'Pencabutan Gigi Susu Anak (Ekstraksi Sulung)', 1, 15000.000, 150000.00),
(2, 'admin', 'Biaya Administrasi & Sterilisasi Alat', 1, 15000.00, 15000.00),
(3, 'konsultasi', 'Biaya Konsultasi & Dental Check-Up', 1, 50000.00, 50000.00),
(3, 'tindakan', 'Penambalan Gigi Komposit Estetik', 1, 350000.00, 350000.00),
(3, 'admin', 'Biaya Administrasi & Sterilisasi Alat', 1, 15000.00, 15000.00),
(4, 'konsultasi', 'Biaya Konsultasi & Dental Check-Up', 1, 50000.00, 50000.00),
(4, 'tindakan', 'Perawatan Saluran Akar (PSA)', 1, 850000.00, 850000.00),
(4, 'obat', 'Clindamycin 300mg Kapsul (1 Strip)', 1, 28000.00, 28000.00),
(4, 'obat', 'Asam Mefenamat 500mg Tablet (1 Strip)', 1, 10000.00, 10000.00),
(4, 'admin', 'Biaya Administrasi & Sterilisasi Alat', 1, 15000.00, 15000.00);

-- Shift kasir lampau
INSERT INTO `shift_kasir` (`id_shift`, `id_kasir`, `waktu_buka`, `waktu_tutup`, `kas_awal`, `total_sistem`, `total_fisik`, `selisih`, `status`) VALUES
(1, 7, '2026-11-10 08:00:00', '2026-11-10 16:00:00', 300000.00, 315000.00, 315000.00, 0.00, 'tutup'),
(2, 7, '2026-11-12 08:00:00', '2026-11-12 16:00:00', 300000.00, 215000.00, 215000.00, 0.00, 'tutup'),
(3, 7, '2026-11-14 08:00:00', '2026-11-14 16:00:00', 300000.00, 215000.00, 215000.00, 0.00, 'tutup'),
(4, 7, '2026-11-16 08:00:00', '2026-11-16 16:00:00', 300000.00, 953000.00, 953000.00, 0.00, 'tutup');

-- Pembayaran kuitansi
INSERT INTO `pembayaran` (`id_pembayaran`, `id_tagihan`, `id_kasir`, `jumlah_dibayar`, `metode`, `tanggal_bayar`, `no_kuitansi`, `id_shift`) VALUES
(1, 1, 7, 315000.00, 'qris', '2026-11-10 10:20:00', 'KW/202611/0001', 1),
(2, 2, 7, 215000.00, 'tunai', '2026-11-12 16:05:00', 'KW/202611/0002', 2),
(3, 3, 7, 215000.00, 'debit', '2026-11-14 10:55:00', 'KW/202611/0003', 3),
(4, 4, 7, 953000.00, 'transfer', '2026-11-16 12:00:00', 'KW/202611/0004', 4);

-- Fee sharing rekap bulan berjalan
INSERT INTO `fee_sharing` (`id_fee`, `id_dokter`, `periode_bulan`, `jumlah_pasien`, `total_pendapatan_tindakan`, `persen_fee`, `nominal_fee`) VALUES
(1, 1, '2026-11', 2, 1100000.00, 45.00, 495000.00),
(2, 2, '2026-11', 1, 350000.00, 40.00, 140000.00),
(3, 3, '2026-11', 1, 150000.00, 50.00, 75000.00);

-- Notifikasi awal untuk pasien
INSERT INTO `pesan_notifikasi` (`id_pesan`, `id_pasien`, `judul`, `isi`, `status`, `tanggal_kirim`) VALUES
(1, 1, 'Selamat Datang di DENTARA!', 'Terima kasih telah bergabung dengan DENTARA. Kesehatan gigi Anda adalah senyum terindah bagi kami.', 'dibaca', '2026-11-10 09:00:00'),
(2, 2, 'Hebat, Kenzo dapat Kartu Gigi Baru!', 'Selamat! Kenzo telah menyelesaikan kunjungan gigi dan mendapatkan kartu Si Gigi Bintang Berkilau!', 'belum_dibaca', '2026-11-12 16:00:00'),
(3, 5, 'Pengingat Kontrol Perawatan Saluran Akar', 'Bpk. Dedi, jangan lupa jadwal kontrol PSA gigi 36 Anda pada hari Senin, 23 November 2026 bersama drg. Budi.', 'belum_dibaca', '2026-11-16 12:30:00');

-- RESERVASI HARI INI (UNTUK DEMO LIVE ANTREAN & CHECKIN)
INSERT INTO `reservasi` (`id_reservasi`, `kode_booking`, `id_pasien`, `nama_tamu`, `no_hp_tamu`, `id_dokter`, `id_layanan`, `tanggal`, `jam`, `keluhan`, `status`, `sumber`) VALUES
(5, 'DNT789', 1, NULL, NULL, 1, 1, CURRENT_DATE(), '09:30:00', 'Pemeriksaan lanjutan dan pemolesan ulang gigi depan.', 'dikonfirmasi', 'online'),
(6, 'DNT888', 3, NULL, NULL, 2, 4, CURRENT_DATE(), '10:00:00', 'Tambalan gigi geraham atas ada yang terasa kasar.', 'menunggu', 'online'),
(7, 'DNT999', 4, NULL, NULL, 3, 2, CURRENT_DATE(), '15:00:00', 'Topical fluoride untuk Naura agar gigi terlindungi.', 'dikonfirmasi', 'online');

