<?php
/**
 * DENTARA - Portal Dokter: Pemeriksaan Lengkap & Rekam Medis Elektronik (EMR)
 * File: dokter/pemeriksaan.php
 */

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout_staff.php';

require_role(['dokter', 'admin']);

$user = current_user();
$id_dokter = (int)($user['id_dokter'] ?? 1);
$id_antrian = (int)($_GET['id_antrian'] ?? 0);

if (!$id_antrian) {
    header('Location: ' . base_url('dokter/panel_antrian.php'));
    exit;
}

// Ambil data antrean, pasien, dan reservasi
$stmt = $pdo->prepare("
    SELECT a.*, r.kode_booking, r.keluhan, r.id_layanan as default_layanan,
           p.*, 
           w.nama_wali, w.hubungan as hubungan_wali, w.kontak as kontak_wali
    FROM antrian a
    JOIN reservasi r ON a.id_reservasi = r.id_reservasi
    JOIN pasien p ON r.id_pasien = p.id_pasien
    LEFT JOIN wali_pasien w ON p.id_pasien = w.id_pasien
    WHERE a.id_antrian = ?
");
$stmt->execute([$id_antrian]);
$antrian = $stmt->fetch();

if (!$antrian) {
    header('Location: ' . base_url('dokter/panel_antrian.php'));
    exit;
}

$id_pasien = (int)$antrian['id_pasien'];

// Ambil riwayat odontogram terakhir pasien ini
$stmt_odonto = $pdo->prepare("SELECT nomor_gigi, permukaan, kondisi FROM odontogram WHERE id_pasien = ?");
$stmt_odonto->execute([$id_pasien]);
$existing_odonto = [];
foreach ($stmt_odonto->fetchAll() as $row) {
    $existing_odonto[$row['nomor_gigi']][$row['permukaan']] = $row['kondisi'];
}

// Ambil master layanan untuk tindakan
$stmt_lay = $pdo->query("SELECT id_layanan, nama_layanan, kategori, tarif_awal FROM layanan WHERE status = 'aktif' ORDER BY kategori ASC, nama_layanan ASC");
$master_layanan = $stmt_lay->fetchAll();

// Ambil pengaturan biaya konsultasi & admin klinik
$stmt_k = $pdo->query("SELECT biaya_konsultasi, biaya_admin FROM pengaturan_klinik LIMIT 1");
$klinik_cfg = $stmt_k->fetch() ?: ['biaya_konsultasi' => 50000, 'biaya_admin' => 15000];

// ====================================================================
// HANDLE POST: SELESAI PEMERIKSAAN (DB TRANSACTION MENYELURUH)
// ====================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        set_flash('error', 'Token Kedaluwarsa', 'Silakan ulangi pengiriman formulir.');
        header('Location: ' . base_url('dokter/pemeriksaan.php?id_antrian=' . $id_antrian));
        exit;
    }

    try {
        $pdo->beginTransaction();

        $subjektif = trim($_POST['subjektif'] ?? '');
        $objektif  = trim($_POST['objektif'] ?? '');
        $asesmen   = trim($_POST['asesmen'] ?? '');
        $plan      = trim($_POST['plan'] ?? '');
        $catatan   = trim($_POST['catatan_khusus'] ?? '');

        // 1. Simpan Rekam Medis Utama
        $stmt_rm = $pdo->prepare("
            INSERT INTO rekam_medis (id_antrian, id_pasien, id_dokter, tanggal_periksa, subjektif, objektif, asesmen, plan, catatan_khusus)
            VALUES (?, ?, ?, CURRENT_DATE(), ?, ?, ?, ?, ?)
        ");
        $stmt_rm->execute([$id_antrian, $id_pasien, $id_dokter, $subjektif, $objektif, $asesmen, $plan, $catatan]);
        $id_rekam = (int)$pdo->lastInsertId();

        // 2. Simpan Diagnosis ICD-10
        $icd10_codes = $_POST['icd10_codes'] ?? [];
        if (!empty($icd10_codes)) {
            $stmt_diag = $pdo->prepare("INSERT INTO rekam_diagnosis (id_rekam, kode_icd10) VALUES (?, ?)");
            foreach ($icd10_codes as $code) {
                if ($code) $stmt_diag->execute([$id_rekam, trim($code)]);
            }
        }

        // 3. Simpan Tindakan Medis & Hitung Total Tindakan
        $tindakan_ids    = $_POST['tindakan_id'] ?? [];
        $tindakan_gigis  = $_POST['tindakan_gigi'] ?? [];
        $tindakan_qtys   = $_POST['tindakan_qty'] ?? [];
        $tindakan_tarifs = $_POST['tindakan_tarif'] ?? [];

        $total_biaya_tindakan = 0;
        $items_tindakan = [];

        if (!empty($tindakan_ids)) {
            $stmt_act = $pdo->prepare("
                INSERT INTO rekam_tindakan (id_rekam, id_layanan, nomor_gigi, jumlah, tarif_saat_itu)
                VALUES (?, ?, ?, ?, ?)
            ");
            for ($i = 0; $i < count($tindakan_ids); $i++) {
                $id_lay = (int)$tindakan_ids[$i];
                if (!$id_lay) continue;

                $gigi  = trim($tindakan_gigis[$i] ?? '');
                $qty   = max(1, (int)($tindakan_qtys[$i] ?? 1));
                $tarif = (float)($tindakan_tarifs[$i] ?? 0);
                $subtotal = $qty * $tarif;
                $total_biaya_tindakan += $subtotal;

                $stmt_act->execute([$id_rekam, $id_lay, $gigi ?: null, $qty, $tarif]);

                // Simpan nama layanan untuk auto-billing
                $stmt_lay_name = $pdo->prepare("SELECT nama_layanan FROM layanan WHERE id_layanan = ?");
                $stmt_lay_name->execute([$id_lay]);
                $nama_lay = $stmt_lay_name->fetchColumn();

                $items_tindakan[] = [
                    'deskripsi' => $nama_lay . ($gigi ? " (Gigi #{$gigi})" : ''),
                    'qty'       => $qty,
                    'harga'     => $tarif,
                    'subtotal'  => $subtotal
                ];
            }
        }

        // 4. Simpan State Odontogram
        $odonto_json = $_POST['odontogram_state'] ?? '{}';
        $odonto_arr = json_decode($odonto_json, true);
        if (is_array($odonto_arr) && !empty($odonto_arr)) {
            // Hapus snapshot sebelumnya untuk pasien ini
            $stmt_del_od = $pdo->prepare("DELETE FROM odontogram WHERE id_pasien = ?");
            $stmt_del_od->execute([$id_pasien]);

            $stmt_ins_od = $pdo->prepare("
                INSERT INTO odontogram (id_pasien, id_rekam, nomor_gigi, permukaan, kondisi)
                VALUES (?, ?, ?, ?, ?)
            ");
            foreach ($odonto_arr as $toothNum => $surfaces) {
                if (!is_array($surfaces)) continue;
                foreach ($surfaces as $surfName => $cond) {
                    $stmt_ins_od->execute([$id_pasien, $id_rekam, (string)$toothNum, (string)$surfName, (string)$cond]);
                }
            }
        }

        // 5. Simpan E-Prescription (Resep Obat)
        $resep_obats  = $_POST['resep_obat_id'] ?? [];
        $resep_qtys   = $_POST['resep_qty'] ?? [];
        $resep_dosis  = $_POST['resep_dosis'] ?? [];
        $resep_signas = $_POST['resep_signa'] ?? [];

        $total_biaya_obat = 0;
        $items_obat = [];

        if (!empty($resep_obats) && !empty(array_filter($resep_obats))) {
            $stmt_rx = $pdo->prepare("INSERT INTO resep (id_rekam, status) VALUES (?, 'antrean')");
            $stmt_rx->execute([$id_rekam]);
            $id_resep = (int)$pdo->lastInsertId();

            $stmt_rxd = $pdo->prepare("
                INSERT INTO resep_detail (id_resep, id_obat, jumlah, dosis, aturan_pakai)
                VALUES (?, ?, ?, ?, ?)
            ");

            for ($j = 0; $j < count($resep_obats); $j++) {
                $id_ob = (int)$resep_obats[$j];
                if (!$id_ob) continue;

                $q_rx   = max(1, (int)($resep_qtys[$j] ?? 1));
                $dosis  = trim($resep_dosis[$j] ?? '');
                $signa  = trim($resep_signas[$j] ?? '');

                $stmt_rxd->execute([$id_resep, $id_ob, $q_rx, $dosis, $signa]);

                // Hitung estimasi biaya obat untuk kasir
                $stmt_ob_info = $pdo->prepare("SELECT nama_obat, harga_jual FROM obat WHERE id_obat = ?");
                $stmt_ob_info->execute([$id_ob]);
                $ob_info = $stmt_ob_info->fetch();

                if ($ob_info) {
                    $harga_ob = (float)$ob_info['harga_jual'];
                    $sub_ob = $q_rx * $harga_ob;
                    $total_biaya_obat += $sub_ob;
                    $items_obat[] = [
                        'deskripsi' => $ob_info['nama_obat'],
                        'qty'       => $q_rx,
                        'harga'     => $harga_ob,
                        'subtotal'  => $sub_ob
                    ];
                }
            }
        }

        // 6. Simpan Treatment Plan (Rencana Kontrol)
        $rencana_kontrol = trim($_POST['treatment_plan_rencana'] ?? '');
        $tgl_kontrol     = trim($_POST['treatment_plan_tanggal'] ?? '');

        if (!empty($rencana_kontrol)) {
            $stmt_tp = $pdo->prepare("
                INSERT INTO treatment_plan (id_pasien, id_rekam, id_dokter, rencana, tanggal_kontrol_berikut, status)
                VALUES (?, ?, ?, ?, ?, 'direncanakan')
            ");
            $stmt_tp->execute([$id_pasien, $id_rekam, $id_dokter, $rencana_kontrol, $tgl_kontrol ?: null]);

            // Jika ada tanggal kontrol, kirim notifikasi otomatis ke pasien
            if (!empty($tgl_kontrol)) {
                kirim_notifikasi_pasien(
                    $pdo,
                    $id_pasien,
                    'Pengingat Kontrol Perawatan',
                    "Dokter Anda telah merencanakan kontrol lanjutan pada tanggal " . tanggal_indo($tgl_kontrol, true) . ". Rencana: {$rencana_kontrol}."
                );
            }
        }

        // 7. Selesaikan Status Antrian & Reservasi
        $stmt_upd_ant = $pdo->prepare("UPDATE antrian SET status = 'selesai', waktu_selesai = CURRENT_TIMESTAMP WHERE id_antrian = ?");
        $stmt_upd_ant->execute([$id_antrian]);

        $stmt_upd_res = $pdo->prepare("UPDATE reservasi SET status = 'selesai' WHERE id_reservasi = ?");
        $stmt_upd_res->execute([(int)$antrian['id_reservasi']]);

        // 8. Berikan Poin Gamifikasi ke Pasien (+50 Poin, +1 Kunjungan)
        $stmt_gp = $pdo->prepare("
            INSERT INTO gamifikasi_poin (id_pasien, total_poin, jumlah_kunjungan_selesai)
            VALUES (?, 50, 1)
            ON DUPLICATE KEY UPDATE total_poin = total_poin + 50, jumlah_kunjungan_selesai = jumlah_kunjungan_selesai + 1
        ");
        $stmt_gp->execute([$id_pasien]);

        // 9. AUTO-BILLING ENGINE: Buat Tagihan Kasir Otomatis
        $biaya_konsul = (float)($klinik_cfg['biaya_konsultasi'] ?? 50000);
        $biaya_admin  = (float)($klinik_cfg['biaya_admin'] ?? 15000);
        $grand_total  = $biaya_konsul + $total_biaya_tindakan + $total_biaya_obat + $biaya_admin;

        $stmt_tag = $pdo->prepare("
            INSERT INTO tagihan (id_antrian, id_pasien, biaya_konsultasi, biaya_tindakan, biaya_obat, biaya_admin, total_biaya, ditanggung_asuransi, ditanggung_pasien, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 0.00, ?, 'belum_bayar')
        ");
        $stmt_tag->execute([$id_antrian, $id_pasien, $biaya_konsul, $total_biaya_tindakan, $total_biaya_obat, $biaya_admin, $grand_total, $grand_total]);
        $id_tagihan = (int)$pdo->lastInsertId();

        // Rincian Tagihan Detail
        $stmt_td = $pdo->prepare("
            INSERT INTO tagihan_detail (id_tagihan, jenis, deskripsi, qty, harga, subtotal)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        // Konsultasi
        $stmt_td->execute([$id_tagihan, 'konsultasi', 'Biaya Konsultasi & Dental Check-Up', 1, $biaya_konsul, $biaya_konsul]);
        // Tindakan
        foreach ($items_tindakan as $it) {
            $stmt_td->execute([$id_tagihan, 'tindakan', $it['deskripsi'], $it['qty'], $it['harga'], $it['subtotal']]);
        }
        // Obat
        foreach ($items_obat as $io) {
            $stmt_td->execute([$id_tagihan, 'obat', $io['deskripsi'], $io['qty'], $io['harga'], $io['subtotal']]);
        }
        // Admin
        $stmt_td->execute([$id_tagihan, 'admin', 'Biaya Administrasi & Sterilisasi Alat', 1, $biaya_admin, $biaya_admin]);

        $pdo->commit();

        catat_log($pdo, 'EMR_SELESAI', "Menyelesaikan pemeriksaan pasien #{$id_pasien} ({$antrian['nama_pasien']}), EMR #{$id_rekam}, Tagihan #{$id_tagihan}");

        set_flash('success', 'Pemeriksaan Selesai!', "Rekam medis pasien {$antrian['nama_pasien']} berhasil disimpan dan tagihan telah diteruskan ke kasir.");
        header('Location: ' . base_url('dokter/panel_antrian.php'));
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        set_flash('error', 'Gagal Simpan EMR', $e->getMessage());
        header('Location: ' . base_url('dokter/pemeriksaan.php?id_antrian=' . $id_antrian));
        exit;
    }
}

render_staff_layout('Pemeriksaan Pasien EMR: ' . $antrian['nama_pasien'], function() use ($antrian, $existing_odonto, $master_layanan) {
?>
<div class="fade-in">
    <!-- ALERT BANNER ALERGI BERKEDIP JIKA PASIEN PUNYA ALERGI / HIPERTENSI / DIABETES -->
    <?php if ($antrian['alergi_obat'] || $antrian['kondisi_medis']): ?>
    <div class="alert-flashing-danger mb-4">
        <i class="bx bxs-shield-x" style="font-size:26px;"></i>
        <div>
            <span style="font-size:15px; font-weight:800; display:block;">PERHATIAN MEDIS PENTING (ALERT SYSTEM):</span>
            <div style="font-size:14px; font-weight:600; margin-top:2px;">
                <?php if ($antrian['alergi_obat']): ?>
                    &bull; ALERGI OBAT / ANESTESI: <u style="color:#B91C1C;"><?= e($antrian['alergi_obat']) ?></u> &nbsp;&nbsp;
                <?php endif; ?>
                <?php if ($antrian['kondisi_medis']): ?>
                    &bull; KONDISI KESEHATAN: <u style="color:#B91C1C;"><?= e($antrian['kondisi_medis']) ?></u>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- HEADER PASIEN INFO -->
    <div class="card p-5 mb-5" style="border-radius:var(--radius-lg);">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-4">
            <div class="d-flex align-items-center gap-3">
                <div style="width:58px; height:58px; border-radius:50%; background:var(--grad-mint-blue); color:#fff; display:flex; align-items:center; justify-content:center; font-size:24px; font-weight:700;">
                    <?= strtoupper(substr($antrian['nama_pasien'], 0, 1)) ?>
                </div>
                <div>
                    <h2 style="font-size:20px; margin:0;"><?= e($antrian['nama_pasien']) ?></h2>
                    <div style="font-size:13px; color:var(--text-muted); margin-top:2px;">
                        No. RM: <strong><?= e($antrian['no_rm'] ?: '-') ?></strong> &bull; NIK: <?= e($antrian['nik']) ?> &bull; 
                        Usia: <strong><?= hitung_usia($antrian['tgl_lahir']) ?></strong> (<?= $antrian['jenis_kelamin'] ?>)
                    </div>
                </div>
            </div>

            <div class="text-right">
                <span class="badge badge-purple" style="font-size:13px;">Antrean: A-<?= sprintf('%03d', $antrian['nomor_antrian']) ?></span>
                <div style="font-size:12px; color:var(--text-muted); margin-top:4px;">Kode: <?= e($antrian['kode_booking']) ?></div>
            </div>
        </div>
    </div>

    <!-- MAIN FORM PEMERIKSAAN DOKTER -->
    <form action="<?= base_url('dokter/pemeriksaan.php?id_antrian=' . $antrian['id_antrian']) ?>" method="POST" id="form-emr-dokter">
        <?= csrf_field() ?>
        <input type="hidden" name="odontogram_state" id="odontogram-state-json" value='<?= json_encode($existing_odonto) ?>'>

        <div class="grid grid-cols-3 gap-6 align-items-start">
            <!-- Kolom Kiri: Tab EMR Form (Span 2) -->
            <div style="grid-column: span 2; display:flex; flex-direction:column; gap:20px;">
                
                <!-- SEKSI 1: SOAP FORM -->
                <div class="card p-6" style="border-radius:var(--radius-lg);">
                    <h3 style="font-size:18px; color:var(--primary); margin-bottom:16px;">
                        <i class="bx bx-notepad"></i> 1. Form SOAP Medis
                    </h3>

                    <div class="form-group mb-3">
                        <label class="form-label required font-bold">Subjektif (S) - Keluhan & Riwayat Penyakit Sekarang</label>
                        <textarea name="subjektif" class="form-control" rows="2" required placeholder="Keluhan yang dirasakan pasien..."><?= e($antrian['keluhan']) ?></textarea>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label required font-bold">Objektif (O) - Hasil Pemeriksaan Fisik & Intraoral</label>
                        <textarea name="objektif" class="form-control" rows="2" required placeholder="Inspeksi gigi, palpasi, sondasi, tes termal, kondisi gusi..."></textarea>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label required font-bold">Asesmen (A) - Kesimpulan Diagnosa Kerja</label>
                        <textarea name="asesmen" class="form-control" rows="2" required placeholder="Contoh: Karies dentin gigi 46, Pulpitis ireversibel, dsb..."></textarea>
                    </div>

                    <div class="form-group mb-0">
                        <label class="form-label required font-bold">Plan (P) - Rencana Terapi & Tindakan</label>
                        <textarea name="plan" class="form-control" rows="2" required placeholder="Tindakan yang dilakukan dan instruksi pasca tindakan..."></textarea>
                    </div>
                </div>

                <!-- SEKSI 2: ODONTOGRAM INTERAKTIF -->
                <div class="card p-6" style="border-radius:var(--radius-lg);">
                    <h3 style="font-size:18px; color:var(--primary); margin-bottom:14px;">
                        <i class="bx bxs-smile"></i> 2. Odontogram Interaktif Pasien
                    </h3>
                    <p class="text-muted" style="font-size:13px; margin-bottom:16px;">
                        Pilih kondisi di toolbar lalu klik permukaan gigi (oklusal, mesial, distal, bukal, lingual).
                    </p>

                    <div id="interactive-odontogram-box"></div>
                </div>

                <!-- SEKSI 3: DIAGNOSIS ICD-10 GIGI -->
                <div class="card p-6" style="border-radius:var(--radius-lg);">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h3 style="font-size:18px; color:var(--primary); margin:0;">
                            <i class="bx bx-search-alt"></i> 3. Kode Diagnosa ICD-10
                        </h3>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="tambahBarisIcd10()">
                            <i class="bx bx-plus"></i> Tambah Kode ICD-10
                        </button>
                    </div>

                    <div id="icd10-container" style="display:flex; flex-direction:column; gap:10px;">
                        <div class="d-flex gap-2 icd10-row">
                            <input type="text" name="icd10_codes[]" class="form-control icd10-autocomplete" placeholder="Ketik kode (misal: K02.1 atau K04.0)..." list="icd10-list">
                            <button type="button" class="btn btn-ghost btn-sm" onclick="this.closest('.icd10-row').remove()"><i class="bx bx-trash text-danger"></i></button>
                        </div>
                    </div>
                    <datalist id="icd10-list"></datalist>
                </div>

                <!-- SEKSI 4: TINDAKAN MEDIS (AUTO-BILLING) -->
                <div class="card p-6" style="border-radius:var(--radius-lg);">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h3 style="font-size:18px; color:var(--primary); margin:0;">
                            <i class="bx bxs-first-aid"></i> 4. Tindakan Medis Dilakukan
                        </h3>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="tambahBarisTindakan()">
                            <i class="bx bx-plus"></i> Tambah Tindakan
                        </button>
                    </div>

                    <div id="tindakan-container" style="display:flex; flex-direction:column; gap:12px;">
                        <div class="grid grid-cols-4 gap-2 tindakan-row align-items-center">
                            <div style="grid-column: span 2;">
                                <select name="tindakan_id[]" class="form-control select-tindakan" onchange="updateTarifTindakan(this)">
                                    <option value="">-- Pilih Layanan --</option>
                                    <?php foreach ($master_layanan as $ml): ?>
                                        <option value="<?= $ml['id_layanan'] ?>" data-tarif="<?= $ml['tarif_awal'] ?>">
                                            [<?= e($ml['kategori']) ?>] <?= e($ml['nama_layanan']) ?> (<?= rupiah($ml['tarif_awal']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <input type="text" name="tindakan_gigi[]" class="form-control" placeholder="Gigi (contoh: 46)">
                            </div>
                            <div class="d-flex gap-2">
                                <input type="number" name="tindakan_qty[]" value="1" min="1" class="form-control" style="width:70px;" onchange="hitungSummaryBiaya()">
                                <input type="hidden" name="tindakan_tarif[]" class="input-tarif" value="0">
                                <button type="button" class="btn btn-ghost btn-sm" onclick="this.closest('.tindakan-row').remove(); hitungSummaryBiaya();"><i class="bx bx-trash text-danger"></i></button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SEKSI 5: E-PRESCRIPTION (RESEP ELEKTRONIK KE APOTEKER) -->
                <div class="card p-6" style="border-radius:var(--radius-lg);">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h3 style="font-size:18px; color:var(--primary); margin:0;">
                            <i class="bx bxs-capsule"></i> 5. Resep Obat Elektronik
                        </h3>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="tambahBarisResep()">
                            <i class="bx bx-plus"></i> Tambah Obat
                        </button>
                    </div>

                    <div id="resep-container" style="display:flex; flex-direction:column; gap:12px;">
                        <div class="grid grid-cols-4 gap-2 resep-row align-items-center">
                            <div style="grid-column: span 1.5;">
                                <input type="text" class="form-control obat-autocomplete" placeholder="Cari nama obat..." list="obat-datalist" onchange="pilihObatAutocomplete(this)">
                                <input type="hidden" name="resep_obat_id[]" class="input-obat-id">
                            </div>
                            <div>
                                <input type="text" name="resep_dosis[]" class="form-control" placeholder="Dosis (500mg)">
                            </div>
                            <div>
                                <input type="text" name="resep_signa[]" class="form-control" placeholder="Aturan (3x1 sesudah makan)">
                            </div>
                            <div class="d-flex gap-2">
                                <input type="number" name="resep_qty[]" value="1" min="1" class="form-control" style="width:65px;">
                                <button type="button" class="btn btn-ghost btn-sm" onclick="this.closest('.resep-row').remove()"><i class="bx bx-trash text-danger"></i></button>
                            </div>
                        </div>
                    </div>
                    <datalist id="obat-datalist"></datalist>
                </div>

                <!-- SEKSI 6: TREATMENT PLAN & KONTROL BERIKUTNYA -->
                <div class="card p-6" style="border-radius:var(--radius-lg);">
                    <h3 style="font-size:18px; color:var(--primary); margin-bottom:14px;">
                        <i class="bx bx-calendar-event"></i> 6. Rencana Kunjungan Lanjutan (Treatment Plan)
                    </h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group mb-0">
                            <label class="form-label font-bold">Rencana Tindakan Kunjungan Berikutnya:</label>
                            <textarea name="treatment_plan_rencana" class="form-control" rows="2" placeholder="Contoh: Kontrol PSA saluran akar tahap 2, preparasi mahkota..."></textarea>
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label font-bold">Tanggal Kontrol Anjuran:</label>
                            <input type="date" name="treatment_plan_tanggal" class="form-control" min="<?= date('Y-m-d') ?>">
                            <span style="font-size:11px; color:var(--text-muted); margin-top:4px; display:block;">
                                Otomatis menjadi pesan notifikasi pengingat ke akun pasien.
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kolom Kanan: Summary Sticky Panel (Span 1) -->
            <div style="position:sticky; top:90px;">
                <div class="card p-5" style="border-radius:var(--radius-lg); box-shadow:var(--shadow-md); border:1px solid var(--border-color);">
                    <h3 style="font-size:18px; margin-bottom:14px; border-bottom:1px solid var(--border-color); padding-bottom:10px;">
                        <i class="bx bx-receipt text-primary"></i> Ringkasan Pemeriksaan
                    </h3>

                    <div style="display:flex; flex-direction:column; gap:8px; font-size:14px;" class="mb-4">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Biaya Konsultasi:</span>
                            <strong><?= rupiah($klinik_cfg['biaya_konsultasi']) ?></strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Estimasi Tindakan:</span>
                            <strong id="summary-tindakan">Rp 0</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Biaya Administrasi:</span>
                            <strong><?= rupiah($klinik_cfg['biaya_admin']) ?></strong>
                        </div>
                    </div>

                    <div class="alert alert-info mb-4" style="font-size:12px; line-height:1.5;">
                        <i class="bx bx-shield-check"></i>
                        Resep obat akan otomatis dikirimkan ke portal Apoteker untuk diverifikasi stok dan disiapkan.
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100" id="btn-selesai-emr">
                        <i class="bx bx-check-double"></i> Selesaikan Pemeriksaan
                    </button>
                    <a href="<?= base_url('dokter/panel_antrian.php') ?>" class="btn btn-ghost btn-sm w-100 mt-2">
                        Batal & Kembali ke Antrean
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>

<script src="<?= base_url('assets/js/odontogram.js') ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inisialisasi Odontogram
    Odontogram.init('interactive-odontogram-box', <?= json_encode($existing_odonto) ?>);

    // Load ICD-10 datalist
    fetch('<?= base_url('api/icd10_search.php') ?>')
        .then(r => r.json())
        .then(res => {
            if (res.status) {
                const dl = document.getElementById('icd10-list');
                res.data.forEach(d => {
                    const opt = document.createElement('option');
                    opt.value = d.kode;
                    opt.textContent = `${d.kode} - ${d.nama_penyakit}`;
                    dl.appendChild(opt);
                });
            }
        });

    // Load Obat datalist
    fetch('<?= base_url('api/obat_search.php') ?>')
        .then(r => r.json())
        .then(res => {
            if (res.status) {
                const dl = document.getElementById('obat-datalist');
                res.data.forEach(o => {
                    const opt = document.createElement('option');
                    opt.value = o.nama_obat;
                    opt.dataset.id = o.id_obat;
                    dl.appendChild(opt);
                });
            }
        });
});

function tambahBarisIcd10() {
    const container = document.getElementById('icd10-container');
    const div = document.createElement('div');
    div.className = 'd-flex gap-2 icd10-row';
    div.innerHTML = `
        <input type="text" name="icd10_codes[]" class="form-control icd10-autocomplete" placeholder="Ketik kode (misal: K02.1)..." list="icd10-list">
        <button type="button" class="btn btn-ghost btn-sm" onclick="this.closest('.icd10-row').remove()"><i class="bx bx-trash text-danger"></i></button>
    `;
    container.appendChild(div);
}

function tambahBarisTindakan() {
    const container = document.getElementById('tindakan-container');
    const div = document.createElement('div');
    div.className = 'grid grid-cols-4 gap-2 tindakan-row align-items-center';
    div.innerHTML = `
        <div style="grid-column: span 2;">
            <select name="tindakan_id[]" class="form-control select-tindakan" onchange="updateTarifTindakan(this)">
                <option value="">-- Pilih Layanan --</option>
                <?php foreach ($master_layanan as $ml): ?>
                    <option value="<?= $ml['id_layanan'] ?>" data-tarif="<?= $ml['tarif_awal'] ?>">
                        [<?= e($ml['kategori']) ?>] <?= e($ml['nama_layanan']) ?> (<?= rupiah($ml['tarif_awal']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <input type="text" name="tindakan_gigi[]" class="form-control" placeholder="Gigi (contoh: 46)">
        </div>
        <div class="d-flex gap-2">
            <input type="number" name="tindakan_qty[]" value="1" min="1" class="form-control" style="width:70px;" onchange="hitungSummaryBiaya()">
            <input type="hidden" name="tindakan_tarif[]" class="input-tarif" value="0">
            <button type="button" class="btn btn-ghost btn-sm" onclick="this.closest('.tindakan-row').remove(); hitungSummaryBiaya();"><i class="bx bx-trash text-danger"></i></button>
        </div>
    `;
    container.appendChild(div);
}

function updateTarifTindakan(selectEl) {
    const row = selectEl.closest('.tindakan-row');
    const opt = selectEl.options[selectEl.selectedIndex];
    const tarif = opt.dataset.tarif || 0;
    row.querySelector('.input-tarif').value = tarif;
    hitungSummaryBiaya();
}

function hitungSummaryBiaya() {
    let total = 0;
    document.querySelectorAll('.tindakan-row').forEach(row => {
        const tarif = parseFloat(row.querySelector('.input-tarif').value) || 0;
        const qty = parseInt(row.querySelector('input[name="tindakan_qty[]"]').value) || 1;
        total += (tarif * qty);
    });
    document.getElementById('summary-tindakan').textContent = 'Rp ' + total.toLocaleString('id-ID');
}

function tambahBarisResep() {
    const container = document.getElementById('resep-container');
    const div = document.createElement('div');
    div.className = 'grid grid-cols-4 gap-2 resep-row align-items-center';
    div.innerHTML = `
        <div style="grid-column: span 1.5;">
            <input type="text" class="form-control obat-autocomplete" placeholder="Cari nama obat..." list="obat-datalist" onchange="pilihObatAutocomplete(this)">
            <input type="hidden" name="resep_obat_id[]" class="input-obat-id">
        </div>
        <div>
            <input type="text" name="resep_dosis[]" class="form-control" placeholder="Dosis (500mg)">
        </div>
        <div>
            <input type="text" name="resep_signa[]" class="form-control" placeholder="Aturan (3x1 sesudah makan)">
        </div>
        <div class="d-flex gap-2">
            <input type="number" name="resep_qty[]" value="1" min="1" class="form-control" style="width:65px;">
            <button type="button" class="btn btn-ghost btn-sm" onclick="this.closest('.resep-row').remove()"><i class="bx bx-trash text-danger"></i></button>
        </div>
    `;
    container.appendChild(div);
}

function pilihObatAutocomplete(inputEl) {
    const row = inputEl.closest('.resep-row');
    const val = inputEl.value;
    const dl = document.getElementById('obat-datalist');
    const opt = Array.from(dl.options).find(o => o.value === val);
    if (opt) {
        row.querySelector('.input-obat-id').value = opt.dataset.id;
    }
}
</script>
<?php
}, 'panel_antrian');

