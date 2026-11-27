<?php
/**
 * DENTARA - Cetak Lembar Rekam Medis Resmi (Print CSS)
 * File: dokter/cetak_rekam.php
 */

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';

require_role(['dokter', 'admin', 'resepsionis']);

$id_rekam = (int)($_GET['id_rekam'] ?? 0);
$id_antrian = (int)($_GET['id_antrian'] ?? 0);

if (!$id_rekam && !$id_antrian) {
    echo "ID Rekam Medis tidak valid.";
    exit;
}

global $pdo;

// Query rekam medis lengkap
$sql = "
    SELECT rm.*, 
           p.*, 
           w.nama_wali, w.hubungan as hubungan_wali, w.kontak as kontak_wali,
           u.nama_lengkap as nama_dokter, d.spesialisasi, d.no_str
    FROM rekam_medis rm
    JOIN pasien p ON rm.id_pasien = p.id_pasien
    LEFT JOIN wali_pasien w ON p.id_pasien = w.id_pasien
    JOIN dokter d ON rm.id_dokter = d.id_dokter
    JOIN pengguna u ON d.id_pengguna = u.id_pengguna
    WHERE " . ($id_rekam ? "rm.id_rekam = ?" : "rm.id_antrian = ?");

$stmt = $pdo->prepare($sql);
$stmt->execute([$id_rekam ?: $id_antrian]);
$rm = $stmt->fetch();

if (!$rm) {
    echo "Data rekam medis tidak ditemukan.";
    exit;
}

$id_rm = (int)$rm['id_rekam'];

// ICD-10 Diagnosa
$stmt_diag = $pdo->prepare("SELECT rd.*, icd.nama_penyakit FROM rekam_diagnosis rd JOIN icd10 icd ON rd.kode_icd10 = icd.kode WHERE rd.id_rekam = ?");
$stmt_diag->execute([$id_rm]);
$diagnoses = $stmt_diag->fetchAll();

// Tindakan
$stmt_act = $pdo->prepare("SELECT rt.*, l.nama_layanan FROM rekam_tindakan rt JOIN layanan l ON rt.id_layanan = l.id_layanan WHERE rt.id_rekam = ?");
$stmt_act->execute([$id_rm]);
$tindakans = $stmt_act->fetchAll();

// Resep
$stmt_rx = $pdo->prepare("
    SELECT rxd.*, o.nama_obat, o.satuan 
    FROM resep r 
    JOIN resep_detail rxd ON r.id_resep = rxd.id_resep 
    JOIN obat o ON rxd.id_obat = o.id_obat 
    WHERE r.id_rekam = ?
");
$stmt_rx->execute([$id_rm]);
$reseps = $stmt_rx->fetchAll();

// Odontogram
$stmt_od = $pdo->prepare("SELECT * FROM odontogram WHERE id_rekam = ? OR id_pasien = ? ORDER BY nomor_gigi ASC");
$stmt_od->execute([$id_rm, $rm['id_pasien']]);
$odontos = $stmt_od->fetchAll();

// Info Klinik
$stmt_k = $pdo->query("SELECT * FROM pengaturan_klinik LIMIT 1");
$klinik = $stmt_k->fetch();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Lembar Rekam Medis - <?= e($rm['no_rm']) ?> - <?= e($rm['nama_pasien']) ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= base_url('assets/img/favicon.svg') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&family=Fredoka:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/css/design_tokens.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/print.css') ?>">
    <style>
        body { font-family: 'Nunito', Arial, sans-serif; background: #fff; color: #1E293B; margin: 0; padding: 24px; font-size: 13px; line-height: 1.5; }
        .doc-container { max-width: 800px; margin: 0 auto; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .section-title { font-family: 'Fredoka', sans-serif; font-size: 14px; font-weight: 700; color: #0F766E; border-bottom: 1.5px solid #0F766E; padding-bottom: 4px; margin: 16px 0 8px; text-transform: uppercase; }
        .soap-box { background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 6px; padding: 8px 12px; margin-bottom: 6px; }
    </style>
</head>
<body>
    <div class="doc-container">
        <!-- Floating Print Actions -->
        <div class="no-print" style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; background: #F1F5F9; padding: 12px 18px; border-radius: 8px;">
            <div>
                <strong>Cetak Lembar Rekam Medis Elektronik (EMR)</strong>
            </div>
            <div style="display: flex; gap: 8px;">
                <button onclick="window.print()" class="btn btn-primary btn-sm" style="background:#2BB5A0; color:#fff; border:none; padding:8px 16px; border-radius:999px; cursor:pointer; font-weight:bold;">
                    <i class="bx bx-printer"></i> Cetak / Simpan PDF
                </button>
                <button onclick="window.close()" class="btn btn-secondary btn-sm" style="background:#E2E8F0; border:none; padding:8px 16px; border-radius:999px; cursor:pointer;">
                    Tutup Halaman
                </button>
            </div>
        </div>

        <!-- KOP SURAT KLINIK RESMI -->
        <div class="print-letterhead">
            <div style="display: flex; align-items: center; gap: 16px;">
                <?= render_logo('md', false) ?>
                <div class="clinic-info">
                    <h2><?= e($klinik['nama_klinik'] ?? 'DENTARA DENTAL CARE') ?></h2>
                    <p><?= e($klinik['alamat'] ?? '') ?></p>
                    <p>Telp: <?= e($klinik['telepon'] ?? '') ?> &bull; WhatsApp: <?= e($klinik['whatsapp'] ?? '') ?> &bull; Email: <?= e($klinik['email'] ?? '') ?></p>
                </div>
            </div>
            <div style="text-align: right; border-left: 2px solid #E2E8F0; padding-left: 14px;">
                <div style="font-family:'Fredoka',sans-serif; font-size: 16px; font-weight: 700; color:#0F766E;">LEMBAR REKAM MEDIS</div>
                <div style="font-size: 11px; color:#64748B;">No. Reg: <?= e($rm['no_rm']) ?></div>
            </div>
        </div>

        <!-- IDENTITAS PASIEN & DOKTER -->
        <div class="grid-2" style="margin-bottom: 16px; font-size: 12px;">
            <div style="background: #F8FAFC; padding: 10px 14px; border-radius: 6px; border: 1px solid #E2E8F0;">
                <div style="font-weight: bold; margin-bottom: 4px; color:#0F766E; text-transform:uppercase;">Data Identitas Pasien</div>
                <div><strong>Nama Pasien:</strong> <?= e($rm['nama_pasien']) ?></div>
                <div><strong>No. Rekam Medis:</strong> <?= e($rm['no_rm']) ?></div>
                <div><strong>NIK:</strong> <?= e($rm['nik']) ?></div>
                <div><strong>Tanggal Lahir:</strong> <?= tanggal_indo($rm['tgl_lahir']) ?> (<?= hitung_usia($rm['tgl_lahir']) ?> - <?= $rm['jenis_kelamin'] ?>)</div>
                <div><strong>Golongan Darah:</strong> <?= e($rm['gol_darah'] ?: '-') ?></div>
                <div><strong>Alamat:</strong> <?= e($rm['alamat']) ?></div>
                <?php if ($rm['nama_wali']): ?>
                    <div style="margin-top:4px; color:#3B82F6;"><strong>Wali/Ortu:</strong> <?= e($rm['nama_wali']) ?> (<?= e($rm['hubungan_wali']) ?> - <?= e($rm['kontak_wali']) ?>)</div>
                <?php endif; ?>
                <?php if ($rm['alergi_obat']): ?>
                    <div style="margin-top:4px; color:#EF4444; font-weight:bold;"><strong>Riwayat Alergi Obat:</strong> <?= e($rm['alergi_obat']) ?></div>
                <?php endif; ?>
            </div>

            <div style="background: #F8FAFC; padding: 10px 14px; border-radius: 6px; border: 1px solid #E2E8F0;">
                <div style="font-weight: bold; margin-bottom: 4px; color:#0F766E; text-transform:uppercase;">Informasi Kunjungan</div>
                <div><strong>Tanggal Periksa:</strong> <?= tanggal_indo($rm['tanggal_periksa'], true) ?></div>
                <div><strong>Waktu:</strong> <?= waktu_indo($rm['created_at']) ?></div>
                <div><strong>Dokter Pemeriksa:</strong> <?= e($rm['nama_dokter']) ?></div>
                <div><strong>Spesialisasi:</strong> Spesialis <?= e($rm['spesialisasi']) ?></div>
                <div><strong>No. STR:</strong> <?= e($rm['no_str']) ?></div>
                <?php if ($rm['kondisi_medis']): ?>
                    <div style="margin-top:4px; color:#F59E0B;"><strong>Kondisi Medis Pasien:</strong> <?= e($rm['kondisi_medis']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- SOAP NOTES -->
        <div class="section-title">I. Catatan Medis SOAP</div>
        <div class="soap-box">
            <strong>Subjektif (S):</strong> <?= e($rm['subjektif']) ?>
        </div>
        <div class="soap-box">
            <strong>Objektif (O):</strong> <?= e($rm['objektif']) ?>
        </div>
        <div class="soap-box">
            <strong>Asesmen / Diagnosa Klinis (A):</strong> <?= e($rm['asesmen']) ?>
        </div>
        <div class="soap-box">
            <strong>Plan / Rencana Terapi (P):</strong> <?= e($rm['plan']) ?>
        </div>

        <!-- DIAGNOSIS ICD-10 -->
        <?php if (!empty($diagnoses)): ?>
        <div class="section-title">II. Klasifikasi Penyakit Gigi (ICD-10)</div>
        <table class="print-table">
            <thead>
                <tr>
                    <th style="width: 120px;">Kode ICD-10</th>
                    <th>Nama Diagnosa Penyakit</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($diagnoses as $dg): ?>
                <tr>
                    <td><strong><?= e($dg['kode_icd10']) ?></strong></td>
                    <td><?= e($dg['nama_penyakit']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <!-- TINDAKAN MEDIS -->
        <?php if (!empty($tindakans)): ?>
        <div class="section-title">III. Tindakan Medis yang Diberikan</div>
        <table class="print-table">
            <thead>
                <tr>
                    <th>Nama Tindakan / Layanan</th>
                    <th style="width: 100px; text-align:center;">Elemen Gigi</th>
                    <th style="width: 60px; text-align:center;">Qty</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tindakans as $act): ?>
                <tr>
                    <td><strong><?= e($act['nama_layanan']) ?></strong></td>
                    <td style="text-align:center;"><?= $act['nomor_gigi'] ? '#' . e($act['nomor_gigi']) : '-' ?></td>
                    <td style="text-align:center;"><?= $act['jumlah'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <!-- RESEP OBAT -->
        <?php if (!empty($reseps)): ?>
        <div class="section-title">IV. Terapi Obat (Resep Dokter)</div>
        <table class="print-table">
            <thead>
                <tr>
                    <th>Nama Obat</th>
                    <th style="width: 80px; text-align:center;">Jumlah</th>
                    <th style="width: 100px;">Dosis</th>
                    <th>Aturan Pakai / Signa</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reseps as $rx): ?>
                <tr>
                    <td><strong><?= e($rx['nama_obat']) ?></strong></td>
                    <td style="text-align:center;"><?= $rx['jumlah'] ?> <?= e($rx['satuan']) ?></td>
                    <td><?= e($rx['dosis']) ?></td>
                    <td><strong><?= e($rx['aturan_pakai']) ?></strong></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <!-- TANDA TANGAN DOKTER -->
        <div class="print-signature-area">
            <div></div>
            <div class="sig-box">
                <div>Bandung, <?= tanggal_indo($rm['tanggal_periksa']) ?></div>
                <div style="font-size: 11px; color:#64748B;">Dokter Gigi Pemeriksa,</div>
                <div class="sig-line"></div>
                <div style="font-weight: bold; margin-top: 4px;"><?= e($rm['nama_dokter']) ?></div>
                <div style="font-size: 11px; color:#64748B;">STR: <?= e($rm['no_str']) ?></div>
            </div>
        </div>
    </div>
</body>
</html>

