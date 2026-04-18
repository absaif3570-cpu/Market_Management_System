<?php
global $conn;
require_role('admin');
$page_title = 'Vendor Report';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="page-heading">All <span>Vendors</span></h1>

<div class="search-wrap">
  <input type="text" id="live-search" data-table="data-table" placeholder="Search by name, phone or shop…">
</div>

<div class="table-wrap">
<table id="data-table">
  <thead>
    <tr>
      <th>#</th><th>Name</th><th>Phone</th><th>Shop</th><th>Added</th><th>Actions</th>
    </tr>
  </thead>
  <tbody>
  <?php
  $sql = "SELECT v.vendor_id, v.name, v.phone, s.shop_name, v.created_at
          FROM vendors v
          JOIN shops s ON v.shop_id = s.shop_id
          ORDER BY v.vendor_id DESC";
  $res = mysqli_query($conn, $sql);
  if (!$res) {
      echo "<tr><td colspan='6' style='color:#fca5a5;padding:20px'>Query error: "
         . htmlspecialchars(mysqli_error($conn)) . "</td></tr>";
  } else {
      while ($row = mysqli_fetch_assoc($res)) {
  ?>
  <tr>
    <td><?php echo (int)$row['vendor_id']; ?></td>
    <td style="font-weight:600;color:#fff;"><?php echo htmlspecialchars($row['name']); ?></td>
    <td><?php echo htmlspecialchars($row['phone']); ?></td>
    <td><?php echo htmlspecialchars($row['shop_name']); ?></td>
    <td style="color:rgba(255,255,255,0.4);font-size:12px;"><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
    <td>
      <div class="tbl-action">
        <a href="index.php?page=edit_vendor&id=<?php echo (int)$row['vendor_id']; ?>" class="edit">Edit</a>
        <a href="index.php?page=vendor_profile&id=<?php echo (int)$row['vendor_id']; ?>" class="view">Profile</a>
        <a href="index.php?page=delete_vendor&id=<?php echo (int)$row['vendor_id']; ?>&csrf=<?php echo csrf_token(); ?>"
           class="del" data-confirm="Delete vendor '<?php echo htmlspecialchars($row['name'], ENT_QUOTES); ?>'? All their payments will also be deleted.">Delete</a>
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
  <a href="index.php?page=add_vendor" class="btn btn-primary">➕ Add Vendor</a>
  <a href="index.php?page=dashboard" class="btn btn-secondary">← Dashboard</a>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
