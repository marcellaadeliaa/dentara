<?php
/**
 * DENTARA - Portal Admin: Kelola FAQ (Pertanyaan yang Sering Diajukan)
 * File: admin/kelola_faq.php
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
        header('Location: ' . base_url('admin/kelola_faq.php'));
        exit;
    }

    $action = $_POST['action'] ?? '';
    $id_faq = (int)($_POST['id_faq'] ?? 0);
    $pertanyaan = trim($_POST['pertanyaan'] ?? '');
    $jawaban = trim($_POST['jawaban'] ?? '');
    $urutan = (int)($_POST['urutan'] ?? 1);

    try {
        if ($action === 'tambah') {
            if (empty($pertanyaan) || empty($jawaban)) throw new Exception('Pertanyaan dan jawaban wajib diisi.');

            $stmt = $pdo->prepare("INSERT INTO faq (pertanyaan, jawaban, urutan) VALUES (?, ?, ?)");
            $stmt->execute([$pertanyaan, $jawaban, $urutan]);
            catat_log($pdo, 'Tambah FAQ', "Menambahkan FAQ baru: $pertanyaan");
            set_flash('success', 'FAQ Ditambahkan', 'Pertanyaan baru berhasil disimpan.');

        } elseif ($action === 'edit' && $id_faq > 0) {
            $stmt = $pdo->prepare("UPDATE faq SET pertanyaan = ?, jawaban = ?, urutan = ? WHERE id_faq = ?");
            $stmt->execute([$pertanyaan, $jawaban, $urutan, $id_faq]);
            catat_log($pdo, 'Edit FAQ', "Mengubah data FAQ #$id_faq");
            set_flash('success', 'FAQ Diperbarui', 'Perubahan FAQ berhasil disimpan.');

        } elseif ($action === 'hapus' && $id_faq > 0) {
            $stmt = $pdo->prepare("DELETE FROM faq WHERE id_faq = ?");
            $stmt->execute([$id_faq]);
            catat_log($pdo, 'Hapus FAQ', "Menghapus FAQ #$id_faq");
            set_flash('success', 'FAQ Dihapus', 'FAQ telah dihapus.');
        }
    } catch (Exception $e) {
        set_flash('error', 'Gagal Memproses Data', $e->getMessage());
    }

    header('Location: ' . base_url('admin/kelola_faq.php'));
    exit;
}

render_staff_layout('Manajemen FAQ Klinik', function() {
    global $pdo;

    $faqs = $pdo->query("SELECT * FROM faq ORDER BY urutan ASC, id_faq ASC")->fetchAll();
    ?>

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 style="font-family:var(--font-heading); margin:0;">Kelola Pertanyaan & Jawaban (FAQ)</h1>
            <p class="text-muted" style="margin:0;">Informasi ini muncul di halaman publik FAQ dan portal pasien.</p>
        </div>
        <div>
            <button type="button" class="btn btn-primary" onclick="bukaModalTambahFAQ()">
                <i class="bx bx-plus-circle"></i> Tambah FAQ Baru
            </button>
        </div>
    </div>

    <!-- Tabel FAQ -->
    <div class="card p-0" style="background:var(--surface); overflow:hidden;">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th style="width:60px;" class="text-center">Urutan</th>
                        <th>Pertanyaan</th>
                        <th>Jawaban</th>
                        <th class="text-center" style="width:120px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($faqs)): ?>
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted">Belum ada daftar FAQ.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($faqs as $f): ?>
                            <tr>
                                <td class="text-center font-bold text-primary"><?= (int)$f['urutan'] ?></td>
                                <td><strong><?= e($f['pertanyaan']) ?></strong></td>
                                <td style="font-size:var(--text-sm); line-height:1.4; color:var(--text-muted); max-width:450px;">
                                    <?= nl2br(e($f['jawaban'])) ?>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <button type="button" class="btn btn-ghost btn-sm text-warning" onclick='bukaModalEditFAQ(<?= json_encode($f) ?>)' title="Edit">
                                            <i class="bx bx-edit" style="font-size:18px;"></i>
                                        </button>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Hapus FAQ ini?')">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="hapus">
                                            <input type="hidden" name="id_faq" value="<?= $f['id_faq'] ?>">
                                            <button type="submit" class="btn btn-ghost btn-sm text-danger" title="Hapus">
                                                <i class="bx bx-trash" style="font-size:18px;"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Form FAQ -->
    <div id="modal-faq" class="modal-backdrop" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
        <div class="card p-4" style="max-width:550px; width:92%; background:var(--surface);">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 style="margin:0; font-family:var(--font-heading);" id="modal-faq-title">Tambah FAQ</h4>
                <button type="button" class="btn btn-ghost btn-sm" onclick="tutupModalFAQ()">&times;</button>
            </div>

            <form method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" id="faq-action" value="tambah">
                <input type="hidden" name="id_faq" id="faq-id" value="">

                <div class="mb-3">
                    <label class="form-label font-bold">Pertanyaan <span class="text-danger">*</span></label>
                    <input type="text" name="pertanyaan" id="faq-pertanyaan" class="form-control" required placeholder="Contoh: Apakah bisa klaim asuransi?">
                </div>

                <div class="mb-3">
                    <label class="form-label font-bold">Nomor Urutan Tampil</label>
                    <input type="number" name="urutan" id="faq-urutan" class="form-control" value="1" min="1" required>
                </div>

                <div class="mb-4">
                    <label class="form-label font-bold">Jawaban Edukatif & Jelas <span class="text-danger">*</span></label>
                    <textarea name="jawaban" id="faq-jawaban" class="form-control" rows="4" required placeholder="Tulis jawaban lengkap..."></textarea>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-secondary" onclick="tutupModalFAQ()">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btn-submit-faq">Simpan FAQ</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function bukaModalTambahFAQ() {
        document.getElementById('modal-faq-title').innerText = 'Tambah FAQ Baru';
        document.getElementById('faq-action').value = 'tambah';
        document.getElementById('faq-id').value = '';
        document.getElementById('faq-pertanyaan').value = '';
        document.getElementById('faq-urutan').value = '1';
        document.getElementById('faq-jawaban').value = '';
        document.getElementById('btn-submit-faq').innerText = 'Simpan FAQ';
        document.getElementById('modal-faq').style.display = 'flex';
    }

    function bukaModalEditFAQ(f) {
        document.getElementById('modal-faq-title').innerText = 'Edit FAQ';
        document.getElementById('faq-action').value = 'edit';
        document.getElementById('faq-id').value = f.id_faq;
        document.getElementById('faq-pertanyaan').value = f.pertanyaan;
        document.getElementById('faq-urutan').value = f.urutan;
        document.getElementById('faq-jawaban').value = f.jawaban;
        document.getElementById('btn-submit-faq').innerText = 'Simpan Perubahan';
        document.getElementById('modal-faq').style.display = 'flex';
    }

    function tutupModalFAQ() {
        document.getElementById('modal-faq').style.display = 'none';
    }
    </script>
    <?php
}, 'artikel');

