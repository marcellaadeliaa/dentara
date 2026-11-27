<?php
/**
 * DENTARA - Portal Admin: Perhitungan & Slip Fee Sharing Dokter
 * File: admin/fee_sharing.php
 */

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout_staff.php';

require_role(['admin']);

$periode = $_GET['periode'] ?? date('Y-m');

// Handle Export CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=fee_sharing_dokter_' . $periode . '.csv');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID Dokter', 'Nama Dokter', 'Spesialisasi', 'Periode', 'Jumlah Pasien', 'Total Omset Tindakan', 'Persen Fee (%)', 'Nominal Fee Dokter']);

    $stmt_csv = $pdo->prepare("
        SELECT 
            d.id_dokter, u.nama_lengkap, d.spesialisasi, d.persen_fee,
            COUNT(DISTINCT rm.id_rekam) as jumlah_pasien,
            COALESCE(SUM(rt.tarif_saat_itu), 0) as omset_tindakan
        FROM dokter d
        JOIN pengguna u ON d.id_pengguna = u.id_pengguna
        LEFT JOIN rekam_medis rm ON d.id_dokter = rm.id_dokter AND DATE_FORMAT(rm.tanggal_periksa, '%Y-%m') = ?
        LEFT JOIN antrian a ON rm.id_antrian = a.id_antrian
        LEFT JOIN tagihan t ON a.id_antrian = t.id_antrian AND t.status = 'lunas'
        LEFT JOIN rekam_tindakan rt ON rm.id_rekam = rt.id_rekam
        GROUP BY d.id_dokter
        ORDER BY u.nama_lengkap ASC
    ");
    $stmt_csv->execute([$periode]);
    while ($row = $stmt_csv->fetch()) {
        $nominal = ($row['omset_tindakan'] * $row['persen_fee']) / 100;
        fputcsv($output, [
            $row['id_dokter'],
            $row['nama_lengkap'],
            $row['spesialisasi'],
            $periode,
            $row['jumlah_pasien'],
            $row['omset_tindakan'],
            $row['persen_fee'],
            $nominal
        ]);
    }
    fclose($output);
    exit;
}

// Handle POST: Simpan/Kunci Fee ke DB
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        set_flash('error', 'Token Keamanan Kadaluarsa', 'Silakan coba lagi.');
        header('Location: ' . base_url('admin/fee_sharing.php?periode=' . $periode));
        exit;
    }

    $id_dokter = (int)($_POST['id_dokter'] ?? 0);
    $jml_pasien = (int)($_POST['jumlah_pasien'] ?? 0);
    $omset = (float)($_POST['omset'] ?? 0);
    $persen = (float)($_POST['persen_fee'] ?? 0);
    $nominal = ($omset * $persen) / 100;

    try {
        $stmt_save = $pdo->prepare("
            INSERT INTO fee_sharing (id_dokter, periode_bulan, jumlah_pasien, total_pendapatan_tindakan, persen_fee, nominal_fee)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                jumlah_pasien = VALUES(jumlah_pasien),
                total_pendapatan_tindakan = VALUES(total_pendapatan_tindakan),
                persen_fee = VALUES(persen_fee),
                nominal_fee = VALUES(nominal_fee)
        ");
        $stmt_save->execute([$id_dokter, $periode, $jml_pasien, $omset, $persen, $nominal]);
        catat_log($pdo, 'Kunci Fee Dokter', "Mengunci fee dokter #$id_dokter periode $periode (" . rupiah($nominal) . ")");
        set_flash('success', 'Fee Berhasil Disimpan', 'Data fee sharing dokter untuk periode ini telah dikunci.');
    } catch (Exception $e) {
        set_flash('error', 'Gagal Menyimpan Fee', $e->getMessage());
    }

    header('Location: ' . base_url('admin/fee_sharing.php?periode=' . $periode));
    exit;
}

render_staff_layout('Fee Sharing Dokter', function() use ($periode) {
    global $pdo;

    // Ambil data omset & fee dokter untuk bulan terpilih
    $stmt = $pdo->prepare("
        SELECT 
            d.id_dokter, u.nama_lengkap, d.spesialisasi, d.persen_fee, d.no_str,
            COUNT(DISTINCT rm.id_rekam) as jumlah_pasien,
            COALESCE(SUM(rt.tarif_saat_itu), 0) as omset_tindakan,
            fs.id_fee, fs.nominal_fee as nominal_tersimpan
        FROM dokter d
        JOIN pengguna u ON d.id_pengguna = u.id_pengguna
        LEFT JOIN rekam_medis rm ON d.id_dokter = rm.id_dokter AND DATE_FORMAT(rm.tanggal_periksa, '%Y-%m') = ?
        LEFT JOIN antrian a ON rm.id_antrian = a.id_antrian
        LEFT JOIN tagihan t ON a.id_antrian = t.id_antrian AND t.status = 'lunas'
        LEFT JOIN rekam_tindakan rt ON rm.id_rekam = rt.id_rekam
        LEFT JOIN fee_sharing fs ON d.id_dokter = fs.id_dokter AND fs.periode_bulan = ?
        GROUP BY d.id_dokter
        ORDER BY u.nama_lengkap ASC
    ");
    $stmt->execute([$periode, $periode]);
    $rekap_fee = $stmt->fetchAll();

    $grand_omset = 0;
    $grand_fee = 0;
    foreach ($rekap_fee as $r) {
        $grand_omset += $r['omset_tindakan'];
        $grand_fee += ($r['omset_tindakan'] * $r['persen_fee']) / 100;
    }
    ?>

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 style="font-family:var(--font-heading); margin:0;">Perhitungan Fee Sharing Dokter Gigi</h1>
            <p class="text-muted" style="margin:0;">Berdasarkan omset tindakan medis yang tagihannya telah berstatus lunas.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= base_url('admin/fee_sharing.php?periode=' . $periode . '&export=csv') ?>" class="btn btn-secondary btn-sm">
                <i class="bx bx-download"></i> Export Data CSV
            </a>
            <button type="button" class="btn btn-primary btn-sm" onclick="window.print()">
                <i class="bx bx-printer"></i> Cetak Rekap Fee
            </button>
        </div>
    </div>

    <!-- Filter Periode Bulan -->
    <div class="card p-3 mb-4" style="background:var(--surface);">
        <form method="GET" class="d-flex align-items-center gap-2 flex-wrap">
            <label class="form-label font-bold mb-0">Pilih Periode Bulan:</label>
            <input type="month" name="periode" class="form-control" style="max-width:200px;" value="<?= e($periode) ?>" onchange="this.form.submit()">
            <button type="submit" class="btn btn-secondary btn-sm">Tampilkan</button>
        </form>
    </div>

    <!-- Summary Box -->
    <div class="row g-4 mb-4">
        <div class="col-12 col-md-6">
            <div class="card p-4" style="background:var(--surface); border-left:5px solid var(--primary-teal);">
                <div style="font-size:var(--text-xs); color:var(--text-muted); font-weight:700;">TOTAL OMSET TINDAKAN DOKTER (LUNAS)</div>
                <div style="font-size:var(--text-3xl); font-weight:800; font-family:var(--font-heading); color:var(--primary-teal);">
                    <?= rupiah($grand_omset) ?>
                </div>
                <div class="text-muted" style="font-size:12px;">Periode <?= tanggal_indo($periode . '-01') ?></div>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="card p-4" style="background:var(--surface); border-left:5px solid var(--accent-orange);">
                <div style="font-size:var(--text-xs); color:var(--text-muted); font-weight:700;">TOTAL ALOKASI FEE DOKTER</div>
                <div style="font-size:var(--text-3xl); font-weight:800; font-family:var(--font-heading); color:var(--accent-orange);">
                    <?= rupiah($grand_fee) ?>
                </div>
                <div class="text-muted" style="font-size:12px;">Bagi hasil ke seluruh dokter gigi</div>
            </div>
        </div>
    </div>

    <!-- Tabel Rekap Fee per Dokter -->
    <div class="card p-0 mb-4" style="background:var(--surface); overflow:hidden;">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Dokter Gigi</th>
                        <th>Spesialisasi & STR</th>
                        <th class="text-center">Jumlah Pasien</th>
                        <th class="text-end">Omset Tindakan</th>
                        <th class="text-center">Bagi Hasil (%)</th>
                        <th class="text-end">Nominal Fee Dokter</th>
                        <th class="text-center" style="width:160px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rekap_fee)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">Belum ada data dokter atau transaksi medis.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rekap_fee as $rf): 
                            $nominal_hitung = ($rf['omset_tindakan'] * $rf['persen_fee']) / 100;
                            $sudah_kunci = !empty($rf['id_fee']);
                        ?>
                            <tr>
                                <td>
                                    <strong>Dr. <?= e($rf['nama_lengkap']) ?></strong>
                                </td>
                                <td>
                                    <div><?= e($rf['spesialisasi']) ?></div>
                                    <div class="text-muted" style="font-size:11px;">STR: <?= e($rf['no_str'] ?: '-') ?></div>
                                </td>
                                <td class="text-center font-bold">
                                    <span class="badge badge-secondary"><?= (int)$rf['jumlah_pasien'] ?> Pasien</span>
                                </td>
                                <td class="text-end font-bold"><?= rupiah($rf['omset_tindakan']) ?></td>
                                <td class="text-center">
                                    <span class="badge badge-mint"><?= number_format($rf['persen_fee'], 1) ?> %</span>
                                </td>
                                <td class="text-end font-bold" style="font-size:var(--text-base); color:var(--primary-teal);">
                                    <?= rupiah($nominal_hitung) ?>
                                    <?php if ($sudah_kunci): ?>
                                        <div style="font-size:10px; color:var(--success); font-weight:normal;"><i class="bx bx-check-double"></i> Terkunci</div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <form method="POST" class="d-inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id_dokter" value="<?= $rf['id_dokter'] ?>">
                                            <input type="hidden" name="jumlah_pasien" value="<?= $rf['jumlah_pasien'] ?>">
                                            <input type="hidden" name="omset" value="<?= $rf['omset_tindakan'] ?>">
                                            <input type="hidden" name="persen_fee" value="<?= $rf['persen_fee'] ?>">
                                            <button type="submit" class="btn btn-ghost btn-sm text-primary" title="Kunci / Simpan ke Buku Kas">
                                                <i class="bx bx-lock-alt" style="font-size:18px;"></i> Kunci
                                            </button>
                                        </form>
                                        <button type="button" class="btn btn-ghost btn-sm text-warning" onclick='cetakSlip(<?= json_encode($rf) ?>, <?= $nominal_hitung ?>, "<?= $periode ?>")' title="Cetak Slip Fee">
                                            <i class="bx bx-printer" style="font-size:18px;"></i> Slip
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

    <!-- Modal Cetak Slip Fee Individual -->
    <div id="modal-slip" class="modal-backdrop" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
        <div class="card p-4" style="max-width:500px; width:92%; background:var(--surface);" id="print-slip-area">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 style="margin:0; font-family:var(--font-heading);">SLIP FEE SHARING DOKTER</h4>
                <button type="button" class="btn btn-ghost btn-sm no-print" onclick="document.getElementById('modal-slip').style.display='none'">&times;</button>
            </div>

            <div class="border-top border-bottom py-3 mb-3" style="font-size:var(--text-sm);">
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted">Nama Dokter:</span>
                    <strong id="slip-nama">-</strong>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted">Spesialisasi:</span>
                    <span id="slip-spesialis">-</span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted">Periode:</span>
                    <strong id="slip-periode">-</strong>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted">Total Pasien:</span>
                    <span id="slip-pasien">-</span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted">Total Omset Tindakan:</span>
                    <span id="slip-omset">-</span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted">Persentase Fee:</span>
                    <span id="slip-persen">-</span>
                </div>
                <div class="d-flex justify-content-between border-top pt-2 mt-2 font-bold" style="font-size:var(--text-base);">
                    <span>TOTAL FEE DITERIMA:</span>
                    <span class="text-primary" id="slip-nominal">-</span>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 no-print">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('modal-slip').style.display='none'">Tutup</button>
                <button type="button" class="btn btn-primary" onclick="window.print()">
                    <i class="bx bx-printer"></i> Cetak Slip Ini
                </button>
            </div>
        </div>
    </div>

    <script>
    function cetakSlip(rf, nominal, periode) {
        document.getElementById('slip-nama').innerText = 'Dr. ' + rf.nama_lengkap;
        document.getElementById('slip-spesialis').innerText = rf.spesialisasi;
        document.getElementById('slip-periode').innerText = periode;
        document.getElementById('slip-pasien').innerText = rf.jumlah_pasien + ' Pasien';
        document.getElementById('slip-omset').innerText = 'Rp ' + Math.round(rf.omset_tindakan).toLocaleString('id-ID');
        document.getElementById('slip-persen').innerText = rf.persen_fee + ' %';
        document.getElementById('slip-nominal').innerText = 'Rp ' + Math.round(nominal).toLocaleString('id-ID');

        document.getElementById('modal-slip').style.display = 'flex';
    }
    </script>
    <?php
}, 'fee_sharing');

