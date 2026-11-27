<?php
/**
 * DENTARA - Portal Dokter: Riwayat Pemeriksaan Pasien EMR
 * File: dokter/riwayat_pasien.php
 */

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout_staff.php';

require_role(['dokter', 'admin']);

$user = current_user();
$id_dokter = (int)($user['id_dokter'] ?? 1);

render_staff_layout('Arsip Riwayat Rekam Medis Pasien', function() use ($id_dokter) {
    global $pdo;

    $search = trim($_GET['q'] ?? '');

    $sql = "
        SELECT rm.*, p.nama_pasien, p.no_rm, p.nik, p.tgl_lahir, p.jenis_kelamin, p.alergi_obat
        FROM rekam_medis rm
        JOIN pasien p ON rm.id_pasien = p.id_pasien
        WHERE rm.id_dokter = ?
    ";
    $params = [$id_dokter];

    if ($search) {
        $sql .= " AND (p.nama_pasien LIKE ? OR p.no_rm LIKE ? OR rm.asesmen LIKE ?)";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
    }
    $sql .= " ORDER BY rm.tanggal_periksa DESC, rm.id_rekam DESC LIMIT 50";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rekams = $stmt->fetchAll();
?>
<div class="fade-in">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-4 mb-6">
        <div>
            <h1 style="font-size:var(--text-2xl); margin-bottom:4px;">Arsip Rekam Medis Pasien</h1>
            <p class="text-muted" style="margin:0;">Seluruh riwayat pemeriksaan, diagnosa, dan tindakan yang telah Anda berikan.</p>
        </div>
    </div>

    <!-- Search Box -->
    <div class="card p-4 mb-6">
        <form action="<?= base_url('dokter/riwayat_pasien.php') ?>" method="GET" class="d-flex gap-2">
            <div class="input-icon-group flex-1">
                <i class="bx bx-search"></i>
                <input type="text" name="q" value="<?= e($search) ?>" class="form-control" placeholder="Cari Nama Pasien, No RM, atau Diagnosa...">
            </div>
            <button type="submit" class="btn btn-primary btn-sm"><i class="bx bx-search"></i> Cari Arsip</button>
            <?php if ($search): ?>
                <a href="<?= base_url('dokter/riwayat_pasien.php') ?>" class="btn btn-secondary btn-sm">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Tabel Arsip Rekam Medis -->
    <div class="table-wrapper">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>Tanggal Periksa</th>
                    <th>No. RM & Pasien</th>
                    <th>Diagnosa Asesmen</th>
                    <th>Rencana Terapi</th>
                    <th class="text-center">Cetak Rekam</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rekams)): ?>
                <tr>
                    <td colspan="5" class="text-center p-5 text-muted">
                        Belum ada arsip riwayat rekam medis.
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($rekams as $r): ?>
                    <tr>
                        <td>
                            <strong><?= tanggal_indo($r['tanggal_periksa']) ?></strong>
                            <div style="font-size:12px; color:var(--text-muted);"><?= waktu_indo($r['created_at']) ?></div>
                        </td>
                        <td>
                            <strong style="color:var(--text-main); font-size:15px;"><?= e($r['nama_pasien']) ?></strong>
                            <span class="badge badge-mint" style="display:block; width:fit-content; margin-top:2px;"><?= e($r['no_rm']) ?></span>
                            <?php if ($r['alergi_obat']): ?>
                                <span class="badge badge-danger" style="margin-top:2px;">Alergi: <?= e($r['alergi_obat']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?= e($r['asesmen']) ?></strong>
                            <div style="font-size:12px; color:var(--text-muted); margin-top:2px; max-width:260px;">
                                O: <?= substr(e($r['objektif']), 0, 60) ?>...
                            </div>
                        </td>
                        <td style="max-width:240px; font-size:13px; color:var(--text-muted);">
                            <?= e($r['plan'] ?: '-') ?>
                        </td>
                        <td class="text-center">
                            <a href="<?= base_url('dokter/cetak_rekam.php?id_rekam=' . $r['id_rekam']) ?>" target="_blank" class="btn btn-secondary btn-sm no-spa">
                                <i class="bx bx-printer"></i> Cetak Lembar EMR
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
}, 'riwayat_pasien');

