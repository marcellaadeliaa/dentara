<?php
/**
 * DENTARA - Portal Kasir: Rekap Shift & Penutupan Kas Harian
 * File: kasir/rekap_shift.php
 */

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout_staff.php';

require_role(['kasir', 'admin']);

$user = current_user();
$id_kasir = (int)($user['id_pengguna'] ?? 0);

// Handle POST: Buka Shift atau Tutup Shift
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        set_flash('error', 'Token Keamanan Kadaluarsa', 'Silakan coba lagi.');
        header('Location: ' . base_url('kasir/rekap_shift.php'));
        exit;
    }

    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'buka') {
            $kas_awal = (float)($_POST['kas_awal'] ?? 0);

            // Cek apakah ada shift yang masih menggantung
            $stmt_c = $pdo->prepare("SELECT id_shift FROM shift_kasir WHERE id_kasir = ? AND status = 'buka' LIMIT 1");
            $stmt_c->execute([$id_kasir]);
            if ($stmt_c->fetchColumn()) {
                throw new Exception('Anda masih memiliki shift yang sedang aktif.');
            }

            $stmt_open = $pdo->prepare("
                INSERT INTO shift_kasir (id_kasir, waktu_buka, kas_awal, status)
                VALUES (?, CURRENT_TIMESTAMP, ?, 'buka')
            ");
            $stmt_open->execute([$id_kasir, $kas_awal]);
            catat_log($pdo, 'Buka Shift Kasir', "Membuka shift kasir dengan kas awal " . rupiah($kas_awal));
            set_flash('success', 'Shift Berhasil Dibuka', 'Selamat bertugas! Kas awal telah tercatat.');

        } elseif ($action === 'tutup') {
            $id_shift = (int)($_POST['id_shift'] ?? 0);
            $total_fisik = (float)($_POST['total_fisik'] ?? 0);
            $total_sistem = (float)($_POST['total_sistem'] ?? 0);
            $selisih = $total_fisik - $total_sistem;

            $stmt_close = $pdo->prepare("
                UPDATE shift_kasir 
                SET waktu_tutup = CURRENT_TIMESTAMP,
                    total_sistem = ?,
                    total_fisik = ?,
                    selisih = ?,
                    status = 'tutup'
                WHERE id_shift = ? AND id_kasir = ?
            ");
            $stmt_close->execute([$total_sistem, $total_fisik, $selisih, $id_shift, $id_kasir]);
            catat_log($pdo, 'Tutup Shift Kasir', "Menutup shift #$id_shift (Fisik: " . rupiah($total_fisik) . ", Selisih: " . rupiah($selisih) . ")");
            set_flash('success', 'Shift Ditutup', 'Shift kasir resmi ditutup dan rekap berhasil dibukukan.');
        }
    } catch (Exception $e) {
        set_flash('error', 'Gagal Memproses Shift', $e->getMessage());
    }

    header('Location: ' . base_url('kasir/rekap_shift.php'));
    exit;
}

render_staff_layout('Manajemen Shift & Kas', function() use ($id_kasir) {
    global $pdo;

    // Ambil shift aktif user saat ini
    $stmt_active = $pdo->prepare("SELECT * FROM shift_kasir WHERE id_kasir = ? AND status = 'buka' ORDER BY id_shift DESC LIMIT 1");
    $stmt_active->execute([$id_kasir]);
    $shift_aktif = $stmt_active->fetch();

    $total_transaksi = 0;
    $total_tunai = 0;
    $total_non_tunai = 0;
    $pembayaran_list = [];

    if ($shift_aktif) {
        $id_shift = $shift_aktif['id_shift'];

        // Ambil rekap transaksi pembayaran dalam shift ini
        $stmt_pay = $pdo->prepare("
            SELECT p.*, t.total_biaya, 
                   COALESCE(pas.nama_pasien, r.nama_tamu) as nama_pasien, pas.no_rm
            FROM pembayaran p
            JOIN tagihan t ON p.id_tagihan = t.id_tagihan
            LEFT JOIN antrian a ON t.id_antrian = a.id_antrian
            LEFT JOIN reservasi r ON a.id_reservasi = r.id_reservasi
            LEFT JOIN pasien pas ON t.id_pasien = pas.id_pasien
            WHERE p.id_shift = ?
            ORDER BY p.id_pembayaran DESC
        ");
        $stmt_pay->execute([$id_shift]);
        $pembayaran_list = $stmt_pay->fetchAll();

        foreach ($pembayaran_list as $row) {
            $total_transaksi++;
            if ($row['metode'] === 'tunai') {
                $total_tunai += $row['jumlah_dibayar'];
            } else {
                $total_non_tunai += $row['jumlah_dibayar'];
            }
        }
    }

    // Riwayat Shift Terdahulu
    $stmt_hist = $pdo->prepare("
        SELECT s.*, u.nama_lengkap as nama_kasir,
               COUNT(p.id_pembayaran) as jml_transaksi
        FROM shift_kasir s
        JOIN pengguna u ON s.id_kasir = u.id_pengguna
        LEFT JOIN pembayaran p ON s.id_shift = p.id_shift
        GROUP BY s.id_shift
        ORDER BY s.id_shift DESC
        LIMIT 15
    ");
    $stmt_hist->execute();
    $history_shifts = $stmt_hist->fetchAll();
    ?>

    <?php if (!$shift_aktif): ?>
        <!-- FORM BUKA SHIFT BARU -->
        <div class="card p-5 mb-5 text-center" style="max-width:550px; margin:0 auto; background:var(--surface);">
            <div style="font-size:3.5rem; color:var(--primary-teal); margin-bottom:1rem;">
                <i class="bx bxs-door-open"></i>
            </div>
            <h2 style="font-family:var(--font-heading); margin-bottom:0.5rem;">Buka Sesi Shift Kasir</h2>
            <p class="text-muted mb-4" style="font-size:var(--text-sm);">Masukkan jumlah uang kas awal (uang modal kembalian di laci kasir) sebelum mulai melayani pembayaran pasien.</p>

            <form method="POST" class="text-left">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="buka">
                <div class="mb-4">
                    <label class="form-label font-bold">Kas Awal / Modal Kembalian (Rp)</label>
                    <input type="number" name="kas_awal" class="form-control form-control-lg text-center font-bold" min="0" step="50000" value="300000" required style="font-size:var(--text-2xl); font-family:var(--font-heading); color:var(--primary-teal);">
                </div>

                <button type="submit" class="btn btn-primary w-100 py-3" style="font-size:var(--text-base); font-weight:700;">
                    <i class="bx bx-check-circle"></i> Buka Shift & Mulai Bekerja
                </button>
            </form>
        </div>

    <?php else: 
        $target_sistem = (float)$shift_aktif['kas_awal'] + (float)$total_tunai;
    ?>
        <!-- SHIFT SEDANG AKTIF -->
        <div class="card p-4 mb-4" style="background:var(--surface); border-left:5px solid var(--primary-teal);">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <span class="badge badge-mint mb-1"><i class="bx bx-check-circle"></i> SHIFT AKTIF BERJALAN</span>
                    <h2 style="font-family:var(--font-heading); margin:0;">Shift #<?= $shift_aktif['id_shift'] ?> (Petugas: <?= e($user['nama_lengkap']) ?>)</h2>
                    <span class="text-muted" style="font-size:var(--text-xs);">Dibuka sejak: <?= format_datetime_indo($shift_aktif['waktu_buka']) ?></span>
                </div>
                <div>
                    <button type="button" class="btn btn-danger" onclick="bukaModalTutupShift(<?= $target_sistem ?>)">
                        <i class="bx bx-lock"></i> Tutup Shift Sekarang
                    </button>
                </div>
            </div>
        </div>

        <!-- Metric Card Kas Shift -->
        <div class="row g-4 mb-4">
            <div class="col-6 col-md-3">
                <div class="card p-3" style="background:var(--surface);">
                    <div style="font-size:var(--text-xs); color:var(--text-muted); font-weight:700;">KAS AWAL (MODAL)</div>
                    <div style="font-size:var(--text-2xl); font-weight:800; font-family:var(--font-heading);"><?= rupiah($shift_aktif['kas_awal']) ?></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card p-3" style="background:var(--surface);">
                    <div style="font-size:var(--text-xs); color:var(--success); font-weight:700;">PENERIMAAN TUNAI</div>
                    <div style="font-size:var(--text-2xl); font-weight:800; font-family:var(--font-heading); color:var(--success);"><?= rupiah($total_tunai) ?></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card p-3" style="background:var(--surface);">
                    <div style="font-size:var(--text-xs); color:var(--accent-blue); font-weight:700;">QRIS / DEBIT / TF</div>
                    <div style="font-size:var(--text-2xl); font-weight:800; font-family:var(--font-heading); color:var(--accent-blue);"><?= rupiah($total_non_tunai) ?></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card p-3" style="background:var(--surface); border:1px solid var(--primary-teal);">
                    <div style="font-size:var(--text-xs); color:var(--primary-teal); font-weight:700;">TOTAL SEHARUSNYA DI LACI</div>
                    <div style="font-size:var(--text-2xl); font-weight:800; font-family:var(--font-heading); color:var(--primary-teal);"><?= rupiah($target_sistem) ?></div>
                </div>
            </div>
        </div>

        <!-- Tabel Transaksi dalam Shift Ini -->
        <div class="card p-0 mb-4" style="background:var(--surface); overflow:hidden;">
            <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                <h3 style="font-family:var(--font-heading); font-size:var(--text-lg); margin:0;">
                    <i class="bx bx-receipt text-primary"></i> Transaksi dalam Shift Ini (<?= $total_transaksi ?>)
                </h3>
            </div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>No Kuitansi</th>
                            <th>Waktu</th>
                            <th>Nama Pasien</th>
                            <th>Metode</th>
                            <th class="text-end">Jumlah Dibayar</th>
                            <th class="text-center" style="width:100px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pembayaran_list)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">Belum ada transaksi pembayaran dalam shift ini.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pembayaran_list as $p): ?>
                                <tr>
                                    <td><strong><?= e($p['no_kuitansi']) ?></strong></td>
                                    <td><?= waktu_indo($p['tanggal_bayar']) ?></td>
                                    <td><?= e($p['nama_pasien']) ?> (<?= e($p['no_rm'] ?: 'Tamu') ?>)</td>
                                    <td><span class="badge badge-secondary"><?= strtoupper(e($p['metode'])) ?></span></td>
                                    <td class="text-end font-bold"><?= rupiah($p['jumlah_dibayar']) ?></td>
                                    <td class="text-center">
                                        <a href="<?= base_url('kasir/kuitansi.php?id_pembayaran=' . $p['id_pembayaran']) ?>" target="_blank" class="btn btn-ghost btn-sm text-primary">
                                            <i class="bx bx-printer"></i> Struk
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal Tutup Shift -->
        <div id="modal-tutup-shift" class="modal-backdrop" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
            <div class="card p-4" style="max-width:500px; width:92%; background:var(--surface);">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 style="margin:0; font-family:var(--font-heading);"><i class="bx bx-lock text-danger"></i> Tutup Shift Kasir</h4>
                    <button type="button" class="btn btn-ghost btn-sm" onclick="tutupModalTutupShift()">&times;</button>
                </div>

                <form method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="tutup">
                    <input type="hidden" name="id_shift" value="<?= $shift_aktif['id_shift'] ?>">
                    <input type="hidden" name="total_sistem" id="hidden-total-sistem" value="<?= $target_sistem ?>">

                    <div class="p-3 mb-3 bg-light rounded">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Kas Awal:</span>
                            <strong><?= rupiah($shift_aktif['kas_awal']) ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span>Total Tunai Masuk:</span>
                            <strong class="text-success"><?= rupiah($total_tunai) ?></strong>
                        </div>
                        <div class="d-flex justify-content-between border-top pt-2 font-bold">
                            <span>Total Uang di Laci Seharusnya:</span>
                            <span class="text-primary"><?= rupiah($target_sistem) ?></span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label font-bold">Total Uang Fisik Hasil Hitung di Laci (Rp) <span class="text-danger">*</span></label>
                        <input type="number" name="total_fisik" id="input-fisik-tutup" class="form-control form-control-lg text-center font-bold" min="0" step="500" required oninput="hitungSelisihShift(<?= $target_sistem ?>)">
                    </div>

                    <div class="p-3 mb-4 rounded border text-center" id="box-selisih" style="display:none;">
                        <span style="font-size:12px; color:var(--text-muted); font-weight:700;">SELISIH KAS FISIK DENGAN SISTEM:</span>
                        <div id="text-selisih-shift" style="font-size:var(--text-xl); font-weight:800; font-family:var(--font-heading);">Rp 0</div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-secondary" onclick="tutupModalTutupShift()">Batal</button>
                        <button type="submit" class="btn btn-danger">Tutup & Simpan Rekap</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
        function bukaModalTutupShift() {
            document.getElementById('modal-tutup-shift').style.display = 'flex';
        }
        function tutupModalTutupShift() {
            document.getElementById('modal-tutup-shift').style.display = 'none';
        }
        function hitungSelisihShift(sistem) {
            const fisik = parseFloat(document.getElementById('input-fisik-tutup').value) || 0;
            const selisih = fisik - sistem;
            const box = document.getElementById('box-selisih');
            const text = document.getElementById('text-selisih-shift');
            box.style.display = 'block';

            if (selisih === 0) {
                text.innerText = 'Rp 0 (Pas Sempurna)';
                text.style.color = 'var(--success)';
            } else if (selisih > 0) {
                text.innerText = '+ Rp ' + Math.round(selisih).toLocaleString('id-ID') + ' (Kelebihan Uang)';
                text.style.color = 'var(--accent-blue)';
            } else {
                text.innerText = '- Rp ' + Math.round(Math.abs(selisih)).toLocaleString('id-ID') + ' (Kekurangan Uang)';
                text.style.color = 'var(--danger)';
            }
        }
        </script>
    <?php endif; ?>

    <!-- Riwayat Sesi Shift Sebelumnya -->
    <div class="card p-0 mt-5" style="background:var(--surface); overflow:hidden;">
        <div class="p-3 border-bottom">
            <h3 style="font-family:var(--font-heading); font-size:var(--text-lg); margin:0;">
                <i class="bx bx-history text-primary"></i> Riwayat Shift Sebelumnya
            </h3>
        </div>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Petugas Kasir</th>
                        <th>Waktu Buka & Tutup</th>
                        <th class="text-end">Kas Awal</th>
                        <th class="text-end">Sistem</th>
                        <th class="text-end">Fisik</th>
                        <th class="text-center">Selisih</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history_shifts as $hs): ?>
                        <tr>
                            <td>#<?= $hs['id_shift'] ?></td>
                            <td><strong><?= e($hs['nama_kasir']) ?></strong></td>
                            <td style="font-size:12px;">
                                <div>Buka: <?= waktu_indo($hs['waktu_buka']) ?> (<?= tanggal_indo(date('Y-m-d', strtotime($hs['waktu_buka']))) ?>)</div>
                                <div>Tutup: <?= $hs['waktu_tutup'] ? waktu_indo($hs['waktu_tutup']) : '-' ?></div>
                            </td>
                            <td class="text-end"><?= rupiah($hs['kas_awal']) ?></td>
                            <td class="text-end"><?= rupiah($hs['total_sistem'] ?? 0) ?></td>
                            <td class="text-end font-bold"><?= rupiah($hs['total_fisik'] ?? 0) ?></td>
                            <td class="text-center">
                                <?php if ($hs['status'] === 'tutup'): ?>
                                    <?php if ($hs['selisih'] == 0): ?>
                                        <span class="badge badge-success">Cocok</span>
                                    <?php elseif ($hs['selisih'] > 0): ?>
                                        <span class="badge badge-primary">+<?= rupiah($hs['selisih']) ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-danger"><?= rupiah($hs['selisih']) ?></span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <span class="badge <?= $hs['status'] === 'buka' ? 'badge-mint' : 'badge-secondary' ?>">
                                    <?= ucfirst($hs['status']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}, 'rekap_shift');

