<?php
global $conn;
require_role('admin');
$page_title = 'Add Shop';
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
        // Check duplicate shop name
        $chk = mysqli_prepare($conn, "SELECT shop_id FROM shops WHERE shop_name = ?");
        mysqli_stmt_bind_param($chk, 's', $shop_name);
        mysqli_stmt_execute($chk);
        mysqli_stmt_store_result($chk);
        if (mysqli_stmt_num_rows($chk) > 0) {
            $error = "A shop named \"$shop_name\" already exists.";
        }
        mysqli_stmt_close($chk);

        if (!$error) {
            $stmt = mysqli_prepare($conn,
                "INSERT INTO shops (shop_name, owner_name, location, rent) VALUES (?,?,?,?)");
            mysqli_stmt_bind_param($stmt, 'sssd', $shop_name, $owner_name, $location, $rent);
            if (mysqli_stmt_execute($stmt)) {
                flash_set('success', "Shop \"$shop_name\" added.");
                header('Location: index.php?page=manage_shop'); exit();
            } else {
                $error = 'Database error: ' . mysqli_error($conn);
            }
        }
    }
}

require __DIR__ . '/../includes/header.php';
?>
<h1 class="page-heading">Add <span>Shop</span></h1>

<div class="card form-box">
  <?php if ($error): ?>
    <div class="alert alert-error"><span>✕</span> <?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>

  <form method="POST" data-validate>
    <?php echo csrf_field(); ?>

    <div class="form-group">
      <label>Shop Name</label>
      <input type="text" name="shop_name" placeholder="e.g. Shop A-12" required
             value="<?php echo htmlspecialchars($_POST['shop_name'] ?? ''); ?>">
    </div>
    <div class="form-group">
      <label>Owner Name</label>
      <input type="text" name="owner_name" placeholder="Owner's full name" required
             value="<?php echo htmlspecialchars($_POST['owner_name'] ?? ''); ?>">
    </div>
    <div class="form-group">
      <label>Location / Block</label>
      <input type="text" name="location" placeholder="e.g. Block C, Row 3" required
             value="<?php echo htmlspecialchars($_POST['location'] ?? ''); ?>">
    </div>
    <div class="form-group">
      <label>Monthly Rent (TK)</label>
      <input type="number" name="rent" placeholder="e.g. 5000" min="0" step="0.01" required
             value="<?php echo htmlspecialchars($_POST['rent'] ?? ''); ?>">
    </div>

    <button type="submit" class="btn btn-primary btn-full">Add Shop →</button>
  </form>

  <p class="form-footer"><a href="index.php?page=manage_shop">← Back to Shops</a></p>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
