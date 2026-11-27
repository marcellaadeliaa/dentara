<?php
/**
 * DENTARA API - Pencarian Obat & Cek Stok Resep
 * File: api/obat_search.php
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db_connect.php';

$q = trim($_GET['q'] ?? '');

try {
    if (empty($q)) {
        $stmt = $pdo->query("SELECT id_obat, nama_obat, kategori, satuan, harga_jual, stok, exp_date FROM obat WHERE stok > 0 ORDER BY nama_obat ASC LIMIT 25");
        $results = $stmt->fetchAll();
    } else {
        $stmt = $pdo->prepare("
            SELECT id_obat, nama_obat, kategori, satuan, harga_jual, stok, exp_date 
            FROM obat 
            WHERE nama_obat LIKE ? OR kategori LIKE ? 
            ORDER BY nama_obat ASC 
            LIMIT 25
        ");
        $stmt->execute(["%{$q}%", "%{$q}%"]);
        $results = $stmt->fetchAll();
    }

    echo json_encode([
        'status' => true,
        'data'   => $results
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status'  => false,
        'message' => $e->getMessage()
    ]);
}

