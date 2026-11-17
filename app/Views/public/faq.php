<div class="py-12 bg-slate-50 min-h-screen">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="text-center mb-12">
            <span class="text-xs font-bold text-teal-600 tracking-wider uppercase">Pusat Bantuan</span>
            <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-900 mt-1">Frequently Asked Questions (FAQ)</h1>
            <p class="text-sm text-slate-500 mt-2">
                Pertanyaan yang sering diajukan seputar alur pendaftaran, antrean, prosedur medis, dan pembayaran.
            </p>
        </div>

        <!-- FAQ Items -->
        <div class="space-y-4 mb-16">
            <?php foreach ($faqs as $idx => $faq): ?>
                <details class="group bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm [&_summary::-webkit-details-marker]:hidden" <?= $idx === 0 ? 'open' : '' ?>>
                    <summary class="flex items-center justify-between cursor-pointer list-none">
                        <span class="font-bold text-sm text-slate-900 group-hover:text-teal-600 transition pr-4">
                            <?= sanitize($faq['question']) ?>
                        </span>
                        <span class="w-8 h-8 rounded-full bg-slate-100 group-open:bg-teal-50 group-open:text-teal-600 text-slate-500 flex items-center justify-center flex-shrink-0 transition">
                            <svg class="w-4 h-4 transition duration-300 group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </span>
                    </summary>
                    <p class="text-xs text-slate-600 leading-relaxed mt-4 pt-4 border-t border-slate-100">
                        <?= sanitize($faq['answer']) ?>
                    </p>
                </details>
            <?php endforeach; ?>
        </div>

        <!-- Contact & Location Box -->
        <div class="bg-white rounded-3xl p-8 border border-slate-200/80 shadow-sm text-center">
            <h3 class="text-lg font-bold text-slate-900 mb-2">Masih Memiliki Pertanyaan Lain?</h3>
            <p class="text-xs text-slate-500 max-w-md mx-auto mb-6">
                Layanan Customer Care DENTARA siap membantu Anda setiap hari kerja melalui WhatsApp atau telepon langsung.
            </p>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="https://wa.me/6281234567890" target="_blank" class="inline-flex items-center gap-2 px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs transition shadow-sm">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.007c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.861.173.086.275.072.376-.044.101-.116.433-.506.549-.68.116-.173.231-.145.39-.087s1.011.477 1.184.564.289.13.332.202c.043.073.043.419-.101.824z"/></svg>
                    Hubungi via WhatsApp
                </a>
                <a href="<?= url('booking') ?>" class="inline-flex items-center gap-2 px-6 py-3 bg-teal-600 hover:bg-teal-700 text-white font-bold rounded-xl text-xs transition shadow-sm">
                    Buat Janji Konsultasi
                </a>
            </div>
        </div>

    </div>
</div>
