<?php
/**
 * DENTARA API - Pencarian Autocomplete Diagnosa ICD-10 Gigi
 * File: api/icd10_search.php
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db_connect.php';

$q = trim($_GET['q'] ?? '');

try {
    if (empty($q)) {
        $stmt = $pdo->query("SELECT kode, nama_penyakit FROM icd10 ORDER BY kode ASC LIMIT 20");
        $results = $stmt->fetchAll();
    } else {
        $stmt = $pdo->prepare("
            SELECT kode, nama_penyakit 
            FROM icd10 
            WHERE kode LIKE ? OR nama_penyakit LIKE ? 
            ORDER BY kode ASC 
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

