<?php
/**
 * DENTARA - Portal Admin: Kelola Kartu Gamifikasi & Reward Pasien
 * File: admin/kelola_kartu.php
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
        header('Location: ' . base_url('admin/kelola_kartu.php'));
        exit;
    }

    $action = $_POST['action'] ?? '';
    $id_kartu = (int)($_POST['id_kartu'] ?? 0);
    $nama_kartu = trim($_POST['nama_kartu'] ?? '');
    $deskripsi = trim($_POST['deskripsi_edukatif'] ?? '');
    $rarity = $_POST['rarity'] ?? 'Common';
    $poin = (int)($_POST['poin_diperlukan'] ?? 100);

    try {
        if ($action === 'tambah') {
            if (empty($nama_kartu)) throw new Exception('Nama kartu wajib diisi.');

            $stmt = $pdo->prepare("
                INSERT INTO master_kartu (nama_kartu, deskripsi_edukatif, rarity, poin_diperlukan)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$nama_kartu, $deskripsi, $rarity, $poin]);
            catat_log($pdo, 'Tambah Kartu Gamifikasi', "Menambahkan kartu baru: $nama_kartu ($rarity)");
            set_flash('success', 'Kartu Ditambahkan', "Kartu $nama_kartu berhasil disimpan.");

        } elseif ($action === 'edit' && $id_kartu > 0) {
            $stmt = $pdo->prepare("
                UPDATE master_kartu 
                SET nama_kartu = ?, deskripsi_edukatif = ?, rarity = ?, poin_diperlukan = ?
                WHERE id_kartu = ?
            ");
            $stmt->execute([$nama_kartu, $deskripsi, $rarity, $poin, $id_kartu]);
            catat_log($pdo, 'Edit Kartu Gamifikasi', "Mengubah data kartu #$id_kartu");
            set_flash('success', 'Kartu Diperbarui', 'Perubahan kartu berhasil disimpan.');

        } elseif ($action === 'hadiahkan') {
            $id_pasien = (int)($_POST['id_pasien'] ?? 0);
            $id_kartu_hadiah = (int)($_POST['id_kartu_hadiah'] ?? 0);

            if ($id_pasien <= 0 || $id_kartu_hadiah <= 0) {
                throw new Exception('Pilih pasien dan kartu yang akan dihadiahkan.');
            }

            $stmt_give = $pdo->prepare("
                INSERT INTO koleksi_kartu_pasien (id_pasien, id_kartu, tanggal_didapat)
                VALUES (?, ?, CURRENT_TIMESTAMP)
            ");
            $stmt_give->execute([$id_pasien, $id_kartu_hadiah]);

            // Kirim notifikasi selamat ke pasien
            kirim_notifikasi_pasien(
                $pdo, 
                $id_pasien, 
                'Selamat! Kamu Mendapat Kartu Hadiah Spesial!', 
                'Dokter DENTARA menghadiahkan kartu karakter gigi baru ke koleksi Smiling Guardians kamu. Buka menu Gamifikasi untuk melihatnya!'
            );

            catat_log($pdo, 'Hadiahkan Kartu', "Menghadiahkan kartu #$id_kartu_hadiah ke pasien #$id_pasien");
            set_flash('success', 'Kartu Berhasil Dihadiahkan', 'Kartu karakter berhasil ditambahkan ke koleksi anak!');
        }
    } catch (Exception $e) {
        set_flash('error', 'Gagal Memproses Data', $e->getMessage());
    }

    header('Location: ' . base_url('admin/kelola_kartu.php'));
    exit;
}

render_staff_layout('Manajemen Kartu Gamifikasi', function() {
    global $pdo;

    // Ambil daftar master kartu
    $kartus = $pdo->query("SELECT * FROM master_kartu ORDER BY poin_diperlukan ASC, id_kartu ASC")->fetchAll();

    // Ambil daftar pasien anak / pasien umum
    $pasiens = $pdo->query("SELECT id_pasien, no_rm, nama_pasien, tgl_lahir FROM pasien ORDER BY nama_pasien ASC")->fetchAll();

    // Hitung total koleksi didapat seluruh pasien
    $total_unlocked = $pdo->query("SELECT COUNT(*) FROM koleksi_kartu_pasien")->fetchColumn();
    ?>

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 style="font-family:var(--font-heading); margin:0;">Koleksi Kartu Gamifikasi Pasien Anak</h1>
            <p class="text-muted" style="margin:0;">Atur karakter "Smiling Guardians", tingkat kelangkaan (Rarity), dan berikan reward khusus untuk anak pemberani.</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-warning" onclick="bukaModalHadiah()">
                <i class="bx bx-gift"></i> Hadiahkan Kartu ke Pasien
            </button>
            <button type="button" class="btn btn-primary" onclick="bukaModalTambahKartu()">
                <i class="bx bx-plus-circle"></i> Tambah Master Kartu
            </button>
        </div>
    </div>

    <!-- Grid Kartu Gamifikasi -->
    <div class="row g-4">
        <?php foreach ($kartus as $k): 
            $rarity_badge = match($k['rarity']) {
                'Legendary' => 'background:linear-gradient(135deg, #FFD700, #FFA500); color:#000;',
                'Epic' => 'background:linear-gradient(135deg, #9C27B0, #E040FB); color:#fff;',
                'Rare' => 'background:linear-gradient(135deg, #2196F3, #00BCD4); color:#fff;',
                default => 'background:linear-gradient(135deg, #78909C, #B0BEC5); color:#fff;'
            };
        ?>
            <div class="col-12 col-sm-6 col-lg-4">
                <div class="card p-4 h-100 text-center d-flex flex-column" style="background:var(--surface); border:1px solid var(--border-color); border-radius:16px;">
                    <div class="mb-3">
                        <span class="badge" style="padding:4px 12px; font-weight:800; border-radius:20px; <?= $rarity_badge ?>">
                            <?= strtoupper(e($k['rarity'])) ?>
                        </span>
                    </div>

                    <div style="font-size:3.5rem; margin-bottom:0.75rem;">
                        <?php
                        $emoji = match($k['nama_kartu']) {
                            'Kapten Gigi Sehat' => '🦷🦸‍♂️',
                            'Ksatria Enamel' => '🛡️⚔️',
                            'Naga Pembersih Karang' => '🐉✨',
                            'Pahlawan Behel Keren' => '🦷🌟',
                            'Peri Gigi Baik Hati' => '🧚‍♀️💖',
                            'Dokter Senyum Ceria' => '👨‍⚕️😁',
                            default => '🦷⭐'
                        };
                        echo $emoji;
                        ?>
                    </div>

                    <h3 style="font-family:var(--font-heading); font-size:var(--text-lg); margin:0 0 8px 0;">
                        <?= e($k['nama_kartu']) ?>
                    </h3>

                    <div class="p-2 mb-3 bg-light rounded text-muted" style="font-size:12px; line-height:1.4; flex:1;">
                        <?= nl2br(e($k['deskripsi_edukatif'])) ?>
                    </div>

                    <div class="mt-auto d-flex justify-content-between align-items-center pt-2 border-top">
                        <div class="text-left">
                            <span class="text-muted" style="font-size:11px;">Poin Diperlukan:</span>
                            <div class="font-bold text-primary"><?= (int)$k['poin_diperlukan'] ?> Poin</div>
                        </div>
                        <button type="button" class="btn btn-ghost btn-sm text-warning" onclick='bukaModalEditKartu(<?= json_encode($k) ?>)'>
                            <i class="bx bx-edit"></i> Edit
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Modal Form Kartu (Tambah / Edit) -->
    <div id="modal-kartu" class="modal-backdrop" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
        <div class="card p-4" style="max-width:500px; width:92%; background:var(--surface);">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 style="margin:0; font-family:var(--font-heading);" id="modal-kartu-title">Tambah Kartu Baru</h4>
                <button type="button" class="btn btn-ghost btn-sm" onclick="tutupModalKartu()">&times;</button>
            </div>

            <form method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" id="kartu-action" value="tambah">
                <input type="hidden" name="id_kartu" id="kartu-id" value="">

                <div class="mb-3">
                    <label class="form-label font-bold">Nama Karakter Kartu <span class="text-danger">*</span></label>
                    <input type="text" name="nama_kartu" id="kartu-nama" class="form-control" required placeholder="Contoh: Sang Juara Sikat Gigi">
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label font-bold">Kelangkaan (Rarity)</label>
                        <select name="rarity" id="kartu-rarity" class="form-select">
                            <option value="Common">Common</option>
                            <option value="Rare">Rare</option>
                            <option value="Epic">Epic</option>
                            <option value="Legendary">Legendary</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label font-bold">Poin Syarat</label>
                        <input type="number" name="poin_diperlukan" id="kartu-poin" class="form-control" value="100" min="0" step="50" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label font-bold">Pesan Edukasi untuk Pasien Anak</label>
                    <textarea name="deskripsi_edukatif" id="kartu-deskripsi" class="form-control" rows="3" placeholder="Pesan edukasi cara sikat gigi atau keberanian periksa ke dokter gigi..."></textarea>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-secondary" onclick="tutupModalKartu()">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btn-submit-kartu">Simpan Kartu</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Hadiahkan Kartu ke Pasien -->
    <div id="modal-hadiah" class="modal-backdrop" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
        <div class="card p-4" style="max-width:500px; width:92%; background:var(--surface);">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 style="margin:0; font-family:var(--font-heading);"><i class="bx bx-gift text-warning"></i> Hadiahkan Kartu ke Anak</h4>
                <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('modal-hadiah').style.display='none'">&times;</button>
            </div>

            <form method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="hadiahkan">

                <div class="mb-3">
                    <label class="form-label font-bold">Pilih Pasien Penerima <span class="text-danger">*</span></label>
                    <select name="id_pasien" class="form-select" required>
                        <?php foreach ($pasiens as $pas): ?>
                            <option value="<?= $pas['id_pasien'] ?>"><?= e($pas['nama_pasien']) ?> (<?= e($pas['no_rm']) ?>) - <?= hitung_usia($pas['tgl_lahir']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label font-bold">Pilih Karakter Kartu <span class="text-danger">*</span></label>
                    <select name="id_kartu_hadiah" class="form-select" required>
                        <?php foreach ($kartus as $kar): ?>
                            <option value="<?= $kar['id_kartu'] ?>">[<?= e($kar['rarity']) ?>] <?= e($kar['nama_kartu']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('modal-hadiah').style.display='none'">Batal</button>
                    <button type="submit" class="btn btn-warning font-bold">Kirim Kartu Hadiah</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function bukaModalTambahKartu() {
        document.getElementById('modal-kartu-title').innerText = 'Tambah Kartu Baru';
        document.getElementById('kartu-action').value = 'tambah';
        document.getElementById('kartu-id').value = '';
        document.getElementById('kartu-nama').value = '';
        document.getElementById('kartu-rarity').value = 'Common';
        document.getElementById('kartu-poin').value = '100';
        document.getElementById('kartu-deskripsi').value = '';
        document.getElementById('btn-submit-kartu').innerText = 'Simpan Karakter';
        document.getElementById('modal-kartu').style.display = 'flex';
    }

    function bukaModalEditKartu(k) {
        document.getElementById('modal-kartu-title').innerText = 'Edit Kartu: ' + k.nama_kartu;
        document.getElementById('kartu-action').value = 'edit';
        document.getElementById('kartu-id').value = k.id_kartu;
        document.getElementById('kartu-nama').value = k.nama_kartu;
        document.getElementById('kartu-rarity').value = k.rarity;
        document.getElementById('kartu-poin').value = k.poin_diperlukan;
        document.getElementById('kartu-deskripsi').value = k.deskripsi_edukatif || '';
        document.getElementById('btn-submit-kartu').innerText = 'Simpan Perubahan';
        document.getElementById('modal-kartu').style.display = 'flex';
    }

    function tutupModalKartu() {
        document.getElementById('modal-kartu').style.display = 'none';
    }

    function bukaModalHadiah() {
        document.getElementById('modal-hadiah').style.display = 'flex';
    }
    </script>
    <?php
}, 'kartu');

