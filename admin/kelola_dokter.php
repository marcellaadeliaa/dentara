<?php
/**
 * DENTARA - Portal Admin: Kelola Profil & Tarif Fee Dokter Gigi
 * File: admin/kelola_dokter.php
 */

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout_staff.php';

require_role(['admin']);

// Handle Edit Dokter
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        set_flash('error', 'Token Keamanan Kadaluarsa', 'Silakan coba lagi.');
        header('Location: ' . base_url('admin/kelola_dokter.php'));
        exit;
    }

    $id_dokter = (int)($_POST['id_dokter'] ?? 0);
    $spesialisasi = $_POST['spesialisasi'] ?? 'Umum';
    $no_str = trim($_POST['no_str'] ?? '');
    $persen_fee = (float)($_POST['persen_fee'] ?? 25.00);
    $bio = trim($_POST['bio'] ?? '');

    if ($id_dokter > 0) {
        try {
            $stmt = $pdo->prepare("
                UPDATE dokter 
                SET spesialisasi = ?, no_str = ?, persen_fee = ?, bio = ?
                WHERE id_dokter = ?
            ");
            $stmt->execute([$spesialisasi, $no_str, $persen_fee, $bio, $id_dokter]);
            catat_log($pdo, 'Update Profil Dokter', "Mengubah data dokter #$id_dokter (Spesialisasi: $spesialisasi, Fee: $persen_fee%)");
            set_flash('success', 'Profil Dokter Disimpan', 'Data spesialisasi dan persentase fee sharing berhasil diperbarui.');
        } catch (Exception $e) {
            set_flash('error', 'Gagal Memperbarui', $e->getMessage());
        }
    }

    header('Location: ' . base_url('admin/kelola_dokter.php'));
    exit;
}

render_staff_layout('Kelola Dokter Gigi', function() {
    global $pdo;

    $stmt = $pdo->query("
        SELECT 
            d.*,
            u.nama_lengkap, u.username, u.email, u.no_hp, u.status as status_akun,
            COUNT(DISTINCT j.id_jadwal) as total_jadwal,
            COUNT(DISTINCT rm.id_rekam) as total_pasien_periksa
        FROM dokter d
        JOIN pengguna u ON d.id_pengguna = u.id_pengguna
        LEFT JOIN jadwal_praktik_dokter j ON d.id_dokter = j.id_dokter AND j.status = 'aktif'
        LEFT JOIN rekam_medis rm ON d.id_dokter = rm.id_dokter
        GROUP BY d.id_dokter
        ORDER BY d.id_dokter ASC
    ");
    $dokters = $stmt->fetchAll();
    ?>

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 style="font-family:var(--font-heading); margin:0;">Daftar Dokter Spesialis & Pembagian Fee</h1>
            <p class="text-muted" style="margin:0;">Kelola kompetensi STR, bio publik, dan persentase bagi hasil (fee sharing) tindakan.</p>
        </div>
        <div>
            <a href="<?= base_url('admin/jadwal_dokter.php') ?>" class="btn btn-secondary btn-sm">
                <i class="bx bxs-calendar"></i> Kelola Jadwal Praktik
            </a>
            <a href="<?= base_url('admin/fee_sharing.php') ?>" class="btn btn-primary btn-sm">
                <i class="bx bxs-wallet"></i> Hitung Fee Sharing
            </a>
        </div>
    </div>

    <div class="row g-4">
        <?php foreach ($dokters as $d): ?>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card p-4 h-100 d-flex flex-column" style="background:var(--surface); border:1px solid var(--border-color);">
                    <!-- Header Kartu Dokter -->
                    <div class="d-flex align-items-center gap-3 mb-3 border-bottom pb-3">
                        <div style="width:56px; height:56px; border-radius:50%; background:linear-gradient(135deg, var(--primary-teal), var(--accent-blue)); color:#fff; display:flex; align-items:center; justify-content:center; font-size:22px; font-weight:800; font-family:var(--font-heading);">
                            <?= strtoupper(substr($d['nama_lengkap'], 0, 1)) ?>
                        </div>
                        <div style="flex:1;">
                            <h3 style="font-family:var(--font-heading); font-size:var(--text-lg); margin:0;">
                                Dr. <?= e($d['nama_lengkap']) ?>
                            </h3>
                            <span class="badge badge-mint"><?= e($d['spesialisasi']) ?></span>
                        </div>
                    </div>

                    <!-- Detail Spesifikasi -->
                    <div class="mb-3" style="font-size:var(--text-sm);">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">No. STR:</span>
                            <strong><?= e($d['no_str'] ?: '-') ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Fee Sharing Tindakan:</span>
                            <span class="badge badge-warning font-bold" style="font-size:13px;"><?= number_format($d['persen_fee'], 1) ?> %</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Jadwal Praktik Aktif:</span>
                            <strong><?= (int)$d['total_jadwal'] ?> Hari / Pekan</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Total Pasien Dilayani:</span>
                            <strong><?= (int)$d['total_pasien_periksa'] ?> Pasien</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Akun Sistem:</span>
                            <span class="badge <?= $d['status_akun'] === 'aktif' ? 'badge-success' : 'badge-danger' ?>">
                                <?= ucfirst($d['status_akun']) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Bio Singkat -->
                    <div class="p-2 mb-3 bg-light rounded text-muted" style="font-size:12px; line-height:1.4; flex:1;">
                        <?= nl2br(e($d['bio'] ?: 'Belum ada bio atau profil edukasi publik untuk dokter ini.')) ?>
                    </div>

                    <!-- Tombol Aksi -->
                    <div class="mt-auto d-flex gap-2">
                        <button type="button" class="btn btn-secondary btn-sm flex-fill" onclick='bukaModalEditDokter(<?= json_encode($d) ?>)'>
                            <i class="bx bx-edit"></i> Edit Profil & Fee
                        </button>
                        <a href="<?= base_url('admin/jadwal_dokter.php?id_dokter=' . $d['id_dokter']) ?>" class="btn btn-ghost btn-sm text-primary" title="Atur Jadwal">
                            <i class="bx bxs-calendar" style="font-size:18px;"></i>
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Modal Edit Dokter -->
    <div id="modal-edit-dokter" class="modal-backdrop" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
        <div class="card p-4" style="max-width:520px; width:92%; background:var(--surface);">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 style="margin:0; font-family:var(--font-heading);" id="modal-dokter-title">Edit Profil Dokter</h4>
                <button type="button" class="btn btn-ghost btn-sm" onclick="tutupModalDokter()">&times;</button>
            </div>

            <form method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="id_dokter" id="dokter-id" value="">

                <div class="mb-3">
                    <label class="form-label font-bold">Nama Dokter</label>
                    <input type="text" id="dokter-nama" class="form-control" readonly disabled>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label font-bold">Spesialisasi</label>
                        <select name="spesialisasi" id="dokter-spesialisasi" class="form-select">
                            <option value="Umum">Umum</option>
                            <option value="Konservasi">Konservasi Gigi (Sp.KG)</option>
                            <option value="Ortodonti">Ortodonti / Behel (Sp.Ort)</option>
                            <option value="Bedah Mulut">Bedah Mulut (Sp.BM)</option>
                            <option value="Pedodonti">Gigi Anak (Sp.KGA)</option>
                            <option value="Periodonti">Periodonsia (Sp.Perio)</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label font-bold">Persentase Fee (%)</label>
                        <input type="number" name="persen_fee" id="dokter-fee" class="form-control" step="0.5" min="0" max="100" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label font-bold">Nomor STR Dokter</label>
                    <input type="text" name="no_str" id="dokter-str" class="form-control" placeholder="Contoh: STR-12345-2026">
                </div>

                <div class="mb-4">
                    <label class="form-label font-bold">Bio / Deskripsi Publik</label>
                    <textarea name="bio" id="dokter-bio" class="form-control" rows="3" placeholder="Profil edukasi dan pengalaman dokter..."></textarea>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-secondary" onclick="tutupModalDokter()">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function bukaModalEditDokter(d) {
        document.getElementById('modal-dokter-title').innerText = 'Edit Dokter: Dr. ' + d.nama_lengkap;
        document.getElementById('dokter-id').value = d.id_dokter;
        document.getElementById('dokter-nama').value = 'Dr. ' + d.nama_lengkap;
        document.getElementById('dokter-spesialisasi').value = d.spesialisasi;
        document.getElementById('dokter-fee').value = d.persen_fee;
        document.getElementById('dokter-str').value = d.no_str || '';
        document.getElementById('dokter-bio').value = d.bio || '';
        document.getElementById('modal-edit-dokter').style.display = 'flex';
    }

    function tutupModalDokter() {
        document.getElementById('modal-edit-dokter').style.display = 'none';
    }
    </script>
    <?php
}, 'kelola_dokter');

