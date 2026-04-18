<?php
global $conn;
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth.php';
require_role('admin');

$type = strtolower(trim($_GET['type'] ?? 'vendors'));
$allowed = ['vendors', 'shops', 'payments'];
if (!in_array($type, $allowed, true)) $type = 'vendors';

$filename = "mms_{$type}_backup_" . date('Y-m-d') . ".xls";

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");

function xls_cell(string $v): string {
    return str_replace(["\t", "\n", "\r", '"'], [' ', ' ', ' ', '""'], $v);
}

if ($type === 'vendors') {
    echo "Vendor ID\tName\tPhone\tShop\tLocation\tAdded\n";
    $res = mysqli_query($GLOBALS['conn'],
        "SELECT v.vendor_id, v.name, v.phone, s.shop_name, s.location, v.created_at
         FROM vendors v JOIN shops s ON v.shop_id = s.shop_id ORDER BY v.vendor_id");
    while ($r = mysqli_fetch_assoc($res)) {
        echo implode("\t", array_map('xls_cell', [
            $r['vendor_id'], $r['name'], $r['phone'],
            $r['shop_name'], $r['location'], $r['created_at']
        ])) . "\n";
    }
} elseif ($type === 'shops') {
    echo "Shop ID\tShop Name\tOwner\tLocation\tMonthly Rent\tAdded\n";
    $res = mysqli_query($GLOBALS['conn'], "SELECT * FROM shops ORDER BY shop_id");
    while ($r = mysqli_fetch_assoc($res)) {
        echo implode("\t", array_map('xls_cell', [
            $r['shop_id'], $r['shop_name'], $r['owner_name'],
            $r['location'], $r['rent'], $r['created_at']
        ])) . "\n";
    }
} elseif ($type === 'payments') {
    echo "Payment ID\tVendor\tAmount (TK)\tMethod\tDate\tNote\tRecorded At\n";
    $res = mysqli_query($GLOBALS['conn'],
        "SELECT p.payment_id, v.name AS vendor, p.amount, p.payment_method,
                p.payment_date, p.note, p.created_at
         FROM payments p JOIN vendors v ON p.vendor_id = v.vendor_id
         ORDER BY p.payment_date DESC");
    while ($r = mysqli_fetch_assoc($res)) {
        echo implode("\t", array_map('xls_cell', [
            $r['payment_id'], $r['vendor'], $r['amount'],
            $r['payment_method'], $r['payment_date'], $r['note'] ?? '', $r['created_at']
        ])) . "\n";
    }
}
exit();
