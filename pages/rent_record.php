<?php
global $conn;
require_role('admin');
$page_title = 'Rent Records';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="page-heading">Rent <span>Records</span></h1>

<div class="search-wrap">
  <input type="text" id="live-search" data-table="data-table" placeholder="Search by vendor or shop…">
</div>

<div class="table-wrap">
<table id="data-table">
  <thead>
    <tr>
      <th>Vendor</th><th>Shop</th><th>Monthly Rent</th>
      <th>Total Paid</th><th>Balance</th><th>Status</th><th>Actions</th>
    </tr>
  </thead>
  <tbody>
  <?php
  $sql = "
    SELECT
      v.vendor_id,
      v.name                               AS vendor_name,
      s.shop_name,
      s.rent,
      COALESCE(SUM(p.amount), 0)           AS total_paid,
      s.rent - COALESCE(SUM(p.amount), 0)  AS due
    FROM vendors v
    JOIN shops s ON v.shop_id = s.shop_id
    LEFT JOIN payments p ON v.vendor_id = p.vendor_id
    GROUP BY v.vendor_id, v.name, s.shop_name, s.rent
    ORDER BY due DESC, v.name ASC
  ";
  $result = mysqli_query($conn, $sql);
  if (!$result) {
      echo "<tr><td colspan='7' style='color:#fca5a5;padding:20px'>"
         . htmlspecialchars(mysqli_error($conn)) . "</td></tr>";
  } else {
      while ($row = mysqli_fetch_assoc($result)) {
          $due = (float)$row['due'];
  ?>
  <tr>
    <td style="font-weight:600;color:#fff;"><?php echo htmlspecialchars($row['vendor_name']); ?></td>
    <td><?php echo htmlspecialchars($row['shop_name']); ?></td>
    <td><?php echo number_format((float)$row['rent'], 2); ?> TK</td>
    <td style="color:#6ee7b7;"><?php echo number_format((float)$row['total_paid'], 2); ?> TK</td>
    <td class="due-cell"><?php echo number_format(abs($due), 2); ?> TK</td>
    <td>
      <?php if ($due <= 0): ?>
        <span class="badge badge-paid">✓ Paid</span>
      <?php else: ?>
        <span class="badge badge-due">⚠ Due</span>
      <?php endif; ?>
    </td>
    <td>
      <div class="tbl-action">
        <a href="index.php?page=add_payment&vendor_id=<?php echo (int)$row['vendor_id']; ?>" class="edit">+ Pay</a>
        <a href="index.php?page=vendor_profile&id=<?php echo (int)$row['vendor_id']; ?>" class="view">History</a>
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

<?php
$totals = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT
       COALESCE(SUM(s.rent), 0) AS total_rent,
       COALESCE(SUM(p.total), 0) AS total_paid
     FROM vendors v
     JOIN shops s ON v.shop_id = s.shop_id
     LEFT JOIN (SELECT vendor_id, SUM(amount) AS total FROM payments GROUP BY vendor_id) p
            ON v.vendor_id = p.vendor_id"));
$total_due = (float)$totals['total_rent'] - (float)$totals['total_paid'];
?>
<div style="width:95%;max-width:1200px;margin:0 auto 20px;display:flex;gap:12px;flex-wrap:wrap;">
  <div class="stat-card card" style="text-align:center;flex:1;min-width:140px;">
    <div class="stat-num"><?php echo number_format((float)$totals['total_rent'], 0); ?></div>
    <div class="stat-label">Total Monthly Rent (TK)</div>
  </div>
  <div class="stat-card card" style="text-align:center;flex:1;min-width:140px;">
    <div class="stat-num" style="color:#6ee7b7;"><?php echo number_format((float)$totals['total_paid'], 0); ?></div>
    <div class="stat-label">Total Collected (TK)</div>
  </div>
  <div class="stat-card card" style="text-align:center;flex:1;min-width:140px;">
    <div class="stat-num" style="color:<?php echo $total_due > 0 ? '#fca5a5' : '#6ee7b7'; ?>">
      <?php echo number_format(abs($total_due), 0); ?>
    </div>
    <div class="stat-label"><?php echo $total_due > 0 ? 'Total Outstanding (TK)' : 'Overpaid (TK)'; ?></div>
  </div>
</div>

<div class="action-row">
  <a href="index.php?page=add_payment" class="btn btn-primary">➕ Add Payment</a>
  <a href="index.php?page=dashboard" class="btn btn-secondary">← Dashboard</a>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
