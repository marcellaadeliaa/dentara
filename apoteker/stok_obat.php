<?php
/**
 * DENTARA - Portal Apoteker: Manajemen Stok Obat
 * File: apoteker/stok_obat.php
 */

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout_staff.php';

require_role(['apoteker', 'admin']);

// Handle Tambah / Edit / Hapus
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        set_flash('error', 'Token Keamanan Kadaluarsa', 'Silakan coba beberapa saat lagi.');
        header('Location: ' . base_url('apoteker/stok_obat.php'));
        exit;
    }

    $action = $_POST['action'] ?? '';
    $id_obat = (int)($_POST['id_obat'] ?? 0);
    $nama_obat = trim($_POST['nama_obat'] ?? '');
    $kategori = $_POST['kategori'] ?? 'Lainnya';
    $satuan = trim($_POST['satuan'] ?? 'Tablet');
    $harga_beli = (float)($_POST['harga_beli'] ?? 0);
    $harga_jual = (float)($_POST['harga_jual'] ?? 0);
    $stok = (int)($_POST['stok'] ?? 0);
    $stok_minimum = (int)($_POST['stok_minimum'] ?? 5);
    $exp_date = !empty($_POST['exp_date']) ? $_POST['exp_date'] : null;

    try {
        if ($action === 'tambah') {
            if (empty($nama_obat)) {
                throw new Exception('Nama obat tidak boleh kosong.');
            }
            $stmt = $pdo->prepare("
                INSERT INTO obat (nama_obat, kategori, satuan, harga_beli, harga_jual, stok, stok_minimum, exp_date)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$nama_obat, $kategori, $satuan, $harga_beli, $harga_jual, $stok, $stok_minimum, $exp_date]);
            catat_log($pdo, 'Tambah Obat', "Menambahkan obat baru: $nama_obat");
            set_flash('success', 'Berhasil Ditambahkan', "Obat $nama_obat telah berhasil disimpan.");
        } elseif ($action === 'edit' && $id_obat > 0) {
            $stmt = $pdo->prepare("
                UPDATE obat 
                SET nama_obat = ?, kategori = ?, satuan = ?, harga_beli = ?, harga_jual = ?, stok = ?, stok_minimum = ?, exp_date = ?
                WHERE id_obat = ?
            ");
            $stmt->execute([$nama_obat, $kategori, $satuan, $harga_beli, $harga_jual, $stok, $stok_minimum, $exp_date, $id_obat]);
            catat_log($pdo, 'Edit Obat', "Mengubah data obat #$id_obat ($nama_obat)");
            set_flash('success', 'Berhasil Diperbarui', "Data obat $nama_obat telah diperbarui.");
        } elseif ($action === 'hapus' && $id_obat > 0) {
            // Cek apakah obat sudah pernah diresepkan
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM resep_detail WHERE id_obat = ?");
            $stmt_check->execute([$id_obat]);
            if ($stmt_check->fetchColumn() > 0) {
                // Jangan hapus langsung, set stok 0 atau peringatan
                throw new Exception('Obat tidak dapat dihapus karena sudah memiliki riwayat resep pasien. Anda dapat mengubah stoknya menjadi 0.');
            }
            $stmt_del = $pdo->prepare("DELETE FROM obat WHERE id_obat = ?");
            $stmt_del->execute([$id_obat]);
            catat_log($pdo, 'Hapus Obat', "Menghapus data obat ID #$id_obat");
            set_flash('success', 'Berhasil Dihapus', 'Data obat telah dihapus.');
        }
    } catch (Exception $e) {
        set_flash('error', 'Gagal Memproses Data', $e->getMessage());
    }

    header('Location: ' . base_url('apoteker/stok_obat.php'));
    exit;
}

render_staff_layout('Manajemen Stok Obat', function() {
    global $pdo;

    $q = trim($_GET['q'] ?? '');
    $kat = trim($_GET['kat'] ?? '');

    $sql = "SELECT * FROM obat WHERE 1=1";
    $params = [];

    if ($q !== '') {
        $sql .= " AND nama_obat LIKE ?";
        $params[] = "%$q%";
    }
    if ($kat !== '') {
        $sql .= " AND kategori = ?";
        $params[] = $kat;
    }
    $sql .= " ORDER BY nama_obat ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $obats = $stmt->fetchAll();

    // Hitung total aset dan warning
    $total_items = count($obats);
    $total_aset = 0;
    $count_kritis = 0;
    $count_exp = 0;
    $today = date('Y-m-d');
    $warning_date = date('Y-m-d', strtotime('+30 days'));

    foreach ($obats as $o) {
        $total_aset += ($o['stok'] * $o['harga_beli']);
        if ($o['stok'] <= $o['stok_minimum']) {
            $count_kritis++;
        }
        if (!empty($o['exp_date']) && $o['exp_date'] <= $warning_date) {
            $count_exp++;
        }
    }
    ?>

    <!-- Metric Cards -->
    <div class="row g-4 mb-4">
        <div class="col-6 col-md-3">
            <div class="card p-3" style="background:var(--surface);">
                <div style="font-size:var(--text-xs); color:var(--text-muted); font-weight:700;">TOTAL MACAM OBAT</div>
                <div style="font-size:var(--text-2xl); font-weight:800; font-family:var(--font-heading);"><?= $total_items ?> Jenis</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card p-3" style="background:var(--surface);">
                <div style="font-size:var(--text-xs); color:var(--text-muted); font-weight:700;">VALUASI STOK (BELI)</div>
                <div style="font-size:var(--text-2xl); font-weight:800; font-family:var(--font-heading); color:var(--primary-teal);"><?= rupiah($total_aset) ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card p-3" style="background:var(--surface);">
                <div style="font-size:var(--text-xs); color:var(--accent-orange); font-weight:700;">STOK KRITIS (≤ MIN)</div>
                <div style="font-size:var(--text-2xl); font-weight:800; font-family:var(--font-heading); color:var(--accent-orange);"><?= $count_kritis ?> Item</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card p-3" style="background:var(--surface);">
                <div style="font-size:var(--text-xs); color:var(--danger); font-weight:700;">MENDEKATI KADALUARSA</div>
                <div style="font-size:var(--text-2xl); font-weight:800; font-family:var(--font-heading); color:var(--danger);"><?= $count_exp ?> Item</div>
            </div>
        </div>
    </div>

    <!-- Filter & Action Bar -->
    <div class="card p-3 mb-4" style="background:var(--surface);">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <form method="GET" class="d-flex align-items-center gap-2 flex-wrap">
                <input type="text" name="q" class="form-control" style="max-width:240px;" placeholder="Cari nama obat..." value="<?= e($q) ?>">
                <select name="kat" class="form-select" style="max-width:180px;">
                    <option value="">Semua Kategori</option>
                    <option value="Antibiotik" <?= $kat === 'Antibiotik' ? 'selected' : '' ?>>Antibiotik</option>
                    <option value="Antinyeri" <?= $kat === 'Antinyeri' ? 'selected' : '' ?>>Antinyeri</option>
                    <option value="Vitamin" <?= $kat === 'Vitamin' ? 'selected' : '' ?>>Vitamin</option>
                    <option value="Lainnya" <?= $kat === 'Lainnya' ? 'selected' : '' ?>>Lainnya</option>
                </select>
                <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
                <?php if ($q || $kat): ?>
                    <a href="<?= base_url('apoteker/stok_obat.php') ?>" class="btn btn-ghost btn-sm">Reset</a>
                <?php endif; ?>
            </form>

            <div>
                <button type="button" class="btn btn-primary" onclick="bukaModalTambah()">
                    <i class="bx bx-plus"></i> Tambah Obat Baru
                </button>
            </div>
        </div>
    </div>

    <!-- Tabel Data Obat -->
    <div class="card p-0" style="background:var(--surface); overflow:hidden;">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Nama Obat</th>
                        <th>Kategori</th>
                        <th>Satuan</th>
                        <th class="text-end">Harga Beli</th>
                        <th class="text-end">Harga Jual</th>
                        <th class="text-center">Stok / Min</th>
                        <th>Tgl Kadaluarsa</th>
                        <th class="text-center" style="width:120px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($obats)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">Tidak ada data obat yang sesuai kriteria.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($obats as $o): 
                            $is_kritis = $o['stok'] <= $o['stok_minimum'];
                            $is_exp = !empty($o['exp_date']) && $o['exp_date'] <= $warning_date;
                            $is_expired_already = !empty($o['exp_date']) && $o['exp_date'] < $today;
                        ?>
                            <tr style="<?= $is_kritis ? 'background:rgba(255,107,107,0.04);' : '' ?>">
                                <td>
                                    <strong><?= e($o['nama_obat']) ?></strong>
                                    <?php if ($is_kritis): ?>
                                        <span class="badge badge-warning" style="font-size:10px; margin-left:4px;">Stok Tipis</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge badge-secondary"><?= e($o['kategori']) ?></span></td>
                                <td><?= e($o['satuan']) ?></td>
                                <td class="text-end"><?= rupiah($o['harga_beli']) ?></td>
                                <td class="text-end font-bold"><?= rupiah($o['harga_jual']) ?></td>
                                <td class="text-center">
                                    <span class="badge <?= $is_kritis ? 'badge-danger' : 'badge-mint' ?>">
                                        <?= (int)$o['stok'] ?>
                                    </span>
                                    <span class="text-muted" style="font-size:11px;">/ <?= (int)$o['stok_minimum'] ?></span>
                                </td>
                                <td>
                                    <?php if ($o['exp_date']): ?>
                                        <?php if ($is_expired_already): ?>
                                            <span class="text-danger font-bold"><i class="bx bxs-error"></i> <?= tanggal_indo($o['exp_date']) ?> (Kadaluarsa)</span>
                                        <?php elseif ($is_exp): ?>
                                            <span class="text-warning font-bold"><i class="bx bxs-time"></i> <?= tanggal_indo($o['exp_date']) ?></span>
                                        <?php else: ?>
                                            <?= tanggal_indo($o['exp_date']) ?>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <button type="button" class="btn btn-ghost btn-sm text-primary" onclick='bukaModalEdit(<?= json_encode($o) ?>)' title="Edit">
                                            <i class="bx bx-edit" style="font-size:18px;"></i>
                                        </button>
                                        <form method="POST" class="d-inline" onsubmit="return confirmDelete(event, '<?= e($o['nama_obat']) ?>')">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="hapus">
                                            <input type="hidden" name="id_obat" value="<?= $o['id_obat'] ?>">
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

    <!-- Modal Form Obat (Tambah / Edit) -->
    <div id="modal-obat" class="modal-backdrop" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
        <div class="card p-4" style="max-width:550px; width:92%; max-height:90vh; overflow-y:auto; background:var(--surface);">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 style="margin:0; font-family:var(--font-heading);" id="modal-obat-title">Tambah Obat Baru</h4>
                <button type="button" class="btn btn-ghost btn-sm" onclick="tutupModalObat()">&times;</button>
            </div>

            <form method="POST" id="form-obat">
                <?= csrf_field() ?>
                <input type="hidden" name="action" id="obat-action" value="tambah">
                <input type="hidden" name="id_obat" id="obat-id" value="">

                <div class="mb-3">
                    <label class="form-label font-bold">Nama Obat <span class="text-danger">*</span></label>
                    <input type="text" name="nama_obat" id="obat-nama" class="form-control" required placeholder="Contoh: Amoxicillin 500mg">
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label font-bold">Kategori</label>
                        <select name="kategori" id="obat-kategori" class="form-select">
                            <option value="Antibiotik">Antibiotik</option>
                            <option value="Antinyeri">Antinyeri</option>
                            <option value="Vitamin">Vitamin</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label font-bold">Satuan</label>
                        <input type="text" name="satuan" id="obat-satuan" class="form-control" required placeholder="Tablet / Strip / Botol">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label font-bold">Harga Beli (Rp)</label>
                        <input type="number" name="harga_beli" id="obat-harga-beli" class="form-control" min="0" step="500" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label font-bold">Harga Jual (Rp)</label>
                        <input type="number" name="harga_jual" id="obat-harga-jual" class="form-control" min="0" step="500" required>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label font-bold">Stok Awal / Saat Ini</label>
                        <input type="number" name="stok" id="obat-stok" class="form-control" min="0" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label font-bold">Batas Stok Minimum</label>
                        <input type="number" name="stok_minimum" id="obat-stok-min" class="form-control" min="0" value="5" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label font-bold">Tanggal Kadaluarsa (Exp Date)</label>
                    <input type="date" name="exp_date" id="obat-exp" class="form-control">
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-secondary" onclick="tutupModalObat()">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btn-simpan-obat">Simpan Obat</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function bukaModalTambah() {
        document.getElementById('modal-obat-title').innerText = 'Tambah Obat Baru';
        document.getElementById('obat-action').value = 'tambah';
        document.getElementById('obat-id').value = '';
        document.getElementById('form-obat').reset();
        document.getElementById('obat-stok-min').value = '5';
        document.getElementById('btn-simpan-obat').innerText = 'Simpan Obat Baru';
        document.getElementById('modal-obat').style.display = 'flex';
    }

    function bukaModalEdit(o) {
        document.getElementById('modal-obat-title').innerText = 'Edit Obat: ' + o.nama_obat;
        document.getElementById('obat-action').value = 'edit';
        document.getElementById('obat-id').value = o.id_obat;
        document.getElementById('obat-nama').value = o.nama_obat;
        document.getElementById('obat-kategori').value = o.kategori;
        document.getElementById('obat-satuan').value = o.satuan;
        document.getElementById('obat-harga-beli').value = o.harga_beli;
        document.getElementById('obat-harga-jual').value = o.harga_jual;
        document.getElementById('obat-stok').value = o.stok;
        document.getElementById('obat-stok-min').value = o.stok_minimum;
        document.getElementById('obat-exp').value = o.exp_date || '';
        document.getElementById('btn-simpan-obat').innerText = 'Simpan Perubahan';
        document.getElementById('modal-obat').style.display = 'flex';
    }

    function tutupModalObat() {
        document.getElementById('modal-obat').style.display = 'none';
    }

    function confirmDelete(e, nama) {
        e.preventDefault();
        const form = e.target;
        Swal.fire({
            title: 'Hapus Obat Ini?',
            text: `Apakah Anda yakin ingin menghapus data "${nama}"? Aksi ini tidak dapat dibatalkan.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ff6b6b',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
        return false;
    }
    </script>
    <?php
}, 'stok_obat');

