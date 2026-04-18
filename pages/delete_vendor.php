<?php
global $conn;
require_role('admin');

$id   = isset($_GET['id'])   ? (int)$_GET['id']   : 0;
$csrf = trim($_GET['csrf'] ?? '');

if ($id <= 0 || !hash_equals(csrf_token(), $csrf)) {
    flash_set('error', 'Invalid delete request.');
    header('Location: index.php?page=vendor_report'); exit();
}

$stmt = mysqli_prepare($conn, "DELETE FROM vendors WHERE vendor_id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
if (mysqli_stmt_execute($stmt)) {
    flash_set('success', 'Vendor deleted.');
} else {
    flash_set('error', 'Delete failed: ' . mysqli_error($conn));
}
mysqli_stmt_close($stmt);
header('Location: index.php?page=vendor_report'); exit();
