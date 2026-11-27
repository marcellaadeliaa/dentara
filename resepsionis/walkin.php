<?php
/**
 * DENTARA - Portal Resepsionis: Pendaftaran Walk-In Langsung
 * File: resepsionis/walkin.php
 */

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout_staff.php';

require_role(['resepsionis', 'admin']);

// Handle POST: Pendaftaran Walk-In Langsung
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        header('Location: ' . base_url('resepsionis/walkin.php'));
        exit;
    }

    $id_pasien     = !empty($_POST['id_pasien']) ? (int)$_POST['id_pasien'] : 0;
    $id_dokter     = (int)($_POST['id_dokter'] ?? 0);
    $id_layanan    = !empty($_POST['id_layanan']) ? (int)$_POST['id_layanan'] : null;
    $keluhan       = trim($_POST['keluhan'] ?? '');
    $prioritas     = (int)($_POST['prioritas'] ?? 0);
    $alasan_prio   = trim($_POST['alasan_prioritas'] ?? '');

    // Jika mendaftarkan pasien baru di tempat
    $is_new_patient = isset($_POST['is_new_patient']) && $_POST['is_new_patient'] === '1';

    if (!$id_dokter) {
        set_flash('warning', 'Pilih Dokter', 'Harap pilih dokter pemeriksa hari ini.');
        header('Location: ' . base_url('resepsionis/walkin.php'));
        exit;
    }

    try {
        $pdo->beginTransaction();
        $today = date('Y-m-d');

        if ($is_new_patient) {
            $nama_pasien   = trim($_POST['nama_pasien'] ?? '');
            $nik           = trim($_POST['nik'] ?? '');
            $tgl_lahir     = trim($_POST['tgl_lahir'] ?? '');
            $jenis_kelamin = trim($_POST['jenis_kelamin'] ?? '');
            $alamat        = trim($_POST['alamat'] ?? '');
            $no_hp         = trim($_POST['no_hp'] ?? '');
            $alergi        = trim($_POST['alergi_obat'] ?? '');
            $kondisi       = trim($_POST['kondisi_medis'] ?? '');

            if (empty($nama_pasien) || empty($nik) || empty($tgl_lahir) || empty($jenis_kelamin)) {
                throw new Exception('Data pasien baru wajib diisi lengkap.');
            }

            $no_rm = generate_no_rm($pdo);

            $stmt_np = $pdo->prepare("
                INSERT INTO pasien (no_rm, hubungan_keluarga, nama_pasien, nik, tgl_lahir, jenis_kelamin, alamat, no_hp, alergi_obat, kondisi_medis)
                VALUES (?, 'Diri Sendiri', ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt_np->execute([$no_rm, $nama_pasien, $nik, $tgl_lahir, $jenis_kelamin, $alamat, $no_hp, $alergi ?: null, $kondisi ?: null]);
            $id_pasien = (int)$pdo->lastInsertId();
        }

        if (!$id_pasien) {
            throw new Exception('Pilih pasien terdaftar atau centang daftar pasien baru.');
        }

        // 1. Generate Kode Booking Walkin
        $kode_booking = generate_kode_booking($pdo);

        // 2. Simpan ke Reservasi (Status langsung check_in, sumber walkin)
        $stmt_res = $pdo->prepare("
            INSERT INTO reservasi (kode_booking, id_pasien, id_dokter, id_layanan, tanggal, jam, keluhan, status, sumber)
            VALUES (?, ?, ?, ?, ?, CURRENT_TIME(), ?, 'check_in', 'walkin')
        ");
        $stmt_res->execute([$kode_booking, $id_pasien, $id_dokter, $id_layanan, $today, $keluhan]);
        $id_reservasi = (int)$pdo->lastInsertId();

        // 3. Generate Nomor Antrean Berurutan Hari Ini
        $stmt_next = $pdo->prepare("
            SELECT COALESCE(MAX(nomor_antrian), 0) + 1 
            FROM antrian 
            WHERE id_dokter = ? AND tanggal = ?
        ");
        $stmt_next->execute([$id_dokter, $today]);
        $nomor_antrian = (int)$stmt_next->fetchColumn();

        // 4. Masukkan ke Antrian
        $stmt_ant = $pdo->prepare("
            INSERT INTO antrian (id_reservasi, id_dokter, tanggal, nomor_antrian, prioritas, alasan_prioritas, status, waktu_checkin)
            VALUES (?, ?, ?, ?, ?, ?, 'menunggu', CURRENT_TIMESTAMP)
        ");
        $stmt_ant->execute([$id_reservasi, $id_dokter, $today, $nomor_antrian, $prioritas, $alasan_prio ?: null]);

        $pdo->commit();

        catat_log($pdo, 'WALKIN_CHECKIN', "Pasien walk-in #{$id_pasien} berhasil didaftarkan ke antrean A-" . sprintf('%03d', $nomor_antrian));

        set_flash('success', 'Walk-In Berhasil!', "Pasien langsung masuk ke antrean dokter dengan Nomor A-" . sprintf('%03d', $nomor_antrian));
        header('Location: ' . base_url('resepsionis/dashboard_antrian.php'));
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        set_flash('error', 'Gagal Walk-In', $e->getMessage());
        header('Location: ' . base_url('resepsionis/walkin.php'));
        exit;
    }
}

render_staff_layout('Registrasi Pasien Walk-In Langsung', function() {
    global $pdo;

    // Ambil daftar pasien untuk autocomplete/pilihan
    $stmt_p = $pdo->query("SELECT id_pasien, no_rm, nama_pasien, nik, no_hp FROM pasien ORDER BY nama_pasien ASC LIMIT 100");
    $pasien_list = $stmt_p->fetchAll();

    // Ambil dokter praktik hari ini
    $hari_map = [
        'Sun' => 'Minggu', 'Mon' => 'Senin', 'Tue' => 'Selasa',
        'Wed' => 'Rabu', 'Thu' => 'Kamis', 'Fri' => 'Jumat', 'Sat' => 'Sabtu'
    ];
    $hari_ini = $hari_map[date('D')];

    $stmt_doc = $pdo->prepare("
        SELECT d.id_dokter, d.spesialisasi, u.nama_lengkap, jpd.jam_mulai, jpd.jam_selesai, jpd.kuota_per_hari
        FROM dokter d
        JOIN pengguna u ON d.id_pengguna = u.id_pengguna
        JOIN jadwal_praktik_dokter jpd ON jpd.id_dokter = d.id_dokter
        WHERE u.status = 'aktif' AND jpd.hari = ? AND jpd.status = 'aktif'
        ORDER BY d.id_dokter ASC
    ");
    $stmt_doc->execute([$hari_ini]);
    $dokters = $stmt_doc->fetchAll();

    $stmt_lay = $pdo->query("SELECT id_layanan, nama_layanan, kategori, tarif_awal FROM layanan WHERE status = 'aktif' ORDER BY kategori ASC, nama_layanan ASC");
    $layanans = $stmt_lay->fetchAll();
?>
<div class="fade-in" style="max-width:860px; margin:0 auto;">
    <div class="text-center mb-6">
        <span class="badge badge-mint mb-2">PELAYANAN WALK-IN KLINIK</span>
        <h1 style="font-size:var(--text-2xl); margin-bottom:4px;">Pendaftaran Pasien Walk-In (Datang Langsung)</h1>
        <p class="text-muted">Daftarkan pasien yang hadir langsung tanpa reservasi online dan langsung terbitkan nomor antrean hari ini.</p>
    </div>

    <div class="card p-6" style="border-radius:var(--radius-lg); box-shadow:var(--shadow-md);">
        <form action="<?= base_url('resepsionis/walkin.php') ?>" method="POST">
            <?= csrf_field() ?>

            <!-- PILIH DOKTER PRAKTIK HARI INI -->
            <div class="form-group mb-4">
                <label class="form-label required font-bold">Pilih Dokter Praktik Hari Ini (<?= $hari_ini ?>):</label>
                <select name="id_dokter" class="form-control" required>
                    <option value="">-- Pilih Dokter Praktik --</option>
                    <?php foreach ($dokters as $doc): ?>
                        <option value="<?= $doc['id_dokter'] ?>">
                            <?= e($doc['nama_lengkap']) ?> (Spesialis <?= e($doc['spesialisasi']) ?>) &bull; Jam: <?= substr($doc['jam_mulai'], 0, 5) ?> - <?= substr($doc['jam_selesai'], 0, 5) ?> WIB
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (empty($dokters)): ?>
                    <span class="text-danger" style="font-size:12px; margin-top:4px; display:block;">
                        Perhatian: Tidak ada jadwal dokter praktik aktif yang terdaftar untuk hari <?= $hari_ini ?>.
                    </span>
                <?php endif; ?>
            </div>

            <!-- PILIH PASIEN ATAU PASIEN BARU -->
            <div class="form-group mb-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <label class="form-label font-bold mb-0">Identitas Pasien:</label>
                    <label class="d-flex align-items-center gap-2" style="font-size:13px; cursor:pointer;">
                        <input type="checkbox" name="is_new_patient" id="check-new-patient" value="1" onchange="toggleNewPatient(this)">
                        <span class="text-primary font-bold"><i class="bx bx-user-plus"></i> Pasien Baru (Belum Punya No RM)</span>
                    </label>
                </div>

                <!-- Bagian 1: Pilih Pasien Lama -->
                <div id="section-existing-patient">
                    <select name="id_pasien" id="select-pasien-lama" class="form-control">
                        <option value="">-- Pilih Pasien Terdaftar (Cari Nama / No RM) --</option>
                        <?php foreach ($pasien_list as $p): ?>
                            <option value="<?= $p['id_pasien'] ?>">
                                [<?= e($p['no_rm']) ?>] <?= e($p['nama_pasien']) ?> &bull; NIK: <?= e($p['nik']) ?> &bull; HP: <?= e($p['no_hp']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Bagian 2: Formulir Cepat Pasien Baru -->
                <div id="section-new-patient" style="display:none; background:var(--surface-subtle); padding:16px; border-radius:var(--radius-md); border:1px solid var(--border-color); margin-top:10px;">
                    <h4 style="font-size:14px; color:var(--primary); margin-bottom:12px;">Registrasi Cepat Pasien Baru di Tempat:</h4>
                    <div class="grid grid-cols-2 gap-3 mb-3">
                        <div class="form-group mb-0">
                            <label class="form-label required">Nama Lengkap Pasien</label>
                            <input type="text" name="nama_pasien" class="form-control" placeholder="Nama sesuai KTP">
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label required">NIK (16 Digit)</label>
                            <input type="text" name="nik" class="form-control" placeholder="16 digit NIK" maxlength="16">
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3 mb-3">
                        <div class="form-group mb-0">
                            <label class="form-label required">Tanggal Lahir</label>
                            <input type="date" name="tgl_lahir" class="form-control" max="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label required">Jenis Kelamin</label>
                            <select name="jenis_kelamin" class="form-control">
                                <option value="L">Laki-laki (L)</option>
                                <option value="P">Perempuan (P)</option>
                            </select>
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label required">Nomor WhatsApp / HP</label>
                            <input type="tel" name="no_hp" class="form-control" placeholder="08xxxxxxxx">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="form-group mb-0">
                            <label class="form-label">Riwayat Alergi Obat</label>
                            <input type="text" name="alergi_obat" class="form-control" placeholder="Misal: Penisilin (opsional)">
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label">Kondisi Medis Khusus</label>
                            <input type="text" name="kondisi_medis" class="form-control" placeholder="Misal: Diabetes (opsional)">
                        </div>
                    </div>
                </div>
            </div>

            <!-- LAYANAN & PRIORITAS -->
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div class="form-group">
                    <label class="form-label">Tindakan / Layanan Utama</label>
                    <select name="id_layanan" class="form-control">
                        <option value="">-- Pemeriksaan Umum & Konsultasi --</option>
                        <?php foreach ($layanans as $lay): ?>
                            <option value="<?= $lay['id_layanan'] ?>">
                                [<?= e($lay['kategori']) ?>] <?= e($lay['nama_layanan']) ?> (<?= rupiah($lay['tarif_awal']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Tingkat Prioritas Antrean</label>
                    <select name="prioritas" class="form-control">
                        <option value="0">Normal (0 - Antrean Standar)</option>
                        <option value="1">Lansia / Disabilitas / Anak Kecil (1 - Prioritas Khusus)</option>
                        <option value="2">Darurat Medis / Pendarahan Hebat (2 - Cito / Urgent)</option>
                    </select>
                </div>
            </div>

            <div class="form-group mb-4">
                <label class="form-label">Keluhan Singkat Pasien</label>
                <textarea name="keluhan" class="form-control" rows="2" placeholder="Contoh: Nyeri akut pada gigi belakang, ingin diperiksa hari ini..."></textarea>
            </div>

            <button type="submit" class="btn btn-primary btn-lg w-100">
                <i class="bx bx-check-double"></i> Daftarkan & Masukkan ke Antrean Sekarang
            </button>
        </form>
    </div>
</div>

<script>
function toggleNewPatient(checkbox) {
    const isNew = checkbox.checked;
    document.getElementById('section-existing-patient').style.display = isNew ? 'none' : 'block';
    document.getElementById('section-new-patient').style.display = isNew ? 'block' : 'none';
}
</script>
<?php
}, 'walkin');

