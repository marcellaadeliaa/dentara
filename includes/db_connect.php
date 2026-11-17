<?php
/**
 * DENTARA - Database Connection PDO
 * File: includes/db_connect.php
 */

$db_host = 'localhost';
$db_name = 'dentara';
$db_user = 'root';
$db_pass = '';
$db_port = 3306;

try {
    $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ]);
} catch (PDOException $e) {
    // Tampilkan pesan ramah jika gagal koneksi
    if (php_sapi_name() === 'cli') {
        fwrite(STDERR, "Database connection error: " . $e->getMessage() . "\n");
        exit(1);
    }
    http_response_code(500);
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Koneksi Database Gagal - DENTARA</title>
        <style>
            body { font-family: 'Nunito', sans-serif; background: #F7FBFC; color: #1E293B; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; padding: 20px; box-sizing: border-box; }
            .box { background: #fff; padding: 32px; border-radius: 16px; box-shadow: 0 10px 30px rgba(43,181,160,0.15); max-width: 500px; text-align: center; border-top: 6px solid #EF4444; }
            h2 { color: #EF4444; margin-top: 0; font-family: 'Fredoka', cursive, sans-serif; }
            p { color: #64748B; font-size: 15px; line-height: 1.6; }
            code { background: #F1F5F9; padding: 4px 8px; border-radius: 6px; font-size: 13px; color: #0F172A; }
        </style>
    </head>
    <body>
        <div class="box">
            <h2>Koneksi Database Terputus</h2>
            <p>Sistem DENTARA tidak dapat terhubung ke server database MySQL. Pastikan service MySQL di Laragon/XAMPP sudah aktif dan database <code>dentara</code> sudah dibuat.</p>
            <p><small style="color: #94A3B8;">Detail teknis: <?= htmlspecialchars($e->getMessage()) ?></small></p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

