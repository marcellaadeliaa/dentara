<?php
/**
 * DENTARA - QR Code Generator Endpoint
 * File: qr.php
 * Parameter: ?kode=ABC123
 */

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/phpqrcode/qrlib.php';

$kode = trim($_GET['kode'] ?? '');

// Validasi format kode: hanya alfanumerik 3-20 karakter
if (empty($kode) || !preg_match('/^[A-Za-z0-9\-\/]{3,20}$/', $kode)) {
    http_response_code(400);
    header('Content-Type: text/plain');
    echo 'Kode tidak valid.';
    exit;
}

// Generate PNG QR Code langsung ke output stream
QRcode::png($kode, false, QR_ECLEVEL_M, 5, 2);
exit;

