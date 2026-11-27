<?php
/**
 * DENTARA - Authentication & Role-Based Access Control (RBAC)
 * File: includes/auth.php
 */

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db_connect.php';

/**
 * Validasi Role Pengguna Staff
 * @param array|string $allowed_roles
 */
function require_role($allowed_roles): void {
    if (!is_array($allowed_roles)) {
        $allowed_roles = [$allowed_roles];
    }

    // Cek jika pengguna belum login sama sekali
    if (empty($_SESSION['id_pengguna']) || empty($_SESSION['role'])) {
        // Simpan intended URL
        $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'] ?? '';
        set_flash('warning', 'Akses Terbatas', 'Silakan masuk dengan akun staff Anda terlebih dahulu.');
        header('Location: ' . base_url('login_staff.php'));
        exit;
    }

    // Validasi apakah akun staff masih aktif di database
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT status FROM pengguna WHERE id_pengguna = ?");
        $stmt->execute([(int)$_SESSION['id_pengguna']]);
        $status = $stmt->fetchColumn();

        if (!$status || $status === 'nonaktif') {
            session_unset();
            session_destroy();
            session_start();
            set_flash('error', 'Akun Dinonaktifkan', 'Akun staff Anda sudah dinonaktifkan. Silakan hubungi manajemen klinik.');
            header('Location: ' . base_url('login_staff.php'));
            exit;
        }
    } catch (Exception $e) {
        // Lanjutkan jika ada gangguan minor koneksi
    }

    $current_role = $_SESSION['role'];

    // Cek kecocokan role
    if (!in_array($current_role, $allowed_roles, true)) {
        set_flash('error', 'Akses Ditolak (403)', 'Anda tidak memiliki hak akses untuk membuka halaman tersebut.');
        header('Location: ' . base_url('403.php'));
        exit;
    }
}

/**
 * Validasi Login untuk Portal Pasien
 */
function require_patient(): void {
    if (empty($_SESSION['id_akun_pasien'])) {
        $_SESSION['intended_patient_url'] = $_SERVER['REQUEST_URI'] ?? '';
        set_flash('info', 'Silakan Masuk', 'Silakan login akun pasien Anda untuk mengakses layanan ini.');
        header('Location: ' . base_url('login_pasien.php'));
        exit;
    }
}

/**
 * Dapatkan data user staff yang sedang login
 */
function current_user(): ?array {
    if (empty($_SESSION['id_pengguna'])) {
        return null;
    }
    return [
        'id_pengguna'  => $_SESSION['id_pengguna'],
        'nama_lengkap' => $_SESSION['nama_lengkap'] ?? 'Staff',
        'username'     => $_SESSION['username'] ?? '',
        'email'        => $_SESSION['email'] ?? '',
        'role'         => $_SESSION['role'] ?? '',
        'id_dokter'    => $_SESSION['id_dokter'] ?? null
    ];
}

/**
 * Dapatkan data akun pasien yang sedang login
 */
function current_patient_account(): ?array {
    if (empty($_SESSION['id_akun_pasien'])) {
        return null;
    }
    return [
        'id_akun'   => $_SESSION['id_akun_pasien'],
        'email'     => $_SESSION['email_pasien'] ?? '',
        'no_hp'     => $_SESSION['no_hp_pasien'] ?? '',
        'id_pasien' => $_SESSION['id_pasien_aktif'] ?? null
    ];
}

/**
 * Mengarahkan redirect URL sesuai role pengguna
 */
function get_dashboard_redirect(string $role): string {
    return match ($role) {
        'admin'       => base_url('admin/dashboard.php'),
        'dokter'      => base_url('dokter/panel_antrian.php'),
        'resepsionis' => base_url('resepsionis/dashboard_antrian.php'),
        'apoteker'    => base_url('apoteker/antrean_resep.php'),
        'kasir'       => base_url('kasir/dashboard_tagihan.php'),
        'pasien'      => base_url('pasien/dashboard.php'),
        default       => base_url('index.php')
    };
}

/**
 * Helper Rate Limiter Login berbasis Session
 */
function check_login_rate_limit(string $key, int $max_attempts = 5, int $lockout_seconds = 300): array {
    $session_key = 'rate_limit_' . md5($key);
    $data = $_SESSION[$session_key] ?? ['attempts' => 0, 'locked_until' => 0];

    $now = time();
    if ($data['locked_until'] > $now) {
        $remaining = $data['locked_until'] - $now;
        return [
            'allowed'   => false,
            'remaining' => $remaining,
            'message'   => "Terlalu banyak percobaan gagal. Silakan coba lagi dalam " . ceil($remaining / 60) . " menit."
        ];
    }

    // Reset jika waktu kunci sudah lewat
    if ($data['locked_until'] > 0 && $data['locked_until'] <= $now) {
        $data = ['attempts' => 0, 'locked_until' => 0];
        $_SESSION[$session_key] = $data;
    }

    return ['allowed' => true, 'remaining' => 0, 'message' => ''];
}

function record_failed_login(string $key, int $max_attempts = 5, int $lockout_seconds = 300): void {
    $session_key = 'rate_limit_' . md5($key);
    $data = $_SESSION[$session_key] ?? ['attempts' => 0, 'locked_until' => 0];
    $data['attempts']++;

    if ($data['attempts'] >= $max_attempts) {
        $data['locked_until'] = time() + $lockout_seconds;
    }
    $_SESSION[$session_key] = $data;
}

function clear_login_rate_limit(string $key): void {
    $session_key = 'rate_limit_' . md5($key);
    unset($_SESSION[$session_key]);
}

