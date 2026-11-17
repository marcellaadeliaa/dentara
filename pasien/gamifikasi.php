<?php
/**
 * DENTARA - Portal Pasien: Gamifikasi Kartu Denta Ceria
 * File: pasien/gamifikasi.php
 */

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout_pasien.php';

require_patient();

$id_akun = (int)$_SESSION['id_akun_pasien'];
$id_pasien_aktif = (int)($_SESSION['id_pasien_aktif'] ?? 0);

// Ambil profil pasien aktif
$stmt_p = $pdo->prepare("SELECT * FROM pasien WHERE id_pasien = ? AND id_akun = ?");
$stmt_p->execute([$id_pasien_aktif, $id_akun]);
$pasien = $stmt_p->fetch();

// Poin Gamifikasi
$stmt_poin = $pdo->prepare("SELECT * FROM gamifikasi_poin WHERE id_pasien = ?");
$stmt_poin->execute([$id_pasien_aktif]);
$gamifikasi = $stmt_poin->fetch() ?: ['total_poin' => 0, 'jumlah_kunjungan_selesai' => 0];

$poin_sekarang = (int)$gamifikasi['total_poin'];
$kunjungan = (int)$gamifikasi['jumlah_kunjungan_selesai'];
$bintang_count = min(5, max(1, $kunjungan));

$newly_won_card = null;

// Handle Gacha POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'gacha') {
    if (!verify_csrf()) {
        set_flash('error', 'Token Kedaluwarsa', 'Silakan refresh halaman.');
        header('Location: ' . base_url('pasien/gamifikasi.php'));
        exit;
    }

    $biaya_poin = 100;
    if ($poin_sekarang < $biaya_poin) {
        set_flash('warning', 'Poin Tidak Cukup', "Kamu membutuhkan minimal {$biaya_poin} Poin Denta untuk membuka Kartu Keberuntungan.");
        header('Location: ' . base_url('pasien/gamifikasi.php'));
        exit;
    }

    try {
        $pdo->beginTransaction();

        // 1. Kurangi Poin
        $stmt_dec = $pdo->prepare("UPDATE gamifikasi_poin SET total_poin = total_poin - ? WHERE id_pasien = ?");
        $stmt_dec->execute([$biaya_poin, $id_pasien_aktif]);

        // 2. Pilih Kartu Acak dari master_kartu
        $stmt_cards = $pdo->query("SELECT * FROM master_kartu ORDER BY RAND() LIMIT 1");
        $card = $stmt_cards->fetch();

        // 3. Simpan ke koleksi_kartu_pasien
        $stmt_add = $pdo->prepare("INSERT INTO koleksi_kartu_pasien (id_pasien, id_kartu) VALUES (?, ?)");
        $stmt_add->execute([$id_pasien_aktif, $card['id_kartu']]);

        $pdo->commit();

        $_SESSION['gacha_won'] = $card;
        set_flash('success', 'Hebat!', "Selamat! Kamu berhasil mendapatkan kartu: {$card['nama_kartu']}");
        header('Location: ' . base_url('pasien/gamifikasi.php'));
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        set_flash('error', 'Gagal Membuka Kartu', $e->getMessage());
        header('Location: ' . base_url('pasien/gamifikasi.php'));
        exit;
    }
}

if (isset($_SESSION['gacha_won'])) {
    $newly_won_card = $_SESSION['gacha_won'];
    unset($_SESSION['gacha_won']);
}

render_patient_layout('Petualangan Kartu Denta Ceria', function() use ($pasien, $gamifikasi, $poin_sekarang, $bintang_count, $newly_won_card, $id_pasien_aktif) {
    global $pdo;

    // Ambil master kartu
    $stmt_mk = $pdo->query("SELECT * FROM master_kartu ORDER BY poin_diperlukan ASC, id_kartu ASC");
    $all_cards = $stmt_mk->fetchAll();

    // Ambil daftar kartu yang sudah dimiliki pasien
    $stmt_owned = $pdo->prepare("SELECT id_kartu, COUNT(*) as qty, MIN(tanggal_didapat) as pertama_dapat FROM koleksi_kartu_pasien WHERE id_pasien = ? GROUP BY id_kartu");
    $stmt_owned->execute([$id_pasien_aktif]);
    $owned_map = [];
    foreach ($stmt_owned->fetchAll() as $row) {
        $owned_map[$row['id_kartu']] = $row;
    }
?>
<div class="fade-in">
    <!-- HEADER GAMIFIKASI & BINTANG KUNJUNGAN -->
    <div class="gamifikasi-card-header">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-4">
            <div>
                <span class="badge badge-gold mb-2"><i class="bx bxs-trophy"></i> PETUALANGAN GIGI CERIA</span>
                <h1 style="font-size:clamp(22px, 3.5vw, 32px); color:#78350F; margin:4px 0 8px;">
                    Galeri Kartu & Bintang Si Kecil: <?= e($pasien['nama_pasien']) ?>
                </h1>
                <p style="color:#92400E; font-size:14px; max-width:600px; margin:0;">
                    Setiap kali menyelesaikan pemeriksaan di klinik, kamu akan mendapatkan bintang keberanian dan Poin Denta untuk membuka kartu-kartu legendaris!
                </p>

                <!-- Rating Bintang Kunjungan -->
                <div class="star-rating-box mt-3">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="bx bxs-star star-item <?= $i <= $bintang_count ? 'active' : '' ?>"></i>
                    <?php endfor; ?>
                    <span style="font-weight:700; color:#92400E; margin-left:8px; font-size:14px;">
                        Tingkat Keberanian (<?= $bintang_count ?> / 5 Bintang)
                    </span>
                </div>
            </div>

            <!-- Poin Box Display -->
            <div class="text-center" style="background:#fff; padding:18px 24px; border-radius:var(--radius-lg); box-shadow:var(--shadow-sm); border:2px solid #FFD166;">
                <div style="font-size:11px; text-transform:uppercase; font-weight:700; color:#92400E;">Total Poin Kamu</div>
                <div style="font-family:var(--font-heading); font-size:38px; font-weight:800; color:#B45309; line-height:1; margin:4px 0;">
                    <?= $poin_sekarang ?>
                </div>
                <span style="font-size:12px; color:var(--text-muted);">Biaya Gacha: 100 Poin</span>
            </div>
        </div>
    </div>

    <!-- AREA GACHA LUCKY CARD -->
    <div class="card p-6 mb-6 text-center" style="border-radius:var(--radius-lg); background:var(--grad-hero); border-color:rgba(43,181,160,0.3);">
        <h2 style="font-size:24px; margin-bottom:6px;">Buka Kartu Misteri (Lucky Draw)</h2>
        <p class="text-muted" style="max-width:500px; margin:0 auto 24px; font-size:14px;">
            Gunakan 100 Poin Denta untuk mengundi satu kartu baru dengan sentuhan animasi 3D spektakuler!
        </p>

        <!-- Wadah 3D Flip Card -->
        <div class="card-flip-container mb-4" onclick="flipGachaCard(this)">
            <div class="card-inner <?= $newly_won_card ? 'is-flipped' : '' ?>" id="gacha-inner-card">
                <!-- Sisi Depan: Cover Belakang Kartu -->
                <div class="card-front">
                    <div class="card-front-pattern">
                        <?= render_mascot('happy', 90) ?>
                        <div style="font-family:var(--font-heading); font-size:20px; font-weight:700; color:#FFD166; margin-top:8px;">
                            DENTARA
                        </div>
                        <span style="font-size:11px; color:#E2E8F0; opacity:0.8;">Klik untuk Balik</span>
                    </div>
                </div>

                <!-- Sisi Belakang: Hasil Undian Kartu -->
                <div class="card-back <?= $newly_won_card ? 'rarity-' . strtolower($newly_won_card['rarity']) : '' ?>">
                    <?php if ($newly_won_card): ?>
                        <div style="width:100%; height:100%; display:flex; flex-direction:column; justify-content:space-between;">
                            <div>
                                <span class="badge badge-<?= strtolower($newly_won_card['rarity']) ?> mb-2">
                                    <?= strtoupper($newly_won_card['rarity']) ?>
                                </span>
                                <h3 style="font-size:16px; margin:4px 0; color:var(--text-main); font-weight:700;">
                                    <?= e($newly_won_card['nama_kartu']) ?>
                                </h3>
                            </div>
                            <div style="margin:12px 0;">
                                <?= render_mascot('success', 100) ?>
                            </div>
                            <p style="font-size:12px; color:var(--text-muted); line-height:1.4; margin:0;">
                                <?= e($newly_won_card['deskripsi_edukatif']) ?>
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="d-flex flex-column align-items-center justify-content-center h-100">
                            <i class="bx bxs-gift text-primary" style="font-size:48px;"></i>
                            <h4 style="margin-top:12px; font-size:16px;">Tarik Kartu Keberuntungan</h4>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <form action="<?= base_url('pasien/gamifikasi.php') ?>" method="POST" class="d-inline-block">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="gacha">
            <button type="submit" class="btn btn-gold btn-lg" <?= $poin_sekarang < 100 ? 'disabled' : '' ?>>
                <i class="bx bxs-magic-wand"></i> Undi Kartu Baru (100 Poin)
            </button>
        </form>
    </div>

    <!-- GALERI SEMUA KARTU DENTARA -->
    <div class="card p-6" style="border-radius:var(--radius-lg);">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4 pb-3" style="border-bottom:1px solid var(--border-color);">
            <div>
                <h3 style="font-size:20px; margin:0;">Galeri Koleksi Seri: Smiling Guardians</h3>
                <p class="text-muted" style="font-size:13px; margin:2px 0 0;">
                    Kumpulkan seluruh <?= count($all_cards) ?> kartu unik untuk menjaga senyum indahmu selalu bersinar!
                </p>
            </div>
            <span class="badge badge-purple" style="font-size:13px;">
                Terkumpul: <?= count($owned_map) ?> / <?= count($all_cards) ?> Kartu
            </span>
        </div>

        <div class="cards-grid">
            <?php foreach ($all_cards as $c): 
                $is_owned = isset($owned_map[$c['id_kartu']]);
                $qty = $is_owned ? $owned_map[$c['id_kartu']]['qty'] : 0;
                $rarity_class = 'rarity-' . strtolower($c['rarity']);
                $badge_class = 'badge-' . strtolower($c['rarity']);
            ?>
            <div class="gallery-card-item <?= $is_owned ? $rarity_class : 'card-item-locked' ?>">
                <span class="badge <?= $badge_class ?> mb-2" style="font-size:10px;">
                    <?= strtoupper($c['rarity']) ?>
                </span>

                <div style="height:100px; display:flex; align-items:center; justify-content:center; margin-bottom:8px;">
                    <?= render_mascot($is_owned ? 'happy' : 'thinking', 75) ?>
                </div>

                <h4 style="font-size:15px; margin-bottom:4px; font-weight:700; color:var(--text-main);">
                    <?= e($c['nama_kartu']) ?>
                </h4>

                <p style="font-size:12px; color:var(--text-muted); line-height:1.4; margin-bottom:12px; flex:1;">
                    <?= e($c['deskripsi_edukatif']) ?>
                </p>

                <?php if ($is_owned): ?>
                    <span class="badge badge-success" style="font-size:11px;">
                        <i class="bx bx-check"></i> Dimiliki (x<?= $qty ?>)
                    </span>
                <?php else: ?>
                    <div class="card-lock-overlay">
                        <i class="bx bxs-lock-alt"></i>
                    </div>
                    <span class="badge badge-gray" style="font-size:11px;">
                        Terkunci
                    </span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
function flipGachaCard(container) {
    const inner = container.querySelector('.card-inner');
    inner.classList.toggle('is-flipped');
}

document.addEventListener('DOMContentLoaded', function() {
    <?php if ($newly_won_card): ?>
        if (typeof confetti === 'function') {
            confetti({
                particleCount: 150,
                spread: 80,
                origin: { y: 0.6 }
            });
        }
    <?php endif; ?>
});
</script>
<?php
}, 'gamifikasi');

