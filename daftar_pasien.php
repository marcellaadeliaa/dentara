<?php
/**
 * DENTARA - Patient Registration Page
 * File: daftar_pasien.php
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
    <title>Pendaftaran Akun Pasien Baru - DENTARA</title>
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
    <div class="auth-wrapper" style="padding: 40px 16px;">
        <div class="auth-bg-blob blob-1"></div>
        <div class="auth-bg-blob blob-2"></div>

        <div class="card fade-in" style="max-width:800px; width:100%; z-index:10; border-radius:var(--radius-xl); padding:36px; box-shadow:var(--shadow-lg);">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-4 mb-4 pb-4" style="border-bottom:1px solid var(--border-color);">
                <div>
                    <a href="<?= base_url('index.php') ?>" style="text-decoration:none;">
                        <?= render_logo('md', true) ?>
                    </a>
                    <h1 style="font-size:var(--text-2xl); margin-top:12px; margin-bottom:4px;">Pendaftaran Pasien Baru</h1>
                    <p class="text-muted" style="font-size:var(--text-sm);">Dapatkan No Rekam Medis otomatis dan nikmati kemudahan booking online.</p>
                </div>
                <div>
                    <?= render_mascot('happy', 90) ?>
                </div>
            </div>

            <form action="<?= base_url('includes/process/daftar_pasien_process.php') ?>" method="POST">
                <?= csrf_field() ?>

                <h3 style="font-size:var(--text-md); color:var(--primary); margin-bottom:16px;">
                    <i class="bx bxs-lock-alt"></i> 1. Kredensial Akun Portal
                </h3>
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div class="form-group">
                        <label class="form-label required">Alamat Email</label>
                        <div class="input-icon-group">
                            <i class="bx bx-envelope"></i>
                            <input type="email" name="email" class="form-control" placeholder="nama@email.com" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label required">Nomor WhatsApp / HP</label>
                        <div class="input-icon-group">
                            <i class="bx bxl-whatsapp"></i>
                            <input type="tel" name="no_hp" class="form-control" placeholder="08xxxxxxxxxx" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label required">Kata Sandi</label>
                        <div class="input-icon-group">
                            <i class="bx bx-lock"></i>
                            <input type="password" name="password" class="form-control" placeholder="Minimal 6 karakter" required minlength="6">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label required">Konfirmasi Kata Sandi</label>
                        <div class="input-icon-group">
                            <i class="bx bx-check-shield"></i>
                            <input type="password" name="password_konfirmasi" class="form-control" placeholder="Ulangi kata sandi" required minlength="6">
                        </div>
                    </div>
                </div>

                <h3 style="font-size:var(--text-md); color:var(--primary); margin:24px 0 16px;">
                    <i class="bx bxs-id-card"></i> 2. Identitas Profil Pasien Utama
                </h3>
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div class="form-group" style="grid-column: span 2;">
                        <label class="form-label required">Nama Lengkap Pasien</label>
                        <div class="input-icon-group">
                            <i class="bx bx-user"></i>
                            <input type="text" name="nama_pasien" class="form-control" placeholder="Nama lengkap sesuai KTP/KIA" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label required">Nomor Induk Kependudukan (NIK)</label>
                        <div class="input-icon-group">
                            <i class="bx bx-card"></i>
                            <input type="text" name="nik" class="form-control" placeholder="16 digit NIK" required pattern="[0-9]{16}" maxlength="16">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label required">Tanggal Lahir</label>
                        <input type="date" name="tgl_lahir" class="form-control" required max="<?= date('Y-m-d') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label required">Jenis Kelamin</label>
                        <select name="jenis_kelamin" class="form-control" required>
                            <option value="">-- Pilih Jenis Kelamin --</option>
                            <option value="L">Laki-laki (L)</option>
                            <option value="P">Perempuan (P)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Golongan Darah</label>
                        <select name="gol_darah" class="form-control">
                            <option value="">-- Tidak Tahu / Belum Cek --</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="AB">AB</option>
                            <option value="O">O</option>
                        </select>
                    </div>

                    <div class="form-group" style="grid-column: span 2;">
                        <label class="form-label required">Alamat Tempat Tinggal</label>
                        <textarea name="alamat" class="form-control" rows="2" placeholder="Nama jalan, RT/RW, kelurahan, kota" required></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Riwayat Alergi Obat</label>
                        <input type="text" name="alergi_obat" class="form-control" placeholder="Misal: Penisilin, Amoxicillin (atau kosongkan)">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Kondisi Medis Khusus</label>
                        <input type="text" name="kondisi_medis" class="form-control" placeholder="Misal: Diabetes, Hipertensi, Asma, Maag">
                    </div>
                </div>

                <div class="mt-4 pt-4" style="border-top:1px solid var(--border-color);">
                    <button type="submit" class="btn btn-primary btn-lg w-100">
                        <i class="bx bx-check-circle"></i> Selesaikan Pendaftaran & Dapatkan No RM
                    </button>
                </div>
            </form>

            <div class="auth-footer mt-4">
                Sudah memiliki akun terdaftar? 
                <a href="<?= base_url('login_pasien.php') ?>" class="text-primary font-bold">Masuk di sini</a>
            </div>
        </div>
    </div>

    <script>
        <?php if ($flash): ?>
        window.dentaraFlash = <?= json_encode($flash) ?>;
        <?php endif; ?>
    </script>
    <script src="<?= base_url('assets/js/global.js') ?>"></script>
</body>
</html>

