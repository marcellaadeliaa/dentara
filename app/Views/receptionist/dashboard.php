<div class="space-y-8">

    <!-- Top Action Bar -->
    <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Manajemen Antrean Front Desk</h2>
            <p class="text-xs text-slate-500 mt-0.5">Kelola kedatangan pasien langsung (walk-in) dan konfirmasi reservasi online</p>
        </div>

        <div class="flex items-center gap-3">
            <a href="<?= url('receptionist/patients/new') ?>" class="inline-flex items-center gap-2 px-5 py-2.5 bg-teal-600 hover:bg-teal-700 text-white font-bold rounded-xl text-xs shadow-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Pendaftaran Pasien Walk-in
            </a>
            <a href="<?= url('queue') ?>" target="_blank" class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl text-xs transition">
                <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                Display Antrean Layar
            </a>
        </div>
    </div>

    <!-- Antrean Hari Ini Table -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-base font-bold text-slate-900">Daftar Antrean Poli Hari Ini</h3>
                <p class="text-xs text-slate-400">Total <?= count($queues) ?> Pasien terdaftar dalam antrean</p>
            </div>
            <span class="text-xs font-semibold text-slate-500 font-mono"><?= formatDateIndo($today) ?></span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left">
                <thead class="bg-slate-50 text-slate-500 uppercase font-bold tracking-wider border-b border-slate-100">
                    <tr>
                        <th class="py-3 px-4">No. Antrean</th>
                        <th class="py-3 px-4">Pasien & No. RM</th>
                        <th class="py-3 px-4">Poli & Dokter Tujuan</th>
                        <th class="py-3 px-4">Tipe & Prioritas</th>
                        <th class="py-3 px-4">Status Antrean</th>
                        <th class="py-3 px-4 text-right">Aksi Front Desk</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (!empty($queues)): ?>
                        <?php foreach ($queues as $q): ?>
                            <tr class="hover:bg-slate-50 transition">
                                <td class="py-3.5 px-4 font-black text-slate-900 text-sm">
                                    <span class="inline-block px-3 py-1 bg-teal-50 text-teal-800 rounded-xl border border-teal-200 font-mono">
                                        <?= sanitize($q['queue_number']) ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-900"><?= sanitize($q['patient_name']) ?></div>
                                    <div class="text-[11px] text-slate-400 font-mono"><?= sanitize($q['medical_record_number']) ?> &bull; <?= sanitize($q['patient_phone']) ?></div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-semibold text-slate-800"><?= sanitize($q['doctor_name']) ?></div>
                                    <div class="text-[11px] text-teal-600 font-medium"><?= sanitize($q['room_name']) ?></div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="inline-block uppercase text-[10px] font-bold px-2 py-0.5 rounded bg-slate-100 text-slate-600">
                                        <?= $q['queue_type'] === 'online' ? 'Online Booking' : 'Walk-in' ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <?= getQueueBadge($q['status']) ?>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="inline-flex items-center gap-1.5">
                                        <?php if ($q['status'] === 'waiting'): ?>
                                            <a href="<?= url('receptionist/queue/call/' . $q['id']) ?>" class="px-2.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-bold text-[11px] transition">
                                                Panggil
                                            </a>
                                        <?php elseif ($q['status'] === 'calling'): ?>
                                            <a href="<?= url('receptionist/queue/start/' . $q['id']) ?>" class="px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-bold text-[11px] transition">
                                                Masuk Poli
                                            </a>
                                        <?php else: ?>
                                            <span class="text-[11px] text-slate-400 font-medium">Dalam Proses</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-400">
                                Belum ada antrean yang masuk untuk hari ini.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Reservasi Online Menunggu Check-in -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">
            <div>
                <h3 class="text-base font-bold text-slate-900">Reservasi Online Menunggu Check-in</h3>
                <p class="text-xs text-slate-400">Pasien yang telah mendaftar online dan perlu diverifikasi saat tiba di klinik</p>
            </div>
            <span class="text-xs font-bold text-teal-700 bg-teal-50 px-3 py-1 rounded-full border border-teal-200">
                <?= count($appointments) ?> Reservasi
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left">
                <thead class="bg-slate-50 text-slate-500 uppercase font-bold tracking-wider border-b border-slate-100">
                    <tr>
                        <th class="py-2.5 px-3">Kode Booking</th>
                        <th class="py-2.5 px-3">Nama Pasien</th>
                        <th class="py-2.5 px-3">Dokter Pilihan</th>
                        <th class="py-2.5 px-3">Keluhan Awal</th>
                        <th class="py-2.5 px-3 text-right">Aksi Check-in</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (!empty($appointments)): ?>
                        <?php foreach ($appointments as $apt): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="py-3 px-3 font-mono font-bold text-teal-700"><?= sanitize($apt['booking_code']) ?></td>
                                <td class="py-3 px-3 font-bold text-slate-800"><?= sanitize($apt['patient_name']) ?></td>
                                <td class="py-3 px-3 text-slate-600"><?= sanitize($apt['doctor_name']) ?> (<?= sanitize($apt['room_name']) ?>)</td>
                                <td class="py-3 px-3 text-slate-500 max-w-xs truncate"><?= sanitize($apt['complaint']) ?></td>
                                <td class="py-3 px-3 text-right">
                                    <a href="<?= url('receptionist/booking/checkin/' . $apt['id']) ?>" class="px-3 py-1.5 bg-teal-600 hover:bg-teal-700 text-white font-bold rounded-lg text-[11px] transition shadow-sm">
                                        Check-in & Terbitkan Antrean
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="py-6 text-center text-slate-400">
                                Tidak ada reservasi online yang menunggu check-in.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
