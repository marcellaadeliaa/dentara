<?php
/**
 * DENTARA - Patient Login Process (Supports Seamless AJAX & Standard POST)
 * File: includes/process/login_pasien_process.php
 */

require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../auth.php';

$is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || 
           (isset($_SERVER['CONTENT_TYPE']) && str_contains($_SERVER['CONTENT_TYPE'], 'application/json'));

function respond_patient_login(bool $success, string $message, string $redirect = '', string $flash_type = 'error'): void {
    global $is_ajax;
    if ($is_ajax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success'  => $success,
            'message'  => $message,
            'redirect' => $redirect
        ]);
        exit;
    }

    if ($success) {
        set_flash('success', 'Selamat Datang!', $message);
        header('Location: ' . ($redirect ?: base_url('pasien/dashboard.php')));
    } else {
        set_flash($flash_type, 'Gagal Masuk', $message);
        header('Location: ' . base_url('login_pasien.php'));
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond_patient_login(false, 'Metode permintaan tidak valid.');
}

if (!verify_csrf()) {
    respond_patient_login(false, 'Sesi formulir telah kedaluwarsa. Silakan ulangi.');
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// Rate limiting
$rate = check_login_rate_limit('patient_' . $email);
if (!$rate['allowed']) {
    respond_patient_login(false, $rate['message']);
}

if (empty($email) || empty($password)) {
    respond_patient_login(false, 'Email dan password akun pasien wajib diisi.');
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

        $intended = $_SESSION['intended_patient_url'] ?? '';
        unset($_SESSION['intended_patient_url']);

        $target = ($intended && !str_contains($intended, 'login')) ? $intended : base_url('pasien/dashboard.php');

        respond_patient_login(true, "Halo {$nama}, senang bertemu Anda kembali!", $target);
    } else {
        record_failed_login('patient_' . $email);
        respond_patient_login(false, 'Email atau kata sandi yang Anda masukkan tidak cocok.');
    }
} catch (Exception $e) {
    respond_patient_login(false, 'Gagal memproses login: ' . $e->getMessage());
}

