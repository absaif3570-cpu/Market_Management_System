<?php
session_start();
require_once __DIR__ . '/config.php';

// Already logged in
if (!empty($_SESSION['user_id'])) {
    header('Location: index.php'); exit();
}

$error = '';
$msg   = htmlspecialchars($_GET['msg'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Both fields are required.';
    } else {
        $stmt = mysqli_prepare($conn, "SELECT user_id, username, password, role FROM users WHERE username = ?");
        mysqli_stmt_bind_param($stmt, 's', $username);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if ($row && password_verify($password, $row['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']  = $row['user_id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role']     = $row['role'];

            if ($row['role'] === 'vendor') {
                $vs = mysqli_prepare($conn, "SELECT vendor_id FROM vendors WHERE user_id = ? LIMIT 1");
                if ($vs) {
                    mysqli_stmt_bind_param($vs, 'i', $row['user_id']);
                    mysqli_stmt_execute($vs);
                    $vr = mysqli_fetch_assoc(mysqli_stmt_get_result($vs));
                    $_SESSION['vendor_id'] = $vr['vendor_id'] ?? null;
                    mysqli_stmt_close($vs);
                }
                header('Location: index.php?page=vendor_dashboard'); exit();
            }
            header('Location: index.php?page=dashboard'); exit();
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Login — MMS</title>
  <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
  <link rel="stylesheet" href="assets/css/style.css">
  <script src="assets/js/main.js" defer></script>
</head>
<body>
<nav class="navbar">
  <div class="logo">
    <div class="logo-icon">🏪</div>
    <div>Market Management System<div class="logo-sub">Market Administration</div></div>
  </div>
  <div class="nav-links">
    <a href="index.php">Home</a>
    <a href="register.php">Register</a>
  </div>
</nav>
<div class="page-wrap" style="display:flex;align-items:center;justify-content:center;padding-top:100px;">
<div class="card form-box">
  <h2><span class="emoji">🔐</span>Welcome Back</h2>

  <?php if ($msg): ?>
    <div class="alert alert-success"><span>✓</span> <?php echo $msg; ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert alert-error"><span>✕</span> <?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>

  <form method="POST" action="login.php" data-validate>
    <?php echo csrf_field(); ?>
    <div class="form-group">
      <label>Username</label>
      <input type="text" name="username" placeholder="Enter your username" required
             value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
    </div>
    <div class="form-group">
      <label>Password</label>
      <input type="password" name="password" placeholder="Enter your password" required>
    </div>
    <button type="submit" class="btn btn-primary btn-full">Login →</button>
  </form>
  <p class="form-footer">No account? <a href="register.php">Register here</a></p>
</div>
</div>
</body></html>
