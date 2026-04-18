<?php
global $conn;
require_role('admin');

$id   = isset($_GET['id'])   ? (int)$_GET['id']   : 0;
$csrf = trim($_GET['csrf'] ?? '');

if ($id <= 0 || !hash_equals(csrf_token(), $csrf)) {
    flash_set('error', 'Invalid delete request.');
    header('Location: index.php?page=manage_shop'); exit();
}

// Safety: refuse if vendors are assigned to this shop
$chk = mysqli_prepare($conn, "SELECT COUNT(*) AS n FROM vendors WHERE shop_id = ?");
mysqli_stmt_bind_param($chk, 'i', $id);
mysqli_stmt_execute($chk);
$cnt = mysqli_fetch_assoc(mysqli_stmt_get_result($chk))['n'];
mysqli_stmt_close($chk);

if ($cnt > 0) {
    flash_set('error', "Cannot delete: $cnt vendor(s) are assigned to this shop. Reassign or delete them first.");
    header('Location: index.php?page=manage_shop'); exit();
}

$stmt = mysqli_prepare($conn, "DELETE FROM shops WHERE shop_id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
if (mysqli_stmt_execute($stmt)) {
    flash_set('success', 'Shop deleted.');
} else {
    flash_set('error', 'Delete failed: ' . mysqli_error($conn));
}
mysqli_stmt_close($stmt);
header('Location: index.php?page=manage_shop'); exit();
