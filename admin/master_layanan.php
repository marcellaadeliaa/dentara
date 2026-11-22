<?php
/**
 * DENTARA - Portal Admin: Master Tindakan Layanan Gigi & Tarif
 * File: admin/master_layanan.php
 */

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout_staff.php';

require_role(['admin']);

// Handle POST: Tambah / Edit / Hapus Layanan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        set_flash('error', 'Token Keamanan Kadaluarsa', 'Silakan coba lagi.');
        header('Location: ' . base_url('admin/master_layanan.php'));
        exit;
    }

    $action = $_POST['action'] ?? '';
    $id_layanan = (int)($_POST['id_layanan'] ?? 0);
    $nama_layanan = trim($_POST['nama_layanan'] ?? '');
    $kategori = $_POST['kategori'] ?? 'Preventif';
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $estimasi_menit = (int)($_POST['estimasi_menit'] ?? 30);
    $tarif_awal = (float)($_POST['tarif_awal'] ?? 0);
    $status = $_POST['status'] ?? 'aktif';

    try {
        if ($action === 'tambah') {
            if (empty($nama_layanan)) throw new Exception('Nama layanan tidak boleh kosong.');

            $stmt = $pdo->prepare("
                INSERT INTO layanan (nama_layanan, kategori, deskripsi, estimasi_menit, tarif_awal, status)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$nama_layanan, $kategori, $deskripsi, $estimasi_menit, $tarif_awal, $status]);
            catat_log($pdo, 'Tambah Layanan', "Menambahkan layanan baru: $nama_layanan (" . rupiah($tarif_awal) . ")");
            set_flash('success', 'Layanan Ditambahkan', "Layanan $nama_layanan berhasil disimpan.");

        } elseif ($action === 'edit' && $id_layanan > 0) {
            $stmt = $pdo->prepare("
                UPDATE layanan 
                SET nama_layanan = ?, kategori = ?, deskripsi = ?, estimasi_menit = ?, tarif_awal = ?, status = ?
                WHERE id_layanan = ?
            ");
            $stmt->execute([$nama_layanan, $kategori, $deskripsi, $estimasi_menit, $tarif_awal, $status, $id_layanan]);
            catat_log($pdo, 'Edit Layanan', "Mengubah data layanan #$id_layanan ($nama_layanan)");
            set_flash('success', 'Layanan Diperbarui', "Data layanan $nama_layanan berhasil disimpan.");

        } elseif ($action === 'hapus' && $id_layanan > 0) {
            // Cek apakah sudah pernah dipakai di rekam_tindakan
            $stmt_c = $pdo->prepare("SELECT COUNT(*) FROM rekam_tindakan WHERE id_layanan = ?");
            $stmt_c->execute([$id_layanan]);
            if ($stmt_c->fetchColumn() > 0) {
                // Nonaktifkan saja
                $stmt_dis = $pdo->prepare("UPDATE layanan SET status = 'nonaktif' WHERE id_layanan = ?");
                $stmt_dis->execute([$id_layanan]);
                set_flash('warning', 'Layanan Dinonaktifkan', 'Layanan ini memiliki riwayat medis sehingga statusnya diubah menjadi Nonaktif.');
            } else {
                $stmt_del = $pdo->prepare("DELETE FROM layanan WHERE id_layanan = ?");
                $stmt_del->execute([$id_layanan]);
                set_flash('success', 'Layanan Dihapus', 'Data layanan telah dihapus.');
            }
        }
    } catch (Exception $e) {
        set_flash('error', 'Gagal Memproses Layanan', $e->getMessage());
    }

    header('Location: ' . base_url('admin/master_layanan.php'));
    exit;
}

render_staff_layout('Master Layanan & Tarif', function() {
    global $pdo;

    $kat_filter = trim($_GET['kategori'] ?? '');
    $q = trim($_GET['q'] ?? '');

    $sql = "SELECT * FROM layanan WHERE 1=1";
    $params = [];

    if ($kat_filter !== '') {
        $sql .= " AND kategori = ?";
        $params[] = $kat_filter;
    }
    if ($q !== '') {
        $sql .= " AND (nama_layanan LIKE ? OR deskripsi LIKE ?)";
        $params[] = "%$q%";
        $params[] = "%$q%";
    }
    $sql .= " ORDER BY kategori ASC, nama_layanan ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $layanan_list = $stmt->fetchAll();
    ?>

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 style="font-family:var(--font-heading); margin:0;">Master Tindakan Medis & Tarif Layanan</h1>
            <p class="text-muted" style="margin:0;">Data ini sinkron otomatis dengan katalog publik, formulir booking, dan auto-billing rekam medis.</p>
        </div>
        <div>
            <button type="button" class="btn btn-primary" onclick="bukaModalTambahLayanan()">
                <i class="bx bx-plus"></i> Tambah Layanan Baru
            </button>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="card p-3 mb-4" style="background:var(--surface);">
        <form method="GET" class="d-flex align-items-center gap-2 flex-wrap">
            <input type="text" name="q" class="form-control" style="max-width:280px;" placeholder="Cari nama tindakan..." value="<?= e($q) ?>">
            <select name="kategori" class="form-select" style="max-width:180px;">
                <option value="">Semua Kategori</option>
                <option value="Preventif" <?= $kat_filter === 'Preventif' ? 'selected' : '' ?>>Preventif</option>
                <option value="Konservasi" <?= $kat_filter === 'Konservasi' ? 'selected' : '' ?>>Konservasi</option>
                <option value="Ortodonti" <?= $kat_filter === 'Ortodonti' ? 'selected' : '' ?>>Ortodonti</option>
                <option value="Bedah" <?= $kat_filter === 'Bedah' ? 'selected' : '' ?>>Bedah</option>
                <option value="Anak" <?= $kat_filter === 'Anak' ? 'selected' : '' ?>>Anak</option>
            </select>
            <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
            <?php if ($q || $kat_filter): ?>
                <a href="<?= base_url('admin/master_layanan.php') ?>" class="btn btn-ghost btn-sm">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Tabel Layanan -->
    <div class="card p-0" style="background:var(--surface); overflow:hidden;">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Nama Layanan & Deskripsi</th>
                        <th>Kategori</th>
                        <th class="text-center">Estimasi Durasi</th>
                        <th class="text-end">Tarif Dasar (Rp)</th>
                        <th class="text-center">Status</th>
                        <th class="text-center" style="width:120px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($layanan_list)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">Belum ada layanan ditemukan.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($layanan_list as $l): ?>
                            <tr>
                                <td>
                                    <strong style="font-size:var(--text-base);"><?= e($l['nama_layanan']) ?></strong>
                                    <div class="text-muted" style="font-size:12px; max-width:400px;"><?= e($l['deskripsi'] ?: '-') ?></div>
                                </td>
                                <td>
                                    <span class="badge badge-mint"><?= e($l['kategori']) ?></span>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-secondary"><i class="bx bx-time"></i> <?= (int)$l['estimasi_menit'] ?> Menit</span>
                                </td>
                                <td class="text-end font-bold" style="font-size:var(--text-base); color:var(--primary-teal);">
                                    <?= rupiah($l['tarif_awal']) ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge <?= $l['status'] === 'aktif' ? 'badge-success' : 'badge-danger' ?>">
                                        <?= ucfirst($l['status']) ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <button type="button" class="btn btn-ghost btn-sm text-primary" onclick='bukaModalEditLayanan(<?= json_encode($l) ?>)' title="Edit Layanan">
                                            <i class="bx bx-edit" style="font-size:18px;"></i>
                                        </button>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Hapus/nonaktifkan layanan ini?')">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="hapus">
                                            <input type="hidden" name="id_layanan" value="<?= $l['id_layanan'] ?>">
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

    <!-- Modal Form Layanan (Tambah / Edit) -->
    <div id="modal-layanan" class="modal-backdrop" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
        <div class="card p-4" style="max-width:550px; width:92%; background:var(--surface);">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 style="margin:0; font-family:var(--font-heading);" id="modal-layanan-title">Tambah Layanan Baru</h4>
                <button type="button" class="btn btn-ghost btn-sm" onclick="tutupModalLayanan()">&times;</button>
            </div>

            <form method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" id="layanan-action" value="tambah">
                <input type="hidden" name="id_layanan" id="layanan-id" value="">

                <div class="mb-3">
                    <label class="form-label font-bold">Nama Tindakan Medis <span class="text-danger">*</span></label>
                    <input type="text" name="nama_layanan" id="layanan-nama" class="form-control" required placeholder="Contoh: Scaling & Polishing Gigi">
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label font-bold">Kategori Layanan</label>
                        <select name="kategori" id="layanan-kategori" class="form-select">
                            <option value="Preventif">Preventif</option>
                            <option value="Konservasi">Konservasi</option>
                            <option value="Ortodonti">Ortodonti</option>
                            <option value="Bedah">Bedah Mulut</option>
                            <option value="Anak">Pedodonti (Anak)</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label font-bold">Estimasi Durasi (Menit)</label>
                        <input type="number" name="estimasi_menit" id="layanan-durasi" class="form-control" value="30" min="5" step="5" required>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label font-bold">Tarif Dasar / Awal (Rp)</label>
                        <input type="number" name="tarif_awal" id="layanan-tarif" class="form-control" min="0" step="5000" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label font-bold">Status Layanan</label>
                        <select name="status" id="layanan-status" class="form-select">
                            <option value="aktif">Aktif</option>
                            <option value="nonaktif">Nonaktif</option>
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label font-bold">Deskripsi Edukatif Pasien</label>
                    <textarea name="deskripsi" id="layanan-deskripsi" class="form-control" rows="3" placeholder="Jelaskan prosedur dan manfaat perawatan ini..."></textarea>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-secondary" onclick="tutupModalLayanan()">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btn-submit-layanan">Simpan Layanan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function bukaModalTambahLayanan() {
        document.getElementById('modal-layanan-title').innerText = 'Tambah Layanan Baru';
        document.getElementById('layanan-action').value = 'tambah';
        document.getElementById('layanan-id').value = '';
        document.getElementById('layanan-nama').value = '';
        document.getElementById('layanan-kategori').value = 'Preventif';
        document.getElementById('layanan-durasi').value = '30';
        document.getElementById('layanan-tarif').value = '150000';
        document.getElementById('layanan-status').value = 'aktif';
        document.getElementById('layanan-deskripsi').value = '';
        document.getElementById('btn-submit-layanan').innerText = 'Simpan Layanan';
        document.getElementById('modal-layanan').style.display = 'flex';
    }

    function bukaModalEditLayanan(l) {
        document.getElementById('modal-layanan-title').innerText = 'Edit Layanan: ' + l.nama_layanan;
        document.getElementById('layanan-action').value = 'edit';
        document.getElementById('layanan-id').value = l.id_layanan;
        document.getElementById('layanan-nama').value = l.nama_layanan;
        document.getElementById('layanan-kategori').value = l.kategori;
        document.getElementById('layanan-durasi').value = l.estimasi_menit;
        document.getElementById('layanan-tarif').value = l.tarif_awal;
        document.getElementById('layanan-status').value = l.status;
        document.getElementById('layanan-deskripsi').value = l.deskripsi || '';
        document.getElementById('btn-submit-layanan').innerText = 'Simpan Perubahan';
        document.getElementById('modal-layanan').style.display = 'flex';
    }

    function tutupModalLayanan() {
        document.getElementById('modal-layanan').style.display = 'none';
    }
    </script>
    <?php
}, 'master_layanan');

