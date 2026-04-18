<?php
global $conn;
require_role('vendor');
$page_title = 'Vendor Dashboard';

$vendor_id = (int)($_SESSION['vendor_id'] ?? 0);
$vendor = null;
$pay_summary = ['total_paid' => 0, 'num_payments' => 0];
$due = 0;

if ($vendor_id > 0) {
    $stmt = mysqli_prepare($conn,
        "SELECT v.vendor_id, v.name, v.phone, s.shop_name, s.location, s.rent
         FROM vendors v JOIN shops s ON v.shop_id = s.shop_id
         WHERE v.vendor_id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $vendor_id);
    mysqli_stmt_execute($stmt);
    $vendor = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if ($vendor) {
        $ps = mysqli_prepare($conn,
            "SELECT COALESCE(SUM(amount),0) AS total_paid, COUNT(*) AS num_payments FROM payments WHERE vendor_id=?");
        mysqli_stmt_bind_param($ps, 'i', $vendor_id);
        mysqli_stmt_execute($ps);
        $pay_summary = mysqli_fetch_assoc(mysqli_stmt_get_result($ps));
        mysqli_stmt_close($ps);
        $due = (float)$vendor['rent'] - (float)$pay_summary['total_paid'];
    }
}

require __DIR__ . '/../includes/header.php';
?>
<h1 class="page-heading">Vendor <span>Dashboard</span></h1>

<?php if (!$vendor): ?>
  <div class="alert-center">
    <div class="alert alert-error">⚠ Your vendor profile is not linked to this account. Please contact the administrator.</div>
  </div>
<?php else: ?>

<div class="stat-strip" style="max-width:900px;margin-left:auto;margin-right:auto;">
  <div class="stat-card card" style="text-align:center;">
    <div class="stat-num"><?php echo number_format((float)$vendor['rent'], 0); ?></div>
    <div class="stat-label">Monthly Rent (TK)</div>
  </div>
  <div class="stat-card card" style="text-align:center;">
    <div class="stat-num"><?php echo number_format((float)$pay_summary['total_paid'], 0); ?></div>
    <div class="stat-label">Total Paid (TK)</div>
  </div>
  <div class="stat-card card" style="text-align:center;">
    <div class="stat-num" style="color:<?php echo $due > 0 ? '#fca5a5' : '#6ee7b7'; ?>">
      <?php echo number_format(abs($due), 0); ?>
    </div>
    <div class="stat-label"><?php echo $due > 0 ? 'Balance Due (TK)' : 'Overpaid (TK)'; ?></div>
  </div>
  <div class="stat-card card" style="text-align:center;">
    <div class="stat-num"><?php echo (int)$pay_summary['num_payments']; ?></div>
    <div class="stat-label">Payments Made</div>
  </div>
</div>

<div style="width:92%;max-width:700px;margin:0 auto;">
  <div class="card report-box">
    <div class="report-row">
      <span class="label">Your Name</span>
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
    <div class="report-row" style="border-bottom:none;padding-top:18px;">
      <span></span>
      <a href="index.php?page=vendor_profile&id=<?php echo $vendor_id; ?>" class="btn btn-primary btn-sm">
        View Full Profile →
      </a>
    </div>
  </div>
</div>

<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
