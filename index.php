<?php
/**
 * DENTARA - Landing Page
 * File: index.php
 */

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/layout_public.php';

render_public_layout('Klinik Gigi Modern & Ramah Keluarga', function() {
    global $pdo;

    // 1. Cek Status Operasional Hari Ini
    $today = date('Y-m-d');
    $hari_map = [
        'Sun' => 'Minggu', 'Mon' => 'Senin', 'Tue' => 'Selasa',
        'Wed' => 'Rabu', 'Thu' => 'Kamis', 'Fri' => 'Jumat', 'Sat' => 'Sabtu'
    ];
    $hari_ini = $hari_map[date('D')];

    // Cek libur mendadak
    $stmt_libur = $pdo->prepare("SELECT alasan FROM libur_mendadak WHERE tanggal_libur = ? AND status = 'aktif'");
    $stmt_libur->execute([$today]);
    $libur_hari_ini = $stmt_libur->fetchColumn();

    // Cek jam operasional hari ini
    $stmt_jk = $pdo->prepare("SELECT * FROM jadwal_klinik WHERE hari = ?");
    $stmt_jk->execute([$hari_ini]);
    $jk_hari_ini = $stmt_jk->fetch();

    // 2. Banner Promo Aktif
    $stmt_promo = $pdo->query("SELECT * FROM promo_banner WHERE status = 'aktif' AND CURRENT_DATE() BETWEEN tanggal_mulai AND tanggal_selesai ORDER BY id_promo DESC");
    $promos = $stmt_promo->fetchAll();

    // 3. Layanan Unggulan
    $stmt_layanan = $pdo->query("SELECT * FROM layanan WHERE status = 'aktif' ORDER BY id_layanan ASC LIMIT 6");
    $layanan_list = $stmt_layanan->fetchAll();

    // 4. Dokter Praktik
    $stmt_doc = $pdo->query("
        SELECT d.*, p.nama_lengkap, p.email, p.no_hp 
        FROM dokter d 
        JOIN pengguna p ON d.id_pengguna = p.id_pengguna 
        WHERE p.status = 'aktif' 
        LIMIT 3
    ");
    $dokter_list = $stmt_doc->fetchAll();

    // 5. Artikel Terbaru
    $stmt_art = $pdo->query("SELECT * FROM artikel WHERE status = 'publish' ORDER BY id_artikel DESC LIMIT 3");
    $artikel_list = $stmt_art->fetchAll();

    // 6. FAQ
    $stmt_faq = $pdo->query("SELECT * FROM faq ORDER BY urutan ASC LIMIT 4");
    $faq_list = $stmt_faq->fetchAll();

    // Info Klinik
    $stmt_k = $pdo->query("SELECT * FROM pengaturan_klinik LIMIT 1");
    $klinik = $stmt_k->fetch();
?>

<!-- HERO SECTION -->
<section class="hero-section">
    <div class="container">
        <!-- Banner Status Operasional Realtime -->
        <div class="mb-4">
            <?php if ($libur_hari_ini): ?>
                <div class="alert alert-warning" style="display:inline-flex; align-items:center; border-radius:var(--radius-pill); padding:8px 20px;">
                    <i class="bx bx-calendar-x" style="font-size:20px;"></i>
                    <span><strong>Klinik Libur Hari Ini:</strong> <?= e($libur_hari_ini) ?></span>
                </div>
            <?php elseif ($jk_hari_ini && $jk_hari_ini['status'] === 'Buka'): ?>
                <div class="status-pill">
                    <span class="status-dot-pulse open"></span>
                    <span>
                        <strong>Buka Hari Ini (<?= $hari_ini ?>):</strong> 
                        Sesi 1: <?= substr($jk_hari_ini['buka_1'], 0, 5) ?> - <?= substr($jk_hari_ini['tutup_1'], 0, 5) ?> WIB
                        <?php if ($jk_hari_ini['buka_2']): ?>
                        &bull; Sesi 2: <?= substr($jk_hari_ini['buka_2'], 0, 5) ?> - <?= substr($jk_hari_ini['tutup_2'], 0, 5) ?> WIB
                        <?php endif; ?>
                    </span>
                </div>
            <?php else: ?>
                <div class="status-pill">
                    <span class="status-dot-pulse closed"></span>
                    <span><strong>Klinik Tutup Hari Ini (<?= $hari_ini ?>)</strong> &bull; Buka kembali besok pagi.</span>
                </div>
            <?php endif; ?>
        </div>

        <div class="hero-content">
            <div class="hero-text">
                <h1 class="hero-title">
                    Senyum Sehat,<br>
                    <span class="highlight-text">Hidup Lebih Hebat!</span>
                </h1>
                <p class="hero-desc">
                    Selamat datang di <strong>DENTARA</strong>, klinik spesialis gigi modern yang menggabungkan kenyamanan tanpa rasa takut, teknologi digital canggih, dan dokter gigi berpengalaman untuk seluruh keluarga.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="<?= base_url('booking.php') ?>" class="btn btn-primary btn-lg">
                        <i class="bx bx-calendar-check"></i> Booking Janji Temu Sekarang
                    </a>
                    <a href="tel:<?= preg_replace('/[^0-9]/', '', $klinik['telepon'] ?? '02287654321') ?>" class="btn btn-secondary btn-lg no-spa">
                        <i class="bx bx-phone-call text-danger"></i> Layanan Darurat Gigi
                    </a>
                </div>
            </div>

            <div class="hero-visual">
                <div class="animate-float">
                    <?= render_mascot('happy', 260) ?>
                </div>
            </div>
        </div>

        <!-- STRIP STATISTIK KEPERCAYAAN -->
        <div class="stats-strip">
            <div class="stat-item">
                <h3 id="stat-kunjungan">1250</h3>
                <p>Pasien Telah Tersenyum</p>
            </div>
            <div class="stat-item">
                <h3 id="stat-dokter">8</h3>
                <p>Dokter Gigi Spesialis</p>
            </div>
            <div class="stat-item">
                <h3 id="stat-tahun">6</h3>
                <p>Tahun Dedikasi Melayani</p>
            </div>
            <div class="stat-item">
                <h3>99.4%</h3>
                <p>Tingkat Kepuasan Pasien</p>
            </div>
        </div>
    </div>
</section>

<!-- PROMO CAROUSEL / BANNER -->
<?php if (!empty($promos)): ?>
<section class="container mt-6">
    <div style="background:var(--grad-purple-blue); border-radius:var(--radius-lg); padding:28px 36px; color:#fff; box-shadow:var(--shadow-md); display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:20px;">
        <div>
            <span class="badge badge-gold mb-2" style="font-size:12px;"><i class="bx bxs-gift"></i> PROMO SPESIAL BULAN INI</span>
            <h2 style="color:#fff; font-size:var(--text-xl); margin:4px 0;"><?= e($promos[0]['judul']) ?></h2>
            <p style="opacity:0.95; font-size:var(--text-sm); max-width:650px;"><?= e($promos[0]['deskripsi']) ?></p>
        </div>
        <div>
            <a href="<?= base_url('booking.php') ?>" class="btn btn-gold btn-md">
                Klaim Promo Ini
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- KATALOG LAYANAN PREVIEW -->
<section class="container" style="margin-top:80px;">
    <div class="text-center mb-6">
        <span class="badge badge-mint mb-2">PELAYANAN TERBAIK</span>
        <h2 style="font-size:clamp(24px, 3.5vw, 36px);">Layanan Medis Gigi Terpadu</h2>
        <p class="text-muted" style="max-width:560px; margin:0 auto;">
            Mulai dari pembersihan karang gigi rutin, behel estetika, hingga perawatan saluran akar bebas rasa sakit.
        </p>
    </div>

    <div class="grid grid-cols-3 gap-6">
        <?php foreach ($layanan_list as $lay): ?>
        <div class="card card-hover">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="badge badge-mint"><?= e($lay['kategori']) ?></span>
                <span class="text-muted" style="font-size:12px;"><i class="bx bx-time"></i> ±<?= $lay['estimasi_menit'] ?> menit</span>
            </div>
            <h3 style="font-size:var(--text-lg); margin-bottom:8px; line-height:1.3;"><?= e($lay['nama_layanan']) ?></h3>
            <p class="text-muted" style="font-size:var(--text-sm); line-height:1.6; margin-bottom:16px;">
                <?= e($lay['deskripsi']) ?>
            </p>
            <div class="d-flex align-items-center justify-content-between pt-3" style="border-top:1px solid var(--border-color); margin-top:auto;">
                <div>
                    <span style="font-size:11px; color:var(--text-muted); display:block;">Mulai dari</span>
                    <strong style="color:var(--primary); font-size:16px; font-family:var(--font-heading);"><?= rupiah($lay['tarif_awal']) ?></strong>
                </div>
                <a href="<?= base_url('booking.php?layanan=' . $lay['id_layanan']) ?>" class="btn btn-primary btn-sm">
                    Pilih Layanan
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="text-center mt-6">
        <a href="<?= base_url('layanan.php') ?>" class="btn btn-secondary btn-md">
            Lihat Semua Layanan & Tarif Lengkap <i class="bx bx-right-arrow-alt"></i>
        </a>
    </div>
</section>

<!-- ALUR BOOKING 4 LANGKAH -->
<section style="background:var(--surface-subtle); padding:80px 0; margin-top:80px;">
    <div class="container">
        <div class="text-center mb-6">
            <span class="badge badge-purple mb-2">MUDAH & CEPAT</span>
            <h2 style="font-size:clamp(24px, 3.5vw, 36px);">Alur Kunjungan 4 Langkah</h2>
            <p class="text-muted">Proses terintegrasi dari pendaftaran online hingga kuitansi pembayaran.</p>
        </div>

        <div class="booking-stepper">
            <div class="step-card">
                <div class="step-number">1</div>
                <h4 style="font-size:18px; margin-bottom:8px;">Booking Online</h4>
                <p class="text-muted" style="font-size:13px;">Pilih dokter spesialis, layanan, dan waktu yang pas untuk Anda.</p>
            </div>
            <div class="step-card">
                <div class="step-number">2</div>
                <h4 style="font-size:18px; margin-bottom:8px;">Dapatkan QR Code</h4>
                <p class="text-muted" style="font-size:13px;">Sistem menerbitkan Kode Booking unik dan tiket antrean digital.</p>
            </div>
            <div class="step-card">
                <div class="step-number">3</div>
                <h4 style="font-size:18px; margin-bottom:8px;">Check-In & Periksa</h4>
                <p class="text-muted" style="font-size:13px;">Tunjukkan QR di meja resepsionis dan nikmati tindakan dokter yang ramah.</p>
            </div>
            <div class="step-card">
                <div class="step-number">4</div>
                <h4 style="font-size:18px; margin-bottom:8px;">Obat & Kasir</h4>
                <p class="text-muted" style="font-size:13px;">Ambil resep di apotek klinik dan bayar fleksibel via QRIS atau tunai.</p>
            </div>
        </div>
    </div>
</section>

<!-- DOKTER DIREKTORI PREVIEW -->
<section class="container" style="margin-top:80px;">
    <div class="text-center mb-6">
        <span class="badge badge-mint mb-2">TIM MEDIS KAMI</span>
        <h2 style="font-size:clamp(24px, 3.5vw, 36px);">Dokter Gigi Spesialis Berpengalaman</h2>
        <p class="text-muted">Mengutamakan diagnosis akurat, transparansi rencana tindakan, dan sentuhan kehangatan.</p>
    </div>

    <div class="grid grid-cols-3 gap-6">
        <?php foreach ($dokter_list as $doc): ?>
        <div class="card text-center card-hover">
            <div style="width:90px; height:90px; border-radius:50%; background:var(--grad-mint-blue); color:#fff; display:flex; align-items:center; justify-content:center; font-size:36px; margin:0 auto 16px; box-shadow:var(--shadow-sm);">
                <i class="bx bxs-user"></i>
            </div>
            <h3 style="font-size:18px; margin-bottom:4px;"><?= e($doc['nama_lengkap']) ?></h3>
            <span class="badge badge-purple mb-3">Spesialis <?= e($doc['spesialisasi']) ?></span>
            <p class="text-muted" style="font-size:13px; line-height:1.6; margin-bottom:16px;">
                <?= e($doc['bio']) ?>
            </p>
            <div style="border-top:1px solid var(--border-color); padding-top:14px; margin-top:auto;">
                <span style="font-size:12px; color:var(--text-muted); display:block; margin-bottom:8px;">STR: <?= e($doc['no_str']) ?></span>
                <a href="<?= base_url('booking.php?dokter=' . $doc['id_dokter']) ?>" class="btn btn-outline btn-sm w-100">
                    Jadwalkan Konsultasi
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="text-center mt-6">
        <a href="<?= base_url('dokter.php') ?>" class="btn btn-secondary btn-md">
            Lihat Jadwal Praktik Lengkap Seluruh Dokter <i class="bx bx-right-arrow-alt"></i>
        </a>
    </div>
</section>

<!-- EDUKASI & ARTIKEL GRID -->
<section class="container" style="margin-top:80px;">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-4 mb-6">
        <div>
            <span class="badge badge-mint mb-2">EDUKASI KESEHATAN GIGI</span>
            <h2 style="font-size:clamp(22px, 3vw, 32px); margin:0;">Artikel & Tips Senyum Sehat</h2>
        </div>
        <a href="<?= base_url('artikel.php') ?>" class="btn btn-secondary btn-sm">Lihat Semua Artikel</a>
    </div>

    <div class="grid grid-cols-3 gap-6">
        <?php foreach ($artikel_list as $art): ?>
        <div class="card card-hover" style="display:flex; flex-direction:column;">
            <div style="height:140px; background:var(--grad-hero); border-radius:var(--radius-sm); display:flex; align-items:center; justify-content:center; color:var(--primary); font-size:48px; margin-bottom:16px;">
                <i class="bx bxs-book-heart"></i>
            </div>
            <span class="text-muted" style="font-size:12px; margin-bottom:6px;">
                <i class="bx bx-calendar"></i> <?= tanggal_indo(date('Y-m-d', strtotime($art['created_at']))) ?> &bull; <?= e($art['penulis']) ?>
            </span>
            <h3 style="font-size:18px; margin-bottom:8px; line-height:1.3;">
                <a href="<?= base_url('artikel_detail.php?slug=' . $art['slug']) ?>" style="color:var(--text-main);">
                    <?= e($art['judul']) ?>
                </a>
            </h3>
            <p class="text-muted" style="font-size:13px; line-height:1.6; margin-bottom:16px;">
                <?= substr(strip_tags($art['isi']), 0, 110) ?>...
            </p>
            <a href="<?= base_url('artikel_detail.php?slug=' . $art['slug']) ?>" class="btn btn-ghost btn-sm" style="margin-top:auto; align-self:flex-start; padding-left:0;">
                Baca Selengkapnya <i class="bx bx-right-arrow-alt"></i>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- FAQ ACCORDION -->
<section class="container" style="margin-top:80px; max-width:900px;">
    <div class="text-center mb-6">
        <span class="badge badge-purple mb-2">TANYA JAWAB</span>
        <h2 style="font-size:clamp(22px, 3vw, 32px);">Pertanyaan yang Sering Diajukan</h2>
        <p class="text-muted">Informasi seputar pendaftaran, asuransi, dan perawatan anak.</p>
    </div>

    <div style="display:flex; flex-direction:column; gap:12px;">
        <?php foreach ($faq_list as $idx => $f): ?>
        <div class="card p-4" style="cursor:pointer;" onclick="toggleFaq(this)">
            <div class="d-flex align-items-center justify-content-between">
                <h4 style="font-size:16px; margin:0; color:var(--text-main); font-weight:700;">
                    <i class="bx bx-help-circle text-primary"></i> <?= e($f['pertanyaan']) ?>
                </h4>
                <i class="bx bx-chevron-down faq-arrow" style="font-size:22px; transition:transform 0.3s;"></i>
            </div>
            <div class="faq-content text-muted mt-3" style="display:<?= $idx === 0 ? 'block' : 'none' ?>; font-size:14px; line-height:1.6; border-top:1px solid var(--border-color); padding-top:12px;">
                <?= e($f['jawaban']) ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- PETA LOKASI KLINIK -->
<section class="container" style="margin-top:80px;">
    <div class="card p-0" style="overflow:hidden; border-radius:var(--radius-lg);">
        <div class="p-5 d-flex align-items-center justify-content-between flex-wrap gap-4" style="border-bottom:1px solid var(--border-color);">
            <div>
                <span class="badge badge-mint mb-2">LOKASI STRATEGIS</span>
                <h3 style="font-size:22px; margin-bottom:4px;"><?= e($klinik['nama_klinik'] ?? 'DENTARA Dental Care') ?></h3>
                <p class="text-muted" style="margin:0;"><i class="bx bx-map text-primary"></i> <?= e($klinik['alamat'] ?? '') ?></p>
            </div>
            <a href="https://maps.google.com/?q=<?= $klinik['latitude'] ?? '-6.885623' ?>,<?= $klinik['longitude'] ?? '107.613942' ?>" target="_blank" class="btn btn-outline btn-md no-spa">
                <i class="bx bx-navigation"></i> Petunjuk Arah Google Maps
            </a>
        </div>
        <div style="width:100%; height:320px; background:#E2E8F0;">
            <iframe 
                width="100%" 
                height="100%" 
                style="border:0;" 
                loading="lazy" 
                allowfullscreen 
                src="https://maps.google.com/maps?q=<?= $klinik['latitude'] ?? '-6.885623' ?>,<?= $klinik['longitude'] ?? '107.613942' ?>&hl=id&z=15&output=embed">
            </iframe>
        </div>
    </div>
</section>

<script>
function toggleFaq(card) {
    const content = card.querySelector('.faq-content');
    const arrow = card.querySelector('.faq-arrow');
    if (content.style.display === 'none' || content.style.display === '') {
        content.style.display = 'block';
        arrow.style.transform = 'rotate(180deg)';
    } else {
        content.style.display = 'none';
        arrow.style.transform = 'rotate(0deg)';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    DENTARA.countUp(document.getElementById('stat-kunjungan'), 1250, 1500);
    DENTARA.countUp(document.getElementById('stat-dokter'), 8, 1000);
    DENTARA.countUp(document.getElementById('stat-tahun'), 6, 800);
});
</script>
<?php
}, 'beranda');

