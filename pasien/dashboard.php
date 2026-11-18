<?php
/**
 * DENTARA - Portal Pasien: Dashboard
 * File: pasien/dashboard.php
 */

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout_pasien.php';

require_patient();

render_patient_layout('Dashboard Pasien', function() {
    global $pdo;

    $id_akun = (int)$_SESSION['id_akun_pasien'];
    $id_pasien_aktif = (int)($_SESSION['id_pasien_aktif'] ?? 0);

    // Ambil data detail pasien aktif
    $stmt_p = $pdo->prepare("SELECT * FROM pasien WHERE id_pasien = ? AND id_akun = ?");
    $stmt_p->execute([$id_pasien_aktif, $id_akun]);
    $pasien = $stmt_p->fetch();

    if (!$pasien) {
        // Ambil profil pertama
        $stmt_first = $pdo->prepare("SELECT * FROM pasien WHERE id_akun = ? ORDER BY id_pasien ASC LIMIT 1");
        $stmt_first->execute([$id_akun]);
        $pasien = $stmt_first->fetch();
        if ($pasien) {
            $_SESSION['id_pasien_aktif'] = (int)$pasien['id_pasien'];
        }
    }

    $id_pasien = $pasien['id_pasien'] ?? 0;

    // Ambil Reservasi & Antrean Aktif (Hari ini s/d 3 hari ke depan dengan status menunggu/dikonfirmasi/check_in)
    $today = date('Y-m-d');
    $next_3_days = date('Y-m-d', strtotime('+3 days'));
    $stmt_res = $pdo->prepare("
        SELECT r.*, a.nomor_antrian, a.status as status_antrian,
               u.nama_lengkap as nama_dokter, d.spesialisasi, l.nama_layanan
        FROM reservasi r
        LEFT JOIN antrian a ON a.id_reservasi = r.id_reservasi
        JOIN dokter d ON r.id_dokter = d.id_dokter
        JOIN pengguna u ON d.id_pengguna = u.id_pengguna
        LEFT JOIN layanan l ON r.id_layanan = l.id_layanan
        WHERE r.id_pasien = ? 
          AND r.tanggal BETWEEN ? AND ? 
          AND r.status IN ('menunggu', 'dikonfirmasi', 'check_in')
        ORDER BY r.tanggal ASC, r.jam ASC
        LIMIT 1
    ");
    $stmt_res->execute([$id_pasien, $today, $next_3_days]);
    $antrean_aktif = $stmt_res->fetch();

    // Data Poin Gamifikasi Pasien
    $stmt_poin = $pdo->prepare("SELECT * FROM gamifikasi_poin WHERE id_pasien = ?");
    $stmt_poin->execute([$id_pasien]);
    $gamifikasi = $stmt_poin->fetch() ?: ['total_poin' => 0, 'jumlah_kunjungan_selesai' => 0];

    // Data Kunjungan Terakhir
    $stmt_last_emr = $pdo->prepare("
        SELECT rm.*, u.nama_lengkap as nama_dokter 
        FROM rekam_medis rm 
        JOIN dokter d ON rm.id_dokter = d.id_dokter 
        JOIN pengguna u ON d.id_pengguna = u.id_pengguna 
        WHERE rm.id_pasien = ? 
        ORDER BY rm.tanggal_periksa DESC 
        LIMIT 1
    ");
    $stmt_last_emr->execute([$id_pasien]);
    $last_emr = $stmt_last_emr->fetch();
?>
<div class="patient-dashboard fade-in">
    <!-- GREETING HERO -->
    <div class="patient-hero-banner">
        <div class="patient-hero-text">
            <h1>Halo, <?= e($pasien['nama_pasien']) ?>! Siap senyum hari ini? 😊</h1>
            <p class="text-muted" style="margin:0;">
                No. Rekam Medis: <strong><?= e($pasien['no_rm']) ?></strong> &bull; Golongan Darah: <strong><?= e($pasien['gol_darah'] ?: '-') ?></strong> &bull; Usia: <strong><?= hitung_usia($pasien['tgl_lahir']) ?></strong>
            </p>
        </div>
        <div class="d-none d-md-block">
            <?= render_mascot('happy', 95) ?>
        </div>
    </div>

    <!-- ALERGI ALERT (Jika pasien memiliki riwayat alergi obat) -->
    <?php if (!empty($pasien['alergi_obat'])): ?>
    <div class="alert alert-danger mb-5" style="border-radius:var(--radius-md);">
        <i class="bx bxs-shield-x" style="font-size:24px;"></i>
        <div>
            <strong>PERINGATAN RIWAYAT ALERGI OBAT:</strong>
            <p style="margin:2px 0 0; font-size:14px; opacity:0.95;">
                Pasien memiliki catatan alergi terhadap: <u><?= e($pasien['alergi_obat']) ?></u>. Tim dokter dan apoteker akan selalu memeriksa sebelum meresepkan obat.
            </p>
        </div>
    </div>
    <?php endif; ?>

    <!-- TIKET ANTREAN AKTIF (JIKA ADA RESERVASI AKTIF HARI INI / MENDATANG) -->
    <?php if ($antrean_aktif): ?>
    <div class="mb-6">
        <h3 style="font-size:18px; margin-bottom:12px; color:var(--text-main); display:flex; align-items:center; gap:8px;">
            <i class="bx bxs-coupon text-primary"></i> Tiket Antrean Kunjungan Aktif
        </h3>
        <div class="ticket-card">
            <div class="ticket-main">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                    <span class="badge badge-success"><i class="bx bx-check-circle"></i> Status: <?= strtoupper($antrean_aktif['status']) ?></span>
                    <span style="font-size:13px; font-weight:700; color:var(--primary);">
                        <i class="bx bx-calendar"></i> <?= tanggal_indo($antrean_aktif['tanggal'], true) ?> &bull; <?= waktu_indo($antrean_aktif['jam']) ?>
                    </span>
                </div>

                <div style="font-size:12px; color:var(--text-muted); text-transform:uppercase; font-weight:700;">Kode Booking</div>
                <div style="font-family:var(--font-heading); font-size:32px; font-weight:800; color:var(--text-main); letter-spacing:1px; margin:2px 0 12px;">
                    <?= e($antrean_aktif['kode_booking']) ?>
                </div>

                <div class="grid grid-cols-2 gap-3" style="font-size:13px; border-top:1px solid var(--border-color); padding-top:12px;">
                    <div>
                        <span class="text-muted" style="display:block; font-size:11px;">Dokter Spesialis</span>
                        <strong><?= e($antrean_aktif['nama_dokter']) ?></strong>
                        <span style="display:block; font-size:11px; color:var(--accent);">Spesialis <?= e($antrean_aktif['spesialisasi']) ?></span>
                    </div>
                    <div>
                        <span class="text-muted" style="display:block; font-size:11px;">Tindakan / Layanan</span>
                        <strong><?= e($antrean_aktif['nama_layanan'] ?: 'Pemeriksaan Umum') ?></strong>
                    </div>
                </div>

                <div class="mt-3">
                    <a href="<?= base_url('tracker.php?kode=' . $antrean_aktif['kode_booking']) ?>" class="btn btn-primary btn-sm">
                        <i class="bx bx-radar"></i> Pantau Antrean Realtime
                    </a>
                </div>
            </div>

            <div class="ticket-divider"></div>

            <div class="ticket-side">
                <?php if ($antrean_aktif['nomor_antrian']): ?>
                    <span style="font-size:11px; text-transform:uppercase; font-weight:700; color:var(--text-muted);">Nomor Antrean</span>
                    <div class="ticket-number-huge">
                        A-<?= sprintf('%03d', $antrean_aktif['nomor_antrian']) ?>
                    </div>
                <?php else: ?>
                    <div style="background:#fff; padding:6px; border-radius:var(--radius-sm); border:1px solid var(--border-color); margin-bottom:8px;">
                        <img src="<?= base_url('qr.php?kode=' . urlencode($antrean_aktif['kode_booking'])) ?>" 
                             alt="QR Code" style="width:110px; height:110px; display:block;">
                    </div>
                    <span style="font-size:11px; color:var(--text-muted); font-weight:700;">Scan QR di Meja Resepsionis</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- PINTASAN & STATISTIK PASIEN -->
    <div class="grid grid-cols-3 gap-6 mb-6">
        <!-- Kartu 1: Poin Gamifikasi / Kartu Denta -->
        <div class="card p-5 card-hover" style="background:linear-gradient(135deg, #FFFBEB 0%, #FEF3C7 100%); border-color:#FFD166;">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="badge badge-gold"><i class="bx bxs-trophy"></i> POIN DENTA</span>
                <i class="bx bxs-star" style="font-size:28px; color:#F59E0B;"></i>
            </div>
            <div style="font-family:var(--font-heading); font-size:36px; font-weight:800; color:#B45309; line-height:1;">
                <?= (int)$gamifikasi['total_poin'] ?> <span style="font-size:16px;">Poin</span>
            </div>
            <p style="font-size:13px; color:#92400E; margin:8px 0 16px;">
                Telah menyelesaikan <?= (int)$gamifikasi['jumlah_kunjungan_selesai'] ?> kunjungan. Tukar poin dengan Kartu Denta seru!
            </p>
            <a href="<?= base_url('pasien/gamifikasi.php') ?>" class="btn btn-gold btn-sm w-100">
                Buka Hadiah Kartu Denta
            </a>
        </div>

        <!-- Kartu 2: Riwayat Rekam Medis Terakhir -->
        <div class="card p-5 card-hover">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="badge badge-mint"><i class="bx bxs-file-plus"></i> KUNJUNGAN TERAKHIR</span>
                <i class="bx bx-history text-primary" style="font-size:24px;"></i>
            </div>
            <?php if ($last_emr): ?>
                <div style="font-weight:700; font-size:16px; margin-bottom:4px;"><?= e($last_emr['asesmen']) ?></div>
                <div class="text-muted" style="font-size:13px; margin-bottom:16px;">
                    <?= tanggal_indo($last_emr['tanggal_periksa'], true) ?><br>
                    Oleh <?= e($last_emr['nama_dokter']) ?>
                </div>
            <?php else: ?>
                <div class="text-muted" style="font-size:14px; margin-bottom:16px;">
                    Belum ada riwayat rekam medis tercatat.
                </div>
            <?php endif; ?>
            <a href="<?= base_url('pasien/rekam_medis.php') ?>" class="btn btn-secondary btn-sm w-100 mt-auto">
                Lihat Semua Riwayat EMR
            </a>
        </div>

        <!-- Kartu 3: Reservasi Baru Cepat -->
        <div class="card p-5 card-hover text-center" style="background:var(--grad-hero); border-color:rgba(43,181,160,0.3); display:flex; flex-direction:column; justify-content:center;">
            <i class="bx bx-calendar-plus text-primary mb-2" style="font-size:42px;"></i>
            <h3 style="font-size:18px; margin-bottom:6px;">Ingin Jadwalkan Periksa?</h3>
            <p class="text-muted" style="font-size:13px; margin-bottom:16px;">Pilih tanggal dan dokter spesialis favorit Anda dengan cepat.</p>
            <a href="<?= base_url('booking.php') ?>" class="btn btn-primary btn-sm">
                Booking Janji Baru
            </a>
        </div>
    </div>
</div>
<?php
}, 'dashboard');

