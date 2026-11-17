<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? sanitize($title) . ' | ' : '' ?><?= APP_NAME ?> - <?= APP_FULL_NAME ?></title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#ecfdf5',
                            100: '#d1fae5',
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                            800: '#065f46',
                            900: '#064e3b',
                        },
                        dentara: {
                            teal: '#0d9488',
                            cyan: '#0891b2',
                            dark: '#0f172a'
                        }
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex flex-col antialiased selection:bg-teal-500 selection:text-white">

    <!-- Top Announcement Bar -->
    <header class="bg-slate-900 text-slate-300 text-xs py-2 px-4 border-b border-slate-800">
        <div class="max-w-7xl mx-auto flex flex-wrap justify-between items-center gap-2">
            <div class="flex items-center gap-4">
                <span class="flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Buka: Sen - Sab 09:00 - 21:00 WIB
                </span>
                <span class="hidden sm:inline text-slate-600">|</span>
                <span class="hidden sm:flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    (021) 7890-1234
                </span>
            </div>
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center gap-1 text-emerald-400 font-medium">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                    Antrean Hari Ini Aktif
                </span>
            </div>
        </div>
    </header>

    <!-- Main Navigation Bar -->
    <nav class="bg-white/90 backdrop-blur-md sticky top-0 z-50 border-b border-slate-200/80 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-20 items-center">
                <!-- Brand Logo -->
                <a href="<?= url() ?>" class="flex items-center gap-3 group">
                    <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-teal-600 to-cyan-500 flex items-center justify-center text-white shadow-md shadow-teal-500/20 group-hover:scale-105 transition-transform">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2C7.58 2 4 5.58 4 10c0 3.2 1.9 5.96 4.63 7.23.46.21.75.69.75 1.2V20c0 1.1.9 2 2 2h1.24c1.1 0 2-.9 2-2v-1.57c0-.51.29-.99.75-1.2C18.1 15.96 20 13.2 20 10c0-4.42-3.58-8-8-8zm-2 11c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm4 0c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1z"/>
                        </svg>
                    </div>
                    <div>
                        <span class="text-2xl font-black tracking-tight text-slate-900 flex items-center gap-1">
                            DENT<span class="text-teal-600">ARA</span>
                        </span>
                        <span class="block text-[10px] font-bold tracking-wider text-slate-400 uppercase">Klinik Gigi Modern</span>
                    </div>
                </a>

                <!-- Desktop Menu -->
                <div class="hidden md:flex items-center gap-1 text-sm font-medium text-slate-600">
                    <a href="<?= url() ?>" class="px-3.5 py-2 rounded-lg hover:text-teal-600 hover:bg-teal-50/60 transition">Beranda</a>
                    <a href="<?= url('services') ?>" class="px-3.5 py-2 rounded-lg hover:text-teal-600 hover:bg-teal-50/60 transition">Layanan & Tarif</a>
                    <a href="<?= url('doctors') ?>" class="px-3.5 py-2 rounded-lg hover:text-teal-600 hover:bg-teal-50/60 transition">Dokter & Jadwal</a>
                    <a href="<?= url('queue') ?>" class="px-3.5 py-2 rounded-lg hover:text-teal-600 hover:bg-teal-50/60 transition flex items-center gap-1.5 text-teal-700 font-semibold bg-teal-50/80">
                        <span class="w-2 h-2 rounded-full bg-teal-500"></span>
                        Pantau Antrean
                    </a>
                    <a href="<?= url('faq') ?>" class="px-3.5 py-2 rounded-lg hover:text-teal-600 hover:bg-teal-50/60 transition">FAQ</a>
                </div>

                <!-- CTA Actions -->
                <div class="flex items-center gap-3">
                    <a href="<?= url('booking') ?>" class="hidden sm:inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white font-semibold text-sm shadow-sm shadow-teal-600/30 transition hover:-translate-y-0.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        Daftar Online
                    </a>

                    <?php if (\App\Core\Auth::check()): ?>
                        <a href="<?= \App\Core\Auth::getDashboardUrl() ?>" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-semibold text-sm transition">
                            <svg class="w-4 h-4 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                            Dashboard
                        </a>
                    <?php else: ?>
                        <a href="<?= url('login') ?>" class="inline-flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl border border-slate-300 hover:border-slate-400 text-slate-700 font-semibold text-sm hover:bg-slate-100 transition">
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                            Masuk
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Global Flash Alerts -->
    <?php if ($successMsg = getFlash('success')): ?>
        <div class="max-w-7xl mx-auto px-4 mt-4 w-full">
            <div class="bg-emerald-50 border-l-4 border-emerald-500 p-4 rounded-r-xl shadow-sm flex items-center justify-between text-emerald-800 text-sm">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span><?= $successMsg ?></span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-900 font-bold">&times;</button>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($errorMsg = getFlash('error')): ?>
        <div class="max-w-7xl mx-auto px-4 mt-4 w-full">
            <div class="bg-rose-50 border-l-4 border-rose-500 p-4 rounded-r-xl shadow-sm flex items-center justify-between text-rose-800 text-sm">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-rose-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span><?= $errorMsg ?></span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-rose-600 hover:text-rose-900 font-bold">&times;</button>
            </div>
        </div>
    <?php endif; ?>

    <!-- Main Content Yield -->
    <main class="flex-grow">
        <?= $content ?>
    </main>

    <!-- Footer -->
    <footer class="bg-slate-900 text-slate-400 text-sm border-t border-slate-800 mt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Col 1 -->
                <div class="space-y-4 md:col-span-1">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-teal-600 flex items-center justify-center text-white font-bold">D</div>
                        <span class="text-xl font-bold text-white tracking-tight">DENTARA</span>
                    </div>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Sistem Informasi Manajemen Klinik Gigi Modern yang terintegrasi, transparan, dan berorientasi pada kenyamanan pasien.
                    </p>
                </div>

                <!-- Col 2 -->
                <div>
                    <h4 class="text-white font-semibold mb-4 text-xs tracking-wider uppercase">Menu Layanan</h4>
                    <ul class="space-y-2 text-xs">
                        <li><a href="<?= url('services') ?>" class="hover:text-teal-400 transition">Daftar Tindakan Gigi</a></li>
                        <li><a href="<?= url('doctors') ?>" class="hover:text-teal-400 transition">Profil & Jadwal Dokter</a></li>
                        <li><a href="<?= url('booking') ?>" class="hover:text-teal-400 transition">Booking Online Mandiri</a></li>
                        <li><a href="<?= url('queue') ?>" class="hover:text-teal-400 transition">Live Queue Tracker</a></li>
                    </ul>
                </div>

                <!-- Col 3 -->
                <div>
                    <h4 class="text-white font-semibold mb-4 text-xs tracking-wider uppercase">Hak Akses Portal</h4>
                    <ul class="space-y-2 text-xs">
                        <li><a href="<?= url('login') ?>" class="hover:text-teal-400 transition">Portal Pasien</a></li>
                        <li><a href="<?= url('login') ?>" class="hover:text-teal-400 transition">Front Desk / Resepsionis</a></li>
                        <li><a href="<?= url('login') ?>" class="hover:text-teal-400 transition">EMR Dokter Gigi</a></li>
                        <li><a href="<?= url('login') ?>" class="hover:text-teal-400 transition">Apotek & Farmasi</a></li>
                        <li><a href="<?= url('login') ?>" class="hover:text-teal-400 transition">Kasir & Billing</a></li>
                        <li><a href="<?= url('login') ?>" class="hover:text-teal-400 transition">Dashboard Manajemen</a></li>
                    </ul>
                </div>

                <!-- Col 4 -->
                <div>
                    <h4 class="text-white font-semibold mb-4 text-xs tracking-wider uppercase">Kontak & Lokasi</h4>
                    <p class="text-xs text-slate-400 mb-2">Jl. Kesehatan Gigi No. 45, Kebayoran Baru, Jakarta Selatan</p>
                    <p class="text-xs text-slate-400 mb-2">Telp: (021) 7890-1234</p>
                    <p class="text-xs text-teal-400 font-medium">WhatsApp: 0812-3456-7890</p>
                </div>
            </div>

            <div class="border-t border-slate-800 mt-10 pt-6 flex flex-col sm:flex-row justify-between items-center text-xs text-slate-500 gap-4">
                <p>&copy; <?= date('Y') ?> DENTARA Dental Care. All rights reserved.</p>
                <p>Dikembangkan dengan PHP Native &bull; Laragon Local Environment</p>
            </div>
        </div>
    </footer>

</body>
</html>
