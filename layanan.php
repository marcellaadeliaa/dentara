<?php
/**
 * DENTARA - Katalog Layanan & Estimasi Tarif
 * File: layanan.php
 */

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/layout_public.php';

render_public_layout('Katalog Layanan & Estimasi Biaya', function() {
    global $pdo;

    $kategori = trim($_GET['kategori'] ?? '');
    $search = trim($_GET['q'] ?? '');

    $sql = "SELECT * FROM layanan WHERE status = 'aktif'";
    $params = [];

    if ($kategori) {
        $sql .= " AND kategori = ?";
        $params[] = $kategori;
    }
    if ($search) {
        $sql .= " AND (nama_layanan LIKE ? OR deskripsi LIKE ?)";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
    }
    $sql .= " ORDER BY kategori ASC, id_layanan ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $layanan = $stmt->fetchAll();

    $categories = ['Semua', 'Preventif', 'Konservasi', 'Ortodonti', 'Bedah', 'Anak'];
?>
<div class="container" style="padding-top:110px; padding-bottom:60px;">
    <!-- Page Header -->
    <div class="text-center mb-6">
        <span class="badge badge-mint mb-2">KATALOG LENGKAP & TRANSPARAN</span>
        <h1 style="font-size:clamp(26px, 4vw, 40px); margin-bottom:8px;">Layanan Medis Gigi & Estimasi Tarif</h1>
        <p class="text-muted" style="max-width:600px; margin:0 auto;">
            Kami berkomitmen memberikan transparansi biaya dan estimasi durasi perawatan sebelum tindakan medis dimulai.
        </p>
    </div>

    <!-- Filter & Search Bar -->
    <div class="card p-4 mb-6">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-4">
            <!-- Filter Chips -->
            <div class="d-flex flex-wrap gap-2">
                <?php foreach ($categories as $cat): 
                    $isActive = ($cat === 'Semua' && !$kategori) || ($cat === $kategori);
                    $url = ($cat === 'Semua') ? base_url('layanan.php') : base_url('layanan.php?kategori=' . urlencode($cat));
                ?>
                <a href="<?= $url ?>" class="btn <?= $isActive ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
                    <?= $cat ?>
                </a>
                <?php endforeach; ?>
            </div>

            <!-- Search Form -->
            <form action="<?= base_url('layanan.php') ?>" method="GET" class="d-flex gap-2" style="max-width:320px; width:100%;">
                <?php if ($kategori): ?>
                    <input type="hidden" name="kategori" value="<?= e($kategori) ?>">
                <?php endif; ?>
                <div class="input-icon-group flex-1">
                    <i class="bx bx-search"></i>
                    <input type="text" name="q" value="<?= e($search) ?>" class="form-control" placeholder="Cari nama tindakan...">
                </div>
                <button type="submit" class="btn btn-primary btn-sm"><i class="bx bx-search"></i></button>
            </form>
        </div>
    </div>

    <!-- Service Grid -->
    <?php if (empty($layanan)): ?>
        <div class="empty-state">
            <?= render_mascot('thinking', 110) ?>
            <div class="empty-state-title">Layanan Tidak Ditemukan</div>
            <div class="empty-state-desc">Tidak ada layanan yang cocok dengan kata kunci atau kategori yang Anda pilih.</div>
            <a href="<?= base_url('layanan.php') ?>" class="btn btn-secondary btn-sm">Reset Pencarian</a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-3 gap-6">
            <?php foreach ($layanan as $item): ?>
            <div class="card card-hover" style="display:flex; flex-direction:column;">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <span class="badge badge-mint"><?= e($item['kategori']) ?></span>
                    <span class="text-muted" style="font-size:12px;">
                        <i class="bx bx-time"></i> ±<?= $item['estimasi_menit'] ?> Menit
                    </span>
                </div>
                <h3 style="font-size:18px; margin-bottom:8px; line-height:1.3; color:var(--text-main);">
                    <?= e($item['nama_layanan']) ?>
                </h3>
                <p class="text-muted" style="font-size:14px; line-height:1.6; margin-bottom:16px;">
                    <?= e($item['deskripsi']) ?>
                </p>
                <div class="d-flex align-items-center justify-content-between pt-3" style="border-top:1px solid var(--border-color); margin-top:auto;">
                    <div>
                        <span style="font-size:11px; color:var(--text-muted); display:block;">Estimasi Tarif</span>
                        <strong style="color:var(--primary); font-size:18px; font-family:var(--font-heading);"><?= rupiah($item['tarif_awal']) ?></strong>
                    </div>
                    <a href="<?= base_url('booking.php?layanan=' . $item['id_layanan']) ?>" class="btn btn-primary btn-sm">
                        Booking Ini
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php
}, 'layanan');

