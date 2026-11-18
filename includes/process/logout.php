<?php
/**
 * DENTARA - Logout Handler
 * File: includes/process/logout.php
 */

require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../db_connect.php';

if (!empty($_SESSION['id_pengguna'])) {
    catat_log($pdo, 'LOGOUT_STAFF', "Staff {$_SESSION['nama_lengkap']} ({$_SESSION['role']}) keluar sistem.", (int)$_SESSION['id_pengguna']);
}

// Hapus seluruh session
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();
session_start();

set_flash('info', 'Sampai Jumpa!', 'Anda telah berhasil keluar dari sistem DENTARA.');
header('Location: ' . base_url('index.php'));
exit;

