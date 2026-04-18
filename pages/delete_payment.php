<?php
global $conn;
require_role('admin');

$id        = isset($_GET['id'])        ? (int)$_GET['id']        : 0;
$vendor_id = isset($_GET['vendor_id']) ? (int)$_GET['vendor_id'] : 0;
$csrf      = trim($_GET['csrf'] ?? '');

if ($id <= 0 || !hash_equals(csrf_token(), $csrf)) {
    flash_set('error', 'Invalid delete request.');
    header('Location: index.php?page=rent_record'); exit();
}

$stmt = mysqli_prepare($conn, "DELETE FROM payments WHERE payment_id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
if (mysqli_stmt_execute($stmt)) {
    flash_set('success', 'Payment deleted.');
} else {
    flash_set('error', 'Delete failed: ' . mysqli_error($conn));
}
mysqli_stmt_close($stmt);

// Return to vendor profile if we know which vendor
if ($vendor_id > 0) {
    header("Location: index.php?page=vendor_profile&id=$vendor_id"); exit();
}
header('Location: index.php?page=rent_record'); exit();
