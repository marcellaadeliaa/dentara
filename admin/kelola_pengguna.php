<?php
/**
 * DENTARA - Portal Admin: Manajemen Staff & Pengguna Sistem (RBAC)
 * File: admin/kelola_pengguna.php
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
        header('Location: ' . base_url('admin/kelola_pengguna.php'));
        exit;
    }

    $action = $_POST['action'] ?? '';
    $id_pengguna = (int)($_POST['id_pengguna'] ?? 0);
    $username = trim($_POST['username'] ?? '');
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');
    $role = $_POST['role'] ?? 'resepsionis';
    $status = $_POST['status'] ?? 'aktif';

    try {
        if ($action === 'tambah') {
            $password = $_POST['password'] ?? '';
            if (empty($username) || empty($password) || empty($nama_lengkap)) {
                throw new Exception('Username, Password, dan Nama Lengkap wajib diisi.');
            }

            // Cek duplikasi username
            $stmt_c = $pdo->prepare("SELECT COUNT(*) FROM pengguna WHERE username = ?");
            $stmt_c->execute([$username]);
            if ($stmt_c->fetchColumn() > 0) {
                throw new Exception('Username sudah digunakan. Pilih username lain.');
            }

            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("
                INSERT INTO pengguna (username, password, nama_lengkap, email, no_hp, role, status)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$username, $hash, $nama_lengkap, $email, $no_hp, $role, $status]);
            $new_id = (int)$pdo->lastInsertId();

            // Jika role adalah dokter, otomatis buat record di tabel dokter jika belum ada
            if ($role === 'dokter') {
                $stmt_d = $pdo->prepare("INSERT INTO dokter (id_pengguna, spesialisasi, persen_fee) VALUES (?, 'Umum', 25.00)");
                $stmt_d->execute([$new_id]);
            }

            catat_log($pdo, 'Tambah Staff', "Menambahkan akun staff baru: $username ($role)");
            set_flash('success', 'Staff Berhasil Didaftarkan', "Akun $username dengan hak akses $role telah aktif.");

        } elseif ($action === 'edit' && $id_pengguna > 0) {
            $stmt = $pdo->prepare("
                UPDATE pengguna 
                SET nama_lengkap = ?, email = ?, no_hp = ?, role = ?, status = ?
                WHERE id_pengguna = ?
            ");
            $stmt->execute([$nama_lengkap, $email, $no_hp, $role, $status, $id_pengguna]);
            catat_log($pdo, 'Edit Staff', "Mengubah data akun #$id_pengguna ($nama_lengkap)");
            set_flash('success', 'Data Diperbarui', "Informasi pengguna $nama_lengkap berhasil disimpan.");

        } elseif ($action === 'reset_password' && $id_pengguna > 0) {
            $new_pass = $_POST['new_password'] ?? '';
            if (strlen($new_pass) < 6) {
                throw new Exception('Password baru minimal harus 6 karakter.');
            }
            $hash = password_hash($new_pass, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE pengguna SET password = ? WHERE id_pengguna = ?");
            $stmt->execute([$hash, $id_pengguna]);
            catat_log($pdo, 'Reset Password Staff', "Mereset password pengguna ID #$id_pengguna");
            set_flash('success', 'Password Direset', 'Password staff berhasil diperbarui.');

        } elseif ($action === 'toggle_status' && $id_pengguna > 0) {
            // Hindari menonaktifkan diri sendiri jika admin
            if ($id_pengguna === (int)$_SESSION['id_pengguna']) {
                throw new Exception('Anda tidak dapat menonaktifkan akun Anda sendiri.');
            }
            $stmt = $pdo->prepare("UPDATE pengguna SET status = IF(status='aktif', 'nonaktif', 'aktif') WHERE id_pengguna = ?");
            $stmt->execute([$id_pengguna]);
            catat_log($pdo, 'Ubah Status Staff', "Mengubah status aktif/nonaktif akun ID #$id_pengguna");
            set_flash('success', 'Status Berubah', 'Status keaktifan staff telah diubah.');
        }
    } catch (Exception $e) {
        set_flash('error', 'Gagal Memproses Data', $e->getMessage());
    }

    header('Location: ' . base_url('admin/kelola_pengguna.php'));
    exit;
}

render_staff_layout('Manajemen Pengguna & Staff', function() {
    global $pdo;

    $role_filter = trim($_GET['role'] ?? '');
    $q = trim($_GET['q'] ?? '');

    $sql = "SELECT * FROM pengguna WHERE 1=1";
    $params = [];

    if ($role_filter !== '') {
        $sql .= " AND role = ?";
        $params[] = $role_filter;
    }
    if ($q !== '') {
        $sql .= " AND (username LIKE ? OR nama_lengkap LIKE ? OR email LIKE ?)";
        $params[] = "%$q%";
        $params[] = "%$q%";
        $params[] = "%$q%";
    }
    $sql .= " ORDER BY id_pengguna ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll();
    ?>

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 style="font-family:var(--font-heading); margin:0;">Manajemen Akun Staff & RBAC</h1>
            <p class="text-muted" style="margin:0;">Atur hak akses login role admin, dokter, resepsionis, apoteker, dan kasir.</p>
        </div>
        <div>
            <button type="button" class="btn btn-primary" onclick="bukaModalTambah()">
                <i class="bx bx-user-plus"></i> Tambah Staff Baru
            </button>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="card p-3 mb-4" style="background:var(--surface);">
        <form method="GET" class="d-flex align-items-center gap-2 flex-wrap">
            <input type="text" name="q" class="form-control" style="max-width:280px;" placeholder="Cari nama, username, email..." value="<?= e($q) ?>">
            <select name="role" class="form-select" style="max-width:180px;">
                <option value="">Semua Role</option>
                <option value="admin" <?= $role_filter === 'admin' ? 'selected' : '' ?>>Admin / Manajer</option>
                <option value="dokter" <?= $role_filter === 'dokter' ? 'selected' : '' ?>>Dokter</option>
                <option value="resepsionis" <?= $role_filter === 'resepsionis' ? 'selected' : '' ?>>Resepsionis</option>
                <option value="apoteker" <?= $role_filter === 'apoteker' ? 'selected' : '' ?>>Apoteker</option>
                <option value="kasir" <?= $role_filter === 'kasir' ? 'selected' : '' ?>>Kasir</option>
            </select>
            <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
            <?php if ($q || $role_filter): ?>
                <a href="<?= base_url('admin/kelola_pengguna.php') ?>" class="btn btn-ghost btn-sm">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Tabel Staff -->
    <div class="card p-0" style="background:var(--surface); overflow:hidden;">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th style="width:60px;">ID</th>
                        <th>Nama & Username</th>
                        <th>Kontak & Email</th>
                        <th>Role Akses</th>
                        <th class="text-center">Status</th>
                        <th>Dibuat</th>
                        <th class="text-center" style="width:180px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">Tidak ada akun staff ditemukan.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $u): 
                            $badge_role = match($u['role']) {
                                'admin' => 'badge-mint',
                                'dokter' => 'badge-primary',
                                'resepsionis' => 'badge-warning',
                                'apoteker' => 'badge-secondary',
                                'kasir' => 'badge-success',
                                default => 'badge-secondary'
                            };
                        ?>
                            <tr>
                                <td>#<?= $u['id_pengguna'] ?></td>
                                <td>
                                    <strong><?= e($u['nama_lengkap']) ?></strong>
                                    <div class="text-muted" style="font-size:12px;">@<?= e($u['username']) ?></div>
                                </td>
                                <td>
                                    <div><?= e($u['email'] ?: '-') ?></div>
                                    <div class="text-muted" style="font-size:11px;"><?= e($u['no_hp'] ?: '-') ?></div>
                                </td>
                                <td>
                                    <span class="badge <?= $badge_role ?>" style="text-transform:capitalize;">
                                        <?= e($u['role']) ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge <?= $u['status'] === 'aktif' ? 'badge-success' : 'badge-danger' ?>">
                                        <?= ucfirst($u['status']) ?>
                                    </span>
                                </td>
                                <td style="font-size:12px; color:var(--text-muted);"><?= tanggal_indo(date('Y-m-d', strtotime($u['created_at']))) ?></td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <button type="button" class="btn btn-ghost btn-sm text-primary" onclick='bukaModalEdit(<?= json_encode($u) ?>)' title="Edit Data">
                                            <i class="bx bx-edit" style="font-size:18px;"></i>
                                        </button>
                                        <button type="button" class="btn btn-ghost btn-sm text-warning" onclick="bukaModalReset(<?= $u['id_pengguna'] ?>, '<?= e($u['username']) ?>')" title="Reset Password">
                                            <i class="bx bx-key" style="font-size:18px;"></i>
                                        </button>
                                        <form method="POST" class="d-inline" onsubmit="return confirmToggleStatus(event, '<?= e($u['nama_lengkap']) ?>', '<?= $u['status'] ?>')">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="id_pengguna" value="<?= $u['id_pengguna'] ?>">
                                            <button type="submit" class="btn btn-ghost btn-sm <?= $u['status'] === 'aktif' ? 'text-danger' : 'text-success' ?>" title="<?= $u['status'] === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' ?>">
                                                <i class="bx <?= $u['status'] === 'aktif' ? 'bx-user-x' : 'bx-user-check' ?>" style="font-size:18px;"></i>
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

    <!-- Modal Form Tambah / Edit Staff -->
    <div id="modal-staff" class="modal-backdrop" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
        <div class="card p-4" style="max-width:550px; width:92%; background:var(--surface);">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 style="margin:0; font-family:var(--font-heading);" id="modal-staff-title">Tambah Staff Baru</h4>
                <button type="button" class="btn btn-ghost btn-sm" onclick="tutupModalStaff()">&times;</button>
            </div>

            <form method="POST" id="form-staff">
                <?= csrf_field() ?>
                <input type="hidden" name="action" id="staff-action" value="tambah">
                <input type="hidden" name="id_pengguna" id="staff-id" value="">

                <div class="mb-3">
                    <label class="form-label font-bold">Nama Lengkap <span class="text-danger">*</span></label>
                    <input type="text" name="nama_lengkap" id="staff-nama" class="form-control" required placeholder="Contoh: Budi Santoso, S.Farm">
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label font-bold">Username <span class="text-danger">*</span></label>
                        <input type="text" name="username" id="staff-user" class="form-control" required placeholder="budi_apotek">
                    </div>
                    <div class="col-6" id="field-password-tambah">
                        <label class="form-label font-bold">Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" id="staff-pass" class="form-control" placeholder="Min. 6 karakter">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label font-bold">Email</label>
                        <input type="email" name="email" id="staff-email" class="form-control" placeholder="staff@dentara.com">
                    </div>
                    <div class="col-6">
                        <label class="form-label font-bold">No. HP / WA</label>
                        <input type="tel" name="no_hp" id="staff-hp" class="form-control" placeholder="08123456789">
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-6">
                        <label class="form-label font-bold">Role Hak Akses <span class="text-danger">*</span></label>
                        <select name="role" id="staff-role" class="form-select">
                            <option value="resepsionis">Resepsionis</option>
                            <option value="dokter">Dokter</option>
                            <option value="apoteker">Apoteker</option>
                            <option value="kasir">Kasir</option>
                            <option value="admin">Admin / Manajer</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label font-bold">Status Keaktifan</label>
                        <select name="status" id="staff-status" class="form-select">
                            <option value="aktif">Aktif</option>
                            <option value="nonaktif">Nonaktif (Resign / Cuti)</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-secondary" onclick="tutupModalStaff()">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btn-submit-staff">Simpan Akun</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Reset Password -->
    <div id="modal-reset" class="modal-backdrop" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
        <div class="card p-4" style="max-width:450px; width:90%; background:var(--surface);">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 style="margin:0; font-family:var(--font-heading);"><i class="bx bx-key text-warning"></i> Reset Password</h4>
                <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('modal-reset').style.display='none'">&times;</button>
            </div>
            <p style="font-size:var(--text-sm);" class="text-muted">Setel ulang kata sandi pengguna: <strong id="reset-target-user"></strong></p>

            <form method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="id_pengguna" id="reset-id" value="">

                <div class="mb-4">
                    <label class="form-label font-bold">Password Baru <span class="text-danger">*</span></label>
                    <input type="password" name="new_password" class="form-control" required minlength="6" placeholder="Masukkan password baru">
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('modal-reset').style.display='none'">Batal</button>
                    <button type="submit" class="btn btn-warning font-bold">Update Password</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function bukaModalTambah() {
        document.getElementById('modal-staff-title').innerText = 'Tambah Staff Baru';
        document.getElementById('staff-action').value = 'tambah';
        document.getElementById('staff-id').value = '';
        document.getElementById('form-staff').reset();
        document.getElementById('field-password-tambah').style.display = 'block';
        document.getElementById('staff-pass').required = true;
        document.getElementById('staff-user').readOnly = false;
        document.getElementById('btn-submit-staff').innerText = 'Simpan Staff';
        document.getElementById('modal-staff').style.display = 'flex';
    }

    function bukaModalEdit(u) {
        document.getElementById('modal-staff-title').innerText = 'Edit Staff: ' + u.nama_lengkap;
        document.getElementById('staff-action').value = 'edit';
        document.getElementById('staff-id').value = u.id_pengguna;
        document.getElementById('staff-nama').value = u.nama_lengkap;
        document.getElementById('staff-user').value = u.username;
        document.getElementById('staff-user').readOnly = true;
        document.getElementById('staff-email').value = u.email || '';
        document.getElementById('staff-hp').value = u.no_hp || '';
        document.getElementById('staff-role').value = u.role;
        document.getElementById('staff-status').value = u.status;
        document.getElementById('field-password-tambah').style.display = 'none';
        document.getElementById('staff-pass').required = false;
        document.getElementById('btn-submit-staff').innerText = 'Simpan Perubahan';
        document.getElementById('modal-staff').style.display = 'flex';
    }

    function tutupModalStaff() {
        document.getElementById('modal-staff').style.display = 'none';
    }

    function bukaModalReset(id, username) {
        document.getElementById('reset-id').value = id;
        document.getElementById('reset-target-user').innerText = '@' + username;
        document.getElementById('modal-reset').style.display = 'flex';
    }

    function confirmToggleStatus(e, nama, status) {
        e.preventDefault();
        const aksi = status === 'aktif' ? 'menonaktifkan' : 'mengaktifkan kembali';
        const form = e.target;
        Swal.fire({
            title: 'Konfirmasi Status',
            text: `Apakah Anda yakin ingin ${aksi} akun "${nama}"?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Lanjutkan',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#2BB5A0'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
        return false;
    }
    </script>
    <?php
}, 'pengguna');

