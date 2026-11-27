<?php
/**
 * DENTARA - Portal Resepsionis: Konfirmasi Reservasi Online
 * File: resepsionis/konfirmasi_reservasi.php
 */

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout_staff.php';

require_role(['resepsionis', 'admin']);

// Handle POST: Setujui / Tolak
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        header('Location: ' . base_url('resepsionis/konfirmasi_reservasi.php'));
        exit;
    }

    $action = $_POST['action'] ?? '';
    $id_res = (int)($_POST['id_reservasi'] ?? 0);

    if ($action === 'setujui' && $id_res) {
        $stmt_setuju = $pdo->prepare("UPDATE reservasi SET status = 'dikonfirmasi' WHERE id_reservasi = ?");
        $stmt_setuju->execute([$id_res]);

        // Ambil info pasien untuk notifikasi
        $stmt_info = $pdo->prepare("SELECT id_pasien, kode_booking, tanggal FROM reservasi WHERE id_reservasi = ?");
        $stmt_info->execute([$id_res]);
        $info = $stmt_info->fetch();

        if ($info && $info['id_pasien']) {
            kirim_notifikasi_pasien(
                $pdo,
                (int)$info['id_pasien'],
                'Reservasi Dikonfirmasi!',
                "Reservasi Anda kode {$info['kode_booking']} untuk tanggal " . tanggal_indo($info['tanggal']) . " telah dikonfirmasi oleh resepsionis klinik."
            );
        }

        set_flash('success', 'Reservasi Dikonfirmasi', 'Jadwal pasien telah disetujui.');
    } elseif ($action === 'tolak' && $id_res) {
        $alasan = trim($_POST['alasan_tolak'] ?? 'Jadwal dokter berhalangan');
        $stmt_tolak = $pdo->prepare("UPDATE reservasi SET status = 'dibatalkan', keluhan = CONCAT(COALESCE(keluhan, ''), ' [Ditolak: ', ?, ']') WHERE id_reservasi = ?");
        $stmt_tolak->execute([$alasan, $id_res]);

        $stmt_info = $pdo->prepare("SELECT id_pasien, kode_booking FROM reservasi WHERE id_reservasi = ?");
        $stmt_info->execute([$id_res]);
        $info = $stmt_info->fetch();

        if ($info && $info['id_pasien']) {
            kirim_notifikasi_pasien(
                $pdo,
                (int)$info['id_pasien'],
                'Pemberitahuan Reservasi',
                "Maaf, reservasi kode {$info['kode_booking']} tidak dapat disetujui: {$alasan}. Silakan hubungi kami untuk menjadwalkan ulang."
            );
        }

        set_flash('info', 'Reservasi Ditolak', 'Permintaan reservasi telah dibatalkan.');
    }

    header('Location: ' . base_url('resepsionis/konfirmasi_reservasi.php'));
    exit;
}

render_staff_layout('Konfirmasi Reservasi Online', function() {
    global $pdo;

    $stmt = $pdo->query("
        SELECT r.*, 
               COALESCE(p.nama_pasien, r.nama_tamu) as nama_pasien,
               COALESCE(p.no_hp, r.no_hp_tamu) as no_hp,
               p.no_rm,
               u.nama_lengkap as nama_dokter, d.spesialisasi,
               l.nama_layanan
        FROM reservasi r
        LEFT JOIN pasien p ON r.id_pasien = p.id_pasien
        JOIN dokter d ON r.id_dokter = d.id_dokter
        JOIN pengguna u ON d.id_pengguna = u.id_pengguna
        LEFT JOIN layanan l ON r.id_layanan = l.id_layanan
        WHERE r.status = 'menunggu'
        ORDER BY r.tanggal ASC, r.jam ASC
    ");
    $reservasis = $stmt->fetchAll();
?>
<div class="fade-in">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-4 mb-4">
        <div>
            <h1 style="font-size:var(--text-2xl); margin-bottom:4px; font-family:var(--font-heading); color:#0F172A;">Konfirmasi Reservasi Masuk</h1>
            <p class="text-muted" style="margin:0; font-size:13.5px;">Tinjau pengajuan jadwal reservasi online pasien yang menunggu konfirmasi.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge badge-warning" style="font-size:13px; padding:7px 14px; font-weight:750; border-radius:20px;">
                <i class="bx bx-time-five"></i> <?= count($reservasis) ?> Menunggu Konfirmasi
            </span>
            <div class="btn-group" style="background:#F1F5F9; padding:3px; border-radius:10px; border:1px solid #E2E8F0;">
                <button type="button" id="btnViewCards" class="btn btn-sm" onclick="setViewMode('cards')" style="background:#FFFFFF; color:#0F766E; font-weight:700; border-radius:8px; border:none; box-shadow:0 1px 3px rgba(0,0,0,0.06); padding:5px 12px; font-size:12px;">
                    <i class="bx bx-grid-alt"></i> Kartu
                </button>
                <button type="button" id="btnViewTable" class="btn btn-sm" onclick="setViewMode('table')" style="background:transparent; color:#64748B; font-weight:600; border-radius:8px; border:none; padding:5px 12px; font-size:12px;">
                    <i class="bx bx-table"></i> Tabel
                </button>
            </div>
        </div>
    </div>

    <?php if (empty($reservasis)): ?>
        <div class="card empty-state" style="border-radius:16px; border:1px solid #E2E8F0; padding:48px 24px;">
            <?= render_mascot('happy', 110) ?>
            <div class="empty-state-title" style="font-family:var(--font-heading); margin-top:16px; font-size:18px;">Semua Reservasi Terkonfirmasi</div>
            <div class="empty-state-desc" style="color:#64748B; font-size:13.5px; max-width:400px; margin:6px auto 0;">Tidak ada antrean reservasi online yang menunggu persetujuan saat ini.</div>
        </div>
    <?php else: ?>
        <!-- 1. TAMPILAN KARTU INTERAKTIF (CARD FEED - Modern & Menarik) -->
        <div id="container-cards" class="dentara-card-list">
            <?php foreach ($reservasis as $r): 
                $initials = strtoupper(substr($r['nama_pasien'], 0, 2));
                $phone_clean = preg_replace('/[^0-9]/', '', $r['no_hp']);
                if (substr($phone_clean, 0, 1) === '0') {
                    $phone_clean = '62' . substr($phone_clean, 1);
                }
            ?>
            <div class="dentara-card-item accent-left-teal">
                <!-- Header Kartu -->
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 pb-3 mb-3 border-bottom" style="border-color:#F1F5F9 !important;">
                    <div class="d-flex align-items-center gap-2">
                        <span style="font-family:var(--font-heading); font-size:16px; font-weight:800; color:#0F766E; background:#CCFBF1; padding:4px 12px; border-radius:8px; border:1px solid #99F6E4; letter-spacing:0.5px;">
                            <?= e($r['kode_booking']) ?>
                        </span>
                        <span class="badge badge-mint" style="font-weight:700; font-size:11px;">
                            <i class="bx bx-globe"></i> <?= strtoupper(e($r['sumber'])) ?>
                        </span>
                    </div>
                    <div style="background:#F8FAFC; color:#1E293B; font-weight:700; font-size:13px; padding:6px 14px; border-radius:20px; border:1px solid #E2E8F0; display:inline-flex; align-items:center; gap:8px;">
                        <span><i class="bx bx-calendar text-primary" style="font-size:15px;"></i> <?= tanggal_indo($r['tanggal']) ?></span>
                        <span style="color:#CBD5E1;">&bull;</span>
                        <span style="color:#0F766E;"><i class="bx bx-time text-primary" style="font-size:15px;"></i> <?= substr($r['jam'], 0, 5) ?> WIB</span>
                    </div>
                </div>

                <!-- Konten Utama Kartu (3 Kolom Berimbang) -->
                <div class="row g-3 align-items-center">
                    <!-- Kolom 1: Profil Pasien -->
                    <div class="col-12 col-md-4">
                        <div class="d-flex align-items-center gap-3">
                            <div style="width:46px; height:46px; border-radius:14px; background:linear-gradient(135deg, #2BB5A0, #5B9DF5); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:800; font-family:var(--font-heading); font-size:16px; flex-shrink:0; box-shadow:0 4px 10px rgba(43,181,160,0.25);">
                                <?= $initials ?>
                            </div>
                            <div>
                                <div style="font-weight:800; font-size:15px; color:#0F172A; line-height:1.3;">
                                    <?= e($r['nama_pasien']) ?>
                                </div>
                                <div class="d-flex align-items-center gap-2 mt-1">
                                    <?php if ($r['no_rm']): ?>
                                        <span class="badge badge-purple" style="font-size:10px; padding:2px 8px;"><?= e($r['no_rm']) ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary" style="font-size:10px; padding:2px 8px;">Pasien Baru</span>
                                    <?php endif; ?>
                                </div>
                                <div class="mt-1">
                                    <a href="https://wa.me/<?= $phone_clean ?>" target="_blank" class="btn btn-sm" style="background:#ECFDF5; color:#065F46; border:1px solid #A7F3D0; font-weight:600; font-size:11.5px; padding:2px 10px; border-radius:14px; text-decoration:none;">
                                        <i class="bx bxl-whatsapp text-success" style="font-size:14px;"></i> <?= e($r['no_hp']) ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Kolom 2: Dokter & Layanan -->
                    <div class="col-12 col-md-4">
                        <div style="padding:0 8px;">
                            <div style="font-size:14.5px; font-weight:750; color:#1E293B;">
                                <i class="bx bx-user-pin text-primary" style="font-size:17px; vertical-align:middle;"></i> <?= e($r['nama_dokter']) ?>
                            </div>
                            <div style="font-size:13px; color:#0F766E; font-weight:600; margin-top:3px;">
                                <i class="bx bx-plus-medical text-primary" style="font-size:12px;"></i> <?= e($r['nama_layanan'] ?: 'Pemeriksaan Umum') ?>
                            </div>
                        </div>
                    </div>

                    <!-- Kolom 3: Keluhan Pasien -->
                    <div class="col-12 col-md-4">
                        <div class="dentara-quote-bubble">
                            <i class="bx bxs-quote-left"></i>
                            <div style="min-width:0;">
                                <span style="font-weight:750; font-size:11px; color:#64748B; display:block; text-transform:uppercase; letter-spacing:0.5px;">Keluhan:</span>
                                <span style="color:#1E293B; font-weight:500; font-size:13px; line-height:1.4;">
                                    <?= e($r['keluhan'] ?: 'Tidak ada keluhan tertulis.') ?>
                                </span>
                                <?php if ($r['catatan_reschedule']): ?>
                                    <div class="badge badge-warning mt-1" style="font-size:10.5px;">Reschedule: <?= e($r['catatan_reschedule']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Aksi Resepsionis -->
                <div class="d-flex align-items-center justify-content-end gap-2 pt-3 mt-3 border-top" style="border-color:#F1F5F9 !important;">
                    <form action="<?= base_url('resepsionis/konfirmasi_reservasi.php') ?>" method="POST" onsubmit="return confirm('Setujui reservasi pasien ini?')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="setujui">
                        <input type="hidden" name="id_reservasi" value="<?= $r['id_reservasi'] ?>">
                        <button type="submit" class="btn" style="background:linear-gradient(135deg, #10B981, #059669); color:#FFFFFF; border:none; padding:8px 20px; font-weight:750; border-radius:10px; box-shadow:0 3px 8px rgba(16,185,129,0.25); font-size:13px;">
                            <i class="bx bx-check-circle" style="font-size:16px;"></i> Setujui Reservasi
                        </button>
                    </form>

                    <button type="button" class="btn btn-outline-danger btn-sm" style="border-radius:10px; padding:7px 16px; font-weight:600; font-size:13px;" onclick="tolakReservasi(<?= $r['id_reservasi'] ?>, '<?= $r['kode_booking'] ?>')">
                        <i class="bx bx-x-circle" style="font-size:16px;"></i> Tolak
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- 2. TAMPILAN TABEL BERSIH (CLEAN TABLE VIEW - Tanpa Garis Kolom Kaku) -->
        <div id="container-table" class="table-responsive" style="display:none;">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>Kode Booking</th>
                        <th>Pasien & Kontak</th>
                        <th>Jadwal Kunjungan</th>
                        <th>Dokter & Layanan</th>
                        <th>Keluhan</th>
                        <th class="text-center">Aksi Resepsionis</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reservasis as $r): ?>
                    <tr>
                        <td>
                            <strong style="font-family:var(--font-heading); font-size:15px; color:#0F766E;">
                                <?= e($r['kode_booking']) ?>
                            </strong>
                            <span class="badge badge-mint" style="display:block; width:fit-content; margin-top:2px;">
                                <?= strtoupper($r['sumber']) ?>
                            </span>
                        </td>
                        <td>
                            <strong><?= e($r['nama_pasien']) ?></strong>
                            <?php if ($r['no_rm']): ?>
                                <span class="badge badge-purple" style="font-size:10px;"><?= e($r['no_rm']) ?></span>
                            <?php endif; ?>
                            <div style="font-size:12px; color:var(--text-muted); margin-top:2px;">
                                <i class="bx bxl-whatsapp text-success"></i> <?= e($r['no_hp']) ?>
                            </div>
                        </td>
                        <td>
                            <strong><?= tanggal_indo($r['tanggal']) ?></strong>
                            <div style="font-size:12px; color:var(--primary); font-weight:700;">
                                <?= substr($r['jam'], 0, 5) ?> WIB
                            </div>
                        </td>
                        <td>
                            <div><strong><?= e($r['nama_dokter']) ?></strong></div>
                            <span class="text-muted" style="font-size:12px;"><?= e($r['nama_layanan'] ?: 'Pemeriksaan Umum') ?></span>
                        </td>
                        <td style="max-width:220px; font-size:13px; color:var(--text-muted);">
                            <?= e($r['keluhan'] ?: '-') ?>
                            <?php if ($r['catatan_reschedule']): ?>
                                <div class="badge badge-warning" style="margin-top:4px;">Reschedule: <?= e($r['catatan_reschedule']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-2">
                                <form action="<?= base_url('resepsionis/konfirmasi_reservasi.php') ?>" method="POST" onsubmit="return confirm('Setujui reservasi pasien ini?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="setujui">
                                    <input type="hidden" name="id_reservasi" value="<?= $r['id_reservasi'] ?>">
                                    <button type="submit" class="btn btn-success btn-sm" style="border-radius:8px; font-weight:700;">
                                        <i class="bx bx-check"></i> Setujui
                                    </button>
                                </form>

                                <button type="button" class="btn btn-danger btn-sm" style="border-radius:8px; font-weight:700;" onclick="tolakReservasi(<?= $r['id_reservasi'] ?>, '<?= $r['kode_booking'] ?>')">
                                    <i class="bx bx-x"></i> Tolak
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
function setViewMode(mode) {
    const cardContainer = document.getElementById('container-cards');
    const tableContainer = document.getElementById('container-table');
    const btnCards = document.getElementById('btnViewCards');
    const btnTable = document.getElementById('btnViewTable');

    if (mode === 'table') {
        if (cardContainer) cardContainer.style.display = 'none';
        if (tableContainer) tableContainer.style.display = 'block';
        btnTable.style.background = '#FFFFFF';
        btnTable.style.color = '#0F766E';
        btnTable.style.boxShadow = '0 1px 3px rgba(0,0,0,0.06)';
        btnCards.style.background = 'transparent';
        btnCards.style.color = '#64748B';
        btnCards.style.boxShadow = 'none';
    } else {
        if (cardContainer) cardContainer.style.display = 'flex';
        if (tableContainer) tableContainer.style.display = 'none';
        btnCards.style.background = '#FFFFFF';
        btnCards.style.color = '#0F766E';
        btnCards.style.boxShadow = '0 1px 3px rgba(0,0,0,0.06)';
        btnTable.style.background = 'transparent';
        btnTable.style.color = '#64748B';
        btnTable.style.boxShadow = 'none';
    }
}
</script>
</div>

<!-- Modal / Form Tolak via SweetAlert2 -->
<script>
function tolakReservasi(id, kode) {
    Swal.fire({
        title: `Tolak Reservasi #${kode}?`,
        input: 'textarea',
        inputLabel: 'Masukkan Alasan Penolakan:',
        inputPlaceholder: 'Contoh: Kuota dokter hari tersebut telah penuh / dokter berhalangan hadir...',
        inputValidator: (value) => {
            if (!value) {
                return 'Wajib mengisi alasan penolakan!';
            }
        },
        showCancelButton: true,
        confirmButtonText: 'Tolak Reservasi',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#EF4444'
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?= base_url('resepsionis/konfirmasi_reservasi.php') ?>';

            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = 'csrf_token';
            csrf.value = '<?= csrf_token() ?>';
            form.appendChild(csrf);

            const act = document.createElement('input');
            act.type = 'hidden';
            act.name = 'action';
            act.value = 'tolak';
            form.appendChild(act);

            const idField = document.createElement('input');
            idField.type = 'hidden';
            idField.name = 'id_reservasi';
            idField.value = id;
            form.appendChild(idField);

            const alasanField = document.createElement('input');
            alasanField.type = 'hidden';
            alasanField.name = 'alasan_tolak';
            alasanField.value = result.value;
            form.appendChild(alasanField);

            document.body.appendChild(form);
            form.submit();
        }
    });
}
</script>
<?php
}, 'konfirmasi');

