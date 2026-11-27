<?php
/**
 * DENTARA - Portal Pasien: Riwayat Tagihan & Kuitansi
 * File: pasien/tagihan.php
 */

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout_pasien.php';

require_patient();

$id_akun = (int)$_SESSION['id_akun_pasien'];
$id_pasien_aktif = (int)($_SESSION['id_pasien_aktif'] ?? 0);

render_patient_layout('Riwayat Tagihan & Pembayaran', function() use ($id_pasien_aktif) {
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT t.*, 
               pemb.no_kuitansi, pemb.metode, pemb.tanggal_bayar
        FROM tagihan t
        LEFT JOIN pembayaran pemb ON pemb.id_tagihan = t.id_tagihan
        WHERE t.id_pasien = ?
        ORDER BY t.created_at DESC
    ");
    $stmt->execute([$id_pasien_aktif]);
    $tagihans = $stmt->fetchAll();
?>
<div class="fade-in">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-4 mb-6">
        <div>
            <h1 style="font-size:var(--text-2xl); margin-bottom:4px;">Riwayat Tagihan & Transaksi</h1>
            <p class="text-muted" style="margin:0;">Rincian biaya perawatan, pembayaran, dan cetak kuitansi digital.</p>
        </div>
    </div>

    <?php if (empty($tagihans)): ?>
        <div class="card empty-state">
            <?= render_mascot('happy', 110) ?>
            <div class="empty-state-title">Tidak Ada Tagihan Aktif</div>
            <div class="empty-state-desc">Seluruh kewajiban pembayaran perawatan gigi Anda telah berstatus lunas atau belum ada tagihan baru.</div>
        </div>
    <?php else: ?>
        <div style="display:flex; flex-direction:column; gap:16px;">
            <?php foreach ($tagihans as $tag): 
                $id_tagihan = (int)$tag['id_tagihan'];
                $is_lunas = $tag['status'] === 'lunas';

                // Ambil rincian tagihan detail
                $stmt_dtl = $pdo->prepare("SELECT * FROM tagihan_detail WHERE id_tagihan = ?");
                $stmt_dtl->execute([$id_tagihan]);
                $details = $stmt_dtl->fetchAll();
            ?>
            <div class="card p-6 card-hover">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
                    <div class="d-flex align-items-center gap-3">
                        <span class="badge <?= $is_lunas ? 'badge-success' : 'badge-warning' ?>" style="font-size:13px;">
                            <?= $is_lunas ? 'LUNAS' : 'MENUNGGU PEMBAYARAN DI KASIR' ?>
                        </span>
                        <?php if ($tag['no_kuitansi']): ?>
                            <span style="font-family:var(--font-heading); font-size:16px; font-weight:700; color:var(--text-main);">
                                No: <?= e($tag['no_kuitansi']) ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <div style="font-size:13px; color:var(--text-muted);">
                        <i class="bx bx-calendar"></i> <?= tanggal_indo(date('Y-m-d', strtotime($tag['created_at'])), true) ?>
                    </div>
                </div>

                <!-- Tabel Item Tagihan -->
                <div class="table-wrapper mb-4">
                    <table class="table-custom">
                        <thead>
                            <tr>
                                <th>Deskripsi Layanan / Item</th>
                                <th class="text-center">Qty</th>
                                <th class="text-right">Tarif</th>
                                <th class="text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($details as $d): ?>
                            <tr>
                                <td>
                                    <span class="badge badge-mint" style="font-size:10px; margin-right:6px;"><?= strtoupper($d['jenis']) ?></span>
                                    <strong><?= e($d['deskripsi']) ?></strong>
                                </td>
                                <td class="text-center"><?= $d['qty'] ?></td>
                                <td class="text-right"><?= rupiah($d['harga']) ?></td>
                                <td class="text-right font-bold"><?= rupiah($d['subtotal']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Ringkasan Biaya & Split Bill -->
                <div class="d-flex justify-content-between flex-wrap gap-4 pt-3" style="border-top:1px solid var(--border-color); font-size:14px;">
                    <div>
                        <?php if ($tag['metode']): ?>
                            <span class="text-muted">Metode Pembayaran:</span>
                            <span class="badge badge-purple" style="font-size:12px;"><?= strtoupper($tag['metode']) ?></span>
                        <?php endif; ?>
                    </div>

                    <div style="min-width:260px;">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Total Tagihan:</span>
                            <strong><?= rupiah($tag['total_biaya']) ?></strong>
                        </div>
                        <?php if ($tag['ditanggung_asuransi'] > 0): ?>
                        <div class="d-flex justify-content-between mb-1" style="color:var(--secondary);">
                            <span>Ditanggung Asuransi:</span>
                            <span>- <?= rupiah($tag['ditanggung_asuransi']) ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="d-flex justify-content-between pt-2" style="border-top:2px solid var(--border-color); font-size:18px; font-family:var(--font-heading);">
                            <span>Total Pasien:</span>
                            <span style="color:var(--primary); font-weight:800;"><?= rupiah($tag['ditanggung_pasien']) ?></span>
                        </div>
                    </div>
                </div>

                <?php if ($is_lunas): ?>
                <div class="d-flex justify-content-end mt-4 pt-3" style="border-top:1px solid var(--border-color);">
                    <button class="btn btn-secondary btn-sm" onclick="window.print()">
                        <i class="bx bx-printer"></i> Cetak Kuitansi Resmi
                    </button>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php
}, 'tagihan');

