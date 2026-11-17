<div class="space-y-6">
    <div class="text-center">
        <h2 class="text-2xl font-bold text-slate-800">Registrasi Pasien</h2>
        <p class="text-xs text-slate-500 mt-1">Daftar akun untuk mengakses rekam medis, antrean, dan jadwal</p>
    </div>

    <form action="<?= url('register') ?>" method="POST" class="space-y-4">
        <?= csrf_field() ?>

        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap Pasien *</label>
            <input type="text" name="full_name" required placeholder="Contoh: Budi Santoso"
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 focus:bg-white transition">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">NIK (16 Digit KTP) *</label>
                <input type="text" name="nik" maxlength="16" required placeholder="3171xxxxxxxxxxxx"
                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 focus:bg-white transition">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Jenis Kelamin *</label>
                <select name="gender" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 focus:bg-white transition">
                    <option value="L">Laki-laki</option>
                    <option value="P">Perempuan</option>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Lahir *</label>
                <input type="date" name="birth_date" required
                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 focus:bg-white transition">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">No. WhatsApp / HP *</label>
                <input type="tel" name="phone" required placeholder="08xxxxxxxxxx"
                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 focus:bg-white transition">
            </div>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Alamat Lengkap *</label>
            <textarea name="address" rows="2" required placeholder="Nama jalan, nomor rumah, RT/RW, kelurahan, kecamatan"
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 focus:bg-white transition"></textarea>
        </div>

        <div class="border-t border-slate-100 pt-3">
            <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Akun Login Portal</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Username *</label>
                    <input type="text" name="username" required placeholder="username_anda"
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 focus:bg-white transition">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Email (Opsional)</label>
                    <input type="email" name="email" placeholder="email@contoh.com"
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 focus:bg-white transition">
                </div>
            </div>
            <div class="mt-3">
                <label class="block text-xs font-semibold text-slate-700 mb-1">Password *</label>
                <input type="password" name="password" required placeholder="Minimal 6 karakter"
                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 focus:bg-white transition">
            </div>
        </div>

        <button type="submit" class="w-full py-3 px-4 bg-teal-600 hover:bg-teal-700 text-white font-semibold rounded-xl text-sm shadow-md shadow-teal-600/30 transition hover:-translate-y-0.5">
            Daftar Sebagai Pasien
        </button>
    </form>

    <div class="border-t border-slate-100 pt-4 text-center">
        <p class="text-xs text-slate-500">
            Sudah memiliki akun portal?
            <a href="<?= url('login') ?>" class="font-semibold text-teal-600 hover:text-teal-700 ml-1">Masuk Sekarang</a>
        </p>
    </div>
</div>
