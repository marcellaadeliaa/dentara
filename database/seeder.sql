-- ==============================================================
-- DENTARA - Seeder Data Awal (Default Test Data)
-- Password untuk semua akun default: password123
-- Hash Bcrypt: $2y$10$e0MYzXyjpJS7Pd0RVvHwHe1mR4QJ8yE2G1m8b8M9W8S2Z2T4G9J9m (di-generate via PHP)
-- ==============================================================

USE `dentara`;

-- 1. PENGATURAN KLINIK
INSERT INTO `clinic_settings` (`setting_key`, `setting_value`, `setting_group`, `description`) VALUES
('clinic_name', 'DENTARA Dental Care', 'general', 'Nama Resmi Klinik Gigi'),
('clinic_tagline', 'Senyum Sehat dan Percaya Diri Bersama DENTARA', 'general', 'Slogan Klinik'),
('clinic_address', 'Jl. Kesehatan Gigi No. 45, Kebayoran Baru, Jakarta Selatan', 'general', 'Alamat Lengkap'),
('clinic_phone', '(021) 7890-1234', 'general', 'Nomor Telepon'),
('clinic_whatsapp', '081234567890', 'general', 'WhatsApp Pasien / CS'),
('clinic_email', 'kontak@dentara.clinic', 'general', 'Email Resmi'),
('operational_hours', 'Senin - Sabtu: 09:00 - 21:00 WIB | Minggu: Sesuai Perjanjian', 'general', 'Jam Layanan'),
('default_registration_fee', '15000', 'billing', 'Biaya Administrasi Pendaftaran (Rp)'),
('default_consultation_fee', '50000', 'billing', 'Biaya Jasa Konsultasi Dokter (Rp)'),
('qris_image', 'assets/images/qris_dentara.png', 'billing', 'Gambar QRIS Statis Kasir');

-- 2. PENGGUNA AWAL (USERS)
-- Catatan: Password default adalah 'password123'
INSERT INTO `users` (`id`, `username`, `email`, `password`, `full_name`, `phone`, `role`, `is_active`) VALUES
(1, 'admin', 'admin@dentara.clinic', '$2y$10$wOaA3Pj9u8V6x4h1p2XwfeY4fS2uU6J2gE8qK1rM0pL7zF4eC8w3O', 'drg. Hendra Pratama, MM', '08111111111', 'admin', 1),
(2, 'drg_sarah', 'sarah.dentist@dentara.clinic', '$2y$10$wOaA3Pj9u8V6x4h1p2XwfeY4fS2uU6J2gE8qK1rM0pL7zF4eC8w3O', 'drg. Sarah Melati, Sp.KG', '08222222222', 'doctor', 1),
(3, 'drg_fajar', 'fajar.dentist@dentara.clinic', '$2y$10$wOaA3Pj9u8V6x4h1p2XwfeY4fS2uU6J2gE8qK1rM0pL7zF4eC8w3O', 'drg. Fajar Nugraha', '08333333333', 'doctor', 1),
(4, 'resepsionis', 'frontdesk@dentara.clinic', '$2y$10$wOaA3Pj9u8V6x4h1p2XwfeY4fS2uU6J2gE8qK1rM0pL7zF4eC8w3O', 'Nadia Putri', '08444444444', 'receptionist', 1),
(5, 'apoteker', 'pharmacy@dentara.clinic', '$2y$10$wOaA3Pj9u8V6x4h1p2XwfeY4fS2uU6J2gE8qK1rM0pL7zF4eC8w3O', 'apt. Rian Hidayat, S.Farm', '08555555555', 'pharmacist', 1),
(6, 'kasir', 'cashier@dentara.clinic', '$2y$10$wOaA3Pj9u8V6x4h1p2XwfeY4fS2uU6J2gE8qK1rM0pL7zF4eC8w3O', 'Lia Anggraini', '08666666666', 'cashier', 1),
(7, 'budi_santoso', 'budi.santoso@gmail.com', '$2y$10$wOaA3Pj9u8V6x4h1p2XwfeY4fS2uU6J2gE8qK1rM0pL7zF4eC8w3O', 'Budi Santoso', '081298765432', 'patient', 1);

-- 3. DATA DOKTER & JADWAL PRAKTIK
INSERT INTO `doctors` (`id`, `user_id`, `sip_number`, `specialization`, `consultation_fee`, `default_fee_percentage`, `room_name`, `bio`, `is_active`) VALUES
(1, 2, 'SIP/446/DKS/2023/0012', 'Spesialis Konservasi Gigi (Sp.KG)', 75000.00, 45.00, 'Poli Gigi 1 (Estetika & Endodontik)', 'Berpengalaman lebih dari 8 tahun dalam perawatan saluran akar dan restorasi estetika gigi.', 1),
(2, 3, 'SIP/446/DKS/2024/0088', 'Dokter Gigi Umum', 50000.00, 40.00, 'Poli Gigi 2 (Umum & Bedah Minor)', 'Fokus pada tindakan preventif, scaling, penambalan komposit, dan pencabutan gigi.', 1);

INSERT INTO `doctor_schedules` (`doctor_id`, `day_of_week`, `start_time`, `end_time`, `quota`, `is_active`) VALUES
(1, 'Senin', '09:00:00', '14:00:00', 12, 1),
(1, 'Rabu', '09:00:00', '14:00:00', 12, 1),
(1, 'Jumat', '14:00:00', '20:00:00', 12, 1),
(1, 'Sabtu', '10:00:00', '16:00:00', 10, 1),
(2, 'Senin', '15:00:00', '21:00:00', 15, 1),
(2, 'Selasa', '09:00:00', '15:00:00', 15, 1),
(2, 'Kamis', '09:00:00', '15:00:00', 15, 1),
(2, 'Jumat', '09:00:00', '13:00:00', 10, 1),
(2, 'Sabtu', '13:00:00', '19:00:00', 12, 1);

-- 4. DATA PASIEN
INSERT INTO `patients` (`id`, `user_id`, `medical_record_number`, `nik`, `full_name`, `gender`, `birth_place`, `birth_date`, `blood_type`, `phone`, `email`, `address`, `occupation`, `allergies`, `medical_history`, `emergency_contact_name`, `emergency_contact_phone`, `emergency_contact_relation`) VALUES
(1, 7, 'RM-202611-0001', '3171012305880001', 'Budi Santoso', 'L', 'Jakarta', '1988-05-23', 'O', '081298765432', 'budi.santoso@gmail.com', 'Jl. Melati Blok C No. 12, Jakarta Selatan', 'Karyawan Swasta', 'Alergi Golongan Penisilin', 'Hipertensi Ringan (terkontrol)', 'Dewi Lestari', '081298765433', 'Istri'),
(2, NULL, 'RM-202611-0002', '3171025510920004', 'Siti Rahmawati', 'P', 'Bandung', '1992-10-15', 'A', '081387654321', 'siti.rahma@yahoo.com', 'Jl. Tebet Timur Dalam No. 88, Jakarta Selatan', 'Wiraswasta', 'Tidak Ada', 'Maag / Dispepsia', 'Ahmad Fauzi', '081387654322', 'Suami'),
(3, NULL, 'RM-202611-0003', '3275031407010005', 'Kevin Christian', 'L', 'Surabaya', '2001-07-14', 'B', '087812345678', 'kevin.c@outlook.com', 'Apartemen Sudirman Tower A Lt 15, Jakarta Pusat', 'Mahasiswa', 'Alergi Mefenamic Acid (Asam Mefenamat)', 'Tidak Ada', 'Linda Christian', '087812345679', 'Ibu');

-- 5. MASTER ICD-10 KESEHATAN GIGI & MULUT
INSERT INTO `icd10_codes` (`code`, `name_en`, `name_id`, `category`, `is_common`) VALUES
('K02.0', 'Dental caries limited to enamel', 'Karies gigi terbatas pada email (white spot / karies dini)', 'Karies Gigi', 1),
('K02.1', 'Dental caries extending into dentine', 'Karies gigi mencapai dentin (karies media / profunda tanpa perforasi)', 'Karies Gigi', 1),
('K02.2', 'Dental caries extending into cementum', 'Karies sementum / karies akar', 'Karies Gigi', 0),
('K02.9', 'Dental caries, unspecified', 'Karies gigi, tidak spesifik', 'Karies Gigi', 1),
('K04.0', 'Pulpitis (acute / chronic / reversible / irreversible)', 'Pulpitis (reversibel atau ireversibel)', 'Penyakit Pulpa & Periapikal', 1),
('K04.1', 'Necrosis of pulp', 'Nekrosis pulpa (gangren pulpa / gigi non-vital)', 'Penyakit Pulpa & Periapikal', 1),
('K04.4', 'Acute apical periodontitis', 'Periodontitis apikalis akut', 'Penyakit Pulpa & Periapikal', 1),
('K04.7', 'Periapical abscess without sinus', 'Abses periapikal tanpa fistula', 'Penyakit Pulpa & Periapikal', 1),
('K05.0', 'Acute gingivitis', 'Gingivitis akut (radang gusi akut)', 'Gingivitis & Penyakit Periodontal', 1),
('K05.1', 'Chronic gingivitis (plaque / calculus induced)', 'Gingivitis kronis (akibat karang gigi / plak)', 'Gingivitis & Penyakit Periodontal', 1),
('K05.3', 'Chronic periodontitis', 'Periodontitis kronis (kegoyangan gigi / poket periodontal)', 'Gingivitis & Penyakit Periodontal', 1),
('K01.1', 'Impacted teeth (wisdom teeth)', 'Gigi impaksi (geraham bungsu terpendam)', 'Gangguan Tumbuh Kembang Gigi', 1),
('K00.6', 'Disturbances in tooth eruption (persisting deciduous teeth)', 'Persistensi gigi sulung / gigi susu belum tanggal', 'Gangguan Tumbuh Kembang Gigi', 1),
('K08.1', 'Loss of teeth due to accident, extraction or local periodontal disease', 'Kehilangan gigi akibat pencabutan/trauma (edentulous)', 'Kelainan Gigi Lainnya', 1),
('K12.0', 'Recurrent aphthous stomatitis', 'Stomatitis aftosa rekuren (sariawan berulang)', 'Penyakit Bibir & Mukosa Mulut', 1);

-- 6. MASTER TINDAKAN MEDIS GIGI & TARIF
INSERT INTO `treatments` (`code`, `name`, `category`, `description`, `price`, `default_doctor_fee_percent`, `is_active`) VALUES
('TDK-001', 'Pembersihan Karang Gigi (Scaling) Ringan', 'Preventif & Pembersihan', 'Pembersihan karang gigi supra-gingiva rahang atas dan bawah tanpa kelainan periodontal berat.', 250000.00, 40.00, 1),
('TDK-002', 'Pembersihan Karang Gigi (Scaling) Berat + Polishing', 'Preventif & Pembersihan', 'Pembersihan karang sub-gingiva dengan stain dan pemolesan pasta profilaksis fluorida.', 350000.00, 40.00, 1),
('TDK-003', 'Penambalan Gigi Komposit / Sinar (Kavitas Kecil/Media)', 'Konservasi / Penambalan', 'Restorasi resin komposit light-curing estetik sewarna gigi untuk 1 permukaan.', 300000.00, 45.00, 1),
('TDK-004', 'Penambalan Gigi Komposit Kompleks / Multi-surface', 'Konservasi / Penambalan', 'Restorasi resin komposit untuk karies kelas II, MOD, atau rekontruksi sudut incisal.', 450000.00, 45.00, 1),
('TDK-005', 'Penambalan Glass Ionomer Cement (GIC)', 'Konservasi / Penambalan', 'Tambalan GIC pelepasan fluor untuk karies servikal atau gigi anak.', 200000.00, 40.00, 1),
('TDK-006', 'Perawatan Saluran Akar (PSA / Endodontik) per Saluran', 'Endodontik / Saluran Akar', 'Ekstirpasi, biomechanical preparation, sterilisasi, dan medikamen saluran akar.', 350000.00, 50.00, 1),
('TDK-007', 'Obturasi Saluran Akar (Pengisian Saluran Akar)', 'Endodontik / Saluran Akar', 'Pengisian permanen saluran akar dengan guttapercha dan sealer endodontik.', 300000.00, 50.00, 1),
('TDK-008', 'Pencabutan Gigi Permanen Sederhana (Tanpa Penyulit)', 'Bedah Mulut / Pencabutan', 'Ekstraksi gigi tetap anterior atau posterior tanpa pembedahan.', 250000.00, 45.00, 1),
('TDK-009', 'Pencabutan Gigi Permanen dengan Penyulit / Sisa Akar', 'Bedah Mulut / Pencabutan', 'Ekstraksi gigi dengan pemisahan akar (separasi) atau sisa akar fraktur.', 400000.00, 45.00, 1),
('TDK-010', 'Odontektomi Gigi Impaksi Geraham Bungsu (Kelas I/II)', 'Bedah Mulut / Pencabutan', 'Pembedahan pengangkatan gigi bungsu impaksi dengan flap dan bone removal minor.', 1500000.00, 55.00, 1),
('TDK-011', 'Pencabutan Gigi Sulung (Gigi Susu Anak)', 'Bedah Mulut / Pencabutan', 'Ekstraksi gigi susu dengan anestesi topikal atau infiltrasi.', 150000.00, 40.00, 1),
('TDK-012', 'Aplikasi Topikal Fluoride (Pencegahan Gigi Berlubang)', 'Preventif & Pembersihan', 'Pemberian varnish fluorida untuk memperkuat enamel gigi anak maupun dewasa.', 180000.00, 40.00, 1),
('TDK-013', 'Bleaching Gigi In-Office (Pemutihan Gigi Profesional)', 'Konservasi / Penambalan', 'Pemutihan gigi menggunakan hydrogen peroxide activated lamp 2 rahang.', 180000.00, 45.00, 1);

-- 7. MASTER OBAT & BMHP
INSERT INTO `medicines` (`code`, `name`, `category`, `unit`, `buy_price`, `sell_price`, `min_stock`, `current_stock`, `is_active`) VALUES
('OBT-001', 'Amoxicillin 500 mg', 'Antibiotik', 'strip (10 tab)', 6000.00, 12000.00, 20, 150, 1),
('OBT-002', 'Ciprofloxacin 500 mg', 'Antibiotik', 'strip (10 tab)', 12000.00, 22000.00, 15, 80, 1),
('OBT-003', 'Clindamycin 300 mg', 'Antibiotik', 'strip (10 kap)', 25000.00, 40000.00, 10, 60, 1),
('OBT-004', 'Asam Mefenamat 500 mg', 'Analgesik & Antiinflamasi', 'strip (10 kap)', 5000.00, 10000.00, 25, 200, 1),
('OBT-005', 'Paracetamol 500 mg', 'Analgesik & Antiinflamasi', 'strip (10 tab)', 4000.00, 8000.00, 30, 250, 1),
('OBT-006', 'Ibuprofen 400 mg', 'Analgesik & Antiinflamasi', 'strip (10 tab)', 6500.00, 13000.00, 20, 120, 1),
('OBT-007', 'Natrium Diklofenak 50 mg', 'Analgesik & Antiinflamasi', 'strip (10 tab)', 7000.00, 14000.00, 15, 90, 1),
('OBT-008', 'Dexamethasone 0.5 mg', 'Kortikosteroid', 'strip (10 tab)', 3000.00, 7000.00, 20, 140, 1),
('OBT-009', 'Metronidazole 500 mg', 'Antibiotik', 'strip (10 tab)', 7500.00, 15000.00, 15, 110, 1),
('OBT-010', 'Minosep Obat Kumur Chlorhexidine 0.2% 150 ml', 'Antiseptik & Obat Kumur', 'botol', 28000.00, 42000.00, 10, 45, 1),
('OBT-011', 'Kenalog in Orabase Triamcinolone 0.1% 5g', 'Kortikosteroid', 'tube', 65000.00, 88000.00, 5, 25, 1),
('OBT-012', 'Pehacain Ampul (Lidocaine 2% + Adrenaline)', 'Anestesi Lokal', 'ampul', 8000.00, 16000.00, 30, 100, 1),
('OBT-013', 'Kasa Steril 16x16 cm', 'Bahan Medis Habis Pakai (BMHP)', 'pack', 7000.00, 12000.00, 20, 75, 1);

-- BATCH OBAT & TANGGAL KEDALUWARSA
INSERT INTO `medicine_batches` (`medicine_id`, `batch_number`, `expired_date`, `stock_in`, `current_stock`, `received_date`) VALUES
(1, 'AMX-2024A', '2027-08-30', 200, 150, '2024-09-01'),
(2, 'CPR-2024B', '2027-05-15', 100, 80, '2024-06-10'),
(3, 'CLN-2024A', '2027-11-20', 80, 60, '2024-11-01'),
(4, 'ASM-2024C', '2028-01-10', 300, 200, '2024-02-15'),
(5, 'PCT-2024D', '2028-03-31', 350, 250, '2024-04-01'),
(6, 'IBP-2024A', '2027-09-12', 150, 120, '2024-09-15'),
(7, 'NDK-2024B', '2027-07-25', 100, 90, '2024-08-01'),
(8, 'DEX-2024A', '2027-12-30', 200, 140, '2024-10-01'),
(9, 'MTZ-2024C', '2027-06-18', 150, 110, '2024-07-01'),
(10, 'MNS-2024B', '2027-04-10', 60, 45, '2024-05-01'),
(11, 'KNL-2024A', '2026-12-31', 40, 25, '2024-01-20'),
(12, 'PHC-2024E', '2027-10-15', 120, 100, '2024-10-20'),
(13, 'KSA-2024A', '2029-01-01', 100, 75, '2024-01-01');

-- 8. PROMOSI AKTIF (UNTUK HALAMAN PUBLIK)
INSERT INTO `promotions` (`title`, `slug`, `banner_image`, `description`, `terms`, `discount_type`, `discount_value`, `start_date`, `end_date`, `is_active`) VALUES
('Promo Scaling Sehat Akhir Tahun', 'promo-scaling-akhir-tahun', 'assets/images/promo1.jpg', 'Dapatkan pembersihan karang gigi lengkap (Rahang Atas & Bawah) dengan potongan 20% khusus reservasi online!', 'Berlaku untuk pendaftaran online di website DENTARA. Tidak dapat digabungkan dengan promo lain.', 'percentage', 20.00, '2026-11-01', '2026-12-31', 1),
('Paket Senyum Cerah (Bleaching Gigi)', 'paket-senyum-cerah-bleaching', 'assets/images/promo2.jpg', 'Gigi putih berkilau natural dalam 1 kali kunjungan in-office dengan teknologi cold light aman & nyaman.', 'Wajib konsultasi dokter terlebih dahulu untuk memastikan kondisi email gigi sehat.', 'fixed', 200000.00, '2026-11-15', '2026-12-31', 1),
('Pemeriksaan Gigi Rutin & Konsultasi Gratis Hari Sabtu', 'konsultasi-gratis-sabtu', 'assets/images/promo3.jpg', 'Bebas biaya konsultasi setiap hari Sabtu bersama tim dokter gigi DENTARA.', 'Kuota terbatas 10 pasien per sesi.', 'percentage', 100.00, '2026-11-01', '2026-12-31', 1);

-- 9. FAQ (FREQUENTLY ASKED QUESTIONS)
INSERT INTO `faqs` (`question`, `answer`, `category`, `sort_order`, `is_active`) VALUES
('Bagaimana cara mendaftar konsultasi di DENTARA?', 'Anda dapat mendaftar langsung secara online melalui menu Pendaftaran Online pada halaman ini, atau langsung datang ke klinik (walk-in). Khusus reservasi online, Anda dapat memilih dokter dan estimasi waktu yang diinginkan.', 'Pendaftaran', 1, 1),
('Apakah saya bisa memantau nomor antrean dari rumah?', 'Ya! DENTARA menyediakan fitur Live Queue Tracker yang memperbarui status antrean secara real-time. Anda cukup memasukkan nomor reservasi atau melihat display antrean hari ini.', 'Antrean', 2, 1),
('Apakah tindakan pencabutan gigi terasa sakit?', 'Tim dokter gigi kami menggunakan anestesi lokal berkualitas tinggi dengan teknik penyuntikan terkini yang meminimalkan rasa tidak nyaman selama prosedur berlangsung.', 'Pelayanan Gigi', 3, 1),
('Metode pembayaran apa saja yang diterima di DENTARA?', 'Kami menerima pembayaran tunai (cash), kartu debit semua bank, QRIS (GoPay, OVO, ShopeePay, BCA, dll.), dan transfer bank.', 'Pembayaran', 4, 1),
('Berapa kali sebaiknya saya melakukan pembersihan karang gigi (scaling)?', 'Persatuan Dokter Gigi menyarankan pembersihan karang gigi secara teratur setiap 6 bulan sekali untuk mencegah radang gusi, bau mulut, dan gigi goyang.', 'Perawatan Gigi', 5, 1);

-- 10. CONTOH APPOINTMENT & ANTREAN HARI INI (UNTUK SIMULASI SISTEM)
INSERT INTO `appointments` (`id`, `booking_code`, `patient_id`, `patient_name`, `patient_phone`, `patient_email`, `doctor_id`, `appointment_date`, `estimated_time`, `complaint`, `is_new_patient`, `status`) VALUES
(1, 'BK-20261117-001', 1, 'Budi Santoso', '081298765432', 'budi.santoso@gmail.com', 1, CURRENT_DATE(), '10:00:00', 'Gigi geraham kiri bawah ngilu berdenyut saat minum dingin sejak 3 hari lalu', 0, 'confirmed'),
(2, 'BK-20261117-002', 2, 'Siti Rahmawati', '081387654321', 'siti.rahma@yahoo.com', 2, CURRENT_DATE(), '11:00:00', 'Ingin pembersihan karang gigi dan konsultasi gusi berdarah', 0, 'confirmed');

INSERT INTO `queues` (`id`, `queue_number`, `queue_date`, `queue_type`, `appointment_id`, `patient_id`, `doctor_id`, `status`, `priority`, `called_at`, `created_at`) VALUES
(1, 'A-001', CURRENT_DATE(), 'online', 1, 1, 1, 'in_consultation', 'normal', NOW(), NOW()),
(2, 'B-001', CURRENT_DATE(), 'online', 2, 2, 2, 'waiting', 'normal', NULL, NOW());
