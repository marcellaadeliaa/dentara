<?php
/**
 * DENTARA - Helper Functions & Utilities
 * File: includes/helpers.php
 */

if (session_status() === PHP_SESSION_NONE) {
    // Pengaturan session aman
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

/**
 * Dapatkan base URL sistem secara dinamis
 */
function base_url(string $path = ''): string {
    static $base = null;
    if ($base === null) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        
        // Cari posisi folder dentara
        if (php_sapi_name() === 'cli') {
            $dir = '/dentara';
        } else {
            $dir = str_replace('\\', '/', dirname($script));
            // Bersihkan seluruh subfolder (termasuk subfolder bersarang seperti /includes/process)
            $subfolders = ['/admin', '/pasien', '/resepsionis', '/dokter', '/apoteker', '/kasir', '/tv', '/api', '/includes', '/process'];
            $changed = true;
            while ($changed) {
                $changed = false;
                foreach ($subfolders as $sub) {
                    if (str_ends_with($dir, $sub)) {
                        $dir = substr($dir, 0, -strlen($sub));
                        $changed = true;
                        break;
                    }
                }
            }
            if ($dir === '.' || $dir === '/') {
                $dir = '';
            }
        }
        $base = rtrim($protocol . $host . $dir, '/');
    }
    return $base . ($path ? '/' . ltrim($path, '/') : '');
}

/**
 * XSS Protection shortcut
 */
function e(?string $str): string {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * CSRF Token Generator
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * CSRF Hidden Input Field HTML
 */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Validasi CSRF Token dari POST request
 */
function verify_csrf(): bool {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return false;
    }
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return !empty($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Format Angka ke Rupiah
 */
function rupiah($angka, bool $with_prefix = true): string {
    $num = (float)($angka ?? 0);
    $formatted = number_format($num, 0, ',', '.');
    return $with_prefix ? 'Rp ' . $formatted : $formatted;
}

/**
 * Format Tanggal Indonesia (contoh: 17 November 2026)
 */
function tanggal_indo(?string $date, bool $with_day = false): string {
    if (empty($date) || $date === '0000-00-00') {
        return '-';
    }
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $hari = [
        'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat',
        'Saturday' => 'Sabtu'
    ];

    $timestamp = strtotime($date);
    $d = date('j', $timestamp);
    $m = $bulan[(int)date('n', $timestamp)];
    $y = date('Y', $timestamp);

    $result = "{$d} {$m} {$y}";
    if ($with_day) {
        $dayName = $hari[date('l', $timestamp)] ?? '';
        $result = "{$dayName}, {$result}";
    }
    return $result;
}

/**
 * Format Waktu (contoh: 09:30 WIB)
 */
function waktu_indo(?string $time, bool $with_wib = true): string {
    if (empty($time)) return '-';
    $timestamp = strtotime($time);
    return date('H:i', $timestamp) . ($with_wib ? ' WIB' : '');
}

/**
 * Format Tanggal & Waktu Lengkap Indonesia
 */
function format_datetime_indo(?string $datetime): string {
    if (empty($datetime)) return '-';
    $timestamp = strtotime($datetime);
    return tanggal_indo(date('Y-m-d', $timestamp), true) . ' pukul ' . date('H:i', $timestamp) . ' WIB';
}

/**
 * Hitung Usia dari Tanggal Lahir
 */
function hitung_usia(?string $tgl_lahir): string {
    if (empty($tgl_lahir)) return '-';
    try {
        $lahir = new DateTime($tgl_lahir);
        $sekarang = new DateTime();
        $diff = $sekarang->diff($lahir);
        if ($diff->y > 0) {
            return $diff->y . ' tahun' . ($diff->m > 0 ? ' ' . $diff->m . ' bln' : '');
        }
        return $diff->m . ' bulan';
    } catch (Exception $e) {
        return '-';
    }
}

/**
 * Generate No Rekam Medis Otomatis: RM-YYYY-XXXX
 */
function generate_no_rm(PDO $pdo): string {
    $year = date('Y');
    $stmt = $pdo->prepare("SELECT no_rm FROM pasien WHERE no_rm LIKE ? ORDER BY id_pasien DESC LIMIT 1");
    $stmt->execute(["RM-{$year}-%"]);
    $last = $stmt->fetchColumn();

    if ($last) {
        $num = (int)substr($last, 8);
        $next = $num + 1;
    } else {
        $next = 1;
    }
    return sprintf("RM-%s-%04d", $year, $next);
}

/**
 * Generate Kode Booking Unik: 6 Karakter (misal: DNT482, K9A2M8)
 */
function generate_kode_booking(PDO $pdo): string {
    $chars = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ'; // Hindari karakter membingungkan (0, O, 1, I)
    do {
        $code = '';
        for ($i = 0; $i < 6; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM reservasi WHERE kode_booking = ?");
        $stmt->execute([$code]);
    } while ($stmt->fetchColumn() > 0);
    return $code;
}

/**
 * Generate Nomor Kuitansi Otomatis: KW/YYYYMM/XXXX
 */
function generate_no_kuitansi(PDO $pdo): string {
    $ym = date('Ym');
    $stmt = $pdo->prepare("SELECT no_kuitansi FROM pembayaran WHERE no_kuitansi LIKE ? ORDER BY id_pembayaran DESC LIMIT 1");
    $stmt->execute(["KW/{$ym}/%"]);
    $last = $stmt->fetchColumn();

    if ($last) {
        $parts = explode('/', $last);
        $num = (int)end($parts);
        $next = $num + 1;
    } else {
        $next = 1;
    }
    return sprintf("KW/%s/%04d", $ym, $next);
}

/**
 * Catat Log Aktivitas Pengguna
 */
function catat_log(PDO $pdo, string $aksi, ?string $detail = null, ?int $id_pengguna = null): void {
    try {
        if ($id_pengguna === null && isset($_SESSION['id_pengguna'])) {
            $id_pengguna = (int)$_SESSION['id_pengguna'];
        }
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $stmt = $pdo->prepare("INSERT INTO log_aktivitas (id_pengguna, aksi, detail, ip) VALUES (?, ?, ?, ?)");
        $stmt->execute([$id_pengguna, $aksi, $detail, $ip]);
    } catch (Exception $e) {
        // Abaikan error log agar tidak mengganggu transaksi utama
    }
}

/**
 * Kirim Pesan Notifikasi ke Pasien
 */
function kirim_notifikasi_pasien(PDO $pdo, int $id_pasien, string $judul, string $isi): bool {
    try {
        $stmt = $pdo->prepare("INSERT INTO pesan_notifikasi (id_pasien, judul, isi, status) VALUES (?, ?, ?, 'belum_dibaca')");
        return $stmt->execute([$id_pasien, $judul, $isi]);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Flash Message System (untuk alert SweetAlert2)
 */
function set_flash(string $type, string $title, string $message = ''): void {
    $_SESSION['flash_message'] = [
        'type'    => $type, // 'success', 'error', 'warning', 'info'
        'title'   => $title,
        'message' => $message
    ];
}

function get_flash(): ?array {
    if (isset($_SESSION['flash_message'])) {
        $msg = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $msg;
    }
    return null;
}

/**
 * Render Logo SVG Resmi DENTARA
 */
function render_logo(string $size = 'md', bool $with_text = true, string $variant = 'default'): string {
    $sizes = [
        'sm' => ['w' => 32, 'h' => 32, 'font' => 18, 'sub' => 10],
        'md' => ['w' => 44, 'h' => 44, 'font' => 24, 'sub' => 12],
        'lg' => ['w' => 60, 'h' => 60, 'font' => 32, 'sub' => 14],
        'xl' => ['w' => 84, 'h' => 84, 'font' => 42, 'sub' => 18],
    ];
    $s = $sizes[$size] ?? $sizes['md'];
    
    $textColor = ($variant === 'white') ? '#FFFFFF' : 'var(--text-main, #1E293B)';
    $subColor = ($variant === 'white') ? 'rgba(255,255,255,0.85)' : 'var(--primary, #2BB5A0)';
    
    $svgIcon = <<<SVG
    <svg width="{$s['w']}" height="{$s['h']}" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg" class="dentara-logo-icon">
        <defs>
            <linearGradient id="dentaraGrad" x1="4" y1="4" x2="60" y2="60" gradientUnits="userSpaceOnUse">
                <stop stop-color="#2BB5A0" />
                <stop offset="0.5" stop-color="#34D399" />
                <stop offset="1" stop-color="#5B9DF5" />
            </linearGradient>
            <linearGradient id="dentaraGlow" x1="0" y1="0" x2="64" y2="64" gradientUnits="userSpaceOnUse">
                <stop stop-color="#2BB5A0" stop-opacity="0.3"/>
                <stop offset="1" stop-color="#5B9DF5" stop-opacity="0"/>
            </linearGradient>
            <filter id="softShadow" x="-20%" y="-20%" width="140%" height="140%">
                <feDropShadow dx="0" dy="4" stdDeviation="4" flood-color="#2BB5A0" flood-opacity="0.25"/>
            </filter>
        </defs>
        <!-- Background soft glow circle -->
        <circle cx="32" cy="32" r="30" fill="url(#dentaraGlow)"/>
        <!-- Tooth Main Shape with Smile & Sparkle -->
        <path d="M22 6C12 6 8 16 8 28C8 38 12 44 18 56C21 62 26 62 28 54C30 46 34 46 36 54C38 62 43 62 46 56C52 44 56 38 56 28C56 16 52 6 42 6C36 6 34 11 32 11C30 11 28 6 22 6Z" 
              fill="url(#dentaraGrad)" filter="url(#softShadow)"/>
        <!-- Inner Smile highlight -->
        <path d="M22 28C22 34 26 38 32 38C38 34 42 34 42 28" 
              stroke="#FFFFFF" stroke-width="3.5" stroke-linecap="round"/>
        <!-- Eyes (friendly wink or happy smile) -->
        <circle cx="23" cy="22" r="2.5" fill="#FFFFFF"/>
        <circle cx="41" cy="22" r="2.5" fill="#FFFFFF"/>
        <!-- Cheek blush -->
        <circle cx="17" cy="27" r="2" fill="#FFAAA6" fill-opacity="0.8"/>
        <circle cx="47" cy="27" r="2" fill="#FFAAA6" fill-opacity="0.8"/>
        <!-- Sparkle 1 (Top Right) -->
        <path d="M52 10L53.5 14L57.5 15.5L53.5 17L52 21L50.5 17L46.5 15.5L50.5 14L52 10Z" fill="#FFD166"/>
        <!-- Sparkle 2 (Small) -->
        <path d="M12 18L13 20L15 21L13 22L12 24L11 22L9 21L11 20L12 18Z" fill="#FFD166"/>
    </svg>
SVG;

    if (!$with_text) {
        return '<div class="dentara-brand-logo inline-flex items-center">' . $svgIcon . '</div>';
    }

    return <<<HTML
    <div class="dentara-brand-logo inline-flex items-center" style="display:inline-flex;align-items:center;gap:12px;text-decoration:none;">
        {$svgIcon}
        <div class="brand-text" style="display:flex;flex-direction:column;line-height:1.1;">
            <span style="font-family:'Fredoka',sans-serif;font-size:{$s['font']}px;font-weight:700;letter-spacing:1px;color:{$textColor};">DENTARA</span>
            <span style="font-family:'Nunito',sans-serif;font-size:{$s['sub']}px;font-weight:700;color:{$subColor};letter-spacing:0.5px;">DENTAL CLINIC</span>
        </div>
    </div>
HTML;
}

/**
 * Render Maskot "Denta" (Gigi Ceria) SVG Inline
 * Moods: 'happy', 'success', 'thinking', 'alert', 'loading'
 */
function render_mascot(string $mood = 'happy', int $size = 140): string {
    $mascotEyeL = '<circle cx="38" cy="45" r="4.5" fill="#1E293B"/><circle cx="36" cy="43" r="1.5" fill="#FFFFFF"/>';
    $mascotEyeR = '<circle cx="62" cy="45" r="4.5" fill="#1E293B"/><circle cx="60" cy="43" r="1.5" fill="#FFFFFF"/>';
    $mouth = '<path d="M42 58C42 66 50 70 50 70C50 70 58 66 58 58" stroke="#1E293B" stroke-width="4" stroke-linecap="round" fill="#FF6B6B"/>';
    $accessory = '';

    if ($mood === 'success') {
        $mascotEyeL = '<path d="M34 46C36 42 42 42 44 46" stroke="#1E293B" stroke-width="4" stroke-linecap="round"/>';
        $mascotEyeR = '<path d="M56 46C58 42 64 42 66 46" stroke="#1E293B" stroke-width="4" stroke-linecap="round"/>';
        $mouth = '<path d="M40 56C40 68 60 68 60 56Z" fill="#EF4444" stroke="#1E293B" stroke-width="3"/>';
        // Jempol jempolan & bintang
        $accessory = '<path d="M78 28L80 34L86 35L81 39L83 45L78 42L73 45L75 39L70 35L76 34L78 28Z" fill="#FFD166"/>';
    } elseif ($mood === 'alert') {
        $mascotEyeL = '<circle cx="38" cy="45" r="5" fill="#EF4444"/>';
        $mascotEyeR = '<circle cx="62" cy="45" r="5" fill="#EF4444"/>';
        $mouth = '<path d="M42 64C46 58 54 58 58 64" stroke="#1E293B" stroke-width="4" stroke-linecap="round"/>';
        $accessory = '<circle cx="50" cy="18" r="8" fill="#F59E0B"/><text x="47" y="24" fill="#FFFFFF" font-family="Fredoka" font-size="14" font-weight="bold">!</text>';
    } elseif ($mood === 'thinking') {
        $mascotEyeL = '<circle cx="38" cy="43" r="4.5" fill="#1E293B"/>';
        $mascotEyeR = '<circle cx="62" cy="43" r="4.5" fill="#1E293B"/>';
        $mouth = '<ellipse cx="50" cy="62" rx="4" ry="6" fill="#1E293B"/>';
        $accessory = '<circle cx="76" cy="30" r="3" fill="#8B7CF6"/><circle cx="82" cy="24" r="5" fill="#8B7CF6"/><circle cx="90" cy="16" r="7" fill="#8B7CF6"/>';
    }

    return <<<SVG
    <svg width="{$size}" height="{$size}" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg" class="denta-mascot mood-{$mood}">
        <defs>
            <linearGradient id="dentaToothGrad" x1="20" y1="10" x2="80" y2="90" gradientUnits="userSpaceOnUse">
                <stop stop-color="#FFFFFF" />
                <stop offset="0.7" stop-color="#F0FDFA" />
                <stop offset="1" stop-color="#CCFBF1" />
            </linearGradient>
            <filter id="dentaShadow" x="-10%" y="-10%" width="120%" height="130%">
                <feDropShadow dx="0" dy="6" stdDeviation="5" flood-color="#0F766E" flood-opacity="0.18"/>
            </filter>
        </defs>
        <!-- Bayangan bawah -->
        <ellipse cx="50" cy="94" rx="30" ry="5" fill="#E2E8F0"/>
        <!-- Badan Gigi Denta -->
        <path d="M35 18C20 18 16 32 16 48C16 64 22 72 30 88C34 94 40 94 43 84C46 74 54 74 57 84C60 94 66 94 70 88C78 72 84 64 84 48C84 32 80 18 65 18C57 18 53 24 50 24C47 24 43 18 35 18Z" 
              fill="url(#dentaToothGrad)" stroke="#2BB5A0" stroke-width="3.5" filter="url(#dentaShadow)"/>
        <!-- Pipi Merah Lembut -->
        <circle cx="26" cy="52" r="5" fill="#FDA4AF" fill-opacity="0.7"/>
        <circle cx="74" cy="52" r="5" fill="#FDA4AF" fill-opacity="0.7"/>
        <!-- Mata & Mulut -->
        {$mascotEyeL}
        {$mascotEyeR}
        {$mouth}
        <!-- Kilauan Gigi Denta (Gleam) -->
        <ellipse cx="28" cy="28" rx="5" ry="3" transform="rotate(-30 28 28)" fill="#FFFFFF"/>
        <!-- Aksesoris Kondisional -->
        {$accessory}
    </svg>
SVG;
}

