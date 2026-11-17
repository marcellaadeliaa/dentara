<?php
/**
 * DENTARA - Patient Login Page
 * File: login_pasien.php
 */

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

// Jika sudah login pasien, redirect langsung
if (!empty($_SESSION['id_akun_pasien'])) {
    header('Location: ' . base_url('pasien/dashboard.php'));
    exit;
}

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk Akun Pasien - DENTARA</title>
    <link rel="icon" type="image/svg+xml" href="<?= base_url('assets/img/favicon.svg') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <link rel="stylesheet" href="<?= base_url('assets/css/design_tokens.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/components.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/modern_ui.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/login.css') ?>">
</head>
<body class="theme-patient">
    <div class="auth-wrapper">
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
                        <h2>Senyum Sehat Keluarga Anda</h2>
                        <p style="font-size:14px; opacity:0.9; margin-top:8px;">
                            Pantau riwayat rekam medis, kelola jadwal janji temu keluarga, dan kumpulkan Kartu Denta seru untuk anak!
                        </p>
                    </div>
                </div>

                <div class="auth-mascot-box">
                    <?= render_mascot('happy', 140) ?>
                </div>

                <div class="auth-trust-note">
                    <i class="bx bxs-heart" style="font-size:20px; color:#FFD166;"></i>
                    <span>Pelayanan klinik gigi ramah anak & keluarga bebas rasa cemas.</span>
                </div>
            </div>

            <!-- Right Form Panel -->
            <div class="auth-right-panel">
                <div class="auth-header">
                    <h1>Masuk Akun Pasien</h1>
                    <p class="text-muted" style="font-size:var(--text-sm);">Kelola janji temu dan odontogram digital Anda.</p>
                </div>

                <form action="<?= base_url('includes/process/login_pasien_process.php') ?>" method="POST">
                    <?= csrf_field() ?>

                    <div class="form-group">
                        <label class="form-label required">Alamat Email</label>
                        <div class="input-icon-group">
                            <i class="bx bx-envelope"></i>
                            <input type="email" name="email" class="form-control" placeholder="nama@email.com" required autofocus>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label required">Kata Sandi</label>
                        <div class="input-icon-group">
                            <i class="bx bx-lock-alt"></i>
                            <input type="password" id="patient-password" name="password" class="form-control" placeholder="Kata sandi akun Anda" required>
                            <i class="bx bx-show icon-right" onclick="togglePasswordVisibility('patient-password', this)"></i>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100 mt-4">
                        <i class="bx bx-log-in"></i> Masuk Sekarang
                    </button>
                </form>

                <!-- Demo Credentials Helper -->
                <div style="background:var(--surface-subtle); padding:14px; border-radius:var(--radius-md); margin-top:20px; font-size:12px; border:1px solid var(--border-color);">
                    <div style="font-weight:700; color:var(--text-main); margin-bottom:4px;">
                        <i class="bx bx-user-check text-primary"></i> Akun Pasien Demo (Password: <code>password123</code>):
                    </div>
                    <div style="color:var(--text-muted); display:flex; flex-direction:column; gap:4px;">
                        <span class="badge badge-mint text-left" onclick="fillPatient('ahmad.dahlan@example.com')" style="cursor:pointer;" title="Klik untuk mengisi">
                            ahmad.dahlan@example.com (Profil: Ahmad & Kenzo Dahlan)
                        </span>
                        <span class="badge badge-purple text-left" onclick="fillPatient('rara.amelia@example.com')" style="cursor:pointer;" title="Klik untuk mengisi">
                            rara.amelia@example.com (Profil: Rara Amelia & Naura)
                        </span>
                    </div>
                </div>

                <div class="auth-footer">
                    Belum memiliki akun pasien? 
                    <a href="<?= base_url('daftar_pasien.php') ?>" class="text-primary font-bold">Daftar Akun Baru</a>
                    <div class="mt-2">
                        <a href="<?= base_url('index.php') ?>" class="text-muted"><i class="bx bx-arrow-back"></i> Kembali ke Beranda</a>
                    </div>
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

        function fillPatient(email) {
            document.querySelector('input[name="email"]').value = email;
            document.getElementById('patient-password').value = 'password123';
        }
    </script>
    <script src="<?= base_url('assets/js/global.js') ?>"></script>
</body>
</html>

