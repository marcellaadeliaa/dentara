<?php
/**
 * DENTARA - Portal Admin: Kelola Template Pesan Notifikasi Pasien
 * File: admin/template_notifikasi.php
 */

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout_staff.php';

require_role(['admin']);

// Handle CRUD POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        set_flash('error', 'Token Keamanan Kadaluarsa', 'Silakan coba lagi.');
        header('Location: ' . base_url('admin/template_notifikasi.php'));
        exit;
    }

    $action = $_POST['action'] ?? '';
    $id_tn = (int)($_POST['id_tn'] ?? 0);
    $judul = trim($_POST['judul'] ?? '');
    $kategori = $_POST['kategori'] ?? 'Informasi';
    $isi = trim($_POST['isi'] ?? '');

    try {
        if ($action === 'tambah') {
            if (empty($judul) || empty($isi)) throw new Exception('Judul dan isi template wajib diisi.');

            $stmt = $pdo->prepare("INSERT INTO template_notifikasi (judul, kategori, isi) VALUES (?, ?, ?)");
            $stmt->execute([$judul, $kategori, $isi]);
            catat_log($pdo, 'Tambah Template Notifikasi', "Menambah template: $judul");
            set_flash('success', 'Template Disimpan', "Template notifikasi \"$judul\" berhasil disimpan.");

        } elseif ($action === 'edit' && $id_tn > 0) {
            $stmt = $pdo->prepare("UPDATE template_notifikasi SET judul = ?, kategori = ?, isi = ? WHERE id_tn = ?");
            $stmt->execute([$judul, $kategori, $isi, $id_tn]);
            catat_log($pdo, 'Edit Template Notifikasi', "Mengubah template #$id_tn");
            set_flash('success', 'Template Diperbarui', 'Perubahan template notifikasi berhasil disimpan.');

        } elseif ($action === 'hapus' && $id_tn > 0) {
            $stmt = $pdo->prepare("DELETE FROM template_notifikasi WHERE id_tn = ?");
            $stmt->execute([$id_tn]);
            catat_log($pdo, 'Hapus Template Notifikasi', "Menghapus template #$id_tn");
            set_flash('success', 'Template Dihapus', 'Template notifikasi telah dihapus.');
        }
    } catch (Exception $e) {
        set_flash('error', 'Gagal Memproses Data', $e->getMessage());
    }

    header('Location: ' . base_url('admin/template_notifikasi.php'));
    exit;
}

render_staff_layout('Template Notifikasi Pasien', function() {
    global $pdo;

    $templates = $pdo->query("SELECT * FROM template_notifikasi ORDER BY id_tn ASC")->fetchAll();
    ?>

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 style="font-family:var(--font-heading); margin:0;">Master Template Pesan & Notifikasi</h1>
            <p class="text-muted" style="margin:0;">Pola pesan standar untuk ucapan ulang tahun, reminder kontrol rutin, dan broadcast info klinik.</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-primary" onclick="bukaModalTambahTemplate()">
                <i class="bx bx-plus-circle"></i> Tambah Template
            </button>
            <a href="<?= base_url('admin/kirim_notifikasi.php') ?>" class="btn btn-secondary">
                <i class="bx bxs-paper-plane"></i> Kirim / Blast Pesan
            </a>
        </div>
    </div>

    <div class="row g-4">
        <?php foreach ($templates as $t): ?>
            <div class="col-12 col-md-6">
                <div class="card p-4 h-100 d-flex flex-column" style="background:var(--surface); border:1px solid var(--border-color);">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge badge-mint"><?= e($t['kategori']) ?></span>
                        <div class="d-flex gap-1">
                            <button type="button" class="btn btn-ghost btn-sm text-warning" onclick='bukaModalEditTemplate(<?= json_encode($t) ?>)' title="Edit">
                                <i class="bx bx-edit" style="font-size:18px;"></i>
                            </button>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Hapus template ini?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="hapus">
                                <input type="hidden" name="id_tn" value="<?= $t['id_tn'] ?>">
                                <button type="submit" class="btn btn-ghost btn-sm text-danger" title="Hapus">
                                    <i class="bx bx-trash" style="font-size:18px;"></i>
                                </button>
                            </form>
                        </div>
                    </div>

                    <h3 style="font-family:var(--font-heading); font-size:var(--text-lg); margin:0 0 10px 0;">
                        <?= e($t['judul']) ?>
                    </h3>

                    <div class="p-3 bg-light rounded text-muted mb-3 flex-1" style="font-size:var(--text-sm); line-height:1.4;">
                        <?= nl2br(e($t['isi'])) ?>
                    </div>

                    <div class="mt-auto">
                        <a href="<?= base_url('admin/kirim_notifikasi.php?template_id=' . $t['id_tn']) ?>" class="btn btn-ghost btn-sm text-primary w-100">
                            <i class="bx bx-send"></i> Gunakan Template Ini untuk Blast &rarr;
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Modal Form Template -->
    <div id="modal-template" class="modal-backdrop" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
        <div class="card p-4" style="max-width:550px; width:92%; background:var(--surface);">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 style="margin:0; font-family:var(--font-heading);" id="modal-template-title">Tambah Template</h4>
                <button type="button" class="btn btn-ghost btn-sm" onclick="tutupModalTemplate()">&times;</button>
            </div>

            <form method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" id="template-action" value="tambah">
                <input type="hidden" name="id_tn" id="template-id" value="">

                <div class="mb-3">
                    <label class="form-label font-bold">Judul Pesan Template <span class="text-danger">*</span></label>
                    <input type="text" name="judul" id="template-judul" class="form-control" required placeholder="Contoh: Pengingat Kontrol 6 Bulanan">
                </div>

                <div class="mb-3">
                    <label class="form-label font-bold">Kategori</label>
                    <select name="kategori" id="template-kategori" class="form-select">
                        <option value="Pengingat Kontrol">Pengingat Kontrol</option>
                        <option value="Ulang Tahun">Ulang Tahun</option>
                        <option value="Informasi">Informasi Klinik</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label font-bold">Isi Pesan Notifikasi <span class="text-danger">*</span></label>
                    <textarea name="isi" id="template-isi" class="form-control" rows="4" required placeholder="Tulis format pesan notifikasi..."></textarea>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-secondary" onclick="tutupModalTemplate()">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btn-submit-template">Simpan Template</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function bukaModalTambahTemplate() {
        document.getElementById('modal-template-title').innerText = 'Tambah Template Notifikasi';
        document.getElementById('template-action').value = 'tambah';
        document.getElementById('template-id').value = '';
        document.getElementById('template-judul').value = '';
        document.getElementById('template-kategori').value = 'Pengingat Kontrol';
        document.getElementById('template-isi').value = '';
        document.getElementById('btn-submit-template').innerText = 'Simpan Template';
        document.getElementById('modal-template').style.display = 'flex';
    }

    function bukaModalEditTemplate(t) {
        document.getElementById('modal-template-title').innerText = 'Edit Template: ' + t.judul;
        document.getElementById('template-action').value = 'edit';
        document.getElementById('template-id').value = t.id_tn;
        document.getElementById('template-judul').value = t.judul;
        document.getElementById('template-kategori').value = t.kategori;
        document.getElementById('template-isi').value = t.isi;
        document.getElementById('btn-submit-template').innerText = 'Simpan Perubahan';
        document.getElementById('modal-template').style.display = 'flex';
    }

    function tutupModalTemplate() {
        document.getElementById('modal-template').style.display = 'none';
    }
    </script>
    <?php
}, 'notifikasi');

