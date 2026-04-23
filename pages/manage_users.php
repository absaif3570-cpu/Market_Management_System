<?php
global $conn;
require_role('admin');
$page_title = 'Manage Users';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $target_user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    $new_role = trim($_POST['role'] ?? '');
    $allowed_roles = ['admin', 'vendor', 'user'];

    if ($target_user_id > 0 && in_array($new_role, $allowed_roles, true)) {
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
  <div class="alert alert-<?php echo $flash['type']; ?>" style="max-width:960px;margin:0 auto 18px;">
    <?php echo htmlspecialchars($flash['msg']); ?>
  </div>
<?php endif; ?>

<div style="max-width:980px;margin:0 auto;overflow-x:auto;">
  <table style="width:100%;border-collapse:collapse;font-size:14px;">
    <thead>
      <tr style="background:rgba(255,255,255,0.05);border-bottom:1px solid rgba(255,255,255,0.1);">
        <th style="padding:12px 14px;text-align:left;color:#a0b8a0;font-weight:600;">#</th>
        <th style="padding:12px 14px;text-align:left;color:#a0b8a0;font-weight:600;">Username</th>
        <th style="padding:12px 14px;text-align:left;color:#a0b8a0;font-weight:600;">Email</th>
        <th style="padding:12px 14px;text-align:left;color:#a0b8a0;font-weight:600;">Role</th>
        <th style="padding:12px 14px;text-align:left;color:#a0b8a0;font-weight:600;">Linked Vendor</th>
        <th style="padding:12px 14px;text-align:left;color:#a0b8a0;font-weight:600;">Joined</th>
        <th style="padding:12px 14px;text-align:left;color:#a0b8a0;font-weight:600;">Change Role</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($users as $u): ?>
      <tr style="border-bottom:1px solid rgba(255,255,255,0.05);">
        <td style="padding:12px 14px;color:#d0e8d0;"><?php echo (int)$u['user_id']; ?></td>
        <td style="padding:12px 14px;color:#d0e8d0;font-weight:600;"><?php echo htmlspecialchars($u['username']); ?></td>
        <td style="padding:12px 14px;color:#a0b8a0;font-size:13px;"><?php echo htmlspecialchars($u['email']); ?></td>
        <td style="padding:12px 14px;">
          <?php
            $badge_color = match($u['role']) {
              'admin'  => 'background:rgba(139,92,246,0.15);color:#c4b5fd;border:1px solid rgba(139,92,246,0.3);',
              'vendor' => 'background:rgba(16,185,129,0.15);color:#6ee7b7;border:1px solid rgba(16,185,129,0.3);',
              default  => 'background:rgba(59,130,246,0.15);color:#93c5fd;border:1px solid rgba(59,130,246,0.3);',
            };
          ?>
          <span style="<?php echo $badge_color; ?> padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600;">
            <?php echo ucfirst($u['role']); ?>
          </span>
        </td>
        <td style="padding:12px 14px;color:#a0b8a0;">
          <?php echo $u['vendor_name'] ? htmlspecialchars($u['vendor_name']) : '<span style="color:#555;">—</span>'; ?>
        </td>
        <td style="padding:12px 14px;color:#666;font-size:12px;">
          <?php echo date('d M Y', strtotime($u['created_at'])); ?>
        </td>
        <td style="padding:12px 14px;">
          <?php if ($u['username'] !== 'admin' && $u['username'] !== $_SESSION['username']): ?>
          <form method="POST" style="display:flex;gap:8px;align-items:center;">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="user_id" value="<?php echo (int)$u['user_id']; ?>">
            <select name="role" style="padding:5px 8px;border-radius:6px;background:#1a2a1a;color:#fff;border:1px solid #2a4a2a;font-size:13px;">
              <option value="user"   <?php if ($u['role']==='user')   echo 'selected'; ?>>User</option>
              <option value="vendor" <?php if ($u['role']==='vendor') echo 'selected'; ?>>Vendor</option>
              <option value="admin"  <?php if ($u['role']==='admin')  echo 'selected'; ?>>Admin</option>
            </select>
            <button type="submit" class="btn btn-primary btn-sm" style="padding:5px 14px;font-size:13px;">Save</button>
          </form>
          <?php else: ?>
            <span style="color:#444;font-size:12px;">Protected</span>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
