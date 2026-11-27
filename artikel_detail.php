<?php
/**
 * DENTARA - Detail Artikel Edukasi
 * File: artikel_detail.php
 */

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/layout_public.php';

$slug = trim($_GET['slug'] ?? '');

if (empty($slug)) {
    header('Location: ' . base_url('artikel.php'));
    exit;
}

global $pdo;
$stmt = $pdo->prepare("SELECT * FROM artikel WHERE slug = ? AND status = 'publish' LIMIT 1");
$stmt->execute([$slug]);
$artikel = $stmt->fetch();

if (!$artikel) {
    header('Location: ' . base_url('404.php'));
    exit;
}

// Ambil artikel terbaru untuk sidebar rekomendasi
$stmt_rec = $pdo->prepare("SELECT * FROM artikel WHERE status = 'publish' AND id_artikel != ? ORDER BY id_artikel DESC LIMIT 4");
$stmt_rec->execute([(int)$artikel['id_artikel']]);
$rekomendasi = $stmt_rec->fetchAll();

render_public_layout($artikel['judul'], function() use ($artikel, $rekomendasi) {
?>
<div class="container" style="padding-top:110px; padding-bottom:60px;">
    <!-- Breadcrumb -->
    <div class="mb-4" style="font-size:13px; color:var(--text-muted);">
        <a href="<?= base_url('index.php') ?>" class="text-muted">Beranda</a> &rsaquo; 
        <a href="<?= base_url('artikel.php') ?>" class="text-muted">Artikel Edukasi</a> &rsaquo; 
        <span class="text-primary"><?= e($artikel['judul']) ?></span>
    </div>

    <div class="grid grid-cols-3 gap-6 align-items-start">
        <!-- Main Article Content (Span 2) -->
        <article class="card p-6" style="grid-column: span 2; border-radius:var(--radius-lg);">
            <div style="height:240px; background:var(--grad-hero); border-radius:var(--radius-md); display:flex; align-items:center; justify-content:center; color:var(--primary); font-size:72px; margin-bottom:24px;">
                <i class="bx bxs-heart-circle"></i>
            </div>

            <div class="d-flex align-items-center gap-3 text-muted mb-3" style="font-size:13px;">
                <span><i class="bx bx-calendar text-primary"></i> <?= tanggal_indo(date('Y-m-d', strtotime($artikel['created_at'])), true) ?></span>
                &bull;
                <span><i class="bx bx-user text-primary"></i> Penulis: <strong><?= e($artikel['penulis']) ?></strong></span>
            </div>

            <h1 style="font-size:clamp(24px, 3.5vw, 34px); margin-bottom:20px; line-height:1.25; color:var(--text-main);">
                <?= e($artikel['judul']) ?>
            </h1>

            <div style="font-size:16px; line-height:1.8; color:var(--text-main); margin-bottom:32px;">
                <?= $artikel['isi'] ?>
            </div>

            <!-- Bagikan Artikel -->
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 pt-4" style="border-top:1px solid var(--border-color);">
                <div style="font-weight:700; font-size:14px; color:var(--text-muted);">
                    Bagikan wawasan kesehatan gigi ini:
                </div>
                <div class="d-flex gap-2">
                    <a href="https://api.whatsapp.com/send?text=<?= urlencode($artikel['judul'] . ' - ' . base_url('artikel_detail.php?slug=' . $artikel['slug'])) ?>" 
                       target="_blank" class="btn btn-success btn-sm no-spa">
                        <i class="bx bxl-whatsapp"></i> WhatsApp
                    </a>
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode(base_url('artikel_detail.php?slug=' . $artikel['slug'])) ?>" 
                       target="_blank" class="btn btn-secondary btn-sm no-spa">
                        <i class="bx bxl-facebook"></i> Facebook
                    </a>
                </div>
            </div>
        </article>

        <!-- Sidebar Rekomendasi -->
        <aside style="display:flex; flex-direction:column; gap:20px;">
            <div class="card p-5" style="border-radius:var(--radius-lg);">
                <h3 style="font-size:18px; margin-bottom:16px; border-bottom:1px solid var(--border-color); padding-bottom:10px;">
                    <i class="bx bx-news text-primary"></i> Artikel Terkait Lainnya
                </h3>
                <div style="display:flex; flex-direction:column; gap:16px;">
                    <?php foreach ($rekomendasi as $rec): ?>
                    <div>
                        <span style="font-size:11px; color:var(--text-muted); display:block; margin-bottom:2px;">
                            <?= tanggal_indo(date('Y-m-d', strtotime($rec['created_at']))) ?>
                        </span>
                        <h4 style="font-size:15px; margin:0; line-height:1.3;">
                            <a href="<?= base_url('artikel_detail.php?slug=' . $rec['slug']) ?>" style="color:var(--text-main);">
                                <?= e($rec['judul']) ?>
                            </a>
                        </h4>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Banner Booking Samping -->
            <div class="card p-5 text-center" style="background:var(--grad-hero); border-color:rgba(43,181,160,0.3);">
                <?= render_mascot('happy', 80) ?>
                <h4 style="font-size:18px; margin:12px 0 6px;">Punya Keluhan Sakit Gigi?</h4>
                <p class="text-muted" style="font-size:13px; line-height:1.5; margin-bottom:16px;">
                    Jangan tunggu hingga bertambah parah. Konsultasikan dengan dokter spesialis DENTARA sekarang.
                </p>
                <a href="<?= base_url('booking.php') ?>" class="btn btn-primary btn-sm w-100">
                    <i class="bx bx-calendar-plus"></i> Booking Janji Temu
                </a>
            </div>
        </aside>
    </div>
</div>
<?php
}, 'artikel');

