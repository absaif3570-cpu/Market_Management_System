<?php
// header.php — included at top of every page
if (session_status() === PHP_SESSION_NONE) session_start();
$page_title  = $page_title ?? APP_NAME;
$is_loggedin = !empty($_SESSION['username']);
$is_admin    = ($is_loggedin && $_SESSION['role'] === 'admin');
$is_vendor   = ($is_loggedin && $_SESSION['role'] === 'vendor');

// Flash message pickup
$flash = flash_get();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($page_title); ?> — MMS</title>
  <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
  <link rel="stylesheet" href="assets/css/style.css">
  <script src="assets/js/main.js" defer></script>
</head>
<body<?php if ($is_loggedin) echo ' class="show-sidebar-btn"'; ?>>

<?php if ($is_loggedin): ?>
<!-- Sidebar toggle -->
<button id="sidebar-toggle" onclick="openSidebar()" title="Menu">&#9776;</button>

<!-- Overlay -->
<div class="sidebar-overlay" id="sidebar-overlay" onclick="closeSidebar()"></div>

<!-- Sidebar -->
<nav id="sidebar">
  <button id="sidebar-close" onclick="closeSidebar()">&#x2715;</button>

  <?php if ($is_admin): ?>
  <div class="sidebar-section">Vendors</div>
  <a href="?page=add_vendor"><span class="s-icon">➕</span> Add Vendor</a>
  <a href="?page=vendor_report"><span class="s-icon">👥</span> All Vendors</a>
  <a href="?page=search_vendor"><span class="s-icon">🔍</span> Search Vendor</a>

  <div class="sidebar-section">Shops</div>
  <a href="?page=add_shop"><span class="s-icon">➕</span> Add Shop</a>
  <a href="?page=manage_shop"><span class="s-icon">🏪</span> Manage Shops</a>

  <div class="sidebar-section">Payments</div>
  <a href="?page=add_payment"><span class="s-icon">➕</span> Add Payment</a>
  <a href="?page=rent_record"><span class="s-icon">💰</span> Rent Records</a>

  <div class="sidebar-section">System</div>
  <a href="?page=report"><span class="s-icon">📊</span> System Report</a>
  <a href="?page=backup"><span class="s-icon">💾</span> Backup Data</a>
  <?php endif; ?>

  <?php if ($is_vendor): ?>
  <div class="sidebar-section">My Account</div>
  <a href="?page=vendor_dashboard"><span class="s-icon">🏠</span> My Dashboard</a>
  <a href="?page=vendor_profile&id=<?php echo (int)($_SESSION['vendor_id'] ?? 0); ?>"><span class="s-icon">👤</span> My Profile</a>
  <?php endif; ?>

  <div class="sidebar-section">Account</div>
  <a href="logout.php" class="s-logout"><span class="s-icon">🚪</span> Logout</a>
</nav>
<?php endif; ?>

<!-- Navbar -->
<nav class="navbar">
  <div class="logo">
    <div class="logo-icon">🏪</div>
    <div>
      <?php echo htmlspecialchars(APP_NAME); ?>
      <div class="logo-sub">Market Administration</div>
    </div>
  </div>
  <div class="nav-links">
    <?php if ($is_loggedin): ?>
      <?php if ($is_admin): ?>
        <a href="index.php">🏠 <span>Dashboard</span></a>
        <a href="?page=vendor_report">👥 <span>Vendors</span></a>
        <a href="?page=manage_shop">🏪 <span>Shops</span></a>
        <a href="?page=rent_record">💰 <span>Payments</span></a>
      <?php else: ?>
        <a href="?page=vendor_dashboard">🏠 <span>Dashboard</span></a>
      <?php endif; ?>
      <a href="logout.php">🚪 <span>Logout</span> <span class="nav-badge"><?php echo htmlspecialchars($_SESSION['username']); ?></span></a>
    <?php else: ?>
      <a href="index.php">Home</a>
      <a href="login.php">Login</a>
      <a href="register.php">Register</a>
    <?php endif; ?>
  </div>
</nav>

<div class="page-wrap">

<?php
// Show flash message if present
if ($flash):
  $ftype = ($flash['type'] === 'success') ? 'alert-success' : 'alert-error';
  $ficon = ($flash['type'] === 'success') ? '✓' : '✕';
?>
<div class="alert-center" style="margin-top:8px;">
  <div class="alert <?php echo $ftype; ?>">
    <span><?php echo $ficon; ?></span>
    <?php echo htmlspecialchars($flash['msg']); ?>
  </div>
</div>
<?php endif; ?>
