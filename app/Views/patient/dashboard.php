<div class="space-y-8">

    <?php if ($patient): ?>
        <!-- DIGITAL PATIENT CARD -->
        <div class="bg-gradient-to-br from-teal-900 via-teal-800 to-slate-900 text-white rounded-3xl p-6 sm:p-8 shadow-xl relative overflow-hidden">
            <div class="absolute -right-10 -bottom-10 w-48 h-48 bg-teal-500/10 rounded-full blur-2xl pointer-events-none"></div>

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-teal-700/60 relative z-10">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-teal-500/20 border border-teal-400/30 text-teal-300 flex items-center justify-center font-black text-2xl">
                        <?= substr($patient['full_name'], 0, 1) ?>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold text-teal-300 uppercase tracking-widest block">Kartu Pasien Digital</span>
                        <h2 class="text-2xl font-black text-white"><?= sanitize($patient['full_name']) ?></h2>
                    </div>
                </div>

                <div class="text-left sm:text-right">
                    <span class="text-[10px] text-teal-300 font-bold uppercase tracking-widest block">Nomor Rekam Medis</span>
                    <span class="text-2xl font-mono font-black text-teal-200 tracking-wider"><?= sanitize($patient['medical_record_number']) ?></span>
                </div>
            </div>

            <!-- Details -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-6 text-xs relative z-10">
                <div>
                    <span class="text-teal-300/80 block">NIK:</span>
                    <span class="font-mono font-semibold text-white"><?= sanitize($patient['nik']) ?></span>
                </div>
                <div>
                    <span class="text-teal-300/80 block">Golongan Darah:</span>
                    <span class="font-semibold text-white"><?= sanitize($patient['blood_type']) ?></span>
                </div>
                <div>
                    <span class="text-teal-300/80 block">Tanggal Lahir:</span>
                    <span class="font-semibold text-white"><?= formatDateIndo($patient['birth_date']) ?></span>
                </div>
                <div>
                    <span class="text-teal-300/80 block">Kontak WhatsApp:</span>
                    <span class="font-semibold text-white"><?= sanitize($patient['phone']) ?></span>
                </div>
            </div>

            <!-- Alergi alert -->
            <?php if (!empty($patient['allergies']) && $patient['allergies'] !== 'Tidak Ada'): ?>
                <div class="mt-6 p-3 bg-rose-950/60 border border-rose-500/40 rounded-xl text-xs flex items-center gap-2 text-rose-200">
                    <svg class="w-4 h-4 text-rose-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span><strong>Alergi Obat:</strong> <?= sanitize($patient['allergies']) ?></span>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- 2 Column: Upcoming Bookings & Past Records -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        
        <!-- Reservasi & Jadwal Pasien -->
        <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">
                    <h3 class="text-base font-bold text-slate-900">Jadwal & Reservasi Pemeriksaan</h3>
                    <a href="<?= url('booking') ?>" class="text-xs font-bold text-teal-600 hover:text-teal-700 flex items-center gap-1">
                        + Buat Janji Baru
                    </a>
                </div>

                <div class="space-y-3">
                    <?php if (!empty($appointments)): ?>
                        <?php foreach ($appointments as $apt): ?>
                            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 flex items-center justify-between text-xs">
                                <div>
                                    <span class="font-mono font-bold text-teal-700 text-sm"><?= sanitize($apt['booking_code']) ?></span>
                                    <div class="font-semibold text-slate-800 mt-0.5"><?= sanitize($apt['doctor_name']) ?> (<?= sanitize($apt['room_name']) ?>)</div>
                                    <div class="text-[11px] text-slate-500 mt-1"><?= formatDateIndo($apt['appointment_date']) ?></div>
                                </div>
                                <div class="text-right">
                                    <span class="inline-block px-2.5 py-1 rounded-full text-[10px] font-bold uppercase <?= $apt['status'] === 'confirmed' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-700' ?>">
                                        <?= $apt['status'] ?>
                                    </span>
                                    <a href="<?= url('queue') ?>" class="block text-[11px] font-bold text-teal-600 hover:underline mt-2">Pantau Antrean</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="py-8 text-center text-xs text-slate-400">
                            Anda belum memiliki riwayat reservasi pemeriksaan.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 mt-4">
                <a href="<?= url('booking') ?>" class="w-full py-2.5 bg-teal-600 hover:bg-teal-700 text-white font-bold rounded-xl text-xs flex items-center justify-center gap-2 transition">
                    Daftar Jadwal Periksa Gigi Baru
                </a>
            </div>
        </div>

        <!-- Riwayat Rekam Medis (EMR Ringkasan) -->
        <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">
                    <h3 class="text-base font-bold text-slate-900">Riwayat Rekam Medis Terakhir</h3>
                    <span class="text-xs text-slate-400">Ringkasan Klinis</span>
                </div>

                <div class="space-y-3">
                    <?php if (!empty($medicalRecords)): ?>
                        <?php foreach ($medicalRecords as $mr): ?>
                            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 text-xs space-y-1.5">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-slate-900"><?= formatDateIndo($mr['record_date']) ?></span>
                                    <span class="text-[10px] text-teal-700 font-medium"><?= sanitize($mr['doctor_name']) ?></span>
                                </div>
                                <div class="text-slate-600">
                                    <strong>Diagnosis:</strong> <?= sanitize($mr['assessment']) ?>
                                </div>
                                <div class="text-[11px] text-slate-500">
                                    <strong>Rencana / Anjuran:</strong> <?= sanitize($mr['plan']) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="py-8 text-center text-xs text-slate-400">
                            Belum ada catatan rekam medis tersimpan.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 mt-4">
                <span class="text-xs text-slate-400">Catatan rekam medis dilindungi kerahasiaan medis.</span>
            </div>
        </div>

    </div>

</div>
