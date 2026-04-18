<?php
global $conn;
require_role('admin');
$page_title = 'System Report';

$vc = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS n FROM vendors"))['n']                  ?? 0;
$sc = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS n FROM shops"))['n']                    ?? 0;
$tr = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COALESCE(SUM(rent),0) AS n FROM shops"))['n']       ?? 0;
$tp = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COALESCE(SUM(amount),0) AS n FROM payments"))['n']  ?? 0;
$pc = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS n FROM payments"))['n']                 ?? 0;
$outstanding = (float)$tr - (float)$tp;

require __DIR__ . '/../includes/header.php';
?>
<h1 class="page-heading">System <span>Report</span></h1>

<div class="card report-box">

  <div class="report-row">
    <span class="label">📋 Total Vendors</span>
    <span class="value"><?php echo (int)$vc; ?></span>
  </div>
  <div class="report-row">
    <span class="label">🏪 Total Shops</span>
    <span class="value"><?php echo (int)$sc; ?></span>
  </div>
  <div class="report-row">
    <span class="label">💰 Monthly Rent Roll</span>
    <span class="value green"><?php echo number_format((float)$tr, 2); ?> TK</span>
  </div>
  <div class="report-row">
    <span class="label">✓ Total Collected</span>
    <span class="value green"><?php echo number_format((float)$tp, 2); ?> TK</span>
  </div>
  <div class="report-row">
    <span class="label">📄 Total Transactions</span>
    <span class="value"><?php echo (int)$pc; ?></span>
  </div>
  <div class="report-row" style="border-bottom:none;">
    <span class="label">⚠ Outstanding Balance</span>
    <span class="value" style="color:<?php echo $outstanding > 0 ? '#fca5a5' : '#6ee7b7'; ?>">
      <?php echo number_format(abs($outstanding), 2); ?> TK
      <?php echo $outstanding > 0 ? '<span class="badge badge-due" style="margin-left:8px;">Unpaid</span>'
                                  : '<span class="badge badge-paid" style="margin-left:8px;">All Clear</span>'; ?>
    </span>
  </div>

  <div style="text-align:center;padding-top:24px;border-top:1px solid rgba(255,255,255,0.07);margin-top:8px;">
    <p style="font-size:12px;color:rgba(255,255,255,0.3);margin-bottom:14px;">
      Generated: <?php echo date('d M Y, H:i:s'); ?>
    </p>
    <a href="index.php?page=pdf_report" class="btn btn-primary">
      📥 Download PDF Report
    </a>
  </div>
</div>

<div class="action-row">
  <a href="index.php?page=dashboard" class="btn btn-secondary">← Dashboard</a>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
