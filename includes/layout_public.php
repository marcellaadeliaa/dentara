<?php
/**
 * DENTARA - Public Website Layout Wrapper
 * File: includes/layout_public.php
 */

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db_connect.php';

function render_public_layout(string $title, callable $content_callback, string $active_page = ''): void {
    global $pdo;
    $flash = get_flash();

    // Ambil info klinik untuk footer
    try {
        $stmt_k = $pdo->query("SELECT * FROM pengaturan_klinik LIMIT 1");
        $klinik = $stmt_k->fetch() ?: [
            'nama_klinik' => 'DENTARA Dental Care & Clinic',
            'telepon' => '022-87654321',
            'whatsapp' => '6281234567890',
            'email' => 'info@dentara.id',
            'alamat' => 'Jl. Ir. H. Juanda No. 128, Dago, Bandung, Jawa Barat 40135'
        ];
    } catch (Exception $e) {
        $klinik = [
            'nama_klinik' => 'DENTARA Dental Care & Clinic',
            'telepon' => '022-87654321',
            'whatsapp' => '6281234567890',
            'email' => 'info@dentara.id',
            'alamat' => 'Jl. Ir. H. Juanda No. 128, Dago, Bandung, Jawa Barat 40135'
        ];
    }
    ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> - DENTARA Klinik Gigi</title>
    <link rel="icon" type="image/svg+xml" href="<?= base_url('assets/img/favicon.svg') ?>">
    <link rel="manifest" href="<?= base_url('manifest.json') ?>">
    <meta name="theme-color" content="#2BB5A0">
    <meta name="description" content="DENTARA - Sistem Informasi Manajemen Klinik Gigi Modern & Ramah Keluarga. Booking online praktis, live antrean, odontogram digital, dan dokter spesialis terpercaya.">

    <!-- Fonts & CDNs -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;500;600;700&family=Nunito:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Dentara CSS -->
    <link rel="stylesheet" href="<?= base_url('assets/css/design_tokens.css?v=' . (@filemtime(__DIR__ . '/../assets/css/design_tokens.css') ?: '1.2')) ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/components.css?v=' . (@filemtime(__DIR__ . '/../assets/css/components.css') ?: '1.2')) ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/modern_ui.css?v=' . (@filemtime(__DIR__ . '/../assets/css/modern_ui.css') ?: '1.2')) ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/landing.css?v=' . (@filemtime(__DIR__ . '/../assets/css/landing.css') ?: '1.2')) ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/print.css?v=' . (@filemtime(__DIR__ . '/../assets/css/print.css') ?: '1.2')) ?>">
</head>
<body>
    <div id="spa-progress-bar"></div>

    <!-- PUBLIC STICKY GLASS NAVBAR -->
    <header class="public-navbar">
        <div class="container navbar-container">
            <a href="<?= base_url('index.php') ?>">
                <?= render_logo('md', true) ?>
            </a>

            <!-- Nav Links -->
            <nav class="nav-menu">
                <a href="<?= base_url('index.php') ?>" class="nav-link-public <?= $active_page === 'beranda' ? 'text-primary font-bold' : '' ?>">Beranda</a>
                <a href="<?= base_url('layanan.php') ?>" class="nav-link-public <?= $active_page === 'layanan' ? 'text-primary font-bold' : '' ?>">Layanan & Tarif</a>
                <a href="<?= base_url('dokter.php') ?>" class="nav-link-public <?= $active_page === 'dokter' ? 'text-primary font-bold' : '' ?>">Dokter & Jadwal</a>
                <a href="<?= base_url('tracker.php') ?>" class="nav-link-public <?= $active_page === 'tracker' ? 'text-primary font-bold' : '' ?>">Live Antrean</a>
                <a href="<?= base_url('artikel.php') ?>" class="nav-link-public <?= $active_page === 'artikel' ? 'text-primary font-bold' : '' ?>">Edukasi Gigi</a>
                <a href="<?= base_url('faq.php') ?>" class="nav-link-public <?= $active_page === 'faq' ? 'text-primary font-bold' : '' ?>">FAQ</a>
            </nav>

            <!-- Actions -->
            <div class="d-flex align-items-center gap-3">
                <!-- Dark Mode Toggle -->
                <button class="btn btn-ghost btn-icon theme-toggle-btn" onclick="DENTARA.toggleDarkMode()" title="Ganti Mode Gelap / Terang">
                    <i class="bx bx-moon"></i>
                </button>

                <?php if (!empty($_SESSION['id_pengguna']) && !empty($_SESSION['role'])): ?>
                    <a href="<?= get_dashboard_redirect($_SESSION['role']) ?>" class="btn btn-primary btn-sm" style="font-weight:750; box-shadow:0 2px 8px rgba(43,181,160,0.3);">
                        <i class="bx bxs-dashboard"></i> Portal <?= ucfirst($_SESSION['role']) ?>
                    </a>
                <?php else: ?>
                    <a href="<?= base_url('login_pasien.php') ?>" class="btn btn-secondary btn-sm">
                        <i class="bx bxs-user"></i> Masuk Pasien
                    </a>
                <?php endif; ?>

                <a href="<?= base_url('booking.php') ?>" class="btn btn-primary btn-sm">
                    <i class="bx bx-calendar-plus"></i> Booking Janji
                </a>
            </div>
        </div>
    </header>

    <!-- CONTENT AREA -->
    <main id="content-area" class="fade-in">
        <?php $content_callback(); ?>
    </main>

    <!-- FLOATING WHATSAPP BUTTON -->
    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $klinik['whatsapp']) ?>?text=Halo%20DENTARA,%20saya%20ingin%20bertanya%20mengenai%20layanan%20klinik" 
       target="_blank" class="float-wa no-spa" aria-label="Chat WhatsApp">
        <i class="fa-brands fa-whatsapp"></i>
    </a>

    <!-- PUBLIC FOOTER -->
    <footer style="background:var(--surface); border-top:1px solid var(--border-color); padding:60px 0 24px; margin-top:80px;">
        <div class="container">
            <div class="grid grid-cols-4 gap-6 mb-6">
                <!-- Col 1: Brand & Slogan -->
                <div style="grid-column: span 1.3;">
                    <?= render_logo('md', true) ?>
                    <p class="text-muted mt-3" style="font-size:var(--text-sm); line-height:1.6;">
                        "Senyum Sehat, Hidup Hebat!" Klinik gigi ramah keluarga dengan pelayanan medis terpadu, standar sterilisasi modern, dan dokter gigi spesialis terpercaya.
                    </p>
                    <div class="d-flex gap-2 mt-4">
                        <a href="#" class="btn btn-secondary btn-icon btn-sm" aria-label="Instagram"><i class="bx bxl-instagram"></i></a>
                        <a href="#" class="btn btn-secondary btn-icon btn-sm" aria-label="Facebook"><i class="bx bxl-facebook"></i></a>
                        <a href="#" class="btn btn-secondary btn-icon btn-sm" aria-label="Youtube"><i class="bx bxl-youtube"></i></a>
                    </div>
                </div>

                <!-- Col 2: Navigasi Cepat -->
                <div>
                    <h4 style="font-size:var(--text-md); margin-bottom:16px;">Tautan Utama</h4>
                    <ul style="display:flex; flex-direction:column; gap:8px; font-size:var(--text-sm);">
                        <li><a href="<?= base_url('booking.php') ?>" class="text-muted">Booking Online</a></li>
                        <li><a href="<?= base_url('tracker.php') ?>" class="text-muted">Pantau Live Antrean</a></li>
                        <li><a href="<?= base_url('layanan.php') ?>" class="text-muted">Daftar Biaya & Tarif</a></li>
                        <li><a href="<?= base_url('dokter.php') ?>" class="text-muted">Jadwal Praktik Dokter</a></li>
                        <li><a href="<?= base_url('artikel.php') ?>" class="text-muted">Tips & Edukasi Gigi</a></li>
                    </ul>
                </div>

                <!-- Col 3: Jam Operasional -->
                <div>
                    <h4 style="font-size:var(--text-md); margin-bottom:16px;">Jam Operasional</h4>
                    <p style="font-size:var(--text-sm); color:var(--text-muted); line-height:1.6;">
                        <strong>Senin - Jumat:</strong><br>
                        Sesi 1: 08.00 - 12.00 WIB<br>
                        Sesi 2: 14.00 - 21.00 WIB<br>
                        <strong>Sabtu:</strong> 08.00 - 20.00 WIB<br>
                        <strong>Minggu:</strong> 09.00 - 15.00 WIB
                    </p>
                </div>

                <!-- Col 4: Kontak & Alamat -->
                <div>
                    <h4 style="font-size:var(--text-md); margin-bottom:16px;">Hubungi Kami</h4>
                    <p style="font-size:var(--text-sm); color:var(--text-muted); line-height:1.6;">
                        <i class="bx bx-map text-primary"></i> <?= e($klinik['alamat']) ?><br>
                        <i class="bx bx-phone text-primary mt-2"></i> <?= e($klinik['telepon']) ?><br>
                        <i class="bx bxl-whatsapp text-success mt-2"></i> <?= e($klinik['whatsapp']) ?><br>
                        <i class="bx bx-envelope text-primary mt-2"></i> <?= e($klinik['email']) ?>
                    </p>
                    <div class="mt-3">
                        <a href="<?= base_url('login_staff.php') ?>" class="text-muted" style="font-size:var(--text-xs);">
                            <i class="bx bx-lock-alt"></i> Portal Khusus Staff Klinik
                        </a>
                    </div>
                </div>
            </div>

            <div style="border-top:1px solid var(--border-color); padding-top:24px; text-align:center; font-size:var(--text-xs); color:var(--text-muted);">
                &copy; <?= date('Y') ?> DENTARA - Sistem Informasi Manajemen Klinik Gigi Berbasis Web. All Rights Reserved.
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script>
        <?php if ($flash): ?>
        window.dentaraFlash = <?= json_encode($flash) ?>;
        <?php endif; ?>

        // Scroll listener for glass navbar
        window.addEventListener('scroll', function() {
            const nav = document.querySelector('.public-navbar');
            if (nav) {
                if (window.scrollY > 20) {
                    nav.classList.add('scrolled');
                } else {
                    nav.classList.remove('scrolled');
                }
            }
        });
    </script>
    <script src="<?= base_url('assets/js/global.js') ?>"></script>
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('<?= base_url('sw.js') ?>').catch(err => console.log('SW Fail', err));
        }
    </script>
</body>
</html>
    <?php
}

