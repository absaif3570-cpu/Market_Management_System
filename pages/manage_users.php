<?php
global $conn;
require_role('admin');
$page_title = 'Manage Users';

// Handle role change
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $target_user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    $new_role = trim($_POST['role'] ?? '');
    $allowed_roles = ['admin', 'vendor', 'user'];

    if ($target_user_id > 0 && in_array($new_role, $allowed_roles, true)) {
        // If demoting from vendor, unlink their vendor record
        if ($new_role !== 'vendor') {
            $ul = mysqli_prepare($conn, "UPDATE vendors SET user_id = NULL WHERE user_id = ?");
            mysqli_stmt_bind_param($ul, 'i', $target_user_id);
            mysqli_stmt_execute($ul);
            mysqli_stmt_close($ul);
        }

        $upd = mysqli_prepare($conn, "UPDATE users SET role = ? WHERE user_id = ?");
        mysqli_stmt_bind_param($upd, 'si', $new_role, $target_user_id);
        mysqli_stmt_execute($upd);
        mysqli_stmt_close($upd);

        flash_set('success', 'User role updated successfully.');
        header('Location: index.php?page=manage_users'); exit();
    }
}

// Fetch all users with their linked vendor name if any
$users = [];
$res = mysqli_query($conn,
    "SELECT u.user_id, u.username, u.email, u.role, u.created_at,
            v.vendor_id, v.name AS vendor_name
     FROM users u
     LEFT JOIN vendors v ON v.user_id = u.user_id
     ORDER BY u.created_at DESC");
while ($r = mysqli_fetch_assoc($res)) $users[] = $r;

require __DIR__ . '/../includes/header.php';
?>
<h1 class="page-heading">Manage <span>Users</span></h1>

<?php
$flash = flash_get();
if ($flash): ?>
  <div class="alert alert-<?php echo $flash['type']; ?>" style="max-width:900px;margin:0 auto 18px;">
    <?php echo htmlspecialchars($flash['msg']); ?>
  </div>
<?php endif; ?>

<div class="card" style="max-width:960px;margin:0 auto;overflow-x:auto;">
  <table class="data-table" style="width:100%;">
    <thead>
      <tr>
        <th>#</th>
        <th>Username</th>
        <th>Email</th>
        <th>Role</th>
        <th>Linked Vendor</th>
        <th>Joined</th>
        <th>Change Role</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($users as $u): ?>
      <tr>
        <td><?php echo (int)$u['user_id']; ?></td>
        <td><?php echo htmlspecialchars($u['username']); ?></td>
        <td><?php echo htmlspecialchars($u['email']); ?></td>
        <td>
          <span class="badge badge-<?php echo $u['role']; ?>">
            <?php echo ucfirst($u['role']); ?>
          </span>
        </td>
        <td>
          <?php echo $u['vendor_name'] ? htmlspecialchars($u['vendor_name']) : '<span style="color:#666;">—</span>'; ?>
        </td>
        <td style="font-size:12px;color:#888;"><?php echo date('d M Y', strtotime($u['created_at'])); ?></td>
        <td>
          <?php if ($u['username'] !== 'admin'): ?>
          <form method="POST" style="display:flex;gap:8px;align-items:center;">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="user_id" value="<?php echo (int)$u['user_id']; ?>">
            <select name="role" style="padding:4px 8px;border-radius:6px;background:#1a2a1a;color:#fff;border:1px solid #2a4a2a;">
              <option value="user"   <?php if ($u['role']==='user')   echo 'selected'; ?>>User</option>
              <option value="vendor" <?php if ($u['role']==='vendor') echo 'selected'; ?>>Vendor</option>
              <option value="admin"  <?php if ($u['role']==='admin')  echo 'selected'; ?>>Admin</option>
            </select>
            <button type="submit" class="btn btn-primary btn-sm">Save</button>
          </form>
          <?php else: ?>
            <span style="color:#555;font-size:12px;">Protected</span>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
