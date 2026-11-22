<?php
/**
 * DENTARA - Portal Admin: Kelola Banner & Promo Klinik
 * File: admin/kelola_promo.php
 */

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout_staff.php';

require_role(['admin']);

// Handle CRUD POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        set_flash('error', 'Token Keamanan Kadaluarsa', 'Silakan coba lagi.');
        header('Location: ' . base_url('admin/kelola_promo.php'));
        exit;
    }

    $action = $_POST['action'] ?? '';
    $id_promo = (int)($_POST['id_promo'] ?? 0);
    $judul = trim($_POST['judul'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $mulai = !empty($_POST['tanggal_mulai']) ? $_POST['tanggal_mulai'] : null;
    $selesai = !empty($_POST['tanggal_selesai']) ? $_POST['tanggal_selesai'] : null;
    $status = $_POST['status'] ?? 'aktif';

    try {
        if ($action === 'tambah') {
            if (empty($judul)) throw new Exception('Judul promo tidak boleh kosong.');

            $stmt = $pdo->prepare("
                INSERT INTO promo_banner (judul, deskripsi, status, tanggal_mulai, tanggal_selesai)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$judul, $deskripsi, $status, $mulai, $selesai]);
            catat_log($pdo, 'Tambah Promo', "Menambahkan promo: $judul");
            set_flash('success', 'Promo Disimpan', "Banner promo $judul berhasil disimpan.");

        } elseif ($action === 'edit' && $id_promo > 0) {
            $stmt = $pdo->prepare("
                UPDATE promo_banner 
                SET judul = ?, deskripsi = ?, status = ?, tanggal_mulai = ?, tanggal_selesai = ?
                WHERE id_promo = ?
            ");
            $stmt->execute([$judul, $deskripsi, $status, $mulai, $selesai, $id_promo]);
            catat_log($pdo, 'Edit Promo', "Mengubah data promo #$id_promo");
            set_flash('success', 'Promo Diperbarui', 'Perubahan banner promo berhasil disimpan.');

        } elseif ($action === 'hapus' && $id_promo > 0) {
            $stmt = $pdo->prepare("DELETE FROM promo_banner WHERE id_promo = ?");
            $stmt->execute([$id_promo]);
            catat_log($pdo, 'Hapus Promo', "Menghapus promo #$id_promo");
            set_flash('success', 'Promo Dihapus', 'Banner promo telah dihapus.');
        }
    } catch (Exception $e) {
        set_flash('error', 'Gagal Memproses Data', $e->getMessage());
    }

    header('Location: ' . base_url('admin/kelola_promo.php'));
    exit;
}

render_staff_layout('Manajemen Promo & Banner', function() {
    global $pdo;

    $promos = $pdo->query("SELECT * FROM promo_banner ORDER BY id_promo DESC")->fetchAll();
    ?>

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 style="font-family:var(--font-heading); margin:0;">Kelola Promo & Diskon Perawatan</h1>
            <p class="text-muted" style="margin:0;">Banner ini tampil pada halaman beranda pasien dan landing page DENTARA.</p>
        </div>
        <div>
            <button type="button" class="btn btn-primary" onclick="bukaModalTambahPromo()">
                <i class="bx bx-plus-circle"></i> Tambah Banner Promo
            </button>
        </div>
    </div>

    <div class="row g-4">
        <?php if (empty($promos)): ?>
            <div class="col-12">
                <div class="card p-5 text-center text-muted">Belum ada banner promo terdaftar.</div>
            </div>
        <?php else: ?>
            <?php foreach ($promos as $p): ?>
                <div class="col-12 col-md-6">
                    <div class="card p-4 h-100" style="background:var(--surface); border:1px solid var(--border-color);">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h3 style="font-family:var(--font-heading); font-size:var(--text-lg); margin:0;">
                                <?= e($p['judul']) ?>
                            </h3>
                            <span class="badge <?= $p['status'] === 'aktif' ? 'badge-success' : 'badge-secondary' ?>">
                                <?= ucfirst($p['status']) ?>
                            </span>
                        </div>

                        <p class="text-muted" style="font-size:var(--text-sm); line-height:1.5;">
                            <?= nl2br(e($p['deskripsi'] ?: '-')) ?>
                        </p>

                        <div class="mt-auto pt-3 border-top d-flex justify-content-between align-items-center" style="font-size:12px;">
                            <div class="text-muted">
                                <i class="bx bx-calendar"></i> 
                                <?= $p['tanggal_mulai'] ? tanggal_indo($p['tanggal_mulai']) : 'Berlaku Selamanya' ?> 
                                <?= $p['tanggal_selesai'] ? 's/d ' . tanggal_indo($p['tanggal_selesai']) : '' ?>
                            </div>

                            <div class="d-flex gap-1">
                                <button type="button" class="btn btn-ghost btn-sm text-warning" onclick='bukaModalEditPromo(<?= json_encode($p) ?>)' title="Edit">
                                    <i class="bx bx-edit" style="font-size:18px;"></i>
                                </button>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Hapus promo ini?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="hapus">
                                    <input type="hidden" name="id_promo" value="<?= $p['id_promo'] ?>">
                                    <button type="submit" class="btn btn-ghost btn-sm text-danger" title="Hapus">
                                        <i class="bx bx-trash" style="font-size:18px;"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Modal Form Promo -->
    <div id="modal-promo" class="modal-backdrop" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
        <div class="card p-4" style="max-width:550px; width:92%; background:var(--surface);">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 style="margin:0; font-family:var(--font-heading);" id="modal-promo-title">Tambah Promo</h4>
                <button type="button" class="btn btn-ghost btn-sm" onclick="tutupModalPromo()">&times;</button>
            </div>

            <form method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" id="promo-action" value="tambah">
                <input type="hidden" name="id_promo" id="promo-id" value="">

                <div class="mb-3">
                    <label class="form-label font-bold">Judul Promo <span class="text-danger">*</span></label>
                    <input type="text" name="judul" id="promo-judul" class="form-control" required placeholder="Contoh: Diskon 20% Pasang Behel Bulan Ini">
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label font-bold">Tanggal Mulai</label>
                        <input type="date" name="tanggal_mulai" id="promo-mulai" class="form-control">
                    </div>
                    <div class="col-6">
                        <label class="form-label font-bold">Tanggal Selesai</label>
                        <input type="date" name="tanggal_selesai" id="promo-selesai" class="form-control">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label font-bold">Status Banner</label>
                    <select name="status" id="promo-status" class="form-select">
                        <option value="aktif">Aktif (Ditampilkan)</option>
                        <option value="nonaktif">Nonaktif (Disembunyikan)</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label font-bold">Deskripsi Syarat & Ketentuan</label>
                    <textarea name="deskripsi" id="promo-deskripsi" class="form-control" rows="3" placeholder="Jelaskan ketentuan promo..."></textarea>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-secondary" onclick="tutupModalPromo()">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btn-submit-promo">Simpan Promo</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function bukaModalTambahPromo() {
        document.getElementById('modal-promo-title').innerText = 'Tambah Banner Promo Baru';
        document.getElementById('promo-action').value = 'tambah';
        document.getElementById('promo-id').value = '';
        document.getElementById('promo-judul').value = '';
        document.getElementById('promo-mulai').value = '';
        document.getElementById('promo-selesai').value = '';
        document.getElementById('promo-status').value = 'aktif';
        document.getElementById('promo-deskripsi').value = '';
        document.getElementById('btn-submit-promo').innerText = 'Simpan Banner';
        document.getElementById('modal-promo').style.display = 'flex';
    }

    function bukaModalEditPromo(p) {
        document.getElementById('modal-promo-title').innerText = 'Edit Promo: ' + p.judul;
        document.getElementById('promo-action').value = 'edit';
        document.getElementById('promo-id').value = p.id_promo;
        document.getElementById('promo-judul').value = p.judul;
        document.getElementById('promo-mulai').value = p.tanggal_mulai || '';
        document.getElementById('promo-selesai').value = p.tanggal_selesai || '';
        document.getElementById('promo-status').value = p.status;
        document.getElementById('promo-deskripsi').value = p.deskripsi || '';
        document.getElementById('btn-submit-promo').innerText = 'Simpan Perubahan';
        document.getElementById('modal-promo').style.display = 'flex';
    }

    function tutupModalPromo() {
        document.getElementById('modal-promo').style.display = 'none';
    }
    </script>
    <?php
}, 'promo');

