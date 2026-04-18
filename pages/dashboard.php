<?php
global $conn;
require_role('admin');
$page_title = 'Admin Dashboard';
require __DIR__ . '/../includes/header.php';

// Stats
$vc  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS n FROM vendors"))['n']         ?? 0;
$sc  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS n FROM shops"))['n']           ?? 0;
$tr  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COALESCE(SUM(rent),0) AS n FROM shops"))['n'] ?? 0;
$tp  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COALESCE(SUM(amount),0) AS n FROM payments"))['n'] ?? 0;
?>

<h1 class="page-heading">Admin <span>Dashboard</span></h1>

<!-- Stat strip -->
<div class="stat-strip">
  <div class="stat-card card">
    <div class="stat-num"><?php echo (int)$vc; ?></div>
    <div class="stat-label">Total Vendors</div>
  </div>
  <div class="stat-card card">
    <div class="stat-num"><?php echo (int)$sc; ?></div>
    <div class="stat-label">Total Shops</div>
  </div>
  <div class="stat-card card">
    <div class="stat-num"><?php echo number_format((float)$tr, 0); ?></div>
    <div class="stat-label">Monthly Rent (TK)</div>
  </div>
  <div class="stat-card card">
    <div class="stat-num"><?php echo number_format((float)$tp, 0); ?></div>
    <div class="stat-label">Payments Collected (TK)</div>
  </div>
</div>

<!-- Dashboard grid -->
<div class="dash-grid">

  <div class="dash-card card">
    <div class="dash-icon">👥</div>
    <div class="dash-title">Vendors</div>
    <div class="dash-links">
      <a href="index.php?page=add_vendor">➕ Add Vendor</a>
      <a href="index.php?page=vendor_report">📋 All Vendors</a>
      <a href="index.php?page=search_vendor">🔍 Search Vendor</a>
    </div>
  </div>

  <div class="dash-card card">
    <div class="dash-icon">🏪</div>
    <div class="dash-title">Shops</div>
    <div class="dash-links">
      <a href="index.php?page=add_shop">➕ Add Shop</a>
      <a href="index.php?page=manage_shop">🗂️ Manage Shops</a>
    </div>
  </div>

  <div class="dash-card card">
    <div class="dash-icon">💰</div>
    <div class="dash-title">Payments</div>
    <div class="dash-links">
      <a href="index.php?page=add_payment">➕ Add Payment</a>
      <a href="index.php?page=rent_record">📄 Rent Records</a>
    </div>
  </div>

  <div class="dash-card card">
    <div class="dash-icon">📊</div>
    <div class="dash-title">Reports</div>
    <div class="dash-links">
      <a href="index.php?page=report">📊 System Report</a>
      <a href="index.php?page=pdf_report">📥 Download PDF</a>
    </div>
  </div>

  <div class="dash-card card">
    <div class="dash-icon">💾</div>
    <div class="dash-title">Backup</div>
    <div class="dash-links">
      <a href="index.php?page=backup&type=vendors">💾 Export Vendors</a>
      <a href="index.php?page=backup&type=shops">💾 Export Shops</a>
      <a href="index.php?page=backup&type=payments">💾 Export Payments</a>
    </div>
  </div>

  <div class="dash-card card">
    <div class="dash-icon">⚙️</div>
    <div class="dash-title">Account</div>
    <div class="dash-links">
      <a href="logout.php" style="color:#fca5a5;">🚪 Logout</a>
    </div>
  </div>

</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
