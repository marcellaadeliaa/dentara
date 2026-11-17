<div class="space-y-8">

    <?php if ($currentPatient): ?>
        <!-- ACTIVE PATIENT BANNER & CLINICAL ALERTS -->
        <div class="bg-white rounded-3xl p-6 sm:p-8 border-2 border-teal-500 shadow-lg shadow-teal-500/10">
            <div class="flex flex-col md:flex-row md:items-center justify-between pb-6 border-b border-slate-100 gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-teal-100 text-teal-800 border border-teal-200">
                            Pasien Sedang Diperiksa
                        </span>
                        <span class="font-mono text-xs font-bold text-slate-400"><?= sanitize($currentPatient['medical_record_number']) ?></span>
                    </div>
                    <h2 class="text-2xl font-black text-slate-900"><?= sanitize($currentPatient['patient_name']) ?></h2>
                    <p class="text-xs text-slate-500 mt-1">
                        Jenis Kelamin: <strong><?= $currentPatient['gender'] === 'L' ? 'Laki-laki' : 'Perempuan' ?></strong> &bull;
                        Tgl Lahir: <strong><?= formatDateIndo($currentPatient['birth_date']) ?></strong> &bull;
                        HP: <strong><?= sanitize($currentPatient['phone']) ?></strong>
                    </p>
                </div>

                <div class="text-right">
                    <span class="text-xs text-slate-400 font-semibold block uppercase tracking-wider">Nomor Antrean</span>
                    <span class="text-4xl font-black text-teal-700 font-mono"><?= sanitize($currentPatient['queue_number']) ?></span>
                </div>
            </div>

            <!-- Medical Alert Warnings (Alergi & Riwayat Sistemik) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
                <!-- Alergi -->
                <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 flex items-start gap-3">
                    <div class="w-8 h-8 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center flex-shrink-0 mt-0.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    <div>
                        <span class="text-[11px] font-bold text-rose-800 uppercase tracking-wider block">Peringatan Alergi Obat / Makanan</span>
                        <p class="text-xs font-semibold text-rose-900 mt-0.5">
                            <?= !empty($currentPatient['allergies']) ? sanitize($currentPatient['allergies']) : 'Tidak ada riwayat alergi yang dilaporkan.' ?>
                        </p>
                    </div>
                </div>

                <!-- Riwayat Sistemik -->
                <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 flex items-start gap-3">
                    <div class="w-8 h-8 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center flex-shrink-0 mt-0.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <span class="text-[11px] font-bold text-amber-800 uppercase tracking-wider block">Riwayat Penyakit Sistemik</span>
                        <p class="text-xs font-semibold text-amber-900 mt-0.5">
                            <?= !empty($currentPatient['medical_history']) ? sanitize($currentPatient['medical_history']) : 'Tidak ada riwayat penyakit sistemik.' ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Keluhan Awal Pasien -->
            <?php if (!empty($currentPatient['complaint'])): ?>
                <div class="mt-4 p-3 bg-slate-50 rounded-xl border border-slate-200 text-xs text-slate-700">
                    <strong class="text-slate-900">Keluhan Pasien saat Pendaftaran:</strong> <?= sanitize($currentPatient['complaint']) ?>
                </div>
            <?php endif; ?>

            <div class="mt-6 flex justify-end">
                <a href="<?= url('doctor/emr/create/' . $currentPatient['id']) ?>" class="px-6 py-3 bg-teal-600 hover:bg-teal-700 text-white font-bold rounded-xl text-xs shadow-md shadow-teal-600/30 transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Buka Lembar Rekam Medis SOAP & Tindakan
                </a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Antrean Pasien Dokter Hari Ini -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-base font-bold text-slate-900">Antrean Poli Gigi Dokter</h3>
                <p class="text-xs text-slate-400">Daftar pasien yang dijadwalkan untuk Anda hari ini</p>
            </div>
            <span class="text-xs font-semibold text-slate-500 font-mono"><?= formatDateIndo($today) ?></span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left">
                <thead class="bg-slate-50 text-slate-500 uppercase font-bold tracking-wider border-b border-slate-100">
                    <tr>
                        <th class="py-3 px-4">No. Antrean</th>
                        <th class="py-3 px-4">Nama Pasien & No. RM</th>
                        <th class="py-3 px-4">Keluhan Awal</th>
                        <th class="py-3 px-4">Alergi & Riwayat</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (!empty($queues)): ?>
                        <?php foreach ($queues as $q): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="py-3.5 px-4 font-black font-mono text-sm text-teal-800">
                                    <?= sanitize($q['queue_number']) ?>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-900"><?= sanitize($q['patient_name']) ?></div>
                                    <div class="text-[11px] text-slate-400 font-mono"><?= sanitize($q['medical_record_number']) ?></div>
                                </td>
                                <td class="py-3.5 px-4 text-slate-600 max-w-xs truncate"><?= sanitize($q['complaint'] ?? '-') ?></td>
                                <td class="py-3.5 px-4">
                                    <?php if (!empty($q['allergies'])): ?>
                                        <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                            Alergi: <?= sanitize($q['allergies']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-slate-400 text-[11px]">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4">
                                    <?= getQueueBadge($q['status']) ?>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <a href="<?= url('doctor/consultation/open/' . $q['id']) ?>" class="px-3 py-1.5 bg-teal-600 hover:bg-teal-700 text-white rounded-lg font-bold text-[11px] transition">
                                        Periksa Pasien
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-400">
                                Tidak ada antrean pasien untuk jadwal Anda hari ini.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
