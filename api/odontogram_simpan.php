<?php
/**
 * DENTARA API - Simpan Kondisi Odontogram Pasien
 * File: api/odontogram_simpan.php
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => false, 'message' => 'Metode request tidak diizinkan.']);
    exit;
}

$id_pasien = (int)($_POST['id_pasien'] ?? 0);
$id_rekam  = !empty($_POST['id_rekam']) ? (int)$_POST['id_rekam'] : null;
$state_raw = $_POST['state'] ?? '{}';

$state = json_decode($state_raw, true);

if (!$id_pasien || !is_array($state)) {
    echo json_encode(['status' => false, 'message' => 'Data odontogram atau ID pasien tidak valid.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Hapus kondisi lama untuk pasien ini agar state diperbarui rapi
    $stmt_del = $pdo->prepare("DELETE FROM odontogram WHERE id_pasien = ?");
    $stmt_del->execute([$id_pasien]);

    $stmt_ins = $pdo->prepare("
        INSERT INTO odontogram (id_pasien, id_rekam, nomor_gigi, permukaan, kondisi)
        VALUES (?, ?, ?, ?, ?)
    ");

    foreach ($state as $toothNum => $surfaces) {
        if (!is_array($surfaces)) continue;
        foreach ($surfaces as $surfName => $cond) {
            $stmt_ins->execute([$id_pasien, $id_rekam, (string)$toothNum, (string)$surfName, (string)$cond]);
        }
    }

    $pdo->commit();

    echo json_encode([
        'status'  => true,
        'message' => 'Odontogram berhasil disimpan ke rekam medis!'
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode([
        'status'  => false,
        'message' => 'Gagal simpan odontogram: ' . $e->getMessage()
    ]);
}

