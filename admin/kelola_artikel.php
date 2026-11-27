<?php
/**
 * DENTARA - Portal Admin: Kelola Artikel & Edukasi Pasien
 * File: admin/kelola_artikel.php
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
        header('Location: ' . base_url('admin/kelola_artikel.php'));
        exit;
    }

    $action = $_POST['action'] ?? '';
    $id_artikel = (int)($_POST['id_artikel'] ?? 0);
    $judul = trim($_POST['judul'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $isi = trim($_POST['isi'] ?? '');
    $penulis = trim($_POST['penulis'] ?? 'Tim Medis Dentara');
    $status = $_POST['status'] ?? 'publish';

    // Auto-generate slug jika kosong
    if (empty($slug)) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $judul)));
    }

    try {
        if ($action === 'tambah') {
            if (empty($judul) || empty($isi)) throw new Exception('Judul dan isi artikel wajib diisi.');

            $stmt = $pdo->prepare("
                INSERT INTO artikel (judul, slug, isi, penulis, status, created_at)
                VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
            ");
            $stmt->execute([$judul, $slug, $isi, $penulis, $status]);
            catat_log($pdo, 'Tambah Artikel', "Menerbitkan artikel baru: $judul");
            set_flash('success', 'Artikel Diterbitkan', "Artikel \"$judul\" berhasil disimpan.");

        } elseif ($action === 'edit' && $id_artikel > 0) {
            $stmt = $pdo->prepare("
                UPDATE artikel 
                SET judul = ?, slug = ?, isi = ?, penulis = ?, status = ?
                WHERE id_artikel = ?
            ");
            $stmt->execute([$judul, $slug, $isi, $penulis, $status, $id_artikel]);
            catat_log($pdo, 'Edit Artikel', "Mengubah artikel #$id_artikel ($judul)");
            set_flash('success', 'Artikel Diperbarui', 'Perubahan artikel berhasil disimpan.');

        } elseif ($action === 'hapus' && $id_artikel > 0) {
            $stmt = $pdo->prepare("DELETE FROM artikel WHERE id_artikel = ?");
            $stmt->execute([$id_artikel]);
            catat_log($pdo, 'Hapus Artikel', "Menghapus artikel #$id_artikel");
            set_flash('success', 'Artikel Dihapus', 'Artikel telah dihapus.');
        }
    } catch (Exception $e) {
        set_flash('error', 'Gagal Memproses Data', $e->getMessage());
    }

    header('Location: ' . base_url('admin/kelola_artikel.php'));
    exit;
}

render_staff_layout('Manajemen Artikel & Edukasi', function() {
    global $pdo;

    $stmt = $pdo->query("SELECT * FROM artikel ORDER BY id_artikel DESC");
    $artikels = $stmt->fetchAll();
    ?>

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 style="font-family:var(--font-heading); margin:0;">Kelola Artikel Edukasi Gigi</h1>
            <p class="text-muted" style="margin:0;">Artikel ini tampil pada halaman publik website dan aplikasi PWA pasien.</p>
        </div>
        <div>
            <button type="button" class="btn btn-primary" onclick="bukaModalTambahArtikel()">
                <i class="bx bx-plus-circle"></i> Tulis Artikel Baru
            </button>
        </div>
    </div>

    <!-- Tabel Artikel -->
    <div class="card p-0" style="background:var(--surface); overflow:hidden;">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th style="width:60px;">ID</th>
                        <th>Judul Artikel & Slug</th>
                        <th>Penulis</th>
                        <th class="text-center">Status</th>
                        <th>Tanggal Terbit</th>
                        <th class="text-center" style="width:140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($artikels)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">Belum ada artikel yang ditulis.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($artikels as $a): ?>
                            <tr>
                                <td>#<?= $a['id_artikel'] ?></td>
                                <td>
                                    <strong style="font-size:var(--text-base);"><?= e($a['judul']) ?></strong>
                                    <div class="text-muted" style="font-size:11px;">Slug: /artikel/<?= e($a['slug']) ?></div>
                                </td>
                                <td><?= e($a['penulis']) ?></td>
                                <td class="text-center">
                                    <span class="badge <?= $a['status'] === 'publish' ? 'badge-success' : 'badge-secondary' ?>">
                                        <?= ucfirst($a['status']) ?>
                                    </span>
                                </td>
                                <td style="font-size:12px; color:var(--text-muted);"><?= tanggal_indo(date('Y-m-d', strtotime($a['created_at']))) ?></td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <a href="<?= base_url('artikel_detail.php?slug=' . urlencode($a['slug'])) ?>" target="_blank" class="btn btn-ghost btn-sm text-primary" title="Lihat di Web">
                                            <i class="bx bx-show" style="font-size:18px;"></i>
                                        </a>
                                        <button type="button" class="btn btn-ghost btn-sm text-warning" onclick='bukaModalEditArtikel(<?= json_encode($a) ?>)' title="Edit">
                                            <i class="bx bx-edit" style="font-size:18px;"></i>
                                        </button>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Hapus artikel ini?')">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="hapus">
                                            <input type="hidden" name="id_artikel" value="<?= $a['id_artikel'] ?>">
                                            <button type="submit" class="btn btn-ghost btn-sm text-danger" title="Hapus">
                                                <i class="bx bx-trash" style="font-size:18px;"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Form Artikel -->
    <div id="modal-artikel" class="modal-backdrop" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
        <div class="card p-4" style="max-width:700px; width:92%; max-height:90vh; overflow-y:auto; background:var(--surface);">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 style="margin:0; font-family:var(--font-heading);" id="modal-artikel-title">Tulis Artikel Baru</h4>
                <button type="button" class="btn btn-ghost btn-sm" onclick="tutupModalArtikel()">&times;</button>
            </div>

            <form method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" id="artikel-action" value="tambah">
                <input type="hidden" name="id_artikel" id="artikel-id" value="">

                <div class="mb-3">
                    <label class="form-label font-bold">Judul Artikel <span class="text-danger">*</span></label>
                    <input type="text" name="judul" id="artikel-judul" class="form-control" required placeholder="Contoh: Pentingnya Scaling Gigi Rutin Setiap 6 Bulan">
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label font-bold">Penulis</label>
                        <input type="text" name="penulis" id="artikel-penulis" class="form-control" value="Tim Medis Dentara">
                    </div>
                    <div class="col-6">
                        <label class="form-label font-bold">Status Publikasi</label>
                        <select name="status" id="artikel-status" class="form-select">
                            <option value="publish">Publish (Tayang)</option>
                            <option value="draft">Draft (Disimpan Sementara)</option>
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label font-bold">Konten / Isi Artikel <span class="text-danger">*</span></label>
                    <textarea name="isi" id="artikel-isi" class="form-control" rows="8" required placeholder="Tulis edukasi kesehatan gigi di sini..."></textarea>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-secondary" onclick="tutupModalArtikel()">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btn-submit-artikel">Simpan Artikel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function bukaModalTambahArtikel() {
        document.getElementById('modal-artikel-title').innerText = 'Tulis Artikel Baru';
        document.getElementById('artikel-action').value = 'tambah';
        document.getElementById('artikel-id').value = '';
        document.getElementById('artikel-judul').value = '';
        document.getElementById('artikel-penulis').value = 'Tim Medis Dentara';
        document.getElementById('artikel-status').value = 'publish';
        document.getElementById('artikel-isi').value = '';
        document.getElementById('btn-submit-artikel').innerText = 'Terbitkan Artikel';
        document.getElementById('modal-artikel').style.display = 'flex';
    }

    function bukaModalEditArtikel(a) {
        document.getElementById('modal-artikel-title').innerText = 'Edit Artikel: ' + a.judul;
        document.getElementById('artikel-action').value = 'edit';
        document.getElementById('artikel-id').value = a.id_artikel;
        document.getElementById('artikel-judul').value = a.judul;
        document.getElementById('artikel-penulis').value = a.penulis;
        document.getElementById('artikel-status').value = a.status;
        document.getElementById('artikel-isi').value = a.isi;
        document.getElementById('btn-submit-artikel').innerText = 'Simpan Perubahan';
        document.getElementById('modal-artikel').style.display = 'flex';
    }

    function tutupModalArtikel() {
        document.getElementById('modal-artikel').style.display = 'none';
    }
    </script>
    <?php
}, 'artikel');

