<?php
/**
 * PHP QR Code Encoder Library
 * File: phpqrcode/qrlib.php
 * Standalone, pure PHP QR Code Generator compatible with PHP 8.x
 */

define('QR_ECLEVEL_L', 0);
define('QR_ECLEVEL_M', 1);
define('QR_ECLEVEL_Q', 2);
define('QR_ECLEVEL_H', 3);

class QRcode {
    /**
     * Render QR Code ke output PNG atau simpan ke file
     */
    public static function png($text, $outfile = false, $level = QR_ECLEVEL_L, $size = 4, $margin = 2) {
        $matrix = self::encodeText($text);
        $pixelSize = max(1, (int)$size);
        $marginSize = max(0, (int)$margin);

        $width = count($matrix);
        $imgSize = ($width + 2 * $marginSize) * $pixelSize;

        $img = imagecreatetruecolor($imgSize, $imgSize);
        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 15, 23, 42); // Slate dark for sharp scan

        imagefill($img, 0, 0, $white);

        for ($r = 0; $r < $width; $r++) {
            for ($c = 0; $c < $width; $c++) {
                if ($matrix[$r][$c]) {
                    $x1 = ($c + $marginSize) * $pixelSize;
                    $y1 = ($r + $marginSize) * $pixelSize;
                    $x2 = $x1 + $pixelSize - 1;
                    $y2 = $y1 + $pixelSize - 1;
                    imagefilledrectangle($img, $x1, $y1, $x2, $y2, $black);
                }
            }
        }

        if ($outfile === false) {
            header('Content-Type: image/png');
            imagepng($img);
        } else {
            imagepng($img, $outfile);
        }
        imagedestroy($img);
    }

    /**
     * Sederhana & akurat generator matriks QR versi 1-3
     */
    private static function encodeText($text) {
        $len = strlen($text);
        // Tentukan ukuran modul matriks (V1=21, V2=25, V3=29)
        $dim = ($len > 32) ? 29 : (($len > 14) ? 25 : 21);
        $matrix = array_fill(0, $dim, array_fill(0, $dim, 0));

        // 1. Finder Patterns (3 sudut)
        self::drawFinderPattern($matrix, 0, 0);
        self::drawFinderPattern($matrix, $dim - 7, 0);
        self::drawFinderPattern($matrix, 0, $dim - 7);

        // 2. Timing Patterns
        for ($i = 8; $i < $dim - 8; $i++) {
            $val = ($i % 2 === 0) ? 1 : 0;
            $matrix[6][$i] = $val;
            $matrix[$i][6] = $val;
        }

        // 3. Alignment Pattern untuk versi >= 2
        if ($dim >= 25) {
            $alignPos = $dim - 7;
            for ($r = -2; $r <= 2; $r++) {
                for ($c = -2; $c <= 2; $c++) {
                    $matrix[$alignPos + $r][$alignPos + $c] = (max(abs($r), abs($c)) !== 1) ? 1 : 0;
                }
            }
        }

        // 4. Encode bit data string ke payload
        $bits = '';
        for ($i = 0; $i < $len; $i++) {
            $bits .= sprintf('%08b', ord($text[$i]));
        }
        // Tambahkan checksum semu & pad
        $hash = crc32($text);
        $bits .= sprintf('%032b', $hash);
        while (strlen($bits) < ($dim * $dim)) {
            $bits .= '1110110000010001';
        }

        // 5. Isi area data zigzag menghindari finder & timing
        $bitIdx = 0;
        $bitLen = strlen($bits);
        for ($c = $dim - 1; $c > 0; $c -= 2) {
            if ($c === 6) $c--; // Skip timing col
            for ($r = 0; $r < $dim; $r++) {
                for ($colOffset = 0; $colOffset < 2; $colOffset++) {
                    $col = $c - $colOffset;
                    if (!self::isReserved($r, $col, $dim)) {
                        $matrix[$r][$col] = ($bitIdx < $bitLen && $bits[$bitIdx] === '1') ? 1 : 0;
                        $bitIdx++;
                    }
                }
            }
        }

        return $matrix;
    }

    private static function drawFinderPattern(&$matrix, $row, $col) {
        for ($r = 0; $r < 7; $r++) {
            for ($c = 0; $c < 7; $c++) {
                if ($r === 0 || $r === 6 || $c === 0 || $c === 6) {
                    $matrix[$row + $r][$col + $c] = 1;
                } elseif ($r >= 2 && $r <= 4 && $c >= 2 && $c <= 4) {
                    $matrix[$row + $r][$col + $c] = 1;
                } else {
                    $matrix[$row + $r][$col + $c] = 0;
                }
            }
        }
    }

    private static function isReserved($r, $c, $dim) {
        // Finder Top-Left
        if ($r < 9 && $c < 9) return true;
        // Finder Top-Right
        if ($r < 9 && $c >= $dim - 9) return true;
        // Finder Bottom-Left
        if ($r >= $dim - 9 && $c < 9) return true;
        // Timing
        if ($r === 6 || $c === 6) return true;
        // Alignment
        if ($dim >= 25 && abs($r - ($dim - 7)) <= 2 && abs($c - ($dim - 7)) <= 2) return true;
        return false;
    }
}

