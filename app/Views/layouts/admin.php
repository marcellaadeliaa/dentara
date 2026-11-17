<?php
use App\Core\Auth;
$currentUser = Auth::user();
$currentRole = Auth::role();

// Label & warna role
$roleBadges = [
    ROLE_ADMIN        => ['label' => 'Administrator', 'bg' => 'bg-indigo-50 text-indigo-700 border-indigo-200'],
    ROLE_DOCTOR       => ['label' => 'Dokter Gigi', 'bg' => 'bg-teal-50 text-teal-700 border-teal-200'],
    ROLE_RECEPTIONIST => ['label' => 'Front Desk / Resepsionis', 'bg' => 'bg-sky-50 text-sky-700 border-sky-200'],
    ROLE_PHARMACIST   => ['label' => 'Apoteker / Farmasi', 'bg' => 'bg-purple-50 text-purple-700 border-purple-200'],
    ROLE_CASHIER      => ['label' => 'Kasir / Billing', 'bg' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
    ROLE_PATIENT      => ['label' => 'Pasien Terdaftar', 'bg' => 'bg-amber-50 text-amber-700 border-amber-200'],
];

$roleBadge = $roleBadges[$currentRole] ?? ['label' => ucfirst($currentRole), 'bg' => 'bg-gray-50 text-gray-700 border-gray-200'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? sanitize($title) . ' | ' : '' ?><?= APP_NAME ?> Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased flex h-screen overflow-hidden">

    <!-- SIDEBAR -->
    <aside class="w-64 bg-slate-900 text-slate-300 flex flex-col flex-shrink-0 z-30 transition-all border-r border-slate-800">
        <!-- Brand -->
        <div class="h-20 flex items-center px-6 gap-3 border-b border-slate-800 bg-slate-950">
            <div class="w-10 h-10 rounded-xl bg-teal-500 flex items-center justify-center text-white font-bold shadow-md shadow-teal-500/20">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2C7.58 2 4 5.58 4 10c0 3.2 1.9 5.96 4.63 7.23.46.21.75.69.75 1.2V20c0 1.1.9 2 2 2h1.24c1.1 0 2-.9 2-2v-1.57c0-.51.29-.99.75-1.2C18.1 15.96 20 13.2 20 10c0-4.42-3.58-8-8-8zm-2 11c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm4 0c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1z"/>
                </svg>
            </div>
            <div>
                <span class="text-xl font-black text-white tracking-tight">DENT<span class="text-teal-400">ARA</span></span>
                <span class="block text-[9px] font-bold text-slate-400 uppercase tracking-widest">Internal System</span>
            </div>
        </div>

        <!-- Navigation Links -->
        <nav class="flex-1 overflow-y-auto p-4 space-y-1.5 text-xs font-medium">

            <!-- 1. ADMIN MENU -->
            <?php if ($currentRole === ROLE_ADMIN): ?>
                <div class="px-3 pt-2 pb-1 text-[10px] font-bold uppercase tracking-wider text-slate-500">Manajemen & Laporan</div>
                <a href="<?= url('admin/dashboard') ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-slate-800 hover:text-white transition <?= strpos($_SERVER['REQUEST_URI'] ?? '', 'admin/dashboard') !== false ? 'bg-teal-600 text-white font-semibold' : '' ?>">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    Ringkasan Eksekutif
                </a>
                <a href="<?= url('admin/reports/revenue') ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-slate-800 hover:text-white transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Laporan Pendapatan
                </a>
                <a href="<?= url('admin/doctor-fees') ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-slate-800 hover:text-white transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    Fee Sharing Dokter
                </a>

                <div class="px-3 pt-4 pb-1 text-[10px] font-bold uppercase tracking-wider text-slate-500">Master Data</div>
                <a href="<?= url('admin/doctors') ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-slate-800 hover:text-white transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    Dokter & Jadwal
                </a>
                <a href="<?= url('admin/treatments') ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-slate-800 hover:text-white transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                    Tindakan & Tarif
                </a>
                <a href="<?= url('admin/medicines') ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-slate-800 hover:text-white transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    Obat & Bahan Medis
                </a>
                <a href="<?= url('admin/users') ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-slate-800 hover:text-white transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    Pengguna & Hak Akses
                </a>
            <?php endif; ?>

            <!-- 2. FRONT DESK (RESEPSIONIS) -->
            <?php if ($currentRole === ROLE_RECEPTIONIST || $currentRole === ROLE_ADMIN): ?>
                <?php if ($currentRole !== ROLE_ADMIN): ?>
                    <div class="px-3 pt-2 pb-1 text-[10px] font-bold uppercase tracking-wider text-slate-500">Front Desk</div>
                <?php endif; ?>
                <a href="<?= url('receptionist/dashboard') ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-slate-800 hover:text-white transition <?= strpos($_SERVER['REQUEST_URI'] ?? '', 'receptionist/dashboard') !== false ? 'bg-teal-600 text-white font-semibold' : '' ?>">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    Antrean Hari Ini
                </a>
                <a href="<?= url('receptionist/patients') ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-slate-800 hover:text-white transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    Data Pasien & No. RM
                </a>
                <a href="<?= url('receptionist/bookings') ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-slate-800 hover:text-white transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    Reservasi Online
                </a>
            <?php endif; ?>

            <!-- 3. EMR DOKTER -->
            <?php if ($currentRole === ROLE_DOCTOR || $currentRole === ROLE_ADMIN): ?>
                <?php if ($currentRole !== ROLE_ADMIN): ?>
                    <div class="px-3 pt-2 pb-1 text-[10px] font-bold uppercase tracking-wider text-slate-500">Pelayanan Medis</div>
                <?php endif; ?>
                <a href="<?= url('doctor/dashboard') ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-slate-800 hover:text-white transition <?= strpos($_SERVER['REQUEST_URI'] ?? '', 'doctor/dashboard') !== false ? 'bg-teal-600 text-white font-semibold' : '' ?>">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                    Antrean Pasien Dokter
                </a>
                <a href="<?= url('doctor/history') ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-slate-800 hover:text-white transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    Riwayat Rekam Medis (EMR)
                </a>
            <?php endif; ?>

            <!-- 4. APOTEK -->
            <?php if ($currentRole === ROLE_PHARMACIST || $currentRole === ROLE_ADMIN): ?>
                <?php if ($currentRole !== ROLE_ADMIN): ?>
                    <div class="px-3 pt-2 pb-1 text-[10px] font-bold uppercase tracking-wider text-slate-500">Farmasi & Resep</div>
                <?php endif; ?>
                <a href="<?= url('pharmacy/dashboard') ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-slate-800 hover:text-white transition <?= strpos($_SERVER['REQUEST_URI'] ?? '', 'pharmacy/dashboard') !== false ? 'bg-teal-600 text-white font-semibold' : '' ?>">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                    Antrean Resep Masuk
                </a>
                <a href="<?= url('pharmacy/stock') ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-slate-800 hover:text-white transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    Stok Obat & Kedaluwarsa
                </a>
            <?php endif; ?>

            <!-- 5. KASIR -->
            <?php if ($currentRole === ROLE_CASHIER || $currentRole === ROLE_ADMIN): ?>
                <?php if ($currentRole !== ROLE_ADMIN): ?>
                    <div class="px-3 pt-2 pb-1 text-[10px] font-bold uppercase tracking-wider text-slate-500">Kasir & Pembayaran</div>
                <?php endif; ?>
                <a href="<?= url('cashier/dashboard') ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-slate-800 hover:text-white transition <?= strpos($_SERVER['REQUEST_URI'] ?? '', 'cashier/dashboard') !== false ? 'bg-teal-600 text-white font-semibold' : '' ?>">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    Tagihan Pasien (Auto-Billing)
                </a>
                <a href="<?= url('cashier/transactions') ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-slate-800 hover:text-white transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                    Riwayat Kuitansi Lunas
                </a>
            <?php endif; ?>

            <!-- 6. PORTAL PASIEN -->
            <?php if ($currentRole === ROLE_PATIENT): ?>
                <div class="px-3 pt-2 pb-1 text-[10px] font-bold uppercase tracking-wider text-slate-500">Portal Pasien</div>
                <a href="<?= url('patient/portal') ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-slate-800 hover:text-white transition <?= strpos($_SERVER['REQUEST_URI'] ?? '', 'patient/portal') !== false ? 'bg-teal-600 text-white font-semibold' : '' ?>">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    Ringkasan & Profil
                </a>
                <a href="<?= url('patient/appointments') ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-slate-800 hover:text-white transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    Jadwal & Booking Saya
                </a>
                <a href="<?= url('patient/medical-records') ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-slate-800 hover:text-white transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Riwayat Rekam Medis & Resep
                </a>
                <a href="<?= url('patient/invoices') ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-slate-800 hover:text-white transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z"/></svg>
                    Tagihan & Kuitansi
                </a>
            <?php endif; ?>

            <!-- Link Balik ke Web Publik -->
            <div class="pt-6 border-t border-slate-800/80 mt-4">
                <a href="<?= url() ?>" target="_blank" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    Buka Website Publik
                </a>
            </div>
        </nav>

        <!-- Current User Info & Quick Logout -->
        <div class="p-4 border-t border-slate-800 bg-slate-950 flex items-center justify-between">
            <div class="flex items-center gap-3 truncate">
                <div class="w-8 h-8 rounded-full bg-teal-600/30 text-teal-300 border border-teal-500/30 flex items-center justify-center font-bold text-xs uppercase flex-shrink-0">
                    <?= substr($currentUser['full_name'] ?? 'U', 0, 1) ?>
                </div>
                <div class="truncate">
                    <div class="text-xs font-semibold text-white truncate"><?= sanitize($currentUser['full_name']) ?></div>
                    <div class="text-[10px] text-teal-400 font-medium"><?= $roleBadge['label'] ?></div>
                </div>
            </div>
            <a href="<?= url('logout') ?>" title="Keluar dari Sistem" class="p-1.5 text-slate-400 hover:text-rose-400 hover:bg-rose-950/40 rounded-lg transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
            </a>
        </div>
    </aside>

    <!-- MAIN BODY -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Top App Bar -->
        <header class="h-20 bg-white border-b border-slate-200 px-8 flex items-center justify-between flex-shrink-0">
            <div>
                <h1 class="text-lg font-bold text-slate-900"><?= $title ?? 'Dashboard' ?></h1>
                <p class="text-xs text-slate-500">Sistem Informasi Manajemen Klinik Gigi DENTARA</p>
            </div>

            <div class="flex items-center gap-4">
                <!-- Role Tag -->
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold border <?= $roleBadge['bg'] ?>">
                    <?= $roleBadge['label'] ?>
                </span>

                <!-- Date Clock -->
                <div class="hidden sm:flex items-center gap-2 text-xs font-medium text-slate-500 bg-slate-100 px-3 py-1.5 rounded-lg">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span><?= formatDateIndo(date('Y-m-d')) ?></span>
                </div>
            </div>
        </header>

        <!-- Dynamic Flash Alert -->
        <?php if ($successMsg = getFlash('success')): ?>
            <div class="px-8 pt-4">
                <div class="bg-emerald-50 border-l-4 border-emerald-500 p-4 rounded-r-xl shadow-sm flex items-center justify-between text-emerald-800 text-xs">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span><?= $successMsg ?></span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-emerald-700 font-bold">&times;</button>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($errorMsg = getFlash('error')): ?>
            <div class="px-8 pt-4">
                <div class="bg-rose-50 border-l-4 border-rose-500 p-4 rounded-r-xl shadow-sm flex items-center justify-between text-rose-800 text-xs">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-rose-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span><?= $errorMsg ?></span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-rose-700 font-bold">&times;</button>
                </div>
            </div>
        <?php endif; ?>

        <!-- Scrollable Dashboard Content -->
        <main class="flex-1 overflow-y-auto p-8 bg-slate-50">
            <?= $content ?>
        </main>
    </div>

</body>
</html>
