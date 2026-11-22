<?php
/**
 * DENTARA - Portal Kasir: Dashboard Tagihan & Pembayaran Pasien
 * File: kasir/dashboard_tagihan.php
 */

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout_staff.php';

require_role(['kasir', 'admin']);

render_staff_layout('Kasir & Tagihan Pasien', function() {
    global $pdo;

    $q = trim($_GET['q'] ?? '');
    $status_filter = trim($_GET['status'] ?? 'belum_bayar');

    // Cek shift kasir aktif
    $id_kasir = current_user()['id_pengguna'] ?? 0;
    $stmt_shift = $pdo->prepare("SELECT * FROM shift_kasir WHERE id_kasir = ? AND status = 'buka' ORDER BY id_shift DESC LIMIT 1");
    $stmt_shift->execute([$id_kasir]);
    $shift_aktif = $stmt_shift->fetch();

    // Query Tagihan
    $sql = "
        SELECT 
            t.*,
            COALESCE(p.nama_pasien, r.nama_tamu) as nama_pasien,
            p.no_rm, p.no_hp,
            a.nomor_antrian, a.status as status_antrian,
            d.id_dokter, u.nama_lengkap as nama_dokter,
            pem.id_pembayaran, pem.no_kuitansi, pem.metode as metode_bayar, pem.tanggal_bayar
        FROM tagihan t
        LEFT JOIN antrian a ON t.id_antrian = a.id_antrian
        LEFT JOIN reservasi r ON a.id_reservasi = r.id_reservasi
        LEFT JOIN pasien p ON t.id_pasien = p.id_pasien
        LEFT JOIN dokter d ON a.id_dokter = d.id_dokter
        LEFT JOIN pengguna u ON d.id_pengguna = u.id_pengguna
        LEFT JOIN pembayaran pem ON t.id_tagihan = pem.id_tagihan
        WHERE 1=1
    ";
    $params = [];

    if ($status_filter !== 'semua') {
        $sql .= " AND t.status = ?";
        $params[] = $status_filter;
    }

    if ($q !== '') {
        $sql .= " AND (p.nama_pasien LIKE ? OR r.nama_tamu LIKE ? OR p.no_rm LIKE ? OR pem.no_kuitansi LIKE ?)";
        $params[] = "%$q%";
        $params[] = "%$q%";
        $params[] = "%$q%";
        $params[] = "%$q%";
    }

    $sql .= " ORDER BY CASE t.status WHEN 'belum_bayar' THEN 1 ELSE 2 END, t.id_tagihan DESC LIMIT 50";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $tagihans = $stmt->fetchAll();

    // Statistik Hari Ini
    $today = date('Y-m-d');
    $stmt_stat = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN status = 'belum_bayar' THEN 1 ELSE 0 END) as count_belum,
            SUM(CASE WHEN status = 'belum_bayar' THEN total_biaya ELSE 0 END) as total_belum,
            SUM(CASE WHEN status = 'lunas' AND DATE(created_at) = ? THEN 1 ELSE 0 END) as count_lunas_today,
            SUM(CASE WHEN status = 'lunas' AND DATE(created_at) = ? THEN total_biaya ELSE 0 END) as total_lunas_today
        FROM tagihan
    ");
    $stmt_stat->execute([$today, $today]);
    $stat = $stmt_stat->fetch();
    ?>

    <!-- Warning jika belum buka shift -->
    <?php if (!$shift_aktif): ?>
        <div class="card p-3 mb-4 alert alert-warning d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-3">
                <i class="bx bxs-error-circle" style="font-size:32px;"></i>
                <div>
                    <h4 style="margin:0; font-family:var(--font-heading);">Shift Kasir Belum Dibuka!</h4>
                    <p style="margin:0; font-size:var(--text-sm);">Anda belum membuka kas awal shift hari ini. Silakan buka shift kasir terlebih dahulu untuk mencatat transaksi.</p>
                </div>
            </div>
            <a href="<?= base_url('kasir/rekap_shift.php') ?>" class="btn btn-primary btn-sm">
                <i class="bx bx-door-open"></i> Buka Shift Sekarang
            </a>
        </div>
    <?php endif; ?>

    <!-- Summary Metrics -->
    <div class="row g-4 mb-4">
        <div class="col-12 col-md-6 col-lg-3">
            <div class="card p-3" style="background:var(--surface); border-left: 5px solid var(--accent-orange);">
                <div style="font-size:var(--text-xs); color:var(--text-muted); font-weight:700;">BELUM DIBAYAR</div>
                <div style="font-size:var(--text-2xl); font-weight:800; font-family:var(--font-heading); color:var(--accent-orange);">
                    <?= (int)($stat['count_belum'] ?? 0) ?> Tagihan
                </div>
                <div style="font-size:var(--text-xs); color:var(--text-muted);"><?= rupiah($stat['total_belum'] ?? 0) ?></div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-3">
            <div class="card p-3" style="background:var(--surface); border-left: 5px solid var(--success);">
                <div style="font-size:var(--text-xs); color:var(--text-muted); font-weight:700;">LUNAS HARI INI</div>
                <div style="font-size:var(--text-2xl); font-weight:800; font-family:var(--font-heading); color:var(--success);">
                    <?= (int)($stat['count_lunas_today'] ?? 0) ?> Transaksi
                </div>
                <div style="font-size:var(--text-xs); color:var(--text-muted);"><?= rupiah($stat['total_lunas_today'] ?? 0) ?></div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-3">
            <div class="card p-3" style="background:var(--surface); border-left: 5px solid var(--primary-teal);">
                <div style="font-size:var(--text-xs); color:var(--text-muted); font-weight:700;">STATUS SHIFT ANDA</div>
                <div style="font-size:var(--text-xl); font-weight:800; font-family:var(--font-heading); color:var(--primary-teal);">
                    <?= $shift_aktif ? 'Shift Aktif' : 'Shift Tertutup' ?>
                </div>
                <div style="font-size:var(--text-xs); color:var(--text-muted);">
                    <?= $shift_aktif ? 'Buka sejak ' . waktu_indo($shift_aktif['waktu_buka']) : '-' ?>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-3">
            <div class="card p-3" style="background:var(--surface); border-left: 5px solid var(--accent-blue);">
                <div style="font-size:var(--text-xs); color:var(--text-muted); font-weight:700;">KAS AWAL SHIFT</div>
                <div style="font-size:var(--text-2xl); font-weight:800; font-family:var(--font-heading);">
                    <?= $shift_aktif ? rupiah($shift_aktif['kas_awal']) : 'Rp 0' ?>
                </div>
                <div style="font-size:var(--text-xs); color:var(--text-muted);">Modal kembalian</div>
            </div>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="card p-3 mb-4" style="background:var(--surface);">
        <form method="GET" class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <input type="text" name="q" class="form-control" style="max-width:280px;" placeholder="Cari nama pasien / RM / No Kuitansi..." value="<?= e($q) ?>">
                <select name="status" class="form-select" style="max-width:180px;">
                    <option value="belum_bayar" <?= $status_filter === 'belum_bayar' ? 'selected' : '' ?>>Hanya Belum Bayar</option>
                    <option value="lunas" <?= $status_filter === 'lunas' ? 'selected' : '' ?>>Hanya Lunas</option>
                    <option value="semua" <?= $status_filter === 'semua' ? 'selected' : '' ?>>Semua Status</option>
                </select>
                <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
                <?php if ($q || $status_filter !== 'belum_bayar'): ?>
                    <a href="<?= base_url('kasir/dashboard_tagihan.php') ?>" class="btn btn-ghost btn-sm">Reset</a>
                <?php endif; ?>
            </div>

            <div>
                <a href="<?= base_url('kasir/rekap_shift.php') ?>" class="btn btn-secondary btn-sm">
                    <i class="bx bxs-bank"></i> Kelola Shift & Rekap Kas
                </a>
            </div>
        </form>
    </div>

    <!-- Tabel Tagihan Pasien -->
    <div class="card p-0" style="background:var(--surface); overflow:hidden;">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th style="width:70px;">Antrean</th>
                        <th>Pasien & No. RM</th>
                        <th>Dokter Pemeriksa</th>
                        <th class="text-end">Rincian Biaya</th>
                        <th class="text-end">Total Tagihan</th>
                        <th class="text-center">Status</th>
                        <th class="text-center" style="width:140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tagihans)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">Tidak ada data tagihan yang sesuai.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($tagihans as $t): 
                            $is_lunas = $t['status'] === 'lunas';
                        ?>
                            <tr style="<?= !$is_lunas ? 'background:rgba(255,107,107,0.03);' : '' ?>">
                                <td class="text-center">
                                    <span class="badge badge-primary font-bold" style="font-size:var(--text-sm);">
                                        #<?= e($t['nomor_antrian'] ?? '-') ?>
                                    </span>
                                </td>
                                <td>
                                    <strong><?= e($t['nama_pasien']) ?></strong>
                                    <div class="text-muted" style="font-size:11px;">
                                        RM: <?= e($t['no_rm'] ?: 'Walk-in') ?> | <?= waktu_indo($t['created_at']) ?>
                                    </div>
                                </td>
                                <td>
                                    <div>Dr. <?= e($t['nama_dokter'] ?: 'Dokter Klinik') ?></div>
                                </td>
                                <td class="text-end" style="font-size:12px;">
                                    <div>Konsul: <?= rupiah($t['biaya_konsultasi']) ?></div>
                                    <div>Tindakan: <?= rupiah($t['biaya_tindakan']) ?></div>
                                    <div>Obat: <?= rupiah($t['biaya_obat']) ?></div>
                                    <div class="text-muted">Admin: <?= rupiah($t['biaya_admin']) ?></div>
                                </td>
                                <td class="text-end font-bold" style="font-size:var(--text-base); color:<?= $is_lunas ? 'var(--text-color)' : 'var(--accent-orange)' ?>;">
                                    <?= rupiah($t['total_biaya']) ?>
                                    <?php if ($is_lunas && $t['ditanggung_asuransi'] > 0): ?>
                                        <div style="font-size:11px; color:var(--text-muted); font-weight:normal;">Asuransi: <?= rupiah($t['ditanggung_asuransi']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($is_lunas): ?>
                                        <span class="badge badge-success">Lunas</span>
                                        <div style="font-size:10px; color:var(--text-muted); text-transform:uppercase; margin-top:2px;">
                                            <?= e($t['metode_bayar']) ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="badge badge-warning">Belum Bayar</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if (!$is_lunas): ?>
                                        <a href="<?= base_url('kasir/detail_tagihan.php?id_tagihan=' . $t['id_tagihan']) ?>" class="btn btn-primary btn-sm w-100">
                                            <i class="bx bx-credit-card"></i> Bayar
                                        </a>
                                    <?php else: ?>
                                        <a href="<?= base_url('kasir/kuitansi.php?id_pembayaran=' . $t['id_pembayaran']) ?>" class="btn btn-secondary btn-sm w-100">
                                            <i class="bx bx-receipt"></i> Kuitansi
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}, 'dashboard_tagihan');

