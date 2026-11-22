<?php
/**
 * DENTARA - Portal Dokter: Panel Antrean Ruangan Pemeriksaan
 * File: dokter/panel_antrian.php
 */

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout_staff.php';

require_role(['dokter', 'admin']);

$user = current_user();
$id_dokter = $user['id_dokter'] ?? 1; // Default ke dokter 1 jika admin melihat

// Handle Action Panggil / Mulai
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        header('Location: ' . base_url('dokter/panel_antrian.php'));
        exit;
    }

    $action = $_POST['action'] ?? '';
    $id_antrian = (int)($_POST['id_antrian'] ?? 0);

    if ($action === 'panggil' && $id_antrian) {
        $stmt_p = $pdo->prepare("UPDATE antrian SET status = 'dipanggil', waktu_panggil = CURRENT_TIMESTAMP WHERE id_antrian = ?");
        $stmt_p->execute([$id_antrian]);
        set_flash('success', 'Pasien Dipanggil', 'Nomor antrean telah dipanggil ke layar TV ruang tunggu.');
        header('Location: ' . base_url('dokter/panel_antrian.php'));
        exit;
    }

    if ($action === 'mulai' && $id_antrian) {
        $stmt_m = $pdo->prepare("UPDATE antrian SET status = 'diperiksa', waktu_mulai = CURRENT_TIMESTAMP WHERE id_antrian = ?");
        $stmt_m->execute([$id_antrian]);
        header('Location: ' . base_url('dokter/pemeriksaan.php?id_antrian=' . $id_antrian));
        exit;
    }

    if ($action === 'tunda' && $id_antrian) {
        $stmt_t = $pdo->prepare("UPDATE antrian SET status = 'ditunda' WHERE id_antrian = ?");
        $stmt_t->execute([$id_antrian]);
        set_flash('info', 'Antrean Ditunda', 'Pasien dipindahkan ke antrean tunda.');
        header('Location: ' . base_url('dokter/panel_antrian.php'));
        exit;
    }
}

render_staff_layout('Panel Antrean Ruang Dokter', function() use ($id_dokter) {
    global $pdo;
    $today = date('Y-m-d');

    // Ambil daftar antrean dokter hari ini
    $stmt = $pdo->prepare("
        SELECT a.*, r.kode_booking, r.keluhan,
               COALESCE(p.nama_pasien, r.nama_tamu) as nama_pasien,
               p.no_rm, p.tgl_lahir, p.jenis_kelamin, p.alergi_obat, p.kondisi_medis,
               l.nama_layanan
        FROM antrian a
        JOIN reservasi r ON a.id_reservasi = r.id_reservasi
        LEFT JOIN pasien p ON r.id_pasien = p.id_pasien
        LEFT JOIN layanan l ON r.id_layanan = l.id_layanan
        WHERE a.id_dokter = ? AND a.tanggal = ?
        ORDER BY FIELD(a.status, 'diperiksa', 'dipanggil', 'menunggu', 'ditunda', 'selesai') ASC,
                 a.prioritas DESC, a.nomor_antrian ASC
    ");
    $stmt->execute([$id_dokter, $today]);
    $antreans = $stmt->fetchAll();
?>
<div class="fade-in">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-4 mb-6">
        <div>
            <h1 style="font-size:var(--text-2xl); margin-bottom:4px;">Antrean Pasien Ruang Praktik</h1>
            <p class="text-muted" style="margin:0;">Jadwal antrean periksa hari ini (<?= tanggal_indo($today, true) ?>).</p>
        </div>
        <span class="badge badge-mint" style="font-size:14px; padding:6px 14px;">
            Total Hari Ini: <?= count($antreans) ?> Pasien
        </span>
    </div>

    <?php if (empty($antreans)): ?>
        <div class="card empty-state">
            <?= render_mascot('happy', 110) ?>
            <div class="empty-state-title">Belum Ada Pasien Check-In</div>
            <div class="empty-state-desc">Belum ada pasien yang melakukan check-in di meja resepsionis untuk jadwal hari ini.</div>
        </div>
    <?php else: ?>
        <div style="display:flex; flex-direction:column; gap:16px;">
            <?php foreach ($antreans as $a): 
                $is_current = in_array($a['status'], ['dipanggil', 'diperiksa']);
                $badge_class = match($a['status']) {
                    'diperiksa' => 'badge-info badge-pulse',
                    'dipanggil' => 'badge-danger badge-pulse',
                    'selesai'   => 'badge-success',
                    'ditunda'   => 'badge-warning',
                    default     => 'badge-mint'
                };
            ?>
            <div class="card p-5 card-hover <?= $is_current ? 'border-primary' : '' ?>" 
                 style="<?= $is_current ? 'border:2px solid var(--primary); background:rgba(43,181,160,0.03);' : '' ?>">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
                    <div class="d-flex align-items-center gap-3">
                        <span style="font-family:var(--font-heading); font-size:26px; font-weight:800; color:var(--primary);">
                            A-<?= sprintf('%03d', $a['nomor_antrian']) ?>
                        </span>
                        <span class="badge <?= $badge_class ?>"><?= strtoupper($a['status']) ?></span>
                        <?php if ($a['prioritas'] > 0): ?>
                            <span class="badge <?= $a['prioritas'] == 2 ? 'badge-danger' : 'badge-warning' ?>">
                                <?= $a['prioritas'] == 2 ? 'DARURAT (CITO)' : 'PRIORITAS KHUSUS' ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <div style="font-size:12px; color:var(--text-muted);">
                        Check-In: <?= waktu_indo($a['waktu_checkin']) ?> &bull; Kode: <strong><?= e($a['kode_booking']) ?></strong>
                    </div>
                </div>

                <!-- Detail Pasien & Alert Medis -->
                <div class="grid grid-cols-3 gap-4 mb-3" style="font-size:14px; border-top:1px solid var(--border-color); padding-top:12px;">
                    <div>
                        <strong style="font-size:16px;"><?= e($a['nama_pasien']) ?></strong>
                        <div style="font-size:12px; color:var(--text-muted); margin-top:2px;">
                            No RM: <?= e($a['no_rm'] ?: '-') ?> &bull; Usia: <?= hitung_usia($a['tgl_lahir']) ?> (<?= $a['jenis_kelamin'] ?>)
                        </div>
                    </div>

                    <div>
                        <span class="text-muted" style="display:block; font-size:12px;">Rencana Layanan</span>
                        <strong><?= e($a['nama_layanan'] ?: 'Pemeriksaan Umum') ?></strong>
                    </div>

                    <div>
                        <span class="text-muted" style="display:block; font-size:12px;">Keluhan Pasien</span>
                        <span><?= e($a['keluhan'] ?: '-') ?></span>
                    </div>
                </div>

                <!-- Peringatan Alergi / Medis Berkedip Jika Ada -->
                <?php if ($a['alergi_obat'] || $a['kondisi_medis']): ?>
                <div class="alert-flashing-danger mb-3" style="font-size:13px; padding:8px 14px;">
                    <i class="bx bxs-shield-x" style="font-size:20px;"></i>
                    <span>
                        <strong>PERINGATAN DOKTER:</strong>
                        <?php if ($a['alergi_obat']): ?> Alergi Obat: <u><?= e($a['alergi_obat']) ?></u>. <?php endif; ?>
                        <?php if ($a['kondisi_medis']): ?> Riwayat Medis: <u><?= e($a['kondisi_medis']) ?></u>. <?php endif; ?>
                    </span>
                </div>
                <?php endif; ?>

                <!-- Tombol Aksi Dokter -->
                <div class="d-flex justify-content-end gap-2 pt-3" style="border-top:1px solid var(--border-color);">
                    <?php if ($a['status'] === 'menunggu' || $a['status'] === 'ditunda'): ?>
                        <form action="<?= base_url('dokter/panel_antrian.php') ?>" method="POST" class="d-inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="panggil">
                            <input type="hidden" name="id_antrian" value="<?= $a['id_antrian'] ?>">
                            <button type="submit" class="btn btn-secondary btn-sm">
                                <i class="bx bx-volume-full"></i> Panggil Pasien (TV)
                            </button>
                        </form>
                    <?php endif; ?>

                    <?php if ($a['status'] === 'dipanggil'): ?>
                        <form action="<?= base_url('dokter/panel_antrian.php') ?>" method="POST" class="d-inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="panggil">
                            <input type="hidden" name="id_antrian" value="<?= $a['id_antrian'] ?>">
                            <button type="submit" class="btn btn-ghost btn-sm">
                                <i class="bx bx-refresh"></i> Panggil Ulang
                            </button>
                        </form>
                    <?php endif; ?>

                    <?php if ($a['status'] !== 'selesai'): ?>
                        <form action="<?= base_url('dokter/panel_antrian.php') ?>" method="POST" class="d-inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="mulai">
                            <input type="hidden" name="id_antrian" value="<?= $a['id_antrian'] ?>">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="bx bx-play-circle"></i> Mulai Pemeriksaan EMR
                            </button>
                        </form>
                    <?php else: ?>
                        <a href="<?= base_url('dokter/cetak_rekam.php?id_antrian=' . $a['id_antrian']) ?>" target="_blank" class="btn btn-secondary btn-sm no-spa">
                            <i class="bx bx-printer"></i> Cetak Lembar EMR
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php
}, 'panel_antrian');

