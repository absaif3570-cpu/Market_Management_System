<?php
global $conn;
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { header('Location: index.php?page=vendor_report'); exit(); }

$stmt = mysqli_prepare($conn,
    "SELECT v.vendor_id, v.name, v.phone, v.created_at, s.shop_name, s.location, s.rent
     FROM vendors v JOIN shops s ON v.shop_id = s.shop_id
     WHERE v.vendor_id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$vendor = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$vendor) { flash_set('error', 'Vendor not found.'); header('Location: index.php?page=vendor_report'); exit(); }

// Payment summary
$ps = mysqli_prepare($conn,
    "SELECT COALESCE(SUM(amount),0) AS total_paid, COUNT(*) AS num_payments
     FROM payments WHERE vendor_id = ?");
mysqli_stmt_bind_param($ps, 'i', $id);
mysqli_stmt_execute($ps);
$pay_summary = mysqli_fetch_assoc(mysqli_stmt_get_result($ps));
mysqli_stmt_close($ps);

$due = (float)$vendor['rent'] - (float)$pay_summary['total_paid'];

// Recent payments
$pr = mysqli_prepare($conn,
    "SELECT payment_id, amount, payment_method, payment_date, note
     FROM payments WHERE vendor_id = ? ORDER BY payment_date DESC LIMIT 10");
mysqli_stmt_bind_param($pr, 'i', $id);
mysqli_stmt_execute($pr);
$payments = mysqli_stmt_get_result($pr);

$page_title = 'Vendor Profile — ' . $vendor['name'];
require __DIR__ . '/../includes/header.php';
?>
<h1 class="page-heading">Vendor <span>Profile</span></h1>

<div style="width:95%;max-width:860px;margin:0 auto;">

  <!-- Info card -->
  <div class="card report-box" style="margin-bottom:20px;">
    <div class="report-row">
      <span class="label">Name</span>
      <span class="value"><?php echo htmlspecialchars($vendor['name']); ?></span>
    </div>
    <div class="report-row">
      <span class="label">Phone</span>
      <span class="value"><?php echo htmlspecialchars($vendor['phone']); ?></span>
    </div>
    <div class="report-row">
      <span class="label">Shop</span>
      <span class="value"><?php echo htmlspecialchars($vendor['shop_name']); ?></span>
    </div>
    <div class="report-row">
      <span class="label">Location</span>
      <span class="value"><?php echo htmlspecialchars($vendor['location']); ?></span>
    </div>
    <div class="report-row">
      <span class="label">Monthly Rent</span>
      <span class="value"><?php echo number_format((float)$vendor['rent'], 2); ?> TK</span>
    </div>
    <div class="report-row">
      <span class="label">Total Paid</span>
      <span class="value green"><?php echo number_format((float)$pay_summary['total_paid'], 2); ?> TK</span>
    </div>
    <div class="report-row">
      <span class="label">Balance Due</span>
      <span class="value" style="color:<?php echo $due > 0 ? '#fca5a5' : '#6ee7b7'; ?>">
        <?php echo number_format($due, 2); ?> TK
        <?php if ($due <= 0): ?>
          <span class="badge badge-paid" style="margin-left:8px;">✓ Paid</span>
        <?php else: ?>
          <span class="badge badge-due" style="margin-left:8px;">⚠ Due</span>
        <?php endif; ?>
      </span>
    </div>
    <div class="report-row">
      <span class="label">Member Since</span>
      <span class="value" style="font-size:14px;font-family:var(--font-body);">
        <?php echo date('d M Y', strtotime($vendor['created_at'])); ?>
      </span>
    </div>
  </div>

  <!-- Recent payments -->
  <h2 style="font-family:var(--font-display);color:#fff;font-size:17px;margin-bottom:12px;padding-left:4px;">
    Recent Payments <span style="color:rgba(255,255,255,0.4);font-size:13px;font-weight:400;">(last 10)</span>
  </h2>
  <div class="table-wrap" style="margin-bottom:20px;">
    <table>
      <thead><tr><th>#</th><th>Amount</th><th>Method</th><th>Date</th><th>Note</th><th>Action</th></tr></thead>
      <tbody>
      <?php if (mysqli_num_rows($payments) === 0): ?>
        <tr><td colspan="6" style="text-align:center;padding:24px;color:rgba(255,255,255,0.35);">No payments recorded yet.</td></tr>
      <?php else: ?>
        <?php while ($p = mysqli_fetch_assoc($payments)): ?>
        <tr>
          <td><?php echo (int)$p['payment_id']; ?></td>
          <td style="font-weight:700;color:#6ee7b7;"><?php echo number_format((float)$p['amount'], 2); ?> TK</td>
          <td><?php echo htmlspecialchars($p['payment_method']); ?></td>
          <td><?php echo date('d M Y', strtotime($p['payment_date'])); ?></td>
          <td style="color:rgba(255,255,255,0.45);font-size:12px;"><?php echo htmlspecialchars($p['note'] ?? '—'); ?></td>
          <td>
            <div class="tbl-action">
              <a href="index.php?page=delete_payment&id=<?php echo (int)$p['payment_id']; ?>&vendor_id=<?php echo $id; ?>&csrf=<?php echo csrf_token(); ?>"
                 class="del" data-confirm="Delete this payment of <?php echo number_format((float)$p['amount'],2); ?> TK?">Delete</a>
            </div>
          </td>
        </tr>
        <?php endwhile; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="action-row" style="justify-content:flex-start;padding-left:0;">
    <?php if ($_SESSION['role'] === 'admin'): ?>
    <a href="index.php?page=edit_vendor&id=<?php echo $id; ?>" class="btn btn-secondary">✏️ Edit Vendor</a>
    <a href="index.php?page=add_payment&vendor_id=<?php echo $id; ?>" class="btn btn-primary">➕ Add Payment</a>
    <a href="index.php?page=vendor_report" class="btn btn-secondary">← All Vendors</a>
    <?php else: ?>
    <a href="index.php?page=vendor_dashboard" class="btn btn-secondary">← Dashboard</a>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
