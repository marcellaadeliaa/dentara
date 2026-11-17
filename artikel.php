<?php
/**
 * DENTARA - Direktori Artikel & Edukasi Kesehatan Gigi
 * File: artikel.php
 */

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/layout_public.php';

render_public_layout('Edukasi & Tips Kesehatan Gigi', function() {
    global $pdo;

    $search = trim($_GET['q'] ?? '');

    $sql = "SELECT * FROM artikel WHERE status = 'publish'";
    $params = [];

    if ($search) {
        $sql .= " AND (judul LIKE ? OR isi LIKE ? OR penulis LIKE ?)";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
    }
    $sql .= " ORDER BY id_artikel DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $artikels = $stmt->fetchAll();
?>
<div class="container" style="padding-top:110px; padding-bottom:60px;">
    <div class="text-center mb-6">
        <span class="badge badge-mint mb-2">EDUKASI KESEHATAN GIGI</span>
        <h1 style="font-size:clamp(26px, 4vw, 40px); margin-bottom:8px;">Artikel, Wawasan & Tips Dokter Gigi</h1>
        <p class="text-muted" style="max-width:600px; margin:0 auto;">
            Panduan lengkap merawat kesehatan mulut dan gigi untuk Anda dan buah hati, ditulis langsung oleh tim dokter gigi DENTARA.
        </p>
    </div>

    <!-- Search Bar -->
    <div class="card p-4 mb-6" style="max-width:600px; margin-left:auto; margin-right:auto;">
        <form action="<?= base_url('artikel.php') ?>" method="GET" class="d-flex gap-2">
            <div class="input-icon-group flex-1">
                <i class="bx bx-search"></i>
                <input type="text" name="q" value="<?= e($search) ?>" class="form-control" placeholder="Cari topik (misal: karang gigi, behel, anak)...">
            </div>
            <button type="submit" class="btn btn-primary btn-sm"><i class="bx bx-search"></i> Cari</button>
        </form>
    </div>

    <?php if (empty($artikels)): ?>
        <div class="empty-state">
            <?= render_mascot('thinking', 110) ?>
            <div class="empty-state-title">Artikel Tidak Ditemukan</div>
            <div class="empty-state-desc">Belum ada artikel yang cocok dengan kata kunci pencarian Anda.</div>
            <a href="<?= base_url('artikel.php') ?>" class="btn btn-secondary btn-sm">Lihat Semua Artikel</a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-3 gap-6">
            <?php foreach ($artikels as $art): ?>
            <div class="card card-hover" style="display:flex; flex-direction:column;">
                <div style="height:150px; background:var(--grad-hero); border-radius:var(--radius-sm); display:flex; align-items:center; justify-content:center; color:var(--primary); font-size:52px; margin-bottom:16px;">
                    <i class="bx bxs-book-heart"></i>
                </div>
                <div class="text-muted mb-2" style="font-size:12px;">
                    <i class="bx bx-calendar"></i> <?= tanggal_indo(date('Y-m-d', strtotime($art['created_at']))) ?> 
                    &bull; <i class="bx bx-user"></i> <?= e($art['penulis']) ?>
                </div>
                <h3 style="font-size:18px; margin-bottom:8px; line-height:1.3;">
                    <a href="<?= base_url('artikel_detail.php?slug=' . $art['slug']) ?>" style="color:var(--text-main);">
                        <?= e($art['judul']) ?>
                    </a>
                </h3>
                <p class="text-muted" style="font-size:14px; line-height:1.6; margin-bottom:16px;">
                    <?= substr(strip_tags($art['isi']), 0, 120) ?>...
                </p>
                <div style="margin-top:auto; pt-3; border-top:1px solid var(--border-color); padding-top:12px;">
                    <a href="<?= base_url('artikel_detail.php?slug=' . $art['slug']) ?>" class="btn btn-primary btn-sm w-100">
                        Baca Artikel Lengkap <i class="bx bx-right-arrow-alt"></i>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php
}, 'artikel');

