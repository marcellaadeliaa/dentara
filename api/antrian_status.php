<?php
/**
 * DENTARA API - Status Antrean Sentral Seluruh Ruangan Hari Ini
 * File: api/antrian_status.php
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db_connect.php';

$today = date('Y-m-d');
$dokter_id = (int)($_GET['dokter'] ?? 0);

try {
    // Ambil daftar dokter aktif
    $sql_doc = "
        SELECT d.id_dokter, d.spesialisasi, u.nama_lengkap 
        FROM dokter d 
        JOIN pengguna u ON d.id_pengguna = u.id_pengguna 
        WHERE u.status = 'aktif'
    ";
    if ($dokter_id) {
        $sql_doc .= " AND d.id_dokter = " . $dokter_id;
    }
    $sql_doc .= " ORDER BY d.id_dokter ASC";
    $dokters = $pdo->query($sql_doc)->fetchAll();

    $result = [];

    foreach ($dokters as $doc) {
        $doc_id = (int)$doc['id_dokter'];

        // Antrean yang sedang dipanggil / diperiksa saat ini
        $stmt_current = $pdo->prepare("
            SELECT a.*, r.kode_booking,
                   COALESCE(p.nama_pasien, r.nama_tamu) as nama_pasien,
                   p.alergi_obat, p.kondisi_medis
            FROM antrian a
            JOIN reservasi r ON a.id_reservasi = r.id_reservasi
            LEFT JOIN pasien p ON r.id_pasien = p.id_pasien
            WHERE a.id_dokter = ? AND a.tanggal = ? AND a.status IN ('dipanggil', 'diperiksa')
            ORDER BY FIELD(a.status, 'dipanggil', 'diperiksa') ASC, a.waktu_panggil DESC
            LIMIT 1
        ");
        $stmt_current->execute([$doc_id, $today]);
        $current = $stmt_current->fetch();

        // Daftar antrean menunggu / ditunda hari ini
        $stmt_list = $pdo->prepare("
            SELECT a.*, r.kode_booking,
                   COALESCE(p.nama_pasien, r.nama_tamu) as nama_pasien,
                   p.no_rm, p.alergi_obat, l.nama_layanan
            FROM antrian a
            JOIN reservasi r ON a.id_reservasi = r.id_reservasi
            LEFT JOIN pasien p ON r.id_pasien = p.id_pasien
            LEFT JOIN layanan l ON r.id_layanan = l.id_layanan
            WHERE a.id_dokter = ? AND a.tanggal = ? AND a.status IN ('menunggu', 'ditunda')
            ORDER BY a.prioritas DESC, a.nomor_antrian ASC
        ");
        $stmt_list->execute([$doc_id, $today]);
        $waiting_list = $stmt_list->fetchAll();

        // Hitung total selesai hari ini
        $stmt_done = $pdo->prepare("SELECT COUNT(*) FROM antrian WHERE id_dokter = ? AND tanggal = ? AND status = 'selesai'");
        $stmt_done->execute([$doc_id, $today]);
        $total_selesai = (int)$stmt_done->fetchColumn();

        $result[] = [
            'id_dokter'     => $doc_id,
            'nama_dokter'   => $doc['nama_lengkap'],
            'spesialisasi'  => $doc['spesialisasi'],
            'current'       => $current ?: null,
            'waiting'       => $waiting_list,
            'total_selesai' => $total_selesai,
            'total_menunggu'=> count($waiting_list)
        ];
    }

    echo json_encode([
        'status'      => true,
        'tanggal'     => $today,
        'server_time' => date('H:i:s'),
        'data'        => $result
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status'  => false,
        'message' => $e->getMessage()
    ]);
}

