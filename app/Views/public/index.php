<!-- Hero Section -->
<section class="relative bg-gradient-to-b from-teal-50/70 via-white to-slate-50 py-16 lg:py-24 overflow-hidden border-b border-slate-200/60">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
            
            <div class="lg:col-span-7 space-y-6 text-center lg:text-left">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-teal-100/80 border border-teal-200 text-teal-800 text-xs font-semibold">
                    <span class="flex h-2 w-2 rounded-full bg-teal-600 animate-pulse"></span>
                    Sistem Pelayanan Klinik Gigi Terintegrasi
                </div>

                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-slate-900 tracking-tight leading-[1.15]">
                    Senyum Sehat & <br class="hidden sm:inline">
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-teal-600 via-cyan-600 to-emerald-600">Percaya Diri</span> Bersama DENTARA
                </h1>

                <p class="text-base sm:text-lg text-slate-600 max-w-2xl mx-auto lg:mx-0 leading-relaxed">
                    Klinik gigi modern dengan standar sterilisasi medis tinggi, dokter gigi spesialis berpengalaman, transparansi tarif, dan kemudahan reservasi tanpa antre lama.
                </p>

                <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4 pt-2">
                    <a href="<?= url('booking') ?>" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-2xl bg-teal-600 hover:bg-teal-700 text-white font-bold text-sm shadow-lg shadow-teal-600/30 transition hover:-translate-y-0.5">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        Daftar Online Sekarang
                    </a>
                    <a href="<?= url('queue') ?>" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-2xl bg-white hover:bg-slate-100 text-slate-800 font-bold text-sm border border-slate-300 shadow-sm transition">
                        <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        Pantau Antrean (Live Tracker)
                    </a>
                </div>

                <!-- Stats summary -->
                <div class="pt-6 grid grid-cols-3 gap-4 border-t border-slate-200/80 text-left">
                    <div>
                        <div class="text-2xl font-black text-slate-900">100%</div>
                        <div class="text-xs text-slate-500 font-medium">Rekam Medis Digital</div>
                    </div>
                    <div>
                        <div class="text-2xl font-black text-slate-900">13+</div>
                        <div class="text-xs text-slate-500 font-medium">Tindakan Gigi Lengkap</div>
                    </div>
                    <div>
                        <div class="text-2xl font-black text-slate-900">Transparan</div>
                        <div class="text-xs text-slate-500 font-medium">Tarif Tanpa Biaya Tersembunyi</div>
                    </div>
                </div>
            </div>

            <!-- Live Antrean Card Preview -->
            <div class="lg:col-span-5">
                <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-xl shadow-slate-200/50 border border-slate-100 relative">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                        <div class="flex items-center gap-2.5">
                            <span class="relative flex h-3 w-3">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                            </span>
                            <span class="text-xs font-bold text-slate-900 uppercase tracking-wider">Antrean Sedang Berjalan</span>
                        </div>
                        <span class="text-[11px] text-slate-400 font-medium"><?= formatDateIndo(date('Y-m-d')) ?></span>
                    </div>

                    <div class="py-6 space-y-4">
                        <?php if (!empty($todayCalling)): ?>
                            <?php foreach ($todayCalling as $tc): ?>
                                <div class="bg-gradient-to-r from-teal-50 to-cyan-50 p-4 rounded-2xl border border-teal-100 flex items-center justify-between">
                                    <div>
                                        <span class="text-xs font-bold text-teal-800"><?= sanitize($tc['room_name']) ?></span>
                                        <div class="text-[11px] text-slate-500"><?= sanitize($tc['doctor_name']) ?></div>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-3xl font-black text-teal-700 tracking-tight"><?= sanitize($tc['queue_number']) ?></span>
                                        <span class="block text-[10px] font-semibold text-emerald-600 uppercase">Dipanggil</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-6">
                                <div class="w-14 h-14 bg-teal-50 rounded-2xl flex items-center justify-center mx-auto mb-3 text-teal-600">
                                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <h4 class="text-sm font-bold text-slate-800">Ruangan Poli Standby</h4>
                                <p class="text-xs text-slate-500 mt-1">Belum ada pasien yang sedang dipanggil saat ini.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
                        <span class="text-xs text-slate-500">Punya nomor antrean?</span>
                        <a href="<?= url('queue') ?>" class="text-xs font-bold text-teal-600 hover:text-teal-700 flex items-center gap-1">
                            Lacak Posisi Saya
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- Active Promotions Section -->
<?php if (!empty($promotions)): ?>
<section class="py-16 bg-white border-b border-slate-200/60">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row md:items-end justify-between mb-10">
            <div>
                <span class="text-xs font-bold text-teal-600 tracking-wider uppercase">Penawaran Spesial</span>
                <h2 class="text-3xl font-extrabold text-slate-900 mt-1">Promo Perawatan Gigi Bulan Ini</h2>
            </div>
            <p class="text-sm text-slate-500 mt-2 md:mt-0">Nikmati potongan harga eksklusif untuk pendaftaran online</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <?php foreach ($promotions as $promo): ?>
                <div class="bg-gradient-to-br from-slate-900 to-slate-800 text-white rounded-3xl p-6 flex flex-col justify-between shadow-lg hover:shadow-xl transition">
                    <div>
                        <div class="inline-flex items-center px-3 py-1 rounded-full bg-teal-500/20 text-teal-300 border border-teal-500/30 text-xs font-bold mb-4">
                            <?php if ($promo['discount_type'] === 'percentage'): ?>
                                Diskon <?= (int)$promo['discount_value'] ?>%
                            <?php else: ?>
                                Potongan <?= formatRupiah($promo['discount_value']) ?>
                            <?php endif; ?>
                        </div>
                        <h3 class="text-xl font-bold mb-2"><?= sanitize($promo['title']) ?></h3>
                        <p class="text-xs text-slate-300 leading-relaxed mb-4"><?= sanitize($promo['description']) ?></p>
                    </div>

                    <div class="pt-4 border-t border-slate-700/80 flex items-center justify-between text-xs text-slate-400">
                        <span>Berlaku s/d <?= formatDateIndo($promo['end_date']) ?></span>
                        <a href="<?= url('booking') ?>" class="text-teal-400 hover:text-teal-300 font-bold flex items-center gap-1">
                            Klaim Promo
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Services & Pricing Highlight -->
<section class="py-16 bg-slate-50 border-b border-slate-200/60">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-12">
            <span class="text-xs font-bold text-teal-600 tracking-wider uppercase">Layanan Unggulan</span>
            <h2 class="text-3xl font-extrabold text-slate-900 mt-1">Tarif Tindakan Transparan</h2>
            <p class="text-sm text-slate-500 mt-2">DENTARA mengedepankan transparansi biaya tanpa kejutan tambahan di kasir.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($treatments as $t): ?>
                <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm hover:shadow-md hover:border-teal-300 transition group flex flex-col justify-between">
                    <div>
                        <span class="text-[10px] font-bold text-teal-700 bg-teal-50 px-2 py-0.5 rounded border border-teal-200">
                            <?= sanitize($t['category']) ?>
                        </span>
                        <h3 class="text-base font-bold text-slate-900 mt-3 group-hover:text-teal-600 transition">
                            <?= sanitize($t['name']) ?>
                        </h3>
                        <p class="text-xs text-slate-500 mt-2 line-clamp-2">
                            <?= sanitize($t['description'] ?? 'Tindakan medis gigi dengan peralatan higienis dan bahan berkualitas tinggi.') ?>
                        </p>
                    </div>

                    <div class="pt-4 mt-4 border-t border-slate-100 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] text-slate-400 font-semibold block">Mulai dari</span>
                            <span class="text-lg font-black text-slate-900"><?= formatRupiah($t['price']) ?></span>
                        </div>
                        <a href="<?= url('booking') ?>" class="p-2 rounded-xl bg-teal-50 text-teal-700 hover:bg-teal-600 hover:text-white transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-10">
            <a href="<?= url('services') ?>" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-white hover:bg-slate-100 border border-slate-300 text-slate-700 font-bold text-sm shadow-sm transition">
                Lihat Seluruh Daftar Layanan & Tarif
                <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
            </a>
        </div>
    </div>
</section>

<!-- Doctors Team Section -->
<section class="py-16 bg-white border-b border-slate-200/60">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-12">
            <span class="text-xs font-bold text-teal-600 tracking-wider uppercase">Tim Medis Profesional</span>
            <h2 class="text-3xl font-extrabold text-slate-900 mt-1">Dokter Gigi Kami</h2>
            <p class="text-sm text-slate-500 mt-2">Dukungan dokter gigi bersertifikat dengan kompetensi spesialis dan umum.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-4xl mx-auto">
            <?php foreach ($doctors as $doc): ?>
                <div class="bg-slate-50 rounded-3xl p-6 sm:p-8 border border-slate-200/80 flex flex-col sm:flex-row items-center sm:items-start gap-6">
                    <div class="w-24 h-24 rounded-2xl bg-teal-100 text-teal-700 flex items-center justify-center font-black text-3xl flex-shrink-0 shadow-inner">
                        <?= substr($doc['full_name'], 0, 1) ?>
                    </div>
                    <div class="space-y-2 text-center sm:text-left">
                        <span class="inline-block text-[11px] font-bold text-teal-700 bg-teal-50 px-2.5 py-0.5 rounded-full border border-teal-200">
                            <?= sanitize($doc['specialization']) ?>
                        </span>
                        <h3 class="text-xl font-bold text-slate-900"><?= sanitize($doc['full_name']) ?></h3>
                        <p class="text-xs text-slate-400 font-medium"><?= sanitize($doc['sip_number']) ?></p>
                        <p class="text-xs text-slate-600 leading-relaxed"><?= sanitize($doc['bio']) ?></p>
                        <div class="pt-2">
                            <span class="text-xs font-semibold text-slate-700">Ruangan: </span>
                            <span class="text-xs text-teal-600 font-bold"><?= sanitize($doc['room_name']) ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- CTA Booking Section -->
<section class="py-16 bg-gradient-to-r from-teal-700 via-teal-800 to-cyan-900 text-white">
    <div class="max-w-5xl mx-auto px-4 text-center space-y-6">
        <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight">Siap Memiliki Senyum Lebih Sehat Hari Ini?</h2>
        <p class="text-base text-teal-100 max-w-2xl mx-auto">
            Daftarkan diri Anda atau keluarga secara online dalam kurang dari 2 menit. Dapatkan nomor booking dan nikmati pelayanan tanpa ribet.
        </p>
        <div class="pt-2">
            <a href="<?= url('booking') ?>" class="inline-flex items-center gap-2 px-8 py-4 bg-white hover:bg-slate-100 text-teal-800 font-extrabold rounded-2xl text-base shadow-xl transition hover:-translate-y-0.5">
                Pesan Jadwal Konsultasi Sekarang
            </a>
        </div>
    </div>
</section>
