<?php
/**
 * DENTARA - Portal Admin: Jam Operasional Klinik & Hari Libur Mendadak
 * File: admin/jadwal_klinik.php
 */

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout_staff.php';

require_role(['admin']);

// Handle POST: Update Jam Klinik & Libur Mendadak
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        set_flash('error', 'Token Keamanan Kadaluarsa', 'Silakan coba lagi.');
        header('Location: ' . base_url('admin/jadwal_klinik.php'));
        exit;
    }

    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'update_jam_klinik') {
            $jadwal_data = $_POST['jadwal'] ?? [];
            $stmt = $pdo->prepare("
                UPDATE jadwal_klinik 
                SET status = ?, buka_1 = ?, tutup_1 = ?, buka_2 = ?, tutup_2 = ?
                WHERE id_jadwal_klinik = ?
            ");

            foreach ($jadwal_data as $id => $row) {
                $status = $row['status'] ?? 'Buka';
                $buka_1 = $row['buka_1'] ?? '09:00';
                $tutup_1 = $row['tutup_1'] ?? '13:00';
                $buka_2 = !empty($row['buka_2']) ? $row['buka_2'] : null;
                $tutup_2 = !empty($row['tutup_2']) ? $row['tutup_2'] : null;

                $stmt->execute([$status, $buka_1, $tutup_1, $buka_2, $tutup_2, (int)$id]);
            }

            catat_log($pdo, 'Update Jam Klinik', 'Memperbarui jam operasional 2 sesi klinik');
            set_flash('success', 'Jam Operasional Diperbarui', 'Jadwal operasional klinik berhasil disimpan.');

        } elseif ($action === 'tambah_libur') {
            $tanggal = $_POST['tanggal_libur'] ?? '';
            $alasan = trim($_POST['alasan'] ?? '');

            if (empty($tanggal)) throw new Exception('Pilih tanggal libur.');

            $stmt_l = $pdo->prepare("
                INSERT INTO libur_mendadak (tanggal_libur, alasan, status)
                VALUES (?, ?, 'aktif')
            ");
            $stmt_l->execute([$tanggal, $alasan]);
            catat_log($pdo, 'Tambah Libur Mendadak', "Menambah tanggal libur klinik: $tanggal ($alasan)");
            set_flash('success', 'Hari Libur Ditetapkan', "Tanggal $tanggal berhasil dicatat sebagai hari libur klinik.");

        } elseif ($action === 'toggle_libur') {
            $id_libur = (int)($_POST['id_libur'] ?? 0);
            $stmt_t = $pdo->prepare("UPDATE libur_mendadak SET status = IF(status='aktif', 'nonaktif', 'aktif') WHERE id_libur = ?");
            $stmt_t->execute([$id_libur]);
            catat_log($pdo, 'Ubah Status Libur', "Mengubah status libur mendadak #$id_libur");
            set_flash('success', 'Status Libur Diubah', 'Status keaktifan libur klinik berhasil diperbarui.');

        } elseif ($action === 'hapus_libur') {
            $id_libur = (int)($_POST['id_libur'] ?? 0);
            $stmt_h = $pdo->prepare("DELETE FROM libur_mendadak WHERE id_libur = ?");
            $stmt_h->execute([$id_libur]);
            catat_log($pdo, 'Hapus Libur Mendadak', "Menghapus hari libur #$id_libur");
            set_flash('success', 'Hari Libur Dihapus', 'Jadwal libur telah dihapus.');
        }
    } catch (Exception $e) {
        set_flash('error', 'Gagal Memproses Data', $e->getMessage());
    }

    header('Location: ' . base_url('admin/jadwal_klinik.php'));
    exit;
}

render_staff_layout('Jam Operasional & Libur Klinik', function() {
    global $pdo;

    // Ambil jam operasional 7 hari
    $jadwal_klinik = $pdo->query("SELECT * FROM jadwal_klinik ORDER BY FIELD(hari, 'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu') ASC")->fetchAll();

    // Ambil daftar libur mendadak
    $liburs = $pdo->query("SELECT * FROM libur_mendadak ORDER BY tanggal_libur DESC LIMIT 10")->fetchAll();
    ?>

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 style="font-family:var(--font-heading); margin:0;">Jam Operasional & Kalender Libur</h1>
            <p class="text-muted" style="margin:0;">Atur 2 sesi buka harian klinik dan tentukan hari libur mendadak (cuti bersama / renovasi).</p>
        </div>
        <div>
            <button type="button" class="btn btn-warning" onclick="bukaModalLibur()">
                <i class="bx bx-calendar-event"></i> Tetapkan Libur Mendadak
            </button>
        </div>
    </div>

    <!-- SEKSI 1: JAM OPERASIONAL 2 SESI -->
    <div class="card p-4 mb-4" style="background:var(--surface);">
        <h3 style="font-family:var(--font-heading); font-size:var(--text-lg); margin-bottom:1.25rem;" class="border-bottom pb-2">
            <i class="bx bx-time-five text-primary"></i> Pengaturan Jam Operasional 2 Sesi (Senin - Minggu)
        </h3>

        <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_jam_klinik">

            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th style="width:130px;">Hari</th>
                            <th style="width:150px;">Status Operasional</th>
                            <th style="min-width:260px;">Sesi 1 (Pagi - Siang)</th>
                            <th style="min-width:280px;">Sesi 2 (Sore - Malam)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($jadwal_klinik as $jk): ?>
                            <tr>
                                <td class="font-bold text-primary" style="font-size:var(--text-base); vertical-align:middle;">
                                    <i class="bx bx-calendar text-primary"></i> <?= e($jk['hari']) ?>
                                </td>
                                <td>
                                    <select name="jadwal[<?= $jk['id_jadwal_klinik'] ?>][status]" 
                                            class="form-select form-select-sm select-status <?= $jk['status'] === 'Buka' ? 'status-buka' : 'status-tutup' ?>" 
                                            style="font-weight:700;"
                                            onchange="this.className='form-select form-select-sm select-status ' + (this.value==='Buka'?'status-buka':'status-tutup');">
                                        <option value="Buka" <?= $jk['status'] === 'Buka' ? 'selected' : '' ?>>🟢 Buka</option>
                                        <option value="Tutup" <?= $jk['status'] === 'Tutup' ? 'selected' : '' ?>>🔴 Tutup</option>
                                    </select>
                                </td>
                                <td>
                                    <div class="time-range-box">
                                        <i class="bx bx-sun text-warning" style="font-size:17px;"></i>
                                        <input type="time" name="jadwal[<?= $jk['id_jadwal_klinik'] ?>][buka_1]" value="<?= substr($jk['buka_1'], 0, 5) ?>">
                                        <span class="text-muted font-bold" style="font-size:12px;">s/d</span>
                                        <input type="time" name="jadwal[<?= $jk['id_jadwal_klinik'] ?>][tutup_1]" value="<?= substr($jk['tutup_1'], 0, 5) ?>">
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="time-range-box">
                                            <i class="bx bx-moon text-secondary" style="font-size:17px;"></i>
                                            <input type="time" name="jadwal[<?= $jk['id_jadwal_klinik'] ?>][buka_2]" value="<?= !empty($jk['buka_2']) ? substr($jk['buka_2'], 0, 5) : '' ?>" placeholder="--:--">
                                            <span class="text-muted font-bold" style="font-size:12px;">s/d</span>
                                            <input type="time" name="jadwal[<?= $jk['id_jadwal_klinik'] ?>][tutup_2]" value="<?= !empty($jk['tutup_2']) ? substr($jk['tutup_2'], 0, 5) : '' ?>" placeholder="--:--">
                                        </div>
                                        <span class="badge badge-secondary" style="font-size:11px;">Opsional</span>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end mt-4">
                <button type="submit" class="btn btn-primary px-4 py-2 font-bold">
                    <i class="bx bx-save"></i> Simpan Jam Operasional Klinik
                </button>
            </div>
        </form>
    </div>

    <!-- SEKSI 2: LIBUR MENDADAK -->
    <div class="card p-0 mb-4" style="background:var(--surface); overflow:hidden;">
        <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-light">
            <h3 style="font-family:var(--font-heading); font-size:var(--text-lg); margin:0;">
                <i class="bx bxs-calendar-x text-warning"></i> Kalender Libur Mendadak & Cuti Bersama
            </h3>
            <span class="badge badge-warning"><?= count($liburs) ?> Tanggal</span>
        </div>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th style="width:230px; min-width:200px;">Tanggal Libur</th>
                        <th style="min-width:240px;">Alasan Libur</th>
                        <th style="width:140px;" class="text-center">Status</th>
                        <th style="width:140px;">Dicatat</th>
                        <th class="text-center" style="width:110px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($liburs)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">Belum ada hari libur mendadak terdaftar.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($liburs as $lib): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bx bx-calendar text-danger"></i>
                                        <strong class="text-danger font-bold" style="font-size:13.5px;">
                                            <?= tanggal_indo($lib['tanggal_libur'], true) ?>
                                        </strong>
                                    </div>
                                </td>
                                <td>
                                    <strong style="color:var(--text-main); font-size:13.5px;"><?= e($lib['alasan'] ?: 'Libur Operasional') ?></strong>
                                </td>
                                <td class="text-center">
                                    <span class="badge <?= $lib['status'] === 'aktif' ? 'badge-danger' : 'badge-secondary' ?>">
                                        <?= $lib['status'] === 'aktif' ? 'Libur Berlaku' : 'Nonaktif' ?>
                                    </span>
                                </td>
                                <td style="font-size:12px; color:var(--text-muted); white-space:nowrap;">
                                    <i class="bx bx-time text-muted"></i> <?= tanggal_indo(date('Y-m-d', strtotime($lib['created_at']))) ?>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <form method="POST" class="d-inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="toggle_libur">
                                            <input type="hidden" name="id_libur" value="<?= $lib['id_libur'] ?>">
                                            <button type="submit" class="btn btn-ghost btn-sm text-warning" title="Ubah Status">
                                                <i class="bx bx-power-off" style="font-size:18px;"></i>
                                            </button>
                                        </form>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Hapus tanggal libur ini?')">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="hapus_libur">
                                            <input type="hidden" name="id_libur" value="<?= $lib['id_libur'] ?>">
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

    <!-- Modal Tambah Libur Mendadak -->
    <div id="modal-libur" class="modal-backdrop" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
        <div class="card p-4" style="max-width:500px; width:92%; background:var(--surface);">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 style="margin:0; font-family:var(--font-heading);"><i class="bx bx-calendar-event text-warning"></i> Tetapkan Libur Mendadak</h4>
                <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('modal-libur').style.display='none'">&times;</button>
            </div>

            <form method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="tambah_libur">

                <div class="mb-3">
                    <label class="form-label font-bold">Tanggal Libur <span class="text-danger">*</span></label>
                    <input type="date" name="tanggal_libur" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="mb-4">
                    <label class="form-label font-bold">Alasan / Keterangan Libur <span class="text-danger">*</span></label>
                    <textarea name="alasan" class="form-control" rows="3" required placeholder="Contoh: Hari Raya Idul Fitri / Maintenance Alat Dental & Sterilisasi Total Ruangan"></textarea>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('modal-libur').style.display='none'">Batal</button>
                    <button type="submit" class="btn btn-warning font-bold">Simpan Hari Libur</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function bukaModalLibur() {
        document.getElementById('modal-libur').style.display = 'flex';
    }
    </script>
    <?php
}, 'jadwal_klinik');

