<?php
/**
 * DENTARA - Portal Pasien: Kelola Anggota Keluarga (Sub-Patients)
 * File: pasien/keluarga.php
 */

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout_pasien.php';

require_patient();

$id_akun = (int)$_SESSION['id_akun_pasien'];

// Handle POST: Tambah Anggota Keluarga / Ganti Profil Aktif
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        set_flash('error', 'Token Tidak Valid', 'Sesi formulir telah kedaluwarsa.');
        header('Location: ' . base_url('pasien/keluarga.php'));
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'switch_profile') {
        $id_p = (int)($_POST['id_pasien'] ?? 0);
        $stmt_c = $pdo->prepare("SELECT id_pasien, nama_pasien FROM pasien WHERE id_pasien = ? AND id_akun = ?");
        $stmt_c->execute([$id_p, $id_akun]);
        $found = $stmt_c->fetch();

        if ($found) {
            $_SESSION['id_pasien_aktif'] = (int)$found['id_pasien'];
            set_flash('success', 'Profil Diganti', "Sekarang mengelola data pasien: {$found['nama_pasien']}");
        }
        header('Location: ' . base_url('pasien/dashboard.php'));
        exit;
    }

    if ($action === 'tambah_anggota') {
        $hubungan      = trim($_POST['hubungan_keluarga'] ?? 'Anak');
        $nama_pasien   = trim($_POST['nama_pasien'] ?? '');
        $nik           = trim($_POST['nik'] ?? '');
        $tgl_lahir     = trim($_POST['tgl_lahir'] ?? '');
        $jenis_kelamin = trim($_POST['jenis_kelamin'] ?? '');
        $gol_darah     = trim($_POST['gol_darah'] ?? '');
        $alamat        = trim($_POST['alamat'] ?? '');
        $alergi_obat   = trim($_POST['alergi_obat'] ?? '');
        $kondisi_medis = trim($_POST['kondisi_medis'] ?? '');

        // Data wali jika anak
        $nama_wali     = trim($_POST['nama_wali'] ?? '');
        $hubungan_wali = trim($_POST['hubungan_wali'] ?? '');
        $kontak_wali   = trim($_POST['kontak_wali'] ?? '');

        if (empty($nama_pasien) || empty($nik) || empty($tgl_lahir) || empty($jenis_kelamin)) {
            set_flash('warning', 'Data Kurang Lengkap', 'Nama, NIK, tanggal lahir, dan jenis kelamin wajib diisi.');
            header('Location: ' . base_url('pasien/keluarga.php'));
            exit;
        }

        try {
            $pdo->beginTransaction();

            $no_rm = generate_no_rm($pdo);

            $stmt_ins = $pdo->prepare("
                INSERT INTO pasien (no_rm, id_akun, hubungan_keluarga, nama_pasien, nik, tgl_lahir, jenis_kelamin, gol_darah, alamat, no_hp, alergi_obat, kondisi_medis)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt_ins->execute([
                $no_rm, $id_akun, $hubungan, $nama_pasien, $nik, $tgl_lahir, $jenis_kelamin,
                $gol_darah ?: null, $alamat, $_SESSION['no_hp_pasien'] ?? '', $alergi_obat ?: null, $kondisi_medis ?: null
            ]);
            $id_pasien_baru = (int)$pdo->lastInsertId();

            // Jika anak dan ada data wali, simpan ke wali_pasien
            if ($hubungan === 'Anak' && !empty($nama_wali)) {
                $stmt_w = $pdo->prepare("
                    INSERT INTO wali_pasien (id_pasien, nama_wali, hubungan, kontak, alamat)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt_w->execute([$id_pasien_baru, $nama_wali, $hubungan_wali ?: 'Orang Tua', $kontak_wali, $alamat]);
            }

            // Inisialisasi poin gamifikasi untuk anak
            $stmt_p = $pdo->prepare("INSERT INTO gamifikasi_poin (id_pasien, total_poin, jumlah_kunjungan_selesai) VALUES (?, 20, 0)");
            $stmt_p->execute([$id_pasien_baru]);

            $pdo->commit();

            set_flash('success', 'Anggota Keluarga Ditambahkan', "Profil {$nama_pasien} berhasil dibuat dengan No RM: {$no_rm}");
            header('Location: ' . base_url('pasien/keluarga.php'));
            exit;

        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            set_flash('error', 'Gagal Menambah Profil', $e->getMessage());
            header('Location: ' . base_url('pasien/keluarga.php'));
            exit;
        }
    }
}

render_patient_layout('Akun Keluarga & Profil', function() use ($id_akun) {
    global $pdo;

    $stmt = $pdo->prepare("SELECT * FROM pasien WHERE id_akun = ? ORDER BY id_pasien ASC");
    $stmt->execute([$id_akun]);
    $keluarga = $stmt->fetchAll();

    $id_aktif = (int)($_SESSION['id_pasien_aktif'] ?? 0);
?>
<div class="fade-in">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-4 mb-6">
        <div>
            <h1 style="font-size:var(--text-2xl); margin-bottom:4px;">Kelola Profil Keluarga (Sub-Patient)</h1>
            <p class="text-muted" style="margin:0;">Satu akun DENTARA untuk seluruh anggota keluarga tercinta.</p>
        </div>
        <button class="btn btn-primary btn-md" onclick="document.getElementById('form-tambah-anggota').scrollIntoView({behavior:'smooth'})">
            <i class="bx bx-user-plus"></i> Tambah Anggota Baru
        </button>
    </div>

    <!-- DAFTAR PROFIL ANGGOTA KELUARGA -->
    <div class="grid grid-cols-2 gap-6 mb-6">
        <?php foreach ($keluarga as $p): 
            $is_current = ($p['id_pasien'] == $id_aktif);
        ?>
        <div class="card p-5 <?= $is_current ? 'border-primary' : '' ?>" style="position:relative; <?= $is_current ? 'border:2px solid var(--primary);' : '' ?>">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="badge badge-mint"><?= e($p['hubungan_keluarga']) ?></span>
                <?php if ($is_current): ?>
                    <span class="badge badge-success"><i class="bx bx-check"></i> PROFIL AKTIF</span>
                <?php endif; ?>
            </div>

            <div class="d-flex align-items-center gap-3 mb-3">
                <div style="width:52px; height:52px; border-radius:50%; background:var(--grad-mint-blue); color:#fff; display:flex; align-items:center; justify-content:center; font-size:22px; font-weight:700;">
                    <?= strtoupper(substr($p['nama_pasien'], 0, 1)) ?>
                </div>
                <div>
                    <h3 style="font-size:18px; margin:0;"><?= e($p['nama_pasien']) ?></h3>
                    <div style="font-size:12px; color:var(--text-muted);">
                        No RM: <strong><?= e($p['no_rm']) ?></strong> &bull; NIK: <?= e($p['nik']) ?>
                    </div>
                </div>
            </div>

            <div style="font-size:13px; color:var(--text-muted); line-height:1.6; border-top:1px solid var(--border-color); padding-top:10px;" class="mb-4">
                <div><i class="bx bx-cake"></i> Tanggal Lahir: <strong><?= tanggal_indo($p['tgl_lahir']) ?></strong> (<?= hitung_usia($p['tgl_lahir']) ?>)</div>
                <div><i class="bx bx-water"></i> Gol. Darah: <strong><?= e($p['gol_darah'] ?: '-') ?></strong> &bull; Gender: <?= $p['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?></div>
                <?php if ($p['alergi_obat']): ?>
                    <div class="text-danger mt-1"><i class="bx bx-shield-x"></i> Alergi: <strong><?= e($p['alergi_obat']) ?></strong></div>
                <?php endif; ?>
            </div>

            <?php if (!$is_current): ?>
                <form action="<?= base_url('pasien/keluarga.php') ?>" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="switch_profile">
                    <input type="hidden" name="id_pasien" value="<?= $p['id_pasien'] ?>">
                    <button type="submit" class="btn btn-secondary btn-sm w-100">
                        <i class="bx bx-refresh"></i> Beralih ke Profil Ini
                    </button>
                </form>
            <?php else: ?>
                <button class="btn btn-primary btn-sm w-100" disabled>
                    Sedang Digunakan
                </button>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- FORM TAMBAH ANGGOTA BARU -->
    <div id="form-tambah-anggota" class="card p-6" style="border-radius:var(--radius-lg);">
        <h3 style="font-size:20px; color:var(--primary); margin-bottom:16px;">
            <i class="bx bx-user-plus"></i> Daftarkan Anggota Keluarga Baru
        </h3>

        <form action="<?= base_url('pasien/keluarga.php') ?>" method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="tambah_anggota">

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div class="form-group">
                    <label class="form-label required">Hubungan Keluarga</label>
                    <select name="hubungan_keluarga" class="form-control" id="select-hubungan" onchange="toggleWaliField()" required>
                        <option value="Anak">Anak</option>
                        <option value="Pasangan">Pasangan (Suami/Istri)</option>
                        <option value="Orang Tua">Orang Tua</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label required">Nama Lengkap Pasien</label>
                    <input type="text" name="nama_pasien" class="form-control" placeholder="Nama sesuai KTP/KIA" required>
                </div>

                <div class="form-group">
                    <label class="form-label required">NIK (16 Digit)</label>
                    <input type="text" name="nik" class="form-control" placeholder="16 digit NIK" required pattern="[0-9]{16}" maxlength="16">
                </div>

                <div class="form-group">
                    <label class="form-label required">Tanggal Lahir</label>
                    <input type="date" name="tgl_lahir" class="form-control" required max="<?= date('Y-m-d') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label required">Jenis Kelamin</label>
                    <select name="jenis_kelamin" class="form-control" required>
                        <option value="L">Laki-laki (L)</option>
                        <option value="P">Perempuan (P)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Golongan Darah</label>
                    <select name="gol_darah" class="form-control">
                        <option value="">-- Pilih --</option>
                        <option value="A">A</option>
                        <option value="B">B</option>
                        <option value="AB">AB</option>
                        <option value="O">O</option>
                    </select>
                </div>

                <div class="form-group" style="grid-column: span 2;">
                    <label class="form-label">Alamat Tempat Tinggal</label>
                    <textarea name="alamat" class="form-control" rows="2" placeholder="Nama jalan, kota..."></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Riwayat Alergi Obat</label>
                    <input type="text" name="alergi_obat" class="form-control" placeholder="Misal: Penisilin (opsional)">
                </div>

                <div class="form-group">
                    <label class="form-label">Kondisi Medis Khusus</label>
                    <input type="text" name="kondisi_medis" class="form-control" placeholder="Misal: Asma, Diabetes (opsional)">
                </div>
            </div>

            <!-- Bagian Wali (Khusus Profil Anak) -->
            <div id="section-wali" style="background:var(--surface-subtle); padding:16px; border-radius:var(--radius-md); border:1px solid var(--border-color); margin-bottom:20px;">
                <h4 style="font-size:15px; margin-bottom:12px; color:var(--text-main);">
                    <i class="bx bx-shield-quarter text-primary"></i> Data Wali / Orang Tua Pasien Anak
                </h4>
                <div class="grid grid-cols-3 gap-3">
                    <div class="form-group mb-0">
                        <label class="form-label">Nama Wali</label>
                        <input type="text" name="nama_wali" class="form-control" placeholder="Nama ayah/ibu">
                    </div>
                    <div class="form-group mb-0">
                        <label class="form-label">Hubungan Wali</label>
                        <input type="text" name="hubungan_wali" class="form-control" placeholder="Misal: Ayah Kandung">
                    </div>
                    <div class="form-group mb-0">
                        <label class="form-label">Kontak / HP Wali</label>
                        <input type="tel" name="kontak_wali" class="form-control" placeholder="08xxxxxxxx">
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-lg w-100">
                <i class="bx bx-check-circle"></i> Simpan Profil Anggota Keluarga
            </button>
        </form>
    </div>
</div>

<script>
function toggleWaliField() {
    const hub = document.getElementById('select-hubungan').value;
    const sec = document.getElementById('section-wali');
    sec.style.display = (hub === 'Anak') ? 'block' : 'none';
}
</script>
<?php
}, 'setting');

