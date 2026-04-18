<?php
global $conn;
require_role('admin');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { header('Location: index.php?page=vendor_report'); exit(); }

// Fetch vendor
$stmt = mysqli_prepare($conn, "SELECT * FROM vendors WHERE vendor_id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$vendor = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$vendor) { flash_set('error', 'Vendor not found.'); header('Location: index.php?page=vendor_report'); exit(); }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $name    = trim($_POST['name']    ?? '');
    $phone   = trim($_POST['phone']   ?? '');
    $shop_id = isset($_POST['shop_id']) ? (int)$_POST['shop_id'] : 0;

    if ($name === '' || $phone === '' || $shop_id <= 0) {
        $error = 'All fields are required.';
    } else {
        $upd = mysqli_prepare($conn, "UPDATE vendors SET name=?, phone=?, shop_id=? WHERE vendor_id=?");
        mysqli_stmt_bind_param($upd, 'ssii', $name, $phone, $shop_id, $id);
        if (mysqli_stmt_execute($upd)) {
            mysqli_stmt_close($upd);
            flash_set('success', "Vendor \"$name\" updated.");
            header('Location: index.php?page=vendor_report'); exit();
        } else {
            $error = 'Update failed: ' . mysqli_error($conn);
        }
    }
}

$page_title = 'Edit Vendor';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="page-heading">Edit <span>Vendor</span></h1>

<div class="card form-box">
  <?php if ($error): ?>
    <div class="alert alert-error"><span>✕</span> <?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>

  <form method="POST" data-validate>
    <?php echo csrf_field(); ?>

    <div class="form-group">
      <label>Vendor Name</label>
      <input type="text" name="name" required
             value="<?php echo htmlspecialchars($_POST['name'] ?? $vendor['name']); ?>">
    </div>

    <div class="form-group">
      <label>Phone Number</label>
      <input type="text" name="phone" required
             value="<?php echo htmlspecialchars($_POST['phone'] ?? $vendor['phone']); ?>">
    </div>

    <div class="form-group">
      <label>Assign Shop</label>
      <select name="shop_id" required>
        <option value="">— Select a Shop —</option>
        <?php
        $current_shop = (int)($_POST['shop_id'] ?? $vendor['shop_id']);
        $shops = mysqli_query($conn, "SELECT shop_id, shop_name, location FROM shops ORDER BY shop_name");
        while ($s = mysqli_fetch_assoc($shops)):
            $sel = ((int)$s['shop_id'] === $current_shop) ? 'selected' : '';
        ?>
          <option value="<?php echo (int)$s['shop_id']; ?>" <?php echo $sel; ?>>
            <?php echo htmlspecialchars($s['shop_name']); ?> — <?php echo htmlspecialchars($s['location']); ?>
          </option>
        <?php endwhile; ?>
      </select>
    </div>

    <button type="submit" class="btn btn-primary btn-full">Update Vendor →</button>
  </form>

  <p class="form-footer"><a href="index.php?page=vendor_report">← Back to Vendors</a></p>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
