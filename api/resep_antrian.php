<?php
/**
 * DENTARA - API Real-Time Antrean Resep Farmasi
 * File: api/resep_antrian.php
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

// Pastikan user memiliki akses staf
if (!is_logged_in() || !in_array($_SESSION['role'] ?? '', ['apoteker', 'admin', 'dokter'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit;
}

try {
    // Ambil resep hari ini atau yang masih aktif (antrean / disiapkan)
    $today = date('Y-m-d');
    $query = "
        SELECT 
            r.id_resep, r.id_rekam, r.status, r.catatan_apoteker, r.created_at, r.updated_at,
            rm.tanggal_periksa,
            COALESCE(p.nama_pasien, res.nama_tamu) as nama_pasien,
            p.no_rm, p.alergi_obat,
            d.id_dokter, u.nama_lengkap as nama_dokter, d.spesialisasi,
            a.nomor_antrian, a.prioritas
        FROM resep r
        JOIN rekam_medis rm ON r.id_rekam = rm.id_rekam
        LEFT JOIN pasien p ON rm.id_pasien = p.id_pasien
        LEFT JOIN antrian a ON rm.id_antrian = a.id_antrian
        LEFT JOIN reservasi res ON a.id_reservasi = res.id_reservasi
        JOIN dokter d ON rm.id_dokter = d.id_dokter
        JOIN pengguna u ON d.id_pengguna = u.id_pengguna
        WHERE (r.status IN ('antrean', 'disiapkan') OR DATE(r.created_at) = ?)
        ORDER BY 
            CASE r.status 
                WHEN 'antrean' THEN 1 
                WHEN 'disiapkan' THEN 2 
                WHEN 'dikembalikan' THEN 3
                ELSE 4 
            END ASC,
            a.prioritas DESC,
            r.id_resep ASC
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute([$today]);
    $resep_list = $stmt->fetchAll();

    // Ambil detail obat untuk setiap resep
    $data = [];
    $total_antrean = 0;
    $total_disiapkan = 0;

    foreach ($resep_list as $row) {
        if ($row['status'] === 'antrean') $total_antrean++;
        if ($row['status'] === 'disiapkan') $total_disiapkan++;

        $stmt_items = $pdo->prepare("
            SELECT rd.*, o.nama_obat, o.satuan, o.stok
            FROM resep_detail rd
            JOIN obat o ON rd.id_obat = o.id_obat
            WHERE rd.id_resep = ?
        ");
        $stmt_items->execute([$row['id_resep']]);
        $items = $stmt_items->fetchAll();

        $data[] = [
            'id_resep'        => (int)$row['id_resep'],
            'id_rekam'        => (int)$row['id_rekam'],
            'status'          => $row['status'],
            'catatan'         => $row['catatan_apoteker'],
            'waktu'           => waktu_indo($row['created_at']),
            'nomor_antrian'   => $row['nomor_antrian'] ?? '-',
            'prioritas'       => (int)($row['prioritas'] ?? 0),
            'nama_pasien'     => $row['nama_pasien'],
            'no_rm'           => $row['no_rm'] ?? '-',
            'alergi_obat'     => $row['alergi_obat'] ?? '',
            'nama_dokter'     => $row['nama_dokter'],
            'spesialisasi'    => $row['spesialisasi'],
            'total_item'      => count($items),
            'items'           => $items
        ];
    }

    echo json_encode([
        'status' => 'success',
        'timestamp' => time(),
        'counts' => [
            'total_antrean'   => $total_antrean,
            'total_disiapkan' => $total_disiapkan,
            'total_aktif'     => count($data)
        ],
        'data' => $data
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Gagal mengambil data antrean resep: ' . $e->getMessage()
    ]);
}

