<?php
/**
 * DENTARA - 500 Gangguan Server Internal
 * File: 500.php
 */
require_once __DIR__ . '/includes/helpers.php';
http_response_code(500);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 Gangguan Server - DENTARA</title>
    <link rel="icon" type="image/svg+xml" href="<?= base_url('assets/img/favicon.svg') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@600;700&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/css/design_tokens.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/components.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/modern_ui.css') ?>">
    <style>
        body { min-height: 100vh; display: flex; align-items: center; justify-content: center; background: var(--bg-app); padding: 20px; text-align: center; }
        .error-box { background: var(--surface); padding: 48px 32px; border-radius: var(--radius-xl); box-shadow: var(--shadow-lg); border: 1px solid var(--border-color); max-width: 480px; width: 100%; }
    </style>
</head>
<body>
    <div class="error-box fade-in">
        <?= render_mascot('alert', 120) ?>
        <h1 style="font-size:32px; color:var(--danger); margin:16px 0 8px;">500 - Gangguan Server</h1>
        <p style="color:var(--text-muted); font-size:15px; line-height:1.6; margin-bottom:24px;">
            Terjadi kendala pada server saat memproses permintaan Anda. Tim teknis kami telah diberitahu mengenai masalah ini.
        </p>
        <button class="btn btn-primary btn-md" onclick="window.location.reload()">
            Coba Muat Ulang
        </button>
    </div>
</body>
</html>

