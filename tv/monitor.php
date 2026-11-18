<?php
/**
 * DENTARA - TV Layar Ruang Tunggu (Queue TV Monitor)
 * File: tv/monitor.php
 */

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db_connect.php';

// Ambil info klinik dan promo untuk running ticker
global $pdo;
$stmt_k = $pdo->query("SELECT nama_klinik, telepon, alamat FROM pengaturan_klinik LIMIT 1");
$klinik = $stmt_k->fetch();

$stmt_promo = $pdo->query("SELECT judul, deskripsi FROM promo_banner WHERE status = 'aktif' LIMIT 3");
$promos = $stmt_promo->fetchAll();

$ticker_text = "Selamat datang di " . ($klinik['nama_klinik'] ?? 'DENTARA Dental Care') . " • Senyum Sehat, Hidup Hebat! • ";
foreach ($promos as $p) {
    $ticker_text .= e($p['judul']) . " : " . e($p['deskripsi']) . " • ";
}
$ticker_text .= "Harap menunggu hingga nomor antrean Anda dipanggil oleh dokter pemeriksa • ";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Layar Monitor Antrean - DENTARA TV</title>
    <link rel="icon" type="image/svg+xml" href="<?= base_url('assets/img/favicon.svg') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@600;700;800&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">

    <link rel="stylesheet" href="<?= base_url('assets/css/design_tokens.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/tv.css') ?>">
</head>
<body class="tv-body">
    <!-- TV HEADER -->
    <header class="tv-header">
        <div style="display:flex; align-items:center; gap:16px;">
            <?= render_logo('lg', true, 'white') ?>
            <div style="border-left:2px solid #1E2D5A; padding-left:16px;">
                <h2 style="font-size:20px; color:#FFFFFF; margin:0; font-family:var(--font-heading);">Layar Informasi Antrean Ruang Tunggu</h2>
                <span style="font-size:12px; color:#64DDBB;"><?= tanggal_indo(date('Y-m-d'), true) ?></span>
            </div>
        </div>

        <div style="display:flex; align-items:center; gap:24px;">
            <button id="btn-audio-enable" class="btn btn-primary btn-sm" onclick="enableAudio()" style="background:#2BB5A0; color:#0B132B; font-weight:800;">
                <i class="bx bx-volume-full"></i> Aktifkan Suara Panggilan
            </button>
            <div class="tv-clock" id="tv-digital-clock">00:00:00 WIB</div>
        </div>
    </header>

    <!-- TV MAIN GRID -->
    <main class="tv-main-grid">
        <!-- Kolom Kiri: Panggilan Utama yang Sedang Berjalan -->
        <div class="tv-current-call-box" id="tv-main-call-box">
            <div class="tv-call-badge" id="tv-call-status-badge">
                <i class="bx bx-broadcast"></i> SEDANG DIPANGGIL
            </div>

            <div class="tv-number-huge" id="tv-huge-number">
                --
            </div>

            <div style="font-size:36px; font-weight:800; color:#FFFFFF; margin-bottom:8px;" id="tv-patient-name">
                Menunggu Panggilan Dokter
            </div>

            <div class="tv-doctor-box" id="tv-doctor-name">
                Klinik Gigi DENTARA
            </div>

            <div class="tv-room-box" id="tv-room-spec">
                Silakan perhatikan nomor antrean pada layar
            </div>

            <!-- Maskot di Sudut Bawah -->
            <div style="position:absolute; bottom:16px; right:20px; opacity:0.85;">
                <?= render_mascot('happy', 110) ?>
            </div>
        </div>

        <!-- Kolom Kanan: Antrean Menunggu Berikutnya -->
        <div class="tv-waiting-list">
            <div class="tv-waiting-header d-flex align-items-center justify-content-between">
                <span><i class="bx bx-list-check text-primary"></i> Antrean Berikutnya</span>
                <span id="tv-total-waiting-badge" class="badge badge-mint" style="font-size:14px;">0 Pasien</span>
            </div>

            <div class="tv-waiting-items" id="tv-waiting-container">
                <div class="text-center p-4 text-muted" style="color:#94A3B8;">Memuat data antrean klinik...</div>
            </div>
        </div>
    </main>

    <!-- TV FOOTER RUNNING TICKER -->
    <div class="tv-ticker-bar">
        <div class="tv-ticker-label">
            <i class="bx bx-info-circle"></i> Info DENTARA
        </div>
        <div class="tv-ticker-content" id="tv-ticker-text">
            <?= $ticker_text ?>
        </div>
    </div>

    <!-- Web Audio Chime Generator & SpeechSynthesis Engine -->
    <script>
    let audioContext = null;
    let audioEnabled = false;
    let lastCalledId = null;

    function enableAudio() {
        if (!audioContext) {
            audioContext = new (window.AudioContext || window.webkitAudioContext)();
        }
        if (audioContext.state === 'suspended') {
            audioContext.resume();
        }
        audioEnabled = true;
        document.getElementById('btn-audio-enable').style.display = 'none';
        playDingDong();
    }

    // Mainkan nada Chime 2-tone menggunakan Web Audio API murni
    function playDingDong() {
        if (!audioContext) return;
        const now = audioContext.currentTime;

        // Tone 1: 523.25 Hz (C5)
        const osc1 = audioContext.createOscillator();
        const gain1 = audioContext.createGain();
        osc1.type = 'sine';
        osc1.frequency.setValueAtTime(523.25, now);
        gain1.gain.setValueAtTime(0.3, now);
        gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.6);
        osc1.connect(gain1);
        gain1.connect(audioContext.destination);
        osc1.start(now);
        osc1.stop(now + 0.6);

        // Tone 2: 659.25 Hz (E5)
        const osc2 = audioContext.createOscillator();
        const gain2 = audioContext.createGain();
        osc2.type = 'sine';
        osc2.frequency.setValueAtTime(659.25, now + 0.35);
        gain2.gain.setValueAtTime(0.3, now + 0.35);
        gain2.gain.exponentialRampToValueAtTime(0.001, now + 1.2);
        osc2.connect(gain2);
        gain2.connect(audioContext.destination);
        osc2.start(now + 0.35);
        osc2.stop(now + 1.2);
    }

    // Suara Panggilan Bahasa Indonesia via Web Speech API
    function speakCall(nomor, namaDokter) {
        if (!('speechSynthesis' in window)) return;

        playDingDong();

        setTimeout(() => {
            const formattedNomor = 'A ' + parseInt(nomor, 10);
            const textToSpeak = `Nomor antrean, ${formattedNomor}. Silakan menuju ruang, ${namaDokter}.`;
            const utterance = new SpeechSynthesisUtterance(textToSpeak);
            utterance.lang = 'id-ID';
            utterance.rate = 0.9;
            utterance.pitch = 1.0;

            // Cari suara bahasa Indonesia jika tersedia di sistem
            const voices = window.speechSynthesis.getVoices();
            const idVoice = voices.find(v => v.lang.includes('id') || v.lang.includes('ID'));
            if (idVoice) utterance.voice = idVoice;

            window.speechSynthesis.speak(utterance);
        }, 1200);
    }

    // Update Jam Digital Realtime
    function updateClock() {
        const now = new Date();
        const h = String(now.getHours()).padStart(2, '0');
        const m = String(now.getMinutes()).padStart(2, '0');
        const s = String(now.getSeconds()).padStart(2, '0');
        document.getElementById('tv-digital-clock').textContent = `${h}:${m}:${s} WIB`;
    }
    setInterval(updateClock, 1000);
    updateClock();

    // Polling Antrean ke Endpoint JSON
    async function pollTvQueue() {
        try {
            const res = await fetch('<?= base_url('api/antrian_status.php') ?>');
            const json = await res.json();
            if (!json.status) return;

            let activeCalling = null;
            let allWaiting = [];

            json.data.forEach(room => {
                if (room.current) {
                    if (!activeCalling || room.current.waktu_panggil > activeCalling.waktu_panggil) {
                        activeCalling = {
                            ...room.current,
                            nama_dokter: room.nama_dokter,
                            spesialisasi: room.spesialisasi
                        };
                    }
                }
                if (room.waiting && room.waiting.length > 0) {
                    room.waiting.forEach(w => {
                        allWaiting.push({
                            ...w,
                            nama_dokter: room.nama_dokter
                        });
                    });
                }
            });

            // Urutkan antrean menunggu berdasarkan prioritas DESC
            allWaiting.sort((a, b) => b.prioritas - a.prioritas || a.nomor_antrian - b.nomor_antrian);

            // 1. Update Layar Panggilan Utama
            const box = document.getElementById('tv-main-call-box');
            const numEl = document.getElementById('tv-huge-number');
            const patEl = document.getElementById('tv-patient-name');
            const docEl = document.getElementById('tv-doctor-name');
            const specEl = document.getElementById('tv-room-spec');

            if (activeCalling) {
                const callKey = `${activeCalling.id_antrian}_${activeCalling.waktu_panggil}`;
                numEl.textContent = 'A-' + String(activeCalling.nomor_antrian).padStart(3, '0');
                patEl.textContent = activeCalling.nama_pasien;
                docEl.textContent = activeCalling.nama_dokter;
                specEl.textContent = `Ruang Spesialis ${activeCalling.spesialisasi}`;

                // Jika nomor baru dipanggil, trigger audio TTS dan animasi flash
                if (lastCalledId !== callKey) {
                    lastCalledId = callKey;
                    box.classList.add('is-calling');
                    setTimeout(() => box.classList.remove('is-calling'), 12000);

                    if (audioEnabled) {
                        speakCall(activeCalling.nomor_antrian, activeCalling.nama_dokter);
                    }
                }
            } else {
                numEl.textContent = '--';
                patEl.textContent = 'Menunggu Panggilan Dokter';
                docEl.textContent = 'Klinik Gigi DENTARA';
                specEl.textContent = 'Silakan perhatikan nomor antrean pada layar';
                box.classList.remove('is-calling');
            }

            // 2. Update Daftar Menunggu
            const waitContainer = document.getElementById('tv-waiting-container');
            document.getElementById('tv-total-waiting-badge').textContent = `${allWaiting.length} Pasien`;

            if (allWaiting.length === 0) {
                waitContainer.innerHTML = '<div class="text-center p-4 text-muted" style="color:#94A3B8;">Tidak ada antrean menunggu saat ini.</div>';
            } else {
                let html = '';
                allWaiting.slice(0, 5).forEach(w => {
                    const prioText = w.prioritas == 2 ? '<span style="color:#EF4444;">(Darurat)</span>' : (w.prioritas == 1 ? '<span style="color:#F59E0B;">(Prioritas)</span>' : '');
                    html += `
                        <div class="tv-waiting-row">
                            <span class="tv-waiting-number">A-${String(w.nomor_antrian).padStart(3, '0')}</span>
                            <span style="flex:1; margin-left:16px; font-size:18px;">${w.nama_pasien} ${prioText}</span>
                            <span style="font-size:14px; color:#64DDBB;">${w.nama_dokter}</span>
                        </div>
                    `;
                });
                waitContainer.innerHTML = html;
            }

        } catch (err) {
            console.error('TV poll error:', err);
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        pollTvQueue();
        setInterval(pollTvQueue, 5000); // Polling setiap 5 detik
    });
    </script>
</body>
</html>

