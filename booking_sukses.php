<?php
/**
 * DENTARA - Halaman Sukses Booking
 * File: booking_sukses.php
 */

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/layout_public.php';

$kode = trim($_GET['kode'] ?? '');

if (empty($kode)) {
    header('Location: ' . base_url('booking.php'));
    exit;
}

global $pdo;
$stmt = $pdo->prepare("
    SELECT r.*, 
           COALESCE(p.nama_pasien, r.nama_tamu) as nama_pasien,
           COALESCE(p.no_hp, r.no_hp_tamu) as no_hp,
           p.no_rm,
           d.spesialisasi,
           u.nama_lengkap as nama_dokter,
           l.nama_layanan, l.tarif_awal
    FROM reservasi r
    LEFT JOIN pasien p ON r.id_pasien = p.id_pasien
    JOIN dokter d ON r.id_dokter = d.id_dokter
    JOIN pengguna u ON d.id_pengguna = u.id_pengguna
    LEFT JOIN layanan l ON r.id_layanan = l.id_layanan
    WHERE r.kode_booking = ?
    LIMIT 1
");
$stmt->execute([$kode]);
$booking = $stmt->fetch();

if (!$booking) {
    header('Location: ' . base_url('404.php'));
    exit;
}

render_public_layout('Reservasi Berhasil - ' . $kode, function() use ($booking, $kode) {
?>
<div class="container" style="padding-top:110px; padding-bottom:60px; max-width:760px;">
    <!-- Centang Sukses Animasi -->
    <div class="text-center mb-6 fade-in">
        <div style="width:72px; height:72px; border-radius:50%; background:var(--success-light); color:var(--success); display:inline-flex; align-items:center; justify-content:center; font-size:42px; margin-bottom:12px; box-shadow:0 0 20px rgba(16,185,129,0.3);">
            <i class="bx bx-check"></i>
        </div>
        <h1 style="font-size:28px; margin-bottom:4px;">Reservasi Janji Temu Berhasil!</h1>
        <p class="text-muted">Simpan tiket digital ini dan tunjukkan QR Code saat tiba di meja resepsionis klinik.</p>
    </div>

    <!-- TIKET BOOKING DIGITAL -->
    <div class="ticket-card fade-in mb-6">
        <div class="ticket-main">
            <span class="badge badge-success mb-2"><i class="bx bx-check-circle"></i> Status: <?= strtoupper($booking['status']) ?></span>
            <div style="font-size:12px; color:var(--text-muted); text-transform:uppercase; font-weight:700; letter-spacing:0.5px;">Kode Booking Reservasi</div>
            <div style="font-family:var(--font-heading); font-size:40px; font-weight:800; color:var(--primary); letter-spacing:2px; margin:4px 0 16px;">
                <?= e($booking['kode_booking']) ?>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; font-size:14px; border-top:1px solid var(--border-color); padding-top:16px;">
                <div>
                    <span class="text-muted" style="display:block; font-size:12px;">Nama Pasien</span>
                    <strong><?= e($booking['nama_pasien']) ?></strong>
                    <?php if ($booking['no_rm']): ?>
                        <span class="badge badge-mint" style="display:inline-block; margin-top:2px;"><?= e($booking['no_rm']) ?></span>
                    <?php endif; ?>
                </div>

                <div>
                    <span class="text-muted" style="display:block; font-size:12px;">Dokter Spesialis</span>
                    <strong><?= e($booking['nama_dokter']) ?></strong>
                    <span style="display:block; font-size:12px; color:var(--accent);">Spesialis <?= e($booking['spesialisasi']) ?></span>
                </div>

                <div>
                    <span class="text-muted" style="display:block; font-size:12px;">Jadwal Kunjungan</span>
                    <strong><?= tanggal_indo($booking['tanggal'], true) ?></strong>
                    <span style="display:block; font-size:12px; color:var(--primary); font-weight:700;">Pukul <?= substr($booking['jam'], 0, 5) ?> WIB</span>
                </div>

                <div>
                    <span class="text-muted" style="display:block; font-size:12px;">Layanan</span>
                    <strong><?= e($booking['nama_layanan'] ?: 'Pemeriksaan Umum') ?></strong>
                </div>
            </div>
        </div>

        <div class="ticket-divider"></div>

        <div class="ticket-side">
            <div style="background:#fff; padding:10px; border-radius:var(--radius-sm); border:1px solid var(--border-color); box-shadow:var(--shadow-sm); margin-bottom:10px;">
                <img src="<?= base_url('qr.php?kode=' . urlencode($booking['kode_booking'])) ?>" 
                     alt="QR Code Booking <?= e($booking['kode_booking']) ?>" 
                     style="width:140px; height:140px; display:block;">
            </div>
            <span style="font-size:11px; color:var(--text-muted); font-weight:700;">Scan di Resepsionis</span>
        </div>
    </div>

    <!-- PETUNJUK & TOMBOL AKSI -->
    <div class="card p-5 mb-6" style="background:var(--surface-subtle); border-left:4px solid var(--primary);">
        <h4 style="font-size:15px; margin-bottom:8px;"><i class="bx bx-info-circle text-primary"></i> Petunjuk Kedatangan:</h4>
        <ul style="font-size:13px; color:var(--text-muted); display:flex; flex-direction:column; gap:6px; padding-left:18px; list-style-type:disc;">
            <li>Harap hadir 10-15 menit sebelum estimasi waktu pemeriksaan.</li>
            <li>Saat tiba di klinik, pindai (scan) QR Code di meja resepsionis atau beritahukan kode <strong><?= e($booking['kode_booking']) ?></strong> untuk mendapatkan nomor antrean fisik.</li>
            <li>Anda dapat memantau pergerakan antrean secara realtime melalui menu Live Antrean.</li>
        </ul>
    </div>

    <div class="d-flex justify-content-center flex-wrap gap-3 no-print">
        <button class="btn btn-secondary btn-md" onclick="window.print()">
            <i class="bx bx-printer"></i> Cetak / Simpan Tiket PDF
        </button>
        <a href="<?= base_url('tracker.php?kode=' . urlencode($booking['kode_booking'])) ?>" class="btn btn-primary btn-md">
            <i class="bx bx-radar"></i> Pantau Antrean Live
        </a>
        <a href="<?= base_url('index.php') ?>" class="btn btn-ghost btn-md">
            Ke Beranda
        </a>
    </div>
</div>
<?php
}, 'booking');

