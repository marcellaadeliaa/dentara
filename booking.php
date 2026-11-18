<?php
/**
 * DENTARA - Formulir Booking Online
 * File: booking.php
 */

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout_public.php';

render_public_layout('Reservasi Janji Temu Online', function() {
    global $pdo;

    $selected_dokter  = (int)($_GET['dokter'] ?? 0);
    $selected_layanan = (int)($_GET['layanan'] ?? 0);

    // Ambil daftar dokter aktif
    $stmt_doc = $pdo->query("
        SELECT d.id_dokter, d.spesialisasi, p.nama_lengkap 
        FROM dokter d 
        JOIN pengguna p ON d.id_pengguna = p.id_pengguna 
        WHERE p.status = 'aktif'
        ORDER BY p.nama_lengkap ASC
    ");
    $dokters = $stmt_doc->fetchAll();

    // Ambil daftar layanan aktif
    $stmt_lay = $pdo->query("SELECT id_layanan, nama_layanan, kategori, tarif_awal FROM layanan WHERE status = 'aktif' ORDER BY kategori ASC, nama_layanan ASC");
    $layanans = $stmt_lay->fetchAll();

    // Cek apakah pasien sedang login
    $is_logged_in = !empty($_SESSION['id_akun_pasien']);
    $patient_profiles = [];
    if ($is_logged_in) {
        $stmt_prof = $pdo->prepare("SELECT id_pasien, nama_pasien, no_rm, hubungan_keluarga, no_hp FROM pasien WHERE id_akun = ?");
        $stmt_prof->execute([(int)$_SESSION['id_akun_pasien']]);
        $patient_profiles = $stmt_prof->fetchAll();
    }
?>
<div class="container" style="padding-top:110px; padding-bottom:60px; max-width:860px;">
    <div class="text-center mb-6">
        <span class="badge badge-mint mb-2">RESERVASI DIGITAL PRAKTIS</span>
        <h1 style="font-size:clamp(24px, 4vw, 36px); margin-bottom:8px;">Booking Janji Temu Online</h1>
        <p class="text-muted">Dapatkan kepastian jadwal dokter, nomor antrean, dan kode booking otomatis.</p>
    </div>

    <div class="card p-6" style="border-radius:var(--radius-lg); box-shadow:var(--shadow-lg);">
        <form action="<?= base_url('includes/process/booking_process.php') ?>" method="POST" id="booking-form">
            <?= csrf_field() ?>

            <!-- LANGKAH 1: DOKTER & LAYANAN -->
            <h3 style="font-size:18px; color:var(--primary); margin-bottom:16px;">
                <i class="bx bxs-user-badge"></i> 1. Pilih Dokter & Tindakan Medis
            </h3>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div class="form-group">
                    <label class="form-label required">Dokter Spesialis</label>
                    <select name="id_dokter" id="booking-dokter" class="form-control" required onchange="cekKuotaJadwal()">
                        <option value="">-- Pilih Dokter Gigi --</option>
                        <?php foreach ($dokters as $doc): ?>
                            <option value="<?= $doc['id_dokter'] ?>" <?= $selected_dokter === (int)$doc['id_dokter'] ? 'selected' : '' ?>>
                                <?= e($doc['nama_lengkap']) ?> (Spesialis <?= e($doc['spesialisasi']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Layanan / Tindakan Utama</label>
                    <select name="id_layanan" class="form-control">
                        <option value="">-- Konsultasi & Pemeriksaan Umum --</option>
                        <?php foreach ($layanans as $lay): ?>
                            <option value="<?= $lay['id_layanan'] ?>" <?= $selected_layanan === (int)$lay['id_layanan'] ? 'selected' : '' ?>>
                                [<?= e($lay['kategori']) ?>] <?= e($lay['nama_layanan']) ?> (Mulai <?= rupiah($lay['tarif_awal']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- LANGKAH 2: TANGGAL & JAM -->
            <h3 style="font-size:18px; color:var(--primary); margin:24px 0 16px;">
                <i class="bx bxs-calendar"></i> 2. Jadwal & Waktu Kunjungan
            </h3>

            <div class="grid grid-cols-2 gap-4 mb-3">
                <div class="form-group">
                    <label class="form-label required">Tanggal Kunjungan</label>
                    <input type="date" name="tanggal" id="booking-tanggal" class="form-control" 
                           value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>" required onchange="cekKuotaJadwal()">
                </div>

                <div class="form-group">
                    <label class="form-label required">Estimasi Jam Kehadiran</label>
                    <select name="jam" id="booking-jam" class="form-control" required>
                        <option value="09:00:00">09:00 WIB</option>
                        <option value="10:00:00">10:00 WIB</option>
                        <option value="11:00:00">11:00 WIB</option>
                        <option value="15:00:00">15:00 WIB</option>
                        <option value="16:00:00">16:00 WIB</option>
                        <option value="17:00:00">17:00 WIB</option>
                        <option value="19:00:00">19:00 WIB</option>
                    </select>
                </div>
            </div>

            <!-- Box Status Kuota Realtime via AJAX -->
            <div id="kuota-status-box" class="mb-4">
                <div class="alert alert-info">
                    <i class="bx bx-info-circle" style="font-size:20px;"></i>
                    <span>Silakan pilih dokter dan tanggal untuk memeriksa ketersediaan kuota.</span>
                </div>
            </div>

            <!-- LANGKAH 3: IDENTITAS PASIEN -->
            <h3 style="font-size:18px; color:var(--primary); margin:24px 0 16px;">
                <i class="bx bxs-id-card"></i> 3. Data Identitas Pasien
            </h3>

            <?php if ($is_logged_in && !empty($patient_profiles)): ?>
                <!-- Pilihan Profil Pasien Login -->
                <div class="form-group mb-4">
                    <label class="form-label required">Pilih Profil Pasien Terdaftar</label>
                    <select name="id_pasien" class="form-control" required>
                        <?php foreach ($patient_profiles as $prof): ?>
                            <option value="<?= $prof['id_pasien'] ?>">
                                <?= e($prof['nama_pasien']) ?> (No RM: <?= e($prof['no_rm']) ?> - Hubungan: <?= e($prof['hubungan_keluarga']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php else: ?>
                <!-- Form Pasien Tamu (Guest) -->
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div class="form-group">
                        <label class="form-label required">Nama Lengkap Pasien</label>
                        <div class="input-icon-group">
                            <i class="bx bx-user"></i>
                            <input type="text" name="nama_tamu" class="form-control" placeholder="Nama lengkap sesuai KTP" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label required">Nomor WhatsApp Aktif</label>
                        <div class="input-icon-group">
                            <i class="bx bxl-whatsapp"></i>
                            <input type="tel" name="no_hp_tamu" class="form-control" placeholder="08xxxxxxxxxx" required>
                        </div>
                    </div>
                </div>
                <div class="alert alert-info mb-4" style="font-size:13px;">
                    <i class="bx bx-bulb"></i>
                    <span>Punya akun pasien DENTARA? <a href="<?= base_url('login_pasien.php') ?>" class="font-bold text-primary">Masuk di sini</a> agar riwayat terhubung otomatis.</span>
                </div>
            <?php endif; ?>

            <!-- Keluhan Utama -->
            <div class="form-group mb-4">
                <label class="form-label">Keluhan atau Catatan Gejala</label>
                <textarea name="keluhan" class="form-control" rows="3" placeholder="Contoh: Gigi geraham kiri bawah ngilu saat minum air dingin sejak 3 hari lalu..."></textarea>
            </div>

            <div class="pt-4" style="border-top:1px solid var(--border-color);">
                <button type="submit" id="btn-submit-booking" class="btn btn-primary btn-lg w-100">
                    <i class="bx bx-check-double"></i> Konfirmasi Booking & Terbitkan Tiket Antrean
                </button>
            </div>
        </form>
    </div>
</div>

<script>
async function cekKuotaJadwal() {
    const dokterId = document.getElementById('booking-dokter').value;
    const tanggal = document.getElementById('booking-tanggal').value;
    const box = document.getElementById('kuota-status-box');
    const submitBtn = document.getElementById('btn-submit-booking');

    if (!dokterId || !tanggal) {
        box.innerHTML = `
            <div class="alert alert-info">
                <i class="bx bx-info-circle" style="font-size:20px;"></i>
                <span>Silakan pilih dokter dan tanggal untuk memeriksa ketersediaan kuota.</span>
            </div>
        `;
        submitBtn.disabled = false;
        return;
    }

    box.innerHTML = `
        <div class="alert alert-info">
            <i class="bx bx-loader-alt bx-spin" style="font-size:20px;"></i>
            <span>Memeriksa jadwal operasional dan sisa kuota dokter...</span>
        </div>
    `;

    try {
        const res = await fetch(`<?= base_url('api/cek_kuota.php') ?>?dokter=${dokterId}&tanggal=${tanggal}`);
        const data = await res.json();

        if (!data.status) {
            box.innerHTML = `
                <div class="alert alert-danger">
                    <i class="bx bx-x-circle" style="font-size:20px;"></i>
                    <span><strong>Tidak Tersedia:</strong> ${data.message}</span>
                </div>
            `;
            submitBtn.disabled = true;
        } else {
            const sisa = data.sisa;
            const alertClass = sisa <= 3 ? 'alert-warning' : 'alert-success';
            box.innerHTML = `
                <div class="alert ${alertClass}">
                    <i class="bx bx-check-circle" style="font-size:20px;"></i>
                    <span><strong>Jadwal Tersedia (${data.hari}):</strong> Sisa kuota <strong>${sisa}</strong> dari ${data.kuota_total} pasien. Jam praktik: ${data.jam_mulai} - ${data.jam_selesai} WIB.</span>
                </div>
            `;
            submitBtn.disabled = false;
        }
    } catch (e) {
        console.error(e);
        box.innerHTML = `
            <div class="alert alert-warning">
                <i class="bx bx-error" style="font-size:20px;"></i>
                <span>Tidak dapat terhubung ke verifikasi kuota. Anda tetap dapat melanjutkan reservasi.</span>
            </div>
        `;
        submitBtn.disabled = false;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    cekKuotaJadwal();
});
</script>
<?php
}, 'booking');

