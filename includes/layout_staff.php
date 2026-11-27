<?php
/**
 * DENTARA - Staff Layout Wrapper
 * File: includes/layout_staff.php
 */

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';

function render_staff_layout(string $title, callable $content_callback, string $active_menu = ''): void {
    $user = current_user();
    $role = $user['role'] ?? 'admin';
    $role_name = ucfirst($role);
    $flash = get_flash();
    ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> - DENTARA Staff Portal</title>
    <link rel="icon" type="image/svg+xml" href="<?= base_url('assets/img/favicon.svg') ?>">
    <link rel="manifest" href="<?= base_url('manifest.json') ?>">
    <meta name="theme-color" content="#2BB5A0">

    <!-- Google Fonts & CDN Libraries -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;500;600;700&family=Nunito:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Dentara Core CSS -->
    <link rel="stylesheet" href="<?= base_url('assets/css/design_tokens.css?v=' . (@filemtime(__DIR__ . '/../assets/css/design_tokens.css') ?: '1.2')) ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/components.css?v=' . (@filemtime(__DIR__ . '/../assets/css/components.css') ?: '1.2')) ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/modern_ui.css?v=' . (@filemtime(__DIR__ . '/../assets/css/modern_ui.css') ?: '1.2')) ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/admin_theme.css?v=' . (@filemtime(__DIR__ . '/../assets/css/admin_theme.css') ?: '1.2')) ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/odontogram.css?v=' . (@filemtime(__DIR__ . '/../assets/css/odontogram.css') ?: '1.2')) ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/print.css?v=' . (@filemtime(__DIR__ . '/../assets/css/print.css') ?: '1.2')) ?>">
</head>
<body class="role-<?= e($role) ?>">
    <div id="spa-progress-bar"></div>

    <div class="staff-layout">
        <!-- Overlay Drawer untuk Layar Mobile -->
        <div class="sidebar-overlay" onclick="DENTARA.closeSidebar()"></div>

        <!-- SIDEBAR -->
        <aside class="staff-sidebar">
            <div class="sidebar-header">
                <?= render_logo('sm', true) ?>
                <button class="btn btn-ghost btn-sm d-block d-md-none" onclick="DENTARA.toggleSidebar()" style="display:none;" aria-label="Tutup Menu">
                    <i class="bx bx-x" style="font-size:24px;"></i>
                </button>
            </div>

            <div class="sidebar-menu-wrapper">
                <?php if ($role === 'admin'): ?>
                    <div class="menu-category">Utama</div>
                    <nav class="sidebar-nav">
                        <a href="<?= base_url('admin/dashboard.php') ?>" class="nav-link <?= $active_menu === 'dashboard' ? 'active' : '' ?>">
                            <i class="bx bxs-dashboard"></i> <span>Dashboard Analitik</span>
                        </a>
                        <a href="<?= base_url('admin/data_pasien.php') ?>" class="nav-link <?= $active_menu === 'data_pasien' ? 'active' : '' ?>">
                            <i class="bx bxs-user-detail"></i> <span>Data Pasien</span>
                        </a>
                    </nav>

                    <div class="menu-category">Klinik & Medis</div>
                    <nav class="sidebar-nav">
                        <a href="<?= base_url('admin/kelola_dokter.php') ?>" class="nav-link <?= $active_menu === 'kelola_dokter' ? 'active' : '' ?>">
                            <i class="bx bxs-user-badge"></i> <span>Kelola Dokter</span>
                        </a>
                        <a href="<?= base_url('admin/jadwal_dokter.php') ?>" class="nav-link <?= $active_menu === 'jadwal_dokter' ? 'active' : '' ?>">
                            <i class="bx bxs-calendar-check"></i> <span>Jadwal & Cuti Dokter</span>
                        </a>
                        <a href="<?= base_url('admin/master_layanan.php') ?>" class="nav-link <?= $active_menu === 'master_layanan' ? 'active' : '' ?>">
                            <i class="bx bxs-first-aid"></i> <span>Master Layanan & Tarif</span>
                        </a>
                        <a href="<?= base_url('admin/jadwal_klinik.php') ?>" class="nav-link <?= $active_menu === 'jadwal_klinik' ? 'active' : '' ?>">
                            <i class="bx bxs-time-five"></i> <span>Jam Operasional & Libur</span>
                        </a>
                    </nav>

                    <div class="menu-category">Keuangan & Analitik</div>
                    <nav class="sidebar-nav">
                        <a href="<?= base_url('admin/laporan_pendapatan.php') ?>" class="nav-link <?= $active_menu === 'laporan_pendapatan' ? 'active' : '' ?>">
                            <i class="bx bxs-report"></i> <span>Laporan Pendapatan</span>
                        </a>
                        <a href="<?= base_url('admin/fee_sharing.php') ?>" class="nav-link <?= $active_menu === 'fee_sharing' ? 'active' : '' ?>">
                            <i class="bx bxs-wallet"></i> <span>Fee Sharing Dokter</span>
                        </a>
                        <a href="<?= base_url('admin/sla_analytics.php') ?>" class="nav-link <?= $active_menu === 'sla_analytics' ? 'active' : '' ?>">
                            <i class="bx bx-timer"></i> <span>SLA Waktu Layanan</span>
                        </a>
                    </nav>

                    <div class="menu-category">Konten & Pasien</div>
                    <nav class="sidebar-nav">
                        <a href="<?= base_url('admin/kelola_artikel.php') ?>" class="nav-link <?= $active_menu === 'artikel' ? 'active' : '' ?>">
                            <i class="bx bxs-news"></i> <span>Edukasi & Artikel</span>
                        </a>
                        <a href="<?= base_url('admin/kelola_faq.php') ?>" class="nav-link <?= $active_menu === 'faq' ? 'active' : '' ?>">
                            <i class="bx bxs-help-circle"></i> <span>Kelola FAQ</span>
                        </a>
                        <a href="<?= base_url('admin/kelola_promo.php') ?>" class="nav-link <?= $active_menu === 'promo' ? 'active' : '' ?>">
                            <i class="bx bxs-purchase-tag"></i> <span>Banner Promo</span>
                        </a>
                        <a href="<?= base_url('admin/kelola_kartu.php') ?>" class="nav-link <?= $active_menu === 'kartu' ? 'active' : '' ?>">
                            <i class="bx bxs-trophy"></i> <span>Kartu Gamifikasi</span>
                        </a>
                        <a href="<?= base_url('admin/kirim_notifikasi.php') ?>" class="nav-link <?= $active_menu === 'notifikasi' ? 'active' : '' ?>">
                            <i class="bx bxs-bell-ring"></i> <span>Blast Notifikasi</span>
                        </a>
                    </nav>

                    <div class="menu-category">Sistem & Pengaturan</div>
                    <nav class="sidebar-nav">
                        <a href="<?= base_url('admin/kelola_pengguna.php') ?>" class="nav-link <?= $active_menu === 'pengguna' ? 'active' : '' ?>">
                            <i class="bx bxs-user-account"></i> <span>Manajemen Staff</span>
                        </a>
                        <a href="<?= base_url('admin/pengaturan_klinik.php') ?>" class="nav-link <?= $active_menu === 'pengaturan' ? 'active' : '' ?>">
                            <i class="bx bxs-cog"></i> <span>Pengaturan Klinik</span>
                        </a>
                        <a href="<?= base_url('admin/log_aktivitas.php') ?>" class="nav-link <?= $active_menu === 'log' ? 'active' : '' ?>">
                            <i class="bx bx-history"></i> <span>Log Aktivitas</span>
                        </a>
                        <a href="<?= base_url('styleguide.php') ?>" class="nav-link <?= $active_menu === 'styleguide' ? 'active' : '' ?>">
                            <i class="bx bxs-palette"></i> <span>Design Styleguide</span>
                        </a>
                    </nav>

                <?php elseif ($role === 'dokter'): ?>
                    <div class="menu-category">Pelayanan Pasien</div>
                    <nav class="sidebar-nav">
                        <a href="<?= base_url('dokter/panel_antrian.php') ?>" class="nav-link <?= $active_menu === 'panel_antrian' ? 'active' : '' ?>">
                            <i class="bx bxs-user-voice"></i> <span>Antrean Ruangan</span>
                        </a>
                        <a href="<?= base_url('dokter/pemeriksaan.php') ?>" class="nav-link <?= $active_menu === 'pemeriksaan' ? 'active' : '' ?>">
                            <i class="bx bxs-smile"></i> <span>Pemeriksaan & Odontogram</span>
                        </a>
                        <a href="<?= base_url('dokter/riwayat_pasien.php') ?>" class="nav-link <?= $active_menu === 'riwayat_pasien' ? 'active' : '' ?>">
                            <i class="bx bxs-folder-open"></i> <span>Riwayat Rekam Medis</span>
                        </a>
                        <a href="<?= base_url('dokter/cetak_rekam.php') ?>" class="nav-link <?= $active_menu === 'cetak_rekam' ? 'active' : '' ?>">
                            <i class="bx bxs-printer"></i> <span>Cetak Resume Medis</span>
                        </a>
                    </nav>

                <?php elseif ($role === 'resepsionis'): ?>
                    <div class="menu-category">Antrean & Tamu</div>
                    <nav class="sidebar-nav">
                        <a href="<?= base_url('resepsionis/dashboard_antrian.php') ?>" class="nav-link <?= $active_menu === 'antrian' ? 'active' : '' ?>">
                            <i class="bx bxs-dashboard"></i> <span>Dashboard Antrean</span>
                        </a>
                        <a href="<?= base_url('resepsionis/checkin.php') ?>" class="nav-link <?= $active_menu === 'checkin' ? 'active' : '' ?>">
                            <i class="bx bx-qr-scan"></i> <span>Check-In & Scan QR</span>
                        </a>
                        <a href="<?= base_url('resepsionis/konfirmasi_reservasi.php') ?>" class="nav-link <?= $active_menu === 'konfirmasi' ? 'active' : '' ?>">
                            <i class="bx bxs-calendar-check"></i> <span>Konfirmasi Reservasi</span>
                        </a>
                        <a href="<?= base_url('resepsionis/walkin.php') ?>" class="nav-link <?= $active_menu === 'walkin' ? 'active' : '' ?>">
                            <i class="bx bxs-user-plus"></i> <span>Pendaftaran Walk-In</span>
                        </a>
                        <a href="<?= base_url('resepsionis/prioritas_antrian.php') ?>" class="nav-link <?= $active_menu === 'prioritas' ? 'active' : '' ?>">
                            <i class="bx bxs-traffic-barrier"></i> <span>Prioritas Antrean</span>
                        </a>
                        <a href="<?= base_url('resepsionis/data_pasien.php') ?>" class="nav-link <?= $active_menu === 'pasien' ? 'active' : '' ?>">
                            <i class="bx bxs-id-card"></i> <span>Data Pasien & RM</span>
                        </a>
                        <a href="<?= base_url('tv/monitor.php') ?>" target="_blank" class="nav-link no-spa">
                            <i class="bx bx-tv"></i> <span>Buka Layar TV Ruang Tunggu</span>
                        </a>
                    </nav>

                <?php elseif ($role === 'apoteker'): ?>
                    <div class="menu-category">Farmasi & Obat</div>
                    <nav class="sidebar-nav">
                        <a href="<?= base_url('apoteker/antrean_resep.php') ?>" class="nav-link <?= $active_menu === 'antrean_resep' ? 'active' : '' ?>">
                            <i class="bx bxs-capsule"></i> <span>Antrean Resep</span>
                        </a>
                        <a href="<?= base_url('apoteker/stok_obat.php') ?>" class="nav-link <?= $active_menu === 'stok_obat' ? 'active' : '' ?>">
                            <i class="bx bxs-box"></i> <span>Stok Obat</span>
                        </a>
                        <a href="<?= base_url('apoteker/stok_supply.php') ?>" class="nav-link <?= $active_menu === 'stok_supply' ? 'active' : '' ?>">
                            <i class="bx bxs-shield"></i> <span>Dental Supply</span>
                        </a>
                        <a href="<?= base_url('apoteker/alert_stok.php') ?>" class="nav-link <?= $active_menu === 'alert_stok' ? 'active' : '' ?>">
                            <i class="bx bxs-error-alt"></i> <span>Peringatan Stok & Expired</span>
                        </a>
                        <a href="<?= base_url('apoteker/stock_opname.php') ?>" class="nav-link <?= $active_menu === 'stock_opname' ? 'active' : '' ?>">
                            <i class="bx bx-clipboard"></i> <span>Stock Opname</span>
                        </a>
                    </nav>

                <?php elseif ($role === 'kasir'): ?>
                    <div class="menu-category">Kasir & Pembayaran</div>
                    <nav class="sidebar-nav">
                        <a href="<?= base_url('kasir/dashboard_tagihan.php') ?>" class="nav-link <?= $active_menu === 'dashboard_tagihan' ? 'active' : '' ?>">
                            <i class="bx bxs-receipt"></i> <span>Tagihan Pasien</span>
                        </a>
                        <a href="<?= base_url('kasir/dashboard_tagihan.php?status=lunas') ?>" class="nav-link <?= $active_menu === 'riwayat_lunas' ? 'active' : '' ?>">
                            <i class="bx bxs-check-shield"></i> <span>Riwayat Kuitansi Lunas</span>
                        </a>
                        <a href="<?= base_url('kasir/rekap_shift.php') ?>" class="nav-link <?= $active_menu === 'rekap_shift' ? 'active' : '' ?>">
                            <i class="bx bxs-bank"></i> <span>Shift & Kas</span>
                        </a>
                    </nav>
                <?php endif; ?>
            </div>

            <div class="sidebar-footer">
                <a href="<?= base_url('includes/process/logout.php') ?>" class="btn btn-secondary btn-sm w-100 no-spa">
                    <i class="bx bx-log-out"></i> Keluar Sistem
                </a>
            </div>
        </aside>

        <!-- MAIN SECTION -->
        <main class="staff-main">
            <!-- TOPBAR -->
            <header class="staff-topbar">
                <div class="topbar-left">
                    <button class="btn btn-ghost btn-sm d-md-none" onclick="DENTARA.toggleSidebar()" aria-label="Buka Menu">
                        <i class="bx bx-menu" style="font-size:24px;"></i>
                    </button>
                    <div>
                        <h2 style="font-size:var(--text-lg); margin:0;"><?= e($title) ?></h2>
                        <span style="font-size:var(--text-xs); color:var(--text-muted);"><?= tanggal_indo(date('Y-m-d'), true) ?></span>
                    </div>
                </div>

                <div class="topbar-right">
                    <!-- Dark Mode Toggle Button -->
                    <button class="btn btn-ghost btn-icon theme-toggle-btn" onclick="DENTARA.toggleDarkMode()" title="Ganti Mode Gelap / Terang">
                        <i class="bx bx-moon"></i>
                    </button>

                    <!-- Staff User Profile & Quick Logout -->
                    <div class="user-profile-btn">
                        <div class="user-avatar"><?= strtoupper(substr($user['nama_lengkap'] ?? 'S', 0, 1)) ?></div>
                        <div class="d-none d-sm-block text-left">
                            <div style="font-size:var(--text-sm); font-weight:700; line-height:1.2;"><?= e($user['nama_lengkap'] ?? 'Staff') ?></div>
                            <span class="badge badge-mint" style="padding:2px 8px; font-size:10px;"><?= e($role_name) ?></span>
                        </div>
                    </div>

                    <a href="<?= base_url('includes/process/logout.php') ?>" class="btn btn-ghost btn-sm text-danger no-spa" title="Keluar / Ganti Akun" style="font-size:12px; font-weight:700; padding:6px 10px; border-radius:8px;">
                        <i class="bx bx-log-out" style="font-size:17px; vertical-align:middle;"></i> <span class="d-none d-md-inline">Keluar</span>
                    </a>
                </div>
            </header>

            <!-- CONTENT AREA (Interchangeable by SPA) -->
            <div id="content-area" class="content-wrapper fade-in">
                <?php $content_callback(); ?>
            </div>
        </main>
    </div>

    <!-- Inject Global Flash Script if any -->
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

