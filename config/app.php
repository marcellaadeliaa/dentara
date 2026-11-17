<?php
/**
 * DENTARA - Application Configuration
 */

// Set Timezone Indonesia (WIB)
date_default_timezone_set('Asia/Jakarta');

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Auto-detect Base URL
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? 80) == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $rawScriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    $scriptDir = str_replace('\\', '/', $rawScriptDir);
    
    // Standarisasi subdirektori
    if ($scriptDir === '/' || $scriptDir === '.' || empty($scriptDir)) {
        $baseUrl = rtrim($protocol . $host, '/');
    } else {
        $baseUrl = rtrim($protocol . $host . '/' . ltrim($scriptDir, '/'), '/');
    }
    
    define('BASE_URL', $baseUrl);
}

// App Constants
define('APP_NAME', 'DENTARA');
define('APP_FULL_NAME', 'Sistem Informasi Manajemen Klinik Gigi');
define('APP_VERSION', '1.0.0');

// Roles Constants
define('ROLE_ADMIN', 'admin');
define('ROLE_DOCTOR', 'doctor');
define('ROLE_RECEPTIONIST', 'receptionist');
define('ROLE_PHARMACIST', 'pharmacist');
define('ROLE_CASHIER', 'cashier');
define('ROLE_PATIENT', 'patient');
