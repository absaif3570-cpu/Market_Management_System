<?php
global $conn;
$page_title = 'Market Management System';
require_once __DIR__ . '/../config.php';
require __DIR__ . '/../includes/header.php';
?>
<div class="home-hero">
  <h1>Manage Your Market<br><span class="accent">Smarter & Faster</span></h1>
  <p>Track vendors, shops, rent payments and generate reports — all in one clean dashboard.</p>
  <div class="home-actions">
    <a href="login.php" class="btn btn-primary" style="padding:14px 36px;font-size:16px;">Get Started →</a>
    <a href="register.php" class="btn btn-secondary" style="padding:14px 28px;font-size:16px;">Create Account</a>
  </div>

  <div class="stat-strip" style="margin-top:56px;max-width:600px;">
    <?php
    $vc = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS n FROM vendors"))['n'] ?? 0;
    $sc = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS n FROM shops"))['n'] ?? 0;
    $pc = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COALESCE(SUM(amount),0) AS n FROM payments"))['n'] ?? 0;
    ?>
    <div class="stat-card card" style="text-align:center;">
      <div class="stat-num"><?php echo (int)$vc; ?></div>
      <div class="stat-label">Vendors</div>
    </div>
    <div class="stat-card card" style="text-align:center;">
      <div class="stat-num"><?php echo (int)$sc; ?></div>
      <div class="stat-label">Shops</div>
    </div>
    <div class="stat-card card" style="text-align:center;">
      <div class="stat-num"><?php echo number_format((float)$pc, 0); ?></div>
      <div class="stat-label">TK Collected</div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
