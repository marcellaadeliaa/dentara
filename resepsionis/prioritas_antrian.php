<?php
/**
 * DENTARA - Portal Resepsionis: Pengaturan Prioritas Antrean
 * File: resepsionis/prioritas_antrian.php
 */

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout_staff.php';

require_role(['resepsionis', 'admin']);

$id_antrian = (int)($_GET['id'] ?? 0);

// Handle POST: Update Prioritas
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        header('Location: ' . base_url('resepsionis/dashboard_antrian.php'));
        exit;
    }

    $id_antrian  = (int)($_POST['id_antrian'] ?? 0);
    $prioritas   = (int)($_POST['prioritas'] ?? 0);
    $alasan_prio = trim($_POST['alasan_prioritas'] ?? '');

    try {
        $stmt_u = $pdo->prepare("UPDATE antrian SET prioritas = ?, alasan_prioritas = ? WHERE id_antrian = ?");
        $stmt_u->execute([$prioritas, $alasan_prio, $id_antrian]);

        catat_log($pdo, 'UPDATE_PRIORITAS', "Prioritas antrean #{$id_antrian} diubah menjadi level {$prioritas}.");

        set_flash('success', 'Prioritas Diperbarui', 'Urutan antrean pasien telah disesuaikan berdasarkan skala prioritas.');
        header('Location: ' . base_url('resepsionis/dashboard_antrian.php'));
        exit;
    } catch (Exception $e) {
        set_flash('error', 'Gagal Update', $e->getMessage());
        header('Location: ' . base_url('resepsionis/dashboard_antrian.php'));
        exit;
    }
}

// Ambil data antrean
$stmt = $pdo->prepare("
    SELECT a.*, r.kode_booking,
           COALESCE(p.nama_pasien, r.nama_tamu) as nama_pasien,
           p.no_rm, u.nama_lengkap as nama_dokter, d.spesialisasi
    FROM antrian a
    JOIN reservasi r ON a.id_reservasi = r.id_reservasi
    LEFT JOIN pasien p ON r.id_pasien = p.id_pasien
    JOIN dokter d ON a.id_dokter = d.id_dokter
    JOIN pengguna u ON d.id_pengguna = u.id_pengguna
    WHERE a.id_antrian = ?
");
$stmt->execute([$id_antrian]);
$antrian = $stmt->fetch();

if (!$antrian) {
    header('Location: ' . base_url('resepsionis/dashboard_antrian.php'));
    exit;
}

render_staff_layout('Pengaturan Prioritas Antrean', function() use ($antrian) {
?>
<div class="fade-in" style="max-width:620px; margin:0 auto;">
    <div class="mb-6">
        <a href="<?= base_url('resepsionis/dashboard_antrian.php') ?>" class="text-muted" style="font-size:13px;">
            <i class="bx bx-arrow-back"></i> Kembali ke Dashboard Antrean
        </a>
        <h1 style="font-size:var(--text-2xl); margin-top:8px; margin-bottom:4px;">Ubah Prioritas Pelayanan</h1>
        <p class="text-muted" style="margin:0;">Pasien dengan prioritas khusus atau darurat akan otomatis diposisikan di depan antrean normal.</p>
    </div>

    <div class="card p-6" style="border-radius:var(--radius-lg); box-shadow:var(--shadow-md);">
        <!-- Info Pasien -->
        <div class="d-flex align-items-center justify-content-between p-4 mb-4" style="background:var(--surface-subtle); border-radius:var(--radius-md); border:1px solid var(--border-color);">
            <div>
                <span class="text-muted" style="font-size:11px; text-transform:uppercase; font-weight:700;">Nomor Antrean Pasien</span>
                <div style="font-family:var(--font-heading); font-size:32px; font-weight:800; color:var(--primary); line-height:1; margin:4px 0;">
                    A-<?= sprintf('%03d', $antrian['nomor_antrian']) ?>
                </div>
                <strong style="font-size:16px;"><?= e($antrian['nama_pasien']) ?></strong>
            </div>
            <div class="text-right">
                <span class="badge badge-purple mb-1">Kode: <?= e($antrian['kode_booking']) ?></span>
                <div style="font-size:13px; color:var(--text-muted);"><?= e($antrian['nama_dokter']) ?></div>
            </div>
        </div>

        <form action="<?= base_url('resepsionis/prioritas_antrian.php') ?>" method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="id_antrian" value="<?= $antrian['id_antrian'] ?>">

            <div class="form-group mb-4">
                <label class="form-label required font-bold">Skala Prioritas:</label>
                <select name="prioritas" class="form-control" required>
                    <option value="0" <?= $antrian['prioritas'] == 0 ? 'selected' : '' ?>>
                        0 - Normal (Antrean Standar Sesuai Urutan Check-in)
                    </option>
                    <option value="1" <?= $antrian['prioritas'] == 1 ? 'selected' : '' ?>>
                        1 - Khusus (Lansia > 65 thn, Disabilitas, Anak Balita, Ibu Hamil)
                    </option>
                    <option value="2" <?= $antrian['prioritas'] == 2 ? 'selected' : '' ?>>
                        2 - Darurat Medis (Cito / Pendarahan Hebat / Trauma Fraktur Gigi)
                    </option>
                </select>
            </div>

            <div class="form-group mb-4">
                <label class="form-label required font-bold">Alasan Penentuan Prioritas:</label>
                <textarea name="alasan_prioritas" class="form-control" rows="3" placeholder="Contoh: Pasien lansia usia 72 tahun dengan keluhan nyeri hebat..." required><?= e($antrian['alasan_prioritas']) ?></textarea>
            </div>

            <div class="d-flex justify-content-end gap-3 pt-3" style="border-top:1px solid var(--border-color);">
                <a href="<?= base_url('resepsionis/dashboard_antrian.php') ?>" class="btn btn-secondary btn-md">
                    Batal
                </a>
                <button type="submit" class="btn btn-primary btn-md">
                    <i class="bx bx-save"></i> Simpan Perubahan Prioritas
                </button>
            </div>
        </form>
    </div>
</div>
<?php
}, 'antrian');

