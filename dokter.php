<?php
/**
 * DENTARA - Direktori Dokter & Jadwal Praktik Live
 * File: dokter.php
 */

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/layout_public.php';

render_public_layout('Direktori Dokter & Jadwal Praktik', function() {
    global $pdo;

    $spesialisasi = trim($_GET['spesialisasi'] ?? '');
    $search = trim($_GET['q'] ?? '');

    $sql = "
        SELECT d.*, p.nama_lengkap, p.email, p.no_hp 
        FROM dokter d 
        JOIN pengguna p ON d.id_pengguna = p.id_pengguna 
        WHERE p.status = 'aktif'
    ";
    $params = [];

    if ($spesialisasi) {
        $sql .= " AND d.spesialisasi = ?";
        $params[] = $spesialisasi;
    }
    if ($search) {
        $sql .= " AND (p.nama_lengkap LIKE ? OR d.bio LIKE ?)";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
    }
    $sql .= " ORDER BY d.id_dokter ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $dokter_list = $stmt->fetchAll();

    $spesialisasi_options = ['Semua', 'Umum', 'Konservasi', 'Ortodonti', 'Bedah Mulut', 'Pedodonti', 'Periodonti'];
?>
<div class="container" style="padding-top:110px; padding-bottom:60px;">
    <div class="text-center mb-6">
        <span class="badge badge-mint mb-2">DOKTER GIGI TERPERCAYA</span>
        <h1 style="font-size:clamp(26px, 4vw, 40px); margin-bottom:8px;">Tim Dokter Spesialis & Jadwal Praktik</h1>
        <p class="text-muted" style="max-width:600px; margin:0 auto;">
            Temukan dokter gigi yang tepat sesuai kebutuhan perawatan Anda dan cek kepastian jadwal praktiknya secara langsung.
        </p>
    </div>

    <!-- Filter & Search Bar -->
    <div class="card p-4 mb-6">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-4">
            <div class="d-flex flex-wrap gap-2">
                <?php foreach ($spesialisasi_options as $sp): 
                    $isActive = ($sp === 'Semua' && !$spesialisasi) || ($sp === $spesialisasi);
                    $url = ($sp === 'Semua') ? base_url('dokter.php') : base_url('dokter.php?spesialisasi=' . urlencode($sp));
                ?>
                <a href="<?= $url ?>" class="btn <?= $isActive ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
                    <?= $sp ?>
                </a>
                <?php endforeach; ?>
            </div>

            <form action="<?= base_url('dokter.php') ?>" method="GET" class="d-flex gap-2" style="max-width:320px; width:100%;">
                <?php if ($spesialisasi): ?>
                    <input type="hidden" name="spesialisasi" value="<?= e($spesialisasi) ?>">
                <?php endif; ?>
                <div class="input-icon-group flex-1">
                    <i class="bx bx-search"></i>
                    <input type="text" name="q" value="<?= e($search) ?>" class="form-control" placeholder="Cari nama dokter...">
                </div>
                <button type="submit" class="btn btn-primary btn-sm"><i class="bx bx-search"></i></button>
            </form>
        </div>
    </div>

    <!-- Doctors Cards with Live Schedules -->
    <div class="grid grid-cols-1 gap-6">
        <?php foreach ($dokter_list as $doc): 
            // Cek status cuti dokter hari ini atau mendatang
            $today = date('Y-m-d');
            $stmt_cuti = $pdo->prepare("
                SELECT * FROM cuti_dokter 
                WHERE id_dokter = ? AND status = 'aktif' AND ? BETWEEN tanggal_mulai AND tanggal_selesai
            ");
            $stmt_cuti->execute([(int)$doc['id_dokter'], $today]);
            $cuti_aktif = $stmt_cuti->fetch();

            // Ambil jadwal praktik dokter
            $stmt_j = $pdo->prepare("
                SELECT * FROM jadwal_praktik_dokter 
                WHERE id_dokter = ? AND status = 'aktif' 
                ORDER BY FIELD(hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu')
            ");
            $stmt_j->execute([(int)$doc['id_dokter']]);
            $jadwal = $stmt_j->fetchAll();
        ?>
        <div class="card p-6 card-hover">
            <div class="d-flex flex-column flex-md-row gap-6 align-items-start">
                <!-- Doctor Avatar -->
                <div style="width:110px; height:110px; border-radius:50%; background:var(--grad-mint-blue); color:#fff; display:flex; align-items:center; justify-content:center; font-size:48px; flex-shrink:0; box-shadow:var(--shadow-md);">
                    <i class="bx bxs-user"></i>
                </div>

                <!-- Doctor Details -->
                <div class="flex-1">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                        <h2 style="font-size:22px; margin:0;"><?= e($doc['nama_lengkap']) ?></h2>
                        <span class="badge badge-purple" style="font-size:13px;">Spesialis <?= e($doc['spesialisasi']) ?></span>
                    </div>
                    <div class="text-muted mb-3" style="font-size:13px;">
                        <span><i class="bx bx-certification text-primary"></i> No. STR: <?= e($doc['no_str']) ?></span>
                    </div>
                    <p class="text-muted" style="font-size:14px; line-height:1.6; margin-bottom:16px;">
                        <?= e($doc['bio']) ?>
                    </p>

                    <!-- Cuti Alert Banner if Active -->
                    <?php if ($cuti_aktif): ?>
                    <div class="alert alert-warning mb-3">
                        <i class="bx bx-calendar-exclamation" style="font-size:22px;"></i>
                        <div>
                            <strong>Sedang Cuti:</strong> <?= e($cuti_aktif['alasan']) ?> 
                            (<?= tanggal_indo($cuti_aktif['tanggal_mulai']) ?> s/d <?= tanggal_indo($cuti_aktif['tanggal_selesai']) ?>).
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Schedule Pills / Badges -->
                    <h4 style="font-size:13px; text-transform:uppercase; letter-spacing:0.5px; color:var(--text-muted); margin-bottom:8px;">Jadwal Praktik Mingguan</h4>
                    <div class="d-flex flex-wrap gap-2 mb-4">
                        <?php if (empty($jadwal)): ?>
                            <span class="text-muted" style="font-size:13px;">Belum ada jadwal praktik aktif.</span>
                        <?php else: ?>
                            <?php foreach ($jadwal as $j): 
                                $isCuti = $cuti_aktif ? true : false;
                            ?>
                            <div class="p-2" style="background:var(--surface-subtle); border-radius:var(--radius-sm); border:1px solid var(--border-color); font-size:12px; <?= $isCuti ? 'text-decoration:line-through; opacity:0.6;' : '' ?>">
                                <strong><?= $j['hari'] ?>:</strong> <?= substr($j['jam_mulai'], 0, 5) ?> - <?= substr($j['jam_selesai'], 0, 5) ?> WIB 
                                <span class="text-muted">(Kuota: <?= $j['kuota_per_hari'] ?>)</span>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <a href="<?= base_url('booking.php?dokter=' . $doc['id_dokter']) ?>" class="btn btn-primary btn-md">
                        <i class="bx bx-calendar-plus"></i> Booking Janji dengan <?= e($doc['nama_lengkap']) ?>
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php
}, 'dokter');

