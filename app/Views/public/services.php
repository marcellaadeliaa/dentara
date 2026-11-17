<div class="py-12 bg-slate-50 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="text-center max-w-3xl mx-auto mb-12">
            <span class="text-xs font-bold text-teal-600 tracking-wider uppercase">Katalog Layanan Klinis</span>
            <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-900 mt-1">Daftar Layanan & Tarif Gigi</h1>
            <p class="text-sm text-slate-500 mt-2">
                DENTARA menyajikan transparansi tarif penuh untuk setiap prosedur kesehatan gigi dan mulut, mulai dari perawatan preventif, konservasi, hingga bedah minor.
            </p>
        </div>

        <!-- Grouped Category Cards -->
        <div class="space-y-10">
            <?php foreach ($categorized as $category => $items): ?>
                <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-sm">
                    <div class="flex items-center gap-3 pb-4 mb-6 border-b border-slate-100">
                        <div class="w-10 h-10 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center font-bold">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-slate-900"><?= sanitize($category) ?></h2>
                            <p class="text-xs text-slate-400"><?= count($items) ?> Pilihan Tindakan</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php foreach ($items as $item): ?>
                            <div class="p-4 rounded-2xl bg-slate-50/70 border border-slate-200 hover:border-teal-300 hover:bg-teal-50/20 transition flex flex-col justify-between">
                                <div>
                                    <div class="flex items-center justify-between gap-2 mb-1">
                                        <span class="text-[10px] font-mono font-bold text-slate-400 uppercase"><?= sanitize($item['code']) ?></span>
                                    </div>
                                    <h3 class="text-sm font-bold text-slate-800"><?= sanitize($item['name']) ?></h3>
                                    <?php if (!empty($item['description'])): ?>
                                        <p class="text-xs text-slate-500 mt-1 leading-relaxed"><?= sanitize($item['description']) ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="mt-4 pt-3 border-t border-slate-200/60 flex items-center justify-between">
                                    <span class="text-base font-extrabold text-teal-700"><?= formatRupiah($item['price']) ?></span>
                                    <a href="<?= url('booking') ?>" class="text-xs font-semibold text-teal-600 hover:text-teal-800 flex items-center gap-1">
                                        Pilih
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Tariff Transparency Guarantee Banner -->
        <div class="mt-12 p-8 bg-gradient-to-r from-teal-900 to-slate-900 text-white rounded-3xl flex flex-col sm:flex-row items-center justify-between gap-6">
            <div class="space-y-1 text-center sm:text-left">
                <h3 class="text-xl font-bold">Butuh Konsultasi Diagnosa Sebelum Tindakan?</h3>
                <p class="text-xs text-teal-200 max-w-xl">
                    Tim dokter gigi kami akan melakukan pemeriksaan mendalam, menjelaskan opsi rencana perawatan beserta estimasi biaya sebelum prosedur dilakukan.
                </p>
            </div>
            <a href="<?= url('booking') ?>" class="px-6 py-3.5 bg-teal-500 hover:bg-teal-400 text-slate-950 font-extrabold rounded-2xl text-xs shadow-md transition whitespace-nowrap">
                Jadwalkan Konsultasi
            </a>
        </div>

    </div>
</div>
