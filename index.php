<?php
/**
 * DENTARA - Front Controller
 * Sistem Informasi Manajemen Klinik Gigi Berbasis Web (PHP Native)
 */

// 1. Inisialisasi Konfigurasi & Session
require_once __DIR__ . '/config/app.php';

// 2. PSR-4 Class Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// 3. Muat Global Helper Functions
require_once __DIR__ . '/app/Helpers/functions.php';

use App\Core\Router;

// 4. Inisialisasi Router
$router = new Router();

// ==========================================
// RUTE HALAMAN PUBLIK (TANPA LOGIN)
// ==========================================
$router->get('/', 'HomeController@index');
$router->get('/services', 'HomeController@services');
$router->get('/doctors', 'HomeController@doctors');
$router->get('/faq', 'HomeController@faq');

// Pendaftaran Online Mandiri (Online Booking)
$router->get('/booking', 'BookingController@showBookingForm');
$router->post('/booking', 'BookingController@storeBooking');

// Live Queue Tracker & AJAX Polling API
$router->get('/queue', 'QueueTrackerController@index');
$router->get('/api/queue/status', 'QueueTrackerController@apiLiveStatus');
$router->get('/api/queue/search', 'QueueTrackerController@apiSearchQueue');

// ==========================================
// RUTE AUTENTIKASI (LOGIN & LOGOUT)
// ==========================================
$router->get('/login', 'AuthController@showLogin');
$router->post('/login', 'AuthController@login');
$router->get('/logout', 'AuthController@logout');
$router->get('/register', 'AuthController@showRegister');
$router->post('/register', 'AuthController@register');

// ==========================================
// RUTE DASHBOARD TERPROTEKSI (RBAC)
// ==========================================

// 1. Administrator & Owner
$router->get('/admin/dashboard', 'AdminDashboardController@index', ['role:admin']);

// 2. Front Desk / Resepsionis
$router->get('/receptionist/dashboard', 'ReceptionistDashboardController@index', ['role:receptionist,admin']);

// 3. EMR Dokter Gigi
$router->get('/doctor/dashboard', 'DoctorDashboardController@index', ['role:doctor,admin']);

// 4. Apotek / Farmasi
$router->get('/pharmacy/dashboard', 'PharmacyDashboardController@index', ['role:pharmacist,admin']);

// 5. Kasir / Billing
$router->get('/cashier/dashboard', 'CashierDashboardController@index', ['role:cashier,admin']);

// 6. Portal Pasien
$router->get('/patient/portal', 'PatientDashboardController@index', ['role:patient']);

// Jalankan Dispatcher
$router->dispatch();
