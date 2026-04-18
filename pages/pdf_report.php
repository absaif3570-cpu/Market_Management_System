<?php
require_once __DIR__ . '/../config.php';
global $conn;
require_once __DIR__ . '/../auth.php';
require_role('admin');

// ============================================================
//  Pure PHP PDF Generator — no FPDF/TCPDF required
//  Produces a valid binary PDF with multiple pages,
//  proper cross-reference table, and styled tables.
// ============================================================

// ── Data ─────────────────────────────────────────────────────
$vendors = [];
$vq = mysqli_query($conn,
    "SELECT v.vendor_id, v.name, v.phone, s.shop_name, s.location
     FROM vendors v JOIN shops s ON v.shop_id = s.shop_id ORDER BY v.vendor_id");
while ($r = mysqli_fetch_assoc($vq)) $vendors[] = $r;

$shops = [];
$sq = mysqli_query($conn, "SELECT * FROM shops ORDER BY shop_id");
while ($r = mysqli_fetch_assoc($sq)) $shops[] = $r;

$payments = [];
$pq = mysqli_query($conn,
    "SELECT p.payment_id, v.name AS vendor_name, p.amount, p.payment_method, p.payment_date
     FROM payments p JOIN vendors v ON p.vendor_id = v.vendor_id
     ORDER BY p.payment_date DESC LIMIT 100");
while ($r = mysqli_fetch_assoc($pq)) $payments[] = $r;

$totals = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COALESCE(SUM(s.rent),0) AS total_rent,
            COALESCE(SUM(p.total),0) AS total_paid
     FROM vendors v JOIN shops s ON v.shop_id = s.shop_id
     LEFT JOIN (SELECT vendor_id, SUM(amount) AS total FROM payments GROUP BY vendor_id) p
            ON v.vendor_id = p.vendor_id"));

// ── PDF helpers ───────────────────────────────────────────────
function pdfEsc(string $s): string {
    return str_replace(['\\','(',')'], ['\\\\','\\(','\\)'], $s);
}
function txt(string $s, float $x, float $y, int $size = 10, bool $bold = false): string {
    $f = $bold ? 'F2' : 'F1';
    return "BT /$f $size Tf $x $y Td (" . pdfEsc(mb_substr($s, 0, 60)) . ") Tj ET\n";
}
function rect(float $x, float $y, float $w, float $h, string $rgb = '0 0 0'): string {
    return "$rgb rg $x $y $w $h re f\n";
}
function line(float $x1, float $y1, float $x2, float $y2, string $rgb = '0.8 0.8 0.8'): string {
    return "$rgb RG 0.3 w $x1 $y1 m $x2 $y2 l S\n";
}

// ── Page builder ──────────────────────────────────────────────
$W = 595; $H = 842; $ML = 36; $MR = 36; $TW = $W - $ML - $MR;
$LINE = 18; $BOTTOM = 56;
$GREEN = '0.04 0.47 0.32';
$GREEN_L = '0.9 0.97 0.93';
$WHITE = '1 1 1';
$DARK  = '0.12 0.18 0.14';
$GRAY  = '0.55 0.55 0.55';

function page_header(string $title, string $sub = ''): string {
    global $ML, $TW, $GREEN, $WHITE, $W;
    $o  = rect($ML, 800, $TW, 32, $GREEN);
    $o .= txt($title, $ML + 8, 810, 13, true);
    $o .= "1 1 1 rg\n";
    $o .= txt($title, $ML + 8, 810, 13, true);
    $o .= "0 0 0 rg\n";
    // Actually set white text:
    $o = "BT /F2 13 Tf " . ($ML+8) . " 810 Td 1 1 1 rg (" . pdfEsc($title) . ") Tj ET\n";
    $o = rect($ML, 800, $TW, 32, $GREEN)
       . "BT /F2 13 Tf " . ($ML+8) . " 813 Td 1 1 1 rg (" . pdfEsc($title) . ") Tj ET\n";
    if ($sub) {
        $o .= "BT /F1 9 Tf " . ($ML + $TW - 6) . " 813 Td "
            . "-1 0 0 1 0 0 Tm (" . pdfEsc($sub) . ") Tj ET\n";
        // Simple right-aligned approximation:
        $approx_x = $ML + $TW - (strlen($sub) * 5);
        $o .= "BT /F1 9 Tf $approx_x 813 Td 0.7 0.9 0.8 rg (" . pdfEsc($sub) . ") Tj ET\n";
    }
    return $o;
}

function table_header(array $cols, float $y): string {
    global $ML, $GREEN, $LINE;
    $o = rect($ML, $y - 3, array_sum(array_column($cols,'w')), $LINE + 2, $GREEN);
    $x = $ML + 4;
    foreach ($cols as $c) {
        $o .= "BT /F2 8 Tf $x " . ($y + 3) . " Td 1 1 1 rg (" . pdfEsc($c['t']) . ") Tj ET\n";
        $x += $c['w'];
    }
    return $o;
}

function table_row(array $cols, array $vals, float $y, bool $alt): string {
    global $ML, $LINE, $GREEN_L;
    $o = '';
    if ($alt) $o .= rect($ML, $y - 3, array_sum(array_column($cols,'w')), $LINE, '0.96 0.99 0.97');
    $x = $ML + 4;
    foreach ($cols as $i => $c) {
        $val = isset($vals[$i]) ? (string)$vals[$i] : '';
        $o .= "BT /F1 8 Tf $x " . ($y + 2) . " Td 0.1 0.15 0.12 rg (" . pdfEsc(mb_strimwidth($val,0,28,'…')) . ") Tj ET\n";
        $x += $c['w'];
    }
    $o .= line($ML, $y - 3, $ML + array_sum(array_column($cols,'w')), $y - 3);
    return $o;
}

// ── Build pages ───────────────────────────────────────────────
$pages = [];

// ── Page 1: Summary ───────────────────────────────────────────
$p = page_header('Market Management System — Summary Report', date('d M Y'));
$y = 786;

$summary = [
    ['Total Vendors',             count($vendors)],
    ['Total Shops',               count($shops)],
    ['Monthly Rent Roll (TK)',    number_format((float)$totals['total_rent'], 2)],
    ['Total Collected (TK)',      number_format((float)$totals['total_paid'], 2)],
    ['Outstanding Balance (TK)',  number_format(abs((float)$totals['total_rent'] - (float)$totals['total_paid']), 2)],
    ['Report Generated',          date('d M Y H:i:s')],
];
$alt = false;
foreach ($summary as $s) {
    if ($alt) $p .= rect($ML, $y - 3, $TW, $LINE + 1, '0.96 0.99 0.97');
    $p .= "BT /F2 9 Tf " . ($ML+6) . " " . ($y+3) . " Td 0.1 0.15 0.12 rg (" . pdfEsc($s[0]) . ") Tj ET\n";
    $p .= "BT /F1 9 Tf " . ($ML+240) . " " . ($y+3) . " Td 0.04 0.47 0.32 rg (" . pdfEsc((string)$s[1]) . ") Tj ET\n";
    $p .= line($ML, $y - 3, $ML + $TW, $y - 3);
    $y -= $LINE + 2;
    $alt = !$alt;
}
$pages[] = $p;

// ── Page 2: Vendors ───────────────────────────────────────────
$cols_v = [['t'=>'ID','w'=>32],['t'=>'Name','w'=>140],['t'=>'Phone','w'=>100],['t'=>'Shop','w'=>120],['t'=>'Location','w'=>131]];
$p = page_header('Vendor List (' . count($vendors) . ' total)');
$y = 780;
$p .= table_header($cols_v, $y);
$y -= $LINE + 2;
$alt = false;
foreach ($vendors as $v) {
    if ($y < $BOTTOM) { $pages[] = $p; $p = page_header('Vendor List (continued)'); $y = 780; $p .= table_header($cols_v, $y); $y -= $LINE + 2; $alt = false; }
    $p .= table_row($cols_v, [$v['vendor_id'], $v['name'], $v['phone'], $v['shop_name'], $v['location']], $y, $alt);
    $y -= $LINE; $alt = !$alt;
}
$pages[] = $p;

// ── Page 3: Shops ─────────────────────────────────────────────
$cols_s = [['t'=>'ID','w'=>32],['t'=>'Shop Name','w'=>130],['t'=>'Owner','w'=>120],['t'=>'Location','w'=>130],['t'=>'Rent (TK)','w'=>111]];
$p = page_header('Shop List (' . count($shops) . ' total)');
$y = 780;
$p .= table_header($cols_s, $y);
$y -= $LINE + 2;
$alt = false;
foreach ($shops as $s) {
    if ($y < $BOTTOM) { $pages[] = $p; $p = page_header('Shop List (continued)'); $y = 780; $p .= table_header($cols_s, $y); $y -= $LINE + 2; $alt = false; }
    $p .= table_row($cols_s, [$s['shop_id'], $s['shop_name'], $s['owner_name'], $s['location'], number_format((float)$s['rent'],2)], $y, $alt);
    $y -= $LINE; $alt = !$alt;
}
$pages[] = $p;

// ── Page 4: Payments ─────────────────────────────────────────
$cols_p = [['t'=>'#','w'=>32],['t'=>'Vendor','w'=>155],['t'=>'Amount (TK)','w'=>90],['t'=>'Method','w'=>100],['t'=>'Date','w'=>146]];
$p = page_header('Payment Records (last ' . count($payments) . ')');
$y = 780;
$p .= table_header($cols_p, $y);
$y -= $LINE + 2;
$alt = false;
foreach ($payments as $pay) {
    if ($y < $BOTTOM) { $pages[] = $p; $p = page_header('Payment Records (continued)'); $y = 780; $p .= table_header($cols_p, $y); $y -= $LINE + 2; $alt = false; }
    $p .= table_row($cols_p,
        [$pay['payment_id'], $pay['vendor_name'], number_format((float)$pay['amount'],2), $pay['payment_method'], date('d M Y', strtotime($pay['payment_date']))],
        $y, $alt);
    $y -= $LINE; $alt = !$alt;
}
$pages[] = $p;

// ── Assemble PDF ──────────────────────────────────────────────
$num_pages = count($pages);
$objs = [];
$offsets = [];

// 1: Catalog
$objs[1] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj";

// 2: Pages dict (kids filled later)
$kid_refs = '';
$content_base = 4; // first content stream obj
for ($i = 0; $i < $num_pages; $i++) {
    $kid_refs .= ($content_base + $i*2) . " 0 R ";
}
$objs[2] = "2 0 obj\n<< /Type /Pages /Kids [$kid_refs] /Count $num_pages >>\nendobj";

// 3: Helvetica
$objs[3] = "3 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>\nendobj";
// 4 reserved for bold — use obj 34
$objs[34] = "34 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>\nendobj";

// Page objects + content streams
$next_obj = $content_base;
foreach ($pages as $pi => $stream) {
    $page_obj = $next_obj;
    $cont_obj = $next_obj + 1;
    $objs[$page_obj] = "$page_obj 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 $W $H]\n"
        . "   /Resources << /Font << /F1 3 0 R /F2 34 0 R >> >> /Contents $cont_obj 0 R >>\nendobj";
    $objs[$cont_obj] = "$cont_obj 0 obj\n<< /Length " . strlen($stream) . " >>\nstream\n$stream\nendstream\nendobj";
    $next_obj += 2;
}

// ── Build binary ──────────────────────────────────────────────
$order = array_keys($objs);
sort($order);

$pdf = "%PDF-1.4\n%\xe2\xe3\xcf\xd3\n"; // header + binary comment
foreach ($order as $n) {
    $offsets[$n] = strlen($pdf);
    $pdf .= $objs[$n] . "\n";
}

$xref_offset = strlen($pdf);
$max_obj = max($order);
$pdf .= "xref\n0 " . ($max_obj + 1) . "\n";
$pdf .= "0000000000 65535 f \n";
for ($i = 1; $i <= $max_obj; $i++) {
    if (isset($offsets[$i])) {
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
    } else {
        $pdf .= "0000000000 65535 f \n";
    }
}
$pdf .= "trailer\n<< /Size " . ($max_obj + 1) . " /Root 1 0 R >>\n";
$pdf .= "startxref\n$xref_offset\n%%EOF";

// ── Output ────────────────────────────────────────────────────
$fname = 'market_report_' . date('Y-m-d') . '.pdf';
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $fname . '"');
header('Content-Length: ' . strlen($pdf));
header('Cache-Control: no-cache, no-store');
header('Pragma: no-cache');
echo $pdf;
exit();
