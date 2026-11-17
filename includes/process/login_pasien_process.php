<?php
/**
 * DENTARA - Patient Login Process
 * File: includes/process/login_pasien_process.php
 */

require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . base_url('login_pasien.php'));
    exit;
}

if (!verify_csrf()) {
    set_flash('error', 'Token Tidak Valid', 'Sesi formulir telah kedaluwarsa. Silakan ulangi.');
    header('Location: ' . base_url('login_pasien.php'));
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// Rate limiting
$rate = check_login_rate_limit('patient_' . $email);
if (!$rate['allowed']) {
    set_flash('error', 'Percobaan Terkunci', $rate['message']);
    header('Location: ' . base_url('login_pasien.php'));
    exit;
}

if (empty($email) || empty($password)) {
    set_flash('warning', 'Data Kosong', 'Email dan password akun pasien wajib diisi.');
    header('Location: ' . base_url('login_pasien.php'));
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM akun_pasien WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $acc = $stmt->fetch();

    if ($acc && password_verify($password, $acc['password'])) {
        clear_login_rate_limit('patient_' . $email);
        session_regenerate_id(true);

        $_SESSION['id_akun_pasien'] = (int)$acc['id_akun'];
        $_SESSION['email_pasien']    = $acc['email'];
        $_SESSION['no_hp_pasien']    = $acc['no_hp'];

        // Ambil profil pasien utama
        $stmt_p = $pdo->prepare("SELECT id_pasien, nama_pasien FROM pasien WHERE id_akun = ? ORDER BY id_pasien ASC LIMIT 1");
        $stmt_p->execute([(int)$acc['id_akun']]);
        $main_profile = $stmt_p->fetch();

        if ($main_profile) {
            $_SESSION['id_pasien_aktif'] = (int)$main_profile['id_pasien'];
            $nama = $main_profile['nama_pasien'];
        } else {
            $nama = 'Pasien';
        }

        set_flash('success', 'Selamat Datang!', "Halo {$nama}, senang bertemu Anda kembali!");

        $intended = $_SESSION['intended_patient_url'] ?? '';
        unset($_SESSION['intended_patient_url']);

        if ($intended && !str_contains($intended, 'login')) {
            header('Location: ' . $intended);
        } else {
            header('Location: ' . base_url('pasien/dashboard.php'));
        }
        exit;
    } else {
        record_failed_login('patient_' . $email);
        set_flash('error', 'Login Gagal', 'Email atau kata sandi tidak cocok.');
        header('Location: ' . base_url('login_pasien.php'));
        exit;
    }
} catch (Exception $e) {
    set_flash('error', 'Kesalahan Sistem', 'Gagal memproses login: ' . $e->getMessage());
    header('Location: ' . base_url('login_pasien.php'));
    exit;
}

