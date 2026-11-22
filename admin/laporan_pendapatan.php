<?php
/**
 * DENTARA - Portal Admin: Laporan Keuangan & Pendapatan Klinik
 * File: admin/laporan_pendapatan.php
 */

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout_staff.php';

require_role(['admin']);

$tgl_mulai = $_GET['tgl_mulai'] ?? date('Y-m-01');
$tgl_selesai = $_GET['tgl_selesai'] ?? date('Y-m-d');
$metode_filter = $_GET['metode'] ?? '';

// Handle Export CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=laporan_pendapatan_' . $tgl_mulai . '_sd_' . $tgl_selesai . '.csv');

    $out = fopen('php://output', 'w');
    fputcsv($out, ['No Kuitansi', 'Tanggal', 'Nama Pasien', 'No RM', 'Dokter', 'Metode Bayar', 'Konsultasi', 'Tindakan', 'Obat', 'Admin', 'Total Tagihan', 'Asuransi', 'Dibayar Pasien', 'Kasir']);

    $sql_csv = "
        SELECT 
            pem.no_kuitansi, pem.tanggal_bayar, pem.metode, pem.jumlah_dibayar,
            t.biaya_konsultasi, t.biaya_tindakan, t.biaya_obat, t.biaya_admin, t.total_biaya, t.ditanggung_asuransi,
            COALESCE(p.nama_pasien, r.nama_tamu) as nama_pasien, p.no_rm,
            u_dok.nama_lengkap as nama_dokter,
            u_kas.nama_lengkap as nama_kasir
        FROM pembayaran pem
        JOIN tagihan t ON pem.id_tagihan = t.id_tagihan
        LEFT JOIN antrian a ON t.id_antrian = a.id_antrian
        LEFT JOIN reservasi r ON a.id_reservasi = r.id_reservasi
        LEFT JOIN pasien p ON t.id_pasien = p.id_pasien
        LEFT JOIN dokter d ON a.id_dokter = d.id_dokter
        LEFT JOIN pengguna u_dok ON d.id_pengguna = u_dok.id_pengguna
        JOIN pengguna u_kas ON pem.id_kasir = u_kas.id_pengguna
        WHERE DATE(pem.tanggal_bayar) BETWEEN ? AND ?
    ";
    $p_csv = [$tgl_mulai, $tgl_selesai];
    if ($metode_filter !== '') {
        $sql_csv .= " AND pem.metode = ?";
        $p_csv[] = $metode_filter;
    }
    $sql_csv .= " ORDER BY pem.id_pembayaran DESC";

    $stmt_c = $pdo->prepare($sql_csv);
    $stmt_c->execute($p_csv);
    while ($row = $stmt_c->fetch()) {
        fputcsv($out, [
            $row['no_kuitansi'],
            $row['tanggal_bayar'],
            $row['nama_pasien'],
            $row['no_rm'] ?: '-',
            $row['nama_dokter'] ?: '-',
            $row['metode'],
            $row['biaya_konsultasi'],
            $row['biaya_tindakan'],
            $row['biaya_obat'],
            $row['biaya_admin'],
            $row['total_biaya'],
            $row['ditanggung_asuransi'],
            $row['jumlah_dibayar'],
            $row['nama_kasir']
        ]);
    }
    fclose($out);
    exit;
}

render_staff_layout('Laporan Pendapatan Klinik', function() use ($tgl_mulai, $tgl_selesai, $metode_filter) {
    global $pdo;

    $sql = "
        SELECT 
            pem.*,
            t.biaya_konsultasi, t.biaya_tindakan, t.biaya_obat, t.biaya_admin, t.total_biaya, t.ditanggung_asuransi,
            COALESCE(p.nama_pasien, r.nama_tamu) as nama_pasien, p.no_rm,
            u_dok.nama_lengkap as nama_dokter,
            u_kas.nama_lengkap as nama_kasir
        FROM pembayaran pem
        JOIN tagihan t ON pem.id_tagihan = t.id_tagihan
        LEFT JOIN antrian a ON t.id_antrian = a.id_antrian
        LEFT JOIN reservasi r ON a.id_reservasi = r.id_reservasi
        LEFT JOIN pasien p ON t.id_pasien = p.id_pasien
        LEFT JOIN dokter d ON a.id_dokter = d.id_dokter
        LEFT JOIN pengguna u_dok ON d.id_pengguna = u_dok.id_pengguna
        JOIN pengguna u_kas ON pem.id_kasir = u_kas.id_pengguna
        WHERE DATE(pem.tanggal_bayar) BETWEEN ? AND ?
    ";
    $params = [$tgl_mulai, $tgl_selesai];

    if ($metode_filter !== '') {
        $sql .= " AND pem.metode = ?";
        $params[] = $metode_filter;
    }
    $sql .= " ORDER BY pem.id_pembayaran DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $transaksi = $stmt->fetchAll();

    // Hitung ringkasan keuangan
    $grand_total = 0;
    $grand_asuransi = 0;
    $metode_totals = [
        'tunai' => 0,
        'qris' => 0,
        'debit' => 0,
        'kartu_kredit' => 0,
        'transfer' => 0
    ];

    foreach ($transaksi as $tr) {
        $grand_total += $tr['jumlah_dibayar'];
        $grand_asuransi += $tr['ditanggung_asuransi'];
        if (isset($metode_totals[$tr['metode']])) {
            $metode_totals[$tr['metode']] += $tr['jumlah_dibayar'];
        }
    }

    $total_trx = count($transaksi);
    $avg_trx = $total_trx > 0 ? round($grand_total / $total_trx) : 0;
    ?>

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 style="font-family:var(--font-heading); margin:0;">Laporan Pendapatan & Kas Masuk</h1>
            <p class="text-muted" style="margin:0;">Rekapitulasi seluruh transaksi pembayaran pasien dan metode pelunasan.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= base_url('admin/laporan_pendapatan.php?tgl_mulai=' . $tgl_mulai . '&tgl_selesai=' . $tgl_selesai . '&metode=' . $metode_filter . '&export=csv') ?>" class="btn btn-secondary btn-sm">
                <i class="bx bx-download"></i> Export CSV
            </a>
            <button type="button" class="btn btn-primary btn-sm" onclick="window.print()">
                <i class="bx bx-printer"></i> Cetak Laporan
            </button>
        </div>
    </div>

    <!-- Filter Rentang Tanggal & Metode -->
    <div class="card p-3 mb-4" style="background:var(--surface);">
        <form method="GET" class="filter-bar d-flex align-items-center gap-2 flex-wrap">
            <div class="d-flex align-items-center gap-2">
                <label class="form-label font-bold mb-0 text-muted" style="font-size:12px; text-transform:uppercase;"><i class="bx bx-calendar"></i> Dari:</label>
                <input type="date" name="tgl_mulai" class="form-control" style="width:160px;" value="<?= e($tgl_mulai) ?>">
            </div>
            <div class="d-flex align-items-center gap-2">
                <label class="form-label font-bold mb-0 text-muted" style="font-size:12px; text-transform:uppercase;"><i class="bx bx-calendar"></i> Sampai:</label>
                <input type="date" name="tgl_selesai" class="form-control" style="width:160px;" value="<?= e($tgl_selesai) ?>">
            </div>
            <select name="metode" class="form-select" style="max-width:180px;">
                <option value="">Semua Metode</option>
                <option value="tunai" <?= $metode_filter === 'tunai' ? 'selected' : '' ?>>Tunai</option>
                <option value="qris" <?= $metode_filter === 'qris' ? 'selected' : '' ?>>QRIS</option>
                <option value="debit" <?= $metode_filter === 'debit' ? 'selected' : '' ?>>Debit</option>
                <option value="kartu_kredit" <?= $metode_filter === 'kartu_kredit' ? 'selected' : '' ?>>Kartu Kredit</option>
                <option value="transfer" <?= $metode_filter === 'transfer' ? 'selected' : '' ?>>Transfer</option>
            </select>
            <button type="submit" class="btn btn-primary" style="height:42px; padding:0 20px;">
                <i class="bx bx-filter-alt"></i> Terapkan Filter
            </button>
        </form>
    </div>

    <!-- Metric Card Keuangan (Grid 3 Kolom Responsif) -->
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:16px; margin-bottom:20px;">
        <div class="card p-4 h-100" style="background:var(--surface); border-left:5px solid var(--success);">
            <div style="font-size:var(--text-xs); color:var(--text-muted); font-weight:800; letter-spacing:0.75px;">TOTAL PENDAPATAN DITERIMA</div>
            <div style="font-size:var(--text-3xl); font-weight:800; font-family:var(--font-heading); color:var(--success); margin:6px 0;">
                <?= rupiah($grand_total) ?>
            </div>
            <div style="font-size:12px; color:var(--text-muted);">
                <i class="bx bx-check-circle text-success"></i> Dari <?= $total_trx ?> transaksi pembayaran lunas
            </div>
        </div>
        <div class="card p-4 h-100" style="background:var(--surface); border-left:5px solid var(--primary);">
            <div style="font-size:var(--text-xs); color:var(--text-muted); font-weight:800; letter-spacing:0.75px;">RATA-RATA TIKET TRANSAKSI</div>
            <div style="font-size:var(--text-3xl); font-weight:800; font-family:var(--font-heading); color:var(--primary); margin:6px 0;">
                <?= rupiah($avg_trx) ?>
            </div>
            <div style="font-size:12px; color:var(--text-muted);">
                <i class="bx bx-user text-primary"></i> Nilai rata-rata per transaksi pasien
            </div>
        </div>
        <div class="card p-4 h-100" style="background:var(--surface); border-left:5px solid var(--secondary);">
            <div style="font-size:var(--text-xs); color:var(--text-muted); font-weight:800; letter-spacing:0.75px;">KLAIM DITANGGUNG ASURANSI</div>
            <div style="font-size:var(--text-3xl); font-weight:800; font-family:var(--font-heading); color:var(--secondary); margin:6px 0;">
                <?= rupiah($grand_asuransi) ?>
            </div>
            <div style="font-size:12px; color:var(--text-muted);">
                <i class="bx bx-shield-quarter text-secondary"></i> Total potongan asuransi / reimburse
            </div>
        </div>
    </div>

    <!-- Rincian Metode Bayar (5 Mini-Cards Tersekat Rapi) -->
    <div class="card p-4 mb-4" style="background:var(--surface);">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h4 style="margin:0; font-family:var(--font-heading); font-size:15px; color:#334155;">
                <i class="bx bx-pie-chart-alt text-primary"></i> Rincian Realisasi Berdasarkan Metode Pembayaran
            </h4>
            <span class="badge badge-mint"><?= $total_trx ?> Transaksi Lunas</span>
        </div>
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(170px, 1fr)); gap:12px;">
            <!-- Tunai -->
            <div style="background:#F0FDF4; border:1.5px solid #BBF7D0; border-radius:10px; padding:12px 14px;">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span style="font-size:11px; font-weight:800; color:#166534; letter-spacing:0.5px;">TUNAI</span>
                    <i class="bx bx-money text-success" style="font-size:18px;"></i>
                </div>
                <div style="font-size:16px; font-weight:800; font-family:var(--font-heading); color:#166534;">
                    <?= rupiah($metode_totals['tunai']) ?>
                </div>
            </div>
            <!-- QRIS -->
            <div style="background:#FAF5FF; border:1.5px solid #E9D5FF; border-radius:10px; padding:12px 14px;">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span style="font-size:11px; font-weight:800; color:#6B21A8; letter-spacing:0.5px;">QRIS</span>
                    <i class="bx bx-qr-scan text-purple" style="font-size:18px; color:#7C3AED;"></i>
                </div>
                <div style="font-size:16px; font-weight:800; font-family:var(--font-heading); color:#6B21A8;">
                    <?= rupiah($metode_totals['qris']) ?>
                </div>
            </div>
            <!-- DEBIT -->
            <div style="background:#F0FDFA; border:1.5px solid #99F6E4; border-radius:10px; padding:12px 14px;">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span style="font-size:11px; font-weight:800; color:#0F766E; letter-spacing:0.5px;">KARTU DEBIT</span>
                    <i class="bx bx-credit-card text-primary" style="font-size:18px;"></i>
                </div>
                <div style="font-size:16px; font-weight:800; font-family:var(--font-heading); color:#0F766E;">
                    <?= rupiah($metode_totals['debit']) ?>
                </div>
            </div>
            <!-- KREDIT -->
            <div style="background:#FFF7ED; border:1.5px solid #FED7AA; border-radius:10px; padding:12px 14px;">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span style="font-size:11px; font-weight:800; color:#C2410C; letter-spacing:0.5px;">KARTU KREDIT</span>
                    <i class="bx bxs-credit-card text-warning" style="font-size:18px;"></i>
                </div>
                <div style="font-size:16px; font-weight:800; font-family:var(--font-heading); color:#C2410C;">
                    <?= rupiah($metode_totals['kartu_kredit']) ?>
                </div>
            </div>
            <!-- TRANSFER -->
            <div style="background:#EFF6FF; border:1.5px solid #BFDBFE; border-radius:10px; padding:12px 14px;">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span style="font-size:11px; font-weight:800; color:#1E40AF; letter-spacing:0.5px;">TRANSFER BANK</span>
                    <i class="bx bx-transfer text-secondary" style="font-size:18px;"></i>
                </div>
                <div style="font-size:16px; font-weight:800; font-family:var(--font-heading); color:#1E40AF;">
                    <?= rupiah($metode_totals['transfer']) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel Rincian Pembayaran -->
    <div class="card p-0 mb-4" style="background:var(--surface); overflow:hidden;">
        <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-light">
            <h3 style="font-family:var(--font-heading); font-size:var(--text-lg); margin:0;">
                <i class="bx bx-receipt text-primary"></i> Daftar Transaksi Pembayaran Kas Masuk
            </h3>
            <span class="badge badge-mint"><?= $total_trx ?> Transaksi Ditemukan</span>
        </div>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th style="width:140px;">No Kuitansi</th>
                        <th style="width:145px;">Waktu & Tanggal</th>
                        <th style="width:170px;">Pasien & RM</th>
                        <th style="width:160px;">Dokter</th>
                        <th style="width:110px;" class="text-center">Metode</th>
                        <th class="text-end" style="width:170px;">Rincian Tagihan</th>
                        <th class="text-end" style="width:130px;">Dibayar</th>
                        <th style="width:120px;">Kasir</th>
                        <th class="text-center" style="width:75px;">Nota</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transaksi)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">Tidak ada transaksi ditemukan pada rentang tanggal ini.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($transaksi as $t): ?>
                            <tr>
                                <td>
                                    <strong style="color:var(--text-main); font-size:13.5px;"><i class="bx bx-barcode text-muted"></i> <?= e($t['no_kuitansi']) ?></strong>
                                </td>
                                <td style="font-size:12px; white-space:nowrap;">
                                    <div style="font-weight:700; color:var(--text-main);"><i class="bx bx-time text-muted"></i> <?= waktu_indo($t['tanggal_bayar']) ?></div>
                                    <div class="text-muted"><?= tanggal_indo(date('Y-m-d', strtotime($t['tanggal_bayar']))) ?></div>
                                </td>
                                <td>
                                    <strong style="color:var(--text-main); font-size:13.5px;"><?= e($t['nama_pasien']) ?></strong>
                                    <div class="text-muted" style="font-size:11px; font-weight:700;"><?= e($t['no_rm'] ?: 'Tamu Walk-in') ?></div>
                                </td>
                                <td>
                                    <strong style="font-size:13px;">Dr. <?= e($t['nama_dokter'] ?: '-') ?></strong>
                                </td>
                                <td class="text-center">
                                    <?php
                                    $m = strtolower($t['metode']);
                                    $badge = 'badge-secondary';
                                    if ($m === 'tunai') $badge = 'badge-success';
                                    elseif ($m === 'qris') $badge = 'badge-mint';
                                    elseif ($m === 'transfer') $badge = 'badge-secondary';
                                    elseif ($m === 'debit') $badge = 'badge-warning';
                                    ?>
                                    <span class="badge <?= $badge ?> font-bold" style="letter-spacing:0.5px;"><?= strtoupper(e($t['metode'])) ?></span>
                                </td>
                                <td class="text-end" style="font-size:11.5px; line-height:1.45;">
                                    <div style="color:#64748B;">Konsul: <span style="font-weight:600; color:#334155;"><?= rupiah($t['biaya_konsultasi']) ?></span></div>
                                    <div style="color:#64748B;">Tindakan: <span style="font-weight:600; color:#334155;"><?= rupiah($t['biaya_tindakan']) ?></span></div>
                                    <div style="color:#64748B;">Obat: <span style="font-weight:600; color:#334155;"><?= rupiah($t['biaya_obat']) ?></span></div>
                                </td>
                                <td class="text-end font-bold text-success" style="font-size:15px; font-family:var(--font-heading);">
                                    <?= rupiah($t['jumlah_dibayar']) ?>
                                </td>
                                <td style="font-size:12px; font-weight:600;">
                                    <i class="bx bx-user-check text-muted"></i> <?= e($t['nama_kasir']) ?>
                                </td>
                                <td class="text-center">
                                    <a href="<?= base_url('kasir/kuitansi.php?id_pembayaran=' . $t['id_pembayaran']) ?>" target="_blank" class="btn btn-ghost btn-sm text-primary" title="Cetak Kuitansi">
                                        <i class="bx bx-receipt" style="font-size:20px;"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}, 'laporan_pendapatan');

