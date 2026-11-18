<?php
/**
 * DENTARA - Portal Apoteker: Antrean Resep Elektronik
 * File: apoteker/antrean_resep.php
 */

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout_staff.php';

require_role(['apoteker', 'admin']);

// Handle Status Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        set_flash('error', 'Token Keamanan Kadaluarsa', 'Silakan coba beberapa saat lagi.');
        header('Location: ' . base_url('apoteker/antrean_resep.php'));
        exit;
    }

    $action = $_POST['action'] ?? '';
    $id_resep = (int)($_POST['id_resep'] ?? 0);
    $catatan = trim($_POST['catatan_apoteker'] ?? '');

    if ($id_resep > 0) {
        try {
            if ($action === 'siapkan') {
                $stmt = $pdo->prepare("UPDATE resep SET status = 'disiapkan', updated_at = CURRENT_TIMESTAMP WHERE id_resep = ?");
                $stmt->execute([$id_resep]);
                catat_log($pdo, 'Mulai Siapkan Resep', "Resep #$id_resep mulai disiapkan");
                set_flash('info', 'Resep Disiapkan', 'Status resep telah diubah menjadi Disiapkan.');
            } elseif ($action === 'selesai') {
                $pdo->beginTransaction();

                // 1. Ambil detail obat dalam resep ini
                $stmt_items = $pdo->prepare("SELECT id_obat, jumlah FROM resep_detail WHERE id_resep = ?");
                $stmt_items->execute([$id_resep]);
                $items = $stmt_items->fetchAll();

                // 2. Kurangi stok obat
                foreach ($items as $item) {
                    $stmt_stok = $pdo->prepare("UPDATE obat SET stok = GREATEST(0, stok - ?) WHERE id_obat = ?");
                    $stmt_stok->execute([$item['jumlah'], $item['id_obat']]);
                }

                // 3. Update status resep
                $stmt_up = $pdo->prepare("UPDATE resep SET status = 'selesai', updated_at = CURRENT_TIMESTAMP WHERE id_resep = ?");
                $stmt_up->execute([$id_resep]);

                $pdo->commit();
                catat_log($pdo, 'Selesai Siapkan Resep', "Resep #$id_resep selesai dan stok obat dipotong");
                set_flash('success', 'Resep Selesai', 'Resep berhasil diselesaikan dan stok obat otomatis dipotong.');
            } elseif ($action === 'kembalikan') {
                if (empty($catatan)) {
                    set_flash('warning', 'Alasan Diperlukan', 'Harap masukkan alasan pengembalian resep ke dokter.');
                } else {
                    $stmt = $pdo->prepare("UPDATE resep SET status = 'dikembalikan', catatan_apoteker = ?, updated_at = CURRENT_TIMESTAMP WHERE id_resep = ?");
                    $stmt->execute([$catatan, $id_resep]);
                    catat_log($pdo, 'Kembalikan Resep', "Resep #$id_resep dikembalikan ke dokter: $catatan");
                    set_flash('warning', 'Resep Dikembalikan', 'Resep telah dikembalikan ke dokter untuk konfirmasi ulang.');
                }
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            set_flash('error', 'Gagal Memproses', $e->getMessage());
        }
    }
    header('Location: ' . base_url('apoteker/antrean_resep.php'));
    exit;
}

render_staff_layout('Antrean Resep Farmasi', function() {
    global $pdo;

    // Ambil resep hari ini atau yang belum selesai
    $stmt = $pdo->prepare("
        SELECT 
            r.id_resep, r.id_rekam, r.status, r.catatan_apoteker, r.created_at,
            rm.tanggal_periksa,
            COALESCE(p.nama_pasien, res.nama_tamu) as nama_pasien,
            p.no_rm, p.alergi_obat,
            u.nama_lengkap as nama_dokter, d.spesialisasi,
            a.nomor_antrian, a.prioritas
        FROM resep r
        JOIN rekam_medis rm ON r.id_rekam = rm.id_rekam
        LEFT JOIN pasien p ON rm.id_pasien = p.id_pasien
        LEFT JOIN antrian a ON rm.id_antrian = a.id_antrian
        LEFT JOIN reservasi res ON a.id_reservasi = res.id_reservasi
        JOIN dokter d ON rm.id_dokter = d.id_dokter
        JOIN pengguna u ON d.id_pengguna = u.id_pengguna
        WHERE (r.status IN ('antrean', 'disiapkan') OR DATE(r.created_at) = CURRENT_DATE)
        ORDER BY 
            CASE r.status 
                WHEN 'antrean' THEN 1 
                WHEN 'disiapkan' THEN 2 
                WHEN 'dikembalikan' THEN 3
                ELSE 4 
            END ASC,
            a.prioritas DESC,
            r.id_resep ASC
    ");
    $stmt->execute();
    $reseps = $stmt->fetchAll();

    // Hitung ringkasan
    $count_antre = 0;
    $count_proses = 0;
    $count_selesai = 0;
    foreach ($reseps as $r) {
        if ($r['status'] === 'antrean') $count_antre++;
        elseif ($r['status'] === 'disiapkan') $count_proses++;
        elseif ($r['status'] === 'selesai') $count_selesai++;
    }
    ?>

    <!-- Status Cards Ringkasan -->
    <div class="row g-4 mb-4">
        <div class="col-12 col-md-4">
            <div class="card p-3 d-flex align-items-center flex-row gap-3" style="border-left: 5px solid var(--accent-orange); background:var(--surface);">
                <div style="width:50px; height:50px; border-radius:12px; background:rgba(255,107,107,0.12); display:flex; align-items:center; justify-content:center; color:var(--accent-orange); font-size:26px;">
                    <i class="bx bxs-time"></i>
                </div>
                <div>
                    <div style="font-size:var(--text-xs); color:var(--text-muted); font-weight:700; text-transform:uppercase;">Resep Antre</div>
                    <div style="font-size:var(--text-3xl); font-weight:800; font-family:var(--font-heading);" id="count-antre"><?= $count_antre ?></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card p-3 d-flex align-items-center flex-row gap-3" style="border-left: 5px solid var(--primary-teal); background:var(--surface);">
                <div style="width:50px; height:50px; border-radius:12px; background:rgba(43,181,160,0.12); display:flex; align-items:center; justify-content:center; color:var(--primary-teal); font-size:26px;">
                    <i class="bx bxs-flask"></i>
                </div>
                <div>
                    <div style="font-size:var(--text-xs); color:var(--text-muted); font-weight:700; text-transform:uppercase;">Sedang Disiapkan</div>
                    <div style="font-size:var(--text-3xl); font-weight:800; font-family:var(--font-heading);" id="count-proses"><?= $count_proses ?></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card p-3 d-flex align-items-center flex-row gap-3" style="border-left: 5px solid var(--success); background:var(--surface);">
                <div style="width:50px; height:50px; border-radius:12px; background:rgba(16,185,129,0.12); display:flex; align-items:center; justify-content:center; color:var(--success); font-size:26px;">
                    <i class="bx bxs-check-circle"></i>
                </div>
                <div>
                    <div style="font-size:var(--text-xs); color:var(--text-muted); font-weight:700; text-transform:uppercase;">Selesai Hari Ini</div>
                    <div style="font-size:var(--text-3xl); font-weight:800; font-family:var(--font-heading);" id="count-selesai"><?= $count_selesai ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Bar Audio / Polling -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <span class="badge badge-mint"><i class="bx bx-radio-circle-marked bx-burst"></i> Polling Otomatis Aktif</span>
            <small class="text-muted">Memeriksa resep baru setiap 8 detik</small>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= base_url('apoteker/stok_obat.php') ?>" class="btn btn-secondary btn-sm">
                <i class="bx bxs-box"></i> Cek Stok Obat
            </a>
            <a href="<?= base_url('apoteker/alert_stok.php') ?>" class="btn btn-warning btn-sm">
                <i class="bx bxs-error-circle"></i> Peringatan Stok
            </a>
        </div>
    </div>

    <!-- Daftar Resep -->
    <?php if (empty($reseps)): ?>
        <div class="card p-5 text-center" style="background:var(--surface);">
            <div style="font-size:4rem; color:var(--text-muted); margin-bottom:1rem;">
                <i class="bx bx-check-shield"></i>
            </div>
            <h3 style="font-family:var(--font-heading);">Tidak Ada Antrean Resep</h3>
            <p class="text-muted">Semua resep dokter hari ini telah selesai diproses atau belum ada pasien selesai periksa.</p>
        </div>
    <?php else: ?>
        <div class="row g-4" id="resep-card-container">
            <?php foreach ($reseps as $resep): 
                // Ambil obat detail
                $stmt_d = $pdo->prepare("
                    SELECT rd.*, o.nama_obat, o.satuan, o.stok
                    FROM resep_detail rd
                    JOIN obat o ON rd.id_obat = o.id_obat
                    WHERE rd.id_resep = ?
                ");
                $stmt_d->execute([$resep['id_resep']]);
                $items = $stmt_d->fetchAll();

                // Status Styling
                $status_class = match($resep['status']) {
                    'antrean' => 'badge-warning',
                    'disiapkan' => 'badge-mint',
                    'selesai' => 'badge-success',
                    'dikembalikan' => 'badge-danger',
                    default => 'badge-secondary'
                };
                $status_label = match($resep['status']) {
                    'antrean' => 'Menunggu Diracik',
                    'disiapkan' => 'Sedang Diracik',
                    'selesai' => 'Siap Diambil / Selesai',
                    'dikembalikan' => 'Dikembalikan ke Dokter',
                    default => ucfirst($resep['status'])
                };
            ?>
                <div class="col-12 col-lg-6">
                    <div class="card h-100 p-4" style="border:1px solid var(--border-color); position:relative;">
                        <!-- Header Kartu -->
                        <div class="d-flex justify-content-between align-items-start mb-3 border-bottom pb-3">
                            <div>
                                <span class="badge <?= $status_class ?> mb-1"><?= $status_label ?></span>
                                <h3 style="font-family:var(--font-heading); margin:0; font-size:var(--text-xl);">
                                    <?= e($resep['nama_pasien']) ?>
                                </h3>
                                <div class="text-muted" style="font-size:var(--text-xs);">
                                    No. RM: <strong><?= e($resep['no_rm'] ?: 'Walk-in / Tamu') ?></strong> | 
                                    Antrean: <strong class="text-primary">#<?= e($resep['nomor_antrian']) ?></strong>
                                    <?php if ($resep['prioritas'] > 0): ?>
                                        <span class="badge badge-danger" style="font-size:10px;">Prioritas Tinggi</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="text-end">
                                <span style="font-size:var(--text-xs); color:var(--text-muted);"><i class="bx bx-time"></i> <?= waktu_indo($resep['created_at']) ?></span>
                                <div style="font-size:var(--text-xs); font-weight:600; color:var(--primary-teal);">Dr. <?= e($resep['nama_dokter']) ?></div>
                            </div>
                        </div>

                        <!-- Peringatan Alergi Pasien -->
                        <?php if (!empty($resep['alergi_obat'])): ?>
                            <div class="alert alert-danger p-2 mb-3 d-flex align-items-center gap-2" style="font-size:var(--text-sm);">
                                <i class="bx bxs-shield-x" style="font-size:20px;"></i>
                                <div>
                                    <strong>PERINGATAN ALERGI OBAT:</strong> <?= e($resep['alergi_obat']) ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Daftar Obat Racikan / Resep -->
                        <div class="mb-3">
                            <div style="font-size:var(--text-xs); font-weight:700; color:var(--text-muted); text-transform:uppercase; margin-bottom:8px;">
                                Rincian Obat (<?= count($items) ?> Item)
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm table-borderless mb-0" style="font-size:var(--text-sm);">
                                    <thead>
                                        <tr style="border-bottom:1px solid var(--border-color); color:var(--text-muted);">
                                            <th>Nama Obat</th>
                                            <th class="text-center">Jumlah</th>
                                            <th>Signa / Aturan Pakai</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($items as $item): ?>
                                            <tr style="border-bottom:1px dashed var(--border-color);">
                                                <td>
                                                    <strong><?= e($item['nama_obat']) ?></strong>
                                                    <div style="font-size:11px; color:var(--text-muted);">Stok: <?= (int)$item['stok'] ?> <?= e($item['satuan']) ?></div>
                                                </td>
                                                <td class="text-center font-bold">
                                                    <?= e($item['jumlah']) ?> <?= e($item['satuan']) ?>
                                                </td>
                                                <td>
                                                    <span class="badge badge-secondary" style="font-size:11px;"><?= e($item['aturan_pakai'] ?: $item['dosis']) ?></span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Catatan Apoteker Jika Ada -->
                        <?php if (!empty($resep['catatan_apoteker'])): ?>
                            <div class="p-2 mb-3 bg-light rounded" style="font-size:var(--text-xs); border-left:3px solid var(--primary-teal);">
                                <strong>Catatan:</strong> <?= e($resep['catatan_apoteker']) ?>
                            </div>
                        <?php endif; ?>

                        <!-- Tombol Aksi Apoteker -->
                        <div class="mt-auto pt-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <a href="<?= base_url('apoteker/detail_resep.php?id_resep=' . $resep['id_resep']) ?>" class="btn btn-ghost btn-sm">
                                <i class="bx bx-show"></i> Detail Lengkap
                            </a>

                            <div class="d-flex gap-2">
                                <?php if ($resep['status'] === 'antrean'): ?>
                                    <form method="POST" class="d-inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id_resep" value="<?= $resep['id_resep'] ?>">
                                        <input type="hidden" name="action" value="siapkan">
                                        <button type="submit" class="btn btn-secondary btn-sm">
                                            <i class="bx bxs-flask"></i> Mulai Siapkan
                                        </button>
                                    </form>
                                <?php elseif ($resep['status'] === 'disiapkan'): ?>
                                    <form method="POST" class="d-inline" onsubmit="return confirmSubmit(event, 'Konfirmasi Selesai', 'Pastikan obat telah dicek dan dikemas rapi. Stok obat akan dipotong otomatis.');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id_resep" value="<?= $resep['id_resep'] ?>">
                                        <input type="hidden" name="action" value="selesai">
                                        <button type="submit" class="btn btn-primary btn-sm">
                                            <i class="bx bx-check-double"></i> Selesai (Siap Diambil)
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <?php if ($resep['status'] !== 'selesai'): ?>
                                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="modalKembalikan(<?= $resep['id_resep'] ?>)">
                                        <i class="bx bx-undo"></i> Kembalikan
                                    </button>
                                <?php else: ?>
                                    <span class="text-success font-bold" style="font-size:var(--text-sm);"><i class="bx bx-check-circle"></i> Selesai</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Modal Kembalikan ke Dokter -->
    <div id="modal-kembalikan" class="modal-backdrop" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
        <div class="card p-4" style="max-width:500px; width:90%; background:var(--surface);">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 style="margin:0; font-family:var(--font-heading);"><i class="bx bx-undo text-danger"></i> Kembalikan ke Dokter</h4>
                <button type="button" class="btn btn-ghost btn-sm" onclick="closeModalKembalikan()">&times;</button>
            </div>
            <p class="text-muted" style="font-size:var(--text-sm);">Berikan alasan wajib mengapa resep ini dikembalikan (misal: stok habis, dosis meragukan, interaksi obat).</p>
            
            <form method="POST" id="form-kembalikan">
                <?= csrf_field() ?>
                <input type="hidden" name="id_resep" id="kembalikan-id-resep" value="">
                <input type="hidden" name="action" value="kembalikan">
                <div class="mb-3">
                    <label class="form-label font-bold">Alasan Pengembalian <span class="text-danger">*</span></label>
                    <textarea name="catatan_apoteker" class="form-control" rows="3" required placeholder="Contoh: Stok Amoxicillin 500mg habis di apotek, mohon konfirmasi alternatif Cefadroxil atau obat lain."></textarea>
                </div>
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-secondary" onclick="closeModalKembalikan()">Batal</button>
                    <button type="submit" class="btn btn-danger">Kembalikan Resep</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function confirmSubmit(e, title, text) {
        e.preventDefault();
        const form = e.target;
        Swal.fire({
            title: title,
            text: text,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Lanjutkan',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#2BB5A0'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
        return false;
    }

    function modalKembalikan(id) {
        document.getElementById('kembalikan-id-resep').value = id;
        const modal = document.getElementById('modal-kembalikan');
        modal.style.display = 'flex';
    }

    function closeModalKembalikan() {
        document.getElementById('modal-kembalikan').style.display = 'none';
    }

    // Auto-polling antrean resep baru setiap 8 detik
    let lastActiveCount = <?= $count_antre + $count_proses ?>;
    setInterval(() => {
        fetch('<?= base_url('api/resep_antrian.php') ?>')
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success') {
                    const currentActive = res.counts.total_antrean + res.counts.total_disiapkan;
                    if (currentActive > lastActiveCount) {
                        // Resep baru masuk! Putar bunyi notifikasi atau beri toast
                        DENTARA.showToast('info', 'Resep Baru Masuk dari Dokter!');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1200);
                    }
                }
            })
            .catch(err => console.error('Polling error', err));
    }, 8000);
    </script>
    <?php
}, 'antrean_resep');

