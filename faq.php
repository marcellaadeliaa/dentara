<?php
/**
 * DENTARA - Halaman FAQ (Pertanyaan yang Sering Diajukan)
 * File: faq.php
 */

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/layout_public.php';

render_public_layout('Pertanyaan yang Sering Diajukan (FAQ)', function() {
    global $pdo;

    $stmt = $pdo->query("SELECT * FROM faq ORDER BY urutan ASC");
    $faqs = $stmt->fetchAll();
?>
<div class="container" style="padding-top:110px; padding-bottom:60px; max-width:860px;">
    <div class="text-center mb-6">
        <span class="badge badge-purple mb-2">PUSAT BANTUAN & INFORMASI</span>
        <h1 style="font-size:clamp(24px, 4vw, 36px); margin-bottom:8px;">Pertanyaan yang Sering Diajukan (FAQ)</h1>
        <p class="text-muted">Temukan jawaban cepat seputar pendaftaran, metode pembayaran, klaim asuransi, dan fasilitas klinik.</p>
    </div>

    <!-- Live Search Filter for FAQ -->
    <div class="card p-4 mb-6">
        <div class="input-icon-group">
            <i class="bx bx-search"></i>
            <input type="text" id="faq-search" class="form-control" placeholder="Ketik kata kunci pertanyaan (misal: asuransi, anak, biaya, jadwal)..." onkeyup="filterFaq()">
        </div>
    </div>

    <!-- FAQ Accordion List -->
    <div id="faq-list-container" style="display:flex; flex-direction:column; gap:12px;">
        <?php foreach ($faqs as $idx => $f): ?>
        <div class="card p-4 faq-item" style="cursor:pointer;" onclick="toggleFaqItem(this)">
            <div class="d-flex align-items-center justify-content-between">
                <h3 class="faq-question" style="font-size:16px; margin:0; color:var(--text-main); font-weight:700;">
                    <i class="bx bx-help-circle text-primary"></i> <?= e($f['pertanyaan']) ?>
                </h3>
                <i class="bx bx-chevron-down faq-arrow" style="font-size:22px; transition:transform 0.3s;"></i>
            </div>
            <div class="faq-answer text-muted mt-3" style="display:<?= $idx === 0 ? 'block' : 'none' ?>; font-size:14px; line-height:1.6; border-top:1px solid var(--border-color); padding-top:12px;">
                <?= e($f['jawaban']) ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Butuh Bantuan Lanjutan -->
    <div class="card p-6 text-center mt-6" style="background:var(--surface-subtle); border:1px dashed var(--primary);">
        <h4 style="font-size:18px; margin-bottom:6px;">Masih Memiliki Pertanyaan Lain?</h4>
        <p class="text-muted" style="font-size:14px; margin-bottom:16px;">
            Staf customer service kami siap menjawab pertanyaan Anda melalui sambungan WhatsApp langsung.
        </p>
        <a href="https://wa.me/6281234567890?text=Halo%20DENTARA,%20saya%20ingin%20bertanya%20seputar%20layanan" 
           target="_blank" class="btn btn-primary btn-md no-spa" style="display:inline-flex;">
            <i class="bx bxl-whatsapp"></i> Hubungi Kami via WhatsApp
        </a>
    </div>
</div>

<script>
function toggleFaqItem(card) {
    const answer = card.querySelector('.faq-answer');
    const arrow = card.querySelector('.faq-arrow');
    if (answer.style.display === 'none' || answer.style.display === '') {
        answer.style.display = 'block';
        arrow.style.transform = 'rotate(180deg)';
    } else {
        answer.style.display = 'none';
        arrow.style.transform = 'rotate(0deg)';
    }
}

function filterFaq() {
    const query = document.getElementById('faq-search').value.toLowerCase();
    const items = document.querySelectorAll('.faq-item');
    items.forEach(item => {
        const text = item.textContent.toLowerCase();
        if (text.includes(query)) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
}
</script>
<?php
}, 'faq');

