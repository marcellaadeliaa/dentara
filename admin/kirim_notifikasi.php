<?php
/**
 * DENTARA - Portal Admin: Kirim & Blast Notifikasi Pasien
 * File: admin/kirim_notifikasi.php
 */

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout_staff.php';

require_role(['admin']);

// Handle Blast / Kirim Notifikasi POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        set_flash('error', 'Token Keamanan Kadaluarsa', 'Silakan coba lagi.');
        header('Location: ' . base_url('admin/kirim_notifikasi.php'));
        exit;
    }

    $target = $_POST['target'] ?? 'semua';
    $id_pasien_single = (int)($_POST['id_pasien'] ?? 0);
    $judul = trim($_POST['judul'] ?? '');
    $isi = trim($_POST['isi'] ?? '');

    try {
        if (empty($judul) || empty($isi)) {
            throw new Exception('Judul dan isi pesan tidak boleh kosong.');
        }

        $target_ids = [];

        if ($target === 'single' && $id_pasien_single > 0) {
            $target_ids = [$id_pasien_single];
        } elseif ($target === 'semua') {
            $target_ids = $pdo->query("SELECT id_pasien FROM pasien")->fetchAll(PDO::FETCH_COLUMN);
        } elseif ($target === 'ultah') {
            $stmt_u = $pdo->query("
                SELECT id_pasien 
                FROM pasien 
                WHERE DATE_FORMAT(tgl_lahir, '%m-%d') = DATE_FORMAT(CURRENT_DATE, '%m-%d')
            ");
            $target_ids = $stmt_u->fetchAll(PDO::FETCH_COLUMN);
        }

        if (empty($target_ids)) {
            throw new Exception('Tidak ada pasien yang memenuhi target kriteria pengiriman.');
        }

        $pdo->beginTransaction();
        $stmt_ins = $pdo->prepare("
            INSERT INTO pesan_notifikasi (id_pasien, judul, isi, status, tanggal_kirim)
            VALUES (?, ?, ?, 'belum_dibaca', CURRENT_TIMESTAMP)
        ");

        $count = 0;
        foreach ($target_ids as $pid) {
            $stmt_ins->execute([$pid, $judul, $isi]);
            $count++;
        }

        $pdo->commit();
        catat_log($pdo, 'Blast Notifikasi', "Mengirim pesan \"$judul\" ke $count pasien");
        set_flash('success', 'Pesan Berhasil Dikirim', "Pesan notifikasi berhasil dikirimkan ke $count pasien.");

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        set_flash('error', 'Gagal Mengirim', $e->getMessage());
    }

    header('Location: ' . base_url('admin/kirim_notifikasi.php'));
    exit;
}

render_staff_layout('Kirim & Blast Notifikasi Pasien', function() {
    global $pdo;

    $template_id = (int)($_GET['template_id'] ?? 0);
    $selected_template = null;
    if ($template_id > 0) {
        $stmt_t = $pdo->prepare("SELECT * FROM template_notifikasi WHERE id_tn = ?");
        $stmt_t->execute([$template_id]);
        $selected_template = $stmt_t->fetch();
    }

    // Ambil daftar template
    $templates = $pdo->query("SELECT * FROM template_notifikasi ORDER BY id_tn ASC")->fetchAll();

    // Ambil daftar pasien untuk pemilih individual
    $pasiens = $pdo->query("SELECT id_pasien, no_rm, nama_pasien, tgl_lahir FROM pasien ORDER BY nama_pasien ASC")->fetchAll();

    // Hitung pasien ulang tahun hari ini
    $ultah_today = $pdo->query("
        SELECT COUNT(*) 
        FROM pasien 
        WHERE DATE_FORMAT(tgl_lahir, '%m-%d') = DATE_FORMAT(CURRENT_DATE, '%m-%d')
    ")->fetchColumn();

    // Riwayat 20 pesan notifikasi terakhir
    $history_pesan = $pdo->query("
        SELECT pn.*, p.nama_pasien, p.no_rm
        FROM pesan_notifikasi pn
        JOIN pasien p ON pn.id_pasien = p.id_pasien
        ORDER BY pn.id_pesan DESC
        LIMIT 20
    ")->fetchAll();
    ?>

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 style="font-family:var(--font-heading); margin:0;">Kirim & Blast Notifikasi Pasien</h1>
            <p class="text-muted" style="margin:0;">Kirim pesan langsung ke kotak masuk akun pasien atau portal keluarga DENTARA.</p>
        </div>
        <div>
            <a href="<?= base_url('admin/template_notifikasi.php') ?>" class="btn btn-secondary btn-sm">
                <i class="bx bx-bookmarks"></i> Kelola Template Notifikasi
            </a>
        </div>
    </div>

    <!-- Alert Ulang Tahun Pasien Hari Ini -->
    <?php if ($ultah_today > 0): ?>
        <div class="card p-3 mb-4 alert alert-warning d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-3">
                <div style="font-size:32px;"><i class="bx bxs-cake text-warning"></i></div>
                <div>
                    <h4 style="margin:0; font-family:var(--font-heading);">Ada <?= $ultah_today ?> Pasien Berulang Tahun Hari Ini!</h4>
                    <p style="margin:0; font-size:var(--text-sm);">Kirimkan ucapan selamat ulang tahun dan voucher promo senyum spesial.</p>
                </div>
            </div>
            <button type="button" class="btn btn-warning btn-sm font-bold" onclick="setTargetUltah()">
                <i class="bx bx-gift"></i> Blast Ucapan Ulang Tahun
            </button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- FORM PENGIRIMAN NOTIFIKASI -->
        <div class="col-12 col-lg-5">
            <div class="card p-4" style="background:var(--surface);">
                <h3 style="font-family:var(--font-heading); font-size:var(--text-lg); margin-bottom:1.25rem;">
                    <i class="bx bx-paper-plane text-primary"></i> Form Pengiriman Pesan
                </h3>

                <form method="POST">
                    <?= csrf_field() ?>

                    <!-- Pilih Template -->
                    <div class="mb-3">
                        <label class="form-label font-bold"><i class="bx bx-bookmark text-primary"></i> Pilih Template (Opsional)</label>
                        <select class="form-select" onchange="pakaiTemplate(this.value)">
                            <option value="">-- Ketik Manual atau Pilih Template --</option>
                            <?php foreach ($templates as $t): ?>
                                <option value="<?= $t['id_tn'] ?>" <?= ($selected_template && $selected_template['id_tn'] == $t['id_tn']) ? 'selected' : '' ?>>
                                    [<?= e($t['kategori']) ?>] <?= e($t['judul']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Target Penerima -->
                    <div class="mb-3">
                        <label class="form-label font-bold"><i class="bx bx-target-lock text-primary"></i> Target Penerima <span class="text-danger">*</span></label>
                        <select name="target" id="select-target" class="form-select" onchange="toggleTarget(this.value)">
                            <option value="semua">Semua Pasien Terdaftar (<?= count($pasiens) ?> Pasien)</option>
                            <option value="ultah">Khusus Pasien Ulang Tahun Hari Ini (<?= $ultah_today ?> Pasien)</option>
                            <option value="single">Pasien Tertentu (Pilih Individual)</option>
                        </select>
                    </div>

                    <!-- Dropdown Pasien Tertentu -->
                    <div class="mb-3" id="group-single-pasien" style="display:none;">
                        <label class="form-label font-bold">Pilih Pasien</label>
                        <select name="id_pasien" class="form-select">
                            <?php foreach ($pasiens as $pas): ?>
                                <option value="<?= $pas['id_pasien'] ?>"><?= e($pas['nama_pasien']) ?> (<?= e($pas['no_rm']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Judul Pesan -->
                    <div class="mb-3">
                        <label class="form-label font-bold">Judul Pesan <span class="text-danger">*</span></label>
                        <input type="text" name="judul" id="input-judul" class="form-control" required value="<?= e($selected_template['judul'] ?? '') ?>" placeholder="Contoh: Selamat Ulang Tahun dari DENTARA!">
                    </div>

                    <!-- Isi Pesan -->
                    <div class="mb-4">
                        <label class="form-label font-bold">Isi Pesan Notifikasi <span class="text-danger">*</span></label>
                        <textarea name="isi" id="input-isi" class="form-control" rows="5" required placeholder="Tuliskan pesan Anda..."><?= e($selected_template['isi'] ?? '') ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-3 font-bold" style="font-size:var(--text-base);">
                        <i class="bx bx-send"></i> Kirim Notifikasi Sekarang
                    </button>
                </form>
            </div>
        </div>

        <!-- RIWAYAT PENGIRIMAN -->
        <div class="col-12 col-lg-7">
            <div class="card p-0 mb-4" style="background:var(--surface); overflow:hidden;">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-light">
                    <h3 style="font-family:var(--font-heading); font-size:var(--text-lg); margin:0;">
                        <i class="bx bx-history text-primary"></i> 20 Pesan Terakhir Terkirim
                    </h3>
                    <span class="badge badge-mint"><?= count($history_pesan) ?> Pesan</span>
                </div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th style="width:180px; min-width:160px;">Pasien</th>
                                <th style="min-width:240px;">Pesan Notifikasi</th>
                                <th style="width:120px;" class="text-center">Status</th>
                                <th style="width:140px;" class="text-center">Waktu Kirim</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($history_pesan)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted">Belum ada riwayat pesan notifikasi.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($history_pesan as $hp): ?>
                                    <tr>
                                        <td>
                                            <strong style="color:var(--text-main); font-size:13.5px;"><i class="bx bx-user text-primary"></i> <?= e($hp['nama_pasien']) ?></strong>
                                            <div class="text-muted" style="font-size:11px; font-weight:700;"><?= e($hp['no_rm']) ?></div>
                                        </td>
                                        <td>
                                            <strong style="color:var(--text-main); font-size:13.5px;"><?= e($hp['judul']) ?></strong>
                                            <div class="text-muted" style="font-size:12px; line-height:1.45; margin-top:3px;"><?= e($hp['isi']) ?></div>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($hp['status'] === 'dibaca'): ?>
                                                <span class="badge badge-success"><i class="bx bx-check-double"></i> Dibaca</span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary"><i class="bx bx-check"></i> Terkirim</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center" style="font-size:11.5px; white-space:nowrap;">
                                            <div style="font-weight:700; color:var(--text-main);"><i class="bx bx-time-five text-muted"></i> <?= waktu_indo($hp['tanggal_kirim']) ?></div>
                                            <div class="text-muted"><?= tanggal_indo(date('Y-m-d', strtotime($hp['tanggal_kirim']))) ?></div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
    const templateData = <?= json_encode($templates) ?>;

    function toggleTarget(val) {
        document.getElementById('group-single-pasien').style.display = (val === 'single') ? 'block' : 'none';
    }

    function setTargetUltah() {
        document.getElementById('select-target').value = 'ultah';
        toggleTarget('ultah');
        document.getElementById('input-judul').value = 'Selamat Ulang Tahun dari Keluarga Besar DENTARA!';
        document.getElementById('input-isi').value = 'Selamat ulang tahun! Semoga kesehatan dan senyum bahagia selalu menyertai Anda. Dapatkan diskon 15% perawatan gigi khusus bulan kelahiran Anda.';
    }

    function pakaiTemplate(id) {
        const t = templateData.find(item => item.id_tn == id);
        if (t) {
            document.getElementById('input-judul').value = t.judul;
            document.getElementById('input-isi').value = t.isi;
        }
    }
    </script>
    <?php
}, 'notifikasi');

