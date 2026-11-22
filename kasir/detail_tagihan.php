<?php
/**
 * DENTARA - Portal Kasir: Proses & Rincian Pembayaran Tagihan
 * File: kasir/detail_tagihan.php
 */

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout_staff.php';

require_role(['kasir', 'admin']);

$id_tagihan = (int)($_GET['id_tagihan'] ?? 0);
if ($id_tagihan <= 0) {
    set_flash('error', 'Tagihan Tidak Valid', 'ID tagihan tidak ditemukan.');
    header('Location: ' . base_url('kasir/dashboard_tagihan.php'));
    exit;
}

// Ambil info tagihan
$stmt = $pdo->prepare("
    SELECT 
        t.*,
        a.id_antrian, a.nomor_antrian, a.id_reservasi,
        COALESCE(p.nama_pasien, r.nama_tamu) as nama_pasien,
        p.id_pasien, p.no_rm, p.nik, p.no_hp, p.alamat,
        d.id_dokter, u.nama_lengkap as nama_dokter, d.spesialisasi,
        pem.id_pembayaran, pem.no_kuitansi, pem.metode as metode_sudah_bayar
    FROM tagihan t
    LEFT JOIN antrian a ON t.id_antrian = a.id_antrian
    LEFT JOIN reservasi r ON a.id_reservasi = r.id_reservasi
    LEFT JOIN pasien p ON t.id_pasien = p.id_pasien
    LEFT JOIN dokter d ON a.id_dokter = d.id_dokter
    LEFT JOIN pengguna u ON d.id_pengguna = u.id_pengguna
    LEFT JOIN pembayaran pem ON t.id_tagihan = pem.id_tagihan
    WHERE t.id_tagihan = ?
");
$stmt->execute([$id_tagihan]);
$tagihan = $stmt->fetch();

if (!$tagihan) {
    set_flash('error', 'Tagihan Tidak Ditemukan', 'Data tagihan tidak ada dalam sistem.');
    header('Location: ' . base_url('kasir/dashboard_tagihan.php'));
    exit;
}

// Jika sudah lunas, langsung arahkan ke kuitansi
if ($tagihan['status'] === 'lunas' && !empty($tagihan['id_pembayaran'])) {
    header('Location: ' . base_url('kasir/kuitansi.php?id_pembayaran=' . $tagihan['id_pembayaran']));
    exit;
}

// Ambil item rincian tagihan
$stmt_det = $pdo->prepare("SELECT * FROM tagihan_detail WHERE id_tagihan = ? ORDER BY id ASC");
$stmt_det->execute([$id_tagihan]);
$details = $stmt_det->fetchAll();

// Ambil rekening pembayaran aktif
$bank_list = $pdo->query("SELECT * FROM pengaturan_pembayaran WHERE status = 'aktif'")->fetchAll();

// Cek shift kasir aktif
$user = current_user();
$id_kasir = $user['id_pengguna'] ?? 0;
$stmt_shift = $pdo->prepare("SELECT id_shift FROM shift_kasir WHERE id_kasir = ? AND status = 'buka' ORDER BY id_shift DESC LIMIT 1");
$stmt_shift->execute([$id_kasir]);
$id_shift = $stmt_shift->fetchColumn();

// Handle Pembayaran POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        set_flash('error', 'Token Keamanan Kadaluarsa', 'Silakan coba beberapa saat lagi.');
        header('Location: ' . base_url('kasir/detail_tagihan.php?id_tagihan=' . $id_tagihan));
        exit;
    }

    $metode = $_POST['metode'] ?? 'tunai';
    $ditanggung_asuransi = (float)($_POST['ditanggung_asuransi'] ?? 0);
    $total_biaya = (float)$tagihan['total_biaya'];

    if ($ditanggung_asuransi > $total_biaya) {
        $ditanggung_asuransi = $total_biaya;
    }
    $ditanggung_pasien = max(0, $total_biaya - $ditanggung_asuransi);

    try {
        $pdo->beginTransaction();

        // 1. Generate No Kuitansi
        $no_kuitansi = generate_no_kuitansi($pdo);

        // 2. Simpan Pembayaran
        $stmt_pay = $pdo->prepare("
            INSERT INTO pembayaran (id_tagihan, id_kasir, jumlah_dibayar, metode, tanggal_bayar, no_kuitansi, id_shift)
            VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP, ?, ?)
        ");
        $stmt_pay->execute([$id_tagihan, $id_kasir, $ditanggung_pasien, $metode, $no_kuitansi, $id_shift ?: null]);
        $id_pembayaran = (int)$pdo->lastInsertId();

        // 3. Update Status Tagihan
        $stmt_tag = $pdo->prepare("
            UPDATE tagihan 
            SET ditanggung_asuransi = ?, ditanggung_pasien = ?, status = 'lunas'
            WHERE id_tagihan = ?
        ");
        $stmt_tag->execute([$ditanggung_asuransi, $ditanggung_pasien, $id_tagihan]);

        // 4. Update Status Antrean ke Selesai
        if (!empty($tagihan['id_antrian'])) {
            $stmt_ant = $pdo->prepare("
                UPDATE antrian 
                SET status = 'selesai', waktu_selesai = CURRENT_TIMESTAMP
                WHERE id_antrian = ?
            ");
            $stmt_ant->execute([$tagihan['id_antrian']]);
        }

        // 5. Gamifikasi: Tambah Poin Pasien jika akun terdaftar
        if (!empty($tagihan['id_pasien'])) {
            $id_pasien = (int)$tagihan['id_pasien'];
            $stmt_g = $pdo->prepare("
                INSERT INTO gamifikasi_poin (id_pasien, total_poin, jumlah_kunjungan_selesai, updated_at)
                VALUES (?, 100, 1, CURRENT_TIMESTAMP)
                ON DUPLICATE KEY UPDATE 
                    total_poin = total_poin + 100,
                    jumlah_kunjungan_selesai = jumlah_kunjungan_selesai + 1,
                    updated_at = CURRENT_TIMESTAMP
            ");
            $stmt_g->execute([$id_pasien]);

            // Kirim notifikasi ucapan terima kasih
            kirim_notifikasi_pasien(
                $pdo, 
                $id_pasien, 
                'Pembayaran Selesai & Poin Bertambah!', 
                "Terima kasih telah berkunjung ke DENTARA. Pembayaran Anda untuk kuitansi $no_kuitansi telah lunas. Anda mendapatkan +100 poin senyum!"
            );
        }

        $pdo->commit();
        catat_log($pdo, 'Proses Pembayaran', "Pembayaran tagihan #$id_tagihan lunas ($no_kuitansi) via $metode");
        set_flash('success', 'Pembayaran Berhasil!', "Kuitansi $no_kuitansi berhasil diterbitkan.");

        header('Location: ' . base_url('kasir/kuitansi.php?id_pembayaran=' . $id_pembayaran));
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        set_flash('error', 'Gagal Memproses Pembayaran', $e->getMessage());
        header('Location: ' . base_url('kasir/detail_tagihan.php?id_tagihan=' . $id_tagihan));
        exit;
    }
}

render_staff_layout('Detail Pembayaran Tagihan #' . $id_tagihan, function() use ($tagihan, $details, $bank_list, $id_shift) {
    global $pdo;
    ?>
    <div class="mb-4">
        <a href="<?= base_url('kasir/dashboard_tagihan.php') ?>" class="btn btn-ghost btn-sm mb-2">
            <i class="bx bx-left-arrow-alt"></i> Kembali ke Daftar Tagihan
        </a>
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h1 style="font-family:var(--font-heading); margin:0;">Pembayaran Tagihan #<?= $tagihan['id_tagihan'] ?></h1>
                <span class="text-muted" style="font-size:var(--text-sm);">Pasien: <strong><?= e($tagihan['nama_pasien']) ?></strong> | RM: <?= e($tagihan['no_rm'] ?: '-') ?></span>
            </div>
            <div>
                <span class="badge badge-warning" style="font-size:var(--text-sm); padding:6px 14px;">Belum Bayar</span>
            </div>
        </div>
    </div>

    <?php if (!$id_shift): ?>
        <div class="alert alert-warning mb-4">
            <i class="bx bxs-info-circle"></i> Catatan: Anda belum membuka sesi shift kasir hari ini. Transaksi tetap dapat disimpan, namun tidak akan masuk ke rekap kas per shift.
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Rincian Biaya (Itemized) -->
        <div class="col-12 col-lg-7">
            <div class="card p-4 h-100" style="background:var(--surface);">
                <h3 style="font-family:var(--font-heading); font-size:var(--text-lg); margin-bottom:1rem;" class="border-bottom pb-2">
                    <i class="bx bx-list-check text-primary"></i> Rincian Tindakan & Obat
                </h3>

                <div class="table-responsive mb-4">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Kategori</th>
                                <th>Deskripsi Tindakan / Obat</th>
                                <th class="text-center" style="width:60px;">Qty</th>
                                <th class="text-end" style="width:110px;">Tarif</th>
                                <th class="text-end" style="width:120px;">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($details as $d): ?>
                                <tr>
                                    <td><span class="badge badge-secondary" style="font-size:10px;"><?= ucfirst($d['jenis']) ?></span></td>
                                    <td><strong><?= e($d['deskripsi']) ?></strong></td>
                                    <td class="text-center"><?= $d['qty'] ?></td>
                                    <td class="text-end"><?= rupiah($d['harga']) ?></td>
                                    <td class="text-end font-bold"><?= rupiah($d['subtotal']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="border-top" style="font-weight:700; font-size:var(--text-base);">
                                <td colspan="4" class="text-end">Total Biaya Tagihan:</td>
                                <td class="text-end text-primary" id="text-total-biaya"><?= rupiah($tagihan['total_biaya']) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="p-3 bg-light rounded" style="font-size:var(--text-xs);">
                    <div class="row g-2">
                        <div class="col-6"><strong>Dokter Pemeriksa:</strong> Dr. <?= e($tagihan['nama_dokter']) ?></div>
                        <div class="col-6"><strong>Antrean:</strong> #<?= e($tagihan['nomor_antrian']) ?></div>
                        <div class="col-6"><strong>Biaya Konsultasi:</strong> <?= rupiah($tagihan['biaya_konsultasi']) ?></div>
                        <div class="col-6"><strong>Biaya Administrasi:</strong> <?= rupiah($tagihan['biaya_admin']) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Pembayaran & Kalkulator Kasir -->
        <div class="col-12 col-lg-5">
            <div class="card p-4" style="background:var(--surface); border-top:5px solid var(--primary-teal);">
                <h3 style="font-family:var(--font-heading); font-size:var(--text-lg); margin-bottom:1.25rem;">
                    <i class="bx bx-calculator text-primary"></i> Kasir Pembayaran
                </h3>

                <form method="POST" id="form-bayar" onsubmit="return validasiBayar(event)">
                    <?= csrf_field() ?>
                    <input type="hidden" id="raw-total" value="<?= (float)$tagihan['total_biaya'] ?>">

                    <!-- Split Bill Asuransi -->
                    <div class="mb-3">
                        <label class="form-label font-bold">Klaim Ditanggung Asuransi (Rp)</label>
                        <input type="number" name="ditanggung_asuransi" id="input-asuransi" class="form-control" value="0" min="0" max="<?= (float)$tagihan['total_biaya'] ?>" step="1000" oninput="hitungTagihan()">
                        <small class="text-muted">Isi nominal klaim jika pasien memakai asuransi / BPJS / Reimburse.</small>
                    </div>

                    <!-- Jumlah Tagihan Pasien Bersih -->
                    <div class="p-3 mb-3 rounded" style="background:rgba(43,181,160,0.1); border:1px solid rgba(43,181,160,0.3);">
                        <div style="font-size:var(--text-xs); color:var(--text-muted); font-weight:700; text-transform:uppercase;">Wajib Dibayar Pasien</div>
                        <div style="font-size:var(--text-3xl); font-weight:800; font-family:var(--font-heading); color:var(--primary-teal);" id="text-harus-bayar">
                            <?= rupiah($tagihan['total_biaya']) ?>
                        </div>
                    </div>

                    <!-- Metode Pembayaran -->
                    <div class="mb-3">
                        <label class="form-label font-bold">Metode Pembayaran</label>
                        <select name="metode" id="select-metode" class="form-select" onchange="toggleMetode(this.value)">
                            <option value="tunai">Uang Tunai (Cash)</option>
                            <option value="qris">QRIS (GoPay, OVO, Dana, BCA, dll)</option>
                            <option value="debit">Kartu Debit ATM</option>
                            <option value="kartu_kredit">Kartu Kredit</option>
                            <option value="transfer">Transfer Bank</option>
                        </select>
                    </div>

                    <!-- Info QRIS Jika Dipilih -->
                    <div id="section-qris" class="mb-3 p-3 bg-light rounded text-center" style="display:none;">
                        <div style="font-weight:700; margin-bottom:8px;">Scan QRIS Resmi DENTARA</div>
                        <img src="<?= base_url('qr.php?data=' . urlencode('00020101021126580016ID.CO.DENTARA.WWW0118936009980000000001520454995802ID5914DENTARA%20DENTAL6007JAKARTA53033605407' . $tagihan['total_biaya'])) ?>" alt="QRIS" style="max-width:160px; border-radius:8px; border:2px solid #ccc;">
                        <div class="text-muted mt-2" style="font-size:11px;">Mendukung seluruh aplikasi dompet digital & mobile banking.</div>
                    </div>

                    <!-- Info Rekening Bank Jika Transfer -->
                    <div id="section-bank" class="mb-3 p-3 bg-light rounded" style="display:none; font-size:var(--text-xs);">
                        <strong class="d-block mb-1">Nomor Rekening Resmi Klinik:</strong>
                        <?php foreach ($bank_list as $b): ?>
                            <div><strong><?= e($b['nama_bank']) ?>:</strong> <?= e($b['no_rekening']) ?> (a.n <?= e($b['atas_nama']) ?>)</div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Kalkulator Uang Diterima & Kembalian (Khusus Tunai) -->
                    <div id="section-tunai" class="mb-4">
                        <div class="mb-2">
                            <label class="form-label font-bold">Uang Diterima dari Pasien (Rp)</label>
                            <input type="number" id="input-uang-diterima" class="form-control" min="0" step="1000" placeholder="0" oninput="hitungKembalian()">
                        </div>

                        <!-- Shortcut Nominal Uang Cepat -->
                        <div class="d-flex gap-1 mb-2 flex-wrap" id="quick-cash-btns">
                            <button type="button" class="btn btn-ghost btn-sm" onclick="setCashPas()">Uang Pas</button>
                            <button type="button" class="btn btn-ghost btn-sm" onclick="setCash(50000)">50.000</button>
                            <button type="button" class="btn btn-ghost btn-sm" onclick="setCash(100000)">100.000</button>
                            <button type="button" class="btn btn-ghost btn-sm" onclick="setCash(200000)">200.000</button>
                            <button type="button" class="btn btn-ghost btn-sm" onclick="setCash(500000)">500.000</button>
                        </div>

                        <div class="p-3 rounded" style="background:var(--surface); border:1px dashed var(--border-color);">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="font-bold">Kembalian:</span>
                                <span id="text-kembalian" style="font-size:var(--text-xl); font-weight:800; font-family:var(--font-heading); color:var(--success);">Rp 0</span>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-3" style="font-size:var(--text-base); font-weight:700;">
                        <i class="bx bx-check-circle"></i> Terima Pembayaran & Terbitkan Kuitansi
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
    let rawTotal = parseFloat(document.getElementById('raw-total').value) || 0;
    let harusBayar = rawTotal;

    function formatRupiah(num) {
        return 'Rp ' + Math.round(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }

    function hitungTagihan() {
        const asuransi = parseFloat(document.getElementById('input-asuransi').value) || 0;
        harusBayar = Math.max(0, rawTotal - asuransi);
        document.getElementById('text-harus-bayar').innerText = formatRupiah(harusBayar);
        hitungKembalian();
    }

    function toggleMetode(metode) {
        const secTunai = document.getElementById('section-tunai');
        const secQris = document.getElementById('section-qris');
        const secBank = document.getElementById('section-bank');

        secTunai.style.display = (metode === 'tunai') ? 'block' : 'none';
        secQris.style.display = (metode === 'qris') ? 'block' : 'none';
        secBank.style.display = (metode === 'transfer') ? 'block' : 'none';
    }

    function hitungKembalian() {
        const diterima = parseFloat(document.getElementById('input-uang-diterima').value) || 0;
        const kembalian = diterima - harusBayar;
        const elem = document.getElementById('text-kembalian');

        if (kembalian >= 0) {
            elem.innerText = formatRupiah(kembalian);
            elem.style.color = 'var(--success)';
        } else {
            elem.innerText = 'Kurang ' + formatRupiah(Math.abs(kembalian));
            elem.style.color = 'var(--danger)';
        }
    }

    function setCash(nominal) {
        document.getElementById('input-uang-diterima').value = nominal;
        hitungKembalian();
    }

    function setCashPas() {
        document.getElementById('input-uang-diterima').value = harusBayar;
        hitungKembalian();
    }

    function validasiBayar(e) {
        e.preventDefault();
        const metode = document.getElementById('select-metode').value;

        if (metode === 'tunai') {
            const diterima = parseFloat(document.getElementById('input-uang-diterima').value) || 0;
            if (diterima < harusBayar) {
                Swal.fire({
                    title: 'Uang Diterima Kurang',
                    text: 'Jumlah uang tunai yang diterima masih kurang dari total tagihan pasien.',
                    icon: 'warning',
                    confirmButtonColor: '#2BB5A0'
                });
                return false;
            }
        }

        Swal.fire({
            title: 'Konfirmasi Pembayaran',
            text: `Apakah Anda yakin ingin memproses pembayaran sebesar ${formatRupiah(harusBayar)} via ${metode.toUpperCase()}?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Bayar Sekarang',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#2BB5A0'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('form-bayar').submit();
            }
        });

        return false;
    }
    </script>
    <?php
}, 'dashboard_tagihan');

