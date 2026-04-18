<?php
global $conn;
require_role('admin');
$page_title = 'Add Vendor';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $name    = trim($_POST['name']    ?? '');
    $phone   = trim($_POST['phone']   ?? '');
    $shop_id = isset($_POST['shop_id']) ? (int)$_POST['shop_id'] : 0;
    $user_id = isset($_POST['user_id']) && $_POST['user_id'] !== '' ? (int)$_POST['user_id'] : null;

    if ($name === '' || $phone === '' || $shop_id <= 0) {
        $error = 'All fields are required.';
    } else {
        $chk = mysqli_prepare($conn, "SELECT shop_id FROM shops WHERE shop_id = ?");
        mysqli_stmt_bind_param($chk, 'i', $shop_id);
        mysqli_stmt_execute($chk);
        mysqli_stmt_store_result($chk);
        $shop_ok = mysqli_stmt_num_rows($chk) > 0;
        mysqli_stmt_close($chk);

        if (!$shop_ok) {
            $error = 'Selected shop does not exist.';
        } else {
            $stmt = mysqli_prepare($conn, "INSERT INTO vendors (name, phone, shop_id, user_id) VALUES (?,?,?,?)");
            mysqli_stmt_bind_param($stmt, 'ssii', $name, $phone, $shop_id, $user_id);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                flash_set('success', "Vendor \"$name\" added successfully.");
                header('Location: index.php?page=vendor_report'); exit();
            } else {
                $error = 'Database error: ' . mysqli_error($conn);
            }
        }
    }
}

require __DIR__ . '/../includes/header.php';
?>
<h1 class="page-heading">Add <span>Vendor</span></h1>

<div class="card form-box">
  <?php if ($error): ?>
    <div class="alert alert-error"><span>✕</span> <?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>

  <form method="POST" data-validate>
    <?php echo csrf_field(); ?>

    <div class="form-group">
      <label>Vendor Name</label>
      <input type="text" name="name" placeholder="Full name" required
             value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
    </div>

    <div class="form-group">
      <label>Phone Number</label>
      <input type="text" name="phone" placeholder="e.g. 01700-000000" required
             value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
    </div>

    <div class="form-group">
      <label>Assign Shop</label>
      <select name="shop_id" required>
        <option value="">— Select a Shop —</option>
        <?php
        $shops = mysqli_query($conn, "SELECT shop_id, shop_name, location FROM shops ORDER BY shop_name");
        while ($s = mysqli_fetch_assoc($shops)) {
            $sel = (isset($_POST['shop_id']) && (int)$_POST['shop_id'] === (int)$s['shop_id']) ? 'selected' : '';
            echo "<option value='" . (int)$s['shop_id'] . "' $sel>"
               . htmlspecialchars($s['shop_name']) . " — " . htmlspecialchars($s['location'])
               . "</option>";
        }
        ?>
      </select>
    </div>
    
    <div class="form-group">
      <label>Link User Account <span style="color:rgba(255,255,255,0.35);font-weight:400;">(optional — for vendor login)</span></label>
      <select name="user_id">
        <option value="">— No account —</option>
        <?php
        $uq = mysqli_query($conn, "SELECT user_id, username FROM users WHERE role='vendor' ORDER BY username");
        while ($u = mysqli_fetch_assoc($uq)) {
            $sel = (isset($_POST['user_id']) && (int)$_POST['user_id'] === (int)$u['user_id']) ? 'selected' : '';
            echo "<option value='" . (int)$u['user_id'] . "' $sel>"
               . htmlspecialchars($u['username'])
               . "</option>";
        }
        ?>
      </select>
    </div>

    <button type="submit" class="btn btn-primary btn-full">Add Vendor →</button>
  </form>

  <p class="form-footer"><a href="index.php?page=vendor_report">← Back to Vendors</a></p>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
