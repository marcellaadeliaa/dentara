<?php
/**
 * DENTARA Database Migration & Seeder CLI Runner
 * Menjalankan schema.sql dan seeder.sql pada database MySQL lokal (Laragon)
 */

$host = '127.0.0.1';
$port = '3306';
$user = 'root';
$pass = '';
$dbName = 'dentara';

echo "=== [DENTARA] Database Migration & Seeder Runner ===\n";

try {
    // 1. Koneksi awal ke server MySQL (tanpa memilih database terlebih dahulu)
    echo "[1/4] Menghubungkan ke MySQL di {$host}:{$port}...\n";
    $pdo = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
    ]);
    echo "      Koneksi berhasil!\n";

    // 2. Eksekusi Schema
    echo "[2/4] Menjalankan schema.sql...\n";
    $schemaFile = __DIR__ . '/schema.sql';
    if (!file_exists($schemaFile)) {
        throw new Exception("File schema.sql tidak ditemukan di: " . $schemaFile);
    }
    $schemaSql = file_get_contents($schemaFile);
    $pdo->exec($schemaSql);
    echo "      Struktur tabel berhasil dibuat!\n";

    // Re-connect langsung ke database dentara
    $pdo = new PDO("mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // 3. Eksekusi Seeder
    echo "[3/4] Menjalankan seeder.sql...\n";
    $seederFile = __DIR__ . '/seeder.sql';
    if (!file_exists($seederFile)) {
        throw new Exception("File seeder.sql tidak ditemukan di: " . $seederFile);
    }
    
    $seederSql = file_get_contents($seederFile);
    $pdo->setAttribute(PDO::MYSQL_ATTR_MULTI_STATEMENTS, true);
    $pdo->exec($seederSql);

    // Update password hash ke semua user default ('password123')
    $defaultHash = password_hash('password123', PASSWORD_BCRYPT);
    $updateStmt = $pdo->prepare("UPDATE users SET password = :hash");
    $updateStmt->execute([':hash' => $defaultHash]);
    echo "      Data awal (seeder) berhasil dimasukkan & password default 'password123' terenkripsi!\n";

    // 4. Verifikasi Jumlah Tabel & Data
    echo "[4/4] Memverifikasi data yang terbuat...\n";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "      Total tabel terdaftar: " . count($tables) . "\n";
    foreach ($tables as $t) {
        $count = $pdo->query("SELECT COUNT(*) FROM `{$t}`")->fetchColumn();
        echo "      - {$t}: {$count} baris\n";
    }

    echo "\n=== MIGRASI & SEEDER SELESAI DENGAN SUKSES! ===\n";

} catch (Exception $e) {
    echo "\n[ERROR]: " . $e->getMessage() . "\n";
    exit(1);
}
