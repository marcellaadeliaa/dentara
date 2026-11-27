<?php
/**
 * DENTARA - Patient Portal Layout Wrapper
 * File: includes/layout_pasien.php
 */

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';

function render_patient_layout(string $title, callable $content_callback, string $active_menu = ''): void {
    $patient_acc = current_patient_account();
    $flash = get_flash();
    
    // Ambil daftar profil keluarga dari akun pasien ini
    global $pdo;
    $profil_list = [];
    $active_patient = null;
    if ($patient_acc && !empty($patient_acc['id_akun'])) {
        $stmt = $pdo->prepare("SELECT id_pasien, no_rm, nama_pasien, hubungan_keluarga, tgl_lahir FROM pasien WHERE id_akun = ? ORDER BY id_pasien ASC");
        $stmt->execute([(int)$patient_acc['id_akun']]);
        $profil_list = $stmt->fetchAll();

        // Tentukan profil aktif
        $active_id = $_SESSION['id_pasien_aktif'] ?? ($profil_list[0]['id_pasien'] ?? null);
        foreach ($profil_list as $p) {
            if ($p['id_pasien'] == $active_id) {
                $active_patient = $p;
                break;
            }
        }
        if (!$active_patient && !empty($profil_list)) {
            $active_patient = $profil_list[0];
            $_SESSION['id_pasien_aktif'] = $active_patient['id_pasien'];
        }
    }

    // Hitung pesan notifikasi belum dibaca
    $unread_count = 0;
    if ($active_patient) {
        $stmt_msg = $pdo->prepare("SELECT COUNT(*) FROM pesan_notifikasi WHERE id_pasien = ? AND status = 'belum_dibaca'");
        $stmt_msg->execute([(int)$active_patient['id_pasien']]);
        $unread_count = (int)$stmt_msg->fetchColumn();
    }
    ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> - Portal Pasien DENTARA</title>
    <link rel="icon" type="image/svg+xml" href="<?= base_url('assets/img/favicon.svg') ?>">
    <link rel="manifest" href="<?= base_url('manifest.json') ?>">
    <meta name="theme-color" content="#2BB5A0">

    <!-- Fonts & CDNs -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;500;600;700&family=Nunito:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>

    <!-- Dentara CSS -->
    <link rel="stylesheet" href="<?= base_url('assets/css/design_tokens.css?v=' . (@filemtime(__DIR__ . '/../assets/css/design_tokens.css') ?: '1.2')) ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/components.css?v=' . (@filemtime(__DIR__ . '/../assets/css/components.css') ?: '1.2')) ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/modern_ui.css?v=' . (@filemtime(__DIR__ . '/../assets/css/modern_ui.css') ?: '1.2')) ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/pasien_theme.css?v=' . (@filemtime(__DIR__ . '/../assets/css/pasien_theme.css') ?: '1.2')) ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/gamifikasi.css?v=' . (@filemtime(__DIR__ . '/../assets/css/gamifikasi.css') ?: '1.2')) ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/odontogram.css?v=' . (@filemtime(__DIR__ . '/../assets/css/odontogram.css') ?: '1.2')) ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/print.css?v=' . (@filemtime(__DIR__ . '/../assets/css/print.css') ?: '1.2')) ?>">
</head>
<body class="patient-layout">
    <div id="spa-progress-bar"></div>

    <!-- PATIENT TOP NAVBAR -->
    <header class="patient-navbar">
        <div class="container patient-nav-inner">
            <div class="d-flex align-items-center gap-4">
                <a href="<?= base_url('pasien/dashboard.php') ?>">
                    <?= render_logo('sm', true) ?>
                </a>
            </div>

            <!-- Desktop Menu -->
            <nav class="patient-desktop-menu">
                <a href="<?= base_url('pasien/dashboard.php') ?>" class="patient-nav-item <?= $active_menu === 'dashboard' ? 'active' : '' ?>">
                    <i class="bx bxs-home-smile"></i> <span>Beranda</span>
                </a>
                <a href="<?= base_url('pasien/janji_temu.php') ?>" class="patient-nav-item <?= $active_menu === 'janji_temu' ? 'active' : '' ?>">
                    <i class="bx bxs-calendar-event"></i> <span>Janji Temu</span>
                </a>
                <a href="<?= base_url('pasien/rekam_medis.php') ?>" class="patient-nav-item <?= $active_menu === 'rekam_medis' ? 'active' : '' ?>">
                    <i class="bx bxs-file-plus"></i> <span>Rekam Medis</span>
                </a>
                <a href="<?= base_url('pasien/tagihan.php') ?>" class="patient-nav-item <?= $active_menu === 'tagihan' ? 'active' : '' ?>">
                    <i class="bx bxs-receipt"></i> <span>Tagihan</span>
                </a>
                <a href="<?= base_url('pasien/gamifikasi.php') ?>" class="patient-nav-item <?= $active_menu === 'gamifikasi' ? 'active' : '' ?>">
                    <i class="bx bxs-trophy"></i> <span>Kartu Denta</span>
                </a>
                <a href="<?= base_url('pasien/pesan.php') ?>" class="patient-nav-item <?= $active_menu === 'pesan' ? 'active' : '' ?>" style="position:relative;">
                    <i class="bx bxs-bell"></i> <span>Pesan</span>
                    <?php if ($unread_count > 0): ?>
                        <span class="badge badge-danger" style="font-size:10px; padding:1px 5px;"><?= $unread_count ?></span>
                    <?php endif; ?>
                </a>
            </nav>

            <!-- Profil Switcher & User Action -->
            <div class="d-flex align-items-center gap-3">
                <!-- Dark Mode Toggle -->
                <button class="btn btn-ghost btn-icon theme-toggle-btn" onclick="DENTARA.toggleDarkMode()" title="Ganti Mode Gelap / Terang">
                    <i class="bx bx-moon"></i>
                </button>

                <!-- Dropdown Profil Pasien Aktif -->
                <?php if ($active_patient): ?>
                <div class="d-flex align-items-center gap-2">
                    <a href="<?= base_url('pasien/keluarga.php') ?>" class="btn btn-secondary btn-sm" title="Ganti Profil Anggota Keluarga">
                        <i class="bx bxs-user-circle"></i>
                        <span><?= e($active_patient['nama_pasien']) ?></span>
                        <span class="badge badge-mint"><?= e($active_patient['hubungan_keluarga']) ?></span>
                    </a>
                </div>
                <?php endif; ?>

                <a href="<?= base_url('includes/process/logout.php') ?>" class="btn btn-ghost btn-sm no-spa" title="Keluar">
                    <i class="bx bx-log-out" style="font-size:18px;"></i>
                </a>
            </div>
        </div>
    </header>

    <!-- MAIN CONTENT (Swapped via SPA) -->
    <main id="content-area" class="container mt-5 fade-in" style="flex:1;">
        <?php $content_callback(); ?>
    </main>

    <!-- MOBILE BOTTOM NAVIGATION (< 768px) -->
    <nav class="bottom-nav">
        <a href="<?= base_url('pasien/dashboard.php') ?>" class="bottom-nav-link <?= $active_menu === 'dashboard' ? 'active' : '' ?>">
            <i class="bx bxs-home-smile"></i>
            <span>Beranda</span>
        </a>
        <a href="<?= base_url('pasien/janji_temu.php') ?>" class="bottom-nav-link <?= $active_menu === 'janji_temu' ? 'active' : '' ?>">
            <i class="bx bxs-calendar-plus"></i>
            <span>Janji</span>
        </a>
        <a href="<?= base_url('pasien/gamifikasi.php') ?>" class="bottom-nav-link <?= $active_menu === 'gamifikasi' ? 'active' : '' ?>">
            <i class="bx bxs-trophy"></i>
            <span>Kartu</span>
        </a>
        <a href="<?= base_url('pasien/pesan.php') ?>" class="bottom-nav-link <?= $active_menu === 'pesan' ? 'active' : '' ?>">
            <i class="bx bxs-bell"></i>
            <span>Pesan</span>
            <?php if ($unread_count > 0): ?>
                <span class="nav-badge"><?= $unread_count ?></span>
            <?php endif; ?>
        </a>
        <a href="<?= base_url('pasien/setting.php') ?>" class="bottom-nav-link <?= $active_menu === 'setting' ? 'active' : '' ?>">
            <i class="bx bxs-cog"></i>
            <span>Akun</span>
        </a>
    </nav>

    <!-- Scripts -->
    <script>
        <?php if ($flash): ?>
        window.dentaraFlash = <?= json_encode($flash) ?>;
        <?php endif; ?>
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

