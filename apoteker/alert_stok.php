<?php
/**
 * DENTARA - Portal Apoteker: Peringatan Stok Kritis & Kadaluarsa
 * File: apoteker/alert_stok.php
 */

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout_staff.php';

require_role(['apoteker', 'admin']);

render_staff_layout('Peringatan Stok & Kadaluarsa', function() {
    global $pdo;

    $today = date('Y-m-d');
    $warning_30d = date('Y-m-d', strtotime('+30 days'));

    // 1. Obat Stok Kritis
    $stmt_obat_kritis = $pdo->prepare("
        SELECT id_obat, nama_obat, kategori, satuan, stok, stok_minimum, harga_beli, exp_date
        FROM obat
        WHERE stok <= stok_minimum
        ORDER BY stok ASC
    ");
    $stmt_obat_kritis->execute();
    $obat_kritis = $stmt_obat_kritis->fetchAll();

    // 2. Supply Stok Kritis
    $stmt_supply_kritis = $pdo->prepare("
        SELECT id_supply, nama_barang, satuan, stok, stok_minimum, exp_date
        FROM dental_supply
        WHERE stok <= stok_minimum
        ORDER BY stok ASC
    ");
    $stmt_supply_kritis->execute();
    $supply_kritis = $stmt_supply_kritis->fetchAll();

    // 3. Obat & Supply Sudah Kadaluarsa
    $stmt_exp_obat = $pdo->prepare("
        SELECT id_obat, nama_obat, kategori, satuan, stok, exp_date
        FROM obat
        WHERE exp_date IS NOT NULL AND exp_date < ?
        ORDER BY exp_date ASC
    ");
    $stmt_exp_obat->execute([$today]);
    $exp_obat = $stmt_exp_obat->fetchAll();

    // 4. Obat & Supply Mendekati Kadaluarsa (< 30 hari)
    $stmt_near_exp_obat = $pdo->prepare("
        SELECT id_obat, nama_obat, kategori, satuan, stok, exp_date,
               DATEDIFF(exp_date, ?) as sisa_hari
        FROM obat
        WHERE exp_date IS NOT NULL AND exp_date >= ? AND exp_date <= ?
        ORDER BY exp_date ASC
    ");
    $stmt_near_exp_obat->execute([$today, $today, $warning_30d]);
    $near_exp_obat = $stmt_near_exp_obat->fetchAll();
    ?>

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 style="font-family:var(--font-heading); margin:0;">Dashboard Peringatan Stok & Farmasi</h1>
            <p class="text-muted" style="margin:0;">Pantau barang kritis dan batas kedaluwarsa demi keamanan terapi pasien.</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-secondary btn-sm" onclick="window.print()">
                <i class="bx bx-printer"></i> Cetak Rekomendasi Re-order
            </button>
            <a href="<?= base_url('apoteker/stock_opname.php') ?>" class="btn btn-primary btn-sm">
                <i class="bx bx-clipboard"></i> Buat Stock Opname
            </a>
        </div>
    </div>

    <!-- Alert Banner Utama -->
    <?php if (count($exp_obat) > 0): ?>
        <div class="card p-3 mb-4 alert alert-danger d-flex align-items-center gap-3">
            <div style="font-size:36px;"><i class="bx bxs-error-circle"></i></div>
            <div>
                <h4 style="margin:0; font-family:var(--font-heading);">TERDAPAT <?= count($exp_obat) ?> OBAT KADALUARSA!</h4>
                <div>Harap segera pisahkan dan amankan barang kadaluarsa ke area karantina untuk dimusnahkan/dikembalikan ke distributor.</div>
            </div>
        </div>
    <?php endif; ?>

    <!-- SEKSI 1: STOK OBAT KRITIS -->
    <div class="card p-4 mb-4" style="background:var(--surface);">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 style="font-family:var(--font-heading); font-size:var(--text-lg); margin:0; color:var(--accent-orange);">
                <i class="bx bxs-box"></i> Obat Dengan Stok Menipis (≤ Batas Minimum)
            </h3>
            <span class="badge badge-warning"><?= count($obat_kritis) ?> Item</span>
        </div>

        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Nama Obat</th>
                        <th>Kategori</th>
                        <th class="text-center">Sisa Stok</th>
                        <th class="text-center">Batas Min</th>
                        <th class="text-end">Estimasi Order (Unit)</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($obat_kritis)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-success font-bold">
                                <i class="bx bx-check-circle"></i> Semua stok obat berada di batas aman!
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($obat_kritis as $o): 
                            $rekomendasi_order = max(10, ($o['stok_minimum'] * 2) - $o['stok']);
                        ?>
                            <tr>
                                <td><strong><?= e($o['nama_obat']) ?></strong></td>
                                <td><span class="badge badge-secondary"><?= e($o['kategori']) ?></span></td>
                                <td class="text-center">
                                    <span class="badge badge-danger"><?= (int)$o['stok'] ?> <?= e($o['satuan']) ?></span>
                                </td>
                                <td class="text-center text-muted"><?= (int)$o['stok_minimum'] ?> <?= e($o['satuan']) ?></td>
                                <td class="text-end font-bold text-primary">+<?= $rekomendasi_order ?> <?= e($o['satuan']) ?></td>
                                <td>
                                    <a href="<?= base_url('apoteker/stok_obat.php?q=' . urlencode($o['nama_obat'])) ?>" class="btn btn-ghost btn-sm text-primary">
                                        <i class="bx bx-edit"></i> Update Stok
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- SEKSI 2: STOK DENTAL SUPPLY KRITIS -->
    <div class="card p-4 mb-4" style="background:var(--surface);">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 style="font-family:var(--font-heading); font-size:var(--text-lg); margin:0; color:var(--primary-teal);">
                <i class="bx bxs-shield"></i> Dental Supply Menipis (≤ Batas Minimum)
            </h3>
            <span class="badge badge-mint"><?= count($supply_kritis) ?> Item</span>
        </div>

        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Nama Bahan / Supply</th>
                        <th>Satuan</th>
                        <th class="text-center">Sisa Stok</th>
                        <th class="text-center">Batas Min</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($supply_kritis)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-success font-bold">
                                <i class="bx bx-check-circle"></i> Stok material dental aman terkendali!
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($supply_kritis as $s): ?>
                            <tr>
                                <td><strong><?= e($s['nama_barang']) ?></strong></td>
                                <td><?= e($s['satuan']) ?></td>
                                <td class="text-center">
                                    <span class="badge badge-danger"><?= (int)$s['stok'] ?></span>
                                </td>
                                <td class="text-center text-muted"><?= (int)$s['stok_minimum'] ?></td>
                                <td>
                                    <a href="<?= base_url('apoteker/stok_supply.php?q=' . urlencode($s['nama_barang'])) ?>" class="btn btn-ghost btn-sm text-primary">
                                        <i class="bx bx-edit"></i> Update Stok
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- SEKSI 3: PERINGATAN KADALUARSA (EXPIRED / NEAR EXPIRY) -->
    <div class="row g-4">
        <!-- Sudah Expired -->
        <div class="col-12 col-lg-6">
            <div class="card p-4 h-100" style="background:var(--surface); border-top:4px solid var(--danger);">
                <h3 style="font-family:var(--font-heading); font-size:var(--text-lg); margin-bottom:1rem; color:var(--danger);">
                    <i class="bx bxs-trash"></i> Sudah Melewati Tanggal Kadaluarsa
                </h3>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Nama Obat</th>
                                <th class="text-center">Stok</th>
                                <th>Tgl Kadaluarsa</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($exp_obat)): ?>
                                <tr>
                                    <td colspan="3" class="text-center py-3 text-muted">Tidak ada obat yang expired. Bagus!</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($exp_obat as $eo): ?>
                                    <tr>
                                        <td><strong><?= e($eo['nama_obat']) ?></strong></td>
                                        <td class="text-center font-bold text-danger"><?= (int)$eo['stok'] ?> <?= e($eo['satuan']) ?></td>
                                        <td class="text-danger font-bold"><?= tanggal_indo($eo['exp_date']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Mendekati Expired (< 30 Hari) -->
        <div class="col-12 col-lg-6">
            <div class="card p-4 h-100" style="background:var(--surface); border-top:4px solid var(--warning);">
                <h3 style="font-family:var(--font-heading); font-size:var(--text-lg); margin-bottom:1rem; color:var(--warning);">
                    <i class="bx bxs-time"></i> Kadaluarsa Dalam 30 Hari Kedepan
                </h3>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Nama Obat</th>
                                <th class="text-center">Stok</th>
                                <th>Sisa Waktu</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($near_exp_obat)): ?>
                                <tr>
                                    <td colspan="3" class="text-center py-3 text-muted">Tidak ada obat yang mendekati exp date.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($near_exp_obat as $neo): ?>
                                    <tr>
                                        <td><strong><?= e($neo['nama_obat']) ?></strong></td>
                                        <td class="text-center"><?= (int)$neo['stok'] ?> <?= e($neo['satuan']) ?></td>
                                        <td>
                                            <span class="badge badge-warning"><?= (int)$neo['sisa_hari'] ?> Hari Lagi</span>
                                            <div style="font-size:10px; color:var(--text-muted);"><?= tanggal_indo($neo['exp_date']) ?></div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php
}, 'stok_obat');

