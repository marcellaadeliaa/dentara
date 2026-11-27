<?php
/**
 * DENTARA - Portal Pasien: Riwayat Rekam Medis (EMR View-Only)
 * File: pasien/rekam_medis.php
 */

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout_pasien.php';

require_patient();

$id_akun = (int)$_SESSION['id_akun_pasien'];
$id_pasien_aktif = (int)($_SESSION['id_pasien_aktif'] ?? 0);

render_patient_layout('Riwayat Rekam Medis', function() use ($id_pasien_aktif) {
    global $pdo;

    // Ambil semua rekam medis untuk profil pasien aktif
    $stmt = $pdo->prepare("
        SELECT rm.*, 
               u.nama_lengkap as nama_dokter, d.spesialisasi
        FROM rekam_medis rm
        JOIN dokter d ON rm.id_dokter = d.id_dokter
        JOIN pengguna u ON d.id_pengguna = u.id_pengguna
        WHERE rm.id_pasien = ?
        ORDER BY rm.tanggal_periksa DESC
    ");
    $stmt->execute([$id_pasien_aktif]);
    $rekams = $stmt->fetchAll();

    // Ambil kondisi odontogram terkini pasien
    $stmt_odonto = $pdo->prepare("
        SELECT * FROM odontogram 
        WHERE id_pasien = ? 
        ORDER BY updated_at DESC
    ");
    $stmt_odonto->execute([$id_pasien_aktif]);
    $odontos = $stmt_odonto->fetchAll();
?>
<div class="fade-in">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-4 mb-6">
        <div>
            <h1 style="font-size:var(--text-2xl); margin-bottom:4px;">Riwayat Rekam Medis & Odontogram</h1>
            <p class="text-muted" style="margin:0;">Catatan resmi diagnosa, tindakan, dan resep obat dari dokter pemeriksa Anda.</p>
        </div>
    </div>

    <!-- SEKSI 1: RINGKASAN KONDISI GIGI ODONTOGRAM TERKINI -->
    <div class="card p-6 mb-6">
        <h3 style="font-size:18px; margin-bottom:16px; display:flex; align-items:center; gap:8px;">
            <i class="bx bxs-smile text-primary"></i> Peta Odontogram Gigi Terakhir
        </h3>
        
        <?php if (empty($odontos)): ?>
            <div class="alert alert-info mb-0">
                <i class="bx bx-info-circle" style="font-size:20px;"></i>
                <span>Kondisi gigi Anda dalam keadaan sehat alami / belum ada catatan khusus pada pemeriksaan odontogram.</span>
            </div>
        <?php else: ?>
            <p class="text-muted" style="font-size:13px; margin-bottom:14px;">
                Daftar kondisi elemen gigi yang telah diperiksa oleh dokter gigi DENTARA:
            </p>
            <div class="d-flex flex-wrap gap-2">
                <?php foreach ($odontos as $od): 
                    $color_class = match($od['kondisi']) {
                        'karies'    => 'badge-danger',
                        'tambalan'  => 'badge-success',
                        'hilang'    => 'badge-gray',
                        'saluran_akar' => 'badge-info',
                        'mahkota'   => 'badge-purple',
                        default     => 'badge-warning'
                    };
                ?>
                <div class="p-2" style="background:var(--surface-subtle); border-radius:var(--radius-sm); border:1px solid var(--border-color); font-size:13px;">
                    <strong>Gigi #<?= e($od['nomor_gigi']) ?></strong>: 
                    <span class="badge <?= $color_class ?>"><?= strtoupper(str_replace('_', ' ', $od['kondisi'])) ?></span>
                    <span class="text-muted" style="font-size:11px;">(Bagian: <?= e($od['permukaan']) ?>)</span>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- SEKSI 2: TIMELINE VERTICAL RIWAYAT PEMERIKSAAN -->
    <?php if (empty($rekams)): ?>
        <div class="card empty-state">
            <?= render_mascot('thinking', 110) ?>
            <div class="empty-state-title">Belum Ada Rekam Medis</div>
            <div class="empty-state-desc">Hasil pemeriksaan, tindakan medis, dan resep obat akan otomatis muncul di sini setelah Anda menyelesaikan kunjungan.</div>
        </div>
    <?php else: ?>
        <div class="emr-timeline">
            <?php foreach ($rekams as $rm): 
                $id_rekam = (int)$rm['id_rekam'];

                // Tindakan
                $stmt_act = $pdo->prepare("
                    SELECT rt.*, l.nama_layanan 
                    FROM rekam_tindakan rt 
                    JOIN layanan l ON rt.id_layanan = l.id_layanan 
                    WHERE rt.id_rekam = ?
                ");
                $stmt_act->execute([$id_rekam]);
                $tindakans = $stmt_act->fetchAll();

                // Diagnosis ICD-10
                $stmt_diag = $pdo->prepare("
                    SELECT rd.*, icd.nama_penyakit 
                    FROM rekam_diagnosis rd 
                    JOIN icd10 icd ON rd.kode_icd10 = icd.kode 
                    WHERE rd.id_rekam = ?
                ");
                $stmt_diag->execute([$id_rekam]);
                $diagnoses = $stmt_diag->fetchAll();

                // Resep Obat
                $stmt_rx = $pdo->prepare("
                    SELECT rxd.*, o.nama_obat, o.satuan 
                    FROM resep r 
                    JOIN resep_detail rxd ON r.id_resep = rxd.id_resep 
                    JOIN obat o ON rxd.id_obat = o.id_obat 
                    WHERE r.id_rekam = ?
                ");
                $stmt_rx->execute([$id_rekam]);
                $reseps = $stmt_rx->fetchAll();
            ?>
            <div class="emr-timeline-item">
                <div class="emr-timeline-node">
                    <i class="bx bxs-check-shield"></i>
                </div>

                <div class="card p-6 card-hover">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                        <span class="badge badge-mint" style="font-size:13px;">
                            <i class="bx bx-calendar"></i> <?= tanggal_indo($rm['tanggal_periksa'], true) ?>
                        </span>
                        <span class="text-muted" style="font-size:13px;">
                            Diperiksa oleh: <strong><?= e($rm['nama_dokter']) ?></strong> (<?= e($rm['spesialisasi']) ?>)
                        </span>
                    </div>

                    <!-- Diagnosa & Asesmen -->
                    <div class="mb-4">
                        <h4 style="font-size:15px; color:var(--primary); margin-bottom:4px;">Diagnosa Klinis:</h4>
                        <div style="font-size:15px; font-weight:700; color:var(--text-main); margin-bottom:6px;">
                            <?= e($rm['asesmen']) ?>
                        </div>
                        <?php if (!empty($diagnoses)): ?>
                            <div class="d-flex flex-wrap gap-2">
                                <?php foreach ($diagnoses as $dg): ?>
                                    <span class="badge badge-purple" style="font-size:11px;">
                                        [<?= e($dg['kode_icd10']) ?>] <?= e($dg['nama_penyakit']) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Ringkasan Keluhan & Kondisi Fisik -->
                    <div class="grid grid-cols-2 gap-4 p-3 mb-4" style="background:var(--surface-subtle); border-radius:var(--radius-sm); font-size:13px;">
                        <div>
                            <span class="text-muted" style="display:block; font-size:11px;">Keluhan Pasien (Subjektif)</span>
                            <?= e($rm['subjektif']) ?>
                        </div>
                        <div>
                            <span class="text-muted" style="display:block; font-size:11px;">Pemeriksaan Dokter (Objektif)</span>
                            <?= e($rm['objektif']) ?>
                        </div>
                    </div>

                    <!-- Tindakan Medis yang Dilakukan -->
                    <?php if (!empty($tindakans)): ?>
                    <div class="mb-4">
                        <h4 style="font-size:14px; text-transform:uppercase; letter-spacing:0.5px; color:var(--text-muted); margin-bottom:8px;">
                            Tindakan Medis yang Diberikan:
                        </h4>
                        <ul style="display:flex; flex-direction:column; gap:6px; font-size:14px;">
                            <?php foreach ($tindakans as $act): ?>
                                <li style="display:flex; align-items:center; gap:8px;">
                                    <i class="bx bx-check text-success" style="font-size:18px;"></i>
                                    <strong><?= e($act['nama_layanan']) ?></strong>
                                    <?php if ($act['nomor_gigi']): ?>
                                        <span class="badge badge-mint">Gigi #<?= e($act['nomor_gigi']) ?></span>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <!-- Resep Obat yang Diterima -->
                    <?php if (!empty($reseps)): ?>
                    <div class="mb-4 p-3" style="background:rgba(91,157,245,0.08); border-radius:var(--radius-sm); border:1px solid rgba(91,157,245,0.2);">
                        <h4 style="font-size:14px; color:var(--secondary); margin-bottom:6px;">
                            <i class="bx bxs-capsule"></i> Resep Obat & Instruksi Minum:
                        </h4>
                        <ul style="display:flex; flex-direction:column; gap:6px; font-size:13px;">
                            <?php foreach ($reseps as $rx): ?>
                                <li>
                                    <strong><?= e($rx['nama_obat']) ?></strong> (<?= $rx['jumlah'] ?> <?= e($rx['satuan']) ?>): 
                                    <span class="text-primary font-bold"><?= e($rx['aturan_pakai']) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <!-- Rencana Perawatan Berikutnya -->
                    <?php if (!empty($rm['plan'])): ?>
                    <div style="font-size:13px; color:var(--text-muted); border-top:1px solid var(--border-color); padding-top:10px;">
                        <strong>Instruksi & Rencana Kontrol:</strong> <?= e($rm['plan']) ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php
}, 'rekam_medis');

