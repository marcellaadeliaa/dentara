<?php
/**
 * DENTARA DESIGN SYSTEM - STYLEGUIDE & VISUAL REFERENCE
 * File: styleguide.php (Khusus Staff & Administrator)
 */

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout_staff.php';

// Pastikan hanya role admin yang bisa membuka styleguide
require_role('admin');

render_staff_layout('Design Styleguide & Component Reference', function() {
?>
<div class="styleguide-page">
    <!-- Intro Card -->
    <div class="card mb-6" style="background:var(--grad-hero); border-color:rgba(43,181,160,0.3);">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-4">
            <div>
                <h1 style="font-size:var(--text-2xl); margin-bottom:8px;">DENTARA UI Styleguide & Design System</h1>
                <p class="text-muted" style="max-width:650px;">
                    Dokumentasi hidup sistem desain DENTARA. Seluruh halaman staff, portal pasien, dan landing page wajib menggunakan token, komponen, dan palet warna di bawah ini agar konsisten, modern, dan menenangkan.
                </p>
            </div>
            <div>
                <?= render_mascot('happy', 110) ?>
            </div>
        </div>
    </div>

    <!-- SEKSI 1: COLOR TOKENS -->
    <div class="card mb-6">
        <div class="card-header">
            <div>
                <h3 class="card-title">1. Palet Warna & Token Desain</h3>
                <p class="card-subtitle">Variabel CSS utama dari <code>assets/css/design_tokens.css</code></p>
            </div>
        </div>

        <h4 style="font-size:var(--text-sm); text-transform:uppercase; letter-spacing:0.5px; color:var(--text-muted); margin-bottom:12px;">Warna Inti (Core Palette)</h4>
        <div class="grid grid-cols-4 gap-4 mb-6">
            <!-- Mint-Teal -->
            <div style="background:#2BB5A0; color:#fff; padding:16px; border-radius:var(--radius-md); box-shadow:var(--shadow-sm);">
                <div style="font-weight:700; font-family:var(--font-heading);">Mint-Teal (Primary)</div>
                <div style="font-size:12px; opacity:0.9;">#2BB5A0 / var(--primary)</div>
            </div>
            <!-- Sky Blue -->
            <div style="background:#5B9DF5; color:#fff; padding:16px; border-radius:var(--radius-md); box-shadow:var(--shadow-sm);">
                <div style="font-weight:700; font-family:var(--font-heading);">Sky Blue (Secondary)</div>
                <div style="font-size:12px; opacity:0.9;">#5B9DF5 / var(--secondary)</div>
            </div>
            <!-- Soft Violet -->
            <div style="background:#8B7CF6; color:#fff; padding:16px; border-radius:var(--radius-md); box-shadow:var(--shadow-sm);">
                <div style="font-weight:700; font-family:var(--font-heading);">Soft Violet (Accent)</div>
                <div style="font-size:12px; opacity:0.9;">#8B7CF6 / var(--accent)</div>
            </div>
            <!-- Honey Gold -->
            <div style="background:#FFD166; color:#1E293B; padding:16px; border-radius:var(--radius-md); box-shadow:var(--shadow-sm);">
                <div style="font-weight:700; font-family:var(--font-heading);">Honey Gold (Highlight)</div>
                <div style="font-size:12px; opacity:0.8;">#FFD166 / var(--highlight)</div>
            </div>
        </div>

        <h4 style="font-size:var(--text-sm); text-transform:uppercase; letter-spacing:0.5px; color:var(--text-muted); margin-bottom:12px;">Warna Status Semantik</h4>
        <div class="grid grid-cols-4 gap-4">
            <div class="p-3" style="background:var(--success-light); color:var(--success-dark); border-radius:var(--radius-sm); font-weight:700;">
                <i class="bx bxs-check-circle"></i> Success (#10B981)
            </div>
            <div class="p-3" style="background:var(--warning-light); color:var(--warning-dark); border-radius:var(--radius-sm); font-weight:700;">
                <i class="bx bxs-error-circle"></i> Warning (#F59E0B)
            </div>
            <div class="p-3" style="background:var(--danger-light); color:var(--danger-dark); border-radius:var(--radius-sm); font-weight:700;">
                <i class="bx bxs-x-circle"></i> Danger (#EF4444)
            </div>
            <div class="p-3" style="background:var(--info-light); color:var(--info-dark); border-radius:var(--radius-sm); font-weight:700;">
                <i class="bx bxs-info-circle"></i> Info (#3B82F6)
            </div>
        </div>
    </div>

    <!-- SEKSI 2: TYPOGRAPHY -->
    <div class="card mb-6">
        <div class="card-header">
            <div>
                <h3 class="card-title">2. Tipografi</h3>
                <p class="card-subtitle">Fredoka untuk judul/angka besar & Nunito untuk isi konten</p>
            </div>
        </div>
        <div class="grid grid-cols-2 gap-6">
            <div>
                <div style="font-size:12px; font-weight:700; color:var(--text-muted); margin-bottom:8px;">HEADING SCALE (FREDOKA)</div>
                <h1 style="font-size:36px; margin-bottom:6px;">H1. Senyum Sehat, Hidup Hebat!</h1>
                <h2 style="font-size:28px; margin-bottom:6px;">H2. Dokter Gigi Spesialis Terpercaya</h2>
                <h3 style="font-size:22px; margin-bottom:6px;">H3. Perawatan Saluran Akar Bebas Cemas</h3>
                <h4 style="font-size:18px;">H4. Jadwal Praktik Hari Ini</h4>
            </div>
            <div>
                <div style="font-size:12px; font-weight:700; color:var(--text-muted); margin-bottom:8px;">BODY TEXT (NUNITO)</div>
                <p style="font-size:var(--text-base); line-height:1.6; margin-bottom:12px;">
                    <strong>Ukuran Base (16px):</strong> Klinik gigi modern DENTARA mengedepankan sterilisasi alat bertaraf internasional, pendekatan ramah anak tanpa rasa takut, dan rekam medis digital terpadu.
                </p>
                <p style="font-size:var(--text-sm); color:var(--text-muted); line-height:1.6;">
                    <strong>Ukuran Small (14px):</strong> Kunjungan rutin setiap 6 bulan sekali membantu mencegah akumulasi karang gigi dan mendeteksi karies sejak dini.
                </p>
            </div>
        </div>
    </div>

    <!-- SEKSI 3: BUTTONS -->
    <div class="card mb-6">
        <div class="card-header">
            <div>
                <h3 class="card-title">3. Tombol (Buttons)</h3>
                <p class="card-subtitle">Varian, ukuran, dan status hover tombol</p>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-3 align-items-center mb-4">
            <button class="btn btn-primary"><i class="bx bx-calendar-plus"></i> Primary Mint</button>
            <button class="btn btn-secondary"><i class="bx bx-cog"></i> Secondary</button>
            <button class="btn btn-outline"><i class="bx bx-check"></i> Outline</button>
            <button class="btn btn-ghost"><i class="bx bx-bell"></i> Ghost</button>
            <button class="btn btn-danger"><i class="bx bx-trash"></i> Danger</button>
            <button class="btn btn-warning"><i class="bx bx-time"></i> Warning</button>
            <button class="btn btn-gold"><i class="bx bxs-trophy"></i> Lucky Gold</button>
        </div>
        <div class="d-flex flex-wrap gap-3 align-items-center">
            <button class="btn btn-primary btn-sm">Small Button</button>
            <button class="btn btn-primary btn-md">Medium Button</button>
            <button class="btn btn-primary btn-lg">Large Button</button>
            <button class="btn btn-primary is-loading">Loading</button>
            <button class="btn btn-primary btn-icon"><i class="bx bx-plus"></i></button>
        </div>
    </div>

    <!-- SEKSI 4: STAT CARDS & COUNT-UP -->
    <div class="card mb-6">
        <div class="card-header">
            <div>
                <h3 class="card-title">4. Kartu Statistik (Stat Cards)</h3>
                <p class="card-subtitle">Dilengkapi animasi angka count-up dan indikator tren</p>
            </div>
            <button class="btn btn-secondary btn-sm" onclick="demoCountUp()"><i class="bx bx-refresh"></i> Uji Count-Up</button>
        </div>
        <div class="grid grid-cols-4 gap-4">
            <div class="card-stat">
                <div class="stat-icon mint"><i class="bx bxs-user-check"></i></div>
                <div class="stat-info">
                    <div class="stat-label">Pasien Hari Ini</div>
                    <div class="stat-value" id="stat-pasien">28</div>
                    <div class="stat-trend up"><i class="bx bx-trending-up"></i> +12% dari kemarin</div>
                </div>
            </div>
            <div class="card-stat">
                <div class="stat-icon blue"><i class="bx bxs-calendar"></i></div>
                <div class="stat-info">
                    <div class="stat-label">Reservasi Aktif</div>
                    <div class="stat-value" id="stat-reservasi">14</div>
                    <div class="stat-trend up"><i class="bx bx-trending-up"></i> Kuota 85%</div>
                </div>
            </div>
            <div class="card-stat">
                <div class="stat-icon purple"><i class="bx bxs-wallet"></i></div>
                <div class="stat-info">
                    <div class="stat-label">Pendapatan Hari Ini</div>
                    <div class="stat-value" id="stat-pendapatan">4850000</div>
                    <div class="stat-trend up"><i class="bx bx-trending-up"></i> Target tercapai</div>
                </div>
            </div>
            <div class="card-stat">
                <div class="stat-icon orange"><i class="bx bxs-capsule"></i></div>
                <div class="stat-info">
                    <div class="stat-label">Stok Kritis</div>
                    <div class="stat-value" id="stat-stok">3</div>
                    <div class="stat-trend down"><i class="bx bx-error-circle"></i> Perlu restock</div>
                </div>
            </div>
        </div>
    </div>

    <!-- SEKSI 5: BADGES & STATUS CHIPS -->
    <div class="card mb-6">
        <div class="card-header">
            <h3 class="card-title">5. Status Badges & Chips</h3>
        </div>
        <div class="d-flex flex-wrap gap-3 align-items-center">
            <span class="badge badge-success badge-dot">Selesai / Lunas</span>
            <span class="badge badge-warning badge-dot">Menunggu Konfirmasi</span>
            <span class="badge badge-danger badge-dot">Dibatalkan</span>
            <span class="badge badge-info badge-pulse badge-dot">Sedang Dipanggil</span>
            <span class="badge badge-mint">Konservasi Gigi</span>
            <span class="badge badge-purple">Spesialis Ortodonti</span>
            <span class="badge badge-gold">Legendary Card</span>
        </div>
    </div>

    <!-- SEKSI 6: INTERACTIVE SWEETALERT2 TEST -->
    <div class="card mb-6">
        <div class="card-header">
            <div>
                <h3 class="card-title">6. Uji Notifikasi SweetAlert2 Ramah</h3>
                <p class="card-subtitle">Pengganti seluruh alert dan konfirmasi bawaan browser</p>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-3">
            <button class="btn btn-success btn-sm" onclick="DENTARA.toast('success', 'Berhasil Disimpan!', 'Data rekam medis berhasil diperbarui.')">
                <i class="bx bx-check-circle"></i> Toast Sukses
            </button>
            <button class="btn btn-warning btn-sm" onclick="DENTARA.toast('warning', 'Peringatan Kuota', 'Sisa kuota dokter tinggal 2 slot.')">
                <i class="bx bx-error"></i> Toast Peringatan
            </button>
            <button class="btn btn-outline btn-sm" onclick="testConfirm()">
                <i class="bx bx-help-circle"></i> Dialog Konfirmasi
            </button>
            <button class="btn btn-gold btn-sm" onclick="testConfetti()">
                <i class="bx bxs-party"></i> Uji Hadiah Confetti
            </button>
        </div>
    </div>

    <!-- SEKSI 7: EMPTY STATE & MASKOT DENTA -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">7. Komponen Empty State & Maskot Denta</h3>
        </div>
        <div class="grid grid-cols-3 gap-6">
            <div class="empty-state" style="padding:24px; background:var(--surface-subtle); border-radius:var(--radius-md);">
                <?= render_mascot('happy', 90) ?>
                <div class="empty-state-title" style="font-size:18px;">Siap Senyum Hari Ini?</div>
                <div class="empty-state-desc" style="font-size:13px;">Semua antrean hari ini telah selesai dilayani. Kerja bagus tim!</div>
                <button class="btn btn-primary btn-sm"><i class="bx bx-check"></i> Selesai</button>
            </div>

            <div class="empty-state" style="padding:24px; background:var(--surface-subtle); border-radius:var(--radius-md);">
                <?= render_mascot('thinking', 90) ?>
                <div class="empty-state-title" style="font-size:18px;">Belum Ada Janji Temu</div>
                <div class="empty-state-desc" style="font-size:13px;">Yuk jadwalkan pemeriksaan gigi pertama Anda bersama dokter spesialis.</div>
                <button class="btn btn-secondary btn-sm"><i class="bx bx-plus"></i> Buat Janji</button>
            </div>

            <div class="empty-state" style="padding:24px; background:var(--surface-subtle); border-radius:var(--radius-md);">
                <?= render_mascot('alert', 90) ?>
                <div class="empty-state-title" style="font-size:18px;">Peringatan Medis!</div>
                <div class="empty-state-desc" style="font-size:13px;">Pasien terdeteksi memiliki riwayat alergi obat antibiotik Penisilin.</div>
                <button class="btn btn-danger btn-sm"><i class="bx bx-shield-x"></i> Periksa Alergi</button>
            </div>
        </div>
    </div>
</div>

<script>
function demoCountUp() {
    DENTARA.countUp(document.getElementById('stat-pasien'), 28, 1200);
    DENTARA.countUp(document.getElementById('stat-reservasi'), 14, 1200);
    DENTARA.countUp(document.getElementById('stat-stok'), 3, 800);
}

function testConfirm() {
    DENTARA.confirm(
        'Batalkan Reservasi?', 
        'Apakah Anda yakin ingin membatalkan jadwal reservasi kode <strong>DNT123</strong>? Tindakan ini tidak dapat diulang.',
        'Ya, Batalkan', 
        'Kembali'
    ).then(confirmed => {
        if (confirmed) {
            DENTARA.toast('success', 'Reservasi Dibatalkan', 'Slot antrean telah dikembalikan.');
        }
    });
}

function testConfetti() {
    if (typeof confetti === 'function') {
        confetti({
            particleCount: 100,
            spread: 70,
            origin: { y: 0.6 }
        });
    }
    DENTARA.alert('success', 'Selamat, Kamu Hebat!', 'Pemeriksaan gigi selesai tanpa menangis! Kamu mendapatkan <strong>100 Poin Denta</strong> dan 1 Kartu Spesial.');
}

document.addEventListener('DOMContentLoaded', demoCountUp);
</script>
<?php
}, 'styleguide');

