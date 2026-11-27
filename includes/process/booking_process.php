<?php
/**
 * DENTARA - Online Booking Process
 * File: includes/process/booking_process.php
 */

require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . base_url('booking.php'));
    exit;
}

if (!verify_csrf()) {
    set_flash('error', 'Token Tidak Valid', 'Sesi booking Anda telah kedaluwarsa. Silakan ulangi.');
    header('Location: ' . base_url('booking.php'));
    exit;
}

$id_dokter  = (int)($_POST['id_dokter'] ?? 0);
$id_layanan = !empty($_POST['id_layanan']) ? (int)$_POST['id_layanan'] : null;
$tanggal    = trim($_POST['tanggal'] ?? '');
$jam        = trim($_POST['jam'] ?? '');
$keluhan    = trim($_POST['keluhan'] ?? '');

$id_pasien  = !empty($_POST['id_pasien']) ? (int)$_POST['id_pasien'] : null;
$nama_tamu  = trim($_POST['nama_tamu'] ?? '');
$no_hp_tamu = trim($_POST['no_hp_tamu'] ?? '');

// Validasi dasar
if (!$id_dokter || empty($tanggal) || empty($jam)) {
    set_flash('warning', 'Data Belum Lengkap', 'Pilih dokter, tanggal kunjungan, dan jam layanan.');
    header('Location: ' . base_url('booking.php'));
    exit;
}

if (!$id_pasien && (empty($nama_tamu) || empty($no_hp_tamu))) {
    set_flash('warning', 'Identitas Diperlukan', 'Nama lengkap dan nomor WhatsApp wajib diisi.');
    header('Location: ' . base_url('booking.php'));
    exit;
}

// Tidak boleh booking tanggal lampau
if ($tanggal < date('Y-m-d')) {
    set_flash('warning', 'Tanggal Tidak Valid', 'Anda tidak dapat memilih tanggal kunjungan di masa lampau.');
    header('Location: ' . base_url('booking.php'));
    exit;
}

try {
    // 1. Cek Libur Mendadak
    $stmt_libur = $pdo->prepare("SELECT alasan FROM libur_mendadak WHERE tanggal_libur = ? AND status = 'aktif'");
    $stmt_libur->execute([$tanggal]);
    $alasan_libur = $stmt_libur->fetchColumn();
    if ($alasan_libur) {
        set_flash('error', 'Klinik Tutup', "Klinik libur pada tanggal " . tanggal_indo($tanggal) . " karena: {$alasan_libur}");
        header('Location: ' . base_url('booking.php'));
        exit;
    }

    // 2. Cek Hari Operasional Klinik
    $hari_map = [
        'Sun' => 'Minggu', 'Mon' => 'Senin', 'Tue' => 'Selasa',
        'Wed' => 'Rabu', 'Thu' => 'Kamis', 'Fri' => 'Jumat', 'Sat' => 'Sabtu'
    ];
    $hari_booking = $hari_map[date('D', strtotime($tanggal))];

    $stmt_klinik = $pdo->prepare("SELECT status FROM jadwal_klinik WHERE hari = ?");
    $stmt_klinik->execute([$hari_booking]);
    $status_klinik = $stmt_klinik->fetchColumn();
    if ($status_klinik === 'Tutup') {
        set_flash('warning', 'Klinik Tutup', "Klinik tidak beroperasi pada hari {$hari_booking}.");
        header('Location: ' . base_url('booking.php'));
        exit;
    }

    // 3. Cek Cuti Dokter
    $stmt_cuti = $pdo->prepare("SELECT alasan FROM cuti_dokter WHERE id_dokter = ? AND ? BETWEEN tanggal_mulai AND tanggal_selesai AND status = 'aktif'");
    $stmt_cuti->execute([$id_dokter, $tanggal]);
    $alasan_cuti = $stmt_cuti->fetchColumn();
    if ($alasan_cuti) {
        set_flash('warning', 'Dokter Sedang Cuti', "Dokter yang dipilih sedang cuti/izin pada tanggal tersebut: {$alasan_cuti}");
        header('Location: ' . base_url('booking.php'));
        exit;
    }

    // 4. Cek Jadwal Praktik Dokter & Kuota
    $stmt_jadwal = $pdo->prepare("SELECT kuota_per_hari FROM jadwal_praktik_dokter WHERE id_dokter = ? AND hari = ? AND status = 'aktif'");
    $stmt_jadwal->execute([$id_dokter, $hari_booking]);
    $kuota = $stmt_jadwal->fetchColumn();

    if ($kuota === false) {
        set_flash('warning', 'Tidak Ada Jadwal', "Dokter tersebut tidak memiliki jadwal praktik pada hari {$hari_booking}.");
        header('Location: ' . base_url('booking.php'));
        exit;
    }

    // Hitung reservasi aktif hari itu
    $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM reservasi WHERE id_dokter = ? AND tanggal = ? AND status != 'dibatalkan'");
    $stmt_count->execute([$id_dokter, $tanggal]);
    $terisi = (int)$stmt_count->fetchColumn();

    if ($terisi >= (int)$kuota) {
        set_flash('error', 'Kuota Penuh', "Maaf, kuota antrean dokter untuk tanggal " . tanggal_indo($tanggal) . " sudah penuh ({$terisi}/{$kuota}). Silakan pilih tanggal lain.");
        header('Location: ' . base_url('booking.php'));
        exit;
    }

    // 5. Generate Kode Booking Unik (6 Karakter)
    $kode_booking = generate_kode_booking($pdo);

    // 6. Simpan Reservasi
    $stmt_ins = $pdo->prepare("
        INSERT INTO reservasi (kode_booking, id_pasien, nama_tamu, no_hp_tamu, id_dokter, id_layanan, tanggal, jam, keluhan, status, sumber)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'dikonfirmasi', 'online')
    ");
    $stmt_ins->execute([
        $kode_booking,
        $id_pasien ?: null,
        $id_pasien ? null : $nama_tamu,
        $id_pasien ? null : $no_hp_tamu,
        $id_dokter,
        $id_layanan ?: null,
        $tanggal,
        $jam,
        $keluhan ?: null
    ]);

    // Jika pasien login, kirimkan notifikasi
    if ($id_pasien) {
        kirim_notifikasi_pasien(
            $pdo,
            $id_pasien,
            'Reservasi Berhasil!',
            "Reservasi Anda berhasil dibuat dengan Kode Booking: {$kode_booking} untuk tanggal " . tanggal_indo($tanggal) . " pukul {$jam} WIB."
        );
    }

    set_flash('success', 'Booking Sukses!', "Reservasi Anda berhasil! Kode Booking: {$kode_booking}");
    header('Location: ' . base_url('booking_sukses.php?kode=' . $kode_booking));
    exit;

} catch (Exception $e) {
    set_flash('error', 'Kesalahan Sistem', 'Gagal memproses booking: ' . $e->getMessage());
    header('Location: ' . base_url('booking.php'));
    exit;
}

