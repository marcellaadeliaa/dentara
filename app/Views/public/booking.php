<div class="py-12 bg-slate-50 border-b border-slate-200">
    <div class="max-w-3xl mx-auto px-4 sm:px-6">

        <div class="text-center mb-8">
            <span class="text-xs font-bold text-teal-600 tracking-wider uppercase">Reservasi Mudah & Cepat</span>
            <h1 class="text-3xl font-extrabold text-slate-900 mt-1">Pendaftaran Online Mandiri</h1>
            <p class="text-sm text-slate-500 mt-2">Pilih dokter gigi dan tanggal kunjungan sesuai kenyamanan Anda.</p>
        </div>

        <div class="bg-white rounded-3xl shadow-xl shadow-slate-200/50 p-6 sm:p-10 border border-slate-100">
            <form action="<?= url('booking') ?>" method="POST" class="space-y-6">
                <?= csrf_field() ?>

                <!-- Pilih Dokter -->
                <div>
                    <label class="block text-xs font-bold text-slate-800 uppercase tracking-wider mb-2">1. Pilih Dokter Gigi *</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <?php foreach ($doctors as $idx => $doc): ?>
                            <label class="relative flex flex-col p-4 bg-slate-50 border-2 border-slate-200 rounded-2xl cursor-pointer hover:border-teal-500 transition has-[:checked]:border-teal-600 has-[:checked]:bg-teal-50/50">
                                <input type="radio" name="doctor_id" value="<?= $doc['id'] ?>" class="sr-only" <?= $idx === 0 ? 'checked' : '' ?> required>
                                <span class="font-bold text-sm text-slate-900"><?= sanitize($doc['full_name']) ?></span>
                                <span class="text-xs text-teal-700 font-medium mt-0.5"><?= sanitize($doc['specialization']) ?></span>
                                <span class="text-[11px] text-slate-400 mt-2"><?= sanitize($doc['room_name']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Tanggal Kunjungan -->
                <div>
                    <label class="block text-xs font-bold text-slate-800 uppercase tracking-wider mb-2">2. Tanggal Rencana Kunjungan *</label>
                    <input type="date" name="appointment_date" min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>" required
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-sm font-medium focus:outline-none focus:ring-2 focus:ring-teal-500 focus:bg-white transition">
                    <p class="text-[11px] text-slate-400 mt-1.5">* Jadwal praktik: Senin - Sabtu (09:00 - 21:00 WIB)</p>
                </div>

                <!-- Status Pasien Baru / Lama -->
                <div>
                    <label class="block text-xs font-bold text-slate-800 uppercase tracking-wider mb-2">3. Status Pasien di DENTARA *</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="flex items-center gap-3 p-3 bg-slate-50 border border-slate-200 rounded-xl cursor-pointer has-[:checked]:border-teal-600 has-[:checked]:bg-teal-50">
                            <input type="radio" name="is_new_patient" value="0" checked class="text-teal-600 focus:ring-teal-500">
                            <div>
                                <div class="text-xs font-bold text-slate-800">Pasien Lama</div>
                                <div class="text-[10px] text-slate-500">Sudah pernah periksa</div>
                            </div>
                        </label>
                        <label class="flex items-center gap-3 p-3 bg-slate-50 border border-slate-200 rounded-xl cursor-pointer has-[:checked]:border-teal-600 has-[:checked]:bg-teal-50">
                            <input type="radio" name="is_new_patient" value="1" class="text-teal-600 focus:ring-teal-500">
                            <div>
                                <div class="text-xs font-bold text-slate-800">Pasien Baru</div>
                                <div class="text-[10px] text-slate-500">Kunjungan perdana</div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Identitas Pasien -->
                <div class="space-y-4 pt-2 border-t border-slate-100">
                    <label class="block text-xs font-bold text-slate-800 uppercase tracking-wider">4. Data Kontak Pasien</label>
                    
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Pasien *</label>
                        <input type="text" name="patient_name" value="<?= sanitize($patientData['full_name'] ?? '') ?>" required placeholder="Nama lengkap yang akan diperiksa"
                            class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 focus:bg-white transition">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor WhatsApp / HP Aktif *</label>
                            <input type="tel" name="patient_phone" value="<?= sanitize($patientData['phone'] ?? '') ?>" required placeholder="08xxxxxxxxxx"
                                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 focus:bg-white transition">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Email (Untuk Bukti Booking)</label>
                            <input type="email" name="patient_email" value="<?= sanitize($patientData['email'] ?? '') ?>" placeholder="email@contoh.com"
                                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 focus:bg-white transition">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Keluhan / Alasan Kunjungan *</label>
                        <textarea name="complaint" rows="3" required placeholder="Contoh: Gigi belakang berlubang dan nyeri saat minum dingin sejak kemarin..."
                            class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 focus:bg-white transition"></textarea>
                    </div>
                </div>

                <div class="pt-4">
                    <button type="submit" class="w-full py-4 bg-teal-600 hover:bg-teal-700 text-white font-bold rounded-2xl text-sm shadow-xl shadow-teal-600/30 transition hover:-translate-y-0.5">
                        Konfirmasi Pendaftaran & Dapatkan Kode Booking
                    </button>
                    <p class="text-center text-xs text-slate-400 mt-3">
                        Dengan mendaftar, data Anda terlindungi dalam sistem rekam medis elektronik DENTARA.
                    </p>
                </div>
            </form>
        </div>

    </div>
</div>
