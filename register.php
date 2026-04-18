<?php
session_start();
require_once __DIR__ . '/config.php';

if (!empty($_SESSION['user_id'])) { header('Location: index.php'); exit(); }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $email === '' || $password === '') {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        // Check duplicate username
        $chk = mysqli_prepare($conn, "SELECT user_id FROM users WHERE username = ?");
        mysqli_stmt_bind_param($chk, 's', $username);
        mysqli_stmt_execute($chk);
        mysqli_stmt_store_result($chk);
        if (mysqli_stmt_num_rows($chk) > 0) {
            $error = 'That username is already taken. Choose another.';
        }
        mysqli_stmt_close($chk);

        if (!$error) {
            // Check duplicate email
            $chk2 = mysqli_prepare($conn, "SELECT user_id FROM users WHERE email = ?");
            mysqli_stmt_bind_param($chk2, 's', $email);
            mysqli_stmt_execute($chk2);
            mysqli_stmt_store_result($chk2);
            if (mysqli_stmt_num_rows($chk2) > 0) {
                $error = 'That email is already registered.';
            }
            mysqli_stmt_close($chk2);
        }

        if (!$error) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $ins  = mysqli_prepare($conn, "INSERT INTO users (username, email, password, role) VALUES (?,?,?,'user')");
            mysqli_stmt_bind_param($ins, 'sss', $username, $email, $hash);
            if (mysqli_stmt_execute($ins)) {
                mysqli_stmt_close($ins);
                header('Location: login.php?msg=' . urlencode('Account created! Please log in.')); exit();
            } else {
                $error = 'Registration failed: ' . mysqli_error($conn);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Register — MMS</title>
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
    <a href="login.php">Login</a>
  </div>
</nav>
<div class="page-wrap" style="display:flex;align-items:center;justify-content:center;padding-top:90px;">
<div class="card form-box">
  <h2><span class="emoji">📋</span>Create Account</h2>

  <?php if ($error): ?>
    <div class="alert alert-error"><span>✕</span> <?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>

  <form method="POST" action="register.php" data-validate>
    <?php echo csrf_field(); ?>
    <div class="form-group">
      <label>Username</label>
      <input type="text" name="username" placeholder="Choose a username" required
             value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
    </div>
    <div class="form-group">
      <label>Email Address</label>
      <input type="email" name="email" placeholder="your@email.com" required
             value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
    </div>
    <div class="form-group">
      <label>Password</label>
      <input type="password" name="password" placeholder="At least 6 characters" required>
    </div>
    <button type="submit" class="btn btn-primary btn-full">Create Account →</button>
  </form>
  <p class="form-footer">Already registered? <a href="login.php">Log in here</a></p>
</div>
</div>
</body></html>
