<?php
global $conn;
require_role('admin');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { header('Location: index.php?page=manage_shop'); exit(); }

$stmt = mysqli_prepare($conn, "SELECT * FROM shops WHERE shop_id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$shop = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$shop) { flash_set('error', 'Shop not found.'); header('Location: index.php?page=manage_shop'); exit(); }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $shop_name  = trim($_POST['shop_name']  ?? '');
    $owner_name = trim($_POST['owner_name'] ?? '');
    $location   = trim($_POST['location']   ?? '');
    $rent       = trim($_POST['rent']       ?? '');

    if (!$shop_name || !$owner_name || !$location || $rent === '') {
        $error = 'All fields are required.';
    } elseif (!is_numeric($rent) || (float)$rent < 0) {
        $error = 'Rent must be a valid positive number.';
    } else {
        // Check duplicate name (excluding self)
        $chk = mysqli_prepare($conn, "SELECT shop_id FROM shops WHERE shop_name = ? AND shop_id != ?");
        mysqli_stmt_bind_param($chk, 'si', $shop_name, $id);
        mysqli_stmt_execute($chk);
        mysqli_stmt_store_result($chk);
        if (mysqli_stmt_num_rows($chk) > 0) {
            $error = "Another shop named \"$shop_name\" already exists.";
        }
        mysqli_stmt_close($chk);

        if (!$error) {
            $upd = mysqli_prepare($conn,
                "UPDATE shops SET shop_name=?, owner_name=?, location=?, rent=? WHERE shop_id=?");
            mysqli_stmt_bind_param($upd, 'sssdi', $shop_name, $owner_name, $location, $rent, $id);
            if (mysqli_stmt_execute($upd)) {
                flash_set('success', "Shop \"$shop_name\" updated.");
                header('Location: index.php?page=manage_shop'); exit();
            } else {
                $error = 'Update failed: ' . mysqli_error($conn);
            }
        }
    }
}

$page_title = 'Edit Shop';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="page-heading">Edit <span>Shop</span></h1>

<div class="card form-box">
  <?php if ($error): ?>
    <div class="alert alert-error"><span>✕</span> <?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>

  <form method="POST" data-validate>
    <?php echo csrf_field(); ?>

    <div class="form-group">
      <label>Shop Name</label>
      <input type="text" name="shop_name" required
             value="<?php echo htmlspecialchars($_POST['shop_name'] ?? $shop['shop_name']); ?>">
    </div>
    <div class="form-group">
      <label>Owner Name</label>
      <input type="text" name="owner_name" required
             value="<?php echo htmlspecialchars($_POST['owner_name'] ?? $shop['owner_name']); ?>">
    </div>
    <div class="form-group">
      <label>Location / Block</label>
      <input type="text" name="location" required
             value="<?php echo htmlspecialchars($_POST['location'] ?? $shop['location']); ?>">
    </div>
    <div class="form-group">
      <label>Monthly Rent (TK)</label>
      <input type="number" name="rent" min="0" step="0.01" required
             value="<?php echo htmlspecialchars($_POST['rent'] ?? $shop['rent']); ?>">
    </div>

    <button type="submit" class="btn btn-primary btn-full">Update Shop →</button>
  </form>

  <p class="form-footer"><a href="index.php?page=manage_shop">← Back to Shops</a></p>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
