<?php
/**
 * DENTARA - Portal Pasien: Kotak Pesan Notifikasi
 * File: pasien/pesan.php
 */

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout_pasien.php';

require_patient();

$id_akun = (int)$_SESSION['id_akun_pasien'];
$id_pasien_aktif = (int)($_SESSION['id_pasien_aktif'] ?? 0);

// Handle POST: Tandai Sudah Dibaca
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verify_csrf()) {
        header('Location: ' . base_url('pasien/pesan.php'));
        exit;
    }

    if ($_POST['action'] === 'read_single') {
        $id_pesan = (int)($_POST['id_pesan'] ?? 0);
        $stmt_r = $pdo->prepare("UPDATE pesan_notifikasi SET status = 'dibaca' WHERE id_pesan = ? AND id_pasien = ?");
        $stmt_r->execute([$id_pesan, $id_pasien_aktif]);
    } elseif ($_POST['action'] === 'read_all') {
        $stmt_all = $pdo->prepare("UPDATE pesan_notifikasi SET status = 'dibaca' WHERE id_pasien = ?");
        $stmt_all->execute([$id_pasien_aktif]);
        set_flash('success', 'Semua Pesan Dibaca', 'Semua pesan telah ditandai sebagai sudah dibaca.');
    }
    header('Location: ' . base_url('pasien/pesan.php'));
    exit;
}

render_patient_layout('Kotak Pesan Notifikasi', function() use ($id_pasien_aktif) {
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT * FROM pesan_notifikasi 
        WHERE id_pasien = ? 
        ORDER BY tanggal_kirim DESC
    ");
    $stmt->execute([$id_pasien_aktif]);
    $pesans = $stmt->fetchAll();

    $unread_total = 0;
    foreach ($pesans as $p) {
        if ($p['status'] === 'belum_dibaca') $unread_total++;
    }
?>
<div class="fade-in">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-4 mb-6">
        <div>
            <h1 style="font-size:var(--text-2xl); margin-bottom:4px;">Kotak Pesan & Pengingat</h1>
            <p class="text-muted" style="margin:0;">Pemberitahuan kontrol perawatan, instruksi medis, dan informasi dari klinik.</p>
        </div>

        <?php if ($unread_total > 0): ?>
        <form action="<?= base_url('pasien/pesan.php') ?>" method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="read_all">
            <button type="submit" class="btn btn-secondary btn-sm">
                <i class="bx bx-check-double"></i> Tandai Semua Sudah Dibaca
            </button>
        </form>
        <?php endif; ?>
    </div>

    <?php if (empty($pesans)): ?>
        <div class="card empty-state">
            <?= render_mascot('happy', 110) ?>
            <div class="empty-state-title">Kotak Pesan Masih Bersih</div>
            <div class="empty-state-desc">Belum ada pesan notifikasi baru untuk profil pasien ini.</div>
        </div>
    <?php else: ?>
        <div style="display:flex; flex-direction:column; gap:12px;">
            <?php foreach ($pesans as $msg): 
                $is_unread = $msg['status'] === 'belum_dibaca';
            ?>
            <div class="card p-4 <?= $is_unread ? 'border-primary' : '' ?>" style="<?= $is_unread ? 'background:rgba(43,181,160,0.04); border-left:4px solid var(--primary);' : '' ?>">
                <div class="d-flex align-items-start justify-content-between gap-3">
                    <div class="d-flex align-items-start gap-3">
                        <div style="width:38px; height:38px; border-radius:50%; background:<?= $is_unread ? 'var(--primary)' : 'var(--surface-subtle)' ?>; color:<?= $is_unread ? '#fff' : 'var(--text-muted)' ?>; display:flex; align-items:center; justify-content:center; font-size:20px; flex-shrink:0;">
                            <i class="bx bxs-bell"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <h3 style="font-size:16px; margin:0; font-weight:700; color:var(--text-main);">
                                    <?= e($msg['judul']) ?>
                                </h3>
                                <?php if ($is_unread): ?>
                                    <span class="badge badge-danger" style="font-size:10px;">Baru</span>
                                <?php endif; ?>
                            </div>
                            <p class="text-muted" style="font-size:14px; line-height:1.6; margin-bottom:6px;">
                                <?= e($msg['isi']) ?>
                            </p>
                            <span style="font-size:11px; color:var(--text-light);">
                                <i class="bx bx-time"></i> <?= format_datetime_indo($msg['tanggal_kirim']) ?>
                            </span>
                        </div>
                    </div>

                    <?php if ($is_unread): ?>
                    <form action="<?= base_url('pasien/pesan.php') ?>" method="POST">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="read_single">
                        <input type="hidden" name="id_pesan" value="<?= $msg['id_pesan'] ?>">
                        <button type="submit" class="btn btn-ghost btn-sm" title="Tandai Dibaca">
                            <i class="bx bx-check"></i>
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php
}, 'pesan');

