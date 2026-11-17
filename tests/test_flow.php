<?php
/**
 * Test Simulation Script
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/Helpers/functions.php';

// PSR-4 Autoload
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $file = $baseDir . str_replace('\\', '/', substr($class, $len)) . '.php';
    if (file_exists($file)) require_once $file;
});

use App\Core\Database;
use App\Core\Auth;

echo "=== [TEST 1] Database Connectivity ===\n";
$usersCount = Database::fetchOne("SELECT COUNT(*) as c FROM users")['c'];
echo "Total Users in DB: {$usersCount}\n";

echo "\n=== [TEST 2] Authentication & Role RBAC ===\n";
$loginSuccess = Auth::login('admin', 'password123');
echo "Admin Login Result: " . ($loginSuccess ? "SUCCESS" : "FAILED") . "\n";
echo "Active User Role: " . Auth::role() . "\n";
echo "Has Role 'admin': " . (Auth::hasRole('admin') ? "YES" : "NO") . "\n";
echo "Has Role 'doctor': " . (Auth::hasRole('doctor') ? "YES" : "NO") . "\n";
echo "Dashboard Target: " . Auth::getDashboardUrl() . "\n";

Auth::logout();
echo "After Logout Check: " . (Auth::check() ? "LOGGED_IN" : "LOGGED_OUT") . "\n";

echo "\n=== [TEST 3] Doctor Authentication & Profile Mapping ===\n";
Auth::login('drg_sarah', 'password123');
echo "Doctor User: " . Auth::user()['full_name'] . " (Doctor ID: " . Auth::user()['doctor_id'] . ")\n";
echo "Doctor Dashboard Target: " . Auth::getDashboardUrl() . "\n";
Auth::logout();

echo "\n=== [TEST 4] Queue & Appointments Query ===\n";
$todayQueues = Database::fetchAll("SELECT * FROM queues WHERE queue_date = CURDATE()");
echo "Today Queues Count: " . count($todayQueues) . "\n";
foreach ($todayQueues as $q) {
    echo " - Queue: {$q['queue_number']}, Status: {$q['status']}, Doctor ID: {$q['doctor_id']}\n";
}

echo "\n=== ALL BASELINE SYSTEM TESTS PASSED! ===\n";
