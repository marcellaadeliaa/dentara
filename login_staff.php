<?php
/**
 * DENTARA - Staff Login Page
 * File: login_staff.php
 */

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

// Jika sudah login staff, redirect langsung
if (!empty($_SESSION['id_pengguna']) && !empty($_SESSION['role'])) {
    header('Location: ' . get_dashboard_redirect($_SESSION['role']));
    exit;
}

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Staff & Dokter - DENTARA</title>
    <link rel="icon" type="image/svg+xml" href="<?= base_url('assets/img/favicon.svg') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <link rel="stylesheet" href="<?= base_url('assets/css/design_tokens.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/components.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/modern_ui.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/login.css') ?>">
</head>
<body class="theme-staff">
    <div class="auth-wrapper">
        <!-- Background decorative blobs -->
        <div class="auth-bg-blob blob-1"></div>
        <div class="auth-bg-blob blob-2"></div>

        <div class="auth-card-2col fade-in">
            <!-- Left Illustrative Panel -->
            <div class="auth-left-panel">
                <div>
                    <a href="<?= base_url('index.php') ?>" style="text-decoration:none;">
                        <?= render_logo('md', true, 'white') ?>
                    </a>
                    <div class="auth-slogan mt-4">
                        <h2>Sistem Informasi Manajemen Terpadu</h2>
                        <p style="font-size:14px; opacity:0.85; margin-top:8px;">
                            Portal internal untuk dokter, resepsionis, perawat, apoteker, kasir, dan pengelola klinik.
                        </p>
                    </div>
                </div>

                <div class="auth-mascot-box">
                    <?= render_mascot('happy', 140) ?>
                </div>

                <div class="auth-trust-note">
                    <i class="bx bxs-shield-check" style="font-size:20px;"></i>
                    <span>Terkoneksi aman dengan enkripsi password & prepared statement PDO.</span>
                </div>
            </div>

            <!-- Right Form Panel -->
            <div class="auth-right-panel">
                <div class="auth-header">
                    <h1>Masuk Portal Staff</h1>
                    <p class="text-muted" style="font-size:var(--text-sm);">Gunakan kredensial akun staff atau dokter Anda.</p>
                </div>

                <form action="<?= base_url('includes/process/login_staff_process.php') ?>" method="POST">
                    <?= csrf_field() ?>

                    <div class="form-group">
                        <label class="form-label required">Nama Pengguna (Username)</label>
                        <div class="input-icon-group">
                            <i class="bx bx-user"></i>
                            <input type="text" name="username" class="form-control" placeholder="Contoh: admin / drg_budi / resepsionis" required autofocus>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label required">Kata Sandi (Password)</label>
                        <div class="input-icon-group">
                            <i class="bx bx-lock-alt"></i>
                            <input type="password" id="staff-password" name="password" class="form-control" placeholder="Masukkan password Anda" required>
                            <i class="bx bx-show icon-right" onclick="togglePasswordVisibility('staff-password', this)"></i>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100 mt-4">
                        <i class="bx bx-log-in"></i> Masuk Sekarang
                    </button>
                </form>

                <!-- Demo Credentials Helper -->
                <div style="background:var(--surface-subtle); padding:14px; border-radius:var(--radius-md); margin-top:20px; font-size:12px; border:1px solid var(--border-color);">
                    <div style="font-weight:700; color:var(--text-main); margin-bottom:4px;">
                        <i class="bx bx-key text-primary"></i> Akun Uji Coba Cepat (Password: <code>password123</code>):
                    </div>
                    <div style="color:var(--text-muted); display:flex; flex-wrap:wrap; gap:6px;">
                        <span class="badge badge-purple" onclick="fillStaff('admin')" style="cursor:pointer;" title="Klik untuk mengisi">admin</span>
                        <span class="badge badge-mint" onclick="fillStaff('drg_budi')" style="cursor:pointer;" title="Klik untuk mengisi">drg_budi (Sp.KG)</span>
                        <span class="badge badge-info" onclick="fillStaff('resepsionis')" style="cursor:pointer;" title="Klik untuk mengisi">resepsionis</span>
                        <span class="badge badge-warning" onclick="fillStaff('apoteker')" style="cursor:pointer;" title="Klik untuk mengisi">apoteker</span>
                        <span class="badge badge-success" onclick="fillStaff('kasir')" style="cursor:pointer;" title="Klik untuk mengisi">kasir</span>
                    </div>
                </div>

                <div class="auth-footer">
                    Pasien yang ingin booking & melihat rekam medis? 
                    <a href="<?= base_url('login_pasien.php') ?>" class="text-primary font-bold">Masuk Portal Pasien</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        <?php if ($flash): ?>
        window.dentaraFlash = <?= json_encode($flash) ?>;
        <?php endif; ?>

        function togglePasswordVisibility(inputId, iconEl) {
            const input = document.getElementById(inputId);
            if (input.type === 'password') {
                input.type = 'text';
                iconEl.className = 'bx bx-hide icon-right';
            } else {
                input.type = 'password';
                iconEl.className = 'bx bx-show icon-right';
            }
        }

        function fillStaff(user) {
            document.querySelector('input[name="username"]').value = user;
            document.getElementById('staff-password').value = 'password123';
        }
    </script>
    <script src="<?= base_url('assets/js/global.js') ?>"></script>
</body>
</html>

