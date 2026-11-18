<?php
/**
 * DENTARA - Portal Apoteker: Stock Opname (Fisik vs Sistem)
 * File: apoteker/stock_opname.php
 */

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout_staff.php';

require_role(['apoteker', 'admin']);

// Handle Submit Opname
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        set_flash('error', 'Token Keamanan Kadaluarsa', 'Silakan coba beberapa saat lagi.');
        header('Location: ' . base_url('apoteker/stock_opname.php'));
        exit;
    }

    $tanggal = $_POST['tanggal'] ?? date('Y-m-d');
    $catatan = trim($_POST['catatan'] ?? '');
    $items = $_POST['items'] ?? [];
    $sesuaikan_stok = isset($_POST['sesuaikan_stok']);
    $user = current_user();

    if (empty($items)) {
        set_flash('warning', 'Item Kosong', 'Harap isi data item obat/supply yang di-opname.');
        header('Location: ' . base_url('apoteker/stock_opname.php'));
        exit;
    }

    try {
        $pdo->beginTransaction();

        // 1. Simpan Header Opname
        $stmt_op = $pdo->prepare("
            INSERT INTO stock_opname (id_pengguna, tanggal, catatan)
            VALUES (?, ?, ?)
        ");
        $stmt_op->execute([$user['id_pengguna'], $tanggal, $catatan]);
        $id_opname = (int)$pdo->lastInsertId();

        // 2. Simpan Detail & Update Stok jika dicentang
        $stmt_det = $pdo->prepare("
            INSERT INTO stock_opname_detail (id_opname, jenis, id_item, stok_sistem, stok_fisik, selisih, keterangan)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($items as $it) {
            $jenis = $it['jenis'] ?? 'obat';
            $id_item = (int)($it['id_item'] ?? 0);
            $stok_sistem = (int)($it['stok_sistem'] ?? 0);
            $stok_fisik = (int)($it['stok_fisik'] ?? 0);
            $selisih = $stok_fisik - $stok_sistem;
            $keterangan = $it['keterangan'] ?? 'lainnya';

            if ($id_item > 0) {
                $stmt_det->execute([$id_opname, $jenis, $id_item, $stok_sistem, $stok_fisik, $selisih, $keterangan]);

                // Update stok jika opsi dicentang
                if ($sesuaikan_stok) {
                    if ($jenis === 'obat') {
                        $stmt_adj = $pdo->prepare("UPDATE obat SET stok = ? WHERE id_obat = ?");
                        $stmt_adj->execute([$stok_fisik, $id_item]);
                    } else {
                        $stmt_adj = $pdo->prepare("UPDATE dental_supply SET stok = ? WHERE id_supply = ?");
                        $stmt_adj->execute([$stok_fisik, $id_item]);
                    }
                }
            }
        }

        $pdo->commit();
        catat_log($pdo, 'Stock Opname', "Melakukan stock opname #$id_opname (" . count($items) . " item)");
        set_flash('success', 'Stock Opname Berhasil', 'Data stock opname berhasil disimpan' . ($sesuaikan_stok ? ' dan stok sistem telah disesuaikan.' : '.'));

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        set_flash('error', 'Gagal Simpan Opname', $e->getMessage());
    }

    header('Location: ' . base_url('apoteker/stock_opname.php'));
    exit;
}

render_staff_layout('Stock Opname Farmasi', function() {
    global $pdo;

    // Ambil daftar obat & supply untuk form opname
    $obats = $pdo->query("SELECT id_obat, nama_obat, satuan, stok FROM obat ORDER BY nama_obat ASC")->fetchAll();
    $supplies = $pdo->query("SELECT id_supply, nama_barang, satuan, stok FROM dental_supply ORDER BY nama_barang ASC")->fetchAll();

    // Ambil riwayat opname terakhir
    $stmt_hist = $pdo->query("
        SELECT so.*, u.nama_lengkap as nama_petugas,
               COUNT(sod.id) as total_item,
               SUM(CASE WHEN sod.selisih != 0 THEN 1 ELSE 0 END) as total_selisih
        FROM stock_opname so
        JOIN pengguna u ON so.id_pengguna = u.id_pengguna
        LEFT JOIN stock_opname_detail sod ON so.id_opname = sod.id_opname
        GROUP BY so.id_opname
        ORDER BY so.tanggal DESC, so.id_opname DESC
        LIMIT 20
    ");
    $history = $stmt_hist->fetchAll();
    ?>

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 style="font-family:var(--font-heading); margin:0;">Stock Opname & Penyesuaian Fisik</h1>
            <p class="text-muted" style="margin:0;">Lakukan pencocokan berkala antara stok tercatat di sistem dan fisik di rak penyimpanan.</p>
        </div>
        <div>
            <button type="button" class="btn btn-primary" onclick="bukaModalFormOpname()">
                <i class="bx bx-plus-circle"></i> Input Stock Opname Baru
            </button>
        </div>
    </div>

    <!-- Riwayat Stock Opname -->
    <div class="card p-0 mb-4" style="background:var(--surface); overflow:hidden;">
        <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
            <h3 style="font-family:var(--font-heading); font-size:var(--text-lg); margin:0;">
                <i class="bx bx-history text-primary"></i> Riwayat Stock Opname Sebelumnya
            </h3>
        </div>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th style="width:60px;">ID</th>
                        <th>Tanggal</th>
                        <th>Petugas Pelaksana</th>
                        <th class="text-center">Total Item Dicek</th>
                        <th class="text-center">Item Selisih</th>
                        <th>Catatan</th>
                        <th class="text-center" style="width:100px;">Detail</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($history)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">Belum ada riwayat stock opname yang tersimpan.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($history as $h): ?>
                            <tr>
                                <td><strong>#<?= $h['id_opname'] ?></strong></td>
                                <td><?= tanggal_indo($h['tanggal'], true) ?></td>
                                <td><strong><?= e($h['nama_petugas']) ?></strong></td>
                                <td class="text-center"><span class="badge badge-secondary"><?= (int)$h['total_item'] ?> Item</span></td>
                                <td class="text-center">
                                    <?php if ($h['total_selisih'] > 0): ?>
                                        <span class="badge badge-warning"><?= (int)$h['total_selisih'] ?> Ada Selisih</span>
                                    <?php else: ?>
                                        <span class="badge badge-success"><i class="bx bx-check"></i> Cocok Semua</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= e($h['catatan'] ?: '-') ?></td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-ghost btn-sm text-primary" onclick="lihatDetailOpname(<?= $h['id_opname'] ?>)">
                                        <i class="bx bx-show" style="font-size:18px;"></i> Lihat
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Form Input Opname -->
    <div id="modal-opname" class="modal-backdrop" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
        <div class="card p-4" style="max-width:850px; width:95%; max-height:90vh; overflow-y:auto; background:var(--surface);">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 style="margin:0; font-family:var(--font-heading);"><i class="bx bx-clipboard text-primary"></i> Form Stock Opname</h3>
                <button type="button" class="btn btn-ghost btn-sm" onclick="tutupModalOpname()">&times;</button>
            </div>

            <form method="POST" id="form-stock-opname">
                <?= csrf_field() ?>
                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-4">
                        <label class="form-label font-bold">Tanggal Opname</label>
                        <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-12 col-md-8">
                        <label class="form-label font-bold">Catatan / Keterangan</label>
                        <input type="text" name="catatan" class="form-control" placeholder="Contoh: Opname Akhir Bulan / Audit Triwulan">
                    </div>
                </div>

                <!-- Pemilih Item Cepat -->
                <div class="card p-3 mb-3 bg-light border">
                    <label class="form-label font-bold mb-2">Pilih Barang untuk Diperiksa Fisik:</label>
                    <div class="d-flex gap-2 flex-wrap">
                        <select id="pilih-item-select" class="form-select" style="flex:1; min-width:260px;">
                            <optgroup label="Daftar Obat Farmasi">
                                <?php foreach ($obats as $ob): ?>
                                    <option value="obat|<?= $ob['id_obat'] ?>|<?= e($ob['nama_obat']) ?>|<?= $ob['stok'] ?>|<?= e($ob['satuan']) ?>">
                                        [Obat] <?= e($ob['nama_obat']) ?> (Stok Sistem: <?= $ob['stok'] ?> <?= e($ob['satuan']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                            <optgroup label="Dental Supply & Bahan">
                                <?php foreach ($supplies as $su): ?>
                                    <option value="supply|<?= $su['id_supply'] ?>|<?= e($su['nama_barang']) ?>|<?= $su['stok'] ?>|<?= e($su['satuan']) ?>">
                                        [Supply] <?= e($su['nama_barang']) ?> (Stok Sistem: <?= $su['stok'] ?> <?= e($su['satuan']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        </select>
                        <button type="button" class="btn btn-secondary" onclick="tambahBarisItem()">
                            <i class="bx bx-plus"></i> Tambahkan
                        </button>
                    </div>
                </div>

                <!-- Tabel Item Opname -->
                <div class="table-responsive mb-3">
                    <table class="table" id="tabel-opname-items">
                        <thead>
                            <tr>
                                <th>Jenis & Nama Barang</th>
                                <th class="text-center" style="width:110px;">Stok Sistem</th>
                                <th class="text-center" style="width:120px;">Stok Fisik</th>
                                <th class="text-center" style="width:100px;">Selisih</th>
                                <th>Alasan / Keterangan</th>
                                <th style="width:50px;"></th>
                            </tr>
                        </thead>
                        <tbody id="opname-tbody">
                            <tr id="empty-row">
                                <td colspan="6" class="text-center py-4 text-muted">Belum ada item yang dimasukkan. Pilih item di atas lalu klik Tambahkan.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Checkbox Penyesuaian Otomatis -->
                <div class="p-3 mb-4 rounded" style="background:rgba(43,181,160,0.08); border:1px solid rgba(43,181,160,0.3);">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="sesuaikan_stok" id="check-sesuaikan" value="1" checked>
                        <label class="form-check-label font-bold" for="check-sesuaikan">
                            Langsung Sesuaikan Stok Sistem dengan Hasil Hitung Fisik Sekarang
                        </label>
                    </div>
                    <small class="text-muted d-block mt-1">Bila dicentang, angka stok di master obat & supply akan otomatis diselaraskan dengan input stok fisik di atas.</small>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-secondary" onclick="tutupModalOpname()">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Hasil Opname</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Detail Riwayat Opname -->
    <div id="modal-detail-opname" class="modal-backdrop" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
        <div class="card p-4" style="max-width:700px; width:92%; max-height:85vh; overflow-y:auto; background:var(--surface);">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 style="margin:0; font-family:var(--font-heading);" id="detail-opname-title">Detail Stock Opname</h4>
                <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('modal-detail-opname').style.display='none'">&times;</button>
            </div>
            <div id="detail-opname-body">
                <div class="text-center py-4"><i class="bx bx-loader-alt bx-spin" style="font-size:32px;"></i> Memuat data...</div>
            </div>
        </div>
    </div>

    <script>
    let opnameIndex = 0;

    function bukaModalFormOpname() {
        document.getElementById('modal-opname').style.display = 'flex';
    }

    function tutupModalOpname() {
        document.getElementById('modal-opname').style.display = 'none';
    }

    function tambahBarisItem() {
        const select = document.getElementById('pilih-item-select');
        const val = select.value;
        if (!val) return;

        const [jenis, id, nama, stok, satuan] = val.split('|');
        const emptyRow = document.getElementById('empty-row');
        if (emptyRow) emptyRow.remove();

        const tbody = document.getElementById('opname-tbody');
        const tr = document.createElement('tr');
        tr.id = 'row-opname-' + opnameIndex;

        tr.innerHTML = `
            <td>
                <strong>${nama}</strong>
                <div style="font-size:11px; color:var(--text-muted); text-transform:uppercase;">[${jenis}]</div>
                <input type="hidden" name="items[${opnameIndex}][jenis]" value="${jenis}">
                <input type="hidden" name="items[${opnameIndex}][id_item]" value="${id}">
                <input type="hidden" name="items[${opnameIndex}][stok_sistem]" id="sistem-${opnameIndex}" value="${stok}">
            </td>
            <td class="text-center font-bold">${stok} ${satuan}</td>
            <td>
                <input type="number" name="items[${opnameIndex}][stok_fisik]" id="fisik-${opnameIndex}" class="form-control form-control-sm text-center" value="${stok}" min="0" oninput="hitungSelisih(${opnameIndex})" required>
            </td>
            <td class="text-center font-bold" id="selisih-badge-${opnameIndex}">
                <span class="badge badge-success">0</span>
            </td>
            <td>
                <select name="items[${opnameIndex}][keterangan]" class="form-select form-select-sm">
                    <option value="lainnya">Sesuai / Standar</option>
                    <option value="hilang">Barang Hilang</option>
                    <option value="rusak">Barang Rusak / Bocor</option>
                    <option value="selisih_hitung">Selisih Hitung / Administrasi</option>
                </select>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-ghost btn-sm text-danger" onclick="hapusBaris(${opnameIndex})">&times;</button>
            </td>
        `;

        tbody.appendChild(tr);
        opnameIndex++;
    }

    function hitungSelisih(idx) {
        const sistem = parseInt(document.getElementById('sistem-' + idx).value) || 0;
        const fisik = parseInt(document.getElementById('fisik-' + idx).value) || 0;
        const diff = fisik - sistem;
        const badge = document.getElementById('selisih-badge-' + idx);

        if (diff === 0) {
            badge.innerHTML = '<span class="badge badge-success">0</span>';
        } else if (diff > 0) {
            badge.innerHTML = `<span class="badge badge-mint">+${diff}</span>`;
        } else {
            badge.innerHTML = `<span class="badge badge-danger">${diff}</span>`;
        }
    }

    function hapusBaris(idx) {
        const row = document.getElementById('row-opname-' + idx);
        if (row) row.remove();
    }

    function lihatDetailOpname(id) {
        document.getElementById('detail-opname-title').innerText = 'Detail Stock Opname #' + id;
        document.getElementById('modal-detail-opname').style.display = 'flex';
        // Ambil data via endpoint atau embed
        fetch('<?= base_url('api/resep_antrian.php') ?>') // Dummy fetch or dynamic view
            .then(() => {
                document.getElementById('detail-opname-body').innerHTML = `
                    <div class="alert alert-info">Stock opname #${id} tercatat dalam database. Periksa riwayat selisih dan logs untuk penyesuaian.</div>
                `;
            });
    }
    </script>
    <?php
}, 'stock_opname');

