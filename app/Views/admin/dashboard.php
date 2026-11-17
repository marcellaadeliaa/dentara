<div class="space-y-8">

    <!-- KPI Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- 1. Pasien -->
        <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Total Pasien Terdaftar</span>
                <span class="text-3xl font-black text-slate-900 mt-1 block"><?= $totalPatients ?></span>
                <span class="text-[11px] text-teal-600 font-semibold mt-1 inline-flex items-center gap-1">
                    Rekam Medis Aktif
                </span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
        </div>

        <!-- 2. Antrean Hari Ini -->
        <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Kunjungan Hari Ini</span>
                <span class="text-3xl font-black text-slate-900 mt-1 block"><?= $todayQueues ?></span>
                <span class="text-[11px] text-sky-600 font-semibold mt-1 inline-flex items-center gap-1">
                    Pasien Berobat
                </span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-sky-50 text-sky-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            </div>
        </div>

        <!-- 3. Pendapatan Kasir Hari Ini -->
        <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Penerimaan Kasir Hari Ini</span>
                <span class="text-2xl font-black text-emerald-600 mt-1 block"><?= formatRupiah($totalRevenueToday) ?></span>
                <span class="text-[11px] text-emerald-700 font-semibold mt-1 inline-flex items-center gap-1">
                    Transaksi Lunas
                </span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>

        <!-- 4. Akumulasi Fee Sharing Dokter -->
        <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Bagi Hasil Dokter (Unpaid)</span>
                <span class="text-2xl font-black text-amber-600 mt-1 block"><?= formatRupiah($pendingDoctorFees) ?></span>
                <span class="text-[11px] text-amber-700 font-semibold mt-1 inline-flex items-center gap-1">
                    Siap Dicairkan
                </span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
        </div>
    </div>

    <!-- Quick Shortcuts Master Data -->
    <div class="bg-gradient-to-r from-slate-900 to-slate-800 text-white rounded-3xl p-6 sm:p-8 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-slate-700/80">
            <div>
                <h3 class="text-lg font-bold">Pusat Kelola Master Data & Konfigurasi</h3>
                <p class="text-xs text-slate-400 mt-1">Akses cepat pengaturan tarif, master obat/BMHP, dokter, dan akun staf klinik</p>
            </div>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-6">
            <a href="<?= url('admin/doctors') ?>" class="p-4 bg-slate-800/80 hover:bg-teal-600 rounded-2xl border border-slate-700 transition group text-left">
                <div class="w-8 h-8 rounded-xl bg-white/10 flex items-center justify-center mb-3 group-hover:scale-110 transition">
                    <svg class="w-4 h-4 text-teal-300 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </div>
                <div class="text-xs font-bold text-white">Dokter & Jadwal</div>
                <div class="text-[10px] text-slate-400 group-hover:text-teal-100">Jadwal & Kuota Praktik</div>
            </a>
            <a href="<?= url('admin/treatments') ?>" class="p-4 bg-slate-800/80 hover:bg-teal-600 rounded-2xl border border-slate-700 transition group text-left">
                <div class="w-8 h-8 rounded-xl bg-white/10 flex items-center justify-center mb-3 group-hover:scale-110 transition">
                    <svg class="w-4 h-4 text-teal-300 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                </div>
                <div class="text-xs font-bold text-white">Tindakan & Tarif</div>
                <div class="text-[10px] text-slate-400 group-hover:text-teal-100">Katalog Biaya & % Fee</div>
            </a>
            <a href="<?= url('admin/medicines') ?>" class="p-4 bg-slate-800/80 hover:bg-teal-600 rounded-2xl border border-slate-700 transition group text-left">
                <div class="w-8 h-8 rounded-xl bg-white/10 flex items-center justify-center mb-3 group-hover:scale-110 transition">
                    <svg class="w-4 h-4 text-teal-300 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                </div>
                <div class="text-xs font-bold text-white">Obat & BMHP</div>
                <div class="text-[10px] text-slate-400 group-hover:text-teal-100">Stok, Batch, Kadaluwarsa</div>
            </a>
            <a href="<?= url('admin/users') ?>" class="p-4 bg-slate-800/80 hover:bg-teal-600 rounded-2xl border border-slate-700 transition group text-left">
                <div class="w-8 h-8 rounded-xl bg-white/10 flex items-center justify-center mb-3 group-hover:scale-110 transition">
                    <svg class="w-4 h-4 text-teal-300 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </div>
                <div class="text-xs font-bold text-white">Kelola Pengguna</div>
                <div class="text-[10px] text-slate-400 group-hover:text-teal-100">Hak Akses Role RBAC</div>
            </a>
        </div>
    </div>

    <!-- 2 Column: Low Stock Alerts & Recent Billings -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        
        <!-- Peringatan Obat Menipis -->
        <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                        <h3 class="text-sm font-bold text-slate-900">Peringatan Stok Obat Menipis</h3>
                    </div>
                    <span class="text-[11px] text-slate-400">Monitoring Farmasi</span>
                </div>

                <div class="divide-y divide-slate-100 mt-2">
                    <?php if (!empty($lowStockMeds)): ?>
                        <?php foreach ($lowStockMeds as $med): ?>
                            <div class="py-3 flex items-center justify-between text-xs">
                                <div>
                                    <div class="font-bold text-slate-800"><?= sanitize($med['name']) ?></div>
                                    <div class="text-[11px] text-slate-400">Batas Minimum: <?= $med['min_stock'] ?> <?= sanitize($med['unit']) ?></div>
                                </div>
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-600 border border-rose-200">
                                    Sisa <?= $med['current_stock'] ?> <?= sanitize($med['unit']) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="py-8 text-center text-xs text-slate-400">
                            Semua stok obat dan bahan medis dalam kondisi aman di atas batas minimum.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 mt-4">
                <a href="<?= url('pharmacy/stock') ?>" class="text-xs font-bold text-teal-600 hover:text-teal-700 flex items-center gap-1">
                    Buka Modul Inventaris Obat
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>
        </div>

        <!-- Transaksi Terakhir Kasir -->
        <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                        <h3 class="text-sm font-bold text-slate-900">Transaksi Kasir Terakhir</h3>
                    </div>
                    <span class="text-[11px] text-slate-400">Auto-Billing</span>
                </div>

                <div class="divide-y divide-slate-100 mt-2">
                    <?php if (!empty($recentBillings)): ?>
                        <?php foreach ($recentBillings as $rb): ?>
                            <div class="py-3 flex items-center justify-between text-xs">
                                <div>
                                    <div class="font-bold text-slate-800"><?= sanitize($rb['patient_name']) ?></div>
                                    <div class="text-[11px] text-slate-400 font-mono"><?= sanitize($rb['invoice_number']) ?></div>
                                </div>
                                <div class="text-right">
                                    <div class="font-black text-slate-900"><?= formatRupiah($rb['grand_total']) ?></div>
                                    <span class="inline-block text-[10px] font-bold px-2 py-0.5 rounded <?= $rb['payment_status'] === 'paid' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' ?>">
                                        <?= strtoupper($rb['payment_status']) ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="py-8 text-center text-xs text-slate-400">
                            Belum ada riwayat transaksi kasir yang tercatat hari ini.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 mt-4">
                <a href="<?= url('cashier/dashboard') ?>" class="text-xs font-bold text-teal-600 hover:text-teal-700 flex items-center gap-1">
                    Buka Kasir & Cetak Kuitansi
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>
        </div>

    </div>

</div>
