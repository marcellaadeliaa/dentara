<div class="py-12 bg-slate-50 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="text-center max-w-3xl mx-auto mb-12">
            <span class="text-xs font-bold text-teal-600 tracking-wider uppercase">Jadwal & Profil Medis</span>
            <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-900 mt-1">Dokter Gigi & Jadwal Praktik</h1>
            <p class="text-sm text-slate-500 mt-2">
                DENTARA didukung oleh dokter gigi umum dan spesialis berlisensi resmi dengan jadwal teratur setiap minggunya.
            </p>
        </div>

        <!-- Doctor Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-16">
            <?php foreach ($doctors as $doc): ?>
                <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="flex items-center gap-4 mb-4">
                            <div class="w-16 h-16 rounded-2xl bg-teal-100 text-teal-700 flex items-center justify-center font-black text-2xl shadow-inner">
                                <?= substr($doc['full_name'], 0, 1) ?>
                            </div>
                            <div>
                                <span class="inline-block text-[10px] font-bold text-teal-700 bg-teal-50 px-2 py-0.5 rounded border border-teal-200">
                                    <?= sanitize($doc['specialization']) ?>
                                </span>
                                <h3 class="text-lg font-bold text-slate-900 mt-1"><?= sanitize($doc['full_name']) ?></h3>
                                <p class="text-xs text-slate-400 font-mono"><?= sanitize($doc['sip_number']) ?></p>
                            </div>
                        </div>

                        <p class="text-xs text-slate-600 leading-relaxed mb-4"><?= sanitize($doc['bio']) ?></p>

                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 text-xs flex justify-between items-center">
                            <span class="text-slate-500">Ruangan Praktik:</span>
                            <span class="font-bold text-teal-800"><?= sanitize($doc['room_name']) ?></span>
                        </div>
                    </div>

                    <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] text-slate-400 block font-semibold">Biaya Konsultasi</span>
                            <span class="text-base font-black text-slate-900"><?= formatRupiah($doc['consultation_fee']) ?></span>
                        </div>
                        <a href="<?= url('booking') ?>" class="px-5 py-2.5 bg-teal-600 hover:bg-teal-700 text-white font-bold rounded-xl text-xs transition shadow-sm">
                            Pilih Dokter Ini
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Comprehensive Schedule Table -->
        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-sm">
            <div class="flex items-center gap-3 pb-4 mb-6 border-b border-slate-100">
                <div class="w-10 h-10 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center font-bold">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Jadwal Lengkap Praktik Mingguan</h2>
                    <p class="text-xs text-slate-400">Pendaftaran online dibuka hingga H-1 sebelum sesi praktik</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left">
                    <thead class="bg-slate-50 text-slate-500 uppercase font-bold tracking-wider border-y border-slate-200/80">
                        <tr>
                            <th class="py-3 px-4">Hari</th>
                            <th class="py-3 px-4">Nama Dokter</th>
                            <th class="py-3 px-4">Jam Praktik</th>
                            <th class="py-3 px-4">Kuota Pasien</th>
                            <th class="py-3 px-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($schedules as $sch): ?>
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="py-3.5 px-4 font-bold text-slate-800">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-teal-50 text-teal-700 border border-teal-200">
                                        <?= sanitize($sch['day_of_week']) ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 font-semibold text-slate-900"><?= sanitize($sch['doctor_name']) ?></td>
                                <td class="py-3.5 px-4 text-slate-600 font-mono">
                                    <?= substr($sch['start_time'], 0, 5) ?> - <?= substr($sch['end_time'], 0, 5) ?> WIB
                                </td>
                                <td class="py-3.5 px-4 text-slate-600"><?= $sch['quota'] ?> Pasien / Sesi</td>
                                <td class="py-3.5 px-4 text-center">
                                    <a href="<?= url('booking') ?>" class="inline-flex items-center gap-1 text-teal-600 hover:text-teal-800 font-bold">
                                        Booking
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
