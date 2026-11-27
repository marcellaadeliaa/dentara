<?php
/**
 * DENTARA - Portal Admin: SLA Analytics (Waktu Tunggu & Durasi Layanan Pasien)
 * File: admin/sla_analytics.php
 */

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout_staff.php';

require_role(['admin']);

render_staff_layout('SLA Analytics & Waktu Pelayanan', function() {
    global $pdo;

    // Filter Periode
    $periode = $_GET['periode'] ?? date('Y-m');

    // Query Analisis SLA per Dokter
    $stmt = $pdo->prepare("
        SELECT 
            d.id_dokter, u.nama_lengkap, d.spesialisasi,
            COUNT(a.id_antrian) as total_antrian,
            COUNT(CASE WHEN a.status = 'selesai' THEN 1 END) as total_selesai,
            AVG(TIMESTAMPDIFF(MINUTE, a.waktu_checkin, a.waktu_mulai)) as avg_tunggu_menit,
            AVG(TIMESTAMPDIFF(MINUTE, a.waktu_mulai, a.waktu_selesai)) as avg_periksa_menit,
            AVG(TIMESTAMPDIFF(MINUTE, a.waktu_checkin, a.waktu_selesai)) as avg_total_menit
        FROM dokter d
        JOIN pengguna u ON d.id_pengguna = u.id_pengguna
        LEFT JOIN antrian a ON d.id_dokter = a.id_dokter 
            AND a.waktu_checkin IS NOT NULL 
            AND DATE_FORMAT(a.tanggal, '%Y-%m') = ?
        GROUP BY d.id_dokter
        ORDER BY u.nama_lengkap ASC
    ");
    $stmt->execute([$periode]);
    $sla_dokters = $stmt->fetchAll();

    // Query 15 Antrean Terakhir dengan Detail Timestamp
    $stmt_recent = $pdo->prepare("
        SELECT 
            a.*,
            COALESCE(p.nama_pasien, r.nama_tamu) as nama_pasien, p.no_rm,
            u.nama_lengkap as nama_dokter,
            TIMESTAMPDIFF(MINUTE, a.waktu_checkin, a.waktu_mulai) as durasi_tunggu,
            TIMESTAMPDIFF(MINUTE, a.waktu_mulai, a.waktu_selesai) as durasi_periksa,
            TIMESTAMPDIFF(MINUTE, a.waktu_checkin, a.waktu_selesai) as durasi_total
        FROM antrian a
        LEFT JOIN reservasi r ON a.id_reservasi = r.id_reservasi
        LEFT JOIN pasien p ON r.id_pasien = p.id_pasien
        JOIN dokter d ON a.id_dokter = d.id_dokter
        JOIN pengguna u ON d.id_pengguna = u.id_pengguna
        WHERE a.waktu_checkin IS NOT NULL AND DATE_FORMAT(a.tanggal, '%Y-%m') = ?
        ORDER BY a.id_antrian DESC
        LIMIT 20
    ");
    $stmt_recent->execute([$periode]);
    $recent_antrian = $stmt_recent->fetchAll();

    // Siapkan data Chart.js
    $labels = [];
    $data_tunggu = [];
    $data_periksa = [];

    $grand_total_antrian = 0;
    $sum_tunggu = 0;
    $sum_periksa = 0;
    $count_valid = 0;

    foreach ($sla_dokters as $sd) {
        $labels[] = 'Dr. ' . $sd['nama_lengkap'];
        $tunggu = round((float)($sd['avg_tunggu_menit'] ?? 0), 1);
        $periksa = round((float)($sd['avg_periksa_menit'] ?? 0), 1);

        $data_tunggu[] = $tunggu;
        $data_periksa[] = $periksa;

        $grand_total_antrian += $sd['total_selesai'];
        if ($sd['total_selesai'] > 0) {
            $sum_tunggu += $tunggu;
            $sum_periksa += $periksa;
            $count_valid++;
        }
    }

    $overall_avg_tunggu = $count_valid > 0 ? round($sum_tunggu / $count_valid, 1) : 0;
    $overall_avg_periksa = $count_valid > 0 ? round($sum_periksa / $count_valid, 1) : 0;
    ?>

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 style="font-family:var(--font-heading); margin:0;">SLA Analytics: Waktu Tunggu & Durasi Pelayanan</h1>
            <p class="text-muted" style="margin:0;">Pantau efisiensi waktu check-in, pemanggilan, dan lama tindakan dental per dokter.</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-secondary btn-sm" onclick="window.print()">
                <i class="bx bx-printer"></i> Cetak Laporan SLA
            </button>
        </div>
    </div>

    <!-- Filter Periode Bulan -->
    <div class="card p-3 mb-4" style="background:var(--surface);">
        <form method="GET" class="d-flex align-items-center gap-2">
            <label class="form-label font-bold mb-0">Periode Evaluasi:</label>
            <input type="month" name="periode" class="form-control" style="max-width:200px;" value="<?= e($periode) ?>" onchange="this.form.submit()">
            <button type="submit" class="btn btn-secondary btn-sm">Terapkan</button>
        </form>
    </div>

    <!-- KPI Summary Cards -->
    <div class="row g-4 mb-4">
        <div class="col-12 col-md-4">
            <div class="card p-4 h-100" style="background:var(--surface); border-left:5px solid <?= $overall_avg_tunggu <= 15 ? 'var(--success)' : ($overall_avg_tunggu <= 25 ? 'var(--warning)' : 'var(--danger)') ?>;">
                <div style="font-size:var(--text-xs); color:var(--text-muted); font-weight:700;">RATA-RATA WAKTU TUNGGU PASIEN</div>
                <div style="font-size:var(--text-3xl); font-weight:800; font-family:var(--font-heading); color:<?= $overall_avg_tunggu <= 15 ? 'var(--success)' : 'var(--accent-orange)' ?>;">
                    <?= $overall_avg_tunggu ?> Menit
                </div>
                <div style="font-size:12px; color:var(--text-muted); margin-top:4px;">
                    Target Klinik: &le; 15 menit dari Check-in
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="card p-4 h-100" style="background:var(--surface); border-left:5px solid var(--primary-teal);">
                <div style="font-size:var(--text-xs); color:var(--text-muted); font-weight:700;">RATA-RATA DURASI TINDAKAN</div>
                <div style="font-size:var(--text-3xl); font-weight:800; font-family:var(--font-heading); color:var(--primary-teal);">
                    <?= $overall_avg_periksa ?> Menit
                </div>
                <div style="font-size:12px; color:var(--text-muted); margin-top:4px;">
                    Di atas dental chair per pasien
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="card p-4 h-100" style="background:var(--surface); border-left:5px solid var(--accent-blue);">
                <div style="font-size:var(--text-xs); color:var(--text-muted); font-weight:700;">TOTAL PASIEN SELESAI DIEVALUASI</div>
                <div style="font-size:var(--text-3xl); font-weight:800; font-family:var(--font-heading); color:var(--accent-blue);">
                    <?= $grand_total_antrian ?> Pasien
                </div>
                <div style="font-size:12px; color:var(--text-muted); margin-top:4px;">
                    Bulan <?= tanggal_indo($periode . '-01') ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart Perbandingan Dokter -->
    <div class="card p-4 mb-4" style="background:var(--surface);">
        <h3 style="font-family:var(--font-heading); font-size:var(--text-lg); margin-bottom:1.5rem;">
            <i class="bx bx-bar-chart-alt-2 text-primary"></i> Perbandingan Waktu Tunggu vs Lama Pemeriksaan per Dokter
        </h3>
        <div style="height:320px; position:relative;">
            <canvas id="chartSla"></canvas>
        </div>
    </div>

    <!-- Tabel Rekap SLA Dokter -->
    <div class="card p-0 mb-4" style="background:var(--surface); overflow:hidden;">
        <div class="p-3 border-bottom">
            <h3 style="font-family:var(--font-heading); font-size:var(--text-lg); margin:0;">
                <i class="bx bx-timer text-primary"></i> Ringkasan Kinerja Waktu per Dokter Gigi
            </h3>
        </div>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Dokter Gigi</th>
                        <th>Spesialisasi</th>
                        <th class="text-center">Total Dilayani</th>
                        <th class="text-center">Rata2 Waktu Tunggu</th>
                        <th class="text-center">Rata2 Tindakan</th>
                        <th class="text-center">Total di Klinik</th>
                        <th class="text-center">Status SLA</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sla_dokters as $s): 
                        $tunggu = round((float)($s['avg_tunggu_menit'] ?? 0), 1);
                        $periksa = round((float)($s['avg_periksa_menit'] ?? 0), 1);
                        $total = round((float)($s['avg_total_menit'] ?? 0), 1);

                        $sla_badge = ($tunggu <= 15) ? 'badge-success' : (($tunggu <= 25) ? 'badge-warning' : 'badge-danger');
                        $sla_text = ($tunggu <= 15) ? 'Sangat Baik' : (($tunggu <= 25) ? 'Standar' : 'Perlu Evaluasi');
                    ?>
                        <tr>
                            <td><strong>Dr. <?= e($s['nama_lengkap']) ?></strong></td>
                            <td><?= e($s['spesialisasi']) ?></td>
                            <td class="text-center font-bold"><?= (int)$s['total_selesai'] ?> Pasien</td>
                            <td class="text-center font-bold" style="color:var(--accent-orange);"><?= $tunggu ?> Menit</td>
                            <td class="text-center font-bold text-primary"><?= $periksa ?> Menit</td>
                            <td class="text-center"><?= $total ?> Menit</td>
                            <td class="text-center">
                                <span class="badge <?= $sla_badge ?>"><?= $sla_text ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Script Chart.js -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('chartSla');
        if (ctx) {
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: <?= json_encode($labels) ?>,
                    datasets: [
                        {
                            label: 'Waktu Tunggu Pasien (Menit)',
                            data: <?= json_encode($data_tunggu) ?>,
                            backgroundColor: 'rgba(255, 107, 107, 0.8)',
                            borderColor: '#FF6B6B',
                            borderWidth: 1
                        },
                        {
                            label: 'Durasi Pemeriksaan Dokter (Menit)',
                            data: <?= json_encode($data_periksa) ?>,
                            backgroundColor: 'rgba(43, 181, 160, 0.8)',
                            borderColor: '#2BB5A0',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Menit'
                            }
                        }
                    }
                }
            });
        }
    });
    </script>
    <?php
}, 'sla_analytics');

