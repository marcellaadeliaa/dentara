-- ==============================================================
-- DENTARA - Sistem Informasi Manajemen Klinik Gigi Berbasis Web
-- Database Schema (MySQL 8.0+ / MariaDB)
-- Character Set: utf8mb4, Collation: utf8mb4_unicode_ci
-- ==============================================================

CREATE DATABASE IF NOT EXISTS `dentara` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `dentara`;

-- --------------------------------------------------------------
-- 1. TABEL PENGGUNA & HAK AKSES (RBAC)
-- --------------------------------------------------------------
DROP TABLE IF EXISTS `doctor_fees`;
DROP TABLE IF EXISTS `billing_items`;
DROP TABLE IF EXISTS `billings`;
DROP TABLE IF EXISTS `stock_movements`;
DROP TABLE IF EXISTS `prescription_items`;
DROP TABLE IF EXISTS `prescriptions`;
DROP TABLE IF EXISTS `medicine_batches`;
DROP TABLE IF EXISTS `medicines`;
DROP TABLE IF EXISTS `medical_record_treatments`;
DROP TABLE IF EXISTS `medical_record_diagnoses`;
DROP TABLE IF EXISTS `treatments`;
DROP TABLE IF EXISTS `icd10_codes`;
DROP TABLE IF EXISTS `medical_records`;
DROP TABLE IF EXISTS `queues`;
DROP TABLE IF EXISTS `appointments`;
DROP TABLE IF EXISTS `doctor_schedules`;
DROP TABLE IF EXISTS `doctors`;
DROP TABLE IF EXISTS `patients`;
DROP TABLE IF EXISTS `faqs`;
DROP TABLE IF EXISTS `promotions`;
DROP TABLE IF EXISTS `clinic_settings`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `role` ENUM('admin', 'doctor', 'receptionist', 'pharmacist', 'cashier', 'patient') NOT NULL,
    `avatar` VARCHAR(255) DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `last_login` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_users_role` (`role`),
    INDEX `idx_users_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------
-- 2. TABEL DATA DOKTER & JADWAL PRAKTIK
-- --------------------------------------------------------------
CREATE TABLE `doctors` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL UNIQUE,
    `sip_number` VARCHAR(100) NOT NULL, -- Surat Izin Praktik
    `specialization` VARCHAR(100) NOT NULL DEFAULT 'Dokter Gigi Umum',
    `consultation_fee` DECIMAL(12,2) NOT NULL DEFAULT 50000.00,
    `default_fee_percentage` DECIMAL(5,2) NOT NULL DEFAULT 40.00, -- Default persentase bagi hasil
    `room_name` VARCHAR(50) DEFAULT 'Poli Gigi 1',
    `bio` TEXT DEFAULT NULL,
    `photo` VARCHAR(255) DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_doctors_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `doctor_schedules` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `doctor_id` INT NOT NULL,
    `day_of_week` ENUM('Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu') NOT NULL,
    `start_time` TIME NOT NULL,
    `end_time` TIME NOT NULL,
    `quota` INT NOT NULL DEFAULT 15,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_schedules_day` (`day_of_week`, `is_active`),
    CONSTRAINT `fk_schedules_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------
-- 3. TABEL DATA PASIEN
-- --------------------------------------------------------------
CREATE TABLE `patients` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT DEFAULT NULL UNIQUE, -- Terhubung jika pasien registrasi portal
    `medical_record_number` VARCHAR(20) NOT NULL UNIQUE, -- Contoh: RM-202611-0001
    `nik` VARCHAR(16) NOT NULL UNIQUE,
    `full_name` VARCHAR(100) NOT NULL,
    `gender` ENUM('L', 'P') NOT NULL,
    `birth_place` VARCHAR(50) DEFAULT NULL,
    `birth_date` DATE NOT NULL,
    `blood_type` ENUM('A', 'B', 'AB', 'O', '-') DEFAULT '-',
    `phone` VARCHAR(20) NOT NULL,
    `email` VARCHAR(100) DEFAULT NULL,
    `address` TEXT NOT NULL,
    `occupation` VARCHAR(50) DEFAULT NULL,
    `allergies` TEXT DEFAULT NULL, -- Alergi obat/makanan
    `medical_history` TEXT DEFAULT NULL, -- Riwayat hipertensi, DM, jantung, asma, kelainan darah
    `emergency_contact_name` VARCHAR(100) DEFAULT NULL,
    `emergency_contact_phone` VARCHAR(20) DEFAULT NULL,
    `emergency_contact_relation` VARCHAR(50) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_patients_rm` (`medical_record_number`),
    INDEX `idx_patients_nik` (`nik`),
    INDEX `idx_patients_name` (`full_name`),
    CONSTRAINT `fk_patients_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------
-- 4. TABEL JANJI TEMU / ONLINE BOOKING
-- --------------------------------------------------------------
CREATE TABLE `appointments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `booking_code` VARCHAR(20) NOT NULL UNIQUE, -- Contoh: BK-20261117-001
    `patient_id` INT DEFAULT NULL,
    `patient_name` VARCHAR(100) NOT NULL,
    `patient_phone` VARCHAR(20) NOT NULL,
    `patient_email` VARCHAR(100) DEFAULT NULL,
    `doctor_id` INT NOT NULL,
    `appointment_date` DATE NOT NULL,
    `estimated_time` TIME DEFAULT NULL,
    `complaint` TEXT NOT NULL, -- Keluhan awal gigi
    `is_new_patient` TINYINT(1) NOT NULL DEFAULT 0,
    `status` ENUM('pending', 'confirmed', 'checked_in', 'cancelled', 'completed') NOT NULL DEFAULT 'pending',
    `cancellation_reason` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_appointments_date` (`appointment_date`, `status`),
    INDEX `idx_appointments_booking_code` (`booking_code`),
    CONSTRAINT `fk_appointments_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_appointments_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------
-- 5. TABEL ANTREAN KLINIK (QUEUE TRACKER)
-- --------------------------------------------------------------
CREATE TABLE `queues` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `queue_number` VARCHAR(10) NOT NULL, -- Contoh: A-001
    `queue_date` DATE NOT NULL,
    `queue_type` ENUM('online', 'walk_in') NOT NULL DEFAULT 'walk_in',
    `appointment_id` INT DEFAULT NULL,
    `patient_id` INT NOT NULL,
    `doctor_id` INT NOT NULL,
    `status` ENUM('waiting', 'calling', 'in_consultation', 'pharmacy', 'cashier', 'completed', 'cancelled', 'skipped') NOT NULL DEFAULT 'waiting',
    `priority` ENUM('normal', 'urgent') NOT NULL DEFAULT 'normal',
    `called_at` DATETIME DEFAULT NULL,
    `consultation_start` DATETIME DEFAULT NULL,
    `consultation_end` DATETIME DEFAULT NULL,
    `completed_at` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_queues_lookup` (`queue_date`, `status`, `doctor_id`),
    INDEX `idx_queues_num` (`queue_date`, `queue_number`),
    CONSTRAINT `fk_queues_appointment` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_queues_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_queues_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------
-- 6. TABEL MASTER ICD-10 & TINDAKAN GIGI
-- --------------------------------------------------------------
CREATE TABLE `icd10_codes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(10) NOT NULL UNIQUE,
    `name_en` VARCHAR(255) NOT NULL,
    `name_id` VARCHAR(255) NOT NULL,
    `category` VARCHAR(100) NOT NULL DEFAULT 'Kesehatan Gigi & Mulut',
    `is_common` TINYINT(1) NOT NULL DEFAULT 1,
    INDEX `idx_icd10_code` (`code`),
    INDEX `idx_icd10_name` (`name_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `treatments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(20) NOT NULL UNIQUE, -- TDK-001
    `name` VARCHAR(150) NOT NULL,
    `category` ENUM('Preventif & Pembersihan', 'Konservasi / Penambalan', 'Endodontik / Saluran Akar', 'Bedah Mulut / Pencabutan', 'Periodonsia', 'Prostodonsia / Gigi Tiruan', 'Ortodonsia', 'Lain-lain') NOT NULL,
    `description` TEXT DEFAULT NULL,
    `price` DECIMAL(12,2) NOT NULL,
    `default_doctor_fee_percent` DECIMAL(5,2) NOT NULL DEFAULT 40.00,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_treatments_active` (`is_active`, `category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------
-- 7. TABEL REKAM MEDIS ELEKTRONIK (EMR - FORMAT SOAP TEKS)
-- --------------------------------------------------------------
CREATE TABLE `medical_records` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `record_number` VARCHAR(25) NOT NULL UNIQUE, -- EMR-202611-0001
    `queue_id` INT NOT NULL UNIQUE,
    `patient_id` INT NOT NULL,
    `doctor_id` INT NOT NULL,
    `record_date` DATE NOT NULL,
    
    -- Tanda Vital
    `blood_pressure` VARCHAR(15) DEFAULT NULL, -- e.g. 120/80 mmHg
    `pulse_rate` INT DEFAULT NULL, -- bpm
    `respiratory_rate` INT DEFAULT NULL, -- rpm
    `body_temperature` DECIMAL(4,1) DEFAULT NULL, -- Celcius
    
    -- Format SOAP (Pencatatan Berbasis Teks Terstruktur)
    `subjective` TEXT NOT NULL, -- Keluhan utama, riwayat penyakit sekarang, lokasi nyeri, durasi
    `objective` TEXT NOT NULL, -- Pemeriksaan ekstraoral, intraoral, perkusi, palpasi, sondasi, mobilitas
    `dental_notes` TEXT DEFAULT NULL, -- Deskripsi teks kondisi elemen gigi (cth: Gigi 36 karies media MO, Gigi 46 sisa akar)
    `assessment` TEXT NOT NULL, -- Kesimpulan / ringkasan diagnosis dokter
    `plan` TEXT NOT NULL, -- Rencana perawatan, anjuran, edukasi pasien pasca tindakan
    
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_mr_patient` (`patient_id`),
    INDEX `idx_mr_doctor` (`doctor_id`),
    INDEX `idx_mr_date` (`record_date`),
    CONSTRAINT `fk_mr_queue` FOREIGN KEY (`queue_id`) REFERENCES `queues` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_mr_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_mr_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `medical_record_diagnoses` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `medical_record_id` INT NOT NULL,
    `icd10_code` VARCHAR(10) NOT NULL,
    `diagnosis_type` ENUM('primary', 'secondary') NOT NULL DEFAULT 'primary',
    `tooth_location` VARCHAR(50) DEFAULT NULL, -- Cth: Gigi 36, Rahang Atas, dll.
    `notes` TEXT DEFAULT NULL,
    CONSTRAINT `fk_mrd_record` FOREIGN KEY (`medical_record_id`) REFERENCES `medical_records` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `medical_record_treatments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `medical_record_id` INT NOT NULL,
    `treatment_id` INT NOT NULL,
    `tooth_location` VARCHAR(50) DEFAULT NULL, -- Cth: Gigi 16, Regio 3, Seluruh Rahang
    `quantity` INT NOT NULL DEFAULT 1,
    `price` DECIMAL(12,2) NOT NULL,
    `doctor_fee_percent` DECIMAL(5,2) NOT NULL,
    `doctor_fee_amount` DECIMAL(12,2) NOT NULL,
    `subtotal` DECIMAL(12,2) NOT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_mrt_record` FOREIGN KEY (`medical_record_id`) REFERENCES `medical_records` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_mrt_treatment` FOREIGN KEY (`treatment_id`) REFERENCES `treatments` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------
-- 8. TABEL APOTEK, OBAT & RESEP ELEKTRONIK (E-PRESCRIPTION)
-- --------------------------------------------------------------
CREATE TABLE `medicines` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(20) NOT NULL UNIQUE, -- OBT-001
    `name` VARCHAR(100) NOT NULL,
    `category` ENUM('Antibiotik', 'Analgesik & Antiinflamasi', 'Kortikosteroid', 'Antiseptik & Obat Kumur', 'Anestesi Lokal', 'Hemostatik', 'Vitamin & Suplemen', 'Bahan Medis Habis Pakai (BMHP)') NOT NULL,
    `unit` VARCHAR(20) NOT NULL, -- tablet, strip, botol, ampul, tube, pcs
    `buy_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `sell_price` DECIMAL(12,2) NOT NULL,
    `min_stock` INT NOT NULL DEFAULT 10,
    `current_stock` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_medicines_stock` (`current_stock`, `min_stock`),
    INDEX `idx_medicines_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `medicine_batches` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `medicine_id` INT NOT NULL,
    `batch_number` VARCHAR(50) NOT NULL,
    `expired_date` DATE NOT NULL,
    `stock_in` INT NOT NULL,
    `current_stock` INT NOT NULL,
    `received_date` DATE NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_batches_expiry` (`expired_date`, `current_stock`),
    CONSTRAINT `fk_batches_medicine` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `prescriptions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `prescription_number` VARCHAR(25) NOT NULL UNIQUE, -- RX-202611-0001
    `medical_record_id` INT NOT NULL,
    `queue_id` INT NOT NULL,
    `doctor_id` INT NOT NULL,
    `patient_id` INT NOT NULL,
    `prescription_date` DATE NOT NULL,
    `status` ENUM('pending', 'processing', 'dispensed', 'cancelled') NOT NULL DEFAULT 'pending',
    `total_medicine_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `pharmacist_notes` TEXT DEFAULT NULL,
    `dispensed_at` DATETIME DEFAULT NULL,
    `dispensed_by` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_prescriptions_status` (`status`),
    CONSTRAINT `fk_rx_record` FOREIGN KEY (`medical_record_id`) REFERENCES `medical_records` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_rx_queue` FOREIGN KEY (`queue_id`) REFERENCES `queues` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_rx_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_rx_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_rx_dispensed_by` FOREIGN KEY (`dispensed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `prescription_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `prescription_id` INT NOT NULL,
    `medicine_id` INT NOT NULL,
    `batch_id` INT DEFAULT NULL,
    `quantity` INT NOT NULL,
    `unit_price` DECIMAL(12,2) NOT NULL,
    `subtotal` DECIMAL(12,2) NOT NULL,
    `dosage_instructions` VARCHAR(150) NOT NULL, -- Signa, cth: 3x1 tablet sesudah makan (habiskan)
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_rxi_prescription` FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_rxi_medicine` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_rxi_batch` FOREIGN KEY (`batch_id`) REFERENCES `medicine_batches` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `stock_movements` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `medicine_id` INT NOT NULL,
    `batch_id` INT DEFAULT NULL,
    `type` ENUM('in', 'out', 'adjustment', 'dispense', 'expired_disposal') NOT NULL,
    `quantity` INT NOT NULL,
    `previous_stock` INT NOT NULL,
    `current_stock` INT NOT NULL,
    `reference_type` ENUM('purchase', 'prescription', 'adjustment', 'initial') NOT NULL,
    `reference_id` INT DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_by` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_sm_medicine` (`medicine_id`, `created_at`),
    CONSTRAINT `fk_sm_medicine` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_sm_batch` FOREIGN KEY (`batch_id`) REFERENCES `medicine_batches` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_sm_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------
-- 9. TABEL KASIR, TAGIHAN (AUTO-BILLING) & KUITANSI
-- --------------------------------------------------------------
CREATE TABLE `billings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `invoice_number` VARCHAR(25) NOT NULL UNIQUE, -- INV-202611-0001
    `queue_id` INT NOT NULL UNIQUE,
    `patient_id` INT NOT NULL,
    `medical_record_id` INT NOT NULL,
    `prescription_id` INT DEFAULT NULL,
    
    -- Komponen Rincian Auto-Billing
    `registration_fee` DECIMAL(12,2) NOT NULL DEFAULT 15000.00,
    `consultation_fee` DECIMAL(12,2) NOT NULL DEFAULT 50000.00,
    `treatments_total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `medicines_total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `discount_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `grand_total` DECIMAL(12,2) NOT NULL,
    
    -- Status & Transaksi Pembayaran
    `payment_status` ENUM('unpaid', 'paid', 'cancelled') NOT NULL DEFAULT 'unpaid',
    `payment_method` ENUM('cash', 'debit', 'transfer', 'qris') DEFAULT NULL,
    `payment_reference` VARCHAR(100) DEFAULT NULL, -- No ref bank / bukti transfer
    `paid_amount` DECIMAL(12,2) DEFAULT NULL,
    `change_amount` DECIMAL(12,2) DEFAULT NULL,
    `paid_at` DATETIME DEFAULT NULL,
    `cashier_id` INT DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_billings_status` (`payment_status`),
    INDEX `idx_billings_invoice` (`invoice_number`),
    CONSTRAINT `fk_billings_queue` FOREIGN KEY (`queue_id`) REFERENCES `queues` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_billings_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_billings_mr` FOREIGN KEY (`medical_record_id`) REFERENCES `medical_records` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_billings_rx` FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_billings_cashier` FOREIGN KEY (`cashier_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `billing_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `billing_id` INT NOT NULL,
    `item_type` ENUM('registration', 'consultation', 'treatment', 'medicine', 'other') NOT NULL,
    `item_reference_id` INT DEFAULT NULL, -- ID treatment atau ID medicine
    `item_name` VARCHAR(150) NOT NULL,
    `quantity` INT NOT NULL DEFAULT 1,
    `unit_price` DECIMAL(12,2) NOT NULL,
    `subtotal` DECIMAL(12,2) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_bi_billing` FOREIGN KEY (`billing_id`) REFERENCES `billings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------
-- 10. TABEL BAGI HASIL DOKTER (DOCTOR FEE SHARING)
-- --------------------------------------------------------------
CREATE TABLE `doctor_fees` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `doctor_id` INT NOT NULL,
    `billing_id` INT NOT NULL,
    `medical_record_id` INT NOT NULL,
    `source_type` ENUM('consultation', 'treatment') NOT NULL,
    `source_name` VARCHAR(150) NOT NULL,
    `gross_amount` DECIMAL(12,2) NOT NULL,
    `fee_percentage` DECIMAL(5,2) NOT NULL,
    `net_fee_amount` DECIMAL(12,2) NOT NULL,
    `status` ENUM('unpaid', 'paid') NOT NULL DEFAULT 'unpaid',
    `settled_at` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_df_doctor_status` (`doctor_id`, `status`),
    INDEX `idx_df_billing` (`billing_id`),
    CONSTRAINT `fk_df_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_df_billing` FOREIGN KEY (`billing_id`) REFERENCES `billings` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_df_mr` FOREIGN KEY (`medical_record_id`) REFERENCES `medical_records` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------
-- 11. TABEL PENGATURAN KLINIK, PROMO & FAQ (HALAMAN PUBLIK)
-- --------------------------------------------------------------
CREATE TABLE `clinic_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(50) NOT NULL UNIQUE,
    `setting_value` TEXT NOT NULL,
    `setting_group` VARCHAR(30) NOT NULL DEFAULT 'general',
    `description` VARCHAR(255) DEFAULT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `promotions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(150) NOT NULL,
    `slug` VARCHAR(150) NOT NULL UNIQUE,
    `banner_image` VARCHAR(255) DEFAULT NULL,
    `description` TEXT NOT NULL,
    `terms` TEXT DEFAULT NULL,
    `discount_type` ENUM('percentage', 'fixed') NOT NULL DEFAULT 'percentage',
    `discount_value` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_promo_active` (`is_active`, `start_date`, `end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `faqs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `question` VARCHAR(255) NOT NULL,
    `answer` TEXT NOT NULL,
    `category` VARCHAR(50) NOT NULL DEFAULT 'Umum',
    `sort_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_faqs_order` (`sort_order`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
