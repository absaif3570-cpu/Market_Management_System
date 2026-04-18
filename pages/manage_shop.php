<?php
global $conn;
require_role('admin');
$page_title = 'Manage Shops';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="page-heading">Manage <span>Shops</span></h1>

<div class="search-wrap">
  <input type="text" id="live-search" data-table="data-table" placeholder="Search shops by name, owner or location…">
</div>

<div class="table-wrap">
<table id="data-table">
  <thead>
    <tr><th>#</th><th>Shop Name</th><th>Owner</th><th>Location</th><th>Monthly Rent</th><th>Vendors</th><th>Actions</th></tr>
  </thead>
  <tbody>
  <?php
  $sql = "SELECT s.shop_id, s.shop_name, s.owner_name, s.location, s.rent,
                 COUNT(v.vendor_id) AS vendor_count
          FROM shops s
          LEFT JOIN vendors v ON s.shop_id = v.shop_id
          GROUP BY s.shop_id
          ORDER BY s.shop_id DESC";
  $res = mysqli_query($conn, $sql);
  if (!$res) {
      echo "<tr><td colspan='7' style='color:#fca5a5;padding:20px'>"
         . htmlspecialchars(mysqli_error($conn)) . "</td></tr>";
  } else {
      while ($row = mysqli_fetch_assoc($res)) {
  ?>
  <tr>
    <td><?php echo (int)$row['shop_id']; ?></td>
    <td style="font-weight:600;color:#fff;"><?php echo htmlspecialchars($row['shop_name']); ?></td>
    <td><?php echo htmlspecialchars($row['owner_name']); ?></td>
    <td><?php echo htmlspecialchars($row['location']); ?></td>
    <td><?php echo number_format((float)$row['rent'], 2); ?> TK</td>
    <td>
      <?php if ($row['vendor_count'] > 0): ?>
        <span class="badge badge-paid"><?php echo (int)$row['vendor_count']; ?> vendor<?php echo $row['vendor_count'] > 1 ? 's' : ''; ?></span>
      <?php else: ?>
        <span style="color:rgba(255,255,255,0.3);font-size:12px;">None</span>
      <?php endif; ?>
    </td>
    <td>
      <div class="tbl-action">
        <a href="index.php?page=edit_shop&id=<?php echo (int)$row['shop_id']; ?>" class="edit">Edit</a>
        <?php if ((int)$row['vendor_count'] === 0): ?>
          <a href="index.php?page=delete_shop&id=<?php echo (int)$row['shop_id']; ?>&csrf=<?php echo csrf_token(); ?>"
             class="del" data-confirm="Delete shop '<?php echo htmlspecialchars($row['shop_name'], ENT_QUOTES); ?>'?">Delete</a>
        <?php else: ?>
          <span style="color:rgba(255,255,255,0.2);font-size:12px;padding:4px 8px;" title="Remove vendors first">Locked</span>
        <?php endif; ?>
      </div>
    </td>
  </tr>
  <?php
      }
  }
  ?>
  </tbody>
</table>
</div>

<div class="action-row">
  <a href="index.php?page=add_shop" class="btn btn-primary">➕ Add Shop</a>
  <a href="index.php?page=dashboard" class="btn btn-secondary">← Dashboard</a>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
