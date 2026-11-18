<?php
/**
 * DENTARA - Portal Apoteker: Detail Resep Obat (Read-Only)
 * File: apoteker/detail_resep.php
 */

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout_staff.php';

require_role(['apoteker', 'admin', 'dokter']);

$id_resep = (int)($_GET['id_resep'] ?? 0);
if ($id_resep <= 0) {
    set_flash('error', 'Resep Tidak Ditemukan', 'ID Resep tidak valid.');
    header('Location: ' . base_url('apoteker/antrean_resep.php'));
    exit;
}

// Ambil data resep utama
$stmt = $pdo->prepare("
    SELECT 
        r.*,
        rm.id_pasien, rm.id_dokter, rm.id_antrian, rm.tanggal_periksa,
        rm.subjektif, rm.objektif, rm.asesmen, rm.plan,
        COALESCE(p.nama_pasien, res.nama_tamu) as nama_pasien,
        p.no_rm, p.nik, p.tgl_lahir, p.jenis_kelamin, p.no_hp, p.alergi_obat, p.kondisi_medis,
        u.nama_lengkap as nama_dokter, d.spesialisasi, d.no_str,
        a.nomor_antrian, a.prioritas
    FROM resep r
    JOIN rekam_medis rm ON r.id_rekam = rm.id_rekam
    LEFT JOIN pasien p ON rm.id_pasien = p.id_pasien
    LEFT JOIN antrian a ON rm.id_antrian = a.id_antrian
    LEFT JOIN reservasi res ON a.id_reservasi = res.id_reservasi
    JOIN dokter d ON rm.id_dokter = d.id_dokter
    JOIN pengguna u ON d.id_pengguna = u.id_pengguna
    WHERE r.id_resep = ?
");
$stmt->execute([$id_resep]);
$resep = $stmt->fetch();

if (!$resep) {
    set_flash('error', 'Resep Tidak Ditemukan', 'Data resep tidak ada dalam sistem.');
    header('Location: ' . base_url('apoteker/antrean_resep.php'));
    exit;
}

// Ambil item obat dalam resep
$stmt_items = $pdo->prepare("
    SELECT rd.*, o.nama_obat, o.kategori, o.satuan, o.stok, o.harga_jual
    FROM resep_detail rd
    JOIN obat o ON rd.id_obat = o.id_obat
    WHERE rd.id_resep = ?
");
$stmt_items->execute([$id_resep]);
$items = $stmt_items->fetchAll();

render_staff_layout('Detail Resep #' . $id_resep, function() use ($resep, $items) {
    global $pdo;
    ?>
    <div class="mb-4">
        <a href="<?= base_url('apoteker/antrean_resep.php') ?>" class="btn btn-ghost btn-sm mb-2">
            <i class="bx bx-left-arrow-alt"></i> Kembali ke Antrean Resep
        </a>
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h1 style="font-family:var(--font-heading); margin:0;">Lembar Resep Dokter #<?= $resep['id_resep'] ?></h1>
                <span class="text-muted" style="font-size:var(--text-sm);">Dibuat pada: <?= format_datetime_indo($resep['created_at']) ?></span>
            </div>
            <div>
                <?php
                $status_class = match($resep['status']) {
                    'antrean' => 'badge-warning',
                    'disiapkan' => 'badge-mint',
                    'selesai' => 'badge-success',
                    'dikembalikan' => 'badge-danger',
                    default => 'badge-secondary'
                };
                ?>
                <span class="badge <?= $status_class ?>" style="font-size:var(--text-sm); padding:6px 14px;">
                    Status: <?= ucfirst($resep['status']) ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Alert Alergi Obat Pasien -->
    <?php if (!empty($resep['alergi_obat'])): ?>
        <div class="card p-3 mb-4 alert alert-danger d-flex align-items-center gap-3">
            <div style="font-size:32px;"><i class="bx bxs-shield-x"></i></div>
            <div>
                <h4 style="margin:0; font-family:var(--font-heading);">PERHATIAN KHUSUS: PASIEN MEMILIKI RIWAYAT ALERGI!</h4>
                <div><strong>Alergi Obat:</strong> <?= e($resep['alergi_obat']) ?></div>
                <?php if (!empty($resep['kondisi_medis'])): ?>
                    <div><strong>Kondisi Medis Penyerta:</strong> <?= e($resep['kondisi_medis']) ?></div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="row g-4 mb-4">
        <!-- Informasi Pasien & Dokter -->
        <div class="col-12 col-lg-6">
            <div class="card p-4 h-100" style="background:var(--surface);">
                <h3 style="font-family:var(--font-heading); font-size:var(--text-lg); margin-bottom:1rem;" class="border-bottom pb-2">
                    <i class="bx bxs-user-detail text-primary"></i> Data Pasien
                </h3>
                <div class="row g-2" style="font-size:var(--text-sm);">
                    <div class="col-4 text-muted">Nama Pasien:</div>
                    <div class="col-8 font-bold"><?= e($resep['nama_pasien']) ?></div>

                    <div class="col-4 text-muted">No. Rekam Medis:</div>
                    <div class="col-8"><strong><?= e($resep['no_rm'] ?: '-') ?></strong></div>

                    <div class="col-4 text-muted">Usia / JK:</div>
                    <div class="col-8"><?= hitung_usia($resep['tgl_lahir']) ?> (<?= $resep['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?>)</div>

                    <div class="col-4 text-muted">Nomor Antrean:</div>
                    <div class="col-8"><span class="badge badge-primary">#<?= e($resep['nomor_antrian']) ?></span></div>

                    <div class="col-4 text-muted">No. Kontak / WA:</div>
                    <div class="col-8"><?= e($resep['no_hp'] ?: '-') ?></div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card p-4 h-100" style="background:var(--surface);">
                <h3 style="font-family:var(--font-heading); font-size:var(--text-lg); margin-bottom:1rem;" class="border-bottom pb-2">
                    <i class="bx bxs-user-voice text-primary"></i> Dokter Penulis Resep
                </h3>
                <div class="row g-2" style="font-size:var(--text-sm);">
                    <div class="col-4 text-muted">Dokter:</div>
                    <div class="col-8 font-bold">Dr. <?= e($resep['nama_dokter']) ?></div>

                    <div class="col-4 text-muted">Spesialisasi:</div>
                    <div class="col-8"><?= e($resep['spesialisasi']) ?></div>

                    <div class="col-4 text-muted">Nomor STR:</div>
                    <div class="col-8"><?= e($resep['no_str'] ?: '-') ?></div>

                    <div class="col-4 text-muted">Tanggal Periksa:</div>
                    <div class="col-8"><?= tanggal_indo($resep['tanggal_periksa'], true) ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Rincian Obat (Read-Only) -->
    <div class="card p-4 mb-4" style="background:var(--surface);">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 style="font-family:var(--font-heading); font-size:var(--text-lg); margin:0;">
                <i class="bx bxs-capsule text-primary"></i> Daftar Item Obat Resep
            </h3>
            <span class="badge badge-secondary"><?= count($items) ?> Item Obat</span>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width:40px;">No</th>
                        <th>Nama Obat</th>
                        <th>Kategori</th>
                        <th class="text-center">Jumlah Resep</th>
                        <th class="text-center">Stok Apotek Saat Ini</th>
                        <th>Dosis & Aturan Pakai (Signa)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">Tidak ada rincian item obat pada resep ini.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($items as $idx => $it): 
                            $stok_cukup = $it['stok'] >= $it['jumlah'];
                        ?>
                            <tr>
                                <td><?= $idx + 1 ?></td>
                                <td>
                                    <strong><?= e($it['nama_obat']) ?></strong>
                                </td>
                                <td><span class="badge badge-secondary"><?= e($it['kategori']) ?></span></td>
                                <td class="text-center font-bold" style="font-size:var(--text-base);">
                                    <?= e($it['jumlah']) ?> <?= e($it['satuan']) ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge <?= $stok_cukup ? 'badge-success' : 'badge-danger' ?>">
                                        <?= (int)$it['stok'] ?> <?= e($it['satuan']) ?>
                                    </span>
                                    <?php if (!$stok_cukup): ?>
                                        <div style="font-size:11px; color:var(--danger); font-weight:700;">Stok Kurang!</div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="p-2 bg-light rounded" style="font-weight:600; color:var(--text-color);">
                                        <?= e($it['aturan_pakai'] ?: $it['dosis']) ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if (!empty($resep['catatan_apoteker'])): ?>
            <div class="mt-3 p-3 bg-light rounded" style="border-left:4px solid var(--accent-orange);">
                <strong>Catatan Petugas Apotek:</strong>
                <div><?= nl2br(e($resep['catatan_apoteker'])) ?></div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Tombol Aksi Apoteker -->
    <div class="card p-3 d-flex justify-content-between align-items-center flex-row flex-wrap gap-2" style="background:var(--surface);">
        <div>
            <button type="button" class="btn btn-secondary btn-sm" onclick="window.print()">
                <i class="bx bx-printer"></i> Cetak Lembar / Etiket
            </button>
        </div>

        <div class="d-flex gap-2">
            <?php if ($resep['status'] === 'antrean'): ?>
                <form method="POST" action="<?= base_url('apoteker/antrean_resep.php') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id_resep" value="<?= $resep['id_resep'] ?>">
                    <input type="hidden" name="action" value="siapkan">
                    <button type="submit" class="btn btn-secondary">
                        <i class="bx bxs-flask"></i> Mulai Siapkan Obat
                    </button>
                </form>
            <?php elseif ($resep['status'] === 'disiapkan'): ?>
                <form method="POST" action="<?= base_url('apoteker/antrean_resep.php') ?>" onsubmit="return confirm('Selesaikan peracikan resep ini? Stok akan otomatis dipotong.')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id_resep" value="<?= $resep['id_resep'] ?>">
                    <input type="hidden" name="action" value="selesai">
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-check-double"></i> Selesai (Siap Diambil)
                    </button>
                </form>
            <?php endif; ?>

            <?php if ($resep['status'] !== 'selesai'): ?>
                <button type="button" class="btn btn-danger" onclick="modalKembalikan()">
                    <i class="bx bx-undo"></i> Kembalikan ke Dokter
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Kembalikan -->
    <div id="modal-kembalikan" class="modal-backdrop" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
        <div class="card p-4" style="max-width:500px; width:90%; background:var(--surface);">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 style="margin:0; font-family:var(--font-heading);"><i class="bx bx-undo text-danger"></i> Kembalikan ke Dokter</h4>
                <button type="button" class="btn btn-ghost btn-sm" onclick="closeModalKembalikan()">&times;</button>
            </div>
            <p class="text-muted" style="font-size:var(--text-sm);">Berikan alasan wajib mengapa resep ini dikembalikan (misal: stok obat habis di apotek).</p>
            
            <form method="POST" action="<?= base_url('apoteker/antrean_resep.php') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="id_resep" value="<?= $resep['id_resep'] ?>">
                <input type="hidden" name="action" value="kembalikan">
                <div class="mb-3">
                    <label class="form-label font-bold">Alasan Pengembalian <span class="text-danger">*</span></label>
                    <textarea name="catatan_apoteker" class="form-control" rows="3" required placeholder="Contoh: Stok obat tidak mencukupi..."></textarea>
                </div>
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-secondary" onclick="closeModalKembalikan()">Batal</button>
                    <button type="submit" class="btn btn-danger">Kirim Kembali ke Dokter</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function modalKembalikan() {
        document.getElementById('modal-kembalikan').style.display = 'flex';
    }
    function closeModalKembalikan() {
        document.getElementById('modal-kembalikan').style.display = 'none';
    }
    </script>
    <?php
}, 'antrean_resep');

