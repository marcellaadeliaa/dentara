<div class="py-16 bg-slate-50 min-h-[70vh] flex items-center justify-center">
    <div class="max-w-lg w-full mx-auto px-4">
        <div class="bg-white rounded-3xl shadow-xl shadow-slate-200/50 p-8 border border-slate-100 text-center">
            
            <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-6 shadow-inner">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
            </div>

            <span class="text-xs font-bold text-emerald-600 tracking-wider uppercase">Pendaftaran Berhasil</span>
            <h1 class="text-2xl font-black text-slate-900 mt-1">Reservasi Anda Terkonfirmasi</h1>
            <p class="text-xs text-slate-500 mt-2">Simpan atau tangkap layar (screenshot) kode reservasi berikut:</p>

            <!-- Kode Booking Box -->
            <div class="my-6 p-6 bg-gradient-to-r from-teal-50 to-cyan-50 rounded-2xl border-2 border-dashed border-teal-300">
                <span class="text-[11px] font-bold text-teal-800 uppercase tracking-widest block">Kode Reservasi Pasien</span>
                <span class="text-3xl font-black text-teal-700 tracking-wider block mt-1 select-all"><?= $bookingCode ?></span>
            </div>

            <!-- Detail Info -->
            <div class="bg-slate-50 rounded-2xl p-4 text-xs text-left space-y-2 border border-slate-200/80 mb-6">
                <div class="flex justify-between py-1 border-b border-slate-200/60">
                    <span class="text-slate-500">Nama Pasien:</span>
                    <span class="font-bold text-slate-800"><?= sanitize($patientName) ?></span>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-200/60">
                    <span class="text-slate-500">Dokter Gigi:</span>
                    <span class="font-bold text-slate-800"><?= sanitize($doctorName) ?></span>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-200/60">
                    <span class="text-slate-500">Tanggal Pemeriksaan:</span>
                    <span class="font-bold text-teal-700"><?= formatDateIndo($appointmentDate) ?></span>
                </div>
                <div class="flex justify-between py-1">
                    <span class="text-slate-500">Jam Operasional Dokter:</span>
                    <span class="font-semibold text-slate-800"><?= $estimatedTime ?></span>
                </div>
            </div>

            <!-- Instruction -->
            <div class="p-3 bg-blue-50 text-blue-800 rounded-xl text-[11px] text-left flex items-start gap-2.5 mb-6">
                <svg class="w-4 h-4 text-blue-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Tunjukkan kode reservasi ini kepada resepsionis saat tiba di klinik untuk melakukan check-in dan penerbitan nomor antrean fisik.</span>
            </div>

            <div class="flex flex-col sm:flex-row gap-3">
                <a href="<?= url('queue') ?>" class="flex-1 py-3 px-4 bg-teal-600 hover:bg-teal-700 text-white font-bold rounded-xl text-xs transition shadow-sm">
                    Pantau Antrean Hari Ini
                </a>
                <a href="<?= url() ?>" class="flex-1 py-3 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-xs transition">
                    Kembali ke Beranda
                </a>
            </div>

        </div>
    </div>
</div>
