<?php

function analyzeFile($filename)
{
    if (!file_exists($filename)) {
        return "File $filename tidak ditemukan.\n";
    }

    $rawContent = file_get_contents($filename);
    $hex = bin2hex(substr($rawContent, 0, 10)); // Ambil 10 byte pertama untuk cek BOM

    // 1. Cek Encoding & BOM
    $bom = '';
    if (strpos($hex, 'fffe') === 0) $bom = 'UTF-16 LE (Little Endian)';
    elseif (strpos($hex, 'feff') === 0) $bom = 'UTF-16 BE (Big Endian)';
    elseif (strpos($hex, 'efbbbf') === 0) $bom = 'UTF-8 with BOM';
    else $bom = 'No BOM detected (Possibly Plain UTF-8 or ASCII)';

    // 2. Cek Line Endings
    $hasCRLF = strpos($rawContent, "\r\n") !== false;
    $hasLF = strpos($rawContent, "\n") !== false && !$hasCRLF;
    $lineEnding = $hasCRLF ? 'CRLF (Windows)' : ($hasLF ? 'LF (Unix/Linux)' : 'Unknown');

    // Convert ke UTF-8 untuk mempermudah pemrosesan logic di PHP
    $content = mb_convert_encoding($rawContent, 'UTF-8', 'UTF-16LE');
    $lines = explode("\n", str_replace("\r", "", $content));
    $header = $lines[0];
    $columns = explode(",", $header);

    return [
        'filename' => $filename,
        'bom' => $bom,
        'line_ending' => $lineEnding,
        'column_count' => count($columns),
        'headers' => $columns,
        'raw_hex_start' => $hex
    ];
}

// Masukkan nama file Anda di sini
$fileERP = 'referensi_asli_erp.csv';
$fileExport = 'hasil_export_laravel.csv';

$res1 = analyzeFile($fileERP);
$res2 = analyzeFile($fileExport);

echo "=== HASIL PERBANDINGAN TEKNIS ===\n\n";

echo "FILE REFERENSI ERP:\n";
echo "BOM          : " . $res1['bom'] . "\n";
echo "Line Ending  : " . $res1['line_ending'] . "\n";
echo "Total Kolom  : " . $res1['column_count'] . "\n";
echo "Hex Start    : " . $res1['raw_hex_start'] . "\n\n";

echo "FILE EXPORT ANDA:\n";
echo "BOM          : " . $res2['bom'] . "\n";
echo "Line Ending  : " . $res2['line_ending'] . "\n";
echo "Total Kolom  : " . $res2['column_count'] . "\n";
echo "Hex Start    : " . $res2['raw_hex_start'] . "\n\n";

echo "=== DETAIL PERBEDAAN HEADER (Trailing Space Check) ===\n";
$max = max(count($res1['headers']), count($res2['headers']));

for ($i = 0; $i < $max; $i++) {
    $h1 = $res1['headers'][$i] ?? '[KOLOM TIDAK ADA]';
    $h2 = $res2['headers'][$i] ?? '[KOLOM TIDAK ADA]';

    $status = ($h1 === $h2) ? "OK" : "BERBEDA";

    // Cek trailing space secara visual
    $h1_vis = str_replace(' ', '·', $h1);
    $h2_vis = str_replace(' ', '·', $h2);

    if ($status === "BERBEDA") {
        echo "Kolom " . ($i + 1) . ":\n";
        echo "  ERP   : '$h1_vis'\n";
        echo "  Export: '$h2_vis'\n";
        echo "  Status: $status\n\n";
    }
}
