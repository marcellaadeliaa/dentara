<?php
/**
 * DENTARA - Portal Admin: Pengaturan Klinik & Akun Pembayaran
 * File: admin/pengaturan_klinik.php
 */

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout_staff.php';

require_role(['admin']);

// Handle POST: Update Profil Klinik & Rekening Bank
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        set_flash('error', 'Token Keamanan Kadaluarsa', 'Silakan coba lagi.');
        header('Location: ' . base_url('admin/pengaturan_klinik.php'));
        exit;
    }

    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'update_klinik') {
            $nama_klinik = trim($_POST['nama_klinik'] ?? '');
            $telepon = trim($_POST['telepon'] ?? '');
            $whatsapp = trim($_POST['whatsapp'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $alamat = trim($_POST['alamat'] ?? '');
            $biaya_konsultasi = (float)($_POST['biaya_konsultasi'] ?? 0);
            $biaya_admin = (float)($_POST['biaya_admin'] ?? 0);

            $stmt = $pdo->prepare("
                UPDATE pengaturan_klinik 
                SET nama_klinik = ?, telepon = ?, whatsapp = ?, email = ?, alamat = ?, biaya_konsultasi = ?, biaya_admin = ?
                WHERE id = 1
            ");
            $stmt->execute([$nama_klinik, $telepon, $whatsapp, $email, $alamat, $biaya_konsultasi, $biaya_admin]);
            catat_log($pdo, 'Update Profil Klinik', 'Memperbarui informasi profil klinik dan tarif dasar');
            set_flash('success', 'Profil Tersimpan', 'Informasi klinik dan pengaturan biaya berhasil diperbarui.');

        } elseif ($action === 'tambah_bank') {
            $nama_bank = trim($_POST['nama_bank'] ?? '');
            $no_rek = trim($_POST['no_rekening'] ?? '');
            $atas_nama = trim($_POST['atas_nama'] ?? '');

            if (empty($nama_bank) || empty($no_rek)) throw new Exception('Nama bank dan no rekening wajib diisi.');

            $stmt = $pdo->prepare("
                INSERT INTO pengaturan_pembayaran (nama_bank, no_rekening, atas_nama, status)
                VALUES (?, ?, ?, 'aktif')
            ");
            $stmt->execute([$nama_bank, $no_rek, $atas_nama]);
            catat_log($pdo, 'Tambah Rekening Bank', "Menambah rekening bank $nama_bank ($no_rek)");
            set_flash('success', 'Rekening Ditambahkan', 'Akun pembayaran baru berhasil disimpan.');

        } elseif ($action === 'hapus_bank') {
            $id_bank = (int)($_POST['id_bank'] ?? 0);
            $stmt = $pdo->prepare("DELETE FROM pengaturan_pembayaran WHERE id = ?");
            $stmt->execute([$id_bank]);
            catat_log($pdo, 'Hapus Rekening Bank', "Menghapus akun rekening ID #$id_bank");
            set_flash('success', 'Rekening Dihapus', 'Data rekening berhasil dihapus.');
        }
    } catch (Exception $e) {
        set_flash('error', 'Gagal Memperbarui', $e->getMessage());
    }

    header('Location: ' . base_url('admin/pengaturan_klinik.php'));
    exit;
}

render_staff_layout('Pengaturan Profil Klinik & Pembayaran', function() {
    global $pdo;

    $klinik = $pdo->query("SELECT * FROM pengaturan_klinik LIMIT 1")->fetch();
    $bank_list = $pdo->query("SELECT * FROM pengaturan_pembayaran ORDER BY id ASC")->fetchAll();
    ?>

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 style="font-family:var(--font-heading); margin:0;">Pengaturan Umum Klinik & Akun Bank</h1>
            <p class="text-muted" style="margin:0;">Identitas klinik yang dicetak pada kuitansi, resep, serta konfigurasi tarif dasar auto-billing.</p>
        </div>
    </div>

    <div class="row g-4">
        <!-- FORM PENGATURAN KLINIK -->
        <div class="col-12 col-lg-7">
            <div class="card p-4 h-100" style="background:var(--surface);">
                <h3 style="font-family:var(--font-heading); font-size:var(--text-lg); margin-bottom:1.25rem;" class="border-bottom pb-2">
                    <i class="bx bxs-institution text-primary"></i> Profil Klinik & Tarif Dasar
                </h3>

                <form method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update_klinik">

                    <div class="mb-3">
                        <label class="form-label font-bold">Nama Resmi Klinik <span class="text-danger">*</span></label>
                        <input type="text" name="nama_klinik" class="form-control" required value="<?= e($klinik['nama_klinik'] ?? 'DENTARA DENTAL CLINIC') ?>">
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label font-bold">Nomor Telepon</label>
                            <input type="text" name="telepon" class="form-control" value="<?= e($klinik['telepon'] ?? '(021) 7890-1234') ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label font-bold">Nomor WhatsApp Resmi</label>
                            <input type="text" name="whatsapp" class="form-control" value="<?= e($klinik['whatsapp'] ?? '0812-3456-7890') ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label font-bold">Email Informasi</label>
                        <input type="email" name="email" class="form-control" value="<?= e($klinik['email'] ?? 'halo@dentara.co.id') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label font-bold">Alamat Lengkap Klinik</label>
                        <textarea name="alamat" class="form-control" rows="3"><?= e($klinik['alamat'] ?? 'Jl. Melati Sehat No. 12, Kebayoran Baru, Jakarta Selatan') ?></textarea>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-6">
                            <label class="form-label font-bold">Biaya Konsultasi Standar (Rp)</label>
                            <input type="number" name="biaya_konsultasi" class="form-control" min="0" step="5000" value="<?= (float)($klinik['biaya_konsultasi'] ?? 50000) ?>" required>
                            <small class="text-muted">Muncul otomatis saat dokter membuka rekam medis.</small>
                        </div>
                        <div class="col-6">
                            <label class="form-label font-bold">Biaya Administrasi (Rp)</label>
                            <input type="number" name="biaya_admin" class="form-control" min="0" step="1000" value="<?= (float)($klinik['biaya_admin'] ?? 10000) ?>" required>
                            <small class="text-muted">Biaya pendaftaran / administrasi tagihan.</small>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-save"></i> Simpan Pengaturan Profil
                    </button>
                </form>
            </div>
        </div>

        <!-- REKENING BANK & METODE PEMBAYARAN -->
        <div class="col-12 col-lg-5">
            <div class="card p-4 h-100" style="background:var(--surface);">
                <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                    <h3 style="font-family:var(--font-heading); font-size:var(--text-lg); margin:0;">
                        <i class="bx bxs-bank text-primary"></i> Rekening Pembayaran
                    </h3>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="bukaModalBank()">
                        <i class="bx bx-plus"></i> Tambah Bank
                    </button>
                </div>

                <div class="table-responsive mb-4">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Bank / Channel</th>
                                <th>No Rekening & Atas Nama</th>
                                <th class="text-center" style="width:50px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($bank_list)): ?>
                                <tr>
                                    <td colspan="3" class="text-center py-3 text-muted">Belum ada akun bank disimpan.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($bank_list as $bk): ?>
                                    <tr>
                                        <td><strong><?= e($bk['nama_bank']) ?></strong></td>
                                        <td>
                                            <div class="font-bold text-primary"><?= e($bk['no_rekening']) ?></div>
                                            <div style="font-size:11px; color:var(--text-muted);">a.n <?= e($bk['atas_nama']) ?></div>
                                        </td>
                                        <td class="text-center">
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Hapus rekening ini?')">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="hapus_bank">
                                                <input type="hidden" name="id_bank" value="<?= $bk['id'] ?>">
                                                <button type="submit" class="btn btn-ghost btn-sm text-danger" title="Hapus">
                                                    <i class="bx bx-trash" style="font-size:16px;"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Preview QRIS -->
                <div class="p-3 bg-light rounded text-center">
                    <strong class="d-block mb-1">Standar QRIS Nasional</strong>
                    <img src="<?= base_url('qr.php?data=' . urlencode('00020101021126580016ID.CO.DENTARA.WWW0118936009980000000001520454995802ID5914DENTARA%20DENTAL6007JAKARTA530336054070000')) ?>" alt="QRIS" style="max-width:130px; border-radius:8px; border:1px solid #ddd;">
                    <div class="text-muted mt-2" style="font-size:11px;">Otomatis tampil pada layar kasir & kuitansi pasien.</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Bank -->
    <div id="modal-bank" class="modal-backdrop" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
        <div class="card p-4" style="max-width:450px; width:90%; background:var(--surface);">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 style="margin:0; font-family:var(--font-heading);"><i class="bx bxs-bank text-primary"></i> Tambah Akun Rekening</h4>
                <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('modal-bank').style.display='none'">&times;</button>
            </div>

            <form method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="tambah_bank">

                <div class="mb-3">
                    <label class="form-label font-bold">Nama Bank / Dompet Digital <span class="text-danger">*</span></label>
                    <input type="text" name="nama_bank" class="form-control" required placeholder="Contoh: BCA / Mandiri / BSI">
                </div>

                <div class="mb-3">
                    <label class="form-label font-bold">Nomor Rekening <span class="text-danger">*</span></label>
                    <input type="text" name="no_rekening" class="form-control" required placeholder="Contoh: 123-456-7890">
                </div>

                <div class="mb-4">
                    <label class="form-label font-bold">Atas Nama (Pemilik Rekening) <span class="text-danger">*</span></label>
                    <input type="text" name="atas_nama" class="form-control" required placeholder="Contoh: PT Dentara Sehat Indonesia">
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('modal-bank').style.display='none'">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Rekening</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function bukaModalBank() {
        document.getElementById('modal-bank').style.display = 'flex';
    }
    </script>
    <?php
}, 'pengaturan');

