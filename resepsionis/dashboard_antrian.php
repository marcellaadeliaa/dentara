<?php
/**
 * DENTARA - Portal Resepsionis: Dashboard Sentral Antrean
 * File: resepsionis/dashboard_antrian.php
 */

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout_staff.php';

require_role(['resepsionis', 'admin']);

// Handle POST: Panggil Ulang / Tunda dari Resepsionis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        header('Location: ' . base_url('resepsionis/dashboard_antrian.php'));
        exit;
    }

    $action = $_POST['action'] ?? '';
    $id_antrian = (int)($_POST['id_antrian'] ?? 0);

    if ($action === 'panggil_ulang' && $id_antrian) {
        $stmt_p = $pdo->prepare("UPDATE antrian SET status = 'dipanggil', waktu_panggil = CURRENT_TIMESTAMP WHERE id_antrian = ?");
        $stmt_p->execute([$id_antrian]);
        set_flash('success', 'Dipanggil', 'Nomor antrean telah dipanggil ulang.');
    } elseif ($action === 'tunda' && $id_antrian) {
        $stmt_t = $pdo->prepare("UPDATE antrian SET status = 'ditunda' WHERE id_antrian = ?");
        $stmt_t->execute([$id_antrian]);
        set_flash('info', 'Antrean Ditunda', 'Pasien telah dipindahkan ke status ditunda.');
    }
    header('Location: ' . base_url('resepsionis/dashboard_antrian.php'));
    exit;
}

render_staff_layout('Dashboard Sentral Antrean Klinik', function() {
    global $pdo;

    $stmt_doc = $pdo->query("
        SELECT d.id_dokter, d.spesialisasi, u.nama_lengkap 
        FROM dokter d 
        JOIN pengguna u ON d.id_pengguna = u.id_pengguna 
        WHERE u.status = 'aktif'
        ORDER BY d.id_dokter ASC
    ");
    $dokters = $stmt_doc->fetchAll();
?>
<div class="fade-in">
    <!-- Header Aksi Cepat Resepsionis -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-4 mb-6">
        <div>
            <h1 style="font-size:var(--text-2xl); margin-bottom:4px;">Monitoring Antrean Ruangan</h1>
            <p class="text-muted" style="margin:0;">Layar kontrol realtime operasional antrean klinik hari ini.</p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a href="<?= base_url('resepsionis/checkin.php') ?>" class="btn btn-primary btn-md">
                <i class="bx bx-qr-scan"></i> Check-In / Scan QR
            </a>
            <a href="<?= base_url('resepsionis/walkin.php') ?>" class="btn btn-secondary btn-md">
                <i class="bx bxs-user-plus"></i> Walk-In Cepat
            </a>
            <a href="<?= base_url('tv/monitor.php') ?>" target="_blank" class="btn btn-outline btn-md no-spa">
                <i class="bx bx-tv"></i> Buka Layar TV
            </a>
        </div>
    </div>

    <!-- Antrean Per Dokter / Ruangan Grid -->
    <div class="grid grid-cols-2 gap-6" id="rooms-queue-container">
        <?php foreach ($dokters as $doc): ?>
        <div class="card p-5" id="room-card-<?= $doc['id_dokter'] ?>">
            <div class="d-flex align-items-center justify-content-between mb-3 pb-3" style="border-bottom:1px solid var(--border-color);">
                <div>
                    <h3 style="font-size:18px; margin:0;"><?= e($doc['nama_lengkap']) ?></h3>
                    <span class="badge badge-purple" style="font-size:11px;">Spesialis <?= e($doc['spesialisasi']) ?></span>
                </div>
                <span class="badge badge-mint" id="count-badge-<?= $doc['id_dokter'] ?>">0 Menunggu</span>
            </div>

            <!-- Sedang Dipanggil Box -->
            <div class="p-4 mb-4 text-center" style="background:var(--grad-hero); border-radius:var(--radius-md); border:1px solid rgba(43,181,160,0.25);">
                <span class="text-muted" style="font-size:11px; text-transform:uppercase; font-weight:700; letter-spacing:0.5px;">Sedang Diperiksa / Dipanggil</span>
                <div id="calling-num-<?= $doc['id_dokter'] ?>" style="font-family:var(--font-heading); font-size:44px; font-weight:800; color:var(--primary); line-height:1; margin:8px 0;">
                    --
                </div>
                <div id="calling-pat-<?= $doc['id_dokter'] ?>" style="font-size:15px; font-weight:700;">
                    Belum Ada Pasien
                </div>
                <div id="calling-action-<?= $doc['id_dokter'] ?>" class="mt-3" style="display:none;"></div>
            </div>

            <!-- List Menunggu Berikutnya -->
            <h4 style="font-size:13px; text-transform:uppercase; letter-spacing:0.5px; color:var(--text-muted); margin-bottom:10px;">
                Daftar Menunggu Berikutnya:
            </h4>
            <div id="waiting-list-<?= $doc['id_dokter'] ?>" style="display:flex; flex-direction:column; gap:8px; min-height:120px;">
                <div class="text-muted text-center p-3" style="font-size:13px;">Memuat antrean...</div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Modal / Form Hidden untuk Action -->
<form id="action-form" action="<?= base_url('resepsionis/dashboard_antrian.php') ?>" method="POST" style="display:none;">
    <?= csrf_field() ?>
    <input type="hidden" name="action" id="action-type">
    <input type="hidden" name="id_antrian" id="action-id-antrian">
</form>

<script>
function triggerAntrianAction(type, idAntrian) {
    document.getElementById('action-type').value = type;
    document.getElementById('action-id-antrian').value = idAntrian;
    document.getElementById('action-form').submit();
}

async function refreshAntrianDashboard() {
    try {
        const res = await fetch('<?= base_url('api/antrian_status.php') ?>');
        const json = await res.json();
        if (!json.status) return;

        json.data.forEach(item => {
            const docId = item.id_dokter;
            const countBadge = document.getElementById(`count-badge-${docId}`);
            const callNum = document.getElementById(`calling-num-${docId}`);
            const callPat = document.getElementById(`calling-pat-${docId}`);
            const callAct = document.getElementById(`calling-action-${docId}`);
            const waitList = document.getElementById(`waiting-list-${docId}`);

            if (countBadge) countBadge.textContent = `${item.total_menunggu} Menunggu`;

            if (item.current) {
                const c = item.current;
                callNum.textContent = 'A-' + String(c.nomor_antrian).padStart(3, '0');
                callPat.innerHTML = `
                    ${c.nama_pasien} 
                    ${c.alergi_obat ? `<span class="badge badge-danger" title="Alergi: ${c.alergi_obat}" style="font-size:10px;">ALERGI</span>` : ''}
                `;
                callAct.style.display = 'block';
                callAct.innerHTML = `
                    <div class="d-flex justify-content-center gap-2">
                        <button class="btn btn-secondary btn-sm" onclick="triggerAntrianAction('panggil_ulang', ${c.id_antrian})">
                            <i class="bx bx-volume-full"></i> Panggil Ulang
                        </button>
                        <button class="btn btn-outline btn-sm" onclick="triggerAntrianAction('tunda', ${c.id_antrian})">
                            <i class="bx bx-time"></i> Tunda
                        </button>
                    </div>
                `;
            } else {
                callNum.textContent = '--';
                callPat.textContent = 'Belum Ada Pasien';
                callAct.style.display = 'none';
            }

            // Waiting List
            if (waitList) {
                if (item.waiting.length === 0) {
                    waitList.innerHTML = '<div class="text-muted text-center p-3" style="font-size:13px;">Tidak ada antrean menunggu.</div>';
                } else {
                    let html = '';
                    item.waiting.forEach((w, idx) => {
                        const prioLabel = w.prioritas == 2 ? '<span class="badge badge-danger">Darurat</span>' : 
                                         (w.prioritas == 1 ? '<span class="badge badge-warning">Khusus</span>' : '');
                        html += `
                            <div style="display:flex; align-items:center; justify-content:space-between; padding:8px 12px; background:var(--surface-subtle); border-radius:var(--radius-sm); border:1px solid var(--border-color); font-size:13px;">
                                <div class="d-flex align-items-center gap-2">
                                    <strong style="font-family:var(--font-heading); font-size:15px; color:var(--text-main);">
                                        A-${String(w.nomor_antrian).padStart(3, '0')}
                                    </strong>
                                    <span>${w.nama_pasien}</span>
                                    ${prioLabel}
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <a href="<?= base_url('resepsionis/prioritas_antrian.php?id=') ?>${w.id_antrian}" class="btn btn-ghost btn-sm" title="Ubah Prioritas">
                                        <i class="bx bx-slider"></i>
                                    </a>
                                </div>
                            </div>
                        `;
                    });
                    waitList.innerHTML = html;
                }
            }
        });
    } catch (e) {
        console.error('Failed to poll queue status:', e);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    refreshAntrianDashboard();
    setInterval(refreshAntrianDashboard, 5000); // Polling setiap 5 detik
});
</script>
<?php
}, 'antrian');

