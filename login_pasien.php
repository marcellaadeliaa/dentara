<?php
/**
 * DENTARA - Patient Login Page
 * File: login_pasien.php
 */

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

// Jika meminta ganti akun atau logout eksplisit
if (isset($_GET['switch']) || isset($_GET['logout'])) {
    unset($_SESSION['id_akun_pasien'], $_SESSION['email_pasien'], $_SESSION['no_hp_pasien'], $_SESSION['id_pasien_aktif']);
}

$is_patient_logged_in = !empty($_SESSION['id_akun_pasien']);
$current_patient = current_patient_account();

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

                <?php if ($is_patient_logged_in && $current_patient): ?>
                    <!-- Active Session Banner -->
                    <div style="background:#F0FDF9; border:1px solid #99F6E4; border-radius:12px; padding:12px 16px; margin-bottom:20px; display:flex; align-items:center; justify-content:space-between; gap:12px;">
                        <div style="font-size:12.5px; color:#0F766E;">
                            <i class="bx bxs-user-check" style="font-size:17px; vertical-align:middle; color:#0D9488;"></i>
                            Sesi aktif: <strong><?= e($current_patient['email']) ?></strong>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="<?= base_url('pasien/dashboard.php') ?>" class="btn btn-sm btn-success" style="font-size:11px; padding:3px 9px; border-radius:8px; font-weight:700;">
                                Buka Dashboard
                            </a>
                            <a href="<?= base_url('login_pasien.php?switch=1') ?>" class="btn btn-sm btn-ghost text-danger" style="font-size:11px; padding:3px 9px; border-radius:8px; font-weight:700;">
                                Reset
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <form id="formLoginPatient" action="<?= base_url('includes/process/login_pasien_process.php') ?>" method="POST" novalidate>
                    <?= csrf_field() ?>

                    <div class="form-group mb-3">
                        <label class="form-label required" style="font-weight:700; font-size:13px; color:#334155;">Alamat Email</label>
                        <div class="input-icon-group">
                            <i class="bx bx-envelope" style="color:#2BB5A0;"></i>
                            <input type="email" id="patient-email" name="email" class="form-control" placeholder="nama@email.com" required autofocus autocomplete="email" style="border-radius:10px;">
                        </div>
                    </div>

                    <div class="form-group mb-4">
                        <label class="form-label required" style="font-weight:700; font-size:13px; color:#334155;">Kata Sandi</label>
                        <div class="input-icon-group">
                            <i class="bx bx-lock-alt" style="color:#2BB5A0;"></i>
                            <input type="password" id="patient-password" name="password" class="form-control" placeholder="Kata sandi akun Anda" required autocomplete="current-password" style="border-radius:10px;">
                            <i class="bx bx-show icon-right" onclick="togglePasswordVisibility('patient-password', this)"></i>
                        </div>
                    </div>

                    <button type="submit" id="btnSubmitPatient" class="btn btn-primary btn-lg w-100" style="border-radius:12px; font-weight:750; box-shadow:0 4px 12px rgba(43,181,160,0.3); transition:all 0.2s ease;">
                        <i class="bx bx-log-in"></i> Masuk Sekarang
                    </button>
                </form>

                <!-- Demo Fast Access (1-Klik Masuk Smooth) -->
                <div style="background:#F8FAFC; padding:15px; border-radius:14px; margin-top:22px; border:1px solid #E2E8F0;">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div style="font-weight:750; color:#1E293B; font-size:12.5px;">
                            <i class="bx bxs-zap text-warning" style="font-size:15px;"></i> Akun Pasien Demo (1-Klik Masuk):
                        </div>
                        <span style="font-size:11px; color:#64748B;">Pass: <code>password123</code></span>
                    </div>
                    <div style="display:flex; flex-direction:column; gap:6px;">
                        <button type="button" class="btn btn-sm text-left d-flex align-items-center justify-content-between" onclick="quickLoginPatient('ahmad.dahlan@example.com')" style="background:#EFF6FF; color:#1D4ED8; border:1px solid #BFDBFE; font-weight:700; border-radius:8px; padding:6px 12px; font-size:12px;">
                            <span>👤 Ahmad Dahlan (Keluarga & Kenzo)</span>
                            <span class="badge badge-mint" style="font-size:10px;">Masuk &rarr;</span>
                        </button>
                        <button type="button" class="btn btn-sm text-left d-flex align-items-center justify-content-between" onclick="quickLoginPatient('rara.amelia@example.com')" style="background:#F5F3FF; color:#6D28D9; border:1px solid #DDD6FE; font-weight:700; border-radius:8px; padding:6px 12px; font-size:12px;">
                            <span>👤 Ibu Rara Amelia (Reservasi Aktif)</span>
                            <span class="badge badge-purple" style="font-size:10px;">Masuk &rarr;</span>
                        </button>
                    </div>
                </div>

                <div class="auth-footer mt-4" style="text-align:center; font-size:13px;">
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

        // 1-Klik Masuk Pasien
        function quickLoginPatient(email) {
            document.getElementById('patient-email').value = email;
            document.getElementById('patient-password').value = 'password123';
            handleSmoothPatientLogin();
        }

        const patientForm = document.getElementById('formLoginPatient');
        const patientBtn = document.getElementById('btnSubmitPatient');

        patientForm.addEventListener('submit', function(e) {
            e.preventDefault();
            handleSmoothPatientLogin();
        });

        async function handleSmoothPatientLogin() {
            const email = document.getElementById('patient-email').value.trim();
            const password = document.getElementById('patient-password').value;

            if (!email || !password) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Data Belum Lengkap',
                    text: 'Silakan isi email dan kata sandi Anda.',
                    confirmButtonColor: '#2BB5A0'
                });
                return;
            }

            patientBtn.disabled = true;
            const originalText = patientBtn.innerHTML;
            patientBtn.innerHTML = '<i class="bx bx-loader-alt bx-spin" style="font-size:18px;"></i> Memverifikasi...';

            try {
                const formData = new FormData(patientForm);
                const response = await fetch(patientForm.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil Masuk!',
                        text: data.message,
                        showConfirmButton: false,
                        timer: 900,
                        timerProgressBar: true
                    });
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 800);
                } else {
                    patientBtn.disabled = false;
                    patientBtn.innerHTML = originalText;
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Masuk',
                        text: data.message || 'Email atau kata sandi tidak cocok.',
                        confirmButtonColor: '#2BB5A0'
                    });
                }
            } catch (err) {
                console.warn('AJAX fallback:', err);
                patientForm.submit();
            }
        }
    </script>
    <script src="<?= base_url('assets/js/global.js') ?>"></script>
</body>
</html>

