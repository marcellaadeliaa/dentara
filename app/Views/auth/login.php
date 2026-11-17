<div class="space-y-6">
    <div class="text-center">
        <h2 class="text-2xl font-bold text-slate-800">Masuk ke Akun</h2>
        <p class="text-xs text-slate-500 mt-1">Gunakan akun staf klinik atau portal pasien Anda</p>
    </div>

    <!-- Quick Role Login Demo Buttons -->
    <div class="bg-slate-50 border border-slate-200 p-3.5 rounded-2xl">
        <div class="flex items-center justify-between mb-2">
            <span class="text-[11px] font-bold text-slate-600 uppercase tracking-wider flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 text-teal-600" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a8 8 0 100 16 8 8 0 000-16zm0 14a6 6 0 110-12 6 6 0 010 12z"/></svg>
                Pilih Akun Demo / Uji Coba:
            </span>
            <span class="text-[10px] text-teal-600 font-semibold bg-teal-50 px-2 py-0.5 rounded border border-teal-200">1-Klik Isi</span>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-1.5 text-[11px]">
            <button type="button" onclick="fillForm('admin', 'password123')" class="px-2.5 py-1.5 bg-white hover:bg-teal-50 hover:border-teal-400 border border-slate-200 rounded-lg text-slate-700 text-left transition flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-indigo-500"></span> Admin / Owner
            </button>
            <button type="button" onclick="fillForm('drg_sarah', 'password123')" class="px-2.5 py-1.5 bg-white hover:bg-teal-50 hover:border-teal-400 border border-slate-200 rounded-lg text-slate-700 text-left transition flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-teal-500"></span> drg. Sarah (Sp.KG)
            </button>
            <button type="button" onclick="fillForm('drg_fajar', 'password123')" class="px-2.5 py-1.5 bg-white hover:bg-teal-50 hover:border-teal-400 border border-slate-200 rounded-lg text-slate-700 text-left transition flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-cyan-500"></span> drg. Fajar (Umum)
            </button>
            <button type="button" onclick="fillForm('resepsionis', 'password123')" class="px-2.5 py-1.5 bg-white hover:bg-teal-50 hover:border-teal-400 border border-slate-200 rounded-lg text-slate-700 text-left transition flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-sky-500"></span> Resepsionis
            </button>
            <button type="button" onclick="fillForm('apoteker', 'password123')" class="px-2.5 py-1.5 bg-white hover:bg-teal-50 hover:border-teal-400 border border-slate-200 rounded-lg text-slate-700 text-left transition flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-purple-500"></span> Apoteker
            </button>
            <button type="button" onclick="fillForm('kasir', 'password123')" class="px-2.5 py-1.5 bg-white hover:bg-teal-50 hover:border-teal-400 border border-slate-200 rounded-lg text-slate-700 text-left transition flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Kasir
            </button>
            <button type="button" onclick="fillForm('budi_santoso', 'password123')" class="col-span-2 sm:col-span-3 px-2.5 py-1.5 bg-amber-50/60 hover:bg-amber-100 hover:border-amber-400 border border-amber-200 rounded-lg text-amber-900 text-left transition flex items-center justify-between">
                <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-amber-500"></span> Pasien Portal (Budi Santoso)</span>
                <span class="text-[10px] text-amber-600 font-semibold">RM-202611-0001</span>
            </button>
        </div>
    </div>

    <!-- Login Form -->
    <form action="<?= url('login') ?>" method="POST" class="space-y-4">
        <?= csrf_field() ?>

        <div>
            <label for="username" class="block text-xs font-semibold text-slate-700 mb-1.5">Username atau Email</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </div>
                <input type="text" id="username" name="username" required placeholder="Masukkan username Anda"
                    class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:bg-white transition">
            </div>
        </div>

        <div>
            <div class="flex items-center justify-between mb-1.5">
                <label for="password" class="block text-xs font-semibold text-slate-700">Password</label>
            </div>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                </div>
                <input type="password" id="password" name="password" required placeholder="••••••••"
                    class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:bg-white transition">
            </div>
        </div>

        <button type="submit" class="w-full py-3 px-4 bg-teal-600 hover:bg-teal-700 text-white font-semibold rounded-xl text-sm shadow-md shadow-teal-600/30 transition hover:-translate-y-0.5">
            Masuk ke Sistem
        </button>
    </form>

    <div class="border-t border-slate-100 pt-4 text-center">
        <p class="text-xs text-slate-500">
            Pasien baru dan belum memiliki akun portal?
            <a href="<?= url('register') ?>" class="font-semibold text-teal-600 hover:text-teal-700 ml-1">Daftar Akun Pasien</a>
        </p>
    </div>
</div>

<script>
function fillForm(user, pass) {
    document.getElementById('username').value = user;
    document.getElementById('password').value = pass;
}
</script>
