<?php
/**
 * DENTARA - Patient Registration Process
 * File: includes/process/daftar_pasien_process.php
 */

require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . base_url('daftar_pasien.php'));
    exit;
}

if (!verify_csrf()) {
    set_flash('error', 'Token Tidak Valid', 'Sesi formulir telah kedaluwarsa. Silakan ulangi.');
    header('Location: ' . base_url('daftar_pasien.php'));
    exit;
}

// Input data akun
$email         = strtolower(trim($_POST['email'] ?? ''));
$password      = $_POST['password'] ?? '';
$password_konf = $_POST['password_konfirmasi'] ?? '';
$no_hp         = trim($_POST['no_hp'] ?? '');

// Input profil pasien
$nama_pasien   = trim($_POST['nama_pasien'] ?? '');
$nik           = trim($_POST['nik'] ?? '');
$tgl_lahir     = trim($_POST['tgl_lahir'] ?? '');
$jenis_kelamin = trim($_POST['jenis_kelamin'] ?? '');
$gol_darah     = trim($_POST['gol_darah'] ?? '');
$alamat        = trim($_POST['alamat'] ?? '');
$alergi_obat   = trim($_POST['alergi_obat'] ?? '');
$kondisi_medis = trim($_POST['kondisi_medis'] ?? '');

// Validasi
if (empty($email) || empty($password) || empty($no_hp) || empty($nama_pasien) || empty($nik) || empty($tgl_lahir) || empty($jenis_kelamin) || empty($alamat)) {
    set_flash('warning', 'Data Belum Lengkap', 'Semua kolom bertanda bintang (*) wajib diisi.');
    header('Location: ' . base_url('daftar_pasien.php'));
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    set_flash('warning', 'Format Email Salah', 'Masukkan alamat email yang valid.');
    header('Location: ' . base_url('daftar_pasien.php'));
    exit;
}

if (strlen($password) < 6) {
    set_flash('warning', 'Kata Sandi Lemah', 'Kata sandi minimal 6 karakter.');
    header('Location: ' . base_url('daftar_pasien.php'));
    exit;
}

if ($password !== $password_konf) {
    set_flash('warning', 'Konfirmasi Sandi Berbeda', 'Kata sandi dan konfirmasi kata sandi tidak cocok.');
    header('Location: ' . base_url('daftar_pasien.php'));
    exit;
}

if (!preg_match('/^[0-9]{16}$/', $nik)) {
    set_flash('warning', 'NIK Tidak Valid', 'NIK harus terdiri dari 16 digit angka sesuai KTP/KIA.');
    header('Location: ' . base_url('daftar_pasien.php'));
    exit;
}

try {
    // Cek apakah email sudah terdaftar
    $stmt_cek = $pdo->prepare("SELECT COUNT(*) FROM akun_pasien WHERE email = ?");
    $stmt_cek->execute([$email]);
    if ($stmt_cek->fetchColumn() > 0) {
        set_flash('error', 'Email Terdaftar', 'Email tersebut sudah digunakan. Silakan gunakan email lain atau login.');
        header('Location: ' . base_url('daftar_pasien.php'));
        exit;
    }

    $pdo->beginTransaction();

    // 1. Buat Akun Pasien
    $hash_password = password_hash($password, PASSWORD_BCRYPT);
    $stmt_acc = $pdo->prepare("INSERT INTO akun_pasien (email, password, no_hp) VALUES (?, ?, ?)");
    $stmt_acc->execute([$email, $hash_password, $no_hp]);
    $id_akun = (int)$pdo->lastInsertId();

    // 2. Generate No RM Baru
    $no_rm = generate_no_rm($pdo);

    // 3. Buat Profil Pasien Utama
    $stmt_pasien = $pdo->prepare("
        INSERT INTO pasien (no_rm, id_akun, hubungan_keluarga, nama_pasien, nik, tgl_lahir, jenis_kelamin, gol_darah, alamat, no_hp, alergi_obat, kondisi_medis)
        VALUES (?, ?, 'Diri Sendiri', ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt_pasien->execute([
        $no_rm, $id_akun, $nama_pasien, $nik, $tgl_lahir, $jenis_kelamin, 
        $gol_darah ?: null, $alamat, $no_hp, $alergi_obat ?: null, $kondisi_medis ?: null
    ]);
    $id_pasien = (int)$pdo->lastInsertId();

    // 4. Inisialisasi Poin Gamifikasi (Bonus sambutan 50 poin)
    $stmt_poin = $pdo->prepare("INSERT INTO gamifikasi_poin (id_pasien, total_poin, jumlah_kunjungan_selesai) VALUES (?, 50, 0)");
    $stmt_poin->execute([$id_pasien]);

    // 5. Kirim Notifikasi Selamat Datang
    kirim_notifikasi_pasien(
        $pdo, 
        $id_pasien, 
        'Selamat Datang di DENTARA!', 
        "Halo {$nama_pasien}, akun Anda telah aktif dengan No Rekam Medis: {$no_rm}. Anda mendapatkan bonus sambutan 50 Poin Denta!"
    );

    $pdo->commit();

    // Auto-login pasien setelah daftar
    $_SESSION['id_akun_pasien'] = $id_akun;
    $_SESSION['email_pasien']   = $email;
    $_SESSION['no_hp_pasien']   = $no_hp;
    $_SESSION['id_pasien_aktif']= $id_pasien;

    set_flash('success', 'Pendaftaran Berhasil!', "Selamat datang di DENTARA! No RM Anda: {$no_rm}");
    header('Location: ' . base_url('pasien/dashboard.php'));
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    set_flash('error', 'Gagal Mendaftar', 'Terjadi kesalahan sistem: ' . $e->getMessage());
    header('Location: ' . base_url('daftar_pasien.php'));
    exit;
}

