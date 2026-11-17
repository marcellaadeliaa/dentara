<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? sanitize($title) . ' | ' : '' ?><?= APP_NAME ?> - Auth</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-gradient-to-br from-slate-900 via-teal-950 to-slate-900 min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-md">
        <!-- Logo Header -->
        <div class="text-center mb-8">
            <a href="<?= url() ?>" class="inline-flex items-center gap-3 group">
                <div class="w-12 h-12 rounded-2xl bg-teal-500 flex items-center justify-center text-white shadow-lg shadow-teal-500/30 group-hover:scale-105 transition">
                    <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C7.58 2 4 5.58 4 10c0 3.2 1.9 5.96 4.63 7.23.46.21.75.69.75 1.2V20c0 1.1.9 2 2 2h1.24c1.1 0 2-.9 2-2v-1.57c0-.51.29-.99.75-1.2C18.1 15.96 20 13.2 20 10c0-4.42-3.58-8-8-8zm-2 11c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm4 0c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1z"/>
                    </svg>
                </div>
                <div class="text-left">
                    <span class="text-3xl font-black text-white tracking-tight">DENT<span class="text-teal-400">ARA</span></span>
                    <span class="block text-[10px] font-bold text-teal-300 uppercase tracking-widest">Sistem Manajemen Klinik Gigi</span>
                </div>
            </a>
        </div>

        <!-- Card Container -->
        <div class="bg-white rounded-3xl shadow-2xl p-8 border border-slate-100">
            <!-- Flash Message inside Card -->
            <?php if ($successMsg = getFlash('success')): ?>
                <div class="mb-5 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs p-3 rounded-xl flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span><?= $successMsg ?></span>
                </div>
            <?php endif; ?>

            <?php if ($errorMsg = getFlash('error')): ?>
                <div class="mb-5 bg-rose-50 border border-rose-200 text-rose-800 text-xs p-3 rounded-xl flex items-center gap-2">
                    <svg class="w-4 h-4 text-rose-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span><?= $errorMsg ?></span>
                </div>
            <?php endif; ?>

            <?= $content ?>
        </div>

        <!-- Back to Website Link -->
        <div class="text-center mt-6">
            <a href="<?= url() ?>" class="text-xs text-teal-200/70 hover:text-white transition flex items-center justify-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Kembali ke Halaman Publik DENTARA
            </a>
        </div>
    </div>

</body>
</html>
