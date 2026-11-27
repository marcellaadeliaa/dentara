<?php
/**
 * DENTARA - Portal Admin: Audit Trail & Log Aktivitas Sistem
 * File: admin/log_aktivitas.php
 */

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout_staff.php';

require_role(['admin']);

render_staff_layout('Log Aktivitas & Audit Trail', function() {
    global $pdo;

    $q = trim($_GET['q'] ?? '');
    $user_filter = (int)($_GET['id_pengguna'] ?? 0);

    // Ambil daftar staff untuk filter
    $staffs = $pdo->query("SELECT id_pengguna, nama_lengkap, role FROM pengguna ORDER BY nama_lengkap ASC")->fetchAll();

    $sql = "
        SELECT l.*, u.nama_lengkap, u.username, u.role
        FROM log_aktivitas l
        LEFT JOIN pengguna u ON l.id_pengguna = u.id_pengguna
        WHERE 1=1
    ";
    $params = [];

    if ($user_filter > 0) {
        $sql .= " AND l.id_pengguna = ?";
        $params[] = $user_filter;
    }
    if ($q !== '') {
        $sql .= " AND (l.aksi LIKE ? OR l.detail LIKE ? OR u.nama_lengkap LIKE ? OR l.ip LIKE ?)";
        $params[] = "%$q%";
        $params[] = "%$q%";
        $params[] = "%$q%";
        $params[] = "%$q%";
    }

    $sql .= " ORDER BY l.id_log DESC LIMIT 100";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll();
    ?>

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 style="font-family:var(--font-heading); margin:0;">Audit Trail & Log Aktivitas Pengguna</h1>
            <p class="text-muted" style="margin:0;">Rekaman historis keamanan setiap aktivitas login, perubahan data medis, dan transaksi kasir.</p>
        </div>
        <div>
            <button type="button" class="btn btn-secondary btn-sm" onclick="window.print()">
                <i class="bx bx-printer"></i> Cetak Log Audit
            </button>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="card p-3 mb-4" style="background:var(--surface);">
        <form method="GET" class="filter-bar d-flex align-items-center gap-2 flex-wrap">
            <div class="input-icon-group" style="max-width:300px; flex:1;">
                <i class="bx bx-search"></i>
                <input type="text" name="q" class="form-control" placeholder="Cari aksi, detail, IP..." value="<?= e($q) ?>">
            </div>
            <select name="id_pengguna" class="form-select" style="max-width:260px;">
                <option value="0">Semua Pengguna</option>
                <?php foreach ($staffs as $s): ?>
                    <option value="<?= $s['id_pengguna'] ?>" <?= $user_filter === (int)$s['id_pengguna'] ? 'selected' : '' ?>>
                        <?= e($s['nama_lengkap']) ?> (<?= e($s['role']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary" style="height:42px; padding:0 20px;">
                <i class="bx bx-filter-alt"></i> Filter
            </button>
            <?php if ($q || $user_filter > 0): ?>
                <a href="<?= base_url('admin/log_aktivitas.php') ?>" class="btn btn-secondary" style="height:42px; padding:0 16px;">
                    <i class="bx bx-reset"></i> Reset
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Tabel Log -->
    <div class="card p-0 mb-4" style="background:var(--surface); overflow:hidden;">
        <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-light">
            <h3 style="font-family:var(--font-heading); font-size:var(--text-lg); margin:0;">
                <i class="bx bx-shield-quarter text-primary"></i> Rekam Audit Trail Keamanan Sistem
            </h3>
            <span class="badge badge-mint"><?= count($logs) ?> Entri Tercatat</span>
        </div>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th style="width:70px;" class="text-center">ID</th>
                        <th style="width:160px;">Waktu & Tanggal</th>
                        <th style="width:220px;">Pengguna / Pelaksana</th>
                        <th style="width:160px;" class="text-center">Aksi</th>
                        <th>Detail Aktivitas</th>
                        <th style="width:130px;" class="text-center">Alamat IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">Belum ada catatan log aktivitas yang sesuai.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $l): ?>
                            <tr>
                                <td class="text-center font-bold text-muted" style="font-size:12px;">#<?= $l['id_log'] ?></td>
                                <td style="white-space:nowrap; font-size:12px;">
                                    <div style="font-weight:700; color:var(--text-main);"><i class="bx bx-time-five text-muted"></i> <?= waktu_indo($l['created_at']) ?></div>
                                    <div class="text-muted"><?= tanggal_indo(date('Y-m-d', strtotime($l['created_at']))) ?></div>
                                </td>
                                <td>
                                    <strong style="color:var(--text-main); font-size:13.5px;"><?= e($l['nama_lengkap'] ?: 'Sistem Otomatis') ?></strong>
                                    <?php if (!empty($l['role'])): ?>
                                        <div style="font-size:11px; color:var(--text-muted); text-transform:uppercase; font-weight:700; letter-spacing:0.5px;">Role: <?= e($l['role']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php
                                    $badge_class = 'badge-secondary';
                                    if (str_contains($l['aksi'], 'LOGIN')) $badge_class = 'badge-mint';
                                    elseif (str_contains($l['aksi'], 'TAMBAH') || str_contains($l['aksi'], 'SIMPAN')) $badge_class = 'badge-success';
                                    elseif (str_contains($l['aksi'], 'HAPUS') || str_contains($l['aksi'], 'BATAL')) $badge_class = 'badge-danger';
                                    elseif (str_contains($l['aksi'], 'UPDATE') || str_contains($l['aksi'], 'EDIT')) $badge_class = 'badge-warning';
                                    ?>
                                    <span class="badge <?= $badge_class ?>"><?= e($l['aksi']) ?></span>
                                </td>
                                <td style="line-height:1.45; font-size:13px;">
                                    <?= nl2br(e($l['detail'] ?: '-')) ?>
                                </td>
                                <td class="text-center">
                                    <code style="font-size:12px; font-weight:700; color:#334155; background:#F1F5F9; border:1px solid #CBD5E1; padding:3px 8px; border-radius:6px;"><?= e($l['ip']) ?></code>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}, 'log');

