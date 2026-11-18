<?php
/**
 * DENTARA - Portal Pasien: Pengaturan Akun & Profil
 * File: pasien/setting.php
 */

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout_pasien.php';

require_patient();

$id_akun = (int)$_SESSION['id_akun_pasien'];
$id_pasien_aktif = (int)($_SESSION['id_pasien_aktif'] ?? 0);

// Handle POST: Update Profil & Ganti Password
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        set_flash('error', 'Token Kedaluwarsa', 'Silakan refresh formulir.');
        header('Location: ' . base_url('pasien/setting.php'));
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'update_profil') {
        $no_hp         = trim($_POST['no_hp'] ?? '');
        $alamat        = trim($_POST['alamat'] ?? '');
        $gol_darah     = trim($_POST['gol_darah'] ?? '');
        $alergi_obat   = trim($_POST['alergi_obat'] ?? '');
        $kondisi_medis = trim($_POST['kondisi_medis'] ?? '');

        try {
            $stmt_u = $pdo->prepare("
                UPDATE pasien 
                SET no_hp = ?, alamat = ?, gol_darah = ?, alergi_obat = ?, kondisi_medis = ?
                WHERE id_pasien = ? AND id_akun = ?
            ");
            $stmt_u->execute([$no_hp, $alamat, $gol_darah ?: null, $alergi_obat ?: null, $kondisi_medis ?: null, $id_pasien_aktif, $id_akun]);

            // Update juga no_hp akun jika profil diri sendiri
            $stmt_acc = $pdo->prepare("UPDATE akun_pasien SET no_hp = ? WHERE id_akun = ?");
            $stmt_acc->execute([$no_hp, $id_akun]);

            set_flash('success', 'Profil Diperbarui', 'Data profil medis Anda berhasil disimpan.');
            header('Location: ' . base_url('pasien/setting.php'));
            exit;
        } catch (Exception $e) {
            set_flash('error', 'Gagal Simpan', $e->getMessage());
            header('Location: ' . base_url('pasien/setting.php'));
            exit;
        }
    }

    if ($action === 'ganti_password') {
        $lama = $_POST['password_lama'] ?? '';
        $baru = $_POST['password_baru'] ?? '';
        $konf = $_POST['password_konfirmasi'] ?? '';

        if (strlen($baru) < 6) {
            set_flash('warning', 'Kata Sandi Lemah', 'Kata sandi baru minimal 6 karakter.');
            header('Location: ' . base_url('pasien/setting.php'));
            exit;
        }

        if ($baru !== $konf) {
            set_flash('warning', 'Konfirmasi Berbeda', 'Kata sandi baru dan konfirmasi tidak cocok.');
            header('Location: ' . base_url('pasien/setting.php'));
            exit;
        }

        $stmt_p = $pdo->prepare("SELECT password FROM akun_pasien WHERE id_akun = ?");
        $stmt_p->execute([$id_akun]);
        $current_hash = $stmt_p->fetchColumn();

        if (password_verify($lama, $current_hash)) {
            $new_hash = password_hash($baru, PASSWORD_BCRYPT);
            $stmt_upd = $pdo->prepare("UPDATE akun_pasien SET password = ? WHERE id_akun = ?");
            $stmt_upd->execute([$new_hash, $id_akun]);

            set_flash('success', 'Kata Sandi Diubah', 'Kata sandi akun Anda berhasil diperbarui.');
            header('Location: ' . base_url('pasien/setting.php'));
            exit;
        } else {
            set_flash('error', 'Kata Sandi Salah', 'Kata sandi lama yang Anda masukkan tidak sesuai.');
            header('Location: ' . base_url('pasien/setting.php'));
            exit;
        }
    }
}

render_patient_layout('Pengaturan Akun & Profil', function() use ($id_pasien_aktif, $id_akun) {
    global $pdo;

    $stmt_p = $pdo->prepare("SELECT * FROM pasien WHERE id_pasien = ? AND id_akun = ?");
    $stmt_p->execute([$id_pasien_aktif, $id_akun]);
    $pasien = $stmt_p->fetch();
?>
<div class="fade-in" style="max-width:800px; margin:0 auto;">
    <div class="mb-6">
        <h1 style="font-size:var(--text-2xl); margin-bottom:4px;">Pengaturan Akun & Profil Pasien</h1>
        <p class="text-muted" style="margin:0;">Kelola data kontak, riwayat medis klinis, dan keamanan kata sandi Anda.</p>
    </div>

    <!-- KARTU 1: DATA PROFIL MEDIS PASIEN AKTIF -->
    <div class="card p-6 mb-6" style="border-radius:var(--radius-lg);">
        <div class="d-flex align-items-center justify-content-between mb-4 pb-3" style="border-bottom:1px solid var(--border-color);">
            <div>
                <h3 style="font-size:18px; margin:0;"><i class="bx bxs-user-detail text-primary"></i> Data Profil: <?= e($pasien['nama_pasien']) ?></h3>
                <span style="font-size:12px; color:var(--text-muted);">No RM: <?= e($pasien['no_rm']) ?> &bull; Hubungan: <?= e($pasien['hubungan_keluarga']) ?></span>
            </div>
            <a href="<?= base_url('pasien/keluarga.php') ?>" class="btn btn-secondary btn-sm">
                <i class="bx bx-group"></i> Ganti Profil
            </a>
        </div>

        <form action="<?= base_url('pasien/setting.php') ?>" method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_profil">

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div class="form-group">
                    <label class="form-label required">Nomor WhatsApp / HP</label>
                    <input type="tel" name="no_hp" class="form-control" value="<?= e($pasien['no_hp']) ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Golongan Darah</label>
                    <select name="gol_darah" class="form-control">
                        <option value="">-- Pilih --</option>
                        <option value="A" <?= $pasien['gol_darah'] === 'A' ? 'selected' : '' ?>>A</option>
                        <option value="B" <?= $pasien['gol_darah'] === 'B' ? 'selected' : '' ?>>B</option>
                        <option value="AB" <?= $pasien['gol_darah'] === 'AB' ? 'selected' : '' ?>>AB</option>
                        <option value="O" <?= $pasien['gol_darah'] === 'O' ? 'selected' : '' ?>>O</option>
                    </select>
                </div>

                <div class="form-group" style="grid-column: span 2;">
                    <label class="form-label">Alamat Lengkap</label>
                    <textarea name="alamat" class="form-control" rows="2"><?= e($pasien['alamat']) ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label text-danger font-bold"><i class="bx bx-shield-x"></i> Catatan Alergi Obat</label>
                    <input type="text" name="alergi_obat" class="form-control" value="<?= e($pasien['alergi_obat']) ?>" placeholder="Misal: Penisilin, Novalgin">
                </div>

                <div class="form-group">
                    <label class="form-label">Kondisi Medis Khusus</label>
                    <input type="text" name="kondisi_medis" class="form-control" value="<?= e($pasien['kondisi_medis']) ?>" placeholder="Misal: Hipertensi, Asma">
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-md">
                <i class="bx bx-save"></i> Simpan Pembaruan Profil
            </button>
        </form>
    </div>

    <!-- KARTU 2: GANTI KATA SANDI -->
    <div class="card p-6" style="border-radius:var(--radius-lg);">
        <h3 style="font-size:18px; margin-bottom:16px; border-bottom:1px solid var(--border-color); padding-bottom:12px;">
            <i class="bx bx-lock-alt text-primary"></i> Ganti Kata Sandi Akun
        </h3>

        <form action="<?= base_url('pasien/setting.php') ?>" method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="ganti_password">

            <div class="form-group mb-3">
                <label class="form-label required">Kata Sandi Saat Ini</label>
                <input type="password" name="password_lama" class="form-control" required>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div class="form-group mb-0">
                    <label class="form-label required">Kata Sandi Baru</label>
                    <input type="password" name="password_baru" class="form-control" placeholder="Minimal 6 karakter" required minlength="6">
                </div>
                <div class="form-group mb-0">
                    <label class="form-label required">Ulangi Kata Sandi Baru</label>
                    <input type="password" name="password_konfirmasi" class="form-control" required minlength="6">
                </div>
            </div>

            <button type="submit" class="btn btn-secondary btn-md">
                <i class="bx bx-key"></i> Perbarui Kata Sandi
            </button>
        </form>
    </div>
</div>
<?php
}, 'setting');

