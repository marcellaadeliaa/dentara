<?php
/**
 * DENTARA - Staff Login Process
 * File: includes/process/login_staff_process.php
 */

require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . base_url('login_staff.php'));
    exit;
}

if (!verify_csrf()) {
    set_flash('error', 'Token Tidak Valid', 'Sesi Anda telah kedaluwarsa. Silakan coba lagi.');
    header('Location: ' . base_url('login_staff.php'));
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

// Rate limiting
$rate = check_login_rate_limit('staff_' . $username);
if (!$rate['allowed']) {
    set_flash('error', 'Login Terkunci', $rate['message']);
    header('Location: ' . base_url('login_staff.php'));
    exit;
}

if (empty($username) || empty($password)) {
    set_flash('warning', 'Data Kurang Lengkap', 'Username dan password wajib diisi.');
    header('Location: ' . base_url('login_staff.php'));
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM pengguna WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Cek status aktif
        if ($user['status'] !== 'aktif') {
            set_flash('error', 'Akun Nonaktif', 'Akun staff Anda sudah dinonaktifkan. Hubungi administrator.');
            header('Location: ' . base_url('login_staff.php'));
            exit;
        }

        // Login sukses: Reset rate limit
        clear_login_rate_limit('staff_' . $username);
        session_regenerate_id(true);

        $_SESSION['id_pengguna']  = (int)$user['id_pengguna'];
        $_SESSION['username']     = $user['username'];
        $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
        $_SESSION['email']        = $user['email'];
        $_SESSION['role']         = $user['role'];

        // Jika dokter, ambil id_dokter
        if ($user['role'] === 'dokter') {
            $stmt_doc = $pdo->prepare("SELECT id_dokter FROM dokter WHERE id_pengguna = ?");
            $stmt_doc->execute([(int)$user['id_pengguna']]);
            $_SESSION['id_dokter'] = (int)$stmt_doc->fetchColumn();
        }

        catat_log($pdo, 'LOGIN_STAFF', "Staff {$user['nama_lengkap']} ({$user['role']}) berhasil login.", (int)$user['id_pengguna']);

        set_flash('success', 'Selamat Datang!', "Halo {$user['nama_lengkap']}, selamat bertugas.");
        
        $intended = $_SESSION['intended_url'] ?? '';
        unset($_SESSION['intended_url']);
        
        if ($intended && !str_contains($intended, 'login')) {
            header('Location: ' . $intended);
        } else {
            header('Location: ' . get_dashboard_redirect($user['role']));
        }
        exit;
    } else {
        record_failed_login('staff_' . $username);
        set_flash('error', 'Login Gagal', 'Username atau password yang Anda masukkan salah.');
        header('Location: ' . base_url('login_staff.php'));
        exit;
    }
} catch (Exception $e) {
    set_flash('error', 'Kesalahan Sistem', 'Gagal memproses login: ' . $e->getMessage());
    header('Location: ' . base_url('login_staff.php'));
    exit;
}

