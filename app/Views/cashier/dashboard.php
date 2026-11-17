<div class="space-y-8">

    <!-- Top KPI Bar -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
        <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Total Penerimaan Hari Ini</span>
                <span class="text-2xl font-black text-emerald-600 mt-1 block"><?= formatRupiah($totalCollectedToday) ?></span>
                <span class="text-[11px] text-emerald-700 font-semibold mt-1">Kasir Tunai & Non-Tunai</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>

        <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Tagihan Menunggu Pembayaran</span>
                <span class="text-2xl font-black text-amber-600 mt-1 block"><?= count($unpaidBillings) ?> Pasien</span>
                <span class="text-[11px] text-amber-700 font-semibold mt-1">Antre Kasir</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            </div>
        </div>

        <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Transaksi Lunas Hari Ini</span>
                <span class="text-2xl font-black text-slate-900 mt-1 block"><?= count($paidToday) ?> Kuitansi</span>
                <span class="text-[11px] text-teal-600 font-semibold mt-1">Kuitansi Resmi Diterbitkan</span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>
    </div>

    <!-- Antrean Tagihan Unpaid (Auto-Billing Engine) -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-base font-bold text-slate-900">Tagihan Pasien Menunggu Pembayaran (Auto-Billing)</h3>
                <p class="text-xs text-slate-400">Kompilasi otomatis biaya registrasi, konsultasi, tindakan gigi, dan obat apotek</p>
            </div>
            <span class="text-xs font-bold text-amber-800 bg-amber-50 px-3 py-1 rounded-full border border-amber-200">
                <?= count($unpaidBillings) ?> Tagihan Aktif
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left">
                <thead class="bg-slate-50 text-slate-500 uppercase font-bold tracking-wider border-b border-slate-100">
                    <tr>
                        <th class="py-3 px-4">No. Invoice & Antrean</th>
                        <th class="py-3 px-4">Pasien & No. RM</th>
                        <th class="py-3 px-4">Dokter Pemeriksa</th>
                        <th class="py-3 px-4">Rincian Komponen Biaya</th>
                        <th class="py-3 px-4">Total Tagihan</th>
                        <th class="py-3 px-4 text-right">Aksi Kasir</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (!empty($unpaidBillings)): ?>
                        <?php foreach ($unpaidBillings as $b): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="py-3.5 px-4">
                                    <div class="font-mono font-black text-slate-900"><?= sanitize($b['invoice_number']) ?></div>
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-teal-50 text-teal-800 border border-teal-200 mt-1">
                                        Antrean: <?= sanitize($b['queue_number']) ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-900"><?= sanitize($b['patient_name']) ?></div>
                                    <div class="text-[11px] text-slate-400 font-mono"><?= sanitize($b['medical_record_number']) ?></div>
                                </td>
                                <td class="py-3.5 px-4 font-semibold text-slate-800">
                                    <?= sanitize($b['doctor_name']) ?>
                                </td>
                                <td class="py-3.5 px-4 text-slate-600">
                                    <div>Reg: <?= formatRupiah($b['registration_fee']) ?> &bull; Konsul: <?= formatRupiah($b['consultation_fee']) ?></div>
                                    <div class="text-[11px] text-slate-500">Tindakan: <?= formatRupiah($b['treatments_total']) ?> &bull; Obat: <?= formatRupiah($b['medicines_total']) ?></div>
                                </td>
                                <td class="py-3.5 px-4 font-black text-slate-900 text-sm">
                                    <?= formatRupiah($b['grand_total']) ?>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <a href="<?= url('cashier/pay/' . $b['id']) ?>" class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold text-[11px] transition shadow-sm">
                                        Proses Pembayaran
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-400">
                                Tidak ada tagihan yang tertunda saat ini. Semua pasien telah diselesaikan.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Riwayat Transaksi Lunas Hari Ini -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">
            <div>
                <h3 class="text-base font-bold text-slate-900">Riwayat Kuitansi Lunas Hari Ini</h3>
                <p class="text-xs text-slate-400">Daftar transaksi yang telah diselesaikan dan kuitansi telah dicetak</p>
            </div>
            <span class="text-xs font-semibold text-slate-500 font-mono"><?= formatDateIndo($today) ?></span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left">
                <thead class="bg-slate-50 text-slate-500 uppercase font-bold tracking-wider border-b border-slate-100">
                    <tr>
                        <th class="py-2.5 px-3">No. Invoice</th>
                        <th class="py-2.5 px-3">Nama Pasien</th>
                        <th class="py-2.5 px-3">Metode Bayar</th>
                        <th class="py-2.5 px-3">Total Transaksi</th>
                        <th class="py-2.5 px-3">Waktu Bayar</th>
                        <th class="py-2.5 px-3 text-right">Kuitansi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (!empty($paidToday)): ?>
                        <?php foreach ($paidToday as $pt): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="py-3 px-3 font-mono font-bold text-slate-800"><?= sanitize($pt['invoice_number']) ?></td>
                                <td class="py-3 px-3 font-bold text-slate-900"><?= sanitize($pt['patient_name']) ?></td>
                                <td class="py-3 px-3">
                                    <span class="inline-block uppercase text-[10px] font-bold px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <?= strtoupper($pt['payment_method'] ?? 'CASH') ?>
                                    </span>
                                </td>
                                <td class="py-3 px-3 font-black text-slate-900"><?= formatRupiah($pt['grand_total']) ?></td>
                                <td class="py-3 px-3 text-slate-500 font-mono"><?= date('H:i:s', strtotime($pt['paid_at'])) ?> WIB</td>
                                <td class="py-3 px-3 text-right">
                                    <a href="<?= url('cashier/receipt/' . $pt['id']) ?>" target="_blank" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg font-bold text-[11px] transition">
                                        Cetak Kuitansi
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="py-6 text-center text-slate-400">
                                Belum ada transaksi yang dibayar hari ini.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
