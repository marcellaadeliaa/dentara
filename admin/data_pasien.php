<?php
/**
 * DENTARA - Portal Admin: Data Pasien & Master Rekam Medis
 * File: admin/data_pasien.php
 */

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout_staff.php';

require_role(['admin']);

// Handle Edit Pasien POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        set_flash('error', 'Token Keamanan Kadaluarsa', 'Silakan coba lagi.');
        header('Location: ' . base_url('admin/data_pasien.php'));
        exit;
    }

    $id_pasien = (int)($_POST['id_pasien'] ?? 0);
    $nama_pasien = trim($_POST['nama_pasien'] ?? '');
    $nik = trim($_POST['nik'] ?? '');
    $tgl_lahir = $_POST['tgl_lahir'] ?? null;
    $jenis_kelamin = $_POST['jenis_kelamin'] ?? 'L';
    $gol_darah = $_POST['gol_darah'] ?? null;
    $alamat = trim($_POST['alamat'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');
    $alergi_obat = trim($_POST['alergi_obat'] ?? '');
    $kondisi_medis = trim($_POST['kondisi_medis'] ?? '');

    if ($id_pasien > 0) {
        try {
            $stmt = $pdo->prepare("
                UPDATE pasien 
                SET nama_pasien = ?, nik = ?, tgl_lahir = ?, jenis_kelamin = ?, gol_darah = ?,
                    alamat = ?, no_hp = ?, alergi_obat = ?, kondisi_medis = ?
                WHERE id_pasien = ?
            ");
            $stmt->execute([$nama_pasien, $nik, $tgl_lahir, $jenis_kelamin, $gol_darah, $alamat, $no_hp, $alergi_obat, $kondisi_medis, $id_pasien]);
            catat_log($pdo, 'Edit Data Pasien', "Memperbarui data master pasien #$id_pasien ($nama_pasien)");
            set_flash('success', 'Data Pasien Diperbarui', "Profil pasien $nama_pasien berhasil disimpan.");
        } catch (Exception $e) {
            set_flash('error', 'Gagal Memperbarui', $e->getMessage());
        }
    }

    header('Location: ' . base_url('admin/data_pasien.php'));
    exit;
}

render_staff_layout('Master Data Pasien & Rekam Medis', function() {
    global $pdo;

    $q = trim($_GET['q'] ?? '');

    $sql = "
        SELECT 
            p.*,
            w.nama_wali, w.hubungan as hubungan_wali, w.kontak as kontak_wali,
            COUNT(DISTINCT rm.id_rekam) as total_rekam
        FROM pasien p
        LEFT JOIN wali_pasien w ON p.id_pasien = w.id_pasien
        LEFT JOIN rekam_medis rm ON p.id_pasien = rm.id_pasien
        WHERE 1=1
    ";
    $params = [];

    if ($q !== '') {
        $sql .= " AND (p.nama_pasien LIKE ? OR p.no_rm LIKE ? OR p.nik LIKE ? OR p.no_hp LIKE ?)";
        $params[] = "%$q%";
        $params[] = "%$q%";
        $params[] = "%$q%";
        $params[] = "%$q%";
    }

    $sql .= " GROUP BY p.id_pasien ORDER BY p.id_pasien DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $pasiens = $stmt->fetchAll();
    ?>

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 style="font-family:var(--font-heading); margin:0;">Database Pasien & Berkas Rekam Medis</h1>
            <p class="text-muted" style="margin:0;">Informasi demografi, kontak darurat, wali pasien anak, dan riwayat klinis.</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-secondary btn-sm" onclick="window.print()">
                <i class="bx bx-printer"></i> Cetak Daftar Pasien
            </button>
            <a href="<?= base_url('resepsionis/walkin.php') ?>" class="btn btn-primary btn-sm">
                <i class="bx bx-user-plus"></i> Pendaftaran Pasien Baru
            </a>
        </div>
    </div>

    <!-- Filter & Pencarian Pasien -->
    <div class="card p-3 mb-4" style="background:var(--surface);">
        <form method="GET" class="d-flex align-items-center gap-2 flex-wrap">
            <input type="text" name="q" class="form-control" style="max-width:350px;" placeholder="Cari nama pasien, No RM, NIK, No HP..." value="<?= e($q) ?>">
            <button type="submit" class="btn btn-primary btn-sm">Cari Pasien</button>
            <?php if ($q): ?>
                <a href="<?= base_url('admin/data_pasien.php') ?>" class="btn btn-ghost btn-sm">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Tabel Data Pasien -->
    <div class="card p-0" style="background:var(--surface); overflow:hidden;">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>No. RM</th>
                        <th>Nama Pasien & NIK</th>
                        <th>Usia & JK</th>
                        <th>Kontak & Alamat</th>
                        <th>Kondisi & Alergi Obat</th>
                        <th class="text-center">Kunjungan</th>
                        <th class="text-center" style="width:120px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pasiens)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">Tidak ada data pasien yang cocok.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($pasiens as $p): ?>
                            <tr>
                                <td>
                                    <strong class="text-primary font-bold" style="font-size:var(--text-base);"><?= e($p['no_rm']) ?></strong>
                                    <div style="font-size:10px; color:var(--text-muted);">Goldar: <?= e($p['gol_darah'] ?: '-') ?></div>
                                </td>
                                <td>
                                    <strong><?= e($p['nama_pasien']) ?></strong>
                                    <?php if (!empty($p['nama_wali'])): ?>
                                        <div style="font-size:11px; color:var(--text-muted);">Wali: <?= e($p['nama_wali']) ?> (<?= e($p['hubungan_wali']) ?>)</div>
                                    <?php endif; ?>
                                    <div class="text-muted" style="font-size:11px;">NIK: <?= e($p['nik'] ?: '-') ?></div>
                                </td>
                                <td>
                                    <div><?= hitung_usia($p['tgl_lahir']) ?></div>
                                    <div class="text-muted" style="font-size:11px;"><?= $p['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?></div>
                                </td>
                                <td style="font-size:12px; max-width:200px;">
                                    <div><strong><?= e($p['no_hp'] ?: '-') ?></strong></div>
                                    <div class="text-muted text-truncate"><?= e($p['alamat'] ?: '-') ?></div>
                                </td>
                                <td>
                                    <?php if (!empty($p['alergi_obat'])): ?>
                                        <span class="badge badge-danger d-block mb-1">Alergi: <?= e($p['alergi_obat']) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($p['kondisi_medis'])): ?>
                                        <span class="badge badge-warning d-block">Medis: <?= e($p['kondisi_medis']) ?></span>
                                    <?php endif; ?>
                                    <?php if (empty($p['alergi_obat']) && empty($p['kondisi_medis'])): ?>
                                        <span class="text-muted" style="font-size:12px;">Tidak Ada Riwayat</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center font-bold">
                                    <span class="badge badge-secondary"><?= (int)$p['total_rekam'] ?> Kali</span>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <button type="button" class="btn btn-ghost btn-sm text-primary" onclick='bukaModalEditPasien(<?= json_encode($p) ?>)' title="Edit Profil">
                                            <i class="bx bx-edit" style="font-size:18px;"></i>
                                        </button>
                                        <a href="<?= base_url('dokter/riwayat_pasien.php?q=' . urlencode($p['no_rm'])) ?>" class="btn btn-ghost btn-sm text-success" title="Lihat Rekam Medis">
                                            <i class="bx bx-folder-open" style="font-size:18px;"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Edit Pasien -->
    <div id="modal-edit-pasien" class="modal-backdrop" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
        <div class="card p-4" style="max-width:650px; width:92%; max-height:90vh; overflow-y:auto; background:var(--surface);">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 style="margin:0; font-family:var(--font-heading);" id="modal-pasien-title">Edit Master Pasien</h4>
                <button type="button" class="btn btn-ghost btn-sm" onclick="tutupModalPasien()">&times;</button>
            </div>

            <form method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="id_pasien" id="pasien-id" value="">

                <div class="row g-3 mb-3">
                    <div class="col-8">
                        <label class="form-label font-bold">Nama Lengkap Pasien <span class="text-danger">*</span></label>
                        <input type="text" name="nama_pasien" id="pasien-nama" class="form-control" required>
                    </div>
                    <div class="col-4">
                        <label class="form-label font-bold">No. Rekam Medis</label>
                        <input type="text" id="pasien-rm" class="form-control" readonly disabled>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label font-bold">NIK (16 Digit)</label>
                        <input type="text" name="nik" id="pasien-nik" class="form-control" maxlength="16">
                    </div>
                    <div class="col-6">
                        <label class="form-label font-bold">No. Telepon / WA</label>
                        <input type="tel" name="no_hp" id="pasien-hp" class="form-control">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-4">
                        <label class="form-label font-bold">Tanggal Lahir</label>
                        <input type="date" name="tgl_lahir" id="pasien-tgl-lahir" class="form-control">
                    </div>
                    <div class="col-4">
                        <label class="form-label font-bold">Jenis Kelamin</label>
                        <select name="jenis_kelamin" id="pasien-jk" class="form-select">
                            <option value="L">Laki-laki</option>
                            <option value="P">Perempuan</option>
                        </select>
                    </div>
                    <div class="col-4">
                        <label class="form-label font-bold">Gol. Darah</label>
                        <select name="gol_darah" id="pasien-goldar" class="form-select">
                            <option value="">-</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="AB">AB</option>
                            <option value="O">O</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label font-bold">Alamat Lengkap</label>
                    <textarea name="alamat" id="pasien-alamat" class="form-control" rows="2"></textarea>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-6">
                        <label class="form-label font-bold text-danger">Alergi Obat</label>
                        <input type="text" name="alergi_obat" id="pasien-alergi" class="form-control" placeholder="Contoh: Amoxicillin, Paracetamol">
                    </div>
                    <div class="col-6">
                        <label class="form-label font-bold text-warning">Kondisi Medis Khusus</label>
                        <input type="text" name="kondisi_medis" id="pasien-medis" class="form-control" placeholder="Contoh: Diabetes, Hipertensi, Asma">
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-secondary" onclick="tutupModalPasien()">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Profil Pasien</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function bukaModalEditPasien(p) {
        document.getElementById('modal-pasien-title').innerText = 'Edit Pasien: ' + p.nama_pasien;
        document.getElementById('pasien-id').value = p.id_pasien;
        document.getElementById('pasien-nama').value = p.nama_pasien;
        document.getElementById('pasien-rm').value = p.no_rm;
        document.getElementById('pasien-nik').value = p.nik || '';
        document.getElementById('pasien-hp').value = p.no_hp || '';
        document.getElementById('pasien-tgl-lahir').value = p.tgl_lahir || '';
        document.getElementById('pasien-jk').value = p.jenis_kelamin;
        document.getElementById('pasien-goldar').value = p.gol_darah || '';
        document.getElementById('pasien-alamat').value = p.alamat || '';
        document.getElementById('pasien-alergi').value = p.alergi_obat || '';
        document.getElementById('pasien-medis').value = p.kondisi_medis || '';
        document.getElementById('modal-edit-pasien').style.display = 'flex';
    }

    function tutupModalPasien() {
        document.getElementById('modal-edit-pasien').style.display = 'none';
    }
    </script>
    <?php
}, 'data_pasien');

