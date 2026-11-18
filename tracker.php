<?php
/**
 * DENTARA - Live Queue Tracker Publik
 * File: tracker.php
 */

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/layout_public.php';

render_public_layout('Live Queue Tracker - Pantau Antrean Realtime', function() {
    global $pdo;

    $stmt_doc = $pdo->query("
        SELECT d.id_dokter, d.spesialisasi, u.nama_lengkap 
        FROM dokter d 
        JOIN pengguna u ON d.id_pengguna = u.id_pengguna 
        WHERE u.status = 'aktif'
        ORDER BY d.id_dokter ASC
    ");
    $dokters = $stmt_doc->fetchAll();

    $initial_kode = trim($_GET['kode'] ?? '');
?>
<div class="container" style="padding-top:110px; padding-bottom:60px; max-width:960px;">
    <div class="text-center mb-6">
        <span class="badge badge-mint mb-2">PEMANTAUAN ANTREAN REALTIME</span>
        <h1 style="font-size:clamp(24px, 4vw, 36px); margin-bottom:8px;">Live Queue Tracker</h1>
        <p class="text-muted">Pantau nomor antrean yang sedang diperiksa tanpa harus menunggu lama di ruang tunggu klinik.</p>
    </div>

    <!-- Pilih Dokter & Input Kode Booking Tracker -->
    <div class="card p-5 mb-6" style="border-radius:var(--radius-lg); box-shadow:var(--shadow-md);">
        <div class="grid grid-cols-2 gap-4">
            <div class="form-group mb-0">
                <label class="form-label font-bold">Pilih Ruangan / Dokter Praktik:</label>
                <select id="tracker-dokter" class="form-control" onchange="fetchTrackerData()">
                    <?php foreach ($dokters as $doc): ?>
                        <option value="<?= $doc['id_dokter'] ?>">
                            <?= e($doc['nama_lengkap']) ?> (Spesialis <?= e($doc['spesialisasi']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group mb-0">
                <label class="form-label font-bold">Lacak Status Antrean Anda:</label>
                <div class="input-icon-group">
                    <i class="bx bx-barcode"></i>
                    <input type="text" id="tracker-kode" class="form-control" 
                           placeholder="Masukkan Kode Booking / No WhatsApp..." 
                           value="<?= e($initial_kode) ?>" onkeyup="if(event.key === 'Enter') fetchTrackerData()">
                    <button type="button" class="btn btn-primary btn-sm icon-right" onclick="fetchTrackerData()" style="right:6px; top:6px; padding:6px 14px;">
                        Lacak
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Hasil Lacak Antrean Pengguna (Kondisional) -->
    <div id="my-queue-card" class="fade-in mb-6" style="display:none;"></div>

    <!-- DISPLAY SENTRAL LIVE QUEUE -->
    <div class="grid grid-cols-2 gap-6">
        <!-- Kolom Kiri: Sedang Dipanggil / Di Ruang Dokter -->
        <div class="card p-6 text-center" style="background:var(--grad-hero); border-color:rgba(43,181,160,0.3);">
            <span class="badge badge-info badge-pulse badge-dot mb-3" style="font-size:13px;">SEDANG DIPANGGIL SAAT INI</span>
            
            <div id="current-queue-num" style="font-family:var(--font-heading); font-size:72px; font-weight:800; color:var(--primary); line-height:1; margin:16px 0;">
                --
            </div>

            <div id="current-queue-patient" style="font-size:18px; font-weight:700; color:var(--text-main); margin-bottom:4px;">
                Belum Ada Panggilan
            </div>
            <div id="current-queue-doctor" class="text-muted" style="font-size:13px;">
                Menunggu antrean dimulai
            </div>

            <div class="mt-4 pt-3" style="border-top:1px solid rgba(43,181,160,0.2); font-size:12px; color:var(--text-muted);">
                <i class="bx bx-refresh"></i> Diperbarui otomatis setiap 6 detik
            </div>
        </div>

        <!-- Kolom Kanan: Daftar Menunggu Berikutnya -->
        <div class="card p-6">
            <h3 style="font-size:18px; margin-bottom:16px; display:flex; align-items:center; justify-content:space-between;">
                <span><i class="bx bx-list-ol text-primary"></i> Antrean Menunggu Berikutnya</span>
                <span id="waiting-count-badge" class="badge badge-mint">0 Pasien</span>
            </h3>

            <div id="waiting-queue-list" style="display:flex; flex-direction:column; gap:8px; max-height:280px; overflow-y:auto;">
                <div class="text-muted text-center p-4">Memuat antrean...</div>
            </div>
        </div>
    </div>
</div>

<script>
let trackerInterval = null;

async function fetchTrackerData() {
    const dokterId = document.getElementById('tracker-dokter').value;
    const kode = document.getElementById('tracker-kode').value.trim();

    try {
        const res = await fetch(`<?= base_url('api/antrian_tracker.php') ?>?dokter=${dokterId}&kode=${encodeURIComponent(kode)}`);
        const data = await res.json();

        if (!data.status) return;

        // 1. Update Antrean Sedang Dipanggil
        const numEl = document.getElementById('current-queue-num');
        const patEl = document.getElementById('current-queue-patient');
        const docEl = document.getElementById('current-queue-doctor');

        if (data.current_call) {
            numEl.textContent = 'A-' + String(data.current_call.nomor_antrian).padStart(3, '0');
            patEl.textContent = data.current_call.nama_pasien_masked || 'Pasien';
            docEl.textContent = `${data.current_call.nama_dokter} (${data.current_call.spesialisasi})`;
            numEl.style.color = 'var(--primary)';
        } else {
            numEl.textContent = '--';
            patEl.textContent = 'Belum Ada Panggilan';
            docEl.textContent = 'Menunggu pasien berikutnya';
        }

        // 2. Update Daftar Menunggu
        const listEl = document.getElementById('waiting-queue-list');
        const countBadge = document.getElementById('waiting-count-badge');
        countBadge.textContent = `${data.waiting_list.length} Pasien`;

        if (data.waiting_list.length === 0) {
            listEl.innerHTML = '<div class="text-muted text-center p-4">Tidak ada antrean menunggu saat ini.</div>';
        } else {
            let html = '';
            data.waiting_list.forEach((item, idx) => {
                const isPriority = item.prioritas > 0;
                html += `
                    <div style="display:flex; align-items:center; justify-content:space-between; padding:10px 14px; background:var(--surface-subtle); border-radius:var(--radius-sm); border:1px solid var(--border-color);">
                        <div class="d-flex align-items-center gap-3">
                            <span style="font-family:var(--font-heading); font-size:18px; font-weight:700; color:var(--text-main);">
                                A-${String(item.nomor_antrian).padStart(3, '0')}
                            </span>
                            ${isPriority ? '<span class="badge badge-warning" style="font-size:10px;">Prioritas</span>' : ''}
                        </div>
                        <span class="badge badge-mint" style="font-size:11px;">Urutan ke-${idx + 1}</span>
                    </div>
                `;
            });
            listEl.innerHTML = html;
        }

        // 3. Update Status Antrean Pengguna jika dicari
        const myCard = document.getElementById('my-queue-card');
        if (data.my_queue) {
            myCard.style.display = 'block';
            if (!data.my_queue.found) {
                myCard.innerHTML = `
                    <div class="alert alert-warning mb-0">
                        <i class="bx bx-error-circle" style="font-size:22px;"></i>
                        <div><strong>Data Tidak Ditemukan:</strong> ${data.my_queue.pesan}</div>
                    </div>
                `;
            } else if (data.my_queue.status_cek === 'belum_checkin') {
                myCard.innerHTML = `
                    <div class="alert alert-info mb-0">
                        <i class="bx bx-time" style="font-size:22px;"></i>
                        <div>
                            <strong>Reservasi Ditemukan (${data.my_queue.kode_booking}):</strong> 
                            Halo <strong>${data.my_queue.nama_pasien}</strong>, Anda belum melakukan check-in di klinik. Silakan scan QR code tiket Anda di meja resepsionis saat tiba.
                        </div>
                    </div>
                `;
            } else {
                const q = data.my_queue;
                const isNow = q.status_antrian === 'dipanggil';
                myCard.innerHTML = `
                    <div class="card p-5" style="border:2px solid var(--primary); background:var(--surface);">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                            <div>
                                <span class="badge ${isNow ? 'badge-danger badge-pulse' : 'badge-mint'} mb-2">
                                    ${isNow ? 'SEGERA MASUK KE RUANG DOKTER' : 'STATUS ANTREAN ANDA'}
                                </span>
                                <h3 style="font-size:20px; margin:0;">${q.nama_pasien}</h3>
                                <div class="text-muted" style="font-size:13px;">Dokter: ${q.nama_dokter} &bull; Kode: <strong>${q.kode_booking}</strong></div>
                            </div>
                            <div class="text-center" style="background:var(--primary-100); padding:12px 24px; border-radius:var(--radius-md);">
                                <span style="font-size:11px; text-transform:uppercase; font-weight:700; color:var(--primary-800); display:block;">Nomor Anda</span>
                                <span style="font-family:var(--font-heading); font-size:36px; font-weight:800; color:var(--primary);">
                                    A-${String(q.nomor_antrian).padStart(3, '0')}
                                </span>
                            </div>
                        </div>

                        ${!isNow ? `
                        <div class="mt-4 pt-3 d-flex flex-wrap gap-4" style="border-top:1px solid var(--border-color); font-size:14px;">
                            <div><i class="bx bx-user text-primary"></i> Sisa Pasien di Depan Anda: <strong>${q.sisa_pasien} pasien</strong></div>
                            <div><i class="bx bx-time text-primary"></i> Estimasi Waktu Tunggu: <strong>±${q.estimasi_menit} menit</strong></div>
                        </div>
                        ` : ''}
                    </div>
                `;
            }
        } else {
            myCard.style.display = 'none';
        }

    } catch (e) {
        console.error('Tracker poll error:', e);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    fetchTrackerData();
    trackerInterval = setInterval(fetchTrackerData, 6000); // Polling setiap 6 detik
});
</script>
<?php
}, 'tracker');

