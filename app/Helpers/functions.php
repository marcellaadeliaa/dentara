<?php
/**
 * DENTARA - Global Helper Functions
 */

use App\Core\Auth;

function url(string $path = ''): string {
    return BASE_URL . '/' . ltrim($path, '/');
}

function asset(string $path = ''): string {
    return BASE_URL . '/assets/' . ltrim($path, '/');
}

function redirect(string $path = ''): void {
    header('Location: ' . url($path));
    exit;
}

function formatRupiah($amount): string {
    return 'Rp ' . number_format((float)$amount, 0, ',', '.');
}

function formatDateIndo($dateStr, bool $includeTime = false): string {
    if (!$dateStr) return '-';
    $timestamp = strtotime($dateStr);
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $tgl = date('d', $timestamp);
    $bln = $bulan[(int)date('m', $timestamp)];
    $thn = date('Y', $timestamp);

    $result = "{$tgl} {$bln} {$thn}";
    if ($includeTime) {
        $result .= ' ' . date('H:i', $timestamp) . ' WIB';
    }
    return $result;
}

function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim((string)$input), ENT_QUOTES, 'UTF-8');
}

function setFlash(string $type, string $message): void {
    $_SESSION['flash_' . $type] = $message;
}

function getFlash(string $type): ?string {
    $key = 'flash_' . $type;
    if (isset($_SESSION[$key])) {
        $msg = $_SESSION[$key];
        unset($_SESSION[$key]);
        return $msg;
    }
    return null;
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function verify_csrf(): bool {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        return hash_equals($_SESSION['csrf_token'] ?? '', $token);
    }
    return true;
}

function getQueueBadge(string $status): string {
    $map = [
        'waiting'         => ['class' => 'bg-amber-100 text-amber-800 border-amber-300', 'label' => 'Menunggu'],
        'calling'         => ['class' => 'bg-blue-100 text-blue-800 border-blue-300 animate-pulse', 'label' => 'Dipanggil'],
        'in_consultation' => ['class' => 'bg-emerald-100 text-emerald-800 border-emerald-300', 'label' => 'Konsultasi'],
        'pharmacy'        => ['class' => 'bg-purple-100 text-purple-800 border-purple-300', 'label' => 'Apotek'],
        'cashier'         => ['class' => 'bg-cyan-100 text-cyan-800 border-cyan-300', 'label' => 'Kasir'],
        'completed'       => ['class' => 'bg-gray-100 text-gray-800 border-gray-300', 'label' => 'Selesai'],
        'cancelled'       => ['class' => 'bg-rose-100 text-rose-800 border-rose-300', 'label' => 'Batal'],
        'skipped'         => ['class' => 'bg-orange-100 text-orange-800 border-orange-300', 'label' => 'Dilewati'],
    ];

    $item = $map[$status] ?? ['class' => 'bg-gray-100 text-gray-800 border-gray-300', 'label' => ucfirst($status)];
    return "<span class=\"inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border {$item['class']}\">{$item['label']}</span>";
}
