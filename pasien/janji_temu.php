<?php
/**
 * DENTARA - Portal Pasien: Kelola Janji Temu
 * File: pasien/janji_temu.php
 */

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout_pasien.php';

require_patient();

$id_akun = (int)$_SESSION['id_akun_pasien'];
$id_pasien_aktif = (int)($_SESSION['id_pasien_aktif'] ?? 0);

// Handle POST actions: Batal Mandiri & Reschedule
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        set_flash('error', 'Token Tidak Valid', 'Sesi Anda telah kedaluwarsa.');
        header('Location: ' . base_url('pasien/janji_temu.php'));
        exit;
    }

    $action = $_POST['action'] ?? '';
    $id_reservasi = (int)($_POST['id_reservasi'] ?? 0);

    // Pastikan reservasi milik akun pasien ini
    $stmt_check = $pdo->prepare("
        SELECT r.*, p.id_akun 
        FROM reservasi r 
        JOIN pasien p ON r.id_pasien = p.id_pasien 
        WHERE r.id_reservasi = ? AND p.id_akun = ?
    ");
    $stmt_check->execute([$id_reservasi, $id_akun]);
    $res = $stmt_check->fetch();

    if (!$res) {
        set_flash('error', 'Tidak Ditemukan', 'Reservasi tidak ditemukan atau bukan milik akun Anda.');
        header('Location: ' . base_url('pasien/janji_temu.php'));
        exit;
    }

    if ($action === 'batalkan') {
        // Aturan: Tidak boleh membatalkan jika kurang dari 2 jam sebelum jadwal
        $schedule_timestamp = strtotime($res['tanggal'] . ' ' . $res['jam']);
        $diff_hours = ($schedule_timestamp - time()) / 3600;

        if ($diff_hours < 2 && $diff_hours > 0) {
            set_flash('warning', 'Pembatalan Terlambat', 'Pembatalan mandiri tidak diizinkan kurang dari 2 jam sebelum jadwal. Silakan hubungi resepsionis via WhatsApp.');
            header('Location: ' . base_url('pasien/janji_temu.php'));
            exit;
        }

        $stmt_upd = $pdo->prepare("UPDATE reservasi SET status = 'dibatalkan' WHERE id_reservasi = ?");
        $stmt_upd->execute([$id_reservasi]);

        // Batalkan juga di tabel antrian jika sudah terbit
        $stmt_ant = $pdo->prepare("DELETE FROM antrian WHERE id_reservasi = ? AND status = 'menunggu'");
        $stmt_ant->execute([$id_reservasi]);

        set_flash('success', 'Berhasil Dibatalkan', "Reservasi kode {$res['kode_booking']} telah dibatalkan.");
        header('Location: ' . base_url('pasien/janji_temu.php'));
        exit;
    }

    if ($action === 'reschedule') {
        $tanggal_baru = trim($_POST['tanggal_baru'] ?? '');
        $jam_baru     = trim($_POST['jam_baru'] ?? '');
        $alasan       = trim($_POST['alasan_reschedule'] ?? '');

        if (empty($tanggal_baru) || empty($jam_baru) || $tanggal_baru < date('Y-m-d')) {
            set_flash('warning', 'Tanggal Tidak Valid', 'Pilih tanggal kunjungan yang sah.');
            header('Location: ' . base_url('pasien/janji_temu.php'));
            exit;
        }

        $stmt_upd = $pdo->prepare("
            UPDATE reservasi 
            SET tanggal = ?, jam = ?, status = 'menunggu', catatan_reschedule = ? 
            WHERE id_reservasi = ?
        ");
        $stmt_upd->execute([$tanggal_baru, $jam_baru, $alasan, $id_reservasi]);

        set_flash('success', 'Pengajuan Reschedule Terkirim', "Jadwal baru tanggal " . tanggal_indo($tanggal_baru) . " telah diajukan ke resepsionis untuk konfirmasi.");
        header('Location: ' . base_url('pasien/janji_temu.php'));
        exit;
    }
}

render_patient_layout('Daftar Janji Temu', function() use ($id_pasien_aktif) {
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT r.*, 
               u.nama_lengkap as nama_dokter, d.spesialisasi,
               l.nama_layanan,
               a.nomor_antrian
        FROM reservasi r
        JOIN dokter d ON r.id_dokter = d.id_dokter
        JOIN pengguna u ON d.id_pengguna = u.id_pengguna
        LEFT JOIN layanan l ON r.id_layanan = l.id_layanan
        LEFT JOIN antrian a ON a.id_reservasi = r.id_reservasi
        WHERE r.id_pasien = ?
        ORDER BY r.tanggal DESC, r.jam DESC
    ");
    $stmt->execute([$id_pasien_aktif]);
    $reservasis = $stmt->fetchAll();
?>
<div class="fade-in">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-4 mb-6">
        <div>
            <h1 style="font-size:var(--text-2xl); margin-bottom:4px;">Kelola Janji Temu</h1>
            <p class="text-muted" style="margin:0;">Riwayat jadwal kunjungan, reschedule, dan tiket antrean Anda.</p>
        </div>
        <a href="<?= base_url('booking.php') ?>" class="btn btn-primary btn-md">
            <i class="bx bx-calendar-plus"></i> Booking Janji Baru
        </a>
    </div>

    <?php if (empty($reservasis)): ?>
        <div class="card empty-state">
            <?= render_mascot('thinking', 110) ?>
            <div class="empty-state-title">Belum Ada Janji Temu</div>
            <div class="empty-state-desc">Profil pasien ini belum memiliki riwayat reservasi. Buat janji temu pertama sekarang!</div>
            <a href="<?= base_url('booking.php') ?>" class="btn btn-primary btn-md">
                <i class="bx bx-calendar-plus"></i> Buat Janji Sekarang
            </a>
        </div>
    <?php else: ?>
        <div style="display:flex; flex-direction:column; gap:16px;">
            <?php foreach ($reservasis as $res): 
                $badge_class = match($res['status']) {
                    'dikonfirmasi' => 'badge-success',
                    'check_in'     => 'badge-info badge-pulse',
                    'selesai'      => 'badge-mint',
                    'dibatalkan'   => 'badge-danger',
                    default        => 'badge-warning'
                };
                $can_cancel = in_array($res['status'], ['menunggu', 'dikonfirmasi']) && $res['tanggal'] >= date('Y-m-d');
            ?>
            <div class="card p-5 card-hover">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge <?= $badge_class ?>"><?= strtoupper($res['status']) ?></span>
                        <span style="font-family:var(--font-heading); font-size:18px; font-weight:700; color:var(--primary);">
                            #<?= e($res['kode_booking']) ?>
                        </span>
                        <?php if ($res['nomor_antrian']): ?>
                            <span class="badge badge-purple">Antrean A-<?= sprintf('%03d', $res['nomor_antrian']) ?></span>
                        <?php endif; ?>
                    </div>

                    <div style="font-size:13px; color:var(--text-muted); font-weight:700;">
                        <i class="bx bx-calendar"></i> <?= tanggal_indo($res['tanggal'], true) ?> pukul <?= substr($res['jam'], 0, 5) ?> WIB
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4" style="font-size:14px; border-top:1px solid var(--border-color); padding-top:12px;">
                    <div>
                        <span class="text-muted" style="display:block; font-size:12px;">Dokter Pemeriksa</span>
                        <strong><?= e($res['nama_dokter']) ?></strong>
                        <span style="display:block; font-size:12px; color:var(--accent);">Spesialis <?= e($res['spesialisasi']) ?></span>
                    </div>

                    <div>
                        <span class="text-muted" style="display:block; font-size:12px;">Layanan</span>
                        <strong><?= e($res['nama_layanan'] ?: 'Pemeriksaan Umum') ?></strong>
                    </div>

                    <div>
                        <span class="text-muted" style="display:block; font-size:12px;">Keluhan</span>
                        <span><?= e($res['keluhan'] ?: '-') ?></span>
                    </div>
                </div>

                <?php if ($res['catatan_reschedule']): ?>
                <div class="mt-3 p-2" style="background:var(--warning-light); color:var(--warning-dark); font-size:12px; border-radius:var(--radius-sm);">
                    <i class="bx bx-time"></i> Catatan Reschedule: <?= e($res['catatan_reschedule']) ?>
                </div>
                <?php endif; ?>

                <!-- Tombol Aksi -->
                <div class="d-flex justify-content-end gap-2 mt-4 pt-3" style="border-top:1px solid var(--border-color);">
                    <a href="<?= base_url('booking_sukses.php?kode=' . $res['kode_booking']) ?>" class="btn btn-secondary btn-sm">
                        <i class="bx bx-show"></i> Tiket & QR
                    </a>

                    <?php if ($can_cancel): ?>
                        <button type="button" class="btn btn-outline btn-sm" onclick="showRescheduleModal(<?= $res['id_reservasi'] ?>, '<?= $res['kode_booking'] ?>')">
                            <i class="bx bx-calendar-edit"></i> Reschedule
                        </button>
                        <button type="button" class="btn btn-danger btn-sm" onclick="confirmBatal(<?= $res['id_reservasi'] ?>, '<?= $res['kode_booking'] ?>')">
                            <i class="bx bx-x"></i> Batalkan
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Form Hidden untuk Cancel -->
<form id="form-cancel" action="<?= base_url('pasien/janji_temu.php') ?>" method="POST" style="display:none;">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="batalkan">
    <input type="hidden" name="id_reservasi" id="cancel-id-reservasi">
</form>

<script>
function confirmBatal(id, kode) {
    DENTARA.confirm(
        'Batalkan Janji Temu?', 
        `Apakah Anda yakin ingin membatalkan reservasi <strong>#${kode}</strong>? Sesuai kebijakan klinik, pembatalan tidak dapat dilakukan kurang dari 2 jam sebelum jadwal.`,
        'Ya, Batalkan Janji',
        'Kembali'
    ).then(confirmed => {
        if (confirmed) {
            document.getElementById('cancel-id-reservasi').value = id;
            document.getElementById('form-cancel').submit();
        }
    });
}

function showRescheduleModal(id, kode) {
    Swal.fire({
        title: `Reschedule Janji #${kode}`,
        html: `
            <form id="swal-reschedule-form" class="text-left" style="font-size:14px;">
                <div class="form-group mb-3">
                    <label class="form-label required">Pilih Tanggal Baru:</label>
                    <input type="date" id="swal-tanggal" class="form-control" min="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="form-group mb-3">
                    <label class="form-label required">Estimasi Jam Baru:</label>
                    <select id="swal-jam" class="form-control" required>
                        <option value="09:00:00">09:00 WIB</option>
                        <option value="10:00:00">10:00 WIB</option>
                        <option value="11:00:00">11:00 WIB</option>
                        <option value="15:00:00">15:00 WIB</option>
                        <option value="16:00:00">16:00 WIB</option>
                        <option value="17:00:00">17:00 WIB</option>
                        <option value="19:00:00">19:00 WIB</option>
                    </select>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">Alasan Perubahan:</label>
                    <textarea id="swal-alasan" class="form-control" rows="2" placeholder="Contoh: Ada keperluan mendadak..."></textarea>
                </div>
            </form>
        `,
        showCancelButton: true,
        confirmButtonText: 'Ajukan Tanggal Baru',
        cancelButtonText: 'Tutup',
        preConfirm: () => {
            const tanggal = document.getElementById('swal-tanggal').value;
            const jam = document.getElementById('swal-jam').value;
            const alasan = document.getElementById('swal-alasan').value;
            if (!tanggal) {
                Swal.showValidationMessage('Harap pilih tanggal kunjungan baru.');
                return false;
            }
            return { tanggal, jam, alasan };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?= base_url('pasien/janji_temu.php') ?>';

            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = 'csrf_token';
            csrf.value = '<?= csrf_token() ?>';
            form.appendChild(csrf);

            const act = document.createElement('input');
            act.type = 'hidden';
            act.name = 'action';
            act.value = 'reschedule';
            form.appendChild(act);

            const idField = document.createElement('input');
            idField.type = 'hidden';
            idField.name = 'id_reservasi';
            idField.value = id;
            form.appendChild(idField);

            const tglField = document.createElement('input');
            tglField.type = 'hidden';
            tglField.name = 'tanggal_baru';
            tglField.value = result.value.tanggal;
            form.appendChild(tglField);

            const jamField = document.createElement('input');
            jamField.type = 'hidden';
            jamField.name = 'jam_baru';
            jamField.value = result.value.jam;
            form.appendChild(jamField);

            const alasanField = document.createElement('input');
            alasanField.type = 'hidden';
            alasanField.name = 'alasan_reschedule';
            alasanField.value = result.value.alasan;
            form.appendChild(alasanField);

            document.body.appendChild(form);
            form.submit();
        }
    });
}
</script>
<?php
}, 'janji_temu');

