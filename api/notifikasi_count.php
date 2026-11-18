<?php
/**
 * DENTARA - API Unread Notification Count
 * File: api/notifikasi_count.php
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

try {
    if (is_patient()) {
        $id_akun = current_patient_account_id();
        // Ambil semua pasien di akun ini
        $stmt_p = $pdo->prepare("SELECT id_pasien FROM pasien WHERE id_akun = ?");
        $stmt_p->execute([$id_akun]);
        $pasien_ids = $stmt_p->fetchAll(PDO::FETCH_COLUMN);

        $unread_count = 0;
        if (!empty($pasien_ids)) {
            $in_clause = implode(',', array_fill(0, count($pasien_ids), '?'));
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM pesan_notifikasi WHERE id_pasien IN ($in_clause) AND status = 'belum_dibaca'");
            $stmt->execute($pasien_ids);
            $unread_count = (int)$stmt->fetchColumn();
        }

        echo json_encode(['status' => 'success', 'unread' => $unread_count]);
        exit;
    }

    if (is_logged_in()) {
        // Untuk staff: bisa menghitung antrean resep atau tagihan belum bayar
        $stmt = $pdo->query("SELECT COUNT(*) FROM tagihan WHERE status = 'belum_bayar'");
        $unpaid = (int)$stmt->fetchColumn();

        $stmt_r = $pdo->query("SELECT COUNT(*) FROM resep WHERE status = 'antrean'");
        $resep_antre = (int)$stmt_r->fetchColumn();

        echo json_encode([
            'status' => 'success',
            'tagihan_belum_bayar' => $unpaid,
            'resep_antrean' => $resep_antre
        ]);
        exit;
    }

    echo json_encode(['status' => 'guest', 'unread' => 0]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

