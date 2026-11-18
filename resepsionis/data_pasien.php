<?php
/**
 * DENTARA - Portal Resepsionis: Kelola Data Pasien & Rekam Medis
 * File: resepsionis/data_pasien.php
 */

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout_staff.php';

require_role(['resepsionis', 'admin']);

// Handle POST: Simpan Pasien Baru / Edit Pasien / Hapus
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        header('Location: ' . base_url('resepsionis/data_pasien.php'));
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'simpan_pasien') {
        $id_pasien     = (int)($_POST['id_pasien'] ?? 0);
        $nama_pasien   = trim($_POST['nama_pasien'] ?? '');
        $nik           = trim($_POST['nik'] ?? '');
        $tgl_lahir     = trim($_POST['tgl_lahir'] ?? '');
        $jenis_kelamin = trim($_POST['jenis_kelamin'] ?? '');
        $gol_darah     = trim($_POST['gol_darah'] ?? '');
        $alamat        = trim($_POST['alamat'] ?? '');
        $no_hp         = trim($_POST['no_hp'] ?? '');
        $alergi        = trim($_POST['alergi_obat'] ?? '');
        $kondisi       = trim($_POST['kondisi_medis'] ?? '');

        if (empty($nama_pasien) || empty($nik) || empty($tgl_lahir) || empty($jenis_kelamin) || empty($alamat)) {
            set_flash('warning', 'Data Belum Lengkap', 'Nama, NIK, tanggal lahir, jenis kelamin, dan alamat wajib diisi.');
            header('Location: ' . base_url('resepsionis/data_pasien.php'));
            exit;
        }

        try {
            if ($id_pasien > 0) {
                // Update
                $stmt_u = $pdo->prepare("
                    UPDATE pasien 
                    SET nama_pasien = ?, nik = ?, tgl_lahir = ?, jenis_kelamin = ?, gol_darah = ?, alamat = ?, no_hp = ?, alergi_obat = ?, kondisi_medis = ?
                    WHERE id_pasien = ?
                ");
                $stmt_u->execute([$nama_pasien, $nik, $tgl_lahir, $jenis_kelamin, $gol_darah ?: null, $alamat, $no_hp, $alergi ?: null, $kondisi ?: null, $id_pasien]);
                catat_log($pdo, 'UPDATE_PASIEN', "Memperbarui data pasien #{$id_pasien} ({$nama_pasien})");
                set_flash('success', 'Data Pasien Diperbarui', "Perubahan data pasien {$nama_pasien} berhasil disimpan.");
            } else {
                // Insert Pasien Baru
                $no_rm = generate_no_rm($pdo);
                $stmt_i = $pdo->prepare("
                    INSERT INTO pasien (no_rm, hubungan_keluarga, nama_pasien, nik, tgl_lahir, jenis_kelamin, gol_darah, alamat, no_hp, alergi_obat, kondisi_medis)
                    VALUES (?, 'Diri Sendiri', ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt_i->execute([$no_rm, $nama_pasien, $nik, $tgl_lahir, $jenis_kelamin, $gol_darah ?: null, $alamat, $no_hp, $alergi ?: null, $kondisi ?: null]);
                $new_id = (int)$pdo->lastInsertId();

                // Bonus gamifikasi awal
                $stmt_p = $pdo->prepare("INSERT INTO gamifikasi_poin (id_pasien, total_poin, jumlah_kunjungan_selesai) VALUES (?, 20, 0)");
                $stmt_p->execute([$new_id]);

                catat_log($pdo, 'TAMBAH_PASIEN', "Mendaftarkan pasien baru {$nama_pasien} No RM: {$no_rm}");
                set_flash('success', 'Pasien Baru Terdaftar', "Pasien {$nama_pasien} berhasil didaftarkan dengan No RM: {$no_rm}");
            }
        } catch (Exception $e) {
            set_flash('error', 'Gagal Simpan', $e->getMessage());
        }
        header('Location: ' . base_url('resepsionis/data_pasien.php'));
        exit;
    }

    if ($action === 'hapus_pasien') {
        $id_pasien = (int)($_POST['id_pasien'] ?? 0);
        try {
            $stmt_d = $pdo->prepare("DELETE FROM pasien WHERE id_pasien = ?");
            $stmt_d->execute([$id_pasien]);
            catat_log($pdo, 'HAPUS_PASIEN', "Menghapus data pasien #{$id_pasien}");
            set_flash('success', 'Pasien Dihapus', 'Data pasien telah dihapus dari sistem.');
        } catch (Exception $e) {
            set_flash('error', 'Gagal Hapus', 'Pasien memiliki riwayat kunjungan medis sehingga tidak dapat dihapus: ' . $e->getMessage());
        }
        header('Location: ' . base_url('resepsionis/data_pasien.php'));
        exit;
    }
}

render_staff_layout('Data Pasien & No Rekam Medis', function() {
    global $pdo;

    $search = trim($_GET['q'] ?? '');

    $sql = "SELECT * FROM pasien WHERE 1=1";
    $params = [];

    if ($search) {
        $sql .= " AND (nama_pasien LIKE ? OR no_rm LIKE ? OR nik LIKE ? OR no_hp LIKE ?)";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
    }
    $sql .= " ORDER BY id_pasien DESC LIMIT 50";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $pasiens = $stmt->fetchAll();
?>
<div class="fade-in">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-4 mb-6">
        <div>
            <h1 style="font-size:var(--text-2xl); margin-bottom:4px;">Master Data Pasien & No RM</h1>
            <p class="text-muted" style="margin:0;">Kelola data induk pasien, pencarian NIK 16 digit, dan riwayat alergi obat.</p>
        </div>
        <button class="btn btn-primary btn-md" onclick="bukaModalPasien()">
            <i class="bx bx-user-plus"></i> Tambah Pasien Baru
        </button>
    </div>

    <!-- Pencarian Cepat -->
    <div class="card p-4 mb-6">
        <form action="<?= base_url('resepsionis/data_pasien.php') ?>" method="GET" class="d-flex gap-2">
            <div class="input-icon-group flex-1">
                <i class="bx bx-search"></i>
                <input type="text" name="q" value="<?= e($search) ?>" class="form-control" placeholder="Cari berdasarkan Nama Pasien, No RM (contoh: RM-2026-0001), NIK, atau No HP...">
            </div>
            <button type="submit" class="btn btn-primary btn-sm"><i class="bx bx-search"></i> Cari Data</button>
            <?php if ($search): ?>
                <a href="<?= base_url('resepsionis/data_pasien.php') ?>" class="btn btn-secondary btn-sm">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Tabel Data Pasien -->
    <div class="table-wrapper">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>No. RM</th>
                    <th>Nama Pasien & NIK</th>
                    <th>Usia & Gender</th>
                    <th>Kontak & Alamat</th>
                    <th>Alergi & Kondisi Medis</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pasiens)): ?>
                <tr>
                    <td colspan="6" class="text-center p-5 text-muted">
                        Tidak ada data pasien yang cocok dengan pencarian Anda.
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($pasiens as $p): ?>
                    <tr>
                        <td>
                            <strong style="font-family:var(--font-heading); font-size:16px; color:var(--primary);">
                                <?= e($p['no_rm']) ?>
                            </strong>
                            <span class="badge badge-mint" style="display:block; width:fit-content; margin-top:2px; font-size:10px;">
                                <?= e($p['hubungan_keluarga']) ?>
                            </span>
                        </td>
                        <td>
                            <div style="font-weight:700; font-size:15px;"><?= e($p['nama_pasien']) ?></div>
                            <span class="text-muted" style="font-size:12px;">NIK: <?= e($p['nik']) ?></span>
                        </td>
                        <td>
                            <div><?= hitung_usia($p['tgl_lahir']) ?></div>
                            <span class="text-muted" style="font-size:12px;">
                                <?= $p['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?> &bull; Gol: <?= e($p['gol_darah'] ?: '-') ?>
                            </span>
                        </td>
                        <td style="font-size:13px;">
                            <div><i class="bx bxl-whatsapp text-success"></i> <?= e($p['no_hp']) ?></div>
                            <span class="text-muted" style="font-size:12px;"><?= substr(e($p['alamat']), 0, 30) ?>...</span>
                        </td>
                        <td>
                            <?php if ($p['alergi_obat']): ?>
                                <span class="badge badge-danger" title="<?= e($p['alergi_obat']) ?>">
                                    <i class="bx bxs-shield-x"></i> Alergi: <?= substr(e($p['alergi_obat']), 0, 18) ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($p['kondisi_medis']): ?>
                                <span class="badge badge-warning" style="display:block; width:fit-content; margin-top:3px;">
                                    <?= substr(e($p['kondisi_medis']), 0, 20) ?>
                                </span>
                            <?php endif; ?>
                            <?php if (!$p['alergi_obat'] && !$p['kondisi_medis']): ?>
                                <span class="text-muted" style="font-size:12px;">Normal / Nihil</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-2">
                                <button type="button" class="btn btn-secondary btn-sm" onclick='editPasien(<?= json_encode($p) ?>)' title="Edit Pasien">
                                    <i class="bx bx-edit"></i>
                                </button>
                                <button type="button" class="btn btn-danger btn-sm" onclick="hapusPasien(<?= $p['id_pasien'] ?>, '<?= e($p['nama_pasien']) ?>')" title="Hapus Pasien">
                                    <i class="bx bx-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Form Tambah / Edit Pasien -->
<div class="modal-backdrop" id="modal-pasien">
    <div class="modal-dialog p-6" style="max-width:680px;">
        <div class="d-flex align-items-center justify-content-between mb-4 pb-3" style="border-bottom:1px solid var(--border-color);">
            <h3 id="modal-title" style="font-size:18px; margin:0;">Pendaftaran Pasien Baru</h3>
            <button type="button" class="btn btn-ghost btn-sm" onclick="tutupModalPasien()"><i class="bx bx-x" style="font-size:24px;"></i></button>
        </div>

        <form action="<?= base_url('resepsionis/data_pasien.php') ?>" method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="simpan_pasien">
            <input type="hidden" name="id_pasien" id="form-id-pasien" value="0">

            <div class="grid grid-cols-2 gap-3 mb-3">
                <div class="form-group mb-0">
                    <label class="form-label required">Nama Lengkap Pasien</label>
                    <input type="text" name="nama_pasien" id="form-nama" class="form-control" required>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label required">Nomor Induk Kependudukan (NIK)</label>
                    <input type="text" name="nik" id="form-nik" class="form-control" maxlength="16" required pattern="[0-9]{16}">
                </div>
            </div>

            <div class="grid grid-cols-3 gap-3 mb-3">
                <div class="form-group mb-0">
                    <label class="form-label required">Tanggal Lahir</label>
                    <input type="date" name="tgl_lahir" id="form-tgl-lahir" class="form-control" required max="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group mb-0">
                    <label class="form-label required">Jenis Kelamin</label>
                    <select name="jenis_kelamin" id="form-gender" class="form-control" required>
                        <option value="L">Laki-laki (L)</option>
                        <option value="P">Perempuan (P)</option>
                    </select>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">Gol. Darah</label>
                    <select name="gol_darah" id="form-goldar" class="form-control">
                        <option value="">-- Pilih --</option>
                        <option value="A">A</option>
                        <option value="B">B</option>
                        <option value="AB">AB</option>
                        <option value="O">O</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 mb-3">
                <div class="form-group mb-0">
                    <label class="form-label required">Nomor WhatsApp / HP</label>
                    <input type="tel" name="no_hp" id="form-hp" class="form-control" required>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">Alamat Lengkap</label>
                    <input type="text" name="alamat" id="form-alamat" class="form-control" required>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 mb-4">
                <div class="form-group mb-0">
                    <label class="form-label text-danger font-bold"><i class="bx bx-shield-x"></i> Riwayat Alergi Obat</label>
                    <input type="text" name="alergi_obat" id="form-alergi" class="form-control" placeholder="Misal: Penisilin (kosongkan jika tidak ada)">
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">Kondisi Medis Khusus</label>
                    <input type="text" name="kondisi_medis" id="form-kondisi" class="form-control" placeholder="Misal: Hipertensi, Asma, Diabetes">
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 pt-3" style="border-top:1px solid var(--border-color);">
                <button type="button" class="btn btn-secondary btn-md" onclick="tutupModalPasien()">Batal</button>
                <button type="submit" class="btn btn-primary btn-md"><i class="bx bx-save"></i> Simpan Data Pasien</button>
            </div>
        </form>
    </div>
</div>

<!-- Form Hapus Pasien Hidden -->
<form id="form-delete" action="<?= base_url('resepsionis/data_pasien.php') ?>" method="POST" style="display:none;">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="hapus_pasien">
    <input type="hidden" name="id_pasien" id="delete-id-pasien">
</form>

<script>
function bukaModalPasien() {
    document.getElementById('modal-title').textContent = 'Pendaftaran Pasien Baru';
    document.getElementById('form-id-pasien').value = '0';
    document.getElementById('form-nama').value = '';
    document.getElementById('form-nik').value = '';
    document.getElementById('form-tgl-lahir').value = '';
    document.getElementById('form-hp').value = '';
    document.getElementById('form-alamat').value = '';
    document.getElementById('form-alergi').value = '';
    document.getElementById('form-kondisi').value = '';
    document.getElementById('modal-pasien').classList.add('is-open');
}

function editPasien(p) {
    document.getElementById('modal-title').textContent = 'Edit Data Pasien: ' + p.nama_pasien;
    document.getElementById('form-id-pasien').value = p.id_pasien;
    document.getElementById('form-nama').value = p.nama_pasien;
    document.getElementById('form-nik').value = p.nik;
    document.getElementById('form-tgl-lahir').value = p.tgl_lahir;
    document.getElementById('form-gender').value = p.jenis_kelamin;
    document.getElementById('form-goldar').value = p.gol_darah || '';
    document.getElementById('form-hp').value = p.no_hp;
    document.getElementById('form-alamat').value = p.alamat;
    document.getElementById('form-alergi').value = p.alergi_obat || '';
    document.getElementById('form-kondisi').value = p.kondisi_medis || '';
    document.getElementById('modal-pasien').classList.add('is-open');
}

function tutupModalPasien() {
    document.getElementById('modal-pasien').classList.remove('is-open');
}

function hapusPasien(id, nama) {
    DENTARA.confirm(
        'Hapus Pasien?',
        `Apakah Anda yakin ingin menghapus data pasien <strong>${nama}</strong>? Data yang telah memiliki rekam medis tidak dapat dihapus.`,
        'Ya, Hapus',
        'Batal'
    ).then(confirmed => {
        if (confirmed) {
            document.getElementById('delete-id-pasien').value = id;
            document.getElementById('form-delete').submit();
        }
    });
}
</script>
<?php
}, 'pasien');

