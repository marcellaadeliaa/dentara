<?php
/**
 * DENTARA - Portal Apoteker: Manajemen Dental Supply & Bahan Medis Habis Pakai (BMHP)
 * File: apoteker/stok_supply.php
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
        header('Location: ' . base_url('apoteker/stok_supply.php'));
        exit;
    }

    $action = $_POST['action'] ?? '';
    $id_supply = (int)($_POST['id_supply'] ?? 0);
    $nama_barang = trim($_POST['nama_barang'] ?? '');
    $satuan = trim($_POST['satuan'] ?? 'Pcs');
    $stok = (int)($_POST['stok'] ?? 0);
    $stok_minimum = (int)($_POST['stok_minimum'] ?? 5);
    $exp_date = !empty($_POST['exp_date']) ? $_POST['exp_date'] : null;

    try {
        if ($action === 'tambah') {
            if (empty($nama_barang)) {
                throw new Exception('Nama dental supply tidak boleh kosong.');
            }
            $stmt = $pdo->prepare("
                INSERT INTO dental_supply (nama_barang, satuan, stok, stok_minimum, exp_date)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$nama_barang, $satuan, $stok, $stok_minimum, $exp_date]);
            catat_log($pdo, 'Tambah Dental Supply', "Menambahkan supply: $nama_barang");
            set_flash('success', 'Berhasil Ditambahkan', "Dental supply $nama_barang telah berhasil disimpan.");
        } elseif ($action === 'edit' && $id_supply > 0) {
            $stmt = $pdo->prepare("
                UPDATE dental_supply 
                SET nama_barang = ?, satuan = ?, stok = ?, stok_minimum = ?, exp_date = ?
                WHERE id_supply = ?
            ");
            $stmt->execute([$nama_barang, $satuan, $stok, $stok_minimum, $exp_date, $id_supply]);
            catat_log($pdo, 'Edit Dental Supply', "Mengubah supply #$id_supply ($nama_barang)");
            set_flash('success', 'Berhasil Diperbarui', "Data supply $nama_barang telah diperbarui.");
        } elseif ($action === 'hapus' && $id_supply > 0) {
            $stmt_del = $pdo->prepare("DELETE FROM dental_supply WHERE id_supply = ?");
            $stmt_del->execute([$id_supply]);
            catat_log($pdo, 'Hapus Dental Supply', "Menghapus dental supply ID #$id_supply");
            set_flash('success', 'Berhasil Dihapus', 'Data dental supply telah dihapus.');
        }
    } catch (Exception $e) {
        set_flash('error', 'Gagal Memproses Data', $e->getMessage());
    }

    header('Location: ' . base_url('apoteker/stok_supply.php'));
    exit;
}

render_staff_layout('Stok Dental Supply & BMHP', function() {
    global $pdo;

    $q = trim($_GET['q'] ?? '');

    $sql = "SELECT * FROM dental_supply WHERE 1=1";
    $params = [];

    if ($q !== '') {
        $sql .= " AND nama_barang LIKE ?";
        $params[] = "%$q%";
    }
    $sql .= " ORDER BY nama_barang ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $supplies = $stmt->fetchAll();

    // Hitung status kritis & kedaluwarsa
    $total_items = count($supplies);
    $count_kritis = 0;
    $count_exp = 0;
    $today = date('Y-m-d');
    $warning_date = date('Y-m-d', strtotime('+30 days'));

    foreach ($supplies as $s) {
        if ($s['stok'] <= $s['stok_minimum']) {
            $count_kritis++;
        }
        if (!empty($s['exp_date']) && $s['exp_date'] <= $warning_date) {
            $count_exp++;
        }
    }
    ?>

    <!-- Metric Cards -->
    <div class="row g-4 mb-4">
        <div class="col-12 col-md-4">
            <div class="card p-3" style="background:var(--surface);">
                <div style="font-size:var(--text-xs); color:var(--text-muted); font-weight:700;">TOTAL MACAM SUPPLY</div>
                <div style="font-size:var(--text-2xl); font-weight:800; font-family:var(--font-heading);"><?= $total_items ?> Macam</div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card p-3" style="background:var(--surface);">
                <div style="font-size:var(--text-xs); color:var(--accent-orange); font-weight:700;">STOK KRITIS (≤ MIN)</div>
                <div style="font-size:var(--text-2xl); font-weight:800; font-family:var(--font-heading); color:var(--accent-orange);"><?= $count_kritis ?> Item</div>
            </div>
        </div>
        <div class="col-12 col-md-4">
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
                <input type="text" name="q" class="form-control" style="max-width:280px;" placeholder="Cari nama barang supply..." value="<?= e($q) ?>">
                <button type="submit" class="btn btn-secondary btn-sm">Cari</button>
                <?php if ($q): ?>
                    <a href="<?= base_url('apoteker/stok_supply.php') ?>" class="btn btn-ghost btn-sm">Reset</a>
                <?php endif; ?>
            </form>

            <div>
                <button type="button" class="btn btn-primary" onclick="bukaModalTambah()">
                    <i class="bx bx-plus"></i> Tambah Dental Supply
                </button>
            </div>
        </div>
    </div>

    <!-- Tabel Data Supply -->
    <div class="card p-0" style="background:var(--surface); overflow:hidden;">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Nama Barang / Material Dental</th>
                        <th>Satuan</th>
                        <th class="text-center">Stok Saat Ini</th>
                        <th class="text-center">Batas Minimum</th>
                        <th>Tanggal Kadaluarsa</th>
                        <th>Status Stok</th>
                        <th class="text-center" style="width:120px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($supplies)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">Belum ada data dental supply.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($supplies as $s): 
                            $is_kritis = $s['stok'] <= $s['stok_minimum'];
                            $is_exp = !empty($s['exp_date']) && $s['exp_date'] <= $warning_date;
                            $is_expired_already = !empty($s['exp_date']) && $s['exp_date'] < $today;
                        ?>
                            <tr style="<?= $is_kritis ? 'background:rgba(255,107,107,0.04);' : '' ?>">
                                <td>
                                    <strong><?= e($s['nama_barang']) ?></strong>
                                </td>
                                <td><?= e($s['satuan']) ?></td>
                                <td class="text-center">
                                    <span class="badge <?= $is_kritis ? 'badge-danger' : 'badge-mint' ?>" style="font-size:var(--text-sm);">
                                        <?= (int)$s['stok'] ?>
                                    </span>
                                </td>
                                <td class="text-center text-muted"><?= (int)$s['stok_minimum'] ?></td>
                                <td>
                                    <?php if ($s['exp_date']): ?>
                                        <?php if ($is_expired_already): ?>
                                            <span class="text-danger font-bold"><i class="bx bxs-error"></i> <?= tanggal_indo($s['exp_date']) ?> (Expired)</span>
                                        <?php elseif ($is_exp): ?>
                                            <span class="text-warning font-bold"><i class="bx bxs-time"></i> <?= tanggal_indo($s['exp_date']) ?></span>
                                        <?php else: ?>
                                            <?= tanggal_indo($s['exp_date']) ?>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">Tidak Ada / Abadi</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($is_kritis): ?>
                                        <span class="badge badge-danger">Kritis / Segera Re-order</span>
                                    <?php else: ?>
                                        <span class="badge badge-success">Aman</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <button type="button" class="btn btn-ghost btn-sm text-primary" onclick='bukaModalEdit(<?= json_encode($s) ?>)' title="Edit">
                                            <i class="bx bx-edit" style="font-size:18px;"></i>
                                        </button>
                                        <form method="POST" class="d-inline" onsubmit="return confirmDelete(event, '<?= e($s['nama_barang']) ?>')">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="hapus">
                                            <input type="hidden" name="id_supply" value="<?= $s['id_supply'] ?>">
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

    <!-- Modal Form Dental Supply -->
    <div id="modal-supply" class="modal-backdrop" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
        <div class="card p-4" style="max-width:500px; width:92%; background:var(--surface);">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 style="margin:0; font-family:var(--font-heading);" id="modal-supply-title">Tambah Dental Supply</h4>
                <button type="button" class="btn btn-ghost btn-sm" onclick="tutupModalSupply()">&times;</button>
            </div>

            <form method="POST" id="form-supply">
                <?= csrf_field() ?>
                <input type="hidden" name="action" id="supply-action" value="tambah">
                <input type="hidden" name="id_supply" id="supply-id" value="">

                <div class="mb-3">
                    <label class="form-label font-bold">Nama Barang / Material <span class="text-danger">*</span></label>
                    <input type="text" name="nama_barang" id="supply-nama" class="form-control" required placeholder="Contoh: Dental Bibs, Masker, Komposit A2">
                </div>

                <div class="mb-3">
                    <label class="form-label font-bold">Satuan <span class="text-danger">*</span></label>
                    <input type="text" name="satuan" id="supply-satuan" class="form-control" required placeholder="Pcs / Box / Botol / Syringe">
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label font-bold">Stok Saat Ini</label>
                        <input type="number" name="stok" id="supply-stok" class="form-control" min="0" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label font-bold">Batas Stok Minimum</label>
                        <input type="number" name="stok_minimum" id="supply-stok-min" class="form-control" min="0" value="5" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label font-bold">Tanggal Kadaluarsa (Bila Ada)</label>
                    <input type="date" name="exp_date" id="supply-exp" class="form-control">
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-secondary" onclick="tutupModalSupply()">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btn-simpan-supply">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function bukaModalTambah() {
        document.getElementById('modal-supply-title').innerText = 'Tambah Dental Supply Baru';
        document.getElementById('supply-action').value = 'tambah';
        document.getElementById('supply-id').value = '';
        document.getElementById('form-supply').reset();
        document.getElementById('supply-stok-min').value = '5';
        document.getElementById('btn-simpan-supply').innerText = 'Simpan Supply';
        document.getElementById('modal-supply').style.display = 'flex';
    }

    function bukaModalEdit(s) {
        document.getElementById('modal-supply-title').innerText = 'Edit Supply: ' + s.nama_barang;
        document.getElementById('supply-action').value = 'edit';
        document.getElementById('supply-id').value = s.id_supply;
        document.getElementById('supply-nama').value = s.nama_barang;
        document.getElementById('supply-satuan').value = s.satuan;
        document.getElementById('supply-stok').value = s.stok;
        document.getElementById('supply-stok-min').value = s.stok_minimum;
        document.getElementById('supply-exp').value = s.exp_date || '';
        document.getElementById('btn-simpan-supply').innerText = 'Simpan Perubahan';
        document.getElementById('modal-supply').style.display = 'flex';
    }

    function tutupModalSupply() {
        document.getElementById('modal-supply').style.display = 'none';
    }

    function confirmDelete(e, nama) {
        e.preventDefault();
        const form = e.target;
        Swal.fire({
            title: 'Hapus Supply Ini?',
            text: `Apakah Anda yakin ingin menghapus "${nama}"? Data akan terhapus permanen.`,
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
}, 'stok_supply');

