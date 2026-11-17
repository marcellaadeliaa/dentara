<div class="space-y-8">

    <!-- Header Actions -->
    <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Manajemen Farmasi & Apotek</h2>
            <p class="text-xs text-slate-500 mt-0.5">Terima resep elektronik dari dokter, verifikasi ketersediaan stok, dan dispensing obat</p>
        </div>

        <div class="flex items-center gap-3">
            <a href="<?= url('pharmacy/stock') ?>" class="inline-flex items-center gap-2 px-4 py-2.5 bg-purple-600 hover:bg-purple-700 text-white font-bold rounded-xl text-xs shadow-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                Kelola Stok & Batch Obat
            </a>
        </div>
    </div>

    <!-- Antrean Resep Masuk Table -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-base font-bold text-slate-900">Resep Elektronik Masuk Hari Ini</h3>
                <p class="text-xs text-slate-400">Total <?= count($pendingPrescriptions) ?> Resep menunggu penyiapan obat</p>
            </div>
            <span class="text-xs font-semibold text-slate-500 font-mono"><?= formatDateIndo($today) ?></span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left">
                <thead class="bg-slate-50 text-slate-500 uppercase font-bold tracking-wider border-b border-slate-100">
                    <tr>
                        <th class="py-3 px-4">No. Resep & Antrean</th>
                        <th class="py-3 px-4">Pasien & No. RM</th>
                        <th class="py-3 px-4">Dokter Perujuk</th>
                        <th class="py-3 px-4">Status Resep</th>
                        <th class="py-3 px-4 text-right">Aksi Farmasi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (!empty($pendingPrescriptions)): ?>
                        <?php foreach ($pendingPrescriptions as $rx): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="py-3.5 px-4">
                                    <div class="font-mono font-black text-slate-900"><?= sanitize($rx['prescription_number']) ?></div>
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-teal-50 text-teal-800 border border-teal-200 mt-1">
                                        Antrean: <?= sanitize($rx['queue_number']) ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-900"><?= sanitize($rx['patient_name']) ?></div>
                                    <div class="text-[11px] text-slate-400 font-mono"><?= sanitize($rx['medical_record_number']) ?></div>
                                </td>
                                <td class="py-3.5 px-4 font-semibold text-slate-800">
                                    <?= sanitize($rx['doctor_name']) ?>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border <?= $rx['status'] === 'pending' ? 'bg-amber-100 text-amber-800 border-amber-300' : 'bg-blue-100 text-blue-800 border-blue-300' ?>">
                                        <?= ucfirst($rx['status']) ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <a href="<?= url('pharmacy/dispense/' . $rx['id']) ?>" class="px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-bold text-[11px] transition">
                                        Proses Resep & Potong Stok
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="py-12 text-center text-slate-400">
                                Belum ada resep dokter yang masuk hari ini.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Alert Stok Minimum & Kedaluwarsa -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                <h3 class="text-sm font-bold text-slate-900">Perhatian: Stok Kritis / Perlu Pengadaan Obat</h3>
            </div>
            <span class="text-xs text-rose-600 font-bold"><?= count($lowStockMeds) ?> Item Kritis</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($lowStockMeds as $med): ?>
                <div class="p-3.5 bg-rose-50/50 rounded-2xl border border-rose-200 flex items-center justify-between">
                    <div>
                        <div class="font-bold text-xs text-slate-900"><?= sanitize($med['name']) ?></div>
                        <div class="text-[10px] text-slate-500 font-mono"><?= sanitize($med['code']) ?> &bull; Min: <?= $med['min_stock'] ?> <?= sanitize($med['unit']) ?></div>
                    </div>
                    <span class="px-2.5 py-1 rounded-xl text-xs font-black bg-rose-600 text-white">
                        <?= $med['current_stock'] ?> <?= sanitize($med['unit']) ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>
