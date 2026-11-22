<?php
/**
 * DENTARA - Portal Admin: Kelola Jadwal Praktik & Cuti Dokter
 * File: admin/jadwal_dokter.php
 */

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout_staff.php';

require_role(['admin']);

// Handle POST: Tambah Jadwal / Hapus Jadwal / Tambah Cuti
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        set_flash('error', 'Token Keamanan Kadaluarsa', 'Silakan coba lagi.');
        header('Location: ' . base_url('admin/jadwal_dokter.php'));
        exit;
    }

    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'tambah_jadwal') {
            $id_dokter = (int)($_POST['id_dokter'] ?? 0);
            $hari = $_POST['hari'] ?? 'Senin';
            $jam_mulai = $_POST['jam_mulai'] ?? '09:00';
            $jam_selesai = $_POST['jam_selesai'] ?? '12:00';
            $kuota = (int)($_POST['kuota_per_hari'] ?? 10);

            if ($id_dokter <= 0) throw new Exception('Pilih dokter terlebih dahulu.');

            $stmt = $pdo->prepare("
                INSERT INTO jadwal_praktik_dokter (id_dokter, hari, jam_mulai, jam_selesai, kuota_per_hari, status)
                VALUES (?, ?, ?, ?, ?, 'aktif')
            ");
            $stmt->execute([$id_dokter, $hari, $jam_mulai, $jam_selesai, $kuota]);
            catat_log($pdo, 'Tambah Jadwal Dokter', "Menambahkan jadwal $hari ($jam_mulai - $jam_selesai) dokter #$id_dokter");
            set_flash('success', 'Jadwal Ditambahkan', "Jadwal praktik hari $hari berhasil disimpan.");

        } elseif ($action === 'hapus_jadwal') {
            $id_jadwal = (int)($_POST['id_jadwal'] ?? 0);
            $stmt = $pdo->prepare("DELETE FROM jadwal_praktik_dokter WHERE id_jadwal = ?");
            $stmt->execute([$id_jadwal]);
            catat_log($pdo, 'Hapus Jadwal Dokter', "Menghapus jadwal praktik #$id_jadwal");
            set_flash('success', 'Jadwal Dihapus', 'Jadwal praktik telah dihapus dari sistem.');

        } elseif ($action === 'tambah_cuti') {
            $id_dokter = (int)($_POST['id_dokter'] ?? 0);
            $mulai = $_POST['tanggal_mulai'] ?? '';
            $selesai = $_POST['tanggal_selesai'] ?? '';
            $alasan = trim($_POST['alasan'] ?? '');

            if ($id_dokter <= 0 || empty($mulai) || empty($selesai)) {
                throw new Exception('Data cuti dokter tidak lengkap.');
            }

            $stmt = $pdo->prepare("
                INSERT INTO cuti_dokter (id_dokter, tanggal_mulai, tanggal_selesai, alasan, status)
                VALUES (?, ?, ?, ?, 'aktif')
            ");
            $stmt->execute([$id_dokter, $mulai, $selesai, $alasan]);
            catat_log($pdo, 'Catat Cuti Dokter', "Mencatat cuti dokter #$id_dokter ($mulai s.d $selesai)");
            set_flash('success', 'Cuti Disimpan', 'Jadwal cuti dokter berhasil disimpan dan akan otomatis menonaktifkan slot booking online.');

        } elseif ($action === 'hapus_cuti') {
            $id_cuti = (int)($_POST['id_cuti'] ?? 0);
            $stmt = $pdo->prepare("DELETE FROM cuti_dokter WHERE id_cuti = ?");
            $stmt->execute([$id_cuti]);
            catat_log($pdo, 'Hapus Cuti Dokter', "Menghapus riwayat cuti #$id_cuti");
            set_flash('success', 'Cuti Dihapus', 'Jadwal cuti telah dibatalkan.');
        }
    } catch (Exception $e) {
        set_flash('error', 'Gagal Memproses Data', $e->getMessage());
    }

    header('Location: ' . base_url('admin/jadwal_dokter.php'));
    exit;
}

render_staff_layout('Jadwal Praktik & Cuti Dokter', function() {
    global $pdo;

    $id_filter_dokter = (int)($_GET['id_dokter'] ?? 0);

    // Ambil daftar dokter
    $dokters = $pdo->query("
        SELECT d.id_dokter, u.nama_lengkap, d.spesialisasi
        FROM dokter d
        JOIN pengguna u ON d.id_pengguna = u.id_pengguna
        WHERE u.status = 'aktif'
        ORDER BY u.nama_lengkap ASC
    ")->fetchAll();

    // Query Jadwal Praktik
    $sql_j = "
        SELECT j.*, u.nama_lengkap, d.spesialisasi
        FROM jadwal_praktik_dokter j
        JOIN dokter d ON j.id_dokter = d.id_dokter
        JOIN pengguna u ON d.id_pengguna = u.id_pengguna
        WHERE 1=1
    ";
    $params_j = [];
    if ($id_filter_dokter > 0) {
        $sql_j .= " AND j.id_dokter = ?";
        $params_j[] = $id_filter_dokter;
    }
    $sql_j .= " ORDER BY FIELD(j.hari, 'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'), j.jam_mulai ASC";

    $stmt_j = $pdo->prepare($sql_j);
    $stmt_j->execute($params_j);
    $jadwals = $stmt_j->fetchAll();

    // Query Cuti Dokter
    $cutis = $pdo->query("
        SELECT c.*, u.nama_lengkap, d.spesialisasi
        FROM cuti_dokter c
        JOIN dokter d ON c.id_dokter = d.id_dokter
        JOIN pengguna u ON d.id_pengguna = u.id_pengguna
        ORDER BY c.tanggal_mulai DESC
        LIMIT 10
    ")->fetchAll();
    ?>

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 style="font-family:var(--font-heading); margin:0;">Pengaturan Jadwal Praktik & Cuti Dokter</h1>
            <p class="text-muted" style="margin:0;">Atur kuota harian konsultasi dan blokir tanggal cuti sementara.</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-primary" onclick="bukaModalJadwal()">
                <i class="bx bx-calendar-plus"></i> Tambah Jadwal Praktik
            </button>
            <button type="button" class="btn btn-warning" onclick="bukaModalCuti()">
                <i class="bx bx-calendar-x"></i> Catat Cuti Dokter
            </button>
        </div>
    </div>

    <!-- Filter Dokter -->
    <div class="card p-3 mb-4" style="background:var(--surface);">
        <form method="GET" class="filter-bar d-flex align-items-center gap-2">
            <label class="form-label font-bold mb-0 d-flex align-items-center gap-1 text-muted" style="font-size:13px;">
                <i class="bx bx-filter-alt text-primary"></i> Filter Dokter:
            </label>
            <select name="id_dokter" class="form-select" style="max-width:320px;" onchange="this.form.submit()">
                <option value="0">Semua Dokter</option>
                <?php foreach ($dokters as $doc): ?>
                    <option value="<?= $doc['id_dokter'] ?>" <?= $id_filter_dokter === (int)$doc['id_dokter'] ? 'selected' : '' ?>>
                        Dr. <?= e($doc['nama_lengkap']) ?> (<?= e($doc['spesialisasi']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if ($id_filter_dokter > 0): ?>
                <a href="<?= base_url('admin/jadwal_dokter.php') ?>" class="btn btn-secondary" style="height:42px;">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- SEKSI 1: TABEL JADWAL PRAKTIK MINGGUAN -->
    <div class="card p-0 mb-4" style="background:var(--surface); overflow:hidden;">
        <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-light">
            <h3 style="font-family:var(--font-heading); font-size:var(--text-lg); margin:0;">
                <i class="bx bxs-calendar-week text-primary"></i> Jadwal Mingguan Dokter
            </h3>
            <span class="badge badge-mint"><?= count($jadwals) ?> Sesi Terdaftar</span>
        </div>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th style="width:130px;">Hari</th>
                        <th style="width:250px; min-width:220px;">Dokter Spesialis</th>
                        <th style="width:220px; min-width:180px;">Jam Praktik</th>
                        <th class="text-center" style="width:140px;">Kuota / Hari</th>
                        <th class="text-center" style="width:110px;">Status</th>
                        <th class="text-center" style="width:90px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($jadwals)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">Belum ada jadwal praktik terdaftar.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($jadwals as $j): ?>
                            <tr>
                                <td>
                                    <strong class="text-primary font-bold" style="font-size:var(--text-base); white-space:nowrap;">
                                        <i class="bx bx-calendar"></i> <?= e($j['hari']) ?>
                                    </strong>
                                </td>
                                <td>
                                    <strong style="color:var(--text-main); font-size:14px;">Dr. <?= e($j['nama_lengkap']) ?></strong>
                                    <div class="text-muted" style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px;"><?= e($j['spesialisasi']) ?></div>
                                </td>
                                <td>
                                    <span class="badge badge-secondary" style="font-size:12px; font-weight:700; white-space:nowrap;">
                                        <i class="bx bx-time"></i> <?= substr($j['jam_mulai'], 0, 5) ?> - <?= substr($j['jam_selesai'], 0, 5) ?> WIB
                                    </span>
                                </td>
                                <td class="text-center font-bold">
                                    <span class="badge badge-mint"><?= (int)$j['kuota_per_hari'] ?> Pasien</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-success">Aktif</span>
                                </td>
                                <td class="text-center">
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Hapus jadwal hari <?= e($j['hari']) ?> untuk Dr. <?= e($j['nama_lengkap']) ?>?')">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="hapus_jadwal">
                                        <input type="hidden" name="id_jadwal" value="<?= $j['id_jadwal'] ?>">
                                        <button type="submit" class="btn btn-ghost btn-sm text-danger" title="Hapus Jadwal">
                                            <i class="bx bx-trash" style="font-size:18px;"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- SEKSI 2: DAFTAR CUTI DOKTER AKTIF -->
    <div class="card p-0 mb-4" style="background:var(--surface); overflow:hidden;">
        <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-light">
            <h3 style="font-family:var(--font-heading); font-size:var(--text-lg); margin:0;">
                <i class="bx bxs-user-x text-warning"></i> Daftar Cuti & Izin Dokter Terkini
            </h3>
            <span class="badge badge-warning"><?= count($cutis) ?> Izin Cuti</span>
        </div>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th style="width:260px; min-width:220px;">Dokter</th>
                        <th style="width:280px; min-width:250px;">Periode Cuti</th>
                        <th style="min-width:240px;">Alasan</th>
                        <th class="text-center" style="width:120px;">Status</th>
                        <th class="text-center" style="width:90px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($cutis)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">Tidak ada dokter yang sedang mengajukan cuti saat ini.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($cutis as $c): ?>
                            <tr>
                                <td>
                                    <strong style="color:var(--text-main); font-size:14px;">Dr. <?= e($c['nama_lengkap']) ?></strong>
                                    <div class="text-muted" style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px;"><?= e($c['spesialisasi']) ?></div>
                                </td>
                                <td>
                                    <div class="d-inline-flex align-items-center gap-1" style="background:#FFFBEB; border:1.5px solid #FDE68A; padding:5px 12px; border-radius:8px; font-size:12.5px; font-weight:700; color:#B45309; white-space:nowrap;">
                                        <i class="bx bx-calendar"></i> <?= tanggal_indo($c['tanggal_mulai']) ?> s.d <?= tanggal_indo($c['tanggal_selesai']) ?>
                                    </div>
                                </td>
                                <td style="font-size:13.5px; line-height:1.45;"><?= e($c['alasan'] ?: '-') ?></td>
                                <td class="text-center"><span class="badge badge-warning"><i class="bx bx-pause-circle"></i> Sedang Cuti</span></td>
                                <td class="text-center">
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Batalkan izin cuti ini?')">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="hapus_cuti">
                                        <input type="hidden" name="id_cuti" value="<?= $c['id_cuti'] ?>">
                                        <button type="submit" class="btn btn-ghost btn-sm text-danger" title="Batalkan Cuti">
                                            <i class="bx bx-trash" style="font-size:18px;"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Tambah Jadwal Praktik -->
    <div id="modal-jadwal" class="modal-backdrop" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
        <div class="card p-4" style="max-width:500px; width:92%; background:var(--surface);">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 style="margin:0; font-family:var(--font-heading);"><i class="bx bx-calendar-plus text-primary"></i> Tambah Jadwal Praktik</h4>
                <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('modal-jadwal').style.display='none'">&times;</button>
            </div>

            <form method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="tambah_jadwal">

                <div class="mb-3">
                    <label class="form-label font-bold">Pilih Dokter <span class="text-danger">*</span></label>
                    <select name="id_dokter" class="form-select" required>
                        <?php foreach ($dokters as $doc): ?>
                            <option value="<?= $doc['id_dokter'] ?>">Dr. <?= e($doc['nama_lengkap']) ?> (<?= e($doc['spesialisasi']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label font-bold">Hari</label>
                        <select name="hari" class="form-select" required>
                            <option value="Senin">Senin</option>
                            <option value="Selasa">Selasa</option>
                            <option value="Rabu">Rabu</option>
                            <option value="Kamis">Kamis</option>
                            <option value="Jumat">Jumat</option>
                            <option value="Sabtu">Sabtu</option>
                            <option value="Minggu">Minggu</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label font-bold">Kuota Pasien / Hari</label>
                        <input type="number" name="kuota_per_hari" class="form-control" value="12" min="1" required>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-6">
                        <label class="form-label font-bold">Jam Mulai</label>
                        <input type="time" name="jam_mulai" class="form-control" value="09:00" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label font-bold">Jam Selesai</label>
                        <input type="time" name="jam_selesai" class="form-control" value="13:00" required>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('modal-jadwal').style.display='none'">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Jadwal</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Tambah Cuti Dokter -->
    <div id="modal-cuti" class="modal-backdrop" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
        <div class="card p-4" style="max-width:500px; width:92%; background:var(--surface);">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 style="margin:0; font-family:var(--font-heading);"><i class="bx bx-calendar-x text-warning"></i> Catat Cuti Dokter</h4>
                <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('modal-cuti').style.display='none'">&times;</button>
            </div>

            <form method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="tambah_cuti">

                <div class="mb-3">
                    <label class="form-label font-bold">Pilih Dokter <span class="text-danger">*</span></label>
                    <select name="id_dokter" class="form-select" required>
                        <?php foreach ($dokters as $doc): ?>
                            <option value="<?= $doc['id_dokter'] ?>">Dr. <?= e($doc['nama_lengkap']) ?> (<?= e($doc['spesialisasi']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label font-bold">Tanggal Mulai Cuti</label>
                        <input type="date" name="tanggal_mulai" class="form-control" required value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-6">
                        <label class="form-label font-bold">Tanggal Selesai Cuti</label>
                        <input type="date" name="tanggal_selesai" class="form-control" required value="<?= date('Y-m-d', strtotime('+2 days')) ?>">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label font-bold">Alasan / Keterangan</label>
                    <textarea name="alasan" class="form-control" rows="2" placeholder="Contoh: Menghadiri Seminar Nasional / Urusan Pribadi"></textarea>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('modal-cuti').style.display='none'">Batal</button>
                    <button type="submit" class="btn btn-warning font-bold">Simpan Cuti</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function bukaModalJadwal() {
        document.getElementById('modal-jadwal').style.display = 'flex';
    }
    function bukaModalCuti() {
        document.getElementById('modal-cuti').style.display = 'flex';
    }
    </script>
    <?php
}, 'jadwal_dokter');

