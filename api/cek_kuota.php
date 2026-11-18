<?php
/**
 * DENTARA API - Cek Kuota & Status Jadwal Dokter
 * File: api/cek_kuota.php
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db_connect.php';

$dokter_id = (int)($_GET['dokter'] ?? 0);
$tanggal   = trim($_GET['tanggal'] ?? '');

if (!$dokter_id || empty($tanggal)) {
    echo json_encode([
        'status'  => false,
        'message' => 'Parameter dokter dan tanggal wajib diisi.'
    ]);
    exit;
}

if ($tanggal < date('Y-m-d')) {
    echo json_encode([
        'status'  => false,
        'message' => 'Tanggal tidak boleh di masa lampau.'
    ]);
    exit;
}

try {
    // 1. Cek Libur Mendadak
    $stmt_libur = $pdo->prepare("SELECT alasan FROM libur_mendadak WHERE tanggal_libur = ? AND status = 'aktif'");
    $stmt_libur->execute([$tanggal]);
    $libur = $stmt_libur->fetchColumn();
    if ($libur) {
        echo json_encode([
            'status'  => false,
            'message' => 'Klinik libur: ' . $libur
        ]);
        exit;
    }

    // 2. Cek Jadwal Hari Klinik
    $hari_map = [
        'Sun' => 'Minggu', 'Mon' => 'Senin', 'Tue' => 'Selasa',
        'Wed' => 'Rabu', 'Thu' => 'Kamis', 'Fri' => 'Jumat', 'Sat' => 'Sabtu'
    ];
    $hari_booking = $hari_map[date('D', strtotime($tanggal))];

    $stmt_klinik = $pdo->prepare("SELECT status FROM jadwal_klinik WHERE hari = ?");
    $stmt_klinik->execute([$hari_booking]);
    $status_klinik = $stmt_klinik->fetchColumn();
    if ($status_klinik === 'Tutup') {
        echo json_encode([
            'status'  => false,
            'message' => "Klinik tutup pada hari {$hari_booking}."
        ]);
        exit;
    }

    // 3. Cek Cuti Dokter
    $stmt_cuti = $pdo->prepare("SELECT alasan FROM cuti_dokter WHERE id_dokter = ? AND ? BETWEEN tanggal_mulai AND tanggal_selesai AND status = 'aktif'");
    $stmt_cuti->execute([$dokter_id, $tanggal]);
    $alasan_cuti = $stmt_cuti->fetchColumn();
    if ($alasan_cuti) {
        echo json_encode([
            'status'  => false,
            'message' => 'Dokter sedang cuti: ' . $alasan_cuti
        ]);
        exit;
    }

    // 4. Cek Jadwal Praktik Dokter
    $stmt_j = $pdo->prepare("SELECT * FROM jadwal_praktik_dokter WHERE id_dokter = ? AND hari = ? AND status = 'aktif'");
    $stmt_j->execute([$dokter_id, $hari_booking]);
    $jadwal = $stmt_j->fetch();

    if (!$jadwal) {
        echo json_encode([
            'status'  => false,
            'message' => "Dokter tidak memiliki jadwal praktik pada hari {$hari_booking}."
        ]);
        exit;
    }

    // 5. Hitung Reservasi Terisi
    $stmt_res = $pdo->prepare("SELECT COUNT(*) FROM reservasi WHERE id_dokter = ? AND tanggal = ? AND status != 'dibatalkan'");
    $stmt_res->execute([$dokter_id, $tanggal]);
    $terisi = (int)$stmt_res->fetchColumn();

    $kuota_total = (int)$jadwal['kuota_per_hari'];
    $sisa = max(0, $kuota_total - $terisi);

    if ($sisa <= 0) {
        echo json_encode([
            'status'  => false,
            'message' => "Kuota reservasi hari ini sudah penuh ({$terisi}/{$kuota_total})."
        ]);
        exit;
    }

    echo json_encode([
        'status'       => true,
        'hari'         => $hari_booking,
        'jam_mulai'    => substr($jadwal['jam_mulai'], 0, 5),
        'jam_selesai'  => substr($jadwal['jam_selesai'], 0, 5),
        'kuota_total'  => $kuota_total,
        'terisi'       => $terisi,
        'sisa'         => $sisa
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status'  => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

