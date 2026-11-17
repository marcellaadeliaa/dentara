<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Akses Ditolak | DENTARA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full text-center bg-white p-8 rounded-2xl shadow-xl border border-rose-100">
        <div class="w-20 h-20 bg-rose-50 text-rose-500 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m0 0v2m0-2h2m-2 0H10m4-11a4 4 0 00-8 0v4h8V6z" />
            </svg>
        </div>
        <h1 class="text-6xl font-extrabold text-slate-800 mb-2">403</h1>
        <h2 class="text-xl font-bold text-slate-700 mb-2">Akses Terbatas</h2>
        <p class="text-slate-500 mb-8 text-sm">Akun Anda tidak memiliki izin hak akses (role) untuk membuka modul ini. Silakan hubungi Administrator sistem jika ini adalah kekeliruan.</p>
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <a href="<?= \App\Core\Auth::getDashboardUrl() ?>" class="px-5 py-2.5 bg-teal-600 hover:bg-teal-700 text-white font-medium rounded-xl transition shadow-sm text-sm">
                Ke Dashboard Saya
            </a>
            <a href="<?= url('logout') ?>" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium rounded-xl transition text-sm">
                Ganti Akun
            </a>
        </div>
    </div>
</body>
</html>
