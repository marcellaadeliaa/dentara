<?php
/**
 * DENTARA - Portal Admin / Owner: Executive Dashboard & Analytics
 * File: admin/dashboard.php
 */

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout_staff.php';

require_role(['admin']);

render_staff_layout('Dashboard Analitik & Manajemen', function() {
    global $pdo;

    $today = date('Y-m-d');
    $this_month = date('Y-m');

    // 1. Total Pasien Terdaftar
    $total_pasien = (int)$pdo->query("SELECT COUNT(*) FROM pasien")->fetchColumn();

    // 2. Kunjungan / Reservasi Hari Ini
    $stmt_book = $pdo->prepare("SELECT COUNT(*) FROM antrian WHERE tanggal = ?");
    $stmt_book->execute([$today]);
    $kunjungan_hari_ini = (int)$stmt_book->fetchColumn();

    // 3. Pendapatan Bulan Ini
    $stmt_rev = $pdo->prepare("
        SELECT COALESCE(SUM(jumlah_dibayar), 0) 
        FROM pembayaran 
        WHERE DATE_FORMAT(tanggal_bayar, '%Y-%m') = ?
    ");
    $stmt_rev->execute([$this_month]);
    $pendapatan_bulan_ini = (float)$stmt_rev->fetchColumn();

    // 4. Tagihan Belum Dibayar
    $stmt_unpaid = $pdo->query("
        SELECT COUNT(*) as count, COALESCE(SUM(total_biaya), 0) as total
        FROM tagihan 
        WHERE status = 'belum_bayar'
    ");
    $unpaid = $stmt_unpaid->fetch();

    // 5. Stok Kritis Farmasi
    $stok_kritis_obat = (int)$pdo->query("SELECT COUNT(*) FROM obat WHERE stok <= stok_minimum")->fetchColumn();
    $stok_kritis_supply = (int)$pdo->query("SELECT COUNT(*) FROM dental_supply WHERE stok <= stok_minimum")->fetchColumn();
    $total_kritis = $stok_kritis_obat + $stok_kritis_supply;

    // 6. Data Trend Pendapatan 7 Hari Terakhir untuk Chart.js
    $trend_labels = [];
    $trend_values = [];
    for ($i = 6; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i days"));
        $trend_labels[] = date('d M', strtotime($d));

        $stmt_d = $pdo->prepare("SELECT COALESCE(SUM(jumlah_dibayar), 0) FROM pembayaran WHERE DATE(tanggal_bayar) = ?");
        $stmt_d->execute([$d]);
        $trend_values[] = (float)$stmt_d->fetchColumn();
    }

    // 7. Top 5 Layanan / Tindakan Paling Laris
    $top_layanan = $pdo->query("
        SELECT l.nama_layanan, COUNT(rt.id) as total_kali, SUM(rt.tarif_saat_itu) as total_omset
        FROM rekam_tindakan rt
        JOIN layanan l ON rt.id_layanan = l.id_layanan
        GROUP BY l.id_layanan
        ORDER BY total_kali DESC
        LIMIT 5
    ")->fetchAll();

    $pie_labels = [];
    $pie_values = [];
    foreach ($top_layanan as $tl) {
        $pie_labels[] = $tl['nama_layanan'];
        $pie_values[] = (int)$tl['total_kali'];
    }

    // 8. Log Aktivitas Terakhir
    $logs = $pdo->query("
        SELECT l.*, u.nama_lengkap, u.role
        FROM log_aktivitas l
        LEFT JOIN pengguna u ON l.id_pengguna = u.id_pengguna
        ORDER BY l.id_log DESC
        LIMIT 8
    ")->fetchAll();
    ?>

    <!-- BANNER SELAMAT DATANG ADMIN -->
    <div class="card p-4 mb-4" style="background: linear-gradient(135deg, var(--primary-teal), var(--primary-teal-dark)); color:#fff; border-radius:16px;">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h1 style="font-family:var(--font-heading); margin:0; font-size:var(--text-2xl); color:#fff;">
                    Selamat Datang di Portal Manajemen DENTARA
                </h1>
                <p style="margin:4px 0 0 0; opacity:0.9; font-size:var(--text-sm);">
                    Ringkasan performa operasional, keuangan klinik, dan pelayanan medis terkini.
                </p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= base_url('admin/laporan_pendapatan.php') ?>" class="btn btn-warning btn-sm">
                    <i class="bx bxs-report"></i> Laporan Keuangan
                </a>
                <a href="<?= base_url('admin/fee_sharing.php') ?>" class="btn btn-light btn-sm text-primary">
                    <i class="bx bxs-wallet"></i> Fee Sharing Dokter
                </a>
            </div>
        </div>
    </div>

    <!-- SUMMARY METRICS CARDS -->
    <div class="row g-4 mb-4">
        <!-- Pendapatan Bulan Ini -->
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card p-4 h-100" style="background:var(--surface); border-left: 5px solid var(--success);">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span style="font-size:var(--text-xs); color:var(--text-muted); font-weight:700; text-transform:uppercase;">Pendapatan Bulan Ini</span>
                    <i class="bx bxs-wallet text-success" style="font-size:24px;"></i>
                </div>
                <div style="font-size:var(--text-2xl); font-weight:800; font-family:var(--font-heading); color:var(--success);">
                    <?= rupiah($pendapatan_bulan_ini) ?>
                </div>
                <div style="font-size:var(--text-xs); color:var(--text-muted); margin-top:4px;">
                    Bulan <?= tanggal_indo(date('Y-m-01')) ?>
                </div>
            </div>
        </div>

        <!-- Pasien Hari Ini -->
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card p-4 h-100" style="background:var(--surface); border-left: 5px solid var(--primary-teal);">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span style="font-size:var(--text-xs); color:var(--text-muted); font-weight:700; text-transform:uppercase;">Antrean Hari Ini</span>
                    <i class="bx bxs-user-check text-primary" style="font-size:24px;"></i>
                </div>
                <div style="font-size:var(--text-2xl); font-weight:800; font-family:var(--font-heading); color:var(--primary-teal);">
                    <?= $kunjungan_hari_ini ?> Pasien
                </div>
                <div style="font-size:var(--text-xs); color:var(--text-muted); margin-top:4px;">
                    Total terdaftar: <?= $total_pasien ?> pasien
                </div>
            </div>
        </div>

        <!-- Tagihan Menunggu -->
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card p-4 h-100" style="background:var(--surface); border-left: 5px solid var(--accent-orange);">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span style="font-size:var(--text-xs); color:var(--text-muted); font-weight:700; text-transform:uppercase;">Tagihan Belum Lunas</span>
                    <i class="bx bxs-receipt text-warning" style="font-size:24px;"></i>
                </div>
                <div style="font-size:var(--text-2xl); font-weight:800; font-family:var(--font-heading); color:var(--accent-orange);">
                    <?= (int)$unpaid['count'] ?> Tagihan
                </div>
                <div style="font-size:var(--text-xs); color:var(--text-muted); margin-top:4px;">
                    Nilai: <?= rupiah($unpaid['total']) ?>
                </div>
            </div>
        </div>

        <!-- Stok Kritis -->
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card p-4 h-100" style="background:var(--surface); border-left: 5px solid var(--danger);">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span style="font-size:var(--text-xs); color:var(--text-muted); font-weight:700; text-transform:uppercase;">Stok Kritis / Exp</span>
                    <i class="bx bxs-error-circle text-danger" style="font-size:24px;"></i>
                </div>
                <div style="font-size:var(--text-2xl); font-weight:800; font-family:var(--font-heading); color:var(--danger);">
                    <?= $total_kritis ?> Item
                </div>
                <div style="font-size:var(--text-xs); color:var(--text-muted); margin-top:4px;">
                    <?= $stok_kritis_obat ?> Obat, <?= $stok_kritis_supply ?> Supply
                </div>
            </div>
        </div>
    </div>

    <!-- GRAFIK ANALITIK: TREND PENDAPATAN & TOP LAYANAN -->
    <div class="row g-4 mb-4">
        <!-- Line Chart: Tren Omset 7 Hari -->
        <div class="col-12 col-lg-8">
            <div class="card p-4 h-100" style="background:var(--surface);">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 style="font-family:var(--font-heading); font-size:var(--text-lg); margin:0;">
                        <i class="bx bx-line-chart text-primary"></i> Tren Pendapatan 7 Hari Terakhir
                    </h3>
                    <a href="<?= base_url('admin/laporan_pendapatan.php') ?>" class="btn btn-ghost btn-sm">Lihat Detail &rarr;</a>
                </div>
                <div style="height:280px; position:relative;">
                    <canvas id="chartPendapatan"></canvas>
                </div>
            </div>
        </div>

        <!-- Doughnut Chart: Top Layanan -->
        <div class="col-12 col-lg-4">
            <div class="card p-4 h-100" style="background:var(--surface);">
                <h3 style="font-family:var(--font-heading); font-size:var(--text-lg); margin-bottom:1rem;">
                    <i class="bx bx-pie-chart-alt-2 text-primary"></i> 5 Tindakan Terbanyak
                </h3>
                <div style="height:240px; position:relative;">
                    <canvas id="chartTopLayanan"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- AKTIVITAS TERBARU & QUICK SHORTCUTS -->
    <div class="row g-4">
        <!-- Log Audit Trail -->
        <div class="col-12 col-lg-8">
            <div class="card p-0" style="background:var(--surface); overflow:hidden;">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                    <h3 style="font-family:var(--font-heading); font-size:var(--text-lg); margin:0;">
                        <i class="bx bx-history text-primary"></i> Aktivitas Sistem Terkini
                    </h3>
                    <a href="<?= base_url('admin/log_aktivitas.php') ?>" class="btn btn-ghost btn-sm">Semua Log &rarr;</a>
                </div>
                <div class="table-responsive">
                    <table class="table mb-0" style="font-size:var(--text-sm);">
                        <thead>
                            <tr>
                                <th>Waktu</th>
                                <th>Pengguna</th>
                                <th>Aksi</th>
                                <th>Detail</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">Belum ada aktivitas tercatat.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td style="white-space:nowrap; color:var(--text-muted); font-size:12px;">
                                            <?= waktu_indo($log['created_at']) ?>
                                        </td>
                                        <td>
                                            <strong><?= e($log['nama_lengkap'] ?: 'Sistem') ?></strong>
                                            <span class="badge badge-secondary" style="font-size:10px; margin-left:4px;"><?= e($log['role'] ?: 'sys') ?></span>
                                        </td>
                                        <td><span class="badge badge-mint"><?= e($log['aksi']) ?></span></td>
                                        <td style="max-width:280px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                            <?= e($log['detail'] ?: '-') ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Quick Access Menu -->
        <div class="col-12 col-lg-4">
            <div class="card p-4 h-100" style="background:var(--surface);">
                <h3 style="font-family:var(--font-heading); font-size:var(--text-lg); margin-bottom:1rem;">
                    <i class="bx bx-grid-alt text-primary"></i> Pintasan Menu Admin
                </h3>
                <div class="d-flex flex-column gap-2">
                    <a href="<?= base_url('admin/kelola_pengguna.php') ?>" class="btn btn-secondary d-flex align-items-center justify-content-between p-3 text-left">
                        <span><i class="bx bxs-user-account text-primary"></i> Manajemen Staff</span>
                        <i class="bx bx-chevron-right"></i>
                    </a>
                    <a href="<?= base_url('admin/kelola_dokter.php') ?>" class="btn btn-secondary d-flex align-items-center justify-content-between p-3 text-left">
                        <span><i class="bx bxs-user-badge text-primary"></i> Kelola Dokter & Fee</span>
                        <i class="bx bx-chevron-right"></i>
                    </a>
                    <a href="<?= base_url('admin/jadwal_dokter.php') ?>" class="btn btn-secondary d-flex align-items-center justify-content-between p-3 text-left">
                        <span><i class="bx bxs-calendar text-primary"></i> Jadwal Praktik & Cuti</span>
                        <i class="bx bx-chevron-right"></i>
                    </a>
                    <a href="<?= base_url('admin/sla_analytics.php') ?>" class="btn btn-secondary d-flex align-items-center justify-content-between p-3 text-left">
                        <span><i class="bx bx-timer text-primary"></i> SLA Waktu Tunggu Layanan</span>
                        <i class="bx bx-chevron-right"></i>
                    </a>
                    <a href="<?= base_url('admin/pengaturan_klinik.php') ?>" class="btn btn-secondary d-flex align-items-center justify-content-between p-3 text-left">
                        <span><i class="bx bxs-cog text-primary"></i> Profil & Tarif Konsultasi</span>
                        <i class="bx bx-chevron-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart.js Init Script -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Line Chart Pendapatan
        const ctxP = document.getElementById('chartPendapatan');
        if (ctxP) {
            new Chart(ctxP, {
                type: 'line',
                data: {
                    labels: <?= json_encode($trend_labels) ?>,
                    datasets: [{
                        label: 'Pendapatan (Rp)',
                        data: <?= json_encode($trend_values) ?>,
                        borderColor: '#2BB5A0',
                        backgroundColor: 'rgba(43, 181, 160, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.35,
                        pointBackgroundColor: '#2BB5A0',
                        pointRadius: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(val) {
                                    return 'Rp ' + (val / 1000).toLocaleString() + 'k';
                                }
                            }
                        }
                    }
                }
            });
        }

        // Doughnut Chart Top Layanan
        const ctxL = document.getElementById('chartTopLayanan');
        if (ctxL) {
            new Chart(ctxL, {
                type: 'doughnut',
                data: {
                    labels: <?= json_encode($pie_labels) ?>,
                    datasets: [{
                        data: <?= json_encode($pie_values) ?>,
                        backgroundColor: [
                            '#2BB5A0', '#5B9DF5', '#FFD166', '#8B7CF6', '#FF6B6B'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { boxWidth: 12, font: { size: 11 } }
                        }
                    }
                }
            });
        }
    });
    </script>
    <?php
}, 'dashboard');

