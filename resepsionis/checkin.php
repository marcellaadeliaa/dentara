<?php
/**
 * DENTARA - Portal Resepsionis: Check-In & Scanner QR
 * File: resepsionis/checkin.php
 */

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout_staff.php';

require_role(['resepsionis', 'admin']);

render_staff_layout('Check-In Pasien & Scan QR', function() {
?>
<div class="fade-in" style="max-width:900px; margin:0 auto;">
    <div class="text-center mb-6">
        <span class="badge badge-mint mb-2">VALIDASI KEDATANGAN PASIEN</span>
        <h1 style="font-size:var(--text-2xl); margin-bottom:4px;">Check-In & Scan QR Tiket Antrean</h1>
        <p class="text-muted">Arahkan QR Code pasien ke kamera atau masukkan 6 karakter Kode Booking secara manual.</p>
    </div>

    <div class="grid grid-cols-2 gap-6 align-items-start">
        <!-- SCANNER KAMERA (jsQR) -->
        <div class="card p-5 text-center" style="border-radius:var(--radius-lg);">
            <h3 style="font-size:18px; margin-bottom:14px; display:flex; align-items:center; justify-content:center; gap:8px;">
                <i class="bx bx-camera text-primary"></i> Pemindai Kamera (QR Scanner)
            </h3>

            <!-- Video / Canvas Scanner Container -->
            <div style="position:relative; width:100%; height:260px; background:#0F172A; border-radius:var(--radius-md); overflow:hidden; display:flex; align-items:center; justify-content:center; margin-bottom:14px; box-shadow:inset 0 0 20px rgba(0,0,0,0.5);">
                <video id="qr-video" playsinline style="display:none; width:100%; height:100%; object-fit:cover;"></video>
                <canvas id="qr-canvas" style="width:100%; height:100%; object-fit:cover;"></canvas>

                <!-- Scanning Aim Reticle -->
                <div style="position:absolute; width:170px; height:170px; border:2px dashed #2BB5A0; border-radius:16px; pointer-events:none; box-shadow:0 0 15px rgba(43,181,160,0.4);"></div>
                <div id="scanner-status" style="position:absolute; bottom:10px; background:rgba(0,0,0,0.7); color:#fff; font-size:11px; padding:4px 12px; border-radius:var(--radius-pill);">
                    Kamera Aktif &bull; Arahkan QR
                </div>
            </div>

            <div class="d-flex justify-content-center gap-2">
                <button type="button" class="btn btn-secondary btn-sm" onclick="toggleCameraFacing()">
                    <i class="bx bx-refresh"></i> Ganti Kamera (Depan/Belakang)
                </button>
                <button type="button" class="btn btn-ghost btn-sm" onclick="restartScanner()">
                    <i class="bx bx-play"></i> Restart Kamera
                </button>
            </div>
        </div>

        <!-- INPUT MANUAL KODE BOOKING -->
        <div class="card p-6" style="border-radius:var(--radius-lg);">
            <h3 style="font-size:18px; margin-bottom:14px; display:flex; align-items:center; gap:8px;">
                <i class="bx bx-keyboard text-primary"></i> Input Kode Booking Manual
            </h3>

            <p class="text-muted" style="font-size:13px; margin-bottom:18px;">
                Jika pasien tidak membawa smartphone atau kamera scanner bermasalah, masukkan 6 karakter kode booking:
            </p>

            <form id="form-manual-checkin" onsubmit="event.preventDefault(); prosesVerifikasi(document.getElementById('input-kode-booking').value);">
                <div class="form-group mb-4">
                    <label class="form-label required font-bold">Kode Booking (6 Karakter)</label>
                    <div class="input-icon-group">
                        <i class="bx bx-barcode"></i>
                        <input type="text" id="input-kode-booking" class="form-control" 
                               placeholder="Contoh: DNT789" 
                               maxlength="10" required 
                               style="font-family:var(--font-heading); font-size:20px; font-weight:700; text-transform:uppercase; letter-spacing:2px;" 
                               autofocus>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-lg w-100" id="btn-submit-manual">
                    <i class="bx bx-check-circle"></i> Verifikasi & Terbitkan Antrean
                </button>
            </form>

            <div class="mt-4 p-3" style="background:var(--surface-subtle); border-radius:var(--radius-sm); font-size:12px; color:var(--text-muted);">
                <i class="bx bx-info-circle text-primary"></i> Sistem otomatis memvalidasi apakah jadwal pasien jatuh pada hari ini, memeriksa status reservasi, dan menerbitkan nomor antrean per dokter.
            </div>
        </div>
    </div>
</div>

<!-- jsQR Library 1.4.0 CDN -->
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
<script>
let video = document.getElementById('qr-video');
let canvasElement = document.getElementById('qr-canvas');
let canvas = canvasElement.getContext('2d');
let currentStream = null;
let currentFacingMode = 'environment'; // Kamera belakang
let isProcessing = false;

function startCamera() {
    if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
        navigator.mediaDevices.getUserMedia({ 
            video: { facingMode: currentFacingMode } 
        }).then(function(stream) {
            currentStream = stream;
            video.srcObject = stream;
            video.setAttribute('playsinline', true);
            video.play();
            requestAnimationFrame(tick);
            document.getElementById('scanner-status').textContent = 'Kamera Aktif • Arahkan QR';
        }).catch(function(err) {
            console.error('Kamera error:', err);
            document.getElementById('scanner-status').textContent = 'Kamera tidak dapat diakses';
        });
    }
}

function restartScanner() {
    if (currentStream) {
        currentStream.getTracks().forEach(track => track.stop());
    }
    isProcessing = false;
    startCamera();
}

function toggleCameraFacing() {
    currentFacingMode = (currentFacingMode === 'environment') ? 'user' : 'environment';
    restartScanner();
}

function tick() {
    if (video.readyState === video.HAVE_ENOUGH_DATA && !isProcessing) {
        canvasElement.height = video.videoHeight;
        canvasElement.width = video.videoWidth;
        canvas.drawImage(video, 0, 0, canvasElement.width, canvasElement.height);

        let imageData = canvas.getImageData(0, 0, canvasElement.width, canvasElement.height);
        let code = jsQR(imageData.data, imageData.width, imageData.height, {
            inversionAttempts: 'dontInvert',
        });

        if (code && code.data) {
            isProcessing = true;
            // Bersihkan format jika ada url lengkap
            let rawData = code.data.trim();
            if (rawData.includes('kode=')) {
                rawData = rawData.split('kode=')[1].split('&')[0];
            }
            prosesVerifikasi(rawData);
            return;
        }
    }
    if (!isProcessing) {
        requestAnimationFrame(tick);
    }
}

async function prosesVerifikasi(kode) {
    if (!kode) return;

    Swal.fire({
        title: 'Memverifikasi Tiket...',
        text: `Memeriksa kode ${kode} ke basis data antrean`,
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    try {
        const formData = new FormData();
        formData.append('kode', kode);

        const res = await fetch('<?= base_url('api/verifikasi_kode.php') ?>', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        if (data.status) {
            // Check-in Sukses! Tampilkan Nomor Antrean dan Alert Alergi
            const alergiHtml = data.alergi_obat ? `
                <div style="background:#FEE2E2; color:#991B1B; padding:10px 14px; border-radius:8px; border:2px solid #EF4444; margin-top:14px; font-weight:bold; font-size:13px; text-align:left;">
                    <i class="bx bxs-shield-x"></i> PERINGATAN ALERGI: ${data.alergi_obat}
                </div>
            ` : '';

            Swal.fire({
                icon: 'success',
                title: 'Check-In Berhasil!',
                html: `
                    <div class="text-center" style="font-size:14px;">
                        <span style="font-size:12px; color:var(--text-muted); text-transform:uppercase;">Nomor Antrean Anda:</span>
                        <div style="font-family:'Fredoka',sans-serif; font-size:54px; font-weight:800; color:#2BB5A0; line-height:1; margin:8px 0 12px;">
                            ${data.nomor_display}
                        </div>
                        <div style="font-weight:bold; font-size:16px;">${data.nama_pasien}</div>
                        <div style="color:var(--text-muted); font-size:13px;">No RM: ${data.no_rm || '-'}</div>
                        <div style="color:var(--accent); font-size:13px; margin-top:4px;">Dokter: ${data.nama_dokter} (${data.spesialisasi})</div>
                        ${alergiHtml}
                    </div>
                `,
                confirmButtonText: 'Selesai & Lanjut',
            }).then(() => {
                isProcessing = false;
                document.getElementById('input-kode-booking').value = '';
                restartScanner();
            });

        } else {
            // Gagal verifikasi
            Swal.fire({
                icon: data.already_done ? 'info' : 'error',
                title: data.already_done ? 'Sudah Check-In' : 'Check-In Gagal',
                text: data.message,
                confirmButtonText: 'Tutup'
            }).then(() => {
                isProcessing = false;
                restartScanner();
            });
        }
    } catch (err) {
        Swal.fire('Kesalahan', 'Gagal terhubung ke server verifikasi: ' + err.message, 'error').then(() => {
            isProcessing = false;
            restartScanner();
        });
    }
}

document.addEventListener('DOMContentLoaded', function() {
    startCamera();
});
</script>
<?php
}, 'checkin');

