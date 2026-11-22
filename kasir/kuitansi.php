<?php
/**
 * DENTARA - Portal Kasir: Cetak Kuitansi Pembayaran (Dual Layout: A4 & 80mm Thermal)
 * File: kasir/kuitansi.php
 */

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';

// Bisa diakses oleh kasir, admin, atau pasien yang bersangkutan
$id_pembayaran = (int)($_GET['id_pembayaran'] ?? 0);
if ($id_pembayaran <= 0) {
    die("ID Pembayaran tidak valid.");
}

// Ambil data lengkap pembayaran, tagihan, pasien, dokter, dan klinik
$stmt = $pdo->prepare("
    SELECT 
        pem.*,
        t.biaya_konsultasi, t.biaya_tindakan, t.biaya_obat, t.biaya_admin, t.total_biaya,
        t.ditanggung_asuransi, t.ditanggung_pasien,
        COALESCE(p.nama_pasien, res.nama_tamu) as nama_pasien,
        p.no_rm, p.nik, p.no_hp, p.alamat,
        u_kasir.nama_lengkap as nama_kasir,
        u_dok.nama_lengkap as nama_dokter, d.spesialisasi,
        a.nomor_antrian
    FROM pembayaran pem
    JOIN tagihan t ON pem.id_tagihan = t.id_tagihan
    LEFT JOIN antrian a ON t.id_antrian = a.id_antrian
    LEFT JOIN reservasi res ON a.id_reservasi = res.id_reservasi
    LEFT JOIN pasien p ON t.id_pasien = p.id_pasien
    JOIN pengguna u_kasir ON pem.id_kasir = u_kasir.id_pengguna
    LEFT JOIN dokter d ON a.id_dokter = d.id_dokter
    LEFT JOIN pengguna u_dok ON d.id_pengguna = u_dok.id_pengguna
    WHERE pem.id_pembayaran = ?
");
$stmt->execute([$id_pembayaran]);
$data = $stmt->fetch();

if (!$data) {
    die("Data kuitansi pembayaran tidak ditemukan.");
}

// Ambil item rincian
$stmt_det = $pdo->prepare("SELECT * FROM tagihan_detail WHERE id_tagihan = ? ORDER BY id ASC");
$stmt_det->execute([$data['id_tagihan']]);
$details = $stmt_det->fetchAll();

// Ambil profil klinik
$klinik = $pdo->query("SELECT * FROM pengaturan_klinik LIMIT 1")->fetch();

// Format teks WA
$pesan_wa = "Halo " . $data['nama_pasien'] . ",\nTerima kasih telah berkunjung ke " . ($klinik['nama_klinik'] ?? 'DENTARA') . ".\n\nBerikut bukti pembayaran Anda:\nNo Kuitansi: " . $data['no_kuitansi'] . "\nTotal Biaya: " . rupiah($data['total_biaya']) . "\nDibayar: " . rupiah($data['jumlah_dibayar']) . " (" . strtoupper($data['metode']) . ")\nTanggal: " . tanggal_indo(date('Y-m-d', strtotime($data['tanggal_bayar']))) . "\n\nSemoga lekas sembuh dan senyum selalu!";
$wa_url = "https://wa.me/" . preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $data['no_hp'] ?? '')) . "?text=" . urlencode($pesan_wa);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kuitansi <?= e($data['no_kuitansi']) ?> - DENTARA</title>
    <link rel="icon" type="image/svg+xml" href="<?= base_url('assets/img/favicon.svg') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@600;700&family=Nunito:wght@400;600;700;800&family=Courier+Prime:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/css/design_tokens.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/print.css') ?>">
    <style>
        body {
            background: #eef2f5;
            font-family: 'Nunito', sans-serif;
            margin: 0;
            padding: 20px;
            color: #2b303a;
        }
        .action-bar {
            max-width: 800px;
            margin: 0 auto 20px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: gap;
        }
        .layout-switch {
            display: inline-flex;
            background: #fff;
            border-radius: 30px;
            padding: 4px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
        }
        .layout-switch button {
            border: none;
            background: transparent;
            padding: 6px 16px;
            border-radius: 20px;
            cursor: pointer;
            font-weight: 700;
            font-size: 13px;
        }
        .layout-switch button.active {
            background: #2BB5A0;
            color: #fff;
        }

        /* TAMPILAN A4 */
        .paper-a4 {
            width: 100%;
            max-width: 800px;
            background: #fff;
            margin: 0 auto;
            padding: 40px 50px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border-radius: 12px;
            box-sizing: border-box;
        }

        /* TAMPILAN 80mm THERMAL POS */
        .paper-thermal {
            display: none;
            width: 320px;
            background: #fff;
            margin: 0 auto;
            padding: 20px 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border-radius: 6px;
            font-family: 'Courier Prime', monospace;
            font-size: 12px;
            line-height: 1.4;
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }
            .action-bar {
                display: none !important;
            }
            .paper-a4 {
                box-shadow: none;
                padding: 20px;
                max-width: 100%;
            }
            .paper-thermal {
                box-shadow: none;
                width: 100%;
                padding: 5px;
            }
        }
    </style>
</head>
<body>

    <!-- Bar Aksi (Hidden saat Print) -->
    <div class="action-bar no-print">
        <div class="d-flex align-items-center gap-2">
            <a href="<?= base_url('kasir/dashboard_tagihan.php') ?>" style="text-decoration:none; color:#555; font-weight:700;">
                <i class="bx bx-left-arrow-alt"></i> Kembali ke Kasir
            </a>
            <div class="layout-switch" style="margin-left:15px;">
                <button type="button" class="active" id="btn-layout-a4" onclick="switchLayout('a4')">Format A4 / Letter</button>
                <button type="button" id="btn-layout-thermal" onclick="switchLayout('thermal')">Format Struk 80mm</button>
            </div>
        </div>

        <div style="display:flex; gap:10px;">
            <?php if (!empty($data['no_hp'])): ?>
                <a href="<?= e($wa_url) ?>" target="_blank" style="background:#25D366; color:#fff; text-decoration:none; padding:8px 16px; border-radius:8px; font-weight:700; font-size:14px; display:inline-flex; align-items:center; gap:6px;">
                    <i class="bx bxl-whatsapp" style="font-size:18px;"></i> Kirim ke WhatsApp
                </a>
            <?php endif; ?>
            <button onclick="window.print()" style="background:#2BB5A0; color:#fff; border:none; padding:8px 18px; border-radius:8px; font-weight:700; font-size:14px; cursor:pointer; display:inline-flex; align-items:center; gap:6px;">
                <i class="bx bx-printer" style="font-size:18px;"></i> Cetak Kuitansi
            </button>
        </div>
    </div>

    <!-- 1. FORMAT A4 / STANDAR LETTERHEAD -->
    <div class="paper-a4" id="view-a4">
        <!-- Kop Surat -->
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:2px solid #2BB5A0; padding-bottom:15px; margin-bottom:25px;">
            <div style="display:flex; align-items:center; gap:15px;">
                <?= render_logo('md', true) ?>
                <div>
                    <h2 style="margin:0; font-family:'Fredoka',sans-serif; color:#2BB5A0; font-size:22px;"><?= e($klinik['nama_klinik'] ?? 'DENTARA DENTAL CLINIC') ?></h2>
                    <div style="font-size:12px; color:#666;"><?= e($klinik['alamat'] ?? 'Jl. Melati Sehat No. 12, Kebayoran Baru') ?></div>
                    <div style="font-size:12px; color:#666;">Telp: <?= e($klinik['telepon'] ?? '(021) 7890-1234') ?> | WA: <?= e($klinik['whatsapp'] ?? '0812-3456-7890') ?></div>
                </div>
            </div>
            <div style="text-align:right;">
                <div style="font-size:20px; font-weight:800; font-family:'Fredoka',sans-serif; color:#2BB5A0;">KUITANSI RESMI</div>
                <div style="font-size:14px; font-weight:700; color:#444;"><?= e($data['no_kuitansi']) ?></div>
                <div style="font-size:11px; color:#888;">Tanggal: <?= tanggal_indo(date('Y-m-d', strtotime($data['tanggal_bayar']))) ?></div>
            </div>
        </div>

        <!-- Identitas Pasien & Dokter -->
        <table style="width:100%; font-size:13px; margin-bottom:20px; border-collapse:collapse;">
            <tr>
                <td style="width:18%; color:#666;">No. Rekam Medis</td>
                <td style="width:32%; font-weight:700;">: <?= e($data['no_rm'] ?: '-') ?></td>
                <td style="width:18%; color:#666;">Dokter Pemeriksa</td>
                <td style="width:32%; font-weight:700;">: Dr. <?= e($data['nama_dokter'] ?: 'Dokter Gigi') ?></td>
            </tr>
            <tr>
                <td style="color:#666;">Nama Pasien</td>
                <td style="font-weight:700;">: <?= e($data['nama_pasien']) ?></td>
                <td style="color:#666;">Metode Bayar</td>
                <td style="font-weight:700; text-transform:uppercase;">: <?= e($data['metode']) ?></td>
            </tr>
            <tr>
                <td style="color:#666;">No. Telepon / WA</td>
                <td>: <?= e($data['no_hp'] ?: '-') ?></td>
                <td style="color:#666;">Kasir</td>
                <td>: <?= e($data['nama_kasir']) ?></td>
            </tr>
        </table>

        <!-- Tabel Rincian -->
        <table style="width:100%; border-collapse:collapse; font-size:13px; margin-bottom:25px;">
            <thead>
                <tr style="background:#f4fbf9; border-top:1px solid #ddd; border-bottom:1px solid #ddd;">
                    <th style="padding:10px; text-align:left; width:40px;">No</th>
                    <th style="padding:10px; text-align:left;">Deskripsi Tindakan & Obat</th>
                    <th style="padding:10px; text-align:center; width:60px;">Qty</th>
                    <th style="padding:10px; text-align:right; width:120px;">Tarif</th>
                    <th style="padding:10px; text-align:right; width:130px;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($details as $i => $row): ?>
                    <tr style="border-bottom:1px solid #eee;">
                        <td style="padding:10px;"><?= $i + 1 ?></td>
                        <td style="padding:10px;">
                            <strong><?= e($row['deskripsi']) ?></strong>
                            <span style="font-size:10px; color:#888; text-transform:uppercase; margin-left:4px;">(<?= e($row['jenis']) ?>)</span>
                        </td>
                        <td style="padding:10px; text-align:center;"><?= $row['qty'] ?></td>
                        <td style="padding:10px; text-align:right;"><?= rupiah($row['harga']) ?></td>
                        <td style="padding:10px; text-align:right; font-weight:700;"><?= rupiah($row['subtotal']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="border-top:2px solid #2BB5A0; font-weight:700;">
                    <td colspan="4" style="padding:10px; text-align:right;">Total Tagihan:</td>
                    <td style="padding:10px; text-align:right;"><?= rupiah($data['total_biaya']) ?></td>
                </tr>
                <?php if ($data['ditanggung_asuransi'] > 0): ?>
                    <tr style="color:#666;">
                        <td colspan="4" style="padding:6px 10px; text-align:right;">Ditanggung Asuransi:</td>
                        <td style="padding:6px 10px; text-align:right;">- <?= rupiah($data['ditanggung_asuransi']) ?></td>
                    </tr>
                <?php endif; ?>
                <tr style="background:#f4fbf9; font-size:15px; font-weight:800; color:#2BB5A0;">
                    <td colspan="4" style="padding:12px 10px; text-align:right;">TOTAL DIBAYAR PASIEN:</td>
                    <td style="padding:12px 10px; text-align:right;"><?= rupiah($data['jumlah_dibayar']) ?></td>
                </tr>
            </tfoot>
        </table>

        <!-- Stempel & Tanda Tangan Kasir -->
        <div style="display:flex; justify-content:space-between; align-items:flex-end; margin-top:30px;">
            <div style="font-size:11px; color:#888; max-width:350px;">
                <strong>Catatan:</strong> Bukti pembayaran ini sah dan diterbitkan secara komputerisasi oleh DENTARA SIM Klinik Gigi. Simpan kuitansi ini untuk klaim reimbursement asuransi kesehatan Anda.
            </div>
            <div style="text-align:center; min-width:180px;">
                <div style="font-size:12px; color:#666; margin-bottom:60px;">Kasir Pembayaran,</div>
                <div style="font-weight:700; text-decoration:underline;"><?= e($data['nama_kasir']) ?></div>
                <div style="font-size:11px; color:#888;">DENTARA DENTAL CLINIC</div>
            </div>
        </div>
    </div>

    <!-- 2. FORMAT 80MM POS THERMAL RECEIPT -->
    <div class="paper-thermal" id="view-thermal">
        <div style="text-align:center; margin-bottom:10px;">
            <div style="font-weight:700; font-size:14px;"><?= strtoupper(e($klinik['nama_klinik'] ?? 'DENTARA DENTAL')) ?></div>
            <div><?= e($klinik['alamat'] ?? 'Kebayoran Baru, Jakarta') ?></div>
            <div>Telp: <?= e($klinik['telepon'] ?? '(021) 7890-1234') ?></div>
            <div>================================</div>
            <div style="font-weight:700;">STRUK PEMBAYARAN</div>
        </div>

        <div>No    : <?= e($data['no_kuitansi']) ?></div>
        <div>Tgl   : <?= date('d/m/Y H:i', strtotime($data['tanggal_bayar'])) ?></div>
        <div>RM    : <?= e($data['no_rm'] ?: '-') ?></div>
        <div>Pasien: <?= substr(e($data['nama_pasien']), 0, 20) ?></div>
        <div>Dokter: Dr. <?= substr(e($data['nama_dokter']), 0, 18) ?></div>
        <div>Kasir : <?= e($data['nama_kasir']) ?></div>
        <div>--------------------------------</div>

        <?php foreach ($details as $it): ?>
            <div><?= substr(e($it['deskripsi']), 0, 22) ?></div>
            <div style="display:flex; justify-content:space-between; margin-bottom:3px;">
                <span><?= $it['qty'] ?> x <?= number_format($it['harga'],0,',','.') ?></span>
                <span><?= number_format($it['subtotal'],0,',','.') ?></span>
            </div>
        <?php endforeach; ?>

        <div>--------------------------------</div>
        <div style="display:flex; justify-content:space-between;">
            <span>Subtotal:</span>
            <span><?= number_format($data['total_biaya'],0,',','.') ?></span>
        </div>
        <?php if ($data['ditanggung_asuransi'] > 0): ?>
            <div style="display:flex; justify-content:space-between;">
                <span>Asuransi:</span>
                <span>-<?= number_format($data['ditanggung_asuransi'],0,',','.') ?></span>
            </div>
        <?php endif; ?>
        <div style="display:flex; justify-content:space-between; font-weight:700;">
            <span>TOTAL   :</span>
            <span>Rp <?= number_format($data['jumlah_dibayar'],0,',','.') ?></span>
        </div>
        <div style="display:flex; justify-content:space-between;">
            <span>Metode  :</span>
            <span><?= strtoupper($data['metode']) ?></span>
        </div>

        <div style="text-align:center; margin-top:15px;">
            <div>================================</div>
            <div>Terima Kasih Atas Kunjungan Anda</div>
            <div>Semoga Senyum Anda Selalu Sehat!</div>
            <div>www.dentara.co.id</div>
        </div>
    </div>

    <script>
    function switchLayout(layout) {
        const viewA4 = document.getElementById('view-a4');
        const viewThermal = document.getElementById('view-thermal');
        const btnA4 = document.getElementById('btn-layout-a4');
        const btnThermal = document.getElementById('btn-layout-thermal');

        if (layout === 'thermal') {
            viewA4.style.display = 'none';
            viewThermal.style.display = 'block';
            btnThermal.classList.add('active');
            btnA4.classList.remove('active');
        } else {
            viewThermal.style.display = 'none';
            viewA4.style.display = 'block';
            btnA4.classList.add('active');
            btnThermal.classList.remove('active');
        }
    }
    </script>
</body>
</html>

