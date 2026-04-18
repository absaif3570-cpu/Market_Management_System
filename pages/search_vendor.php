<?php
global $conn;
require_role('admin');
$page_title = 'Search Vendor';

$search  = trim($_GET['search'] ?? '');
$results = null;

if ($search !== '') {
    // Escape LIKE special chars to avoid wildcard injection
    $safe   = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search);
    $like   = "%$safe%";
    $stmt   = mysqli_prepare($conn,
        "SELECT v.vendor_id, v.name, v.phone, s.shop_name, s.location
         FROM vendors v
         JOIN shops s ON v.shop_id = s.shop_id
         WHERE v.name LIKE ? OR v.phone LIKE ?
         ORDER BY v.name ASC");
    mysqli_stmt_bind_param($stmt, 'ss', $like, $like);
    mysqli_stmt_execute($stmt);
    $results = mysqli_stmt_get_result($stmt);
}

require __DIR__ . '/../includes/header.php';
?>
<h1 class="page-heading">Search <span>Vendor</span></h1>

<div class="card form-box" style="width:600px;margin-bottom:20px;">
  <form method="GET" style="display:flex;gap:8px;align-items:flex-end;">
    <input type="hidden" name="page" value="search_vendor">
    <div style="flex:1;">
      <label>Search by name or phone</label>
      <input type="text" name="search" placeholder="e.g. Karim or 017…"
             value="<?php echo htmlspecialchars($search); ?>" required>
    </div>
    <button type="submit" class="btn btn-primary" style="margin-bottom:0;white-space:nowrap;">🔍 Search</button>
  </form>
</div>

<?php if ($results !== null): ?>
<div class="table-wrap">
<table>
  <thead><tr><th>#</th><th>Name</th><th>Phone</th><th>Shop</th><th>Location</th><th>Actions</th></tr></thead>
  <tbody>
  <?php
  $count = 0;
  while ($row = mysqli_fetch_assoc($results)):
      $count++;
  ?>
  <tr>
    <td><?php echo (int)$row['vendor_id']; ?></td>
    <td style="font-weight:600;color:#fff;"><?php echo htmlspecialchars($row['name']); ?></td>
    <td><?php echo htmlspecialchars($row['phone']); ?></td>
    <td><?php echo htmlspecialchars($row['shop_name']); ?></td>
    <td><?php echo htmlspecialchars($row['location']); ?></td>
    <td>
      <div class="tbl-action">
        <a href="index.php?page=edit_vendor&id=<?php echo (int)$row['vendor_id']; ?>" class="edit">Edit</a>
        <a href="index.php?page=vendor_profile&id=<?php echo (int)$row['vendor_id']; ?>" class="view">Profile</a>
        <a href="index.php?page=delete_vendor&id=<?php echo (int)$row['vendor_id']; ?>&csrf=<?php echo csrf_token(); ?>"
           class="del" data-confirm="Delete vendor '<?php echo htmlspecialchars($row['name'], ENT_QUOTES); ?>'?">Delete</a>
      </div>
    </td>
  </tr>
  <?php endwhile; ?>
  <?php if ($count === 0): ?>
    <tr><td colspan="6" style="text-align:center;padding:28px;color:rgba(255,255,255,0.4);">
      No vendors found for "<em><?php echo htmlspecialchars($search); ?></em>"
    </td></tr>
  <?php endif; ?>
  </tbody>
</table>
</div>
<p style="text-align:center;color:rgba(255,255,255,0.35);font-size:13px;margin-top:10px;">
  <?php echo $count; ?> result<?php echo $count !== 1 ? 's' : ''; ?> found
</p>
<?php endif; ?>

<div class="action-row">
  <a href="index.php?page=dashboard" class="btn btn-secondary">← Dashboard</a>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
