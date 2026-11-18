<?php
/**
 * DENTARA API - Verifikasi Kode Booking & Check-In Mandiri / QR
 * File: api/verifikasi_kode.php
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db_connect.php';

$kode = strtoupper(trim($_POST['kode'] ?? $_GET['kode'] ?? ''));

if (empty($kode)) {
    echo json_encode([
        'status'  => false,
        'message' => 'Kode booking tidak boleh kosong.'
    ]);
    exit;
}

$today = date('Y-m-d');

try {
    $stmt = $pdo->prepare("
        SELECT r.*, 
               COALESCE(p.nama_pasien, r.nama_tamu) as nama_pasien,
               p.no_rm, p.alergi_obat, p.kondisi_medis,
               u.nama_lengkap as nama_dokter, d.spesialisasi
        FROM reservasi r
        LEFT JOIN pasien p ON r.id_pasien = p.id_pasien
        JOIN dokter d ON r.id_dokter = d.id_dokter
        JOIN pengguna u ON d.id_pengguna = u.id_pengguna
        WHERE r.kode_booking = ?
        LIMIT 1
    ");
    $stmt->execute([$kode]);
    $res = $stmt->fetch();

    if (!$res) {
        echo json_encode([
            'status'  => false,
            'message' => "Kode booking '{$kode}' tidak ditemukan dalam sistem."
        ]);
        exit;
    }

    // Cek Tanggal
    if ($res['tanggal'] !== $today) {
        echo json_encode([
            'status'  => false,
            'message' => "Jadwal reservasi ini adalah untuk tanggal " . tanggal_indo($res['tanggal'], true) . ", bukan hari ini."
        ]);
        exit;
    }

    // Cek jika dibatalkan
    if ($res['status'] === 'dibatalkan') {
        echo json_encode([
            'status'  => false,
            'message' => "Reservasi dengan kode '{$kode}' telah dibatalkan."
        ]);
        exit;
    }

    // Cek jika sudah pernah check-in
    if ($res['status'] === 'check_in' || $res['status'] === 'selesai') {
        // Ambil nomor antrean yang sudah ada
        $stmt_ant = $pdo->prepare("SELECT nomor_antrian, status FROM antrian WHERE id_reservasi = ?");
        $stmt_ant->execute([(int)$res['id_reservasi']]);
        $existing = $stmt_ant->fetch();

        echo json_encode([
            'status'        => false,
            'already_done'  => true,
            'message'       => "Pasien sudah check-in sebelumnya dengan nomor antrean A-" . sprintf('%03d', $existing['nomor_antrian'] ?? 0) . " (Status: {$existing['status']}).",
            'nomor_antrian' => $existing['nomor_antrian'] ?? 0
        ]);
        exit;
    }

    // Cek jika masih menunggu konfirmasi resepsionis
    if ($res['status'] === 'menunggu') {
        echo json_encode([
            'status'  => false,
            'message' => "Reservasi ini masih menunggu persetujuan/konfirmasi dari resepsionis."
        ]);
        exit;
    }

    // PROSES CHECK-IN: Buat nomor antrean berurutan per dokter per hari
    $pdo->beginTransaction();

    $id_doc = (int)$res['id_dokter'];
    $stmt_next = $pdo->prepare("
        SELECT COALESCE(MAX(nomor_antrian), 0) + 1 
        FROM antrian 
        WHERE id_dokter = ? AND tanggal = ?
    ");
    $stmt_next->execute([$id_doc, $today]);
    $next_nomor = (int)$stmt_next->fetchColumn();

    // Insert ke tabel antrian
    $stmt_ins = $pdo->prepare("
        INSERT INTO antrian (id_reservasi, id_dokter, tanggal, nomor_antrian, prioritas, status, waktu_checkin)
        VALUES (?, ?, ?, ?, 0, 'menunggu', CURRENT_TIMESTAMP)
    ");
    $stmt_ins->execute([(int)$res['id_reservasi'], $id_doc, $today, $next_nomor]);

    // Update reservasi jadi check_in
    $stmt_upd = $pdo->prepare("UPDATE reservasi SET status = 'check_in' WHERE id_reservasi = ?");
    $stmt_upd->execute([(int)$res['id_reservasi']]);

    $pdo->commit();

    echo json_encode([
        'status'         => true,
        'message'        => 'Check-In Berhasil! Nomor antrean telah diterbitkan.',
        'nomor_antrian'  => $next_nomor,
        'nomor_display'  => 'A-' . sprintf('%03d', $next_nomor),
        'kode_booking'   => $res['kode_booking'],
        'nama_pasien'    => $res['nama_pasien'],
        'no_rm'          => $res['no_rm'],
        'nama_dokter'    => $res['nama_dokter'],
        'spesialisasi'   => $res['spesialisasi'],
        'alergi_obat'    => $res['alergi_obat'],
        'kondisi_medis'  => $res['kondisi_medis']
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode([
        'status'  => false,
        'message' => 'Kesalahan: ' . $e->getMessage()
    ]);
}

