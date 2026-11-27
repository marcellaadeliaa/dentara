<?php
/**
 * DENTARA API - Live Queue Tracker Publik
 * File: api/antrian_tracker.php
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db_connect.php';

$dokter_id = (int)($_GET['dokter'] ?? 0);
$kode      = trim($_GET['kode'] ?? '');
$today     = date('Y-m-d');

try {
    // 1. Ambil dokter default jika tidak ditentukan
    if (!$dokter_id) {
        $stmt_def = $pdo->query("SELECT id_dokter FROM dokter LIMIT 1");
        $dokter_id = (int)$stmt_def->fetchColumn();
    }

    // 2. Antrean yang sedang dipanggil/diperiksa saat ini
    $stmt_current = $pdo->prepare("
        SELECT a.*, r.kode_booking,
               COALESCE(p.nama_pasien, r.nama_tamu) as nama_pasien,
               u.nama_lengkap as nama_dokter, d.spesialisasi
        FROM antrian a
        JOIN reservasi r ON a.id_reservasi = r.id_reservasi
        LEFT JOIN pasien p ON r.id_pasien = p.id_pasien
        JOIN dokter d ON a.id_dokter = d.id_dokter
        JOIN pengguna u ON d.id_pengguna = u.id_pengguna
        WHERE a.id_dokter = ? AND a.tanggal = ? AND a.status IN ('dipanggil', 'diperiksa')
        ORDER BY FIELD(a.status, 'dipanggil', 'diperiksa') ASC, a.waktu_panggil DESC
        LIMIT 1
    ");
    $stmt_current->execute([$dokter_id, $today]);
    $current = $stmt_current->fetch();

    // Mask nama pasien untuk privasi publik (contoh: Ah*** Da***)
    if ($current) {
        $words = explode(' ', $current['nama_pasien'] ?? '');
        $masked = [];
        foreach ($words as $w) {
            $masked[] = (strlen($w) > 2) ? substr($w, 0, 2) . '***' : $w;
        }
        $current['nama_pasien_masked'] = implode(' ', $masked);
    }

    // 3. Daftar antrean menunggu
    $stmt_waiting = $pdo->prepare("
        SELECT a.nomor_antrian, a.prioritas, a.status
        FROM antrian a
        WHERE a.id_dokter = ? AND a.tanggal = ? AND a.status = 'menunggu'
        ORDER BY a.prioritas DESC, a.nomor_antrian ASC
        LIMIT 10
    ");
    $stmt_waiting->execute([$dokter_id, $today]);
    $waiting_list = $stmt_waiting->fetchAll();

    // 4. Jika ada input kode booking atau nomor HP pasien
    $my_queue = null;
    if ($kode) {
        $stmt_my = $pdo->prepare("
            SELECT a.*, r.kode_booking, r.tanggal,
                   COALESCE(p.nama_pasien, r.nama_tamu) as nama_pasien,
                   u.nama_lengkap as nama_dokter
            FROM reservasi r
            LEFT JOIN antrian a ON a.id_reservasi = r.id_reservasi
            LEFT JOIN pasien p ON r.id_pasien = p.id_pasien
            JOIN dokter d ON r.id_dokter = d.id_dokter
            JOIN pengguna u ON d.id_pengguna = u.id_pengguna
            WHERE (r.kode_booking = ? OR p.no_hp = ? OR r.no_hp_tamu = ?) AND r.tanggal = ?
            ORDER BY r.id_reservasi DESC
            LIMIT 1
        ");
        $stmt_my->execute([$kode, $kode, $kode, $today]);
        $res = $stmt_my->fetch();

        if ($res) {
            if (!$res['nomor_antrian']) {
                $my_queue = [
                    'found'         => true,
                    'status_cek'    => 'belum_checkin',
                    'kode_booking'  => $res['kode_booking'],
                    'nama_pasien'   => $res['nama_pasien'],
                    'nama_dokter'   => $res['nama_dokter'],
                    'pesan'         => 'Reservasi terdaftar tetapi Anda belum melakukan check-in di klinik.'
                ];
            } else {
                // Hitung sisa pasien di depan nomor ini
                $stmt_ahead = $pdo->prepare("
                    SELECT COUNT(*) FROM antrian 
                    WHERE id_dokter = ? AND tanggal = ? AND status = 'menunggu' 
                    AND (prioritas > ? OR (prioritas = ? AND nomor_antrian < ?))
                ");
                $stmt_ahead->execute([
                    $res['id_dokter'], $today, 
                    $res['prioritas'], $res['prioritas'], $res['nomor_antrian']
                ]);
                $sisa = (int)$stmt_ahead->fetchColumn();

                // Jika sedang dipanggil
                if ($res['status'] === 'dipanggil') {
                    $sisa = 0;
                }

                $estimasi_menit = $sisa * 20; // Rata-rata 20 menit per pasien

                $my_queue = [
                    'found'          => true,
                    'status_cek'     => 'terdaftar',
                    'status_antrian' => $res['status'],
                    'nomor_antrian'  => $res['nomor_antrian'],
                    'kode_booking'   => $res['kode_booking'],
                    'nama_pasien'    => $res['nama_pasien'],
                    'nama_dokter'    => $res['nama_dokter'],
                    'sisa_pasien'    => $sisa,
                    'estimasi_menit' => $estimasi_menit
                ];
            }
        } else {
            $my_queue = [
                'found' => false,
                'pesan' => 'Tidak ditemukan reservasi hari ini dengan kode atau nomor HP tersebut.'
            ];
        }
    }

    echo json_encode([
        'status'         => true,
        'current_call'   => $current,
        'waiting_list'   => $waiting_list,
        'my_queue'       => $my_queue,
        'server_time'    => date('H:i:s')
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status'  => false,
        'message' => $e->getMessage()
    ]);
}

